<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api, { errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import DateInput from '../components/DateInput.vue'
import Pager from '../components/Pager.vue'
import HistoryVersion from '../components/HistoryVersion.vue'

// "Histories" of one kind of record (pages, forms, templates, campaigns, people, segments): every change made to any of them,
// newest first, with filters. Pages, forms, templates and campaigns can open the editor on a saved version ("View this version", read-only);
// people and segments have no editor to open, so "View changes" shows what was different, old -> new.
const props = defineProps({ kind: { type: String, required: true } })
const router = useRouter()

const COMMON = { name: 'Name', description: 'Description' }
const CONFIG = {
  landing_page: {
    title: 'Page histories', noun: 'Page', back: ['/pages', 'Back to pages'], records: '/landing-pages', all: 'All pages', search: 'Search by page name…',
    columns: { slug: 'Address (slug)', title: 'Title', html: 'Design', design: 'Design', published: 'Published', form_id: 'Form' },
    open: (r, entry) => (entry.exists && entry.data.design?.kind === 'page' ? '/pages?edit=' + r.row_id + '&history=' + r.history_id : null), preview: true,
    intro: 'Every change made to your pages, newest first: who changed what, and when. Open a version to see the page exactly as it was. You can only look, nothing changes.',
  },
  form: {
    title: 'Form histories', noun: 'Form', back: ['/forms', 'Back to forms'], records: '/forms', all: 'All forms', search: 'Search by form name…',
    columns: { type: 'Type', fields: 'Fields', style: 'Design', title: 'Form title', button_text: 'Button text', success_message: 'Thank-you message', existing_message: 'Message for existing people', redirect_url: 'Redirect', save_person: 'Save to People', subscribe: 'Subscribe', notify_emails: 'Notify by email', autoreply: 'Auto-reply', autoreply_subject: 'Auto-reply subject', autoreply_body: 'Auto-reply message', autoreply_attachments: 'Auto-reply attachments', list_ids: 'Lists', tag_ids: 'Tags', active: 'Active' },
    open: (r, entry) => (entry.exists ? '/forms/' + r.row_id + '?history=' + r.history_id : null), preview: false,
    intro: 'Every change made to your forms, newest first: who changed what, and when. Open a version to see the form exactly as it was. You can only look, nothing changes.',
  },
  template: {
    title: 'Template histories', noun: 'Template', back: ['/templates', 'Back to templates'], records: '/templates', all: 'All templates', search: 'Search by template name…',
    columns: { type: 'Channel', subject: 'Subject', body: 'Design', design: 'Design', attachments: 'Attachments' },
    open: (r, entry) => (entry.exists && entry.data.type !== 'sms' ? '/templates?edit=' + r.row_id + '&history=' + r.history_id : null), preview: true,
    intro: 'Every change made to your email and SMS templates, newest first: who changed what, and when. Open a version to see the template exactly as it was. You can only look, nothing changes.',
  },
  campaign: {
    title: 'Campaign histories', noun: 'Campaign', back: ['/campaigns', 'Back to campaigns'], records: null, all: null, search: 'Search by campaign name…',
    columns: { type: 'Channel', subject: 'Subject', body: 'Design', design: 'Design', template_id: 'Template', people_list_id: 'Audience', tag_id: 'Audience tag', segment_id: 'Audience', scheduled_at: 'Schedule', started_at: 'Started', completed_at: 'Finished', status: 'Status', attachments: 'Attachments' },
    // a campaign that is still a draft or scheduled opens in its editor; one that has gone out can no longer be edited, so it gets the read-only preview
    open: (r, entry) => (entry.exists && entry.data.type === 'email' && ['draft', 'scheduled'].includes(entry.current_status) ? '/campaigns/' + r.row_id + '?history=' + r.history_id : null), preview: true,
    intro: 'Every change made to your campaigns, newest first: who changed what, and when. Open a version to see the campaign content exactly as it was. You can only look, nothing changes.',
  },
  person: {
    title: 'People histories', noun: 'Person', back: ['/people', 'Back to people'], records: null, all: null, search: 'Search by email, phone or name…',
    columns: { first_name: 'First name', last_name: 'Last name', email: 'Email', phone: 'Phone', source: 'Source', is_subscribed: 'Subscribed', subscribed_at: 'Subscribed on' },
    open: null, details: true,
    intro: 'Every change made to your people, newest first: who changed what, and when. Click View changes to see exactly what was different. Changes to a person’s lists and tags are in that person’s own History.',
  },
  workflow: {
    title: 'Workflow histories', noun: 'Workflow', back: ['/workflows', 'Back to workflows'], records: '/workflows', all: 'All workflows', search: 'Search by workflow name…',
    columns: { trigger_type: 'Trigger', trigger_config: 'Trigger settings', steps: 'Steps', status: 'Status' },
    open: null, details: true,
    intro: 'Every change made to your workflows, newest first: who changed what, and when, including when one was switched on or paused. Click View changes to see exactly what was different.',
  },
  webhook: {
    title: 'Webhook histories', noun: 'Webhook', back: ['/automation/webhooks', 'Back to webhooks'], records: '/webhooks', all: 'All webhooks', search: 'Search by webhook name…',
    columns: { url: 'URL', events: 'Events', active: 'Active' },
    open: null, details: true,
    intro: 'Every change made to your webhooks, newest first: who changed what, and when, including when one was switched off. The signing secret is never recorded. Click View changes to see exactly what was different. For what was actually sent to each URL, see Deliveries.',
  },
  segment: {
    title: 'Segment histories', noun: 'Segment', back: ['/segments', 'Back to segments'], records: '/segments', all: 'All segments', search: 'Search by segment name…',
    columns: { match: 'Match', rules: 'Rules' },
    open: null, details: true,
    intro: 'Every change made to your segments, newest first: who changed what, and when. Click View changes to see exactly what was different.',
  },
}
const cfg = computed(() => CONFIG[props.kind])

const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const records = ref([])
const error = ref('')
const loading = ref(true)
const opening = ref(0)
const expanded = ref(null) // history_id whose changes are shown (people, segments)
const viewing = ref(null) // a version that has no editor to open (deleted, an SMS, already sent...): shown as a read-only preview
const f = reactive({ search: '', id: '', action: '', from: '', to: '' })
const fDirty = computed(() => Object.values(f).some(Boolean))
const fClear = () => Object.keys(f).forEach((k) => (f[k] = ''))

const ACTIONS = { created: 'Created', updated: 'Updated', deleted: 'Deleted', restored: 'Restored', baseline: 'Existing record' }
const SOURCES = { dashboard: 'Dashboard', api: 'API', public: 'Public page / form', system: 'System' }
const nice = (k) => ({ ...COMMON, ...cfg.value.columns })[k] || k.replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase())
const what = (r) => [...new Set(Object.keys(r.changes || {}).map(nice))].join(' · ')
// columns that are on / off: shown as Yes / No instead of 1 / 0
const YES_NO = ['active', 'published', 'is_subscribed', 'save_person', 'subscribe', 'autoreply']
const show = (v, k) => (v === null || v === undefined || v === '' ? '—' : YES_NO.includes(k) ? (v === 1 || v === '1' || v === true ? 'Yes' : 'No') : typeof v === 'object' ? JSON.stringify(v) : String(v))

async function load(page = 1) {
  try {
    const params = { type: props.kind, all: 1, page }
    for (const k in f) if (f[k]) params[k] = f[k]
    const { data } = await api.get('/history', { params })
    rows.value = data.data
    meta.value = data
    error.value = ''
  } catch (e) { error.value = errorMessage(e) }
  loading.value = false
}

async function open(r) {
  if (cfg.value.details) { expanded.value = expanded.value === r.history_id ? null : r.history_id; return }
  opening.value = r.history_id
  error.value = ''
  try {
    const entry = (await api.get('/history/' + props.kind + '/entry/' + r.history_id)).data
    const to = cfg.value.open(r, entry)
    if (to) { router.push(to); return }
    if (cfg.value.preview) viewing.value = r // no editor to open (deleted, an SMS, already sent, or from before the page builder): a read-only preview
    else error.value = 'This ' + cfg.value.noun.toLowerCase() + ' has been deleted, so its editor cannot be opened.'
  } catch (e) { error.value = errorMessage(e) }
  opening.value = 0
}

let t
watch(f, () => { clearTimeout(t); t = setTimeout(() => load(1), 300) })
onMounted(async () => {
  if (cfg.value.records) {
    try { const d = (await api.get(cfg.value.records)).data; records.value = Array.isArray(d) ? d : d.data || [] } catch (e) { /* the record filter just stays empty */ }
  }
  load()
})
</script>

<template>
  <div class="head">
    <h1>{{ cfg.title }} <small>({{ meta.total }})</small></h1>
    <router-link :to="cfg.back[0]" class="btn ghost">{{ cfg.back[1] }}</router-link>
  </div>
  <p class="intro">{{ cfg.intro }}</p>
  <div v-if="error" class="alert err">{{ error }}</div>

  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="f.search" type="search" :placeholder="cfg.search" />
    <select v-if="cfg.all" v-model="f.id"><option value="">{{ cfg.all }}</option><option v-for="p in records" :key="p.id" :value="p.id">{{ p.name }}</option></select>
    <select v-model="f.action"><option value="">All changes</option><option value="created">Created</option><option value="updated">Updated</option><option value="deleted">Deleted</option><option value="restored">Restored</option></select>
    <label>From <DateInput v-model="f.from" /></label>
    <label>To <DateInput v-model="f.to" /></label>
  </FilterBar>

  <div class="card">
    <p v-if="loading" class="empty">Loading…</p>
    <p v-else-if="!rows.length" class="empty">{{ fDirty ? 'No changes match these filters.' : 'No changes have been recorded yet.' }}</p>
    <table v-else>
      <thead><tr><th>When</th><th>{{ cfg.noun }}</th><th>What happened</th><th>Changed by</th><th>What changed</th><th class="act">{{ cfg.details ? 'Details' : 'Version' }}</th></tr></thead>
      <template v-for="r in rows" :key="r.history_id">
        <tr>
          <td style="white-space: nowrap">{{ $dt(r.changed_at) }}</td>
          <td><b>{{ r.record_label || '—' }}</b></td>
          <td><span class="badge" :class="'a-' + r.change_action">{{ ACTIONS[r.change_action] || r.change_action }}</span></td>
          <td>{{ r.changed_by_name || 'System' }}<br /><small>{{ SOURCES[r.change_source] || r.change_source }}</small></td>
          <td class="what">{{ what(r) || '—' }}</td>
          <td class="act">
            <button type="button" class="btn ghost sm" :disabled="opening === r.history_id" @click="open(r)">
              {{ cfg.details ? (expanded === r.history_id ? 'Hide changes' : 'View changes') : opening === r.history_id ? 'Opening…' : 'View this version' }}
            </button>
          </td>
        </tr>
        <tr v-if="cfg.details && expanded === r.history_id" class="detail">
          <td colspan="6">
            <p v-if="!Object.keys(r.changes || {}).length" class="none">Nothing to show for this entry.</p>
            <div v-for="(c, k) in r.changes" :key="k" class="chg">
              <b>{{ nice(k) }}</b>
              <template v-if="['created', 'baseline'].includes(r.change_action)"><span class="new">{{ show(c.new, k) }}</span></template>
              <template v-else><span class="old">{{ show(c.old, k) }}</span> &rarr; <span class="new">{{ show(c.new, k) }}</span></template>
            </div>
          </td>
        </tr>
      </template>
    </table>
    <Pager :meta="meta" @page="load" />
  </div>

  <HistoryVersion v-if="viewing" :type="kind" :history-id="viewing.history_id" :title="viewing.record_label" @close="viewing = null" />
</template>

<style scoped>
.what { color: #4b5563; font-size: 13px; }
.badge.a-created, .badge.a-restored, .badge.a-baseline { background: #d1fae5; color: #065f46; }
.badge.a-updated { background: #dbeafe; color: #1e40af; }
.badge.a-deleted { background: #fee2e2; color: #991b1b; }
.detail td { background: #f7f8fc; padding: 8px 20px 12px; }
.chg { font-size: 13px; line-height: 1.8; word-break: break-word; }
.chg b { margin-right: 8px; }
.old { background: #fee2e2; color: #991b1b; border-radius: 3px; padding: 0 5px; text-decoration: line-through; }
.new { background: #d1fae5; color: #065f46; border-radius: 3px; padding: 0 5px; }
.none { margin: 0; color: #6b7280; font-size: 13px; }
</style>
