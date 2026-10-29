<script setup>
defineProps({ title: String, wide: Boolean })
defineEmits(['close'])
</script>

<template>
  <div class="modal-back" @mousedown.self="$emit('close')">
    <div class="modal" :class="{ wide }">
      <div class="modal-head">
        <h2 style="margin: 0">{{ title }}</h2>
        <button class="x" @click="$emit('close')">&times;</button>
      </div>
      <slot />
    </div>
  </div>
</template>
