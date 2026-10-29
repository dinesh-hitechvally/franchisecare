<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../stores/auth'
import { errorMessage } from '../api'

const auth = useAuth()
const router = useRouter()
const mode = ref('login')
const error = ref('')
const busy = ref(false)
const showPass = ref(false)
const form = reactive({ business_name: '', name: '', email: '', password: '', password_confirmation: '' })

async function submit() {
  busy.value = true
  error.value = ''
  try {
    await (mode.value === 'login' ? auth.login(form) : auth.register(form))
    router.push('/dashboard')
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="lg-page">
    <div class="lg-card">
      <form class="lg-form" @submit.prevent="submit">
        <h1>{{ mode === 'login' ? 'Login to Marketing' : 'Create your account' }}</h1>
        <div v-if="error" class="lg-err">{{ error }}</div>

        <template v-if="mode === 'register'">
          <div class="lg-field"><input v-model="form.business_name" placeholder="Business name" required /></div>
          <div class="lg-field"><input v-model="form.name" placeholder="Your name" required /></div>
        </template>

        <div class="lg-field"><input v-model="form.email" type="email" placeholder="Email" autocomplete="username" required /></div>
        <div class="lg-field">
          <input v-model="form.password" :type="showPass ? 'text' : 'password'" placeholder="Password" minlength="8" autocomplete="current-password" required />
          <button type="button" class="lg-eye" :aria-label="showPass ? 'Hide password' : 'Show password'" @click="showPass = !showPass">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path v-if="!showPass" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17a5 5 0 110-10 5 5 0 010 10zm0-8a3 3 0 100 6 3 3 0 000-6z" /><path v-else d="M12 7a5 5 0 015 5c0 .65-.13 1.26-.36 1.83l2.92 2.92A11.8 11.8 0 0023 12c-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46A11.8 11.8 0 001 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55a3 3 0 003.57 3.57l1.55 1.55A5 5 0 017.53 9.8z" /></svg>
          </button>
        </div>
        <div v-if="mode === 'register'" class="lg-field"><input v-model="form.password_confirmation" type="password" placeholder="Confirm password" required /></div>

        <div class="lg-actions">
          <button class="lg-btn" :disabled="busy">{{ mode === 'login' ? 'Sign in' : 'Sign up' }}</button>
          <a href="#" class="lg-link" @click.prevent="mode = mode === 'login' ? 'register' : 'login'; error = ''">
            {{ mode === 'login' ? 'Create account' : 'Have an account?' }}
          </a>
        </div>
      </form>

      <div class="lg-brand">
        <div class="lg-logo">FranchiseCare<span>Marketing</span></div>
      </div>
    </div>

    <p class="lg-foot">Email &amp; SMS marketing for your business</p>
  </div>
</template>

<style scoped>
.lg-page { min-height: 100vh; background: #f3f4f6; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 16px; font-family: Roboto, Inter, system-ui, sans-serif; }
.lg-card { display: flex; width: 100%; max-width: 680px; min-height: 338px; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 6px rgba(0, 0, 0, .18); }
.lg-form { flex: 0 0 60%; padding: 34px 35px 24px; }
.lg-form h1 { font-size: 24px; font-weight: 400; margin: 0 0 44px; color: #212121; }
.lg-field { position: relative; margin-bottom: 26px; }
.lg-field input { width: 100%; border: 0; border-bottom: 1px solid #8a8a8a; border-radius: 0; padding: 8px 32px 8px 0; font-size: 16px; background: transparent; outline: none; color: #212121; }
.lg-field input::placeholder { color: #6b6b6b; }
.lg-field input:focus { border-bottom: 2px solid #3f51b5; margin-bottom: -1px; outline: none; }
.lg-eye { position: absolute; right: 0; top: 6px; border: 0; background: none; color: #6b6b6b; cursor: pointer; padding: 0; display: flex; }
.lg-actions { display: flex; align-items: center; justify-content: space-between; margin-top: 8px; }
.lg-btn { background: #3f51b5; color: #fff; border: 0; border-radius: 4px; padding: 10px 16px; font-size: 14px; font-weight: 500; letter-spacing: .03em; text-transform: uppercase; cursor: pointer; box-shadow: 0 2px 4px rgba(0, 0, 0, .2); }
.lg-btn:hover { background: #3949ab; } .lg-btn:disabled { opacity: .6; cursor: not-allowed; }
.lg-link { color: #3f51b5; font-size: 14px; text-decoration: none; } .lg-link:hover { text-decoration: underline; }
.lg-err { background: #fdecea; color: #b71c1c; padding: 8px 12px; border-radius: 4px; font-size: 13px; margin: -24px 0 18px; }
.lg-brand { flex: 1; background: #3f51b5; display: flex; align-items: center; justify-content: center; padding: 20px; }
.lg-logo { color: #fff; font-size: 24px; font-weight: 700; text-align: center; line-height: 1.2; }
.lg-logo span { display: block; font-size: 14px; font-weight: 400; opacity: .85; margin-top: 4px; letter-spacing: .12em; text-transform: uppercase; }
.lg-foot { margin-top: 28px; font-size: 13px; color: #6b6b6b; }
@media (max-width: 600px) { .lg-card { flex-direction: column-reverse; } .lg-brand { padding: 24px; } .lg-form { padding: 28px 24px; } }
</style>
