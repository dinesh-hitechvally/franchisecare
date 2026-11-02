<script setup>
import { ref, onMounted } from 'vue'
import api, { errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import { useFilters } from '../composables/useFilters'
import RowMenu from '../components/RowMenu.vue'
import Modal from '../components/Modal.vue'
import PageDesigner from '../components/PageDesigner.vue'
import Icon from '../components/Icon.vue'

const rows = ref([])
const { f: flt, shown: visible, dirty: fDirty, clear: fClear } = useFilters(rows, { text: (p) => [p.name, p.title, p.form?.name], match: { status: (p) => (p.published ? 'published' : 'draft') } })
const forms = ref([])
const designer = ref(false)
const initial = ref(null)
const editing = ref(null)   // id | 'new'
const settings = ref(null)  // pending publish settings after the designer is saved
const saving = ref(false)
const error = ref('')
const current = ref(null)

const load = async () => { rows.value = (await api.get('/landing-pages')).data }

function create() {
  editing.value = 'new'
  current.value = { title: '', form_id: '', published: false }
  initial.value = { name: '', design: null }
  designer.value = true
}

// pages built before forms were chosen per section used the page's own form: give those sections that form explicitly
const withForms = (d, formId) => (d?.kind === 'page' ? { ...d, blocks: (d.blocks || []).map((b) => (b.type === 'form' && b.form_id === undefined ? { ...b, form_id: formId || '' } : b)) } : d)

function edit(p) {
  editing.value = p.id
  current.value = { title: p.title || '', form_id: p.form_id || '', published: p.published }
  initial.value = { name: p.name, design: withForms(p.design, p.form_id) }
  // a page made before the page builder existed has an email-style design; the builder can't edit that, so it starts a fresh layout
  if (p.design?.kind !== 'page' && !confirm('This page was made with the old email-style layout. The page builder will start a new design and replace it when you save. Continue?')) return
  designer.value = true
}

// after the designer: ask for title / form / publish, then save everything in one request
function designed(d) {
  settings.value = { name: d.name, design: d.design, html: d.html }
  designer.value = false
  error.value = ''
}

async function save() {
  saving.value = true
  error.value = ''
  // the page's "Form" column shows the first form section's form
  const first = (settings.value.design?.blocks || []).find((b) => b.type === 'form' && b.form_id)
  const body = { ...settings.value, title: current.value.title || null, form_id: first ? first.form_id : null, published: current.value.published }
  try {
    editing.value === 'new' ? await api.post('/landing-pages', body) : await api.put(`/landing-pages/${editing.value}`, body)
    settings.value = null
    editing.value = null
    load()
  } catch (e) { error.value = errorMessage(e) } finally { saving.value = false }
}

async function togglePublish(p) {
  await api.put(`/landing-pages/${p.id}`, { name: p.name, title: p.title, design: p.design, html: p.html, form_id: p.form_id, published: !p.published })
  load()
}

async function remove(p) {
  if (!confirm(`Delete landing page "${p.name}"? Its public link stops working.`)) return
  await api.delete(`/landing-pages/${p.id}`)
  load()
}

onMounted(async () => { forms.value = (await api.get('/forms')).data; load() })
</script>

<template>
  <div class="head"><h1>Pages</h1><button class="btn" @click="create">New page</button></div>
  <p class="intro">Build a full web page from ready-made sections (hero, features, testimonials, pricing, FAQ…), add Form sections (each can show a different form) where you want them, and publish it on its own link. Pages are responsive and look right on phones.</p>

  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="flt.q" type="search" placeholder="Search pages…" />
    <select v-model="flt.status"><option value="">All statuses</option><option value="published">Published</option><option value="draft">Draft</option></select>
  </FilterBar>

  <div class="card">
    <p v-if="!rows.length" class="empty">No landing pages yet.</p>
    <p v-else-if="!visible.length" class="empty">Nothing matches these filters.</p>
    <table v-else>
      <thead><tr><th>Name</th><th>Title</th><th>Views</th><th>Status</th><th class="act">Actions</th></tr></thead>
      <tr v-for="p in visible" :key="p.id">
        <td><a href="#" class="pg-name" @click.prevent="edit(p)"><b>{{ p.name }}</b></a><br /><a v-if="p.published" :href="p.url" target="_blank" rel="noopener"><small>{{ p.url }}</small></a><small v-else>Not published</small></td>
        <td>{{ p.title || 'Landing page' }}<small v-if="!p.title" style="color: #9ca3af"> (default)</small></td>
        <td>{{ p.views }}</td>
        <td><div class="st"><span class="badge" :class="p.published ? 'sent' : ''">{{ p.published ? 'published' : 'draft' }}</span> <a v-if="p.published" :href="p.url" target="_blank" rel="noopener" class="eye" title="View the live page"><Icon name="eye" :size="18" /></a></div></td>
        <td class="act"><RowMenu :history="{ type: 'landing_page', id: p.id, title: p.name }" :items="[{ label: 'Edit', run: () => edit(p) }, ...(p.form_id ? [{ label: 'View submissions', to: `/forms/${p.form_id}/submissions` }] : []), { label: p.published ? 'Unpublish' : 'Publish', run: () => togglePublish(p) }, { label: 'Delete', run: () => remove(p), danger: true }]" /></td>
      </tr>
    </table>
  </div>

  <PageDesigner v-if="designer" :initial="initial" :forms="forms" @save="designed" @close="designer = false; editing = null" />

  <Modal v-if="settings" title="Page settings" @close="settings = null; editing = null">
    <form @submit.prevent="save">
      <div v-if="error" class="alert err">{{ error }}</div>
      <div class="field"><label>Browser tab title</label><input v-model="current.title" :placeholder="settings.name" maxlength="255" /></div>
      <label class="check"><input v-model="current.published" type="checkbox" /> Publish this page now</label>
      <div class="row" style="margin-top: 16px"><button class="btn" :disabled="saving">{{ saving ? 'Saving…' : 'Save page' }}</button><button type="button" class="btn ghost" @click="settings = null; editing = null">Cancel</button></div>
    </form>
  </Modal>
</template>

<style scoped>
.pg-name { color: inherit; text-decoration: none; } .pg-name:hover { color: #3f51b5; text-decoration: underline; }
.st { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.eye { color: #6b7280; display: inline-flex; margin-left: auto; } .eye:hover { color: #3f51b5; }
.intro { color: #6b7280; margin: -8px 0 18px; max-width: 760px; }
</style>
