import { Link, useNavigate } from 'react-router-dom'
import { CalendarDays, BookOpen, List, UserPlus, Bell, User } from 'lucide-react'
import { useAuthStore } from '../../store/authStore'

export function Header() {
  const user = useAuthStore((state) => state.user)
  const navigate = useNavigate()

  return (
    <header className="bg-header-bg px-4 py-2.5 shadow-md">
      <div className="flex items-center justify-between">
        {/* Logo & Version */}
        <div className="flex items-center gap-3">
          <div className="w-9 h-9 bg-white rounded-full flex items-center justify-center shadow-sm">
            <span className="text-[10px] font-extrabold text-red-800 leading-none">FC</span>
          </div>
          <span className="text-xs text-red-200 font-medium">Ver: 2.7.3002</span>
        </div>

        {/* Quick Navigation */}
        <div className="flex items-center gap-1">
          <Link
            to="/calendar/month"
            className="flex items-center gap-1.5 px-3 py-1.5 text-white/90 hover:text-white hover:bg-white/10 rounded-md transition-colors text-sm"
          >
            <CalendarDays className="w-4 h-4" />
            <span className="hidden sm:inline">Calendar</span>
          </Link>
          <Link
            to="/bookings/active"
            className="flex items-center gap-1.5 px-3 py-1.5 text-white/90 hover:text-white hover:bg-white/10 rounded-md transition-colors text-sm"
          >
            <CalendarDays className="w-4 h-4" />
            <span className="hidden sm:inline">Bookings</span>
          </Link>
          <Link
            to="/customers/list"
            className="flex items-center gap-1.5 px-3 py-1.5 text-white/90 hover:text-white hover:bg-white/10 rounded-md transition-colors text-sm"
          >
            <List className="w-4 h-4" />
            <span className="hidden sm:inline">List Customers</span>
          </Link>
          <Link
            to="/customers/add"
            className="flex items-center gap-1.5 px-3 py-1.5 text-white/90 hover:text-white hover:bg-white/10 rounded-md transition-colors text-sm"
          >
            <UserPlus className="w-4 h-4" />
            <span className="hidden sm:inline">Add Customer</span>
          </Link>
          <Link
            to="/reports/daily-diary"
            className="flex items-center gap-1.5 px-3 py-1.5 text-white/90 hover:text-white hover:bg-white/10 rounded-md transition-colors text-sm"
          >
            <BookOpen className="w-4 h-4" />
            <span className="hidden sm:inline">Daily Diary</span>
          </Link>

          {/* Notification Bell */}
          <button className="relative p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-md transition-colors ml-2">
            <Bell className="w-5 h-5" />
            <span className="absolute top-1 right-1 w-2 h-2 bg-yellow-400 rounded-full"></span>
          </button>

          {/* User Avatar */}
          <button
            onClick={() => navigate('/forum/profile')}
            className="ml-1 p-1 rounded-full hover:bg-white/10 transition-colors"
            title="View your profile"
          >
            <img
              src={user?.avatar || 'https://ui-avatars.com/api/?name=Admin&background=6b7280&color=fff'}
              alt={user?.name || 'Admin'}
              className="w-8 h-8 rounded-full object-cover border-2 border-white/20 cursor-pointer"
            />
          </button>
        </div>
      </div>
    </header>
  )
}
