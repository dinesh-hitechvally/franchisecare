import { useState } from 'react'
import { Printer, Download, Search, Receipt, Landmark } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'

interface GSTEntry {
  date: string
  name: string
  amount: number
  gst: number
  total: number
}

interface GSTSection {
  title: string
  entries: GSTEntry[]
  totalGST: number
}

export function GSTDetailPage() {
  const [year, setYear] = useState('2026')
  const [month, setMonth] = useState('April')

  const salesGST: GSTSection = {
    title: 'GST Collected (Sales)',
    entries: [
      { date: '2026-04-03', name: 'John Doe - Full Groom', amount: 77.27, gst: 7.73, total: 85.00 },
      { date: '2026-04-03', name: 'Sarah Smith - Bath & Dry', amount: 40.91, gst: 4.09, total: 45.00 },
      { date: '2026-04-03', name: 'Mike Johnson - Nail Trim', amount: 27.27, gst: 2.73, total: 30.00 },
    ],
    totalGST: 14.55
  }

  const purchaseGST: GSTSection = {
    title: 'GST Paid (Purchases)',
    entries: [
      { date: '2026-04-01', name: 'Shell - Fuel', amount: 72.72, gst: 7.28, total: 80.00 },
      { date: '2026-04-02', name: 'Pet Supplies Co - Shampoos', amount: 150.00, gst: 15.00, total: 165.00 },
    ],
    totalGST: 22.28
  }

  return (
    <div className="flex flex-col gap-6 p-6 min-h-screen bg-[#f4f6f8]">
      <PageHeader
        title="GST Detailed Report"
        description="Transaction-level GST breakdown"
        icon={<Receipt size={20} />}
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
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">Financial Year</label>
            <select 
              value={year}
              onChange={(e) => setYear(e.target.value)}
              className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
              <option value="2026">2026-2027</option>
              <option value="2025">2025-2026</option>
            </select>
          </div>
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">Month</label>
            <select 
              value={month}
              onChange={(e) => setMonth(e.target.value)}
              className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
              <option value="April">April</option>
              <option value="March">March</option>
            </select>
          </div>
          <button className="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-all shadow-md font-bold text-xs uppercase tracking-widest active:scale-[0.98]">
            <Search size={18} />
            Generate Detail Report
          </button>
        </div>
      </div>

      {/* Report Tables */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="p-8 text-center border-b border-gray-100">
          <h2 className="text-2xl font-bold text-gray-900">RetailCare Pty Ltd</h2>
          <p className="text-sm font-bold text-gray-800 uppercase mt-4 tracking-widest">Detailed GST Transaction Ledger</p>
          <p className="text-xs font-medium text-gray-400 mt-1 italic">Period: {month} {year}</p>
        </div>

        <div className="p-6 space-y-12">
          {/* Sales Section */}
          <div className="space-y-4">
            <h3 className="px-4 py-2 bg-primary-900 text-white text-xs font-bold uppercase tracking-[0.2em] rounded-md">
              {salesGST.title}
            </h3>
            <table className="w-full border-collapse">
              <thead>
                <tr className="bg-gray-50">
                  <th className="p-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 text-left w-24">Date</th>
                  <th className="p-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 text-left">Transaction Details</th>
                  <th className="p-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 text-right">Amount (Excl)</th>
                  <th className="p-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 text-right text-primary-600">GST</th>
                  <th className="p-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 text-right">Total (Incl)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {salesGST.entries.map((entry, idx) => (
                  <tr key={idx} className="hover:bg-gray-50/50 transition-colors">
                    <td className="p-3 text-xs text-gray-500 font-medium">{entry.date}</td>
                    <td className="p-3 text-xs text-gray-600 font-bold">{entry.name}</td>
                    <td className="p-3 text-xs text-gray-500 text-right font-medium">${entry.amount.toFixed(2)}</td>
                    <td className="p-3 text-xs text-primary-600 text-right font-black">${entry.gst.toFixed(2)}</td>
                    <td className="p-3 text-xs text-gray-900 text-right font-black border-l border-gray-50 bg-gray-50/20">${entry.total.toFixed(2)}</td>
                  </tr>
                ))}
              </tbody>
              <tfoot>
                <tr className="bg-primary-50 font-black">
                  <td colSpan={3} className="p-4 text-xs text-primary-900 text-right uppercase tracking-widest">Total GST on Sales</td>
                  <td className="p-4 text-sm text-primary-900 text-right">${salesGST.totalGST.toFixed(2)}</td>
                  <td className="p-4 text-sm text-primary-900 text-right"></td>
                </tr>
              </tfoot>
            </table>
          </div>

          {/* Purchases Section */}
          <div className="space-y-4">
            <h3 className="px-4 py-2 bg-error-900 text-white text-xs font-bold uppercase tracking-[0.2em] rounded-md">
              {purchaseGST.title}
            </h3>
            <table className="w-full border-collapse">
              <thead>
                <tr className="bg-gray-50">
                  <th className="p-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 text-left w-24">Date</th>
                  <th className="p-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 text-left">Item / Supplier</th>
                  <th className="p-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 text-right">Amount (Excl)</th>
                  <th className="p-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 text-right text-error-600">GST</th>
                  <th className="p-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 text-right">Total (Incl)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {purchaseGST.entries.map((entry, idx) => (
                  <tr key={idx} className="hover:bg-gray-50/50 transition-colors">
                    <td className="p-3 text-xs text-gray-500 font-medium">{entry.date}</td>
                    <td className="p-3 text-xs text-gray-600 font-bold">{entry.name}</td>
                    <td className="p-3 text-xs text-gray-500 text-right font-medium">${entry.amount.toFixed(2)}</td>
                    <td className="p-3 text-xs text-error-600 text-right font-black">${entry.gst.toFixed(2)}</td>
                    <td className="p-3 text-xs text-gray-900 text-right font-black border-l border-gray-50 bg-gray-50/20">${entry.total.toFixed(2)}</td>
                  </tr>
                ))}
              </tbody>
              <tfoot>
                <tr className="bg-error-50 font-black">
                  <td colSpan={3} className="p-4 text-xs text-error-900 text-right uppercase tracking-widest">Total GST Credits</td>
                  <td className="p-4 text-sm text-error-900 text-right">${purchaseGST.totalGST.toFixed(2)}</td>
                  <td className="p-4 text-sm text-error-900 text-right"></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        {/* Total Summary Row Footer */}
        <div className="px-8 py-6 bg-gray-900 text-white flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Landmark size={32} className="text-primary-400" />
            <div>
              <span className="block text-[10px] font-bold uppercase tracking-widest text-primary-400">Net GST Position</span>
              <p className="text-xs text-gray-400 italic">Net liability for this period</p>
            </div>
          </div>
          <div className="text-right">
            <span className="block text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Difference</span>
            <span className="text-3xl font-black">${Math.abs(salesGST.totalGST - purchaseGST.totalGST).toFixed(2)}</span>
          </div>
        </div>
      </div>
    </div>
  )
}
