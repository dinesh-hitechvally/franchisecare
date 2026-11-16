<script setup>
// Integrations → Connected → Settings: one page to configure a connected integration. The platform's keys are not shown (it is already connected).
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { errorMessage, responseMessage } from '../api'
import InfoTip from '../components/InfoTip.vue'
import MultiSelect from '../components/MultiSelect.vue'
import { tileStyle, tileText } from '../integrationTiles'

const route = useRoute()
const router = useRouter()
const conn = ref(null)
const provider = ref(null)
const lists = ref([])
const segments = ref([])
const audiences = ref(null) // the account's own audiences (Mailchimp), to pick from; null until loaded or when the platform has none
const audienceError = ref('')
const tags = ref([])
const loading = ref(true)
const msg = ref('')
const error = ref('')
const busy = ref(false) // an action in the header
const saving = ref(false)
const form = reactive({ name: '', options: {}, direction: 'import', export_lists: [], export_segments: [], export_tags: [], list_id: '', tags: '', subscribe_mode: 'consent', confirm: false, sync_every: 60, run_automations: false, roles: [] })
let stored = '' // what the form held when it was loaded, to know when there is something to save

const EVERY = [[0, 'Only when I press Sync now'], [15, 'Every 15 minutes'], [60, 'Every hour'], [360, 'Every 6 hours'], [1440, 'Every day']]
const everyText = (m) => (EVERY.find((e) => e[0] === Number(m))?.[1] || '').replace('Only when I press Sync now', 'only when you press Sync now').replace(/^Every/, 'every')

const messaging = computed(() => !!provider.value?.messaging)
const AUDIENCE_KEYS = ['audience_id', 'export_audience_id']
const options = computed(() => (provider.value?.fields || []).filter((f) => !f.secret && f.key !== 'url' && !(audiences.value && AUDIENCE_KEYS.includes(f.key)))) // non-secret options of the platform (the audiences are chosen from a list below when it loaded)
// each option goes to the card it is about: what comes in, where people are sent, or (no `card`) the connection itself
const connectionOptions = computed(() => options.value.filter((f) => !f.card))
const importOptions = computed(() => options.value.filter((f) => f.card === 'import'))
const exportOptions = computed(() => options.value.filter((f) => f.card === 'export'))
const audienceOptions = (current) => (current && !audiences.value.some((a) => a.id === current) ? [{ id: current, name: current, members: null }, ...audiences.value] : audiences.value) // an id saved earlier stays selectable
const audienceName = (id) => audiences.value?.find((a) => a.id === id)?.name || id
const members = (a) => (a.members === null ? '' : ` (${a.members.toLocaleString()} ${a.members === 1 ? 'member' : 'members'})`)
const noun = computed(() => provider.value?.audience_noun || 'audience') // Mailchimp has audiences, Klaviyo has lists
const importFrom = computed(() => (form.options.audience_id ? 'the ' + noun.value + ' “' + audienceName(form.options.audience_id) + '”' : 'every ' + noun.value))
const sendTo = computed(() => (form.options.export_audience_id || form.options.audience_id ? 'the ' + noun.value + ' “' + audienceName(form.options.export_audience_id || form.options.audience_id) + '”' : ''))
const roleSetting = computed(() => provider.value?.settings?.find((s) => s.key === 'roles'))
const statusLabel = computed(() => (conn.value?.running ? 'syncing' : conn.value?.status === 'connected' ? 'connected' : 'needs attention'))
const dirty = computed(() => JSON.stringify(form) !== stored)
const list = computed(() => lists.value.find((l) => l.id === Number(form.list_id)))
const opts = (rows) => rows.map((x) => ({ value: x.id, label: x.name }))
const listOptions = computed(() => opts(lists.value))
const segmentOptions = computed(() => opts(segments.value))
const tagOptions = computed(() => opts(tags.value))
// "who is sent", in words: the chosen lists, segments and tags add up; nothing chosen = everyone with an email address
const sendWho = computed(() => {
  const named = (rows, ids, kind) => (ids.length ? ids.length + ' ' + kind + (ids.length === 1 ? '' : 's') + ' (' + rows.filter((x) => ids.includes(x.id)).map((x) => x.name).join(', ') + ')' : '')
  const parts = [named(lists.value, form.export_lists, 'list'), named(segments.value, form.export_segments, 'segment'), named(tags.value, form.export_tags, 'tag')].filter(Boolean)
  return parts.length ? 'People in ' + parts.join(' or ') : 'Everyone with an email address'
})
const imports = computed(() => !messaging.value && form.direction !== 'export')
const exports_ = computed(() => !messaging.value && provider.value?.supports_export && form.direction !== 'import')

// Permissions: what the app asks the platform for (and what each is for) against what the platform said it granted when this connection was made.
const permSpec = computed(() => provider.value?.auto_connect || null)
const granted = computed(() => conn.value?.settings?.granted_scopes || null) // null: not recorded (a pasted key, or a connection made before this was kept)
const permRows = computed(() => (permSpec.value?.scopes || []).map((scope) => {
  const note = permSpec.value.scope_notes?.[scope] || {}
  const dirs = note.for || ['import', 'send']
  return {
    scope, why: note.why || '', for: dirs,
    needed: (imports.value && dirs.includes('import')) || (exports_.value && dirs.includes('send')), // needed for what is set up now
    state: granted.value ? (granted.value.includes(scope) ? 'given' : 'missing') : 'unknown',
  }
}))
const permMissing = computed(() => permRows.value.filter((r) => r.needed && r.state === 'missing'))
const permGivenCount = computed(() => permRows.value.filter((r) => r.state === 'given').length)
const permExtra = computed(() => (granted.value || []).filter((g) => !(permSpec.value?.scopes || []).includes(g)))
const checking = ref(false)
// Asks the platform which permissions this connection really has (harmless requests), and keeps the answer. The form is left as it is.
async function checkPermissions(silent = false) {
  checking.value = true
  if (!silent) error.value = msg.value = ''
  try {
    await api.post(`/integrations/${conn.value.id}/permissions`)
    await load()
    if (!silent) msg.value = 'Permissions checked.'
  } catch (e) { if (!silent) error.value = errorMessage(e) } finally { checking.value = false }
}
const STATE_TEXT = { given: 'Given', missing: 'Not given', unknown: 'Not known' }
const forText = (dirs) => (dirs.length === 2 ? 'importing and sending' : dirs[0] === 'send' ? 'sending' : 'importing')

function fill() {
  const c = conn.value, p = provider.value
  Object.assign(form, {
    name: c.name || '', options: Object.fromEntries((p.fields || []).filter((f) => !f.secret && f.key !== 'url').map((f) => [f.key, c.settings?.config?.[f.key] || ''])),
    direction: c.settings?.direction || 'import', list_id: c.settings?.list_id || '', tags: (c.settings?.tags || []).join(', '),
    subscribe_mode: c.settings?.subscribe_mode || 'consent', confirm: c.settings?.subscribe_mode === 'all', sync_every: c.settings?.sync_every ?? 60,
    export_lists: [...(c.settings?.export_audience?.lists || (c.settings?.export_list_id ? [c.settings.export_list_id] : []))], export_segments: [...(c.settings?.export_audience?.segments || [])], export_tags: [...(c.settings?.export_audience?.tags || [])],
    run_automations: !!c.settings?.run_automations, roles: c.settings?.roles || p.settings?.find((s) => s.key === 'roles')?.default || [],
  })
  if (form.options.export_audience_id === '' && form.options.audience_id) form.options.export_audience_id = form.options.audience_id // an older connection used one audience for both
  stored = JSON.stringify(form)
}

async function load() {
  const rows = (await api.get('/integrations', { params: { include_disconnected: 1 } })).data
  conn.value = rows.find((r) => String(r.id) === String(route.params.id)) || null
}

async function save() {
  saving.value = true
  error.value = msg.value = ''
  try {
    const body = {
      list_id: form.list_id || null, tags: form.tags.split(',').map((t) => t.trim()).filter(Boolean), subscribe_mode: form.subscribe_mode,
      confirm_permission: form.subscribe_mode === 'all' ? form.confirm : false, sync_every: Number(form.sync_every), run_automations: form.run_automations,
      name: form.name.trim() || undefined,
    }
    if (roleSetting.value) body.roles = form.roles
    if (provider.value.supports_export) Object.assign(body, { direction: form.direction, export_lists: form.direction === 'import' ? [] : form.export_lists, export_segments: form.direction === 'import' ? [] : form.export_segments, export_tags: form.direction === 'import' ? [] : form.export_tags })
    for (const f of provider.value.fields.filter((x) => !x.secret && x.key !== 'url')) body[f.key] = form.options[f.key] || (f.required === false ? '' : undefined)
    await api.put(`/integrations/${conn.value.id}`, body)
    msg.value = 'Saved.'
    await load()
    fill()
  } catch (e) { error.value = errorMessage(e) } finally { saving.value = false }
}

async function act(fn, ok) {
  busy.value = true
  error.value = msg.value = ''
  try { const r = await fn(); msg.value = responseMessage(r) || ok; await load() } catch (e) { error.value = errorMessage(e) } finally { busy.value = false }
}
const check = () => act(() => api.post(`/integrations/${conn.value.id}/check`), 'The connection works.')
const sync = () => act(() => api.post(`/integrations/${conn.value.id}/sync`, {}), 'Sync started.')
async function reconnect() {
  busy.value = true
  error.value = ''
  try {
    const body = { provider: provider.value.key }
    if ((provider.value.auto_connect?.asks || []).includes('url')) body.url = conn.value.store_url
    window.location.href = (await api.post('/integrations/oauth/start', body)).data.url
  } catch (e) { error.value = errorMessage(e); busy.value = false }
}
async function disconnect() {
  if (!confirm(`Disconnect “${conn.value.name}”? The people already imported stay in People; nothing more is imported.`)) return
  try { await api.delete(`/integrations/${conn.value.id}`); router.push('/integrations/connected') } catch (e) { error.value = errorMessage(e) }
}

onMounted(async () => {
  try {
    const [providers, l, sg, tg] = (await Promise.all([api.get('/integrations/providers'), api.get('/lists'), api.get('/segments'), api.get('/tags')])).map((r) => r.data)
    lists.value = l
    segments.value = sg
    tags.value = tg
    await load()
    provider.value = conn.value ? providers.find((p) => p.key === conn.value.provider) || null : null
    if (conn.value && provider.value) {
      fill()
      if (provider.value.has_audiences && conn.value.status === 'connected') {
        try { audiences.value = (await api.get(`/integrations/${conn.value.id}/audiences`)).data } catch (e) { audienceError.value = errorMessage(e) } // the ids can still be typed
      }
      if (provider.value.auto_connect?.can_check_scopes && conn.value.status === 'connected' && !conn.value.settings?.granted_scopes) checkPermissions(true) // nothing recorded yet: find out
    }
  } catch (e) { error.value = errorMessage(e) } finally { loading.value = false }
})
</script>

<template>
  <p class="crumb"><router-link to="/integrations/connected">← Connected</router-link></p>
  <p v-if="loading" class="empty">Loading…</p>
  <p v-else-if="!conn || !provider" class="empty">This integration was not found. <router-link to="/integrations/connected">Back to Connected</router-link></p>

  <template v-else>
    <!-- header -->
    <header class="top">
      <span class="tile" :style="tileStyle(provider.key)">{{ tileText(provider.key) }}</span>
      <div class="who">
        <h1>{{ conn.name }}</h1>
        <p>{{ provider.label }} <span class="badge" :class="conn.status === 'connected' ? 'sent' : 'failed'">{{ statusLabel }}</span><template v-if="conn.uses_url"> · {{ conn.store_url }}</template></p>
      </div>
      <div class="acts">
        <router-link :to="`/integrations/${conn.id}/history`" class="btn ghost">Sync history</router-link>
        <button v-if="!messaging" class="btn ghost" :disabled="busy || conn.running" @click="sync">{{ conn.running ? 'Syncing…' : 'Sync now' }}</button>
        <button class="btn ghost" :disabled="busy" @click="check">Check connection</button>
        <button v-if="provider.auto_connect?.available" class="btn ghost" :disabled="busy" @click="reconnect">Reconnect</button>
        <button class="btn danger ghost" :disabled="busy" @click="disconnect">Disconnect</button>
      </div>
    </header>

    <div v-if="conn.last_error" class="alert err">{{ conn.last_error }}</div>
    <div v-if="msg" class="alert ok" @click="msg = ''">{{ msg }}</div>
    <div v-if="error" class="alert err" @click="error = ''">{{ error }}</div>

    <!-- numbers -->
    <div class="stats">
      <div class="stat"><span>People imported</span><b>{{ messaging ? '—' : conn.imported }}</b></div>
      <div class="stat"><span>People exported</span><b>{{ messaging || !provider.supports_export ? '—' : conn.exported }}</b></div>
      <div class="stat"><span>Last synced</span><b>{{ messaging ? '—' : conn.last_synced_at ? $dt(conn.last_synced_at) : 'never' }}</b></div>
      <div class="stat"><span>Updates</span><b>{{ messaging ? '—' : form.sync_every ? EVERY.find((e) => e[0] === Number(form.sync_every))?.[1].replace('Every', 'Every') : 'Manual' }}</b></div>
      <div class="stat"><span>Direction</span><b>{{ messaging ? 'Sends messages' : form.direction === 'both' ? 'Import and send' : form.direction === 'export' ? 'Send only' : 'Import only' }}</b></div>
    </div>

    <div class="cols">
      <!-- settings -->
      <form class="main" @submit.prevent="save">
        <section class="card sec">
          <h2>Connection</h2>
          <div class="field">
            <label>Connection name <InfoTip text="Only a label, so you can tell apart several connections of the same platform. It changes nothing on the platform." /></label>
            <input v-model="form.name" maxlength="120" placeholder="e.g. Australia store" autocomplete="off" />
          </div>
          <div v-for="f in connectionOptions" :key="f.key" class="field">
            <label>{{ f.label }} <InfoTip v-if="f.help" :text="f.help" /></label>
            <input v-model="form.options[f.key]" :placeholder="f.placeholder" autocomplete="off" />
          </div>
          <p v-if="messaging" class="note">{{ provider.consent_note }} This account sends campaign messages and imports nothing: it appears in the <b>Channel</b> list when you make a campaign.</p>
        </section>

        <!-- Connection, direction and schedule are always there. "Who comes in" shows when people are imported (import only, both ways) and "Send which people?" when they are sent (send only, both ways). -->
        <template v-if="!messaging">
          <section class="card sec">
            <h2>Which way should people move? <InfoTip :text="'Import brings ' + provider.label + ' contacts into People. ' + (provider.supports_export ? provider.export_note + ' Both imports first, then sends.' : provider.label + ' can only be imported from: sending your People to it is not available.')" /></h2>
            <div class="choices">
              <label class="choice" :class="{ on: form.direction === 'import' }"><input v-model="form.direction" type="radio" value="import" /><b>Import only</b><span>{{ provider.label }} → People</span></label>
              <label class="choice" :class="{ on: form.direction === 'export', off: !provider.supports_export }"><input v-model="form.direction" type="radio" value="export" :disabled="!provider.supports_export" /><b>Send only</b><span>People → {{ provider.label }}</span></label>
              <label class="choice" :class="{ on: form.direction === 'both', off: !provider.supports_export }"><input v-model="form.direction" type="radio" value="both" :disabled="!provider.supports_export" /><b>Both ways</b><span>Import, then send</span></label>
            </div>
            <p v-if="!provider.supports_export" class="note">{{ provider.label }} can only be imported from. Sending your People to it is not available.</p>
          </section>

          <section v-if="imports" class="card sec">
            <h2>Who comes in</h2>
            <p v-if="audienceError" class="note warn top">Could not load the {{ noun }}s from {{ provider.label }} ({{ audienceError }}). You can still type the {{ noun }} ID below.</p>
            <fieldset>
              <div v-if="audiences" class="field">
                <label>Import from which {{ noun }}? <InfoTip :text="'Choose one ' + provider.label + ' ' + noun + ' to read, or ' + (noun === 'list' ? 'every profile' : 'every ' + noun) + ' of the account. To import another ' + noun + ' separately, connect the same account again and choose that one there.'" /></label>
                <select v-model="form.options.audience_id">
                  <option value="">{{ noun === 'list' ? 'Every profile' : 'Every ' + noun }}</option>
                  <option v-for="a in audienceOptions(form.options.audience_id)" :key="a.id" :value="a.id">{{ a.name }}{{ members(a) }}</option>
                </select>
              </div>
              <div v-for="f in importOptions" :key="f.key" class="field">
                <label>{{ f.label }} <InfoTip v-if="f.help" :text="f.help" /></label>
                <input v-model="form.options[f.key]" :placeholder="f.placeholder" autocomplete="off" />
              </div>
              <div v-if="roleSetting" class="field">
                <label>{{ roleSetting.label }}</label>
                <div class="checks"><label v-for="(label, key) in roleSetting.options" :key="key" class="chk"><input v-model="form.roles" type="checkbox" :value="key" /> {{ label }}</label></div>
              </div>
              <div class="grid g2">
                <div class="field">
                  <label>Add them to this list <InfoTip text="Every person the sync brings in is added to this list, so you can send a campaign to exactly them." /></label>
                  <select v-model="form.list_id"><option value="">No list</option><option v-for="l in lists" :key="l.id" :value="l.id">{{ l.name }}</option></select>
                </div>
                <div class="field">
                  <label>Also give them these tags <InfoTip text="Tags, comma separated, put on every imported person, for example Customer or 2026 import. Use them to build segments." /></label>
                  <input v-model="form.tags" placeholder="Customer, 2026 import" />
                </div>
              </div>
              <div class="field">
                <label>Who should be subscribed to marketing? <InfoTip :text="provider.consent_note" /></label>
                <div class="choices two">
                  <label class="choice" :class="{ on: form.subscribe_mode === 'consent' }"><input v-model="form.subscribe_mode" type="radio" value="consent" /><b>Only people who agreed</b><span>Follows what {{ provider.label }} records. Recommended.</span></label>
                  <label class="choice" :class="{ on: form.subscribe_mode === 'all' }"><input v-model="form.subscribe_mode" type="radio" value="all" /><b>Everyone</b><span>Except people who unsubscribed. Only if you have permission.</span></label>
                </div>
                <label v-if="form.subscribe_mode === 'all'" class="chk confirm"><input v-model="form.confirm" type="checkbox" required /> I confirm I have permission to send marketing to these customers.</label>
              </div>
            </fieldset>
          </section>

          <section v-if="exports_" class="card sec">
            <h2>Send which people? <InfoTip text="Pick any mix of lists, segments and tags: a person who is in any of the chosen ones is sent, and nobody is sent twice. Choose nothing to send everyone with an email address. After the first sync, only people who changed since the last one are sent." /></h2>
            <p v-if="audienceError" class="note warn top">Could not load the {{ noun }}s from {{ provider.label }} ({{ audienceError }}). You can still type the {{ noun }} ID below.</p>
            <p v-if="conn.settings?.export_all" class="note top">Everyone this covers will be sent on the next sync (press <b>Sync now</b>); after that, only people who changed.</p>
            <fieldset>
              <div v-if="audiences" class="field">
                <label>Send to which {{ noun }}? <InfoTip :text="'The ' + provider.label + ' ' + noun + ' your People are added to. Only people who are subscribed here are sent as subscribed; nobody who unsubscribed in ' + provider.label + ' is ever re-subscribed.'" /></label>
                <select v-model="form.options.export_audience_id" required>
                  <option value="" disabled>Choose a {{ noun }}…</option>
                  <option v-for="a in audienceOptions(form.options.export_audience_id)" :key="a.id" :value="a.id">{{ a.name }}{{ members(a) }}</option>
                </select>
              </div>
              <div v-for="f in exportOptions" :key="f.key" class="field">
                <label>{{ f.label }} <InfoTip v-if="f.help" :text="f.help" /></label>
                <input v-model="form.options[f.key]" :placeholder="f.placeholder" autocomplete="off" :required="!!f.export_required" />
              </div>
              <div class="grid g3">
                <div class="field"><label>Lists</label><MultiSelect v-model="form.export_lists" :options="listOptions" placeholder="Any list" empty-text="No lists yet." /></div>
                <div class="field"><label>Segments</label><MultiSelect v-model="form.export_segments" :options="segmentOptions" placeholder="Any segment" empty-text="No segments yet." /></div>
                <div class="field"><label>Tags</label><MultiSelect v-model="form.export_tags" :options="tagOptions" placeholder="Any tag" empty-text="No tags yet." /></div>
              </div>
              <p class="sendnote"><b>Will send:</b> {{ sendWho }}.</p>
            </fieldset>
          </section>

          <section class="card sec">
            <h2>Schedule</h2>
            <div class="grid g2">
              <div class="field">
                <label>Keep them up to date <InfoTip text="After the first sync, only people that changed since the last sync are read or sent. People who are already here are only completed: nothing is overwritten and nobody is re-subscribed." /></label>
                <select v-model.number="form.sync_every"><option v-for="[m, label] in EVERY" :key="m" :value="m">{{ label }}</option></select>
              </div>
              <div v-if="imports" class="field">
                <label>Workflows <InfoTip text="Off by default, so importing a whole store does not start a welcome email for every customer at once. Turn it on to let imported people start workflows." /></label>
                <label class="chk"><input v-model="form.run_automations" type="checkbox" /> Start workflows for imported people</label>
              </div>
            </div>
          </section>
        </template>

        <div class="bar">
          <button class="btn" :disabled="saving || !dirty">{{ saving ? 'Saving…' : 'Save changes' }}</button>
          <button type="button" class="btn ghost" @click="router.push('/integrations/connected')">Back</button>
          <small v-if="dirty" class="unsaved">You have unsaved changes</small>
        </div>
      </form>

      <!-- how it works, overall -->
      <div class="sidecol">
      <aside class="side card">
        <h3>How this connection works</h3>

        <div v-if="!messaging" class="flow">
          <span class="node">{{ provider.label }}</span>
          <span class="arrow">{{ form.direction === 'both' ? '⇄' : form.direction === 'export' ? '←' : '→' }}</span>
          <span class="node us">People</span>
        </div>
        <div v-else class="flow"><span class="node us">Campaign</span><span class="arrow">→</span><span class="node">{{ provider.label }}</span></div>

        <ol v-if="!messaging" class="steps">
          <li v-if="imports"><b>First sync.</b> Reads every contact from {{ provider.has_audiences ? importFrom + ' of ' + provider.label : provider.label }} and adds each one to People. A person who is already in People (same email) is only completed, never duplicated or overwritten.</li>
          <li v-if="imports"><b>After that</b>, {{ everyText(form.sync_every) }}, it reads only what changed since the last sync. <i>Sync now</i> runs it at once; every run is listed in Sync history.</li>
          <li v-if="imports"><b>Marketing permission.</b> {{ form.subscribe_mode === 'all' ? 'Everyone is subscribed except people who unsubscribed.' : 'Only people who agreed to marketing on ' + provider.label + ' are subscribed; the others are added without a subscription.' }} People who unsubscribed are never re-subscribed.</li>
          <li v-if="imports && (list || form.tags.trim())"><b>Where they land.</b> {{ list ? 'In the list “' + list.name + '”' : '' }}{{ list && form.tags.trim() ? ', ' : '' }}{{ form.tags.trim() ? 'tagged ' + form.tags.trim() : '' }}.</li>
          <li v-if="exports_"><b>Sending to {{ provider.label }}{{ provider.has_audiences && sendTo ? ', ' + sendTo : '' }}.</b> {{ sendWho }} is sent after each import; later runs send only people who changed. {{ provider.export_note }}</li>
          <li><b>Workflows.</b> {{ imports && form.run_automations ? 'Imported people can start workflows.' : 'Imported people do not start workflows, so a big import sends nothing by surprise.' }}</li>
        </ol>
        <p v-else class="plain">{{ provider.consent_note }} Nothing is imported from this account. It appears in the <b>Channel</b> list when you create a campaign, and each message is sent through it.</p>

        <h3>Keys and addresses</h3>
        <p class="plain">This integration is already connected, so its keys are not shown. To use different ones, disconnect it and connect again from <router-link to="/integrations">Integrations</router-link>. People already imported stay in People.</p>
      </aside>

      <aside v-if="!messaging" class="side card perm">
        <h3>Permissions</h3>
        <template v-if="permRows.length">
          <p v-if="granted" class="plain"><b>{{ permGivenCount }} of {{ permRows.length }}</b> permissions given by {{ provider.label }}.</p>
          <p v-else-if="permSpec.can_check_scopes" class="plain">{{ checking ? 'Checking which permissions ' + provider.label + ' gave…' : 'Not checked yet. Press Check permissions.' }}</p>
          <p v-else class="plain">Not recorded for this connection (it was made with a pasted key, or before permissions were kept). <b>Reconnect</b> to see which ones {{ provider.label }} gave.</p>
          <p v-if="permMissing.length" class="note warn">Missing for what is set up now: <b>{{ permMissing.map((r) => r.scope).join(', ') }}</b>. Switch them on for the app at {{ provider.label }}, then reconnect.</p>
          <ul class="perms">
            <li v-for="r in permRows" :key="r.scope" :class="r.state">
              <span class="mark" :title="r.state === 'given' ? 'Given' : r.state === 'missing' ? 'Not given' : 'Not known'">{{ r.state === 'given' ? '✓' : r.state === 'missing' ? '✗' : '?' }}</span>
              <div>
                <code>{{ r.scope }}</code> <b class="st" :class="r.state">{{ STATE_TEXT[r.state] }}</b> <small v-if="r.needed" class="need">needed now</small>
                <small class="why">{{ r.why }} Used for {{ forText(r.for) }}.</small>
              </div>
            </li>
          </ul>
          <p v-if="permExtra.length" class="plain"><b>Also given:</b> {{ permExtra.join(', ') }}</p>
          <p v-if="permSpec.can_check_scopes" class="checkrow"><button type="button" class="btn ghost sm" :disabled="checking" @click="checkPermissions()">{{ checking ? 'Checking…' : 'Check permissions' }}</button><small v-if="conn.settings?.scopes_checked_at" class="plain">Checked {{ $dt(conn.settings.scopes_checked_at) }}</small></p>
        </template>
        <p v-else class="plain">{{ provider.label }} is connected with details you pasted or its own approval page, so it does not report permissions here. If a sync is refused, the message names what is missing, and <b>Help</b> on the Integrations page lists what the key needs.</p>
      </aside>
      </div>
    </div>
  </template>
</template>

<style scoped>
.crumb { margin: 0 0 10px; font-size: 14px; }
.top { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; margin-bottom: 16px; }
.tile { flex: none; display: inline-flex; align-items: center; justify-content: center; width: 52px; height: 52px; border-radius: 12px; font-weight: 800; font-size: 17px; }
.who { flex: 1 1 260px; min-width: 0; }
.who h1 { margin: 0; font-size: 24px; line-height: 1.2; }
.who p { margin: 4px 0 0; color: #6b7280; font-size: 14px; word-break: break-all; }
.acts { display: flex; flex-wrap: wrap; gap: 8px; }
.btn.danger.ghost { color: #c62828; border-color: #f0c4c4; background: #fff; }
.btn.danger.ghost:hover { background: #fdecea; }

.stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; margin: 14px 0 20px; }
.stat { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 16px; }
.stat span { display: block; font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
.stat b { display: block; margin-top: 4px; font-size: 16px; color: #111827; }

.cols { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 20px; align-items: start; }
.main { display: grid; gap: 16px; min-width: 0; }
.sec { padding: 20px 22px; }
.sec h2 { margin: 0 0 14px; font-size: 16px; display: flex; align-items: center; gap: 6px; }
.sec .field:last-child { margin-bottom: 0; }
.note { margin: 12px 0 0; padding: 10px 12px; background: #eef2ff; border-radius: 8px; font-size: 13.5px; color: #374151; }

.choices { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-top: 4px; }
.choices.two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.choice { position: relative; display: flex; flex-direction: column; gap: 3px; padding: 12px 14px 12px 38px; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; cursor: pointer; line-height: 1.3; }
.choice input { position: absolute; left: 14px; top: 15px; width: auto; margin: 0; }
.choice b { font-size: 14px; }
.choice span { font-size: 12.5px; color: #6b7280; }
.choice:hover { border-color: #c7d2fe; }
.choice.on { border-color: #3f51b5; background: #f5f6fd; box-shadow: 0 0 0 1px #3f51b5 inset; }
.sec fieldset { border: 0; margin: 0; padding: 0; min-width: 0; }
.choice.off { opacity: .5; cursor: not-allowed; }
.choice.off:hover { border-color: #e5e7eb; }
.note.top { margin: 0 0 14px; }
.sendnote { margin: 6px 0 0; padding: 8px 12px; background: #f5f6fd; border-radius: 8px; font-size: 13.5px; color: #374151; }
.note.warn { background: #fff4e0; color: #8a5a00; }
.chk { display: inline-flex; align-items: center; gap: 8px; margin: 4px 0; font-size: 14px; }
.chk input { width: auto; margin: 0; }
.checks { display: flex; flex-wrap: wrap; gap: 6px 18px; }
.confirm { margin-top: 10px; color: #8a5a00; }

.bar { position: sticky; bottom: 0; display: flex; align-items: center; gap: 10px; padding: 12px 0; background: linear-gradient(to top, #f4f5f7 70%, transparent); }
.unsaved { color: #8a5a00; }

.sidecol { position: sticky; top: 16px; display: grid; gap: 16px; }
.side { padding: 20px 22px; font-size: 13.5px; color: #4b5563; line-height: 1.5; }
.perms { list-style: none; margin: 12px 0 0; padding: 0; display: grid; gap: 10px; }
.perms li { display: flex; gap: 10px; align-items: flex-start; }
.perms .mark { flex: none; width: 20px; height: 20px; border-radius: 50%; display: grid; place-items: center; font-size: 12px; font-weight: 700; background: #eef0f3; color: #6b7280; }
.perms li.given .mark { background: #d1fae5; color: #065f46; }
.perms li.missing .mark { background: #fee2e2; color: #991b1b; }
.perms code { font-size: 12.5px; background: #f3f4f6; padding: 1px 6px; border-radius: 4px; color: #111827; }
.perms .need { margin-left: 4px; padding: 1px 7px; border-radius: 99px; background: #eef2ff; color: #3f51b5; font-size: 11px; font-weight: 600; }
.perms li.missing .need { background: #fee2e2; color: #991b1b; }
.perms .st { margin-left: 4px; font-size: 12px; }
.perms .st.given { color: #065f46; }
.perms .st.missing { color: #991b1b; }
.perms .st.unknown { color: #6b7280; font-weight: 600; }
.checkrow { display: flex; align-items: center; gap: 10px; margin: 14px 0 0; }
.perms .why { display: block; margin-top: 2px; color: #6b7280; font-size: 12.5px; }
.side .note { margin-top: 10px; }
.side h3 { margin: 0 0 12px; font-size: 13px; text-transform: uppercase; letter-spacing: .05em; color: #3f51b5; }
.side h3:not(:first-child) { margin-top: 22px; }
.flow { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 14px 10px; margin-bottom: 14px; background: #f5f6fd; border-radius: 10px; }
.node { padding: 6px 12px; border-radius: 8px; background: #fff; border: 1px solid #e0e3f0; font-weight: 700; color: #111827; font-size: 13px; }
.node.us { background: #3f51b5; border-color: #3f51b5; color: #fff; }
.arrow { font-size: 22px; color: #3f51b5; line-height: 1; }
.steps { margin: 0; padding-left: 20px; display: grid; gap: 10px; }
.steps b { color: #111827; }
.plain { margin: 0; }

@media (max-width: 1100px) { .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 960px) { .cols { grid-template-columns: 1fr; } .side { position: static; } .choices, .choices.two { grid-template-columns: 1fr; } }
</style>
