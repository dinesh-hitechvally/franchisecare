export interface User {
  id: number
  name: string
  email: string
  role: string
  status: string
  phone: string | null
  avatar: string | null
}

export interface Paginated<T> {
  data: T[]
  current_page: number
  last_page: number
  total: number
  per_page: number
}

export interface Franchise {
  id: number
  name: string
  code: string
  owner_name: string
  email: string
  phone: string | null
  mobile: string | null
  address: string | null
  suburb: string | null
  state: string | null
  postcode: string | null
  abn: string | null
  franchise_fee: number | null
  royalty_percentage: number | null
  marketing_fee: number | null
  start_date: string | null
  end_date: string | null
  contract_length: number | null
  territory: string | null
  notes: string | null
  status: 'ACTIVE' | 'INACTIVE' | 'SUSPENDED' | 'TERMINATED'
  franchisee_type: 'MASTER_FRANCHISEE' | 'FRANCHISEE' | 'FRANCHISOR' | null
  has_ipad: boolean
  tscs_accepted: boolean
  tscs_accepted_at: string | null
  logo?: string | null
  created_at: string
  updated_at: string
}

export interface FranchiseUser {
  id: number
  franchise_id: number
  name: string
  email: string
  phone: string | null
  role: 'OWNER' | 'MANAGER' | 'STAFF'
  status: 'ACTIVE' | 'INACTIVE'
  avatar: string | null
  created_at: string
  updated_at: string
  franchise?: { id: number; name: string; code: string }
}

export interface FranchiseSuburb {
  id: number
  franchise_id: number
  suburb_name: string
  postcode: string
  state: string
  status: 'ACTIVE' | 'INACTIVE'
  created_at: string
  updated_at: string
  franchise?: { id: number; name: string; code: string } | null
}

export interface FranchiseService {
  id: number
  franchise_id: number
  service_id: number | null
  name: string
  description: string | null
  price: number
  duration: number
  status: 'ACTIVE' | 'INACTIVE'
  created_at: string
  updated_at: string
  franchise?: { id: number; name: string; code: string }
  service?: { id: number; name: string } | null
}

export interface ForumCategory {
  id: number
  name: string
  description: string | null
  status: 'ACTIVE' | 'INACTIVE'
  created_at: string
  updated_at: string
  posts_count?: number
}

export interface ForumTopic {
  id: number
  category_id: number | null
  name: string
  description: string | null
  status: 'ACTIVE' | 'INACTIVE'
  created_at: string
  updated_at: string
  category?: { id: number; name: string } | null
  posts_count?: number
}

export interface ForumPost {
  id: number
  category_id: number | null
  topic_id: number | null
  title: string
  content: string | null
  author_name: string | null
  views: number
  status: 'ACTIVE' | 'INACTIVE'
  created_at: string
  updated_at: string
  category?: { id: number; name: string } | null
  topic?: { id: number; name: string } | null
}

export interface ForumGroup {
  id: number
  name: string
  description: string | null
  permissions: string | null
  status: 'ACTIVE' | 'INACTIVE'
  created_at: string
  updated_at: string
}

export interface AppVersion {
  id: number
  version: string
  title: string
  description: string | null
  logout_required: boolean
  refresh_required: boolean
  created_at: string
  updated_at: string
}

export interface FranchiseAudit {
  id: number
  franchise_id: number
  user_id: number | null
  action: string
  changes?: Record<string, unknown>
  created_at: string
  user?: { id: number; name: string } | null
}

export interface AdminUser {
  id: number
  name: string
  email: string
  phone: string | null
  role: 'ADMIN' | 'SUPPORT' | 'VIEWER'
  status: 'ACTIVE' | 'INACTIVE'
  avatar: string | null
  created_at: string
  updated_at: string
}

export interface NewsItem {
  id: number
  title: string
  content: string
  category: string | null
  status: 'DRAFT' | 'PUBLISHED'
  image: string | null
  user_id: number
  published_at: string | null
  created_at: string
  updated_at: string
  author?: { id: number; name: string }
}

export interface DocumentItem {
  id: number
  title: string
  description: string | null
  category: 'MANUALS' | 'TEMPLATES' | 'OTHER'
  file_path: string
  file_name: string
  file_size: number
  file_type: string
  status: 'ACTIVE' | 'INACTIVE'
  user_id: number
  created_at: string
  updated_at: string
  uploadedBy?: { id: number; name: string }
}

export interface Service {
  id: number
  name: string
  description: string | null
  category_id: number | null
  base_price: number
  duration: number
  status: 'ACTIVE' | 'INACTIVE'
  icon: string | null
  sort_order: number
  category?: ServiceCategory | null
}

export interface ServiceCategory {
  id: number
  name: string
  description: string | null
  icon: string | null
  status: 'ACTIVE' | 'INACTIVE'
  sort_order: number
}

export interface SupportTicket {
  id: number
  franchise_id: number
  user_id: number
  assigned_to: number | null
  title: string
  description: string | null
  priority: 'LOW' | 'MEDIUM' | 'HIGH' | 'URGENT'
  status: 'OPEN' | 'IN_PROGRESS' | 'WAITING' | 'RESOLVED' | 'CLOSED'
  category: string | null
  resolved_at: string | null
  created_at: string
  updated_at: string
  franchise?: { id: number; name: string; code?: string }
  assignedTo?: { id: number; name: string } | null
  replies?: SupportTicketReply[]
}

export interface SupportTicketReply {
  id: number
  ticket_id: number
  user_id: number
  user_type: string
  message: string
  attachments?: string[] | null
  created_at: string
}

export interface SupportTicketStats {
  total: number
  open: number
  in_progress: number
  waiting: number
  resolved: number
  by_priority: { urgent: number; high: number; medium: number; low: number }
}

export interface SystemSetting {
  key: string
  value: unknown
  group: string
  type: string
  description: string | null
}
