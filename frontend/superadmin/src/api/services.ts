import { apiClient, API_BASE_URL } from './client'
import type {
  User,
  Paginated,
  Franchise,
  FranchiseAudit,
  AdminUser,
  NewsItem,
  DocumentItem,
  Service,
  ServiceCategory,
  SupportTicket,
  SupportTicketReply,
  SupportTicketStats,
  SystemSetting,
  FranchiseUser,
  FranchiseSuburb,
  FranchiseService,
  ForumCategory,
  ForumTopic,
  ForumPost,
  ForumGroup,
  AppVersion,
} from '../types'

export const authApi = {
  login: (email: string, password: string) =>
    apiClient.post<{ token: string; user: User }>('/login', { email, password }),

  logout: () => apiClient.post('/logout'),

  changePassword: (data: { current_password: string; password: string; password_confirmation: string }) =>
    apiClient.post('/change-password', data),

  updateProfile: (data: FormData) => apiClient.post<User>('/profile', data),
}

export const dashboardApi = {
  metrics: () =>
    apiClient.get<{
      total_franchises: number
      active_franchises: number
      total_users: number
      open_tickets: number
      monthly_revenue: number
      revenue_change: number
    }>('/dashboard/metrics'),

  revenueChart: (months = 12) =>
    apiClient.get<{ month: string; total: number }[]>('/dashboard/revenue-chart', { params: { months } }),

  franchisesByStatus: () =>
    apiClient.get<{ status: string; count: number }[]>('/dashboard/franchises-by-status'),

  recentActivities: () =>
    apiClient.get<{ type: string; message: string; date: string }[]>('/dashboard/recent-activities'),

  topFranchises: () => apiClient.get<(Franchise & { total_revenue: number })[]>('/dashboard/top-franchises'),
}

export interface FranchiseListParams {
  search?: string
  status?: string
  state?: string
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  per_page?: number
  page?: number
}

export const franchisesApi = {
  list: (params?: FranchiseListParams) => apiClient.get<Paginated<Franchise>>('/franchises', { params }),

  get: (id: number) => apiClient.get<Franchise & { users?: unknown[]; services?: unknown[]; suburbs?: unknown[] }>(`/franchises/${id}`),

  create: (data: Partial<Franchise>) => apiClient.post<Franchise>('/franchises', data),

  update: (id: number, data: Partial<Franchise>) => apiClient.put<Franchise>(`/franchises/${id}`, data),

  remove: (id: number) => apiClient.delete<{ message: string }>(`/franchises/${id}`),

  history: (id: number, page = 1) =>
    apiClient.get<Paginated<FranchiseAudit>>(`/franchises/${id}/history`, { params: { page } }),

  updateStatus: (id: number, status: string, reason?: string) =>
    apiClient.patch<Franchise>(`/franchises/${id}/status`, { status, reason }),
}

export const adminUsersApi = {
  list: (params?: { search?: string; role?: string; status?: string; per_page?: number; page?: number }) =>
    apiClient.get<Paginated<AdminUser>>('/admin-users', { params }),

  get: (id: number) => apiClient.get<AdminUser>(`/admin-users/${id}`),

  create: (data: { name: string; email: string; password: string; phone?: string; role: string }) =>
    apiClient.post<AdminUser>('/admin-users', data),

  update: (id: number, data: Partial<AdminUser>) => apiClient.put<AdminUser>(`/admin-users/${id}`, data),

  remove: (id: number) => apiClient.delete<{ message: string }>(`/admin-users/${id}`),
}

export const newsApi = {
  list: (params?: { search?: string; status?: string; category?: string; per_page?: number; page?: number }) =>
    apiClient.get<Paginated<NewsItem>>('/news', { params }),

  get: (id: number) => apiClient.get<NewsItem>(`/news/${id}`),

  create: (data: FormData) => apiClient.post<NewsItem>('/news', data),

  update: (id: number, data: FormData) => apiClient.post<NewsItem>(`/news/${id}?_method=PUT`, data),

  remove: (id: number) => apiClient.delete<{ message: string }>(`/news/${id}`),

  publish: (id: number) => apiClient.post<NewsItem>(`/news/${id}/publish`),
}

export const documentsApi = {
  list: (params?: { search?: string; category?: string; per_page?: number; page?: number }) =>
    apiClient.get<Paginated<DocumentItem>>('/documents', { params }),

  get: (id: number) => apiClient.get<DocumentItem>(`/documents/${id}`),

  create: (data: FormData) => apiClient.post<DocumentItem>('/documents', data),

  update: (id: number, data: Partial<Pick<DocumentItem, 'title' | 'description' | 'category' | 'status'>>) =>
    apiClient.put<DocumentItem>(`/documents/${id}`, data),

  remove: (id: number) => apiClient.delete<{ message: string }>(`/documents/${id}`),

  download: async (id: number, fileName: string) => {
    const token = localStorage.getItem('token')
    const response = await fetch(`${API_BASE_URL}/documents/${id}/download`, {
      headers: token ? { Authorization: `Bearer ${token}` } : undefined,
    })
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  },
}

export const servicesApi = {
  list: (params?: { search?: string; status?: string; category_id?: number }) =>
    apiClient.get<Service[]>('/services', { params }),

  get: (id: number) => apiClient.get<Service>(`/services/${id}`),

  create: (data: { name: string; description?: string; category_id?: number | null; base_price: number; duration: number; icon?: string }) =>
    apiClient.post<Service>('/services', data),

  update: (id: number, data: Partial<Service>) => apiClient.put<Service>(`/services/${id}`, data),

  remove: (id: number) => apiClient.delete<{ message: string }>(`/services/${id}`),

  reorder: (services: { id: number; sort_order: number }[]) =>
    apiClient.post<{ message: string }>('/services/reorder', { services }),
}

export const serviceCategoriesApi = {
  list: (params?: { search?: string; status?: string }) =>
    apiClient.get<ServiceCategory[]>('/service-categories', { params }),

  get: (id: number) => apiClient.get<ServiceCategory>(`/service-categories/${id}`),

  create: (data: { name: string; description?: string; icon?: string }) =>
    apiClient.post<ServiceCategory>('/service-categories', data),

  update: (id: number, data: Partial<ServiceCategory>) =>
    apiClient.put<ServiceCategory>(`/service-categories/${id}`, data),

  remove: (id: number) => apiClient.delete<{ message: string }>(`/service-categories/${id}`),
}

export const supportTicketsApi = {
  list: (params?: {
    search?: string
    status?: string
    priority?: string
    franchise_id?: number
    assigned_to?: number
    per_page?: number
    page?: number
  }) => apiClient.get<Paginated<SupportTicket>>('/support-tickets', { params }),

  get: (id: number) => apiClient.get<SupportTicket>(`/support-tickets/${id}`),

  update: (id: number, data: { status?: string; priority?: string; assigned_to?: number | null }) =>
    apiClient.put<SupportTicket>(`/support-tickets/${id}`, data),

  reply: (id: number, message: string) =>
    apiClient.post<SupportTicketReply>(`/support-tickets/${id}/reply`, { message }),

  assign: (id: number, assigned_to: number) =>
    apiClient.post<SupportTicket>(`/support-tickets/${id}/assign`, { assigned_to }),

  resolve: (id: number) => apiClient.post<SupportTicket>(`/support-tickets/${id}/resolve`),

  stats: () => apiClient.get<SupportTicketStats>('/support-tickets/stats'),
}

export const reportsApi = {
  franchisePerformance: (params?: { start_date?: string; end_date?: string }) =>
    apiClient.get<{ period: { start: string | null; end: string | null }; franchises: (Franchise & { total_revenue: number; active_users: number })[] }>(
      '/reports/franchise-performance',
      { params }
    ),

  revenue: (params?: { year?: number; franchise_id?: number }) =>
    apiClient.get<{ year: number; data: { month: number; total: number; count: number }[]; summary: { total: number; count: number } }>(
      '/reports/revenue',
      { params }
    ),

  franchiseGrowth: (months = 12) =>
    apiClient.get<{ month: string; new_franchises: number; cumulative: number }[]>('/reports/franchise-growth', {
      params: { months },
    }),

  paymentStatus: (params?: { franchise_id?: number }) =>
    apiClient.get<{ stats: { paid: number; pending: number; overdue: number }; overdue_payments: unknown[] }>(
      '/reports/payment-status',
      { params }
    ),
}

export const settingsApi = {
  list: (group?: string) => apiClient.get<SystemSetting[]>('/settings', { params: group ? { group } : undefined }),

  groups: () => apiClient.get<string[]>('/settings/groups'),

  byGroup: (group: string) => apiClient.get<SystemSetting[]>(`/settings/group/${group}`),

  get: (key: string) => apiClient.get<SystemSetting>(`/settings/${key}`),

  update: (key: string, value: unknown) => apiClient.put<SystemSetting>(`/settings/${key}`, { value }),

  updateBulk: (settings: { key: string; value: unknown }[]) =>
    apiClient.post<{ message: string }>('/settings/bulk', { settings }),
}

export const franchiseUsersApi = {
  list: (params?: { search?: string; franchise_id?: number; status?: string; role?: string; per_page?: number; page?: number }) =>
    apiClient.get<Paginated<FranchiseUser>>('/franchise-users', { params }),

  get: (id: number) => apiClient.get<FranchiseUser & { franchise?: Franchise }>(`/franchise-users/${id}`),

  create: (data: { franchise_id: number; name: string; email: string; password: string; phone?: string; role: string }) =>
    apiClient.post<FranchiseUser>('/franchise-users', data),

  update: (id: number, data: Partial<FranchiseUser>) => apiClient.put<FranchiseUser>(`/franchise-users/${id}`, data),

  remove: (id: number) => apiClient.delete<{ message: string }>(`/franchise-users/${id}`),

  resetPassword: (id: number, password: string, password_confirmation: string) =>
    apiClient.post<{ message: string }>(`/franchise-users/${id}/reset-password`, { password, password_confirmation }),

  getByFranchise: (franchiseId: number) => apiClient.get<FranchiseUser[]>(`/franchises/${franchiseId}/users`),
}

export const franchiseSuburbsApi = {
  list: (params?: { search?: string; franchise_id?: number; state?: string; status?: string; per_page?: number; page?: number }) =>
    apiClient.get<Paginated<FranchiseSuburb>>('/franchise-suburbs', { params }),

  get: (id: number) => apiClient.get<FranchiseSuburb>(`/franchise-suburbs/${id}`),

  create: (data: { franchise_id: number; suburb_name: string; postcode: string; state: string }) =>
    apiClient.post<FranchiseSuburb>('/franchise-suburbs', data),

  update: (id: number, data: Partial<FranchiseSuburb>) => apiClient.put<FranchiseSuburb>(`/franchise-suburbs/${id}`, data),

  remove: (id: number) => apiClient.delete<{ message: string }>(`/franchise-suburbs/${id}`),
}

export const franchiseServicesApi = {
  list: (params?: { search?: string; franchise_id?: number; status?: string; per_page?: number; page?: number }) =>
    apiClient.get<Paginated<FranchiseService>>('/franchise-services', { params }),

  get: (id: number) => apiClient.get<FranchiseService>(`/franchise-services/${id}`),

  create: (data: { franchise_id: number; service_id?: number | null; name: string; description?: string; price: number; duration: number }) =>
    apiClient.post<FranchiseService>('/franchise-services', data),

  update: (id: number, data: Partial<FranchiseService>) => apiClient.put<FranchiseService>(`/franchise-services/${id}`, data),

  remove: (id: number) => apiClient.delete<{ message: string }>(`/franchise-services/${id}`),
}

export const forumCategoriesApi = {
  list: (params?: { search?: string; status?: string; per_page?: number; page?: number }) =>
    apiClient.get<Paginated<ForumCategory>>('/forum-categories', { params }),

  get: (id: number) => apiClient.get<ForumCategory>(`/forum-categories/${id}`),

  create: (data: { name: string; description?: string }) => apiClient.post<ForumCategory>('/forum-categories', data),

  update: (id: number, data: Partial<ForumCategory>) => apiClient.put<ForumCategory>(`/forum-categories/${id}`, data),

  remove: (id: number) => apiClient.delete<{ message: string }>(`/forum-categories/${id}`),
}

export const forumTopicsApi = {
  list: (params?: { search?: string; category_id?: number; status?: string; per_page?: number; page?: number }) =>
    apiClient.get<Paginated<ForumTopic>>('/forum-topics', { params }),

  get: (id: number) => apiClient.get<ForumTopic>(`/forum-topics/${id}`),

  create: (data: { category_id?: number | null; name: string; description?: string }) =>
    apiClient.post<ForumTopic>('/forum-topics', data),

  update: (id: number, data: Partial<ForumTopic>) => apiClient.put<ForumTopic>(`/forum-topics/${id}`, data),

  remove: (id: number) => apiClient.delete<{ message: string }>(`/forum-topics/${id}`),
}

export const forumPostsApi = {
  list: (params?: { search?: string; category_id?: number; topic_id?: number; status?: string; per_page?: number; page?: number }) =>
    apiClient.get<Paginated<ForumPost>>('/forum-posts', { params }),

  get: (id: number) => apiClient.get<ForumPost>(`/forum-posts/${id}`),

  create: (data: { category_id?: number | null; topic_id?: number | null; title: string; content?: string; author_name?: string }) =>
    apiClient.post<ForumPost>('/forum-posts', data),

  update: (id: number, data: Partial<ForumPost>) => apiClient.put<ForumPost>(`/forum-posts/${id}`, data),

  remove: (id: number) => apiClient.delete<{ message: string }>(`/forum-posts/${id}`),
}

export const forumGroupsApi = {
  list: (params?: { search?: string; status?: string; per_page?: number; page?: number }) =>
    apiClient.get<Paginated<ForumGroup>>('/forum-groups', { params }),

  get: (id: number) => apiClient.get<ForumGroup>(`/forum-groups/${id}`),

  create: (data: { name: string; description?: string; permissions?: string }) =>
    apiClient.post<ForumGroup>('/forum-groups', data),

  update: (id: number, data: Partial<ForumGroup>) => apiClient.put<ForumGroup>(`/forum-groups/${id}`, data),

  remove: (id: number) => apiClient.delete<{ message: string }>(`/forum-groups/${id}`),
}

export const appVersionsApi = {
  list: (params?: { search?: string; per_page?: number; page?: number }) =>
    apiClient.get<Paginated<AppVersion>>('/app-versions', { params }),

  get: (id: number) => apiClient.get<AppVersion>(`/app-versions/${id}`),

  create: (data: { version: string; title: string; description?: string; logout_required?: boolean; refresh_required?: boolean }) =>
    apiClient.post<AppVersion>('/app-versions', data),

  update: (id: number, data: Partial<AppVersion>) => apiClient.put<AppVersion>(`/app-versions/${id}`, data),

  remove: (id: number) => apiClient.delete<{ message: string }>(`/app-versions/${id}`),
}
