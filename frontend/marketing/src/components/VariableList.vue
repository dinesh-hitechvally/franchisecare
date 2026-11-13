<script setup>
// Every variable a template can use, to copy: click one to copy it ({{first_name}}). Colours say where the value comes from.
import { ref, computed } from 'vue'
import { mergeTags, loadMergeTags } from '../mergeTags'

loadMergeTags()
const copied = ref('')
const GROUPS = [
  ['setting', 'From your settings', 'Real values from Settings → Company profile.'],
  ['dynamic', 'About the person', 'Different for every person; a sample is shown in previews.'],
  ['custom', 'Custom fields', 'Your own person fields; a sample is shown in previews.'],
]
const groups = computed(() => GROUPS.map(([kind, title, help]) => ({ kind, title, help, items: mergeTags.value.filter((t) => t.kind === kind && t.key !== 'business_name') })).filter((g) => g.items.length))

const shown = (key) => (copied.value === key ? 'Copied ✓' : '{{' + key + '}}')

async function copy(key) {
  const text = '{{' + key + '}}'
  try { await navigator.clipboard.writeText(text) } catch (e) {
    const ta = document.createElement('textarea') // clipboard blocked (plain http): the old way
    ta.value = text; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove()
  }
  copied.value = key
  setTimeout(() => { if (copied.value === key) copied.value = '' }, 1200)
}
</script>

<template>
  <details class="vars" open>
    <summary>Variables <small>click to copy</small></summary>
    <p v-if="!mergeTags.length" class="none">Loading…</p>
    <section v-for="g in groups" :key="g.kind">
      <h4 :title="g.help">{{ g.title }}</h4>
      <button v-for="t in g.items" :key="t.key" type="button" class="v" :class="[g.kind, { off: g.kind === 'setting' && !t.set }]" :title="t.label + (g.kind === 'setting' && !t.set ? ': not set in Settings yet' : '')" @click="copy(t.key)">
        <code>{{ shown(t.key) }}</code>
        <span>{{ g.kind === 'setting' && !t.set ? 'not set yet' : t.value || '' }}</span>
      </button>
    </section>
  </details>
</template>

<style scoped>
.vars { margin-top: 14px; border-top: 1px solid #e5e7eb; padding-top: 10px; font-size: 12px; }
.vars summary { cursor: pointer; font-weight: 700; font-size: 13px; }
.vars summary small { font-weight: 400; color: #6b7280; margin-left: 6px; }
h4 { margin: 10px 0 4px; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
.v { display: flex; width: 100%; gap: 8px; justify-content: space-between; align-items: baseline; text-align: left; border: 0; border-radius: 6px; padding: 5px 8px; margin-bottom: 3px; cursor: pointer; font: inherit; }
.v code { font-weight: 700; white-space: nowrap; }
.v span { color: inherit; opacity: .8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 55%; }
.v.setting { background: #e8f5e9; color: #1b5e20; }
.v.dynamic, .v.custom { background: #e3f2fd; color: #0d47a1; }
.v.off { background: #fff4e0; color: #8a5a00; }
.v:hover { filter: brightness(.96); }
.none { color: #6b7280; }
</style>
