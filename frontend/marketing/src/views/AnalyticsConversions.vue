<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../api'
import FilterBar from '../components/FilterBar.vue'
import LineChart from '../components/LineChart.vue'
import BarList from '../components/BarList.vue'

const d = ref(null)
const q = ref('')
const state = ref('') // '' | live | paused (forms) / published | draft (pages)
const has = (t) => String(t || '').toLowerCase().includes(q.value.trim().toLowerCase())
const forms = computed(() => d.value.forms.filter((f) => has(f.name) && (state.value === '' || (f.active ? 'live' : 'paused') === state.value)))
const pages = computed(() => d.value.pages.filter((p) => (has(p.name) || has(p.form)) && (state.value === '' || (p.published === false ? 'draft' : 'live') === state.value)))
const dirty = computed(() => q.value !== '' || state.value !== '')
const clear = () => { q.value = ''; state.value = '' }
const SRC = { manual: 'Added manually', import: 'CSV import', form: 'Sign-up form', api: 'API' }
const signups = computed(() => [{ name: 'Submissions', color: '#4caf50', points: d.value.signups_30d }])
const rate = (v) => (v === null || v === undefined ? '—' : v + '%')

onMounted(async () => { d.value = (await api.get('/analytics/conversions')).data })
</script>

<template>
  <div class="head"><h1>Forms &amp; pages analytics</h1></div>
  <p class="intro">A conversion is a visitor who signs up. Conversion rate = sign-ups ÷ views.</p>
  <p v-if="!d" class="empty">Loading…</p>
  <template v-else>
    <div class="grid g4">
      <div class="card stat"><div class="n">{{ d.totals.form_submissions }}</div><div class="l">Form submissions</div></div>
      <div class="card stat"><div class="n">{{ d.totals.form_views }}</div><div class="l">Form views</div></div>
      <div class="card stat"><div class="n">{{ d.totals.overall_rate }}%</div><div class="l">Overall conversion rate</div></div>
      <div class="card stat"><div class="n">{{ d.totals.page_views }}</div><div class="l">Landing page views</div></div>
    </div>

    <div class="card"><h2>Sign-ups from forms &amp; API <small>(last 30 days)</small></h2><LineChart :series="signups" /></div>

    <FilterBar :dirty="dirty" @clear="clear">
      <input v-model="q" type="search" placeholder="Search forms and pages…" />
      <select v-model="state"><option value="">Live and paused</option><option value="live">Live</option><option value="paused">Paused</option></select>
    </FilterBar>

    <div class="grid g2">
      <div class="card">
        <h2>Forms</h2>
        <p v-if="!d.forms.length" class="muted">No forms yet.</p>
        <p v-else-if="!forms.length" class="muted">Nothing matches these filters.</p>
        <table v-else>
          <thead><tr><th>Form</th><th>Views</th><th>Submissions</th><th>Rate</th></tr></thead>
          <tr v-for="f in forms" :key="f.id"><td>{{ f.name }} <span v-if="!f.active" class="badge">paused</span></td><td>{{ f.views }}</td><td>{{ f.submissions }}</td><td><b>{{ rate(f.rate) }}</b></td></tr>
        </table>
      </div>
      <div class="card">
        <h2>Landing pages</h2>
        <p v-if="!d.pages.length" class="muted">No landing pages yet.</p>
        <p v-else-if="!pages.length" class="muted">Nothing matches these filters.</p>
        <table v-else>
          <thead><tr><th>Page</th><th>Views</th><th>Submissions</th><th>Rate</th></tr></thead>
          <tr v-for="p in pages" :key="p.id"><td>{{ p.name }}<br /><small>{{ p.form ? 'Form: ' + p.form : 'No form attached' }}</small></td><td>{{ p.views }}</td><td>{{ p.form ? p.submissions : '—' }}</td><td><b>{{ rate(p.rate) }}</b></td></tr>
        </table>
      </div>
    </div>

    <div class="card"><h2>New people by source <small>(last 30 days)</small></h2><BarList :items="d.by_source_30d.map((s) => ({ label: SRC[s.label] || s.label, value: s.value }))" color="#9c27b0" /></div>
  </template>
</template>

<style scoped>
.intro { color: #6b7280; margin: -8px 0 18px; }
.muted { color: #6b7280; margin: 0; font-size: 14px; }
</style>
