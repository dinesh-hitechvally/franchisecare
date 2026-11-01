<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { errorMessage } from '../api'
import RecentSends from '../components/RecentSends.vue'

// Every mail service can be connected at once (each keeps its own login); exactly one is in use for sending.
const route = useRoute()
const router = useRouter()
const c = ref(null) // sender, active, connections, oauth
const editing = ref('') // the service whose card is open below
const forms = reactive({}) // working copy of each SMTP service's login
const msg = ref('')
const error = ref('')
const testTo = ref('')
const testMsg = ref('')
const testErr = ref('')
const testHint = ref('')
const tested = ref('') // the service a test email last succeeded for
const busy = ref(false)
const tick = ref(0) // reloads the recent test messages after each test
const connecting = ref('')
const showServer = ref(false)

// ---- the services
const DEFAULT = { key: 'default', name: 'Default', sub: 'Platform mail server', color: '#455a64', letter: 'D', type: 'default' }
const LOGONLY = { key: 'log', name: 'Log only', sub: 'Nothing is sent', color: '#78909c', letter: 'L', type: 'log' }
const ACCOUNTS = [
  { key: 'google', name: 'Google', sub: 'Sign in with Google', color: '#ea4335', letter: 'G', type: 'oauth' },
  { key: 'microsoft', name: 'Microsoft', sub: 'Sign in with Microsoft', color: '#0078d4', letter: 'M', type: 'oauth' },
]
const SERVICES = [
  { key: 'gmail', name: 'Gmail', sub: 'Google Workspace', color: '#ea4335', host: 'smtp.gmail.com', port: 587, encryption: 'tls', user: 'Your full Gmail address', pass: 'An app password (Google account → Security → 2-Step Verification → App passwords)', mismatch: true },
  { key: 'outlook', name: 'Outlook', sub: 'Microsoft 365', color: '#0078d4', host: 'smtp.office365.com', port: 587, encryption: 'tls', user: 'Your full Microsoft 365 email address', pass: 'Your password, or an app password if you use two-step sign-in. SMTP sending must be enabled for the mailbox.', mismatch: true },
  { key: 'yahoo', name: 'Yahoo Mail', sub: '', color: '#6001d2', host: 'smtp.mail.yahoo.com', port: 465, encryption: 'ssl', user: 'Your full Yahoo address', pass: 'An app password (Yahoo account security → Generate app password)', mismatch: true },
  { key: 'zoho', name: 'Zoho Mail', sub: '', color: '#c8202f', host: 'smtp.zoho.com', port: 465, encryption: 'ssl', user: 'Your full Zoho address', pass: 'Your password, or an application-specific password', mismatch: true },
  { key: 'sendgrid', name: 'SendGrid', sub: 'Twilio', color: '#1a82e2', host: 'smtp.sendgrid.net', port: 587, encryption: 'tls', user: 'Type the word apikey', fixedUser: 'apikey', pass: 'Your SendGrid API key (with Mail Send permission)' },
  { key: 'mailgun', name: 'Mailgun', sub: '', color: '#f06b66', host: 'smtp.mailgun.org', port: 587, encryption: 'tls', user: 'postmaster@your-sending-domain', pass: 'The SMTP password for that domain (Mailgun → Sending → Domain settings → SMTP credentials)' },
  { key: 'ses', name: 'Amazon SES', sub: 'AWS', color: '#ff9900', host: 'email-smtp.us-east-1.amazonaws.com', port: 587, encryption: 'tls', editHost: true, user: 'Your SES SMTP user name', pass: 'Your SES SMTP password. Change the server above if your SES region is not us-east-1.' },
  { key: 'brevo', name: 'Brevo', sub: 'Sendinblue', color: '#0b996e', host: 'smtp-relay.brevo.com', port: 587, encryption: 'tls', user: 'Your Brevo login email', pass: 'Your Brevo SMTP key (SMTP & API → SMTP)' },
  { key: 'postmark', name: 'Postmark', sub: '', color: '#ffde00', dark: true, host: 'smtp.postmarkapp.com', port: 587, encryption: 'tls', user: 'Your Postmark Server API token', pass: 'The same Server API token' },
  { key: 'mandrill', name: 'Mandrill', sub: 'Mailchimp Transactional', color: '#e8603c', host: 'smtp.mandrillapp.com', port: 587, encryption: 'tls', user: 'Any name, e.g. your Mailchimp login', pass: 'A Mandrill API key' },
  { key: 'custom', name: 'Custom server', sub: 'Enter the details yourself', color: '#6b7280', letter: '⚙', host: '', port: 587, encryption: 'tls', user: 'Normally the full mailbox address', pass: 'The mailbox password', editHost: true },
].map((x) => ({ ...x, type: 'smtp' }))
const TILES = [DEFAULT, LOGONLY, ...ACCOUNTS, ...SERVICES]
const tile = (k) => TILES.find((x) => x.key === k)

const HOWTO = {
  gmail: ['Turn on 2-Step Verification for the Google account (myaccount.google.com → Security).', 'Open App passwords and create one named "Marketing".', 'Use your full Gmail address as the username and the 16-character app password, without spaces, as the password.'],
  outlook: ['In the Microsoft 365 admin centre, make sure the mailbox allows "Authenticated SMTP" (Users → Active users → Mail → Manage email apps).', 'Use the mailbox address as the username.', 'If the account uses two-step sign-in, create an app password in your Microsoft account security page and use it as the password.'],
  yahoo: ['Open Yahoo Account security and switch on two-step verification.', 'Choose "Generate app password" and name it "Marketing".', 'Use your full Yahoo address as the username and the app password as the password.'],
  zoho: ['In Zoho Mail, open Settings → Mail Accounts → SMTP and make sure SMTP access is enabled.', 'Use your full Zoho address as the username.', 'With two-factor sign-in, generate an application-specific password and use it instead of your password.'],
  sendgrid: ['In SendGrid, open Settings → API Keys and create a key with "Mail Send" permission.', 'Verify the sender address or domain you will send from (Settings → Sender Authentication).', 'Type apikey as the username and paste the API key as the password.'],
  mailgun: ['In Mailgun, add and verify your sending domain (Sending → Domains).', 'Open the domain → SMTP credentials and create or reset a password.', 'Use the postmaster@your-domain address as the username and that password.'],
  ses: ['In Amazon SES, verify the domain or address you will send from.', 'Open SMTP settings and click "Create SMTP credentials".', 'Use the SMTP user name and password it shows (not your AWS keys). If your region is not us-east-1, change the server above to the one shown on that page.'],
  brevo: ['In Brevo, open SMTP & API and generate an SMTP key.', 'Use your Brevo login email as the username and the SMTP key as the password.', 'Add your sender address under Senders & IP.'],
  postmark: ['In Postmark, create a Server and open its API Tokens tab.', 'Add and confirm your sender signature or domain.', 'Use the Server API token as both the username and the password.'],
  mandrill: ['Mandrill needs a paid Mailchimp Transactional add-on. Create an API key in its Settings.', 'Add and verify your sending domain.', 'Use any name as the username and the API key as the password.'],
  custom: ['Ask your hosting or mail provider for its "outgoing mail (SMTP)" settings: server name, port, security type, user name and password.', 'Port 587 with TLS/STARTTLS is the usual choice; 465 uses SSL.', 'The user name is normally the full mailbox address.'],
}

// ---- loading
async function load() {
  c.value = (await api.get('/config/email')).data
  if (!editing.value) open(c.value.active)
}
onMounted(async () => {
  await load()
  // coming back from Google / Microsoft
  const back = route.query.connected
  if (back) { msg.value = `${back === 'google' ? 'Google' : 'Microsoft'} account connected.`; open(String(back)) }
  if (route.query.oauth_error) error.value = String(route.query.oauth_error)
  if (back || route.query.oauth_error) router.replace({ query: {} })
})

const conn = (k) => c.value.connections[k] || { connected: false }
const isActive = (k) => c.value.active === k
const statusOf = (t) => (t.key === 'default' ? (c.value.default_ready ? 'Sent by this server' : '⚠ Not available here') : t.key === 'log' ? 'Nothing is sent' : conn(t.key).connected ? (conn(t.key).email || conn(t.key).username || 'Connected') : 'Not connected')

function open(k) {
  editing.value = k
  showServer.value = false
  const t = tile(k)
  if (t?.type === 'smtp' && !forms[k]) {
    const cn = conn(k)
    forms[k] = { host: cn.host || t.host, port: cn.port || t.port, encryption: cn.encryption || t.encryption, username: cn.username || t.fixedUser || '', password: '' }
  }
}
const current = computed(() => (editing.value ? tile(editing.value) : null))
const form = computed(() => forms[editing.value])
const connected = computed(() => conn(editing.value).connected)

// ---- sender + which service is in use
const senderBody = (active) => ({ from_name: c.value.from_name, from_email: c.value.from_email, reply_to: c.value.reply_to, address: c.value.address, active })
async function saveSender(active = c.value.active, note = 'Sender saved.') {
  msg.value = error.value = ''
  busy.value = true
  try { c.value = (await api.put('/config/email', senderBody(active))).data; msg.value = note } catch (e) { error.value = errorMessage(e) } finally { busy.value = false }
}
const useService = (k) => saveSender(k, `${tile(k).name} is now used for sending.`)
const viaOauth = computed(() => ['google', 'microsoft'].includes(c.value?.active))

// ---- SMTP connections
async function saveConnection(activate = false) {
  msg.value = error.value = ''
  busy.value = true
  try {
    const f = forms[editing.value]
    c.value = (await api.put(`/config/email/connections/${editing.value}`, { ...f, activate })).data
    f.password = ''
    msg.value = activate ? `${current.value.name} connected and in use.` : `${current.value.name} connected.`
  } catch (e) { error.value = errorMessage(e) } finally { busy.value = false }
}
async function disconnect(k) {
  if (!confirm(`Disconnect ${tile(k).name}? Its saved login is removed.${isActive(k) ? ' Emails go back to the Default server.' : ''}`)) return
  error.value = msg.value = ''
  try {
    c.value = (await api.delete(`/config/email/connections/${k}`)).data
    if (forms[k]) delete forms[k]
    open(k)
    msg.value = `${tile(k).name} disconnected.`
  } catch (e) { error.value = errorMessage(e) }
}
const dirtyForm = computed(() => {
  const f = form.value, cn = conn(editing.value)
  return !!f && (f.password !== '' || !cn.connected || f.host !== cn.host || Number(f.port) !== Number(cn.port) || f.encryption !== cn.encryption || (f.username || '') !== (cn.username || ''))
})

// ---- Google / Microsoft sign-in
async function connect(key) {
  error.value = msg.value = ''
  connecting.value = key
  try { window.location.href = (await api.get(`/oauth/mail/${key}/start`)).data.url } catch (e) { error.value = errorMessage(e); connecting.value = '' }
}

// ---- test email
async function test() {
  testMsg.value = testErr.value = testHint.value = ''
  busy.value = true
  try { const r = (await api.post('/config/email/test', { to: testTo.value })).data; testMsg.value = (r.logged ? '⚠ ' : '') + r.message; tested.value = r.logged ? '' : c.value.active } catch (e) { testErr.value = errorMessage(e); testHint.value = e.response?.data?.hint || '' } finally { busy.value = false; tick.value++ }
}
watch(() => c.value?.active, () => { testMsg.value = testErr.value = testHint.value = '' })

// ---- guidance
const suggestion = ref(null)
let st
watch(() => c.value?.from_email, (e) => {
  clearTimeout(st)
  if (!e || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(e)) { suggestion.value = null; return }
  st = setTimeout(async () => { try { suggestion.value = (await api.get('/config/email/suggest', { params: { email: e } })).data } catch (err) { suggestion.value = null } }, 500)
})
const suggested = computed(() => (suggestion.value?.preset && !conn(suggestion.value.preset).connected ? tile(suggestion.value.preset) : null))

const loginOk = computed(() => !!form.value?.username && (conn(editing.value).password_set || !!form.value?.password))
const steps = computed(() => [
  { done: loginOk.value, text: 'Enter the sign-in details' },
  { done: connected.value && !dirtyForm.value, text: 'Save the connection' },
  { done: isActive(editing.value), text: 'Use it for sending' },
  { done: tested.value === editing.value && isActive(editing.value), text: 'Send a test email' },
])
const nextStep = computed(() => steps.value.findIndex((x) => !x.done))
const mismatch = computed(() => {
  const from = (c.value.from_email || '').toLowerCase()
  const user = (form.value?.username || '').toLowerCase()
  return current.value?.mismatch && isActive(editing.value) && from && user.includes('@') && from !== user
    ? `${current.value.name} only lets you send as the account you sign in with, so set From email to ${form.value.username}.` : ''
})
</script>

<template>
  <div class="head"><h1>Email Configuration</h1></div>
  <p v-if="!c" class="empty">Loading…</p>
  <template v-else>
    <div v-if="msg" class="alert ok">{{ msg }}</div>
    <div v-if="error" class="alert err">{{ error }}</div>

    <form class="card" @submit.prevent="saveSender()">
      <h2>Sender</h2>
      <div class="grid g2">
        <div class="field"><label>From name</label><input v-model="c.from_name" /></div>
        <div class="field"><label>From email</label><input v-model="c.from_email" type="email" placeholder="hello@yourdomain.com" :readonly="viaOauth" /><small>{{ viaOauth ? 'Google and Microsoft only let you send as the connected account.' : 'Must be an address your mail provider allows you to send from.' }}</small></div>
        <div class="field"><label>Reply-to email</label><input v-model="c.reply_to" type="email" /></div>
        <div class="field"><label>Postal address (email footer)</label><input v-model="c.address" /></div>
      </div>
      <button class="btn" :disabled="busy">Save sender</button>
    </form>

    <div class="card">
      <h2>Mail services</h2>
      <p class="hint">Connect as many services as you like: each keeps its own login. Only one is <b>in use</b> for sending at a time; switch whenever you want.</p>

      <p v-if="suggested" class="sug">💡 {{ suggestion.reason }} We suggest <b>{{ suggested.name }}</b>.
        <button type="button" class="btn sm" @click="open(suggested.key)">Set up {{ suggested.name }}</button></p>

      <div class="blocks">
        <div v-for="t in TILES" :key="t.key" class="block pick" :class="{ on: editing === t.key, using: isActive(t.key) }" role="button" tabindex="0" @click="open(t.key)" @keydown.enter="open(t.key)">
          <span class="ico" :style="{ background: t.color, color: t.dark ? '#222' : '#fff' }">{{ t.letter || t.name[0] }}</span>
          <div class="txt">
            <b>{{ t.name }}</b>
            <small :class="{ ok: conn(t.key).connected && t.key !== 'default' }">{{ t.key !== 'default' && conn(t.key).connected ? '✓ ' : '' }}{{ statusOf(t) }}</small>
          </div>
          <span v-if="isActive(t.key)" class="badge sent">In use</span>
        </div>
      </div>

      <!-- the open service -->
      <div v-if="current" class="panel">
        <div class="phead">
          <span class="ico" :style="{ background: current.color, color: current.dark ? '#222' : '#fff' }">{{ current.letter || current.name[0] }}</span>
          <h3>{{ current.name }}</h3>
          <span v-if="isActive(current.key)" class="badge sent">In use</span>
          <span v-else-if="connected" class="badge">Connected, not in use</span>
          <span v-else-if="current.type === 'default' || current.type === 'log'" class="badge">Not in use</span>
        </div>

        <!-- Default -->
        <template v-if="current.type === 'default'">
          <template v-if="c.default_ready">
            <p class="hint">Sent from <b>this server itself</b>, with its own mail program ({{ c.default_via }}), not through SMTP. The message is handed to it for real: it goes out, or it fails with the reason.</p>
            <p v-if="c.default_catcher" class="note">ℹ This server's mail program is Mailpit: it keeps the mail in its own inbox (open <b>http://localhost:8025</b>) and never delivers to real inboxes. Fine for development. On a live server it would deliver for real.</p>
          </template>
          <p v-else class="warn">⚠ This server's mail program could not be found, so <b>Default</b> cannot send and would fail. Ask for <code>MAIL_SENDMAIL_PATH</code> to be set in <code>.env</code>, connect a mail service below, or use <b>Log only</b> to try things out.</p>
          <button v-if="!isActive('default')" type="button" class="btn" :disabled="busy || !c.default_ready" :title="c.default_ready ? '' : 'This server has no mail program configured'" @click="useService('default')">Use Default for sending</button>
        </template>

        <!-- Log only -->
        <template v-else-if="current.type === 'log'">
          <p class="hint">Nothing is sent. Each message is only written to the log and listed in the send history as <b>logged only</b>. Handy for trying campaigns and automations safely.</p>
          <button v-if="!isActive('log')" type="button" class="btn" :disabled="busy" @click="useService('log')">Use Log only</button>
        </template>

        <!-- Google / Microsoft -->
        <template v-else-if="current.type === 'oauth'">
          <template v-if="connected">
            <p class="auto"><b>Connected as {{ conn(current.key).email }}.</b> No password is stored: access is renewed automatically. Google and Microsoft only let you send as this mailbox.</p>
            <div class="row">
              <button v-if="!isActive(current.key)" type="button" class="btn" :disabled="busy" @click="useService(current.key)">Use {{ current.name }} for sending</button>
              <button type="button" class="btn ghost" @click="connect(current.key)">Reconnect</button>
              <button type="button" class="btn ghost" @click="disconnect(current.key)">Disconnect</button>
            </div>
          </template>
          <template v-else>
            <p class="hint">Sign in with {{ current.name }} once and mail is sent through your mailbox, with no password to type.</p>
            <p v-if="!c.oauth[current.key].configured" class="warn">One-click sign-in isn't available yet: the platform administrator has to add the {{ current.name }} app keys (see the README). Use one of the mail services meanwhile.</p>
            <button type="button" class="btn" :disabled="!c.oauth[current.key].configured || connecting === current.key" @click="connect(current.key)">{{ connecting === current.key ? 'Opening…' : `Connect ${current.name}` }}</button>
          </template>
        </template>

        <!-- SMTP services -->
        <template v-else-if="form">
          <div class="guide">
            <h4>Setup guide</h4>
            <ol class="checks">
              <li v-for="(x, i) in steps" :key="i" :class="{ done: x.done, next: i === nextStep }">
                <span class="dot">{{ x.done ? '✓' : i + 1 }}</span>{{ x.text }}<em v-if="i === nextStep"> ← do this next</em>
              </li>
            </ol>
            <template v-if="nextStep !== -1 && nextStep <= 1">
              <b class="how">How to get your sign-in details for {{ current.name }}:</b>
              <ol class="howto"><li v-for="(t, i) in HOWTO[current.key]" :key="i">{{ t }}</li></ol>
            </template>
            <p v-if="mismatch" class="warn">⚠ {{ mismatch }}</p>
            <p v-if="nextStep === 3" class="hint" style="margin: 8px 0 0">In use. Send a test email below to make sure it really connects.</p>
            <p v-if="nextStep === -1" class="done-msg">✓ All set. Your campaigns go out through {{ current.name }}.</p>
          </div>

          <p v-if="current.key !== 'custom'" class="auto"><b>Connected automatically:</b> {{ current.host }} · port {{ current.port }} · {{ current.encryption === 'ssl' ? 'SSL' : 'TLS / STARTTLS' }}
            <a href="#" @click.prevent="showServer = !showServer">{{ showServer || current.editHost ? 'hide' : 'change' }} server details</a></p>
          <div v-if="current.key === 'custom' || showServer || current.editHost" class="grid g3">
            <div class="field"><label>SMTP host</label><input v-model="form.host" placeholder="smtp.example.com" /></div>
            <div class="field"><label>Port</label><input v-model.number="form.port" type="number" min="1" max="65535" /></div>
            <div class="field"><label>Encryption</label><select v-model="form.encryption"><option value="tls">TLS / STARTTLS</option><option value="ssl">SSL</option><option value="none">None</option></select></div>
          </div>
          <div v-if="current.key === 'custom'" class="row" style="margin-bottom: 10px"><small>Quick fill:</small>
            <button type="button" class="btn ghost sm" @click="Object.assign(form, { port: 587, encryption: 'tls' })">STARTTLS · 587</button>
            <button type="button" class="btn ghost sm" @click="Object.assign(form, { port: 465, encryption: 'ssl' })">SSL · 465</button>
          </div>
          <div class="grid g2">
            <div class="field"><label>Username</label><input v-model="form.username" autocomplete="off" /><small>{{ current.user }}</small></div>
            <div class="field"><label>Password {{ conn(current.key).password_set ? '(saved)' : '' }}</label><input v-model="form.password" type="password" autocomplete="new-password" :placeholder="conn(current.key).password_set ? '•••••• leave blank to keep' : ''" /><small>{{ current.pass }}</small></div>
          </div>

          <div class="row">
            <button type="button" class="btn" :disabled="busy || !form.host" @click="saveConnection(false)">{{ connected ? 'Save changes' : 'Connect' }}</button>
            <button v-if="!isActive(current.key)" type="button" class="btn ghost" :disabled="busy || !form.host" @click="saveConnection(true)">{{ connected ? 'Save and use for sending' : 'Connect and use for sending' }}</button>
            <button v-if="connected && !dirtyForm && !isActive(current.key)" type="button" class="btn ghost" :disabled="busy" @click="useService(current.key)">Use for sending</button>
            <button v-if="connected" type="button" class="btn ghost" @click="disconnect(current.key)">Disconnect</button>
          </div>
        </template>
      </div>
    </div>

    <div class="card">
      <h2>Send a test email</h2>
      <p class="hint">Sends through the service that is <b>in use</b> ({{ tile(c.active).name }}).</p>
      <div v-if="testMsg" class="alert ok">{{ testMsg }}</div>
      <div v-if="testErr" class="alert err">{{ testErr }}</div>
      <div v-if="testHint" class="alert hintbox"><b>What to do:</b> {{ testHint }}</div>
      <form class="row" @submit.prevent="test"><input v-model="testTo" type="email" required placeholder="you@example.com" style="max-width: 300px" /><button class="btn ghost" :disabled="busy">Send test</button></form>
      <RecentSends channel="email" :tick="tick" />
    </div>
  </template>
</template>

<style scoped>
.hint { color: #6b7280; font-size: 14px; margin: 0 0 12px; }
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
.sug { background: #fffbea; border: 1px solid #f3e2a3; border-radius: 6px; padding: 10px 14px; font-size: 14px; margin: 0 0 4px; }
.sug .btn { margin-left: 8px; }
.auto { background: #eef7ee; color: #1b5e20; border-radius: 6px; padding: 10px 14px; font-size: 14px; margin: 0 0 14px; }
.auto a { margin-left: 8px; }
.warn { margin: 0 0 12px; color: #8a4b00; font-size: 14px; }
.note { margin: 0 0 12px; color: #1b5e20; background: #eef7ee; border-radius: 6px; padding: 8px 12px; font-size: 14px; }
.hintbox { background: #fffbea; border: 1px solid #f3e2a3; color: #5f4b00; }
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
