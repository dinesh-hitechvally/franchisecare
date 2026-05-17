import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Printer, Download, Search, Landmark, Quote } from 'lucide-react'
import { reportsApi } from '../../api/services'
import { format, startOfMonth, endOfMonth, startOfQuarter, endOfQuarter } from 'date-fns'
import { PageHeader } from '../../components/layout/PageHeader'

export function GSTSummaryPage() {
  const [dateFrom, setDateFrom] = useState(format(startOfMonth(new Date()), 'yyyy-MM-dd'))
  const [dateTo, setDateTo] = useState(format(endOfMonth(new Date()), 'yyyy-MM-dd'))

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['gst-summary', dateFrom, dateTo],
    queryFn: () => reportsApi.getGstSummary({ date_from: dateFrom, date_to: dateTo }),
  })

  const gstCollected = data?.gst_collected?.items ?? []
  const gstPaid = data?.gst_paid?.items ?? []
  const totalCollected = data?.gst_collected?.total ?? 0
  const totalPaid = data?.gst_paid?.total ?? 0
  const netGST = data?.net_gst ?? 0

  const setQuarter = (q: number) => {
    const year = new Date().getFullYear()
    const quarterStart = startOfQuarter(new Date(year, (q - 1) * 3, 1))
    const quarterEnd = endOfQuarter(quarterStart)
    setDateFrom(format(quarterStart, 'yyyy-MM-dd'))
    setDateTo(format(quarterEnd, 'yyyy-MM-dd'))
  }

  return (
    <div className="flex flex-col gap-6 p-6 min-h-screen bg-[#f4f6f8]">
      <PageHeader
        title="GST Summary Report"
        description="BAS calculation and GST position"
        icon={<Landmark size={20} />}
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
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">From Date</label>
            <input
              type="date"
              value={dateFrom}
              onChange={(e) => setDateFrom(e.target.value)}
              className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-lg"
            />
          </div>
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">To Date</label>
            <input
              type="date"
              value={dateTo}
              onChange={(e) => setDateTo(e.target.value)}
              className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-lg"
            />
          </div>
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">Quick Select</label>
            <select
              onChange={(e) => setQuarter(Number(e.target.value))}
              className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-lg"
            >
              <option value="">Custom</option>
              <option value="1">Q1 (Jan-Mar)</option>
              <option value="2">Q2 (Apr-Jun)</option>
              <option value="3">Q3 (Jul-Sep)</option>
              <option value="4">Q4 (Oct-Dec)</option>
            </select>
          </div>
          <button
            onClick={() => refetch()}
            className="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-all shadow-md font-bold text-xs uppercase tracking-widest"
          >
            <Search size={16} />
            Generate Report
          </button>
        </div>
      </div>

      {/* Main Report Content */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="p-8 text-center border-b border-gray-100 bg-gray-50/20">
          <h2 className="text-2xl font-bold text-gray-900">GST Summary</h2>
          <p className="text-xs text-gray-500 mt-1 uppercase tracking-widest font-bold">BAS Calculation</p>
          <p className="text-xs font-medium text-gray-400 mt-1 italic">Period: {dateFrom} to {dateTo}</p>
        </div>

        {isLoading ? (
          <div className="p-16 text-center text-gray-400">Loading...</div>
        ) : (
          <div className="p-8 space-y-12">
            {/* GST Collected */}
            <div className="space-y-4">
              <h3 className="text-sm font-bold text-primary-700 uppercase tracking-widest flex items-center gap-2">
                <span className="w-2 h-2 bg-primary-500 rounded-full"></span>
                GST Collected (Sales)
              </h3>
              <table className="w-full border-collapse">
                <thead>
                  <tr className="border-b-2 border-gray-100">
                    <th className="p-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest">Description</th>
                    <th className="p-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest">Amount</th>
                    <th className="p-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest">GST</th>
                  </tr>
                </thead>
                <tbody>
                  {gstCollected.length === 0 ? (
                    <tr><td colSpan={3} className="p-4 text-center text-gray-400 italic">No GST collected items</td></tr>
                  ) : (
                    gstCollected.map((item, idx) => (
                      <tr key={idx} className="border-b border-gray-50">
                        <td className="p-3 text-xs text-gray-600 font-medium">{item.description}</td>
                        <td className="p-3 text-xs text-right text-gray-500">${item.amount.toLocaleString()}</td>
                        <td className="p-3 text-xs text-right text-gray-900 font-bold">${item.gst.toLocaleString()}</td>
                      </tr>
                    ))
                  )}
                </tbody>
                <tfoot className="bg-gray-50">
                  <tr>
                    <td colSpan={2} className="p-3 text-xs font-bold text-gray-600 uppercase tracking-wider">Total GST Collected</td>
                    <td className="p-3 text-xs text-right text-primary-900 font-black">${totalCollected.toLocaleString()}</td>
                  </tr>
                </tfoot>
              </table>
            </div>

            {/* GST Paid */}
            <div className="space-y-4">
              <h3 className="text-sm font-bold text-red-700 uppercase tracking-widest flex items-center gap-2">
                <span className="w-2 h-2 bg-red-500 rounded-full"></span>
                GST Paid (Expenses)
              </h3>
              <table className="w-full border-collapse">
                <thead>
                  <tr className="border-b-2 border-gray-100">
                    <th className="p-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest">Description</th>
                    <th className="p-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest">Amount</th>
                    <th className="p-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest">GST</th>
                  </tr>
                </thead>
                <tbody>
                  {gstPaid.length === 0 ? (
                    <tr><td colSpan={3} className="p-4 text-center text-gray-400 italic">No GST paid items</td></tr>
                  ) : (
                    gstPaid.map((item, idx) => (
                      <tr key={idx} className="border-b border-gray-50">
                        <td className="p-3 text-xs text-gray-600 font-medium">{item.description}</td>
                        <td className="p-3 text-xs text-right text-gray-500">${item.amount.toLocaleString()}</td>
                        <td className="p-3 text-xs text-right text-gray-900 font-bold">${item.gst.toLocaleString()}</td>
                      </tr>
                    ))
                  )}
                </tbody>
                <tfoot className="bg-gray-50">
                  <tr>
                    <td colSpan={2} className="p-3 text-xs font-bold text-gray-600 uppercase tracking-wider">Total GST Paid</td>
                    <td className="p-3 text-xs text-right text-red-900 font-black">${totalPaid.toLocaleString()}</td>
                  </tr>
                </tfoot>
              </table>
            </div>

            {/* Net GST Position */}
            <div className="bg-primary-900 rounded-xl p-8 text-white">
              <div className="flex flex-col md:flex-row items-center justify-between gap-8">
                <div className="space-y-2">
                  <h4 className="text-xs font-bold text-primary-400 uppercase tracking-widest">Net GST Position</h4>
                  <p className="text-xs text-primary-200 font-medium italic">
                    Amount {netGST >= 0 ? 'Payable to' : 'Refundable from'} the ATO
                  </p>
                </div>
                <div className="flex gap-12 items-center">
                  <div className="text-center">
                    <span className="block text-[10px] text-primary-300 uppercase font-black tracking-widest mb-1">
                      {netGST >= 0 ? 'Amount Due' : 'Refund'}
                    </span>
                    <span className="text-3xl font-black">${Math.abs(netGST).toLocaleString()}</span>
                  </div>
                  <div className={`p-4 rounded-xl ${netGST >= 0 ? 'bg-red-500 text-white' : 'bg-green-500 text-white'}`}>
                    {netGST >= 0 ? <Landmark size={32} /> : <Quote size={32} />}
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
