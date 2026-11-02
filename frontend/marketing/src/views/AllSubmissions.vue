<script setup>
import DateInput from '../components/DateInput.vue'
import { ref, reactive, computed, onMounted, watch } from 'vue'
import api, { errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import RowMenu from '../components/RowMenu.vue'
import Pager from '../components/Pager.vue'

// Everything people submitted through any form, newest first. Each form's own submissions page (with CSV export) is under Forms.
const forms = ref([])
const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const error = ref('')
const f = reactive({ form_id: '', search: '', contact: '', from: '', to: '' })
const fDirty = computed(() => Object.values(f).some(Boolean))
const fClear = () => Object.keys(f).forEach((k) => (f[k] = ''))
const open = ref(null)

const who = (s) => (s.contact ? [s.contact.first_name, s.contact.last_name].filter(Boolean).join(' ') || s.contact.email : null)
const preview = (s) => {
  const rest = s.data.filter((a) => a.value !== '' && !['first_name', 'last_name', 'email'].includes(a.type))
  return (rest.length ? rest : s.data.filter((a) => a.value !== '')).slice(0, 3).map((a) => `${a.label}: ${a.value}`).join('  ·  ')
}

async function load(page = 1) {
  try {
    const { data } = await api.get('/submissions', { params: { page, ...Object.fromEntries(Object.entries(f).filter(([, v]) => v)) } })
    rows.value = data.data
    meta.value = data
  } catch (e) { error.value = errorMessage(e) }
}

async function remove(s) {
  if (!confirm('Delete this submission? This cannot be undone.')) return
  try { await api.delete(`/forms/${s.form_id}/submissions/${s.id}`); load(meta.value.current_page) } catch (e) { error.value = errorMessage(e) }
}

let t
watch(f, () => { clearTimeout(t); t = setTimeout(() => load(), 300) })
onMounted(async () => {
  try { forms.value = (await api.get('/forms')).data } catch (e) { /* the filter just stays empty */ }
  load()
})
</script>

<template>
  <div class="head">
    <h1>All submissions <small>({{ meta.total }})</small></h1>
    <router-link to="/forms" class="btn ghost">Back to forms</router-link>
  </div>
  <div v-if="error" class="alert err">{{ error }}</div>

  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="f.search" type="search" placeholder="Search answers, name, email…" />
    <select v-model="f.form_id"><option value="">All forms</option><option v-for="fm in forms" :key="fm.id" :value="fm.id">{{ fm.name }}</option></select>
    <select v-model="f.contact"><option value="">All submissions</option><option value="yes">Saved as a contact</option><option value="no">Not saved as a contact</option></select>
    <label>From <DateInput v-model="f.from" /></label>
    <label>To <DateInput v-model="f.to" /></label>
  </FilterBar>

  <div class="card">
    <p v-if="!rows.length" class="empty">{{ fDirty ? 'Nothing matches these filters.' : 'Nothing here yet. Submissions appear as soon as someone fills in a form.' }}</p>
    <table v-else>
      <thead><tr><th>Received</th><th>Form</th><th>From</th><th>Answers</th><th class="act">Actions</th></tr></thead>
      <template v-for="s in rows" :key="s.id">
        <tr class="clickable" @click="open = open === s.id ? null : s.id">
          <td style="white-space: nowrap">{{ $dt(s.created_at) }}</td>
          <td><router-link :to="`/forms/${s.form_id}/submissions`" @click.stop>{{ s.form?.name || 'Deleted form' }}</router-link></td>
          <td>{{ who(s) || '—' }}<br /><small v-if="s.contact">{{ s.contact.email }}</small></td>
          <td class="prev">{{ preview(s) }}</td>
          <td class="act" @click.stop><RowMenu :history="{ type: 'form_submission', id: s.id, title: $dt(s.created_at) }" :items="[{ label: open === s.id ? 'Hide answers' : 'View answers', run: () => (open = open === s.id ? null : s.id) }, { label: 'Open form submissions', to: `/forms/${s.form_id}/submissions` }, { label: 'Delete', run: () => remove(s), danger: true }]" /></td>
        </tr>
        <tr v-if="open === s.id" class="detail">
          <td colspan="5">
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
.prev { max-width: 420px; color: #555; font-size: 13px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.detail td { background: #f7f8fc; padding: 4px 20px 14px; }
dl { display: grid; grid-template-columns: 200px 1fr; gap: 6px 16px; margin: 8px 0 0; }
dt { color: #6b7280; font-size: 13px; } dd { margin: 0; white-space: pre-wrap; word-break: break-word; }
</style>
