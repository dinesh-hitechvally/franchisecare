<script setup>
import { ref, reactive, onMounted } from 'vue'
import api, { errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import { useFilters } from '../composables/useFilters'
import RowMenu from '../components/RowMenu.vue'
import Modal from '../components/Modal.vue'
import { useAuth } from '../stores/auth'

// The people of your company. Everyone has full access; a new user automatically joins this company.
const auth = useAuth()
const rows = ref([])
const { f: flt, shown: visible, dirty: fDirty, clear: fClear } = useFilters(rows, { text: (u) => [u.name, u.email] })
const editing = ref(null)
const error = ref('')
const form = reactive({ name: '', email: '', password: '' })

const load = async () => { rows.value = (await api.get('/users')).data }

function open(u) {
  Object.assign(form, { name: u?.name || '', email: u?.email || '', password: '' })
  editing.value = u ? u.id : 'new'
  error.value = ''
}

async function save() {
  error.value = ''
  try {
    editing.value === 'new' ? await api.post('/users', form) : await api.put(`/users/${editing.value}`, form)
    editing.value = null
    load()
  } catch (e) { error.value = errorMessage(e) }
}

async function remove(u) {
  if (!confirm(`Remove ${u.name}? They lose access immediately.`)) return
  try { await api.delete(`/users/${u.id}`); load() } catch (e) { error.value = errorMessage(e) }
}

onMounted(load)
</script>

<template>
  <div class="head">
    <div>
      <h1>Users</h1>
      <small>{{ auth.company?.name }} · {{ rows.length }} user{{ rows.length === 1 ? '' : 's' }}</small>
    </div>
    <button class="btn" @click="open(null)">Add user</button>
  </div>
  <p class="intro">Everyone listed here belongs to <b>{{ auth.company?.name }}</b>, sees the same company data, and has full access. People you add are placed in this company automatically.</p>
  <div v-if="error && !editing" class="alert err">{{ error }}</div>

  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="flt.q" type="search" placeholder="Search users…" />
  </FilterBar>

  <div class="card">
    <table>
      <thead><tr><th>Name</th><th>Email (login)</th><th>Added</th><th class="act">Actions</th></tr></thead>
      <tr v-for="u in visible" :key="u.id">
        <td><b>{{ u.name }}</b> <span v-if="u.id === auth.user.id" class="badge">you</span></td>
        <td>{{ u.email }}</td>
        <td>{{ new Date(u.created_at).toLocaleDateString() }}</td>
        <td class="act"><RowMenu :history="{ type: 'user', id: u.id, title: u.email }" :items="[{ label: 'Edit', run: () => open(u) }, { label: 'Remove', run: () => remove(u), danger: true, show: u.id !== auth.user.id }]" /></td>
      </tr>
    </table>
  </div>

  <Modal v-if="editing" :title="editing === 'new' ? 'Add user' : 'Edit user'" @close="editing = null">
    <form @submit.prevent="save">
      <div v-if="error" class="alert err">{{ error }}</div>
      <p v-if="editing === 'new'" class="intro" style="margin-top: 0">This person will join <b>{{ auth.company?.name }}</b>.</p>
      <div class="field"><label>Name</label><input v-model="form.name" required maxlength="255" /></div>
      <div class="field"><label>Email (their login)</label><input v-model="form.email" type="email" required /></div>
      <div class="field">
        <label>{{ editing === 'new' ? 'Password' : 'New password (leave blank to keep)' }}</label>
        <input v-model="form.password" type="password" minlength="8" :required="editing === 'new'" autocomplete="new-password" />
        <small>At least 8 characters. Share it with them securely.</small>
      </div>
      <div class="row"><button class="btn">Save</button><button type="button" class="btn ghost" @click="editing = null">Cancel</button></div>
    </form>
  </Modal>
</template>

<style scoped>
.intro { color: #6b7280; margin: -4px 0 18px; max-width: 760px; }
</style>
