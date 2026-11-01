// Block definitions + HTML rendering for the visual email designer.
// A design is { settings: {...}, blocks: [{ id, type, ...props }] } and renders to table-based email HTML.

export const uid = () => Math.random().toString(36).slice(2, 9)

export const esc = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')

/** Strip scripts, handlers and javascript: urls from user-entered HTML. */
export function sanitize(html) {
  const doc = new DOMParser().parseFromString(`<div>${html || ''}</div>`, 'text/html')
  doc.querySelectorAll('script,iframe,object,embed,link,meta,style,form').forEach((n) => n.remove())
  doc.querySelectorAll('*').forEach((el) => {
    for (const a of [...el.attributes]) {
      if (/^on/i.test(a.name) || (/^(href|src|xlink:href)$/i.test(a.name) && /^\s*javascript:/i.test(a.value))) el.removeAttribute(a.name)
    }
  })
  return doc.body.firstChild.innerHTML
}

export const FONTS = ['Arial, Helvetica, sans-serif', 'Georgia, serif', 'Verdana, sans-serif', 'Tahoma, sans-serif', "'Trebuchet MS', sans-serif", "'Times New Roman', serif"]

export const defaultSettings = () => ({
  bg: '#f4f5f7', contentBg: '#ffffff', width: 600, radius: 8, font: FONTS[0], textColor: '#333333', linkColor: '#3f51b5', preheader: '',
})

const pad = (y = 16, x = 24) => ({ padY: y, padX: x, bg: '' })

export const BLOCKS = {
  header: { label: 'Header', icon: 'header', make: () => ({ text: '{{company_name}}', align: 'center', size: 22, color: '#111111', ...pad(22, 24), bg: '#ffffff' }) },
  hero: {
    label: 'Hero banner', icon: 'star',
    make: () => ({
      emoji: '🎉', eyebrow: 'ANNOUNCEMENT', title: 'Your big headline', subtitle: 'A short, friendly line that explains the message.',
      btnText: 'Learn more', btnUrl: 'https://example.com', btnBg: '#ffffff', btnColor: '#3f51b5', color: '#ffffff', subColor: '#e8eaf6',
      size: 40, align: 'center', image: '', padY: 56, padX: 32, bg: '#3f51b5',
    }),
  },
  heading: { label: 'Heading', icon: 'heading', make: () => ({ text: 'Your headline here', size: 28, align: 'center', color: '', ...pad() }) },
  text: { label: 'Text', icon: 'text', make: () => ({ html: '<p>Write your message here. You can use merge tags like {{first_name}}.</p>', align: 'left', size: 16, color: '', ...pad() }) },
  image: { label: 'Image', icon: 'image', make: () => ({ src: '', alt: '', link: '', align: 'center', width: 100, radius: 0, caption: '', ...pad(8, 0) }) },
  button: { label: 'Button', icon: 'button', make: () => ({ text: 'Click here', url: 'https://example.com', btnBg: '#3f51b5', color: '#ffffff', align: 'center', radius: 4, ...pad(16, 24) }) },
  // Email apps can't run a form, so this is a card whose button opens the chosen form's hosted page (form_url is filled in when a form is picked).
  form: { label: 'Form', icon: 'email', make: () => ({ form_id: '', form_url: '', heading: 'We’d love to hear from you', text: 'It only takes a minute to fill in.', btnText: 'Open the form', btnBg: '#3f51b5', color: '#ffffff', boxBg: '#f3f4f6', align: 'center', radius: 4, ...pad(20, 24) }) },
  features: {
    label: '3 Features', icon: 'features',
    make: () => ({
      items: [
        { icon: '⭐', title: 'Feature one', text: 'A short description of this point.' },
        { icon: '🚀', title: 'Feature two', text: 'A short description of this point.' },
        { icon: '💡', title: 'Feature three', text: 'A short description of this point.' },
      ],
      ...pad(24, 16),
    }),
  },
  coupon: { label: 'Coupon code', icon: 'coupon', make: () => ({ label: 'Use code', code: 'SAVE10', note: 'Valid for 7 days', boxBg: '#fff8e1', border: '#ff9800', color: '#222222', ...pad(16, 24) }) },
  split: { label: 'Image + Text', icon: 'split', make: () => ({ src: '', alt: '', side: 'left', html: '<p><b>Subheading</b></p><p>Tell readers more here.</p>', ...pad(16, 24) }) },
  divider: { label: 'Divider', icon: 'divider', make: () => ({ color: '#e5e7eb', thickness: 1, ...pad(12, 24) }) },
  spacer: { label: 'Spacer', icon: 'spacer', make: () => ({ height: 32, padY: 0, padX: 0, bg: '' }) },
  social: { label: 'Social links', icon: 'social', make: () => ({ links: [{ name: 'Facebook', url: '' }, { name: 'Instagram', url: '' }, { name: 'Website', url: '' }], ...pad() }) },
  footer: {
    label: 'Footer', icon: 'footer',
    make: () => ({ html: '<p>{{company_name}}<br>You are receiving this email because you subscribed.<br><a href="{{unsubscribe_url}}">Unsubscribe</a></p>', color: '#6b7280', ...pad(24, 24) }),
  },
  html: { label: 'Custom HTML', icon: 'code', make: () => ({ html: '<p>Custom HTML</p>', ...pad(8, 24) }) },
}

export const newBlock = (type) => ({ id: uid(), type, ...BLOCKS[type].make() })

const styleLinks = (html, color) => html.replace(/<a (?![^>]*style=)/gi, `<a style="color:${color};" `)
const styleParas = (html) => html.replace(/<p(?![^>]*style=)/gi, '<p style="margin:0 0 12px;"')

const placeholder = (label, h = 160) =>
  `<div style="background:#eceff1;color:#78909c;text-align:center;font:14px Arial,sans-serif;height:${h}px;line-height:${h}px;border-radius:4px">${label}</div>`

const button = (text, url, bg, color, radius, font) =>
  `<a href="${esc(url)}" target="_blank" style="display:inline-block;background:${bg};color:${color};font-family:${font};font-weight:700;font-size:16px;text-decoration:none;padding:14px 32px;border-radius:${radius}px;">${esc(text)}</a>`

/** Inner markup of one block. `edit` shows placeholders for empty media; the sent email omits them. */
export function renderBlock(b, s, edit = false) {
  const font = s.font
  const wrap = (inner) => `<div style="background:${b.bg || 'transparent'};padding:${b.padY}px ${b.padX}px;">${inner}</div>`
  switch (b.type) {
    case 'header':
      return wrap(`<div style="font-family:${font};font-weight:800;font-size:${b.size}px;letter-spacing:2px;text-transform:uppercase;color:${b.color};text-align:${b.align};">${esc(b.text)}</div>`)

    case 'hero': {
      const bgImg = b.image ? `background-image:url('${esc(b.image)}');background-size:cover;background-position:center;` : ''
      const parts = []
      if (b.emoji) parts.push(`<div style="font-size:56px;line-height:1.1;margin-bottom:14px;">${esc(b.emoji)}</div>`)
      if (b.eyebrow) parts.push(`<div style="font-family:${font};font-weight:700;font-size:13px;letter-spacing:3px;text-transform:uppercase;color:${b.subColor};margin-bottom:12px;">${esc(b.eyebrow)}</div>`)
      parts.push(`<div style="font-family:${font};font-weight:800;font-size:${b.size}px;line-height:1.15;color:${b.color};">${esc(b.title)}</div>`)
      if (b.subtitle) parts.push(`<div style="font-family:${font};font-size:18px;line-height:1.5;color:${b.subColor};margin-top:16px;">${esc(b.subtitle)}</div>`)
      if (b.btnText) parts.push(`<div style="margin-top:28px;">${button(b.btnText, b.btnUrl, b.btnBg, b.btnColor, 30, font)}</div>`)
      return `<div style="background:${b.bg};${bgImg}padding:${b.padY}px ${b.padX}px;text-align:${b.align};">${parts.join('')}</div>`
    }

    case 'heading':
      return wrap(`<div style="font-family:${font};font-weight:700;font-size:${b.size}px;line-height:1.25;color:${b.color || s.textColor};text-align:${b.align};">${esc(b.text)}</div>`)

    case 'text':
      return wrap(`<div style="font-family:${font};font-size:${b.size || 16}px;line-height:1.6;color:${b.color || s.textColor};text-align:${b.align};">${styleParas(styleLinks(sanitize(b.html), s.linkColor))}</div>`)

    case 'image': {
      if (!b.src) return edit ? wrap(placeholder('Click to choose an image')) : ''
      const img = `<img src="${esc(b.src)}" alt="${esc(b.alt)}" style="width:${b.width}%;max-width:100%;height:auto;border:0;display:inline-block;border-radius:${b.radius}px;">`
      const body = b.link ? `<a href="${esc(b.link)}" target="_blank">${img}</a>` : img
      const cap = b.caption ? `<div style="font:11px ${font};color:#9ca3af;text-align:center;margin-top:6px;">${styleLinks(sanitize(b.caption), '#9ca3af')}</div>` : ''
      return wrap(`<div style="text-align:${b.align};">${body}</div>${cap}`)
    }

    case 'button':
      return wrap(`<div style="text-align:${b.align};">${button(b.text, b.url, b.btnBg, b.color, b.radius, font)}</div>`)

    case 'form': {
      if (!b.form_url) return edit ? wrap(placeholder('Choose a form in the panel on the right', 110)) : ''
      return wrap(`<div style="background:${b.boxBg || '#f3f4f6'};border-radius:8px;padding:24px;text-align:${b.align};font-family:${font};">`
        + (b.heading ? `<div style="font-weight:700;font-size:20px;color:${s.textColor};margin-bottom:8px;">${esc(b.heading)}</div>` : '')
        + (b.text ? `<div style="font-size:15px;line-height:1.5;color:#6b7280;margin-bottom:18px;">${esc(b.text)}</div>` : '')
        + `${button(b.btnText || 'Open the form', b.form_url, b.btnBg, b.color, b.radius, font)}</div>`)
    }

    case 'features': {
      const cells = (b.items || []).map((it) =>
        `<td class="col" width="33%" valign="top" style="padding:0 10px;text-align:center;font-family:${font};">`
        + `<div style="font-size:38px;line-height:1.2;margin-bottom:8px;">${esc(it.icon)}</div>`
        + `<div style="font-weight:700;font-size:16px;color:${s.textColor};margin-bottom:6px;">${esc(it.title)}</div>`
        + `<div style="font-size:14px;line-height:1.5;color:#6b7280;">${esc(it.text)}</div></td>`).join('')
      return wrap(`<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>${cells}</tr></table>`)
    }

    case 'coupon':
      return wrap(`<div style="text-align:center;"><div style="display:inline-block;border:2px dashed ${b.border};background:${b.boxBg};border-radius:10px;padding:18px 40px;font-family:${font};">`
        + `<div style="font-size:12px;letter-spacing:3px;text-transform:uppercase;color:#6b7280;margin-bottom:6px;">${esc(b.label)}</div>`
        + `<div style="font-family:'Courier New',monospace;font-weight:800;font-size:32px;letter-spacing:5px;color:${b.color};">${esc(b.code)}</div>`
        + (b.note ? `<div style="font-size:13px;color:#6b7280;margin-top:6px;">${esc(b.note)}</div>` : '')
        + '</div></div>')

    case 'split': {
      const img = b.src ? `<img src="${esc(b.src)}" alt="${esc(b.alt)}" style="width:100%;height:auto;border:0;display:block;border-radius:6px;">` : edit ? placeholder('Image', 120) : ''
      const txt = `<div style="font-family:${font};font-size:16px;line-height:1.6;color:${s.textColor};">${styleParas(styleLinks(sanitize(b.html), s.linkColor))}</div>`
      if (!img) return wrap(txt) // no image set: text takes the full width instead of leaving a blank half
      const cells = [
        `<td class="col" width="50%" valign="top" style="padding-${b.side === 'left' ? 'right' : 'left'}:12px;">${img}</td>`,
        `<td class="col" width="50%" valign="middle" style="padding-${b.side === 'left' ? 'left' : 'right'}:12px;">${txt}</td>`,
      ]
      if (b.side !== 'left') cells.reverse()
      return wrap(`<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>${cells.join('')}</tr></table>`)
    }

    case 'divider':
      return wrap(`<div style="border-top:${b.thickness}px solid ${b.color};font-size:0;line-height:0;">&nbsp;</div>`)

    case 'spacer':
      return `<div style="height:${b.height}px;line-height:${b.height}px;font-size:1px;">&nbsp;</div>`

    case 'social': {
      const links = (b.links || []).filter((l) => l.url).map((l) => `<a href="${esc(l.url)}" target="_blank" style="color:${s.linkColor};text-decoration:none;font-weight:700;">${esc(l.name)}</a>`)
      if (!links.length) return edit ? wrap(`<div style="text-align:center;font:14px ${font};color:#9ca3af;">Add social links in the settings panel</div>`) : ''
      return wrap(`<div style="text-align:center;font-family:${font};font-size:14px;">${links.join(' &nbsp;&middot;&nbsp; ')}</div>`)
    }

    case 'footer': {
      const c = b.color || '#6b7280'
      return wrap(`<div style="font-family:${font};font-size:12px;line-height:1.7;color:${c};text-align:center;">${styleParas(styleLinks(sanitize(b.html), c))}</div>`)
    }

    case 'html':
      return wrap(sanitize(b.html))
  }
  return ''
}

/** Full email document (what is saved as the template/campaign body and sent). */
export function renderEmail(design) {
  const s = { ...defaultSettings(), ...(design?.settings || {}) }
  const rows = (design?.blocks || []).map((b) => renderBlock(b, s)).filter(Boolean).map((h) => `<tr><td>${h}</td></tr>`).join('\n')
  const pre = s.preheader ? `<div style="display:none;max-height:0;overflow:hidden;opacity:0;">${esc(s.preheader)}</div>` : ''

  return `<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<style>@media only screen and (max-width:620px){.col{display:block!important;width:100%!important;padding:0 0 18px 0!important}}img{max-width:100%;height:auto}</style></head>
<body style="margin:0;padding:0;background:${s.bg};">${pre}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:${s.bg};"><tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="${s.width}" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:${s.width}px;background:${s.contentBg};font-family:${s.font};border-collapse:separate;border-radius:${s.radius}px;overflow:hidden;">
${rows}
</table></td></tr></table></body></html>`
}

// ---- starter layouts (shown in the gallery when creating a new email) ----
const B = (type, over = {}) => ({ ...newBlock(type), ...over })
const S = (over) => ({ ...defaultSettings(), ...over })

export const LAYOUTS = [
  {
    id: 'blank', name: 'Blank',
    make: () => ({ settings: S({}), blocks: [B('footer')] }),
  },

  {
    id: 'newsletter', name: 'Newsletter',
    make: () => ({
      settings: S({ bg: '#eef0f7', linkColor: '#3f51b5' }),
      blocks: [
        B('header', { color: '#1e2a5a' }),
        B('hero', {
          bg: '#3f51b5', emoji: '', eyebrow: 'Monthly newsletter', title: "What's new this month",
          subtitle: 'Fresh stories, handy tips and offers picked just for you, {{first_name}}.', btnText: '', size: 38, padY: 52,
        }),
        B('image', { padY: 0, padX: 0 }),
        B('heading', { text: 'Top story', size: 26, align: 'left', color: '#1e2a5a', padY: 24 }),
        B('text', { html: '<p>Hi {{first_name}},</p><p>Open with your most important news. Keep it short, friendly and useful — two or three sentences is plenty — then invite readers to find out more.</p>', padY: 0 }),
        B('button', { text: 'Read the full story', align: 'left', padY: 20 }),
        B('divider'),
        B('features', {
          items: [
            { icon: '📰', title: 'Latest news', text: 'Updates from around the company.' },
            { icon: '💡', title: 'Tips & ideas', text: 'Simple ways to get more value.' },
            { icon: '🎁', title: 'Member offers', text: 'Deals just for our subscribers.' },
          ],
        }),
        B('split', { side: 'right', bg: '#f5f6fb', padY: 24, html: '<p><b style="font-size:18px">Featured this month</b></p><p>Introduce a product, event or story with a short teaser.</p>' }),
        B('social', { padY: 24 }),
        B('footer', { bg: '#1e2a5a', color: '#c5cae9' }),
      ],
    }),
  },

  {
    id: 'promo', name: 'Sale / Promotion',
    make: () => ({
      settings: S({ bg: '#fff1ec', linkColor: '#e53935' }),
      blocks: [
        B('header', { bg: '#1a1a1a', color: '#ffffff' }),
        B('hero', {
          bg: '#e53935', emoji: '🔥', eyebrow: 'Limited time only', title: 'UP TO 20% OFF', size: 50,
          subtitle: 'Everything you love, for less. Ends Sunday at midnight.', subColor: '#ffcdd2',
          btnText: 'Shop the sale', btnBg: '#ffffff', btnColor: '#e53935', padY: 60,
        }),
        B('text', { align: 'center', padY: 28, html: '<p>Hi {{first_name}}, our biggest sale of the season has landed. Use the code below at checkout to save on your next order.</p>' }),
        B('coupon', { label: 'Your discount code', code: 'SAVE20', note: 'Ends Sunday midnight', boxBg: '#fff8e1', border: '#ff9800', color: '#e53935' }),
        B('button', { text: 'Start shopping', btnBg: '#e53935', radius: 30, padY: 24 }),
        B('features', {
          bg: '#fafafa',
          items: [
            { icon: '🚚', title: 'Free delivery', text: 'On orders over $50.' },
            { icon: '↩️', title: 'Easy returns', text: '30-day hassle-free returns.' },
            { icon: '⭐', title: 'Top rated', text: 'Loved by our customers.' },
          ],
        }),
        B('text', { align: 'center', size: 12, color: '#9e9e9e', padY: 20, html: '<p>Offer valid while stocks last. Cannot be combined with other discounts.</p>' }),
        B('footer', { bg: '#1a1a1a', color: '#9e9e9e' }),
      ],
    }),
  },

  {
    id: 'welcome', name: 'Welcome',
    make: () => ({
      settings: S({ bg: '#e6f4f1', linkColor: '#00897b' }),
      blocks: [
        B('header', { color: '#00796b' }),
        B('hero', {
          bg: '#00897b', emoji: '👋', eyebrow: 'Welcome aboard', title: 'Welcome, {{first_name}}!', size: 42,
          subtitle: "We're so happy you're here. Here's a quick look at what's ahead.", subColor: '#b2dfdb',
          btnText: 'Get started', btnBg: '#ffffff', btnColor: '#00796b', padY: 56,
        }),
        B('heading', { text: "Here's what you can expect", size: 24, color: '#00695c', padY: 32 }),
        B('features', {
          padY: 8,
          items: [
            { icon: '💌', title: 'Great offers', text: 'Exclusive deals sent straight to you.' },
            { icon: '🎉', title: 'Early access', text: 'Be the first to hear our news.' },
            { icon: '💬', title: 'Friendly help', text: 'Real people, ready to help.' },
          ],
        }),
        B('divider', { padY: 24 }),
        B('coupon', { label: 'Your welcome gift', code: 'WELCOME10', note: '10% off your first visit', boxBg: '#e0f2f1', border: '#00897b', color: '#00695c' }),
        B('text', { align: 'center', padY: 24, html: '<p>Have a question? Just reply to this email — a real person will get back to you.</p>' }),
        B('social'),
        B('footer', { bg: '#f1f8f7' }),
      ],
    }),
  },

  {
    id: 'announcement', name: 'Announcement',
    make: () => ({
      settings: S({ bg: '#ede7f6', linkColor: '#5e35b1' }),
      blocks: [
        B('header', { color: '#4527a0' }),
        B('hero', {
          bg: '#4527a0', emoji: '📣', eyebrow: 'Announcement', title: 'Big news from {{company_name}}', size: 38,
          subtitle: 'We have something exciting to share with you.', subColor: '#d1c4e9',
          btnText: 'Read the details', btnBg: '#ffca28', btnColor: '#311b92', padY: 56,
        }),
        B('text', { padY: 32, html: '<p>Hello {{first_name}},</p><p>Explain the announcement here in a few clear sentences — what is happening, and why it matters to your customers.</p>' }),
        B('split', { side: 'left', bg: '#f5f1fb', padY: 24, html: '<p><b style="font-size:18px">What is changing</b></p><p>Add the key detail, date or product here.</p>' }),
        B('features', {
          items: [
            { icon: '✅', title: "What's new", text: 'The main change in one line.' },
            { icon: '📅', title: 'When', text: 'Starting on your date here.' },
            { icon: '🤝', title: 'For you', text: 'What it means for customers.' },
          ],
        }),
        B('button', { text: 'Learn more', btnBg: '#5e35b1', radius: 30, padY: 8 }),
        B('text', { align: 'center', padY: 24, size: 14, color: '#757575', html: '<p>Questions? Just reply to this email and we will be happy to help.</p>' }),
        B('footer', { bg: '#f5f1fb' }),
      ],
    }),
  },

  {
    id: 'birthday', name: 'Birthday',
    make: () => ({
      settings: S({ bg: '#fff0f6', linkColor: '#d81b60' }),
      blocks: [
        B('header', { color: '#ad1457' }),
        B('hero', {
          bg: '#ec407a', emoji: '🎂🎉', eyebrow: 'Happy birthday', title: "{{first_name}}, it's your day!", size: 42,
          subtitle: 'Wishing you a fantastic year ahead from all of us at {{company_name}}.', subColor: '#fce4ec', btnText: '', padY: 60,
        }),
        B('text', { align: 'center', padY: 32, size: 18, html: "<p>To celebrate, here's a little gift just for you:</p>" }),
        B('coupon', { label: 'Your birthday gift', code: 'BDAY20', note: 'Redeem within 30 days', boxBg: '#fff8e1', border: '#ec407a', color: '#ad1457' }),
        B('button', { text: 'Claim my gift', btnBg: '#ec407a', radius: 30, padY: 28 }),
        B('features', {
          bg: '#fff7fa',
          items: [
            { icon: '🎁', title: 'A gift for you', text: 'Our way of saying thanks.' },
            { icon: '🥳', title: 'Treat yourself', text: 'You deserve something nice.' },
            { icon: '💖', title: 'Thank you', text: "We're glad you're with us." },
          ],
        }),
        B('social', { padY: 24 }),
        B('footer', { bg: '#fff7fa' }),
      ],
    }),
  },
]

/** HTML for a saved design: hand-coded HTML wins, otherwise render the blocks. */
export const designHtml = (design) => (typeof design?.rawHtml === 'string' ? design.rawHtml : renderEmail(design))

/** Wrap legacy HTML-only content into a design so it can open in the designer. */
export function designFromHtml(html) {
  return { settings: defaultSettings(), blocks: [{ ...newBlock('html'), html: html || '', padX: 0, padY: 0 }] }
}
