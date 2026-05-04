import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Search, ChevronLeft, ChevronRight, Check, X } from 'lucide-react'

export function ListIncomesPage() {
  const [searchTerm, setSearchTerm] = useState('')
  const [dateFrom, setDateFrom] = useState('2025-10-02')
  const [dateTo, setDateTo] = useState('2026-04-02')

  const incomes = [
    { 
      id: '1', 
      title: 'Income from Rabee (Account) Subedi - 2026-03-15', 
      description: 'Booking Completed successfully for Rabee (Account) Subedi - 2026-03-15', 
      category: 'Booking Income', 
      date: '15th Mar, 2026', 
      amount: 100.00, 
      active: true, 
      recurring: false 
    },
    { 
      id: '2', 
      title: 'Monthly Franchise Fee', 
      description: 'Monthly service fee for April', 
      category: 'Other Income', 
      date: '1st Apr, 2026', 
      amount: 450.00, 
      active: true, 
      recurring: true 
    },
  ]

  return (
    <div className="space-y-6">
      {/* Top Header Card */}
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">List Income</h1>
      </div>

      {/* Search and Action Row */}
      <div className="flex flex-col md:flex-row justify-between gap-4">
        <div className="relative flex-1 max-w-md">
          <Search className="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
          <input
            type="text"
            placeholder="Search income"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <Button variant="secondary" className="border-gray-900 text-gray-900 hover:bg-gray-900 hover:text-white px-6">
          + New Income
        </Button>
      </div>

      {/* Filter Panel */}
      <Card className="p-6 bg-white border border-gray-100 shadow-sm">
        <div className="flex flex-wrap items-end gap-8">
          <div className="flex-1 min-w-[200px]">
            <label className="block text-xs font-medium text-gray-400 mb-1 uppercase">Loading Income From</label>
            <input
              type="date"
              value={dateFrom}
              onChange={(e) => setDateFrom(e.target.value)}
              className="w-full bg-transparent border-b border-gray-200 py-2 outline-none focus:border-blue-500 text-gray-700"
            />
          </div>
          <div className="flex-1 min-w-[200px]">
            <label className="block text-xs font-medium text-gray-400 mb-1 uppercase">To</label>
            <input
              type="date"
              value={dateTo}
              onChange={(e) => setDateTo(e.target.value)}
              className="w-full bg-transparent border-b border-gray-200 py-2 outline-none focus:border-blue-500 text-gray-700"
            />
          </div>
          <Button className="bg-gray-900 text-white flex items-center gap-2 px-8 py-2.5 h-auto">
            <Search className="w-4 h-4" />
            List Incomes
          </Button>
        </div>
      </Card>

      {/* Info Disclaimer */}
      <div className="py-2">
        <p className="text-sm font-bold text-gray-800">
          You must edit the original booking if you want to edit income related to any booking
        </p>
      </div>

      {/* Data Table */}
      <Card className="border border-gray-200 shadow-sm overflow-hidden bg-white">
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left border-collapse">
            <thead className="bg-white border-b border-gray-100">
              <tr>
                <th className="px-6 py-4 font-bold text-gray-900">Title</th>
                <th className="px-6 py-4 font-bold text-gray-900">Description</th>
                <th className="px-6 py-4 font-bold text-gray-900">Category</th>
                <th className="px-6 py-4 font-bold text-gray-900 whitespace-nowrap">Date</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-right">Amount</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center">Active?</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center">Recurring?</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50 bg-white">
              {incomes.map((income) => (
                <tr key={income.id} className="hover:bg-gray-50/50 transition-colors">
                  <td className="px-6 py-4 text-gray-800 font-medium min-w-[200px]">{income.title}</td>
                  <td className="px-6 py-4 text-gray-500 min-w-[250px]">{income.description}</td>
                  <td className="px-6 py-4 text-gray-600 whitespace-nowrap">{income.category}</td>
                  <td className="px-6 py-4 text-gray-600 whitespace-nowrap">{income.date}</td>
                  <td className="px-6 py-4 text-right text-gray-900 font-bold">${income.amount.toFixed(2)}</td>
                  <td className="px-6 py-4">
                    <div className="flex justify-center">
                      <Check className="w-5 h-5 text-green-500" strokeWidth={3} />
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex justify-center">
                      {income.recurring ? (
                        <Check className="w-5 h-5 text-blue-500" strokeWidth={3} />
                      ) : (
                        <X className="w-5 h-5 text-gray-300" strokeWidth={3} />
                      )}
                    </div>
                  </td>
                </tr>
              ))}
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
          <span className="text-sm text-gray-600">1-30 of 34</span>
          <div className="flex items-center gap-1">
            <button className="p-1 hover:bg-gray-100 rounded-full">
              <ChevronLeft className="w-5 h-5" />
            </button>
            <button className="p-1 hover:bg-gray-100 rounded-full">
              <ChevronRight className="w-5 h-5" />
            </button>
          </div>
        </div>
      </Card>

      {/* Footer Support Info */}
      <div className="text-center text-xs text-gray-400 pt-10 pb-6 border-t border-gray-100 flex flex-col md:flex-row justify-between gap-4">
        <span>Copyright FranchiseCare © 2026</span>
        <div className="flex flex-col text-right">
          <span>For Mate Support, please call 03 9514 9606</span>
          <span>Monday – Friday: 9:00 AM – 10:30 PM | Saturday – Sunday: 9:00 AM – 6:00 PM</span>
        </div>
      </div>
    </div>
  )
}
