<script setup>
// Analytics → Jobs & queue: every kind of background work, what is waiting, what finished and what failed.
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import api from '../api'
import RangeSelect from '../components/RangeSelect.vue'
import InfoTip from '../components/InfoTip.vue'

const days = ref(30)
const d = ref(null)
const error = ref('')

async function load() {
  try { d.value = (await api.get('/analytics/jobs', { params: { days: days.value } })).data; error.value = '' } catch (e) { error.value = 'Could not load the jobs.' }
}
watch(days, load)
let timer
onMounted(() => { load(); timer = setInterval(load, 10000) }) // work in progress changes while you look
onBeforeUnmount(() => clearInterval(timer))

const q = computed(() => d.value?.queue)
const totals = computed(() => (d.value?.groups || []).reduce((t, g) => ({ pending: t.pending + g.pending, completed: t.completed + g.completed, failed: t.failed + g.failed }), { pending: 0, completed: 0, failed: 0 }))
const BADGE = { running: 'queued', queued: 'queued', scheduled: 'queued', sending: 'queued', success: 'sent', sent: 'sent', completed: 'sent', failed: 'failed', cancelled: 'skipped', skipped: 'skipped' }
const badge = (s) => BADGE[(s || '').toLowerCase()] || ''
const CHANNEL = { email: 'Email', sms: 'SMS', push: 'Push', social: 'Social' }
const ago = (m) => (m < 1 ? 'less than a minute' : m < 60 ? m + ' minute' + (m === 1 ? '' : 's') : Math.floor(m / 60) + ' hour' + (Math.floor(m / 60) === 1 ? '' : 's'))
</script>

<template>
  <div class="head"><h1>Jobs &amp; queue</h1><RangeSelect v-model="days" /></div>
  <p class="intro">Everything the system does in the background: import runs, campaigns, messages, workflow runs and webhook calls. <b>Pending</b> is waiting or in progress, <b>Completed</b> is finished, <b>Failed</b> needs a look. Updates by itself every few seconds.</p>
  <div v-if="error" class="alert err">{{ error }}</div>
  <p v-if="!d" class="empty">Loading…</p>

  <template v-else>
    <div class="grid g3">
      <div class="card stat"><div class="n" :style="{ color: totals.pending ? '#8a5a00' : '' }">{{ totals.pending }}</div><div class="l">Pending · waiting or in progress</div></div>
      <div class="card stat"><div class="n" style="color: #2e7d32">{{ totals.completed }}</div><div class="l">Completed · in the last {{ d.days }} days</div></div>
      <div class="card stat"><div class="n" :style="{ color: totals.failed ? '#c62828' : '' }">{{ totals.failed }}</div><div class="l">Failed · in the last {{ d.days }} days</div></div>
    </div>

    <!-- the queue itself -->
    <div class="card">
      <h2>Queue <InfoTip text="Jobs waiting for a queue worker, for all companies together (only counts are shown). A job leaves the queue when it finishes: finished work is listed per kind below." /></h2>
      <div v-if="q.worker_hint" class="alert" :class="q.available ? 'err' : 'ok'">{{ q.worker_hint }}</div>
      <div v-if="q.available" class="qstats">
        <div><b>{{ q.pending }}</b><span>Waiting now</span></div>
        <div><b>{{ q.processing }}</b><span>Being worked on</span></div>
        <div><b>{{ q.delayed }}</b><span>Delayed (waiting for their time)</span></div>
        <div><b :style="{ color: q.failed ? '#c62828' : '' }">{{ q.failed }}</b><span>Failed (kept for a retry)</span></div>
        <div v-if="q.oldest_waiting_minutes !== null"><b>{{ ago(q.oldest_waiting_minutes) }}</b><span>Oldest waiting job</span></div>
      </div>
      <table v-if="q.by_job.length">
        <thead><tr><th>Job</th><th>Waiting / delayed / working</th><th>Failed</th></tr></thead>
        <tr v-for="j in q.by_job" :key="j.job"><td>{{ j.job }}</td><td>{{ j.pending }}</td><td :style="{ color: j.failed ? '#c62828' : '' }">{{ j.failed }}</td></tr>
      </table>
      <p v-else-if="q.available" class="empty">The queue is empty.</p>
    </div>

    <!-- every kind of work -->
    <div v-for="g in d.groups" :key="g.key" class="card">
      <div class="row" style="justify-content: space-between; align-items: flex-start">
        <div><h2 style="margin: 0">{{ g.label }}</h2><small class="what">{{ g.what }}</small></div>
        <div class="trio">
          <span class="pill wait"><b>{{ g.pending }}</b> pending</span>
          <span class="pill ok"><b>{{ g.completed }}</b> completed</span>
          <span class="pill bad"><b>{{ g.failed }}</b> failed</span>
        </div>
      </div>
      <div v-if="g.stale" class="alert err" style="margin-top: 10px">{{ g.stale }} run{{ g.stale === 1 ? '' : 's' }} {{ g.stale === 1 ? 'has' : 'have' }} been waiting for more than 10 minutes. A queue worker must be running to process them.</div>

      <table v-if="g.by_channel" class="by">
        <thead><tr><th>Channel</th><th>Pending</th><th>Completed</th><th>Failed</th><th>Skipped</th></tr></thead>
        <tr v-for="c in g.by_channel" :key="c.channel"><td>{{ CHANNEL[c.channel] || c.channel }}</td><td>{{ c.pending }}</td><td>{{ c.completed }}</td><td>{{ c.failed }}</td><td>{{ c.skipped }}</td></tr>
      </table>

      <table v-if="g.recent.length" class="recent">
        <thead><tr><th>Latest</th><th>Status</th><th>When</th><th>Details</th></tr></thead>
        <tr v-for="(r, i) in g.recent" :key="i">
          <td><router-link v-if="r.link" :to="r.link">{{ r.title }}</router-link><template v-else>{{ r.title }}</template></td>
          <td><span class="badge" :class="badge(r.status)">{{ r.status }}</span></td>
          <td class="nw">{{ r.at ? $dt(r.at) : '—' }}</td>
          <td class="det">{{ r.detail || '' }}</td>
        </tr>
      </table>
      <p v-else-if="!g.pending && !g.completed && !g.failed" class="empty">Nothing in this period.</p>
    </div>
  </template>
</template>

<style scoped>
.intro { color: #6b7280; margin: -8px 0 16px; }
.card { margin-bottom: 16px; }
.qstats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin: 12px 0; }
.qstats div { background: #f9fafb; border: 1px solid #eef0f3; border-radius: 10px; padding: 10px 14px; }
.qstats b { display: block; font-size: 20px; }
.qstats span { font-size: 12px; color: #6b7280; }
.what { color: #6b7280; }
.trio { display: flex; flex-wrap: wrap; gap: 8px; }
.pill { padding: 4px 12px; border-radius: 999px; font-size: 13px; }
.pill.wait { background: #fff4e0; color: #8a5a00; } .pill.ok { background: #e8f5e9; color: #1b5e20; } .pill.bad { background: #fdecea; color: #b71c1c; }
.by, .recent { margin-top: 14px; }
.nw { white-space: nowrap; }
.det { color: #6b7280; font-size: 13px; max-width: 420px; word-break: break-word; }
</style>
