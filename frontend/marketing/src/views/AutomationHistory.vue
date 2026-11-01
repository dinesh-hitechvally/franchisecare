<script setup>
import { fmtDateTime } from '../format'
import { ref, reactive, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import api, { errorMessage } from '../api'
import Pager from '../components/Pager.vue'

const route = useRoute()
const tab = ref('runs')
const workflows = ref([])
const f = reactive({ workflow_id: route.query.workflow_id || '', status: '' })
const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const error = ref('')

const STEP = { trigger: 'Trigger', send_email: 'Send email', send_sms: 'Send SMS', wait: 'Wait', add_tag: 'Add tag', remove_tag: 'Remove tag', add_to_list: 'Add to list', end: 'Finished', cancelled: 'Cancelled' }
const who = (c) => (c ? [c.first_name, c.last_name].filter(Boolean).join(' ') || c.email : 'Deleted contact')
const when = (d) => fmtDateTime(d, '—')
const badge = (s) => ({ completed: 'sent', done: 'sent', failed: 'failed', running: 'sending', waiting: 'sending', cancelled: '', skipped: '' }[s] ?? '')

async function load(page = 1) {
  const params = { page }
  if (f.workflow_id) params.workflow_id = f.workflow_id
  if (f.status) params.status = f.status
  const { data } = await api.get(tab.value === 'runs' ? '/automation/runs' : '/automation/history', { params })
  rows.value = data.data
  meta.value = data
}

async function cancel(run) {
  if (!confirm(`Remove ${who(run.contact)} from "${run.workflow.name}"?`)) return
  try { await api.post(`/automation/runs/${run.id}/cancel`); load(meta.value.current_page) } catch (e) { error.value = errorMessage(e) }
}

watch(tab, () => { f.status = ''; load() })
watch(() => [f.workflow_id, f.status], () => load())
onMounted(async () => { workflows.value = (await api.get('/workflows')).data; load() })
</script>

<template>
  <div class="head"><h1>Automation History</h1></div>
  <div v-if="error" class="alert err">{{ error }}</div>

  <div class="row" style="margin-bottom: 14px">
    <button class="btn sm" :class="{ ghost: tab !== 'runs' }" @click="tab = 'runs'">Contacts in workflows</button>
    <button class="btn sm" :class="{ ghost: tab !== 'steps' }" @click="tab = 'steps'">Step log</button>
    <span style="flex: 1"></span>
    <select v-model="f.workflow_id" style="width: 220px"><option value="">All workflows</option><option v-for="w in workflows" :key="w.id" :value="w.id">{{ w.name }}</option></select>
    <select v-model="f.status" style="width: 150px">
      <option value="">All statuses</option>
      <template v-if="tab === 'runs'"><option>running</option><option>completed</option><option>failed</option><option>cancelled</option></template>
      <template v-else><option>done</option><option>waiting</option><option>skipped</option><option>failed</option></template>
    </select>
  </div>

  <div class="card">
    <p v-if="!rows.length" class="empty">Nothing here yet. Activate a workflow and add a contact to see it run.</p>

    <table v-else-if="tab === 'runs'">
      <thead><tr><th>Contact</th><th>Workflow</th><th>Started</th><th>Progress</th><th>Status</th><th>Next / finished</th><th></th></tr></thead>
      <tr v-for="r in rows" :key="r.id">
        <td>{{ who(r.contact) }}</td><td>{{ r.workflow?.name }}</td><td>{{ when(r.started_at) }}</td>
        <td>step {{ r.current_step }}</td>
        <td><span class="badge" :class="badge(r.status)">{{ r.status }}</span><br /><small v-if="r.error">{{ r.error }}</small></td>
        <td>{{ r.status === 'running' ? when(r.next_run_at) : when(r.completed_at) }}</td>
        <td style="text-align: right"><button v-if="r.status === 'running'" class="btn ghost sm" @click="cancel(r)">Remove</button></td>
      </tr>
    </table>

    <table v-else>
      <thead><tr><th>When</th><th>Contact</th><th>Workflow</th><th>Step</th><th>Result</th><th>Details</th></tr></thead>
      <tr v-for="h in rows" :key="h.id">
        <td>{{ when(h.created_at) }}</td><td>{{ who(h.contact) }}</td><td>{{ h.workflow?.name }}</td>
        <td>{{ h.step_index >= 0 ? h.step_index + 1 + '. ' : '' }}{{ STEP[h.step_type] || h.step_type }}</td>
        <td><span class="badge" :class="badge(h.status)">{{ h.status }}</span></td>
        <td><small>{{ h.detail }}</small></td>
      </tr>
    </table>
    <Pager :meta="meta" @page="load" />
  </div>
</template>
