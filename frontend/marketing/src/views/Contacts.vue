<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import api, { download, errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import RowMenu from '../components/RowMenu.vue'
import Modal from '../components/Modal.vue'
import MultiSelect from '../components/MultiSelect.vue'
import Pager from '../components/Pager.vue'

const route = useRoute()
const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const lists = ref([])
const tags = ref([])
const customFields = ref([]) // the company's custom contact fields, edited alongside the built-in ones
const segments = ref([])
const f = reactive({ search: '', list_id: '', tag_id: route.query.tag_id || '', segment_id: route.query.segment_id || '', status: route.query.status || '' })
const fDirty = computed(() => Object.values(f).some(Boolean))
const fClear = () => Object.keys(f).forEach((k) => (f[k] = ''))
const error = ref('')
const msg = ref('')
const editing = ref(null)
const importing = ref(false)
const importList = ref('')
const importTag = ref('')
const file = ref(null)
const selected = ref([])
const bulk = reactive({ action: '', tag_id: '', list_id: '' })

const listOptions = computed(() => lists.value.map((l) => ({ value: l.id, label: l.name })))
const tagOptions = computed(() => tags.value.map((t) => ({ value: t.id, label: t.name })))
// the file holds the same contacts as the list shows: whatever filters are set apply
async function exportCsv() {
  const q = new URLSearchParams(Object.entries(f).filter(([, v]) => v)).toString()
  try { await download('/contacts/export' + (q ? '?' + q : ''), 'contacts.csv') } catch (e) { error.value = errorMessage(e) }
}

const blank = () => ({ first_name: '', last_name: '', email: '', phone: '', subscribed: true, list_ids: [], tag_ids: [], custom: {} })
const form = reactive(blank())
const allSelected = computed(() => rows.value.length > 0 && rows.value.every((r) => selected.value.includes(r.id)))

async function load(page = 1) {
  const params = { page }
  for (const k in f) if (f[k]) params[k] = f[k]
  const { data } = await api.get('/contacts', { params })
  rows.value = data.data
  meta.value = data
  selected.value = []
}

let subDirty = false // an existing contact's subscription is only rewritten when the box is toggled
function edit(c) {
  subDirty = false
  Object.assign(form, blank(), c ? { ...c, subscribed: !!c.is_subscribed, list_ids: c.lists.map((l) => l.id), tag_ids: c.tags.map((t) => t.id), custom: { ...(c.custom || {}) } } : {})
  editing.value = c ? c.id : 'new'
  error.value = ''
}

async function save() {
  error.value = ''
  const { subscribed, is_subscribed, subscribed_at, ...rest } = form
  const body = editing.value === 'new' || subDirty ? { ...rest, is_subscribed: subscribed } : rest
  try {
    editing.value === 'new' ? await api.post('/contacts', body) : await api.put(`/contacts/${editing.value}`, body)
    editing.value = null
    load(meta.value.current_page)
  } catch (e) { error.value = errorMessage(e) }
}

async function remove(c) {
  if (!confirm(`Delete ${c.email || c.phone}?`)) return
  await api.delete(`/contacts/${c.id}`)
  load(meta.value.current_page)
}

const toggleAll = () => (selected.value = allSelected.value ? [] : rows.value.map((r) => r.id))

async function runBulk() {
  if (!bulk.action || !selected.value.length) return
  if (bulk.action === 'delete' && !confirm(`Delete ${selected.value.length} contact(s)? This cannot be undone.`)) return
  try {
    const { data } = await api.post('/contacts/bulk', { ids: selected.value, action: bulk.action, tag_id: bulk.tag_id || null, list_id: bulk.list_id || null })
    msg.value = `${data.affected} contact(s) updated.`
    bulk.action = ''
    load(meta.value.current_page)
  } catch (e) { error.value = errorMessage(e) }
}

async function doImport() {
  if (!file.value) return
  const fd = new FormData()
  fd.append('file', file.value)
  if (importList.value) fd.append('list_id', importList.value)
  if (importTag.value) fd.append('tag_id', importTag.value)
  error.value = ''
  try {
    const { data } = await api.post('/contacts/import', fd)
    msg.value = `Imported: ${data.created} new, ${data.updated} updated, ${data.skipped} skipped.`
    importing.value = false
    load()
  } catch (e) { error.value = errorMessage(e) }
}

let t
watch(f, () => { clearTimeout(t); t = setTimeout(() => load(), 300) })
onMounted(async () => {
  ;[lists.value, tags.value, segments.value, customFields.value] = (await Promise.all([api.get('/lists'), api.get('/tags'), api.get('/segments'), api.get('/contact-fields')])).map((r) => r.data)
  load()
  if (route.query.new) edit(null)
})
</script>

<template>
  <div class="head">
    <h1>Contacts <small>({{ meta.total }})</small></h1>
    <div class="row">
      <router-link to="/contacts/history" class="btn ghost">Contact histories</router-link>
      <button class="btn ghost" @click="exportCsv">Export CSV</button>
      <button class="btn ghost" @click="importing = true; error = ''">Import CSV</button>
      <button class="btn" @click="edit(null)">Add contact</button>
    </div>
  </div>
  <div v-if="msg" class="alert ok">{{ msg }}</div>
  <div v-if="error && !editing && !importing" class="alert err">{{ error }}</div>

  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="f.search" placeholder="Search name, email, phone…" style="max-width: 260px" />
    <select v-model="f.list_id" style="max-width: 170px"><option value="">All lists</option><option v-for="l in lists" :key="l.id" :value="l.id">{{ l.name }}</option></select>
    <select v-model="f.tag_id" style="max-width: 170px"><option value="">All tags</option><option v-for="x in tags" :key="x.id" :value="x.id">{{ x.name }}</option></select>
    <select v-model="f.status" style="max-width: 190px"><option value="">Subscribed &amp; unsubscribed</option><option value="subscribed">Subscribed</option><option value="unsubscribed">Unsubscribed</option></select>
    <select v-model="f.segment_id" style="max-width: 190px"><option value="">All contacts</option><option v-for="s in segments" :key="s.id" :value="s.id">Segment: {{ s.name }}</option></select>
  </FilterBar>

  <div class="card">

    <div v-if="selected.length" class="bulkbar">
      <b>{{ selected.length }} selected</b>
      <select v-model="bulk.action" style="width: 170px">
        <option value="">Choose action…</option><option value="add_tag">Add tag</option><option value="remove_tag">Remove tag</option>
        <option value="add_list">Add to list</option><option value="delete">Delete</option>
      </select>
      <select v-if="bulk.action === 'add_tag' || bulk.action === 'remove_tag'" v-model="bulk.tag_id" style="width: 160px"><option value="">Tag…</option><option v-for="x in tags" :key="x.id" :value="x.id">{{ x.name }}</option></select>
      <select v-if="bulk.action === 'add_list'" v-model="bulk.list_id" style="width: 160px"><option value="">List…</option><option v-for="l in lists" :key="l.id" :value="l.id">{{ l.name }}</option></select>
      <button class="btn sm" :disabled="!bulk.action || (bulk.action.includes('tag') && !bulk.tag_id) || (bulk.action === 'add_list' && !bulk.list_id)" @click="runBulk">Apply</button>
    </div>

    <p v-if="!rows.length" class="empty">No contacts found.</p>
    <table v-else>
      <thead><tr><th style="width: 30px"><input type="checkbox" :checked="allSelected" @change="toggleAll" /></th><th>Name</th><th>Email</th><th>Phone</th><th>Subscription</th><th>Lists &amp; tags</th><th class="act">Actions</th></tr></thead>
      <tr v-for="c in rows" :key="c.id">
        <td><input v-model="selected" type="checkbox" :value="c.id" /></td>
        <td>{{ c.first_name }} {{ c.last_name }}<br /><small>{{ c.source }}</small></td>
        <td>{{ c.email }}</td>
        <td>{{ c.phone }}</td>
        <td><span class="badge" :class="c.is_subscribed ? 'sent' : 'failed'">{{ c.is_subscribed ? 'Subscribed' : 'Unsubscribed' }}</span></td>
        <td>
          <span v-for="l in c.lists" :key="'l' + l.id" class="badge" style="margin: 0 4px 4px 0">{{ l.name }}</span>
          <span v-for="x in c.tags" :key="'t' + x.id" class="tagchip" :style="{ background: x.color + '22', color: x.color }">{{ x.name }}</span>
        </td>
        <td class="act"><RowMenu :history="{ type: 'contact', id: c.id, title: c.email || c.phone }" :items="[{ label: 'Edit', run: () => edit(c) }, { label: 'Delete', run: () => remove(c), danger: true }]" /></td>
      </tr>
    </table>
    <Pager :meta="meta" @page="load" />
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
        <MultiSelect v-model="form.list_ids" :options="listOptions" placeholder="Select one or more lists" empty-text="No lists yet — create one under Contact Lists." />
      </div>
      <div class="field">
        <label>Tags</label>
        <MultiSelect v-model="form.tag_ids" :options="tagOptions" placeholder="Select one or more tags" empty-text="No tags yet — create one under Tags." />
      </div>
      <template v-if="customFields.length">
        <div class="grp">Custom fields</div>
        <div v-for="cf in customFields" :key="cf.id" class="field">
          <label>{{ cf.label }}</label>
          <select v-if="['dropdown', 'radio'].includes(cf.type)" v-model="form.custom[cf.key]"><option value="">—</option><option v-for="o in cf.options" :key="o" :value="o">{{ o }}</option></select>
          <select v-else-if="cf.type === 'checkbox'" v-model="form.custom[cf.key]"><option value="">—</option><option value="Yes">Yes</option><option value="No">No</option></select>
          <textarea v-else-if="cf.type === 'textarea'" v-model="form.custom[cf.key]" rows="2" style="font-family: inherit"></textarea>
          <input v-else v-model="form.custom[cf.key]" :type="cf.type === 'number' ? 'number' : cf.type === 'date' ? 'date' : 'text'" />
        </div>
      </template>
      <label class="check"><input type="checkbox" v-model="form.subscribed" @change="subDirty = true" />Subscribed to newsletter</label>
      <div class="row" style="margin-top: 16px"><button class="btn">Save</button><button type="button" class="btn ghost" @click="editing = null">Cancel</button></div>
    </form>
  </Modal>

  <Modal v-if="importing" title="Import contacts from CSV" @close="importing = false">
    <div v-if="error" class="alert err">{{ error }}</div>
    <p><small>First row must be a header with columns: <code>first_name, last_name, email, phone</code>. Existing contacts (same email) are updated.</small></p>
    <div class="field"><input type="file" accept=".csv,.txt" @change="file = $event.target.files[0]" /></div>
    <div class="grid g2">
      <div class="field">
        <label>Add to list (optional)</label>
        <select v-model="importList"><option value="">None</option><option v-for="l in lists" :key="l.id" :value="l.id">{{ l.name }}</option></select>
      </div>
      <div class="field">
        <label>Add tag (optional)</label>
        <select v-model="importTag"><option value="">None</option><option v-for="x in tags" :key="x.id" :value="x.id">{{ x.name }}</option></select>
      </div>
    </div>
    <button class="btn" :disabled="!file" @click="doImport">Import</button>
  </Modal>
</template>

<style scoped>
.bulkbar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; background: #eef0fb; border-radius: 4px; padding: 8px 12px; margin-bottom: 12px; }
.grp { font-size: 12px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: #3f51b5; margin: 16px 0 8px; }
.tagchip { display: inline-block; padding: 2px 9px; border-radius: 99px; font-size: 12px; font-weight: 500; margin: 0 4px 4px 0; }
</style>
