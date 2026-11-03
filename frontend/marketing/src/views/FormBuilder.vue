<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { errorMessage } from '../api'
import AttachmentsField from '../components/AttachmentsField.vue'
import Icon from '../components/Icon.vue'
import MultiSelect from '../components/MultiSelect.vue'
import HistoryTimeline from '../components/HistoryTimeline.vue'
import { fmtDateTime } from '../format'
import { setQuery } from '../urlState'
import { useAuth } from '../stores/auth'
import InfoTip from '../components/InfoTip.vue'
import { FIELD_TYPES, FORM_TYPES, GROUPS, GROUP_INFO, HELP_LONG, FONTS, WIDTHS, newField, defaultForm, typePreset, slug, uid } from '../builder/formFields'
import { ruleKinds, hasRules, checkValue, ruleProblems, PATTERNS, patternsFor } from '../builder/formChecks'

const route = useRoute()
const router = useRouter()
const id = ref(route.params.id || null)
const auth = useAuth()
const picking = ref(!route.params.id) // a new form starts by asking what kind of form it is

const form = reactive(defaultForm())
const saved = ref(null) // server copy: slug + public url
const loading = ref(!!id.value)
const selectedId = ref(null)
const tryValue = ref('') // a sample answer to test against the selected field's rules
const tab = ref('general') // general | design | settings: what they show depends on whether a field is selected
const hover = ref(-1)
const busy = ref(false)
const error = ref('')
const notice = ref('')
const lists = ref([])
const tags = ref([])
const customFields = ref([]) // the company's managed person fields (Audience → Custom Fields)
const shareOpen = ref(false)
const copied = ref('')
const snapshot = ref('')

// the server sends no rules as [] (an empty PHP array); the builder edits them as an object
const normFields = (list) => (list || []).map((f) => ({ ...f, default: f.default || '', rules: Array.isArray(f.rules) || !f.rules ? {} : { ...f.rules } }))

const s = computed(() => form.style)
const current = computed(() => form.fields.find((f) => f.id === selectedId.value))
const currentIndex = () => form.fields.findIndex((f) => f.id === selectedId.value)
const listOptions = computed(() => lists.value.map((l) => ({ value: l.id, label: l.name })))
const tagOptions = computed(() => tags.value.map((t) => ({ value: t.id, label: t.name })))
const customByKey = computed(() => Object.fromEntries(customFields.value.map((c) => [c.key, c])))
const linked = (f) => !!f && customByKey.value[f.key]?.type === f.type // question bound to an existing custom field
const usedKey = (key) => form.fields.some((f) => f.key === key)
const has = (type) => form.fields.some((f) => f.type === type)
const taken = (type) => FIELD_TYPES[type].unique && has(type)
const typeLabel = (f) => FIELD_TYPES[f.type].label
const isBlock = (f) => FIELD_TYPES[f.type].block
const longHelp = (f) => !isBlock(f) && (f.help || '').length > HELP_LONG // shown as an (i) icon instead of text under the field
const hasEmailOrPhone = computed(() => has('email') || has('phone'))
const needsReachable = computed(() => (form.save_person || form.autoreply) && !hasEmailOrPhone.value)
const parseEmails = (text) => [...new Set((text || '').split(/[,;\s]+/).map((e) => e.trim().toLowerCase()).filter(Boolean))]

const payload = () => ({
  name: form.name.trim(), title: form.title, description: form.description,
  fields: form.fields.map((f) => ({ id: f.id, key: f.key, type: f.type, label: f.label, placeholder: f.placeholder, help: f.help, required: !!f.required, options: f.options || [], width: isBlock(f) ? 'full' : f.width || 'full', to_person: !!f.to_person, default: f.default || '', rules: f.rules || {} })),
  style: form.style, button_text: form.button_text, success_message: form.success_message, existing_message: form.existing_message || null, redirect_url: form.redirect_url || null,
  list_ids: form.list_ids, tag_ids: form.tag_ids, active: form.active,
  type: form.type, save_person: form.save_person, subscribe: form.subscribe,
  notify_emails: form.notify_on ? parseEmails(form.notify_text) : [],
  autoreply: form.autoreply, autoreply_subject: form.autoreply ? form.autoreply_subject : null, autoreply_body: form.autoreply ? form.autoreply_body : null,
  autoreply_attachments: form.autoreply ? form.autoreply_attachments || [] : [],
})
const dirty = computed(() => !viewing.value && snapshot.value !== JSON.stringify(payload()))

// ---------------------------------------------------------------- validation
const tryResult = computed(() => (current.value && tryValue.value !== '' ? checkValue(current.value, tryValue.value) : null))
// "Format": a ready-made pattern, or "custom" to type one
const customFormat = ref(false)
const formatValue = computed(() => {
  if (customFormat.value) return 'custom' // chosen by hand, even when the pattern equals a ready-made one
  const p = current.value?.rules?.pattern
  if (!p) return ''
  return PATTERNS.find((x) => x.pattern === p)?.id || 'custom'
})
const formatInfo = computed(() => PATTERNS.find((x) => x.id === formatValue.value))
function chooseFormat(v) {
  const prev = formatInfo.value
  customFormat.value = v === 'custom'
  if (v === 'custom') return
  if (v === '') {
    setRule('pattern', '')
    if (prev && current.value.rules.message === prev.message) setRule('message', '') // the message that came with the format goes with it
    return
  }
  const next = PATTERNS.find((x) => x.id === v)
  setRule('pattern', next.pattern)
  if (!current.value.rules.message || (prev && current.value.rules.message === prev.message)) setRule('message', next.message)
}
// What the (i) icons say. Each one sits right after the label or title it explains.
const savingText = computed(() => {
  const c = current.value
  if (!c) return ''
  const t = FIELD_TYPES[c.type]
  const parts = []
  if (t.hint) parts.push(form.save_person || c.type === 'captcha' ? t.hint : 'Kept in this form’s submissions. This form does not save people.')
  if (linked(c)) parts.push('Linked to the custom field ' + customByKey.value[c.key].label + ' (' + tagFor(c.key) + '). Its choices are managed under Audience → Custom Fields.')
  if (t.custom && !form.save_person) parts.push('This form does not save sign-ups to People, so no custom field is created.')
  if (['first_name', 'last_name', 'email', 'phone'].includes(c.type)) parts.push('This field always fills the person’s own record when the form saves people. There is nothing more to configure.')
  return parts.join(' ')
})
const toPersonText = computed(() => {
  const c = current.value
  if (!c) return ''
  const key = linked(c) ? c.key : slug(c.label)
  return c.to_person ? 'Stored as the person field ' + key + ' (see Audience → Custom Fields) and usable as the merge tag ' + tagFor(key) + '.' : 'Kept in this form’s submissions only; the person’s record is not changed.'
})
const validationText = computed(() => {
  const c = current.value
  if (!c || hasRules(c.type) || isBlock(c)) return ''
  return c.type === 'captcha' ? 'The visitor has to answer the small sum correctly. It is checked on our server, and each sum works once.' : 'Choices and tick boxes are checked automatically: only a listed choice is accepted, and a required box must be ticked.'
})
const scopeNote = computed(() => {
  if (current.value) {
    if (!isBlock(current.value)) return ''
    return tab.value === 'general' ? 'A heading or paragraph only shows text. Layout is on the Design tab.' : tab.value === 'design' ? 'A heading or paragraph always takes the whole row. Colours, font and width of the form are set when no field is selected.' : ''
  }
  return tab.value === 'general' ? 'The name of the form is at the top. The fields are edited by clicking them in the form.' : ''
})
const submitText = computed(() => (form.save_person
  ? 'First name, last name, email and mobile fill the person’s record. Other questions can be saved as custom fields — choose per question. People are subscribed to marketing when this is a newsletter sign-up, or when they tick the Subscribe to newsletter box if the form has one.'
  : 'Submissions are only kept in this form’s inbox. No person is created.'))
const currentProblems = computed(() => (current.value ? ruleProblems(current.value) : []))
const setRule = (key, value) => { current.value.rules = { ...(current.value.rules || {}), [key]: value } }
watch(() => current.value?.id, () => { tryValue.value = ''; customFormat.value = false })

// ---------------------------------------------------------------- field operations
function select(fid) {
  selectedId.value = fid // the tab stays; its content switches between the field and the whole form
}
function add(type, at) {
  if (taken(type)) return
  const f = newField(type)
  const i = at ?? (currentIndex() >= 0 ? currentIndex() + 1 : form.fields.length)
  form.fields.splice(i, 0, f)
  select(f.id)
}
// a managed custom field becomes a question that is already tied to it: same key, type and choices
function addCustom(cf, at) {
  if (usedKey(cf.key)) return
  const f = { ...newField(cf.type), key: cf.key, label: cf.label, options: [...(cf.options || [])], to_person: true }
  const i = at ?? (currentIndex() >= 0 ? currentIndex() + 1 : form.fields.length)
  form.fields.splice(i, 0, f)
  select(f.id)
}
function move(i, d) {
  const j = i + d
  if (j < 0 || j >= form.fields.length) return
  form.fields.splice(j, 0, form.fields.splice(i, 1)[0])
}
function duplicate(i) {
  const f = JSON.parse(JSON.stringify(form.fields[i]))
  Object.assign(f, { id: uid(), key: null })
  form.fields.splice(i + 1, 0, f)
  select(f.id)
}
function remove(i) {
  form.fields.splice(i, 1)
  selectedId.value = null
}

// drag & drop: palette -> form, and reordering inside the form
function dragNew(e, type) { e.dataTransfer.setData('text/plain', `new:${type}`); e.dataTransfer.effectAllowed = 'copy' }
function dragCustom(e, key) { e.dataTransfer.setData('text/plain', `custom:${key}`); e.dataTransfer.effectAllowed = 'copy' }
function dragMove(e, i) { e.dataTransfer.setData('text/plain', `move:${i}`); e.dataTransfer.effectAllowed = 'move' }
function drop(e, i) {
  hover.value = -1
  if (locked.value) return
  const [kind, v] = (e.dataTransfer.getData('text/plain') || '').split(':')
  if (kind === 'new' && FIELD_TYPES[v]) add(v, i)
  if (kind === 'custom' && customByKey.value[v]) addCustom(customByKey.value[v], i)
  if (kind === 'move') {
    const from = Number(v)
    if (Number.isNaN(from) || from === i || from + 1 === i) return
    const [f] = form.fields.splice(from, 1)
    form.fields.splice(from < i ? i - 1 : i, 0, f)
  }
}

// new form: the chosen type brings its fields, wording and submit actions
function chooseType(type) {
  Object.assign(form, typePreset(type, auth.user?.email || ''))
  picking.value = false
  selectedId.value = null
  tab.value = 'general'
}
// existing form: switching type changes only what happens on submit, never the fields or wording
function changeType(type) {
  const p = typePreset(type, auth.user?.email || '')
  Object.assign(form, { type, save_person: p.save_person, subscribe: p.subscribe, notify_on: p.notify_on, autoreply: p.autoreply })
  if (p.notify_on && !form.notify_text) form.notify_text = p.notify_text
  if (p.autoreply) {
    form.autoreply_subject = form.autoreply_subject || p.autoreply_subject
    form.autoreply_body = form.autoreply_body || p.autoreply_body
  }
}
const tagFor = (k) => '{{' + k + '}}'
const widthOf = (f) => (isBlock(f) ? 'full' : f.width || 'full')
const toggleHalf = (f) => { f.width = widthOf(f) === 'half' ? 'full' : 'half' } // quick: full <-> half
const optionsText = (f) => (f.options || []).join('\n')
const setOptions = (f, text) => { f.options = text.split('\n') }

// ---------------------------------------------------------------- load / save
const fromData = (data) => ({
      name: data.name, title: data.title || '', description: data.description || '', fields: normFields(data.fields), style: { ...defaultForm().style, ...data.style },
      button_text: data.button_text, success_message: data.success_message, existing_message: data.existing_message || '', redirect_url: data.redirect_url || '',
      list_ids: data.list_ids || [], tag_ids: data.tag_ids || [], active: data.active,
      type: data.type || 'newsletter', save_person: data.save_person, subscribe: data.subscribe,
      notify_on: (data.notify_emails || []).length > 0, notify_text: (data.notify_emails || []).join(', '),
      autoreply: data.autoreply, autoreply_subject: data.autoreply_subject || '', autoreply_body: data.autoreply_body || '', autoreply_attachments: data.autoreply_attachments || [],
})

// ---------------------------------------------------------------- history: the header button swaps the side panel for a timeline;
// picking a point shows that saved copy in this same builder, read-only
const historyOpen = ref(false)
const histTick = ref(0) // bumped after each save so an open timeline shows the new entry
const viewing = ref(null) // the saved copy being shown, or null for the form you are editing
let backup = null // the form being edited, kept while a past version is shown
const locked = computed(() => !!viewing.value)
const canHistory = computed(() => !!id.value)
const WHAT = { created: 'Created', updated: 'Updated', deleted: 'Deleted', restored: 'Restored', baseline: 'Existing record' }
const noteText = computed(() => (viewing.value ? (WHAT[viewing.value.change_action] || viewing.value.change_action) + ' on ' + fmtDateTime(viewing.value.changed_at) + (viewing.value.changed_by_name ? ' by ' + viewing.value.changed_by_name : '') + '.' : ''))
const HISTORY_NAMES = { fields: 'Fields', style: 'Design', title: 'Form title', description: 'Description', button_text: 'Button text', success_message: 'Thank-you message', existing_message: 'Message for existing people', redirect_url: 'Redirect', save_person: 'Save to People', subscribe: 'Subscribe', notify_emails: 'Notify by email', autoreply: 'Auto-reply', autoreply_subject: 'Auto-reply subject', autoreply_body: 'Auto-reply message', autoreply_attachments: 'Auto-reply attachments', list_ids: 'Lists', tag_ids: 'Tags', active: 'Active' }
function viewCopy(entry) {
  if (!backup) backup = JSON.parse(JSON.stringify(form))
  Object.assign(form, fromData(entry.data))
  selectedId.value = null
  viewing.value = entry
  setQuery({ history: entry.history_id })
}
function backToCurrent() {
  if (!backup) return
  Object.assign(form, backup)
  backup = null
  viewing.value = null
  selectedId.value = null
  setQuery({ history: null })
}
// opened from a history list (?history=): start with History open and that version shown
async function openVersion(historyId) {
  try {
    const entry = (await api.get('/history/form/entry/' + historyId)).data
    historyOpen.value = true
    viewCopy(entry)
  } catch (e) { error.value = errorMessage(e) }
}
function toggleHistory() {
  if (historyOpen.value) backToCurrent() // leaving history: back on the form you are editing
  historyOpen.value = !historyOpen.value
}
async function load() {
  try {
    const { data } = await api.get(`/forms/${id.value}`)
    Object.assign(form, fromData(data))
    saved.value = data
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
    snapshot.value = JSON.stringify(payload())
  }
}

async function save(close = false) {
  if (!form.name.trim()) { error.value = 'Give the form a name first.'; return }
  busy.value = true
  error.value = notice.value = ''
  try {
    const { data } = id.value ? await api.put(`/forms/${id.value}`, payload()) : await api.post('/forms', payload())
    saved.value = data
    form.fields = normFields(data.fields) // the server may have generated field keys
    snapshot.value = JSON.stringify(payload())
    histTick.value++
    if (close) return router.push('/forms')
    notice.value = 'Saved'
    setTimeout(() => (notice.value = ''), 2500)
    if (!id.value) {
      id.value = data.id
      window.history.replaceState(window.history.state, '', `/forms/${data.id}`) // now editing the saved form, without reloading the builder
    }
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    busy.value = false
  }
}

function back() {
  if (dirty.value && !confirm('You have unsaved changes. Leave without saving?')) return
  router.push('/forms')
}
const warn = (e) => { if (dirty.value) { e.preventDefault(); e.returnValue = '' } }

const iframeCode = computed(() => (saved.value ? `<iframe src="${saved.value.url}" width="100%" height="460" style="border:0" title="${form.name.replace(/"/g, '')}"></iframe>` : ''))
async function copy(text, key) {
  try { await navigator.clipboard.writeText(text); copied.value = key; setTimeout(() => (copied.value = ''), 1500) } catch (e) { /* clipboard blocked */ }
}

onMounted(async () => {
  window.addEventListener('beforeunload', warn)
  ;[lists.value, tags.value, customFields.value] = (await Promise.all([api.get('/lists'), api.get('/tags'), api.get('/person-fields')])).map((r) => r.data)
  if (id.value) {
    await load()
    if (route.query.history) await openVersion(Number(route.query.history))
  } else snapshot.value = JSON.stringify(payload())
})
onBeforeUnmount(() => window.removeEventListener('beforeunload', warn))
</script>

<template>
  <div class="fb" :class="{ ro: locked }">
    <header class="fb-top">
      <button class="fb-back" @click="back"><Icon name="back" :size="18" /> Forms</button>
      <input v-if="!picking" v-model="form.name" class="fb-name" :class="{ miss: !form.name.trim() }" placeholder="Form name *" maxlength="255" :readonly="locked" />
      <span v-if="viewing" class="ro-pill">Read only · saved copy</span>
      <button v-if="viewing" class="btn ghost sm" @click="backToCurrent">Back to current version</button>
      <span v-if="!picking && !viewing" class="state" :class="{ dirty }">{{ notice || (dirty ? 'Unsaved changes' : saved ? 'All changes saved' : 'New form') }}</span>
      <span style="flex: 1"></span>
      <button v-if="canHistory" class="btn ghost sm" :class="{ hon: historyOpen }" @click="toggleHistory">History</button>
      <button v-else-if="!picking" class="btn ghost sm" disabled title="History starts once the form has been saved for the first time">History</button>
      <a v-if="saved" :href="saved.url" target="_blank" rel="noopener" class="btn ghost sm">Open live form</a>
      <button class="btn ghost sm" :disabled="!saved" :title="saved ? '' : 'Save the form first'" @click="shareOpen = true">Share / embed</button>
      <button v-if="!locked" class="btn ghost sm" :disabled="busy" @click="save(false)">Save</button>
      <button v-if="!locked" class="btn" :disabled="busy || !form.name.trim()" @click="save(true)">{{ busy ? 'Saving…' : 'Save & close' }}</button>
    </header>
    <div v-if="error" class="fb-err" @click="error = ''">{{ error }}</div>
    <div v-else-if="needsReachable" class="fb-warn">Add an <b>Email</b> or <b>Mobile</b> field so you can reach the people who submit — or switch off “Save them to People” and the auto-reply.</div>

    <div v-if="picking" class="fb-pick">
      <h2>What kind of form are you building?</h2>
      <p class="hint">This sets sensible defaults for the fields and for what happens when someone submits. You can change everything afterwards.</p>
      <div class="pk-grid">
        <button v-for="(t, k) in FORM_TYPES" :key="k" class="pk" @click="chooseType(k)">
          <span class="pk-ico"><Icon :name="t.icon" :size="30" /></span>
          <b>{{ t.label }}</b>
          <span>{{ t.desc }}</span>
        </button>
      </div>
    </div>
    <p v-else-if="loading" class="empty" style="margin: 80px 0">Loading…</p>
    <div v-else class="fb-body">
      <!-- left: fields -->
      <aside class="fb-left" data-tip-bounds>
        <div v-if="locked" class="ro-note"><b>Saved copy</b><p>{{ noteText }}</p><p>This is exactly how the form was when it was saved. You can look around and click a field to see its settings, but nothing can be changed.</p></div>
        <p class="hint">Click a field to add it, or drag it into the form. Set a field to <b>½</b> width to put two side by side.</p>
        <template v-for="g in GROUPS" :key="g">
          <div class="grp">{{ g }}<InfoTip :text="GROUP_INFO[g]" /></div>
          <div class="tiles">
            <div v-for="(def, type) in FIELD_TYPES" v-show="def.group === g" :key="type" class="pal" :class="{ off: taken(type) }"
              :draggable="!taken(type)" :title="taken(type) ? 'Already in the form' : ''" @dragstart="dragNew($event, type)" @click="add(type)">
              <Icon :name="def.icon" :size="22" /><span>{{ def.label }}</span>
            </div>
          </div>
          <template v-if="g === 'People fields'">
          <div class="grp">Custom people fields<InfoTip :text="GROUP_INFO['Custom people fields']" /></div>
          <div v-if="customFields.length" class="tiles">
            <div v-for="cf in customFields" :key="cf.key" class="pal" :class="{ off: usedKey(cf.key) }" :draggable="!usedKey(cf.key)"
              :title="usedKey(cf.key) ? 'Already in the form' : 'Saved on the person as ' + tagFor(cf.key)" @dragstart="dragCustom($event, cf.key)" @click="addCustom(cf)">
              <Icon :name="FIELD_TYPES[cf.type].icon" :size="22" /><span>{{ cf.label }}</span>
            </div>
          </div>
          <p v-else class="hint">None yet. Any extra field you add below and save on the person becomes a custom field.</p>
          <router-link class="manage" to="/custom-fields">Manage custom fields</router-link>
          </template>
        </template>
      </aside>

      <!-- centre: live preview -->
      <main class="fb-canvas" :style="{ background: s.pageBg }" @click.self="select(null)">
        <div class="card" @click="select(null)" :style="{ background: s.cardBg, color: s.text, fontFamily: FONTS[s.font], maxWidth: s.width + 'px', borderRadius: s.radius + 'px', '--accent': s.accent, '--r': s.radius + 'px' }" @click.self="select(null)">
          <h2 v-if="form.title" class="p-title" @click="tab = 'general'; selectedId = null">{{ form.title }}</h2>
          <p v-if="form.description" class="p-desc" @click="tab = 'general'; selectedId = null">{{ form.description }}</p>

          <div v-if="!form.fields.length && !locked" class="dz-empty" :class="{ hov: hover === 0 }" @dragover.prevent="hover = 0" @dragleave="hover = -1" @drop.prevent="drop($event, 0)">Drag a field here</div>

          <div class="rows">
            <div v-for="(f, i) in form.fields" :key="f.id" class="fld" :class="['w-' + widthOf(f), { sel: selectedId === f.id, hov: hover === i }]" :draggable="!locked"
              @dragstart="dragMove($event, i)" @dragover.prevent="hover = i" @dragleave="hover = -1" @drop.prevent="drop($event, i)" @click.stop="select(f.id)">
              <div class="fld-in">
                <h3 v-if="f.type === 'heading'" class="p-h">{{ f.label }}</h3>
                <p v-else-if="f.type === 'paragraph'" class="p-p">{{ f.label }}</p>
                <label v-else-if="f.type === 'captcha'" class="p-l">{{ f.label }}<b class="req"> *</b><InfoTip v-if="longHelp(f)" :text="f.help" /><span class="p-q">7 + 4 = ?</span><input type="text" placeholder="Your answer" disabled /></label>
                <label v-else-if="f.type === 'checkbox' || f.type === 'subscribe'" class="p-opt"><input type="checkbox" :checked="!!f.default" disabled /> <span>{{ f.label }}<b v-if="f.required" class="req"> *</b></span><InfoTip v-if="longHelp(f)" :text="f.help" /></label>
                <fieldset v-else-if="f.type === 'radio'" class="p-fs">
                  <legend>{{ f.label }}<b v-if="f.required" class="req"> *</b><InfoTip v-if="longHelp(f)" :text="f.help" /></legend>
                  <label v-for="(o, oi) in f.options.filter((x) => x && x.trim())" :key="oi" class="p-opt"><input type="radio" :checked="o === f.default" disabled /> {{ o }}</label>
                </fieldset>
                <label v-else class="p-l">{{ f.label }}<b v-if="f.required" class="req"> *</b><InfoTip v-if="longHelp(f)" :text="f.help" />
                  <select v-if="f.type === 'dropdown'" disabled><option>{{ f.default || f.placeholder || 'Select…' }}</option></select>
                  <textarea v-else-if="f.type === 'textarea'" rows="3" :placeholder="f.placeholder" :value="f.default" disabled></textarea>
                  <input v-else :type="f.type === 'date' ? 'date' : 'text'" :placeholder="f.placeholder" :value="f.default" disabled />
                </label>
                <small v-if="f.help && !isBlock(f) && f.help.length <= HELP_LONG" class="p-help">{{ f.help }}</small>
              </div>
              <div v-if="selectedId === f.id && !locked" class="tools" @click.stop>
                <span class="grip" title="Drag to move"><Icon name="drag" :size="16" /></span>
                <button v-if="!isBlock(f)" :title="widthOf(f) === 'half' ? 'Make full width' : 'Make half width (share a row)'" class="half" :class="{ on: widthOf(f) === 'half' }" @click="toggleHalf(f)">½</button>
                <button title="Move up" @click="move(i, -1)"><Icon name="up" :size="16" /></button>
                <button title="Move down" @click="move(i, 1)"><Icon name="down" :size="16" /></button>
                <button v-if="!FIELD_TYPES[f.type].unique" title="Duplicate" @click="duplicate(i)"><Icon name="copy" :size="16" /></button>
                <button title="Remove" @click="remove(i)"><Icon name="trash" :size="16" /></button>
              </div>
            </div>
            <div v-if="form.fields.length && !locked" class="dz end" :class="{ hov: hover === form.fields.length }" @dragover.prevent="hover = form.fields.length" @dragleave="hover = -1" @drop.prevent="drop($event, form.fields.length)"></div>
          </div>

          <button class="p-btn" :style="{ background: s.accent, color: s.btnText, borderRadius: s.radius + 'px' }" @click="tab = 'general'; selectedId = null">{{ form.button_text || 'Submit' }}</button>
        </div>
      </main>

      <!-- right: settings -->
      <aside class="fb-right" data-tip-bounds>
        <div v-if="historyOpen" class="hist-wrap">
          <HistoryTimeline type="form" :id="id" :active-id="viewing ? viewing.history_id : null" :names="HISTORY_NAMES" :refresh="histTick" @pick="viewCopy" @current="backToCurrent" @close="toggleHistory" />
        </div>
        <template v-else>
        <div class="tabs">
          <button :class="{ on: tab === 'general' }" @click="tab = 'general'">General</button>
          <button :class="{ on: tab === 'design' }" @click="tab = 'design'">Design</button>
          <button :class="{ on: tab === 'settings' }" @click="tab = 'settings'">Settings</button>
        </div>

        <fieldset :disabled="locked" class="fs">
        <div class="panel">
          <div class="scope" :class="{ on: !!current }">
            <template v-if="current"><span><b>{{ typeLabel(current) }}</b> field<InfoTip v-if="scopeNote" :text="scopeNote" /></span><button class="x" title="Back to the whole form" @click="select(null)">&times;</button></template>
            <template v-else><span><b>Whole form</b> &middot; click a field to edit just that field<InfoTip v-if="scopeNote" :text="scopeNote" /></span></template>
          </div>

          <!-- GENERAL -->
          <template v-if="tab === 'general'">
            <template v-if="current">
              <div class="field"><label>{{ current.type === 'paragraph' ? 'Text' : current.type === 'heading' ? 'Heading' : 'Label' }}</label>
                <textarea v-if="current.type === 'paragraph'" v-model="current.label" rows="4" style="font-family: inherit"></textarea>
                <input v-else v-model="current.label" maxlength="300" />
              </div>
              <template v-if="!isBlock(current)">
                <div v-if="['first_name', 'last_name', 'email', 'phone', 'text', 'textarea', 'number', 'dropdown'].includes(current.type)" class="field">
                  <label>{{ current.type === 'dropdown' ? 'First option shown (placeholder)' : 'Placeholder' }}</label><input v-model="current.placeholder" maxlength="120" />
                </div>
                <div v-if="FIELD_TYPES[current.type].options" class="field">
                  <label>Options <small>(one per line)</small></label>
                  <textarea :value="optionsText(current)" :readonly="linked(current)" rows="5" style="font-family: inherit" @input="setOptions(current, $event.target.value)"></textarea>
                </div>

                <div v-if="current.type !== 'captcha'" class="field">
                  <label>Default value <small>(filled in when the form opens)</small></label>
                  <label v-if="current.type === 'checkbox' || current.type === 'subscribe'" class="check"><input type="checkbox" :checked="!!current.default" @change="current.default = $event.target.checked ? 'yes' : ''" /> Ticked by default</label>
                  <select v-else-if="current.type === 'dropdown' || current.type === 'radio'" v-model="current.default">
                    <option value="">None</option>
                    <option v-for="o in (current.options || []).filter((x) => x && x.trim())" :key="o" :value="o">{{ o }}</option>
                  </select>
                  <textarea v-else-if="current.type === 'textarea'" v-model="current.default" rows="3" maxlength="500" style="font-family: inherit"></textarea>
                  <input v-else v-model="current.default" :type="current.type === 'date' ? 'date' : current.type === 'number' ? 'number' : 'text'" maxlength="500" />
                </div>

                <div class="field"><label>Help text <small>(optional)</small><InfoTip v-if="(current.help || '').length > HELP_LONG" text="Long help text is shown as an i icon, with the text on hover." /></label><input v-model="current.help" maxlength="200" />
                </div>
                <label v-if="current.type !== 'captcha'" class="check"><input v-model="current.required" type="checkbox" /> {{ current.type === 'checkbox' || current.type === 'subscribe' ? 'Must be ticked to submit' : 'Required' }}</label>
                <div v-else class="static-line">Always required<InfoTip text="A captcha is always required: the visitor has to answer the sum to send the form." /></div>
              </template>
            </template>

            <template v-else>
              <div class="field"><label>Heading</label><input v-model="form.title" maxlength="255" /></div>
              <div class="field"><label>Description</label><textarea v-model="form.description" rows="2" maxlength="1000" style="font-family: inherit"></textarea></div>
              <div class="field"><label>Button text</label><input v-model="form.button_text" maxlength="60" /></div>
              <div class="field"><label>Thank-you message</label><input v-model="form.success_message" maxlength="255" /></div>
            </template>
          </template>

          <!-- DESIGN -->
          <template v-else-if="tab === 'design'">
            <template v-if="current">
              <template v-if="!isBlock(current)">
                <div class="field">
                  <label>Width in the row<InfoTip text="Fields flow left to right and wrap. Put two ½ fields (or ⅓ + ⅔) next to each other to share a row." /></label>
                  <div class="seg">
                    <button v-for="[value, text] in WIDTHS" :key="value" type="button" :class="{ on: widthOf(current) === value }" :title="value.replace('_', ' ')" @click="current.width = value">{{ text }}</button>
                  </div>
                </div>
              </template>
            </template>
            <template v-else>
              <div v-for="[k, label] in [['cardBg', 'Form background'], ['pageBg', 'Page background'], ['text', 'Text colour'], ['accent', 'Accent & button colour'], ['btnText', 'Button text colour']]" :key="k" class="field">
                <label>{{ label }}<InfoTip v-if="k === 'pageBg'" text="The page background shows on the hosted link; when embedded on your site the form sits on your own page." /></label>
                <div class="row"><input v-model="form.style[k]" type="color" class="clr" /><input v-model="form.style[k]" maxlength="7" /></div>
              </div>
              <div class="field"><label>Corner radius <span class="val">{{ s.radius }}px</span></label><input v-model.number="form.style.radius" type="range" min="0" max="30" /></div>
              <div class="field"><label>Form width <span class="val">{{ s.width }}px</span></label><input v-model.number="form.style.width" type="range" min="320" max="720" step="10" /></div>
              <div class="field"><label>Font</label><select v-model="form.style.font"><option value="sans">Sans-serif</option><option value="serif">Serif</option><option value="mono">Monospace</option></select></div>
            </template>
          </template>

          <!-- SETTINGS -->
          <template v-else>
            <template v-if="current">
              <div class="grp" style="margin-top: 0">Validation<InfoTip v-if="validationText" :text="validationText" /></div>
              <template v-if="hasRules(current.type)">
                <div v-if="ruleKinds(current.type).length" class="two">
                  <div class="field"><label>Min length</label><input :value="current.rules.min_length" type="number" min="0" max="2000" placeholder="none" @input="setRule('min_length', $event.target.value)" /></div>
                  <div class="field"><label>Max length</label><input :value="current.rules.max_length" type="number" min="0" max="2000" placeholder="none" @input="setRule('max_length', $event.target.value)" /></div>
                </div>
                <div v-if="ruleKinds(current.type).number" class="two">
                  <div class="field"><label>Smallest number</label><input :value="current.rules.min" type="number" placeholder="none" @input="setRule('min', $event.target.value)" /></div>
                  <div class="field"><label>Largest number</label><input :value="current.rules.max" type="number" placeholder="none" @input="setRule('max', $event.target.value)" /></div>
                </div>
                <div v-if="ruleKinds(current.type).date" class="two">
                  <div class="field"><label>Earliest date</label><input :value="current.rules.min" type="date" @input="setRule('min', $event.target.value)" /></div>
                  <div class="field"><label>Latest date</label><input :value="current.rules.max" type="date" @input="setRule('max', $event.target.value)" /></div>
                </div>
                <div v-if="ruleKinds(current.type).pattern" class="field">
                  <label>Format <small>(optional)</small></label>
                  <select :value="formatValue" @change="chooseFormat($event.target.value)">
                    <option value="">No format check</option>
                    <option v-for="p in patternsFor(current.type)" :key="p.id" :value="p.id">{{ p.label }}</option>
                    <option value="custom">Custom pattern…</option>
                  </select>
                  <small v-if="formatInfo">Accepts, for example, <code>{{ formatInfo.example }}</code>.</small>
                </div>
                <div v-if="ruleKinds(current.type).pattern && formatValue === 'custom'" class="field">
                  <label>Custom pattern<InfoTip text="A regular expression, without slashes. ^[0-9]{4}$ accepts exactly four digits." /></label>
                  <input :value="current.rules.pattern" maxlength="200" placeholder="e.g. ^[0-9]{4}$" @input="setRule('pattern', $event.target.value)" />
                </div>
                <div v-if="ruleKinds(current.type).domains" class="field">
                  <label>Only accept these email domains <small>(optional)</small></label>
                  <input :value="Array.isArray(current.rules.allowed_domains) ? current.rules.allowed_domains.join(', ') : current.rules.allowed_domains" placeholder="acme.com, acme.org" @input="setRule('allowed_domains', $event.target.value)" />
                </div>
                <div class="field"><label>Error message <small>(optional, replaces the standard ones)</small></label><input :value="current.rules.message" maxlength="200" placeholder="Please check this answer" @input="setRule('message', $event.target.value)" /></div>
              </template>
              <p v-else-if="isBlock(current)" class="hint">A heading or paragraph has nothing to check.</p>
              <p v-for="p in currentProblems" :key="p" class="warn-inline">{{ p }}</p>

              <div v-if="!isBlock(current)" class="tryit">
                <label>Try a value</label>
                <input v-model="tryValue" :placeholder="current.type === 'date' ? 'YYYY-MM-DD' : 'Type a sample answer'" />
                <p v-if="tryResult" class="try" :class="tryResult.ok ? 'ok' : 'bad'">{{ tryResult.ok ? '✓' : '✗' }} {{ tryResult.message }}</p>
              </div>

              <div class="grp">Saving<InfoTip v-if="savingText" :text="savingText" /></div>
              <div v-if="FIELD_TYPES[current.type].custom && form.save_person" class="dest">
                <label class="check"><input v-model="current.to_person" type="checkbox" /> Also save this answer on the person<InfoTip :text="toPersonText" /></label>
              </div>
              <p v-if="isBlock(current)" class="hint">A heading or paragraph has no advanced settings.</p>
            </template>

            <template v-else>
              <div class="grp" style="margin-top: 0">Form type<InfoTip :text="FORM_TYPES[form.type].desc" /></div>
              <div class="field">
                <select :value="form.type" @change="changeType($event.target.value)">
                  <option v-for="(t, k) in FORM_TYPES" :key="k" :value="k">{{ t.label }}</option>
                </select>
              </div>

              <div class="grp">After sending</div>
              <div v-if="form.save_person" class="field">
                <label>Message for people who are already in People <small>(optional)</small><InfoTip text="Shown instead of the thank-you when the email is already in People. Leave it empty to show everyone the same thank-you, which never reveals who is in your people. Their details are never overwritten either way." /></label>
                <input v-model="form.existing_message" maxlength="500" placeholder="You are already on our list. Thanks, we have updated your details." />
              </div>
              <div class="field"><label>Redirect to a page <small>(optional)</small></label><input v-model="form.redirect_url" type="url" placeholder="https://yoursite.com/thanks" /></div>

              <div class="grp">When someone submits</div>
              <label class="check"><input v-model="form.save_person" type="checkbox" /> Save them to People<InfoTip :text="submitText" /></label>
              <template v-if="form.save_person">
                <div class="field" style="margin-top: 12px"><label>Add to lists</label><MultiSelect v-model="form.list_ids" :options="listOptions" placeholder="No list" empty-text="No lists yet — create one under Lists." /></div>
                <div class="field"><label>Add tags</label><MultiSelect v-model="form.tag_ids" :options="tagOptions" placeholder="No tag" empty-text="No tags yet — create one under Tags." /></div>
              </template>

              <label class="check" style="margin-top: 14px"><input v-model="form.notify_on" type="checkbox" /> Email me every submission</label>
              <div v-if="form.notify_on" class="field sub">
                <label>Send to <small>(up to 5 addresses, separated by commas)</small></label>
                <input v-model="form.notify_text" placeholder="you@yourcompany.com, team@yourcompany.com" />
              </div>

              <label class="check" style="margin-top: 14px"><input v-model="form.autoreply" type="checkbox" /> Send the person an auto-reply</label>
              <div v-if="form.autoreply" class="sub">
                <p v-if="!has('email')" class="warn-inline">Add an <b>Email</b> field so we know where to send it.</p>
                <div class="field"><label>Subject</label><input v-model="form.autoreply_subject" maxlength="255" /></div>
                <div class="field"><label>Message</label><textarea v-model="form.autoreply_body" rows="6" maxlength="5000" style="font-family: inherit"></textarea>
                  <small>You can use {{ tagFor('first_name') }} and {{ tagFor('company_name') }}.</small></div>
                <div class="field"><label>Attachments <small>(optional)</small></label>
                  <AttachmentsField v-model="form.autoreply_attachments" />
                </div>
              </div>

              <label class="check" style="margin-top: 18px"><input v-model="form.active" type="checkbox" /> Form is active (accepting submissions)</label>
            </template>
          </template>
        </div>
        </fieldset>
        </template>
      </aside>
    </div>

    <div v-if="shareOpen && saved" class="dlg-back" @mousedown.self="shareOpen = false">
      <div class="dlg">
        <div class="r-head"><b>Share “{{ form.name }}”</b><button class="x" @click="shareOpen = false">&times;</button></div>
        <div class="field"><label>Hosted page link</label>
          <div class="row"><input :value="saved.url" readonly /><button class="btn sm" @click="copy(saved.url, 'url')">{{ copied === 'url' ? 'Copied ✓' : 'Copy' }}</button></div>
        </div>
        <div class="field"><label>Embed on your website</label>
          <textarea :value="iframeCode" readonly rows="3"></textarea>
          <button class="btn sm" style="margin-top: 6px" @click="copy(iframeCode, 'code')">{{ copied === 'code' ? 'Copied ✓' : 'Copy code' }}</button>
        </div>
        <small>New sign-ups are added to your lists and tags automatically and can start an automation workflow.</small>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fb { position: fixed; inset: 0; z-index: 60; background: #eceff1; display: flex; flex-direction: column; }
.fb-top { background: #fff; display: flex; align-items: center; gap: 10px; padding: 10px 16px; box-shadow: 0 1px 4px rgba(0, 0, 0, .15); z-index: 2; }
.fb-back { display: flex; align-items: center; gap: 6px; border: 0; background: none; color: #3f51b5; font-weight: 500; cursor: pointer; font-size: 14px; }
.fb-name { max-width: 300px; font-weight: 500; } .fb-name.miss { border-color: #e53935; background: #fff8f8; }
.state { font-size: 13px; color: #2e7d32; white-space: nowrap; } .state.dirty { color: #e65100; }
.fb-err { background: #fdecea; color: #b71c1c; padding: 9px 20px; font-size: 14px; cursor: pointer; }
.fb-warn { background: #fff8e1; color: #6d4c00; padding: 9px 20px; font-size: 14px; }

.fb-top .btn.hon { background: #eef0fb; border-color: #3f51b5; color: #3f51b5; }
.ro-pill { background: #fef3c7; color: #92400e; border-radius: 99px; padding: 2px 12px; font-size: 12.5px; font-weight: 500; white-space: nowrap; }
.ro-note { background: #f5f6fd; border: 1px solid #e0e3f0; border-radius: 8px; padding: 12px 14px; font-size: 13px; color: #4b5563; }
.ro-note p { margin: 6px 0 0; }
.fb.ro .fb-left > :not(.ro-note) { display: none; }
.fs { border: 0; padding: 0; margin: 0; min-width: 0; }
.fb.ro .panel { pointer-events: none; }
.hist-wrap { padding: 16px; }
.fb-body { flex: 1; display: grid; grid-template-columns: 250px 1fr 340px; min-height: 0; }
.fb-left, .fb-right { background: #fff; overflow: auto; }
.fb-left { border-right: 1px solid #e0e0e0; padding: 16px; } .fb-right { border-left: 1px solid #e0e0e0; }
.hint { color: #6b7280; font-size: 13px; margin: 0 0 12px; }
.p-q { display: block; margin: 2px 0 6px; font-size: 18px; font-weight: 700; letter-spacing: .05em; }
.grp { position: relative; font-size: 12px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: #3f51b5; margin: 18px 0 8px; }
.tiles { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.pal { border: 1px solid #e0e0e0; border-radius: 4px; padding: 11px 4px; text-align: center; cursor: grab; display: flex; flex-direction: column; align-items: center; gap: 4px; font-size: 12px; color: #424242; background: #fff; }
.pal:hover { border-color: #3f51b5; color: #3f51b5; box-shadow: 0 2px 6px rgba(63, 81, 181, .2); }
.pal.off { opacity: .4; cursor: not-allowed; box-shadow: none; border-color: #e0e0e0; color: #424242; }

.fb-canvas { overflow: auto; padding: 28px 20px; }
.card { margin: 0 auto; padding: 28px 28px 24px; box-shadow: 0 2px 10px rgba(0, 0, 0, .12); min-height: 160px; }
.p-title { margin: 0 0 6px; font-size: 24px; cursor: pointer; } .p-desc { margin: 0 0 18px; opacity: .75; cursor: pointer; }
.p-h { margin: 14px 0 6px; font-size: 18px; } .p-p { margin: 0 0 12px; line-height: 1.5; white-space: pre-line; }
.rows { display: flex; flex-wrap: wrap; column-gap: 14px; }
.fld { box-sizing: border-box; width: 100%; min-width: 0; }
.fld.w-two_thirds { width: calc(66.667% - 4.667px); } .fld.w-half { width: calc(50% - 7px); } .fld.w-third { width: calc(33.333% - 9.333px); } .fld.w-quarter { width: calc(25% - 10.5px); }
.fld.hov { box-shadow: -5px 0 0 #3f51b5; border-radius: 0; }
.fld { position: relative; cursor: pointer; outline: 2px solid transparent; outline-offset: 3px; border-radius: 3px; margin: 2px 0; }
.fld:hover { outline-color: #9fa8da; } .fld.sel { outline-color: #3f51b5; }
.fld-in { pointer-events: none; position: relative; }
.fld-in :deep(.tip) { pointer-events: auto; }
.p-l { display: block; font-size: 14px; margin-bottom: 14px; }
.p-l input, .p-l select, .p-l textarea { display: block; width: 100%; box-sizing: border-box; margin-top: 5px; padding: 11px; border: 1px solid #c9ccd3; border-radius: var(--r); font: inherit; background: #fff; color: #333; }
.p-fs { border: 0; padding: 0; margin: 0 0 14px; } .p-fs legend { font-size: 14px; padding: 0; margin-bottom: 6px; }
.p-opt { display: flex; gap: 8px; align-items: flex-start; font-size: 14px; margin-bottom: 8px; } .p-opt input { width: auto; margin-top: 3px; accent-color: var(--accent); }
.p-help { display: block; margin: -8px 0 12px; font-size: 12px; opacity: .65; } .req { color: #d32f2f; }
.p-btn { width: 100%; margin-top: 8px; padding: 13px; border: 0; font: inherit; font-size: 16px; font-weight: 700; cursor: pointer; }
.tools { position: absolute; top: -12px; right: -6px; background: #3f51b5; display: flex; z-index: 3; border-radius: 4px; box-shadow: 0 2px 6px rgba(0, 0, 0, .25); }
.tools .half { font-size: 15px; font-weight: 700; min-width: 26px; justify-content: center; } .tools .half.on { background: #1a237e; }
.seg { display: flex; border: 1px solid #d1d5db; border-radius: 4px; overflow: hidden; margin-bottom: 6px; }
.seg button { flex: 1; border: 0; background: #fff; padding: 8px 4px; cursor: pointer; font: inherit; font-size: 15px; color: #555; border-right: 1px solid #e5e7eb; } .seg button:last-child { border-right: 0; } .seg button.on { background: #3f51b5; color: #fff; }
.tools button, .grip { background: none; border: 0; color: #fff; padding: 5px 6px; cursor: pointer; display: flex; } .grip { cursor: grab; } .tools button:hover { background: #303f9f; }
.dz { position: relative; z-index: 2; } .dz.end { flex-basis: 100%; height: 18px; }
.dz.hov { background: #3f51b5; height: 8px; border-radius: 3px; }
.dz-empty { border: 2px dashed #9fa8da; margin: 14px 0; padding: 40px 10px; text-align: center; color: #7986cb; border-radius: 6px; } .dz-empty.hov { background: #e8eaf6; }

.two { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.tryit { margin-top: 6px; padding: 10px; border-radius: 8px; background: #f7f8fc; border: 1px dashed #d7dbea; }
.tryit label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 4px; }
.try { margin: 6px 0 0; font-size: 13px; } .try.ok { color: #2e7d32; } .try.bad { color: #c62828; }
.static-line { font-size: 14px; color: #4b5563; margin: 4px 0 10px; }
.scope { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin: -2px 0 14px; padding: 8px 10px; border-radius: 8px; background: #f5f6fd; color: #4b5563; font-size: 13px; }
.scope.on { background: #eef0fb; color: #3f51b5; }
.scope .x { border: 0; background: none; font-size: 20px; line-height: 1; cursor: pointer; color: inherit; }
.tabs { display: flex; border-bottom: 1px solid #e0e0e0; position: sticky; top: 0; background: #fff; z-index: 1; }
.tabs button { flex: 1; border: 0; background: none; padding: 14px 6px; cursor: pointer; font: inherit; font-weight: 500; color: #6b7280; border-bottom: 2px solid transparent; }
.tabs .on { color: #3f51b5; border-bottom-color: #e91e63; }
.panel { padding: 16px; }
.r-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; font-size: 16px; }
.x { background: none; border: 0; font-size: 22px; cursor: pointer; color: #757575; }
.val { color: #3f51b5; font-weight: 400; } .clr { width: 44px !important; padding: 2px !important; height: 36px; flex: none; } input[type=range] { padding: 0; }
code { background: #f1f2f6; padding: 1px 6px; border-radius: 3px; font-size: 12px; }

.fb-pick { flex: 1; overflow: auto; padding: 48px 6vw; text-align: center; }
.fb-pick h2 { margin: 0 0 6px; font-size: 24px; font-weight: 500; }
.pk-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 300px)); gap: 20px; justify-content: center; margin-top: 28px; }
.pk { background: #fff; border: 2px solid transparent; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, .12); padding: 26px 22px; cursor: pointer; text-align: center; font: inherit; display: flex; flex-direction: column; align-items: center; gap: 10px; }
.pk:hover { border-color: #3f51b5; } .pk b { font-size: 17px; font-weight: 500; } .pk span:last-child { font-size: 13px; color: #6b7280; line-height: 1.5; }
.pk-ico { width: 56px; height: 56px; border-radius: 50%; background: #e8eaf6; color: #3f51b5; display: grid; place-items: center; }
.dest { margin-top: 14px; padding: 10px 12px; background: #f5f6fb; border-radius: 6px; } .dest small { display: block; margin-top: 6px; color: #6b7280; line-height: 1.5; }
.manage { display: inline-block; margin: 4px 0 12px; font-size: 13px; color: #3f51b5; }
.linked { margin: 0 0 12px; padding: 8px 10px; font-size: 13px; line-height: 1.5; background: #eef0fb; border-radius: 6px; color: #3b4280; }
.sub { margin-left: 22px; } .sub-note { display: block; margin: 2px 0 0 22px; color: #6b7280; font-size: 12px; line-height: 1.4; }
.warn-inline { background: #fff8e1; color: #6d4c00; padding: 8px 10px; border-radius: 4px; font-size: 13px; margin: 0 0 10px; }
.dlg-back { position: fixed; inset: 0; background: rgba(17, 24, 39, .55); display: flex; align-items: center; justify-content: center; z-index: 80; padding: 20px; }
.dlg { background: #fff; border-radius: 6px; padding: 22px; width: 100%; max-width: 520px; box-shadow: 0 8px 30px rgba(0, 0, 0, .3); }

@media (max-width: 1000px) { .fb-body { grid-template-columns: 1fr; grid-template-rows: auto 1fr auto; overflow: auto; } .fb-left, .fb-right { max-height: 260px; } }
</style>
