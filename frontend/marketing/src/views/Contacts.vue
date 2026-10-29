<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import api, { errorMessage } from '../api'
import Modal from '../components/Modal.vue'
import MultiSelect from '../components/MultiSelect.vue'

const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const lists = ref([])
const search = ref('')
const listFilter = ref('')
const error = ref('')
const msg = ref('')
const editing = ref(null)
const importing = ref(false)
const importList = ref('')
const file = ref(null)

const listOptions = computed(() => lists.value.map((l) => ({ value: l.id, label: l.name })))
const blank = () => ({ first_name: '', last_name: '', email: '', phone: '', email_subscribed: true, sms_subscribed: true, list_ids: [] })
const form = reactive(blank())

async function load(page = 1) {
  const { data } = await api.get('/contacts', { params: { page, search: search.value || undefined, list_id: listFilter.value || undefined } })
  rows.value = data.data
  meta.value = data
}

function edit(c) {
  Object.assign(form, blank(), c ? { ...c, list_ids: c.lists.map((l) => l.id) } : {})
  editing.value = c ? c.id : 'new'
  error.value = ''
}

async function save() {
  error.value = ''
  try {
    editing.value === 'new' ? await api.post('/contacts', form) : await api.put(`/contacts/${editing.value}`, form)
    editing.value = null
    load(meta.value.current_page)
  } catch (e) { error.value = errorMessage(e) }
}

async function remove(c) {
  if (!confirm(`Delete ${c.email || c.phone}?`)) return
  await api.delete(`/contacts/${c.id}`)
  load(meta.value.current_page)
}

async function doImport() {
  if (!file.value) return
  const fd = new FormData()
  fd.append('file', file.value)
  if (importList.value) fd.append('list_id', importList.value)
  error.value = ''
  try {
    const { data } = await api.post('/contacts/import', fd)
    msg.value = `Imported: ${data.created} new, ${data.updated} updated, ${data.skipped} skipped.`
    importing.value = false
    load()
  } catch (e) { error.value = errorMessage(e) }
}

let t
watch([search, listFilter], () => { clearTimeout(t); t = setTimeout(() => load(), 300) })
const route = useRoute()
onMounted(async () => { lists.value = (await api.get('/lists')).data; load(); if (route.query.new) edit(null) })
</script>

<template>
  <div class="head">
    <h1>Contacts <small>({{ meta.total }})</small></h1>
    <div class="row">
      <button class="btn ghost" @click="importing = true; error = ''">Import CSV</button>
      <button class="btn" @click="edit(null)">Add contact</button>
    </div>
  </div>
  <div v-if="msg" class="alert ok">{{ msg }}</div>

  <div class="card">
    <div class="row" style="margin-bottom: 12px">
      <input v-model="search" placeholder="Search name, email, phone…" style="max-width: 320px" />
      <select v-model="listFilter" style="max-width: 200px">
        <option value="">All lists</option>
        <option v-for="l in lists" :key="l.id" :value="l.id">{{ l.name }}</option>
      </select>
    </div>
    <p v-if="!rows.length" class="empty">No contacts found.</p>
    <table v-else>
      <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Lists</th><th></th></tr></thead>
      <tr v-for="c in rows" :key="c.id">
        <td>{{ c.first_name }} {{ c.last_name }}</td>
        <td>{{ c.email }} <span v-if="c.email && !c.email_subscribed" class="badge failed">unsub</span></td>
        <td>{{ c.phone }} <span v-if="c.phone && !c.sms_subscribed" class="badge failed">unsub</span></td>
        <td><span v-for="l in c.lists" :key="l.id" class="badge" style="margin-right: 4px">{{ l.name }}</span></td>
        <td style="text-align: right; white-space: nowrap">
          <button class="btn ghost sm" @click="edit(c)">Edit</button>
          <button class="btn ghost sm" @click="remove(c)">Delete</button>
        </td>
      </tr>
    </table>
    <div v-if="meta.last_page > 1" class="row" style="justify-content: center; margin-top: 12px">
      <button class="btn ghost sm" :disabled="meta.current_page <= 1" @click="load(meta.current_page - 1)">Previous</button>
      <small>Page {{ meta.current_page }} of {{ meta.last_page }}</small>
      <button class="btn ghost sm" :disabled="meta.current_page >= meta.last_page" @click="load(meta.current_page + 1)">Next</button>
    </div>
  </div>

  <Modal v-if="editing" :title="editing === 'new' ? 'Add contact' : 'Edit contact'" @close="editing = null">
    <form @submit.prevent="save">
      <div v-if="error" class="alert err">{{ error }}</div>
      <div class="grid g2">
        <div class="field"><label>First name</label><input v-model="form.first_name" /></div>
        <div class="field"><label>Last name</label><input v-model="form.last_name" /></div>
      </div>
      <div class="field"><label>Email</label><input v-model="form.email" type="email" /></div>
      <div class="field"><label>Phone (international, e.g. +61400000000)</label><input v-model="form.phone" /></div>
      <div class="field">
        <label>Lists</label>
        <MultiSelect v-model="form.list_ids" :options="listOptions" placeholder="Select one or more lists" empty-text="No lists yet — create one under Lists." />
      </div>
      <div class="row">
        <label class="check"><input type="checkbox" v-model="form.email_subscribed" />Subscribed to email</label>
        <label class="check"><input type="checkbox" v-model="form.sms_subscribed" />Subscribed to SMS</label>
      </div>
      <div class="row" style="margin-top: 16px"><button class="btn">Save</button><button type="button" class="btn ghost" @click="editing = null">Cancel</button></div>
    </form>
  </Modal>

  <Modal v-if="importing" title="Import contacts from CSV" @close="importing = false">
    <div v-if="error" class="alert err">{{ error }}</div>
    <p><small>First row must be a header with columns: <code>first_name, last_name, email, phone</code>. Existing contacts (same email) are updated.</small></p>
    <div class="field"><input type="file" accept=".csv,.txt" @change="file = $event.target.files[0]" /></div>
    <div class="field">
      <label>Add to list (optional)</label>
      <select v-model="importList"><option value="">None</option><option v-for="l in lists" :key="l.id" :value="l.id">{{ l.name }}</option></select>
    </div>
    <button class="btn" :disabled="!file" @click="doImport">Import</button>
  </Modal>
</template>
