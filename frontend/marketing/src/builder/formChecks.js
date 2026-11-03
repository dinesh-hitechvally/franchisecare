// Checks for the form builder: does a value pass a field's rules?
// The server checks everything again when the form is saved and when someone submits; this only gives early feedback.

const TEXT = ['first_name', 'last_name', 'phone', 'text', 'textarea']

/**
 * Ready-made formats for the pattern rule. `for` lists the field types the format makes sense on.
 * The pattern is stored on the field, so the server enforces exactly what is shown here.
 */
export const PATTERNS = [
  { id: 'email', label: 'Email address (stricter)', group: 'Email', for: ['email', 'text'], pattern: '^[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(\\.[A-Za-z0-9-]+)*\\.[A-Za-z]{2,}$', example: 'name@company.com', message: 'Enter a valid email address.' },
  { id: 'phone_intl', label: 'Phone, international (+61400000000)', group: 'Phone', for: ['phone', 'text'], pattern: '^\\+[1-9][0-9]{7,14}$', example: '+61400000000', message: 'Enter the number with country code, e.g. +61400000000.' },
  { id: 'phone_au', label: 'Phone, Australian mobile', group: 'Phone', for: ['phone', 'text'], pattern: '^(?:\\+?61|0)\\s?4(?:\\s?[0-9]){8}$', example: '0412 345 678', message: 'Enter an Australian mobile number, e.g. 0412 345 678.' },
  { id: 'phone_digits', label: 'Phone, digits only (8 to 15)', group: 'Phone', for: ['phone', 'text'], pattern: '^[0-9]{8,15}$', example: '0412345678', message: 'Use digits only, 8 to 15 of them.' },
  { id: 'postcode_au', label: 'Postcode, Australia (4 digits)', group: 'Address', for: ['text'], pattern: '^[0-9]{4}$', example: '2000', message: 'Enter a 4 digit postcode.' },
  { id: 'zip_us', label: 'ZIP code, USA', group: 'Address', for: ['text'], pattern: '^[0-9]{5}(-[0-9]{4})?$', example: '90210', message: 'Enter a ZIP code like 90210 or 90210-1234.' },
  { id: 'letters', label: 'Letters only (names)', group: 'Text', for: ['first_name', 'last_name', 'text'], pattern: "^[\\p{L}][\\p{L} '\\-]*$", example: "Mary-Jane O'Neil", message: 'Use letters only.' },
  { id: 'numbers', label: 'Numbers only', group: 'Text', for: ['text'], pattern: '^[0-9]+$', example: '12345', message: 'Use numbers only.' },
  { id: 'alnum', label: 'Letters and numbers only', group: 'Text', for: ['text'], pattern: '^[\\p{L}\\p{N}]+$', example: 'ABC123', message: 'Use letters and numbers only, no spaces.' },
  { id: 'url', label: 'Web address (https://…)', group: 'Web', for: ['text'], pattern: '^https?://[^\\s/$.?#][^\\s]*$', example: 'https://example.com', message: 'Enter a web address starting with http:// or https://.' },
]
export const patternsFor = (type) => PATTERNS.filter((p) => p.for.includes(type))

/** Which rule inputs a field type offers. */
export const ruleKinds = (type) => ({
  length: TEXT.includes(type),
  pattern: ['first_name', 'last_name', 'phone', 'text', 'email'].includes(type),
  number: type === 'number',
  date: type === 'date',
  domains: type === 'email',
})
export const hasRules = (type) => Object.values(ruleKinds(type)).some(Boolean)

const num = (v) => (v === '' || v === null || v === undefined || Number.isNaN(Number(v)) ? null : Number(v))
const pos = (v) => (num(v) > 0 ? Math.floor(num(v)) : null)
export const domainList = (v) => (Array.isArray(v) ? v : String(v || '').split(/[\s,;]+/)).map((d) => d.trim().toLowerCase().replace(/^@/, '')).filter(Boolean)

/** Problems in the rules themselves (e.g. a minimum above the maximum, a pattern that does not compile). */
export function ruleProblems(f) {
  const r = f.rules || {}
  const out = []
  if (pos(r.min_length) && pos(r.max_length) && pos(r.min_length) > pos(r.max_length)) out.push('The minimum length is larger than the maximum.')
  if (num(r.min) !== null && num(r.max) !== null && f.type === 'number' && num(r.min) > num(r.max)) out.push('The minimum is larger than the maximum.')
  if (f.type === 'date' && r.min && r.max && r.min > r.max) out.push('The first date is after the last date.')
  if (r.pattern) {
    try { new RegExp(r.pattern, 'u') } catch (e) { out.push('The pattern is not a valid regular expression.') }
  }
  return out
}

/** Does this value pass the field's own rules? Returns { ok, message } (the custom message when one is set). */
export function checkValue(f, value) {
  const r = f.rules || {}
  const v = String(value ?? '')
  const fail = (text) => ({ ok: false, message: r.message || text })

  if (v === '') return f.required ? { ok: false, message: 'This field is required.' } : { ok: true, message: 'Empty is fine: the field is optional.' }
  if (f.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return { ok: false, message: 'That is not a valid email address.' }
  if (f.type === 'number' && num(v) === null) return { ok: false, message: 'That is not a number.' }
  if (f.type === 'date' && Number.isNaN(Date.parse(v))) return { ok: false, message: 'That is not a date.' }

  if (pos(r.min_length) && v.length < pos(r.min_length)) return fail(`Must be at least ${pos(r.min_length)} characters.`)
  if (pos(r.max_length) && v.length > pos(r.max_length)) return fail(`Must be at most ${pos(r.max_length)} characters.`)
  if (f.type === 'number') {
    if (num(r.min) !== null && Number(v) < num(r.min)) return fail(`Must be at least ${num(r.min)}.`)
    if (num(r.max) !== null && Number(v) > num(r.max)) return fail(`Must be at most ${num(r.max)}.`)
  }
  if (f.type === 'date') {
    if (r.min && v < r.min) return fail(`Must be on or after ${r.min}.`)
    if (r.max && v > r.max) return fail(`Must be on or before ${r.max}.`)
  }
  if (r.pattern) {
    try { if (!new RegExp(r.pattern, 'u').test(v)) return fail('Not in the expected format.') } catch (e) { /* reported as a rule problem */ }
  }
  const domains = domainList(r.allowed_domains)
  if (f.type === 'email' && domains.length && !domains.includes(v.split('@').pop().toLowerCase())) return fail(`Must be an address at ${domains.join(', ')}.`)
  if ((f.type === 'dropdown' || f.type === 'radio') && !(f.options || []).includes(v)) return { ok: false, message: 'That is not one of the choices.' }
  return { ok: true, message: 'This value is accepted.' }
}
