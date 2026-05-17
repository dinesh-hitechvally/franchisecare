import { useState } from 'react'
import { Printer, Download, Search, ChevronDown, TrendingUp } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'

interface DetailedEntry {
  date: string
  name: string
  amount: number
  gst: number
  total: number
}

interface DetailedCategory {
  name: string
  entries: DetailedEntry[]
  totalAmount: number
  totalGST: number
  total: number
}

export function DetailProfitLossPage() {
  const [fromDate, setFromDate] = useState('2026-03-05')
  const [toDate, setToDate] = useState('2026-04-02')

  const detailedData: DetailedCategory[] = [
    {
      name: 'Booking Income',
      entries: [
        { date: '2026-03-05', name: 'Clark Brown', amount: 154.55, gst: 15.45, total: 170.00 },
        { date: '2026-03-12', name: 'Sarah Smith', amount: 80.00, gst: 8.00, total: 88.00 },
        { date: '2026-03-20', name: 'Mike Johnson', amount: 120.00, gst: 12.00, total: 132.00 },
      ],
      totalAmount: 354.55,
      totalGST: 35.45,
      total: 390.00
    },
    {
      name: 'Product Sales',
      entries: [
        { date: '2026-03-07', name: 'Oatmeal Shampoo (250ml)', amount: 15.00, gst: 1.50, total: 16.50 },
      ],
      totalAmount: 15.00,
      totalGST: 1.50,
      total: 16.50
    }
  ]

  const totalRevenue = detailedData.reduce((acc, cat) => acc + cat.total, 0)

  return (
    <div className="flex flex-col gap-6 p-6 min-h-screen bg-[#f4f6f8]">
      <PageHeader
        title="Detailed Profit/Loss Statement"
        description="Comprehensive income and expense breakdown"
        icon={<TrendingUp size={20} />}
        actions={
          <div className="flex gap-2">
            <button className="flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors text-xs font-medium">
              <Download size={14} /> Export CSV
            </button>
            <button className="flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors text-xs font-medium">
              <Printer size={14} /> Print
            </button>
          </div>
        }
      />

      {/* Filter Card */}
      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">From</label>
            <input
              type="date"
              value={fromDate}
              onChange={(e) => setFromDate(e.target.value)}
              className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            />
          </div>
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">To</label>
            <input
              type="date"
              value={toDate}
              onChange={(e) => setToDate(e.target.value)}
              className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            />
          </div>
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">Category</label>
            <select className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 text-gray-500">
              <option>All Income/Expense</option>
            </select>
          </div>
          <button className="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-all shadow-md font-bold text-xs uppercase tracking-widest">
            <Search size={16} />
            Generate Detail Statement
          </button>
        </div>
      </div>

      {/* Detailed Table Container */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="p-6 border-b border-gray-100 text-center">
          <h2 className="text-xl font-bold text-gray-900">RetailCare Pty Ltd</h2>
          <p className="text-sm font-bold text-gray-800 uppercase mt-4 tracking-widest">Detailed Income/Expense Statement</p>
          <p className="text-xs font-medium text-gray-400 mt-1 italic">{fromDate} to {toDate}</p>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full border-collapse">
            <thead className="bg-gray-50 font-bold">
              <tr>
                <th className="p-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 text-left">Date</th>
                <th className="p-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 text-left">Account Category / Name</th>
                <th className="p-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 text-right">Amount (ex GST)</th>
                <th className="p-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 text-right">GST</th>
                <th className="p-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 text-right">Total (inc GST)</th>
              </tr>
            </thead>
            <tbody>
              {detailedData.map((cat, catIdx) => (
                <React.Fragment key={catIdx}>
                  {/* Category Header */}
                  <tr className="bg-gray-50/50">
                    <td colSpan={5} className="px-4 py-3 text-sm font-bold text-blue-800 border-b border-gray-200">
                      <div className="flex items-center gap-2">
                        <ChevronDown size={16} />
                        {cat.name}
                      </div>
                    </td>
                  </tr>
                  {/* Itemized Entries */}
                  {cat.entries.map((entry, entryIdx) => (
                    <tr key={entryIdx} className="hover:bg-gray-50/50 transition-colors border-b border-gray-50">
                      <td className="px-8 py-2 text-xs text-gray-500 font-medium">{entry.date}</td>
                      <td className="px-4 py-2 text-xs text-gray-600">{entry.name}</td>
                      <td className="px-4 py-2 text-xs text-gray-900 font-medium text-right">${entry.amount.toFixed(2)}</td>
                      <td className="px-4 py-2 text-xs text-gray-500 text-right">${entry.gst.toFixed(2)}</td>
                      <td className="px-4 py-2 text-xs text-gray-900 font-bold text-right">${entry.total.toFixed(2)}</td>
                    </tr>
                  ))}
                  {/* Category Subtotal */}
                  <tr className="bg-blue-50/30 font-bold italic">
                    <td colSpan={2} className="px-4 py-2 text-xs text-blue-900 text-right uppercase tracking-wider">Subtotal: {cat.name}</td>
                    <td className="px-4 py-2 text-xs text-blue-900 text-right">${cat.totalAmount.toFixed(2)}</td>
                    <td className="px-4 py-2 text-xs text-blue-900 text-right">${cat.totalGST.toFixed(2)}</td>
                    <td className="px-4 py-2 text-xs text-blue-900 text-right font-black border-l border-blue-100">${cat.total.toFixed(2)}</td>
                  </tr>
                </React.Fragment>
              ))}
            </tbody>
            <tfoot>
              <tr className="bg-blue-900 text-white font-black">
                <td colSpan={2} className="px-6 py-4 text-sm uppercase tracking-[0.2em] text-left">Total Net Performance</td>
                <td className="px-4 py-4 text-sm text-right">${detailedData.reduce((acc, cat) => acc + cat.totalAmount, 0).toFixed(2)}</td>
                <td className="px-4 py-4 text-sm text-right">${detailedData.reduce((acc, cat) => acc + cat.totalGST, 0).toFixed(2)}</td>
                <td className="px-4 py-4 text-base text-right border-l border-blue-800">${totalRevenue.toFixed(2)}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  )
}
