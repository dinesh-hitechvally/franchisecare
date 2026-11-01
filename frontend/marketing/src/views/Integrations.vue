<script setup>
import { ref, computed, onMounted } from 'vue'
import api, { errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import { useFilters } from '../composables/useFilters'
import Modal from '../components/Modal.vue'

const keys = ref([])
const { f: flt, shown: visible, dirty: fDirty, clear: fClear } = useFilters(keys, { text: (k) => k.name })
const name = ref('')
const created = ref(null) // { name, key } — shown once
const error = ref('')
const copied = ref(false)

const base = computed(() => (api.defaults.baseURL || '').replace(/\/$/, ''))
const curl = computed(() => `curl -X POST ${base.value}/v1/contacts \\
  -H "Authorization: Bearer YOUR_API_KEY" \\
  -H "Content-Type: application/json" \\
  -d '{"email":"jane@example.com","first_name":"Jane","tags":["website"],"lists":["Newsletter"]}'`)

const load = async () => { keys.value = (await api.get('/api-keys')).data }

async function create() {
  error.value = ''
  try {
    created.value = (await api.post('/api-keys', { name: name.value })).data
    name.value = ''
    load()
  } catch (e) { error.value = errorMessage(e) }
}

async function revoke(k) {
  if (!confirm(`Revoke the key "${k.name}"? Anything using it stops working immediately.`)) return
  await api.delete(`/api-keys/${k.id}`)
  load()
}

async function copy(text) {
  try { await navigator.clipboard.writeText(text); copied.value = true; setTimeout(() => (copied.value = false), 1500) } catch (e) { /* clipboard blocked */ }
}

onMounted(load)
</script>

<template>
  <div class="head"><h1>API Integrations</h1></div>
  <p class="intro">Connect other systems (your website, Zapier, a CRM) so they can add and update contacts here. Each system gets its own key, which you can revoke at any time.</p>
  <div v-if="error" class="alert err">{{ error }}</div>

  <div class="card">
    <h2>Create an API key</h2>
    <form class="row" @submit.prevent="create">
      <input v-model="name" required maxlength="80" placeholder="Key name, e.g. Website or Zapier" style="max-width: 320px" />
      <button class="btn">Create key</button>
    </form>
  </div>

  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="flt.q" type="search" placeholder="Search API keys…" />
  </FilterBar>

  <div class="card">
    <h2>API keys</h2>
    <p v-if="!keys.length" class="empty">No API keys yet.</p>
    <p v-else-if="!visible.length" class="empty">Nothing matches these filters.</p>
    <table v-else>
      <thead><tr><th>Name</th><th>Created</th><th>Last used</th><th></th></tr></thead>
      <tr v-for="k in visible" :key="k.id">
        <td><b>{{ k.name }}</b></td>
        <td>{{ new Date(k.created_at).toLocaleDateString() }}</td>
        <td>{{ k.last_used_at ? new Date(k.last_used_at).toLocaleString() : 'never' }}</td>
        <td style="text-align: right"><button class="btn ghost sm" @click="revoke(k)">Revoke</button></td>
      </tr>
    </table>
  </div>

  <div class="card">
    <h2>Using the API</h2>
    <p class="hint">Send the key as a Bearer token. Base URL: <code>{{ base }}</code>. A key can only use these <code>/v1</code> endpoints — it cannot open the dashboard.</p>
    <table class="docs">
      <tr><td><code>POST /v1/contacts</code></td><td>Create or update a contact by email (or phone). Optional: <code>first_name, last_name, phone, tags[], lists[], custom_fields</code>. Tags and lists are matched by name and created if missing. Triggers workflows and webhooks.</td></tr>
      <tr><td><code>GET /v1/contacts?email=</code></td><td>Look up a contact, with its lists and tags.</td></tr>
      <tr><td><code>POST /v1/contacts/unsubscribe</code></td><td>Opt a contact out: <code>email</code>, optional <code>channel</code> (email or sms).</td></tr>
    </table>
    <pre>{{ curl }}</pre>
  </div>

  <Modal v-if="created" title="Your new API key" @close="created = null">
    <div class="alert ok">Copy this key now — for security it is shown <b>only once</b> and cannot be retrieved later.</div>
    <label>{{ created.name }}</label>
    <div class="row"><input :value="created.key" readonly style="font-family: monospace" @focus="$event.target.select()" /><button class="btn sm" @click="copy(created.key)">{{ copied ? 'Copied ✓' : 'Copy' }}</button></div>
  </Modal>
</template>

<style scoped>
.intro { color: #6b7280; margin: -8px 0 18px; max-width: 760px; }
.hint { color: #6b7280; font-size: 14px; margin: 0 0 12px; }
.docs td { vertical-align: top; font-size: 14px; } .docs td:first-child { white-space: nowrap; }
code { background: #f1f2f6; padding: 1px 6px; border-radius: 3px; font-size: 13px; }
pre { background: #1e1e1e; color: #d4d4d4; padding: 14px; border-radius: 6px; overflow: auto; font-size: 13px; }
</style>
