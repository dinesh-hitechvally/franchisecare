<script setup>
// Every recipient of a campaign and how it went, filterable by status, one page at a time.
import { ref, reactive, watch, onMounted } from 'vue'
import api from '../api'
import FilterBar from './FilterBar.vue'
import Pager from './Pager.vue'

const props = defineProps({ id: { type: [Number, String], required: true }, type: { type: String, default: 'email' } })

const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const f = reactive({ search: '', status: '' })

const LABELS = { sent: 'Delivered', logged: 'Logged only', failed: 'Failed', skipped: 'Skipped', queued: 'Queued' }
const BADGE = { sent: 'sent', logged: 'logged', failed: 'failed', skipped: 'skipped', queued: 'queued' }

async function load(page = 1) {
  loading.value = true
  try {
    const params = { page }
    for (const k in f) if (f[k]) params[k] = f[k]
    const { data } = await api.get(`/reports/campaigns/${props.id}/deliveries`, { params })
    rows.value = data.data
    meta.value = data
  } finally { loading.value = false }
}

let t
watch(f, () => { clearTimeout(t); t = setTimeout(() => load(), 300) })
onMounted(() => load())
const clear = () => { f.search = ''; f.status = '' }
</script>

<template>
  <div class="card">
    <h2>Delivery Reports <small v-if="!loading">({{ meta.total }})</small></h2>
    <FilterBar :dirty="!!(f.search || f.status)" @clear="clear">
      <input v-model="f.search" type="search" placeholder="Search name or recipient…" />
      <select v-model="f.status">
        <option value="">All statuses</option>
        <option value="sent">Delivered</option>
        <option value="skipped">Skipped</option>
        <option value="failed">Failed</option>
        <option value="logged">Logged only</option>
        <option value="queued">Queued</option>
      </select>
    </FilterBar>
    <p v-if="loading && !rows.length" class="muted">Loading…</p>
    <p v-else-if="!rows.length" class="muted">{{ f.search || f.status ? 'No recipients match.' : 'No recipients yet.' }}</p>
    <table v-else>
      <thead><tr><th>Name</th><th>{{ type === 'sms' ? 'Phone' : 'Email' }}</th><th>Status</th><th>Reason</th></tr></thead>
      <tr v-for="(p, i) in rows" :key="i">
        <td>{{ p.name || '—' }}</td>
        <td>{{ p.recipient || '—' }}</td>
        <td><span class="badge" :class="BADGE[p.status]">{{ LABELS[p.status] || p.status }}</span></td>
        <td><small>{{ p.error || (p.status === 'logged' ? 'Written to a log, not delivered (Log only service)' : '') }}</small></td>
      </tr>
    </table>
    <Pager :meta="meta" @page="load" />
  </div>
</template>

<style scoped>
.muted { color: #6b7280; margin: 0; font-size: 14px; }
</style>
