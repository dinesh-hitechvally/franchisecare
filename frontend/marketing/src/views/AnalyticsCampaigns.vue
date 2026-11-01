<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '../api'
import LineChart from '../components/LineChart.vue'
import BarList from '../components/BarList.vue'

const days = ref(30)
const d = ref(null)

const series = computed(() => [
  { name: 'Sent', color: '#3f51b5', points: d.value.daily.sent },
  { name: 'Opened', color: '#00bcd4', points: d.value.daily.opened },
  { name: 'Clicked', color: '#e91e63', points: d.value.daily.clicked },
])

async function load() { d.value = (await api.get('/analytics/campaigns', { params: { days: days.value } })).data }
watch(days, load)
onMounted(load)
</script>

<template>
  <div class="head">
    <h1>Campaign Analytics</h1>
    <select v-model.number="days" style="width: 160px"><option :value="7">Last 7 days</option><option :value="14">Last 14 days</option><option :value="30">Last 30 days</option><option :value="60">Last 60 days</option><option :value="90">Last 90 days</option></select>
  </div>
  <p v-if="!d" class="empty">Loading…</p>
  <template v-else>
    <div class="grid g4">
      <div class="card stat"><div class="n">{{ d.totals.sent }}</div><div class="l">Messages sent · {{ d.totals.emails }} email, {{ d.totals.sms }} SMS</div></div>
      <div class="card stat"><div class="n">{{ d.totals.open_rate }}%</div><div class="l">Email open rate</div></div>
      <div class="card stat"><div class="n">{{ d.totals.click_rate }}%</div><div class="l">Email click rate</div></div>
      <div class="card stat"><div class="n" :style="{ color: d.totals.failed ? '#c62828' : '' }">{{ d.totals.failed }}</div><div class="l">Failed · {{ d.totals.campaigns }} campaigns created</div></div>
    </div>

    <div class="card"><h2>Activity</h2><LineChart :series="series" :height="260" /></div>

    <div class="grid g2">
      <div class="card"><h2>Best-performing emails <small>(open rate)</small></h2><BarList :items="d.top_campaigns.map((c) => ({ label: c.name, value: c.open_rate }))" suffix="%" empty="No sent email campaigns in this period." /></div>
      <div class="card"><h2>Most-clicked links</h2><BarList :items="d.top_links.map((l) => ({ label: l.url, value: l.clicks }))" color="#e91e63" empty="No link clicks in this period." /></div>
    </div>
  </template>
</template>
