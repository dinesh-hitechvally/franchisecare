<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api, { errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import { useFilters } from '../composables/useFilters'
import RowMenu from '../components/RowMenu.vue'
import Modal from '../components/Modal.vue'
import Icon from '../components/Icon.vue'

const route = useRoute()
const rows = ref([])
const { f: flt, shown: visible, dirty: fDirty, clear: fClear } = useFilters(rows, { text: (w) => w.name, match: { status: (w) => w.status, trigger: (w) => w.trigger_type } })
const triggers = ref([])
const templates = ref([])
const tags = ref([])
const lists = ref([])
const forms = ref([])
const customFields = ref([])
const dateFields = computed(() => customFields.value.filter((c) => c.type === 'date')) // what "a date arrives" can follow
const editing = ref(null)
const error = ref('')
const msg = ref('')
const form = reactive({ name: '', trigger_type: 'contact_created', trigger_config: {}, steps: [] })

const STEP_TYPES = [
  ['send_email', 'Send email', 'email'], ['send_sms', 'Send SMS', 'sms'], ['wait', 'Wait', 'schedule'],
  ['add_tag', 'Add tag', 'coupon'], ['remove_tag', 'Remove tag', 'coupon'], ['add_to_list', 'Add to list', 'list'],
]
const emailTpl = computed(() => templates.value.filter((t) => t.type === 'email'))
const smsTpl = computed(() => templates.value.filter((t) => t.type === 'sms'))
const trig = computed(() => triggers.value.find((t) => t.type === form.trigger_type))
// what a trigger starts out with when you pick it
function defaults(type) {
  return { date_reached: { when: 'on', days: 1, yearly: true }, inactive: { days: 30 } }[type] || {}
}
const label = (type) => triggers.value.find((t) => t.type === type)?.label || type

const load = async () => { rows.value = (await api.get('/workflows')).data }

function blankStep(type) {
  return { send_email: { type, template_id: '' }, send_sms: { type, template_id: '', body: '' }, wait: { type, amount: 1, unit: 'days' }, add_tag: { type, tag_id: '' }, remove_tag: { type, tag_id: '' }, add_to_list: { type, list_id: '' } }[type]
}

function open(w, trigger) {
  Object.assign(form, { name: w?.name || '', trigger_type: w?.trigger_type || trigger || 'contact_created', trigger_config: w ? { ...(w.trigger_config || {}) } : defaults(trigger || 'contact_created'), steps: w ? JSON.parse(JSON.stringify(w.steps)) : [] })
  editing.value = w ? w.id : 'new'
  error.value = ''
}

function move(i, d) {
  const j = i + d
  if (j < 0 || j >= form.steps.length) return
  form.steps.splice(j, 0, form.steps.splice(i, 1)[0])
}

const clean = (v) => (v === '' ? null : v)
async function save() {
  error.value = ''
  const body = {
    name: form.name, trigger_type: form.trigger_type,
    trigger_config: {
      list_id: clean(form.trigger_config.list_id), tag_id: clean(form.trigger_config.tag_id), form_id: clean(form.trigger_config.form_id),
      url: clean((form.trigger_config.url || '').trim()), field_id: clean(form.trigger_config.field_id),
      when: clean(form.trigger_config.when), days: form.trigger_config.when === 'on' ? 0 : clean(form.trigger_config.days),
      yearly: form.trigger_config.yearly ? true : null,
    },
    steps: form.steps.map((s) => Object.fromEntries(Object.entries(s).map(([k, v]) => [k, clean(v)]))),
  }
  try {
    editing.value === 'new' ? await api.post('/workflows', body) : await api.put(`/workflows/${editing.value}`, body)
    editing.value = null
    msg.value = 'Workflow saved. Activate it when you are ready.'
    load()
  } catch (e) { error.value = errorMessage(e) }
}

async function toggle(w) {
  error.value = msg.value = ''
  try {
    const { data } = await api.post(`/workflows/${w.id}/toggle`)
    msg.value = data.status === 'active' ? `“${w.name}” is now active — new matching contacts will enter it.` : `“${w.name}” paused. Contacts already inside stay where they are.`
    load()
  } catch (e) { error.value = errorMessage(e) }
}

async function remove(w) {
  if (!confirm(`Delete workflow "${w.name}" and its history?`)) return
  await api.delete(`/workflows/${w.id}`)
  load()
}

const describe = (s) => {
  if (s.type === 'wait') return `Wait ${s.amount} ${s.unit}`
  const t = templates.value.find((x) => x.id === s.template_id)
  if (s.type === 'send_email') return `Email: ${t?.name || '?'}`
  if (s.type === 'send_sms') return s.template_id ? `SMS: ${t?.name || '?'}` : 'SMS: custom message'
  if (s.type === 'add_to_list') return `Add to list: ${lists.value.find((l) => l.id === s.list_id)?.name || '?'}`
  return `${s.type === 'add_tag' ? 'Add' : 'Remove'} tag: ${tags.value.find((x) => x.id === s.tag_id)?.name || '?'}`
}

onMounted(async () => {
  ;[triggers.value, templates.value, tags.value, lists.value, forms.value, customFields.value] = (await Promise.all([
    api.get('/automation/triggers'), api.get('/templates'), api.get('/tags'), api.get('/lists'), api.get('/forms'), api.get('/contact-fields'),
  ])).map((r) => r.data)
  load()
  if (route.query.trigger) open(null, route.query.trigger) // arrived from the Triggers page
})
</script>

<template>
  <div class="head"><h1>Workflows</h1><div class="row"><router-link to="/workflows/history" class="btn ghost">Workflow histories</router-link><button class="btn" @click="open(null)">New workflow</button></div></div>
  <p class="intro">A workflow runs automatically for each contact when its trigger happens: send emails or texts, wait, then tag or move them.</p>
  <div v-if="msg" class="alert ok">{{ msg }}</div>
  <div v-if="error && !editing" class="alert err">{{ error }}</div>

  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="flt.q" type="search" placeholder="Search workflows…" />
    <select v-model="flt.status"><option value="">All statuses</option><option value="active">Active</option><option value="paused">Paused</option></select>
    <select v-model="flt.trigger"><option value="">Any trigger</option><option v-for="t in [...new Set(rows.map((w) => w.trigger_type))]" :key="t" :value="t">{{ label(t) }}</option></select>
  </FilterBar>

  <div class="card">
    <p v-if="!rows.length" class="empty">No workflows yet. Try “Welcome new contacts”: trigger <i>Contact is added</i> → send email → wait 3 days → send follow-up.</p>
    <p v-else-if="!visible.length" class="empty">Nothing matches these filters.</p>
    <table v-else>
      <thead><tr><th>Name</th><th>Trigger</th><th>Steps</th><th>In progress</th><th>Completed</th><th>Status</th><th class="act">Actions</th></tr></thead>
      <tr v-for="w in visible" :key="w.id">
        <td><b>{{ w.name }}</b></td>
        <td>{{ label(w.trigger_type) }}</td>
        <td>{{ w.steps.length }}</td>
        <td>{{ w.running_count }}</td>
        <td>{{ w.completed_count }}<span v-if="w.failed_count" class="badge failed" style="margin-left: 6px">{{ w.failed_count }} failed</span></td>
        <td><span class="badge" :class="w.status === 'active' ? 'sent' : ''">{{ w.status }}</span></td>
        <td class="act"><RowMenu :history="{ type: 'workflow', id: w.id, title: w.name }" :items="[{ label: 'Edit', run: () => open(w) }, { label: w.status === 'active' ? 'Pause' : 'Activate', run: () => toggle(w) }, { label: 'Run history', to: `/automation/history?workflow_id=${w.id}` }, { label: 'Delete', run: () => remove(w), danger: true }]" /></td>
      </tr>
    </table>
  </div>

  <Modal v-if="editing" :title="editing === 'new' ? 'New workflow' : 'Edit workflow'" wide @close="editing = null">
    <form @submit.prevent="save">
      <div v-if="error" class="alert err">{{ error }}</div>
      <div class="field"><label>Name</label><input v-model="form.name" required maxlength="255" placeholder="e.g. Welcome series" /></div>

      <div class="block start">
        <b>When</b>
        <div class="row" style="margin-top: 8px">
          <select v-model="form.trigger_type" style="width: 240px" @change="form.trigger_config = defaults(form.trigger_type)"><option v-for="t in triggers" :key="t.type" :value="t.type">{{ t.label }}</option></select>
          <select v-if="trig?.config === 'list'" v-model="form.trigger_config.list_id" style="width: 220px"><option value="">Any list</option><option v-for="l in lists" :key="l.id" :value="l.id">{{ l.name }}</option></select>
          <select v-if="trig?.config === 'tag'" v-model="form.trigger_config.tag_id" style="width: 220px"><option value="">Any tag</option><option v-for="t in tags" :key="t.id" :value="t.id">{{ t.name }}</option></select>
          <select v-if="trig?.config === 'form'" v-model="form.trigger_config.form_id" style="width: 220px"><option value="">Any form</option><option v-for="f in forms" :key="f.id" :value="f.id">{{ f.name }}</option></select>
          <input v-if="trig?.config === 'url'" v-model="form.trigger_config.url" maxlength="500" placeholder="Link contains… (empty = any link)" style="width: 280px" />
          <template v-if="trig?.config === 'days'">
            <span>after</span><input v-model.number="form.trigger_config.days" type="number" min="1" max="365" style="width: 90px" /><span>days without opening an email</span>
          </template>
          <template v-if="trig?.config === 'date'">
            <select v-model="form.trigger_config.field_id" style="width: 220px"><option value="">Choose a date field…</option><option v-for="d in dateFields" :key="d.id" :value="d.id">{{ d.label }}</option></select>
            <select v-model="form.trigger_config.when" style="width: 150px"><option value="on">On the day</option><option value="before">Days before</option><option value="after">Days after</option></select>
            <input v-if="form.trigger_config.when !== 'on'" v-model.number="form.trigger_config.days" type="number" min="1" max="3650" style="width: 90px" title="How many days" />
            <label class="check"><input v-model="form.trigger_config.yearly" type="checkbox" /> Repeat every year</label>
          </template>
        </div>
        <small>{{ trig?.description }}</small>
        <small v-if="trig?.config === 'date' && !dateFields.length" style="display: block; margin-top: 6px; color: #c62828">You have no date fields yet. <router-link to="/custom-fields">Add one in Custom Fields</router-link> (type Date), for example Birthday.</small>
      </div>

      <div v-for="(s, i) in form.steps" :key="i" class="block">
        <div class="row" style="justify-content: space-between">
          <b>{{ i + 1 }}. {{ STEP_TYPES.find((t) => t[0] === s.type)[1] }}</b>
          <span>
            <button type="button" class="ic" title="Move up" :disabled="i === 0" @click="move(i, -1)"><Icon name="up" :size="16" /></button>
            <button type="button" class="ic" title="Move down" :disabled="i === form.steps.length - 1" @click="move(i, 1)"><Icon name="down" :size="16" /></button>
            <button type="button" class="ic" title="Remove step" @click="form.steps.splice(i, 1)"><Icon name="trash" :size="16" /></button>
          </span>
        </div>

        <div v-if="s.type === 'send_email'" class="row" style="margin-top: 8px">
          <select v-model="s.template_id" style="width: 300px"><option value="">Choose an email template…</option><option v-for="t in emailTpl" :key="t.id" :value="t.id">{{ t.name }}</option></select>
          <small v-if="!emailTpl.length">Create an email template first.</small>
        </div>
        <div v-else-if="s.type === 'send_sms'" style="margin-top: 8px">
          <select v-model="s.template_id" style="width: 300px"><option value="">Write a custom message instead</option><option v-for="t in smsTpl" :key="t.id" :value="t.id">{{ t.name }}</option></select>
          <textarea v-if="!s.template_id" v-model="s.body" rows="2" maxlength="1600" placeholder="Hi {{first_name}}, …" style="margin-top: 6px; font-family: inherit"></textarea>
        </div>
        <div v-else-if="s.type === 'wait'" class="row" style="margin-top: 8px">
          <input v-model.number="s.amount" type="number" min="0" max="3650" style="width: 90px" />
          <select v-model="s.unit" style="width: 120px"><option value="minutes">minutes</option><option value="hours">hours</option><option value="days">days</option></select>
        </div>
        <div v-else-if="s.type === 'add_to_list'" style="margin-top: 8px">
          <select v-model="s.list_id" style="width: 300px"><option value="">Choose a list…</option><option v-for="l in lists" :key="l.id" :value="l.id">{{ l.name }}</option></select>
        </div>
        <div v-else style="margin-top: 8px">
          <select v-model="s.tag_id" style="width: 300px"><option value="">Choose a tag…</option><option v-for="t in tags" :key="t.id" :value="t.id">{{ t.name }}</option></select>
        </div>
      </div>

      <div class="adds">
        <small>Add a step:</small>
        <button v-for="[type, text, icon] in STEP_TYPES" :key="type" type="button" class="btn ghost sm" :disabled="form.steps.length >= 30" @click="form.steps.push(blankStep(type))"><Icon :name="icon" :size="14" /> {{ text }}</button>
      </div>

      <div class="row" style="margin-top: 16px"><button class="btn" :disabled="!form.name.trim()">Save workflow</button><button type="button" class="btn ghost" @click="editing = null">Cancel</button></div>
    </form>
  </Modal>
</template>

<style scoped>
.intro { color: #6b7280; margin: -8px 0 18px; }
.block { border: 1px solid #e0e0e0; border-radius: 6px; padding: 12px 14px; margin-bottom: 10px; background: #fafafa; }
.block.start { background: #eef0fb; border-color: #c5cae9; }
.ic { border: 0; background: none; cursor: pointer; color: #757575; padding: 3px; } .ic:hover:not(:disabled) { color: #3f51b5; } .ic:disabled { opacity: .3; cursor: default; }
.adds { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-top: 6px; }
.adds .btn { display: inline-flex; align-items: center; gap: 5px; }
</style>
