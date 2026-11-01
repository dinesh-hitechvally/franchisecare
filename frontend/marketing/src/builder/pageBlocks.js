// Landing-page builder: web sections, renderer and starter layouts.
// Unlike the email builder (600px tables, inline styles) this produces a full-width, responsive web page.
// A design is { kind: 'page', settings, blocks: [{ id, type, ...props }] }.

import { esc, sanitize, uid } from './blocks'

export { uid }

// single quotes only: these are written inside a style="…" attribute, where a double quote would cut the value off
export const FONTS = {
  sans: "Inter, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif",
  serif: "Georgia, 'Times New Roman', serif",
  rounded: "'Trebuchet MS', 'Segoe UI', Verdana, sans-serif",
}

export const defaultSettings = () => ({ bg: '#ffffff', text: '#1f2937', accent: '#4f46e5', accentText: '#ffffff', font: 'sans', width: 1100, radius: 10 })

const hex = (c) => (/^#[0-9a-fA-F]{3,8}$/.test(c || '') ? c : '')
/** Only normal link targets; never javascript: and friends. */
const url = (u) => (/^(https?:|mailto:|tel:|#|\/)/i.test(String(u || '').trim()) ? String(u).trim() : '#')
const lines = (t) => String(t || '').split('\n').map((l) => l.trim()).filter(Boolean)

// The server swaps <!--fc-form:ID--> for that form (0 = none chosen); a bare <!--fc-form--> is a page made before forms were chosen per section.
const formMarker = (b) => (b.form_id === undefined ? '<!--fc-form-->' : `<!--fc-form:${parseInt(b.form_id, 10) || 0}-->`)
let formNames = {}
/** The company's forms, so the editor can show which one a section uses. */
export const setForms = (list) => { formNames = Object.fromEntries((list || []).map((f) => [String(f.id), f.name])) }

// ---------------------------------------------------------------- block catalogue
const base = (over = {}) => ({ bg: '', bg2: '', color: '', pad: 80, ...over })
const year = () => new Date().getFullYear()

export const BLOCKS = {
  nav: { label: 'Navigation', icon: 'header', make: () => ({ bg: '#ffffff', bg2: '', color: '', pad: 0, brand: '{{company_name}}', links: [{ label: 'Features', url: '#features' }, { label: 'FAQ', url: '#faq' }], ctaText: 'Sign up', ctaUrl: '#form' }) },
  hero: {
    label: 'Hero', icon: 'star',
    make: () => base({ bg: '#eef2ff', pad: 96, layout: 'center', eyebrow: 'Introducing', title: 'A clear headline that says what you offer', subtitle: 'One or two sentences that explain the benefit and why someone should act now.', primaryText: 'Get started', primaryUrl: '#form', secondaryText: '', secondaryUrl: '#features', image: '', bgImage: '', overlay: 45 }),
  },
  stats: { label: 'Numbers', icon: 'chart', make: () => base({ pad: 48, items: [{ value: '10k+', label: 'Happy customers' }, { value: '4.9/5', label: 'Average rating' }, { value: '24/7', label: 'Support' }] }) },
  features: { label: 'Features', icon: 'features', make: () => base({ heading: 'Why people love it', subheading: 'A short line that introduces the list below.', columns: 3, items: [{ icon: '⚡', title: 'Fast', text: 'Describe the first benefit in a sentence or two.' }, { icon: '🔒', title: 'Secure', text: 'Describe the second benefit in a sentence or two.' }, { icon: '💬', title: 'Supported', text: 'Describe the third benefit in a sentence or two.' }] }) },
  steps: { label: 'How it works', icon: 'list', make: () => base({ bg: '#f9fafb', heading: 'How it works', items: [{ title: 'Sign up', text: 'Tell us a little about yourself.' }, { title: 'We get to work', text: 'We take care of the details.' }, { title: 'Enjoy the result', text: 'Sit back and see the difference.' }] }) },
  text: { label: 'Text', icon: 'text', make: () => base({ pad: 64, heading: 'A section heading', html: '<p>Write your content here. Use this block for the story behind your offer, details, or anything that needs a few paragraphs.</p>', align: 'left' }) },
  image: { label: 'Image', icon: 'image', make: () => base({ pad: 48, src: '', alt: '', caption: '', wide: false }) },
  split: { label: 'Image + text', icon: 'split', make: () => base({ heading: 'Tell the story', html: '<p>Explain this part of your offer next to a picture.</p>', image: '', side: 'right', btnText: '', btnUrl: '#form' }) },
  testimonials: { label: 'Testimonials', icon: 'people', make: () => base({ bg: '#f9fafb', heading: 'What customers say', items: [{ quote: 'This made everything so much easier. Highly recommended!', name: 'Alex Morgan', role: 'Owner, Example Co' }, { quote: 'Friendly, fast and great value. We will be back.', name: 'Sam Lee', role: 'Customer' }] }) },
  pricing: { label: 'Pricing', icon: 'coupon', make: () => base({ heading: 'Simple pricing', subheading: 'Pick the plan that fits.', items: [{ name: 'Starter', price: '$19', period: 'month', features: 'One thing\nAnother thing', btnText: 'Choose', btnUrl: '#form', highlight: false }, { name: 'Pro', price: '$49', period: 'month', features: 'Everything in Starter\nMore of everything\nPriority support', btnText: 'Choose', btnUrl: '#form', highlight: true }, { name: 'Team', price: '$99', period: 'month', features: 'Everything in Pro\nUnlimited users', btnText: 'Choose', btnUrl: '#form', highlight: false }] }) },
  faq: { label: 'FAQ', icon: 'template', make: () => base({ pad: 72, heading: 'Questions & answers', items: [{ q: 'How does it work?', a: 'Give a short, helpful answer.' }, { q: 'How much does it cost?', a: 'Give a short, helpful answer.' }] }) },
  cta: { label: 'Call to action', icon: 'button', make: () => base({ bg: '#4f46e5', bg2: '#7c3aed', color: '#ffffff', pad: 72, heading: 'Ready to get started?', text: 'Join today — it only takes a minute.', btnText: 'Get started', btnUrl: '#form' }) },
  form: { label: 'Form', icon: 'email', make: () => base({ bg: '#f3f4f6', form_id: '', heading: 'Get started today', text: 'Fill in the form and we will be in touch.' }) },
  spacer: { label: 'Spacer', icon: 'spacer', make: () => ({ bg: '', bg2: '', color: '', pad: 0, height: 48 }) },
  divider: { label: 'Divider', icon: 'divider', make: () => ({ bg: '', bg2: '', color: '', pad: 0 }) },
  footer: { label: 'Footer', icon: 'footer', make: () => base({ bg: '#111827', color: '#d1d5db', pad: 40, text: `© ${year()} {{company_name}}. All rights reserved.`, links: [{ label: 'Privacy', url: '#' }, { label: 'Terms', url: '#' }] }) },
  html: { label: 'Custom HTML', icon: 'code', make: () => base({ pad: 48, html: '<p>Custom HTML</p>' }) },
}

export const newBlock = (type) => ({ id: uid(), type, ...BLOCKS[type].make() })

// ---------------------------------------------------------------- settings-panel schema
// [key, label, kind, options] — kinds: text, textarea, rich, range, select, color, image, check, items
const ALIGN = { options: [['left', 'Left'], ['center', 'Centre']] }
const common = [['bg', 'Background colour', 'color'], ['bg2', 'Gradient to (optional)', 'color'], ['color', 'Text colour', 'color'], ['pad', 'Vertical spacing', 'range', { min: 0, max: 180 }]]
const cta2 = [['btnText', 'Button text', 'text'], ['btnUrl', 'Button link', 'text']]

export const FIELDS = {
  nav: [['brand', 'Brand / logo text', 'text'], ['links', 'Menu links', 'items', { sub: [['label', 'Label', 'text'], ['url', 'Link', 'text']], blank: () => ({ label: 'Link', url: '#' }) }], ['ctaText', 'Button text (blank = none)', 'text'], ['ctaUrl', 'Button link', 'text'], ['bg', 'Background colour', 'color'], ['color', 'Text colour', 'color']],
  hero: [
    ['layout', 'Layout', 'select', { options: [['center', 'Centred text'], ['split', 'Text + image']] }], ['eyebrow', 'Small label above', 'text'], ['title', 'Headline', 'text'], ['subtitle', 'Sub-headline', 'textarea'],
    ['primaryText', 'Main button text', 'text'], ['primaryUrl', 'Main button link', 'text'], ['secondaryText', 'Second button text (optional)', 'text'], ['secondaryUrl', 'Second button link', 'text'],
    ['image', 'Image (for “Text + image”)', 'image'], ['bgImage', 'Background photo (optional)', 'image'], ['overlay', 'Darken the photo', 'range', { min: 0, max: 85 }], ...common,
  ],
  stats: [['items', 'Numbers', 'items', { sub: [['value', 'Number', 'text'], ['label', 'Label', 'text']], blank: () => ({ value: '100', label: 'Label' }) }], ...common],
  features: [['heading', 'Heading', 'text'], ['subheading', 'Sub-heading', 'text'], ['columns', 'Columns', 'select', { options: [[2, '2'], [3, '3'], [4, '4']], number: true }], ['items', 'Features', 'items', { sub: [['icon', 'Emoji', 'text'], ['title', 'Title', 'text'], ['text', 'Text', 'textarea']], blank: () => ({ icon: '⭐', title: 'Feature', text: 'Describe it.' }) }], ...common],
  steps: [['heading', 'Heading', 'text'], ['items', 'Steps', 'items', { sub: [['title', 'Title', 'text'], ['text', 'Text', 'textarea']], blank: () => ({ title: 'Step', text: 'Describe it.' }) }], ...common],
  text: [['heading', 'Heading (optional)', 'text'], ['html', 'Content', 'rich'], ['align', 'Align', 'select', ALIGN], ...common],
  image: [['src', 'Image', 'image'], ['alt', 'Alt text', 'text'], ['caption', 'Caption', 'text'], ['wide', 'Extra wide', 'check'], ...common],
  split: [['heading', 'Heading', 'text'], ['html', 'Text', 'rich'], ['image', 'Image', 'image'], ['side', 'Image side', 'select', { options: [['left', 'Left'], ['right', 'Right']] }], ...cta2, ...common],
  testimonials: [['heading', 'Heading', 'text'], ['items', 'Testimonials', 'items', { sub: [['quote', 'Quote', 'textarea'], ['name', 'Name', 'text'], ['role', 'Role / company', 'text']], blank: () => ({ quote: 'A kind word.', name: 'Name', role: 'Role' }) }], ...common],
  pricing: [['heading', 'Heading', 'text'], ['subheading', 'Sub-heading', 'text'], ['items', 'Plans', 'items', { sub: [['name', 'Plan name', 'text'], ['price', 'Price', 'text'], ['period', 'Per', 'text'], ['features', 'Features (one per line)', 'textarea'], ['btnText', 'Button text', 'text'], ['btnUrl', 'Button link', 'text'], ['highlight', 'Highlight this plan', 'check']], blank: () => ({ name: 'Plan', price: '$0', period: 'month', features: 'Feature', btnText: 'Choose', btnUrl: '#form', highlight: false }) }], ...common],
  faq: [['heading', 'Heading', 'text'], ['items', 'Questions', 'items', { sub: [['q', 'Question', 'text'], ['a', 'Answer', 'textarea']], blank: () => ({ q: 'Question?', a: 'Answer.' }) }], ...common],
  cta: [['heading', 'Heading', 'text'], ['text', 'Text', 'text'], ...cta2, ...common],
  form: [['form_id', 'Form to show', 'form'], ['heading', 'Heading', 'text'], ['text', 'Text above the form', 'text'], ...common],
  spacer: [['height', 'Height', 'range', { min: 8, max: 240 }]],
  divider: [],
  footer: [['text', 'Text', 'text'], ['links', 'Links', 'items', { sub: [['label', 'Label', 'text'], ['url', 'Link', 'text']], blank: () => ({ label: 'Link', url: '#' }) }], ...common],
  html: [['html', 'HTML', 'textarea'], ...common],
}

// ---------------------------------------------------------------- rendering
const wrapStyle = (b, extra = '') => {
  const bg = hex(b.bg), bg2 = hex(b.bg2), color = hex(b.color)
  const bgCss = bg && bg2 ? `background:linear-gradient(135deg,${bg},${bg2});` : bg ? `background:${bg};` : ''
  return `${bgCss}${color ? `color:${color};` : ''}padding-top:${+b.pad || 0}px;padding-bottom:${+b.pad || 0}px;${extra}`
}
const btn = (text, href, cls = '') => (text ? `<a class="btn ${cls}" href="${esc(url(href))}">${esc(text)}</a>` : '')
const head = (title, sub) => (title || sub ? `<div class="head">${title ? `<h2>${esc(title)}</h2>` : ''}${sub ? `<p>${esc(sub)}</p>` : ''}</div>` : '')
const placeholder = (label, h = 240) => `<div class="img-ph" style="min-height:${h}px">${label}</div>`
const img = (src, alt) => `<img src="${esc(url(src))}" alt="${esc(alt)}" loading="lazy">`

export function renderBlock(b, s, edit = false) {
  switch (b.type) {
    case 'nav':
      return `<header class="nav" style="${wrapStyle(b)}"><div class="wrap nav-in"><a class="brand" href="#">${esc(b.brand)}</a><nav class="nav-links">${(b.links || []).map((l) => `<a href="${esc(url(l.url))}">${esc(l.label)}</a>`).join('')}</nav>${btn(b.ctaText, b.ctaUrl, 'sm')}</div></header>`

    case 'hero': {
      const photo = b.bgImage ? `background-image:linear-gradient(rgba(0,0,0,${(b.overlay || 0) / 100}),rgba(0,0,0,${(b.overlay || 0) / 100})),url('${esc(url(b.bgImage))}');background-size:cover;background-position:center;color:#fff;` : ''
      const text = `${b.eyebrow ? `<span class="eyebrow">${esc(b.eyebrow)}</span>` : ''}<h1>${esc(b.title)}</h1>${b.subtitle ? `<p class="lead">${esc(b.subtitle)}</p>` : ''}<div class="btns">${btn(b.primaryText, b.primaryUrl)}${btn(b.secondaryText, b.secondaryUrl, 'alt')}</div>`
      if (b.layout === 'split' && (b.image || edit)) {
        return `<section class="sec hero" style="${wrapStyle(b, photo)}"><div class="wrap split ${b.side === 'left' ? 'rev' : ''}"><div class="hero-text">${text}</div><div class="col-img">${b.image ? img(b.image, b.title) : placeholder('Image', 300)}</div></div></section>`
      }
      return `<section class="sec hero" style="${wrapStyle(b, photo)}"><div class="wrap center">${text}</div></section>`
    }

    case 'stats':
      return `<section class="sec" style="${wrapStyle(b)}"><div class="wrap"><div class="grid" style="--cols:${Math.min((b.items || []).length || 1, 4)}">${(b.items || []).map((i) => `<div class="stat"><b>${esc(i.value)}</b><span>${esc(i.label)}</span></div>`).join('')}</div></div></section>`

    case 'features':
      return `<section class="sec" id="features" style="${wrapStyle(b)}"><div class="wrap">${head(b.heading, b.subheading)}<div class="grid" style="--cols:${+b.columns || 3}">${(b.items || []).map((i) => `<div class="card"><div class="ico">${esc(i.icon)}</div><h3>${esc(i.title)}</h3><p>${esc(i.text)}</p></div>`).join('')}</div></div></section>`

    case 'steps':
      return `<section class="sec" id="how" style="${wrapStyle(b)}"><div class="wrap">${head(b.heading)}<div class="grid" style="--cols:${Math.min((b.items || []).length || 1, 4)}">${(b.items || []).map((i, n) => `<div class="step"><span class="n">${n + 1}</span><div><h3>${esc(i.title)}</h3><p class="muted">${esc(i.text)}</p></div></div>`).join('')}</div></div></section>`

    case 'text':
      return `<section class="sec" style="${wrapStyle(b)}"><div class="wrap narrow" style="text-align:${b.align === 'center' ? 'center' : 'left'}">${b.heading ? `<h2 style="margin-bottom:18px">${esc(b.heading)}</h2>` : ''}<div class="rt">${sanitize(b.html)}</div></div></section>`

    case 'image':
      if (!b.src) return edit ? `<section class="sec" style="${wrapStyle(b)}"><div class="wrap">${placeholder('Click to choose an image')}</div></section>` : ''
      return `<section class="sec" style="${wrapStyle(b)}"><div class="wrap ${b.wide ? '' : 'narrow'}"><figure>${img(b.src, b.alt)}${b.caption ? `<figcaption class="cap">${esc(b.caption)}</figcaption>` : ''}</figure></div></section>`

    case 'split': {
      const pic = b.image ? img(b.image, b.heading) : edit ? placeholder('Image', 280) : ''
      if (!pic) return `<section class="sec" style="${wrapStyle(b)}"><div class="wrap narrow">${b.heading ? `<h2>${esc(b.heading)}</h2>` : ''}<div class="rt" style="margin-top:14px">${sanitize(b.html)}</div>${b.btnText ? `<div style="margin-top:22px">${btn(b.btnText, b.btnUrl)}</div>` : ''}</div></section>`
      return `<section class="sec" style="${wrapStyle(b)}"><div class="wrap split ${b.side === 'left' ? 'rev' : ''}"><div>${b.heading ? `<h2>${esc(b.heading)}</h2>` : ''}<div class="rt" style="margin-top:14px">${sanitize(b.html)}</div>${b.btnText ? `<div style="margin-top:22px">${btn(b.btnText, b.btnUrl)}</div>` : ''}</div><div class="col-img">${pic}</div></div></section>`
    }

    case 'testimonials':
      return `<section class="sec" style="${wrapStyle(b)}"><div class="wrap">${head(b.heading)}<div class="grid" style="--cols:${Math.min((b.items || []).length || 1, 3)}">${(b.items || []).map((i) => `<figure class="card quote"><blockquote>“${esc(i.quote)}”</blockquote><figcaption><b>${esc(i.name)}</b>${esc(i.role)}</figcaption></figure>`).join('')}</div></div></section>`

    case 'pricing':
      return `<section class="sec" style="${wrapStyle(b)}"><div class="wrap">${head(b.heading, b.subheading)}<div class="grid" style="--cols:${Math.min((b.items || []).length || 1, 3)}">${(b.items || []).map((p) => `<div class="card plan ${p.highlight ? 'hl' : ''}"><h3>${esc(p.name)}</h3><div class="price">${esc(p.price)}${p.period ? `<small> / ${esc(p.period)}</small>` : ''}</div><ul>${lines(p.features).map((l) => `<li>✓ ${esc(l)}</li>`).join('')}</ul>${btn(p.btnText, p.btnUrl)}</div>`).join('')}</div></div></section>`

    case 'faq':
      return `<section class="sec" id="faq" style="${wrapStyle(b)}"><div class="wrap narrow">${head(b.heading)}${(b.items || []).map((i) => `<details class="faq"><summary>${esc(i.q)}</summary><p>${esc(i.a)}</p></details>`).join('')}</div></section>`

    case 'cta':
      return `<section class="sec cta" style="${wrapStyle(b)}"><div class="wrap center">${b.heading ? `<h2>${esc(b.heading)}</h2>` : ''}${b.text ? `<p class="lead">${esc(b.text)}</p>` : ''}<div class="btns">${btn(b.btnText, b.btnUrl, 'inv')}</div></div></section>`

    case 'form': {
      const name = formNames[String(b.form_id)]
      const slot = edit ? `<div class="form-ph">${name ? `Form: <b>${esc(name)}</b>` : 'No form chosen yet'}<br><small>${name ? 'Shown here on the published page.' : 'Pick one in the panel on the right.'}</small></div>` : formMarker(b)
      return `<section class="sec" id="form" style="${wrapStyle(b)}"><div class="wrap narrow center">${b.heading ? `<h2>${esc(b.heading)}</h2>` : ''}${b.text ? `<p class="lead" style="margin-bottom:28px">${esc(b.text)}</p>` : ''}<div style="text-align:left">${slot}</div></div></section>`
    }

    case 'spacer':
      return `<div style="height:${+b.height || 0}px"></div>`

    case 'divider':
      return '<div class="wrap" style="padding-top:8px;padding-bottom:8px"><hr></div>'

    case 'footer':
      return `<footer class="foot" style="${wrapStyle(b)}"><div class="wrap"><div class="foot-links">${(b.links || []).map((l) => `<a href="${esc(url(l.url))}">${esc(l.label)}</a>`).join('')}</div><p>${esc(b.text)}</p></div></footer>`

    case 'html':
      return `<section class="sec" style="${wrapStyle(b)}"><div class="wrap">${sanitize(b.html)}</div></section>`
  }
  return ''
}

export function pageCss() {
  return `.pg{container-type:inline-size;container-name:pg;font-family:var(--font);color:var(--text);background:var(--bg);line-height:1.6;-webkit-font-smoothing:antialiased;overflow-x:hidden}
.pg *,.pg *::before,.pg *::after{box-sizing:border-box}
.pg h1,.pg h2,.pg h3,.pg p,.pg figure,.pg blockquote,.pg ul,.pg ol{margin:0}
.pg img{max-width:100%;height:auto;display:block;border-radius:var(--r)}
.pg a{color:inherit}
.pg .wrap{max-width:var(--w);margin:0 auto;padding:0 28px}
.pg .narrow{max-width:760px}
.pg .center{text-align:center}
.pg h1{font-size:clamp(32px,5.2cqi,58px);line-height:1.1;font-weight:800;letter-spacing:-.02em}
.pg h2{font-size:clamp(26px,3.4cqi,38px);line-height:1.2;font-weight:800;letter-spacing:-.01em}
.pg h3{font-size:19px;font-weight:700;line-height:1.3}
.pg .lead{font-size:clamp(17px,1.9cqi,21px);opacity:.88;max-width:640px;margin:18px auto 0}
.pg .eyebrow{display:inline-block;font-size:13px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;opacity:.85;margin-bottom:14px}
.pg .btn{display:inline-block;padding:14px 28px;border-radius:var(--r);background:var(--accent);color:var(--accentText);font-weight:700;text-decoration:none;border:2px solid var(--accent);font-size:16px;line-height:1.2}
.pg .btn.alt{background:transparent;border-color:currentColor;color:inherit}
.pg .btn.inv{background:#fff;color:var(--accent);border-color:#fff}
.pg .btn.sm{padding:10px 18px;font-size:14px}
.pg .btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:28px}
.pg .split .btns{justify-content:flex-start}
.pg .sec{padding:80px 0}
.pg .head{text-align:center;max-width:680px;margin:0 auto 44px}
.pg .head p{margin-top:12px;opacity:.75}
.pg .muted{opacity:.7;font-size:15px;margin-top:6px}
.pg .nav-in{display:flex;align-items:center;gap:28px;padding-top:16px;padding-bottom:16px}
.pg .brand{font-weight:800;font-size:20px;text-decoration:none;margin-right:auto}
.pg .nav-links{display:flex;gap:24px}
.pg .nav-links a{text-decoration:none;font-weight:500;opacity:.8}
.pg .grid{display:grid;grid-template-columns:repeat(var(--cols,3),1fr);gap:24px}
.pg .card{background:#fff;color:#1f2937;border-radius:calc(var(--r) + 4px);padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.08),0 8px 24px rgba(0,0,0,.05)}
.pg .card p{margin-top:8px;color:#6b7280;font-size:15px}
.pg .ico{font-size:34px;line-height:1;margin-bottom:14px}
.pg .stat{text-align:center}
.pg .stat b{display:block;font-size:clamp(30px,4cqi,46px);font-weight:800;color:var(--accent);line-height:1.1}
.pg .stat span{opacity:.75;font-size:15px}
.pg .step{display:flex;gap:16px}
.pg .step .n{flex:none;width:40px;height:40px;border-radius:50%;background:var(--accent);color:var(--accentText);display:grid;place-items:center;font-weight:800}
.pg .quote blockquote{font-size:17px;line-height:1.6}
.pg .quote figcaption{margin-top:16px;font-size:14px;color:#6b7280}
.pg .quote figcaption b{display:block;color:#1f2937}
.pg .plan{text-align:center;display:flex;flex-direction:column;gap:14px}
.pg .plan.hl{outline:3px solid var(--accent)}
.pg .price{font-size:40px;font-weight:800}
.pg .price small{font-size:15px;font-weight:500;color:#6b7280}
.pg .plan ul{list-style:none;padding:0;color:#4b5563;font-size:15px;display:grid;gap:8px}
.pg .faq{border-bottom:1px solid rgba(0,0,0,.12);padding:16px 0}
.pg .faq summary{cursor:pointer;font-weight:700;font-size:17px}
.pg .faq p{margin-top:10px;opacity:.8}
.pg .split{display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:center}
.pg .split.rev .col-img{order:-1}
.pg .hero-text{text-align:left}
.pg .hero-text .lead{margin-left:0}
.pg .rt p{margin-bottom:14px}
.pg .rt ul,.pg .rt ol{padding-left:22px;margin-bottom:14px}
.pg .cap{text-align:center;font-size:14px;opacity:.7;margin-top:10px}
.pg .img-ph{background:#e5e7eb;color:#6b7280;display:grid;place-items:center;border-radius:var(--r);font-size:14px;width:100%}
.pg .form-ph{border:2px dashed currentColor;opacity:.55;border-radius:var(--r);padding:36px 16px;text-align:center}
.pg .foot{padding:40px 0;font-size:14px;text-align:center}
.pg .foot-links{display:flex;gap:20px;justify-content:center;flex-wrap:wrap;margin-bottom:12px}
.pg .foot p{opacity:.7}
.pg hr{border:0;border-top:1px solid rgba(0,0,0,.12)}
.pg .credits{font-size:12px;opacity:.6;text-align:center;padding:14px 20px}
@container pg (max-width:760px){.pg .grid{grid-template-columns:1fr!important}.pg .split{grid-template-columns:1fr;gap:28px}.pg .nav-links{display:none}.pg .sec{padding-top:56px!important;padding-bottom:56px!important}.pg .wrap{padding:0 20px}.pg .hero-text{text-align:center}.pg .hero-text .lead{margin-left:auto}.pg .split .btns{justify-content:center}}
@container pg (min-width:761px) and (max-width:1000px){.pg .grid{grid-template-columns:repeat(2,1fr)!important}}`
}

export function pageVars(s) {
  const c = (v, d) => hex(v) || d
  return `--w:${Math.max(720, Math.min(1400, +s.width || 1100))}px;--r:${Math.max(0, Math.min(30, +s.radius || 0))}px;--bg:${c(s.bg, '#ffffff')};--text:${c(s.text, '#1f2937')};--accent:${c(s.accent, '#4f46e5')};--accentText:${c(s.accentText, '#ffffff')};--font:${FONTS[s.font] || FONTS.sans}`
}

/** Just the sections (and photo credits) of a page, without the surrounding document. */
export function renderBody(design, edit = false) {
  const s = { ...defaultSettings(), ...(design?.settings || {}) }
  const body = (design?.blocks || []).map((b) => renderBlock(b, s, edit)).join('\n')
  if (edit) return body
  const credits = (design?.credits || []).map((c) => `<a href="${esc(url(c.author_url))}">${esc(c.author)}</a> on <a href="${esc(url(c.link))}">${esc(c.source)}</a>${c.license ? ` (${esc(String(c.license).trim())})` : ''}`)

  return `${body}\n${credits.length ? `<p class="credits">Photo credits: ${credits.join(' · ')}</p>` : ''}`
}

/** The saved HTML for a page. */
export function renderPage(design) {
  const s = { ...defaultSettings(), ...(design?.settings || {}) }

  return `<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Landing page</title>
<style>html{scroll-behavior:smooth}body{margin:0}${pageCss()}</style></head>
<body><div class="pg" style="${pageVars(s)}">
${renderBody(design)}
</div></body></html>`
}

// ---------------------------------------------------------------- starter layouts
const B = (type, over = {}) => ({ ...newBlock(type), ...over })
const S = (over) => ({ ...defaultSettings(), ...over })
const L = (settings, blocks) => ({ kind: 'page', settings, blocks })

export const LAYOUTS = [
  { id: 'blank', name: 'Blank', make: () => L(S({}), [B('footer')]) },

  {
    id: 'lead-magnet', name: 'Lead magnet (free guide)',
    make: () => L(S({ accent: '#0f766e', bg: '#ffffff' }), [
      B('nav', { links: [{ label: 'What’s inside', url: '#features' }, { label: 'FAQ', url: '#faq' }], ctaText: 'Get the guide' }),
      B('hero', { bg: '#ecfdf5', bg2: '#d1fae5', color: '#064e3b', eyebrow: 'Free download', title: 'The 10-minute guide to getting more customers', subtitle: 'Simple, proven steps you can use this week. Enter your email and we’ll send it straight to your inbox.', primaryText: 'Send me the guide', secondaryText: 'See what’s inside', pad: 100 }),
      B('stats', { items: [{ value: '12', label: 'Practical tips' }, { value: '10 min', label: 'To read' }, { value: '5,000+', label: 'Downloads' }] }),
      B('features', { heading: 'What’s inside', subheading: 'Everything you need, nothing you don’t.', items: [{ icon: '🎯', title: 'Find the right people', text: 'Work out who your best customers are and where they spend time.' }, { icon: '✍️', title: 'Say the right thing', text: 'Plain-English message templates you can copy and use.' }, { icon: '📈', title: 'Measure what works', text: 'A simple tracker so you know where to put your effort.' }] }),
      B('testimonials', { heading: 'Readers love it' }),
      B('form', { heading: 'Get your free guide', text: 'We’ll email it to you right away. No spam, ever.', bg: '#ecfdf5' }),
      B('faq', { items: [{ q: 'Is it really free?', a: 'Yes. The guide is free and you can unsubscribe from our emails at any time.' }, { q: 'How will I receive it?', a: 'It lands in your inbox within a couple of minutes of signing up.' }] }),
      B('footer'),
    ]),
  },

  {
    id: 'product-launch', name: 'Product launch',
    make: () => L(S({ accent: '#6d28d9' }), [
      B('nav', { links: [{ label: 'Features', url: '#features' }, { label: 'Pricing', url: '#pricing' }, { label: 'FAQ', url: '#faq' }], ctaText: 'Join the waitlist' }),
      B('hero', { bg: '#1e1b4b', bg2: '#6d28d9', color: '#ffffff', eyebrow: 'Now in early access', title: 'Meet the easiest way to run your business', subtitle: 'One simple tool that replaces the spreadsheets, sticky notes and guesswork. Be one of the first to try it.', primaryText: 'Get early access', secondaryText: 'See features', pad: 110 }),
      B('stats', { bg: '#f5f3ff', items: [{ value: '3×', label: 'Faster setup' }, { value: '98%', label: 'Customer satisfaction' }, { value: '2 min', label: 'To get started' }] }),
      B('features', { columns: 4, heading: 'Everything in one place', subheading: 'Powerful where it matters, simple everywhere else.', items: [{ icon: '⚡', title: 'Lightning fast', text: 'Set up in minutes, not days.' }, { icon: '🔒', title: 'Private & secure', text: 'Your data stays yours.' }, { icon: '🔗', title: 'Works with your tools', text: 'Connects to what you already use.' }, { icon: '💬', title: 'Real human support', text: 'Friendly help when you need it.' }] }),
      B('testimonials'),
      B('pricing', { bg: '#f9fafb', heading: 'Simple, honest pricing' }),
      B('cta', { bg: '#6d28d9', bg2: '#4f46e5', heading: 'Be first in line', text: 'Early members get 3 months free.' }),
      B('form', { heading: 'Join the waitlist', text: 'Leave your details and we’ll be in touch with your invite.' }),
      B('footer'),
    ]),
  },

  {
    id: 'event', name: 'Event / webinar registration',
    make: () => L(S({ accent: '#e11d48' }), [
      B('nav', { links: [], ctaText: 'Reserve my seat' }),
      B('hero', { bg: '#0f172a', bg2: '#1e293b', color: '#ffffff', eyebrow: 'Live webinar · Thursday 12 March · 11am', title: 'How to double your bookings in 30 days', subtitle: 'A free, practical session with real examples you can copy. Live Q&A at the end.', primaryText: 'Reserve my seat', secondaryText: '', pad: 110 }),
      B('stats', { items: [{ value: '45 min', label: 'Live session' }, { value: 'Free', label: 'To attend' }, { value: '100', label: 'Seats available' }, { value: 'Replay', label: 'Sent to everyone' }] }),
      B('features', { heading: 'What you’ll learn', items: [{ icon: '1️⃣', title: 'Fill your calendar', text: 'The three channels that bring in bookings fastest.' }, { icon: '2️⃣', title: 'Turn enquiries into customers', text: 'Follow-up scripts that actually get replies.' }, { icon: '3️⃣', title: 'Keep them coming back', text: 'Simple habits that build loyalty.' }] }),
      B('testimonials', { heading: 'What past attendees said' }),
      B('form', { heading: 'Save your seat', text: 'Register now — we’ll email your joining link and a reminder.', bg: '#fff1f2' }),
      B('faq', { items: [{ q: 'Will there be a recording?', a: 'Yes, everyone who registers gets the replay.' }, { q: 'Do I need any software?', a: 'Just a web browser. We’ll send you the link.' }] }),
      B('footer'),
    ]),
  },

  {
    id: 'coming-soon', name: 'Coming soon',
    make: () => L(S({ accent: '#f59e0b', accentText: '#111827', bg: '#0b1020', text: '#e5e7eb' }), [
      B('hero', { bg: '#0b1020', bg2: '#312e81', color: '#ffffff', eyebrow: 'Coming soon', title: 'Something great is on its way', subtitle: 'We’re putting the finishing touches on something new. Leave your email and be the first to know when we launch.', primaryText: 'Notify me', primaryUrl: '#form', pad: 150 }),
      B('form', { bg: '#0b1020', color: '#e5e7eb', heading: 'Get notified at launch', text: 'One email, the day we open. That’s it.', pad: 70 }),
      B('footer', { bg: '#060913' }),
    ]),
  },

  {
    id: 'service', name: 'Service business',
    make: () => L(S({ accent: '#2563eb' }), [
      B('nav', { links: [{ label: 'Services', url: '#features' }, { label: 'How it works', url: '#how' }, { label: 'FAQ', url: '#faq' }], ctaText: 'Book now' }),
      B('hero', { bg: '#eff6ff', color: '#1e3a8a', eyebrow: 'Trusted locally', title: 'Friendly, reliable service you can count on', subtitle: 'From quick fixes to full projects — done properly, on time and at a fair price.', primaryText: 'Book a free consultation', secondaryText: 'Our services', secondaryUrl: '#features', pad: 100 }),
      B('features', { heading: 'What we do', subheading: 'Straightforward services with no surprises.', items: [{ icon: '🛠️', title: 'Repairs & fixes', text: 'Fast, tidy work for the everyday jobs.' }, { icon: '🏠', title: 'Full projects', text: 'Planning and delivery from start to finish.' }, { icon: '📞', title: 'Ongoing care', text: 'Regular check-ups so small issues stay small.' }] }),
      B('steps', { heading: 'How it works' }),
      B('testimonials', { heading: 'Kind words from our customers' }),
      B('form', { heading: 'Book a free consultation', text: 'Tell us what you need and we’ll get back to you within one business day.', bg: '#eff6ff' }),
      B('faq', { items: [{ q: 'Do you offer free quotes?', a: 'Yes — every consultation is free with no obligation.' }, { q: 'What areas do you cover?', a: 'Add the suburbs or regions you serve here.' }] }),
      B('footer'),
    ]),
  },
]
