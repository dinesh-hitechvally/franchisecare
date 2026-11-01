<script setup>
// Full history of one row: every saved copy, who changed it, and what changed (old → new).
import { ref, onMounted } from 'vue'
import api, { errorMessage } from '../api'
import Modal from './Modal.vue'

const props = defineProps({ type: String, id: [Number, String], title: String })
defineEmits(['close'])

const rows = ref([])
const loading = ref(true)
const error = ref('')

const ACTIONS = { created: 'Created', updated: 'Updated', deleted: 'Deleted', restored: 'Restored', purged: 'Removed for good', baseline: 'Existing record' }
const SOURCES = { dashboard: 'Dashboard', api: 'API', public: 'Public page / form', system: 'System' }
const KINDS = { list_membership: 'List', contact_tag: 'Tag' }
const label = (k) => k.replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase())
const show = (v) => (v === null || v === undefined || v === '' ? '—' : v === 1 || v === '1' ? v : String(v))

onMounted(async () => {
  try { rows.value = (await api.get(`/history/${props.type}/${props.id}`)).data } catch (e) { error.value = errorMessage(e) }
  loading.value = false
})
</script>

<template>
  <Modal :title="`Change history${title ? ' — ' + title : ''}`" wide @close="$emit('close')">
    <p v-if="loading" class="empty">Loading…</p>
    <p v-else-if="error" class="alert err">{{ error }}</p>
    <p v-else-if="!rows.length" class="empty">No changes have been recorded for this row yet.</p>
    <ol v-else class="tl">
      <li v-for="r in rows" :key="r.type + r.history_id">
        <div class="top">
          <span class="badge" :class="'a-' + r.change_action">{{ ACTIONS[r.change_action] || r.change_action }}</span>
          <b v-if="KINDS[r.type]"> {{ KINDS[r.type] }} · {{ r.record_label }}</b>
          <span class="when">{{ $dt(r.changed_at) }}</span>
        </div>
        <div class="who"><template v-if="r.changed_by_name">{{ r.changed_by_name }} · </template>{{ SOURCES[r.change_source] || r.change_source }}<template v-if="r.change_ip"> · {{ r.change_ip }}</template></div>
        <div v-for="(c, k) in r.changes" :key="k" class="chg">
          <b>{{ label(k) }}</b>
          <template v-if="['created', 'baseline'].includes(r.change_action)"><span class="new">{{ show(c.new) }}</span></template>
          <template v-else><span class="old">{{ show(c.old) }}</span> → <span class="new">{{ show(c.new) }}</span></template>
        </div>
      </li>
    </ol>
    <p class="note">Each entry is a saved copy of the row at that moment. Secrets such as passwords are never copied, and history cannot be edited or removed.</p>
  </Modal>
</template>

<style scoped>
.tl { list-style: none; margin: 0; padding: 0; max-height: 60vh; overflow: auto; }
.tl li { border-left: 3px solid #c5cae9; padding: 2px 0 14px 14px; margin-left: 4px; }
.top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.when { margin-left: auto; color: #6b7280; font-size: 13px; }
.who { color: #6b7280; font-size: 13px; margin: 2px 0 6px; }
.chg { font-size: 13px; line-height: 1.7; word-break: break-word; }
.chg b { margin-right: 6px; }
.old { background: #fee2e2; color: #991b1b; border-radius: 3px; padding: 0 5px; text-decoration: line-through; }
.new { background: #d1fae5; color: #065f46; border-radius: 3px; padding: 0 5px; }
.badge.a-created, .badge.a-restored, .badge.a-baseline { background: #d1fae5; color: #065f46; }
.badge.a-updated { background: #dbeafe; color: #1e40af; }
.badge.a-deleted, .badge.a-purged { background: #fee2e2; color: #991b1b; }
.note { color: #6b7280; font-size: 12px; margin: 10px 0 0; }
</style>
