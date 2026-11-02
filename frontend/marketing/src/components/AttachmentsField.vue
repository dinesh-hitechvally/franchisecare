<script setup>
// File attachments for an email. v-model is a list of { token, name, size }; files are chosen from the media library,
// copied for the email, and the server attaches them to every message sent with them.
import { ref } from 'vue'
import api, { errorMessage } from '../api'
import MediaPicker from './MediaPicker.vue'

const props = defineProps({ modelValue: { type: Array, default: () => [] }, disabled: Boolean })
const emit = defineEmits(['update:modelValue'])

const MAX = 10
const uploading = ref(false)
const error = ref('')
const size = (n) => (n >= 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB')

const choosing = ref(false)
// files are chosen from the media library (upload them there first); a copy is kept with the email
async function pick(m) {
  choosing.value = false
  error.value = ''
  const list = [...(props.modelValue || [])]
  if (list.length >= MAX) { error.value = `You can attach up to ${MAX} files.`; return }
  if (list.some((f) => f.name === m.file_name && f.size === m.size)) { error.value = 'That file is already attached.'; return }
  uploading.value = true
  try { emit('update:modelValue', [...list, (await api.post('/attachments/from-media', { media_id: m.id })).data]) } catch (err) { error.value = errorMessage(err) } finally { uploading.value = false }
}
const remove = (i) => emit('update:modelValue', props.modelValue.filter((_, j) => j !== i))
</script>

<template>
  <div class="att">
    <div v-for="(f, i) in modelValue" :key="f.token" class="frow">
      <span>&#128206; {{ f.name }} <small>{{ size(f.size) }}</small></span>
      <button v-if="!disabled" type="button" class="x" title="Remove" @click="remove(i)">&times;</button>
    </div>
    <button v-if="!disabled" type="button" class="btn ghost sm add" :disabled="uploading" @click="choosing = true">{{ uploading ? 'Attaching…' : modelValue.length ? 'Add more files' : 'Add files' }}</button>
    <MediaPicker v-if="choosing" title="Choose a file to attach" kind="" noun="file" :online="false" @pick="pick" @close="choosing = false" />
    <small v-if="error" class="err">{{ error }}</small>
    <small class="note">Choose files from your media library (upload new ones in Media first). Up to {{ MAX }} files, 5 MB each (PDF, Office documents, CSV, text, images, ZIP).</small>
  </div>
</template>

<style scoped>
.frow { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 4px 0; font-size: 13px; word-break: break-all; }
.frow .x { background: none; border: 0; font-size: 18px; cursor: pointer; color: #757575; flex: none; }
.add { display: inline-block; cursor: pointer; margin-top: 4px; }
.err { display: block; color: #c62828; margin-top: 6px; }
.note { display: block; margin-top: 6px; color: #6b7280; }
</style>
