<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import api, { errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import { useFilters } from '../composables/useFilters'
import RowMenu from '../components/RowMenu.vue'
import Modal from '../components/Modal.vue'
import Pager from '../components/Pager.vue'

const rows = ref([])
const { f: flt, shown: visible, dirty: fDirty, clear: fClear } = useFilters(rows, { text: (h) => [h.name, h.url], match: { status: (h) => (h.active ? 'active' : 'paused') } })
const events = ref([])
const editing = ref(null)
const error = ref('')
const msg = ref('')
const secret = ref(null)
const log = ref(null) // { hook, meta, items }
const form = reactive({ name: '', url: '', events: [], active: true })
const all = computed({ get: () => form.events.includes('*'), set: (v) => (form.events = v ? ['*'] : []) })

const load = async () => { rows.value = (await api.get('/webhooks')).data }

function open(h) {
  Object.assign(form, { name: h?.name || '', url: h?.url || '', events: h ? [...h.events] : [], active: h ? h.active : true })
  editing.value = h ? h.id : 'new'
  error.value = ''
}

function toggleEvent(key) {
  form.events = form.events.includes(key) ? form.events.filter((e) => e !== key) : [...form.events.filter((e) => e !== '*'), key]
}

async function save() {
  error.value = ''
  try {
    if (editing.value === 'new') {
      secret.value = (await api.post('/webhooks', form)).data
    } else {
      await api.put(`/webhooks/${editing.value}`, form)
    }
    editing.value = null
    load()
  } catch (e) { error.value = errorMessage(e) }
}

async function remove(h) {
  if (!confirm(`Delete webhook "${h.name}"?`)) return
  await api.delete(`/webhooks/${h.id}`)
  load()
}

async function test(h) {
  msg.value = error.value = ''
  try {
    const d = (await api.post(`/webhooks/${h.id}/test`)).data
    msg.value = d.success ? `“${h.name}” answered ${d.status_code} — it works.` : `“${h.name}” failed: ${d.status_code ? 'HTTP ' + d.status_code : d.response}`
    if (!d.success) { error.value = msg.value; msg.value = '' }
    load()
  } catch (e) { error.value = errorMessage(e) }
}

async function showLog(h, page = 1) {
  const d = (await api.get(`/webhooks/${h.id}/deliveries`, { params: { page } })).data
  log.value = { hook: h, meta: d, items: d.data }
}

onMounted(async () => { events.value = (await api.get('/webhooks/events')).data; load() })
</script>

<template>
  <div class="head"><h1>Webhooks</h1><button class="btn" @click="open(null)">Add webhook</button></div>
  <p class="intro">Webhooks tell another system the moment something happens here (a contact joins, a campaign finishes…). Each request is signed so the receiver can verify it came from you.</p>
  <div v-if="msg" class="alert ok">{{ msg }}</div>
  <div v-if="error && !editing" class="alert err">{{ error }}</div>

  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="flt.q" type="search" placeholder="Search webhooks…" />
    <select v-model="flt.status"><option value="">All statuses</option><option value="active">Active</option><option value="paused">Paused</option></select>
  </FilterBar>

  <div class="card">
    <p v-if="!rows.length" class="empty">No webhooks yet.</p>
    <p v-else-if="!visible.length" class="empty">Nothing matches these filters.</p>
    <table v-else>
      <thead><tr><th>Name</th><th>URL</th><th>Events</th><th>Last delivery</th><th>Status</th><th class="act">Actions</th></tr></thead>
      <tr v-for="h in visible" :key="h.id">
        <td><b>{{ h.name }}</b></td>
        <td class="url" :title="h.url">{{ h.url }}</td>
        <td><small>{{ h.events.includes('*') ? 'All events' : h.events.join(', ') }}</small></td>
        <td>
          <span v-if="h.last_delivery" class="badge" :class="h.last_delivery.success ? 'sent' : 'failed'">{{ h.last_delivery.success ? 'OK' : 'failed' }} {{ h.last_delivery.status_code || '' }}</span>
          <small v-else>never</small>
        </td>
        <td><span class="badge" :class="h.active ? 'sent' : ''">{{ h.active ? 'active' : 'paused' }}</span></td>
        <td class="act"><RowMenu :history="{ type: 'webhook', id: h.id, title: h.name }" :items="[{ label: 'Edit', run: () => open(h) }, { label: 'Send test', run: () => test(h) }, { label: 'Deliveries', run: () => showLog(h) }, { label: 'Delete', run: () => remove(h), danger: true }]" /></td>
      </tr>
    </table>
  </div>

  <Modal v-if="editing" :title="editing === 'new' ? 'Add webhook' : 'Edit webhook'" @close="editing = null">
    <form @submit.prevent="save">
      <div v-if="error" class="alert err">{{ error }}</div>
      <div class="field"><label>Name</label><input v-model="form.name" required maxlength="255" placeholder="e.g. CRM sync" /></div>
      <div class="field"><label>Endpoint URL</label><input v-model="form.url" type="url" required placeholder="https://example.com/hooks/marketing" /><small>Must be a public https/http address — private and local addresses are blocked.</small></div>
      <div class="field">
        <label>Send me</label>
        <label class="check"><input v-model="all" type="checkbox" /> Every event</label>
        <template v-if="!all"><label v-for="e in events" :key="e.key" class="check"><input type="checkbox" :checked="form.events.includes(e.key)" @change="toggleEvent(e.key)" /> <code>{{ e.key }}</code> <small>{{ e.label }}</small></label></template>
      </div>
      <label class="check"><input v-model="form.active" type="checkbox" /> Active</label>
      <div class="row" style="margin-top: 16px"><button class="btn" :disabled="!form.events.length">Save</button><button type="button" class="btn ghost" @click="editing = null">Cancel</button></div>
    </form>
  </Modal>

  <Modal v-if="secret" title="Webhook created" @close="secret = null">
    <div class="alert ok">Copy the signing secret now — it is shown <b>only once</b>.</div>
    <div class="field"><label>Signing secret</label><input :value="secret.secret" readonly style="font-family: monospace" @focus="$event.target.select()" /></div>
    <small>Each request has an <code>X-Webhook-Signature: sha256=…</code> header: the HMAC-SHA256 of the raw body, keyed with this secret. Verify it before trusting the payload.</small>
  </Modal>

  <Modal v-if="log" :title="`Deliveries — ${log.hook.name}`" wide @close="log = null">
    <p v-if="!log.items.length" class="empty">No deliveries yet.</p>
    <table v-else>
      <thead><tr><th>When</th><th>Event</th><th>Result</th><th>Response</th></tr></thead>
      <tr v-for="d in log.items" :key="d.id">
        <td>{{ new Date(d.created_at).toLocaleString() }}</td><td><code>{{ d.event }}</code></td>
        <td><span class="badge" :class="d.success ? 'sent' : 'failed'">{{ d.success ? 'OK' : 'failed' }} {{ d.status_code || '' }}</span></td>
        <td><small>{{ d.response }}</small></td>
      </tr>
    </table>
    <Pager :meta="log.meta" @page="(p) => showLog(log.hook, p)" />
  </Modal>
</template>

<style scoped>
.intro { color: #6b7280; margin: -8px 0 18px; max-width: 760px; }
.url { max-width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
code { background: #f1f2f6; padding: 1px 6px; border-radius: 3px; font-size: 12px; }
</style>
