<script setup>
// Pagination footer for Laravel paginator responses: <Pager :meta="meta" @page="load" />
defineProps({ meta: { type: Object, required: true } })
defineEmits(['page'])
</script>

<template>
  <div v-if="meta.last_page > 1" class="row" style="justify-content: center; margin-top: 12px">
    <button class="btn ghost sm" :disabled="meta.current_page <= 1" @click="$emit('page', meta.current_page - 1)">Previous</button>
    <small>Page {{ meta.current_page }} of {{ meta.last_page }}</small>
    <button class="btn ghost sm" :disabled="meta.current_page >= meta.last_page" @click="$emit('page', meta.current_page + 1)">Next</button>
  </div>
</template>
