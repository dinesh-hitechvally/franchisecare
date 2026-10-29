<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

// v-model = array of selected option values. options = [{ value, label }]
const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Select…' },
  emptyText: { type: String, default: 'No options available' },
})
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const q = ref('')
const root = ref(null)

const selected = computed(() => props.options.filter((o) => props.modelValue.includes(o.value)))
const filtered = computed(() => props.options.filter((o) => o.label.toLowerCase().includes(q.value.toLowerCase())))

function toggle(v) {
  emit('update:modelValue', props.modelValue.includes(v) ? props.modelValue.filter((x) => x !== v) : [...props.modelValue, v])
}
const remove = (v) => emit('update:modelValue', props.modelValue.filter((x) => x !== v))

const onDoc = (e) => { if (root.value && !root.value.contains(e.target)) open.value = false }
onMounted(() => document.addEventListener('mousedown', onDoc))
onBeforeUnmount(() => document.removeEventListener('mousedown', onDoc))
</script>

<template>
  <div ref="root" class="ms">
    <div class="ms-box" :class="{ open }" tabindex="0" @click="open = !open" @keydown.esc="open = false" @keydown.enter.prevent="open = !open">
      <span v-if="!selected.length" class="ph">{{ placeholder }}</span>
      <span v-for="o in selected" :key="o.value" class="chip">
        {{ o.label }}<button type="button" aria-label="Remove" @click.stop="remove(o.value)">&times;</button>
      </span>
      <svg class="caret" viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M7 10l5 5 5-5z" /></svg>
    </div>

    <div v-if="open" class="ms-menu">
      <input v-if="options.length > 6" v-model="q" class="ms-search" placeholder="Search…" @click.stop />
      <p v-if="!options.length" class="ms-empty">{{ emptyText }}</p>
      <p v-else-if="!filtered.length" class="ms-empty">No matches</p>
      <label v-for="o in filtered" :key="o.value" class="ms-opt" :class="{ on: modelValue.includes(o.value) }">
        <input type="checkbox" :checked="modelValue.includes(o.value)" @change="toggle(o.value)" />
        <span>{{ o.label }}</span>
      </label>
    </div>
  </div>
</template>

<style scoped>
.ms { position: relative; }
.ms-box { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; min-height: 40px; padding: 5px 34px 5px 8px; border: 1px solid #d1d5db; border-radius: 4px; background: #fff; cursor: pointer; position: relative; }
.ms-box:focus, .ms-box.open { outline: 2px solid #c5cae9; border-color: #3f51b5; }
.ph { color: #9ca3af; font-size: 14px; padding-left: 3px; }
.chip { display: inline-flex; align-items: center; gap: 4px; background: #e8eaf6; color: #3f51b5; border-radius: 14px; padding: 3px 4px 3px 10px; font-size: 13px; }
.chip button { border: 0; background: none; color: #3f51b5; cursor: pointer; font-size: 16px; line-height: 1; padding: 0 4px; border-radius: 50%; }
.chip button:hover { background: #c5cae9; }
.caret { position: absolute; right: 8px; top: 10px; color: #6b7280; pointer-events: none; }
.ms-box.open .caret { transform: rotate(180deg); }
.ms-menu { margin-top: 4px; background: #fff; border: 1px solid #e0e0e0; border-radius: 4px; box-shadow: 0 2px 8px rgba(0, 0, 0, .12); max-height: 200px; overflow: auto; padding: 4px 0; } /* inline (not floating) so a modal never clips it */
.ms-search { margin: 6px 8px; width: calc(100% - 16px); }
.ms-opt { display: flex; align-items: center; gap: 10px; padding: 9px 14px; margin: 0; cursor: pointer; font-weight: 400; font-size: 14px; }
.ms-opt:hover { background: #f4f5f7; } .ms-opt.on { background: #eef0fb; color: #3f51b5; }
.ms-opt input { width: auto; accent-color: #3f51b5; }
.ms-empty { margin: 0; padding: 12px 14px; color: #6b7280; font-size: 13px; }
</style>
