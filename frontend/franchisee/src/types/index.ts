// Domain types for Dog Wash System

export interface User {
  id: string
  email: string
  name: string
  role: 'owner' | 'franchise_admin' | 'staff'
  companyId?: string
  company_id?: string
  company_name?: string
  avatar?: string
  phone?: string
  location?: string
  territory?: string
}

export interface Lead {
  id: string
  firstName: string
  lastName: string
  customerName: string
  email?: string
  phone: string
  alternatePhone?: string
  interestedServices: string
  address: string
  suburb?: string
  postcode?: string
  petBreed?: string
  referredBy?: string
  additionalNote?: string
  notes?: string
  source: 'phone' | 'internet' | 'walk-in' | 'referral'
  leadsFrom: 'phone' | 'internet'
  status:
    | 'new'
    | 'contacted'
    | 'qualified'
    | 'converted'
    | 'lost'
    | 'snoozed'
    | 'completed'
    | 'cancellation_request'
    | 'message_for_operator'
  companyId?: string
  snoozedUntil?: string
  comments?: LeadComment[]
  createdAt: string
  updatedAt: string
}

export interface LeadComment {
  id: string
  leadId: string
  comment: string
  createdBy: string
  createdAt: string
}

export interface Customer {
  id: string
  first_name: string
  last_name: string
  email: string
  phone: string
  address: string
  street_address?: string
  suburb?: string
  postcode?: string
  state?: string
  other_email?: string
  other_phone?: string
  referred_by?: string
  is_ndis?: boolean
  is_subscribed?: boolean
  is_active?: boolean
  company_id: string
  notes: string
  pets?: Pet[]
  bookings?: Booking[]
  is_archived?: boolean
  created_at: string
  updated_at: string
}

export interface Pet {
  id: string
  name: string
  gender?: 'male' | 'female' | null
  birthDate?: string
  breed: string
  size: 'Toy' | 'Small' | 'Medium' | 'Large' | 'Extra Large'
  ownerId: string
  notes?: string
  image?: string
  isActive: boolean
  createdAt: string
  updatedAt: string
}

export interface Service {
  id: string
  name: string
  category: string
  price: number
  duration: number
}

export interface BookingDetail {
  id: string
  bookingId: string
  petId: string
  serviceId: string
  price: number
  pet?: Pet
  service?: Service
  createdAt: string
  updatedAt: string
}

export interface Booking {
  id: string
  customerId: string
  customer?: Customer
  petIds: string[]
  serviceIds: string[]
  details?: BookingDetail[]
  companyId: string
  startDate: string
  startTime: string
  endTime?: string
  calendarColor?: string
  sendSms?: boolean
  sendEmail?: boolean
  status: 'active' | 'cancelled' | 'completed' | 'archived'
  total: number
  recurringId?: string
  isRecurring?: boolean // Derived from recurringId
  notes?: string
  createdAt: string
  updatedAt: string
}

export interface Blockout {
  id: string
  companyId: string
  title: string
  location?: string
  startDate: string
  startTime: string
  endDate: string
  endTime: string
  isRecurring: boolean
  repeatEvery?: string
  repeatOn?: string
  repeatUntil?: string
  notes?: string
  active: boolean
  createdAt: string
  updatedAt: string
}

export interface InventoryItem {
  id: string
  name: string
  category: 'office' | 'shampoo' | 'treats' | 'uniforms' | 'marketing'
  sku: string
  quantity: number
  minStock: number
  unitPrice: number
  companyId: string
}

export interface InventoryOrder {
  id: string
  type: 'office' | 'shampoo' | 'treats' | 'uniforms' | 'marketing'
  items: OrderItem[]
  status: 'pending' | 'ordered' | 'received' | 'completed'
  totalCost: number
  companyId: string
  createdAt: string
  updatedAt: string
}

export interface OrderItem {
  itemId: string
  quantity: number
  unitCost: number
}

export interface IncomeCategory {
  id: string
  company_id?: string
  name: string
  description?: string
  gst_inclusive: boolean
  is_active: boolean
  is_system: boolean
  incomes_count?: number
  created_at: string
  updated_at: string
}

export interface ExpenseCategory {
  id: string
  company_id?: string
  name: string
  description?: string
  gst_inclusive: boolean
  is_active: boolean
  is_system: boolean
  expenses_count?: number
  created_at: string
  updated_at: string
}

export interface Income {
  id: string
  company_id: string
  income_category_id?: string
  booking_id?: string
  title: string
  description?: string
  amount: number
  income_date: string
  is_active: boolean
  recurring_income_id?: string
  category?: IncomeCategory
  created_at: string
  updated_at: string
}

export interface Expense {
  id: string
  company_id: string
  expense_category_id?: string
  title: string
  description?: string
  amount: number
  expense_date: string
  is_active: boolean
  recurring_expense_id?: string
  category?: ExpenseCategory
  created_at: string
  updated_at: string
}

export interface Document {
  id: string
  title: string
  description: string
  fileUrl: string
  fileType: string
  companyId?: string
  visibility: 'global' | 'franchise'
  category?: 'manual' | 'template' | 'other' | 'general' | string
  uploadedBy: string
  createdAt: string
}

export interface CommunicationTemplate {
  id: string
  name: string
  type: 'email' | 'sms'
  subject?: string
  body: string
  variables: string[]
}

export interface CommunicationLog {
  id: string
  templateId?: string
  recipientIds: string[]
  type: 'email' | 'sms'
  status: 'pending' | 'sent' | 'failed'
  sentAt?: string
  createdAt: string
}

export interface SmsHistory {
  id: string
  user_id?: string | null
  to_number: string
  customer_name?: string | null
  message: string
  status: 'queued' | 'sent' | 'failed'
  gateway_response?: string | null
  sent_at?: string | null
  created_at: string
  updated_at: string
}

export interface EmailHistory {
  id: string
  user_id?: string | null
  from_email: string
  to_email: string
  subject: string
  body?: string | null
  status: 'queued' | 'sent' | 'failed'
  mailer_response?: string | null
  sent_at?: string | null
  created_at: string
  updated_at: string
}

export interface ForumThread {
  id: string
  title: string
  content: string
  authorId: string
  author?: User
  groupId?: string | null
  group_id?: string | null
  group?: ForumGroup
  topic?: string
  liked?: boolean
  likes_count: number
  comments: ForumComment[]
  isPinned: boolean
  createdAt: string
  updatedAt: string
  created_at: string
}

export interface ForumGroup {
  id: string
  name: string
  description?: string
  type: 'topic' | 'state' | 'custom'
  icon?: string
  color?: string
  createdBy?: string
  created_by?: string
  creator?: User
  isPublic: boolean
  is_public: boolean
  membersCount: number
  members_count: number
  threadsCount: number
  threads_count: number
  members?: User[]
  createdAt: string
  created_at: string
}

export interface ForumComment {
  id: string
  threadId: string
  parentId?: string | null
  content: string
  authorId: string
  author?: User
  liked?: boolean
  likesCount: number
  likes_count: number
  replies?: ForumComment[]
  createdAt: string
  created_at: string
}

export interface ForumNotification {
  id: string
  user_id: string
  actor_id?: string | null
  actor?: User
  group_id?: string | null
  thread_id: string
  comment_id?: string | null
  type: 'post' | 'comment' | 'reply' | 'like_thread' | 'like_comment' | string
  message: string
  is_read: boolean
  created_at: string
}

export interface NewsItem {
  id: string
  title: string
  content: string
  authorId: string
  isPublished: boolean
  publishedAt?: string
  createdAt: string
  updatedAt: string
  published_at?: string
  created_at: string
}

export interface Activity {
  id: string
  userId: string
  user?: User
  action: string
  entityType: string
  entityId: string
  metadata?: Record<string, unknown>
  createdAt: string
}

export interface DashboardMetrics {
  activeCustomers: number
  activeBookings: number
  cancellations: number
  forumNotifications: number
  birthdayThisMonth: number
  leadsCount: number
  cancelRequests: number
  operatorMessages: number
  attentionCount: number
  totalRevenue: number
  thisMonthRevenue: number
}

export interface DashboardActivity {
  id: string
  message: string
  createdAt: string
}

export interface DashboardScheduleItem {
  id: string
  customer: string
  date: string
  startTime: string
  total: number
}

export interface DashboardForecastItem {
  id: string
  week: string
  bookings: number
  income: number
  services: number
}

export interface DashboardNewsPayload {
  news: NewsItem[]
  bluesNews: NewsItem[]
}

export interface StockTake {
  id?: string
  stockTakeID?: string
  appID?: string
  companyID?: string
  inventory_category_id?: string
  updated_at?: string
  createdAt?: string
  values?: Record<string, { qty: string; percent: string }>
}

export interface StockTakeLog {
  id?: string
  stockTakeLogID?: string
  logID?: string
  inventory_id?: string
  inventory_item_name?: string
  no_of_bookings?: number
  current_stock?: number
  remaining_percent?: number
  log_quantity?: number
  log_remaining_percent?: number
  log_created_at?: string
  createdAt?: string
}
