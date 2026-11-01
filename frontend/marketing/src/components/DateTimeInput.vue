<script setup>
// A date-and-time box that always reads and takes "Y-m-d h:i A" (2026-11-01 01:28 PM), plus a picker button.
// What is typed is kept as typed: there is no time zone conversion either way.
// v-model is "YYYY-MM-DDTHH:mm" (what the campaign form keeps) or "".
import { ref, watch } from 'vue'
import { fmtDateTime, parseDateTime } from '../format'

const props = defineProps({ modelValue: { type: String, default: '' } })
const emit = defineEmits(['update:modelValue'])

const text = ref(props.modelValue ? fmtDateTime(props.modelValue) : '')
const native = ref(null)
watch(() => props.modelValue, (v) => { if (parseDateTime(text.value) !== (v || null) && !(v === '' && text.value === '')) text.value = v ? fmtDateTime(v) : '' })

function typed(e) {
  text.value = e.target.value
  if (text.value.trim() === '') emit('update:modelValue', '')
  else {
    const v = parseDateTime(text.value)
    if (v) emit('update:modelValue', v) // only a complete, real date and time is passed on
  }
}
function picked(e) {
  emit('update:modelValue', e.target.value)
  text.value = e.target.value ? fmtDateTime(e.target.value) : ''
}
function openPicker() {
  try { native.value.showPicker() } catch (err) { native.value.focus() }
}
</script>

<template>
  <span class="di">
    <input type="text" :value="text" placeholder="YYYY-MM-DD hh:mm AM" maxlength="20" autocomplete="off" :class="{ bad: text.trim() && !parseDateTime(text) }" @input="typed" />
    <button type="button" class="cal" title="Pick a date and time" aria-label="Pick a date and time" @click="openPicker">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 15H5V9h14v10z" /></svg>
    </button>
    <input ref="native" type="datetime-local" class="native" tabindex="-1" aria-hidden="true" :value="parseDateTime(text) || ''" @change="picked" />
  </span>
</template>

<style scoped>
.di { position: relative; display: inline-flex; align-items: center; }
.di input[type='text'] { width: 200px !important; min-width: 0 !important; padding-right: 34px; }
.di input.bad { border-color: #c62828; }
.cal { position: absolute; right: 4px; background: none; border: 0; padding: 4px; color: #6b7280; cursor: pointer; line-height: 0; border-radius: 4px; }
.cal:hover { color: #3f51b5; background: #eceff7; }
.native { position: absolute; right: 0; bottom: 0; width: 1px; height: 1px; opacity: 0; pointer-events: none; padding: 0 !important; border: 0 !important; min-width: 0 !important; }
</style>
