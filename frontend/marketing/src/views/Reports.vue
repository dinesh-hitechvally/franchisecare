<script setup>
import DateInput from '../components/DateInput.vue'
import { ref, reactive, computed, onMounted, watch } from 'vue'
import api from '../api'
import FilterBar from '../components/FilterBar.vue'
import Pager from '../components/Pager.vue'

const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const f = reactive({ search: '', type: '', status: '', from: '', to: '' })
const fDirty = computed(() => Object.values(f).some(Boolean))
const fClear = () => Object.keys(f).forEach((k) => (f[k] = ''))

async function load(page = 1) {
  const { data } = await api.get('/reports/campaigns', { params: { page, ...Object.fromEntries(Object.entries(f).filter(([, v]) => v)) } })
  rows.value = data.data
  meta.value = data
}

let t
watch(f, () => { clearTimeout(t); t = setTimeout(() => load(), 300) })
onMounted(() => load())
</script>

<template>
  <div class="head"><h1>Campaign Reports</h1></div>
  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="f.search" type="search" placeholder="Search campaigns…" />
    <select v-model="f.type"><option value="">All channels</option><option value="email">Email</option><option value="sms">SMS</option></select>
    <select v-model="f.status"><option value="">All statuses</option><option value="sent">Sent</option><option value="sending">Sending</option><option value="failed">Failed</option></select>
    <label>From <DateInput v-model="f.from" /></label>
    <label>To <DateInput v-model="f.to" /></label>
  </FilterBar>
  <div class="card">
    <p v-if="!rows.length" class="empty">Reports appear here once a campaign has been sent.</p>
    <table v-else>
      <thead><tr><th>Campaign</th><th>Channel</th><th>Sent</th><th>Skipped</th><th>Failed</th><th>Open rate</th><th>Click rate</th><th>Date</th><th></th></tr></thead>
      <tr v-for="c in rows" :key="c.id">
        <td><router-link :to="`/reports/${c.id}`">{{ c.name }}</router-link></td>
        <td><span class="badge" :class="c.type">{{ c.type }}</span></td>
        <td>{{ c.sent_count }}</td>
        <td>{{ c.skipped_count }}</td>
        <td>{{ c.failed_count }}</td>
        <td>{{ c.type === 'email' ? c.open_rate + '%' : '—' }}</td>
        <td>{{ c.type === 'email' ? c.click_rate + '%' : '—' }}</td>
        <td>{{ $d(c.completed_at || c.created_at) }}</td>
        <td style="text-align: right"><router-link :to="`/reports/${c.id}`" class="btn ghost sm">View report</router-link></td>
      </tr>
    </table>
    <Pager :meta="meta" @page="load" />
  </div>
</template>
