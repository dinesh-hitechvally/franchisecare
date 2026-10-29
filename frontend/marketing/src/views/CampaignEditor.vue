<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { errorMessage } from '../api'
import MessageEditor from '../components/MessageEditor.vue'
import EmailDesigner from '../components/EmailDesigner.vue'

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

const form = reactive({ name: '', type: 'email', template_id: '', contact_list_id: '', subject: '', body: '', design: null, scheduled_at: '' })
const designer = ref(false)
const rawHtml = ref(false)
const designInitial = () => ({ subject: form.subject, design: form.design, html: form.design ? '' : form.body })
function designed(d) {
  Object.assign(form, { subject: d.subject, body: d.body, design: d.design })
  designer.value = false
}
function useRaw() {
  rawHtml.value = true
  form.design = null // hand-edited HTML no longer matches the visual design
}
const locked = computed(() => campaign.value && !['draft', 'scheduled'].includes(campaign.value.status))
const typeTemplates = computed(() => templates.value.filter((t) => t.type === form.type))

// <input type=datetime-local> works in local time without a timezone
const toLocal = (iso) => (iso ? new Date(new Date(iso) - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16) : '')

function applyTemplate() {
  const t = templates.value.find((x) => x.id === form.template_id)
  if (t) { form.subject = t.subject || ''; form.body = t.body; form.design = t.design || null; rawHtml.value = false }
}
watch(() => form.type, () => { form.template_id = '' })

function payload() {
  return { ...form, template_id: form.template_id || null, contact_list_id: form.contact_list_id || null, scheduled_at: when.value === 'later' && form.scheduled_at ? new Date(form.scheduled_at).toISOString() : null }
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
  const who = form.contact_list_id ? lists.value.find((l) => l.id === form.contact_list_id)?.name : 'ALL contacts'
  if (!confirm(`${when.value === 'later' ? 'Schedule' : 'Send'} this ${form.type} campaign to ${who}?`)) return
  busy.value = true
  try {
    await save()
    await api.post(`/campaigns/${id.value}/send`)
    router.push('/campaigns')
  } catch (e) { error.value = errorMessage(e) } finally { busy.value = false }
}

async function onTest() {
  busy.value = true
  error.value = msg.value = ''
  try { await save(); await api.post(`/campaigns/${id.value}/test`, { to: testTo.value }); msg.value = `Test sent to ${testTo.value}.` }
  catch (e) { error.value = errorMessage(e) } finally { busy.value = false }
}

onMounted(async () => {
  lists.value = (await api.get('/lists')).data
  templates.value = (await api.get('/templates')).data
  if (id.value) {
    const { data } = await api.get(`/campaigns/${id.value}`)
    campaign.value = data
    Object.assign(form, { name: data.name, type: data.type, template_id: data.template_id || '', contact_list_id: data.contact_list_id || '', subject: data.subject || '', body: data.body, design: data.design || null, scheduled_at: toLocal(data.scheduled_at) })
    if (data.scheduled_at) when.value = 'later'
  }
})
</script>

<template>
  <div class="head">
    <h1>{{ id ? 'Campaign' : 'New campaign' }}</h1>
    <span v-if="campaign" class="badge" :class="campaign.status">{{ campaign.status }}</span>
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
    <p style="margin-bottom: 0"><router-link :to="`/logs?campaign_id=${campaign.id}`">View message logs →</router-link></p>
  </div>

  <fieldset :disabled="locked" style="border: 0; padding: 0; margin: 0; min-width: 0">
    <div class="card">
      <div class="grid g3">
        <div class="field"><label>Campaign name</label><input v-model="form.name" required /></div>
        <div class="field">
          <label>Channel</label>
          <select v-model="form.type" :disabled="!!id"><option value="email">Email</option><option value="sms">SMS</option></select>
        </div>
        <div class="field">
          <label>Audience</label>
          <select v-model="form.contact_list_id"><option value="">All contacts</option><option v-for="l in lists" :key="l.id" :value="l.id">{{ l.name }} ({{ l.contacts_count }})</option></select>
        </div>
      </div>
      <div class="field" style="max-width: 360px">
        <label>Start from template</label>
        <select v-model="form.template_id" @change="applyTemplate"><option value="">— none —</option><option v-for="t in typeTemplates" :key="t.id" :value="t.id">{{ t.name }}</option></select>
      </div>
    </div>

    <div v-if="form.type === 'email' && !rawHtml" class="card">
      <div class="row" style="justify-content: space-between; margin-bottom: 12px">
        <div><b>Subject:</b> {{ form.subject || '—' }}</div>
        <div class="row">
          <button type="button" class="btn ghost sm" @click="useRaw">Edit raw HTML</button>
          <button type="button" class="btn" @click="designer = true">{{ form.body ? 'Edit design' : 'Design email' }}</button>
        </div>
      </div>
      <iframe v-if="form.body" class="preview" sandbox="" :srcdoc="form.body" style="height: 520px"></iframe>
      <p v-else class="empty">No design yet. Choose a template above, or click “Design email” to build one.</p>
    </div>
    <div v-else class="card"><MessageEditor :type="form.type" v-model:subject="form.subject" v-model:body="form.body" /></div>

    <div v-if="!locked" class="card">
      <h2>Delivery</h2>
      <div class="row" style="margin-bottom: 12px">
        <label class="check"><input type="radio" value="now" v-model="when" />Send now</label>
        <label class="check"><input type="radio" value="later" v-model="when" />Schedule</label>
        <input v-if="when === 'later'" type="datetime-local" v-model="form.scheduled_at" style="width: auto" />
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
  <EmailDesigner v-if="designer" :initial="designInitial()" @save="designed" @close="designer = false" />
</template>
