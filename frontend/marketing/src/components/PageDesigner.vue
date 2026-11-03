<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import Icon from './Icon.vue'
import ImagePicker from './ImagePicker.vue'
import MediaPicker from './MediaPicker.vue'
import { useAuth } from '../stores/auth'
import api, { errorMessage } from '../api'
import { fmtDate, fmtDateTime } from '../format'
import RichText from './RichText.vue'
import { BLOCKS, FIELDS, LAYOUTS, setForms, defaultSettings, newBlock, renderBlock, renderBody, renderPage, pageCss, pageVars, uid } from '../builder/pageBlocks'

// Landing-page builder (a real web page, not an email). Pass `initial` ({ name, design }); emits `save` / `close`.
const props = defineProps({ initial: Object, saving: Boolean, error: String, readonly: Boolean, note: String, historyId: [Number, String], startVersion: Number, savedTick: { type: Number, default: 0 }, urlBase: { type: String, default: '' }, forms: { type: Array, default: () => [] } })
const emit = defineEmits(['save', 'close', 'version'])

setForms(props.forms)
const init = props.initial || {}
const meta = reactive({ name: init.name || '', slug: init.slug || '', published: !!init.published })
const design = ref(init.design?.kind === 'page' ? JSON.parse(JSON.stringify(init.design)) : null)
// the browser tab title is a page setting: it lives in the design and becomes the <title> of the page
if (design.value) design.value.settings = { ...defaultSettings(), ...(design.value.settings || {}), title: design.value.settings?.title || init.title || '' }

// Page name and browser tab title help each other: while one is empty, whatever you type in the other fills it in too
// and keeps following. As soon as you edit a filled-in field by hand the two become independent.
const tabTitle = computed({ get: () => design.value?.settings?.title ?? '', set: (v) => { if (design.value) design.value.settings.title = v } })
const auto = { name: false, title: false } // true while that field only repeats the other one
let syncing = false
const mirror = (field, value) => { syncing = true; field === 'title' ? (tabTitle.value = value) : (meta.name = value); auto[field] = true; nextTick(() => (syncing = false)) }
watch(() => meta.name, (now) => {
  if (syncing) return
  auto.name = false
  if (design.value && (tabTitle.value === '' || auto.title)) mirror('title', now)
})
watch(tabTitle, (now) => {
  if (syncing) return
  auto.title = false
  if (meta.name === '' || auto.name) mirror('name', now)
})

const picking = ref(!design.value) // layout gallery first
const selected = ref(null)
const side = ref('content') // right sidebar tab: 'content' (what it says) | 'design' (how it looks), for the page itself or the selected section

// the web address: letters, numbers and dashes. Left empty it is made from the page name when saved.
const slugify = (v, final = false) => {
  const s = String(v || '').normalize('NFD').replace(/\p{M}/gu, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+/, '')
  return final ? s.replace(/-+$/, '').slice(0, 40) : s.slice(0, 40)
}
const autoSlug = computed(() => slugify(meta.name, true))
const device = ref('desktop')
const tab = ref('view')
const hover = ref(-1)
const auth = useAuth()
// {{company_logo}} is filled in when the page is published; show the real logo while designing
const withLogo = (h) => h.split('{{company_logo}}').join(auth.company?.logo_url || '')
const imagePicker = ref(null)
const mediaPicker = ref(null) // { block, key } while choosing from the media library
const copied = ref(false)

const s = computed(() => ({ ...defaultSettings(), ...(design.value?.settings || {}) }))
const blocks = computed(() => design.value?.blocks || [])
const current = computed(() => blocks.value.find((b) => b.id === selected.value))
const fields = computed(() => (current.value ? FIELDS[current.value.type] || [] : []))
// colours, sliders, choices and tick-boxes (and the background photo) are design; text, links, images and lists are content
const isDesign = ([k, , kind]) => ['color', 'range', 'select', 'check'].includes(kind) || k === 'bgImage'
const shownFields = computed(() => fields.value.filter((fd) => (side.value === 'design') === isDesign(fd)))
const html = computed(() => (design.value ? renderPage(design.value) : ''))
const css = pageCss()
const vars = computed(() => pageVars(s.value))
// gallery thumbnails are drawn inline at desktop width and zoomed down: instant, and nothing to wait for like an iframe would need
const layoutPreviews = LAYOUTS.map((l) => { const d = l.make(); return { ...l, body: renderBody(d, true), vars: pageVars({ ...defaultSettings(), ...d.settings }) } })

// ---- device preview: the page is laid out at a real device width, then zoomed to fit the canvas
const DEVICE_W = { desktop: 1280, tablet: 820, mobile: 390 }
const area = ref(null)
const avail = ref(900)
let ro
const zoom = computed(() => Math.min(1, Math.max(0.3, (avail.value - 40) / DEVICE_W[device.value])))
onMounted(() => {
  ro = new ResizeObserver(([e]) => (avail.value = e.contentRect.width))
  nextTick(() => area.value && ro.observe(area.value))
})
onBeforeUnmount(() => ro?.disconnect())

function startFrom(layout) {
  const title = design.value?.settings?.title || init.title || meta.name // keep the title when the layout is changed; a page named before the layout was chosen uses its name
  design.value = layout.make()
  design.value.settings = { ...defaultSettings(), ...(design.value.settings || {}), title }
  picking.value = false
  selected.value = null
}

// ---- block operations
const selectedIndex = () => blocks.value.findIndex((b) => b.id === selected.value)
function insertAt(block, i = blocks.value.length) {
  blocks.value.splice(i, 0, block)
  selected.value = block.id
}
const add = (type) => insertAt(newBlock(type), selectedIndex() >= 0 ? selectedIndex() + 1 : blocks.value.length)
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

// ---- drag & drop (palette -> page, reorder)
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

// ---- list editors (features, pricing plans, FAQ…)
const moveItem = (list, i, d) => { const j = i + d; if (j >= 0 && j < list.length) list.splice(j, 0, list.splice(i, 1)[0]) }

// ---- images (free stock search). Credits are collected and printed at the bottom of the page.
function chooseImage(block, key) {
  imagePicker.value = {
    apply(img) {
      block[key] = img.url
      if ('alt' in block && !block.alt) block.alt = `Photo by ${img.author}`
      if (block.type === 'image' && !block.caption) block.caption = `Photo by ${img.author} on ${img.source}`
      design.value.credits = [...(design.value.credits || []).filter((c) => c.url !== img.url), { url: img.url, author: img.author, author_url: img.author_url, source: img.source, link: img.link, license: img.license || '' }]
    },
  }
}
function pickMedia(m) {
  mediaPicker.value.block[mediaPicker.value.key] = m.url
  if ('alt' in mediaPicker.value.block && !mediaPicker.value.block.alt) mediaPicker.value.block.alt = m.alt || m.name
  mediaPicker.value = null
}
function pick(img) {
  imagePicker.value.apply(img)
  imagePicker.value = null
}

// ---- history: a timeline of this page; picking a point shows the page as it was, on this same canvas, read-only
const versions = ref(null) // null = not loaded yet
const vLoading = ref(false)
const vError = ref('')
const opening = ref(0)
const viewing = ref(null) // the saved copy being shown, or null for the version you are editing
let backup = null // the version being edited, kept while a past one is shown
const locked = computed(() => props.readonly || !!viewing.value)
watch(viewing, (v) => emit('version', v ? v.history_id : null)) // so the address can say which version is on show
const ACTIONS = { created: 'Created', updated: 'Updated', deleted: 'Deleted', restored: 'Restored', baseline: 'Existing page' }
const colName = (k) => k.replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase())
const canHistory = computed(() => !!props.historyId && !props.readonly)
const versionDays = computed(() => {
  const out = []
  for (const v of versions.value || []) {
    const day = fmtDate(v.changed_at)
    if (!out.length || out[out.length - 1].day !== day) out.push({ day, items: [] })
    out[out.length - 1].items.push(v)
  }
  return out
})
const noteText = computed(() => (viewing.value ? `${ACTIONS[viewing.value.change_action] || viewing.value.change_action} on ${fmtDateTime(viewing.value.changed_at)}${viewing.value.changed_by_name ? ' by ' + viewing.value.changed_by_name : ''}.` : props.note))
// what a version changed, in words (the design and the generated HTML are one thing to a person: "Design")
const what = (v) => {
  const keys = Object.keys(v.changes || {})
  const names = keys.filter((k) => k !== 'design' && k !== 'html').map(colName)
  return (keys.some((k) => k === 'design' || k === 'html') ? names.concat(['Design']) : names).join(' · ')
}

async function loadVersions() {
  vLoading.value = versions.value === null // a reload after a save keeps the list on screen
  vError.value = ''
  try { versions.value = (await api.get(`/history/landing_page/${props.historyId}`)).data } catch (e) { vError.value = errorMessage(e) }
  vLoading.value = false
}
watch(side, (v) => { if (v === 'history') loadVersions() })
// the parent bumps savedTick after a save that kept the editor open (History was open): say so and show the new entry
const flash = ref('')
watch(() => props.savedTick, () => { flash.value = 'Saved'; setTimeout(() => (flash.value = ''), 2500); if (side.value === 'history') loadVersions() })
// the header History button switches the sidebar between the timeline (alone) and the usual Content / Design tabs
let sideBefore = 'content'
function toggleHistory() {
  if (side.value === 'history') {
    backToCurrent() // leaving history: you are back on the version you are editing
    side.value = sideBefore
  } else {
    sideBefore = side.value
    side.value = 'history'
  }
}

// swapping what the canvas shows must not trigger the name <-> title mirroring
function swap(next) {
  syncing = true
  design.value = next.design
  meta.name = next.name
  meta.slug = next.slug
  selected.value = null
  nextTick(() => (syncing = false))
}
async function viewVersion(v) {
  opening.value = v.history_id
  vError.value = ''
  try {
    const e = (await api.get(`/history/landing_page/entry/${v.history_id}`)).data
    if (e.data.design?.kind !== 'page') { vError.value = 'That version was saved before the page builder existed, so it cannot be shown here.'; return }
    if (!backup) backup = { design: design.value, name: meta.name, slug: meta.slug }
    const d = JSON.parse(JSON.stringify(e.data.design))
    d.settings = { ...defaultSettings(), ...(d.settings || {}), title: d.settings?.title || e.data.title || '' }
    swap({ design: d, name: e.data.name, slug: e.data.slug || '' })
    viewing.value = { ...e, history_id: v.history_id }
  } catch (err) { vError.value = errorMessage(err) } finally { opening.value = 0 }
}
function backToCurrent() {
  if (!backup) return
  swap(backup)
  backup = null
  viewing.value = null
}
// opened from a history list: start with History open and that version on the canvas
onMounted(() => {
  if (props.startVersion && props.historyId) {
    side.value = 'history'
    viewVersion({ history_id: props.startVersion })
  }
})

async function copyHtml() {
  try { await navigator.clipboard.writeText(html.value); copied.value = true; setTimeout(() => (copied.value = false), 1500) } catch (e) { /* clipboard blocked */ }
}

const save = () => emit('save', { keepOpen: side.value === 'history' && !!props.historyId, name: meta.name, slug: slugify(meta.slug, true), title: (design.value?.settings?.title || '').trim(), published: meta.published, design: { ...design.value, kind: 'page' }, html: html.value })
</script>

<template>
  <div class="pd" :class="{ locked: locked }">
    <header class="pd-top">
      <button class="pd-back" @click="emit('close')"><Icon name="back" :size="18" /> {{ readonly ? 'Back to history' : 'Back' }}</button>
      <input v-model="meta.name" class="pd-name" :class="{ miss: !locked && !meta.name.trim() }" placeholder="Page name *" maxlength="255" :readonly="locked" />
      <span v-if="locked" class="ro-pill">Read only · saved copy</span>
      <button v-if="viewing" class="btn ghost sm" @click="backToCurrent">Back to current version</button>
      <span style="flex: 1"></span>
      <div v-if="design" class="seg">
        <button :class="{ on: device === 'desktop' }" title="Desktop" @click="device = 'desktop'"><Icon name="desktop" :size="18" /></button>
        <button :class="{ on: device === 'tablet' }" title="Tablet" @click="device = 'tablet'"><Icon name="template" :size="18" /></button>
        <button :class="{ on: device === 'mobile' }" title="Mobile" @click="device = 'mobile'"><Icon name="mobile" :size="18" /></button>
      </div>
      <button v-if="canHistory" class="btn ghost sm" :class="{ hon: side === 'history' }" @click="toggleHistory">History</button>
      <button v-else-if="design && !readonly && !historyId" class="btn ghost sm" disabled title="History starts once the page has been saved for the first time">History</button>
      <button v-if="design && !locked" class="btn ghost sm" @click="picking = true">Change layout</button>
      <span v-if="!locked && design && !meta.name.trim()" class="need">Add a page name to save</span>
      <span v-else-if="error" class="need" :title="error">{{ error }}</span>
      <span v-if="flash" class="flash">{{ flash }}</span>
      <label v-if="design && !locked" class="pub"><input v-model="meta.published" type="checkbox" /> Publish</label>
      <button v-if="!locked" class="btn" :disabled="!design || saving || !meta.name.trim()" @click="save">{{ saving ? 'Saving…' : 'Save page' }}</button>
    </header>

    <!-- layout gallery -->
    <div v-if="picking && !locked" class="gallery">
      <component :is="'style'">{{ css }}</component>
      <h2>Choose a page layout</h2>
      <p class="muted">Full-width web pages that work on desktop, tablet and phone. Pick a starting point — you can change everything afterwards.</p>
      <div class="g-grid">
        <button v-for="l in layoutPreviews" :key="l.id" class="g-card" @click="startFrom(l)">
          <div class="g-thumb"><div class="g-frame"><div class="pg" :style="l.vars" v-html="l.body"></div></div></div>
          <span>{{ l.name }}</span>
        </button>
      </div>
      <button v-if="design" class="btn ghost" style="margin-top: 20px" @click="picking = false">Keep my current design</button>
    </div>

    <div v-else class="pd-body">
      <!-- left: sections -->
      <aside class="pd-left">
        <div v-if="locked" class="ro-note"><b>Saved copy</b><p>{{ noteText }}</p><p>This is exactly how the page was when it was saved. You can look around, switch device sizes, open the HTML and click a section to see its settings, but nothing can be changed.</p></div>
        <p v-if="!locked" class="hint">Drag a section onto the page, or click to add it below the selected one.</p>
        <div v-if="!locked" class="tiles">
          <div v-for="(def, type) in BLOCKS" :key="type" class="pal" draggable="true" @dragstart="dragNew($event, type)" @click="add(type)">
            <Icon :name="def.icon" :size="24" /><span>{{ def.label }}</span>
          </div>
        </div>
      </aside>

      <!-- centre: page preview / HTML -->
      <section class="pd-center">
        <div class="tabs c-tabs">
          <button :class="{ on: tab === 'view' }" @click="tab = 'view'">Page</button>
          <button :class="{ on: tab === 'html' }" @click="tab = 'html'">HTML</button>
          <span style="flex: 1"></span>
          <small v-if="tab === 'view'" class="dev">{{ device === 'desktop' ? 'Desktop 1280px' : device === 'tablet' ? 'Tablet 820px' : 'Phone 390px' }}</small>
          <button v-if="tab === 'html'" class="copy" @click="copyHtml">{{ copied ? 'Copied ✓' : 'Copy HTML' }}</button>
        </div>

        <textarea v-if="tab === 'html'" class="code" locked spellcheck="false" :value="html"></textarea>

        <div v-show="tab === 'view'" ref="area" class="pd-canvas" @click.self="selected = null">
          <div class="frame" :style="{ width: DEVICE_W[device] + 'px', zoom }">
            <component :is="'style'">{{ css }}</component>
            <div class="pg" :style="vars" @click.self="selected = null">
              <div v-if="!blocks.length && !locked" class="dz-empty" :class="{ hov: hover === 0 }" @dragover.prevent="hover = 0" @dragleave="hover = -1" @drop.prevent="drop($event, 0)">Drag a section here</div>
              <template v-for="(b, i) in blocks" :key="b.id">
                <div v-if="!locked" class="dz" :class="{ hov: hover === i }" @dragover.prevent="hover = i" @dragleave="hover = -1" @drop.prevent="drop($event, i)"></div>
                <div class="cv-b" :class="{ sel: selected === b.id }" :draggable="!locked" @dragstart="dragMove($event, i)" @click.prevent.stop="selected = b.id">
                  <div class="cv-html" v-html="withLogo(renderBlock(b, s, true))"></div>
                  <div v-if="selected === b.id && !locked" class="cv-tools" @click.stop>
                    <span class="lbl">{{ BLOCKS[b.type].label }}</span>
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
        </div>
      </section>

      <!-- right: settings -->
      <aside class="pd-right">
        <div v-if="design && side !== 'history'" class="r-tabs">
          <button :class="{ on: side === 'content' }" @click="side = 'content'">Content</button>
          <button :class="{ on: side === 'design' }" @click="side = 'design'">Design</button>
        </div>

        <!-- the timeline of this page: pick a point to see the page as it was (view only) -->
        <div v-if="side === 'history'" class="hist">
          <div class="r-head"><b>Page history</b><button class="x" title="Close history" @click="toggleHistory">&times;</button></div>
          <p class="hint">Pick a point on the timeline to see the page as it was. You can only look: nothing changes.</p>
          <button type="button" class="vcur" :class="{ on: !viewing }" @click="backToCurrent">Current version <small>the one you are editing</small></button>
          <p v-if="vLoading" class="hint">Loading…</p>
          <p v-else-if="vError" class="need" style="white-space: normal">{{ vError }}</p>
          <p v-else-if="versions && !versions.length" class="hint">No changes have been recorded for this page yet.</p>
          <template v-else>
            <section v-for="g in versionDays" :key="g.day">
              <h4 class="vday">{{ g.day }}</h4>
              <ol class="vtl">
                <li v-for="v in g.items" :key="v.history_id">
                  <button type="button" class="vitem" :class="{ on: viewing && viewing.history_id === v.history_id }" :disabled="opening === v.history_id" @click="viewVersion(v)">
                    <span class="top"><span class="badge" :class="'a-' + v.change_action">{{ ACTIONS[v.change_action] || v.change_action }}</span><span class="when">{{ fmtDateTime(v.changed_at).slice(11) }}</span></span>
                    <small class="who">{{ v.changed_by_name || 'System' }}</small>
                    <small v-if="what(v)" class="what">{{ what(v) }}</small>
                  </button>
                </li>
              </ol>
            </section>
          </template>
        </div>

        <fieldset v-else :disabled="locked" class="fs">

        <template v-if="!current && side === 'content' && design">
          <div class="r-head"><b>Page content</b></div>
          <div class="field">
            <label>Page title</label>
            <input v-model="design.settings.title" maxlength="255" placeholder="Landing page" />
            <small class="hint">Shown in the browser tab, in bookmarks and in search results. It is the title tag of the page.</small>
          </div>
          <div class="field">
            <label>Slug <small>(web address)</small></label>
            <input v-model="meta.slug" maxlength="40" :placeholder="autoSlug || 'made from the page name'" @input="meta.slug = slugify(meta.slug)" @blur="meta.slug = slugify(meta.slug, true)" />
            <small class="hint">Your page will be at <b class="url">{{ urlBase || '…/p/' }}{{ slugify(meta.slug, true) || autoSlug || '…' }}</b>. Leave it empty and it is made from the page name.<template v-if="init.slug"> Changing it breaks links you have already shared.</template></small>
          </div>
        </template>

        <template v-else-if="current">
          <div class="r-head"><b>{{ BLOCKS[current.type].label }}</b><button class="x" @click="selected = null">&times;</button></div>

          <p v-if="!shownFields.length" class="hint">{{ side === 'content' ? 'This section has no text to edit. Use the Design tab for its colours and spacing.' : 'This section has no design settings.' }}</p>

          <div v-for="[k, label, kind, o] in shownFields" :key="current.id + k" class="field">
            <label v-if="kind !== 'check'">{{ label }}<span v-if="kind === 'range'" class="val"> {{ current[k] }}</span></label>
            <input v-if="kind === 'text'" v-model="current[k]" />
            <textarea v-else-if="kind === 'textarea'" v-model="current[k]" :rows="current.type === 'html' ? 9 : 3" style="font-family: inherit"></textarea>
            <RichText v-else-if="kind === 'rich'" v-model="current[k]" />
            <input v-else-if="kind === 'range'" v-model.number="current[k]" type="range" :min="o.min" :max="o.max" />
            <div v-else-if="kind === 'form'">
              <select v-model="current[k]"><option value="">— Choose a form —</option><option v-for="f in forms" :key="f.id" :value="f.id">{{ f.name }}</option></select>
              <small class="hint">{{ forms.length ? 'You can add this section more than once, each with its own form (or the same one).' : 'No forms yet — create one under Content → Forms.' }}</small>
            </div>
            <select v-else-if="kind === 'select'" v-model="current[k]"><option v-for="[v, l] in o.options" :key="v" :value="v">{{ l }}</option></select>
            <label v-else-if="kind === 'check'" class="check"><input v-model="current[k]" type="checkbox" /> {{ label }}</label>
            <div v-else-if="kind === 'color'" class="row"><input v-model="current[k]" type="color" class="clr" /><input v-model="current[k]" placeholder="none" /></div>
            <div v-else-if="kind === 'image'">
              <img v-if="current[k]" :src="withLogo(current[k])" class="thumb" />
              <button class="btn sm" type="button" @click="mediaPicker = { block: current, key: k }">Choose from media</button>
              <button class="btn ghost sm" type="button" style="margin-left: 6px" @click="chooseImage(current, k)">Free stock image</button>
              <button v-if="k === 'logo'" class="btn ghost sm" type="button" style="margin-left: 6px" @click="current[k] = '{{company_logo}}'">Use company logo</button>
              <button v-if="current[k]" class="btn ghost sm" type="button" style="margin-left: 6px" @click="current[k] = ''">Remove</button>
              <input v-model="current[k]" placeholder="…or paste an image URL" style="margin-top: 6px" />
            </div>

            <div v-else-if="kind === 'items'">
              <div v-for="(it, ii) in current[k]" :key="ii" class="item-box">
                <div class="item-head">
                  <small>#{{ ii + 1 }}</small>
                  <span>
                    <button type="button" class="ib" title="Up" @click="moveItem(current[k], ii, -1)"><Icon name="up" :size="14" /></button>
                    <button type="button" class="ib" title="Down" @click="moveItem(current[k], ii, 1)"><Icon name="down" :size="14" /></button>
                    <button type="button" class="ib" title="Remove" @click="current[k].splice(ii, 1)"><Icon name="trash" :size="14" /></button>
                  </span>
                </div>
                <div v-for="[sk, sl, sKind] in o.sub" :key="sk" class="sub-f">
                  <label v-if="sKind !== 'check'">{{ sl }}</label>
                  <textarea v-if="sKind === 'textarea'" v-model="it[sk]" rows="2" style="font-family: inherit"></textarea>
                  <label v-else-if="sKind === 'check'" class="check"><input v-model="it[sk]" type="checkbox" /> {{ sl }}</label>
                  <input v-else v-model="it[sk]" />
                </div>
              </div>
              <button type="button" class="btn ghost sm" @click="current[k].push(o.blank())">+ Add</button>
            </div>
          </div>
        </template>

        <template v-else-if="design">
          <div class="r-head"><b>Page design</b></div>
          <div v-for="[k, label] in [['bg', 'Page background'], ['text', 'Text colour'], ['accent', 'Accent / button colour'], ['accentText', 'Button text colour']]" :key="k" class="field">
            <label>{{ label }}</label>
            <div class="row"><input v-model="design.settings[k]" type="color" class="clr" /><input v-model="design.settings[k]" maxlength="7" /></div>
          </div>
          <div class="field"><label>Font</label><select v-model="design.settings.font"><option value="sans">Modern sans-serif</option><option value="serif">Classic serif</option><option value="rounded">Friendly rounded</option></select></div>
          <div class="field"><label>Content width <span class="val">{{ s.width }}px</span></label><input v-model.number="design.settings.width" type="range" min="800" max="1400" step="20" /></div>
          <div class="field"><label>Corner radius <span class="val">{{ s.radius }}px</span></label><input v-model.number="design.settings.radius" type="range" min="0" max="30" /></div>
          <p class="hint">Click any section on the page to edit it. Colours set on a section override these.</p>
        </template>
        </fieldset>
      </aside>
    </div>

    <MediaPicker v-if="mediaPicker" @close="mediaPicker = null" @pick="pickMedia" />
    <ImagePicker v-if="imagePicker" @close="imagePicker = null" @pick="pick" />
  </div>
</template>

<style scoped>
.pd { position: fixed; inset: 0; z-index: 60; background: #eceff1; display: flex; flex-direction: column; }
.pd-top { background: #fff; display: flex; align-items: center; gap: 10px; padding: 10px 16px; box-shadow: 0 1px 4px rgba(0, 0, 0, .15); z-index: 2; }
.pd-back { display: flex; align-items: center; gap: 6px; border: 0; background: none; color: #3f51b5; font-weight: 500; cursor: pointer; font-size: 14px; }
.r-tabs { display: flex; gap: 4px; margin: -4px 0 14px; border-bottom: 1px solid #e0e3f0; }
.r-tabs button { flex: 1; background: none; border: 0; border-bottom: 2px solid transparent; padding: 8px 6px; font: inherit; font-weight: 500; color: #6b7280; cursor: pointer; margin-bottom: -1px; }
.r-tabs button:disabled { opacity: .45; cursor: not-allowed; }
.r-tabs button.on { color: #3f51b5; border-bottom-color: #3f51b5; }
.url { word-break: break-all; font-weight: 600; }
.hist { padding-bottom: 20px; }
.pd-top .btn.hon { background: #eef0fb; border-color: #3f51b5; color: #3f51b5; }
.vcur, .vitem { display: block; width: 100%; text-align: left; background: #fff; border: 1px solid #e0e3f0; border-radius: 8px; padding: 8px 10px; font: inherit; font-size: 13px; cursor: pointer; margin-bottom: 6px; }
.vcur small, .vitem small { display: block; color: #6b7280; }
.vcur:hover, .vitem:hover { border-color: #3f51b5; }
.vcur.on, .vitem.on { border-color: #3f51b5; background: #eef0fb; box-shadow: inset 3px 0 0 #3f51b5; }
.vday { margin: 14px 0 6px; font-size: 12.5px; color: #3f51b5; font-weight: 600; }
.vtl { list-style: none; margin: 0; padding: 0 0 0 4px; border-left: 2px solid #c5cae9; }
.vtl li { padding-left: 10px; }
.vitem .top { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.vitem .badge.a-created, .vitem .badge.a-restored, .vitem .badge.a-baseline { background: #d1fae5; color: #065f46; }
.vitem .badge.a-updated { background: #dbeafe; color: #1e40af; }
.vitem .badge.a-deleted { background: #fee2e2; color: #991b1b; }
.vitem .what { margin-top: 2px; }
.ro-pill { background: #fef3c7; color: #92400e; border-radius: 99px; padding: 2px 12px; font-size: 12.5px; font-weight: 500; white-space: nowrap; }
.ro-note { background: #f5f6fd; border: 1px solid #e0e3f0; border-radius: 8px; padding: 12px 14px; font-size: 13px; color: #4b5563; }
.ro-note p { margin: 6px 0 0; }
.fs { border: 0; padding: 0; margin: 0; min-width: 0; }
.pd.ro .fs :deep([contenteditable]) { pointer-events: none; }
.pd.ro .fs :deep(input:disabled), .pd.ro .fs :deep(textarea:disabled), .pd.ro .fs :deep(select:disabled) { background: #f9fafb; color: #374151; }
.pub { display: inline-flex; align-items: center; gap: 6px; font-size: 14px; white-space: nowrap; cursor: pointer; }
.pd-name { max-width: 260px; font-weight: 500; } .pd-name.miss { border-color: #e53935; background: #fff8f8; }
.flash { color: #2e7d32; font-size: 13px; white-space: nowrap; }
.need { color: #e53935; font-size: 13px; white-space: nowrap; max-width: 320px; overflow: hidden; text-overflow: ellipsis; }
.seg { display: flex; border: 1px solid #d1d5db; border-radius: 4px; overflow: hidden; }
.seg button { border: 0; background: #fff; padding: 6px 10px; cursor: pointer; color: #6b7280; display: flex; } .seg .on { background: #3f51b5; color: #fff; }

.gallery { flex: 1; overflow: auto; padding: 36px 6vw; } .muted { color: #6b7280; }
.g-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 24px; margin-top: 16px; }
.g-card { background: #fff; border: 2px solid transparent; border-radius: 6px; box-shadow: 0 2px 6px rgba(0, 0, 0, .12); padding: 10px; cursor: pointer; text-align: center; font: inherit; font-weight: 500; }
.g-card:hover { border-color: #3f51b5; }
.g-thumb { height: 300px; overflow: hidden; background: #f4f5f7; border-radius: 4px; margin-bottom: 8px; position: relative; }
.g-frame { width: 1280px; zoom: .19; pointer-events: none; }

.pd-body { flex: 1; display: grid; grid-template-columns: 250px 1fr 340px; min-height: 0; }
.pd-left, .pd-right { background: #fff; overflow: auto; padding: 16px; }
.pd-left { border-right: 1px solid #e0e0e0; } .pd-right { border-left: 1px solid #e0e0e0; }
.hint { color: #6b7280; font-size: 13px; margin: 0 0 12px; } .hint.note { background: #eef0fb; padding: 8px 10px; border-radius: 4px; }
.tiles { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.pal { border: 1px solid #e0e0e0; border-radius: 4px; padding: 12px 4px; text-align: center; cursor: grab; display: flex; flex-direction: column; align-items: center; gap: 5px; font-size: 12px; color: #424242; background: #fff; }
.pal:hover { border-color: #3f51b5; color: #3f51b5; box-shadow: 0 2px 6px rgba(63, 81, 181, .2); }

.pd-center { display: flex; flex-direction: column; min-width: 0; min-height: 0; }
.tabs { display: flex; border-bottom: 1px solid #e0e0e0; background: #fff; padding: 0 12px; align-items: center; }
.tabs button { border: 0; background: none; padding: 14px 26px; cursor: pointer; font: inherit; font-weight: 500; color: #6b7280; border-bottom: 2px solid transparent; }
.tabs .on { color: #3f51b5; border-bottom-color: #e91e63; }
.dev { color: #6b7280; } .copy { color: #3f51b5 !important; border: 1px solid #c5cae9 !important; border-radius: 4px; padding: 6px 14px !important; font-size: 13px; }
.code { flex: 1; width: 100%; border: 0; padding: 20px 24px; font: 13px/1.6 Consolas, 'Courier New', monospace; resize: none; background: #1e1e1e; color: #d4d4d4; white-space: pre-wrap; word-break: break-all; overflow: auto; border-radius: 0; }

.pd-canvas { flex: 1; overflow: auto; padding: 20px; display: flex; justify-content: center; align-items: flex-start; background: #cfd8dc; }
.frame { flex: none; background: #fff; box-shadow: 0 4px 18px rgba(0, 0, 0, .25); min-height: 400px; }
.cv-b { position: relative; cursor: pointer; outline: 2px solid transparent; outline-offset: -2px; }
.cv-b:hover { outline-color: #9fa8da; } .cv-b.sel { outline: 3px solid #3f51b5; outline-offset: -3px; }
.cv-html { pointer-events: none; }
.cv-tools { position: absolute; top: 0; right: 0; background: #3f51b5; display: flex; align-items: center; z-index: 5; border-radius: 0 0 0 4px; font-size: 13px; }
.cv-tools .lbl { color: #fff; padding: 0 10px; font-size: 12px; }
.cv-tools button, .grip { background: none; border: 0; color: #fff; padding: 6px 7px; cursor: pointer; display: flex; } .grip { cursor: grab; } .cv-tools button:hover { background: #303f9f; }
.dz { height: 8px; margin: -4px 0; position: relative; z-index: 4; } .dz.end { margin: 0; }
.dz.hov { background: #3f51b5; height: 10px; } .dz.end.hov { opacity: .7; }
.dz-empty { border: 3px dashed #9fa8da; margin: 60px; padding: 90px 10px; text-align: center; color: #7986cb; border-radius: 8px; font-size: 18px; } .dz-empty.hov { background: #e8eaf6; }

.r-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; font-size: 16px; }
.x { background: none; border: 0; font-size: 22px; cursor: pointer; color: #757575; }
.val { color: #3f51b5; font-weight: 400; } .thumb { width: 100%; max-height: 140px; object-fit: cover; border-radius: 4px; margin-bottom: 8px; display: block; }
.clr { width: 44px !important; padding: 2px !important; height: 36px; flex: none; } input[type=range] { padding: 0; }
.item-box { border: 1px solid #e0e0e0; border-radius: 6px; padding: 8px 10px; margin-bottom: 8px; background: #fafafa; }
.item-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; color: #6b7280; }
.ib { border: 0; background: none; color: #757575; cursor: pointer; padding: 3px; } .ib:hover { color: #3f51b5; }
.sub-f { margin-bottom: 8px; } .sub-f label { font-size: 12px; color: #6b7280; margin-bottom: 2px; }

@media (max-width: 1000px) { .pd-body { grid-template-columns: 1fr; grid-template-rows: auto 1fr auto; overflow: auto; } .pd-left, .pd-right { max-height: 260px; } }
</style>
