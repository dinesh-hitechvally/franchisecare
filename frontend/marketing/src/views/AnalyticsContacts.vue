<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../api'
import LineChart from '../components/LineChart.vue'
import BarList from '../components/BarList.vue'

const d = ref(null)
const SRC = { manual: 'Added manually', import: 'CSV import', form: 'Sign-up form', api: 'API' }

const growth = computed(() => [{ name: 'New contacts', color: '#3f51b5', points: d.value.growth }])
const pct = (a, b) => (b ? Math.round((a / b) * 100) : 0)
const emailTotal = computed(() => d.value.subscriptions.email_subscribed + d.value.subscriptions.email_unsubscribed)
const smsTotal = computed(() => d.value.subscriptions.sms_subscribed + d.value.subscriptions.sms_unsubscribed)
const engagement = computed(() => [
  { label: 'Opened in the last 30 days', value: d.value.engagement.engaged_30d },
  { label: 'Opened before, not recently', value: d.value.engagement.opened_before },
  { label: 'Received email, never opened', value: d.value.engagement.never_opened },
  { label: 'Never emailed', value: d.value.engagement.not_emailed },
])

onMounted(async () => { d.value = (await api.get('/analytics/contacts')).data })
</script>

<template>
  <div class="head"><h1>Contact Analytics</h1></div>
  <p v-if="!d" class="empty">Loading…</p>
  <template v-else>
    <div class="grid g4">
      <div class="card stat"><div class="n">{{ d.total }}</div><div class="l">Total contacts</div></div>
      <div class="card stat"><div class="n">{{ d.new_30d }}</div><div class="l">New in the last 30 days</div></div>
      <div class="card stat"><div class="n">{{ pct(d.subscriptions.email_subscribed, emailTotal) }}%</div><div class="l">Email subscribed · {{ d.subscriptions.email_unsubscribed }} opted out</div></div>
      <div class="card stat"><div class="n">{{ pct(d.subscriptions.sms_subscribed, smsTotal) }}%</div><div class="l">SMS subscribed · {{ d.subscriptions.sms_unsubscribed }} opted out</div></div>
    </div>

    <div class="card"><h2>Audience growth <small>(last 30 days)</small></h2><LineChart :series="growth" /></div>

    <div class="grid g2">
      <div class="card"><h2>Email engagement</h2><BarList :items="engagement" color="#00bcd4" /></div>
      <div class="card"><h2>Where contacts come from</h2><BarList :items="d.by_source.map((s) => ({ label: SRC[s.label] || s.label, value: s.value }))" color="#9c27b0" /></div>
      <div class="card"><h2>Biggest lists</h2><BarList :items="d.by_list" empty="No lists yet." /></div>
      <div class="card"><h2>Most-used tags</h2><BarList :items="d.by_tag" color="#ff9800" empty="No tags yet." /></div>
    </div>
  </template>
</template>
