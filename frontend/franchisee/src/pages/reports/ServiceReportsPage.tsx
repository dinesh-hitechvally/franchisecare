import { useState } from 'react'
import { 
  PieChart, Pie, Cell, 
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer 
} from 'recharts'
import { Search, Download, FileText } from 'lucide-react'

const SERVICE_DATA = [
  { name: 'Full Groom', value: 45, amount: 3825, color: '#3b82f6' },
  { name: 'Bath & Dry', value: 30, amount: 1350, color: '#10b981' },
  { name: 'Nail Trim', value: 15, amount: 225, color: '#f59e0b' },
  { name: 'Puppy Cut', value: 10, amount: 650, color: '#8b5cf6' },
]

const TOP_SERVICES = [
  { name: 'Groom-L', count: 22 },
  { name: 'Groom-M', count: 15 },
  { name: 'Bath-S', count: 12 },
  { name: 'Bath-L', count: 10 },
  { name: 'Nails', count: 15 },
]

export function ServiceReportsPage() {
  const [year, setYear] = useState('2026')
  const [month, setMonth] = useState('April')

  return (
    <div className="flex flex-col gap-6 p-6 min-h-screen bg-[#f4f6f8]">
      {/* Page Title Card */}
      <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-100 flex items-center justify-between">
        <h1 className="text-xl font-bold text-gray-800">Service Reports</h1>
        <div className="flex gap-2">
          <button className="flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors text-xs font-medium">
            <Download size={14} /> Export CSV
          </button>
          <button className="flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors text-xs font-medium">
            <FileText size={14} /> PDF Report
          </button>
        </div>
      </div>

      {/* Filter Card */}
      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
          <div className="space-y-1.5">
            <label className="text-sm font-medium text-gray-600 uppercase tracking-wider text-[10px]">Year</label>
            <select 
              value={year}
              onChange={(e) => setYear(e.target.value)}
              className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
              <option value="2026">2026</option>
              <option value="2025">2025</option>
            </select>
          </div>
          <div className="space-y-1.5">
            <label className="text-sm font-medium text-gray-600 uppercase tracking-wider text-[10px]">Month</label>
            <select 
              value={month}
              onChange={(e) => setMonth(e.target.value)}
              className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
              <option value="April">April</option>
              <option value="March">March</option>
            </select>
          </div>
          <button className="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-md transition-all shadow-sm font-bold text-xs uppercase tracking-widest">
            <Search size={16} />
            Generate Service Report
          </button>
        </div>
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Pie Chart: Distribution */}
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
          <h2 className="text-sm font-bold text-gray-500 uppercase tracking-wider mb-6">Service Category Distribution</h2>
          <div className="h-[300px]">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={SERVICE_DATA}
                  cx="50%"
                  cy="50%"
                  innerRadius={60}
                  outerRadius={100}
                  paddingAngle={5}
                  dataKey="value"
                >
                  {SERVICE_DATA.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={entry.color} />
                  ))}
                </Pie>
                <Tooltip />
                <Legend layout="vertical" align="right" verticalAlign="middle" />
              </PieChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Bar Chart: Frequency */}
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
          <h2 className="text-sm font-bold text-gray-500 uppercase tracking-wider mb-6">Top 5 Services by Frequency</h2>
          <div className="h-[300px]">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={TOP_SERVICES}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f0f0f0" />
                <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#94a3b8' }} />
                <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#94a3b8' }} />
                <Tooltip cursor={{ fill: '#f8fafc' }} />
                <Bar dataKey="count" fill="#3b82f6" radius={[4, 4, 0, 0]} barSize={40} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>

      {/* Numerical Data Table */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div className="p-6 bg-gray-50/50 border-b border-gray-100 flex items-center justify-between">
          <h2 className="text-sm font-bold text-gray-500 uppercase tracking-wider">Service Performance Summary</h2>
          <span className="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{month} {year}</span>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-gray-50/50">
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">Category</th>
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-center">Services Completed</th>
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-right">Total Revenue</th>
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-right">Avg. Ticket</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {SERVICE_DATA.map((entry, index) => (
                <tr key={index} className="hover:bg-primary-50/30 transition-colors">
                  <td className="p-4 flex items-center gap-3">
                    <div className="w-2 h-2 rounded-full" style={{ backgroundColor: entry.color }}></div>
                    <span className="text-sm font-semibold text-gray-700">{entry.name}</span>
                  </td>
                  <td className="p-4 text-sm text-gray-600 text-center font-medium">{entry.value}</td>
                  <td className="p-4 text-sm font-bold text-gray-900 text-right">${entry.amount.toLocaleString()}</td>
                  <td className="p-4 text-sm font-semibold text-primary-600 text-right">${(entry.amount / entry.value).toFixed(2)}</td>
                </tr>
              ))}
            </tbody>
            <tfoot>
              <tr className="bg-gray-50/80 font-bold">
                <td className="p-4 text-sm text-gray-800">Total All Categories</td>
                <td className="p-4 text-sm text-gray-800 text-center">{SERVICE_DATA.reduce((acc, curr) => acc + curr.value, 0)}</td>
                <td className="p-4 text-sm text-gray-800 text-right">${SERVICE_DATA.reduce((acc, curr) => acc + curr.amount, 0).toLocaleString()}</td>
                <td className="p-4 text-sm text-primary-700 text-right">
                  ${(SERVICE_DATA.reduce((acc, curr) => acc + curr.amount, 0) / SERVICE_DATA.reduce((acc, curr) => acc + curr.value, 0)).toFixed(2)}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  )
}
