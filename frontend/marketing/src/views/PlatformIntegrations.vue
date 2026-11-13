<script setup>
// Integrations → Connections: connect a store, website, CRM, email-marketing or payments account and import its customers as People.
// Several stores/accounts of the same platform can be connected; each has its own name, list, tags and schedule.
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { errorMessage, responseMessage } from '../api'
import Modal from '../components/Modal.vue'
import InfoTip from '../components/InfoTip.vue'

const providers = ref([])
const connections = ref([])
const lists = ref([])
const loading = ref(true)
const msg = ref('')
const error = ref('')
const dlg = ref(null) // { mode: 'new' | 'edit', provider, id }
const busy = ref(false)
const dlgError = ref('')
const route = useRoute()
const router = useRouter()
const manual = ref(false) // paste the details instead of the one-click connect

const TILE = {
  shopify: ['#5e8e3e', 'S'], woocommerce: ['#7f54b3', 'Woo'], wordpress: ['#21759b', 'WP'], magento: ['#f26322', 'M'],
  hubspot: ['#ff7a59', 'HS'], mailchimp: ['#ffe01b', 'MC', '#241c15'], klaviyo: ['#232426', 'K'], brevo: ['#0b996e', 'B'], stripe: ['#635bff', 'St'],
  bigcommerce: ['#34313f', 'BC'], ecwid: ['#0f4c81', 'Ec'], ghost: ['#15171a', 'G'], squarespace: ['#111111', 'Sq'], webflow: ['#146ef5', 'Wf'], wix: ['#0c6efc', 'Wix'],
  pipedrive: ['#017737', 'Pd'], salesforce: ['#00a1e0', 'SF'], activecampaign: ['#356ae6', 'AC'], close: ['#4a4de6', 'Cl'],
  facebook: ['#1877f2', 'f'], instagram: ['#d62976', 'Ig'], linkedin: ['#0a66c2', 'in'], whatsapp: ['#25d366', 'Wa'], messenger: ['#0084ff', 'Ms'], x: ['#000000', 'X'],
  mailerlite: ['#09c269', 'ML'], kit: ['#fb6970', 'Kit'], square: ['#3e4348', 'Sq'], paddle: ['#2b3a67', 'Pa'], chargebee: ['#ff3300', 'Cb'], gocardless: ['#ffdb00', 'GC', '#1b1b1b'],
}
const CATEGORIES = [['store', 'Online stores'], ['website', 'Websites'], ['crm', 'CRM'], ['email', 'Email marketing'], ['payments', 'Payments'], ['social', 'Social media'], ['messaging', 'Messaging channels']]
const categoryLabel = (key) => CATEGORIES.find((c) => c[0] === key)?.[1] || key
const f = reactive({ q: '', category: '', method: '' }) // filters of the table: search, category, one-click or pasted details
const dirty = computed(() => !!(f.q || f.category || f.method))
const clear = () => Object.assign(f, { q: '', category: '', method: '' })
const hasOneClick = (p) => !!p.auto_connect?.available
const rows = computed(() => {
  const q = f.q.trim().toLowerCase()
  return CATEGORIES.flatMap(([key]) => providers.value.filter((p) => p.category === key)) // grouped by category, in the catalogue's order
    .filter((p) => (!f.category || p.category === f.category) && (!f.method || (f.method === 'oneclick') === hasOneClick(p)) && (!q || (p.label + ' ' + p.description).toLowerCase().includes(q)))
})
const tileStyle = (key) => ({ background: TILE[key]?.[0] || '#546e7a', color: TILE[key]?.[2] || '#fff' })
const EVERY = [[0, 'Only when I press Sync now'], [15, 'Every 15 minutes'], [60, 'Every hour'], [360, 'Every 6 hours'], [1440, 'Every day']]
const everyLabel = (m) => (EVERY.find((e) => e[0] === m)?.[1] || '').replace('Only when I press Sync now', 'Manual only')

const form = reactive({ direction: 'import', export_list_id: '', name: '', values: {}, list_id: '', tags: '', subscribe_mode: 'consent', confirm: false, sync_every: 60, run_automations: false, import_now: true, roles: [] })
const provider = computed(() => dlg.value?.provider)
const roleSetting = computed(() => provider.value?.settings?.find((s) => s.key === 'roles'))
const auto = computed(() => provider.value?.auto_connect || null)
// "Connect with …": the person approves on the platform's own page, so there is nothing to paste
const oneClick = computed(() => dlg.value?.mode === 'new' && !!auto.value?.available && !manual.value)
const needed = (f) => (auto.value?.asks || []).includes(f.key) || (f.export_required && form.direction !== 'import') // what one-click still has to ask
const shownFields = computed(() => (oneClick.value ? provider.value.fields.filter(needed) : provider.value.fields))
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

function open(mode, p, c = null) {
  Object.assign(form, {
    name: c?.name || '', direction: c?.settings?.direction || 'import', export_list_id: c?.settings?.export_list_id || '',
    values: Object.fromEntries(p.fields.map((f) => [f.key, f.key === 'url' && c ? c.store_url : (!f.secret && c?.settings?.config?.[f.key]) || ''])),
    list_id: c?.settings?.list_id || '', tags: (c?.settings?.tags || []).join(', '),
    subscribe_mode: c?.settings?.subscribe_mode || 'consent', confirm: c?.settings?.subscribe_mode === 'all', // an existing "everyone" was already confirmed
    sync_every: c?.settings?.sync_every ?? 60, run_automations: !!c?.settings?.run_automations, import_now: true,
    roles: c?.settings?.roles || p.settings?.find((s) => s.key === 'roles')?.default || [],
  })
  dlg.value = { mode, provider: p, id: c?.id, connection: c }
  manual.value = false
  dlgError.value = ''
}

function payload() {
  const body = {
    list_id: form.list_id || null, tags: form.tags.split(',').map((t) => t.trim()).filter(Boolean), subscribe_mode: form.subscribe_mode,
    confirm_permission: form.subscribe_mode === 'all' ? form.confirm : false, sync_every: Number(form.sync_every), run_automations: form.run_automations,
  }
  if (roleSetting.value) body.roles = form.roles
  if (provider.value.supports_export) Object.assign(body, { direction: form.direction, export_list_id: form.direction === 'import' ? null : form.export_list_id || null })
  if (form.name.trim()) body.name = form.name.trim()
  for (const f of provider.value.fields) {
    if (form.values[f.key]) body[f.key] = form.values[f.key]
    else if (!f.secret && f.required === false) body[f.key] = '' // an optional option can be cleared
  }
  return body
}

// Sends the person to the platform to approve access; the platform sends them back to this page.
async function startAuto(reconnect = false) {
  busy.value = true
  dlgError.value = ''
  try {
    const asks = auto.value.asks || []
    const body = { provider: provider.value.key }
    if (reconnect) {
      if (asks.includes('url')) body.url = dlg.value.connection.store_url
    } else {
      Object.assign(body, payload(), { import_now: form.import_now })
      for (const f of provider.value.fields) if (!needed(f)) delete body[f.key]
    }
    window.location.href = (await api.post('/integrations/oauth/start', body)).data.url
  } catch (e) { dlgError.value = errorMessage(e); busy.value = false }
}

const submit = () => (oneClick.value ? startAuto() : save())

async function save() {
  busy.value = true
  dlgError.value = ''
  try {
    if (dlg.value.mode === 'new') {
      const r = await api.post('/integrations', { provider: provider.value.key, import_now: form.import_now, ...payload() })
      msg.value = responseMessage(r) || 'Connected.'
    } else {
      await api.put(`/integrations/${dlg.value.id}`, payload())
      msg.value = 'Saved.'
    }
    dlg.value = null
    await load()
  } catch (e) { dlgError.value = errorMessage(e) } finally { busy.value = false }
}

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
  if (q.connected) msg.value = `Connected “${q.connected}”. Importing customers now.`
  else if (q.reconnected) msg.value = `Reconnected “${q.reconnected}”.`
  else if (q.woocommerce) msg.value = 'If you approved access in WooCommerce, the shop is being connected and will appear here in a moment.'
  if (q.oauth_error) error.value = q.oauth_error
  if (Object.keys(q).length) router.replace({ query: {} })
  try {
    ;[providers.value, lists.value] = (await Promise.all([api.get('/integrations/providers'), api.get('/lists')])).map((r) => r.data)
    await load()
    if (q.connect && providerOf(q.connect)) { // “Connect again” on a disconnected integration
      open('new', providerOf(q.connect))
      if (q.name) form.name = q.name
      if (q.url && 'url' in form.values) form.values.url = q.url
    }
    if (q.edit) { // “Settings” on the Connected page
      const c = connections.value.find((x) => String(x.id) === String(q.edit))
      if (c && providerOf(c.provider)) open('edit', providerOf(c.provider), c)
    }
  } finally { loading.value = false }
  if (q.woocommerce) setTimeout(() => load().catch(() => {}), 4000)
  poll()
})
onBeforeUnmount(() => clearInterval(timer))
</script>

<template>
  <div class="head"><h1>Integrations</h1><div class="row"><router-link to="/integrations/history" class="btn ghost">Sync history</router-link></div></div>
  <p class="intro">Connect a store, website, CRM, email-marketing or payments account and bring its customers into <b>People</b> automatically. You can connect several stores or accounts of the same platform (for example three Shopify stores); each keeps its own name, list, tags and schedule. Details you enter are stored encrypted, and only customers who allowed marketing are subscribed unless you choose otherwise.</p>
  <div v-if="msg" class="alert ok" @click="msg = ''">{{ msg }}</div>
  <div v-if="error" class="alert err" @click="error = ''">{{ error }}</div>

  <p v-if="connections.length" class="connected-note"><b>{{ connections.length }}</b> connected. <router-link to="/integrations/connected">View connected integrations</router-link> to sync, change settings or disconnect.</p>

  <h2 class="sec">{{ connections.length ? 'Connect another' : 'Connect a platform' }}</h2>
  <p v-if="loading" class="empty">Loading…</p>
  <template v-else>
    <FilterBar :dirty="dirty" @clear="clear">
      <input v-model="f.q" type="search" placeholder="Search name or description…" />
      <select v-model="f.category"><option value="">All categories</option><option v-for="[key, label] in CATEGORIES" :key="key" :value="key">{{ label }}</option></select>
      <select v-model="f.method"><option value="">Any way to connect</option><option value="oneclick">One-click</option><option value="manual">Paste details</option></select>
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
            <td class="c-act"><button class="btn sm" @click="open('new', p)">Connect</button></td>
          </tr>
          <tr v-if="!rows.length"><td colspan="5" class="empty">No platform matches these filters.</td></tr>
        </tbody>
      </table>
    </div>
  </template>
  <p class="more">Connecting something else? Another system can add and update people with an <router-link to="/settings/api">API key</router-link>.</p>

  <Modal v-if="dlg" :title="dlg.mode === 'new' ? 'Connect ' + provider.label : 'Settings · ' + dlg.connection.name" wide @close="dlg = null">
    <form @submit.prevent="submit">
      <div v-if="dlgError" class="alert err">{{ dlgError }}</div>

      <div v-if="oneClick" class="auto">
        <b>Connect with {{ provider.label }}</b>
        <p>You will go to {{ provider.label }} to approve read-only access to your customers, then come back here. Nothing to copy and paste.</p>
        <button type="button" class="linkbtn" @click="manual = true">Paste the details instead</button>
      </div>
      <div v-else-if="dlg.mode === 'new' && auto?.available" class="auto soft">
        <button type="button" class="linkbtn" @click="manual = false">← Connect with {{ provider.label }} in one click instead</button>
      </div>
      <div v-else-if="dlg.mode === 'edit' && auto?.available" class="auto soft">
        <button type="button" class="btn ghost sm" :disabled="busy" @click="startAuto(true)">Reconnect with {{ provider.label }}</button>
        <small>Use this if the connection expired or access was removed. Nothing else changes.</small>
      </div>

      <details v-if="!oneClick" class="how" :open="dlg.mode === 'new'">
        <summary>How to get these details from {{ provider.label }}</summary>
        <ol><li v-for="(s, i) in provider.steps" :key="i">{{ s }}</li></ol>
      </details>

      <h3 class="blk">{{ dlg.mode === 'new' ? 'Your ' + provider.label : 'Connection' }}</h3>
      <p v-if="dlg.mode === 'edit'" class="hint">Leave a credential empty to keep the one that is stored.</p>
      <div class="field">
        <label>Connection name <small>(optional)</small></label>
        <input v-model="form.name" maxlength="120" placeholder="e.g. Australia store" autocomplete="off" />
        <small>Helps you tell apart several {{ provider.label }} connections. Leave empty to use the name {{ provider.label }} reports.</small>
      </div>
      <div v-for="f in shownFields" :key="f.key" class="field">
        <label>{{ f.label }}</label>
        <input v-model="form.values[f.key]" :type="f.type === 'password' ? 'password' : 'text'" :required="(dlg.mode === 'new' && f.required !== false) || (f.export_required && form.direction !== 'import')" :placeholder="dlg.mode === 'edit' && f.secret && dlg.connection.secret_hints?.[f.key] ? dlg.connection.secret_hints[f.key] + ' (stored)' : f.placeholder" autocomplete="off" />
        <small>{{ f.help }}</small>
      </div>

      <div v-if="provider.messaging" class="field"><small>{{ provider.consent_note }} Nothing is imported from this account: it appears as a channel when you make a campaign (Campaigns → New → Channel).</small></div>
      <template v-if="!provider.messaging">
      <h3 class="blk">What to sync</h3>
      <div v-if="provider.supports_export" class="field">
        <label>Which way should people move?<InfoTip :text="'Import brings ' + provider.label + ' contacts into People. Send adds your People to ' + provider.label + ': only people who are subscribed here are sent as subscribed, people who unsubscribed here are marked unsubscribed there, and nobody who unsubscribed in ' + provider.label + ' is ever re-subscribed. Both does the two, import first.'" /></label>
        <label class="check"><input v-model="form.direction" type="radio" value="import" /> <span><b>Import only</b>: {{ provider.label }} → People</span></label>
        <label class="check"><input v-model="form.direction" type="radio" value="export" /> <span><b>Send only</b>: People → {{ provider.label }}</span></label>
        <label class="check"><input v-model="form.direction" type="radio" value="both" /> <span><b>Both ways</b>: import, then send</span></label>
      </div>
      <div v-if="provider.supports_export && form.direction !== 'import'" class="field">
        <label>Send which people?</label>
        <select v-model="form.export_list_id"><option value="">Everyone with an email address</option><option v-for="l in lists" :key="l.id" :value="l.id">Only people in “{{ l.name }}”</option></select>
        <small>After the first sync, only people who changed since the last one are sent. Choose the {{ provider.label }} audience to send to above.</small>
      </div>
      <div v-if="roleSetting" class="field">
        <label>{{ roleSetting.label }}</label>
        <label v-for="(label, key) in roleSetting.options" :key="key" class="check"><input v-model="form.roles" type="checkbox" :value="key" /> {{ label }}</label>
      </div>
      <div v-if="form.direction !== 'export'" class="grid g2">
        <div class="field">
          <label>Add them to this list</label>
          <select v-model="form.list_id"><option value="">No list</option><option v-for="l in lists" :key="l.id" :value="l.id">{{ l.name }}</option></select>
        </div>
        <div class="field">
          <label>Also give them these tags <small>(comma separated)</small></label>
          <input v-model="form.tags" placeholder="Customer, 2026 import" />
        </div>
      </div>

      <div v-if="form.direction !== 'export'" class="field">
        <label>Who should be subscribed to marketing?<InfoTip :text="provider.consent_note" /></label>
        <label class="check"><input v-model="form.subscribe_mode" type="radio" value="consent" /> Only people who agreed to marketing <b>(recommended)</b></label>
        <label class="check"><input v-model="form.subscribe_mode" type="radio" value="all" /> Everyone, except people who unsubscribed</label>
        <label v-if="form.subscribe_mode === 'all'" class="check confirm"><input v-model="form.confirm" type="checkbox" required /> I confirm I have permission to send marketing to these customers.</label>
      </div>

      <div class="grid g2">
        <div class="field">
          <label>Keep them up to date<InfoTip text="After the first import, only customers that changed since the last sync are read. People who are already here are only completed: nothing is overwritten and nobody is re-subscribed." /></label>
          <select v-model.number="form.sync_every"><option v-for="[m, label] in EVERY" :key="m" :value="m">{{ label }}</option></select>
        </div>
        <div class="field">
          <label>Workflows<InfoTip text="Off by default, so importing a whole store does not start a welcome email for every customer at once. Turn it on to let imported people start workflows (for example “Welcome new people”)." /></label>
          <label class="check"><input v-model="form.run_automations" type="checkbox" /> Start workflows for imported people</label>
        </div>
      </div>
      <label v-if="dlg.mode === 'new'" class="check"><input v-model="form.import_now" type="checkbox" /> {{ form.direction === 'export' ? 'Send people now' : form.direction === 'both' ? 'Sync now' : 'Import the customers now' }}</label>
      </template>

      <div class="row" style="margin-top: 18px">
        <button class="btn" :disabled="busy">{{ busy ? (oneClick ? 'Opening ' + provider.label + '…' : dlg.mode === 'new' ? 'Connecting… testing your store' : 'Saving…') : oneClick ? 'Connect with ' + provider.label : dlg.mode === 'new' ? 'Connect' : 'Save' }}</button>
        <button type="button" class="btn ghost" @click="dlg = null">Cancel</button>
      </div>
    </form>
  </Modal>
</template>

<style scoped>
.connected-note { margin: 4px 0 0; padding: 10px 14px; background: #f1f8f1; border: 1px solid #cfe5cf; border-radius: 8px; }
.check span { line-height: 1.3; }
.oneclick { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #2e7d32; background: #e8f5e9; border-radius: 999px; padding: 2px 8px; vertical-align: middle; margin-left: 4px; }
.auto { background: #f1f8f1; border: 1px solid #cfe5cf; border-radius: 8px; padding: 12px 14px; margin-bottom: 10px; }
.auto p { margin: 4px 0 6px; color: #4b5563; font-size: 14px; }
.auto.soft { background: transparent; border-style: dashed; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.auto.soft small { color: #6b7280; }
.linkbtn { background: none; border: 0; padding: 0; color: #3f51b5; cursor: pointer; font-size: 13.5px; text-decoration: underline; }
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
.c-act { width: 120px; text-align: right !important; }
.provs { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px; }
.prov { display: flex; flex-direction: column; gap: 6px; } .prov h3 { margin: 0; } .prov p { margin: 0 0 8px; color: #6b7280; font-size: 14px; flex: 1; }
.more { margin-top: 20px; color: #6b7280; font-size: 14px; }
.how { background: #f5f6fd; border: 1px solid #e0e3f0; border-radius: 8px; padding: 10px 14px; margin-bottom: 8px; }
.how summary { cursor: pointer; font-weight: 600; color: #3f51b5; } .how ol { margin: 10px 0 2px; padding-left: 20px; font-size: 14px; color: #4b5563; } .how li { margin: 5px 0; }
.blk { font-size: 13px; text-transform: uppercase; letter-spacing: .06em; color: #3f51b5; margin: 20px 0 8px; }
.hint { color: #6b7280; font-size: 13.5px; margin: 0 0 10px; }
.confirm { margin-top: 6px; color: #8a5a00; }
.field .check { display: flex; align-items: center; gap: 8px; margin: 4px 0; }
.field .check input[type='radio'] { width: auto; flex: none; margin: 0; }
</style>
