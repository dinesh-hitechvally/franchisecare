<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { setQuery } from '../urlState'
import api, { errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import { useFilters } from '../composables/useFilters'
import RowMenu from '../components/RowMenu.vue'
import PageDesigner from '../components/PageDesigner.vue'
import Icon from '../components/Icon.vue'

const route = useRoute()
const router = useRouter()
const rows = ref([])
const { f: flt, shown: visible, dirty: fDirty, clear: fClear } = useFilters(rows, { text: (p) => [p.name, p.title, p.form?.name], match: { status: (p) => (p.published ? 'published' : 'draft') } })
const forms = ref([])
const designer = ref(false)
const initial = ref(null)
const editing = ref(null)   // id | 'new'
const saving = ref(false)
const savedTick = ref(0) // bumped after a save that kept the editor open, so its History timeline reloads
const startVersion = ref(null) // history_id to show as soon as the editor opens (coming from a history list)
const error = ref('')

// the part of a page's public address before its slug, taken from any page we already know (the server decides it)
const urlBase = computed(() => { const p = rows.value.find((r) => r.url && r.slug); return p ? p.url.slice(0, p.url.length - p.slug.length) : '' })

const load = async () => { rows.value = (await api.get('/landing-pages')).data }

function create() {
  editing.value = 'new'
  error.value = ''
  initial.value = { name: '', slug: '', title: '', published: false, design: null }
  designer.value = true
}

// pages built before forms were chosen per section used the page's own form: give those sections that form explicitly
const withForms = (d, formId) => (d?.kind === 'page' ? { ...d, blocks: (d.blocks || []).map((b) => (b.type === 'form' && b.form_id === undefined ? { ...b, form_id: formId || '' } : b)) } : d)

function edit(p, version = null) {
  editing.value = p.id
  startVersion.value = version
  setQuery({ edit: p.id, history: version }) // an open editor has an address: a refresh or a shared link comes back to it
  error.value = ''
  initial.value = { name: p.name, slug: p.slug, title: p.title || '', published: p.published, design: withForms(p.design, p.form_id) }
  // a page made before the page builder existed has an email-style design; the builder can't edit that, so it starts a fresh layout
  if (p.design?.kind !== 'page' && !confirm('This page was made with the old email-style layout. The page builder will start a new design and replace it when you save. Continue?')) return
  designer.value = true
}

// the designer holds everything (name, browser tab title, publish, design): one save, then back to the list
async function save(d) {
  saving.value = true
  error.value = ''
  // the page's attached form is the first form section's form
  const first = (d.design?.blocks || []).find((b) => b.type === 'form' && b.form_id)
  const body = { name: d.name, slug: d.slug || null, title: d.title || null, design: d.design, html: d.html, form_id: first ? first.form_id : null, published: d.published }
  try {
    editing.value === 'new' ? await api.post('/landing-pages', body) : await api.put(`/landing-pages/${editing.value}`, body)
    if (d.keepOpen && editing.value !== 'new') { load(); savedTick.value++; return } // History is open in the editor: stay, so the new version shows in the timeline
    designer.value = false
    editing.value = null
    leave()
    load()
  } catch (e) { error.value = errorMessage(e) } finally { saving.value = false }
}

// the address carries ?edit=&history= when the editor was opened from a history list: drop it once the editor is closed
function leave() {
  startVersion.value = null
  setQuery({ edit: null, history: null })
  if (route.query.edit) router.replace('/pages') // arrived through a link with ?edit=: bring the router's own record of the address back in line
}
function closeDesigner() { designer.value = false; editing.value = null; leave() }

function openFromQuery() {
  const q = route.query
  const p = q.edit ? rows.value.find((r) => String(r.id) === String(q.edit)) : null
  if (p) edit(p, q.history ? Number(q.history) : null)
}

async function togglePublish(p) {
  await api.put(`/landing-pages/${p.id}`, { name: p.name, title: p.title, design: p.design, html: p.html, form_id: p.form_id, published: !p.published })
  load()
}

async function clone(p) {
  error.value = ''
  try { await api.post(`/landing-pages/${p.id}/duplicate`); load() } catch (e) { error.value = errorMessage(e) }
}

async function remove(p) {
  if (!confirm(`Delete landing page "${p.name}"? Its public link stops working.`)) return
  await api.delete(`/landing-pages/${p.id}`)
  load()
}

onMounted(async () => { forms.value = (await api.get('/forms')).data; await load(); openFromQuery() })
</script>

<template>
  <div class="head"><h1>Pages</h1><div class="row"><router-link to="/pages/history" class="btn ghost">Page histories</router-link><button class="btn" @click="create">New page</button></div></div>
  <div v-if="error && !designer" class="alert err">{{ error }}</div>
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
        <td class="act"><RowMenu :history="{ type: 'landing_page', id: p.id, title: p.name }" :items="[{ label: 'Edit', run: () => edit(p) }, { label: 'Clone', run: () => clone(p) }, ...(p.form_id ? [{ label: 'View submissions', to: `/forms/${p.form_id}/submissions` }] : []), { label: p.published ? 'Unpublish' : 'Publish', run: () => togglePublish(p) }, { label: 'Delete', run: () => remove(p), danger: true }]" /></td>
      </tr>
    </table>
  </div>

  <PageDesigner v-if="designer" :initial="initial" :history-id="editing === 'new' ? null : editing" :start-version="startVersion" :saved-tick="savedTick" :url-base="urlBase" :forms="forms" :saving="saving" :error="error" @save="save" @close="closeDesigner" @version="(v) => setQuery({ history: v })" />
</template>

<style scoped>
.pg-name { color: inherit; text-decoration: none; } .pg-name:hover { color: #3f51b5; text-decoration: underline; }
.st { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.eye { color: #6b7280; display: inline-flex; margin-left: auto; } .eye:hover { color: #3f51b5; }
.intro { color: #6b7280; margin: -8px 0 18px; }
</style>
