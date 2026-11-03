<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '../api'
import { exportCsv } from '../csv'
import LineChart from '../components/LineChart.vue'
import RangeSelect from '../components/RangeSelect.vue'

// Did the messages arrive, and when not, why. Failed = tried and the service refused; skipped = deliberately not sent.
const days = ref(30)
const d = ref(null)
const SERVICES = {
  default: 'Default', log: 'Log only', gmail: 'Gmail', outlook: 'Outlook', yahoo: 'Yahoo Mail', zoho: 'Zoho Mail', sendgrid: 'SendGrid', mailgun: 'Mailgun', ses: 'Amazon SES', brevo: 'Brevo',
  postmark: 'Postmark', mandrill: 'Mandrill', custom: 'Custom SMTP', google: 'Google', microsoft: 'Microsoft', twilio: 'Twilio', vonage: 'Vonage', plivo: 'Plivo', telnyx: 'Telnyx',
  clicksend: 'ClickSend', messagebird: 'MessageBird', messagemedia: 'MessageMedia', sinch: 'Sinch',
}
const svc = (k) => SERVICES[k] || k || 'Default'
const chan = (c) => (c === 'sms' ? 'SMS' : 'Email')

const failedDaily = computed(() => [{ name: 'Failed', color: '#c62828', points: d.value.daily_failed }])
const tiles = computed(() => [
  [d.value.totals.sent, 'Sent', ''], [d.value.totals.failed, 'Failed', d.value.totals.failed ? '#c62828' : ''],
  [d.value.totals.skipped, 'Skipped', ''], [`${d.value.totals.success_rate}%`, 'Success rate', ''],
])
const save = () => exportCsv(`delivery-by-service-${days.value}d.csv`, ['Channel', 'Service', 'Sent', 'Failed', 'Skipped', 'Log only', 'Success rate %'],
  d.value.by_service.map((r) => [chan(r.channel), svc(r.service), r.sent, r.failed, r.skipped, r.logged, r.success_rate]))

async function load() { d.value = (await api.get('/analytics/delivery', { params: { days: days.value } })).data }
watch(days, load)
onMounted(load)
</script>

<template>
  <div class="head"><h1>Delivery &amp; errors</h1><RangeSelect v-model="days" /></div>
  <p class="intro">Every email and text in one place. <b>Failed</b> means the service refused it; <b>skipped</b> means it was left out on purpose (unsubscribed, or no email address or number).</p>
  <p v-if="!d" class="empty">Loading…</p>
  <template v-else>
    <div class="grid g4">
      <div v-for="[n, l, color] in tiles" :key="l" class="card stat"><div class="n" :style="{ color }">{{ n }}</div><div class="l">{{ l }}</div></div>
    </div>

    <div class="card"><h2>Failures per day</h2><LineChart :series="failedDaily" :height="200" /></div>

    <div class="grid g2">
      <div class="card">
        <h2>Why messages failed</h2>
        <p v-if="!d.failed_reasons.length" class="empty">Nothing failed in this period.</p>
        <table v-else>
          <thead><tr><th>Reason</th><th>Channel</th><th>Times</th></tr></thead>
          <tr v-for="r in d.failed_reasons" :key="r.reason"><td>{{ r.reason }}</td><td>{{ r.channels.map(chan).join(', ') }}</td><td><b>{{ r.count }}</b></td></tr>
        </table>
      </div>
      <div class="card">
        <h2>Why messages were skipped</h2>
        <p v-if="!d.skipped_reasons.length" class="empty">Nothing was skipped in this period.</p>
        <table v-else>
          <thead><tr><th>Reason</th><th>Channel</th><th>Times</th></tr></thead>
          <tr v-for="r in d.skipped_reasons" :key="r.reason"><td>{{ r.reason }}</td><td>{{ r.channels.map(chan).join(', ') }}</td><td><b>{{ r.count }}</b></td></tr>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="row" style="justify-content: space-between"><h2 style="margin: 0">By service</h2><button class="btn ghost sm" :disabled="!d.by_service.length" @click="save">Export CSV</button></div>
      <p v-if="!d.by_service.length" class="empty">Nothing was sent in this period.</p>
      <table v-else>
        <thead><tr><th>Channel</th><th>Service</th><th>Sent</th><th>Failed</th><th>Skipped</th><th>Log only</th><th>Success rate</th></tr></thead>
        <tr v-for="r in d.by_service" :key="r.channel + r.service"><td>{{ chan(r.channel) }}</td><td>{{ svc(r.service) }}</td><td>{{ r.sent }}</td><td :style="{ color: r.failed ? '#c62828' : '' }">{{ r.failed }}</td><td>{{ r.skipped }}</td><td>{{ r.logged }}</td><td><b>{{ r.success_rate }}%</b></td></tr>
      </table>
    </div>

    <div class="card">
      <div class="row" style="justify-content: space-between"><h2 style="margin: 0">Latest failures</h2><router-link to="/message-logs" class="btn ghost sm">Open message logs</router-link></div>
      <p v-if="!d.recent_failures.length" class="empty">No failures in this period.</p>
      <table v-else>
        <thead><tr><th>When</th><th>Channel</th><th>To</th><th>Service</th><th>Error</th></tr></thead>
        <tr v-for="(r, i) in d.recent_failures" :key="i"><td style="white-space: nowrap">{{ $dt(r.when) }}</td><td>{{ chan(r.channel) }}</td><td>{{ r.to }}</td><td>{{ svc(r.service) }}</td><td class="err">{{ r.error }}</td></tr>
      </table>
    </div>
  </template>
</template>

<style scoped>
.err { color: #c62828; max-width: 420px; }
</style>
