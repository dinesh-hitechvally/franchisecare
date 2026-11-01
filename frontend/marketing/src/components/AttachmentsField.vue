<script setup>
// File attachments for an email. v-model is a list of { token, name, size }; files are uploaded straight away
// and stored for the company, then the server attaches them to every message sent with them.
import { ref } from 'vue'
import api, { errorMessage } from '../api'

const props = defineProps({ modelValue: { type: Array, default: () => [] }, disabled: Boolean })
const emit = defineEmits(['update:modelValue'])

const MAX = 10
const uploading = ref(false)
const error = ref('')
const size = (n) => (n >= 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB')

async function add(e) {
  error.value = ''
  uploading.value = true
  let list = [...(props.modelValue || [])]
  for (const file of [...e.target.files]) {
    if (list.length >= MAX) { error.value = `You can attach up to ${MAX} files.`; break }
    const body = new FormData()
    body.append('file', file)
    try {
      list = [...list, (await api.post('/attachments', body)).data]
      emit('update:modelValue', list)
    } catch (err) { error.value = errorMessage(err); break }
  }
  uploading.value = false
  e.target.value = ''
}
const remove = (i) => emit('update:modelValue', props.modelValue.filter((_, j) => j !== i))
</script>

<template>
  <div class="att">
    <div v-for="(f, i) in modelValue" :key="f.token" class="frow">
      <span>&#128206; {{ f.name }} <small>{{ size(f.size) }}</small></span>
      <button v-if="!disabled" type="button" class="x" title="Remove" @click="remove(i)">&times;</button>
    </div>
    <label v-if="!disabled" class="btn ghost sm add">{{ uploading ? 'Uploading…' : modelValue.length ? 'Add more files' : 'Add files' }}
      <input type="file" multiple hidden :disabled="uploading" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv,.txt,.png,.jpg,.jpeg,.gif,.zip" @change="add" />
    </label>
    <small v-if="error" class="err">{{ error }}</small>
    <small class="note">Up to {{ MAX }} files, 5 MB each (PDF, Office documents, CSV, text, images, ZIP).</small>
  </div>
</template>

<style scoped>
.frow { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 4px 0; font-size: 13px; word-break: break-all; }
.frow .x { background: none; border: 0; font-size: 18px; cursor: pointer; color: #757575; flex: none; }
.add { display: inline-block; cursor: pointer; margin-top: 4px; }
.err { display: block; color: #c62828; margin-top: 6px; }
.note { display: block; margin-top: 6px; color: #6b7280; }
</style>
