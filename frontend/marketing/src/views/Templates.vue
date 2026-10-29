<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api, { errorMessage } from '../api'
import Modal from '../components/Modal.vue'
import MessageEditor from '../components/MessageEditor.vue'
import EmailDesigner from '../components/EmailDesigner.vue'

const rows = ref([])
const tab = ref(useRoute().query.tab === 'sms' ? 'sms' : 'email')
const editing = ref(null) // id | 'new'
const designer = ref(false) // email templates use the visual designer
const initial = ref(null)
const saving = ref(false)
const error = ref('')
const form = reactive({ name: '', type: 'sms', subject: '', body: '' })

const load = async () => { rows.value = (await api.get('/templates')).data }

function create() {
  editing.value = 'new'
  error.value = ''
  if (tab.value === 'email') {
    initial.value = { name: '', subject: '', design: null }
    designer.value = true
  } else {
    Object.assign(form, { name: '', type: 'sms', subject: '', body: '' })
  }
}

function edit(t) {
  editing.value = t.id
  error.value = ''
  if (t.type === 'email') {
    initial.value = { name: t.name, subject: t.subject || '', design: t.design, html: t.body }
    designer.value = true
  } else {
    Object.assign(form, { name: t.name, type: 'sms', subject: '', body: t.body })
  }
}

async function persist(payload) {
  try {
    editing.value === 'new' ? await api.post('/templates', payload) : await api.put(`/templates/${editing.value}`, payload)
    editing.value = null
    designer.value = false
    await load()
  } catch (e) {
    error.value = errorMessage(e)
    if (designer.value) alert(error.value)
  }
}

async function saveEmail(d) {
  saving.value = true
  await persist({ name: d.name, type: 'email', subject: d.subject, body: d.body, design: d.design })
  saving.value = false
}

const saveSms = () => persist({ ...form })

async function remove(t) {
  if (!confirm(`Delete template "${t.name}"?`)) return
  await api.delete(`/templates/${t.id}`)
  load()
}

onMounted(load)
</script>

<template>
  <div class="head">
    <h1>Templates</h1>
    <button class="btn" @click="create">New {{ tab }} template</button>
  </div>
  <div class="row" style="margin-bottom: 14px">
    <button class="btn sm" :class="{ ghost: tab !== 'email' }" @click="tab = 'email'">Email</button>
    <button class="btn sm" :class="{ ghost: tab !== 'sms' }" @click="tab = 'sms'">SMS</button>
  </div>

  <div v-if="rows.some((r) => r.type === tab)" class="t-grid">
    <div v-for="t in rows.filter((r) => r.type === tab)" :key="t.id" class="t-card">
      <div class="t-thumb" @click="edit(t)">
        <iframe v-if="t.type === 'email'" :srcdoc="t.body" tabindex="-1" sandbox="" loading="lazy"></iframe>
        <div v-else class="sms-bubble" style="margin: 16px">{{ t.body }}</div>
      </div>
      <div class="t-meta">
        <div><b>{{ t.name }}</b><br /><small>{{ t.subject || 'SMS template' }}</small></div>
        <div><button class="btn ghost sm" @click="edit(t)">Edit</button> <button class="btn ghost sm" @click="remove(t)">Delete</button></div>
      </div>
    </div>
  </div>
  <div v-else class="card"><p class="empty">No {{ tab }} templates yet.</p></div>

  <EmailDesigner v-if="designer" :initial="initial" show-name :saving="saving" @save="saveEmail" @close="designer = false; editing = null" />

  <Modal v-if="editing && !designer" :title="editing === 'new' ? 'New SMS template' : 'Edit SMS template'" @close="editing = null">
    <form @submit.prevent="saveSms">
      <div v-if="error" class="alert err">{{ error }}</div>
      <div class="field"><label>Template name</label><input v-model="form.name" required /></div>
      <MessageEditor type="sms" v-model:body="form.body" />
      <button class="btn" style="margin-top: 14px">Save template</button>
    </form>
  </Modal>
</template>

<style scoped>
.t-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px; }
.t-card { background: #fff; border-radius: 6px; box-shadow: 0 2px 6px rgba(0, 0, 0, .12); overflow: hidden; }
.t-thumb { height: 300px; overflow: hidden; background: #f4f5f7; cursor: pointer; position: relative; border-bottom: 1px solid #e0e0e0; }
.t-thumb iframe { width: 700px; height: 1100px; border: 0; transform: scale(.37); transform-origin: top left; pointer-events: none; }
.t-meta { padding: 14px 16px; display: flex; flex-direction: column; gap: 10px; }
</style>
