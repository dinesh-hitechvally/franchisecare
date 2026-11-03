<script setup>
// A small "i" that explains the label or title it sits right after: <label>Name <InfoTip text="…" /></label>
// The box opens under the icon on hover or keyboard focus. It is positioned with `fixed`, kept inside the nearest
// ancestor marked data-tip-bounds (a side panel) and flipped above the icon when there is no room below, so a narrow
// or scrolling panel never cuts it off.
import { ref, nextTick } from 'vue'

defineProps({ text: { type: String, required: true } })

const root = ref(null)
const box = ref(null)
const open = ref(false)
const pos = ref({ visibility: 'hidden' })

async function show() {
  open.value = true
  pos.value = { visibility: 'hidden' }
  await nextTick()
  if (!root.value || !box.value) return
  const r = root.value.getBoundingClientRect()
  const area = root.value.closest('[data-tip-bounds]')?.getBoundingClientRect() || { left: 0, right: window.innerWidth }
  const margin = 8
  const width = Math.min(280, area.right - area.left - margin * 2)
  const left = Math.max(area.left + margin, Math.min(r.left - 10, area.right - margin - width))
  let top = r.bottom + 6
  const height = box.value.offsetHeight
  if (top + height > window.innerHeight - 8) top = Math.max(8, r.top - height - 6)
  pos.value = { left: left + 'px', top: top + 'px', width: width + 'px', visibility: 'visible' }
}
const hide = () => { open.value = false }
</script>

<template>
  <span ref="root" class="tip" tabindex="0" role="note" :aria-label="text" @mouseenter="show" @mouseleave="hide" @focus="show" @blur="hide" @click.prevent>i<span v-show="open" ref="box" class="tip-box" :style="pos">{{ text }}</span></span>
</template>

<style scoped>
.tip { display: inline-flex; align-items: center; justify-content: center; width: 15px; height: 15px; margin-left: 6px; border-radius: 50%; border: 1.5px solid currentColor; font: italic 700 10px/1 Georgia, serif; text-transform: none; letter-spacing: 0; cursor: help; vertical-align: middle; opacity: .75; outline: none; }
.tip:hover, .tip:focus { opacity: 1; }
.tip-box { position: fixed; z-index: 200; box-sizing: border-box; padding: 9px 11px; border-radius: 8px; background: #1f2937; color: #fff; box-shadow: 0 6px 18px rgba(0, 0, 0, .25); font: 400 12.5px/1.45 Arial, sans-serif; font-style: normal; font-weight: 400; text-transform: none; letter-spacing: 0; text-align: left; white-space: normal; cursor: default; }
</style>
