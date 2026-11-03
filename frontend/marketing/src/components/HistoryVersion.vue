<script setup>
// One saved copy from the history, read-only: how it looked (Preview) and what it was made of (HTML). Nothing here can be edited or saved.
import { ref, computed, onMounted } from 'vue'
import api, { errorMessage } from '../api'
import Modal from './Modal.vue'

const props = defineProps({ type: String, historyId: [Number, String], title: String })
defineEmits(['close'])

const v = ref(null)
const error = ref('')
const tab = ref('preview')
const copied = ref(false)

const d = computed(() => v.value?.data || {})
// pages keep their HTML in `html`, templates and campaigns in `body`; an SMS is plain text
const code = computed(() => (props.type === 'landing_page' ? d.value.html : d.value.body) || '')
const isText = computed(() => d.value.type === 'sms')
const ACTIONS = { created: 'Created', updated: 'Updated', deleted: 'Deleted', restored: 'Restored', baseline: 'Existing record' }

async function copy() {
  try { await navigator.clipboard.writeText(code.value); copied.value = true; setTimeout(() => (copied.value = false), 1500) } catch (e) { /* clipboard blocked */ }
}
onMounted(async () => { try { v.value = (await api.get(`/history/${props.type}/entry/${props.historyId}`)).data } catch (e) { error.value = errorMessage(e) } })
</script>

<template>
  <Modal :title="`Saved copy${title ? ' — ' + title : ''}`" wide @close="$emit('close')">
    <p v-if="error" class="alert err">{{ error }}</p>
    <p v-else-if="!v" class="empty">Loading…</p>
    <template v-else>
      <p class="meta">
        <b>{{ ACTIONS[v.change_action] || v.change_action }}</b> · {{ $dt(v.changed_at) }}<template v-if="v.changed_by_name"> · {{ v.changed_by_name }}</template>
        <span class="ro">Read only</span>
      </p>
      <p v-if="d.name || d.subject || d.title" class="facts">
        <span v-if="d.name"><b>Name:</b> {{ d.name }}</span>
        <span v-if="d.subject"><b>Subject:</b> {{ d.subject }}</span>
        <span v-if="d.title"><b>Page title:</b> {{ d.title }}</span>
      </p>

      <div v-if="!isText" class="tabs">
        <button type="button" :class="{ on: tab === 'preview' }" @click="tab = 'preview'">Preview</button>
        <button type="button" :class="{ on: tab === 'html' }" @click="tab = 'html'">HTML</button>
        <button v-if="tab === 'html'" type="button" class="btn ghost sm cp" @click="copy">{{ copied ? 'Copied ✓' : 'Copy HTML' }}</button>
      </div>

      <div v-if="isText" class="sms-bubble">{{ code }}</div>
      <p v-else-if="!code" class="empty">This copy has no content.</p>
      <iframe v-else-if="tab === 'preview'" class="frame" sandbox="" :srcdoc="code" title="Preview of this saved copy"></iframe>
      <textarea v-else class="code" readonly spellcheck="false" :value="code"></textarea>

      <p class="note">This is how it was when it was saved. Merge tags such as the first name, and forms inside a page, show as they are stored. You cannot change a saved copy.</p>
    </template>
  </Modal>
</template>

<style scoped>
.meta { margin: 0 0 6px; color: #4b5563; font-size: 13px; display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
.ro { margin-left: auto; background: #e5e7eb; color: #374151; border-radius: 99px; padding: 1px 10px; font-size: 12px; }
.facts { margin: 0 0 12px; font-size: 13px; display: flex; gap: 18px; flex-wrap: wrap; }
.tabs { display: flex; align-items: center; gap: 4px; border-bottom: 1px solid #e0e3f0; margin-bottom: 12px; }
.tabs button:not(.btn) { background: none; border: 0; border-bottom: 2px solid transparent; padding: 8px 14px; font: inherit; color: #6b7280; cursor: pointer; margin-bottom: -1px; }
.tabs button.on { color: #3f51b5; border-bottom-color: #3f51b5; font-weight: 600; }
.cp { margin-left: auto; }
.frame { width: 100%; height: 58vh; border: 1px solid #e0e3f0; border-radius: 6px; background: #fff; }
.code { width: 100%; height: 58vh; font: 12px/1.5 ui-monospace, Consolas, monospace; border: 1px solid #e0e3f0; border-radius: 6px; padding: 10px; resize: none; background: #f9fafb; box-sizing: border-box; }
.note { color: #6b7280; font-size: 12px; margin: 10px 0 0; }
</style>
