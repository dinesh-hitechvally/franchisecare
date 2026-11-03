<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'
import FilterBar from '../components/FilterBar.vue'
import { useFilters } from '../composables/useFilters'

// Every event that can start a workflow, as a table: what it is, what it needs, and which workflows already use it.
const rows = ref([])
const { f: flt, shown, dirty, clear } = useFilters(rows, {
  text: (t) => [t.label, t.description],
  match: { group: (t) => t.group, used: (t) => (t.workflows.length ? 'used' : 'unused') },
})
const SETTINGS = {
  list: 'A list (or any list)', tag: 'A tag (or any tag)', form: 'A form (or any form)', url: 'Link text (optional)',
  date: 'A date field, days before or after, once or every year', days: 'A number of days',
}

onMounted(async () => { rows.value = (await api.get('/automation/triggers')).data })
</script>

<template>
  <div class="head"><h1>Triggers <small>({{ rows.length }})</small></h1></div>
  <p class="intro">A trigger is the event that starts a workflow. Pick one to build a workflow around it, or see which workflows already listen for it.</p>

  <FilterBar :dirty="dirty" @clear="clear">
    <input v-model="flt.q" type="search" placeholder="Search triggers…" />
    <select v-model="flt.group"><option value="">All kinds</option><option v-for="g in [...new Set(rows.map((t) => t.group))]" :key="g" :value="g">{{ g }}</option></select>
    <select v-model="flt.used"><option value="">Used and unused</option><option value="used">Used by a workflow</option><option value="unused">Not used yet</option></select>
  </FilterBar>

  <div class="card">
    <p v-if="!rows.length" class="empty">Loading…</p>
    <p v-else-if="!shown.length" class="empty">No trigger matches these filters.</p>
    <table v-else>
      <thead><tr><th>Trigger</th><th>Kind</th><th>When it starts</th><th>Settings</th><th>Used by</th><th class="act">Action</th></tr></thead>
      <tr v-for="t in shown" :key="t.type">
        <td style="white-space: nowrap"><b>{{ t.label }}</b></td>
        <td><span class="badge" :class="'g-' + t.group.split(' ')[0].toLowerCase()">{{ t.group }}</span></td>
        <td class="d">{{ t.description }}</td>
        <td class="d">{{ SETTINGS[t.config] || '—' }}</td>
        <td>
          <small v-if="!t.workflows.length" class="none">Not used yet</small>
          <template v-else>
            <router-link v-for="w in t.workflows" :key="w.id" to="/workflows" class="pill">{{ w.name }} <span class="badge" :class="w.status === 'active' ? 'sent' : ''">{{ w.status }}</span></router-link>
          </template>
        </td>
        <td class="act"><router-link :to="`/workflows?trigger=${t.type}`" class="btn sm">Create workflow</router-link></td>
      </tr>
    </table>
  </div>
</template>

<style scoped>
.d { color: #555; font-size: 13px; max-width: 420px; }
.none { color: #6b7280; }
.pill { display: inline-block; background: #f4f5f7; border-radius: 99px; padding: 3px 10px; font-size: 13px; color: #333; text-decoration: none; margin: 0 6px 4px 0; }
.badge.g-people { background: #e8eaf6; color: #3f51b5; }
.badge.g-email { background: #e0f2f1; color: #00796b; }
.badge.g-time { background: #fff3e0; color: #e65100; }
</style>
