<script setup>
// The last few test messages of one channel, so a test send is visible right where it was made.
import { ref, watch, onMounted } from 'vue'
import api from '../api'

const props = defineProps({ channel: String, tick: { type: Number, default: 0 } }) // change `tick` to reload
const rows = ref([])

const SERVICES = {
  default: 'Default mail server', log: 'Test mode (not sent)', gmail: 'Gmail', outlook: 'Outlook', yahoo: 'Yahoo Mail', zoho: 'Zoho Mail', sendgrid: 'SendGrid', mailgun: 'Mailgun',
  ses: 'Amazon SES', brevo: 'Brevo', postmark: 'Postmark', mandrill: 'Mandrill', custom: 'Custom SMTP', google: 'Google', microsoft: 'Microsoft',
  twilio: 'Twilio', vonage: 'Vonage', plivo: 'Plivo', telnyx: 'Telnyx', clicksend: 'ClickSend', messagebird: 'MessageBird', sinch: 'Sinch',
}

async function load() {
  try { rows.value = (await api.get('/logs', { params: { channel: props.channel, source: 'test', per_page: 5 } })).data.data } catch (e) { rows.value = [] }
}
onMounted(load)
watch(() => props.tick, load)
</script>

<template>
  <div class="recent">
    <div class="rhead"><b>Recent test messages</b><router-link :to="`/logs?channel=${channel}`">View all send history →</router-link></div>
    <p v-if="!rows.length" class="none">Nothing yet. Send a test above and it appears here.</p>
    <table v-else>
      <thead><tr><th>When</th><th>To</th><th>Service</th><th>Result</th></tr></thead>
      <tr v-for="r in rows" :key="r.id">
        <td class="nw">{{ new Date(r.sent_at || r.created_at).toLocaleString() }}</td>
        <td>{{ r.recipient }}</td>
        <td>{{ SERVICES[r.service] || r.service || '—' }}</td>
        <td><span class="badge" :class="r.status">{{ r.status }}</span><small v-if="r.error" class="err"> {{ r.error }}</small></td>
      </tr>
    </table>
  </div>
</template>

<style scoped>
.recent { margin-top: 18px; border-top: 1px solid #eceff1; padding-top: 14px; }
.rhead { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 14px; }
.none { color: #6b7280; font-size: 14px; margin: 0; }
.nw { white-space: nowrap; }
.err { color: #c62828; }
table { font-size: 13px; }
</style>
