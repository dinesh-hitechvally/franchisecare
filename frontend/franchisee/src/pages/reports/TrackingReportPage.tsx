import { useState } from 'react'
import { 
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer,
  LineChart, Line
} from 'recharts'
import { Search, MapPin, Download, Target, MousePointer2 } from 'lucide-react'

const TRACKING_DATA = [
  { name: 'Google Search', count: 45, conversion: 12.5, revenue: 3825, color: '#3b82f6' },
  { name: 'Facebook Ads', count: 32, conversion: 8.2, revenue: 2400, color: '#10b981' },
  { name: 'Referral', count: 18, conversion: 25.0, revenue: 1530, color: '#f59e0b' },
  { name: 'Local Flyer', count: 12, conversion: 4.5, revenue: 900, color: '#8b5cf6' },
  { name: 'Walk-in', count: 8, conversion: 100.0, revenue: 680, color: '#f43f5e' },
]

const MONTHLY_TREND = [
  { month: 'Jan', count: 22 },
  { month: 'Feb', count: 28 },
  { month: 'Mar', count: 35 },
  { month: 'Apr', count: 42 },
]

export function TrackingReportPage() {
  const [fromDate, setFromDate] = useState('2026-04-01')
  const [toDate, setToDate] = useState('2026-04-30')

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
          Export Detailed Log
        </button>
      </div>

      {/* Filter Card */}
      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-2">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">From Date</label>
            <input
              type="date"
              value={fromDate}
              onChange={(e) => setFromDate(e.target.value)}
              className="w-full text-sm p-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            />
          </div>
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">To Date</label>
            <input
              type="date"
              value={toDate}
              onChange={(e) => setToDate(e.target.value)}
              className="w-full text-sm p-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            />
          </div>
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">Tracking Category</label>
            <select className="w-full text-sm p-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20">
              <option>Marketing Source</option>
              <option>Lead Source</option>
              <option>Discount Codes</option>
            </select>
          </div>
          <button className="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-all shadow-md font-bold text-xs uppercase tracking-widest active:scale-[0.98]">
            <Search size={18} />
            Generate Tracking Report
          </button>
        </div>
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Bar Chart: Performance by Source */}
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <h2 className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-8 flex items-center gap-2">
            <MousePointer2 size={14} className="text-primary-500" />
            Lead Volume by Source
          </h2>
          <div className="h-[300px]">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={TRACKING_DATA}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 9, fill: '#94a3b8' }} />
                <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 10, fill: '#94a3b8' }} />
                <Tooltip cursor={{ fill: '#f8fafc' }} />
                <Bar dataKey="count" radius={[4, 4, 0, 0]} barSize={40}>
                  {TRACKING_DATA.map((entry, index) => (
                    <Bar key={`bar-${index}`} dataKey="count" fill={entry.color} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Line Chart: Trend Over Time */}
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <h2 className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-8 flex items-center gap-2">
            <TrendingUp size={14} className="text-green-500" />
            Activity Trend (Monthly)
          </h2>
          <div className="h-[300px]">
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={MONTHLY_TREND}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                <XAxis dataKey="month" axisLine={false} tickLine={false} tick={{ fontSize: 10, fill: '#94a3b8' }} />
                <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 10, fill: '#94a3b8' }} />
                <Tooltip />
                <Line type="monotone" dataKey="count" stroke="#6366f1" strokeWidth={3} dot={{ r: 6, fill: '#6366f1' }} activeDot={{ r: 8 }} />
              </LineChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>

      {/* Detailed Tracking Table */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="p-6 bg-gray-50/50 border-b border-gray-100 flex items-center justify-between">
          <h2 className="text-xs font-bold text-gray-500 uppercase tracking-widest">Source Conversion & Revenue Analysis</h2>
          <span className="text-[10px] text-gray-400 font-bold bg-white px-2 py-1 rounded border border-gray-100 shadow-sm uppercase tracking-widest">
            Last 30 Days
          </span>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-gray-50/30">
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">Source Name</th>
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-center">Total Contacts</th>
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-center">Conv. Rate (%)</th>
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-right">Revenue ($)</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {TRACKING_DATA.map((entry, index) => (
                <tr key={index} className="hover:bg-primary-50/20 transition-colors">
                  <td className="p-4">
                    <div className="flex items-center gap-3">
                      <div className="w-2 h-2 rounded-full" style={{ backgroundColor: entry.color }}></div>
                      <span className="text-sm font-bold text-gray-700">{entry.name}</span>
                    </div>
                  </td>
                  <td className="p-4 text-sm text-gray-600 text-center font-semibold">{entry.count}</td>
                  <td className="p-4 text-center">
                    <div className="w-full max-w-[100px] mx-auto bg-gray-100 h-2 rounded-full overflow-hidden">
                      <div className="bg-primary-600 h-full" style={{ width: `${entry.conversion}%` }}></div>
                    </div>
                    <span className="text-[10px] font-bold text-primary-700 mt-1 block">{entry.conversion}%</span>
                  </td>
                  <td className="p-4 text-sm font-bold text-gray-900 text-right">${entry.revenue.toLocaleString()}</td>
                </tr>
              ))}
            </tbody>
            <tfoot>
              <tr className="bg-gray-900 text-white font-bold">
                <td className="p-4 text-xs uppercase tracking-widest">Total Channel Analytics</td>
                <td className="p-4 text-sm text-center">{TRACKING_DATA.reduce((acc, curr) => acc + curr.count, 0)}</td>
                <td className="p-4 text-sm text-center">Average: 30.0%</td>
                <td className="p-4 text-sm font-black text-right">${TRACKING_DATA.reduce((acc, curr) => acc + curr.revenue, 0).toLocaleString()}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  )
}
