<script setup>
import { ref, reactive, onMounted } from 'vue'
import api, { errorMessage } from '../api'
import Modal from '../components/Modal.vue'

const rows = ref([])
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
  if (!confirm(`Delete list "${l.name}"? Contacts are kept.`)) return
  await api.delete(`/lists/${l.id}`)
  load()
}

onMounted(load)
</script>

<template>
  <div class="head"><h1>Lists</h1><button class="btn" @click="edit(null)">New list</button></div>
  <div class="card">
    <p v-if="!rows.length" class="empty">No lists yet. Lists group contacts so you can target a campaign.</p>
    <table v-else>
      <thead><tr><th>Name</th><th>Description</th><th>Contacts</th><th></th></tr></thead>
      <tr v-for="l in rows" :key="l.id">
        <td>{{ l.name }}</td><td>{{ l.description }}</td><td>{{ l.contacts_count }}</td>
        <td style="text-align: right">
          <button class="btn ghost sm" @click="edit(l)">Edit</button>
          <button class="btn ghost sm" @click="remove(l)">Delete</button>
        </td>
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
