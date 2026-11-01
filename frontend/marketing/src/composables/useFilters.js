import { reactive, computed } from 'vue'

/**
 * Client-side filters for a list that is already loaded.
 *   text:  row => string | string[]      what the search box looks in
 *   match: { key: row => value }         one entry per dropdown; '' means "all"
 * Returns the reactive filter values `f` (bind inputs to f.q and f[key]), the filtered rows, and clear().
 */
export function useFilters(rows, { text = () => '', match = {} } = {}) {
  const f = reactive({ q: '', ...Object.fromEntries(Object.keys(match).map((k) => [k, ''])) })
  const dirty = computed(() => Object.values(f).some((v) => v !== ''))
  const shown = computed(() => {
    const q = f.q.trim().toLowerCase()
    return rows.value.filter((r) => {
      if (q && ![].concat(text(r)).some((t) => String(t ?? '').toLowerCase().includes(q))) return false
      return Object.entries(match).every(([k, fn]) => f[k] === '' || String(fn(r)) === String(f[k]))
    })
  })
  const clear = () => Object.keys(f).forEach((k) => (f[k] = ''))

  return { f, shown, dirty, clear }
}
