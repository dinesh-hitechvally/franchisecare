<script setup>
// The reporting period picker shared by the analytics pages.
defineProps({ modelValue: { type: Number, default: 30 } })
defineEmits(['update:modelValue'])
</script>

<template>
  <select :value="modelValue" style="width: 160px" @change="$emit('update:modelValue', Number($event.target.value))">
    <option :value="7">Last 7 days</option><option :value="14">Last 14 days</option><option :value="30">Last 30 days</option><option :value="60">Last 60 days</option><option :value="90">Last 90 days</option>
  </select>
</template>
