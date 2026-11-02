<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../api'
import RowMenu from '../components/RowMenu.vue'
import FilterBar from '../components/FilterBar.vue'
import Modal from '../components/Modal.vue'
import { FORM_TYPES } from '../builder/formFields'

const rows = ref([])
const embed = ref(null)
const copied = ref('')
const filter = ref('all') // all | newsletter | contact | other

const q = ref('')
const status = ref('') // '' | active | paused
const dirty = computed(() => filter.value !== 'all' || q.value !== '' || status.value !== '')
const clear = () => { filter.value = 'all'; q.value = ''; status.value = '' }
const shown = computed(() => rows.value.filter((f) => (filter.value === 'all' || f.type === filter.value)
  && (status.value === '' || (f.active ? 'active' : 'paused') === status.value)
  && (!q.value.trim() || [f.name, f.title, f.url].some((t) => String(t || '').toLowerCase().includes(q.value.trim().toLowerCase())))))
const count = (t) => rows.value.filter((f) => t === 'all' || f.type === t).length
const rate = (f) => (f.views ? Math.round((f.submissions / f.views) * 1000) / 10 + '%' : '—')
const iframeCode = (f) => `<iframe src="${f.url}" width="100%" height="460" style="border:0" title="${f.name.replace(/"/g, '')}"></iframe>`
const fieldSummary = (f) => f.fields.filter((x) => x.type !== 'heading' && x.type !== 'paragraph').map((x) => x.label).join(' · ')

// one line saying what happens on submit, so the list shows how each form behaves
function actions(f) {
  const a = []
  if (f.save_contact) a.push(f.subscribe ? 'Saved & subscribed' : 'Saved to contacts')
  else a.push('Inbox only')
  if (f.notify_emails?.length) a.push(`emails ${f.notify_emails.length} ${f.notify_emails.length === 1 ? 'person' : 'people'}`)
  if (f.autoreply) a.push('auto-reply')
  return a.join(' · ')
}

const load = async () => { rows.value = (await api.get('/forms')).data }

async function remove(f) {
  if (!confirm(`Delete form "${f.name}" and its ${f.submissions} stored submission(s)? Its hosted page and embed code stop working.`)) return
  await api.delete(`/forms/${f.id}`)
  load()
}

async function copy(text, key) {
  try { await navigator.clipboard.writeText(text); copied.value = key; setTimeout(() => (copied.value = ''), 1500) } catch (e) { /* clipboard blocked */ }
}

onMounted(load)
</script>

<template>
  <div class="head"><h1>Forms</h1><div class="row"><router-link to="/submissions" class="btn ghost">All submissions</router-link><router-link to="/forms/new" class="btn">New form</router-link></div></div>
  <p class="intro">Build newsletter sign-ups, contact forms and more. Each form decides what happens when someone submits: save them to contacts and lists, email you the message, send them an auto-reply, or just collect answers in its inbox.</p>

  <FilterBar :dirty="dirty" @clear="clear">
    <input v-model="q" type="search" placeholder="Search forms…" />
    <select v-model="filter"><option value="all">All types ({{ count('all') }})</option><option v-for="(t, k) in FORM_TYPES" :key="k" :value="k">{{ t.short }} ({{ count(k) }})</option></select>
    <select v-model="status"><option value="">All statuses</option><option value="active">Active</option><option value="paused">Paused</option></select>
  </FilterBar>

  <div class="card">
    <p v-if="!rows.length" class="empty">No forms yet. <router-link to="/forms/new">Build your first form</router-link>.</p>
    <p v-else-if="!shown.length" class="empty">No forms match these filters.</p>
    <table v-else>
      <thead><tr><th>Form</th><th>Type</th><th>On submit</th><th>Views</th><th>Submissions</th><th>Conv.</th><th>Status</th><th class="act">Actions</th></tr></thead>
      <tr v-for="f in shown" :key="f.id">
        <td>
          <router-link :to="`/forms/${f.id}`"><b>{{ f.name }}</b></router-link><br />
          <small>{{ fieldSummary(f) }}</small><br />
          <a :href="f.url" target="_blank" rel="noopener"><small>{{ f.url }}</small></a>
        </td>
        <td><span class="badge" :class="'ft-' + f.type">{{ FORM_TYPES[f.type]?.short || f.type }}</span></td>
        <td><small>{{ actions(f) }}</small></td>
        <td>{{ f.views }}</td>
        <td><router-link :to="`/forms/${f.id}/submissions`">{{ f.submissions }}</router-link></td>
        <td>{{ rate(f) }}</td>
        <td><span class="badge" :class="f.active ? 'sent' : ''">{{ f.active ? 'active' : 'paused' }}</span></td>
        <td class="act"><RowMenu :history="{ type: 'form', id: f.id, title: f.name }" :items="[{ label: 'Edit', to: `/forms/${f.id}` }, { label: 'View submissions', to: `/forms/${f.id}/submissions` }, { label: 'Share', run: () => (embed = f) }, { label: 'Delete', run: () => remove(f), danger: true }]" /></td>
      </tr>
    </table>
  </div>

  <Modal v-if="embed" :title="`Share “${embed.name}”`" @close="embed = null">
    <div class="field">
      <label>Hosted page link</label>
      <div class="row"><input :value="embed.url" readonly /><button class="btn sm" @click="copy(embed.url, 'url')">{{ copied === 'url' ? 'Copied ✓' : 'Copy' }}</button></div>
    </div>
    <div class="field">
      <label>Embed on your website</label>
      <textarea :value="iframeCode(embed)" readonly rows="3"></textarea>
      <button class="btn sm" style="margin-top: 6px" @click="copy(iframeCode(embed), 'code')">{{ copied === 'code' ? 'Copied ✓' : 'Copy code' }}</button>
    </div>
  </Modal>
</template>

<style scoped>
.intro { color: #6b7280; margin: -8px 0 18px; max-width: 800px; }
.ft-newsletter { background: #e8eaf6; color: #3f51b5; } .ft-contact { background: #e0f2f1; color: #00796b; } .ft-other { background: #f3e5f5; color: #8e24aa; }
</style>
