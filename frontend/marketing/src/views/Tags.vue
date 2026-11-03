<script setup>
import { ref, reactive, onMounted } from 'vue'
import api, { errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import { useFilters } from '../composables/useFilters'
import RowMenu from '../components/RowMenu.vue'
import Modal from '../components/Modal.vue'

const rows = ref([])
const { f: flt, shown: visible, dirty: fDirty, clear: fClear } = useFilters(rows, { text: (t) => t.name, match: { use: (t) => (t.contacts_count ? 'used' : 'unused') } })
const editing = ref(null)
const error = ref('')
const form = reactive({ name: '', color: '#3f51b5' })
const COLORS = ['#3f51b5', '#e91e63', '#00897b', '#ff9800', '#9c27b0', '#00bcd4', '#4caf50', '#f44336', '#607d8b']

const load = async () => { rows.value = (await api.get('/tags')).data }

function edit(t) {
  Object.assign(form, { name: t?.name || '', color: t?.color || '#3f51b5' })
  editing.value = t ? t.id : 'new'
  error.value = ''
}

async function save() {
  try {
    editing.value === 'new' ? await api.post('/tags', form) : await api.put(`/tags/${editing.value}`, form)
    editing.value = null
    load()
  } catch (e) { error.value = errorMessage(e) }
}

async function remove(t) {
  if (!confirm(`Delete tag "${t.name}"? It is removed from ${t.contacts_count} contact(s); the contacts themselves are kept.`)) return
  await api.delete(`/tags/${t.id}`)
  load()
}

onMounted(load)
</script>

<template>
  <div class="head"><h1>Tags</h1><button class="btn" @click="edit(null)">New tag</button></div>
  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="flt.q" type="search" placeholder="Search tags…" />
    <select v-model="flt.use"><option value="">Any usage</option><option value="used">In use</option><option value="unused">Not used</option></select>
  </FilterBar>

  <div class="card">
    <p v-if="!rows.length" class="empty">No tags yet. Tags label contacts (e.g. “VIP”, “Wholesale”) so you can filter, segment and automate.</p>
    <p v-else-if="!visible.length" class="empty">Nothing matches these filters.</p>
    <table v-else>
      <thead><tr><th>Name</th><th>Contacts</th><th class="act">Actions</th></tr></thead>
      <tr v-for="t in visible" :key="t.id">
        <td><span class="chip" :style="{ background: t.color + '22', color: t.color }">{{ t.name }}</span></td>
        <td><router-link :to="`/contacts?tag_id=${t.id}`">{{ t.contacts_count }}</router-link></td>
        <td class="act"><RowMenu :history="{ type: 'tag', id: t.id, title: t.name }" :items="[{ label: 'Edit', run: () => edit(t) }, { label: 'Delete', run: () => remove(t), danger: true }]" /></td>
      </tr>
    </table>
  </div>

  <Modal v-if="editing" :title="editing === 'new' ? 'New tag' : 'Edit tag'" @close="editing = null">
    <form @submit.prevent="save">
      <div v-if="error" class="alert err">{{ error }}</div>
      <div class="field"><label>Name</label><input v-model="form.name" required maxlength="60" /></div>
      <div class="field">
        <label>Colour</label>
        <div class="row"><button v-for="c in COLORS" :key="c" type="button" class="sw" :class="{ on: form.color === c }" :style="{ background: c }" :aria-label="c" @click="form.color = c"></button></div>
      </div>
      <button class="btn">Save</button>
    </form>
  </Modal>
</template>

<style scoped>
.chip { display: inline-block; padding: 3px 12px; border-radius: 99px; font-size: 13px; font-weight: 500; }
.sw { width: 28px; height: 28px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 0 1px #d1d5db; cursor: pointer; }
.sw.on { box-shadow: 0 0 0 2px #212121; }
</style>
