<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '../api'
import { exportCsv } from '../csv'
import LineChart from '../components/LineChart.vue'
import BarList from '../components/BarList.vue'
import RangeSelect from '../components/RangeSelect.vue'

const days = ref(30)
const d = ref(null)
const SOURCES = { campaign: 'Campaigns', automation: 'Automations', form: 'Form replies' }
const SERVICES = { default: 'Default', log: 'Log only', twilio: 'Twilio', vonage: 'Vonage', plivo: 'Plivo', telnyx: 'Telnyx', clicksend: 'ClickSend', messagebird: 'MessageBird', messagemedia: 'MessageMedia', sinch: 'Sinch' }

const series = computed(() => [
  { name: 'Sent', color: '#3f51b5', points: d.value.daily.sent },
  { name: 'Failed', color: '#c62828', points: d.value.daily.failed },
])
const save = () => exportCsv(`sms-campaigns-${days.value}d.csv`, ['Campaign', 'Sent', 'Failed', 'Skipped', 'Delivery rate %'],
  d.value.campaigns.map((c) => [c.name, c.sent, c.failed, c.skipped, c.delivery_rate]))

async function load() { d.value = (await api.get('/analytics/sms', { params: { days: days.value } })).data }
watch(days, load)
onMounted(load)
</script>

<template>
  <div class="head"><h1>SMS analytics</h1><RangeSelect v-model="days" /></div>
  <p v-if="!d" class="empty">Loading…</p>
  <template v-else>
    <div class="grid g4">
      <div class="card stat"><div class="n">{{ d.totals.sent }}</div><div class="l">Texts sent</div></div>
      <div class="card stat"><div class="n">{{ d.totals.success_rate }}%</div><div class="l">Success rate (sent vs failed)</div></div>
      <div class="card stat"><div class="n" :style="{ color: d.totals.failed ? '#c62828' : '' }">{{ d.totals.failed }}</div><div class="l">Failed</div></div>
      <div class="card stat"><div class="n">{{ d.totals.skipped }}</div><div class="l">Skipped · {{ d.totals.logged }} log only</div></div>
    </div>

    <div class="card"><h2>Activity</h2><LineChart :series="series" :height="260" /></div>

    <div class="card">
      <div class="row" style="justify-content: space-between"><h2 style="margin: 0">SMS campaigns</h2><button class="btn ghost sm" :disabled="!d.campaigns.length" @click="save">Export CSV</button></div>
      <p v-if="!d.campaigns.length" class="empty">No SMS campaigns were sent in this period.</p>
      <table v-else>
        <thead><tr><th>Campaign</th><th>Sent</th><th>Failed</th><th>Skipped</th><th>Delivery rate</th></tr></thead>
        <tr v-for="c in d.campaigns" :key="c.id"><td><router-link :to="`/reports/${c.id}`">{{ c.name }}</router-link></td><td>{{ c.sent }}</td><td>{{ c.failed }}</td><td>{{ c.skipped }}</td><td><b>{{ c.delivery_rate }}%</b></td></tr>
      </table>
    </div>

    <div class="grid g2">
      <div class="card"><h2>Sent by SMS service</h2><BarList :items="d.by_service.map((s) => ({ label: SERVICES[s.label] || s.label || 'Default', value: s.value }))" empty="No texts sent in this period." /></div>
      <div class="card"><h2>Sent by</h2><BarList :items="d.by_source.map((s) => ({ label: SOURCES[s.label] || s.label, value: s.value }))" empty="No texts sent in this period." /></div>
    </div>
    <p class="note">"Sent" means the SMS service accepted the text. Whether the phone received it is only known to your SMS service.</p>
  </template>
</template>

<style scoped>
.note { color: #6b7280; font-size: 13px; }
</style>
