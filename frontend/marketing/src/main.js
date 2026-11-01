import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'
import { fmtDate, fmtDateTime, fmtStored } from './format'

const app = createApp(App)
// dates are shown as Y-m-d (2026-11-01) and Y-m-d h:i A (2026-11-01 01:28 PM) everywhere
app.config.globalProperties.$d = fmtDate
app.config.globalProperties.$dt = fmtDateTime
app.config.globalProperties.$ds = fmtStored // typed-in times: shown exactly as stored
app.use(createPinia()).use(router).mount('#app')
