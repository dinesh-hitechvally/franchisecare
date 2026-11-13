<script setup>
import { ref, computed, onMounted } from 'vue'
import api, { errorMessage } from '../api'
import RowMenu from '../components/RowMenu.vue'
import Icon from '../components/Icon.vue'
import FilterBar from '../components/FilterBar.vue'
import Modal from '../components/Modal.vue'
import { FORM_TYPES } from '../builder/formFields'

const rows = ref([])
const error = ref('')
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
// the one-script embed: the script goes once on the page, the div goes where the form should appear (any number of forms, by their id)
const scriptCode = (f) => `<script async src="${f.embed_script}"><\/script>`
const divCode = (f) => `<div class="mate-form" data-form-id="${f.id}"></div>`
const jsCode = (f) => scriptCode(f) + '\n' + divCode(f)
const iframeCode = (f) => `<iframe src="${f.url}" width="100%" height="460" style="border:0" title="${f.name.replace(/"/g, '')}"></iframe>`
const fieldSummary = (f) => f.fields.filter((x) => x.type !== 'heading' && x.type !== 'paragraph').map((x) => x.label).join(' · ')

// one line saying what happens on submit, so the list shows how each form behaves
function actions(f) {
  const a = []
  if (f.save_person) a.push(f.subscribe ? 'Saved & subscribed' : 'Saved to People')
  else a.push('Kept as a submission')
  if (f.notify_emails?.length) a.push(`emails ${f.notify_emails.length} ${f.notify_emails.length === 1 ? 'person' : 'people'}`)
  if (f.autoreply) a.push('auto-reply')
  return a.join(' · ')
}

const load = async () => { rows.value = (await api.get('/forms')).data }

async function clone(f) {
  try { await api.post(`/forms/${f.id}/duplicate`); load() } catch (e) { error.value = errorMessage(e) }
}

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
  <div class="head"><h1>Forms</h1><div class="row"><router-link to="/forms/history" class="btn ghost">Form histories</router-link><router-link to="/submissions" class="btn ghost">All submissions</router-link><router-link to="/forms/new" class="btn">New form</router-link></div></div>
  <div v-if="error" class="alert err">{{ error }}</div>
  <p class="intro">Build newsletter sign-ups, contact forms and more. Each form decides what happens when someone submits: save them to People and lists, email you the message, send them an auto-reply, or just collect answers in its inbox.</p>

  <FilterBar :dirty="dirty" @clear="clear">
    <input v-model="q" type="search" placeholder="Search forms…" />
    <select v-model="filter"><option value="all">All types ({{ count('all') }})</option><option v-for="(t, k) in FORM_TYPES" :key="k" :value="k">{{ t.short }} ({{ count(k) }})</option></select>
    <select v-model="status"><option value="">All statuses</option><option value="active">Active</option><option value="paused">Paused</option></select>
  </FilterBar>

  <div class="card">
    <p v-if="!rows.length" class="empty">No forms yet. <router-link to="/forms/new">Build your first form</router-link>.</p>
    <p v-else-if="!shown.length" class="empty">No forms match these filters.</p>
    <table v-else>
      <thead><tr><th>Name</th><th>Type</th><th>On submit</th><th>Views</th><th>Submissions</th><th>Conversion</th><th>Status</th><th class="act">Actions</th></tr></thead>
      <tr v-for="f in shown" :key="f.id">
        <td>
          <router-link :to="`/forms/${f.id}`"><b>{{ f.name }}</b></router-link><a v-if="f.active" :href="f.url" target="_blank" rel="noopener" class="eye" title="View the live form"><Icon name="eye" :size="18" /></a><br />
          <small>{{ fieldSummary(f) }}</small><br />
          <a :href="f.url" target="_blank" rel="noopener"><small>{{ f.url }}</small></a>
        </td>
        <td><span class="badge" :class="'ft-' + f.type">{{ FORM_TYPES[f.type]?.short || f.type }}</span></td>
        <td><small>{{ actions(f) }}</small></td>
        <td>{{ f.views }}</td>
        <td><router-link :to="`/forms/${f.id}/submissions`">{{ f.submissions }}</router-link></td>
        <td>{{ rate(f) }}</td>
        <td><span class="badge" :class="f.active ? 'sent' : ''">{{ f.active ? 'active' : 'paused' }}</span></td>
        <td class="act"><RowMenu :history="{ type: 'form', id: f.id, title: f.name }" :items="[{ label: 'Edit', to: `/forms/${f.id}` }, { label: 'View submissions', to: `/forms/${f.id}/submissions` }, { label: 'Share', run: () => (embed = f) }, { label: 'Clone', run: () => clone(f) }, { label: 'Delete', run: () => remove(f), danger: true }]" /></td>
      </tr>
    </table>
  </div>

  <Modal v-if="embed" :title="`Share “${embed.name}”`" @close="embed = null">
    <div class="field">
      <label>Hosted page link</label>
      <div class="row"><input :value="embed.url" readonly /><button class="btn sm" @click="copy(embed.url, 'url')">{{ copied === 'url' ? 'Copied ✓' : 'Copy' }}</button></div>
    </div>
    <div class="field">
      <label>Embed with JavaScript <span class="rec">recommended</span></label>
      <textarea :value="jsCode(embed)" readonly rows="3" spellcheck="false"></textarea>
      <button class="btn sm" style="margin-top: 6px" @click="copy(jsCode(embed), 'js')">{{ copied === 'js' ? 'Copied ✓' : 'Copy code' }}</button>
      <small class="hint">Add the <b>script</b> line once on the page (for example before &lt;/body&gt;). Put the <b>div</b> where the form should appear. The form is drawn in your page with its own colours, and it uses the form's id, so you can show several forms on one page by adding more divs with their ids. It stays up to date when you change the form. A form that is paused does not show.</small>
    </div>
    <div class="field">
      <label>Embed with an iframe</label>
      <textarea :value="iframeCode(embed)" readonly rows="3"></textarea>
      <button class="btn sm" style="margin-top: 6px" @click="copy(iframeCode(embed), 'code')">{{ copied === 'code' ? 'Copied ✓' : 'Copy code' }}</button>
      <small class="hint">The whole hosted page inside your page. Use it if your website does not allow other scripts.</small>
    </div>
  </Modal>
</template>

<style scoped>
.intro { color: #6b7280; margin: -8px 0 18px; }
.ft-newsletter { background: #e8eaf6; color: #3f51b5; } .ft-contact { background: #e0f2f1; color: #00796b; } .ft-other { background: #f3e5f5; color: #8e24aa; }
.rec { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #2e7d32; background: #e8f5e9; border-radius: 999px; padding: 2px 8px; margin-left: 6px; vertical-align: middle; }
.hint { display: block; margin-top: 8px; color: #6b7280; line-height: 1.45; }
.eye { color: #6b7280; display: inline-flex; vertical-align: middle; margin-left: 8px; } .eye:hover { color: #3f51b5; }
</style>
