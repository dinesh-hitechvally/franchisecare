// The variables a template can use ({{first_name}}, {{company_name}}…) and what a preview shows for each:
//   setting → the real value from Settings (or a note when Settings has none yet)
//   dynamic → depends on the person, so a sample value stands in
//   custom  → a custom field of the person, a sample of its type
//   anything else does not exist and is called out in the text
import { ref } from 'vue'
import api from './api'

export const mergeTags = ref([])
let loading = null

export function loadMergeTags() {
  loading ??= api.get('/merge-tags').then((r) => { mergeTags.value = r.data }).catch(() => { loading = null })
  return loading
}

const PATTERN = /\{\{\s*([\w.]+)\s*\}\}/g
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')

/** What a variable is, for a label: { key, state: 'setting' | 'empty' | 'dynamic' | 'custom' | 'missing', value, label } */
export function describeTag(key) {
  const t = mergeTags.value.find((x) => x.key === key)
  if (!t) return { key, state: 'missing', value: '', label: 'this variable does not exist' }
  if (t.kind === 'setting' && !t.set) return { key, state: 'empty', value: '', label: 'not set in Settings yet' }
  return { key, state: t.kind, value: t.value, label: t.label }
}

/** Replaces every variable in `text` for display. `html` escapes the values (for a preview inside a page). */
export function fillTags(text, html = false) {
  if (!mergeTags.value.length) return String(text || '') // not loaded yet: show the template as it is
  return String(text || '').replace(PATTERN, (m, key) => {
    const d = describeTag(key)
    if (d.state === 'missing' || d.state === 'empty') return `[${key}: ${d.label}]`
    return html ? esc(d.value) : d.value
  })
}

/** The distinct variables used in `text`, each described. */
export function tagsIn(text) {
  return [...new Set([...String(text || '').matchAll(PATTERN)].map((m) => m[1]))].map(describeTag)
}
