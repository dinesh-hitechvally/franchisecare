import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from './stores/auth'

const routes = [
  { path: '/login', component: () => import('./views/Login.vue'), meta: { guest: true } },
  { path: '/', redirect: '/dashboard' },
  { path: '/dashboard', component: () => import('./views/Dashboard.vue') },

  // Audience
  { path: '/contacts', component: () => import('./views/Contacts.vue') },
  { path: '/lists', component: () => import('./views/Lists.vue') },
  { path: '/tags', component: () => import('./views/Tags.vue') },
  { path: '/custom-fields', component: () => import('./views/CustomFields.vue') },
  { path: '/segments', component: () => import('./views/Segments.vue') },
  { path: '/subscriptions', component: () => import('./views/Subscriptions.vue') },

  // Campaigns
  { path: '/campaigns', component: () => import('./views/Campaigns.vue') },
  { path: '/campaigns/new', component: () => import('./views/CampaignEditor.vue') },
  { path: '/campaigns/:id', component: () => import('./views/CampaignEditor.vue') },
  { path: '/reports', component: () => import('./views/Reports.vue') },
  { path: '/reports/:id', component: () => import('./views/ReportDetail.vue') },
  { path: '/logs', component: () => import('./views/Logs.vue') },

  // Media library
  { path: '/media', component: () => import('./views/Media.vue') },

  // Content
  { path: '/templates', component: () => import('./views/Templates.vue') },
  { path: '/landing-pages', component: () => import('./views/LandingPages.vue') },
  { path: '/forms', component: () => import('./views/Forms.vue') },
  { path: '/forms/new', component: () => import('./views/FormBuilder.vue') },
  { path: '/submissions', component: () => import('./views/AllSubmissions.vue') },
  { path: '/forms/:id/submissions', component: () => import('./views/FormSubmissions.vue') },
  { path: '/forms/:id', component: () => import('./views/FormBuilder.vue') },

  // Automation
  { path: '/workflows', component: () => import('./views/Workflows.vue') },
  { path: '/automation/triggers', component: () => import('./views/Triggers.vue') },
  { path: '/automation/history', component: () => import('./views/AutomationHistory.vue') },

  // Analytics
  { path: '/analytics/campaigns', component: () => import('./views/AnalyticsCampaigns.vue') },
  { path: '/analytics/contacts', component: () => import('./views/AnalyticsContacts.vue') },
  { path: '/analytics/conversions', component: () => import('./views/AnalyticsConversions.vue') },

  // System settings
  {
    path: '/settings', component: () => import('./views/SettingsLayout.vue'),
    children: [
      { path: '', redirect: '/settings/company' },
      { path: 'account', component: () => import('./views/Account.vue') },
      { path: 'company', component: () => import('./views/Settings.vue') },
      { path: 'email', component: () => import('./views/EmailConfig.vue') },
      { path: 'sms', component: () => import('./views/SmsConfig.vue') },
      { path: 'users', component: () => import('./views/Users.vue') },
      { path: 'api', component: () => import('./views/Integrations.vue') },
      { path: 'webhooks', component: () => import('./views/Webhooks.vue') },
      { path: 'activity', component: () => import('./views/ActivityLogs.vue') },
    ],
  },
  // the old addresses still work
  { path: '/config/email', redirect: '/settings/email' },
  { path: '/config/sms', redirect: '/settings/sms' },
  { path: '/integrations', redirect: '/settings/api' },
  { path: '/webhooks', redirect: '/settings/webhooks' },
  { path: '/users', redirect: '/settings/users' },
  { path: '/activity', redirect: (to) => ({ path: '/settings/activity', query: to.query }) },

  { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
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
