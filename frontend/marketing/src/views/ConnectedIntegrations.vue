<script setup>
// Integrations → All integrations: every store, site, CRM, email or payments account that is connected, or was disconnected, as a filterable table.
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import api, { errorMessage, responseMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import RowMenu from '../components/RowMenu.vue'

const rows = ref([])
const providers = ref([])
const loading = ref(true)
const msg = ref('')
const error = ref('')
const f = reactive({ q: '', category: '', provider: '', status: '', direction: '' })

const CATEGORIES = { store: 'Online store', website: 'Website', crm: 'CRM', email: 'Email marketing', payments: 'Payments', social: 'Social media', messaging: 'Messaging' }
const DIRECTION = { import: 'Import only', export: 'Send only', both: 'Both ways' }
const EVERY = { 0: 'Manual only', 15: 'Every 15 minutes', 60: 'Every hour', 360: 'Every 6 hours', 1440: 'Every day' }
const dirOf = (c) => c.settings?.direction || 'import'
const statusOf = (c) => (c.status === 'disconnected' ? 'disconnected' : c.running ? 'syncing' : c.status === 'connected' ? 'connected' : 'attention')

const used = computed(() => providers.value.filter((p) => rows.value.some((c) => c.provider === p.key))) // only platforms that are connected
const filtered = computed(() => {
  const q = f.q.trim().toLowerCase()
  return rows.value.filter((c) =>
    (!q || [c.name, c.store_url, c.provider_label].some((v) => (v || '').toLowerCase().includes(q)))
    && (!f.category || c.category === f.category)
    && (!f.provider || c.provider === f.provider)
    && (!f.status || statusOf(c) === f.status)
    && (!f.direction || dirOf(c) === f.direction))
})
const dirty = computed(() => Object.values(f).some(Boolean))
const clear = () => Object.keys(f).forEach((k) => { f[k] = '' })

const load = async () => { rows.value = (await api.get('/integrations', { params: { include_disconnected: 1 } })).data }

// the “⋮” menu of a row
const menu = (c) => (c.status === 'disconnected'
  ? [
      { label: 'Connect again', to: { path: '/integrations', query: { connect: c.provider, name: c.name, url: c.uses_url ? c.store_url : undefined } } },
      { label: 'Sync history', to: `/integrations/${c.id}/history` },
    ]
  : c.channel ? [ // a messaging account (WhatsApp, Messenger, X): nothing to sync
      { label: 'Settings', to: `/integrations/${c.id}/settings` },
      { label: 'Check connection', run: () => check(c) },
      { label: 'Disconnect', run: () => disconnect(c), danger: true },
    ]
  : [
      { label: 'Sync now', run: () => syncNow(c) }, // always offered: while one is running the server answers that it is already running
      { label: 'Settings', to: `/integrations/${c.id}/settings` },
      { label: 'Check connection', run: () => check(c) },
      { label: 'Full re-import', run: () => syncNow(c, true) },
      { label: 'Sync history', to: `/integrations/${c.id}/history` },
      { label: 'Disconnect', run: () => disconnect(c), danger: true },
    ])

async function syncNow(c, full = false) {
  error.value = msg.value = ''
  if (full && !confirm('Read every customer again? People who are already here are not duplicated or changed, only completed.')) return
  try {
    msg.value = responseMessage(await api.post(`/integrations/${c.id}/sync`, { full }), 'Sync started.')
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

let timer
onMounted(async () => {
  try {
    ;providers.value = (await api.get('/integrations/providers')).data
    await load()
  } finally { loading.value = false }
  timer = setInterval(() => { if (rows.value.some((c) => c.running)) load().catch(() => {}) }, 3000) // a running sync updates itself
})
onBeforeUnmount(() => clearInterval(timer))
</script>

<template>
  <div class="head">
    <h1>All integrations <small v-if="!loading">({{ filtered.length }}{{ dirty ? ' of ' + rows.length : '' }})</small></h1>
    <div class="row"><router-link to="/integrations" class="btn">Connect a platform</router-link></div>
  </div>
  <p class="intro">Every store, website, CRM, email-marketing and payments account you have connected, including the ones you disconnected. The people already imported from a disconnected integration stay in People.</p>
  <div v-if="msg" class="alert ok" @click="msg = ''">{{ msg }}</div>
  <div v-if="error" class="alert err" @click="error = ''">{{ error }}</div>

  <FilterBar :dirty="dirty" @clear="clear">
    <input v-model="f.q" type="search" placeholder="Search name or address…" />
    <select v-model="f.category"><option value="">All types</option><option v-for="(label, key) in CATEGORIES" :key="key" :value="key">{{ label }}</option></select>
    <select v-model="f.provider"><option value="">All platforms</option><option v-for="p in used" :key="p.key" :value="p.key">{{ p.label }}</option></select>
    <select v-model="f.status"><option value="">All statuses</option><option value="connected">Connected</option><option value="syncing">Syncing</option><option value="attention">Needs attention</option><option value="disconnected">Disconnected</option></select>
    <select v-model="f.direction"><option value="">Any direction</option><option v-for="(label, key) in DIRECTION" :key="key" :value="key">{{ label }}</option></select>
  </FilterBar>

  <div class="card">
    <p v-if="loading" class="empty">Loading…</p>
    <p v-else-if="!rows.length" class="empty">Nothing has been connected yet. <router-link to="/integrations">Connect a platform</router-link> to import its customers.</p>
    <p v-else-if="!filtered.length" class="empty">No connections match these filters.</p>
    <table v-else>
      <thead><tr><th>Connection</th><th>Platform</th><th>Status</th><th>Direction</th><th>People imported</th><th>Last synced</th><th>Automatic sync</th><th></th></tr></thead>
      <tbody>
        <template v-for="c in filtered" :key="c.id">
          <tr>
            <td :class="{ off: c.status === 'disconnected' }"><b>{{ c.name }}</b><template v-if="c.uses_url"><br /><small class="muted">{{ c.store_url }}</small></template></td>
            <td>{{ c.provider_label }}<br /><small class="muted">{{ CATEGORIES[c.category] || '' }}</small></td>
            <td>
              <span v-if="c.status === 'disconnected'" class="badge off" :title="'Disconnected ' + $dt(c.disconnected_at)">disconnected</span>
              <span v-else-if="c.running" class="badge queued">syncing…</span>
              <span v-else class="badge" :class="c.status === 'connected' ? 'sent' : 'failed'">{{ c.status === 'connected' ? 'connected' : 'needs attention' }}</span>
            </td>
            <template v-if="c.channel"><td colspan="4" class="muted">Sends campaign messages ({{ CATEGORIES[c.category] }} channel)</td></template>
            <template v-else>
              <td>{{ DIRECTION[dirOf(c)] }}</td>
              <td>{{ c.imported }}</td>
              <td>{{ c.last_synced_at ? $dt(c.last_synced_at) : 'never' }}</td>
              <td>{{ c.status === 'disconnected' ? '–' : EVERY[c.settings?.sync_every ?? 0] || 'Manual only' }}</td>
            </template>
            <td class="act"><RowMenu :history="{ type: 'integration', id: c.id, title: c.name }" :items="menu(c)" /></td>
          </tr>
          <tr v-if="c.last_error"><td colspan="8" class="errrow">{{ c.last_error }}</td></tr>
        </template>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.intro { color: #6b7280; margin: -8px 0 18px; }
.muted { color: #6b7280; word-break: break-all; }
.off { color: #6b7280; }
.badge.off { background: #eceff1; color: #546e7a; }
.errrow { background: #fdecea; color: #b71c1c; font-size: 13.5px; }
</style>
