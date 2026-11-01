<script setup>
// The filters of a table. It sits above the table's card, never inside it.
defineProps({ dirty: Boolean })
defineEmits(['clear'])
</script>

<template>
  <div class="filterbar">
    <slot />
    <button v-if="dirty" type="button" class="clear" @click="$emit('clear')">Clear filters</button>
  </div>
</template>

<style scoped>
.filterbar { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin: 0 0 14px; }
.filterbar :deep(input), .filterbar :deep(select) { width: auto; min-width: 150px; max-width: 260px; background: #fff; margin: 0; }
.filterbar :deep(input:not([type='date']):not([type='checkbox'])) { min-width: 240px; }
.filterbar :deep(label) { display: inline-flex; align-items: center; gap: 6px; margin: 0; font-size: 13px; color: #6b7280; }
.clear { background: none; border: 0; color: #3f51b5; font: inherit; font-size: 13px; cursor: pointer; padding: 6px 4px; }
.clear:hover { text-decoration: underline; }
</style>
