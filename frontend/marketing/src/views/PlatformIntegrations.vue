<script setup>
// Integrations → Integrations: connect a store, website, CRM, email-marketing or payments account and import its customers as People.
// Several stores/accounts of the same platform can be connected; each has its own name, list, tags and schedule.
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { errorMessage, responseMessage } from '../api'
import Modal from '../components/Modal.vue'
import IntegrationDialog from '../components/IntegrationDialog.vue'
import { TILE, tileStyle } from '../integrationTiles'
import InfoTip from '../components/InfoTip.vue'
import FilterBar from '../components/FilterBar.vue'

const providers = ref([])
const connections = ref([])
const lists = ref([])
const loading = ref(true)
const msg = ref('')
const error = ref('')
const dlg = ref(null) // { mode: 'new' | 'edit', provider, id }
const route = useRoute()
const router = useRouter()

const CATEGORIES = [['store', 'Online stores'], ['marketplace', 'Marketplaces'], ['website', 'Websites'], ['crm', 'CRM'], ['email', 'Email marketing'], ['payments', 'Payments'], ['social', 'Social media'], ['messaging', 'Messaging channels']]
// Help: everything needed to connect one platform, in a window. The steps, fields and notes come from the platform's own definition;
// the links go to the official page where its keys or app are created.
const helpOf = ref(null)
const HELP_LINKS = {
  shopify: ['https://help.shopify.com/en/manual/apps/app-types/custom-apps', 'Shopify: custom apps and access tokens'],
  woocommerce: ['https://woocommerce.com/document/woocommerce-rest-api/', 'WooCommerce: REST API keys'],
  wordpress: ['https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/', 'WordPress: application passwords'],
  magento: ['https://developer.adobe.com/commerce/webapi/get-started/authentication/gs-authentication-token/', 'Magento: integration access tokens'],
  bigcommerce: ['https://developer.bigcommerce.com/docs/start/authentication/api-accounts', 'BigCommerce: API accounts'],
  hubspot: ['https://developers.hubspot.com/docs/api/private-apps', 'HubSpot: private apps'],
  mailchimp: ['https://mailchimp.com/help/about-api-keys/', 'Mailchimp: API keys'],
  klaviyo: ['https://developers.klaviyo.com/en/docs/authenticate_', 'Klaviyo: API authentication'],
  brevo: ['https://app.brevo.com/settings/keys/api', 'Brevo: API keys page'],
  stripe: ['https://dashboard.stripe.com/apikeys', 'Stripe: API keys page'],
  pipedrive: ['https://pipedrive.readme.io/docs/how-to-find-the-api-token', 'Pipedrive: finding the API token'],
  activecampaign: ['https://help.activecampaign.com/hc/en-us/articles/207317590', 'ActiveCampaign: API URL and key'],
  mailerlite: ['https://developers.mailerlite.com/docs/#authentication', 'MailerLite: authentication'],
  kit: ['https://developers.kit.com/', 'Kit: developer documentation'],
  square: ['https://developer.squareup.com/apps', 'Square: developer dashboard'],
  facebook: ['https://developers.facebook.com/docs/marketing-api/guides/lead-ads/retrieving', 'Meta: retrieving lead ads leads'],
  instagram: ['https://developers.facebook.com/docs/marketing-api/guides/lead-ads/retrieving', 'Meta: retrieving lead ads leads'],
  linkedin: ['https://learn.microsoft.com/en-us/linkedin/marketing/lead-sync/leadsync', 'LinkedIn: Lead Sync API'],
  whatsapp: ['https://developers.facebook.com/docs/whatsapp/cloud-api/get-started', 'WhatsApp Cloud API: get started'],
  messenger: ['https://developers.facebook.com/docs/messenger-platform/get-started', 'Messenger Platform: get started'],
  x: ['https://developer.x.com/en/portal/dashboard', 'X Developer Portal'],
  etsy: ['https://developers.etsy.com/documentation/essentials/authentication', 'Etsy Open API: authentication'],
  ebay: ['https://developer.ebay.com/api-docs/static/oauth-authorization-code-grant.html', 'eBay: authorization code grant'],
  amazon: ['https://developer-docs.amazon.com/sp-api/docs/access-orders-pii', 'Amazon SP-API: access orders PII'],
  walmart: ['https://developer.walmart.com/us-marketplace/docs/get-all-orders', 'Walmart Marketplace: get all orders'],
  mercadolibre: ['https://developers.mercadolibre.com.ar/en_us/manage-sales', 'Mercado Libre: manage sales'],
  daraz: ['https://open.daraz.com/', 'Daraz Open Platform'],
}
const helpLink = (p) => HELP_LINKS[p.key]
const copied = ref('') // which "Copy" button of the help window was just used
async function copyText(text, which) {
  try { await navigator.clipboard.writeText(text); copied.value = which; setTimeout(() => (copied.value = ''), 1500) } catch (e) { /* clipboard blocked: the text is shown, so it can be selected by hand */ }
}
// the .env lines for the app's client id and secret, as examples (the server sends placeholder values, never the real ones)
const envExample = (p) => (p.auto_connect?.env || []).map((e) => e.name + '=' + e.example).join('\n')
const categoryLabel = (key) => CATEGORIES.find((c) => c[0] === key)?.[1] || key
const f = reactive({ q: '', category: '', method: '' }) // filters of the table: search, category, one-click or pasted details
const dirty = computed(() => !!(f.q || f.category || f.method))
const clear = () => Object.assign(f, { q: '', category: '', method: '' })
const hasOneClick = (p) => !!p.auto_connect?.available
const rows = computed(() => {
  const q = f.q.trim().toLowerCase()
  return [...providers.value].sort((a, b) => a.label.localeCompare(b.label, undefined, { sensitivity: 'base' })) // by name, A to Z
    .filter((p) => (!f.category || p.category === f.category) && (!f.method || (f.method === 'oneclick') === hasOneClick(p)) && (!q || (p.label + ' ' + p.description).toLowerCase().includes(q)))
})
const EVERY = [[0, 'Only when I press Sync now'], [15, 'Every 15 minutes'], [60, 'Every hour'], [360, 'Every 6 hours'], [1440, 'Every day']]
const everyLabel = (m) => (EVERY.find((e) => e[0] === m)?.[1] || '').replace('Only when I press Sync now', 'Manual only')

const providerOf = (key) => providers.value.find((p) => p.key === key)

const load = async () => {
  connections.value = (await api.get('/integrations')).data
}
const anyRunning = computed(() => connections.value.some((c) => c.running))

let timer
function poll() {
  clearInterval(timer)
  timer = setInterval(() => { if (anyRunning.value) load().catch(() => {}) }, 3000)
}

// the window itself is IntegrationDialog; this page only opens it for a new connection (settings are on the Connected page)
function open(mode, p, c = null, prefill = null) { dlg.value = { mode, provider: p, connection: c, prefill } }
function onDone(m) { msg.value = m; dlg.value = null; load() }

async function syncNow(c, full = false) {
  error.value = msg.value = ''
  if (full && !confirm('Read every customer again? People who are already here are not duplicated or changed, only completed.')) return
  try {
    msg.value = responseMessage(await api.post(`/integrations/${c.id}/sync`, { full }), 'Import started.')
    await load()
  } catch (e) { error.value = errorMessage(e) }
}

async function check(c) {
  error.value = msg.value = ''
  try { msg.value = responseMessage(await api.post(`/integrations/${c.id}/check`), 'The connection works.') } catch (e) { error.value = errorMessage(e) }
  load()
}

async function disconnect(c) {
  if (!confirm(`Disconnect “${c.name}”? The people already imported stay in People; nothing more is imported.`)) return
  await api.delete(`/integrations/${c.id}`)
  msg.value = 'Disconnected.'
  load()
}

const summary = (s) => (s ? `${s.fetched} read · ${s.created} new · ${s.updated} updated · ${s.skipped} unchanged or skipped` + (s.exported || s.export_failed ? ` · ${s.exported} sent` + (s.export_failed ? ` (${s.export_failed} refused)` : '') : '') : '')
const DIRECTION = { import: 'Import only', export: 'Send only', both: 'Both ways' }

onMounted(async () => {
  // back from the platform's approval page
  const q = route.query
  if (q.connected) msg.value = `Connected “${q.connected}”. Importing customers now. Change its list, tags, direction and schedule on the Connected page (⋮ → Edit Configuration).`
  else if (q.reconnected) msg.value = `Reconnected “${q.reconnected}”.`
  else if (q.woocommerce) msg.value = 'If you approved access in WooCommerce, the shop is being connected and will appear here in a moment.'
  if (q.oauth_error) error.value = q.oauth_error
  if (Object.keys(q).length) router.replace({ query: {} })
  try {
    ;[providers.value, lists.value] = (await Promise.all([api.get('/integrations/providers'), api.get('/lists')])).map((r) => r.data)
    await load()
    if (q.connect && providerOf(q.connect)) open('new', providerOf(q.connect), null, { name: q.name, url: q.url }) // “Connect again” on a disconnected integration
    if (q.edit) router.replace(`/integrations/connected/${q.edit}`) // Settings has its own page
  } finally { loading.value = false }
  if (q.woocommerce) setTimeout(() => load().catch(() => {}), 4000)
  poll()
})
onBeforeUnmount(() => clearInterval(timer))
</script>

<template>
  <div class="head"><h1>Integrations</h1><div class="row"><router-link to="/integrations/history" class="btn ghost">Sync history</router-link></div></div>
  <p class="intro">Connect a store, marketplace, website, CRM, email-marketing or payments account and bring its customers into <b>People</b> automatically. You can connect several stores or accounts of the same platform (for example three Shopify stores); each keeps its own name, list, tags and schedule. Details you enter are stored encrypted, and only customers who allowed marketing are subscribed unless you choose otherwise.</p>
  <div v-if="msg" class="alert ok" @click="msg = ''">{{ msg }}</div>
  <div v-if="error" class="alert err" @click="error = ''">{{ error }}</div>

  <p v-if="connections.length" class="connected-note"><b>{{ connections.length }}</b> connected. <router-link to="/integrations/connected">View connected integrations</router-link> to sync, change settings or disconnect.</p>

  <h2 class="sec">Connect a platform</h2>
  <p v-if="loading" class="empty">Loading…</p>
  <template v-else>
    <FilterBar :dirty="dirty" @clear="clear">
      <input v-model="f.q" type="search" placeholder="Search name or description…" />
      <select v-model="f.category"><option value="">All categories</option><option v-for="[key, label] in CATEGORIES" :key="key" :value="key">{{ label }}</option></select>
      <select v-model="f.method"><option value="">Any way to connect</option><option value="oneclick">One-click</option><option value="manual">Paste details</option></select>
      <template #end>{{ rows.length }} of {{ providers.length }} platforms</template>
    </FilterBar>
    <div class="card tbl">
      <table>
        <thead><tr><th class="c-logo">Logo</th><th>Name</th><th>Category</th><th>Description</th><th class="c-act"></th></tr></thead>
        <tbody>
          <tr v-for="p in rows" :key="p.key">
            <td class="c-logo"><span class="tile" :style="tileStyle(p.key)">{{ TILE[p.key]?.[1] || '?' }}</span></td>
            <td>
              <b>{{ p.label }}</b><InfoTip :text="p.description" />
              <span v-if="p.auto_connect?.available" class="oneclick" title="Approve access on the platform's own page: nothing to copy and paste">One-click</span>
            </td>
            <td>{{ categoryLabel(p.category) }}</td>
            <td class="c-desc">{{ p.description }}</td>
            <td class="c-act"><button class="btn ghost sm" @click="helpOf = p">Help</button> <button class="btn sm" @click="open('new', p)">Connect</button></td>
          </tr>
          <tr v-if="!rows.length"><td colspan="5" class="empty">No platform matches these filters.</td></tr>
        </tbody>
      </table>
    </div>
  </template>
  <p class="more">Connecting something else? Another system can add and update people with an <router-link to="/settings/api">API key</router-link>.</p>

  <Modal v-if="helpOf" :title="'How to connect ' + helpOf.label" wide @close="helpOf = null">
    <div class="help">
      <p class="lead">{{ helpOf.description }}</p>

      <h4>Before you start</h4>
      <ul>
        <li>You need an account on <b>{{ helpOf.label }}</b> with permission to create keys or approve apps.</li>
        <li v-if="helpOf.auto_connect?.available"><b>One-click is available:</b> choose <i>Connect</i>, then approve access on {{ helpOf.label }}'s own page. Nothing to copy and paste.</li>
        <li v-else-if="helpOf.auto_connect"><b>One-click is supported</b> but not switched on yet for this server (the platform owner must add the app's client ID and secret). Until then, paste the details below.</li>
        <li v-else>This platform is connected by pasting the details below. They are stored encrypted.</li>
        <li v-if="helpOf.uses_url">You will also type the address of your site or store.</li>
      </ul>

      <template v-if="helpOf.auto_connect?.callback_url">
        <h4>Callback URL (redirect URI)</h4>
        <p>When you create the app on {{ helpOf.label }}'s developer page, add this as its <b>redirect / callback URL</b>. It must match exactly, or {{ helpOf.label }} will refuse the sign-in.</p>
        <div class="cb">
          <code>{{ helpOf.auto_connect.callback_url }}</code>
          <button type="button" class="btn ghost sm" @click="copyText(helpOf.auto_connect.callback_url, 'url')">{{ copied === 'url' ? 'Copied ✓' : 'Copy' }}</button>
        </div>

        <template v-if="helpOf.auto_connect.scopes?.length">
          <h4>Permissions to switch on</h4>
          <p>Switch these on for the app at {{ helpOf.label }} (its scopes or permissions). {{ helpOf.label }} only shows, and only grants, the ones the app has enabled: if one is missing here, the connection is made but that part fails.</p>
          <div class="cb"><div class="scopes"><code v-for="s in helpOf.auto_connect.scopes" :key="s">{{ s }}</code></div><button type="button" class="btn ghost sm" @click="copyText(helpOf.auto_connect.scopes.join(' '), 'scopes')">{{ copied === 'scopes' ? 'Copied ✓' : 'Copy' }}</button></div>
        </template>

        <h4>Client ID and secret</h4>
        <p>{{ helpOf.label }} gives you a <b>client ID</b> and a <b>client secret</b> when the app is created. Put them in the server's <code>.env</code> file, then run <code>php artisan config:clear</code>. Example (use your own values):</p>
        <div class="cb">
          <pre>{{ envExample(helpOf) }}</pre>
          <button type="button" class="btn ghost sm" @click="copyText(envExample(helpOf), 'env')">{{ copied === 'env' ? 'Copied ✓' : 'Copy' }}</button>
        </div>
        <p class="note">Keep the secret private: it stays on the server and is never shown in the app.</p>
      </template>

      <h4>What you will enter</h4>
      <table class="hf">
        <thead><tr><th>Field</th><th>Where to find it</th><th>Example</th></tr></thead>
        <tbody>
          <tr v-for="f in helpOf.fields" :key="f.key">
            <td><b>{{ f.label }}</b><br /><small>{{ f.secret ? 'secret: stored encrypted' : f.required === false ? 'optional' : 'required' }}</small></td>
            <td>{{ f.help || '—' }}</td>
            <td><code v-if="f.placeholder">{{ f.placeholder }}</code></td>
          </tr>
        </tbody>
      </table>

      <h4>Step by step</h4>
      <ol><li v-for="(st, i) in helpOf.steps" :key="i">{{ st }}</li></ol>
      <p v-if="helpOf.messaging" class="note">This account sends campaign messages. Nothing is imported from it: after connecting, it appears in the <b>Channel</b> list when you create a campaign.</p>

      <h4>Who gets subscribed, and what is imported</h4>
      <p>{{ helpOf.consent_note }}</p>
      <p v-if="helpOf.supports_export" class="note">This connection can also <b>send your People to {{ helpOf.label }}</b>. Choose the direction (import, send or both) when you connect.</p>

      <template v-if="helpLink(helpOf)">
        <h4>Official documentation</h4>
        <p><a :href="helpLink(helpOf)[0]" target="_blank" rel="noopener noreferrer">{{ helpLink(helpOf)[1] }} ↗</a></p>
      </template>

      <h4>If it does not connect</h4>
      <ul>
        <li>Copy the key again and check there is no space before or after it.</li>
        <li>Check that the key has permission to read what is listed above (for example customers, or leads).</li>
        <li>Keys of a test or sandbox account only work with that account's data.</li>
        <li>The message shown after pressing Connect comes from {{ helpOf.label }} itself and says what it refused.</li>
      </ul>
      <div class="row" style="margin-top: 16px"><button class="btn" @click="open('new', helpOf); helpOf = null">Connect {{ helpOf.label }}</button><button class="btn ghost" @click="helpOf = null">Close</button></div>
    </div>
  </Modal>

  <IntegrationDialog v-if="dlg" :key="dlg.provider.key + (dlg.connection?.id || 'new')" :provider="dlg.provider" :mode="dlg.mode" :connection="dlg.connection" :lists="lists" :prefill="dlg.prefill" @close="dlg = null" @done="onDone" />
</template>

<style scoped>
.connected-note { margin: 4px 0 0; padding: 10px 14px; background: #f1f8f1; border: 1px solid #cfe5cf; border-radius: 8px; }
.check span { line-height: 1.3; }
.oneclick { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #2e7d32; background: #e8f5e9; border-radius: 999px; padding: 2px 8px; vertical-align: middle; margin-left: 4px; }
.intro { color: #6b7280; margin: -8px 0 18px; }
.sec { font-size: 18px; margin: 26px 0 12px; }
.conns { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 16px; }
.conn .top { display: flex; align-items: center; gap: 12px; }
.conn .who { flex: 1; min-width: 0; } .conn .who small { color: #6b7280; word-break: break-all; }
.tile { flex: none; display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 10px; color: #fff; font-weight: 800; font-size: 14px; }
.tile.big { width: 52px; height: 52px; font-size: 17px; margin-bottom: 8px; }
.err-line { margin: 12px 0 0; padding: 8px 10px; border-radius: 8px; background: #fdecea; color: #b71c1c; font-size: 13.5px; }
.facts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin: 14px 0 6px; }
.facts dt { font-size: 11.5px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; } .facts dd { margin: 2px 0 0; font-size: 14px; }
.last { margin: 0 0 8px; color: #6b7280; }
.btns { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
.cat { font-size: 13px; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; margin: 18px 0 10px; }
.tbl { padding: 0; overflow: hidden; }
.tbl table { width: 100%; border-collapse: collapse; }
.tbl th, .tbl td { padding: 10px 14px; text-align: left; border-bottom: 1px solid #eef0f3; vertical-align: middle; }
.tbl th { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; background: #fafbfc; }
.c-logo { width: 64px; }
.c-desc { color: #4b5563; font-size: 13px; line-height: 1.4; max-width: 420px; }
.help h4 { margin: 18px 0 6px; font-size: 13px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
.help .lead { font-size: 15px; margin: 0; }
.help ul, .help ol { margin: 0; padding-left: 20px; display: grid; gap: 6px; }
.help .note { background: #eef2ff; border-radius: 8px; padding: 8px 12px; margin-top: 10px; }
.help .cb { display: flex; align-items: center; gap: 10px; background: #f3f4f6; border-radius: 8px; padding: 8px 12px; margin-top: 8px; }
.help .scopes { flex: 1; display: flex; flex-wrap: wrap; gap: 6px; }
.help .scopes code { background: #fff; border: 1px solid #e0e3f0; border-radius: 6px; padding: 2px 8px; font-size: 13px; }
.help .cb code, .help .cb pre { flex: 1; word-break: break-all; font-size: 13px; }
.help .cb pre { margin: 0; white-space: pre-wrap; font-family: ui-monospace, Menlo, Consolas, monospace; }
.help p code { background: #f3f4f6; padding: 1px 5px; border-radius: 4px; }
.hf { width: 100%; border-collapse: collapse; font-size: 13px; }
.hf th, .hf td { text-align: left; vertical-align: top; padding: 8px 10px; border-bottom: 1px solid #eef0f3; }
.hf th { color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
.hf code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; word-break: break-all; }
.c-act { width: 160px; text-align: right !important; }
.provs { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px; }
.prov { display: flex; flex-direction: column; gap: 6px; } .prov h3 { margin: 0; } .prov p { margin: 0 0 8px; color: #6b7280; font-size: 14px; flex: 1; }
.more { margin-top: 20px; color: #6b7280; font-size: 14px; }
</style>
