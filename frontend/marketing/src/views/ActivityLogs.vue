<script setup>
import DateInput from '../components/DateInput.vue'
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api'
import FilterBar from '../components/FilterBar.vue'
import Pager from '../components/Pager.vue'

const route = useRoute()
const tab = ref(route.query.tab === 'requests' ? 'requests' : 'changes') // changes = what changed; requests = what was done
const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const types = ref([])
const f = reactive({ search: '', from: '', to: '', type: route.query.type || '', event: '', source: '', id: route.query.id || '' })
const fDirty = computed(() => Object.entries(f).some(([k, v]) => v && k !== 'id'))
const fClear = () => Object.keys(f).forEach((k) => (f[k] = ''))

const EVENTS = { created: 'Created', updated: 'Updated', deleted: 'Deleted', restored: 'Restored', purged: 'Removed for good', baseline: 'Existing record' }
const SOURCES = { dashboard: 'Dashboard', api: 'API', public: 'Public page / form', system: 'System' }
const nice = (s) => (s || '').replace(/_/g, ' ')
const label = (k) => nice(k).replace(/^./, (c) => c.toUpperCase())
const show = (v) => (v === null || v === undefined || v === '' ? '—' : typeof v === 'object' ? JSON.stringify(v) : v === true ? 'Yes' : v === false ? 'No' : String(v))

async function load(page = 1) {
  const params = { page }
  for (const k in f) if (f[k] && (tab.value === 'changes' || ['search', 'from', 'to'].includes(k))) params[k] = k === 'event' ? undefined : f[k]
  if (tab.value === 'changes' && f.event) params.action = f.event
  const { data } = await api.get(tab.value === 'changes' ? '/history' : '/activity-logs', { params })
  rows.value = data.data
  meta.value = data
}

let t
watch(f, () => { clearTimeout(t); t = setTimeout(() => load(), 300) })
watch(tab, () => load())
onMounted(async () => {
  types.value = (await api.get('/history/types')).data
  load()
})
</script>

<template>
  <div class="head"><h1>My activity <small>({{ meta.total }})</small></h1></div>
  <div class="bar">
    <button :class="{ on: tab === 'changes' }" @click="tab = 'changes'">Change history</button>
    <button :class="{ on: tab === 'requests' }" @click="tab = 'requests'">Actions</button>
  </div>

  <FilterBar :dirty="fDirty" @clear="fClear">
    <select v-if="tab === 'changes'" v-model="f.type" style="width: 160px"><option value="">All records</option><option v-for="x in types" :key="x" :value="x">{{ label(x) }}</option></select>
    <select v-if="tab === 'changes'" v-model="f.event" style="width: 150px"><option value="">All events</option><option v-for="(l, k) in EVENTS" :key="k" :value="k">{{ l }}</option></select>
    <select v-if="tab === 'changes'" v-model="f.source" style="width: 170px"><option value="">Everywhere</option><option v-for="(l, k) in SOURCES" :key="k" :value="k">{{ l }}</option></select>
    <input v-model="f.search" :placeholder="tab === 'changes' ? 'Search by record name…' : 'Search activity…'" style="max-width: 220px" />
    <label class="check" style="margin: 0">From <DateInput v-model="f.from" /></label>
    <label class="check" style="margin: 0">To <DateInput v-model="f.to" /></label>
    <button v-if="f.id" class="btn ghost sm" @click="f.id = ''">Showing one record — show all</button>
  </FilterBar>

  <div class="card">

    <p v-if="!rows.length" class="empty">Nothing recorded.</p>

    <table v-else-if="tab === 'changes'">
      <thead><tr><th>When</th><th>Who</th><th>What happened</th><th>What changed</th></tr></thead>
      <tr v-for="l in rows" :key="l.type + l.history_id">
        <td class="nw">{{ $dt(l.changed_at) }}</td>
        <td>{{ l.changed_by_name || '—' }}<br /><small>{{ SOURCES[l.change_source] || l.change_source }}<template v-if="l.change_ip"> · {{ l.change_ip }}</template></small></td>
        <td>
          <span class="badge" :class="'ev-' + l.change_action">{{ EVENTS[l.change_action] || l.change_action }}</span>
          <b> {{ label(l.type) }}</b><br />
          <small>{{ l.record_label }}</small>
        </td>
        <td>
          <div v-for="(c, k) in l.changes" :key="k" class="chg">
            <b>{{ label(k) }}</b>
            <template v-if="['created', 'baseline'].includes(l.change_action)"><span class="new">{{ show(c.new) }}</span></template>
            <template v-else><span class="old">{{ show(c.old) }}</span> → <span class="new">{{ show(c.new) }}</span></template>
          </div>
        </td>
      </tr>
    </table>

    <table v-else>
      <thead><tr><th>When</th><th>User</th><th>Activity</th><th>IP</th></tr></thead>
      <tr v-for="l in rows" :key="l.id">
        <td>{{ $dt(l.created_at) }}</td>
        <td>{{ l.user_name || '—' }}</td>
        <td>{{ l.description }}</td>
        <td><small>{{ l.ip }}</small></td>
      </tr>
    </table>
    <Pager :meta="meta" @page="load" />
  </div>
  <p class="note">
    <template v-if="tab === 'changes'">This shows only what <b>you</b> did. Every table keeps a full copy of each row after every change (create, update, delete), with who did it, where it came from and what changed (old → new). Use the ⋮ menu on any row, on any page, for that row’s own history. Passwords, keys and other secrets are never copied, and entries can’t be edited or removed.</template>
    <template v-else>This shows only your own actions. Every action made in the dashboard is recorded with who did it and from where. Passwords, message content and other submitted data are never stored in this log.</template>
  </p>
</template>

<style scoped>
.note { color: #6b7280; font-size: 13px; max-width: 760px; }
.bar { display: flex; gap: 4px; margin-bottom: 14px; border-bottom: 1px solid #e0e0e0; }
.bar button { background: none; border: 0; border-bottom: 3px solid transparent; padding: 10px 16px; font: inherit; font-weight: 500; color: #6b7280; cursor: pointer; }
.bar button.on { color: #3f51b5; border-bottom-color: #3f51b5; }
.nw { white-space: nowrap; }
.chg { font-size: 13px; line-height: 1.6; word-break: break-word; }
.chg b { margin-right: 6px; }
.old { background: #fee2e2; color: #991b1b; border-radius: 3px; padding: 0 5px; text-decoration: line-through; }
.new { background: #d1fae5; color: #065f46; border-radius: 3px; padding: 0 5px; }
.badge.ev-created, .badge.ev-restored, .badge.ev-baseline { background: #d1fae5; color: #065f46; }
.badge.ev-updated { background: #dbeafe; color: #1e40af; }
.badge.ev-deleted, .badge.ev-purged, .badge.ev-login_failed { background: #fee2e2; color: #991b1b; }
</style>
