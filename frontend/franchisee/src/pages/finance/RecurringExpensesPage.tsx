import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { PageHeader } from '../../components/layout/PageHeader'
import { Check, ChevronLeft, ChevronRight, Repeat, Loader2, X } from 'lucide-react'
import { apiClient } from '../../api/client'

interface RecurringExpense {
  id: string
  title: string
  description?: string
  category_id?: string
  category?: { name: string; type?: string }
  amount: number
  start_date: string
  frequency: string
  next_date?: string
  end_date?: string
  is_active: boolean
  created_at: string
}

export function RecurringExpensesPage() {
  const { data: recurringExpenses = [], isLoading } = useQuery({
    queryKey: ['recurring-expenses'],
    queryFn: async () => {
      const response = await apiClient.get<{ data: RecurringExpense[] }>('/recurring-expenses')
      return response.data
    },
  })

  const formatDate = (dateStr: string) => {
    if (!dateStr) return '-'
    const date = new Date(dateStr)
    const day = date.getDate()
    const suffix = day === 1 || day === 21 || day === 31 ? 'st' : day === 2 || day === 22 ? 'nd' : day === 3 || day === 23 ? 'rd' : 'th'
    return `${day}${suffix} ${date.toLocaleDateString('en-AU', { month: 'short', year: 'numeric' })}`
  }

  const getRecurRule = (exp: RecurringExpense) => {
    const freq = exp.frequency || 'WEEKLY'
    const endDate = exp.end_date ? formatDate(exp.end_date) : 'ongoing'
    return `${freq.charAt(0).toUpperCase() + freq.slice(1).toLowerCase()} until ${endDate}`
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Recurring Expenses"
        icon={<Repeat className="w-5 h-5" />}
      />

      {isLoading ? (
        <Card className="border border-gray-200 shadow-sm bg-white p-8">
          <div className="flex items-center justify-center gap-2 text-gray-500">
            <Loader2 className="w-5 h-5 animate-spin" />
            <span>Loading recurring expenses...</span>
          </div>
        </Card>
      ) : (
      <Card className="border border-gray-200 shadow-sm overflow-hidden bg-white">
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left border-collapse">
            <thead className="bg-white border-b border-gray-200">
              <tr>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Title</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Description</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Category</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Created</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Amount</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Recur Start</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Recur</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100 text-center">Active</th>
                <th className="px-6 py-4 font-bold text-gray-900">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100 bg-white">
              {recurringExpenses.length === 0 ? (
                <tr>
                  <td colSpan={9} className="px-6 py-8 text-center text-gray-500">
                    No recurring expenses found
                  </td>
                </tr>
              ) : (
              recurringExpenses.map((exp) => (
                <tr key={exp.id} className="hover:bg-gray-50 transition-colors">
                  <td className="px-6 py-4 text-gray-900 font-medium">{exp.title}</td>
                  <td className="px-6 py-4 text-gray-500">{exp.description || '-'}</td>
                  <td className="px-6 py-4 text-gray-600">{exp.category?.name || '-'}</td>
                  <td className="px-6 py-4 text-gray-600 whitespace-nowrap">{formatDate(exp.created_at)}</td>
                  <td className="px-6 py-4 text-gray-900 font-bold">${Number(exp.amount).toFixed(2)}</td>
                  <td className="px-6 py-4 text-gray-600 whitespace-nowrap">{formatDate(exp.start_date)}</td>
                  <td className="px-6 py-4 text-gray-600 text-xs italic">{getRecurRule(exp)}</td>
                  <td className="px-6 py-4">
                    <div className="flex justify-center">
                      {exp.is_active ? (
                        <Check className="w-5 h-5 text-green-500" strokeWidth={3} />
                      ) : (
                        <X className="w-5 h-5 text-gray-300" strokeWidth={3} />
                      )}
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <button className="text-blue-600 hover:underline font-semibold">Delete</button>
                  </td>
                </tr>
              ))
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        <div className="px-6 py-4 bg-white border-t border-gray-100 flex items-center justify-end gap-6">
          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-500">Rows per page:</span>
            <select className="bg-transparent border-none text-sm font-medium focus:ring-0 outline-none cursor-pointer" defaultValue="25">
              <option>25</option>
              <option>50</option>
              <option>100</option>
            </select>
          </div>
          <span className="text-sm text-gray-600">1-{recurringExpenses.length} of {recurringExpenses.length}</span>
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
      )}
    </div>
  )
}
