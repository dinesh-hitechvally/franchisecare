<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api, { download, errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import LineChart from '../components/LineChart.vue'

const route = useRoute()
const r = ref(null)
const error = ref('')
const q = ref('')
const status = ref('') // '' | failed | skipped
const has = (t) => String(t || '').toLowerCase().includes(q.value.trim().toLowerCase())
const links = computed(() => (r.value?.top_links || []).filter((l) => has(l.url)))
const problems = computed(() => (r.value?.problems || []).filter((p) => (has(p.recipient) || has(p.error)) && (status.value === '' || p.status === status.value)))
const dirty = computed(() => q.value !== '' || status.value !== '')
const clear = () => { q.value = ''; status.value = '' }

const isEmail = computed(() => r.value?.campaign.type === 'email')
const series = computed(() => [
  { name: 'Opens', color: '#3f51b5', points: r.value.timeline.opens },
  { name: 'Clicks', color: '#e91e63', points: r.value.timeline.clicks },
])
const audience = computed(() => (r.value.campaign.segment ? `Segment: ${r.value.campaign.segment.name}` : r.value.campaign.list?.name || 'All contacts'))

async function exportCsv() {
  try { await download(`/reports/campaigns/${route.params.id}/export`, `campaign-${route.params.id}-recipients.csv`) } catch (e) { error.value = errorMessage(e) }
}

onMounted(async () => { r.value = (await api.get(`/reports/campaigns/${route.params.id}`)).data })
</script>

<template>
  <p v-if="!r" class="empty">Loading…</p>
  <template v-else>
    <div class="head">
      <div>
        <h1>{{ r.campaign.name }}</h1>
        <small>{{ r.campaign.type.toUpperCase() }} · {{ audience }} · <span class="badge" :class="r.campaign.status">{{ r.campaign.status }}</span>
          <template v-if="r.campaign.completed_at"> · finished {{ new Date(r.campaign.completed_at).toLocaleString() }}</template></small>
      </div>
      <div class="row">
        <router-link to="/reports" class="btn ghost">Back</router-link>
        <button class="btn" @click="exportCsv">Export recipients (CSV)</button>
      </div>
    </div>
    <div v-if="error" class="alert err">{{ error }}</div>

    <div class="grid g4">
      <div class="card stat"><div class="n">{{ r.stats.sent }}</div><div class="l">Delivered of {{ r.stats.recipients }} recipients</div></div>
      <div class="card stat"><div class="n" :style="{ color: r.stats.failed ? '#c62828' : '' }">{{ r.stats.failed }}</div><div class="l">Failed · {{ r.stats.skipped }} skipped</div></div>
      <template v-if="isEmail">
        <div class="card stat"><div class="n">{{ r.stats.open_rate }}%</div><div class="l">Open rate · {{ r.stats.opened }} opened</div></div>
        <div class="card stat"><div class="n">{{ r.stats.click_rate }}%</div><div class="l">Click rate · {{ r.stats.clicks }} clicks</div></div>
      </template>
    </div>

    <FilterBar :dirty="dirty" @clear="clear">
      <input v-model="q" type="search" placeholder="Search links and recipients…" />
      <select v-model="status"><option value="">Failed and skipped</option><option value="failed">Failed</option><option value="skipped">Skipped</option></select>
    </FilterBar>

    <template v-if="isEmail">
      <div class="card"><h2>Opens &amp; clicks over time</h2><LineChart :series="series" /></div>
      <div class="grid g2">
        <div class="card">
          <h2>Top links</h2>
          <p v-if="!r.top_links.length" class="muted">No link clicks yet.</p>
          <p v-else-if="!links.length" class="muted">No links match.</p>
          <table v-else>
            <thead><tr><th>Link</th><th>Clicks</th><th>People</th></tr></thead>
            <tr v-for="l in links" :key="l.url"><td class="url" :title="l.url">{{ l.url }}</td><td>{{ l.clicks }}</td><td>{{ l.unique_clicks }}</td></tr>
          </table>
          <p class="muted" style="margin-top: 10px">Click-to-open rate: <b>{{ r.stats.click_to_open }}%</b></p>
        </div>
        <div class="card">
          <h2>Delivery problems</h2>
          <p v-if="!r.problems.length" class="muted">No failed or skipped recipients.</p>
          <p v-else-if="!problems.length" class="muted">No recipients match.</p>
          <table v-else>
            <thead><tr><th>Recipient</th><th>Status</th><th>Reason</th></tr></thead>
            <tr v-for="(p, i) in problems" :key="i"><td>{{ p.recipient }}</td><td><span class="badge" :class="p.status">{{ p.status }}</span></td><td><small>{{ p.error }}</small></td></tr>
          </table>
        </div>
      </div>
    </template>

    <div v-else class="card">
      <h2>Delivery problems</h2>
      <p v-if="!r.problems.length" class="muted">No failed or skipped recipients.</p>
          <p v-else-if="!problems.length" class="muted">No recipients match.</p>
      <table v-else>
        <thead><tr><th>Recipient</th><th>Status</th><th>Reason</th></tr></thead>
        <tr v-for="(p, i) in problems" :key="i"><td>{{ p.recipient }}</td><td><span class="badge" :class="p.status">{{ p.status }}</span></td><td><small>{{ p.error }}</small></td></tr>
      </table>
    </div>
  </template>
</template>

<style scoped>
.muted { color: #6b7280; margin: 0; font-size: 14px; }
.url { max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
