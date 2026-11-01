// How dates are shown everywhere in the app (in the viewer's own time zone):
//   date       Y-m-d        2026-11-01
//   date time  Y-m-d h:i A  2026-11-01 01:28 PM
// System times are stored in UTC and shown in the viewer's time zone. A time a person types in (e.g. a campaign schedule) is stored
// exactly as typed and shown exactly as stored (fmtStored), with no time zone conversion.
const p = (n) => String(n).padStart(2, '0')

const parse = (v) => {
  if (v === null || v === undefined || v === '') return null
  // a bare "2026-11-01" is a calendar date, not a moment: keep it on that day whatever the time zone
  const d = typeof v === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(v) ? new Date(`${v}T00:00`) : new Date(v)
  return Number.isNaN(d.getTime()) ? null : d
}

export function fmtDate(v, empty = '') {
  const d = parse(v)
  return d ? `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}` : empty
}

export function fmtDateTime(v, empty = '') {
  const d = parse(v)
  if (!d) return empty
  const h = d.getHours()

  return `${fmtDate(d)} ${p(h % 12 || 12)}:${p(d.getMinutes())} ${h < 12 ? 'AM' : 'PM'}`
}

/**
 * A date-time shown exactly as it is stored, whatever the viewer's time zone: "2026-11-02T06:00:00.000000Z" or "2026-11-02 06:00:00"
 * -> "2026-11-02 06:00 AM". For times people typed in, which are never converted.
 */
export function fmtStored(v, empty = '') {
  const m = /^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/.exec(typeof v === 'string' ? v : '')
  if (!m) return empty
  const h = Number(m[4])

  return `${m[1]}-${m[2]}-${m[3]} ${p(h % 12 || 12)}:${m[5]} ${h < 12 ? 'AM' : 'PM'}`
}

/** The stored value as the "YYYY-MM-DDTHH:mm" an input holds, again without any conversion. */
export const asEntered = (v) => (/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}/.test(v || '') ? String(v).slice(0, 10) + 'T' + String(v).slice(11, 16) : '')

/** "2026-11-01 01:28 PM" (what people type) -> "2026-11-01T13:28" (what the form keeps); null when it is not that format. */
export function parseDateTime(text) {
  const m = /^(\d{4})-(\d{2})-(\d{2})\s+(\d{1,2}):(\d{2})\s*(AM|PM)$/i.exec((text || '').trim())
  if (!m) return null
  let h = Number(m[4])
  if (h < 1 || h > 12 || Number(m[5]) > 59) return null
  h = (h % 12) + (m[6].toUpperCase() === 'PM' ? 12 : 0)
  const out = `${m[1]}-${m[2]}-${m[3]}T${p(h)}:${m[5]}`

  return fmtDate(out.slice(0, 10)) === `${m[1]}-${m[2]}-${m[3]}` ? out : null // rejects 2026-02-31
}

export const isDateText = (t) => /^\d{4}-\d{2}-\d{2}$/.test(t || '') && fmtDate(t) === t
