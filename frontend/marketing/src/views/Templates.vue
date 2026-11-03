<script setup>
import { fmtDateTime } from '../format'
import { useAuth } from '../stores/auth'
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { setQuery } from '../urlState'
import api, { errorMessage } from '../api'
import RowMenu from '../components/RowMenu.vue'
import HistoryModal from '../components/HistoryModal.vue'
import FilterBar from '../components/FilterBar.vue'
import Modal from '../components/Modal.vue'
import MessageEditor from '../components/MessageEditor.vue'
import EmailDesigner from '../components/EmailDesigner.vue'

const rows = ref([])
const route = useRoute()
const router = useRouter()
const q = route.query.tab
const tab = ref(['email', 'sms'].includes(q) ? q : 'all') // all | email | sms
const saved = (() => { try { return localStorage.getItem('templates.view') } catch (e) { return null } })()
const view = ref(saved === 'table' ? 'table' : 'grid')
const setView = (v) => { view.value = v; try { localStorage.setItem('templates.view', v) } catch (e) { /* storage blocked */ } }
const search = ref('')
const age = ref('') // '' | 7 | 30 (days since last change)
const dirty = computed(() => search.value !== '' || age.value !== '')
const clear = () => { search.value = ''; age.value = '' }
const shown = computed(() => rows.value.filter((r) => (tab.value === 'all' || r.type === tab.value)
  && (!age.value || new Date(r.updated_at) >= new Date(Date.now() - age.value * 86400000))
  && (!search.value.trim() || [r.name, r.subject, r.body && r.body.length < 4000 ? plain(r) : ''].some((t) => String(t || '').toLowerCase().includes(search.value.trim().toLowerCase())))))
const count = (t) => (t === 'all' ? rows.value.length : rows.value.filter((r) => r.type === t).length)
const when = (d) => fmtDateTime(d)
const auth = useAuth()
// The cards are only a preview: merge tags show sample values instead of {{first_name}}. Nothing is saved or sent with them.
const SAMPLE = { first_name: 'Alex', last_name: 'Taylor', full_name: 'Alex Taylor', email: 'alex@example.com', phone: '+61 400 000 000', unsubscribe_url: '#' }
const sample = (text) => String(text || '').replace(/\{\{\s*([a-z_]+)\s*\}\}/gi, (m, k) => {
  const key = k.toLowerCase()
  if (key === 'company_name' || key === 'business_name') return auth.company?.name || 'Your company'
  return SAMPLE[key] ?? m
})
const plain = (t) => (t.body || '').replace(/<style[\s\S]*?<\/style>/gi, '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 90)
const historyOf = ref(null)
const editing = ref(null) // id | 'new'
const designer = ref(false) // email templates use the visual designer
const initial = ref(null)
const saving = ref(false)
const error = ref('')
const form = reactive({ name: '', type: 'sms', subject: '', body: '' })

const load = async () => { rows.value = (await api.get('/templates')).data }

function create(type) {
  editing.value = 'new'
  error.value = ''
  if (type === 'email') {
    initial.value = { name: '', subject: '', design: null, attachments: [] }
    designer.value = true
  } else {
    Object.assign(form, { name: '', type: 'sms', subject: '', body: '' })
  }
}

const savedTick = ref(0) // bumped after a save that kept the editor open, so its History timeline reloads
const startVersion = ref(null) // history_id to show as soon as the editor opens (coming from a history list)
function edit(t, version = null) {
  editing.value = t.id
  error.value = ''
  startVersion.value = version
  if (t.type === 'email') {
    initial.value = { name: t.name, subject: t.subject || '', design: t.design, html: t.body, attachments: t.attachments || [] }
    designer.value = true
    setQuery({ edit: t.id, history: version }) // an open editor has an address: a refresh or a shared link comes back to it
  } else {
    Object.assign(form, { name: t.name, type: 'sms', subject: '', body: t.body })
  }
}

async function persist(payload, keepOpen = false) {
  try {
    editing.value === 'new' ? await api.post('/templates', payload) : await api.put(`/templates/${editing.value}`, payload)
    if (keepOpen) { await load(); savedTick.value++; return } // History is open in the editor: stay, so the new version shows in the timeline
    editing.value = null
    designer.value = false
    leave()
    await load()
  } catch (e) {
    error.value = errorMessage(e)
    if (designer.value) alert(error.value)
  }
}

// the address carries ?edit=&history= when the editor was opened from a history list: drop it once the editor is closed
function leave() {
  startVersion.value = null
  setQuery({ edit: null, history: null })
  if (route.query.edit) router.replace('/templates') // arrived through a link with ?edit=: bring the router's own record of the address back in line
}
function closeDesigner() { designer.value = false; editing.value = null; leave() }
function openFromQuery() {
  const q = route.query
  const t = q.edit ? rows.value.find((r) => String(r.id) === String(q.edit)) : null
  if (t) edit(t, q.history ? Number(q.history) : null)
}

async function saveEmail(d) {
  saving.value = true
  await persist({ name: d.name, type: 'email', subject: d.subject, body: d.body, design: d.design, attachments: d.attachments }, d.keepOpen && editing.value !== 'new')
  saving.value = false
}

const saveSms = () => persist({ ...form })

async function duplicate(t) {
  try { await api.post(`/templates/${t.id}/duplicate`); load() } catch (e) { alert(errorMessage(e)) }
}

async function remove(t) {
  if (!confirm(`Delete template "${t.name}"?`)) return
  await api.delete(`/templates/${t.id}`)
  load()
}

onMounted(async () => { await load(); openFromQuery() })
</script>

<template>
  <div class="head">
    <h1>Templates</h1>
    <div class="row"><router-link to="/templates/history" class="btn ghost">Template histories</router-link><button class="btn" @click="create('email')">New email template</button><button class="btn ghost" @click="create('sms')">New SMS template</button></div>
  </div>
  <div class="bar">
    <div class="tabs">
      <button v-for="[k, l] in [['all', 'All'], ['email', 'Email'], ['sms', 'SMS']]" :key="k" :class="{ on: tab === k }" @click="tab = k">{{ l }} <span class="n">{{ count(k) }}</span></button>
    </div>
    <div class="seg" role="group" aria-label="View">
      <button :class="{ on: view === 'grid' }" title="Grid view" @click="setView('grid')">Grid</button>
      <button :class="{ on: view === 'table' }" title="Table view" @click="setView('table')">Table</button>
    </div>
  </div>

  <FilterBar :dirty="dirty" @clear="clear">
    <input v-model="search" type="search" placeholder="Search templates…" />
    <select v-model="age"><option value="">Changed any time</option><option value="7">Last 7 days</option><option value="30">Last 30 days</option></select>
  </FilterBar>

  <div v-if="shown.length && view === 'table'" class="card">
    <table>
      <thead><tr><th>Name</th><th>Channel</th><th>Subject / message</th><th>Updated</th><th class="act">Actions</th></tr></thead>
      <tr v-for="t in shown" :key="t.id">
        <td><a href="#" @click.prevent="edit(t)"><b>{{ t.name }}</b></a></td>
        <td><span class="badge" :class="t.type">{{ t.type }}</span></td>
        <td><small>{{ t.type === 'email' ? t.subject || plain(t) : plain(t) }}</small></td>
        <td>{{ when(t.updated_at) }}</td>
        <td class="act"><RowMenu :history="{ type: 'template', id: t.id, title: t.name }" :items="[{ label: 'Edit', run: () => edit(t) }, { label: 'Clone', run: () => duplicate(t) }, { label: 'Delete', run: () => remove(t), danger: true }]" /></td>
      </tr>
    </table>
  </div>

  <div v-else-if="shown.length" class="t-grid">
    <div v-for="t in shown" :key="t.id" class="t-card">
      <div class="t-thumb" @click="edit(t)">
        <iframe v-if="t.type === 'email'" :srcdoc="sample(t.body)" tabindex="-1" sandbox="" loading="lazy"></iframe>
        <div v-else class="sms-bubble" style="margin: 16px">{{ sample(t.body) }}</div>
      </div>
      <div class="t-meta">
        <div><b>{{ t.name }}</b><br /><small><span class="badge" :class="t.type">{{ t.type }}</span> {{ sample(t.subject) || (t.type === 'sms' ? 'SMS template' : '') }}</small></div>
      </div>
      <div class="t-btns">
        <button type="button" class="btn sm" @click="edit(t)">Edit</button>
        <button type="button" class="btn ghost sm" @click="duplicate(t)">Clone</button>
        <button type="button" class="btn ghost sm" @click="historyOf = t">History</button>
        <button type="button" class="btn sm danger" @click="remove(t)">Delete</button>
      </div>
    </div>
  </div>
  <div v-else class="card"><p class="empty">No {{ tab === 'all' ? '' : tab + ' ' }}templates yet.</p></div>

  <HistoryModal v-if="historyOf" type="template" :id="historyOf.id" :title="historyOf.name" @close="historyOf = null" />
  <EmailDesigner v-if="designer" :initial="initial" :history-id="editing === 'new' ? null : editing" :start-version="startVersion" :saved-tick="savedTick" show-name :saving="saving" @save="saveEmail" @close="closeDesigner" @version="(v) => setQuery({ history: v })" />

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
.bar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; border-bottom: 1px solid #e0e0e0; }
.tabs { display: flex; gap: 4px; }
.tabs button { background: none; border: 0; border-bottom: 3px solid transparent; padding: 10px 16px; font: inherit; font-weight: 500; color: #6b7280; cursor: pointer; }
.tabs button.on { color: #3f51b5; border-bottom-color: #3f51b5; }
.tabs .n { background: #eceff7; border-radius: 10px; padding: 1px 8px; font-size: 12px; margin-left: 4px; }
.seg { display: flex; margin-bottom: 8px; border: 1px solid #c5cae9; border-radius: 4px; overflow: hidden; }
.seg button { background: #fff; border: 0; padding: 6px 14px; font: inherit; font-size: 13px; cursor: pointer; color: #3f51b5; }
.seg button.on { background: #3f51b5; color: #fff; }
.actions { white-space: nowrap; text-align: right; }
.t-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px; }
.t-card { background: #fff; border-radius: 6px; box-shadow: 0 2px 6px rgba(0, 0, 0, .12); overflow: hidden; }
.t-thumb { height: 300px; overflow: hidden; background: #f4f5f7; cursor: pointer; position: relative; border-bottom: 1px solid #e0e0e0; }
.t-thumb iframe { width: 700px; height: 1100px; border: 0; transform: scale(.37); transform-origin: top left; pointer-events: none; }
.t-btns { display: flex; gap: 8px; padding: 0 16px 14px; }
.t-meta { padding: 14px 16px; display: flex; flex-direction: column; gap: 10px; }
</style>
