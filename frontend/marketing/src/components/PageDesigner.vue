<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import Icon from './Icon.vue'
import ImagePicker from './ImagePicker.vue'
import MediaPicker from './MediaPicker.vue'
import { useAuth } from '../stores/auth'
import RichText from './RichText.vue'
import { BLOCKS, FIELDS, LAYOUTS, setForms, defaultSettings, newBlock, renderBlock, renderBody, renderPage, pageCss, pageVars, uid } from '../builder/pageBlocks'

// Landing-page builder (a real web page, not an email). Pass `initial` ({ name, design }); emits `save` / `close`.
const props = defineProps({ initial: Object, saving: Boolean, forms: { type: Array, default: () => [] } })
const emit = defineEmits(['save', 'close'])

setForms(props.forms)
const init = props.initial || {}
const meta = reactive({ name: init.name || '' })
const design = ref(init.design?.kind === 'page' ? JSON.parse(JSON.stringify(init.design)) : null)
if (design.value) design.value.settings = { ...defaultSettings(), ...(design.value.settings || {}) }

const picking = ref(!design.value) // layout gallery first
const selected = ref(null)
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
  design.value = layout.make()
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

async function copyHtml() {
  try { await navigator.clipboard.writeText(html.value); copied.value = true; setTimeout(() => (copied.value = false), 1500) } catch (e) { /* clipboard blocked */ }
}

const save = () => emit('save', { name: meta.name, design: { ...design.value, kind: 'page' }, html: html.value })
</script>

<template>
  <div class="pd">
    <header class="pd-top">
      <button class="pd-back" @click="emit('close')"><Icon name="back" :size="18" /> Back</button>
      <input v-model="meta.name" class="pd-name" :class="{ miss: !meta.name.trim() }" placeholder="Page name *" maxlength="255" />
      <span style="flex: 1"></span>
      <div v-if="design" class="seg">
        <button :class="{ on: device === 'desktop' }" title="Desktop" @click="device = 'desktop'"><Icon name="desktop" :size="18" /></button>
        <button :class="{ on: device === 'tablet' }" title="Tablet" @click="device = 'tablet'"><Icon name="template" :size="18" /></button>
        <button :class="{ on: device === 'mobile' }" title="Mobile" @click="device = 'mobile'"><Icon name="mobile" :size="18" /></button>
      </div>
      <button v-if="design" class="btn ghost sm" @click="picking = true">Change layout</button>
      <span v-if="design && !meta.name.trim()" class="need">Add a page name to save</span>
      <button class="btn" :disabled="!design || saving || !meta.name.trim()" @click="save">{{ saving ? 'Saving…' : 'Next: publish' }}</button>
    </header>

    <!-- layout gallery -->
    <div v-if="picking" class="gallery">
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
        <p class="hint">Drag a section onto the page, or click to add it below the selected one.</p>
        <div class="tiles">
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

        <textarea v-if="tab === 'html'" class="code" readonly spellcheck="false" :value="html"></textarea>

        <div v-show="tab === 'view'" ref="area" class="pd-canvas" @click.self="selected = null">
          <div class="frame" :style="{ width: DEVICE_W[device] + 'px', zoom }">
            <component :is="'style'">{{ css }}</component>
            <div class="pg" :style="vars" @click.self="selected = null">
              <div v-if="!blocks.length" class="dz-empty" :class="{ hov: hover === 0 }" @dragover.prevent="hover = 0" @dragleave="hover = -1" @drop.prevent="drop($event, 0)">Drag a section here</div>
              <template v-for="(b, i) in blocks" :key="b.id">
                <div class="dz" :class="{ hov: hover === i }" @dragover.prevent="hover = i" @dragleave="hover = -1" @drop.prevent="drop($event, i)"></div>
                <div class="cv-b" :class="{ sel: selected === b.id }" draggable="true" @dragstart="dragMove($event, i)" @click.prevent.stop="selected = b.id">
                  <div class="cv-html" v-html="withLogo(renderBlock(b, s, true))"></div>
                  <div v-if="selected === b.id" class="cv-tools" @click.stop>
                    <span class="lbl">{{ BLOCKS[b.type].label }}</span>
                    <span class="grip" title="Drag to move"><Icon name="drag" :size="16" /></span>
                    <button title="Move up" @click="move(i, -1)"><Icon name="up" :size="16" /></button>
                    <button title="Move down" @click="move(i, 1)"><Icon name="down" :size="16" /></button>
                    <button title="Duplicate" @click="dup(i)"><Icon name="copy" :size="16" /></button>
                    <button title="Delete" @click="remove(i)"><Icon name="trash" :size="16" /></button>
                  </div>
                </div>
              </template>
              <div v-if="blocks.length" class="dz end" :class="{ hov: hover === blocks.length }" @dragover.prevent="hover = blocks.length" @dragleave="hover = -1" @drop.prevent="drop($event, blocks.length)"></div>
            </div>
          </div>
        </div>
      </section>

      <!-- right: settings -->
      <aside class="pd-right">
        <template v-if="current">
          <div class="r-head"><b>{{ BLOCKS[current.type].label }}</b><button class="x" @click="selected = null">&times;</button></div>

          <div v-for="[k, label, kind, o] in fields" :key="current.id + k" class="field">
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
.pd-name { max-width: 320px; font-weight: 500; } .pd-name.miss { border-color: #e53935; background: #fff8f8; }
.need { color: #e53935; font-size: 13px; white-space: nowrap; }
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
.dz { height: 8px; margin: -4px 0; position: relative; z-index: 4; } .dz.end { height: 40px; margin: 0; }
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
