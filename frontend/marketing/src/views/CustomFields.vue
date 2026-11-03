<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import api, { errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import { useFilters } from '../composables/useFilters'
import RowMenu from '../components/RowMenu.vue'
import Modal from '../components/Modal.vue'

// Extra data a company keeps on its people, beyond the built-in name, email and mobile.
const rows = ref([])
const { f: flt, shown: visible, dirty: fDirty, clear: fClear } = useFilters(rows, { text: (x) => [x.label, x.key], match: { type: (x) => x.type } })
const editing = ref(null) // id | 'new'
const error = ref('')
const form = reactive({ label: '', type: 'text', optionsText: '' })

const TYPES = {
  text: 'Short text', textarea: 'Long text', number: 'Number', date: 'Date', dropdown: 'Dropdown', radio: 'Choice list', checkbox: 'Yes / No',
}
const hasOptions = computed(() => ['dropdown', 'radio'].includes(form.type))
const tag = (k) => '{{' + k + '}}'

const load = async () => { rows.value = (await api.get('/person-fields')).data }

function open(f) {
  Object.assign(form, { label: f?.label || '', type: f?.type || 'text', optionsText: (f?.options || []).join('\n') })
  editing.value = f ? f.id : 'new'
  error.value = ''
}

async function save() {
  error.value = ''
  const options = hasOptions.value ? form.optionsText.split('\n') : null
  try {
    editing.value === 'new'
      ? await api.post('/person-fields', { label: form.label, type: form.type, options })
      : await api.put(`/person-fields/${editing.value}`, { label: form.label, options })
    editing.value = null
    load()
  } catch (e) { error.value = errorMessage(e) }
}

async function remove(f) {
  const n = f.values_count
  if (!confirm(`Delete the field "${f.label}"?${n ? ` The answers saved on ${n} people are deleted too.` : ''} Forms that ask this question keep working, but stop saving it on people.`)) return
  await api.delete(`/person-fields/${f.id}`)
  load()
}

onMounted(load)
</script>

<template>
  <div class="head"><h1>Custom Fields</h1><button class="btn" @click="open(null)">New field</button></div>
  <p class="intro">Every person has a first name, last name, email and mobile. Custom fields hold anything else you want to know about them — company, plan, birthday… Fill them in on a person, from a sign-up form question, or through the API, and use them as merge tags in emails and SMS.</p>

  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="flt.q" type="search" placeholder="Search custom fields…" />
    <select v-model="flt.type"><option value="">All types</option><option v-for="(l, k) in TYPES" :key="k" :value="k">{{ l }}</option></select>
  </FilterBar>

  <div class="card">
    <p v-if="!rows.length" class="empty">No custom fields yet. They are also created automatically when a form question is set to “Also save this answer on the person”.</p>
    <p v-else-if="!visible.length" class="empty">Nothing matches these filters.</p>
    <table v-else>
      <thead><tr><th>Name</th><th>Merge tag</th><th>Type</th><th>Choices</th><th>People with a value</th><th class="act">Actions</th></tr></thead>
      <tr v-for="f in visible" :key="f.id">
        <td><b>{{ f.label }}</b></td>
        <td><code>{{ tag(f.key) }}</code></td>
        <td>{{ TYPES[f.type] }}</td>
        <td><small>{{ (f.options || []).join(', ') }}</small></td>
        <td>{{ f.values_count }}</td>
        <td class="act"><RowMenu :history="{ type: 'person_field', id: f.id, title: f.label }" :items="[{ label: 'Edit', run: () => open(f) }, { label: 'Delete', run: () => remove(f), danger: true }]" /></td>
      </tr>
    </table>
  </div>

  <Modal v-if="editing" :title="editing === 'new' ? 'New custom field' : 'Edit custom field'" @close="editing = null">
    <form @submit.prevent="save">
      <div v-if="error" class="alert err">{{ error }}</div>
      <div class="field"><label>Label</label><input v-model="form.label" required maxlength="120" placeholder="e.g. Company name" /></div>
      <div class="field">
        <label>Type</label>
        <select v-model="form.type" :disabled="editing !== 'new'">
          <option v-for="(label, value) in TYPES" :key="value" :value="value">{{ label }}</option>
        </select>
        <small v-if="editing !== 'new'">The type and merge tag can’t change once a field exists, so existing answers keep their meaning.</small>
        <small v-else>The merge tag is made from the label and fixed after saving.</small>
      </div>
      <div v-if="hasOptions" class="field">
        <label>Choices <small>(one per line)</small></label>
        <textarea v-model="form.optionsText" rows="5" style="font-family: inherit"></textarea>
      </div>
      <div class="row"><button class="btn">Save</button><button type="button" class="btn ghost" @click="editing = null">Cancel</button></div>
    </form>
  </Modal>
</template>

<style scoped>
.intro { color: #6b7280; margin: -8px 0 18px; }
code { background: #f1f2f6; padding: 1px 6px; border-radius: 3px; font-size: 12px; }
</style>
