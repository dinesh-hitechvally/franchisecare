import { apiClient, API_BASE_URL } from './client'
import type { Lead, Customer, Pet, Service, Booking, Blockout, InventoryItem, InventoryOrder, Income, Expense, Document, CommunicationTemplate, CommunicationLog, ForumThread, ForumGroup, ForumComment, ForumNotification, NewsItem, DashboardMetrics, User } from '../types'

export type PaginationMeta = {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export type PaginatedBookings = {
  data: Booking[]
  meta: PaginationMeta
}

function mapBookingFromApi(b: any): Booking {
  const raw = b as any
  b.startDate = raw.start_date || raw.date || b.startDate
  b.startTime = raw.start_time || raw.time || b.startTime
  b.endTime = raw.end_time || b.endTime
  b.calendarColor = raw.calendar_color || raw.color || b.calendarColor
  b.sendSms = raw.send_sms !== undefined ? !!raw.send_sms : b.sendSms
  b.sendEmail = raw.send_email !== undefined ? !!raw.send_email : b.sendEmail
  b.recurringId = raw.recurring_id || raw.recurring_parent_id || b.recurringId
  b.isRecurring = !!b.recurringId
  b.createdAt = raw.created_at || b.createdAt
  b.updatedAt = raw.updated_at || b.updatedAt
  b.customerId = raw.customer_id || b.customerId
  b.companyId = raw.company_id || raw.franchise_id || b.companyId

  if (b.details) {
    b.details = (b.details as any[]).map((d) => {
      const rawD = d as any
      d.petId = rawD.pet_id || rawD.item_id || d.petId
      d.serviceId = rawD.service_id || d.serviceId
      // Map 'item' to 'pet' for consistency with frontend
      if (rawD.item && !d.pet) {
        d.pet = rawD.item
      }
      return d
    })
  }

  if (b.customer) {
    (b.customer as any).street_address =
      (b.customer as any).street_address || (b.customer as any).street_address
  }
  return b as Booking
}

function mapBookingsFromApi(rows: unknown): Booking[] {
  if (!Array.isArray(rows)) return []
  return rows.map((row) => mapBookingFromApi(row))
}

function mapRecurringFromApi(r: any): any {
  const raw = r as any
  r.startDate = raw.start_date || r.startDate
  r.startTime = raw.start_time || r.startTime
  r.endTime = raw.end_time || r.endTime
  r.color = raw.color || raw.calendar_color || r.color
  r.calendarColor = r.color
  r.autoExtend = raw.auto_extend !== undefined ? !!raw.auto_extend : r.autoExtend
  r.totalPerBooking = raw.total_per_booking || r.totalPerBooking
  r.cancelledDate = raw.cancelled_date || r.cancelledDate
  r.cancellationReason = raw.cancellation_reason || r.cancellationReason
  r.repeatUntil = raw.repeat_until || r.repeatUntil
  r.repeatTime = raw.repeat_time || r.repeatTime
  r.repeatDay = raw.repeat_day || r.repeatDay
  r.createdAt = raw.created_at || r.createdAt
  r.updatedAt = raw.updated_at || r.updatedAt
  r.recurringFrequency = raw.frequency || r.recurringFrequency
  r.date = r.startDate
  r.total = raw.total || r.total || r.totalPerBooking || '0.00'
  r.companyId = raw.company_id || raw.franchise_id || r.companyId

  if (r.details) {
    r.details = (r.details as any[]).map((d) => {
      const rawD = d as any
      d.petId = rawD.pet_id || d.petId
      d.serviceId = rawD.service_id || d.serviceId
      d.bookingRecurringId = rawD.booking_recurring_id || d.bookingRecurringId
      return d
    })
  }

  if (r.bookings && Array.isArray(r.bookings)) {
    r.bookings = r.bookings.map((b: any) => {
      const rawB = b as any
      b.id = rawB.id
      b.date = rawB.start_date || rawB.date
      b.startTime = rawB.start_time || rawB.startTime
      b.endTime = rawB.end_time || rawB.endTime
      b.status = rawB.status || b.status
      b.total = rawB.total || b.total
      b.duration = rawB.duration || b.duration
      b.recurringId = rawB.recurring_id || b.recurringId
      b.customerId = rawB.customer_id || b.customerId
      b.companyId = rawB.company_id || b.companyId
      b.notes = rawB.notes || b.notes
      b.calendarColor = rawB.calendar_color || rawB.calendarColor
      b.createdAt = rawB.created_at || b.createdAt
      b.updatedAt = rawB.updated_at || b.updatedAt
      return b
    })
  }

  return r
}

function mapRecurringsFromApi(rows: unknown): any[] {
  if (!Array.isArray(rows)) return []
  return rows.map((row) => mapRecurringFromApi(row))
}

function mapBlockoutFromApi(b: any): any {
  const raw = b as any
  b.startDate = raw.start_date || b.startDate
  b.startTime = raw.start_time || b.startTime
  b.endDate = raw.end_date || b.endDate
  b.endTime = raw.end_time || b.endTime
  b.isRecurring = raw.is_recurring !== undefined ? !!raw.is_recurring : b.isRecurring
  b.repeatEvery = raw.repeat_every || b.repeatEvery
  b.repeatOn = raw.repeat_on || b.repeatOn
  b.repeatUntil = raw.repeat_until || b.repeatUntil
  b.companyId = raw.company_id || b.companyId
  b.createdAt = raw.created_at || b.createdAt
  b.updatedAt = raw.updated_at || b.updatedAt
  return b as any
}

function mapBlockoutsFromApi(rows: unknown): any[] {
  if (!Array.isArray(rows)) return []
  return rows.map((row) => mapBlockoutFromApi(row))
}

export const authApi = {
  login: (email: string, password: string) =>
    apiClient.post<{ access_token: string; user: User }>('/login', { email, password }),
  
  logout: () => apiClient.post('/logout'),
  
  me: () => apiClient.get<User>('/user'),

  changePassword: (data: any) => apiClient.post('/change-password', data),
}

export const usersApi = {
  getById: (id: string) => apiClient.get<User>(`/users/${id}`),
  
  getPosts: (userId: string, params?: { per_page?: number; page?: number }) =>
    apiClient.get<{ data: ForumThread[]; meta: PaginationMeta }>(`/users/${userId}/posts`, { params }),

  createPost: (data: { title?: string; content: string; topic?: string; group_id?: string | number }) =>
    apiClient.post<ForumThread>('/profile/posts', data),

  updatePost: (threadId: string | number, data: { title?: string; content: string; topic?: string }) =>
    apiClient.patch<ForumThread>(`/profile/posts/${threadId}`, data),
}

export const leadsApi = {
  getAll: (params?: { status?: string }) =>
    apiClient.get<Lead[]>('/leads', { params }),

  getById: (id: string) => apiClient.get<Lead>(`/leads/${id}`),

  create: (data: Omit<Lead, 'id' | 'createdAt' | 'updatedAt'>) =>
    apiClient.post<Lead>('/leads', data),

  update: (id: string, data: Partial<Lead>) =>
    apiClient.put<Lead>(`/leads/${id}`, data),

  delete: (id: string) => apiClient.delete(`/leads/${id}`),

  convert: (id: string, staffId?: string) =>
    apiClient.post<Customer>(`/leads/${id}/convert`, { staffId }),
  
  import: (file: File) => {
    const formData = new FormData()
    formData.append('file', file)
    return apiClient.post('/leads/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
}

export const customersApi = {
  getAll: (params?: { search?: string; status?: string }) =>
    apiClient.get<Customer[]>('/customers', { params }),
  
  getById: (id: string) => apiClient.get<Customer>(`/customers/${id}`),
  
  create: (data: Omit<Customer, 'id' | 'createdAt' | 'updatedAt'>) =>
    apiClient.post<Customer>('/customers', data),
  
  update: (id: string, data: Partial<Customer>) =>
    apiClient.put<Customer>(`/customers/${id}`, data),
  
  delete: (id: string) => apiClient.delete(`/customers/${id}`),

  restore: (id: string) => apiClient.post(`/customers/${id}/restore`),
  getAudits: (id: string, page: number = 1) =>
    apiClient.get(`/customers/${id}/audits?page=${page}`),
}

export const servicesApi = {
  getAll: (params?: { category?: string }) =>
    apiClient.get<Service[]>('/services', { params }),
  
  getById: (id: string) => apiClient.get<Service>(`/services/${id}`),
  
  create: (data: Omit<Service, 'id'>) =>
    apiClient.post<Service>('/services', data),
}

export const petsApi = {
  getByCustomer: (customerId: string) =>
    apiClient.get<Pet[]>(`/customers/${customerId}/pets`),
  
  getById: (id: string) => apiClient.get<Pet>(`/pets/${id}`),
  
  create: (data: any) =>
    apiClient.post<Pet>('/pets', data),
  
  update: (id: string, data: any) => {
    if (data instanceof FormData) {
      data.append('_method', 'PUT');
      return apiClient.post<Pet>(`/pets/${id}`, data);
    }
    return apiClient.put<Pet>(`/pets/${id}`, data);
  },
  
  delete: (id: string) => apiClient.delete(`/pets/${id}`),
  
  saveIntakeForm: (data: any) =>
    apiClient.post('/intake-forms', data),

  getWaivers: (petId: string) =>
    apiClient.get(`/pets/${petId}/waivers`),

  getHistory: (petId: string, type: string) =>
    apiClient.get<any[]>(`/pets/${petId}/waivers/${type}/history`),

  getWaiver: (id: string | number) =>
    apiClient.get(`/waivers/${id}`),

  getAudits: (petId: string, page: number = 1) =>
    apiClient.get(`/pets/${petId}/audits?page=${page}`),

  uploadSignature: (id: string, signature: string) =>
    apiClient.post(`/pets/${id}/signature`, { signature }),
}

export const bookingsApi = {
  getAll: (params?: {
    status?: string
    customer_id?: string
    dateFrom?: string
    dateTo?: string
    search?: string
  }) =>
    apiClient.get<Booking[]>('/bookings', { params }).then((data) => mapBookingsFromApi(data)),

  getPaginated: (params: {
    page: number
    per_page?: number
    status?: string
    customer_id?: string
    dateFrom?: string
    dateTo?: string
    search?: string
  }) =>
    apiClient
      .get<{ data: unknown[]; meta: PaginationMeta }>('/bookings', {
        params: {
          ...params,
          page: params.page,
          per_page: params.per_page ?? 25,
        },
      })
      .then((res) => ({
        data: mapBookingsFromApi(res.data),
        meta: res.meta,
      })),
  
  getById: (id: string) => apiClient.get<Booking>(`/bookings/${id}`).then((data) => mapBookingFromApi(data)),
  
  getSchedule: (days: number = 5) =>
    apiClient.get<Booking[]>('/bookings/schedule', { params: { days } }),
  
  create: (data: Omit<Booking, 'id' | 'createdAt' | 'updatedAt' | 'status'>) =>
    apiClient.post<Booking>('/bookings', data),
  
  update: (id: string, data: Partial<Booking>) =>
    apiClient.put<Booking>(`/bookings/${id}`, data),
  
  updateStatus: (id: string, status: Booking['status']) =>
    apiClient.patch<Booking>(`/bookings/${id}/status`, { status }),

  delete: (id: string) => apiClient.delete(`/bookings/${id}`),

  generateInvoice: (id: string) => {
    const token = localStorage.getItem('token');
    window.open(`${API_BASE_URL}/bookings/${id}/invoice?token=${token}`, '_blank');
  },

  generateReceipt: (id: string) => {
    const token = localStorage.getItem('token');
    window.open(`${API_BASE_URL}/bookings/${id}/receipt?token=${token}`, '_blank');
  },
}

// Recurring Bookings API
export type PaginatedRecurringBookings = {
  data: any[]
  meta: PaginationMeta
}

export const recurringBookingsApi = {
  getAll: (params?: {
    status?: string
    companyId?: string
    customer_id?: string
    search?: string
    hide_expired?: boolean
  }) =>
    apiClient.get<any[]>('/booking-recurrings', { params }).then((data) => mapRecurringsFromApi(data)),

  getPaginated: (params: {
    page: number
    per_page?: number
    status?: string
    companyId?: string
    customer_id?: string
    search?: string
    hide_expired?: boolean
  }) =>
    apiClient
      .get<{ data: unknown[]; meta: PaginationMeta }>('/booking-recurrings', {
        params: {
          ...params,
          page: params.page,
          per_page: params.per_page ?? 25,
        },
      })
      .then((res) => ({
        data: mapRecurringsFromApi(res.data),
        meta: res.meta,
      })),
  
  getById: (id: string) => apiClient.get<any>(`/booking-recurrings/${id}`),
  
  create: (data: any) =>
    apiClient.post<any>('/booking-recurrings', data),
  
  update: (id: string, data: Partial<any>) =>
    apiClient.put<any>(`/booking-recurrings/${id}`, data),
  
  cancel: (id: string, reason?: string) =>
    apiClient.patch<any>(`/booking-recurrings/${id}/cancel`, { cancellation_reason: reason }),
  
  delete: (id: string) => apiClient.delete(`/booking-recurrings/${id}`),
}

export const blockoutsApi = {
  getAll: (params?: { company_id?: string; is_recurring?: boolean; search?: string }) =>
    apiClient.get<Blockout[]>('/blockouts', { params }).then((data) => mapBlockoutsFromApi(data)),

  getPaginated: (params: {
    page: number
    per_page?: number
    company_id?: string
    is_recurring?: boolean
    search?: string
  }) =>
    apiClient.get<{ data: Blockout[]; meta: PaginationMeta }>('/blockouts', {
      params: {
        ...params,
        page: params.page,
        per_page: params.per_page ?? 25,
      },
    }).then((res) => ({
      data: mapBlockoutsFromApi(res.data),
      meta: res.meta,
    })),

  getById: (id: string) => apiClient.get<Blockout>(`/blockouts/${id}`).then((data) => mapBlockoutFromApi(data)),

  create: (data: Partial<Blockout>) => apiClient.post<Blockout>('/blockouts', data).then((data) => mapBlockoutFromApi(data)),

  update: (id: string, data: Partial<Blockout>) => apiClient.put<Blockout>(`/blockouts/${id}`, data).then((data) => mapBlockoutFromApi(data)),

  delete: (id: string) => apiClient.delete(`/blockouts/${id}`),
}

export const inventoryApi = {
  getItems: (params?: { category?: string; franchiseId?: string }) =>
    apiClient.get<InventoryItem[]>('/inventory/items', { params }),
  
  createItem: (data: Omit<InventoryItem, 'id'>) =>
    apiClient.post<InventoryItem>('/inventory/items', data),
  
  updateItem: (id: string, data: Partial<InventoryItem>) =>
    apiClient.put<InventoryItem>(`/inventory/items/${id}`, data),
  
  deleteItem: (id: string) => apiClient.delete(`/inventory/items/${id}`),
  
  getOrders: (params?: { status?: string; franchiseId?: string }) =>
    apiClient.get<InventoryOrder[]>('/inventory/orders', { params }),
  
  createOrder: (data: Omit<InventoryOrder, 'id' | 'createdAt' | 'updatedAt'>) =>
    apiClient.post<InventoryOrder>('/inventory/orders', data),
  
  updateOrderStatus: (id: string, status: InventoryOrder['status']) =>
    apiClient.patch<InventoryOrder>(`/inventory/orders/${id}/status`, { status }),
}

export const financeApi = {
  getIncome: (params?: { dateFrom?: string; dateTo?: string; franchiseId?: string }) =>
    apiClient.get<Income[]>('/finance/income', { params }),
  
  createIncome: (data: Omit<Income, 'id' | 'createdAt'>) =>
    apiClient.post<Income>('/finance/income', data),
  
  updateIncome: (id: string, data: Partial<Income>) =>
    apiClient.put<Income>(`/finance/income/${id}`, data),
  
  deleteIncome: (id: string) => apiClient.delete(`/finance/income/${id}`),
  
  getExpenses: (params?: { dateFrom?: string; dateTo?: string; franchiseId?: string }) =>
    apiClient.get<Expense[]>('/finance/expenses', { params }),
  
  createExpense: (data: Omit<Expense, 'id' | 'createdAt'>) =>
    apiClient.post<Expense>('/finance/expenses', data),
  
  updateExpense: (id: string, data: Partial<Expense>) =>
    apiClient.put<Expense>(`/finance/expenses/${id}`, data),
  
  deleteExpense: (id: string) => apiClient.delete(`/finance/expenses/${id}`),
  
  getSummary: (params?: { dateFrom?: string; dateTo?: string; franchiseId?: string }) =>
    apiClient.get<{ income: number; expenses: number; net: number }>('/finance/summary', { params }),
}

export const documentsApi = {
  getAll: (params?: { franchiseId?: string }) =>
    apiClient.get<Document[]>('/documents', { params }),
  
  upload: (file: File, metadata: { title: string; description: string; visibility: string; franchiseId?: string }) => {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('metadata', JSON.stringify(metadata))
    return apiClient.post<Document>('/documents', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  
  update: (id: string, data: Partial<Document>) =>
    apiClient.put<Document>(`/documents/${id}`, data),
  
  delete: (id: string) => apiClient.delete(`/documents/${id}`),
}

export const communicationApi = {
  getTemplates: () => apiClient.get<CommunicationTemplate[]>('/communication/templates'),
  
  createTemplate: (data: Omit<CommunicationTemplate, 'id'>) =>
    apiClient.post<CommunicationTemplate>('/communication/templates', data),
  
  updateTemplate: (id: string, data: Partial<CommunicationTemplate>) =>
    apiClient.put<CommunicationTemplate>(`/communication/templates/${id}`, data),
  
  deleteTemplate: (id: string) => apiClient.delete(`/communication/templates/${id}`),
  
  send: (data: { templateId?: string; recipientIds: string[]; type: 'email' | 'sms'; content?: string }) =>
    apiClient.post<CommunicationLog>('/communication/send', data),
  
  getLogs: (params?: { recipientId?: string }) =>
    apiClient.get<CommunicationLog[]>('/communication/logs', { params }),
}

export const forumApi = {
  getThreads: (params?: { topic?: string; search?: string; page?: number; per_page?: number; group_id?: string; no_group?: boolean }) =>
    apiClient.get<{ data: ForumThread[]; meta: PaginationMeta }>('/forum/threads', { params }),

  getThread: (id: string) => apiClient.get<ForumThread>(`/forum/threads/${id}`),

  createThread: (data: { title?: string; content: string; topic?: string; group_id?: string }) =>
    apiClient.post<ForumThread>('/forum/threads', data),

  addComment: (threadId: string, content: string) =>
    apiClient.post<ForumComment>(`/forum/threads/${threadId}/comments`, { content }),

  likeThread: (id: string) => apiClient.post<{ likes_count: number; liked: boolean }>(`/forum/threads/${id}/like`),

  likeComment: (commentId: string) => apiClient.post<{ likes_count: number; liked: boolean }>(`/forum/comments/${commentId}/like`),

  replyToComment: (commentId: string, content: string) =>
    apiClient.post<ForumComment>(`/forum/comments/${commentId}/reply`, { content }),

  getNotifications: (params?: { group_id?: string; no_group?: boolean; unread_only?: boolean; limit?: number }) =>
    apiClient.get<ForumNotification[]>('/forum/notifications', { params }),

  markAllNotificationsAsRead: (params?: { group_id?: string; no_group?: boolean }) =>
    apiClient.post<{ updated: number }>('/forum/notifications/read-all', params),

  markNotificationAsRead: (id: string) =>
    apiClient.post<ForumNotification>(`/forum/notifications/${id}/read`),

  deleteThread: (id: string | number) => apiClient.delete(`/forum/threads/${id}`),

  // Groups
  getGroups: (params?: { type?: 'topic' | 'state' | 'custom'; my_groups?: boolean }) =>
    apiClient.get<ForumGroup[]>('/forum/groups', { params }),

  getGroup: (id: string) => apiClient.get<ForumGroup>(`/forum/groups/${id}`),

  createGroup: (data: { name: string; description?: string; type: 'topic' | 'state' | 'custom'; icon?: string; color?: string; is_public?: boolean }) =>
    apiClient.post<ForumGroup>('/forum/groups', data),

  updateGroup: (id: string, data: Partial<ForumGroup>) =>
    apiClient.put<ForumGroup>(`/forum/groups/${id}`, data),

  deleteGroup: (id: string) => apiClient.delete(`/forum/groups/${id}`),

  joinGroup: (id: string) => apiClient.post(`/forum/groups/${id}/join`),

  leaveGroup: (id: string) => apiClient.post(`/forum/groups/${id}/leave`),

  getGroupMembers: (id: string) => apiClient.get<User[]>(`/forum/groups/${id}/members`),
}

export const newsApi = {
  getAll: (params?: { isPublished?: boolean; category?: string; search?: string }) =>
    apiClient.get<NewsItem[]>('/news', { params }),
  
  getPaginated: (params: {
    page: number
    per_page?: number
    isPublished?: boolean
    category?: string
    search?: string
  }) =>
    apiClient
      .get<{ data: NewsItem[]; meta: PaginationMeta }>('/news', {
        params: {
          ...params,
          page: params.page,
          per_page: params.per_page ?? 10,
        },
      }),

  getById: (id: string) => apiClient.get<NewsItem>(`/news/${id}`),
  
  create: (data: Omit<NewsItem, 'id' | 'createdAt' | 'updatedAt'>) =>
    apiClient.post<NewsItem>('/news', data),
  
  update: (id: string, data: Partial<NewsItem>) =>
    apiClient.put<NewsItem>(`/news/${id}`, data),
  
  publish: (id: string) => apiClient.patch<NewsItem>(`/news/${id}/publish`),
  
  delete: (id: string) => apiClient.delete(`/news/${id}`),
}

export const dashboardApi = {
  getMetrics: () => apiClient.get<DashboardMetrics>('/dashboard/metrics'),

  getActivities: (limit: number = 10) =>
    apiClient.get('/dashboard/activities', { params: { limit } }),

  getRecentNews: () => apiClient.get<NewsItem[]>('/dashboard/news'),
}

export const calendarApi = {
  getEvents: (params: {
    company_id: string
    start_date: string
    end_date: string
    event_type?: string
  }) =>
    apiClient.get<any[]>('/calendar-events', { params }),

  getByMonth: (params: {
    company_id: string
    year: number
    month: number
  }) =>
    apiClient.get<any[]>('/calendar-events/month', { params }),

  create: (data: any) =>
    apiClient.post('/calendar-events', data),

  update: (id: string, data: any) =>
    apiClient.put(`/calendar-events/${id}`, data),

  delete: (id: string) =>
    apiClient.delete(`/calendar-events/${id}`),

  sync: (companyId: string) =>
    apiClient.post('/calendar-events/sync', { company_id: companyId }),
}
