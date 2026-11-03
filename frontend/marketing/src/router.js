import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from './stores/auth'

const routes = [
  { path: '/login', component: () => import('./views/Login.vue'), meta: { guest: true } },
  { path: '/', redirect: '/dashboard' },
  { path: '/dashboard', component: () => import('./views/Dashboard.vue') },

  // Audience
  { path: '/people', component: () => import('./views/People.vue') },
  // old addresses (this screen used to be called Contacts)
  { path: '/contacts', redirect: (to) => ({ path: '/people', query: to.query }) },
  { path: '/contacts/history', redirect: (to) => ({ path: '/people/history', query: to.query }) },
  { path: '/analytics/contacts', redirect: '/analytics/people' },
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
  { path: '/message-logs', component: () => import('./views/Logs.vue') },
  { path: '/logs', redirect: (to) => ({ path: '/message-logs', query: to.query }) }, // old address

  // Media library
  { path: '/media', component: () => import('./views/Media.vue') },

  // Content
  { path: '/templates', component: () => import('./views/Templates.vue') },
  { path: '/pages', component: () => import('./views/LandingPages.vue') },
  { path: '/pages/history', component: () => import('./views/HistoryList.vue'), props: { kind: 'landing_page' }, meta: { parent: '/pages' } },
  { path: '/forms/history', component: () => import('./views/HistoryList.vue'), props: { kind: 'form' }, meta: { parent: '/forms' } },
  { path: '/templates/history', component: () => import('./views/HistoryList.vue'), props: { kind: 'template' }, meta: { parent: '/templates' } },
  { path: '/campaigns/history', component: () => import('./views/HistoryList.vue'), props: { kind: 'campaign' }, meta: { parent: '/campaigns' } },
  { path: '/people/history', component: () => import('./views/HistoryList.vue'), props: { kind: 'person' }, meta: { parent: '/people' } },
  { path: '/workflows/history', component: () => import('./views/HistoryList.vue'), props: { kind: 'workflow' }, meta: { parent: '/workflows' } },
  { path: '/segments/history', component: () => import('./views/HistoryList.vue'), props: { kind: 'segment' }, meta: { parent: '/segments' } },
  { path: '/landing-pages', redirect: '/pages' }, // the old address
  { path: '/forms', component: () => import('./views/Forms.vue') },
  { path: '/forms/new', component: () => import('./views/FormBuilder.vue') },
  { path: '/submissions', component: () => import('./views/AllSubmissions.vue') },
  { path: '/forms/:id/submissions', component: () => import('./views/FormSubmissions.vue') },
  { path: '/forms/:id', component: () => import('./views/FormBuilder.vue') },

  // Automation
  { path: '/workflows', component: () => import('./views/Workflows.vue') },
  { path: '/automation/triggers', component: () => import('./views/Triggers.vue') },
  { path: '/automation/webhooks', component: () => import('./views/Webhooks.vue') },
  { path: '/automation/webhooks/history', component: () => import('./views/HistoryList.vue'), props: { kind: 'webhook' }, meta: { parent: '/automation/webhooks' } },
  { path: '/automation/webhooks/deliveries', component: () => import('./views/WebhookDeliveries.vue'), meta: { parent: '/automation/webhooks' } },
  { path: '/automation/history', component: () => import('./views/AutomationHistory.vue') },

  // Analytics
  { path: '/analytics', redirect: '/analytics/overview' },
  { path: '/analytics/overview', component: () => import('./views/AnalyticsOverview.vue') },
  { path: '/analytics/email', component: () => import('./views/AnalyticsEmail.vue') },
  { path: '/analytics/sms', component: () => import('./views/AnalyticsSms.vue') },
  { path: '/analytics/delivery', component: () => import('./views/AnalyticsDelivery.vue') },
  { path: '/analytics/automation', component: () => import('./views/AnalyticsAutomation.vue') },
  { path: '/analytics/campaigns', redirect: '/analytics/email' },
  { path: '/analytics/people', component: () => import('./views/AnalyticsPeople.vue') },
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
      { path: 'activity', component: () => import('./views/ActivityLogs.vue') },
    ],
  },
  // the old addresses still work
  { path: '/config/email', redirect: '/settings/email' },
  { path: '/config/sms', redirect: '/settings/sms' },
  { path: '/integrations', redirect: '/settings/api' },
  { path: '/webhooks', redirect: '/automation/webhooks' },
  { path: '/settings/webhooks', redirect: '/automation/webhooks' }, // Webhooks moved from Settings to the Automation menu
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
