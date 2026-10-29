<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { EditorView, basicSetup } from 'codemirror'
import { html, htmlLanguage } from '@codemirror/lang-html'
import { oneDark } from '@codemirror/theme-one-dark'

// HTML editor with IDE features: syntax colours, tag/attribute completion, auto-close tags,
// bracket matching, line numbers, search (Ctrl+F) and merge-tag completion on "{{".
const props = defineProps({ modelValue: String })
const emit = defineEmits(['update:modelValue'])

const host = ref(null)
let view

const MERGE_TAGS = ['first_name', 'last_name', 'full_name', 'email', 'phone', 'business_name', 'unsubscribe_url']
const mergeTags = (ctx) => {
  const m = ctx.matchBefore(/\{\{\s*\w*/)
  if (!m || (m.from === m.to && !ctx.explicit)) return null
  return { from: m.from, options: MERGE_TAGS.map((t) => ({ label: `{{${t}}}`, type: 'variable', detail: 'merge tag' })), validFor: /^\{\{\s*\w*$/ }
}

onMounted(() => {
  view = new EditorView({
    doc: props.modelValue || '',
    parent: host.value,
    extensions: [
      basicSetup,
      html({ autoCloseTags: true, matchClosingTags: true }),
      htmlLanguage.data.of({ autocomplete: mergeTags }),
      oneDark,
      EditorView.lineWrapping,
      EditorView.updateListener.of((u) => { if (u.docChanged) emit('update:modelValue', u.state.doc.toString()) }),
      EditorView.theme({ '&': { height: '100%', fontSize: '13px' }, '.cm-scroller': { overflow: 'auto', fontFamily: "Consolas, 'Courier New', monospace" } }),
    ],
  })
})

// push outside changes in (e.g. "back to visual blocks") without clobbering what the user is typing
watch(() => props.modelValue, (v) => {
  if (view && v !== view.state.doc.toString()) view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: v || '' } })
})

onBeforeUnmount(() => view?.destroy())
</script>

<template>
  <div ref="host" class="cm-host"></div>
</template>

<style scoped>
.cm-host { height: 100%; min-height: 0; }
.cm-host :deep(.cm-editor) { height: 100%; }
.cm-host :deep(.cm-editor.cm-focused) { outline: none; }
</style>
