<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '../api'
import { exportCsv } from '../csv'
import LineChart from '../components/LineChart.vue'
import BarList from '../components/BarList.vue'
import RangeSelect from '../components/RangeSelect.vue'

const days = ref(30)
const d = ref(null)
const STEPS = { send_email: 'Send email', send_sms: 'Send SMS', wait: 'Wait', add_tag: 'Add tag', remove_tag: 'Remove tag', add_to_list: 'Add to list', end: 'Finished' }
const RESULTS = { done: 'Completed', waiting: 'Waiting', skipped: 'Skipped', failed: 'Failed' }
const nice = (m, k) => m[k] || (k || '').replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase())

const daily = computed(() => [{ name: 'Runs started', color: '#7b2cbf', points: d.value.daily }])
const save = () => exportCsv(`automation-${days.value}d.csv`, ['Workflow', 'Status', 'Started', 'Completed', 'In progress', 'Failed', 'Completion rate %', 'Emails sent', 'SMS sent'],
  d.value.workflows.map((w) => [w.name, w.status, w.started, w.completed, w.running, w.failed, w.completion_rate, w.emails, w.sms]))

async function load() { d.value = (await api.get('/analytics/automation', { params: { days: days.value } })).data }
watch(days, load)
onMounted(load)
</script>

<template>
  <div class="head"><h1>Automation analytics</h1><RangeSelect v-model="days" /></div>
  <p v-if="!d" class="empty">Loading…</p>
  <template v-else>
    <div class="grid g4">
      <div class="card stat"><div class="n">{{ d.totals.started }}</div><div class="l">Runs started · {{ d.totals.active }} of {{ d.totals.workflows }} workflows active</div></div>
      <div class="card stat"><div class="n">{{ d.totals.completed }}</div><div class="l">Completed · {{ d.totals.running }} in progress</div></div>
      <div class="card stat"><div class="n" :style="{ color: d.totals.failed ? '#c62828' : '' }">{{ d.totals.failed }}</div><div class="l">Failed runs</div></div>
      <div class="card stat"><div class="n">{{ d.totals.emails + d.totals.sms }}</div><div class="l">Messages sent · {{ d.totals.emails }} email, {{ d.totals.sms }} SMS</div></div>
    </div>

    <div class="card"><h2>Runs started per day</h2><LineChart :series="daily" :height="220" /></div>

    <div class="card">
      <div class="row" style="justify-content: space-between"><h2 style="margin: 0">Workflows</h2><button class="btn ghost sm" :disabled="!d.workflows.length" @click="save">Export CSV</button></div>
      <p v-if="!d.workflows.length" class="empty">No workflows yet. <router-link to="/workflows">Create one</router-link>.</p>
      <table v-else>
        <thead><tr><th>Workflow</th><th>Status</th><th>Started</th><th>Completed</th><th>In progress</th><th>Failed</th><th>Completion</th><th>Emails</th><th>SMS</th></tr></thead>
        <tr v-for="w in d.workflows" :key="w.id">
          <td><router-link to="/workflows">{{ w.name }}</router-link></td>
          <td><span class="badge" :class="w.status === 'active' ? 'sent' : ''">{{ w.status }}</span></td>
          <td>{{ w.started }}</td><td>{{ w.completed }}</td><td>{{ w.running }}</td><td :style="{ color: w.failed ? '#c62828' : '' }">{{ w.failed }}</td><td><b>{{ w.completion_rate }}%</b></td><td>{{ w.emails }}</td><td>{{ w.sms }}</td>
        </tr>
      </table>
    </div>

    <div class="grid g2">
      <div class="card"><h2>Steps run</h2><BarList :items="d.steps.map((s) => ({ label: nice(STEPS, s.label), value: s.value }))" empty="No steps ran in this period." /></div>
      <div class="card"><h2>Step results</h2><BarList :items="d.step_results.map((s) => ({ label: nice(RESULTS, s.label), value: s.value }))" color="#00a152" empty="No steps ran in this period." /></div>
    </div>
    <p class="note">Runs are counted in the period they started. <router-link to="/automation/history">Open automation history</router-link> to see every step for a person.</p>
  </template>
</template>

<style scoped>
.note { color: #6b7280; font-size: 13px; }
</style>
