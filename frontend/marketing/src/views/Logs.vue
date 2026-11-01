<script setup>
// Send history: every email and SMS the platform sent (or tried to), including tests and staff notifications.
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api'
import FilterBar from '../components/FilterBar.vue'
import Modal from '../components/Modal.vue'

const route = useRoute()
const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const detail = ref(null)
const f = reactive({ channel: route.query.channel || '', source: route.query.source || '', service: '', status: '', search: '', from: '', to: '', campaign_id: route.query.campaign_id || '' })
const fDirty = computed(() => Object.values(f).some(Boolean))
const fClear = () => Object.keys(f).forEach((k) => (f[k] = ''))

const SOURCES = { campaign: 'Campaign', automation: 'Automation', form: 'Form auto-reply', test: 'Test message', notification: 'Staff notification' }
const SERVICES = {
  default: 'Default mail server', log: 'SMS test mode', gmail: 'Gmail', outlook: 'Outlook', yahoo: 'Yahoo Mail', zoho: 'Zoho Mail', sendgrid: 'SendGrid', mailgun: 'Mailgun',
  ses: 'Amazon SES', brevo: 'Brevo', postmark: 'Postmark', mandrill: 'Mandrill', custom: 'Custom SMTP', google: 'Google', microsoft: 'Microsoft',
  twilio: 'Twilio', vonage: 'Vonage', plivo: 'Plivo', telnyx: 'Telnyx', clicksend: 'ClickSend', messagebird: 'MessageBird', sinch: 'Sinch',
}
const service = (k) => (k ? SERVICES[k] || k : '—')

async function load(page = 1) {
  const params = { page }
  for (const k in f) if (f[k]) params[k] = f[k]
  const { data } = await api.get('/logs', { params })
  rows.value = data.data
  meta.value = data
}

async function open(l) { detail.value = (await api.get(`/logs/${l.channel}/${l.id}`)).data }

let t
watch(f, () => { clearTimeout(t); t = setTimeout(() => load(), 300) })
onMounted(() => load())
</script>

<template>
  <div class="head"><h1>Send history <small>({{ meta.total }})</small></h1></div>
  <p class="intro">Every email and SMS that was sent or tried: campaigns, automations, form replies, test messages and staff notifications. In SMS test mode, texts are listed here but never leave the server.</p>

  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="f.search" type="search" placeholder="Search recipient or subject…" />
    <select v-model="f.channel"><option value="">Email and SMS</option><option value="email">Email</option><option value="sms">SMS</option></select>
    <select v-model="f.source"><option value="">All kinds</option><option v-for="(l, k) in SOURCES" :key="k" :value="k">{{ l }}</option></select>
    <select v-model="f.service"><option value="">All services</option><option v-for="(l, k) in SERVICES" :key="k" :value="k">{{ l }}</option></select>
    <select v-model="f.status"><option value="">All statuses</option><option>queued</option><option>sent</option><option>failed</option><option>skipped</option></select>
    <label>From <input v-model="f.from" type="date" /></label>
    <label>To <input v-model="f.to" type="date" /></label>
    <button v-if="f.campaign_id" class="btn ghost sm" @click="f.campaign_id = ''">Clear campaign filter ✕</button>
  </FilterBar>

  <div class="card">
    <p v-if="!rows.length" class="empty">Nothing has been sent yet.</p>
    <table v-else>
      <thead><tr><th>When</th><th>Channel</th><th>To</th><th>Kind</th><th>Service</th><th>Status</th><th>Opens / clicks</th><th class="act">Actions</th></tr></thead>
      <tr v-for="l in rows" :key="l.channel + l.id">
        <td class="nw">{{ new Date(l.sent_at || l.created_at).toLocaleString() }}</td>
        <td><span class="badge" :class="l.channel">{{ l.channel }}</span></td>
        <td>{{ l.recipient }}<br /><small v-if="l.subject">{{ l.subject }}</small></td>
        <td>{{ SOURCES[l.source] || l.source }}<br /><small v-if="l.campaign">{{ l.campaign.name }}</small></td>
        <td>{{ service(l.service) }}</td>
        <td><span class="badge" :class="l.status">{{ l.status }}</span><br /><small v-if="l.error">{{ l.error }}</small></td>
        <td>{{ l.channel === 'email' && l.source === 'campaign' ? `${l.opened_at ? 'Opened' : '—'} / ${l.clicks}` : '—' }}</td>
        <td class="act"><button class="btn ghost sm" @click="open(l)">View</button></td>
      </tr>
    </table>
    <div v-if="meta.last_page > 1" class="row" style="justify-content: center; margin-top: 12px">
      <button class="btn ghost sm" :disabled="meta.current_page <= 1" @click="load(meta.current_page - 1)">Previous</button>
      <small>Page {{ meta.current_page }} of {{ meta.last_page }}</small>
      <button class="btn ghost sm" :disabled="meta.current_page >= meta.last_page" @click="load(meta.current_page + 1)">Next</button>
    </div>
  </div>

  <Modal v-if="detail" :title="`${detail.channel.toUpperCase()} to ${detail.recipient}`" wide @close="detail = null">
    <p class="meta"><span class="badge" :class="detail.status">{{ detail.status }}</span> · {{ SOURCES[detail.source] || detail.source }} · {{ service(detail.service) }} · {{ new Date(detail.sent_at || detail.created_at).toLocaleString() }}</p>
    <p v-if="detail.subject"><strong>Subject:</strong> {{ detail.subject }}</p>
    <p v-if="detail.error" class="alert err">{{ detail.error }}</p>
    <iframe v-if="detail.channel === 'email' && detail.body" class="preview" sandbox="" :srcdoc="detail.body"></iframe>
    <div v-else-if="detail.body" class="sms-bubble">{{ detail.body }}</div>
    <p v-else><small>No content stored (message was not sent).</small></p>
  </Modal>
</template>

<style scoped>
.nw { white-space: nowrap; }
.meta { color: #6b7280; font-size: 13px; margin: 0 0 10px; }
</style>
