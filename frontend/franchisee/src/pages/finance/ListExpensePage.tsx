import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Search, ChevronLeft, ChevronRight, FileDown, Plus } from 'lucide-react'

export function ListExpensePage() {
  const [searchTerm, setSearchTerm] = useState('')
  const [dateFrom, setDateFrom] = useState('2026-02-05')
  const [dateTo, setDateTo] = useState('2026-04-02')

  return (
    <div className="space-y-6">
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">List Expenses</h1>
      </div>

      {/* Action Row */}
      <div className="flex flex-col md:flex-row justify-between gap-4">
        <div className="flex gap-2">
          <Button variant="secondary" className="border-gray-300 text-gray-700 font-semibold px-4 flex items-center gap-2">
            <FileDown className="w-4 h-4" />
            Export Expenses
          </Button>
          <Button variant="secondary" className="border-gray-300 text-gray-700 font-semibold px-4 flex items-center gap-2">
            <Plus className="w-4 h-4" />
            New Expense
          </Button>
        </div>
      </div>

      {/* Filter Panel */}
      <Card className="p-6 bg-white border border-gray-100 shadow-sm">
        <div className="flex flex-col xl:flex-row xl:items-end gap-4 xl:gap-6">
          <div className="xl:w-[320px]">
            <label className="block text-xs font-medium text-gray-400 mb-1 uppercase tracking-tight">Search</label>
            <div className="relative">
              <Search className="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                placeholder="Search expenses"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
          <div className="flex-1 min-w-[180px]">
            <label className="block text-xs font-medium text-gray-400 mb-1 uppercase tracking-tight">Loading Expenses From</label>
            <input
              type="date"
              value={dateFrom}
              onChange={(e) => setDateFrom(e.target.value)}
              className="w-full bg-transparent border-b border-gray-200 py-2 outline-none focus:border-blue-500 text-gray-700 font-medium"
            />
          </div>
          <div className="flex-1 min-w-[180px]">
            <label className="block text-xs font-medium text-gray-400 mb-1 uppercase tracking-tight">To</label>
            <input
              type="date"
              value={dateTo}
              onChange={(e) => setDateTo(e.target.value)}
              className="w-full bg-transparent border-b border-gray-200 py-2 outline-none focus:border-blue-500 text-gray-700 font-medium"
            />
          </div>
          <button className="bg-gray-800 text-white p-3 rounded-md hover:bg-gray-900 transition-colors shadow-md xl:mb-[1px]">
            <Search className="w-5 h-5" />
          </button>
        </div>
      </Card>

      {/* Data Table */}
      <Card className="border border-gray-200 shadow-sm overflow-hidden bg-white">
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left border-collapse">
            <thead className="bg-white border-b border-gray-100">
              <tr>
                <th className="px-6 py-4 font-bold text-gray-900">Title</th>
                <th className="px-6 py-4 font-bold text-gray-900">Description</th>
                <th className="px-6 py-4 font-bold text-gray-900">Category</th>
                <th className="px-6 py-4 font-bold text-gray-900">Date</th>
                <th className="px-6 py-4 font-bold text-gray-900">Amount</th>
                <th className="px-6 py-4 font-bold text-gray-900">Receipt</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center">Active?</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center">Part of Recurring?</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colSpan={8} className="px-6 py-20 text-center text-gray-500 italic bg-white">
                  No Expenses Found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        <div className="px-6 py-4 bg-white border-t border-gray-100 flex items-center justify-end gap-6">
          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-500">Rows per page:</span>
            <select className="bg-transparent border-none text-sm font-medium focus:ring-0 outline-none cursor-pointer">
              <option>25</option>
              <option>50</option>
              <option>100</option>
            </select>
          </div>
          <span className="text-sm text-gray-600">0-0 of 0</span>
          <div className="flex items-center gap-1">
            <button className="p-1 hover:bg-gray-100 rounded-full disabled:opacity-30" disabled>
              <ChevronLeft className="w-5 h-5" />
            </button>
            <button className="p-1 hover:bg-gray-100 rounded-full disabled:opacity-30" disabled>
              <ChevronRight className="w-5 h-5" />
            </button>
          </div>
        </div>
      </Card>
    </div>
  )
}
