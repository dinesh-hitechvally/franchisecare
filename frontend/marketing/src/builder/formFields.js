// Field model for the form builder. Mirrors App\Services\FormFields on the server (which validates everything again).

export const uid = () => Math.random().toString(36).slice(2, 10)

/** group order = palette order */
export const FIELD_TYPES = {
  first_name: { label: 'First name', icon: 'people', group: 'Contact details', unique: true, hint: 'Saved as the contact’s first name', def: { label: 'First name' } },
  last_name: { label: 'Last name', icon: 'people', group: 'Contact details', unique: true, hint: 'Saved as the contact’s last name', def: { label: 'Last name' } },
  email: { label: 'Email', icon: 'email', group: 'Contact details', unique: true, hint: 'Saved as the contact’s email', def: { label: 'Email', required: true, placeholder: 'you@example.com' } },
  phone: { label: 'Mobile', icon: 'sms', group: 'Contact details', unique: true, hint: 'Saved as the contact’s phone number', def: { label: 'Mobile', placeholder: '+61 400 000 000' } },

  text: { label: 'Short text', icon: 'text', group: 'Other fields', custom: true, def: { label: 'Your question' } },
  textarea: { label: 'Long text', icon: 'template', group: 'Other fields', custom: true, def: { label: 'Message' } },
  number: { label: 'Number', icon: 'chart', group: 'Other fields', custom: true, def: { label: 'Number' } },
  date: { label: 'Date', icon: 'schedule', group: 'Other fields', custom: true, def: { label: 'Date' } },
  dropdown: { label: 'Dropdown', icon: 'filter', group: 'Other fields', custom: true, options: true, def: { label: 'Choose one', placeholder: 'Select…', options: ['Option 1', 'Option 2', 'Option 3'] } },
  radio: { label: 'Choice list', icon: 'check', group: 'Other fields', custom: true, options: true, def: { label: 'Pick one', options: ['Option 1', 'Option 2'] } },
  checkbox: { label: 'Checkbox', icon: 'check', group: 'Other fields', custom: true, def: { label: 'I agree to receive emails' } },

  heading: { label: 'Heading', icon: 'heading', group: 'Content', block: true, def: { label: 'Section heading' } },
  paragraph: { label: 'Paragraph', icon: 'text', group: 'Content', block: true, def: { label: 'Add a short explanation here.' } },
}

export const GROUPS = ['Contact details', 'Other fields', 'Content']

export const newField = (type) => ({ id: uid(), key: null, type, label: '', placeholder: '', help: '', required: false, options: [], width: 'full', to_contact: type !== 'textarea', ...FIELD_TYPES[type].def })

/** Column layout: fields flow left to right and wrap, so two ½ fields share a row. */
export const WIDTHS = [['full', 'Full'], ['two_thirds', '⅔'], ['half', '½'], ['third', '⅓'], ['quarter', '¼']]

export const defaultStyle = () => ({ pageBg: '#f4f5f7', cardBg: '#ffffff', text: '#333333', accent: '#3f51b5', btnText: '#ffffff', radius: 6, font: 'sans', width: 480 })

export const FONTS = { sans: 'Arial, Helvetica, sans-serif', serif: 'Georgia, "Times New Roman", serif', mono: '"Courier New", monospace' }

export const defaultForm = () => ({
  name: '', title: 'Join our newsletter', description: 'Tips and offers, straight to your inbox.',
  fields: [{ ...newField('first_name') }, { ...newField('email') }],
  style: defaultStyle(), button_text: 'Subscribe', success_message: 'Thanks for subscribing!', existing_message: '', redirect_url: '',
  list_ids: [], tag_ids: [], active: true,
  type: 'newsletter', save_contact: true, subscribe: true,
  notify_on: false, notify_text: '', autoreply: false, autoreply_subject: '', autoreply_body: '', autoreply_attachments: [],
})

const RESERVED = ['first_name', 'last_name', 'full_name', 'email', 'phone', 'company_name', 'company_phone', 'company_website', 'company_email', 'company_logo', 'business_name', 'unsubscribe_url', 'website', 'id', 'company_id']

/** Mirrors the server's key generation; only used to show "stored as …" while editing. */
export const slug = (label) => {
  let s = (label || '').toLowerCase().normalize('NFKD').replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '')
  s = s ? (/^[a-z]/.test(s) ? s : 'f_' + s) : 'field'
  if (RESERVED.includes(s)) s = 'contact_' + s // e.g. "Company name" must not clash with the built-in tag for YOUR company
  return s.slice(0, 36)
}

/** What kind of form it is. The type only picks defaults; every action stays an explicit setting. */
export const FORM_TYPES = {
  newsletter: { label: 'Newsletter sign-up', icon: 'email', short: 'Newsletter', desc: 'Grow your mailing list. Sign-ups are saved to your contacts and subscribed to marketing emails.' },
  contact: { label: 'Contact form', icon: 'sms', short: 'Contact', desc: 'Let people send you a message. You get it by email and in the form’s submissions, and they get a confirmation. Nobody is added to your contacts unless you turn that on.' },
  other: { label: 'Other', icon: 'template', short: 'Other', desc: 'A survey, booking request, feedback… Answers are kept in the form’s submissions; choose whether to also save people as contacts.' },
}

/** A brand-new form of this type: fields, wording and what happens on submit. */
export function typePreset(type, userEmail = '') {
  const f = (t, extra = {}) => ({ ...newField(t), ...extra })
  const none = { notify_on: false, notify_text: '', autoreply: false, autoreply_subject: '', autoreply_body: '' }

  if (type === 'contact') {
    return {
      type, title: 'Contact us', description: "Send us a message and we'll get back to you.", button_text: 'Send message', success_message: "Thanks, we'll be in touch soon.",
      fields: [f('first_name', { required: true, width: 'half' }), f('last_name', { width: 'half' }), f('email', { required: true }), f('textarea', { label: 'Message', placeholder: 'How can we help?', required: true })],
      save_contact: false, subscribe: false, notify_on: true, notify_text: userEmail,
      autoreply: true, autoreply_subject: 'We got your message', autoreply_body: "Hi {{first_name}},\n\nThanks for getting in touch with {{company_name}}. We'll reply within one business day.",
    }
  }
  if (type === 'other') {
    return { type, title: 'Tell us more', description: '', button_text: 'Submit', success_message: 'Thank you! We have received your response.', fields: [f('first_name'), f('email', { required: true })], save_contact: false, subscribe: false, ...none }
  }
  return { type: 'newsletter', title: 'Join our newsletter', description: 'Tips and offers, straight to your inbox.', button_text: 'Subscribe', success_message: 'Thanks for subscribing!', fields: [f('first_name'), f('email', { required: true })], save_contact: true, subscribe: true, ...none }
}
