import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from './stores/auth'

const routes = [
  { path: '/login', component: () => import('./views/Login.vue'), meta: { guest: true } },
  { path: '/', redirect: '/dashboard' },
  { path: '/dashboard', component: () => import('./views/Dashboard.vue') },
  { path: '/contacts', component: () => import('./views/Contacts.vue') },
  { path: '/lists', component: () => import('./views/Lists.vue') },
  { path: '/templates', component: () => import('./views/Templates.vue') },
  { path: '/campaigns', component: () => import('./views/Campaigns.vue') },
  { path: '/campaigns/new', component: () => import('./views/CampaignEditor.vue') },
  { path: '/campaigns/:id', component: () => import('./views/CampaignEditor.vue') },
  { path: '/logs', component: () => import('./views/Logs.vue') },
  { path: '/settings', component: () => import('./views/Settings.vue') },
]

const router = createRouter({ history: createWebHistory(), routes })

router.beforeEach(async (to) => {
  const auth = useAuth()
  if (!auth.token) return to.meta.guest ? true : '/login'
  if (!auth.user) {
    try { await auth.fetchMe() } catch (e) { return '/login' }
  }
  if (to.meta.guest) return '/dashboard'
})

export default router
