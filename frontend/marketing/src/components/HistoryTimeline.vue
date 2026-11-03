<script setup>
// The timeline of one record, for the side panel of an editor: newest first, grouped by day. Picking a point loads that saved copy
// and hands it to the editor (`pick`), which shows it read-only; "Current version" (`current`) goes back to the one being edited.
import { ref, computed, watch, onMounted } from 'vue'
import api, { errorMessage } from '../api'
import { fmtDate, fmtDateTime } from '../format'

const props = defineProps({
  type: String, // history type, e.g. 'template', 'form'
  id: [Number, String],
  activeId: { type: [Number, String], default: null }, // history_id of the copy being shown, null = the current version
  hide: { type: Array, default: () => [] }, // columns that are noise for this record (generated copies of another column)
  names: { type: Object, default: () => ({}) }, // column -> wording
  refresh: { type: Number, default: 0 }, // changes after each save: the list is fetched again, so the new entry shows up
})
const emit = defineEmits(['pick', 'current', 'close'])

const versions = ref(null)
const loading = ref(true)
const error = ref('')
const opening = ref(0)

const ACTIONS = { created: 'Created', updated: 'Updated', deleted: 'Deleted', restored: 'Restored', baseline: 'Existing record' }
const colName = (k) => props.names[k] || k.replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase())
const days = computed(() => {
  const out = []
  for (const v of versions.value || []) {
    const day = fmtDate(v.changed_at) // the person's own day, the one the times beside it use
    if (!out.length || out[out.length - 1].day !== day) out.push({ day, items: [] })
    out[out.length - 1].items.push(v)
  }
  return out
})
const what = (v) => [...new Set(Object.keys(v.changes || {}).filter((k) => !props.hide.includes(k)).map(colName))].join(' · ')

async function pick(v) {
  opening.value = v.history_id
  error.value = ''
  try {
    const entry = (await api.get(`/history/${props.type}/entry/${v.history_id}`)).data
    emit('pick', { ...entry, history_id: v.history_id })
  } catch (e) { error.value = errorMessage(e) }
  opening.value = 0
}
async function load() {
  try { versions.value = (await api.get(`/history/${props.type}/${props.id}`)).data; error.value = '' } catch (e) { error.value = errorMessage(e) }
  loading.value = false
}
onMounted(load)
watch(() => props.refresh, load) // no "Loading…" flash: the list stays while the new one is fetched
</script>

<template>
  <div class="hist">
    <div class="head"><b>History</b><button class="x" title="Close history" @click="emit('close')">&times;</button></div>
    <p class="hint">Pick a point on the timeline to see it as it was. You can only look: nothing changes.</p>
    <button type="button" class="vcur" :class="{ on: activeId === null }" @click="emit('current')">Current version <small>the one you are editing</small></button>
    <p v-if="loading" class="hint">Loading…</p>
    <p v-if="error" class="err">{{ error }}</p>
    <p v-else-if="versions && !versions.length" class="hint">No changes have been recorded yet.</p>
    <section v-for="g in days" :key="g.day">
      <h4 class="day">{{ g.day }}</h4>
      <ol class="tl">
        <li v-for="v in g.items" :key="v.history_id">
          <button type="button" class="vitem" :class="{ on: activeId === v.history_id }" :disabled="opening === v.history_id" @click="pick(v)">
            <span class="top"><span class="badge" :class="'a-' + v.change_action">{{ ACTIONS[v.change_action] || v.change_action }}</span><span class="when">{{ fmtDateTime(v.changed_at).slice(11) }}</span></span>
            <small>{{ v.changed_by_name || 'System' }}</small>
            <small v-if="what(v)" class="what">{{ what(v) }}</small>
          </button>
        </li>
      </ol>
    </section>
  </div>
</template>

<style scoped>
.hist { padding-bottom: 20px; }
.head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; font-size: 16px; }
.x { background: none; border: 0; font-size: 22px; cursor: pointer; color: #757575; }
.err { color: #c62828; font-size: 13px; }
.vcur, .vitem { display: block; width: 100%; text-align: left; background: #fff; border: 1px solid #e0e3f0; border-radius: 8px; padding: 8px 10px; font: inherit; font-size: 13px; cursor: pointer; margin-bottom: 6px; }
.vcur small, .vitem small { display: block; color: #6b7280; }
.vcur:hover, .vitem:hover { border-color: #3f51b5; }
.vcur.on, .vitem.on { border-color: #3f51b5; background: #eef0fb; box-shadow: inset 3px 0 0 #3f51b5; }
.day { margin: 14px 0 6px; font-size: 12.5px; color: #3f51b5; font-weight: 600; }
.tl { list-style: none; margin: 0; padding: 0 0 0 4px; border-left: 2px solid #c5cae9; }
.tl li { padding-left: 10px; }
.top { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.badge.a-created, .badge.a-restored, .badge.a-baseline { background: #d1fae5; color: #065f46; }
.badge.a-updated { background: #dbeafe; color: #1e40af; }
.badge.a-deleted { background: #fee2e2; color: #991b1b; }
.what { margin-top: 2px; }
</style>
