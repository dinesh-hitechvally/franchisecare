<script setup>
import { computed } from 'vue'

// Horizontal bars: items = [{ label, value }]; `suffix` e.g. "%"
const props = defineProps({ items: { type: Array, default: () => [] }, color: { type: String, default: '#3f51b5' }, suffix: { type: String, default: '' }, empty: { type: String, default: 'No data yet.' } })
const max = computed(() => Math.max(1, ...props.items.map((i) => Number(i.value) || 0)))
</script>

<template>
  <p v-if="!items.length" class="muted">{{ empty }}</p>
  <div v-else class="bl">
    <div v-for="i in items" :key="i.label" class="r">
      <span class="lab" :title="i.label">{{ i.label }}</span>
      <span class="bar"><i :style="{ width: Math.max(2, (i.value / max) * 100) + '%', background: color }"></i></span>
      <b>{{ i.value }}{{ suffix }}</b>
    </div>
  </div>
</template>

<style scoped>
.muted { color: #9ca3af; font-size: 14px; margin: 0; }
.r { display: grid; grid-template-columns: minmax(80px, 38%) 1fr 56px; gap: 10px; align-items: center; padding: 5px 0; font-size: 14px; }
.lab { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #333; }
.bar { background: #eef0f4; border-radius: 4px; height: 12px; overflow: hidden; display: block; }
.bar i { display: block; height: 100%; border-radius: 4px; }
b { text-align: right; font-weight: 500; }
</style>
