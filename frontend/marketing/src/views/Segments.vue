<script setup>
import DateInput from '../components/DateInput.vue'
import { ref, reactive, computed, watch, onMounted } from 'vue'
import api, { errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import { useFilters } from '../composables/useFilters'
import RowMenu from '../components/RowMenu.vue'
import Modal from '../components/Modal.vue'

const rows = ref([])
const { f: flt, shown: visible, dirty: fDirty, clear: fClear } = useFilters(rows, { text: (s) => [s.name, s.description] })
const fields = ref([])
const lists = ref([])
const tags = ref([])
const editing = ref(null)
const error = ref('')
const form = reactive({ name: '', description: '', match: 'all', rules: [] })
const preview = ref(null)
const busy = ref(false)

const OPS = {
  is: 'is', in: 'is', not_in: 'is not', equals: 'equals', contains: 'contains', not_contains: 'does not contain',
  starts_with: 'starts with', after: 'on or after', before: 'before', yes: 'did', no: 'did not',
}
const SOURCES = [['manual', 'Added manually'], ['import', 'CSV import'], ['form', 'Sign-up form'], ['api', 'API']]
const meta = (key) => fields.value.find((f) => f.key === key)

const load = async () => { rows.value = (await api.get('/segments')).data }

function newRule() {
  const f = fields.value[0]
  return { field: f.key, op: f.ops[0], value: f.type === 'bool' ? 'true' : '' }
}

function open(s) {
  Object.assign(form, { name: s?.name || '', description: s?.description || '', match: s?.match || 'all', rules: s ? JSON.parse(JSON.stringify(s.rules)) : [newRule()] })
  editing.value = s ? s.id : 'new'
  error.value = ''
  preview.value = null
  refreshPreview()
}

// changing the field resets operator + value to something valid for the new field
function fieldChanged(r) {
  const f = meta(r.field)
  r.op = f.ops[0]
  r.value = f.type === 'bool' ? 'true' : f.type === 'source' ? 'manual' : f.type === 'days' ? '30' : ''
}

let timer
function refreshPreview() {
  clearTimeout(timer)
  timer = setTimeout(async () => {
    try { preview.value = (await api.post('/segments/preview', { match: form.match, rules: form.rules })).data } catch (e) { preview.value = null }
  }, 350)
}
watch(() => [form.match, JSON.stringify(form.rules)], refreshPreview)

async function save() {
  busy.value = true
  error.value = ''
  try {
    editing.value === 'new' ? await api.post('/segments', form) : await api.put(`/segments/${editing.value}`, form)
    editing.value = null
    load()
  } catch (e) { error.value = errorMessage(e) } finally { busy.value = false }
}

async function remove(s) {
  if (!confirm(`Delete segment "${s.name}"? Campaigns using it fall back to all people.`)) return
  await api.delete(`/segments/${s.id}`)
  load()
}

const summary = (s) => `${s.rules.length} rule(s), match ${s.match === 'all' ? 'ALL' : 'ANY'}`
const canSave = computed(() => form.name.trim() && form.rules.length)

onMounted(async () => {
  ;[fields.value, lists.value, tags.value] = (await Promise.all([api.get('/segments/fields'), api.get('/lists'), api.get('/tags')])).map((r) => r.data)
  load()
})
</script>

<template>
  <div class="head"><h1>Segments</h1><div class="row"><router-link to="/segments/history" class="btn ghost">Segment histories</router-link><button class="btn" :disabled="!fields.length" @click="open(null)">New segment</button></div></div>
  <p class="intro">A segment is a saved group of people that updates itself — e.g. “gmail users tagged VIP who opened an email this month”. Use it as the audience for a campaign.</p>

  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="flt.q" type="search" placeholder="Search segments…" />
  </FilterBar>

  <div class="card">
    <p v-if="!rows.length" class="empty">No segments yet.</p>
    <p v-else-if="!visible.length" class="empty">Nothing matches these filters.</p>
    <table v-else>
      <thead><tr><th>Name</th><th>Rules</th><th>People now</th><th class="act">Actions</th></tr></thead>
      <tr v-for="s in visible" :key="s.id">
        <td><b>{{ s.name }}</b><br /><small>{{ s.description }}</small></td>
        <td>{{ summary(s) }}</td>
        <td><router-link :to="`/people?segment_id=${s.id}`">{{ s.people_count }}</router-link></td>
        <td class="act"><RowMenu :history="{ type: 'segment', id: s.id, title: s.name }" :items="[{ label: 'Send campaign', to: `/campaigns/new?segment_id=${s.id}` }, { label: 'Edit', run: () => open(s) }, { label: 'Delete', run: () => remove(s), danger: true }]" /></td>
      </tr>
    </table>
  </div>

  <Modal v-if="editing" :title="editing === 'new' ? 'New segment' : 'Edit segment'" wide @close="editing = null">
    <form @submit.prevent="save">
      <div v-if="error" class="alert err">{{ error }}</div>
      <div class="grid g2">
        <div class="field"><label>Name</label><input v-model="form.name" required maxlength="255" /></div>
        <div class="field"><label>Description (optional)</label><input v-model="form.description" /></div>
      </div>

      <div class="row" style="margin-bottom: 10px">
        People must match
        <select v-model="form.match" style="width: 130px"><option value="all">ALL rules</option><option value="any">ANY rule</option></select>
      </div>

      <div v-for="(r, i) in form.rules" :key="i" class="rule">
        <select v-model="r.field" style="width: 230px" @change="fieldChanged(r)"><option v-for="f in fields" :key="f.key" :value="f.key">{{ f.label }}</option></select>
        <select v-model="r.op" style="width: 150px"><option v-for="o in meta(r.field).ops" :key="o" :value="o">{{ OPS[o] }}</option></select>

        <select v-if="meta(r.field).type === 'bool'" v-model="r.value" style="width: 110px"><option value="true">Yes</option><option value="false">No</option></select>
        <select v-else-if="meta(r.field).type === 'tag'" v-model="r.value" style="flex: 1"><option value="">Choose tag…</option><option v-for="t in tags" :key="t.id" :value="t.id">{{ t.name }}</option></select>
        <select v-else-if="meta(r.field).type === 'list'" v-model="r.value" style="flex: 1"><option value="">Choose list…</option><option v-for="l in lists" :key="l.id" :value="l.id">{{ l.name }}</option></select>
        <select v-else-if="meta(r.field).type === 'source'" v-model="r.value" style="flex: 1"><option v-for="[v, l] in SOURCES" :key="v" :value="v">{{ l }}</option></select>
        <DateInput v-else-if="meta(r.field).type === 'date'" v-model="r.value" style="flex: 1" />
        <input v-else-if="meta(r.field).type === 'days'" v-model="r.value" type="number" min="1" max="365" style="width: 90px" /><span v-if="meta(r.field).type === 'days'">days</span>
        <input v-else v-model="r.value" style="flex: 1" placeholder="value" />

        <button type="button" class="x" title="Remove rule" @click="form.rules.splice(i, 1)">&times;</button>
      </div>
      <button type="button" class="btn ghost sm" :disabled="form.rules.length >= 20" @click="form.rules.push(newRule())">+ Add rule</button>

      <div class="pv">
        <template v-if="preview">
          <b>{{ preview.count }}</b> person(s) match right now
          <span v-if="preview.sample.length" class="muted"> — e.g. {{ preview.sample.map((c) => c.email || c.phone).join(', ') }}</span>
        </template>
        <span v-else class="muted">Calculating…</span>
      </div>

      <div class="row" style="margin-top: 14px"><button class="btn" :disabled="busy || !canSave">Save segment</button><button type="button" class="btn ghost" @click="editing = null">Cancel</button></div>
    </form>
  </Modal>
</template>

<style scoped>
.intro { color: #6b7280; margin: -8px 0 18px; }
.rule { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap; }
.x { background: none; border: 0; font-size: 22px; color: #9e9e9e; cursor: pointer; }
.pv { margin-top: 16px; background: #eef0fb; border-radius: 4px; padding: 10px 14px; }
.muted { color: #6b7280; }
</style>
