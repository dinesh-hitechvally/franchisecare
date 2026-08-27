import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { PageHeader } from '../../components/layout/PageHeader'
import { Repeat, Loader2, Check, X } from 'lucide-react'
import { apiClient } from '../../api/client'

interface RecurringIncome {
  id: string
  title: string
  description?: string
  category_id?: string
  category?: { name: string }
  amount: number
  start_date: string
  frequency: string
  end_date?: string
  is_active: boolean
  created_at: string
}

export function RecurringIncomePage() {
  const { data: recurringIncomes = [], isLoading } = useQuery({
    queryKey: ['recurring-incomes'],
    queryFn: async () => {
      const response = await apiClient.get<{ data: RecurringIncome[] }>('/recurring-incomes')
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

  const getRecurRule = (income: RecurringIncome) => {
    const freq = income.frequency || 'WEEKLY'
    const endDate = income.end_date ? formatDate(income.end_date) : 'ongoing'
    return `${freq.charAt(0).toUpperCase() + freq.slice(1).toLowerCase()} until ${endDate}`
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Recurring Income"
        icon={<Repeat className="w-5 h-5" />}
      />

      {isLoading ? (
        <Card className="border border-gray-200 shadow-sm bg-white p-8">
          <div className="flex items-center justify-center gap-2 text-gray-500">
            <Loader2 className="w-5 h-5 animate-spin" />
            <span>Loading recurring income...</span>
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
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Recur</th>
                <th className="px-6 py-4 font-bold text-gray-900">Active</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-right">Mgmt</th>
              </tr>
            </thead>
            <tbody>
              {recurringIncomes.length === 0 ? (
              <tr>
                <td colSpan={8} className="px-6 py-20 text-center text-gray-500 italic bg-white">
                  No Recurring Income Found
                </td>
              </tr>
              ) : (
                recurringIncomes.map((income) => (
                  <tr key={income.id} className="hover:bg-gray-50 transition-colors border-b border-gray-100">
                    <td className="px-6 py-4 text-gray-900 font-medium">{income.title}</td>
                    <td className="px-6 py-4 text-gray-500">{income.description || '-'}</td>
                    <td className="px-6 py-4 text-gray-600">{income.category?.name || '-'}</td>
                    <td className="px-6 py-4 text-gray-600 whitespace-nowrap">{formatDate(income.created_at)}</td>
                    <td className="px-6 py-4 text-gray-900 font-bold">${Number(income.amount).toFixed(2)}</td>
                    <td className="px-6 py-4 text-gray-600 text-xs italic">{getRecurRule(income)}</td>
                    <td className="px-6 py-4">
                      {income.is_active ? (
                        <Check className="w-5 h-5 text-green-500" strokeWidth={3} />
                      ) : (
                        <X className="w-5 h-5 text-gray-300" strokeWidth={3} />
                      )}
                    </td>
                    <td className="px-6 py-4 text-right">
                      <button className="text-blue-600 hover:underline font-semibold">Delete</button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </Card>
      )}
    </div>
  )
}
