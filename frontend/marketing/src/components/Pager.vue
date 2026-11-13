<script setup>
// The pagination footer of every table, for Laravel paginator responses: <Pager :meta="meta" @page="load" />
// Optional rows-per-page choice: <Pager :meta="meta" :sizes="[10, 30, 50]" @page="load" @per-page="setPerPage" /> (the page's own choice is in meta.per_page).
import { computed } from 'vue'

const props = defineProps({ meta: { type: Object, required: true }, sizes: { type: Array, default: null } })
const emit = defineEmits(['page', 'per-page'])

const cur = computed(() => props.meta.current_page || 1)
const last = computed(() => props.meta.last_page || 1)
const total = computed(() => props.meta.total ?? 0)
const from = computed(() => props.meta.from ?? (total.value ? (cur.value - 1) * (props.meta.per_page || 0) + 1 : 0))
const to = computed(() => props.meta.to ?? Math.min(total.value, cur.value * (props.meta.per_page || 0)))
// page numbers around the current one, with the first and the last: 1 … 4 5 [6] 7 8 … 20
const pages = computed(() => {
  const set = new Set([1, last.value, ...Array.from({ length: 5 }, (_, i) => cur.value - 2 + i)].filter((n) => n >= 1 && n <= last.value))
  const out = []
  ;[...set].sort((a, b) => a - b).forEach((n, i, all) => { if (i && n - all[i - 1] > 1) out.push('…'); out.push(n) })
  return out
})
</script>

<template>
  <div v-if="total > 0 && (last > 1 || sizes)" class="pager">
    <span class="range">Showing <b>{{ from }}–{{ to }}</b> of <b>{{ total }}</b></span>
    <nav v-if="last > 1" class="pages" aria-label="Pages">
      <button type="button" class="pg" :disabled="cur <= 1" @click="emit('page', cur - 1)">‹ Previous</button>
      <template v-for="(n, i) in pages" :key="i">
        <span v-if="n === '…'" class="gap">…</span>
        <button v-else type="button" class="pg" :class="{ on: n === cur }" @click="emit('page', n)">{{ n }}</button>
      </template>
      <button type="button" class="pg" :disabled="cur >= last" @click="emit('page', cur + 1)">Next ›</button>
    </nav>
    <label v-if="sizes" class="per">Rows per page
      <select :value="meta.per_page" @change="emit('per-page', Number($event.target.value))"><option v-for="n in sizes" :key="n" :value="n">{{ n }}</option></select>
    </label>
  </div>
</template>

<style scoped>
.pager { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px 16px; margin-top: 14px; padding-top: 12px; border-top: 1px solid #eef0f3; font-size: 13px; color: #6b7280; }
.pages { display: flex; flex-wrap: wrap; gap: 4px; align-items: center; }
.pg { min-width: 32px; height: 32px; padding: 0 10px; border: 1px solid #e5e7eb; background: #fff; border-radius: 6px; font: inherit; cursor: pointer; color: #374151; }
.pg:hover:not(:disabled) { background: #f3f4f6; }
.pg.on { background: #3f51b5; border-color: #3f51b5; color: #fff; font-weight: 700; }
.pg:disabled { opacity: .45; cursor: default; }
.gap { padding: 0 4px; }
.per { display: inline-flex; align-items: center; gap: 6px; margin: 0; }
.per select { width: auto; height: 32px; padding: 0 8px; }
</style>
