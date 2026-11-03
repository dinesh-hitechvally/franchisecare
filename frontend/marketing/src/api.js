import axios from 'axios'
import { useUi } from './stores/ui'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

/**
 * The API answers in one standard shape: { success, message, data, meta }. Screens work with the payload, so a successful answer is
 * unwrapped here: response.data is what the endpoint returned (a record, a list, or a paginator with its paging details put back),
 * and response.message is the sentence the server chose, if any. Files and anything else pass through unchanged.
 */
function unwrap(r) {
  const b = r.data
  if (b && typeof b === 'object' && !Array.isArray(b) && b.success === true && 'data' in b && 'message' in b) {
    r.message = b.message
    r.data = b.meta ? { data: b.data, ...b.meta } : b.data
  }
  return r
}

api.interceptors.response.use(
  unwrap,
  (err) => {
    const status = err.response?.status
    if (status === 401 && !location.pathname.startsWith('/login')) {
      localStorage.removeItem('token')
      location.href = '/login'
    } else if (status === 403) {
      // permission problems are never page-specific, so tell the user right away
      useUi().show(err.response.data?.message || 'You do not have permission to do this.')
    }
    return Promise.reject(err)
  },
)

/** The sentence the server sent with a successful answer (e.g. "Sending stopped."), or a fallback. */
export const responseMessage = (r, fallback = '') => r?.message || fallback

/** First validation message, or the server message, or a fallback. Failures are { success: false, message, code, errors? }. */
export function errorMessage(err) {
  const d = err.response?.data
  if (d?.errors) return Object.values(d.errors)[0][0]
  return d?.message || err.message || 'Something went wrong'
}

/** Download a file from an authenticated endpoint (a plain link can't send the Bearer token). */
export async function download(url, filename) {
  const res = await api.get(url, { responseType: 'blob' })
  const a = document.createElement('a')
  a.href = URL.createObjectURL(res.data)
  a.download = filename
  a.click()
  URL.revokeObjectURL(a.href)
}

export default api
