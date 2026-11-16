<script setup>
// Integrations → Sync history: every import run of every connected platform.
import { ref, reactive, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api'
import FilterBar from '../components/FilterBar.vue'
import Pager from '../components/Pager.vue'

const route = useRoute()
const router = useRouter()
const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const connections = ref([])
const loading = ref(true)
const f = reactive({ integration_id: route.params.id || '', status: '' })

const TRIGGER = { connect: 'First import', manual: 'Manual', scheduled: 'Automatic' }
const took = (s) => {
  if (!s.started_at) return '—'
  const secs = Math.max(0, Math.round(((s.finished_at ? new Date(s.finished_at) : new Date()) - new Date(s.started_at)) / 1000))
  return secs < 60 ? secs + ' s' : Math.floor(secs / 60) + ' min ' + (secs % 60) + ' s'
}

async function load(page = 1) {
  loading.value = true
  try {
    const params = { page }
    for (const k in f) if (f[k]) params[k] = f[k]
    const { data } = await api.get('/integration-syncs', { params })
    rows.value = data.data
    meta.value = data
  } finally { loading.value = false }
}

let timer
watch(f, () => load())
onMounted(async () => {
  connections.value = (await api.get('/integrations')).data
  await load()
  timer = setInterval(() => { if (rows.value.some((r) => r.status === 'running')) load(meta.value.current_page || 1) }, 4000) // a running import updates itself
})
onBeforeUnmount(() => clearInterval(timer))
</script>

<template>
  <div class="head"><h1>Sync history <small v-if="!loading">({{ meta.total }})</small></h1><div class="row"><router-link to="/integrations" class="btn ghost">Back to Integrations</router-link></div></div>
  <p class="intro">Every time customers were imported from a connected platform: what was read, how many people were added or completed, and anything that went wrong.</p>

  <FilterBar :dirty="!!(f.integration_id || f.status)" @clear="f.status = ''; f.integration_id && router.push('/integrations/history')">
    <select :value="f.integration_id" @change="router.push($event.target.value ? `/integrations/${$event.target.value}/history` : '/integrations/history')"><option value="">All connections</option><option v-for="c in connections" :key="c.id" :value="c.id">{{ c.provider_label }} · {{ c.name }}</option></select>
    <select v-model="f.status"><option value="">All statuses</option><option value="running">Running</option><option value="success">Success</option><option value="failed">Failed</option></select>
  </FilterBar>

  <div class="card">
    <p v-if="loading && !rows.length" class="empty">Loading…</p>
    <p v-else-if="!rows.length" class="empty">{{ f.integration_id || f.status ? 'No imports match.' : 'Nothing has been imported yet. Connect a platform under Integrations.' }}</p>
    <table v-else>
      <thead><tr><th>Started</th><th>Connection</th><th>Type</th><th>Status</th><th>Read</th><th>New people</th><th>Updated</th><th>Unchanged / skipped</th><th>Sent</th><th>Took</th></tr></thead>
      <tbody>
        <template v-for="s in rows" :key="s.id">
          <tr>
            <td>{{ $dt(s.started_at || s.created_at) }}</td>
            <td><b>{{ s.integration?.name || '(removed)' }}</b><br /><small>{{ s.integration?.provider_label }}</small></td>
            <td>{{ TRIGGER[s.trigger] || s.trigger }}<br /><small>{{ s.full ? 'every customer' : 'changes only' }}</small></td>
            <td><span class="badge" :class="s.status === 'success' ? 'sent' : s.status === 'failed' ? 'failed' : 'queued'">{{ s.status === 'running' ? 'running…' : s.status }}</span></td>
            <td>{{ s.fetched }}</td><td>{{ s.created }}</td><td>{{ s.updated }}</td><td>{{ s.skipped }}</td>
            <td>{{ s.exported || s.export_failed ? s.exported : '–' }}<template v-if="s.export_failed"><br /><small class="bad">{{ s.export_failed }} refused</small></template></td>
            <td>{{ took(s) }}</td>
          </tr>
          <tr v-if="s.error"><td colspan="10" class="errrow">{{ s.error }}</td></tr>
        </template>
      </tbody>
    </table>
    <Pager :meta="meta" @page="load" />
  </div>
</template>

<style scoped>
.intro { color: #6b7280; margin: -8px 0 18px; }
.errrow { background: #fdecea; color: #b71c1c; font-size: 13.5px; }
.bad { color: #b71c1c; }
</style>
