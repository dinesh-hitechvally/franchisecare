import { useState } from 'react'
import { 
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, ReferenceLine 
} from 'recharts'
import { Search, TrendingUp, DollarSign, Calendar } from 'lucide-react'

const CATEGORY_FORECAST = [
  { name: 'Booking Income', amount: 8450, color: '#3b82f6' },
  { name: 'Product Sales', amount: 1200, color: '#10b981' },
  { name: 'Recurring Service', amount: 3500, color: '#8b5cf6' },
]

const CUSTOMER_FORECAST = [
  { name: 'Clark Brown', amount: 1895 },
  { name: 'Sarah Smith', amount: 700 },
  { name: 'Rabee Accr', amount: 555 },
  { name: 'Rabee (Account) Subedi', amount: 455 },
  { name: 'Rabee Dev Test Lead', amount: 440 },
]

const WEEKLY_FORECAST = [
  { week: '30th Mar - 5th Apr', income: 5550, services: 11 },
  { week: '6th Apr - 12th Apr', income: 4200, services: 8 },
  { week: '13th Apr - 19th Apr', income: 6100, services: 12 },
  { week: '20th Apr - 26th Apr', income: 5800, services: 10 },
]

export function IncomeForecastPage() {
  const [fromDate, setFromDate] = useState('2026-04-03')
  const [toDate, setToDate] = useState('2026-04-10')

  return (
    <div className="flex flex-col gap-6 p-6 min-h-screen bg-[#f4f6f8]">
      {/* Page Title Card */}
      <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-100 mb-2">
        <h1 className="text-xl font-bold text-gray-800 flex items-center gap-2">
          <TrendingUp size={24} className="text-primary-600" />
          Income Forecast
        </h1>
      </div>

      {/* Filter Card */}
      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-2">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">From</label>
            <input
              type="date"
              value={fromDate}
              onChange={(e) => setFromDate(e.target.value)}
              className="w-full text-sm p-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            />
          </div>
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">To</label>
            <input
              type="date"
              value={toDate}
              onChange={(e) => setToDate(e.target.value)}
              className="w-full text-sm p-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            />
          </div>
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">Income Category</label>
            <select className="w-full text-sm p-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20">
              <option>All Categories</option>
              <option>Booking Income</option>
              <option>Product Sales</option>
            </select>
          </div>
          <button className="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-all shadow-md font-bold text-xs uppercase tracking-widest active:scale-[0.98]">
            <Search size={18} />
            Generate Forecast
          </button>
        </div>
      </div>

      {/* Charts Row Cluster */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Category Forecast Bar Chart */}
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <h2 className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-8 flex items-center gap-2">
            <DollarSign size={14} className="text-primary-500" />
            Income Forecast Categories
          </h2>
          <div className="h-[300px]">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={CATEGORY_FORECAST}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 10, fill: '#94a3b8' }} />
                <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 10, fill: '#94a3b8' }} />
                <Tooltip cursor={{ fill: '#f8fafc' }} />
                <Bar dataKey="amount" radius={[4, 4, 0, 0]} barSize={50}>
                  {CATEGORY_FORECAST.map((entry, index) => (
                    <Bar key={`bar-${index}`} dataKey="amount" fill={entry.color} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Top 10 Customers Forecast */}
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <h2 className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-8 flex items-center gap-2">
            <Calendar size={14} className="text-primary-500" />
            Highest (top 5) Customer Forecast
          </h2>
          <div className="h-[300px]">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={CUSTOMER_FORECAST}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 9, fill: '#94a3b8' }} />
                <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 10, fill: '#94a3b8' }} />
                <Tooltip cursor={{ fill: '#f8fafc' }} />
                <Bar dataKey="amount" fill="#6366f1" radius={[4, 4, 0, 0]} barSize={24} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>

      {/* Weekly Forecast Table */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="p-6 bg-gray-50/50 border-b border-gray-100 flex items-center justify-between">
          <h2 className="text-xs font-bold text-gray-500 uppercase tracking-widest">Weekly Income Projections</h2>
          <span className="text-[10px] text-gray-400 font-bold bg-white px-2 py-1 rounded border border-gray-100 shadow-sm uppercase tracking-widest">
            Estimated Based on Recurring Bookings
          </span>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-gray-50/30">
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">Week Period</th>
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-right">Income Forecast ($)</th>
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-center"># of Services</th>
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-right">Projection Accuracy</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {WEEKLY_FORECAST.map((entry, index) => (
                <tr key={index} className="hover:bg-primary-50/20 transition-colors">
                  <td className="p-4 text-sm font-bold text-gray-700">{entry.week}</td>
                  <td className="p-4 text-sm font-bold text-gray-900 text-right">${entry.income.toLocaleString()}</td>
                  <td className="p-4 text-sm text-gray-600 text-center font-semibold">{entry.services}</td>
                  <td className="p-4 text-right">
                    <div className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800 uppercase">
                      High Confidence
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
            <tfoot>
              <tr className="bg-gray-50 font-bold border-t border-gray-200">
                <td className="p-4 text-sm text-gray-900">Total Monthly Projection</td>
                <td className="p-4 text-sm text-gray-900 text-right">${WEEKLY_FORECAST.reduce((acc, curr) => acc + curr.income, 0).toLocaleString()}</td>
                <td className="p-4 text-sm text-gray-900 text-center">{WEEKLY_FORECAST.reduce((acc, curr) => acc + curr.services, 0)}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  )
}
