import { useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Input } from '../../components/ui/Input'
import { Search, Filter, PieChart } from 'lucide-react'
import { expensesApi } from '../../api/services'
import type { Expense } from '../../types'
import { PageHeader } from '../../components/layout/PageHeader'

type ExpenseReportRow = {
  id: string
  date: string
  supplies: number
  utilities: number
  marketing: number
  wages: number
  other: number
  total: number
}

export function ExpenseReportsPage() {
  const [searchTerm, setSearchTerm] = useState('')
  const [dateFrom, setDateFrom] = useState('')
  const [dateTo, setDateTo] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['reporting', 'expenses', searchTerm, dateFrom, dateTo],
    queryFn: () =>
      expensesApi.getPaginated({
        page: 1,
        per_page: 200,
        search: searchTerm || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
      }),
  })

  const expenses = data?.data ?? []

  const reportData = useMemo<ExpenseReportRow[]>(() => {
    const byDate: Record<string, ExpenseReportRow> = {}

    expenses.forEach((expense: Expense) => {
      const dateKey = expense.expense_date
      if (!byDate[dateKey]) {
        byDate[dateKey] = {
          id: dateKey,
          date: dateKey,
          supplies: 0,
          utilities: 0,
          marketing: 0,
          wages: 0,
          other: 0,
          total: 0,
        }
      }

      const categoryName = (expense.category?.name || '').toLowerCase()
      if (categoryName.includes('suppl')) {
        byDate[dateKey].supplies += expense.amount
      } else if (categoryName.includes('util')) {
        byDate[dateKey].utilities += expense.amount
      } else if (categoryName.includes('market')) {
        byDate[dateKey].marketing += expense.amount
      } else if (categoryName.includes('wage') || categoryName.includes('salary')) {
        byDate[dateKey].wages += expense.amount
      } else {
        byDate[dateKey].other += expense.amount
      }
      byDate[dateKey].total += expense.amount
    })

    return Object.values(byDate).sort((a, b) => b.date.localeCompare(a.date))
  }, [expenses])

  const totals = useMemo(() => {
    return reportData.reduce(
      (acc, row) => {
        acc.supplies += row.supplies
        acc.utilities += row.utilities
        acc.marketing += row.marketing
        acc.total += row.total
        return acc
      },
      { supplies: 0, utilities: 0, marketing: 0, total: 0 }
    )
  }, [reportData])

  return (
    <div className="space-y-6">
      <PageHeader
        title="Expense Reports"
        description="Track and analyze your business expenses"
        icon={<PieChart size={20} />}
        actions={
          <Button variant="secondary" size="sm">
            <Filter className="w-4 h-4 mr-2" />
            Filter
          </Button>
        }
      />

      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <p className="text-sm text-gray-500">Supplies</p>
          <p className="text-2xl font-bold">${totals.supplies.toFixed(2)}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Utilities</p>
          <p className="text-2xl font-bold">${totals.utilities.toFixed(2)}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Marketing</p>
          <p className="text-2xl font-bold">${totals.marketing.toFixed(2)}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Total Expenses</p>
          <p className="text-2xl font-bold text-red-600">${totals.total.toFixed(2)}</p>
        </Card>
      </div>

      <Card>
        <div className="p-4 border-b border-gray-200">
          <div className="flex flex-col xl:flex-row xl:items-end gap-4">
            <div className="flex-1 relative">
              <Search className="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <Input
                placeholder="Search reports..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10"
              />
            </div>
            <input
              type="date"
              value={dateFrom}
              onChange={(e) => setDateFrom(e.target.value)}
              className="input xl:w-48"
            />
            <input
              type="date"
              value={dateTo}
              onChange={(e) => setDateTo(e.target.value)}
              className="input xl:w-48"
            />
          </div>
        </div>

        <Table
          columns={[
            { key: 'date', title: 'Date' },
            { key: 'supplies', title: 'Supplies ($)' },
            { key: 'utilities', title: 'Utilities ($)' },
            { key: 'marketing', title: 'Marketing ($)' },
            { key: 'wages', title: 'Wages ($)' },
            { key: 'total', title: 'Total ($)' },
          ]}
          data={reportData}
          keyExtractor={(row) => row.id}
          isLoading={isLoading}
          emptyMessage="No expense reports found"
        />
      </Card>
    </div>
  )
}
