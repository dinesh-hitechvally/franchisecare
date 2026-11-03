<script setup>
import { ref, reactive, onMounted } from 'vue'
import api, { errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import { useFilters } from '../composables/useFilters'
import RowMenu from '../components/RowMenu.vue'
import Modal from '../components/Modal.vue'

const rows = ref([])
const { f: flt, shown: visible, dirty: fDirty, clear: fClear } = useFilters(rows, { text: (l) => [l.name, l.description], match: { size: (l) => (l.people_count ? 'has' : 'empty') } })
const editing = ref(null)
const error = ref('')
const form = reactive({ name: '', description: '' })

const load = async () => { rows.value = (await api.get('/lists')).data }

function edit(l) {
  Object.assign(form, { name: l?.name || '', description: l?.description || '' })
  editing.value = l ? l.id : 'new'
  error.value = ''
}

async function save() {
  try {
    editing.value === 'new' ? await api.post('/lists', form) : await api.put(`/lists/${editing.value}`, form)
    editing.value = null
    load()
  } catch (e) { error.value = errorMessage(e) }
}

async function remove(l) {
  if (!confirm(`Delete list "${l.name}"? People are kept.`)) return
  await api.delete(`/lists/${l.id}`)
  load()
}

onMounted(load)
</script>

<template>
  <div class="head"><h1>Lists</h1><button class="btn" @click="edit(null)">New list</button></div>
  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="flt.q" type="search" placeholder="Search lists…" />
    <select v-model="flt.size"><option value="">Any size</option><option value="has">With people</option><option value="empty">Empty</option></select>
  </FilterBar>

  <div class="card">
    <p v-if="!rows.length" class="empty">No lists yet. Lists group people so you can target a campaign.</p>
    <p v-else-if="!visible.length" class="empty">Nothing matches these filters.</p>
    <table v-else>
      <thead><tr><th>Name</th><th>Description</th><th>People</th><th class="act">Actions</th></tr></thead>
      <tr v-for="l in visible" :key="l.id">
        <td>{{ l.name }}</td><td>{{ l.description }}</td><td>{{ l.people_count }}</td>
        <td class="act"><RowMenu :history="{ type: 'people_list', id: l.id, title: l.name }" :items="[{ label: 'Edit', run: () => edit(l) }, { label: 'Delete', run: () => remove(l), danger: true }]" /></td>
      </tr>
    </table>
  </div>
  <Modal v-if="editing" :title="editing === 'new' ? 'New list' : 'Edit list'" @close="editing = null">
    <form @submit.prevent="save">
      <div v-if="error" class="alert err">{{ error }}</div>
      <div class="field"><label>Name</label><input v-model="form.name" required /></div>
      <div class="field"><label>Description</label><textarea v-model="form.description" rows="3" style="font-family: inherit"></textarea></div>
      <button class="btn">Save</button>
    </form>
  </Modal>
</template>
