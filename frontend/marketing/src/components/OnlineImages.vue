<script setup>
// Search free online images (Openverse, Wikimedia, and Unsplash / Pexels / Pixabay when the server has keys) and copy one into the media library.
import { ref, onMounted } from 'vue'
import api, { errorMessage } from '../api'

const props = defineProps({ actionLabel: { type: String, default: 'Add to library' } })
const emit = defineEmits(['added']) // the new (or already existing) media item

const provider = ref('all')
const providers = ref([{ id: 'all', label: 'All free sources' }])
const q = ref('')
const page = ref(1)
const results = ref([])
const loading = ref(false)
const busy = ref('') // key of the image being copied
const error = ref('')
const searched = ref(false)
const STARTERS = ['business', 'office', 'food', 'nature', 'people', 'city', 'technology', 'abstract background']

onMounted(async () => { try { providers.value = (await api.get('/images/providers')).data } catch (e) { /* keep default */ } })

async function search(p = 1) {
  if (!q.value.trim()) return
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/images/search', { params: { q: q.value, provider: provider.value, page: p } })
    page.value = p
    results.value = data
    searched.value = true
  } catch (e) { error.value = errorMessage(e); results.value = [] } finally { loading.value = false }
}
const starter = (s) => { q.value = s; search(1) }

const key = (img) => img.source + img.id
async function add(img) {
  busy.value = key(img)
  error.value = ''
  try {
    const { data } = await api.post('/media/import', {
      url: img.url, thumb: img.thumb, source: img.source, author: img.author, author_url: img.author_url, license: img.license, link: img.link,
    })
    emit('added', data)
  } catch (e) { error.value = errorMessage(e) } finally { busy.value = '' }
}
</script>

<template>
  <div>
    <form class="row" @submit.prevent="search(1)">
      <select v-model="provider" style="width: 190px">
        <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.label }}</option>
      </select>
      <input v-model="q" type="search" placeholder="Search free photos, e.g. coffee shop" style="flex: 1; min-width: 200px" />
      <button class="btn" :disabled="loading">{{ loading ? 'Searching…' : 'Search' }}</button>
    </form>
    <div class="chips"><button v-for="s in STARTERS" :key="s" type="button" @click="starter(s)">{{ s }}</button></div>

    <div v-if="error" class="alert err" style="margin-top: 12px">{{ error }}</div>
    <p v-else-if="!searched" class="empty">Search above, or pick a topic, to browse free-licence photos.</p>
    <p v-else-if="!results.length" class="empty">No images found.</p>

    <div class="imgs">
      <figure v-for="img in results" :key="key(img)">
        <img :src="img.thumb" :alt="`Photo by ${img.author}`" loading="lazy" />
        <figcaption>
          <span :title="img.author">{{ img.author || 'Unknown' }}</span>
          <small>{{ img.source }}<template v-if="img.license"> · {{ img.license }}</template></small>
          <button type="button" class="btn sm" :disabled="busy === key(img)" @click="add(img)">{{ busy === key(img) ? 'Adding…' : props.actionLabel }}</button>
        </figcaption>
      </figure>
    </div>

    <div v-if="results.length" class="row" style="justify-content: center; margin-top: 14px">
      <button class="btn ghost sm" :disabled="page <= 1 || loading" @click="search(page - 1)">Previous</button>
      <small>Page {{ page }}</small>
      <button class="btn ghost sm" :disabled="loading" @click="search(page + 1)">Next</button>
    </div>
    <p v-if="searched" class="note">Free-licence images. A copy is saved in your media library with the photographer's credit and licence.</p>
  </div>
</template>

<style scoped>
.chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
.chips button { border: 1px solid #d5d9ee; background: #fff; border-radius: 14px; padding: 3px 11px; font: inherit; font-size: 12.5px; color: #3f51b5; cursor: pointer; }
.chips button:hover { background: #eef0fb; }
.imgs { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 14px; margin-top: 14px; }
figure { margin: 0; border: 1px solid #e0e3f0; border-radius: 8px; overflow: hidden; background: #fff; display: flex; flex-direction: column; }
figure img { width: 100%; height: 125px; object-fit: cover; display: block; background: #f3f4f8; }
figcaption { padding: 8px 10px 10px; display: flex; flex-direction: column; gap: 2px; font-size: 13px; }
figcaption span { font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
figcaption small { color: #6b7280; margin-bottom: 6px; }
.note { margin: 14px 0 0; font-size: 12.5px; color: #6b7280; }
</style>
