<script setup>
// The filters of a table. It sits above the table's card, never inside it. The default slot holds the inputs, the `end` slot a result count.
defineProps({ dirty: Boolean, stacked: Boolean }) // stacked: every filter on its own line, with its label above it
defineEmits(['clear'])
</script>

<template>
  <div class="filterbar" :class="{ stacked }">
    <div class="fields"><slot /></div>
    <div class="tail">
      <button v-if="dirty" type="button" class="clear" @click="$emit('clear')">Clear filters</button>
      <span v-if="$slots.end" class="count"><slot name="end" /></span>
    </div>
  </div>
</template>

<style scoped>
.filterbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px 16px; margin: 0 0 14px; padding: 10px 12px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; }
.fields { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; flex: 1 1 auto; min-width: 0; }
.filterbar :deep(input), .filterbar :deep(select) { width: auto; height: 36px; min-width: 140px; max-width: 220px; margin: 0; padding: 0 12px; font-size: 14px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: none; }
.filterbar :deep(input:focus), .filterbar :deep(select:focus) { background-color: #fff; border-color: #3f51b5; outline: 0; box-shadow: 0 0 0 3px rgba(63, 81, 181, .12); }
.filterbar :deep(input:not([type='date']):not([type='checkbox']):not([type='radio'])) { min-width: 220px; }
.filterbar :deep(input[type='search']) { padding-left: 34px; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%239ca3af' stroke-width='2' stroke-linecap='round' viewBox='0 0 24 24'%3E%3Ccircle cx='11' cy='11' r='7'/%3E%3Cpath d='m20 20-3.5-3.5'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: 11px center; }
.filterbar :deep(label) { display: inline-flex; align-items: center; gap: 6px; margin: 0; font-size: 13px; color: #6b7280; }
.tail { display: flex; align-items: center; gap: 14px; margin-left: auto; }
.count { font-size: 13px; color: #6b7280; white-space: nowrap; }
.clear { background: none; border: 0; color: #3f51b5; font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; padding: 6px 4px; }
.clear:hover { text-decoration: underline; }
.stacked { flex-direction: column; align-items: stretch; padding: 14px 16px; }
.stacked .fields { flex-direction: column; align-items: stretch; gap: 12px; }
.stacked :deep(.ff) { display: flex; flex-direction: column; align-items: stretch; gap: 4px; font-size: 12px; font-weight: 600; color: #4b5563; }
.stacked :deep(.ff input), .stacked :deep(.ff select) { width: 100%; max-width: 420px; min-width: 0; font-weight: 400; }
.stacked .tail { margin-left: 0; justify-content: space-between; padding-top: 4px; border-top: 1px solid #f0f1f3; }
@media (max-width: 640px) { .filterbar :deep(input), .filterbar :deep(select), .filterbar :deep(input:not([type='date']):not([type='checkbox']):not([type='radio'])) { width: 100%; max-width: none; } .fields { width: 100%; } }
</style>
