<script setup>
import { ref, reactive, onMounted } from 'vue'
import api, { errorMessage } from '../api'

const s = ref(null)
const msg = ref('')
const error = ref('')
// secrets are write-only: blank means "keep existing"
const secrets = reactive({ twilio_token: '' })

onMounted(async () => { s.value = (await api.get('/settings')).data })

async function save() {
  msg.value = error.value = ''
  const body = {
    ...s.value,
    sms: { provider: s.value.sms.provider, twilio_sid: s.value.sms.twilio_sid, from: s.value.sms.from, twilio_token: secrets.twilio_token },
  }
  try {
    s.value = (await api.put('/settings', body)).data
    Object.keys(secrets).forEach((k) => (secrets[k] = ''))
    msg.value = 'Settings saved.'
  } catch (e) { error.value = errorMessage(e) }
}
</script>

<template>
  <div class="head"><h1>Settings</h1></div>
  <p v-if="!s" class="empty">Loading…</p>
  <form v-else @submit.prevent="save">
    <div v-if="msg" class="alert ok">{{ msg }}</div>
    <div v-if="error" class="alert err">{{ error }}</div>

    <div class="card">
      <h2>Business &amp; email sender</h2>
      <div class="grid g2">
        <div class="field"><label>Business name</label><input v-model="s.name" required /></div>
        <div class="field"><label>From name</label><input v-model="s.from_name" /></div>
        <div class="field"><label>From email</label><input v-model="s.from_email" type="email" placeholder="Must be allowed by your mail server" /></div>
        <div class="field"><label>Reply-to email</label><input v-model="s.reply_to" type="email" /></div>
      </div>
      <div class="field"><label>Postal address (shown in email footer)</label><input v-model="s.address" /></div>
    </div>

    <div class="card">
      <h2>SMS provider</h2>
      <div class="field" style="max-width: 280px">
        <label>Provider</label>
        <select v-model="s.sms.provider"><option value="log">Log only (testing – nothing is sent)</option><option value="twilio">Twilio</option></select>
      </div>
      <div v-if="s.sms.provider === 'twilio'" class="grid g3">
        <div class="field"><label>Account SID</label><input v-model="s.sms.twilio_sid" /></div>
        <div class="field"><label>Auth token {{ s.sms.twilio_token_set ? '(saved)' : '' }}</label><input v-model="secrets.twilio_token" type="password" :placeholder="s.sms.twilio_token_set ? '•••••• leave blank to keep' : ''" /></div>
        <div class="field"><label>Sender number / ID</label><input v-model="s.sms.from" placeholder="+1555…" /></div>
      </div>
    </div>

    <button class="btn">Save settings</button>
  </form>
</template>
