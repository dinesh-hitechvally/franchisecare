<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '../api'
import { exportCsv } from '../csv'
import LineChart from '../components/LineChart.vue'
import BarList from '../components/BarList.vue'
import RangeSelect from '../components/RangeSelect.vue'

const days = ref(30)
const d = ref(null)
const SOURCES = { campaign: 'Campaigns', automation: 'Automations', form: 'Form auto-replies' }

const series = computed(() => [
  { name: 'Sent', color: '#3f51b5', points: d.value.daily.sent },
  { name: 'Opened', color: '#00bcd4', points: d.value.daily.opened },
  { name: 'Clicked', color: '#e91e63', points: d.value.daily.clicked },
])
const save = () => exportCsv(`email-campaigns-${days.value}d.csv`, ['Campaign', 'Sent', 'Opened', 'Open rate %', 'Clicked', 'Click rate %'],
  d.value.campaigns.map((c) => [c.name, c.sent, c.opened, c.open_rate, c.clicked, c.click_rate]))

async function load() { d.value = (await api.get('/analytics/email', { params: { days: days.value } })).data }
watch(days, load)
onMounted(load)
</script>

<template>
  <div class="head"><h1>Email analytics</h1><RangeSelect v-model="days" /></div>
  <p v-if="!d" class="empty">Loading…</p>
  <template v-else>
    <div class="grid g4">
      <div class="card stat"><div class="n">{{ d.totals.sent }}</div><div class="l">Emails sent</div></div>
      <div class="card stat"><div class="n">{{ d.totals.open_rate }}%</div><div class="l">Open rate · {{ d.totals.opened }} opened</div></div>
      <div class="card stat"><div class="n">{{ d.totals.click_rate }}%</div><div class="l">Click rate · {{ d.totals.total_clicks }} clicks</div></div>
      <div class="card stat"><div class="n" :style="{ color: d.totals.failed ? '#c62828' : '' }">{{ d.totals.failed }}</div><div class="l">Failed · {{ d.totals.skipped }} skipped</div></div>
    </div>

    <div class="card"><h2>Activity</h2><LineChart :series="series" :height="260" /></div>

    <div class="card">
      <div class="row" style="justify-content: space-between"><h2 style="margin: 0">Campaigns</h2><button class="btn ghost sm" :disabled="!d.campaigns.length" @click="save">Export CSV</button></div>
      <p v-if="!d.campaigns.length" class="empty">No email campaigns were sent in this period.</p>
      <table v-else>
        <thead><tr><th>Campaign</th><th>Sent</th><th>Opened</th><th>Open rate</th><th>Clicked</th><th>Click rate</th></tr></thead>
        <tr v-for="c in d.campaigns" :key="c.id"><td><router-link :to="`/reports/${c.id}`">{{ c.name }}</router-link></td><td>{{ c.sent }}</td><td>{{ c.opened }}</td><td><b>{{ c.open_rate }}%</b></td><td>{{ c.clicked }}</td><td><b>{{ c.click_rate }}%</b></td></tr>
      </table>
    </div>

    <div class="grid g2">
      <div class="card"><h2>Most-clicked links</h2><BarList :items="d.top_links.map((l) => ({ label: l.url, value: l.clicks }))" color="#e91e63" empty="No link clicks in this period." /></div>
      <div class="card"><h2>Sent by</h2><BarList :items="d.by_source.map((s) => ({ label: SOURCES[s.label] || s.label, value: s.value }))" empty="Nothing sent in this period." /></div>
    </div>
    <p class="note">Opens are counted when the email's tracking image loads, so some opens are missed (images blocked) and a few are machine opens. Use the rates to compare campaigns with each other.</p>
  </template>
</template>

<style scoped>
.note { color: #6b7280; font-size: 13px; }
</style>
