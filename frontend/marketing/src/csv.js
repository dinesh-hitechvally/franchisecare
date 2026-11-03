// Saves a table as a CSV file in the browser. `rows` is a list of arrays, `headers` the column titles.
// Anything that starts with = + - @ is written as text, so a spreadsheet never runs it as a formula.
const cell = (v) => {
  let s = v === null || v === undefined ? '' : String(v)
  if (/^[=+\-@]/.test(s) && Number.isNaN(Number(s))) s = "'" + s
  return /[",\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s
}

export function exportCsv(filename, headers, rows) {
  const text = [headers, ...rows].map((r) => r.map(cell).join(',')).join('\r\n')
  const url = URL.createObjectURL(new Blob(['﻿' + text], { type: 'text/csv;charset=utf-8' }))
  const a = Object.assign(document.createElement('a'), { href: url, download: filename })
  document.body.appendChild(a)
  a.click()
  a.remove()
  URL.revokeObjectURL(url)
}
