<script setup>
// The "⋮" actions menu at the end of every table row. `items` are the row's own actions; when `history` is given
// ({ type, id, title }) the menu ends with "Change history", which opens the row's full history.
import { ref, computed, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import HistoryModal from './HistoryModal.vue'

const props = defineProps({ items: { type: Array, default: () => [] }, history: Object })
const router = useRouter()
const open = ref(false)
const showHistory = ref(false)
const pos = ref({ top: 0, left: 0 })
const btn = ref(null)

const visible = computed(() => props.items.filter((i) => i.show !== false))

function toggle() {
  if (open.value) return close()
  const r = btn.value.getBoundingClientRect()
  const below = window.innerHeight - r.bottom > 260
  pos.value = { top: below ? r.bottom + 4 : Math.max(8, r.top - 4 - (visible.value.length + 1) * 38), left: Math.max(8, r.right - 190) }
  open.value = true
  setTimeout(() => {
    document.addEventListener('click', close)
    window.addEventListener('scroll', close, true)
    window.addEventListener('keydown', onKey)
  })
}
function close() {
  open.value = false
  document.removeEventListener('click', close)
  window.removeEventListener('scroll', close, true)
  window.removeEventListener('keydown', onKey)
}
const onKey = (e) => e.key === 'Escape' && close()
function pick(i) {
  close()
  if (i.to) router.push(i.to)
  else i.run?.()
}
onBeforeUnmount(close)
</script>

<template>
  <span class="rm">
    <button ref="btn" type="button" class="dots" title="Actions" aria-label="Actions" :aria-expanded="open" @click.stop="toggle">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z" /></svg>
    </button>
    <Teleport to="body">
      <div v-if="open" class="rm-menu" :style="{ top: pos.top + 'px', left: pos.left + 'px' }" @click.stop>
        <button v-for="(i, n) in visible" :key="n" type="button" :class="{ danger: i.danger }" @click="pick(i)">{{ i.label }}</button>
        <hr v-if="visible.length && history" />
        <button v-if="history" type="button" @click="close(); showHistory = true">Change history</button>
      </div>
    </Teleport>
    <Teleport to="body">
      <HistoryModal v-if="showHistory" :type="history.type" :id="history.id" :title="history.title" @close="showHistory = false" />
    </Teleport>
  </span>
</template>

<style scoped>
.dots { background: none; border: 0; padding: 4px; border-radius: 50%; color: #6b7280; cursor: pointer; line-height: 0; }
.dots:hover { background: #eceff7; color: #3f51b5; }
</style>
<style>
.rm-menu { position: fixed; z-index: 3000; min-width: 190px; background: #fff; border-radius: 6px; box-shadow: 0 4px 18px rgba(0, 0, 0, .22); padding: 6px 0; text-align: left; }
.rm-menu button { display: block; width: 100%; background: none; border: 0; text-align: left; padding: 9px 16px; font: inherit; font-size: 14px; color: #333; cursor: pointer; white-space: nowrap; }
.rm-menu button:hover { background: #f1f3fb; }
.rm-menu button.danger { color: #c62828; }
.rm-menu hr { border: 0; border-top: 1px solid #eceff1; margin: 6px 0; }
</style>
