import { useState, useMemo } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Printer, Download, Search, Receipt, Landmark, Loader2 } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'
import { reportsApi } from '../../api/services'
import { format, startOfMonth, endOfMonth } from 'date-fns'

export function GSTDetailPage() {
  const currentDate = new Date()
  const [year, setYear] = useState(currentDate.getFullYear().toString())
  const [month, setMonth] = useState(format(currentDate, 'MMMM'))

  const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ]

  const dateRange = useMemo(() => {
    const monthIndex = months.indexOf(month)
    const selectedDate = new Date(parseInt(year), monthIndex, 1)
    return {
      from: format(startOfMonth(selectedDate), 'yyyy-MM-dd'),
      to: format(endOfMonth(selectedDate), 'yyyy-MM-dd'),
    }
  }, [year, month])

  const { data: gstData, isLoading, refetch } = useQuery({
    queryKey: ['gst-report', dateRange.from, dateRange.to],
    queryFn: () => reportsApi.getGstSummary({ date_from: dateRange.from, date_to: dateRange.to }),
  })

  const salesGST = {
    title: 'GST Collected (Sales)',
    entries: gstData?.gst_collected?.items || [],
    totalGST: gstData?.gst_collected?.total || 0
  }

  const purchaseGST = {
    title: 'GST Paid (Purchases)',
    entries: gstData?.gst_paid?.items || [],
    totalGST: gstData?.gst_paid?.total || 0
  }

  const netGST = gstData?.net_gst || (salesGST.totalGST - purchaseGST.totalGST)

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
              {[currentDate.getFullYear(), currentDate.getFullYear() - 1, currentDate.getFullYear() - 2].map(y => (
                <option key={y} value={y}>{y}-{y + 1}</option>
              ))}
            </select>
          </div>
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">Month</label>
            <select 
              value={month}
              onChange={(e) => setMonth(e.target.value)}
              className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
              {months.map(m => (
                <option key={m} value={m}>{m}</option>
              ))}
            </select>
          </div>
          <button 
            onClick={() => refetch()}
            disabled={isLoading}
            className="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-all shadow-md font-bold text-xs uppercase tracking-widest active:scale-[0.98] disabled:opacity-50"
          >
            {isLoading ? <Loader2 size={18} className="animate-spin" /> : <Search size={18} />}
            Generate Detail Report
          </button>
        </div>
      </div>

      {/* Report Tables */}
      {isLoading ? (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-12">
          <div className="flex items-center justify-center gap-2 text-gray-500">
            <Loader2 className="w-6 h-6 animate-spin" />
            <span>Loading GST report...</span>
          </div>
        </div>
      ) : (
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
                {salesGST.entries.length === 0 ? (
                  <tr>
                    <td colSpan={5} className="p-6 text-center text-gray-500 text-sm">No sales entries for this period</td>
                  </tr>
                ) : (
                salesGST.entries.map((entry, idx) => (
                  <tr key={idx} className="hover:bg-gray-50/50 transition-colors">
                    <td className="p-3 text-xs text-gray-500 font-medium">{entry.date}</td>
                    <td className="p-3 text-xs text-gray-600 font-bold">{entry.description}</td>
                    <td className="p-3 text-xs text-gray-500 text-right font-medium">${Number(entry.amount).toFixed(2)}</td>
                    <td className="p-3 text-xs text-primary-600 text-right font-black">${Number(entry.gst).toFixed(2)}</td>
                    <td className="p-3 text-xs text-gray-900 text-right font-black border-l border-gray-50 bg-gray-50/20">${(Number(entry.amount) + Number(entry.gst)).toFixed(2)}</td>
                  </tr>
                ))
                )}
              </tbody>
              <tfoot>
                <tr className="bg-primary-50 font-black">
                  <td colSpan={3} className="p-4 text-xs text-primary-900 text-right uppercase tracking-widest">Total GST on Sales</td>
                  <td className="p-4 text-sm text-primary-900 text-right">${Number(salesGST.totalGST).toFixed(2)}</td>
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
                {purchaseGST.entries.length === 0 ? (
                  <tr>
                    <td colSpan={5} className="p-6 text-center text-gray-500 text-sm">No purchase entries for this period</td>
                  </tr>
                ) : (
                purchaseGST.entries.map((entry, idx) => (
                  <tr key={idx} className="hover:bg-gray-50/50 transition-colors">
                    <td className="p-3 text-xs text-gray-500 font-medium">{entry.date}</td>
                    <td className="p-3 text-xs text-gray-600 font-bold">{entry.description}</td>
                    <td className="p-3 text-xs text-gray-500 text-right font-medium">${Number(entry.amount).toFixed(2)}</td>
                    <td className="p-3 text-xs text-error-600 text-right font-black">${Number(entry.gst).toFixed(2)}</td>
                    <td className="p-3 text-xs text-gray-900 text-right font-black border-l border-gray-50 bg-gray-50/20">${(Number(entry.amount) + Number(entry.gst)).toFixed(2)}</td>
                  </tr>
                ))
                )}
              </tbody>
              <tfoot>
                <tr className="bg-error-50 font-black">
                  <td colSpan={3} className="p-4 text-xs text-error-900 text-right uppercase tracking-widest">Total GST Credits</td>
                  <td className="p-4 text-sm text-error-900 text-right">${Number(purchaseGST.totalGST).toFixed(2)}</td>
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
            <span className="block text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">
              {netGST >= 0 ? 'Amount Owed' : 'Credit'}
            </span>
            <span className="text-3xl font-black">${Math.abs(netGST).toFixed(2)}</span>
          </div>
        </div>
      </div>
      )}
    </div>
  )
}
