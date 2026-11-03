<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import api, { errorMessage } from '../api'
import RecentSends from '../components/RecentSends.vue'

// Every SMS service can be connected at once (each keeps its own login); exactly one is in use for sending.
const c = ref(null) // active, connections, providers (the login fields of each service)
const editing = ref('')
const forms = reactive({})
const msg = ref('')
const error = ref('')
const testTo = ref('')
const testMsg = ref('')
const testErr = ref('')
const testHint = ref('')
const tested = ref('')
const busy = ref(false)
const tick = ref(0) // reloads the recent test messages after each test

const LOG = { key: 'log', name: 'Log only', sub: 'Nothing is sent', color: '#78909c', letter: 'L' }
const LOOK = {
  twilio: '#f22f46', vonage: '#7b2cbf', plivo: '#2db742', telnyx: '#00c08b', clicksend: '#e8441b', messagebird: '#2481d7', messagemedia: '#00a3e0', sinch: '#ffbe3c',
}
const HOWTO = {
  twilio: ['Open the Twilio Console and copy the Account SID and Auth token from the dashboard.', 'Buy or verify a phone number (Phone Numbers → Manage), or register an alphanumeric sender ID where your country allows it.', 'Paste the SID, the token and the sender (a number in international format, e.g. +15551234567).'],
  vonage: ['In the Vonage API Dashboard, copy the API key and API secret.', 'Use an alphanumeric sender ID where your country allows it, or one of your Vonage numbers.', 'Paste them here.'],
  plivo: ['In the Plivo console, copy the Auth ID and Auth token from the Overview page.', 'Buy a Plivo number (Phone Numbers → Buy) or use an approved sender ID.', 'Paste them here; write the sender number in international format.'],
  telnyx: ['In the Telnyx portal create an API V2 key (Auth → API keys).', 'Buy a number and attach it to a Messaging Profile.', 'Paste the key and use that number as the sender.'],
  clicksend: ['Open the ClickSend dashboard → Developers → API credentials.', 'Use your username and the API key shown there.', 'The sender is an alphanumeric ID or one of your dedicated numbers.'],
  messagebird: ['In the MessageBird dashboard open Developers → API access and create a live access key.', 'The originator is a sender name (up to 11 letters) or a number you own.', 'Paste the key here.'],
  messagemedia: ['Sign in to the MessageMedia hub and open Settings → API credentials (or Developers → API keys).', 'Create a Basic-auth API key: you get an API key and an API secret.', 'Paste both here. The sender is optional: leave it blank to send from the shared MessageMedia number, or enter a number or sender ID that MessageMedia has approved for your account.'],
  sinch: ['In the Sinch portal open SMS → APIs and note the Service plan ID and API token.', 'The region (us, eu, au, br or ca) is shown on the same page; leave it blank for us.', 'The sender is a number from your plan.'],
}
const TILES = computed(() => [LOG, ...Object.entries(c.value.providers).map(([key, p]) => ({ key, name: p.name, sub: '', color: LOOK[key] || '#6b7280', fields: p.fields }))])
const tile = (k) => TILES.value.find((x) => x.key === k)

async function load() {
  c.value = (await api.get('/config/sms')).data
  if (!editing.value) open(c.value.active)
}
onMounted(load)

const conn = (k) => c.value.connections[k] || { connected: false }
const isActive = (k) => c.value.active === k
const statusOf = (t) => (t.key === 'log' ? 'Always available' : conn(t.key).connected ? (conn(t.key).from || 'Connected') : 'Not connected')

function open(k) {
  editing.value = k
  const t = tile(k)
  if (t?.fields && !forms[k]) {
    const cn = conn(k)
    forms[k] = Object.fromEntries(t.fields.map((f) => [f.key, f.secret ? '' : cn[f.key] || '']))
  }
}
const current = computed(() => (editing.value ? tile(editing.value) : null))
const form = computed(() => forms[editing.value])
const connected = computed(() => conn(editing.value).connected)

async function useService(k) {
  msg.value = error.value = ''
  busy.value = true
  try { c.value = (await api.put('/config/sms', { active: k })).data; msg.value = k === 'log' ? 'Log only is on: nothing is sent.' : `${tile(k).name} is now used for sending.` } catch (e) { error.value = errorMessage(e) } finally { busy.value = false }
}
async function saveConnection(activate = false) {
  msg.value = error.value = ''
  busy.value = true
  try {
    c.value = (await api.put(`/config/sms/connections/${editing.value}`, { ...forms[editing.value], activate })).data
    for (const f of current.value.fields) if (f.secret) forms[editing.value][f.key] = ''
    msg.value = activate ? `${current.value.name} connected and in use.` : `${current.value.name} connected.`
  } catch (e) { error.value = errorMessage(e) } finally { busy.value = false }
}
async function disconnect(k) {
  if (!confirm(`Disconnect ${tile(k).name}? Its saved login is removed.${isActive(k) ? ' Texts go back to Log only (nothing is sent).' : ''}`)) return
  msg.value = error.value = ''
  try {
    c.value = (await api.delete(`/config/sms/connections/${k}`)).data
    delete forms[k]
    open(k)
    msg.value = `${tile(k).name} disconnected.`
  } catch (e) { error.value = errorMessage(e) }
}

const dirtyForm = computed(() => {
  const f = form.value, cn = conn(editing.value)
  return !!f && (!cn.connected || current.value.fields.some((x) => (x.secret ? f[x.key] !== '' : (f[x.key] || '') !== (cn[x.key] || ''))))
})
const detailsOk = computed(() => !!form.value && current.value.fields.every((x) => x.key === 'region' || (x.secret ? conn(editing.value)[x.key + '_set'] || !!form.value[x.key] : !!form.value[x.key])))
const steps = computed(() => [
  { done: detailsOk.value, text: 'Enter your account details' },
  { done: connected.value && !dirtyForm.value, text: 'Save the connection' },
  { done: isActive(editing.value), text: 'Use it for sending' },
  { done: tested.value === editing.value && isActive(editing.value), text: 'Send a test SMS' },
])
const nextStep = computed(() => steps.value.findIndex((x) => !x.done))

async function test() {
  testMsg.value = testErr.value = testHint.value = ''
  busy.value = true
  try { const r = (await api.post('/config/sms/test', { to: testTo.value })).data; testMsg.value = (r.logged ? '⚠ ' : '') + r.message; tested.value = r.logged ? '' : c.value.active } catch (e) { testErr.value = errorMessage(e); testHint.value = e.response?.data?.hint || '' } finally { busy.value = false; tick.value++ }
}
watch(() => c.value?.active, () => { testMsg.value = testErr.value = '' })
</script>

<template>
  <div class="head"><h1>SMS Configuration</h1></div>
  <p v-if="!c" class="empty">Loading…</p>
  <template v-else>
    <div v-if="msg" class="alert ok">{{ msg }}</div>
    <div v-if="error" class="alert err">{{ error }}</div>

    <div class="card">
      <h2>SMS services</h2>
      <p class="hint">Connect as many services as you like: each keeps its own login. Only one is <b>in use</b> for sending at a time; switch whenever you want. With Log only, texts are only written to the log.</p>

      <div class="blocks">
        <div v-for="t in TILES" :key="t.key" class="block pick" :class="{ on: editing === t.key, using: isActive(t.key) }" role="button" tabindex="0" @click="open(t.key)" @keydown.enter="open(t.key)">
          <span class="ico" :style="{ background: t.color }">{{ t.letter || t.name[0] }}</span>
          <div class="txt">
            <b>{{ t.name }}</b>
            <small :class="{ ok: t.key !== 'log' && conn(t.key).connected }">{{ t.key !== 'log' && conn(t.key).connected ? '✓ ' : '' }}{{ t.key === 'log' ? t.sub : statusOf(t) }}</small>
          </div>
          <span v-if="isActive(t.key)" class="badge sent">In use</span>
        </div>
      </div>

      <div v-if="current" class="panel">
        <div class="phead">
          <span class="ico" :style="{ background: current.color }">{{ current.letter || current.name[0] }}</span>
          <h3>{{ current.name }}</h3>
          <span v-if="isActive(current.key)" class="badge sent">In use</span>
          <span v-else-if="connected || current.key === 'log'" class="badge">Connected, not in use</span>
        </div>

        <template v-if="current.key === 'log'">
          <p class="hint">Nothing is sent. Each text is only written to the log and listed in Message logs as <b>logged only</b>, so you can try campaigns and automations safely. Nothing leaves the server and nothing is charged.</p>
          <button v-if="!isActive('log')" type="button" class="btn" :disabled="busy" @click="useService('log')">Use Log only</button>
        </template>

        <template v-else-if="form">
          <div class="guide">
            <h4>Setup guide</h4>
            <ol class="checks">
              <li v-for="(x, i) in steps" :key="i" :class="{ done: x.done, next: i === nextStep }">
                <span class="dot">{{ x.done ? '✓' : i + 1 }}</span>{{ x.text }}<em v-if="i === nextStep"> ← do this next</em>
              </li>
            </ol>
            <template v-if="nextStep !== -1 && nextStep <= 1">
              <b class="how">How to get your {{ current.name }} details:</b>
              <ol class="howto"><li v-for="(t, i) in HOWTO[current.key]" :key="i">{{ t }}</li></ol>
            </template>
            <p v-if="nextStep === 3" class="hint" style="margin: 8px 0 0">In use. Send a test SMS below to make sure it really connects.</p>
            <p v-if="nextStep === -1" class="done-msg">✓ All set. Your texts go out through {{ current.name }}.</p>
          </div>

          <div class="grid g2">
            <div v-for="f in current.fields" :key="f.key" class="field">
              <label>{{ f.label }} {{ f.secret && conn(current.key)[f.key + '_set'] ? '(saved)' : '' }}</label>
              <input v-model="form[f.key]" :type="f.secret ? 'password' : 'text'" :autocomplete="f.secret ? 'new-password' : 'off'" :placeholder="f.secret && conn(current.key)[f.key + '_set'] ? '•••••• leave blank to keep' : f.placeholder || ''" />
            </div>
          </div>

          <div class="row">
            <button type="button" class="btn" :disabled="busy || !detailsOk" @click="saveConnection(false)">{{ connected ? 'Save changes' : 'Connect' }}</button>
            <button v-if="!isActive(current.key)" type="button" class="btn ghost" :disabled="busy || !detailsOk" @click="saveConnection(true)">{{ connected ? 'Save and use for sending' : 'Connect and use for sending' }}</button>
            <button v-if="connected && !dirtyForm && !isActive(current.key)" type="button" class="btn ghost" :disabled="busy" @click="useService(current.key)">Use for sending</button>
            <button v-if="connected" type="button" class="btn ghost" @click="disconnect(current.key)">Disconnect</button>
          </div>
        </template>
      </div>
    </div>

    <div class="card">
      <h2>Send a test SMS</h2>
      <p class="hint">Sends through the service that is <b>in use</b> ({{ tile(c.active).name }}){{ c.active === 'log' ? '. Log only never sends: the text is only written to the log' : '' }}.</p>
      <div v-if="testMsg" class="alert ok">{{ testMsg }}</div>
      <div v-if="testErr" class="alert err">{{ testErr }}</div>
      <div v-if="testHint" class="alert hintbox"><b>What to do:</b> {{ testHint }}</div>
      <form class="row" @submit.prevent="test"><input v-model="testTo" required placeholder="+61400000000" style="max-width: 240px" /><button class="btn ghost" :disabled="busy">Send test</button></form>
      <RecentSends channel="sms" :tick="tick" />
    </div>
  </template>
</template>

<style scoped>
.hint { color: #6b7280; font-size: 14px; margin: 0 0 12px; }
.hintbox { background: #fffbea; border: 1px solid #f3e2a3; color: #5f4b00; }
.blocks { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 12px; margin: 14px 0; }
.block { display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: #fff; border: 1px solid #d9dcef; border-radius: 8px; transition: border-color .15s, box-shadow .15s; }
.block.pick { cursor: pointer; }
.block.pick:hover { border-color: #3f51b5; box-shadow: 0 2px 8px rgba(63, 81, 181, .15); }
.block.on { border: 2px solid #3f51b5; padding: 11px 13px; background: #f5f6ff; }
.block.using:not(.on) { border-color: #2e7d32; }
.ico { width: 38px; height: 38px; border-radius: 8px; color: #fff; display: grid; place-items: center; font-weight: 700; font-size: 17px; flex: none; }
.txt { display: flex; flex-direction: column; min-width: 0; flex: 1; }
.txt b { font-size: 14px; }
.txt small { color: #6b7280; font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.txt small.ok { color: #2e7d32; }
.panel { border: 1px solid #d9dcef; border-radius: 8px; padding: 16px 18px; background: #fcfcff; }
.phead { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.phead h3 { margin: 0; font-size: 17px; }
.guide { background: #fffbea; border: 1px solid #f3e2a3; border-radius: 8px; padding: 14px 16px; margin: 0 0 16px; }
.guide h4 { margin: 0 0 8px; font-size: 15px; }
.checks { list-style: none; margin: 0 0 8px; padding: 0; }
.checks li { display: flex; align-items: center; gap: 10px; padding: 3px 0; font-size: 14px; color: #6b7280; }
.checks li.done { color: #2e7d32; }
.checks li.next { color: #111; font-weight: 600; }
.checks em { font-weight: 400; font-style: normal; color: #b26a00; font-size: 13px; }
.dot { width: 22px; height: 22px; border-radius: 50%; border: 1px solid #c9ccd3; display: grid; place-items: center; font-size: 12px; flex: none; background: #fff; }
.done .dot { background: #2e7d32; border-color: #2e7d32; color: #fff; }
.next .dot { border-color: #3f51b5; color: #3f51b5; font-weight: 700; }
.how { display: block; margin: 10px 0 4px; font-size: 14px; }
.howto { margin: 0 0 6px; padding-left: 20px; font-size: 14px; line-height: 1.55; }
.done-msg { margin: 8px 0 0; color: #2e7d32; font-weight: 600; font-size: 14px; }
</style>
