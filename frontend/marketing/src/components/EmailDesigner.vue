<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import api, { errorMessage } from '../api'
import Icon from './Icon.vue'
import Modal from './Modal.vue'
import AttachmentsField from './AttachmentsField.vue'
import ImagePicker from './ImagePicker.vue'
import RichText from './RichText.vue'
import CodeEditor from './CodeEditor.vue'
import HistoryTimeline from './HistoryTimeline.vue'
import { fmtDateTime } from '../format'
import { BLOCKS, LAYOUTS, FONTS, defaultSettings, newBlock, renderBlock, renderEmail, designFromHtml, designHtml, uid } from '../builder/blocks'

// v-model-less: pass `initial` ({ name, subject, design, html }), receive `save` / `close`.
const props = defineProps({ initial: Object, showName: Boolean, saving: Boolean, noSubject: Boolean, readonly: Boolean, note: String, historyId: [Number, String], startVersion: Number, savedTick: { type: Number, default: 0 }, historyType: { type: String, default: 'template' } })
const emit = defineEmits(['save', 'close', 'version'])

const init = props.initial || {}
const meta = reactive({ name: init.name || '', subject: init.subject || '' })
const attachments = ref([...(init.attachments || [])])
const attachOpen = ref(false)
// why Save is disabled, shown next to the button and as red outlines on the empty fields
const missing = computed(() => [
  ...(props.showName && !meta.name.trim() ? ['template name'] : []),
  ...(!props.noSubject && !meta.subject.trim() ? ['subject line'] : []),
])
const design = ref(
  init.design?.blocks ? JSON.parse(JSON.stringify(init.design))
    : init.html ? designFromHtml(init.html)
      : null,
)
if (design.value) design.value.settings = { ...defaultSettings(), ...(design.value.settings || {}) }
const layoutPreviews = LAYOUTS.map((l) => ({ ...l, html: renderEmail(l.make()) })) // built once, not on every render
const picking = ref(!design.value)

// ---- saved ("My") layouts ----
const myLayouts = ref([])
const saveDlg = ref(false)
const layoutName = ref('')
const layoutBusy = ref(false)
const layoutMsg = ref('')
const loadLayouts = async () => {
  try {
    myLayouts.value = (await api.get('/layouts')).data.map((l) => ({ ...l, html: designHtml(l.design) }))
  } catch (e) { /* gallery still works with the built-in layouts */ }
}
onMounted(loadLayouts)

// forms the "Form" block can point to
const forms = ref([])
onMounted(async () => { try { forms.value = (await api.get('/forms')).data } catch (e) { /* the block just offers none */ } })
function pickForm(b, id) {
  const f = forms.value.find((x) => String(x.id) === String(id))
  b.form_id = f ? f.id : ''
  b.form_url = f ? f.url : ''
}

function useSaved(l) {
  const d = JSON.parse(JSON.stringify(l.design))
  d.blocks = (d.blocks || []).map((b) => ({ ...b, id: uid() })) // fresh ids so the copy is independent
  design.value = { ...d, settings: { ...defaultSettings(), ...(d.settings || {}) } }
  picking.value = false
  selected.value = null
}
function openSaveDlg() {
  layoutName.value = meta.name || ''
  layoutMsg.value = ''
  saveDlg.value = true
}
async function saveLayout() {
  layoutBusy.value = true
  layoutMsg.value = ''
  try {
    await api.post('/layouts', { name: layoutName.value.trim(), design: JSON.parse(JSON.stringify(design.value)) })
    await loadLayouts()
    saveDlg.value = false
    flash.value = 'Saved to My layouts'
    setTimeout(() => (flash.value = ''), 2500)
  } catch (e) {
    layoutMsg.value = errorMessage(e)
  } finally {
    layoutBusy.value = false
  }
}
async function deleteLayout(l) {
  if (!confirm('Delete layout "' + l.name + '"? Emails already made from it are not affected.')) return
  await api.delete('/layouts/' + l.id)
  loadLayouts()
}
const flash = ref('') // layout gallery first for new emails
const selected = ref(null) // block id, or null = design settings
const device = ref('desktop')
const tab = ref('view') // centre tabs: 'view' | 'html'
const copied = ref(false)
async function copyHtml() {
  try { await navigator.clipboard.writeText(html.value); copied.value = true; setTimeout(() => (copied.value = false), 1500) } catch (e) { /* clipboard blocked */ }
}
// ---- history: the header button swaps the side panel for a timeline of this record; picking a point shows that saved copy here, read-only
const historyOpen = ref(false)
// the parent bumps savedTick after a save that kept the editor open (History was open): say so; the timeline reloads itself
watch(() => props.savedTick, () => { flash.value = 'Saved'; setTimeout(() => (flash.value = ''), 2500) })
const viewing = ref(null) // the saved copy being shown, or null for the version you are editing
let backup = null // the version being edited, kept while a past one is shown
const locked = computed(() => props.readonly || !!viewing.value)
watch(viewing, (v) => emit('version', v ? v.history_id : null)) // so the address can say which version is on show
const canHistory = computed(() => !!props.historyId && !props.readonly)
const WHAT = { created: 'Created', updated: 'Updated', deleted: 'Deleted', restored: 'Restored', baseline: 'Existing record' }
const noteText = computed(() => (viewing.value ? (WHAT[viewing.value.change_action] || viewing.value.change_action) + ' on ' + fmtDateTime(viewing.value.changed_at) + (viewing.value.changed_by_name ? ' by ' + viewing.value.changed_by_name : '') + '.' : props.note))
function swapTo(next) {
  design.value = next.design
  meta.name = next.name
  meta.subject = next.subject
  attachments.value = next.attachments
  selected.value = null
}
function viewCopy(entry) {
  const d = entry.data
  if (!backup) backup = { design: design.value, name: meta.name, subject: meta.subject, attachments: attachments.value }
  const next = d.design?.blocks ? JSON.parse(JSON.stringify(d.design)) : d.body ? designFromHtml(d.body) : null // a hand-coded email has no blocks: open it as the editor does
  if (next) next.settings = { ...defaultSettings(), ...(next.settings || {}) }
  swapTo({ design: next, name: d.name || '', subject: d.subject || '', attachments: d.attachments || [] })
  viewing.value = entry
}
function backToCurrent() {
  if (!backup) return
  swapTo(backup)
  backup = null
  viewing.value = null
}
// opened from a history list: start with History open and that version shown
onMounted(async () => {
  if (!props.startVersion || !props.historyId) return
  try {
    historyOpen.value = true
    viewCopy((await api.get('/history/' + props.historyType + '/entry/' + props.startVersion)).data)
  } catch (e) { flash.value = errorMessage(e) }
})
function toggleHistory() {
  if (historyOpen.value) backToCurrent() // leaving history: back on the version you are editing
  historyOpen.value = !historyOpen.value
}
const imagePicker = ref(null) // { apply(img) }
const hover = ref(-1)

const s = computed(() => ({ ...defaultSettings(), ...(design.value?.settings || {}) }))
const blocks = computed(() => design.value?.blocks || [])
const current = computed(() => blocks.value.find((b) => b.id === selected.value))
// design.rawHtml set => the email is hand-coded in the HTML tab and the visual blocks are bypassed (but kept, so they can be restored)
const rawMode = computed(() => typeof design.value?.rawHtml === 'string')
const html = computed(() => (!design.value ? '' : rawMode.value ? design.value.rawHtml : renderEmail(design.value)))
// readable version for the code editor: line breaks + indentation (display only; whitespace between block tags is harmless)
function pretty(src) {
  const NL = String.fromCharCode(10)
  const open = /^<(html|head|body|table|tr|td|div|ul|ol|li)\b/i
  const close = /<\/(html|head|body|table|tr|td|div|ul|ol|li)>\s*$/i
  let depth = 0
  return src
    .replace(/(<\/?(?:html|head|body|table|tr|td|div|p|ul|ol|li|style|h[1-6])\b)/gi, NL + '$1')
    .split(NL).map((l) => l.trim()).filter(Boolean)
    .map((l) => {
      if (/^<\/(html|head|body|table|tr|td|div|ul|ol|li)\b/i.test(l)) depth = Math.max(0, depth - 1)
      const line = '  '.repeat(depth) + l
      if (open.test(l) && !close.test(l)) depth++
      return line
    }).join(NL)
}
const editorValue = computed(() => (rawMode.value ? html.value : pretty(html.value)))
function onCode(v) {
  if (!locked.value && design.value && v !== editorValue.value) design.value.rawHtml = v
}
function backToBlocks() {
  if (confirm('Discard your HTML edits and go back to the visual blocks?')) {
    delete design.value.rawHtml
    selected.value = null
  }
}
const canvasWidth = computed(() => (device.value === 'mobile' ? 375 : s.value.width))

function startFrom(layout) {
  design.value = layout.make()
  picking.value = false
  selected.value = null
}

// ---- block operations ----
function insertAt(block, i = blocks.value.length) {
  blocks.value.splice(i, 0, block)
  selected.value = block.id
}
const add = (type) => insertAt(newBlock(type), selectedIndex() + 1 || blocks.value.length)
const selectedIndex = () => blocks.value.findIndex((b) => b.id === selected.value)
function move(i, d) {
  const j = i + d
  if (j < 0 || j >= blocks.value.length) return
  blocks.value.splice(j, 0, blocks.value.splice(i, 1)[0])
}
function dup(i) {
  const copy = JSON.parse(JSON.stringify(blocks.value[i]))
  copy.id = uid()
  insertAt(copy, i + 1)
}
function remove(i) {
  blocks.value.splice(i, 1)
  selected.value = null
}

// ---- drag & drop (palette -> canvas, canvas reorder) ----
function dragNew(e, type) { e.dataTransfer.setData('text/plain', `new:${type}`); e.dataTransfer.effectAllowed = 'copy' }
function dragMove(e, i) { e.dataTransfer.setData('text/plain', `move:${i}`); e.dataTransfer.effectAllowed = 'move' }
function drop(e, i) {
  hover.value = -1
  const [kind, v] = (e.dataTransfer.getData('text/plain') || '').split(':')
  if (kind === 'new' && BLOCKS[v]) insertAt(newBlock(v), i)
  if (kind === 'move') {
    const from = Number(v)
    if (Number.isNaN(from) || from === i || from + 1 === i) return
    const [b] = blocks.value.splice(from, 1)
    blocks.value.splice(from < i ? i - 1 : i, 0, b)
  }
}

// ---- settings panel schema ----
const common = [['padY', 'Padding top/bottom', 'range', { min: 0, max: 80 }], ['padX', 'Padding left/right', 'range', { min: 0, max: 80 }], ['bg', 'Background', 'color']]
const ALIGN = [['left', 'Left'], ['center', 'Center'], ['right', 'Right']]
const FIELDS = {
  header: [['text', 'Text', 'text'], ['size', 'Font size', 'range', { min: 14, max: 40 }], ['align', 'Align', 'select', { options: ALIGN }], ['color', 'Text colour', 'color']],
  hero: [
    ['emoji', 'Emoji / icon', 'text'], ['eyebrow', 'Small label above', 'text'], ['title', 'Headline', 'text'], ['subtitle', 'Sub-headline', 'text'],
    ['size', 'Headline size', 'range', { min: 24, max: 72 }], ['color', 'Text colour', 'color'], ['subColor', 'Sub-text colour', 'color'],
    ['btnText', 'Button label (blank = no button)', 'text'], ['btnUrl', 'Button link', 'text'], ['btnBg', 'Button colour', 'color'], ['btnColor', 'Button text colour', 'color'],
    ['align', 'Align', 'select', { options: ALIGN }], ['image', 'Background image (optional)', 'image'],
  ],
  features: [['items', 'Columns', 'items']],
  coupon: [['label', 'Label', 'text'], ['code', 'Code', 'text'], ['note', 'Note', 'text'], ['boxBg', 'Box colour', 'color'], ['border', 'Border colour', 'color'], ['color', 'Code colour', 'color']],
  heading: [['text', 'Text', 'text'], ['size', 'Font size', 'range', { min: 14, max: 56 }], ['align', 'Align', 'select', { options: ALIGN }], ['color', 'Text colour', 'color']],
  text: [['html', 'Content', 'rich'], ['size', 'Font size', 'range', { min: 11, max: 28 }], ['color', 'Text colour', 'color'], ['align', 'Align', 'select', { options: ALIGN }]],
  image: [['src', 'Image', 'image'], ['alt', 'Alt text', 'text'], ['link', 'Link URL (optional)', 'text'], ['width', 'Width %', 'range', { min: 20, max: 100 }], ['align', 'Align', 'select', { options: ALIGN }], ['radius', 'Corner radius', 'range', { min: 0, max: 40 }], ['caption', 'Credit / caption', 'text']],
  button: [['text', 'Label', 'text'], ['url', 'Link URL', 'text'], ['btnBg', 'Button colour', 'color'], ['color', 'Text colour', 'color'], ['align', 'Align', 'select', { options: ALIGN }], ['radius', 'Corner radius', 'range', { min: 0, max: 40 }]],
  form: [['form_id', 'Form to open', 'form'], ['heading', 'Heading', 'text'], ['text', 'Text', 'text'], ['btnText', 'Button label', 'text'], ['btnBg', 'Button colour', 'color'], ['color', 'Button text colour', 'color'], ['boxBg', 'Box colour', 'color'], ['align', 'Align', 'select', { options: ALIGN }], ['radius', 'Corner radius', 'range', { min: 0, max: 40 }]],
  split: [['src', 'Image', 'image'], ['alt', 'Alt text', 'text'], ['side', 'Image side', 'select', { options: [['left', 'Left'], ['right', 'Right']] }], ['html', 'Text', 'rich']],
  divider: [['color', 'Colour', 'color'], ['thickness', 'Thickness', 'range', { min: 1, max: 10 }]],
  spacer: [['height', 'Height', 'range', { min: 8, max: 120 }]],
  social: [['links', 'Links', 'links']],
  footer: [['html', 'Content', 'rich'], ['color', 'Text colour', 'color']],
  html: [['html', 'HTML', 'textarea']],
}
const fields = computed(() => {
  const t = current.value?.type
  if (!t) return []
  return t === 'spacer' ? FIELDS[t] : [...FIELDS[t], ...common]
})

function chooseImage(block, key) {
  imagePicker.value = {
    apply(img) {
      block[key] = img.url
      if ('alt' in block) block.alt = block.alt || `Photo by ${img.author}`
      if (block.type === 'image') {
        block.caption = `Photo by <a href="${img.author_url}">${img.author}</a> on <a href="${img.link}">${img.source}</a>${img.license ? ` (${img.license.trim()})` : ''}`
      }
    },
  }
}
function pick(img) {
  imagePicker.value.apply(img)
  imagePicker.value = null
}

function save() {
  emit('save', { name: meta.name, subject: meta.subject, design: design.value, body: html.value, attachments: attachments.value, keepOpen: historyOpen.value && !!props.historyId })
}
</script>

<template>
  <div class="ed" :class="{ ro: locked }">
    <!-- top bar -->
    <header class="ed-top">
      <button class="ed-back" @click="emit('close')"><Icon name="back" :size="18" /> {{ readonly ? 'Back to history' : 'Back' }}</button>
      <input v-if="showName" v-model="meta.name" :class="{ miss: !meta.name.trim() }" class="ed-in" placeholder="Template name *" style="max-width: 240px" :readonly="locked" />
      <input v-if="!noSubject" v-model="meta.subject" :class="{ miss: !meta.subject.trim() }" class="ed-in" placeholder="Subject line * (merge tags allowed, e.g. Hi {{first_name}})" :readonly="locked" />
      <span v-if="locked" class="ro-pill">Read only · saved copy</span>
      <button v-if="viewing" class="btn ghost sm" @click="backToCurrent">Back to current version</button>
      <div class="seg" v-if="design">
        <button :class="{ on: device === 'desktop' }" title="Desktop" @click="device = 'desktop'"><Icon name="desktop" :size="18" /></button>
        <button :class="{ on: device === 'mobile' }" title="Mobile" @click="device = 'mobile'"><Icon name="mobile" :size="18" /></button>
      </div>
      <button v-if="canHistory" class="btn ghost sm" :class="{ hon: historyOpen }" @click="toggleHistory">History</button>
      <button v-else-if="design && !readonly && !historyId" class="btn ghost sm" disabled title="History starts once it has been saved for the first time">History</button>
      <button v-if="design && !locked" class="btn ghost sm" @click="picking = true">Change layout</button>
      <button v-if="design && !locked" class="btn ghost sm" @click="openSaveDlg">Save as layout</button>
      <button class="btn ghost sm" @click="attachOpen = true">&#128206; Attachments{{ attachments.length ? ` (${attachments.length})` : '' }}</button>
      <span v-if="flash" class="flash">{{ flash }}</span>
      <span v-if="!locked && design && missing.length" class="need">Add a {{ missing.join(' and a ') }} to save</span>
      <button v-if="!locked" class="btn" :disabled="!design || saving || missing.length > 0" @click="save">{{ saving ? 'Saving…' : historyOpen ? 'Save' : 'Save & close' }}</button>
    </header>

    <Modal v-if="attachOpen" title="Attachments" @close="attachOpen = false">
      <p class="hint" style="margin-top: 0">These files are attached to every email sent with this design.</p>
      <AttachmentsField v-model="attachments" :disabled="locked" />
      <button class="btn" style="margin-top: 14px" @click="attachOpen = false">Done</button>
    </Modal>

    <!-- layout gallery -->
    <div v-if="picking && !locked" class="gallery">
      <template v-if="myLayouts.length">
        <h2>My layouts</h2>
        <p class="muted">Layouts you saved with “Save as layout”.</p>
        <div class="g-grid">
          <div v-for="l in myLayouts" :key="l.id" class="g-card mine" role="button" tabindex="0" @click="useSaved(l)" @keydown.enter="useSaved(l)">
            <button class="g-del" title="Delete layout" @click.stop="deleteLayout(l)">&times;</button>
            <div class="g-thumb"><iframe :srcdoc="l.html" tabindex="-1" sandbox="" loading="lazy"></iframe></div>
            <span>{{ l.name }}</span>
          </div>
        </div>
      </template>

      <h2 :style="myLayouts.length ? 'margin-top: 36px' : ''">{{ myLayouts.length ? 'Ready-made layouts' : 'Choose a layout' }}</h2>
      <p class="muted">Pick a starting point — you can change everything afterwards.</p>
      <div class="g-grid">
        <button v-for="l in layoutPreviews" :key="l.id" class="g-card" @click="startFrom(l)">
          <div class="g-thumb"><iframe :srcdoc="l.html" tabindex="-1" sandbox="" loading="lazy"></iframe></div>
          <span>{{ l.name }}</span>
        </button>
      </div>
      <button v-if="design" class="btn ghost" style="margin-top: 20px" @click="picking = false">Keep my current design</button>
    </div>

    <div v-else class="ed-body">
      <!-- left: content blocks -->
      <aside class="ed-left">
        <div class="tabs"><button class="on">Content</button></div>
        <div class="palette">
          <div v-if="locked" class="ro-note"><b>Saved copy</b><p>{{ noteText }}</p><p>This is exactly how it was when it was saved. You can look around, switch device sizes, open the HTML and click a block to see its settings, but nothing can be changed.</p></div>
          <p v-else-if="rawMode" class="hint">Visual blocks are off while you hand-edit the HTML.</p>
          <p v-else class="hint">Drag a block onto the email, or click to add.</p>
          <div v-if="!locked" class="tiles" :class="{ off: rawMode }">
            <div v-for="(def, type) in BLOCKS" :key="type" class="pal" draggable="true" @dragstart="dragNew($event, type)" @click="add(type)">
              <Icon :name="def.icon" :size="26" /><span>{{ def.label }}</span>
            </div>
          </div>
        </div>
      </aside>

      <!-- centre: View / HTML -->
      <section class="ed-center">
        <div class="tabs c-tabs">
          <button :class="{ on: tab === 'view' }" @click="tab = 'view'">View</button>
          <button :class="{ on: tab === 'html' }" @click="tab = 'html'">HTML</button>
          <span style="flex: 1"></span>
          <button v-if="tab === 'html'" class="copy" @click="copyHtml">{{ copied ? 'Copied ✓' : 'Copy HTML' }}</button>
        </div>
        <div v-if="rawMode" class="raw-note">
          <span><b>Custom HTML mode.</b> You edited the code, so the visual blocks are switched off for this email.</span>
          <button v-if="!locked" class="btn sm" @click="backToBlocks">Back to visual blocks</button>
        </div>
        <div v-else-if="tab === 'html' && !locked" class="raw-note soft">Edit the code freely — typing here switches this email to custom HTML. Type <code>&lt;</code> for tag suggestions or <code>{{'{{'}}</code> for merge tags.</div>
        <div v-if="tab === 'html'" class="code-wrap"><CodeEditor :model-value="editorValue" :readonly="locked" @update:model-value="onCode" /></div>
        <div v-else-if="rawMode" class="ed-canvas raw-view"><iframe class="raw-frame" sandbox="" :srcdoc="html" :style="{ width: canvasWidth + 48 + 'px' }"></iframe></div>
      <main v-show="tab === 'view' && !rawMode" class="ed-canvas" @click.self="selected = null">
        <div class="cv-page" :style="{ background: s.bg, width: canvasWidth + 48 + 'px' }" @click.self="selected = null">
          <div class="cv-content" :style="{ background: s.contentBg, width: canvasWidth + 'px', fontFamily: s.font, borderRadius: s.radius + 'px', overflow: 'hidden' }">
            <div v-if="!blocks.length && !locked" class="dz-empty" :class="{ hov: hover === 0 }" @dragover.prevent="hover = 0" @dragleave="hover = -1" @drop.prevent="drop($event, 0)">Drag content here</div>
            <template v-for="(b, i) in blocks" :key="b.id">
              <div v-if="!locked" class="dz" :class="{ hov: hover === i }" @dragover.prevent="hover = i" @dragleave="hover = -1" @drop.prevent="drop($event, i)"></div>
              <div class="cv-block" :class="{ sel: selected === b.id, stack: device === 'mobile' }" :draggable="!locked"
                @dragstart="dragMove($event, i)" @click.prevent.stop="selected = b.id">
                <div class="cv-html" v-html="renderBlock(b, s, true)"></div>
                <div v-if="selected === b.id && !locked" class="cv-tools" @click.stop>
                  <span class="grip" title="Drag to move"><Icon name="drag" :size="16" /></span>
                  <button title="Move up" @click="move(i, -1)"><Icon name="up" :size="16" /></button>
                  <button title="Move down" @click="move(i, 1)"><Icon name="down" :size="16" /></button>
                  <button title="Duplicate" @click="dup(i)"><Icon name="copy" :size="16" /></button>
                  <button title="Delete" @click="remove(i)"><Icon name="trash" :size="16" /></button>
                </div>
              </div>
            </template>
            <div v-if="blocks.length && !locked" class="dz end" :class="{ hov: hover === blocks.length }" @dragover.prevent="hover = blocks.length" @dragleave="hover = -1" @drop.prevent="drop($event, blocks.length)"></div>
          </div>
        </div>
      </main>
      </section>

      <!-- right: settings -->
      <aside class="ed-right" :class="{ off: rawMode }">
        <HistoryTimeline v-if="historyOpen" :type="historyType" :id="historyId" :active-id="viewing ? viewing.history_id : null" :refresh="savedTick" :names="{ body: 'Design', design: 'Design' }" @pick="viewCopy" @current="backToCurrent" @close="toggleHistory" />
        <fieldset v-else :disabled="locked" class="fs">
        <template v-if="current">
          <div class="r-head"><b>{{ BLOCKS[current.type].label }}</b><button class="x" @click="selected = null">&times;</button></div>
          <div v-for="[k, label, kind, o] in fields" :key="current.id + k" class="field">
            <label>{{ label }}<span v-if="kind === 'range'" class="val"> {{ current[k] }}</span></label>
            <input v-if="kind === 'text'" v-model="current[k]" />
            <textarea v-else-if="kind === 'textarea'" v-model="current[k]" rows="8"></textarea>
            <RichText v-else-if="kind === 'rich'" v-model="current[k]" />
            <input v-else-if="kind === 'range'" v-model.number="current[k]" type="range" :min="o.min" :max="o.max" />
            <div v-else-if="kind === 'form'">
              <select :value="current[k]" @change="pickForm(current, $event.target.value)"><option value="">— Choose a form —</option><option v-for="f in forms" :key="f.id" :value="f.id">{{ f.name }}</option></select>
              <small class="hint">{{ forms.length ? 'Email apps can’t show a form inside an email, so readers get a button that opens the form on its own page.' : 'No forms yet — create one under Content → Forms.' }}</small>
            </div>
            <select v-else-if="kind === 'select'" v-model="current[k]"><option v-for="[v, l] in o.options" :key="v" :value="v">{{ l }}</option></select>
            <div v-else-if="kind === 'color'" class="row"><input v-model="current[k]" type="color" class="clr" /><input v-model="current[k]" placeholder="none" /></div>
            <div v-else-if="kind === 'image'">
              <img v-if="current[k]" :src="current[k]" class="thumb" />
              <button class="btn sm" type="button" @click="chooseImage(current, k)">Choose free image</button>
              <input v-model="current[k]" placeholder="…or paste an image URL" style="margin-top: 6px" />
            </div>
            <div v-else-if="kind === 'items'">
              <div v-for="(it, ii) in current.items" :key="ii" class="item-box">
                <div class="row"><input v-model="it.icon" style="width: 56px; text-align: center; font-size: 18px" title="Emoji" /><input v-model="it.title" placeholder="Title" /></div>
                <textarea v-model="it.text" rows="2" style="margin-top: 6px; font-family: inherit"></textarea>
              </div>
            </div>
            <div v-else-if="kind === 'links'">
              <div v-for="(l, li) in current.links" :key="li" class="row" style="margin-bottom: 6px">
                <input v-model="l.name" style="width: 38%" placeholder="Label" /><input v-model="l.url" placeholder="https://…" />
                <button class="x" type="button" @click="current.links.splice(li, 1)">&times;</button>
              </div>
              <button class="btn ghost sm" type="button" @click="current.links.push({ name: '', url: '' })">+ Add link</button>
            </div>
          </div>
        </template>

        <template v-else>
          <div class="r-head"><b>Email design</b></div>
          <div class="field"><label>Preview text (inbox snippet)</label><input v-model="design.settings.preheader" placeholder="Shown after the subject in the inbox" /></div>
          <div class="field"><label>Page background</label><div class="row"><input v-model="design.settings.bg" type="color" class="clr" /><input v-model="design.settings.bg" /></div></div>
          <div class="field"><label>Content background</label><div class="row"><input v-model="design.settings.contentBg" type="color" class="clr" /><input v-model="design.settings.contentBg" /></div></div>
          <div class="field"><label>Text colour</label><div class="row"><input v-model="design.settings.textColor" type="color" class="clr" /><input v-model="design.settings.textColor" /></div></div>
          <div class="field"><label>Link colour</label><div class="row"><input v-model="design.settings.linkColor" type="color" class="clr" /><input v-model="design.settings.linkColor" /></div></div>
          <div class="field"><label>Content width <span class="val">{{ s.width }}px</span></label><input v-model.number="design.settings.width" type="range" min="480" max="700" step="10" /></div>
          <div class="field"><label>Corner radius <span class="val">{{ s.radius }}px</span></label><input v-model.number="design.settings.radius" type="range" min="0" max="24" /></div>
          <div class="field"><label>Font</label><select v-model="design.settings.font"><option v-for="f in FONTS" :key="f" :value="f">{{ f.split(',')[0].replace(/'/g, '') }}</option></select></div>
          <p class="hint">Click any block in the email to {{ locked ? 'see its settings' : 'edit it' }}.</p>
        </template>
        </fieldset>
      </aside>
    </div>

    <div v-if="saveDlg" class="dlg-back" @mousedown.self="saveDlg = false">
      <form class="dlg" @submit.prevent="saveLayout">
        <h3>Save as layout</h3>
        <p class="muted">Reuse this design later from the layout gallery under “My layouts”.</p>
        <div v-if="layoutMsg" class="alert err">{{ layoutMsg }}</div>
        <div class="field"><label>Layout name</label><input v-model="layoutName" placeholder="e.g. Summer promo" autofocus required maxlength="255" /></div>
        <div class="row" style="justify-content: flex-end">
          <button type="button" class="btn ghost" @click="saveDlg = false">Cancel</button>
          <button class="btn" :disabled="layoutBusy || !layoutName.trim()">{{ layoutBusy ? 'Saving…' : 'Save layout' }}</button>
        </div>
      </form>
    </div>
    <ImagePicker v-if="imagePicker" @close="imagePicker = null" @pick="pick" />
  </div>
</template>

<style scoped>
.ed { position: fixed; inset: 0; z-index: 60; background: #eceff1; display: flex; flex-direction: column; }
.ed-top { background: #fff; display: flex; align-items: center; gap: 10px; padding: 10px 16px; box-shadow: 0 1px 4px rgba(0, 0, 0, .15); z-index: 2; }
.ed-top .btn.hon { background: #eef0fb; border-color: #3f51b5; color: #3f51b5; }
.ro-pill { background: #fef3c7; color: #92400e; border-radius: 99px; padding: 2px 12px; font-size: 12.5px; font-weight: 500; white-space: nowrap; }
.ro-note { background: #f5f6fd; border: 1px solid #e0e3f0; border-radius: 8px; padding: 12px 14px; font-size: 13px; color: #4b5563; }
.ro-note p { margin: 6px 0 0; }
.fs { border: 0; padding: 0; margin: 0; min-width: 0; }
.ed.ro .fs :deep([contenteditable]) { pointer-events: none; }
.ed.ro .fs :deep(input:disabled), .ed.ro .fs :deep(textarea:disabled), .ed.ro .fs :deep(select:disabled) { background: #f9fafb; color: #374151; }
.ed-back { display: flex; align-items: center; gap: 6px; border: 0; background: none; color: #3f51b5; font-weight: 500; cursor: pointer; font-size: 14px; }
.ed-in { flex: 1; min-width: 120px; }
.ed-in.miss { border-color: #e53935; background: #fff8f8; }
.flash { color: #2e7d32; font-size: 13px; font-weight: 500; white-space: nowrap; }
.g-card.mine { position: relative; }
.g-del { position: absolute; top: 6px; right: 6px; z-index: 2; width: 26px; height: 26px; border-radius: 50%; border: 0; background: rgba(0, 0, 0, .55); color: #fff; font-size: 18px; line-height: 1; cursor: pointer; opacity: 0; transition: opacity .15s; }
.g-card.mine:hover .g-del, .g-del:focus { opacity: 1; } .g-del:hover { background: #e53935; }
.dlg-back { position: fixed; inset: 0; background: rgba(17, 24, 39, .55); display: flex; align-items: center; justify-content: center; z-index: 80; padding: 20px; }
.dlg { background: #fff; border-radius: 6px; padding: 24px; width: 100%; max-width: 440px; box-shadow: 0 8px 30px rgba(0, 0, 0, .3); }
.dlg h3 { margin: 0 0 6px; font-size: 18px; font-weight: 500; }
.need { color: #e53935; font-size: 13px; white-space: nowrap; }
.seg { display: flex; border: 1px solid #d1d5db; border-radius: 4px; overflow: hidden; }
.seg button { border: 0; background: #fff; padding: 6px 10px; cursor: pointer; color: #6b7280; display: flex; } .seg .on { background: #3f51b5; color: #fff; }

.gallery { flex: 1; overflow: auto; padding: 36px 6vw; } .muted { color: #6b7280; }
.g-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 24px; margin-top: 16px; }
.g-card { background: #fff; border: 2px solid transparent; border-radius: 6px; box-shadow: 0 2px 6px rgba(0, 0, 0, .12); padding: 10px; cursor: pointer; text-align: center; font: inherit; font-weight: 500; }
.g-card:hover { border-color: #3f51b5; }
.g-thumb { height: 280px; overflow: hidden; background: #f4f5f7; border-radius: 4px; margin-bottom: 8px; position: relative; }
.g-thumb iframe { width: 700px; height: 1000px; border: 0; transform: scale(.27); transform-origin: top left; pointer-events: none; }

.ed-body { flex: 1; display: grid; grid-template-columns: 290px 1fr 330px; min-height: 0; }
.ed-left, .ed-right { background: #fff; overflow: auto; padding: 16px; }
.ed-left { border-right: 1px solid #e0e0e0; padding: 0; } .ed-right { border-left: 1px solid #e0e0e0; }
.tabs { display: flex; border-bottom: 1px solid #e0e0e0; } .tabs button { flex: 1; border: 0; background: none; padding: 14px; cursor: pointer; font: inherit; font-weight: 500; color: #6b7280; border-bottom: 2px solid transparent; }
.tabs .on { color: #3f51b5; border-bottom-color: #e91e63; }
.palette { padding: 16px; } .hint { color: #6b7280; font-size: 13px; }
.tiles { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.pal { border: 1px solid #e0e0e0; border-radius: 4px; padding: 14px 6px; text-align: center; cursor: grab; display: flex; flex-direction: column; align-items: center; gap: 6px; font-size: 13px; color: #424242; background: #fff; }
.pal:hover { border-color: #3f51b5; color: #3f51b5; box-shadow: 0 2px 6px rgba(63, 81, 181, .2); }
.ed-center { display: flex; flex-direction: column; min-width: 0; min-height: 0; }
.c-tabs { background: #fff; padding: 0 12px; } .c-tabs button { flex: none; padding: 14px 26px; }
.copy { color: #3f51b5 !important; align-self: center; border: 1px solid #c5cae9 !important; border-radius: 4px; padding: 6px 14px !important; font-size: 13px; }
.code-wrap { flex: 1; min-height: 0; background: #282c34; }
.raw-note { background: #fff8e1; color: #6d4c00; padding: 10px 16px; font-size: 13px; display: flex; justify-content: space-between; align-items: center; gap: 12px; border-bottom: 1px solid #ffe082; }
.raw-note.soft { background: #eef0fb; color: #3f51b5; border-color: #c5cae9; justify-content: flex-start; }
.raw-note code { background: rgba(0,0,0,.08); padding: 0 4px; border-radius: 3px; }
.raw-view { background: #eceff1; } .raw-frame { height: 100%; min-height: 600px; border: 0; background: #fff; max-width: 100%; box-shadow: 0 2px 10px rgba(0,0,0,.15); }
.tiles.off, .ed-right.off { opacity: .4; pointer-events: none; }
.code { flex: 1; width: 100%; border: 0; padding: 20px 24px; font: 13px/1.6 Consolas, 'Courier New', monospace; resize: none; background: #1e1e1e; color: #d4d4d4; white-space: pre-wrap; word-break: break-all; overflow: auto; border-radius: 0; }
.code:focus { outline: none; }

.ed-canvas { flex: 1; overflow: auto; padding: 24px; display: flex; justify-content: center; align-items: flex-start; }
.cv-page { padding: 24px; min-height: 400px; box-shadow: 0 2px 10px rgba(0, 0, 0, .15); max-width: 100%; }
.cv-content { margin: 0 auto; min-height: 200px; max-width: 100%; }
.cv-block { position: relative; cursor: pointer; outline: 2px solid transparent; outline-offset: -2px; }
.cv-block:hover { outline-color: #9fa8da; } .cv-block.sel { outline-color: #3f51b5; }
.cv-html { pointer-events: none; }
.cv-block.stack :deep(.col) { display: block !important; width: 100% !important; padding: 0 0 12px 0 !important; }
.cv-tools { position: absolute; top: -1px; right: -1px; background: #3f51b5; display: flex; z-index: 3; border-radius: 0 0 0 4px; }
.cv-tools button, .grip { background: none; border: 0; color: #fff; padding: 5px 6px; cursor: pointer; display: flex; } .grip { cursor: grab; }
.cv-tools button:hover { background: #303f9f; }
.dz { height: 6px; margin: -3px 0; position: relative; z-index: 2; } .dz.end { height: 24px; margin: 0; }
.dz.hov { background: #3f51b5; height: 8px; } .dz.end.hov { height: 24px; opacity: .7; }
.dz-empty { border: 2px dashed #9fa8da; margin: 24px; padding: 60px 10px; text-align: center; color: #7986cb; border-radius: 6px; } .dz-empty.hov { background: #e8eaf6; }

.r-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; font-size: 16px; }
.x { background: none; border: 0; font-size: 22px; cursor: pointer; color: #757575; }
.val { color: #3f51b5; font-weight: 400; } .thumb { width: 100%; max-height: 140px; object-fit: cover; border-radius: 4px; margin-bottom: 8px; display: block; }
.item-box { border: 1px solid #e0e0e0; border-radius: 4px; padding: 8px; margin-bottom: 8px; background: #fafafa; }
.clr { width: 44px !important; padding: 2px !important; height: 36px; flex: none; } input[type=range] { padding: 0; }

@media (max-width: 1000px) { .ed-body { grid-template-columns: 1fr; grid-template-rows: auto 1fr auto; overflow: auto; } .ed-left, .ed-right { max-height: 260px; } }
</style>
