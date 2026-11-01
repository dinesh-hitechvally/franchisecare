import { defineStore } from 'pinia'

// One global toast: used for errors the page did not handle itself (e.g. "Your role is read-only").
export const useUi = defineStore('ui', {
  state: () => ({ toast: null }),
  actions: {
    show(text, kind = 'err') {
      this.toast = { text, kind }
      clearTimeout(this._t)
      this._t = setTimeout(() => (this.toast = null), 4500)
    },
  },
})
