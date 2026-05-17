import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Printer, Download, Search, TrendingUp } from 'lucide-react'
import { reportsApi } from '../../api/services'
import { format, startOfYear, endOfYear } from 'date-fns'
import { PageHeader } from '../../components/layout/PageHeader'

export function ProfitLossPage() {
  const [dateFrom, setDateFrom] = useState(format(startOfYear(new Date()), 'yyyy-MM-dd'))
  const [dateTo, setDateTo] = useState(format(endOfYear(new Date()), 'yyyy-MM-dd'))

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['profit-loss', dateFrom, dateTo],
    queryFn: () => reportsApi.getProfitLoss({ date_from: dateFrom, date_to: dateTo }),
  })

  const salesItems = data?.sales?.items ?? []
  const totalSales = data?.sales?.total ?? 0
  const expenseItems = data?.expenses?.items ?? []
  const totalExpenses = data?.expenses?.total ?? 0
  const netProfit = data?.net_profit ?? 0
  const profitMargin = data?.profit_margin ?? 0

  return (
    <div className="flex flex-col gap-6 p-6 min-h-screen bg-[#f4f6f8]">
      <PageHeader
        title="Profit/Loss Statement"
        description="Compare income against expenses"
        icon={<TrendingUp size={20} />}
      />

      {/* Filter Card */}
      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-2">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">From</label>
            <input
              type="date"
              value={dateFrom}
              onChange={(e) => setDateFrom(e.target.value)}
              className="w-full text-sm p-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            />
          </div>
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">To</label>
            <input
              type="date"
              value={dateTo}
              onChange={(e) => setDateTo(e.target.value)}
              className="w-full text-sm p-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            />
          </div>
          <button
            onClick={() => refetch()}
            className="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-all shadow-md font-bold text-sm"
          >
            <Search size={18} />
            Generate Statement
          </button>
        </div>
      </div>

      {/* Report Container */}
      <div className="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden print:shadow-none print:border-none">
        {/* Statement Header */}
        <div className="p-8 text-center border-b border-gray-100 bg-gray-50/30">
          <div className="flex justify-end gap-2 mb-6 print:hidden">
            <button className="px-3 py-1 bg-white border border-gray-200 rounded text-xs font-medium hover:bg-gray-50 flex items-center gap-2">
              <Download size={14} /> Export CSV
            </button>
            <button className="px-3 py-1 bg-white border border-gray-200 rounded text-xs font-medium hover:bg-gray-50 flex items-center gap-2">
              <Printer size={14} /> Print Preview
            </button>
          </div>
          
          <h2 className="text-2xl font-bold text-gray-900">Profit & Loss Statement</h2>
          <div className="mt-4">
            <p className="text-xs font-medium text-gray-400 italic">
              Period: {format(new Date(dateFrom), 'PPP')} - {format(new Date(dateTo), 'PPP')}
            </p>
          </div>
        </div>

        {isLoading ? (
          <div className="p-16 text-center text-gray-400">Loading...</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full border-collapse">
              <thead>
                <tr className="bg-gray-100/80">
                  <th className="p-3 text-left text-[11px] font-bold text-gray-600 uppercase tracking-wider w-[60%]">Category</th>
                  <th className="p-3 text-right text-[11px] font-bold text-gray-600 uppercase tracking-wider">Count</th>
                  <th className="p-3 text-right text-[11px] font-bold text-gray-600 uppercase tracking-wider">Amount</th>
                </tr>
              </thead>
              
              {/* Sales/Income Section */}
              <tbody>
                <tr className="bg-green-50">
                  <td colSpan={3} className="px-4 py-3 text-[12px] font-bold text-green-700 uppercase tracking-widest border-b border-green-200">
                    Income / Sales
                  </td>
                </tr>
                {salesItems.length === 0 ? (
                  <tr>
                    <td colSpan={3} className="px-6 py-4 text-xs text-gray-400 italic text-center">No income records</td>
                  </tr>
                ) : (
                  salesItems.map((item, idx) => (
                    <tr key={idx} className="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                      <td className="px-6 py-2 text-sm text-gray-700">{item.category}</td>
                      <td className="px-3 py-2 text-sm text-gray-500 text-right">{item.count}</td>
                      <td className="px-3 py-2 text-sm text-green-600 font-semibold text-right">${item.amount.toLocaleString()}</td>
                    </tr>
                  ))
                )}
                <tr className="bg-green-50 font-bold border-t-2 border-green-200">
                  <td className="px-4 py-3 text-sm text-green-800 uppercase tracking-wider text-left">Total Income</td>
                  <td className="px-3 py-3 text-sm text-green-700 text-right"></td>
                  <td className="px-3 py-3 text-sm text-green-700 font-black text-right">${totalSales.toLocaleString()}</td>
                </tr>
              </tbody>

              {/* Expenses Section */}
              <tbody>
                <tr className="bg-red-50 border-t-4 border-white">
                  <td colSpan={3} className="px-4 py-3 text-[12px] font-bold text-red-700 uppercase tracking-widest border-b border-red-200">
                    Expenses
                  </td>
                </tr>
                {expenseItems.length === 0 ? (
                  <tr>
                    <td colSpan={3} className="px-6 py-4 text-xs text-gray-400 italic text-center">No expense records</td>
                  </tr>
                ) : (
                  expenseItems.map((item, idx) => (
                    <tr key={idx} className="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                      <td className="px-6 py-2 text-sm text-gray-700">{item.category}</td>
                      <td className="px-3 py-2 text-sm text-gray-500 text-right">{item.count}</td>
                      <td className="px-3 py-2 text-sm text-red-600 font-semibold text-right">${item.amount.toLocaleString()}</td>
                    </tr>
                  ))
                )}
                <tr className="bg-red-50 font-bold border-t-2 border-red-200">
                  <td className="px-4 py-3 text-sm text-red-800 uppercase tracking-wider text-left">Total Expenses</td>
                  <td className="px-3 py-3 text-sm text-red-700 text-right"></td>
                  <td className="px-3 py-3 text-sm text-red-700 font-black text-right">${totalExpenses.toLocaleString()}</td>
                </tr>
              </tbody>

              {/* Bottom Summary */}
              <tfoot>
                <tr className={`${netProfit >= 0 ? 'bg-green-900' : 'bg-red-900'} text-white font-black`}>
                  <td className="px-4 py-4 text-sm uppercase tracking-[0.1em] text-left">
                    Net {netProfit >= 0 ? 'Profit' : 'Loss'}
                    <span className="ml-2 text-xs font-normal opacity-75">({profitMargin}% margin)</span>
                  </td>
                  <td className="px-3 py-4 text-sm text-right"></td>
                  <td className="px-3 py-4 text-lg font-black text-right">
                    {netProfit >= 0 ? '' : '-'}${Math.abs(netProfit).toLocaleString()}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        )}
      </div>
    </div>
  )
}
