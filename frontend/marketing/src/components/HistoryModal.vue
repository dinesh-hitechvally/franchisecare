<script setup>
// History as a timeline, newest first, grouped by day: every saved copy, who changed it, and what changed (old → new).
// With an `id` it is the history of that one row; without one it is the timeline of every row of that kind (e.g. all pages).
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api, { errorMessage } from '../api'
import { fmtDate, fmtDateTime } from '../format'
import Modal from './Modal.vue'
import HistoryVersion from './HistoryVersion.vue'
import PageDesigner from './PageDesigner.vue'
import EmailDesigner from './EmailDesigner.vue'

const props = defineProps({ type: String, id: [Number, String], title: String })
const emit = defineEmits(['close'])
const router = useRouter()

const whole = computed(() => props.id === undefined || props.id === null || props.id === '')
const rows = ref([])
const loading = ref(true)
const more = ref(false)
const page = ref(1)
const viewing = ref(null) // a plain-text copy (an SMS) in the small read-only viewer
const copy = ref(null) // { kind: 'page' | 'email', entry, forms } a saved copy opened in the real editor, read-only
const opening = ref(0) // history_id being opened
const forms = ref(null)
// records whose saved copy has content worth previewing
const VIEWABLE = ['template', 'campaign', 'landing_page', 'form']
// where that record is edited: the editor opens on that version, with History open in its side panel
const EDITOR = {
  landing_page: (r) => '/pages?edit=' + r.row_id + '&history=' + r.history_id,
  template: (r) => '/templates?edit=' + r.row_id + '&history=' + r.history_id,
  form: (r) => '/forms/' + r.row_id + '?history=' + r.history_id,
  campaign: (r) => '/campaigns/' + r.row_id + '?history=' + r.history_id,
}
const error = ref('')

const ACTIONS = { created: 'Created', updated: 'Updated', deleted: 'Deleted', restored: 'Restored', purged: 'Removed for good', baseline: 'Existing record' }
const SOURCES = { dashboard: 'Dashboard', api: 'API', public: 'Public page / form', system: 'System' }
const KINDS = { list_membership: 'List', person_tag: 'Tag' }
const label = (k) => k.replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase())
const show = (v) => (v === null || v === undefined || v === '' ? '—' : v === 1 || v === '1' ? v : String(v))

// one heading per day, so the list reads as a timeline
const days = computed(() => {
  const out = []
  for (const r of rows.value) {
    const day = fmtDate(r.changed_at) // the person's own day, the same one the times beside it use
    if (!out.length || out[out.length - 1].day !== day) out.push({ day, items: [] })
    out[out.length - 1].items.push(r)
  }
  return out
})

async function load() {
  loading.value = page.value === 1
  try {
    if (whole.value) {
      const { data } = await api.get('/history', { params: { type: props.type, all: 1, page: page.value } })
      rows.value = rows.value.concat(data.data)
      more.value = data.current_page < data.last_page
    } else {
      rows.value = (await api.get(`/history/${props.type}/${props.id}`)).data
    }
  } catch (e) { error.value = errorMessage(e) }
  loading.value = false
}
const next = () => { page.value++; load() }

// A saved copy opens in the same editor you edit it in, but read-only. An SMS is plain text, so it gets the small viewer.
async function open(r) {
  opening.value = r.history_id
  error.value = ''
  try {
    const entry = (await api.get(`/history/${r.type}/entry/${r.history_id}`)).data
    // pages, email templates and forms: go to their editor (an SMS, a campaign, or a record that was deleted keeps the read-only view below)
    const canOpen = { landing_page: entry.data.design?.kind === 'page', template: entry.data.type !== 'sms', form: true, campaign: entry.data.type === 'email' && ['draft', 'scheduled'].includes(entry.current_status) }[r.type]
    if (entry.exists && canOpen) { emit('close'); router.push(EDITOR[r.type](r)); return }
    if (r.type === 'form') { error.value = 'This form has been deleted, so its editor cannot be opened.'; opening.value = 0; return }
    if (r.type === 'landing_page' && entry.data.design?.kind !== 'page') {
      viewing.value = r // made before the page builder existed: nothing to open in the editor, show its HTML
    } else if (r.type === 'landing_page') {
      if (forms.value === null) forms.value = (await api.get('/forms').catch(() => ({ data: [] }))).data // so form sections can show their form's name
      copy.value = { kind: 'page', entry, forms: forms.value }
    } else if (entry.data.type === 'sms') {
      viewing.value = r
    } else {
      copy.value = { kind: 'email', entry, named: r.type === 'template' }
    }
  } catch (e) { error.value = errorMessage(e) }
  opening.value = 0
}
const note = (e) => `${ACTIONS[e.change_action] || e.change_action} on ${fmtDateTime(e.changed_at)}${e.changed_by_name ? ' by ' + e.changed_by_name : ''}.`
onMounted(load)
</script>

<template>
  <Modal :title="`History${title ? ' — ' + title : ''}`" wide @close="$emit('close')">
    <p v-if="loading" class="empty">Loading…</p>
    <p v-else-if="error" class="alert err">{{ error }}</p>
    <p v-else-if="!rows.length" class="empty">No changes have been recorded {{ whole ? 'yet' : 'for this row yet' }}.</p>
    <div v-else class="scroll">
      <section v-for="g in days" :key="g.day">
        <h4 class="day">{{ g.day }}</h4>
        <ol class="tl">
          <li v-for="r in g.items" :key="r.type + r.history_id">
            <div class="top">
              <span class="badge" :class="'a-' + r.change_action">{{ ACTIONS[r.change_action] || r.change_action }}</span>
              <b v-if="KINDS[r.type]"> {{ KINDS[r.type] }} · {{ r.record_label }}</b>
              <b v-else-if="whole"> {{ r.record_label }}</b>
              <button v-if="VIEWABLE.includes(r.type)" type="button" class="btn ghost sm view" :disabled="opening === r.history_id" @click="open(r)">{{ opening === r.history_id ? 'Opening…' : 'View this version' }}</button>
              <span class="when">{{ $dt(r.changed_at) }}</span>
            </div>
            <div class="who"><template v-if="r.changed_by_name">{{ r.changed_by_name }} · </template>{{ SOURCES[r.change_source] || r.change_source }}<template v-if="r.change_ip"> · {{ r.change_ip }}</template></div>
            <div v-for="(c, k) in r.changes" :key="k" class="chg">
              <b>{{ label(k) }}</b>
              <template v-if="['created', 'baseline'].includes(r.change_action)"><span class="new">{{ show(c.new) }}</span></template>
              <template v-else><span class="old">{{ show(c.old) }}</span> → <span class="new">{{ show(c.new) }}</span></template>
            </div>
          </li>
        </ol>
      </section>
      <p v-if="more" style="text-align: center"><button type="button" class="btn ghost sm" @click="next">Show older changes</button></p>
    </div>
    <PageDesigner v-if="copy && copy.kind === 'page'" readonly :note="note(copy.entry)" :forms="copy.forms"
      :initial="{ name: copy.entry.data.name, slug: copy.entry.data.slug, title: copy.entry.data.title, published: false, design: copy.entry.data.design }" @close="copy = null" />
    <EmailDesigner v-if="copy && copy.kind === 'email'" readonly :show-name="copy.named" :no-subject="false" :note="note(copy.entry)"
      :initial="{ name: copy.entry.data.name, subject: copy.entry.data.subject, design: copy.entry.data.design, html: copy.entry.data.body, attachments: copy.entry.data.attachments || [] }" @close="copy = null" />
    <HistoryVersion v-if="viewing" :type="viewing.type" :history-id="viewing.history_id" :title="whole ? viewing.record_label : title" @close="viewing = null" />
    <p class="note">Each entry is a saved copy of the row at that moment. Secrets such as passwords are never copied, and history cannot be edited or removed.</p>
  </Modal>
</template>

<style scoped>
.scroll { max-height: 62vh; overflow: auto; padding-right: 4px; }
.day { margin: 14px 0 8px; font-size: 13px; color: #3f51b5; font-weight: 600; }
.day:first-child { margin-top: 0; }
.tl { list-style: none; margin: 0; padding: 0; }
.tl li { border-left: 3px solid #c5cae9; padding: 2px 0 14px 14px; margin-left: 4px; }
.top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.view { margin-left: 4px; }
.when { margin-left: auto; color: #6b7280; font-size: 13px; }
.who { color: #6b7280; font-size: 13px; margin: 2px 0 6px; }
.chg { font-size: 13px; line-height: 1.7; word-break: break-word; }
.chg b { margin-right: 6px; }
.old { background: #fee2e2; color: #991b1b; border-radius: 3px; padding: 0 5px; text-decoration: line-through; }
.new { background: #d1fae5; color: #065f46; border-radius: 3px; padding: 0 5px; }
.badge.a-created, .badge.a-restored, .badge.a-baseline { background: #d1fae5; color: #065f46; }
.badge.a-updated { background: #dbeafe; color: #1e40af; }
.badge.a-deleted, .badge.a-purged { background: #fee2e2; color: #991b1b; }
.note { color: #6b7280; font-size: 12px; margin: 10px 0 0; }
</style>
