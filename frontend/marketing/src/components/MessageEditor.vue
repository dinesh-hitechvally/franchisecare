<script setup>
import { ref, watch, computed, onBeforeUnmount } from 'vue'
import api from '../api'
import ImagePicker from './ImagePicker.vue'

// v-model:subject and v-model:body; `type` is 'email' or 'sms'
const props = defineProps({ type: String, subject: String, body: String })
const emit = defineEmits(['update:subject', 'update:body'])

const TAGS = ['first_name', 'last_name', 'full_name', 'email', 'phone', 'company_name', 'company_phone', 'company_website', 'company_email', 'unsubscribe_url']
const tag = (t) => `{{${t}}}`
const subjectHint = 'Subject line, e.g. Hi ' + tag('first_name')
const bodyHint = computed(() => (props.type === 'email' ? `<h1>Hello ${tag('first_name')}</h1>` : `Hi ${tag('first_name')}, …`))
const bodyEl = ref(null)
const picker = ref(false)
const preview = ref({ html: '', text: '', subject: '' })

const smsSegments = computed(() => {
  const n = (props.body || '').length
  return n === 0 ? 0 : n <= 160 ? 1 : Math.ceil(n / 153)
})

function insert(text) {
  const el = bodyEl.value
  const body = props.body || ''
  const start = el?.selectionStart ?? body.length
  const end = el?.selectionEnd ?? body.length
  emit('update:body', body.slice(0, start) + text + body.slice(end))
  requestAnimationFrame(() => { el?.focus(); el?.setSelectionRange(start + text.length, start + text.length) })
}

function pickImage(img) {
  const credit = `<p style="font-size:11px;color:#9ca3af;margin:4px 0 16px">Photo by <a href="${img.author_url}">${img.author}</a> on <a href="${img.link}">${img.source}</a>${img.license ? ' (' + img.license.trim() + ')' : ''}</p>`
  insert(`<img src="${img.url}" alt="${img.author}" style="max-width:100%;height:auto;display:block;border-radius:6px">\n${credit}\n`)
  picker.value = false
}

let timer
watch(() => [props.type, props.subject, props.body], () => {
  clearTimeout(timer)
  timer = setTimeout(async () => {
    if (!props.body) { preview.value = { html: '', text: '', subject: '' }; return }
    try {
      const { data } = await api.post('/campaigns/preview', { type: props.type, subject: props.subject, body: props.body })
      preview.value = { html: data.html || '', text: data.text || '', subject: data.subject || '' }
    } catch (e) { /* preview is best-effort */ }
  }, 400)
}, { immediate: true })
onBeforeUnmount(() => clearTimeout(timer))
</script>

<template>
  <div>
    <div v-if="type === 'email'" class="field">
      <label>Subject</label>
      <input :value="subject" @input="emit('update:subject', $event.target.value)" :placeholder="subjectHint" />
    </div>

    <div class="editor">
      <div>
        <label>{{ type === 'email' ? 'HTML body' : 'Message' }}</label>
        <div class="tags">
          <button v-for="t in TAGS.slice(0, type === 'sms' ? 4 : 7)" :key="t" type="button" class="tag" @click="insert(tag(t))">{{ tag(t) }}</button>
          <button v-if="type === 'email'" type="button" class="btn sm" @click="picker = true">🖼 Insert free image</button>
        </div>
        <textarea ref="bodyEl" :value="body" :rows="type === 'email' ? 18 : 7" @input="emit('update:body', $event.target.value)"
          :placeholder="bodyHint"></textarea>
        <small v-if="type === 'sms'">{{ (body || '').length }} characters · {{ smsSegments }} SMS segment(s) · include "Reply STOP to opt out"</small>
      </div>
      <div>
        <label>Preview <small v-if="preview.subject">— {{ preview.subject }}</small></label>
        <iframe v-if="type === 'email'" class="preview" sandbox="" :srcdoc="preview.html" style="margin-top: 6px"></iframe>
        <div v-else class="sms-bubble" style="margin-top: 6px">{{ preview.text || 'Your message preview' }}</div>
      </div>
    </div>

    <ImagePicker v-if="picker" @close="picker = false" @pick="pickImage" />
  </div>
</template>
