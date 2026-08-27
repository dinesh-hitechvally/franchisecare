import { useState } from 'react'
import { NavLink, useLocation, useNavigate } from 'react-router-dom'
import { useAuthStore } from '../../store/authStore'
import { authApi } from '../../api/services'
import {
  LayoutDashboard,
  Flame,
  Users,
  BookOpen,
  PieChart,
  Flag,
  BookMarked,
  MessageSquare,
  Grid3X3,
  Boxes,
  Puzzle,
  FolderOpen,
  Calculator,
  Phone,
  Mail,
  Truck,
  Inbox,
  DollarSign,
  CreditCard,
  FileText,
  Video,
  Newspaper,
  BarChart3,
  ChevronDown,
  ChevronUp,
  ArrowRight,
  LifeBuoy,
} from 'lucide-react'

interface SubMenuItem {
  name: string
  href: string
}

interface MenuItem {
  name: string
  icon: React.ReactNode
  href?: string
  children?: SubMenuItem[]
}

const menuItems: MenuItem[] = [
  {
    name: 'Dashboard',
    icon: <LayoutDashboard size={20} />,
    href: '/dashboard',
  },
  {
    name: 'Franchises',
    icon: <Flag size={20} />,
    children: [
      { name: 'List Franchises', href: '/franchises/list' },
      { name: 'Add Franchise', href: '/franchises/add' },
    ],
  },
  {
    name: 'Admin Users',
    icon: <Users size={20} />,
    children: [
      { name: 'List Admin Users', href: '/admin-users/list' },
      { name: 'Add Admin User', href: '/admin-users/add' },
    ],
  },
  {
    name: 'Versions',
    icon: <Flame size={20} />,
    children: [
      { name: 'List Versions', href: '/versions/list' },
      { name: 'Add Versions', href: '/versions/add' },
    ],
  },
  {
    name: 'Members',
    icon: <Users size={20} />,
    children: [
      { name: 'List Members', href: '/members/list' },
      { name: 'Add Members', href: '/members/add' },
      { name: 'Manage Admins', href: '/members/admins' },
      { name: 'Suspended Lead Members', href: '/members/suspended' },
    ],
  },
  {
    name: 'Bookings',
    icon: <BookOpen size={20} />,
    children: [
      { name: 'Active Bookings', href: '/bookings/active' },
      { name: 'Completed Bookings', href: '/bookings/completed' },
      { name: 'Cancelled Bookings', href: '/bookings/cancelled' },
    ],
  },
  {
    name: 'Customers',
    icon: <PieChart size={20} />,
    children: [
      { name: 'List Customers', href: '/customers/list' },
    ],
  },
  {
    name: 'Suburb',
    icon: <Flag size={20} />,
    children: [
      { name: 'List Suburb', href: '/suburb/list' },
      { name: 'Add Suburb', href: '/suburb/add' },
    ],
  },
  {
    name: 'Services',
    icon: <BookMarked size={20} />,
    children: [
      { name: 'List Services', href: '/services/list' },
      { name: 'Add Services', href: '/services/add' },
      { name: 'List Groups', href: '/services/list-groups' },
      { name: 'Add Groups', href: '/services/add-groups' },
    ],
  },
  {
    name: 'Forum',
    icon: <MessageSquare size={20} />,
    children: [
      { name: 'List Categories', href: '/forum/list-categories' },
      { name: 'Add Categories', href: '/forum/add-categories' },
      { name: 'List Posts', href: '/forum/list-posts' },
      { name: 'Add Posts', href: '/forum/add-posts' },
      { name: 'List Topics', href: '/forum/list-topics' },
      { name: 'Add Topics', href: '/forum/add-topics' },
      { name: 'List Groups', href: '/forum/list-groups' },
      { name: 'Add Groups', href: '/forum/add-groups' },
    ],
  },
  {
    name: 'New Forum',
    icon: <Grid3X3 size={20} />,
    children: [
      { name: 'List System Groups', href: '/new-forum/list-system-groups' },
      { name: 'List User Groups', href: '/new-forum/list-user-groups' },
      { name: 'Manage Social Posts', href: '/new-forum/manage-social-posts' },
      { name: 'Manage Social Users', href: '/new-forum/manage-social-users' },
      { name: 'Manage Social Comments', href: '/new-forum/manage-social-comments' },
      { name: 'Add Social Group', href: '/new-forum/add-social-group' },
    ],
  },
  {
    name: 'Inventory',
    icon: <Boxes size={20} />,
    children: [
      { name: 'List Item', href: '/inventory/list-item' },
      { name: 'Add Item', href: '/inventory/add-item' },
      { name: 'List Inventory Category', href: '/inventory/list-category' },
      { name: 'Add Inventory Category', href: '/inventory/add-category' },
    ],
  },
  {
    name: 'Uniform',
    icon: <Puzzle size={20} />,
    children: [
      { name: 'List Uniform', href: '/uniform/list' },
      { name: 'Add Uniform', href: '/uniform/add' },
      { name: 'List Product Attribute', href: '/uniform/list-product-attribute' },
      { name: 'Add Product Attribute', href: '/uniform/add-product-attribute' },
      { name: 'List Attribute Type', href: '/uniform/list-attribute-type' },
      { name: 'Add Attribute Type', href: '/uniform/add-attribute-type' },
    ],
  },
  {
    name: 'Flyers',
    icon: <FolderOpen size={20} />,
    children: [
      { name: 'List Flyers', href: '/flyers/list' },
      { name: 'Add Flyers', href: '/flyers/add' },
      { name: 'List Product Attribute', href: '/flyers/list-product-attribute' },
      { name: 'Add Product Attribute', href: '/flyers/add-product-attribute' },
      { name: 'List Attribute Type', href: '/flyers/list-attribute-type' },
      { name: 'Add Attribute Type', href: '/flyers/add-attribute-type' },
    ],
  },
  {
    name: 'Leads',
    icon: <Calculator size={20} />,
    children: [
      { name: 'Issue Leads', href: '/leads/issue' },
      { name: 'Active Leads', href: '/leads/active' },
      { name: 'Snoozed Leads', href: '/leads/snoozed' },
      { name: 'Completed Leads', href: '/leads/completed' },
      { name: 'Forwarded HO Leads', href: '/leads/forwarded' },
      { name: 'HO Completed Leads', href: '/leads/ho-completed' },
      { name: 'Leads Report', href: '/leads/report' },
    ],
  },
  {
    name: 'Lead Suspend Reason',
    icon: <FileText size={20} />,
    children: [
      { name: 'List Reasons', href: '/lead-suspend-reason/list' },
      { name: 'Add Reason', href: '/lead-suspend-reason/add' },
    ],
  },
  {
    name: 'Call Center Reasons',
    icon: <Phone size={20} />,
    children: [
      { name: 'List Reasons', href: '/call-center-reasons/list' },
      { name: 'Add Reason', href: '/call-center-reasons/add' },
    ],
  },
  {
    name: 'Email Template',
    icon: <Mail size={20} />,
    children: [
      { name: 'List Emails', href: '/email-template/list' },
      { name: 'Add Email', href: '/email-template/add' },
    ],
  },
  {
    name: 'Freight',
    icon: <Truck size={20} />,
    children: [
      { name: 'List Freights', href: '/freight/list' },
      { name: 'Add Freight', href: '/freight/add' },
      { name: 'List Shipping', href: '/freight/list-shipping' },
      { name: 'Add Shipping', href: '/freight/add-shipping' },
    ],
  },
  {
    name: 'Orders',
    icon: <Inbox size={20} />,
    children: [
      { name: 'List Recent Orders', href: '/orders/recent' },
      { name: 'List Confirmed Orders', href: '/orders/confirmed' },
      { name: 'List Shipped Orders', href: '/orders/shipped' },
      { name: 'List Unpaid Orders', href: '/orders/unpaid' },
      { name: 'List Past Orders', href: '/orders/past' },
      { name: 'List Undelivered Orders', href: '/orders/undelivered' },
      { name: 'List Cancelled Orders', href: '/orders/cancelled' },
      { name: 'List Archived Orders', href: '/orders/archived' },
      { name: 'List Shipping Company', href: '/orders/list-shipping-company' },
      { name: 'Add Shipping Company', href: '/orders/add-shipping-company' },
    ],
  },
  {
    name: 'Financial',
    icon: <DollarSign size={20} />,
    children: [
      { name: 'List Expense Category', href: '/financial/list-expense-category' },
      { name: 'Add Expense Category', href: '/financial/add-expense-category' },
      { name: 'List Expense Super Category', href: '/financial/list-expense-super-category' },
      { name: 'Add Expense Super Category', href: '/financial/add-expense-super-category' },
      { name: 'List Income Category', href: '/financial/list-income-category' },
      { name: 'Add Income Category', href: '/financial/add-income-category' },
    ],
  },
  {
    name: 'Payment Type',
    icon: <CreditCard size={20} />,
    children: [
      { name: 'List Payment Type', href: '/payment-type/list' },
      { name: 'Add Payment Type', href: '/payment-type/add' },
    ],
  },
  {
    name: 'Documents',
    icon: <FileText size={20} />,
    children: [
      { name: 'List Documents', href: '/documents/list' },
      { name: 'Add Document', href: '/documents/add' },
      { name: 'List Document Type', href: '/documents/list-type' },
      { name: 'Add Document Type', href: '/documents/add-type' },
    ],
  },
  {
    name: 'Training',
    icon: <Video size={20} />,
    children: [
      { name: 'List Material', href: '/training/list-material' },
      { name: 'Add Material', href: '/training/add-material' },
      { name: 'List Category', href: '/training/list-category' },
      { name: 'Add Category', href: '/training/add-category' },
    ],
  },
  {
    name: 'News',
    icon: <Newspaper size={20} />,
    children: [
      { name: 'List News', href: '/news/list' },
      { name: 'Add News', href: '/news/add' },
      { name: 'List News Category', href: '/news/list-category' },
      { name: 'Add News Category', href: '/news/add-category' },
    ],
  },
  {
    name: 'Support Tickets',
    icon: <LifeBuoy size={20} />,
    children: [
      { name: 'List Tickets', href: '/support-tickets/list' },
    ],
  },
  {
    name: 'Reports',
    icon: <BarChart3 size={20} />,
    children: [
      { name: 'Dashboard', href: '/reports/dashboard' },
      { name: 'Income Report', href: '/reports/income' },
      { name: 'Service Group Report', href: '/reports/service-group' },
      { name: 'Login Log', href: '/reports/login-log' },
      { name: 'Ipad Access Log', href: '/reports/ipad-log' },
      { name: 'Service Report', href: '/reports/service' },
      { name: 'Franchisee Overview', href: '/reports/franchisee-overview' },
      { name: 'Income Overview', href: '/reports/income-overview' },
      { name: 'Shampoo Overview', href: '/reports/shampoo-overview' },
      { name: 'Income by Franchisee per Month', href: '/reports/income-franchisee-month' },
      { name: 'Income by State per Month', href: '/reports/income-state-month' },
      { name: 'Inventory Report', href: '/reports/inventory' },
      { name: 'Member Credit', href: '/reports/member-credit' },
      { name: 'SMS Purchase History', href: '/reports/sms-purchase' },
      { name: 'Credit Usage', href: '/reports/credit-usage' },
      { name: 'Benchmarking Figures', href: '/reports/benchmarking' },
    ],
  },
  {
    name: 'Settings',
    icon: <FileText size={20} />,
    href: '/settings',
  },
]

export function Sidebar() {
  const location = useLocation()
  const navigate = useNavigate()
  const user = useAuthStore((state) => state.user)
  const logout = useAuthStore((state) => state.logout)
  const [expandedItems, setExpandedItems] = useState<string[]>(['Versions'])
  const [profileMenuOpen, setProfileMenuOpen] = useState(false)

  const handleLogout = () => {
    authApi.logout().catch(() => {})
    logout()
    navigate('/signin')
  }

  const toggleExpanded = (name: string) => {
    setExpandedItems((prev) =>
      prev.includes(name) ? prev.filter((n) => n !== name) : [...prev, name]
    )
  }

  const isActive = (href?: string) => {
    if (!href) return false
    return location.pathname === href || location.pathname.startsWith(href + '/')
  }

  const isChildActive = (children?: { href: string }[]) => {
    if (!children) return false
    return children.some((child) => location.pathname === child.href || location.pathname.startsWith(child.href + '/'))
  }

  return (
    <aside className="sidebar">
      {/* Profile Section */}
      <div style={{ position: 'relative' }}>
        <div
          className="sidebar-profile"
          style={{ cursor: 'pointer' }}
          onClick={() => setProfileMenuOpen((prev) => !prev)}
        >
          <div className="sidebar-avatar">{(user?.name || '?').charAt(0).toUpperCase()}</div>
          <div className="flex-1">
            <div className="font-semibold text-gray-800 text-sm">{user?.name || 'Super Admin'}</div>
            <div className="flex items-center gap-1 text-xs text-gray-500">
              {user?.email || ''}
              <ChevronDown size={14} />
            </div>
          </div>
        </div>
        {profileMenuOpen && (
          <div
            className="absolute top-full left-4 right-4 mt-1 bg-white border border-gray-200 rounded shadow-md overflow-hidden z-10"
          >
            <div
              onClick={handleLogout}
              className="px-4 py-2.5 text-sm text-red-600 cursor-pointer hover:bg-gray-50"
            >
              Logout
            </div>
          </div>
        )}
      </div>

      {/* Dashboard Label */}
      <div className="sidebar-label">Dashboard</div>

      {/* Menu Items */}
      <nav className="pb-4">
        {menuItems.map((item) => (
          <div key={item.name}>
            {item.name === 'Settings' && (
              <div className="border-t border-gray-200 my-2" />
            )}
            {item.href ? (
              <NavLink
                to={item.href}
                className={`sidebar-item ${isActive(item.href) ? 'active' : ''}`}
              >
                <span className="sidebar-icon">{item.icon}</span>
                <span className="flex-1">{item.name}</span>
              </NavLink>
            ) : (
              <>
                <div
                  className={`sidebar-item ${isChildActive(item.children) ? 'text-purple-600' : ''}`}
                  onClick={() => toggleExpanded(item.name)}
                >
                  <span className="sidebar-icon">{item.icon}</span>
                  <span className="flex-1">{item.name}</span>
                  {expandedItems.includes(item.name) ? (
                    <ChevronUp size={16} className="text-gray-400" />
                  ) : (
                    <ChevronDown size={16} className="text-gray-400" />
                  )}
                </div>
                {expandedItems.includes(item.name) && item.children && (
                  <div className="sidebar-submenu">
                    {item.children.map((child) => (
                      <NavLink
                        key={child.href}
                        to={child.href}
                        className={`sidebar-item ${
                          location.pathname === child.href ? 'active' : ''
                        }`}
                      >
                        <ArrowRight size={14} className="text-gray-400" />
                        <span>{child.name}</span>
                      </NavLink>
                    ))}
                  </div>
                )}
              </>
            )}
          </div>
        ))}
      </nav>
    </aside>
  )
}
