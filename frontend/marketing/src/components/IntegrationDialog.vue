<script setup>
// The window of one connection. mode "new": connect a platform (only credentials, or straight to the platform's approval page when one-click is set up).
// mode "edit": the settings of a connected integration (credentials, what to sync, list, tags, schedule…). Used on Integrations and on Connected.
import { ref, reactive, computed, onMounted } from 'vue'
import api, { errorMessage, responseMessage } from '../api'
import Modal from './Modal.vue'
import InfoTip from './InfoTip.vue'

const props = defineProps({
  provider: { type: Object, required: true },
  mode: { type: String, default: 'new' }, // 'new' | 'edit'
  connection: { type: Object, default: null }, // the integration being edited
  lists: { type: Array, default: () => [] },
  prefill: { type: Object, default: null }, // { name, url } when connecting a disconnected one again
})
const emit = defineEmits(['close', 'done'])

const busy = ref(false)
const dlgError = ref('')
const quick = ref(false) // one-click with nothing to ask: no window, straight to the platform's approval page
const EVERY = [[0, 'Only when I press Sync now'], [15, 'Every 15 minutes'], [60, 'Every hour'], [360, 'Every 6 hours'], [1440, 'Every day']]

const p = props.provider
const c = props.connection
const form = reactive({
  direction: c?.settings?.direction || 'import', export_list_id: c?.settings?.export_list_id || '', name: c?.name || props.prefill?.name || '',
  values: Object.fromEntries(p.fields.map((f) => [f.key, f.key === 'url' && c ? c.store_url : (!f.secret && c?.settings?.config?.[f.key]) || (f.key === 'url' && props.prefill?.url) || ''])),
  list_id: c?.settings?.list_id || '', tags: (c?.settings?.tags || []).join(', '),
  subscribe_mode: c?.settings?.subscribe_mode || 'consent', confirm: c?.settings?.subscribe_mode === 'all', // an existing "everyone" was already confirmed
  sync_every: c?.settings?.sync_every ?? 60, run_automations: !!c?.settings?.run_automations, import_now: true,
  roles: c?.settings?.roles || p.settings?.find((s) => s.key === 'roles')?.default || [],
})
const isNew = computed(() => props.mode === 'new')
const roleSetting = computed(() => p.settings?.find((s) => s.key === 'roles'))
const auto = computed(() => p.auto_connect || null)
// "Connect with …": the person approves on the platform's own page, so there is nothing to paste
const oneClick = computed(() => isNew.value && !!auto.value?.available)
const needed = (f) => (auto.value?.asks || []).includes(f.key) || (f.export_required && form.direction !== 'import') // what one-click still has to ask
const shownFields = computed(() => (oneClick.value ? p.fields.filter(needed) : p.fields))

function payload() {
  const body = {
    list_id: form.list_id || null, tags: form.tags.split(',').map((t) => t.trim()).filter(Boolean), subscribe_mode: form.subscribe_mode,
    confirm_permission: form.subscribe_mode === 'all' ? form.confirm : false, sync_every: Number(form.sync_every), run_automations: form.run_automations,
  }
  if (roleSetting.value) body.roles = form.roles
  if (p.supports_export) Object.assign(body, { direction: form.direction, export_list_id: form.direction === 'import' ? null : form.export_list_id || null })
  if (form.name.trim()) body.name = form.name.trim()
  for (const f of p.fields) {
    if (form.values[f.key]) body[f.key] = form.values[f.key]
    else if (!f.secret && f.required === false) body[f.key] = '' // an optional option can be cleared
  }
  return body
}

// Sends the person to the platform to approve access; the platform sends them back to the Integrations page.
async function startAuto(reconnect = false) {
  busy.value = true
  dlgError.value = ''
  try {
    const asks = auto.value.asks || []
    const body = { provider: p.key }
    if (reconnect) {
      if (asks.includes('url')) body.url = c.store_url
    } else {
      Object.assign(body, payload(), { import_now: form.import_now })
      for (const f of p.fields) if (!needed(f)) delete body[f.key]
    }
    window.location.href = (await api.post('/integrations/oauth/start', body)).data.url
  } catch (e) { dlgError.value = errorMessage(e); busy.value = false; quick.value = false }
}

const submit = () => (oneClick.value ? startAuto() : save())

async function save() {
  busy.value = true
  dlgError.value = ''
  try {
    if (isNew.value) {
      const r = await api.post('/integrations', { provider: p.key, import_now: form.import_now, ...payload() })
      emit('done', responseMessage(r) || 'Connected.')
    } else {
      await api.put(`/integrations/${c.id}`, payload())
      emit('done', 'Saved.')
    }
  } catch (e) { dlgError.value = errorMessage(e) } finally { busy.value = false }
}

// Connecting needs no settings: they are changed afterwards, on the Connected page (⋮ → Edit Configuration).
// - one-click is set up (OAuth app in the server config): go straight to the platform, asking only for what it cannot know (a store address)
// - not set up: ask only for the credentials to paste
onMounted(() => {
  quick.value = isNew.value && !!auto.value?.available && !(auto.value.asks || []).length
  if (quick.value) startAuto()
})
</script>

<template>
  <Modal v-if="!quick" :title="isNew ? 'Connect ' + provider.label : 'Settings · ' + connection.name" wide @close="emit('close')">
    <form @submit.prevent="submit">
      <div v-if="dlgError" class="alert err">{{ dlgError }}</div>

      <div v-if="oneClick" class="auto">
        <b>Connect with {{ provider.label }}</b>
        <p>You will go to {{ provider.label }} to approve read-only access to your customers, then come back here. Nothing to copy and paste.</p>
      </div>
      <div v-else-if="!isNew && auto?.available" class="auto soft">
        <button type="button" class="btn ghost sm" :disabled="busy" @click="startAuto(true)">Reconnect with {{ provider.label }}</button>
        <small>Use this if the connection expired or access was removed. Nothing else changes.</small>
      </div>

      <details v-if="!oneClick" class="how" :open="isNew">
        <summary>How to get these details from {{ provider.label }}</summary>
        <ol><li v-for="(s, i) in provider.steps" :key="i">{{ s }}</li></ol>
      </details>

      <h3 class="blk">{{ isNew ? 'Your ' + provider.label : 'Connection' }}</h3>
      <p v-if="!isNew" class="hint">Leave a credential empty to keep the one that is stored.</p>
      <div v-if="!isNew" class="field">
        <label>Connection name <small>(optional)</small></label>
        <input v-model="form.name" maxlength="120" placeholder="e.g. Australia store" autocomplete="off" />
        <small>Helps you tell apart several {{ provider.label }} connections. Leave empty to use the name {{ provider.label }} reports.</small>
      </div>
      <div v-for="f in shownFields" :key="f.key" class="field">
        <label>{{ f.label }}</label>
        <input v-model="form.values[f.key]" :type="f.type === 'password' ? 'password' : 'text'" :required="(isNew && f.required !== false) || (f.export_required && form.direction !== 'import')" :placeholder="!isNew && f.secret && connection.secret_hints?.[f.key] ? connection.secret_hints[f.key] + ' (stored)' : f.placeholder" autocomplete="off" />
        <small>{{ f.help }}</small>
      </div>

      <div v-if="provider.messaging" class="field"><small>{{ provider.consent_note }} Nothing is imported from this account: it appears as a channel when you make a campaign (Campaigns → New → Channel).</small></div>
      <template v-if="!isNew && !provider.messaging">
        <h3 class="blk">What to sync</h3>
        <div v-if="provider.supports_export" class="field">
          <label>Which way should people move?<InfoTip :text="'Import brings ' + provider.label + ' contacts into People. ' + provider.export_note + ' Both does the two, import first.'" /></label>
          <label class="check"><input v-model="form.direction" type="radio" value="import" /> <span><b>Import only</b>: {{ provider.label }} → People</span></label>
          <label class="check"><input v-model="form.direction" type="radio" value="export" /> <span><b>Send only</b>: People → {{ provider.label }}</span></label>
          <label class="check"><input v-model="form.direction" type="radio" value="both" /> <span><b>Both ways</b>: import, then send</span></label>
        </div>
        <div v-if="provider.supports_export && form.direction !== 'import'" class="field">
          <label>Send which people?</label>
          <select v-model="form.export_list_id"><option value="">Everyone with an email address</option><option v-for="l in lists" :key="l.id" :value="l.id">Only people in “{{ l.name }}”</option></select>
          <small>After the first sync, only people who changed since the last one are sent. <template v-if="provider.has_audiences">Choose the {{ provider.label }} {{ provider.audience_noun }} to send to above.</template></small>
        </div>
        <div v-if="roleSetting" class="field">
          <label>{{ roleSetting.label }}</label>
          <label v-for="(label, key) in roleSetting.options" :key="key" class="check"><input v-model="form.roles" type="checkbox" :value="key" /> {{ label }}</label>
        </div>
        <div v-if="form.direction !== 'export'" class="grid g2">
          <div class="field">
            <label>Add them to this list</label>
            <select v-model="form.list_id"><option value="">No list</option><option v-for="l in lists" :key="l.id" :value="l.id">{{ l.name }}</option></select>
          </div>
          <div class="field">
            <label>Also give them these tags <small>(comma separated)</small></label>
            <input v-model="form.tags" placeholder="Customer, 2026 import" />
          </div>
        </div>

        <div v-if="form.direction !== 'export'" class="field">
          <label>Who should be subscribed to marketing?<InfoTip :text="provider.consent_note" /></label>
          <label class="check"><input v-model="form.subscribe_mode" type="radio" value="consent" /> Only people who agreed to marketing <b>(recommended)</b></label>
          <label class="check"><input v-model="form.subscribe_mode" type="radio" value="all" /> Everyone, except people who unsubscribed</label>
          <label v-if="form.subscribe_mode === 'all'" class="check confirm"><input v-model="form.confirm" type="checkbox" required /> I confirm I have permission to send marketing to these customers.</label>
        </div>

        <div class="grid g2">
          <div class="field">
            <label>Keep them up to date<InfoTip text="After the first import, only customers that changed since the last sync are read. People who are already here are only completed: nothing is overwritten and nobody is re-subscribed." /></label>
            <select v-model.number="form.sync_every"><option v-for="[m, label] in EVERY" :key="m" :value="m">{{ label }}</option></select>
          </div>
          <div class="field">
            <label>Workflows<InfoTip text="Off by default, so importing a whole store does not start a welcome email for every customer at once. Turn it on to let imported people start workflows (for example “Welcome new people”)." /></label>
            <label class="check"><input v-model="form.run_automations" type="checkbox" /> Start workflows for imported people</label>
          </div>
        </div>
      </template>

      <div class="row" style="margin-top: 18px">
        <button class="btn" :disabled="busy">{{ busy ? (oneClick ? 'Opening ' + provider.label + '…' : isNew ? 'Connecting… testing your details' : 'Saving…') : oneClick ? 'Connect with ' + provider.label : isNew ? 'Connect' : 'Save' }}</button>
        <button type="button" class="btn ghost" @click="emit('close')">Cancel</button>
      </div>
    </form>
  </Modal>
</template>

<style scoped>
.check span { line-height: 1.3; }
.auto { background: #f1f8f1; border: 1px solid #cfe5cf; border-radius: 8px; padding: 12px 14px; margin-bottom: 10px; }
.auto p { margin: 4px 0 6px; color: #4b5563; font-size: 14px; }
.auto.soft { background: transparent; border-style: dashed; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.auto.soft small { color: #6b7280; }
.how { background: #f5f6fd; border: 1px solid #e0e3f0; border-radius: 8px; padding: 10px 14px; margin-bottom: 8px; }
.how summary { cursor: pointer; font-weight: 600; color: #3f51b5; }
.how ol { margin: 10px 0 2px; padding-left: 20px; font-size: 14px; color: #4b5563; }
.how li { margin: 4px 0; }
.blk { font-size: 13px; text-transform: uppercase; letter-spacing: .06em; color: #3f51b5; margin: 20px 0 8px; }
.hint { color: #6b7280; font-size: 13.5px; margin: 0 0 10px; }
.confirm { margin-top: 6px; color: #8a5a00; }
.field .check { display: flex; align-items: center; gap: 8px; margin: 4px 0; }
.field .check input[type='radio'] { width: auto; flex: none; margin: 0; }
</style>
