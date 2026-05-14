import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { 
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
  LineChart, Line
} from 'recharts'
import { Download, Target, TrendingUp, Calendar } from 'lucide-react'
import { reportsApi } from '../../api/services'

export function TrackingReportPage() {
  const [year, setYear] = useState(new Date().getFullYear())

  const { data, isLoading } = useQuery({
    queryKey: ['tracking-report', year],
    queryFn: () => reportsApi.getTracking({ year }),
  })

  const monthlyData = data?.monthly_data ?? []
  const totals = data?.totals ?? { total_bookings: 0, completed_bookings: 0, cancelled_bookings: 0, total_revenue: 0 }

  const years = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i)

  return (
    <div className="flex flex-col gap-6 p-6 min-h-screen bg-[#f4f6f8]">
      {/* Page Title Card */}
      <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-100 flex items-center justify-between">
        <h1 className="text-xl font-bold text-gray-800 flex items-center gap-2">
          <Target size={24} className="text-red-600" />
          Tracking Report
        </h1>
        <button className="flex items-center gap-2 px-4 py-2 bg-secondary-100 hover:bg-secondary-200 text-secondary-700 rounded-md transition-colors text-sm font-medium">
          <Download size={18} />
          Export Report
        </button>
      </div>

      {/* Filter Card */}
      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <div className="flex items-center gap-4">
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2">
              <Calendar size={14} />
              Year
            </label>
            <select
              value={year}
              onChange={(e) => setYear(Number(e.target.value))}
              className="text-sm p-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
              {years.map((y) => (
                <option key={y} value={y}>{y}</option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <p className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Total Bookings</p>
          <p className="text-3xl font-bold text-gray-900">{totals.total_bookings}</p>
        </div>
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <p className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Completed</p>
          <p className="text-3xl font-bold text-green-600">{totals.completed_bookings}</p>
        </div>
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <p className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Cancelled</p>
          <p className="text-3xl font-bold text-red-600">{totals.cancelled_bookings}</p>
        </div>
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <p className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Total Revenue</p>
          <p className="text-3xl font-bold text-primary-600">${totals.total_revenue.toLocaleString()}</p>
        </div>
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Bar Chart: Bookings by Month */}
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <h2 className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-8 flex items-center gap-2">
            <Target size={14} className="text-primary-500" />
            Bookings by Month
          </h2>
          <div className="h-[300px]">
            {isLoading ? (
              <div className="h-full flex items-center justify-center text-gray-400">Loading...</div>
            ) : (
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={monthlyData}>
                  <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                  <XAxis dataKey="month" axisLine={false} tickLine={false} tick={{ fontSize: 10, fill: '#94a3b8' }} />
                  <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 10, fill: '#94a3b8' }} />
                  <Tooltip />
                  <Bar dataKey="completed_bookings" name="Completed" fill="#10b981" radius={[4, 4, 0, 0]} barSize={20} />
                  <Bar dataKey="cancelled_bookings" name="Cancelled" fill="#ef4444" radius={[4, 4, 0, 0]} barSize={20} />
                </BarChart>
              </ResponsiveContainer>
            )}
          </div>
        </div>

        {/* Line Chart: Revenue Trend */}
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <h2 className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-8 flex items-center gap-2">
            <TrendingUp size={14} className="text-green-500" />
            Revenue Trend
          </h2>
          <div className="h-[300px]">
            {isLoading ? (
              <div className="h-full flex items-center justify-center text-gray-400">Loading...</div>
            ) : (
              <ResponsiveContainer width="100%" height="100%">
                <LineChart data={monthlyData}>
                  <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                  <XAxis dataKey="month" axisLine={false} tickLine={false} tick={{ fontSize: 10, fill: '#94a3b8' }} />
                  <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 10, fill: '#94a3b8' }} />
                  <Tooltip formatter={(value) => [`$${Number(value).toLocaleString()}`, 'Revenue']} />
                  <Line type="monotone" dataKey="revenue" stroke="#6366f1" strokeWidth={3} dot={{ r: 6, fill: '#6366f1' }} activeDot={{ r: 8 }} />
                </LineChart>
              </ResponsiveContainer>
            )}
          </div>
        </div>
      </div>

      {/* Monthly Data Table */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="p-6 bg-gray-50/50 border-b border-gray-100">
          <h2 className="text-xs font-bold text-gray-500 uppercase tracking-widest">Monthly Breakdown - {year}</h2>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-gray-50/30">
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">Month</th>
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-center">Total Bookings</th>
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-center">Completed</th>
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-center">Cancelled</th>
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-right">Revenue</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {isLoading ? (
                <tr>
                  <td colSpan={5} className="p-8 text-center text-gray-400">Loading...</td>
                </tr>
              ) : monthlyData.length === 0 ? (
                <tr>
                  <td colSpan={5} className="p-8 text-center text-gray-400">No data available</td>
                </tr>
              ) : (
                monthlyData.map((entry, index) => (
                  <tr key={index} className="hover:bg-primary-50/20 transition-colors">
                    <td className="p-4 text-sm font-bold text-gray-700">{entry.month}</td>
                    <td className="p-4 text-sm text-gray-600 text-center font-semibold">{entry.total_bookings}</td>
                    <td className="p-4 text-sm text-green-600 text-center font-semibold">{entry.completed_bookings}</td>
                    <td className="p-4 text-sm text-red-600 text-center font-semibold">{entry.cancelled_bookings}</td>
                    <td className="p-4 text-sm font-bold text-gray-900 text-right">${entry.revenue.toLocaleString()}</td>
                  </tr>
                ))
              )}
            </tbody>
            <tfoot>
              <tr className="bg-gray-900 text-white font-bold">
                <td className="p-4 text-xs uppercase tracking-widest">Total</td>
                <td className="p-4 text-sm text-center">{totals.total_bookings}</td>
                <td className="p-4 text-sm text-center">{totals.completed_bookings}</td>
                <td className="p-4 text-sm text-center">{totals.cancelled_bookings}</td>
                <td className="p-4 text-sm font-black text-right">${totals.total_revenue.toLocaleString()}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  )
}
