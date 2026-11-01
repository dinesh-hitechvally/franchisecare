<script setup>
// Choose an image from the media library. Nothing is typed or uploaded here: upload in Media, then pick.
import { ref, onMounted, watch } from 'vue'
import api from '../api'
import Modal from './Modal.vue'
import Pager from './Pager.vue'
import OnlineImages from './OnlineImages.vue'

const props = defineProps({ title: { type: String, default: 'Choose an image' }, selectedId: { type: [Number, String], default: null } })
const emit = defineEmits(['pick', 'close'])

const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const search = ref('')
const loading = ref(true)
const tab = ref('library') // library | online

async function load(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/media', { params: { page, kind: 'image', per_page: 12, search: search.value || undefined } })
    rows.value = data.data
    meta.value = data
  } finally { loading.value = false }
}
let t
watch(search, () => { clearTimeout(t); t = setTimeout(() => load(1), 300) })
onMounted(() => load())
</script>

<template>
  <Modal :title="title" wide @close="emit('close')">
    <div class="tabs">
      <button type="button" :class="{ on: tab === 'library' }" @click="tab = 'library'">My media</button>
      <button type="button" :class="{ on: tab === 'online' }" @click="tab = 'online'">Free online images</button>
    </div>
    <OnlineImages v-if="tab === 'online'" action-label="Use this image" @added="(m) => emit('pick', m)" />
    <template v-else>
    <input v-model="search" type="search" placeholder="Search images…" style="max-width: 280px; margin-bottom: 14px" />
    <p v-if="loading" class="empty">Loading…</p>
    <p v-else-if="!rows.length" class="empty">
      {{ search ? 'No image matches.' : 'There are no images in the media library yet.' }}
      <router-link to="/media" @click="emit('close')">Open Media to upload one</router-link>, then come back and choose it.
    </p>
    <div v-else class="pk">
      <button v-for="m in rows" :key="m.id" type="button" class="pic" :class="{ on: Number(selectedId) === m.id }" :title="m.name" @click="emit('pick', m)">
        <span class="im"><img :src="m.url" :alt="m.alt || m.name" loading="lazy" /></span>
        <span class="nm">{{ m.name }}</span>
        <small>{{ m.width }}×{{ m.height }}</small>
      </button>
    </div>
    <Pager :meta="meta" @page="load" />
    </template>
    <p class="foot"><router-link to="/media" @click="emit('close')">Manage the media library</router-link></p>
  </Modal>
</template>

<style scoped>
.tabs { display: flex; gap: 4px; border-bottom: 1px solid #e0e3f0; margin-bottom: 14px; }
.tabs button { background: none; border: 0; border-bottom: 2px solid transparent; padding: 8px 14px; font: inherit; color: #6b7280; cursor: pointer; margin-bottom: -1px; }
.tabs button.on { color: #3f51b5; border-bottom-color: #3f51b5; font-weight: 600; }
.pk { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
.pic { background: #fff; border: 2px solid #e0e3f0; border-radius: 8px; padding: 8px; cursor: pointer; text-align: left; font: inherit; }
.pic:hover { border-color: #3f51b5; }
.pic.on { border-color: #3f51b5; background: #eef0fb; }
.im { display: grid; place-items: center; height: 96px; background: repeating-conic-gradient(#f3f4f8 0% 25%, #fff 0% 50%) 50% / 14px 14px; border-radius: 4px; overflow: hidden; }
.im img { max-width: 100%; max-height: 96px; object-fit: contain; }
.nm { display: block; margin-top: 6px; font-size: 13px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
small { color: #6b7280; }
.foot { margin: 12px 0 0; font-size: 13px; }
</style>
