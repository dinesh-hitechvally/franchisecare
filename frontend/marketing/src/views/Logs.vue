<script setup>
import DateInput from '../components/DateInput.vue'
// Message logs: every email and SMS the platform sent (or tried to), including tests and staff notifications.
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api'
import FilterBar from '../components/FilterBar.vue'
import Modal from '../components/Modal.vue'
import RowMenu from '../components/RowMenu.vue'

const route = useRoute()
const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const detail = ref(null)
const f = reactive({ channel: route.query.channel || '', source: route.query.source || '', service: '', status: '', search: '', from: '', to: '', campaign_id: route.query.campaign_id || '' })
const fDirty = computed(() => Object.values(f).some(Boolean))
const fClear = () => Object.keys(f).forEach((k) => (f[k] = ''))

const SOURCES = { campaign: 'Campaign', automation: 'Automation', form: 'Form auto-reply', test: 'Test message', notification: 'Staff notification' }
const SERVICES = {
  default: 'Default', log: 'Log only', gmail: 'Gmail', outlook: 'Outlook', yahoo: 'Yahoo Mail', zoho: 'Zoho Mail', sendgrid: 'SendGrid', mailgun: 'Mailgun',
  ses: 'Amazon SES', brevo: 'Brevo', postmark: 'Postmark', mandrill: 'Mandrill', custom: 'Custom SMTP', google: 'Google', microsoft: 'Microsoft',
  twilio: 'Twilio', vonage: 'Vonage', plivo: 'Plivo', telnyx: 'Telnyx', clicksend: 'ClickSend', messagebird: 'MessageBird', messagemedia: 'MessageMedia', sinch: 'Sinch',
}
const service = (k) => (k ? SERVICES[k] || k : '—')
const statusLabel = (s) => (s === 'logged' ? 'logged only' : s) // logged = written to a log, nothing was delivered

async function load(page = 1) {
  const params = { page }
  for (const k in f) if (f[k]) params[k] = f[k]
  const { data } = await api.get('/logs', { params })
  rows.value = data.data
  meta.value = data
}

async function copy(text) { try { await navigator.clipboard.writeText(text) } catch (e) { /* clipboard blocked */ } }
async function open(l) { detail.value = (await api.get(`/logs/${l.channel}/${l.id}`)).data }

let t
watch(f, () => { clearTimeout(t); t = setTimeout(() => load(), 300) })
onMounted(() => load())

// Messages are sent by the background worker, so a queued one changes status on its own: keep the open page up to date while any is waiting
const poll = setInterval(() => { if (rows.value.some((r) => r.status === 'queued')) load(meta.value.current_page || 1) }, 4000)
onBeforeUnmount(() => clearInterval(poll))
</script>

<template>
  <div class="head"><h1>Message logs <small>({{ meta.total }})</small></h1></div>
  <p class="intro">Every email and SMS that was sent or tried: campaigns, automations, form replies, test messages and staff notifications. Messages marked “logged only” were just written to a log (the Log only email service, or the Log only SMS service): nothing was delivered.</p>

  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="f.search" type="search" placeholder="Search recipient or subject…" />
    <select v-model="f.channel"><option value="">Email and SMS</option><option value="email">Email</option><option value="sms">SMS</option></select>
    <select v-model="f.source"><option value="">All sources</option><option v-for="(l, k) in SOURCES" :key="k" :value="k">{{ l }}</option></select>
    <select v-model="f.service"><option value="">All services</option><option v-for="(l, k) in SERVICES" :key="k" :value="k">{{ l }}</option></select>
    <select v-model="f.status"><option value="">All statuses</option><option value="queued">queued</option><option value="sent">sent</option><option value="logged">logged only</option><option value="failed">failed</option><option value="skipped">skipped</option></select>
    <label>From <DateInput v-model="f.from" /></label>
    <label>To <DateInput v-model="f.to" /></label>
    <button v-if="f.campaign_id" class="btn ghost sm" @click="f.campaign_id = ''">Clear campaign filter ✕</button>
  </FilterBar>

  <div class="card">
    <p v-if="!rows.length" class="empty">Nothing has been sent yet.</p>
    <table v-else>
      <thead><tr><th>When</th><th>Channel</th><th>To</th><th>Sent from</th><th>Service</th><th>Status</th><th>Error / reason</th><th>Opens / clicks</th><th class="act">Actions</th></tr></thead>
      <tr v-for="l in rows" :key="l.channel + l.id">
        <td class="nw">{{ $dt(l.sent_at || l.created_at) }}</td>
        <td><span class="badge" :class="l.channel">{{ l.channel }}</span></td>
        <td>{{ l.recipient }}<br /><small v-if="l.subject">{{ l.subject }}</small></td>
        <td>{{ SOURCES[l.source] || l.source }}<br /><small v-if="l.campaign">{{ l.campaign.name }}</small></td>
        <td>{{ service(l.service) }}</td>
        <td><span class="badge" :class="l.status" :title="l.status === 'logged' ? 'Only written to a log: nothing was delivered' : ''">{{ statusLabel(l.status) }}</span></td>
        <td class="errcell"><div v-if="l.error" class="errmsg" :class="{ soft: l.status === 'skipped' }" :title="l.error" @click="open(l)">{{ l.error }}</div><span v-else class="none">—</span></td>
        <td>{{ l.channel === 'email' && l.source === 'campaign' ? `${l.opened_at ? 'Opened' : '—'} / ${l.clicks}` : '—' }}</td>
        <td class="act"><RowMenu :items="[
          { label: 'View details', run: () => open(l) },
          { label: 'Copy error', run: () => copy(l.error), show: !!l.error },
          { label: 'Campaign report', to: `/campaigns/${l.campaign_id}`, show: !!l.campaign },
          { label: 'Show all to this recipient', run: () => (f.search = l.recipient) },
        ]" /></td>
      </tr>
    </table>
    <div v-if="meta.last_page > 1" class="row" style="justify-content: center; margin-top: 12px">
      <button class="btn ghost sm" :disabled="meta.current_page <= 1" @click="load(meta.current_page - 1)">Previous</button>
      <small>Page {{ meta.current_page }} of {{ meta.last_page }}</small>
      <button class="btn ghost sm" :disabled="meta.current_page >= meta.last_page" @click="load(meta.current_page + 1)">Next</button>
    </div>
  </div>

  <Modal v-if="detail" :title="`${detail.channel.toUpperCase()} to ${detail.recipient}`" wide @close="detail = null">
    <p class="meta"><span class="badge" :class="detail.status">{{ statusLabel(detail.status) }}</span> · {{ SOURCES[detail.source] || detail.source }} · {{ service(detail.service) }} · {{ $dt(detail.sent_at || detail.created_at) }}</p>
    <p v-if="detail.subject"><strong>Subject:</strong> {{ detail.subject }}</p>
    <div v-if="detail.error" class="alert" :class="detail.status === 'skipped' ? 'ok' : 'err'">
      <b>{{ detail.status === 'skipped' ? 'Why it was skipped' : 'Error' }}</b>
      <pre class="errfull">{{ detail.error }}</pre>
    </div>
    <div v-if="detail.hint" class="alert hintbox"><b>What to do:</b> {{ detail.hint }}</div>
    <iframe v-if="detail.channel === 'email' && detail.body" class="preview" sandbox="" :srcdoc="detail.body"></iframe>
    <div v-else-if="detail.body" class="sms-bubble">{{ detail.body }}</div>
    <p v-else><small>No content stored (message was not sent).</small></p>
  </Modal>
</template>

<style scoped>
.nw { white-space: nowrap; }
.errcell { max-width: 320px; }
.errmsg { color: #c62828; font-size: 13px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; word-break: break-word; cursor: pointer; }
.errmsg.soft { color: #6b7280; }
.none { color: #9ca3af; }
.errfull { margin: 6px 0 0; white-space: pre-wrap; word-break: break-word; font: 12px/1.5 Consolas, Menlo, monospace; max-height: 220px; overflow: auto; }
.hintbox { background: #fffbea; border: 1px solid #f3e2a3; color: #5f4b00; }
.meta { color: #6b7280; font-size: 13px; margin: 0 0 10px; }
</style>
