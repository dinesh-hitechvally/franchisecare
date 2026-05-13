import { useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Input } from '../../components/ui/Input'
import { Search, Filter } from 'lucide-react'
import { incomesApi } from '../../api/services'
import type { Income } from '../../types'

type IncomeReportRow = {
  id: string
  date: string
  serviceRevenue: number
  productSales: number
  other: number
  total: number
}

export function IncomeReportsPage() {
  const now = new Date()
  const defaultFrom = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10)
  const defaultTo = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10)

  const [searchTerm, setSearchTerm] = useState('')
  const [dateFrom, setDateFrom] = useState(defaultFrom)
  const [dateTo, setDateTo] = useState(defaultTo)

  const [appliedSearchTerm, setAppliedSearchTerm] = useState('')
  const [appliedDateFrom, setAppliedDateFrom] = useState(defaultFrom)
  const [appliedDateTo, setAppliedDateTo] = useState(defaultTo)

  const { data, isLoading } = useQuery({
    queryKey: ['reporting', 'income', appliedSearchTerm, appliedDateFrom, appliedDateTo],
    queryFn: () =>
      incomesApi.getPaginated({
        page: 1,
        per_page: 200,
        search: appliedSearchTerm || undefined,
        date_from: appliedDateFrom || undefined,
        date_to: appliedDateTo || undefined,
      }),
  })

  const incomes = data?.data ?? []

  const reportData = useMemo<IncomeReportRow[]>(() => {
    const byDate: Record<string, IncomeReportRow> = {}

    incomes.forEach((income: Income) => {
      const dateKey = income.income_date
      const amount = Number(income.amount || 0)
      if (!byDate[dateKey]) {
        byDate[dateKey] = {
          id: dateKey,
          date: dateKey,
          serviceRevenue: 0,
          productSales: 0,
          other: 0,
          total: 0,
        }
      }

      const categoryName = (income.category?.name || '').toLowerCase()
      if (categoryName.includes('service')) {
        byDate[dateKey].serviceRevenue += amount
      } else if (categoryName.includes('product')) {
        byDate[dateKey].productSales += amount
      } else {
        byDate[dateKey].other += amount
      }
      byDate[dateKey].total += amount
    })

    return Object.values(byDate).sort((a, b) => b.date.localeCompare(a.date))
  }, [incomes])

  const totals = useMemo(() => {
    return reportData.reduce(
      (acc, row) => {
        acc.service += Number(row.serviceRevenue || 0)
        acc.product += Number(row.productSales || 0)
        acc.other += Number(row.other || 0)
        acc.total += Number(row.total || 0)
        return acc
      },
      { service: 0, product: 0, other: 0, total: 0 }
    )
  }, [reportData])

  return (
    <div className="space-y-6 px-1 py-1 w-full">
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Income Reports</h1>
      </div>

      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <form
          onSubmit={(e) => {
            e.preventDefault()
            setAppliedSearchTerm(searchTerm)
            setAppliedDateFrom(dateFrom)
            setAppliedDateTo(dateTo)
          }}
        >
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end mb-4">
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-gray-600">From</label>
              <input
                type="date"
                value={dateFrom}
                onChange={(e) => setDateFrom(e.target.value)}
                className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500/20"
              />
            </div>
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-gray-600">To</label>
              <input
                type="date"
                value={dateTo}
                onChange={(e) => setDateTo(e.target.value)}
                className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500/20"
              />
            </div>
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-gray-600">Search</label>
              <div className="relative">
                <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                <Input
                  placeholder="Search income records..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-10"
                />
              </div>
            </div>
          </div>
          <div className="flex items-end">
            <Button
              type="submit"
              className="flex items-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-md transition-all shadow-sm font-medium text-sm"
            >
              <Filter size={18} />
              Generate Report
            </Button>
          </div>
        </form>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <p className="text-sm text-gray-500">Service Revenue</p>
          <p className="text-2xl font-bold">${totals.service.toFixed(2)}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Product Sales</p>
          <p className="text-2xl font-bold">${totals.product.toFixed(2)}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Other Income</p>
          <p className="text-2xl font-bold">${totals.other.toFixed(2)}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Total Income</p>
          <p className="text-2xl font-bold text-green-600">${totals.total.toFixed(2)}</p>
        </Card>
      </div>

      <Card>
        <div className="p-4 border-b border-gray-200 bg-gray-50">
          <h2 className="text-lg font-semibold text-gray-700">Income Summary by Date</h2>
        </div>

        <Table
          columns={[
            { key: 'date', title: 'Date' },
            { key: 'serviceRevenue', title: 'Service Revenue ($)' },
            { key: 'productSales', title: 'Product Sales ($)' },
            { key: 'other', title: 'Other ($)' },
            { key: 'total', title: 'Total ($)' },
          ]}
          data={reportData}
          keyExtractor={(row) => row.id}
          isLoading={isLoading}
          emptyMessage="No income reports found"
        />
      </Card>
    </div>
  )
}
