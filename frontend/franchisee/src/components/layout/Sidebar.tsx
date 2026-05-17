import { useState, useEffect, useRef } from 'react'
import { NavLink, useNavigate, useLocation } from 'react-router-dom'
import { ChevronRight, Settings, User, Key, LogOut } from 'lucide-react'

import { cn } from '../../lib/utils'
import { useAuthStore } from '../../store/authStore'
import { useToastStore } from '../../store/toastStore'
import { authApi } from '../../api/services'

interface NavItem {
  name: string
  href?: string
}

interface NavSection {
  name: string
  icon: React.ReactNode
  hasChildren: boolean
  href?: string
  children?: NavItem[]
}

// Flat top-level items (no submenu)
const topFlatItems: { name: string; href: string; icon: React.ReactNode }[] = [
  {
    name: 'Dashboard',
    href: '/dashboard',
    icon: <SvgIcon type="dashboard" />,
  },
  {
    name: 'Calendar',
    href: '/calendar',
    icon: <SvgIcon type="calendar" />,
  },

  {
    name: 'Forum',
    href: '/forum',
    icon: <SvgIcon type="forum" />,
  },
  {
    name: 'News',
    href: '/news',
    icon: <SvgIcon type="news" />,
  },
]

// Sections with submenus
const sections: NavSection[] = [
  {
    name: 'Bookings',
    icon: <SvgIcon type="bookings" />,
    hasChildren: true,
    children: [
      { name: 'New Bookings', href: '/bookings/new' },
      { name: 'Active Bookings', href: '/bookings/active' },
      { name: 'Completed Bookings', href: '/bookings/completed' },
      { name: 'Recurring Bookings', href: '/bookings/recurring' },
      { name: 'Cancelled Recurring', href: '/bookings/cancelled-recurring' },
      { name: 'Cancelled Bookings', href: '/bookings/cancelled' },
      { name: 'Archived Bookings', href: '/bookings/archived' },
    ],
  },
  {
    name: 'Leads',
    icon: <SvgIcon type="leads" />,
    hasChildren: true,
    children: [
      { name: 'New Leads', href: '/leads/new' },
      { name: 'Completed Leads', href: '/leads/completed' },
      { name: 'Snoozed Leads', href: '/leads/snooze' },
      { name: 'Cancellation Request', href: '/leads/cancellation-request' },
      { name: 'Message For Operator', href: '/leads/message-operator' },
    ],
  },
  {
    name: 'Block Outs',
    icon: <SvgIcon type="blockouts" />,
    hasChildren: true,
    children: [
      { name: 'New Blockouts', href: '/blockouts/new' },
      { name: 'List Blockouts', href: '/blockouts' },
      { name: 'Recurring Blockouts', href: '/blockouts/recurring' },
    ],
  },
  {
    name: 'Customers',
    icon: <SvgIcon type="customers" />,
    hasChildren: true,
    children: [
      { name: 'Add Customers', href: '/customers/add' },
      { name: 'List Customers', href: '/customers/list' },
      { name: 'Inactive Customers', href: '/customers/inactive' },
      { name: 'Archived Customers', href: '/customers/archived' },
    ],
  },
  {
    name: 'Communication',
    icon: <SvgIcon type="communication" />,
    hasChildren: true,
    children: [
      { name: 'Buy Credits', href: '/communication/buy-credits' },
      { name: 'Send SMS', href: '/communication/sms/send' },
      { name: 'Send Email', href: '/communication/email/send' },
      { name: 'SMS History', href: '/communication/sms/history' },
      { name: 'Email History', href: '/communication/email/history' },
      { name: 'Message Template', href: '/communication/templates' },
    ],
  },
  {
    name: 'Inventory',
    icon: <SvgIcon type="inventory" />,
    hasChildren: true,
    children: [
      { name: 'Office Order', href: '/inventory/office-order' },
      { name: 'Shampoo Order', href: '/inventory/shampoo-order' },
      { name: 'Place Treat Order', href: '/inventory/order-treats' },
      { name: 'Place Uniform Orders', href: '/inventory/order-uniforms' },
      { name: 'Place Business Card, Magnet, Flyers', href: '/inventory/marketing-materials' },
      { name: 'Stock Take', href: '/inventory/stock-take' },
      { name: 'Inward Goods', href: '/inventory/inward' },
      { name: 'Completed Orders', href: '/orders/completed' },
    ],
  },
  {
    name: 'Income',
    icon: <SvgIcon type="income" />,
    hasChildren: true,
    children: [
      { name: 'Add Income', href: '/finance/income/add' },
      { name: 'Add Income Category', href: '/finance/income/add-category' },
      { name: 'Income Categories', href: '/finance/income/categories' },
      { name: 'List Income', href: '/finance/income' },
      { name: 'Recurring Income', href: '/finance/income/recurring' },
    ],
  },
  {
    name: 'Expense',
    icon: <SvgIcon type="expense" />,
    hasChildren: true,
    children: [
      { name: 'Add Expense', href: '/finance/expense/add' },
      { name: 'Expense Categories', href: '/finance/expense/categories' },
      { name: 'List Expenses', href: '/finance/expense' },
      { name: 'Recurring Expenses', href: '/finance/expense/recurring' },
    ],
  },
  {
    name: 'Documents',
    icon: <SvgIcon type="documents" />,
    hasChildren: true,
    children: [
      { name: 'System Manuals', href: '/documents/manuals' },
      { name: 'Templates', href: '/documents/templates' },
      { name: 'Other Files', href: '/documents/other' },
    ],
  },
  {
    name: 'Reports',
    icon: <SvgIcon type="reports" />,
    hasChildren: true,
    children: [
      { name: 'Daily Diary', href: '/reports/daily-diary' },
      { name: 'Benchmarking Figures', href: '/reports/benchmarking' },
      { name: 'Booking Reports', href: '/reports/bookings' },
      { name: 'Service Reports', href: '/reports/services' },
      { name: 'Suburb Reports', href: '/reports/suburbs' },
      { name: 'Customer Reports', href: '/reports/customers' },
      { name: 'Income Reports', href: '/reports/income' },
      { name: 'Income Forecast', href: '/reports/income-forecast' },
      { name: 'Expense Reports', href: '/reports/expenses' },
      { name: 'Profit/Loss', href: '/reports/profit-loss' },
      { name: 'Detail Profit/Loss', href: '/reports/detail-profit-loss' },
      { name: 'Gst Report Summary', href: '/reports/gst-summary' },
      { name: 'Gst Report Detail', href: '/reports/gst-detail' },
      { name: 'Tracing Report', href: '/reports/tracking' },
    ],
  },
  {
    name: 'Training',
    icon: <SvgIcon type="training" />,
    hasChildren: true,
    children: [
      { name: 'Elearning', href: '/training/e-learning' },
      { name: 'Mate Training Videos', href: '/training/videos' },
      { name: 'Marketing', href: '/training/marketing' },
    ],
  },
  {
    name: 'Orders',
    icon: <SvgIcon type="orders" />,
    hasChildren: true,
    children: [
      { name: 'SMS Purchase Log', href: '/orders/sms-purchase-log' },
    ],
  },
  {
    name: 'Setup',
    icon: <SvgIcon type="setup" />,
    hasChildren: true,
    children: [
      { name: 'Manage Bookings', href: '/settings/manage-bookings' },
      { name: 'Settings', href: '/settings' },
      { name: 'Inventory Usage', href: '/inventory/usage' },
      { name: 'Service Prices', href: '/settings/service-prices' },
    ],
  },
  {
    name: 'Integration',
    icon: <SvgIcon type="integration" />,
    hasChildren: true,
    children: [
      { name: 'API Settings', href: '/integration/api' },
    ],
  },
]

// SVG icon component
function SvgIcon({ type }: { type: string }) {
  const cls = "sidebar-icon"
  switch (type) {
    case 'dashboard':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" /></svg>
    case 'calendar':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" /></svg>
    case 'leads':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><line x1="19" y1="8" x2="19" y2="14" /><line x1="22" y1="11" x2="16" y2="11" /></svg>
    case 'forum':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" /></svg>
    case 'news':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9" /><line x1="10" y1="6" x2="18" y2="6" /><line x1="10" y1="10" x2="18" y2="10" /><line x1="10" y1="14" x2="14" y2="14" /></svg>
    case 'bookings':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" /><path d="M8 14h.01" /><path d="M12 14h.01" /><path d="M16 14h.01" /><path d="M8 18h.01" /><path d="M12 18h.01" /></svg>
    case 'blockouts':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" /><line x1="9" y1="9" x2="15" y2="15" /><line x1="15" y1="9" x2="9" y2="15" /></svg>
    case 'customers':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></svg>
    case 'communication':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" /><polyline points="22,6 12,13 2,6" /></svg>
    case 'inventory':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" /><polyline points="3.27 6.96 12 12.01 20.73 6.96" /><line x1="12" y1="22.08" x2="12" y2="12" /></svg>
    case 'income':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><line x1="12" y1="1" x2="12" y2="23" /><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" /></svg>
    case 'expense':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" /><line x1="1" y1="10" x2="23" y2="10" /></svg>
    case 'documents':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="16" y1="13" x2="8" y2="13" /><line x1="16" y1="17" x2="8" y2="17" /></svg>
    case 'reports':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="20" x2="18" y2="10" /><line x1="12" y1="20" x2="12" y2="4" /><line x1="6" y1="20" x2="6" y2="14" /></svg>
    case 'training':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" /><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" /></svg>
    case 'orders':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" /><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" /></svg>
    case 'setup':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" /></svg>
    case 'integration':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="16 18 22 12 16 6" /><polyline points="8 6 2 12 8 18" /></svg>
    case 'version':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="23 4 23 10 17 10" /><polyline points="1 20 1 14 7 14" /><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" /></svg>
    case 'manual':
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" /><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" /></svg>
    default:
      return <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10" /></svg>
  }
}

export function Sidebar() {
  const navigate = useNavigate()
  const location = useLocation()
  const { user, logout } = useAuthStore()
  const addToast = useToastStore((state) => state.addToast)
  const [expandedItems, setExpandedItems] = useState<string[]>([])
  const [isProfileOpen, setIsProfileOpen] = useState(false)
  const profileRef = useRef<HTMLDivElement>(null)

  const toggleExpanded = (name: string) => {
    setExpandedItems(prev =>
      prev.includes(name) ? prev.filter(n => n !== name) : [...prev, name]
    )
  }

  const toggleProfile = () => setIsProfileOpen(!isProfileOpen)

  const handleLogout = async () => {
    try {
      await authApi.logout()
      addToast('Successfully logged out. See you soon!', 'success')
    } catch (error) {
      console.error('Logout failed', error)
      addToast('Logged out locally.', 'info')
    } finally {
      logout()
      navigate('/signin')
    }
  }

  // Click outside to close profile dropdown
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (profileRef.current && !profileRef.current.contains(event.target as Node)) {
        setIsProfileOpen(false)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const isActive = (href?: string) => {
    if (!href) return false
    if (href === '/dashboard') return location.pathname === '/dashboard' || location.pathname === '/'
    return location.pathname === href || location.pathname.startsWith(href + '/')
  }

  // Auto-expand parent if child is active
  useEffect(() => {
    sections.forEach(section => {
      if (section.children) {
        const childActive = section.children.some(child => isActive(child.href))
        if (childActive && !expandedItems.includes(section.name)) {
          setExpandedItems(prev => [...prev, section.name])
        }
      }
    })
  }, [location.pathname])

  return (
    <aside className="sb">
      {/* User info area */}
      <div className="sb-user" ref={profileRef} onClick={toggleProfile}>
        <div className="sb-avatar">
          <img 
            src={user?.avatar || "https://ui-avatars.com/api/?name=Mate+Support&background=6b7280&color=fff"} 
            alt={user?.name || "User avatar"} 
          />
        </div>
        <div className="sb-user-info">
          <span className="sb-user-name">
            {user?.name || 'Mate Support'} 
            <span className="sb-dropdown-arrow">▾</span>
          </span>
          <span className="sb-sms-credit">SMS Credit: $1.14 AU</span>
        </div>

        {/* Profile Dropdown Menu */}
        {isProfileOpen && (
          <div className="sb-profile-menu">
            <div className="sb-profile-item" onClick={() => navigate('/settings')}>
              <Settings />
              <span>Setting</span>
            </div>
            <div className="sb-profile-item" onClick={() => navigate('/profile')}>
              <User />
              <span>My Profile</span>
            </div>
            <div className="sb-profile-item" onClick={() => navigate('/settings/change-password')}>
              <Key />
              <span>Change Password</span>
            </div>
            <div className="sb-profile-item" onClick={handleLogout}>
              <LogOut />
              <span>Logout</span>
            </div>
          </div>
        )}
      </div>

      {/* MAIN label */}
      <div className="sb-label">MAIN</div>

      {/* Navigation */}
      <nav className="sb-nav">
        {/* Top flat items */}
        {topFlatItems.map(item => (
          <NavLink
            key={item.name}
            to={item.href}
            className={({ isActive }) =>
              cn('sb-item', isActive && 'active')
            }
          >
            {item.icon}
            <span>{item.name}</span>
          </NavLink>
        ))}

        {/* Collapsible sections */}
        {sections.map(section => {
          const isExpanded = expandedItems.includes(section.name)
          const childActive = section.children?.some(child => isActive(child.href))

          return (
            <div key={section.name}>
              <button
                onClick={() => toggleExpanded(section.name)}
                className={cn('sb-item sb-section-btn', childActive && 'active')}
              >
                {section.icon}
                <span className="sb-section-name">{section.name}</span>
                <ChevronRight className={cn('sb-chevron', isExpanded && 'expanded')} />
              </button>

              <div className={cn('sb-submenu', isExpanded && 'open')}>
                {section.children?.map(child => (
                  <NavLink
                    key={child.name}
                    to={child.href!}
                    className={({ isActive }) =>
                      cn('sb-sub-item', isActive && 'active')
                    }
                  >
                    {child.name}
                  </NavLink>
                ))}
              </div>
            </div>
          )
        })}

        {/* Bottom items */}
        <div className="sb-bottom-section">
          <NavLink to="/version-update" className={({ isActive: active }) => cn('sb-item', active && 'active')}>
            <SvgIcon type="version" />
            <span>Version Update</span>
          </NavLink>
        </div>
      </nav>

      {/* Download App button */}
      <div className="sb-download-wrapper">
        <button onClick={() => navigate('/download-app')} className="sb-download-btn">
          {/* Apple Icon */}
          <svg className="sb-dl-apple" viewBox="0 0 24 24" fill="white" width="16" height="16">
            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
          </svg>
          <span className="sb-dl-slash">/</span>
          {/* Android Icon */}
          <svg className="sb-dl-android" viewBox="0 0 24 24" fill="#a4c639" width="16" height="16">
            <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993.0001.5511-.4482.9997-.9993.9997m-11.046 0c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993 0 .5511-.4482.9997-.9993.9997m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0225 3.503c-1.4655-.6697-3.1088-1.0508-4.8462-1.0508-1.737 0-3.381.3811-4.8462 1.0508L5.215 5.4465a.4161.4161 0 00-.5676-.1521.416.416 0 00-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589.3432 18.6617h23.3136c0-4.0028-2.3457-7.475-5.795-9.3403"/>
          </svg>
          <span className="sb-dl-text">Download App</span>
        </button>
      </div>

      {/* Operations Manual - below download */}
      <div className="sb-after-download">
        <NavLink to="/operations-manual" className={({ isActive: active }) => cn('sb-item', active && 'active')}>
          <SvgIcon type="manual" />
          <span>Operations Manual</span>
        </NavLink>
      </div>
    </aside>
  )
}
