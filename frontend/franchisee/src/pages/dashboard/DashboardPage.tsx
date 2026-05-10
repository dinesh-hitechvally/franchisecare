import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { dashboardApi } from '../../api/services'
import { format } from 'date-fns'
import {
  Users, CalendarDays, XCircle, MessageSquare,
  Ban, Bell, Download, CreditCard,
  ChevronRight, Printer, X, Cake
} from 'lucide-react'

export function DashboardPage() {
  const [showForecastTable, setShowForecastTable] = useState(false)
  const navigate = useNavigate()

  const { data: metrics } = useQuery({
    queryKey: ['dashboard-metrics'],
    queryFn: () => dashboardApi.getMetrics(),
  })

  const { data: dashboardNews } = useQuery({
    queryKey: ['dashboard-news'],
    queryFn: () => dashboardApi.getRecentNews(),
  })

  const { data: activities } = useQuery({
    queryKey: ['dashboard-activities'],
    queryFn: () => dashboardApi.getActivities(10),
  })

  const { data: bookingSchedule } = useQuery({
    queryKey: ['dashboard-booking-schedule'],
    queryFn: () => dashboardApi.getBookingSchedule(5),
  })

  const { data: forecastData } = useQuery({
    queryKey: ['dashboard-forecast'],
    queryFn: () => dashboardApi.getForecast(12),
  })

  const newsItems = dashboardNews?.news ?? []
  const bluesNewsItems = dashboardNews?.bluesNews ?? []
  const recentActivities = activities ?? []
  const scheduleRows = bookingSchedule ?? []
  const forecastRows = forecastData ?? []

  const maxIncome = Math.max(...forecastRows.map((r) => r.income), 1)
  const revenueBars = (forecastRows.length > 0 ? forecastRows : [{ income: 0 }]).slice(0, 11)

  const formatDateTime = (input?: string) => {
    if (!input) {
      return '-'
    }

    const date = new Date(input)
    return Number.isNaN(date.getTime()) ? '-' : format(date, 'EEEE, do MMM yyyy')
  }

  const formatMoney = (value: number) =>
    new Intl.NumberFormat('en-AU', {
      style: 'currency',
      currency: 'AUD',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(value || 0)

  return (
    <div className="flex flex-col xl:flex-row gap-5">
      {/* ========== LEFT COLUMN ========== */}
      <div className="w-full xl:w-[370px] flex-shrink-0 space-y-5">
        {/* News Card */}
        <div className="card overflow-hidden">
          <div className="px-4 py-3 border-b border-gray-100">
            <h3 className="text-base font-semibold text-gray-800">News</h3>
          </div>
          <div className="divide-y divide-gray-100">
            {newsItems.map((item) => (
              <div key={item.id} className="px-4 py-3 flex items-start gap-3 hover:bg-gray-50 transition-colors cursor-pointer group">
                <div className="w-10 h-10 bg-gray-200 rounded-full flex-shrink-0 flex items-center justify-center mt-0.5">
                  <img src={`https://ui-avatars.com/api/?name=${encodeURIComponent(item.title.charAt(0))}&background=ddd&color=888&size=40`} alt="" className="w-10 h-10 rounded-full" />
                </div>
                <div className="flex-1 min-w-0">
                  <h4 className="text-sm font-medium text-red-600 group-hover:text-red-700 truncate">{item.title}</h4>
                  <p className="text-xs text-gray-500 mt-0.5 line-clamp-2">{item.content}</p>
                  <p className="text-[11px] text-gray-400 mt-1">
                    {formatDateTime(item.published_at || item.publishedAt || item.created_at || item.createdAt)}
                  </p>
                </div>
                <ChevronRight className="w-4 h-4 text-gray-300 mt-1 flex-shrink-0" />
              </div>
            ))}
            {newsItems.length === 0 && (
              <div className="px-4 py-5 text-sm text-gray-500">No news available.</div>
            )}
          </div>
        </div>

        {/* Blues News Card */}
        <div className="card overflow-hidden">
          <div className="px-4 py-3 border-b border-gray-100">
            <h3 className="text-base font-semibold text-gray-800">Blues News</h3>
          </div>
          <div className="divide-y divide-gray-100">
            {bluesNewsItems.map((item) => (
              <div key={item.id} className="px-4 py-3 flex items-start gap-3 hover:bg-gray-50 transition-colors cursor-pointer group">
                <div className="w-10 h-10 bg-gray-200 rounded-full flex-shrink-0 flex items-center justify-center mt-0.5">
                  <img src={`https://ui-avatars.com/api/?name=${encodeURIComponent(item.title.charAt(0))}&background=ddd&color=888&size=40`} alt="" className="w-10 h-10 rounded-full" />
                </div>
                <div className="flex-1 min-w-0">
                  <h4 className="text-sm font-medium text-blue-600 group-hover:text-blue-700">{item.title}</h4>
                  <p className="text-xs text-gray-500 mt-0.5 line-clamp-2">{item.content}</p>
                  <p className="text-[11px] text-gray-400 mt-1">
                    {formatDateTime(item.published_at || item.publishedAt || item.created_at || item.createdAt)}
                  </p>
                </div>
                <ChevronRight className="w-4 h-4 text-gray-300 mt-1 flex-shrink-0" />
              </div>
            ))}
            {bluesNewsItems.length === 0 && (
              <div className="px-4 py-5 text-sm text-gray-500">No blues news available.</div>
            )}
          </div>
        </div>

        {/* Recent Activities */}
        <div className="card overflow-hidden">
          <div className="px-4 py-3 border-b border-gray-100">
            <h3 className="text-base font-semibold text-gray-800">Recent Activities</h3>
          </div>
          <div className="divide-y divide-gray-100">
            {recentActivities.map((item) => (
              <div key={item.id} className="px-4 py-3 flex items-start gap-3">
                <div className="w-10 h-10 bg-gray-200 rounded-full flex-shrink-0 flex items-center justify-center mt-0.5">
                  <img src="https://ui-avatars.com/api/?name=U&background=ddd&color=888&size=40" alt="" className="w-10 h-10 rounded-full" />
                </div>
                <p className="text-xs text-gray-600 leading-relaxed">{item.message}</p>
              </div>
            ))}
            {recentActivities.length === 0 && (
              <div className="px-4 py-5 text-sm text-gray-500">No recent activities.</div>
            )}
          </div>
          <div className="px-4 py-3 border-t border-gray-100">
            <button className="text-sm text-blue-600 hover:text-blue-700 font-medium">Load More</button>
          </div>
        </div>
      </div>

      {/* ========== RIGHT CONTENT AREA ========== */}
      <div className="flex-1 space-y-5 min-w-0">
        {/* Top Strip: Attention, Daily Diary, 2FA */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {/* Attention Card */}
          <div
            className="card p-4 flex items-center gap-4 col-span-1 sm:col-span-1 lg:col-span-2 cursor-pointer hover:shadow-md transition-shadow"
            role="button"
            tabIndex={0}
            onClick={() => navigate('/bookings/manage')}
            onKeyDown={(event) => {
              if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault()
                navigate('/bookings/manage')
              }
            }}
          >
            <div>
              <p className="text-xs text-gray-400 uppercase font-semibold tracking-wider">ATTENTION</p>
              <p className="text-3xl font-bold text-gray-900 mt-1">{metrics?.attentionCount ?? 0}</p>
              <p className="text-xs text-gray-500 mt-1">Bookings that needs to be completed</p>
            </div>
            <div className="flex-1 flex items-end justify-end gap-0.5 h-14 opacity-40">
              {[35, 50, 40, 60, 45, 55, 48, 65, 50].map((h, i) => (
                <div key={i} className="w-2.5 bg-green-400 rounded-t" style={{ height: `${h}%` }} />
              ))}
            </div>
          </div>

          {/* Daily Diary */}
          <div className="card p-4 flex items-center gap-3 cursor-pointer hover:shadow-md transition-shadow">
            <div className="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
              <Printer className="w-5 h-5 text-gray-500" />
            </div>
            <div>
              <p className="text-sm font-semibold text-gray-800">Daily Diary</p>
              <p className="text-xs text-gray-400">Print Daily Diary</p>
            </div>
          </div>

          {/* 2FA Card */}
          <div className="bg-emerald-500 rounded-xl p-4 text-white cursor-pointer hover:bg-emerald-600 transition-colors shadow-sm">
            <p className="text-xl font-bold">2FA</p>
            <p className="text-sm opacity-90 mt-1">Click to enable/<br/>disable 2FA</p>
          </div>
        </div>

        {/* Stats Row 1: 4 colored cards */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <StatCard number={String(metrics?.activeCustomers ?? 0)} label="Active Customers" color="bg-blue-600" icon={<Users className="w-6 h-6" />} />
          <StatCard number={String(metrics?.activeBookings ?? 0)} label="Active Bookings (CY)" color="bg-red-500" icon={<CalendarDays className="w-6 h-6" />} />
          <StatCard number={String(metrics?.cancellations ?? 0)} label="Cancellations (CY)" color="bg-green-600" icon={<XCircle className="w-6 h-6" />} />
          <StatCard number={String(metrics?.forumNotifications ?? 0)} label="Forum Notification" color="bg-green-700" icon={<Bell className="w-6 h-6" />} />
        </div>

        {/* Stats Row 2: 4 colored cards */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <StatCard number={String(metrics?.birthdayThisMonth ?? 0)} label="Birthday This Month" color="bg-blue-700" icon={<Cake className="w-6 h-6" />} />
          <StatCard number={String(metrics?.leadsCount ?? 0)} label="Leads Count" color="bg-blue-800" icon={<Users className="w-6 h-6" />} />
          <StatCard number={String(metrics?.cancelRequests ?? 0)} label="Cancel Requests" color="bg-gray-600" icon={<Ban className="w-6 h-6" />} />
          <StatCard number={String(metrics?.operatorMessages ?? 0)} label="Operator Messages" color="bg-green-600" icon={<MessageSquare className="w-6 h-6" />} />
        </div>

        {/* Download App Card */}
        <div className="bg-gray-900 rounded-xl p-5 text-white flex items-center gap-4 cursor-pointer hover:bg-gray-800 transition-colors shadow-sm">
          <div className="flex items-center gap-2">
            <svg className="w-8 h-8 opacity-80" viewBox="0 0 24 24" fill="currentColor">
              <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
            </svg>
            <span className="text-lg opacity-40 font-light">/</span>
            <svg className="w-8 h-8 opacity-80" viewBox="0 0 24 24" fill="currentColor">
              <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993.0001.5511-.4482.9997-.9993.9997m-11.046 0c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993 0 .5511-.4482.9997-.9993.9997m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0225 3.503c-1.4655-.6697-3.1088-1.0508-4.8462-1.0508-1.737 0-3.381.3811-4.8462 1.0508L5.215 5.4465a.4161.4161 0 00-.5676-.1521.416.416 0 00-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589.3432 18.6617h23.3136c0-4.0028-2.3457-7.475-5.795-9.3403"/>
            </svg>
          </div>
          <div>
            <p className="font-bold text-base">Download App</p>
          </div>
        </div>

        {/* Booking Schedules */}
        <div className="card overflow-hidden">
          <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 className="text-base font-semibold text-gray-800">Booking Schedules</h3>
            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider">NEXT 5 DAYS BOOKINGS</span>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-gray-200 bg-gray-50">
                  <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Customer Name</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Booked Date</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Booked Time</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Total $</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {scheduleRows.map((booking) => (
                  <tr key={booking.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-4 py-3 text-sm text-gray-700">{booking.customer}</td>
                    <td className="px-4 py-3 text-sm text-gray-700">{formatDateTime(booking.date)}</td>
                    <td className="px-4 py-3 text-sm text-gray-700">{booking.startTime}</td>
                    <td className="px-4 py-3 text-sm text-gray-700">{formatMoney(booking.total)}</td>
                    <td className="px-4 py-3">
                      <button className="text-sm text-blue-600 hover:text-blue-700 font-medium">View</button>
                    </td>
                  </tr>
                ))}
                {scheduleRows.length === 0 && (
                  <tr>
                    <td colSpan={5} className="px-4 py-6 text-sm text-gray-500 text-center">No bookings in the next 5 days.</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* Revenue Section */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {/* Total Revenue */}
          <div className="card p-5 md:col-span-2">
            <p className="text-xs text-gray-400 uppercase font-semibold tracking-wider">TOTAL REVENUE</p>
            <p className="text-2xl font-bold text-gray-900 mt-1">{formatMoney(metrics?.totalRevenue ?? 0)}</p>
            <p className="text-xs text-gray-400 mt-0.5">Financial YTD revenue<br />(Inc GST)</p>
            <div className="flex items-end gap-1 h-16 mt-4">
              {revenueBars.map((row, i) => {
                const height = Math.max(8, Math.round(((row.income || 0) / maxIncome) * 100))

                return (
                <div
                  key={i}
                  className={`flex-1 rounded-t ${i % 2 === 0 ? 'bg-green-500' : 'bg-blue-600'}`}
                  style={{ height: `${height}%` }}
                />
                )
              })}
            </div>
          </div>

          {/* Revenue This Month */}
          <div className="card p-5">
            <div>
              <p className="text-sm text-gray-500">Revenue</p>
              <p className="text-sm text-gray-400">This Month</p>
            </div>
            <p className="text-2xl font-bold text-gray-900 mt-3">{formatMoney(metrics?.thisMonthRevenue ?? 0)}</p>
            <p className="text-xs text-gray-400 mt-0.5">Revenue<br />this month<br />(Inc GST)</p>
          </div>
        </div>

        {/* Twelve Weeks Income Forecast */}
        {!showForecastTable ? (
          <div className="card text-center py-10">
            <div className="w-14 h-14 bg-gray-100 rounded-xl mx-auto mb-4 flex items-center justify-center">
              <CreditCard className="w-7 h-7 text-gray-400" />
            </div>
            <h3 className="font-semibold text-gray-800 text-base">Twelve Weeks Income Forecast</h3>
            <p className="text-sm text-gray-400 mt-1">Click here to view twelve weeks Income Forecast</p>
            <button
              className="mt-4 px-6 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-medium text-sm transition-colors inline-flex items-center gap-2"
              onClick={() => setShowForecastTable(true)}
            >
              View Data
            </button>
          </div>
        ) : (
          <div className="card overflow-hidden">
            <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
              <h3 className="text-base font-semibold text-gray-800">Twelve Weeks Income Forecast</h3>
              <button
                onClick={() => setShowForecastTable(false)}
                className="p-1.5 hover:bg-gray-100 rounded-lg transition-colors"
              >
                <X className="w-4 h-4 text-gray-500" />
              </button>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-gray-200 bg-gray-50">
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Week</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600"># of bookings</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Income Forecast ($)</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600"># of services</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {forecastRows.map((row, i) => (
                    <tr key={row.id} className={`${i % 2 === 0 ? 'bg-white' : 'bg-gray-50'} hover:bg-blue-50 transition-colors`}>
                      <td className="px-4 py-3 text-sm text-gray-700">{row.week}</td>
                      <td className="px-4 py-3 text-sm text-gray-700">{row.bookings}</td>
                      <td className="px-4 py-3 text-sm text-gray-700">{formatMoney(row.income)}</td>
                      <td className="px-4 py-3 text-sm text-gray-700">{row.services}</td>
                    </tr>
                  ))}
                  {forecastRows.length === 0 && (
                    <tr>
                      <td colSpan={4} className="px-4 py-6 text-sm text-gray-500 text-center">No forecast data available.</td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* Retrieve Data */}
        <div className="bg-gray-900 rounded-xl p-8 text-white text-center shadow-sm">
          <div className="w-14 h-14 bg-gray-800 rounded-xl mx-auto mb-4 flex items-center justify-center">
            <Download className="w-7 h-7 text-white opacity-70" />
          </div>
          <h3 className="font-semibold text-lg">Retrieve Data</h3>
          <p className="text-sm text-gray-400 mt-2 max-w-md mx-auto">
            Retrieve Latest Data from Server. It may take few minutes to download all latest data.
          </p>
          <button className="mt-5 px-6 py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-medium text-sm transition-colors">
            ReSync Data
          </button>
        </div>
      </div>
    </div>
  )
}

// Stat Card Component
function StatCard({
  number,
  label,
  color,
  icon,
}: {
  number: string
  label: string
  color: string
  icon: React.ReactNode
}) {
  return (
    <div className={`${color} rounded-xl p-4 text-white cursor-pointer hover:opacity-90 transition-opacity shadow-sm`}>
      <div className="flex items-start justify-between">
        <div>
          <p className="text-2xl font-bold">{number}</p>
          <p className="text-sm opacity-90 mt-1 leading-tight">{label}</p>
        </div>
        <div className="opacity-50">{icon}</div>
      </div>
    </div>
  )
}
