<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api'
import Modal from '../components/Modal.vue'

const route = useRoute()
const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const detail = ref(null)
const f = reactive({ channel: route.query.channel || '', status: '', search: '', campaign_id: route.query.campaign_id || '' })

async function load(page = 1) {
  const params = { page }
  for (const k in f) if (f[k]) params[k] = f[k]
  const { data } = await api.get('/logs', { params })
  rows.value = data.data
  meta.value = data
}

async function open(l) { detail.value = (await api.get(`/logs/${l.id}`)).data }

let t
watch(f, () => { clearTimeout(t); t = setTimeout(() => load(), 300) })
onMounted(() => load())
</script>

<template>
  <div class="head"><h1>Message logs <small>({{ meta.total }})</small></h1></div>
  <div class="card">
    <div class="row" style="margin-bottom: 12px">
      <input v-model="f.search" placeholder="Search recipient…" style="max-width: 260px" />
      <select v-model="f.channel" style="max-width: 140px"><option value="">All channels</option><option value="email">Email</option><option value="sms">SMS</option></select>
      <select v-model="f.status" style="max-width: 140px">
        <option value="">All statuses</option><option>queued</option><option>sent</option><option>failed</option><option>skipped</option>
      </select>
      <button v-if="f.campaign_id" class="btn ghost sm" @click="f.campaign_id = ''">Clear campaign filter ✕</button>
    </div>
    <p v-if="!rows.length" class="empty">No logs.</p>
    <table v-else>
      <thead><tr><th>When</th><th>Channel</th><th>Recipient</th><th>Campaign</th><th>Status</th><th>Opens/Clicks</th><th></th></tr></thead>
      <tr v-for="l in rows" :key="l.id">
        <td>{{ new Date(l.sent_at || l.created_at).toLocaleString() }}</td>
        <td><span class="badge" :class="l.channel">{{ l.channel }}</span></td>
        <td>{{ l.recipient }}</td>
        <td>{{ l.campaign?.name }}</td>
        <td><span class="badge" :class="l.status">{{ l.status }}</span><br /><small v-if="l.error">{{ l.error }}</small></td>
        <td>{{ l.channel === 'email' ? `${l.opened_at ? 'Opened' : '—'} / ${l.clicks}` : '—' }}</td>
        <td><button class="btn ghost sm" @click="open(l)">View</button></td>
      </tr>
    </table>
    <div v-if="meta.last_page > 1" class="row" style="justify-content: center; margin-top: 12px">
      <button class="btn ghost sm" :disabled="meta.current_page <= 1" @click="load(meta.current_page - 1)">Previous</button>
      <small>Page {{ meta.current_page }} of {{ meta.last_page }}</small>
      <button class="btn ghost sm" :disabled="meta.current_page >= meta.last_page" @click="load(meta.current_page + 1)">Next</button>
    </div>
  </div>

  <Modal v-if="detail" :title="`${detail.channel.toUpperCase()} to ${detail.recipient}`" wide @close="detail = null">
    <p v-if="detail.subject"><strong>Subject:</strong> {{ detail.subject }}</p>
    <p v-if="detail.error" class="alert err">{{ detail.error }}</p>
    <iframe v-if="detail.channel === 'email' && detail.body" class="preview" sandbox="" :srcdoc="detail.body"></iframe>
    <div v-else-if="detail.body" class="sms-bubble">{{ detail.body }}</div>
    <p v-else><small>No content stored (message was not sent).</small></p>
  </Modal>
</template>
