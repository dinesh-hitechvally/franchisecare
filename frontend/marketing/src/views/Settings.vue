<script setup>
import { ref, onMounted } from 'vue'
import api, { errorMessage } from '../api'
import { useAuth } from '../stores/auth'
import MediaPicker from '../components/MediaPicker.vue'

// Company profile. Email sender / SMTP and SMS credentials have their own sections.
const auth = useAuth()
const s = ref(null)
const msg = ref('')
const error = ref('')
const busy = ref(false)
const picking = ref(false)

onMounted(async () => { s.value = (await api.get('/settings')).data })

async function save() {
  msg.value = error.value = ''
  busy.value = true
  try {
    s.value = (await api.put('/settings', { name: s.value.name, address: s.value.address, profile: s.value.profile })).data
    auth.company = { ...auth.company, name: s.value.name, logo_url: s.value.logo_url || null }
    msg.value = 'Company profile saved.'
  } catch (e) { error.value = errorMessage(e) } finally { busy.value = false }
}
const tag = (k) => '{{' + k + '}}'
function chooseLogo(m) {
  s.value.profile.logo_media_id = m.id
  s.value.logo_url = m.url
  picking.value = false
}
function removeLogo() {
  s.value.profile.logo_media_id = null
  s.value.logo_url = null
}
</script>

<template>
  <div class="head"><h1>Company profile</h1></div>
  <p class="intro">Who you are. These details appear in your emails and can be used as merge tags.</p>
  <p v-if="!s" class="empty">Loading…</p>
  <form v-else @submit.prevent="save">
    <div v-if="msg" class="alert ok">{{ msg }}</div>
    <div v-if="error" class="alert err">{{ error }}</div>

    <div class="card">
      <h2>Business</h2>
      <div class="grid2">
        <div class="field"><label>Company name</label><input v-model="s.name" required maxlength="255" /><small>Merge tag <code>{{ tag('company_name') }}</code></small></div>
        <div class="field"><label>Legal / registered name</label><input v-model="s.profile.legal_name" maxlength="255" /><small>Shown in the email footer instead of the company name, if filled in.</small></div>
        <div class="field"><label>Tax / business number</label><input v-model="s.profile.tax_id" maxlength="60" placeholder="e.g. ABN 12 345 678 901" /><small>Shown in the email footer.</small></div>
        <div class="field"><label>Industry</label><input v-model="s.profile.industry" maxlength="120" placeholder="e.g. Retail, Fitness, Real estate" /></div>
      </div>
    </div>

    <div class="card">
      <h2>Contact details</h2>
      <div class="grid2">
        <div class="field"><label>Public email</label><input v-model="s.profile.support_email" type="email" maxlength="255" /><small>Merge tag <code>{{ tag('company_email') }}</code></small></div>
        <div class="field"><label>Phone</label><input v-model="s.profile.phone" maxlength="40" /><small>Merge tag <code>{{ tag('company_phone') }}</code></small></div>
        <div class="field"><label>Website</label><input v-model="s.profile.website" type="url" maxlength="255" placeholder="https://" /><small>Merge tag <code>{{ tag('company_website') }}</code></small></div>
        <div class="field">
          <label>Logo</label>
          <div class="logo">
            <img v-if="s.logo_url" :src="s.logo_url" alt="Company logo" class="lg" />
            <span v-else class="lg ph">{{ (s.name || '?')[0] }}</span>
            <div>
              <button type="button" class="btn sm" @click="picking = true">{{ s.logo_url ? 'Change logo' : 'Choose from media' }}</button>
              <button v-if="s.logo_url" type="button" class="btn ghost sm" style="margin-left: 6px" @click="removeLogo">Remove</button>
            </div>
          </div>
          <small>Picked from the <router-link to="/media">Media library</router-link>. Shown next to your company name in the sidebar.</small>
        </div>
      </div>
    </div>

    <div class="card">
      <h2>Postal address</h2>
      <p class="muted">Shown in the footer of every email: most anti-spam laws require a physical address.</p>
      <div class="grid2">
        <div class="field span2"><label>Street address</label><input v-model="s.address" maxlength="255" /></div>
        <div class="field"><label>City / suburb</label><input v-model="s.profile.city" maxlength="120" /></div>
        <div class="field"><label>State / region</label><input v-model="s.profile.state" maxlength="120" /></div>
        <div class="field"><label>Postcode</label><input v-model="s.profile.postcode" maxlength="20" /></div>
        <div class="field"><label>Country</label><input v-model="s.profile.country" maxlength="120" /></div>
      </div>
    </div>

    <div class="row"><button class="btn" :disabled="busy">{{ busy ? 'Saving…' : 'Save profile' }}</button></div>
    <MediaPicker v-if="picking" title="Choose your company logo" :selected-id="s.profile.logo_media_id" @pick="chooseLogo" @close="picking = false" />
    <p class="hint">Sender name and delivery are under <router-link to="/settings/email">Email sending</router-link>; SMS under <router-link to="/settings/sms">SMS sending</router-link>.</p>
  </form>
</template>

<style scoped>
h2 { font-size: 16px; margin: 0 0 14px; }
.grid2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 24px; }
.span2 { grid-column: 1 / -1; }
.muted { color: #6b7280; margin: -6px 0 14px; font-size: 14px; }
.hint { color: #6b7280; font-size: 14px; }
.logo { display: flex; align-items: center; gap: 14px; margin: 4px 0 6px; }
.lg { width: 56px; height: 56px; border-radius: 10px; object-fit: contain; background: #fff; border: 1px solid #e0e3f0; flex: none; }
.lg.ph { display: grid; place-items: center; background: #3f51b5; color: #fff; font-weight: 700; font-size: 22px; text-transform: uppercase; border: 0; }
@media (max-width: 760px) { .grid2 { grid-template-columns: 1fr; } }
</style>
