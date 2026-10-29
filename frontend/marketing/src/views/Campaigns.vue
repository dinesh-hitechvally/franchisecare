<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import api from '../api'

const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1 })
let timer

async function load(page = 1) {
  const { data } = await api.get('/campaigns', { params: { page } })
  rows.value = data.data
  meta.value = data
}

async function remove(c) {
  if (!confirm(`Delete campaign "${c.name}" and its logs?`)) return
  await api.delete(`/campaigns/${c.id}`)
  load(meta.value.current_page)
}

onMounted(() => {
  load()
  // keep progress fresh while something is sending
  timer = setInterval(() => rows.value.some((r) => r.status === 'sending') && load(meta.value.current_page), 4000)
})
onBeforeUnmount(() => clearInterval(timer))
</script>

<template>
  <div class="head"><h1>Campaigns</h1><router-link class="btn" to="/campaigns/new">New campaign</router-link></div>
  <div class="card">
    <p v-if="!rows.length" class="empty">No campaigns yet.</p>
    <table v-else>
      <thead><tr><th>Name</th><th>Type</th><th>Audience</th><th>Status</th><th>Sent</th><th>Failed</th><th>Opened</th><th></th></tr></thead>
      <tr v-for="c in rows" :key="c.id">
        <td><router-link :to="`/campaigns/${c.id}`">{{ c.name }}</router-link><br /><small v-if="c.scheduled_at && c.status === 'scheduled'">for {{ new Date(c.scheduled_at).toLocaleString() }}</small></td>
        <td><span class="badge" :class="c.type">{{ c.type }}</span></td>
        <td>{{ c.list?.name || 'All contacts' }}</td>
        <td><span class="badge" :class="c.status">{{ c.status }}</span></td>
        <td>{{ c.sent_count }}/{{ c.logs_count }}</td>
        <td>{{ c.failed_count }}</td>
        <td>{{ c.type === 'email' ? c.opened_count : '—' }}</td>
        <td style="text-align: right"><button v-if="c.status !== 'sending'" class="btn ghost sm" @click="remove(c)">Delete</button></td>
      </tr>
    </table>
    <div v-if="meta.last_page > 1" class="row" style="justify-content: center; margin-top: 12px">
      <button class="btn ghost sm" :disabled="meta.current_page <= 1" @click="load(meta.current_page - 1)">Previous</button>
      <small>Page {{ meta.current_page }} of {{ meta.last_page }}</small>
      <button class="btn ghost sm" :disabled="meta.current_page >= meta.last_page" @click="load(meta.current_page + 1)">Next</button>
    </div>
  </div>
</template>
