<script setup>
import { fmtDate } from '../format'
import { computed } from 'vue'

// series: [{ name, color, points: [{ date, value }] }]  — all series share the same dates.
const props = defineProps({ series: { type: Array, default: () => [] }, height: { type: Number, default: 220 } })

const W = 700
const pad = { l: 36, r: 10, t: 12, b: 26 }
const max = computed(() => Math.max(4, ...props.series.flatMap((s) => s.points.map((p) => p.value))))
const niceMax = computed(() => { const m = max.value; const step = 10 ** Math.floor(Math.log10(m)); return Math.ceil(m / step) * step })
const n = computed(() => props.series[0]?.points.length || 0)
const x = (i) => pad.l + (n.value <= 1 ? 0 : (i * (W - pad.l - pad.r)) / (n.value - 1))
const y = (v) => props.height - pad.b - (v / niceMax.value) * (props.height - pad.t - pad.b)
const path = (s) => s.points.map((p, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(p.value).toFixed(1)}`).join(' ')
const area = (s) => `${path(s)} L${x(n.value - 1)},${props.height - pad.b} L${x(0)},${props.height - pad.b} Z`
const ticks = computed(() => [0, 0.25, 0.5, 0.75, 1].map((t) => ({ v: Math.round(niceMax.value * t), y: y(niceMax.value * t) })))
const labels = computed(() => {
  const pts = props.series[0]?.points || []
  if (!pts.length) return []
  const idx = [...new Set([0, Math.floor(pts.length / 2), pts.length - 1])]
  return idx.map((i) => ({ i, text: fmtDate(pts[i].date) }))
})
</script>

<template>
  <div>
    <svg :viewBox="`0 0 ${W} ${height}`" width="100%" role="img" aria-label="Line chart">
      <g v-for="t in ticks" :key="t.v">
        <line :x1="pad.l" :x2="W - pad.r" :y1="t.y" :y2="t.y" stroke="#e5e7eb" />
        <text :x="pad.l - 6" :y="t.y + 4" text-anchor="end" font-size="11" fill="#9ca3af">{{ t.v }}</text>
      </g>
      <text v-for="l in labels" :key="l.i" :x="x(l.i)" :y="height - 6" :text-anchor="l.i === 0 ? 'start' : l.i === n - 1 ? 'end' : 'middle'" font-size="11" fill="#9ca3af">{{ l.text }}</text>
      <template v-for="s in series" :key="s.name">
        <path v-if="series.length === 1" :d="area(s)" :fill="s.color" opacity=".12" />
        <path :d="path(s)" fill="none" :stroke="s.color" stroke-width="2.2" stroke-linejoin="round" />
        <circle v-for="(p, i) in s.points" :key="i" :cx="x(i)" :cy="y(p.value)" r="6" fill="transparent"><title>{{ s.name }} · {{ p.date }}: {{ p.value }}</title></circle>
      </template>
    </svg>
    <div v-if="series.length > 1" class="row" style="justify-content: center; gap: 18px">
      <span v-for="s in series" :key="s.name" class="legend"><i :style="{ background: s.color }"></i>{{ s.name }}</span>
    </div>
  </div>
</template>

<style scoped>
.legend { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #555; }
.legend i { width: 12px; height: 4px; border-radius: 2px; display: inline-block; }
</style>
