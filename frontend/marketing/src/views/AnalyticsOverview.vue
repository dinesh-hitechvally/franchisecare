<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '../api'
import LineChart from '../components/LineChart.vue'
import RangeSelect from '../components/RangeSelect.vue'

// One-page summary of everything, each headline number compared with the period before it.
const days = ref(30)
const d = ref(null)

const messages = computed(() => [{ name: 'Messages sent', color: '#3f51b5', points: d.value.daily.messages }])
const people = computed(() => [{ name: 'New people', color: '#00a152', points: d.value.daily.people }])
const subs = computed(() => [{ name: 'Form submissions', color: '#e91e63', points: d.value.daily.submissions }])
const tiles = computed(() => [
  ['Messages sent', d.value.metrics.messages, `${d.value.totals.emails} email · ${d.value.totals.sms} SMS`],
  ['New people', d.value.metrics.new_people, `${d.value.totals.people} people in total`],
  ['Form submissions', d.value.metrics.submissions, 'From all forms and pages'],
  ['Automation runs started', d.value.metrics.automation_runs, 'People who entered a workflow'],
])
const change = (m) => (m.change === null ? 'no data before' : `${m.change > 0 ? '▲' : m.change < 0 ? '▼' : '•'} ${Math.abs(m.change)}% vs previous ${days.value} days`)
const tone = (m) => (m.change > 0 ? '#2e7d32' : m.change < 0 ? '#c62828' : '#6b7280')

async function load() { d.value = (await api.get('/analytics/overview', { params: { days: days.value } })).data }
watch(days, load)
onMounted(load)
</script>

<template>
  <div class="head"><h1>Analytics overview</h1><RangeSelect v-model="days" /></div>
  <p class="intro">How your marketing is doing across email, SMS, people, forms and automations. Open a report below for the detail.</p>
  <p v-if="!d" class="empty">Loading…</p>
  <template v-else>
    <div class="grid g4">
      <div v-for="[label, m, sub] in tiles" :key="label" class="card stat">
        <div class="n">{{ m.value }}</div><div class="l">{{ label }}</div>
        <small :style="{ color: tone(m) }">{{ change(m) }}</small><br /><small class="sub">{{ sub }}</small>
      </div>
    </div>

    <div class="grid g4">
      <div class="card stat"><div class="n">{{ d.totals.open_rate }}%</div><div class="l">Email open rate</div></div>
      <div class="card stat"><div class="n">{{ d.totals.click_rate }}%</div><div class="l">Email click rate</div></div>
      <div class="card stat"><div class="n" :style="{ color: d.totals.failed ? '#c62828' : '' }">{{ d.totals.failed }}</div><div class="l">Failed messages</div></div>
      <div class="card stat"><div class="n">{{ d.totals.subscribed }}</div><div class="l">Subscribed · {{ d.totals.unsubscribed }} unsubscribed</div></div>
    </div>

    <div class="grid g2">
      <div class="card"><h2>Messages sent</h2><LineChart :series="messages" /></div>
      <div class="card"><h2>New people</h2><LineChart :series="people" /></div>
    </div>
    <div class="card"><h2>Form submissions</h2><LineChart :series="subs" :height="200" /></div>

    <div class="card">
      <h2>Reports</h2>
      <div class="links">
        <router-link to="/analytics/email">Email<small>Opens, clicks, best campaigns, top links</small></router-link>
        <router-link to="/analytics/sms">SMS<small>Sent, failed, by provider</small></router-link>
        <router-link to="/analytics/delivery">Delivery &amp; errors<small>Why messages failed or were skipped</small></router-link>
        <router-link to="/analytics/people">People<small>Growth, sources, subscriptions</small></router-link>
        <router-link to="/analytics/conversions">Forms &amp; pages<small>Views, submissions, conversion</small></router-link>
        <router-link to="/analytics/automation">Automation<small>Workflow runs and results</small></router-link>
      </div>
    </div>
  </template>
</template>

<style scoped>
.sub { color: #6b7280; }
.links { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
.links a { display: block; border: 1px solid #e0e3f0; border-radius: 8px; padding: 12px 14px; font-weight: 600; text-decoration: none; }
.links a:hover { background: #f5f6fd; }
.links small { display: block; font-weight: 400; color: #6b7280; margin-top: 2px; }
</style>
