<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import RowMenu from '../components/RowMenu.vue'
import Pager from '../components/Pager.vue'

// One view for all four sidebar entries: /campaigns, ?type=email, ?type=sms, ?status=scheduled
const route = useRoute()
const router = useRouter()
const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const search = ref('')
const type = ref(route.query.type || '')
const status = ref(route.query.status || '')
const fDirty = computed(() => search.value !== '' || type.value !== '' || status.value !== '')
const fClear = () => { search.value = ''; type.value = ''; status.value = '' }
const error = ref('')
let timer

const title = computed(() => (route.query.status === 'scheduled' ? 'Scheduled Campaigns' : route.query.type === 'email' ? 'Email Campaigns' : route.query.type === 'sms' ? 'SMS Campaigns' : 'All Campaigns'))
const scheduledView = computed(() => status.value === 'scheduled')
const audience = (c) => c.segment?.name ? `Segment: ${c.segment.name}` : c.tag?.name ? `Tag: ${c.tag.name}` : c.list?.name || 'All people'

async function load(page = 1) {
  const params = { page }
  if (type.value) params.type = type.value
  if (status.value) params.status = status.value
  if (search.value) params.search = search.value
  const { data } = await api.get('/campaigns', { params })
  rows.value = data.data
  meta.value = data
}

async function remove(c) {
  if (!confirm(`Delete campaign "${c.name}" and its logs?`)) return
  await api.delete(`/campaigns/${c.id}`)
  load(meta.value.current_page)
}

async function unschedule(c) {
  if (!confirm(`Cancel the scheduled send of "${c.name}"? It goes back to drafts.`)) return
  try { await api.post(`/campaigns/${c.id}/unschedule`); load(meta.value.current_page) } catch (e) { error.value = errorMessage(e) }
}

async function duplicate(c) {
  error.value = ''
  try { router.push(`/campaigns/${(await api.post(`/campaigns/${c.id}/duplicate`)).data.id}`) } catch (e) { error.value = errorMessage(e) }
}

async function stop(c) {
  if (!confirm(`Stop sending "${c.name}"? What has already gone out stays sent; everything still waiting is skipped.`)) return
  try { await api.post(`/campaigns/${c.id}/cancel`); load(meta.value.current_page) } catch (e) { error.value = errorMessage(e) }
}

let t
watch(search, () => { clearTimeout(t); t = setTimeout(() => load(), 300) })
watch([type, status], () => load())
// the sidebar entries (All / Email / SMS / Scheduled) preset the filters
watch(() => route.query, (q) => { type.value = q.type || ''; status.value = q.status || '' })
onMounted(() => {
  load()
  // keep progress fresh while something is sending
  timer = setInterval(() => rows.value.some((r) => r.status === 'sending') && load(meta.value.current_page), 4000)
})
onBeforeUnmount(() => clearInterval(timer))
</script>

<template>
  <div class="head">
    <h1>{{ title }} <small>({{ meta.total }})</small></h1>
    <div class="row"><router-link to="/campaigns/history" class="btn ghost">Campaign histories</router-link><router-link class="btn" :to="route.query.type ? `/campaigns/new?type=${route.query.type}` : '/campaigns/new'">New campaign</router-link></div>
  </div>
  <div v-if="error" class="alert err">{{ error }}</div>
  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="search" type="search" placeholder="Search campaigns…" />
    <select v-model="type"><option value="">All channels</option><option value="email">Email</option><option value="sms">SMS</option></select>
    <select v-model="status"><option value="">All statuses</option><option value="draft">Draft</option><option value="scheduled">Scheduled</option><option value="sending">Sending</option><option value="sent">Sent</option><option value="failed">Failed</option><option value="cancelled">Stopped</option></select>
  </FilterBar>

  <div class="card">
    <p v-if="!rows.length" class="empty">{{ scheduledView ? 'Nothing is scheduled.' : 'No campaigns found.' }}</p>
    <table v-else>
      <thead>
        <tr><th>Name</th><th>Channel</th><th>Audience</th><th>Status</th><template v-if="scheduledView"><th>Sends at (UTC)</th></template><template v-else><th>Sent</th><th>Failed</th><th>Opened</th></template><th class="act">Actions</th></tr>
      </thead>
      <tr v-for="c in rows" :key="c.id">
        <td><router-link :to="`/campaigns/${c.id}`">{{ c.name }}</router-link></td>
        <td><span class="badge" :class="c.type">{{ c.type }}</span></td>
        <td>{{ audience(c) }}</td>
        <td><span class="badge" :class="c.status">{{ c.status }}</span></td>
        <template v-if="scheduledView"><td>{{ $ds(c.scheduled_at, '—') }}</td></template>
        <template v-else>
          <td>{{ c.sent_count }}/{{ c.logs_count }}<br v-if="c.logged_count" /><small v-if="c.logged_count" title="Written to a log only: nothing was delivered">{{ c.logged_count }} logged only</small></td>
          <td>{{ c.failed_count }}</td>
          <td>{{ c.type === 'email' ? c.opened_count : '—' }}</td>
        </template>
        <td class="act"><RowMenu :history="{ type: 'campaign', id: c.id, title: c.name }" :items="[{ label: 'Open', to: `/campaigns/${c.id}` }, { label: 'Report', to: `/reports/${c.id}`, show: ['sent', 'sending', 'failed'].includes(c.status) }, { label: 'Cancel schedule', run: () => unschedule(c), show: c.status === 'scheduled' }, { label: 'Stop sending', run: () => stop(c), show: c.status === 'sending', danger: true }, { label: 'Clone', run: () => duplicate(c) }, { label: 'Delete', run: () => remove(c), danger: true, show: c.status !== 'sending' }]" /></td>
      </tr>
    </table>
    <Pager :meta="meta" @page="load" />
  </div>
</template>
