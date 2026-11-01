<script setup>
import { ref, onMounted, watch } from 'vue'

// Small contenteditable editor: bold / italic / underline / link / bullets + merge tags.
const props = defineProps({ modelValue: String })
const emit = defineEmits(['update:modelValue'])
const el = ref(null)
const TAGS = ['first_name', 'last_name', 'company_name', 'unsubscribe_url']

onMounted(() => { el.value.innerHTML = props.modelValue || '' })
// only push outside changes in; never rewrite while the user is typing (keeps the caret)
watch(() => props.modelValue, (v) => { if (el.value && el.value.innerHTML !== v && document.activeElement !== el.value) el.value.innerHTML = v || '' })

const input = () => emit('update:modelValue', el.value.innerHTML)
function cmd(name, val = null) {
  el.value.focus()
  document.execCommand(name, false, val)
  input()
}
function link() {
  const url = prompt('Link URL (https://…)')
  if (url) cmd('createLink', url)
}
function tag(e) {
  const t = e.target.value
  if (!t) return
  cmd('insertText', `{{${t}}}`)
  e.target.value = ''
}
</script>

<template>
  <div class="rt">
    <div class="rt-bar">
      <button type="button" title="Bold" @mousedown.prevent="cmd('bold')"><b>B</b></button>
      <button type="button" title="Italic" @mousedown.prevent="cmd('italic')"><i>I</i></button>
      <button type="button" title="Underline" @mousedown.prevent="cmd('underline')"><u>U</u></button>
      <button type="button" title="Bullet list" @mousedown.prevent="cmd('insertUnorderedList')">• List</button>
      <button type="button" title="Link" @mousedown.prevent="link">Link</button>
      <button type="button" title="Clear formatting" @mousedown.prevent="cmd('removeFormat')">Tx</button>
      <select title="Insert merge tag" @change="tag"><option value="">Merge tag…</option><option v-for="t in TAGS" :key="t" :value="t">{{ '{' + '{' + t + '}' + '}' }}</option></select>
    </div>
    <div ref="el" class="rt-body" contenteditable="true" @input="input"></div>
  </div>
</template>

<style scoped>
.rt { border: 1px solid #d1d5db; border-radius: 4px; background: #fff; }
.rt-bar { display: flex; flex-wrap: wrap; gap: 4px; padding: 6px; border-bottom: 1px solid #e5e7eb; background: #fafafa; }
.rt-bar button { border: 1px solid #e0e0e0; background: #fff; border-radius: 3px; padding: 3px 8px; font-size: 13px; cursor: pointer; }
.rt-bar button:hover { background: #eef0fb; }
.rt-bar select { width: auto; padding: 3px 6px; font-size: 12px; }
.rt-body { min-height: 120px; max-height: 320px; overflow: auto; padding: 10px; font-size: 14px; line-height: 1.5; outline: none; }
.rt-body:focus { box-shadow: inset 0 0 0 2px #c5cae9; }
</style>
