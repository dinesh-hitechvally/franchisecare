<script setup>
import { reactive, ref } from 'vue'
import api, { errorMessage } from '../api'
import { useAuth } from '../stores/auth'

// The signed-in person's own account. Other people are managed under Users.
const auth = useAuth()
const f = reactive({
  name: auth.user?.name || '', job_title: auth.user?.job_title || '', phone: auth.user?.phone || '', email: auth.user?.email || '',
  current_password: '', password: '', password_confirmation: '',
})
const msg = ref('')
const error = ref('')
const busy = ref(false)

async function save() {
  msg.value = error.value = ''
  busy.value = true
  try {
    const { data } = await api.put('/account', f)
    auth.user = data.user
    Object.assign(f, { current_password: '', password: '', password_confirmation: '' })
    msg.value = 'Your account was updated.'
  } catch (e) { error.value = errorMessage(e) } finally { busy.value = false }
}
</script>

<template>
  <div class="head"><h1>My account</h1></div>
  <p class="intro">This is you. To add or remove other people, go to <router-link to="/settings/users">Users</router-link>.</p>
  <form @submit.prevent="save">
    <div v-if="msg" class="alert ok">{{ msg }}</div>
    <div v-if="error" class="alert err">{{ error }}</div>

    <div class="card">
      <h2>Profile</h2>
      <div class="grid2">
        <div class="field"><label>Full name</label><input v-model="f.name" required maxlength="255" autocomplete="name" /></div>
        <div class="field"><label>Job title</label><input v-model="f.job_title" maxlength="120" autocomplete="organization-title" placeholder="e.g. Marketing manager" /></div>
        <div class="field"><label>Phone</label><input v-model="f.phone" maxlength="40" autocomplete="tel" /></div>
        <div class="field"><label>Sign-in email</label><input v-model="f.email" type="email" required maxlength="255" autocomplete="email" /><small>Changing it needs your current password.</small></div>
      </div>
    </div>

    <div class="card">
      <h2>Password</h2>
      <div class="grid3">
        <div class="field"><label>Current password</label><input v-model="f.current_password" type="password" autocomplete="current-password" /><small>Needed to change your email or password.</small></div>
        <div class="field"><label>New password</label><input v-model="f.password" type="password" minlength="8" autocomplete="new-password" placeholder="Leave blank to keep it" /><small>At least 8 characters.</small></div>
        <div class="field"><label>Repeat new password</label><input v-model="f.password_confirmation" type="password" minlength="8" autocomplete="new-password" :disabled="!f.password" /></div>
      </div>
      <p class="note">Changing your password signs you out on your other devices.</p>
    </div>

    <div class="row"><button class="btn" :disabled="busy">{{ busy ? 'Saving…' : 'Save changes' }}</button></div>
  </form>
</template>

<style scoped>
h2 { font-size: 16px; margin: 0 0 14px; }
.grid2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 24px; }
.grid3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0 24px; }
.note { color: #6b7280; font-size: 13px; margin: 0; }
@media (max-width: 860px) { .grid2, .grid3 { grid-template-columns: 1fr; } }
</style>
