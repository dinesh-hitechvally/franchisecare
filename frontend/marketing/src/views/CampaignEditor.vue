<script setup>
import DateTimeInput from '../components/DateTimeInput.vue'
import { asEntered, fmtStored } from '../format'
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { errorMessage } from '../api'
import MessageEditor from '../components/MessageEditor.vue'
import EmailDesigner from '../components/EmailDesigner.vue'
import AttachmentsField from '../components/AttachmentsField.vue'
import { setQuery } from '../urlState'
import Modal from '../components/Modal.vue'
import AudiencePreview from '../components/AudiencePreview.vue'

const route = useRoute()
const router = useRouter()
const id = ref(route.params.id || null)
const lists = ref([])
const templates = ref([])
const campaign = ref(null)
const error = ref('')
const msg = ref('')
const busy = ref(false)
const testTo = ref('')
const when = ref('now')

const form = reactive({ name: '', type: route.query.type === 'sms' ? 'sms' : 'email', template_id: '', contact_list_id: '', segment_id: '', tag_id: '', subject: '', body: '', design: null, attachments: [], scheduled_at: '' })
const designer = ref(false)
const startVersion = ref(null) // history_id to show as soon as the designer opens (coming from a history list)
function closeDesigner() { designer.value = false; startVersion.value = null; setQuery({ history: null }) }
const rawHtml = ref(false)
const designInitial = () => ({ subject: form.subject, design: form.design, html: form.design ? '' : form.body, attachments: form.attachments })
function designed(d) {
  Object.assign(form, { subject: d.subject, body: d.body, design: d.design, attachments: d.attachments || [] })
  closeDesigner()
}
function useRaw() {
  rawHtml.value = true
  form.design = null // hand-edited HTML no longer matches the visual design
}
const locked = computed(() => campaign.value && !['draft', 'scheduled'].includes(campaign.value.status))
const segments = ref([])
const tags = ref([])
const allContacts = ref(0)
const subCounts = ref({ all: 0, subscribed: 0, unsubscribed: 0 })
const subscription = ref('') // narrows the audience shown below; unsubscribed contacts are never sent to either way
const audience = computed({
  get: () => (form.segment_id ? 'segment:' + form.segment_id : form.tag_id ? 'tag:' + form.tag_id : form.contact_list_id ? 'list:' + form.contact_list_id : ''),
  set: (v) => {
    const [kind, id] = v.split(':')
    form.contact_list_id = kind === 'list' ? Number(id) : ''
    form.segment_id = kind === 'segment' ? Number(id) : ''
    form.tag_id = kind === 'tag' ? Number(id) : ''
  },
})
const audienceName = computed(() => (form.segment_id ? 'segment "' + segments.value.find((x) => x.id === form.segment_id)?.name + '"' : form.tag_id ? 'tag "' + tags.value.find((x) => x.id === form.tag_id)?.name + '"' : form.contact_list_id ? 'list "' + lists.value.find((l) => l.id === form.contact_list_id)?.name + '"' : 'ALL contacts'))
const typeTemplates = computed(() => templates.value.filter((t) => t.type === form.type))

// <input type=datetime-local> works in local time without a timezone
// A schedule is kept exactly as typed: no time zone conversion in or out. The server sends it when its UTC clock reaches that time.
const toLocal = asEntered
const utcNow = ref(fmtStored(new Date().toISOString()))
const clock = setInterval(() => (utcNow.value = fmtStored(new Date().toISOString())), 20000)
onBeforeUnmount(() => clearInterval(clock))

// A template is only a starting point: importing one copies its content and attachments into this campaign, and every edit after that belongs to the campaign alone.
const importing = ref(false)
function importTemplate(t) {
  if (form.body && !confirm('Replace the content and attachments of this campaign with the template? Your edits to the campaign will be lost.')) return
  form.template_id = t.id
  importing.value = false
  msg.value = `Imported "${t.name}". Edit it freely: changes are saved with this campaign only.`
  form.subject = t.subject || ''; form.body = t.body; form.design = t.design ? JSON.parse(JSON.stringify(t.design)) : null; form.attachments = [...(t.attachments || [])]; rawHtml.value = false
}
watch(() => form.type, () => { form.template_id = '' })

function payload() {
  return { ...form, template_id: form.template_id || null, contact_list_id: form.contact_list_id || null, segment_id: form.segment_id || null, tag_id: form.tag_id || null, scheduled_at: when.value === 'later' && form.scheduled_at ? form.scheduled_at.replace('T', ' ') + ':00' : null }
}

async function save() {
  error.value = ''
  const { data } = id.value ? await api.put(`/campaigns/${id.value}`, payload()) : await api.post('/campaigns', payload())
  id.value = data.id
  campaign.value = { ...campaign.value, ...data }
  return data
}

async function onSave() {
  busy.value = true
  try { await save(); msg.value = 'Saved.'; if (!route.params.id) router.replace(`/campaigns/${id.value}`) }
  catch (e) { error.value = errorMessage(e) } finally { busy.value = false }
}

async function onSend() {
  busy.value = true
  error.value = msg.value = ''
  try {
    await save()
    // what sending would really do, and anything worth fixing first
    const c = (await api.get(`/campaigns/${id.value}/check`)).data
    if (!c.reach) { error.value = c.audience ? `Nobody in ${audienceName.value} can receive this: ${c.unsubscribed} unsubscribed, the rest have no ${form.type === 'sms' ? 'phone number' : 'email address'}.` : `${audienceName.value} has no contacts yet.`; return }
    const lines = [`${when.value === 'later' ? 'Schedule' : 'Send'} this ${form.type} campaign to ${c.reach} ${c.reach === 1 ? 'person' : 'people'} (${audienceName.value})?`]
    if (c.skipped) lines.push(`${c.skipped} more will be skipped: unsubscribed, or no ${form.type === 'sms' ? 'phone number' : 'email address'}.`)
    if (c.warnings.length) lines.push('', 'Worth a look first:', ...c.warnings.map((w) => '• ' + w), '', 'Send anyway?')
    if (!confirm(lines.join(String.fromCharCode(10)))) return
    await api.post(`/campaigns/${id.value}/send`)
    router.push('/campaigns')
  } catch (e) { error.value = errorMessage(e) } finally { busy.value = false }
}

async function onStop() {
  if (!confirm('Stop sending this campaign? What has already gone out stays sent; everything still waiting is skipped.')) return
  try { campaign.value = { ...campaign.value, ...(await api.post(`/campaigns/${id.value}/cancel`)).data }; msg.value = 'Sending stopped.' } catch (e) { error.value = errorMessage(e) }
}

async function onDuplicate() {
  try { router.push(`/campaigns/${(await api.post(`/campaigns/${id.value}/duplicate`)).data.id}`) } catch (e) { error.value = errorMessage(e) }
}

async function onTest() {
  busy.value = true
  error.value = msg.value = ''
  try { await save(); await api.post(`/campaigns/${id.value}/test`, { to: testTo.value }); msg.value = `Test sent to ${testTo.value}.` }
  catch (e) { error.value = errorMessage(e) } finally { busy.value = false }
}

onMounted(async () => {
  lists.value = (await api.get('/lists')).data
  segments.value = (await api.get('/segments')).data
  tags.value = (await api.get('/tags')).data
  allContacts.value = (await api.get('/contacts', { params: { per_page: 1 } })).data.total
  if (route.query.segment_id) form.segment_id = Number(route.query.segment_id) // coming from Segments → Send campaign
  templates.value = (await api.get('/templates')).data
  if (id.value) {
    const { data } = await api.get(`/campaigns/${id.value}`)
    campaign.value = data
    Object.assign(form, { name: data.name, type: data.type, template_id: data.template_id || '', contact_list_id: data.contact_list_id || '', segment_id: data.segment_id || '', tag_id: data.tag_id || '', subject: data.subject || '', body: data.body, design: data.design || null, attachments: data.attachments || [], scheduled_at: toLocal(data.scheduled_at) })
    if (data.scheduled_at) when.value = 'later'
    // coming from a history list: the email designer opens on that saved version, with History open
    if (route.query.history && !locked.value && form.type === 'email') { startVersion.value = Number(route.query.history); designer.value = true }
  }
})
</script>

<template>
  <div class="head">
    <h1>{{ id ? 'Campaign' : 'New campaign' }}</h1>
    <span v-if="campaign" class="badge" :class="campaign.status">{{ campaign.status === 'cancelled' ? 'stopped' : campaign.status }}</span>
    <span style="flex: 1"></span>
    <button v-if="campaign?.status === 'sending'" type="button" class="btn danger" @click="onStop">Stop sending</button>
    <button v-if="id" type="button" class="btn ghost" @click="onDuplicate">Clone</button>
  </div>
  <div v-if="error" class="alert err">{{ error }}</div>
  <div v-if="msg" class="alert ok">{{ msg }}</div>

  <div v-if="locked" class="card">
    <h2>Results</h2>
    <div class="grid g4">
      <div class="stat"><div class="n">{{ campaign.sent_count }}</div><div class="l">Sent</div></div>
      <div class="stat"><div class="n">{{ campaign.failed_count }}</div><div class="l">Failed</div></div>
      <div class="stat"><div class="n">{{ campaign.skipped_count }}</div><div class="l">Skipped (unsubscribed / missing)</div></div>
      <div class="stat" v-if="campaign.type === 'email'"><div class="n">{{ campaign.opened_count }}</div><div class="l">Opened</div></div>
    </div>
    <p style="margin-bottom: 0"><router-link :to="`/message-logs?campaign_id=${campaign.id}`">View message logs →</router-link></p>
  </div>

  <fieldset :disabled="locked" style="border: 0; padding: 0; margin: 0; min-width: 0">
    <div class="card">
      <div class="grid g4">
        <div class="field"><label>Campaign name</label><input v-model="form.name" required /></div>
        <div class="field">
          <label>Channel</label>
          <select v-model="form.type" :disabled="!!id"><option value="email">Email</option><option value="sms">SMS</option></select>
        </div>
        <div class="field">
          <label>Audience</label>
          <select v-model="audience">
            <option value="">All contacts ({{ allContacts }})</option>
            <optgroup v-if="lists.length" label="Lists"><option v-for="l in lists" :key="'l' + l.id" :value="'list:' + l.id">{{ l.name }} ({{ l.contacts_count }})</option></optgroup>
            <optgroup v-if="tags.length" label="Tags"><option v-for="t in tags" :key="'t' + t.id" :value="'tag:' + t.id">{{ t.name }} ({{ t.contacts_count }})</option></optgroup>
            <optgroup v-if="segments.length" label="Segments"><option v-for="g in segments" :key="'s' + g.id" :value="'segment:' + g.id">{{ g.name }} ({{ g.contacts_count }})</option></optgroup>
          </select>
        </div>
        <div class="field">
          <label>Subscription</label>
          <select v-model="subscription"><option value="">All Status ({{ subCounts.all }})</option><option value="subscribed">Subscribed ({{ subCounts.subscribed }})</option><option value="unsubscribed">Unsubscribed ({{ subCounts.unsubscribed }})</option></select>
        </div>
      </div>
      <AudiencePreview @counts="(c) => (subCounts = c)" :list-id="form.contact_list_id" :segment-id="form.segment_id" :tag-id="form.tag_id" :status="subscription" :type="form.type" />
    </div>

    <div v-if="form.type === 'email' && !rawHtml" class="card">
      <div class="row" style="justify-content: space-between; margin-bottom: 12px">
        <div><b>Subject:</b> {{ form.subject || '—' }}</div>
        <div class="row">
          <button type="button" class="btn ghost sm" @click="importing = true">Import from template</button>
          <button type="button" class="btn ghost sm" @click="useRaw">Edit raw HTML</button>
          <button type="button" class="btn sm" @click="designer = true">{{ form.body ? 'Edit design' : 'Design email' }}</button>
        </div>
      </div>
      <iframe v-if="form.body" class="preview" sandbox="" :srcdoc="form.body" style="height: 520px"></iframe>
      <p v-else class="empty">No design yet. Import a template above, or click “Design email” to build one.</p>
    </div>
    <div v-else class="card">
      <div class="row" style="justify-content: flex-end; margin-bottom: 10px"><button type="button" class="btn ghost sm" @click="importing = true">Import from template</button></div>
      <MessageEditor :type="form.type" v-model:subject="form.subject" v-model:body="form.body" />
    </div>

    <div v-if="form.type === 'email'" class="card">
      <h2>Attachments</h2>
      <p class="hint" style="margin-top: 0">Files attached to every email in this campaign, including the test email.</p>
      <AttachmentsField v-model="form.attachments" :disabled="locked" />
    </div>

    <div v-if="!locked" class="card">
      <h2>Delivery</h2>
      <div class="row" style="margin-bottom: 12px">
        <label class="check"><input type="radio" value="now" v-model="when" />Send now</label>
        <label class="check"><input type="radio" value="later" v-model="when" />Schedule</label>
        <DateTimeInput v-if="when === 'later'" v-model="form.scheduled_at" />
        <small v-if="when === 'later'" style="display: block; margin-top: 6px; color: #6b7280">Saved exactly as typed and sent when the server clock (UTC) reaches it. UTC now: <b>{{ utcNow }}</b></small>
      </div>
      <div class="row">
        <input v-model="testTo" :placeholder="form.type === 'email' ? 'test@example.com' : '+61400000000'" style="max-width: 240px" />
        <button class="btn ghost" :disabled="busy || !testTo || !form.body" @click="onTest">Send test</button>
        <span style="flex: 1"></span>
        <button class="btn ghost" :disabled="busy || !form.name || !form.body" @click="onSave">Save draft</button>
        <button class="btn" :disabled="busy || !form.name || !form.body || (when === 'later' && !form.scheduled_at)" @click="onSend">{{ when === 'later' ? 'Schedule' : 'Send campaign' }}</button>
      </div>
    </div>
  </fieldset>
  <Modal v-if="importing" title="Import from template" @close="importing = false">
    <p v-if="!typeTemplates.length" class="empty">There are no {{ form.type }} templates yet. Create one in Content → Templates.</p>
    <ul v-else class="tpl-list">
      <li v-for="t in typeTemplates" :key="t.id">
        <div><b>{{ t.name }}</b><small v-if="t.subject"> · {{ t.subject }}</small><small v-if="(t.attachments || []).length"> · {{ t.attachments.length }} attachment{{ t.attachments.length === 1 ? '' : 's' }}</small></div>
        <button type="button" class="btn sm" @click="importTemplate(t)">Import</button>
      </li>
    </ul>
    <p class="hint" style="margin-bottom: 0">The template is copied into this campaign together with its attachments. The template itself is never changed.</p>
  </Modal>
  <EmailDesigner v-if="designer" :initial="designInitial()" :history-id="id" history-type="campaign" :start-version="startVersion" @save="designed" @close="closeDesigner" @version="(v) => setQuery({ history: v })" />
</template>

<style scoped>
.tpl-list { list-style: none; margin: 0 0 12px; padding: 0; max-height: 360px; overflow: auto; }
.tpl-list li { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 9px 0; border-bottom: 1px solid #eceef7; }
.tpl-list small { color: #6b7280; }
</style>
