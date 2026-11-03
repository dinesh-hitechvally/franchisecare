<script setup>
// Who a campaign will go to: the first few people of the chosen audience, and a "Show all" window for the rest.
import { ref, watch, onMounted } from 'vue'
import api from '../api'
import Modal from './Modal.vue'
import Pager from './Pager.vue'

const props = defineProps({ listId: { type: [Number, String], default: '' }, segmentId: { type: [Number, String], default: '' }, tagId: { type: [Number, String], default: '' }, status: { type: String, default: '' }, type: { type: String, default: 'email' } })

const PREVIEW = 5
const rows = ref([])
const total = ref(0)
const loading = ref(true)
const all = ref(false)
const full = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const search = ref('')
const modalLoading = ref(false)

const emit = defineEmits(['counts'])
const params = (extra) => ({ ...extra, list_id: props.listId || undefined, segment_id: props.segmentId || undefined, tag_id: props.tagId || undefined, status: props.status || undefined })
const channel = (c) => (props.type === 'sms' ? { value: c.phone, ok: c.is_subscribed } : { value: c.email, ok: c.is_subscribed })

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/people', { params: params({ per_page: PREVIEW }) })
    rows.value = data.data
    total.value = data.total
    // totals for the whole audience (not the open filter), shown in the Subscription dropdown
    const count = async (st) => (await api.get('/people', { params: { ...params({ per_page: 1 }), status: st } })).data.total
    const [all, subscribed, unsubscribed] = await Promise.all([count(undefined), count('subscribed'), count('unsubscribed')])
    emit('counts', { all, subscribed, unsubscribed })
  } finally { loading.value = false }
}
async function loadAll(page = 1) {
  modalLoading.value = true
  try {
    const { data } = await api.get('/people', { params: params({ per_page: 20, page, search: search.value || undefined }) })
    full.value = data.data
    meta.value = data
  } finally { modalLoading.value = false }
}
function openAll() { search.value = ''; all.value = true; loadAll(1) }
let t
watch(search, () => { clearTimeout(t); t = setTimeout(() => loadAll(1), 300) })
watch(() => [props.listId, props.segmentId, props.tagId, props.status], load)
onMounted(load)
</script>

<template>
  <div class="aud">
    <div class="top">
      <b>{{ loading ? 'Loading audience…' : `${total} ${total === 1 ? 'person' : 'people'}${props.status ? ' (' + props.status + ')' : ' in this audience'}` }}</b>
      <button v-if="total > PREVIEW" type="button" class="btn ghost sm" @click="openAll">Show all ({{ total }})</button>
    </div>
    <p v-if="!loading && !rows.length" class="empty">No people in this audience yet.</p>
    <table v-else-if="rows.length">
      <thead><tr><th>Name</th><th>Email</th><th v-if="type === 'sms'">Phone</th><th>Subscription</th></tr></thead>
      <tr v-for="c in rows" :key="c.id">
        <td>{{ [c.first_name, c.last_name].filter(Boolean).join(' ') || '—' }}</td>
        <td>{{ c.email || '—' }}</td>
        <td v-if="type === 'sms'">{{ c.phone || '—' }}</td>
        <td><span class="badge" :class="c.is_subscribed ? 'sent' : 'failed'">{{ c.is_subscribed ? 'Subscribed' : 'Unsubscribed' }}</span></td>
      </tr>
    </table>
  </div>

  <Modal v-if="all" title="Audience" wide @close="all = false">
    <input v-model="search" type="search" placeholder="Search name, email or phone…" style="max-width: 300px; margin-bottom: 12px" />
    <p v-if="modalLoading && !full.length" class="empty">Loading…</p>
    <p v-else-if="!full.length" class="empty">No person matches.</p>
    <table v-else>
      <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Subscription</th></tr></thead>
      <tr v-for="c in full" :key="c.id">
        <td>{{ [c.first_name, c.last_name].filter(Boolean).join(' ') || '—' }}</td>
        <td>{{ c.email || '—' }}</td>
        <td>{{ c.phone || '—' }}</td>
        <td><span class="badge" :class="c.is_subscribed ? 'sent' : 'failed'">{{ c.is_subscribed ? 'Subscribed' : 'Unsubscribed' }}</span></td>
      </tr>
    </table>
    <Pager :meta="meta" @page="loadAll" />
  </Modal>
</template>

<style scoped>
.aud { margin-top: 14px; border-top: 1px solid #eceef7; padding-top: 12px; }
.top { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 8px; }
</style>
