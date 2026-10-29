<script setup>
import { ref, onMounted } from 'vue'
import api, { errorMessage } from '../api'
import Modal from './Modal.vue'

const emit = defineEmits(['close', 'pick'])

const provider = ref('all')
const providers = ref([{ id: 'all', label: 'All free sources' }])
onMounted(async () => { try { providers.value = (await api.get('/images/providers')).data } catch (e) { /* keep default */ } })
const q = ref('')
const page = ref(1)
const results = ref([])
const loading = ref(false)
const error = ref('')
const searched = ref(false)

async function search(p = 1) {
  if (!q.value.trim()) return
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/images/search', { params: { q: q.value, provider: provider.value, page: p } })
    page.value = p
    results.value = data
    searched.value = true
  } catch (e) {
    error.value = errorMessage(e)
    results.value = []
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Modal title="Free stock images" wide @close="emit('close')">
    <form class="row" @submit.prevent="search(1)">
      <select v-model="provider" style="width: 190px">
        <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.label }}</option>
      </select>
      <input v-model="q" placeholder="Search photos, e.g. coffee shop" style="flex: 1; min-width: 200px" autofocus />
      <button class="btn" :disabled="loading">{{ loading ? 'Searching…' : 'Search' }}</button>
    </form>

    <div v-if="error" class="alert err" style="margin-top: 12px">{{ error }}</div>
    <p v-else-if="searched && !results.length" class="empty">No images found.</p>

    <div class="imgs">
      <figure v-for="img in results" :key="img.source + img.id" @click="emit('pick', img)">
        <img :src="img.thumb" :alt="`Photo by ${img.author}`" loading="lazy" />
        <figcaption>{{ img.author }} · {{ img.source }}</figcaption>
      </figure>
    </div>

    <div v-if="results.length" class="row" style="justify-content: center; margin-top: 14px">
      <button class="btn ghost sm" :disabled="page <= 1 || loading" @click="search(page - 1)">Previous</button>
      <small>Page {{ page }}</small>
      <button class="btn ghost sm" :disabled="loading" @click="search(page + 1)">Next</button>
    </div>
    <p v-if="searched" style="margin-bottom: 0"><small>Free-licence images. Photographer credit and licence are added automatically under the image.</small></p>
  </Modal>
</template>
