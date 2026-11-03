<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import api, { errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import DateInput from '../components/DateInput.vue'
import Pager from '../components/Pager.vue'

// Every request sent to your webhooks, newest first: which webhook, which event, and what the receiver answered.
const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const hooks = ref([])
const events = ref([])
const error = ref('')
const loading = ref(true)
const open = ref(null) // delivery id whose request and full answer are shown
const f = reactive({ search: '', webhook_id: '', event: '', result: '', from: '', to: '' })
const fDirty = computed(() => Object.values(f).some(Boolean))
const fClear = () => Object.keys(f).forEach((k) => (f[k] = ''))

const eventLabel = (key) => events.value.find((e) => e.key === key)?.label || key
// the request body, readable
const pretty = (text) => { try { return JSON.stringify(JSON.parse(text), null, 2) } catch (e) { return text || '' } }

async function load(page = 1) {
  try {
    const params = { page }
    for (const k in f) if (f[k]) params[k] = f[k]
    const { data } = await api.get('/webhook-deliveries', { params })
    rows.value = data.data
    meta.value = data
    error.value = ''
  } catch (e) { error.value = errorMessage(e) }
  loading.value = false
}

let t
watch(f, () => { clearTimeout(t); t = setTimeout(() => load(1), 300) })
onMounted(async () => {
  try {
    ;[hooks.value, events.value] = (await Promise.all([api.get('/webhooks'), api.get('/webhooks/events')])).map((r) => r.data)
  } catch (e) { /* the filters just stay empty */ }
  load()
})
</script>

<template>
  <div class="head">
    <h1>Webhook deliveries <small>({{ meta.total }})</small></h1>
    <router-link to="/automation/webhooks" class="btn ghost">Back to webhooks</router-link>
  </div>
  <p class="intro">Every request sent to your webhooks, newest first: which webhook, what happened, and what the receiving system answered. A failed delivery shows why. Open <b>View request</b> to see exactly what was sent.</p>
  <div v-if="error" class="alert err">{{ error }}</div>

  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="f.search" type="search" placeholder="Search the answer or event…" />
    <select v-model="f.webhook_id"><option value="">All webhooks</option><option v-for="h in hooks" :key="h.id" :value="h.id">{{ h.name }}</option></select>
    <select v-model="f.event"><option value="">All events</option><option v-for="e in events" :key="e.key" :value="e.key">{{ e.label }}</option><option value="ping">Test (ping)</option></select>
    <select v-model="f.result"><option value="">All results</option><option value="ok">Delivered</option><option value="failed">Failed</option></select>
    <label>From <DateInput v-model="f.from" /></label>
    <label>To <DateInput v-model="f.to" /></label>
  </FilterBar>

  <div class="card">
    <p v-if="loading" class="empty">Loading…</p>
    <p v-else-if="!rows.length" class="empty">{{ fDirty ? 'No deliveries match these filters.' : 'Nothing has been delivered yet. Add a webhook, then use its Test button, or wait for an event.' }}</p>
    <table v-else>
      <thead><tr><th>When</th><th>Webhook</th><th>Event</th><th>Result</th><th>Answer</th><th class="act">Request</th></tr></thead>
      <template v-for="d in rows" :key="d.id">
        <tr>
          <td style="white-space: nowrap">{{ $dt(d.created_at) }}</td>
          <td><b>{{ d.webhook_name }}</b></td>
          <td><code>{{ d.event }}</code><br /><small>{{ d.event === 'ping' ? 'Test delivery' : eventLabel(d.event) }}</small></td>
          <td><span class="badge" :class="d.success ? 'sent' : 'failed'">{{ d.success ? 'Delivered' : 'Failed' }} {{ d.status_code || '' }}</span></td>
          <td class="ans">{{ d.response }}</td>
          <td class="act"><button type="button" class="btn ghost sm" @click="open = open === d.id ? null : d.id">{{ open === d.id ? 'Hide request' : 'View request' }}</button></td>
        </tr>
        <tr v-if="open === d.id" class="detail">
          <td colspan="6">
            <div class="pair">
              <div><b>What was sent</b><pre>{{ pretty(d.payload) }}</pre></div>
              <div><b>What the receiver answered</b><pre>{{ d.status_code ? 'HTTP ' + d.status_code + '\n' : '' }}{{ d.response || '(no answer)' }}</pre></div>
            </div>
          </td>
        </tr>
      </template>
    </table>
    <Pager :meta="meta" @page="load" />
  </div>
</template>

<style scoped>
code { background: #f1f2f6; padding: 1px 6px; border-radius: 3px; font-size: 12px; }
.ans { color: #4b5563; font-size: 13px; max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.detail td { background: #f7f8fc; padding: 10px 20px 14px; }
.pair { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.pair pre { margin: 6px 0 0; background: #fff; border: 1px solid #e0e3f0; border-radius: 6px; padding: 10px; font-size: 12px; max-height: 260px; overflow: auto; white-space: pre-wrap; word-break: break-word; }
@media (max-width: 800px) { .pair { grid-template-columns: 1fr; } }
</style>
