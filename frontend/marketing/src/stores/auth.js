import { defineStore } from 'pinia'
import api from '../api'

export const useAuth = defineStore('auth', {
  state: () => ({ user: null, company: null, token: localStorage.getItem('token') }),
  actions: {
    set(data) {
      if (data.token) {
        this.token = data.token
        localStorage.setItem('token', data.token)
      }
      this.user = data.user
      this.company = data.company
    },
    async login(form) {
      this.set((await api.post('/login', form)).data)
    },
    async register(form) {
      this.set((await api.post('/register', form)).data)
    },
    async fetchMe() {
      this.set((await api.get('/me')).data)
    },
    async logout() {
      try { await api.post('/logout') } catch (e) { /* token may already be invalid */ }
      localStorage.removeItem('token')
      this.$reset()
      this.token = null
    },
  },
})
