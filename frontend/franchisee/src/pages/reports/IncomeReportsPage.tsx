import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import {
  BarChart,
  Bar,
  CartesianGrid,
  Cell,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { format } from 'date-fns'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Select } from '../../components/ui/Select'
import { Filter } from 'lucide-react'
import { incomeCategoriesApi, reportsApi } from '../../api/services'

type CategoryDatum = {
  name: string
  amount: number
  income_count: number
  percentage: number
  color: string
}

type TopCustomerDatum = {
  customer_id: string
  name: string
  amount: number
  income_count: number
  color: string
}

type DateRangeDatum = {
  date: string
  amount: number
  income_count: number
}

const formatCurrency = (value: number) => `$${value.toFixed(2)}`

const isIsoDate = (value: string) => /^\d{4}-\d{2}-\d{2}$/.test(value)

const formatCompactCurrency = (value: number) => {
  if (Math.abs(value) >= 1000) {
    return `$${(value / 1000).toFixed(1)}k`
  }

  return `$${value.toFixed(0)}`
}

const formatDisplayDate = (value: string) => {
  if (!isIsoDate(value)) {
    return value
  }

  const date = new Date(`${value}T00:00:00`)

  if (Number.isNaN(date.getTime())) {
    return value
  }

  return format(date, 'dd MMM yyyy')
}

const formatAxisDate = (value: string) => {
  if (!isIsoDate(value)) {
    return value
  }

  const date = new Date(`${value}T00:00:00`)

  if (Number.isNaN(date.getTime())) {
    return value
  }

  return format(date, 'dd MMM')
}

function ChartEmptyState({ label }: { label: string }) {
  return (
    <div className="flex h-[320px] items-center justify-center rounded-lg border border-dashed border-gray-200 bg-gray-50 text-sm text-gray-500">
      {label}
    </div>
  )
}

export function IncomeReportsPage() {
  const now = new Date()
  const defaultFrom = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10)
  const defaultTo = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10)

  const [dateFrom, setDateFrom] = useState(defaultFrom)
  const [dateTo, setDateTo] = useState(defaultTo)
  const [categoryId, setCategoryId] = useState('')
  const [minAmount, setMinAmount] = useState('')
  const [maxAmount, setMaxAmount] = useState('')
  const [appliedDateFrom, setAppliedDateFrom] = useState(defaultFrom)
  const [appliedDateTo, setAppliedDateTo] = useState(defaultTo)
  const [appliedCategoryId, setAppliedCategoryId] = useState('')
  const [appliedMinAmount, setAppliedMinAmount] = useState('')
  const [appliedMaxAmount, setAppliedMaxAmount] = useState('')

  const { data: categories = [] } = useQuery({
    queryKey: ['income-categories'],
    queryFn: () => incomeCategoriesApi.getAll(),
  })

  const { data, isLoading } = useQuery({
    queryKey: ['reporting', 'income', appliedDateFrom, appliedDateTo, appliedCategoryId, appliedMinAmount, appliedMaxAmount],
    queryFn: () =>
      reportsApi.getIncome({
        date_from: appliedDateFrom || undefined,
        date_to: appliedDateTo || undefined,
        category_id: appliedCategoryId || undefined,
        min: appliedMinAmount || undefined,
        max: appliedMaxAmount || undefined,
      }),
  })

  const categoryOptions = [
    { label: 'All Categories', value: '' },
    ...categories.map((category) => ({
      label: category.name,
      value: String(category.id),
    })),
  ]

  const summary = data?.summary ?? {
    total_income: 0,
    income_count: 0,
    category_count: 0,
    customer_count: 0,
  }
  const categoryData = data?.category_data ?? []
  const topCustomerData = data?.top_customer_data ?? []
  const dateRangeData = data?.date_range_data ?? []
  const categoryTotals = {
    income_count: categoryData.reduce((sum: number, row: CategoryDatum) => sum + row.income_count, 0),
    percentage: categoryData.reduce((sum: number, row: CategoryDatum) => sum + row.percentage, 0),
    amount: categoryData.reduce((sum: number, row: CategoryDatum) => sum + row.amount, 0),
  }
  const topCustomerTotals = {
    income_count: topCustomerData.reduce((sum: number, row: TopCustomerDatum) => sum + row.income_count, 0),
    amount: topCustomerData.reduce((sum: number, row: TopCustomerDatum) => sum + row.amount, 0),
  }
  const dateRangeTotals = {
    income_count: dateRangeData.reduce((sum: number, row: DateRangeDatum) => sum + row.income_count, 0),
    amount: dateRangeData.reduce((sum: number, row: DateRangeDatum) => sum + row.amount, 0),
  }

  return (
    <div className="space-y-6 px-1 py-1 w-full">
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Income Reports</h1>
      </div>

      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <form
          onSubmit={(e) => {
            e.preventDefault()
            setAppliedDateFrom(dateFrom)
            setAppliedDateTo(dateTo)
            setAppliedCategoryId(categoryId)
            setAppliedMinAmount(minAmount)
            setAppliedMaxAmount(maxAmount)
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
              <label className="text-sm font-medium text-gray-600">Category</label>
              <Select
                options={categoryOptions}
                value={categoryId}
                onChange={(value) => setCategoryId(String(value))}
                placeholder="All Categories"
              />
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-gray-600">Min</label>
              <Input
                type="number"
                placeholder="Min value"
                value={minAmount}
                onChange={(e) => setMinAmount(e.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-gray-600">Max</label>
              <Input
                type="number"
                placeholder="Max value"
                value={maxAmount}
                onChange={(e) => setMaxAmount(e.target.value)}
              />
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
          </div>
        </form>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <p className="text-sm text-gray-500">Total Income</p>
          <p className="text-2xl font-bold text-green-600">{formatCurrency(summary.total_income)}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Income Entries</p>
          <p className="text-2xl font-bold">{summary.income_count}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Income Categories</p>
          <p className="text-2xl font-bold">{summary.category_count}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Top Customers</p>
          <p className="text-2xl font-bold">{summary.customer_count}</p>
        </Card>
      </div>

      <Card className="overflow-hidden">
        <div className="border-b border-gray-200 bg-gray-50 px-4 py-4">
          <h2 className="text-lg font-semibold text-gray-700">Income Categories</h2>
        </div>
        <div className="grid grid-cols-1 gap-6 p-6 lg:grid-cols-2">
          <div>
            {isLoading ? (
              <ChartEmptyState label="Loading income categories..." />
            ) : categoryData.length === 0 ? (
              <ChartEmptyState label="No category data found for this date range." />
            ) : (
              <div className="h-[320px]">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={categoryData} margin={{ top: 10, right: 20, left: 0, bottom: 40 }}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e5e7eb" />
                    <XAxis
                      dataKey="name"
                      type="category"
                      interval={0}
                      height={60}
                      tick={{ fontSize: 12 }}
                      angle={-20}
                      textAnchor="end"
                      label={{ value: 'Category Name', position: 'insideBottom', offset: -4, style: { fill: '#6b7280', fontSize: 12 } }}
                    />
                    <YAxis
                      tickFormatter={formatCompactCurrency}
                      width={70}
                      label={{ value: 'Amount', angle: -90, position: 'insideLeft', style: { fill: '#6b7280', fontSize: 12 } }}
                    />
                    <Tooltip formatter={(value: number) => formatCurrency(Number(value))} />
                    <Bar dataKey="amount" radius={[8, 8, 0, 0]}>
                      {categoryData.map((entry: CategoryDatum) => (
                        <Cell key={entry.name} fill={entry.color} />
                      ))}
                    </Bar>
                  </BarChart>
                </ResponsiveContainer>
              </div>
            )}
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead>
                <tr className="border-b border-gray-200 text-gray-600">
                  <th className="px-4 py-3 font-semibold">Category</th>
                  <th className="px-4 py-3 font-semibold text-right">Entries</th>
                  <th className="px-4 py-3 font-semibold text-right">Share</th>
                  <th className="px-4 py-3 font-semibold text-right">Amount</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {isLoading ? (
                  <tr>
                    <td colSpan={4} className="px-4 py-8 text-center text-gray-500">Loading income categories...</td>
                  </tr>
                ) : categoryData.length === 0 ? (
                  <tr>
                    <td colSpan={4} className="px-4 py-8 text-center text-gray-500">No category data found.</td>
                  </tr>
                ) : (
                  categoryData.map((row: CategoryDatum) => (
                    <tr key={row.name} className="hover:bg-gray-50">
                      <td className="px-4 py-3 font-medium text-gray-800">
                        <div className="flex items-center gap-3">
                          <span className="h-3 w-3 rounded-full" style={{ backgroundColor: row.color }} />
                          {row.name}
                        </div>
                      </td>
                      <td className="px-4 py-3 text-right text-gray-700">{row.income_count}</td>
                      <td className="px-4 py-3 text-right text-gray-700">{row.percentage.toFixed(2)}%</td>
                      <td className="px-4 py-3 text-right font-semibold text-gray-900">{formatCurrency(row.amount)}</td>
                    </tr>
                  ))
                )}
                {!isLoading && categoryData.length > 0 && (
                  <tr className="border-t-2 border-gray-200 bg-gray-50 font-semibold">
                    <td className="px-4 py-3 text-gray-900">Total</td>
                    <td className="px-4 py-3 text-right text-gray-900">{categoryTotals.income_count}</td>
                    <td className="px-4 py-3 text-right text-gray-900">{categoryTotals.percentage.toFixed(2)}%</td>
                    <td className="px-4 py-3 text-right text-gray-900">{formatCurrency(categoryTotals.amount)}</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </Card>

      <Card className="overflow-hidden">
        <div className="border-b border-gray-200 bg-gray-50 px-4 py-4">
          <h2 className="text-lg font-semibold text-gray-700">Highest Income from Customers (Top 10)</h2>
        </div>
        <div className="grid grid-cols-1 gap-6 p-6 lg:grid-cols-2">
          <div>
            {isLoading ? (
              <ChartEmptyState label="Loading top customers..." />
            ) : topCustomerData.length === 0 ? (
              <ChartEmptyState label="No customer-linked income found for this date range." />
            ) : (
              <div className="h-[320px]">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={topCustomerData} margin={{ top: 10, right: 20, left: 0, bottom: 40 }}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e5e7eb" />
                    <XAxis
                      dataKey="name"
                      type="category"
                      interval={0}
                      height={60}
                      tick={{ fontSize: 12 }}
                      angle={-20}
                      textAnchor="end"
                      label={{ value: 'Customer Name', position: 'insideBottom', offset: -4, style: { fill: '#6b7280', fontSize: 12 } }}
                    />
                    <YAxis
                      tickFormatter={formatCompactCurrency}
                      width={70}
                      label={{ value: 'Amount', angle: -90, position: 'insideLeft', style: { fill: '#6b7280', fontSize: 12 } }}
                    />
                    <Tooltip formatter={(value: number) => formatCurrency(Number(value))} />
                    <Bar dataKey="amount" radius={[8, 8, 0, 0]}>
                      {topCustomerData.map((entry: TopCustomerDatum) => (
                        <Cell key={entry.customer_id || entry.name} fill={entry.color} />
                      ))}
                    </Bar>
                  </BarChart>
                </ResponsiveContainer>
              </div>
            )}
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead>
                <tr className="border-b border-gray-200 text-gray-600">
                  <th className="px-4 py-3 font-semibold">Customer</th>
                  <th className="px-4 py-3 font-semibold text-right">Entries</th>
                  <th className="px-4 py-3 font-semibold text-right">Income</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {isLoading ? (
                  <tr>
                    <td colSpan={3} className="px-4 py-8 text-center text-gray-500">Loading top customers...</td>
                  </tr>
                ) : topCustomerData.length === 0 ? (
                  <tr>
                    <td colSpan={3} className="px-4 py-8 text-center text-gray-500">No customer-linked income found.</td>
                  </tr>
                ) : (
                  topCustomerData.map((row: TopCustomerDatum, index: number) => (
                    <tr key={row.customer_id || `${row.name}-${index}`} className="hover:bg-gray-50">
                      <td className="px-4 py-3 font-medium text-gray-800">{row.name}</td>
                      <td className="px-4 py-3 text-right text-gray-700">{row.income_count}</td>
                      <td className="px-4 py-3 text-right font-semibold text-gray-900">{formatCurrency(row.amount)}</td>
                    </tr>
                  ))
                )}
                {!isLoading && topCustomerData.length > 0 && (
                  <tr className="border-t-2 border-gray-200 bg-gray-50 font-semibold">
                    <td className="px-4 py-3 text-gray-900">Total</td>
                    <td className="px-4 py-3 text-right text-gray-900">{topCustomerTotals.income_count}</td>
                    <td className="px-4 py-3 text-right text-gray-900">{formatCurrency(topCustomerTotals.amount)}</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </Card>

      <Card className="overflow-hidden">
        <div className="border-b border-gray-200 bg-gray-50 px-4 py-4">
          <h2 className="text-lg font-semibold text-gray-700">Income by Date Range</h2>
        </div>
        <div className="grid grid-cols-1 gap-6 p-6 lg:grid-cols-2">
          <div>
            {isLoading ? (
              <ChartEmptyState label="Loading date range income..." />
            ) : dateRangeData.length === 0 ? (
              <ChartEmptyState label="No date range income found for this period." />
            ) : (
              <div className="h-[320px]">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={dateRangeData} margin={{ top: 10, right: 20, left: 0, bottom: 30 }}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e5e7eb" />
                    <XAxis dataKey="date" tickFormatter={formatAxisDate} minTickGap={24} />
                    <YAxis tickFormatter={formatCompactCurrency} width={70} />
                    <Tooltip
                      labelFormatter={(label: string) => formatDisplayDate(label)}
                      formatter={(value: number) => formatCurrency(Number(value))}
                    />
                    <Bar dataKey="amount" fill="#0f766e" radius={[8, 8, 0, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            )}
          </div>
          <div className="max-h-[320px] overflow-auto">
            <table className="w-full text-left text-sm">
              <thead className="sticky top-0 bg-white">
                <tr className="border-b border-gray-200 text-gray-600">
                  <th className="px-4 py-3 font-semibold">Date</th>
                  <th className="px-4 py-3 font-semibold text-right">Entries</th>
                  <th className="px-4 py-3 font-semibold text-right">Income</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {isLoading ? (
                  <tr>
                    <td colSpan={3} className="px-4 py-8 text-center text-gray-500">Loading date range income...</td>
                  </tr>
                ) : dateRangeData.length === 0 ? (
                  <tr>
                    <td colSpan={3} className="px-4 py-8 text-center text-gray-500">No date range income found.</td>
                  </tr>
                ) : (
                  dateRangeData.map((row: DateRangeDatum) => (
                    <tr key={row.date} className="hover:bg-gray-50">
                      <td className="px-4 py-3 font-medium text-gray-800">{formatDisplayDate(row.date)}</td>
                      <td className="px-4 py-3 text-right text-gray-700">{row.income_count}</td>
                      <td className="px-4 py-3 text-right font-semibold text-gray-900">{formatCurrency(row.amount)}</td>
                    </tr>
                  ))
                )}
                {!isLoading && dateRangeData.length > 0 && (
                  <tr className="border-t-2 border-gray-200 bg-gray-50 font-semibold">
                    <td className="px-4 py-3 text-gray-900">Total</td>
                    <td className="px-4 py-3 text-right text-gray-900">{dateRangeTotals.income_count}</td>
                    <td className="px-4 py-3 text-right text-gray-900">{formatCurrency(dateRangeTotals.amount)}</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </Card>
    </div>
  )
}
