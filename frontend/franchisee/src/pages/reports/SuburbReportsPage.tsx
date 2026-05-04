import { useState } from 'react'
import { 
  PieChart, Pie, Cell, 
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer 
} from 'recharts'
import { Search, MapPin, Download } from 'lucide-react'

const SUBURB_DATA = [
  { name: 'Suburbia West', value: 52, amount: 4420, color: '#0ea5e9' },
  { name: 'Dogtown North', value: 38, amount: 3230, color: '#f43f5e' },
  { name: 'Houndhill', value: 25, amount: 2125, color: '#10b981' },
  { name: 'Barking Heights', value: 18, amount: 1530, color: '#f59e0b' },
  { name: 'Paws Park', value: 12, amount: 1020, color: '#8b5cf6' },
]

export function SuburbReportsPage() {
  const [fromDate, setFromDate] = useState('2026-04-01')
  const [toDate, setToDate] = useState('2026-04-30')

  return (
    <div className="flex flex-col gap-6 p-6 min-h-screen bg-[#f4f6f8]">
      {/* Page Title Card */}
      <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-100 flex items-center justify-between">
        <h1 className="text-xl font-bold text-gray-800 flex items-center gap-2">
          <MapPin size={24} className="text-primary-600" />
          Suburb Reports
        </h1>
        <button className="flex items-center gap-2 px-4 py-2 bg-secondary-100 hover:bg-secondary-200 text-secondary-700 rounded-md transition-colors text-sm font-medium">
          <Download size={18} />
          Export Data
        </button>
      </div>

      {/* Filter Card */}
      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-2">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
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
          <button className="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-all shadow-md font-bold text-xs uppercase tracking-widest active:scale-[0.98]">
            <Search size={18} />
            Generate Suburb Report
          </button>
        </div>
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Pie Chart: Suburb Volume */}
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <h2 className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-8">Service Volume by Suburb</h2>
          <div className="h-[350px]">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={SUBURB_DATA}
                  cx="50%"
                  cy="45%"
                  innerRadius={80}
                  outerRadius={120}
                  paddingAngle={5}
                  dataKey="value"
                >
                  {SUBURB_DATA.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={entry.color} stroke="none" />
                  ))}
                </Pie>
                <Tooltip 
                  contentStyle={{ borderRadius: '12px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                />
                <Legend layout="horizontal" align="center" verticalAlign="bottom" wrapperStyle={{ paddingTop: '20px' }} />
              </PieChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Bar Chart: Revenue by Suburb */}
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <h2 className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-8">Revenue Contribution ($)</h2>
          <div className="h-[350px]">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={SUBURB_DATA} layout="vertical">
                <CartesianGrid strokeDasharray="3 3" horizontal={false} stroke="#f1f5f9" />
                <XAxis type="number" axisLine={false} tickLine={false} tick={{ fontSize: 10, fill: '#94a3b8' }} />
                <YAxis dataKey="name" type="category" axisLine={false} tickLine={false} tick={{ fontSize: 11, fill: '#64748b', fontWeight: '500' }} />
                <Tooltip cursor={{ fill: '#f8fafc' }} />
                <Bar dataKey="amount" fill="#0ea5e9" radius={[0, 4, 4, 0]} barSize={24} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>

      {/* Detailed Suburb Table */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="p-6 bg-gray-50/50 border-b border-gray-100 flex items-center justify-between">
          <h2 className="text-xs font-bold text-gray-500 uppercase tracking-widest">Suburb Performance Metrics</h2>
          <span className="text-[10px] text-primary-600 font-bold bg-primary-50 px-2 py-1 rounded">DATA SOURCE: BOOKINGS ENGINE</span>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-gray-50/30">
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">Suburb Name</th>
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-center">Total Services</th>
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-right">Total Revenue</th>
                <th className="p-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-right">Avg. Order Value</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {SUBURB_DATA.map((entry, index) => (
                <tr key={index} className="hover:bg-primary-50/20 transition-colors group">
                  <td className="p-4">
                    <div className="flex items-center gap-3">
                      <div className="w-1.5 h-1.5 rounded-full" style={{ backgroundColor: entry.color }}></div>
                      <span className="text-sm font-bold text-gray-700 group-hover:text-primary-700 transition-colors">{entry.name}</span>
                    </div>
                  </td>
                  <td className="p-4 text-sm text-gray-600 text-center font-semibold">{entry.value}</td>
                  <td className="p-4 text-sm font-bold text-gray-900 text-right">${entry.amount.toLocaleString()}</td>
                  <td className="p-4 text-sm font-bold text-secondary-600 text-right">${(entry.amount / entry.value).toFixed(2)}</td>
                </tr>
              ))}
            </tbody>
            <tfoot>
              <tr className="bg-primary-900 text-white font-bold">
                <td className="p-4 text-xs uppercase tracking-widest">Total Network</td>
                <td className="p-4 text-sm text-center">{SUBURB_DATA.reduce((acc, curr) => acc + curr.value, 0)}</td>
                <td className="p-4 text-sm text-right">${SUBURB_DATA.reduce((acc, curr) => acc + curr.amount, 0).toLocaleString()}</td>
                <td className="p-4 text-sm text-right">
                  ${(SUBURB_DATA.reduce((acc, curr) => acc + curr.amount, 0) / SUBURB_DATA.reduce((acc, curr) => acc + curr.value, 0)).toFixed(2)}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  )
}
