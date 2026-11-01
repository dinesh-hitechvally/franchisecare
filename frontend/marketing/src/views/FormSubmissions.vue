<script setup>
import DateInput from '../components/DateInput.vue'
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import api, { download, errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import RowMenu from '../components/RowMenu.vue'
import Pager from '../components/Pager.vue'
import { FORM_TYPES } from '../builder/formFields'

// The inbox of one form: everything people submitted, whether or not they became contacts.
const route = useRoute()
const form = ref(null)
const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const error = ref('')
const f = reactive({ search: '', contact: '', from: '', to: '' })
const fDirty = computed(() => Object.values(f).some(Boolean))
const fClear = () => Object.keys(f).forEach((k) => (f[k] = ''))
const open = ref(null) // submission id whose full answers are expanded

const who = (s) => (s.contact ? [s.contact.first_name, s.contact.last_name].filter(Boolean).join(' ') || s.contact.email : null)
// the From column already shows name + email, so lead with the rest (e.g. the message)
const preview = (s) => {
  const rest = s.data.filter((a) => a.value !== '' && !['first_name', 'last_name', 'email'].includes(a.type))
  return (rest.length ? rest : s.data.filter((a) => a.value !== '')).slice(0, 3).map((a) => `${a.label}: ${a.value}`).join('  ·  ')
}

async function load(page = 1) {
  const { data } = await api.get(`/forms/${route.params.id}/submissions`, { params: { page, ...Object.fromEntries(Object.entries(f).filter(([, v]) => v)) } })
  rows.value = data.data
  meta.value = data
}

async function remove(s) {
  if (!confirm('Delete this submission? This cannot be undone.')) return
  try { await api.delete(`/forms/${route.params.id}/submissions/${s.id}`); load(meta.value.current_page) } catch (e) { error.value = errorMessage(e) }
}

async function exportCsv() {
  try { await download(`/forms/${route.params.id}/submissions/export`, `form-${route.params.id}-submissions.csv`) } catch (e) { error.value = errorMessage(e) }
}

let t
watch(f, () => { clearTimeout(t); t = setTimeout(() => load(), 300) })
onMounted(async () => {
  try {
    form.value = (await api.get(`/forms/${route.params.id}`)).data
    await load()
  } catch (e) { error.value = errorMessage(e) }
})
</script>

<template>
  <div class="head">
    <div>
      <h1>{{ form?.name || 'Form' }} — inbox <small>({{ meta.total }})</small></h1>
      <small v-if="form"><span class="badge" :class="'ft-' + form.type">{{ FORM_TYPES[form.type]?.label }}</span></small>
    </div>
    <div class="row">
      <router-link to="/forms" class="btn ghost">Back to forms</router-link>
      <router-link v-if="form" :to="`/forms/${form.id}`" class="btn ghost">Edit form</router-link>
      <button class="btn" :disabled="!meta.total" @click="exportCsv">Export CSV</button>
    </div>
  </div>
  <div v-if="error" class="alert err">{{ error }}</div>

  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="f.search" type="search" placeholder="Search answers, name, email…" />
    <select v-model="f.contact"><option value="">All submissions</option><option value="yes">Saved as a contact</option><option value="no">Inbox only</option></select>
    <label>From <DateInput v-model="f.from" /></label>
    <label>To <DateInput v-model="f.to" /></label>
  </FilterBar>

  <div class="card">
    <p v-if="!rows.length" class="empty">Nothing here yet. Submissions appear as soon as someone fills in the form.</p>
    <table v-else>
      <thead><tr><th>Received</th><th>From</th><th>Answers</th><th class="act">Actions</th></tr></thead>
      <template v-for="s in rows" :key="s.id">
        <tr class="clickable" @click="open = open === s.id ? null : s.id">
          <td style="white-space: nowrap">{{ $dt(s.created_at) }}</td>
          <td>{{ who(s) || '—' }}<br /><small v-if="s.contact">{{ s.contact.email }}</small></td>
          <td class="prev">{{ preview(s) }}</td>
          <td class="act" @click.stop><RowMenu :history="{ type: 'form_submission', id: s.id, title: $dt(s.created_at) }" :items="[{ label: open === s.id ? 'Hide answers' : 'View answers', run: () => (open = open === s.id ? null : s.id) }, { label: 'Delete', run: () => remove(s), danger: true }]" /></td>
        </tr>
        <tr v-if="open === s.id" class="detail">
          <td colspan="4">
            <dl>
              <template v-for="(a, i) in s.data" :key="i"><dt>{{ a.label }}</dt><dd>{{ a.value === '' ? '—' : a.value }}</dd></template>
            </dl>
          </td>
        </tr>
      </template>
    </table>
    <Pager :meta="meta" @page="load" />
  </div>
</template>

<style scoped>
.clickable { cursor: pointer; } .clickable:hover { background: #fafbff; }
.prev { max-width: 460px; color: #555; font-size: 13px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.detail td { background: #f7f8fc; padding: 4px 20px 14px; }
dl { display: grid; grid-template-columns: 200px 1fr; gap: 6px 16px; margin: 8px 0 0; }
dt { color: #6b7280; font-size: 13px; } dd { margin: 0; white-space: pre-wrap; word-break: break-word; }
.ft-newsletter { background: #e8eaf6; color: #3f51b5; } .ft-contact { background: #e0f2f1; color: #00796b; } .ft-other { background: #f3e5f5; color: #8e24aa; }
</style>
