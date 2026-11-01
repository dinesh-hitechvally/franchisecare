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

api.interceptors.response.use(
  (r) => r,
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

/** First validation message, or the server message, or a fallback. */
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
