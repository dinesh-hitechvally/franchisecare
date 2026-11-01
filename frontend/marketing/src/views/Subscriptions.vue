<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import api, { errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import RowMenu from '../components/RowMenu.vue'
import Pager from '../components/Pager.vue'

const data = ref(null)
const f = reactive({ channel: 'email', status: 'unsubscribed', search: '' })
const fDirty = computed(() => f.search !== '' || f.channel !== 'email' || f.status !== 'unsubscribed')
const fClear = () => Object.assign(f, { channel: 'email', status: 'unsubscribed', search: '' })
const selected = ref([])
const msg = ref('')
const error = ref('')

const rows = computed(() => data.value?.contacts.data || [])
const col = computed(() => (f.channel === 'sms' ? 'sms_subscribed' : 'email_subscribed'))
const when = computed(() => (f.channel === 'sms' ? 'sms_unsubscribed_at' : 'email_unsubscribed_at'))
const allSelected = computed(() => rows.value.length > 0 && rows.value.every((r) => selected.value.includes(r.id)))

async function load(page = 1) {
  const params = { channel: f.channel, page }
  if (f.status) params.status = f.status
  if (f.search) params.search = f.search
  data.value = (await api.get('/subscriptions', { params })).data
  selected.value = []
}

async function change(ids, subscribed) {
  const word = subscribed ? 're-subscribe' : 'unsubscribe'
  if (subscribed && !confirm(`Only ${word} people who have given you permission again. Continue with ${ids.length} contact(s)?`)) return
  error.value = msg.value = ''
  try {
    const r = await api.post('/subscriptions/bulk', { ids, channel: f.channel, subscribed })
    msg.value = `${r.data.changed} contact(s) updated.`
    load(data.value?.contacts.current_page || 1)
  } catch (e) { error.value = errorMessage(e) }
}

const toggleAll = () => (selected.value = allSelected.value ? [] : rows.value.map((r) => r.id))
const name = (c) => [c.first_name, c.last_name].filter(Boolean).join(' ') || '—'

let t
watch(() => f.search, () => { clearTimeout(t); t = setTimeout(() => load(), 300) })
watch(() => [f.channel, f.status], () => load())
onMounted(() => load())
</script>

<template>
  <div class="head"><h1>Subscriptions</h1></div>
  <div v-if="msg" class="alert ok">{{ msg }}</div>
  <div v-if="error" class="alert err">{{ error }}</div>

  <div v-if="data" class="grid g4">
    <div class="card stat"><div class="n">{{ data.summary.email.subscribed }}</div><div class="l">Email subscribed</div></div>
    <div class="card stat"><div class="n" style="color: #c62828">{{ data.summary.email.unsubscribed }}</div><div class="l">Email unsubscribed</div></div>
    <div class="card stat"><div class="n">{{ data.summary.sms.subscribed }}</div><div class="l">SMS subscribed</div></div>
    <div class="card stat"><div class="n" style="color: #c62828">{{ data.summary.sms.unsubscribed }}</div><div class="l">SMS unsubscribed</div></div>
  </div>

  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="f.search" type="search" placeholder="Search name, email, phone…" />
    <select v-model="f.channel"><option value="email">Email</option><option value="sms">SMS</option></select>
    <select v-model="f.status"><option value="">All contacts</option><option value="unsubscribed">Unsubscribed</option><option value="subscribed">Subscribed</option></select>
  </FilterBar>

  <div class="card">
    <div v-if="selected.length" class="row" style="margin-bottom: 12px">
      <template v-if="selected.length">
        <b>{{ selected.length }} selected</b>
        <button class="btn ghost sm" @click="change(selected, false)">Unsubscribe</button>
        <button class="btn sm" @click="change(selected, true)">Re-subscribe</button>
      </template>
    </div>

    <p v-if="!rows.length" class="empty">No contacts match.</p>
    <table v-else>
      <thead><tr><th style="width: 30px"><input type="checkbox" :checked="allSelected" @change="toggleAll" /></th><th>Name</th><th>{{ f.channel === 'sms' ? 'Phone' : 'Email' }}</th><th>Status</th><th>Unsubscribed on</th><th class="act">Actions</th></tr></thead>
      <tr v-for="c in rows" :key="c.id">
        <td><input v-model="selected" type="checkbox" :value="c.id" /></td>
        <td>{{ name(c) }}</td>
        <td>{{ f.channel === 'sms' ? c.phone : c.email }}</td>
        <td><span class="badge" :class="c[col] ? 'sent' : 'failed'">{{ c[col] ? 'subscribed' : 'unsubscribed' }}</span></td>
        <td>{{ c[when] ? $dt(c[when]) : '—' }}</td>
        <td class="act"><RowMenu :history="{ type: 'contact', id: c.id, title: c.email || c.phone }" :items="[{ label: c[col] ? 'Unsubscribe' : 'Re-subscribe', run: () => change([c.id], !c[col]) }]" /></td>
      </tr>
    </table>
    <Pager v-if="data" :meta="data.contacts" @page="load" />
  </div>
  <p class="note">Contacts can unsubscribe themselves from any email using the link in the footer; that is recorded here with the date. Only re-subscribe someone who has asked to hear from you again.</p>
</template>

<style scoped>
.note { color: #6b7280; font-size: 13px; max-width: 760px; }
</style>
