<script setup>
// Settings → Push notifications: connect websites (browser push) and apps (Android / iOS through Firebase), choose live or test mode, and see the registered devices.
import { ref, computed, onMounted } from 'vue'
import api, { errorMessage, responseMessage } from '../api'
import InfoTip from '../components/InfoTip.vue'

const s = ref(null)
const msg = ref('')
const error = ref('')
const busy = ref(false)
const account = ref('')

const snippet = computed(() => (s.value ? `<script src="${s.value.api_url}/push/sdk.js" data-key="${s.value.site_key}"><\/script>\n<button onclick="MatePush.subscribe({ email: 'visitor@example.com' })">Turn on notifications</button>` : ''))
const appCall = computed(() => (s.value ? `curl -X POST ${s.value.api_url}/v1/push/devices \\\n  -H "Authorization: Bearer YOUR_API_KEY" -H "Content-Type: application/json" \\\n  -d '{"token":"FIREBASE_TOKEN","platform":"android","email":"person@example.com"}'` : ''))

async function load() { s.value = (await api.get('/push/settings')).data }
async function run(fn, ok) {
  msg.value = error.value = ''
  busy.value = true
  try { const r = await fn(); msg.value = ok || responseMessage(r) || 'Saved.'; await load() } catch (e) { error.value = errorMessage(e) } finally { busy.value = false }
}
const setMode = (mode) => run(() => api.put('/push/settings', { mode }), mode === 'live' ? 'Push notifications are live.' : 'Test mode on: nothing is sent to devices.')
const saveFcm = () => run(async () => { const r = await api.put('/push/fcm', { service_account: account.value }); account.value = ''; return r })
const removeFcm = () => confirm('Disconnect Firebase? Android and iOS apps stop receiving notifications.') && run(() => api.delete('/push/fcm'))
const rotate = () => confirm('Create new browser keys? Every browser that allowed notifications must allow them again.') && run(() => api.post('/push/web-keys/rotate'))

const copy = async (text) => { try { await navigator.clipboard.writeText(text); msg.value = 'Copied.' } catch { error.value = 'Could not copy. Select the text and copy it.' } }

onMounted(load)
</script>

<template>
  <h1>Push notifications</h1>
  <p class="intro">Send notifications to people’s browsers and phones, as a third channel next to email and SMS. People receive them only when they allowed notifications on your website or in your app.</p>
  <div v-if="msg" class="alert ok" @click="msg = ''">{{ msg }}</div>
  <div v-if="error" class="alert err" @click="error = ''">{{ error }}</div>

  <template v-if="s">
    <div class="card">
      <h2>Sending mode</h2>
      <label class="check"><input type="radio" :checked="s.mode === 'log'" :disabled="busy" @change="setMode('log')" /> <span><b>Test mode</b>: notifications are only written to the message log, nothing reaches a device. Safe for trying a campaign.</span></label>
      <label class="check"><input type="radio" :checked="s.mode === 'live'" :disabled="busy" @change="setMode('live')" /> <span><b>Live</b>: notifications are delivered to browsers and apps.</span></label>
      <div class="stats">
        <router-link to="/devices" class="stat" style="text-decoration: none; color: inherit" title="Open the device list"><div class="n">{{ s.devices.total }}</div><div class="l">Devices</div></router-link>
        <div class="stat"><div class="n">{{ s.devices.web }}</div><div class="l">Browsers</div></div>
        <div class="stat"><div class="n">{{ s.devices.android }}</div><div class="l">Android</div></div>
        <div class="stat"><div class="n">{{ s.devices.ios }}</div><div class="l">iOS</div></div>
      </div>
    </div>

    <div class="card">
      <h2>Websites (browser push)</h2>
      <p class="hint">No third-party account is needed: notifications are signed with your own keys. Works in Chrome, Edge, Firefox and Safari.</p>
      <p><b>Easiest:</b> add the <b>Push notifications</b> block to a page in the page builder (Content → Pages), or the <b>Push notifications</b> field to a form in the form builder (Content → Forms). When a visitor allows notifications there, their browser is registered as a device. No email is needed.</p>
      <p class="hint" style="margin-top: 14px"><b>On your own website</b> (outside this app), add the script yourself:</p>
      <ol class="steps">
        <li><b>Download the service worker</b> and put it at the top level of your website, so it opens as <code>https://your-site.com/mate-push-sw.js</code>. <a :href="s.api_url + '/push/sw.js'" download class="btn ghost sm">Download mate-push-sw.js</a></li>
        <li><b>Add this to your pages</b> (your website must use https). The button asks the visitor to allow notifications; give their email so the browser is linked to the right person.
          <pre>{{ snippet }}</pre><button class="btn ghost sm" @click="copy(snippet)">Copy</button></li>
        <li>For a visitor who is already known, link their browser later with <code>MatePush.identify({ email })</code>. To let them turn notifications off: <code>MatePush.unsubscribe()</code>.</li>
      </ol>
      <p class="hint">Your site key: <code>{{ s.site_key }}</code> <InfoTip text="The site key is public. It only says which company a browser registers with." /></p>
      <button class="btn ghost sm" :disabled="busy" @click="rotate">Create new browser keys</button>
    </div>

    <div class="card">
      <h2>Android and iOS apps (Firebase)</h2>
      <template v-if="s.fcm.connected">
        <p><span class="badge sent">connected</span> Firebase project <b>{{ s.fcm.project_id }}</b> <small class="muted">({{ s.fcm.client_email }})</small></p>
        <button class="btn danger sm" :disabled="busy" @click="removeFcm">Disconnect Firebase</button>
      </template>
      <template v-else>
        <ol class="steps">
          <li>In the Firebase console open <b>Project settings → Service accounts</b> and choose <b>Generate new private key</b>.</li>
          <li>Open the downloaded file, copy everything in it and paste it here.</li>
        </ol>
        <div class="field"><label>Service account file</label><textarea v-model="account" rows="5" placeholder='{ "type": "service_account", "project_id": "…", … }' spellcheck="false"></textarea><small>Stored encrypted. It is checked with Firebase before it is saved.</small></div>
        <button class="btn" :disabled="busy || !account.trim()" @click="saveFcm">Connect Firebase</button>
      </template>
      <p class="hint" style="margin-top: 14px">Your app registers each installation with its Firebase token and the person’s email, using an API key from <router-link to="/settings/api">API keys</router-link>:</p>
      <pre>{{ appCall }}</pre><button class="btn ghost sm" @click="copy(appCall)">Copy</button>
    </div>
  </template>

  <p class="more">The registered browsers and apps are listed under <router-link to="/devices">Audience → Devices</router-link>, where you can search them, send a test notification to one, or remove it.</p>
</template>

<style scoped>
.intro { color: #6b7280; margin: -8px 0 18px; }
.sec { font-size: 18px; margin: 26px 0 12px; }
.card { margin-bottom: 18px; }
.check { display: flex; align-items: flex-start; gap: 8px; margin: 8px 0; }
.check input { margin-top: 3px; width: auto; flex: none; }
.stats { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 14px; }
.steps { padding-left: 20px; } .steps li { margin: 10px 0; }
pre { background: #f5f6fa; border: 1px solid #e3e6f0; border-radius: 6px; padding: 10px 12px; overflow: auto; font-size: 13px; margin: 8px 0; }
.muted { color: #6b7280; }
.more { margin-top: 18px; color: #6b7280; }
</style>
