<script setup>
// Audience → Devices: the browsers and apps that can receive push notifications, and which person each belongs to.
import { ref, reactive, onMounted, watch } from 'vue'
import api, { errorMessage, responseMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import Pager from '../components/Pager.vue'
import RowMenu from '../components/RowMenu.vue'

const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const msg = ref('')
const error = ref('')
const f = reactive({ search: '', platform: '', status: '', linked: '' })

const PLATFORM = { web: 'Browser', android: 'Android app', ios: 'iOS app' }
// "example.com/blog/post" instead of the whole address with its protocol and query
const shortUrl = (u) => { try { const x = new URL(u); const s = x.host + (x.pathname === '/' ? '' : x.pathname) + (x.search ? '?…' : ''); return s.length > 48 ? s.slice(0, 47) + '…' : s } catch { return u } }
const dirty = () => Object.values(f).some(Boolean)

async function load(page = 1) {
  loading.value = true
  try {
    const params = { page }
    for (const k in f) if (f[k]) params[k] = f[k]
    const { data } = await api.get('/push/devices', { params })
    rows.value = data.data
    meta.value = data
  } finally { loading.value = false }
}

async function run(fn, ok) {
  msg.value = error.value = ''
  try { const r = await fn(); msg.value = ok || responseMessage(r) || 'Done.'; await load(meta.value.current_page || 1) } catch (e) { error.value = errorMessage(e); load(meta.value.current_page || 1) }
}
const testDevice = (d) => run(() => api.post(`/push/devices/${d.id}/test`))
const remove = (d) => confirm('Remove this device? It can register again later.') && run(() => api.delete(`/push/devices/${d.id}`), 'Device removed.')

watch(f, () => load())
onMounted(() => load())
</script>

<template>
  <div class="head">
    <h1>Devices <small v-if="!loading">({{ meta.total }})</small></h1>
    <div class="row"><router-link to="/devices/history" class="btn ghost">Device histories</router-link><router-link to="/settings/push" class="btn ghost">Push notification settings</router-link></div>
  </div>
  <p class="intro">The browsers and apps that can receive push notifications. A push campaign goes to the devices of the people in its audience, or to All devices (including devices not linked to a person). New devices appear here when a visitor allows notifications on a page or form that has the Push notifications block or field, or an app registers.</p>
  <div v-if="msg" class="alert ok" @click="msg = ''">{{ msg }}</div>
  <div v-if="error" class="alert err" @click="error = ''">{{ error }}</div>

  <FilterBar :dirty="dirty()" @clear="Object.keys(f).forEach((k) => (f[k] = ''))">
    <input v-model="f.search" type="search" placeholder="Search person, email, device or page…" />
    <select v-model="f.platform"><option value="">All types</option><option v-for="(l, k) in PLATFORM" :key="k" :value="k">{{ l }}</option></select>
    <select v-model="f.status"><option value="">Any status</option><option value="active">Active</option><option value="revoked">Turned off</option></select>
    <select v-model="f.linked"><option value="">Linked or not</option><option value="yes">Linked to a person</option><option value="no">Not linked</option></select>
  </FilterBar>

  <div class="card">
    <p v-if="loading && !rows.length" class="empty">Loading…</p>
    <p v-else-if="!rows.length" class="empty">{{ dirty() ? 'No devices match these filters.' : 'No devices yet. Publish a page with the Push notifications block (or a form with the Push notifications field), open it and allow notifications.' }}</p>
    <table v-else>
      <thead><tr><th>Person</th><th>Type</th><th>Device</th><th>Enabled from page</th><th>Status</th><th>Registered</th><th>Last seen</th><th></th></tr></thead>
      <tbody>
        <tr v-for="d in rows" :key="d.id">
          <td>
            <template v-if="d.person"><router-link :to="`/people?search=${encodeURIComponent(d.person.email || d.person.name)}`"><b>{{ d.person.name || d.person.email }}</b></router-link><br /><small class="muted">{{ d.person.email }}</small></template>
            <span v-else class="muted">Not linked: campaigns cannot reach it</span>
          </td>
          <td>{{ PLATFORM[d.platform] }}</td>
          <td>{{ d.label || '—' }}</td>
          <td class="pageurl"><a v-if="d.page_url" :href="d.page_url" target="_blank" rel="noopener noreferrer" :title="d.page_url">{{ shortUrl(d.page_url) }}</a><span v-else class="muted" :title="d.platform === 'web' ? 'Registered before the page was recorded' : 'Apps have no web page'">—</span></td>
          <td><span class="badge" :class="d.status === 'active' ? 'sent' : 'queued'">{{ d.status === 'active' ? 'active' : 'turned off' }}</span></td>
          <td>{{ d.created_at ? $dt(d.created_at) : '—' }}</td>
          <td>{{ d.last_seen_at ? $dt(d.last_seen_at) : '—' }}</td>
          <td class="act"><RowMenu :history="{ type: 'push_device', id: d.id, title: d.label || PLATFORM[d.platform] }" :items="[{ label: 'Send test notification', run: () => testDevice(d), show: d.status === 'active' }, { label: 'Remove device', run: () => remove(d), danger: true }]" /></td>
        </tr>
      </tbody>
    </table>
    <Pager :meta="meta" @page="load" />
  </div>
</template>

<style scoped>
.intro { color: #6b7280; margin: -8px 0 18px; }
.muted { color: #6b7280; }
.pageurl { max-width: 280px; word-break: break-all; }
</style>
