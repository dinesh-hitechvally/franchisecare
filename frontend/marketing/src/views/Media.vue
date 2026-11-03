<script setup>
// The media library: upload once, then pick from it (company logo, …).
import { ref, reactive, computed, onMounted, watch } from 'vue'
import api, { errorMessage } from '../api'
import FilterBar from '../components/FilterBar.vue'
import DateInput from '../components/DateInput.vue'
import Modal from '../components/Modal.vue'
import Pager from '../components/Pager.vue'
import OnlineImages from '../components/OnlineImages.vue'
import HistoryModal from '../components/HistoryModal.vue'

const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const f = reactive({ search: '', kind: '', from: '', to: '' })
const tab = ref('all') // all | uploaded | online (saved from free sources) | find (search free images)
const TABS = [['all', 'All'], ['uploaded', 'Uploaded'], ['online', 'From free sources'], ['find', 'Find free images']]
const fDirty = computed(() => Object.values(f).some(Boolean))
const fClear = () => Object.keys(f).forEach((k) => (f[k] = ''))
const msg = ref('')
const error = ref('')
const uploading = ref([]) // { name, state: 'uploading' | 'done' | 'failed', error }
const dragging = ref(false)
const at = ref(-1) // index (in rows) of the file shown in the details window
const cur = computed(() => rows.value[at.value] || null)
const rn = reactive({ name: '', alt: '', description: '' })
const saving = ref(false)
const historyOf = ref(null)

const ACCEPT = '.png,.jpg,.jpeg,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv,.txt,.zip'
const size = (n) => (n >= 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB')
const ext = (m) => (m.file_name.split('.').pop() || '').toUpperCase()

async function load(page = 1) {
  const params = { page }
  if (tab.value === 'uploaded' || tab.value === 'online') params.origin = tab.value
  for (const k in f) if (f[k]) params[k] = f[k]
  const { data } = await api.get('/media', { params })
  rows.value = data.data
  meta.value = data
}

async function send(files) {
  msg.value = error.value = ''
  for (const file of files) {
    const item = reactive({ name: file.name, state: 'uploading', error: '' })
    uploading.value.push(item)
    const body = new FormData()
    body.append('file', file)
    try {
      await api.post('/media', body)
      item.state = 'done'
    } catch (e) { item.state = 'failed'; item.error = errorMessage(e) }
  }
  const ok = uploading.value.filter((u) => u.state === 'done').length
  if (ok) msg.value = `${ok} file${ok === 1 ? '' : 's'} uploaded.`
  setTimeout(() => (uploading.value = uploading.value.filter((u) => u.state === 'failed')), 2500)
  load(1)
}
const pick = (e) => { send([...e.target.files]); e.target.value = '' }
const drop = (e) => { dragging.value = false; send([...e.dataTransfer.files]) }

// Attachment details window (like WordPress): big preview, file facts, editable name / description, previous & next.
function show(i) { at.value = i; Object.assign(rn, { name: cur.value.name, alt: cur.value.alt || '', description: cur.value.description || '' }) }
const openDetails = (m) => show(rows.value.findIndex((r) => r.id === m.id))
const step = (d) => { const j = at.value + d; if (j >= 0 && j < rows.value.length) show(j) }
const dirty = computed(() => cur.value && (rn.name !== cur.value.name || rn.alt !== (cur.value.alt || '') || rn.description !== (cur.value.description || '')))
async function saveDetails() {
  saving.value = true
  error.value = ''
  try { Object.assign(cur.value, (await api.put(`/media/${cur.value.id}`, rn)).data); msg.value = 'Saved.' } catch (e) { error.value = errorMessage(e) } finally { saving.value = false }
}
async function removeDetails() {
  const m = cur.value
  if (!confirm(`Delete "${m.name}"? Its link stops working.`)) return
  msg.value = error.value = ''
  try { await api.delete(`/media/${m.id}`); at.value = -1; msg.value = 'Deleted.'; load(meta.value.current_page) } catch (e) { error.value = errorMessage(e) }
}
async function remove(m) {
  if (!confirm(`Delete "${m.name}"? Its link stops working.`)) return
  msg.value = error.value = ''
  try { await api.delete(`/media/${m.id}`); msg.value = 'Deleted.'; load(meta.value.current_page) } catch (e) { error.value = errorMessage(e) }
}
const openFile = (m) => window.open(m.url, '_blank', 'noopener')
async function copyLink(m) {
  try { await navigator.clipboard.writeText(m.url); msg.value = 'Link copied.' } catch (e) { error.value = 'Could not copy: ' + m.url }
}

function added() {
  msg.value = 'Added to your media library.'
  error.value = ''
  load(1)
}
function setTab(k) { tab.value = k; msg.value = error.value = ''; if (k !== 'find') load(1) }
const isOnline = (m) => m.source && m.source !== 'upload'

let t
watch(f, () => { clearTimeout(t); t = setTimeout(() => load(1), 300) })
onMounted(() => load())
</script>

<template>
  <div class="head">
    <h1>Media <small>({{ meta.total }})</small></h1>
    <label class="btn" style="cursor: pointer">Upload files<input type="file" multiple hidden :accept="ACCEPT" @change="pick" /></label>
  </div>
  <p class="intro">Upload images and files once, or add free online photos, then choose them wherever they are needed, for example as your company logo in <router-link to="/settings/company">Settings → Company profile</router-link>.</p>

  <div v-if="msg" class="alert ok">{{ msg }}</div>
  <div v-if="error" class="alert err">{{ error }}</div>

  <div class="tabs">
    <button v-for="[k, label] in TABS" :key="k" type="button" :class="{ on: tab === k }" @click="setTab(k)">{{ label }}</button>
  </div>

  <div v-if="tab === 'find'" class="card"><OnlineImages action-label="Add to library" @added="added" /></div>

  <template v-else>
  <div v-if="tab !== 'online'" class="drop" :class="{ on: dragging }" @dragover.prevent="dragging = true" @dragleave="dragging = false" @drop.prevent="drop">
    Drop files here to upload them <small>(images up to 8 MB: PNG, JPG, GIF, WebP · documents: PDF, Office, CSV, TXT, ZIP)</small>
  </div>
  <ul v-if="uploading.length && tab !== 'online'" class="ups">
    <li v-for="(u, i) in uploading" :key="i" :class="u.state"><span>{{ u.name }}</span><b v-if="u.state === 'uploading'">Uploading…</b><b v-else-if="u.state === 'done'">✓ Done</b><b v-else>{{ u.error }}</b></li>
  </ul>

  <FilterBar :dirty="fDirty" @clear="fClear">
    <input v-model="f.search" type="search" placeholder="Search by name…" />
    <select v-if="tab !== 'online'" v-model="f.kind"><option value="">Images and documents</option><option value="image">Images</option><option value="document">Documents</option></select>
    <label>From <DateInput v-model="f.from" /></label>
    <label>To <DateInput v-model="f.to" /></label>
  </FilterBar>

  <div class="card">
    <p v-if="!rows.length" class="empty">{{ fDirty ? 'Nothing matches these filters.' : tab === 'online' ? 'No free online images saved yet. Use "Find free images" to add some.' : 'No files yet. Upload your first one above.' }}</p>
    <div v-else class="grid-m">
      <div v-for="m in rows" :key="m.id" class="m">
        <div class="thumb click" @click="openDetails(m)">
          <img v-if="m.kind === 'image'" :src="m.url" :alt="m.alt || m.name" loading="lazy" />
          <span v-else class="doc">{{ ext(m) }}</span>
        </div>
        <div class="info">
          <div class="nm click" :title="m.file_name" @click="openDetails(m)">{{ m.name }}</div>
          <small>{{ size(m.size) }}<template v-if="m.width"> · {{ m.width }}×{{ m.height }}</template></small>
          <small class="when">{{ $d(m.created_at) }}</small>
          <small v-if="isOnline(m)" class="cr"><span class="src">{{ m.source }}</span> <a v-if="m.credit_url || m.source_url" :href="m.credit_url || m.source_url" target="_blank" rel="noopener noreferrer">{{ m.credit || 'Source' }}</a><template v-else>{{ m.credit }}</template><template v-if="m.license"> · {{ m.license }}</template></small>
          <small v-else class="cr"><span class="src">uploaded</span> {{ m.creator?.name || 'Unknown' }} · Own file</small>
        </div>
        <div class="btns">
          <button type="button" class="btn sm" @click="openDetails(m)">Details</button>
          <button type="button" class="btn ghost sm" @click="historyOf = m">History</button>
          <button type="button" class="btn sm danger" @click="remove(m)">Delete</button>
        </div>
      </div>
    </div>
    <Pager :meta="meta" @page="load" />
  </div>

  </template>

  <Modal v-if="cur" title="File details" wide @close="at = -1">
    <div class="dt">
      <div class="dt-pre">
        <img v-if="cur.kind === 'image'" :src="cur.url" :alt="cur.alt || cur.name" />
        <span v-else class="doc big">{{ ext(cur) }}</span>
      </div>
      <div class="dt-side">
        <ul class="facts">
          <li><b>File name:</b> {{ cur.file_name }}</li>
          <li><b>Uploaded on:</b> {{ $dt(cur.created_at) }}</li>
          <li><b>File type:</b> {{ cur.mime }}</li>
          <li><b>File size:</b> {{ size(cur.size) }}</li>
          <li v-if="cur.width"><b>Dimensions:</b> {{ cur.width }} × {{ cur.height }} pixels</li>
          <li v-if="!isOnline(cur)"><b>Source:</b> Uploaded<template v-if="cur.creator"> by {{ cur.creator.name }}</template> · Own file</li>
          <li v-if="isOnline(cur)"><b>Source:</b> <span style="text-transform: capitalize">{{ cur.source }}</span><template v-if="cur.credit"> · <a v-if="cur.credit_url || cur.source_url" :href="cur.credit_url || cur.source_url" target="_blank" rel="noopener noreferrer">{{ cur.credit }}</a><template v-else>{{ cur.credit }}</template></template><template v-if="cur.license"> · {{ cur.license }}</template></li>
        </ul>
        <form @submit.prevent="saveDetails">
          <div class="field"><label>Name</label><input v-model="rn.name" required maxlength="255" /></div>
          <div v-if="cur.kind === 'image'" class="field"><label>Alt text <small>(read out for people who cannot see the image)</small></label><input v-model="rn.alt" maxlength="255" /></div>
          <div class="field"><label>Description</label><textarea v-model="rn.description" rows="4" maxlength="5000" placeholder="Notes about this file"></textarea></div>
          <div class="field"><label>File URL</label><div class="row"><input :value="cur.url" readonly style="flex: 1" @focus="$event.target.select()" /><button type="button" class="btn ghost sm" @click="copyLink(cur)">Copy link</button></div></div>
          <div class="row">
            <button class="btn" :disabled="saving || !dirty">{{ saving ? 'Saving…' : 'Save changes' }}</button>
            <button type="button" class="btn ghost" @click="openFile(cur)">View file</button>
            <button type="button" class="btn ghost" @click="historyOf = cur">History</button>
            <button type="button" class="btn danger" style="margin-left: auto" @click="removeDetails">Delete</button>
          </div>
        </form>
      </div>
    </div>
    <div class="dt-nav">
      <button type="button" class="btn ghost sm" :disabled="at <= 0" @click="step(-1)">&larr; Previous</button>
      <small>{{ at + 1 }} of {{ rows.length }}</small>
      <button type="button" class="btn ghost sm" :disabled="at >= rows.length - 1" @click="step(1)">Next &rarr;</button>
    </div>
  </Modal>
  <HistoryModal v-if="historyOf" type="media" :id="historyOf.id" :title="historyOf.name" @close="historyOf = null" />
</template>

<style scoped>
.click { cursor: pointer; }
.dt { display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr); gap: 22px; }
.dt-pre { background: repeating-conic-gradient(#f3f4f8 0% 25%, #fff 0% 50%) 50% / 16px 16px; border-radius: 8px; display: grid; place-items: center; min-height: 260px; padding: 12px; }
.dt-pre img { max-width: 100%; max-height: 62vh; object-fit: contain; }
.doc.big { font-size: 26px; padding: 26px 34px; }
.facts { list-style: none; margin: 0 0 16px; padding: 0 0 14px; border-bottom: 1px solid #eceef7; font-size: 13px; color: #4b5563; }
.facts li { padding: 2px 0; word-break: break-all; } .facts b { color: #111827; }
.dt-nav { display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 12px; border-top: 1px solid #eceef7; }
@media (max-width: 760px) { .dt { grid-template-columns: 1fr; } }
.tabs { display: flex; gap: 4px; border-bottom: 1px solid #e0e3f0; margin-bottom: 16px; flex-wrap: wrap; }
.tabs button { background: none; border: 0; border-bottom: 2px solid transparent; padding: 9px 14px; font: inherit; color: #6b7280; cursor: pointer; margin-bottom: -1px; }
.tabs button:hover { color: #3f51b5; }
.tabs button.on { color: #3f51b5; border-bottom-color: #3f51b5; font-weight: 600; }
.cr { font-size: 12px; }
.cr a { color: #3f51b5; }
.src { background: #e8eaf6; color: #3f51b5; border-radius: 3px; padding: 0 5px; text-transform: capitalize; }
.drop { border: 2px dashed #c5cae9; border-radius: 8px; padding: 22px; text-align: center; color: #6b7280; background: #fafbff; margin-bottom: 14px; }
.drop.on { border-color: #3f51b5; background: #eef0fb; color: #3f51b5; }
.drop small { display: block; margin-top: 4px; }
.ups { list-style: none; margin: 0 0 14px; padding: 0; font-size: 13px; }
.ups li { display: flex; justify-content: space-between; gap: 12px; padding: 4px 0; color: #6b7280; }
.ups li.done b { color: #2e7d32; } .ups li.failed b { color: #c62828; font-weight: 500; }
.grid-m { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
.m { display: flex; flex-direction: column; border: 1px solid #e0e3f0; border-radius: 8px; overflow: hidden; background: #fff; }
.thumb { height: 130px; background: repeating-conic-gradient(#f3f4f8 0% 25%, #fff 0% 50%) 50% / 16px 16px; display: grid; place-items: center; flex: none; }
.thumb img { max-width: 100%; max-height: 130px; object-fit: contain; }
.doc { background: #e8eaf6; color: #3f51b5; font-weight: 700; padding: 14px 18px; border-radius: 6px; font-size: 15px; }
.info { padding: 10px 12px 12px; flex: 1; }
.nm { font-weight: 600; font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.info small { color: #6b7280; display: block; line-height: 1.5; }
.btns { display: flex; flex-wrap: nowrap; gap: 6px; padding: 0 12px 12px; margin-top: auto; flex: none; }
.btns .btn { flex: 1; padding-left: 6px; padding-right: 6px; white-space: nowrap; }
</style>
