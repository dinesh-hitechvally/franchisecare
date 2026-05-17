import { useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import {
  PieChart, Pie, Cell,
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer,
} from 'recharts'
import { Filter, Download, FileText, TrendingUp } from 'lucide-react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Select } from '../../components/ui/Select'
import { PageHeader } from '../../components/layout/PageHeader'
import { customerReportsApi, customersApi, unbookedCustomerReportsApi } from '../../api/services'

export function CustomerReportsPage() {
  type CustomerReportTab = 'booked' | 'unbooked' | 'created' | 'referral'

  const now = new Date()
  const defaultFrom = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10)
  const defaultTo = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10)

  const [fromDate, setFromDate] = useState(defaultFrom)
  const [toDate, setToDate] = useState(defaultTo)
  const [customerId, setCustomerId] = useState('')
  const [min, setMin] = useState('')
  const [max, setMax] = useState('')

  const [appliedFromDate, setAppliedFromDate] = useState(defaultFrom)
  const [appliedToDate, setAppliedToDate] = useState(defaultTo)
  const [appliedCustomerId, setAppliedCustomerId] = useState('')
  const [appliedMin, setAppliedMin] = useState('')
  const [appliedMax, setAppliedMax] = useState('')

  const [activeTab, setActiveTab] = useState<CustomerReportTab>('booked')

  const [unbookedFromDate, setUnbookedFromDate] = useState(defaultFrom)
  const [unbookedToDate, setUnbookedToDate] = useState(defaultTo)
  const [unbookedCustomerId, setUnbookedCustomerId] = useState('')
  const [unbookedMin, setUnbookedMin] = useState('')
  const [unbookedMax, setUnbookedMax] = useState('')
  const [unbookedNumberOfPets, setUnbookedNumberOfPets] = useState('')
  const [unbookedPhone, setUnbookedPhone] = useState('')
  const [unbookedState, setUnbookedState] = useState('')

  const [appliedUnbookedFromDate, setAppliedUnbookedFromDate] = useState(defaultFrom)
  const [appliedUnbookedToDate, setAppliedUnbookedToDate] = useState(defaultTo)
  const [appliedUnbookedCustomerId, setAppliedUnbookedCustomerId] = useState('')
  const [appliedUnbookedMin, setAppliedUnbookedMin] = useState('')
  const [appliedUnbookedMax, setAppliedUnbookedMax] = useState('')
  const [appliedUnbookedNumberOfPets, setAppliedUnbookedNumberOfPets] = useState('')
  const [appliedUnbookedPhone, setAppliedUnbookedPhone] = useState('')
  const [appliedUnbookedState, setAppliedUnbookedState] = useState('')

  const [createdFromDate, setCreatedFromDate] = useState(defaultFrom)
  const [createdToDate, setCreatedToDate] = useState(defaultTo)
  const [createdStatus, setCreatedStatus] = useState('')
  const [createdNumberOfPets, setCreatedNumberOfPets] = useState('')
  const [createdSearch, setCreatedSearch] = useState('')

  const [appliedCreatedFromDate, setAppliedCreatedFromDate] = useState(defaultFrom)
  const [appliedCreatedToDate, setAppliedCreatedToDate] = useState(defaultTo)
  const [appliedCreatedStatus, setAppliedCreatedStatus] = useState('')
  const [appliedCreatedNumberOfPets, setAppliedCreatedNumberOfPets] = useState('')
  const [appliedCreatedSearch, setAppliedCreatedSearch] = useState('')

  const [referralFromDate, setReferralFromDate] = useState(defaultFrom)
  const [referralToDate, setReferralToDate] = useState(defaultTo)
  const [referrerCustomerId, setReferrerCustomerId] = useState('')

  const [appliedReferralFromDate, setAppliedReferralFromDate] = useState(defaultFrom)
  const [appliedReferralToDate, setAppliedReferralToDate] = useState(defaultTo)
  const [appliedReferrerCustomerId, setAppliedReferrerCustomerId] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['customer-report', appliedFromDate, appliedToDate, appliedCustomerId, appliedMin, appliedMax],
    queryFn: () =>
      customerReportsApi.getReport({
        date_from: appliedFromDate,
        date_to: appliedToDate,
        customer_id: appliedCustomerId || undefined,
        min: appliedMin || undefined,
        max: appliedMax || undefined,
      }),
  })

  const { data: customerOptionsData = [] } = useQuery({
    queryKey: ['customer-report-options'],
    queryFn: () => customersApi.getAll(),
  })

  const { data: unbookedData, isLoading: isUnbookedLoading } = useQuery({
    queryKey: [
      'unbooked-customer-report',
      appliedUnbookedFromDate,
      appliedUnbookedToDate,
      appliedUnbookedCustomerId,
      appliedUnbookedMin,
      appliedUnbookedMax,
      appliedUnbookedNumberOfPets,
      appliedUnbookedPhone,
      appliedUnbookedState,
    ],
    queryFn: () =>
      unbookedCustomerReportsApi.getReport({
        date_from: appliedUnbookedFromDate,
        date_to: appliedUnbookedToDate,
        customer_id: appliedUnbookedCustomerId || undefined,
        min: appliedUnbookedMin || undefined,
        max: appliedUnbookedMax || undefined,
        number_of_pets: appliedUnbookedNumberOfPets || undefined,
        phone: appliedUnbookedPhone || undefined,
        state: appliedUnbookedState || undefined,
      }),
    enabled: activeTab === 'unbooked',
  })

  const customerData = data?.data || []
  const rows = data?.weekly_data || []
  const summary = data?.summary || { total_customer_services: 0, total_revenue: 0 }

  const customerOptions = useMemo(() => {
    const options = customerOptionsData.map((customer: any) => ({
      label: `${customer.first_name} ${customer.last_name}`,
      value: String(customer.id),
    }))

    return [{ label: 'All Customers', value: '' }, ...options]
  }, [customerOptionsData])

  const unbookedRows = unbookedData?.data || []

  const createdRows = useMemo(() => {
    return customerOptionsData
      .filter((customer: any) => {
        const createdAtRaw = customer.created_at
        const createdAtTs = createdAtRaw ? new Date(createdAtRaw).getTime() : NaN

        if (!Number.isNaN(createdAtTs)) {
          const fromTs = new Date(`${appliedCreatedFromDate}T00:00:00`).getTime()
          const toTs = new Date(`${appliedCreatedToDate}T23:59:59`).getTime()

          if (!Number.isNaN(fromTs) && createdAtTs < fromTs) {
            return false
          }

          if (!Number.isNaN(toTs) && createdAtTs > toTs) {
            return false
          }
        }

        const status = customer.is_archived
          ? 'archived'
          : customer.is_active === false
            ? 'inactive'
            : 'active'

        if (appliedCreatedStatus && status !== appliedCreatedStatus) {
          return false
        }

        if (appliedCreatedNumberOfPets !== '') {
          const petsCount = Array.isArray(customer.pets) ? customer.pets.length : 0
          if (petsCount !== Number(appliedCreatedNumberOfPets)) {
            return false
          }
        }

        if (appliedCreatedSearch.trim()) {
          const text = appliedCreatedSearch.trim().toLowerCase()
          const haystack = [
            customer.id,
            customer.first_name,
            customer.last_name,
            customer.email,
            customer.phone,
            customer.other_phone,
            customer.address,
          ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()

          if (!haystack.includes(text)) {
            return false
          }
        }

        return true
      })
      .map((customer: any) => {
        const statusLabel = customer.is_archived
          ? 'Archived'
          : customer.is_active === false
            ? 'Inactive'
            : 'Active'

        return {
          id: String(customer.id ?? '-'),
          name: `${customer.first_name ?? ''} ${customer.last_name ?? ''}`.trim() || '-',
          email: customer.email || '-',
          mobile: customer.phone || customer.other_phone || '-',
          address: customer.address || '-',
          status: statusLabel,
          registeredDate: customer.created_at || '-',
        }
      })
  }, [
    customerOptionsData,
    appliedCreatedFromDate,
    appliedCreatedToDate,
    appliedCreatedStatus,
    appliedCreatedNumberOfPets,
    appliedCreatedSearch,
  ])

  const tabOptions: Array<{ key: CustomerReportTab; label: string }> = [
    { key: 'booked', label: 'Booked Customer Report' },
    { key: 'unbooked', label: 'Unbooked Customer Report' },
    { key: 'created', label: 'Customer Created Report' },
    { key: 'referral', label: 'Referral Customer Reports' },
  ]

  const applyReferralFilters = () => {
    setAppliedReferralFromDate(referralFromDate)
    setAppliedReferralToDate(referralToDate)
    setAppliedReferrerCustomerId(referrerCustomerId)
  }

  const referralColumns = useMemo(() => {
    const values = (customerOptionsData || [])
      .map((customer: any) => String(customer.referred_by ?? '').trim())
      .filter((value: string) => value !== '')

    return Array.from(new Set(values)).sort((a, b) => a.localeCompare(b))
  }, [customerOptionsData])

  const referralRows = useMemo(() => {
    const start = new Date(`${appliedReferralFromDate}T00:00:00`)
    const end = new Date(`${appliedReferralToDate}T23:59:59`)

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || start > end) {
      return []
    }

    const selectedReferrer = (customerOptionsData || []).find(
      (customer: any) => String(customer.id) === String(appliedReferrerCustomerId),
    )

    const possibleReferrerValues = selectedReferrer
      ? [
          String(selectedReferrer.id ?? '').trim(),
          String(selectedReferrer.reference_id ?? '').trim(),
          `${selectedReferrer.first_name ?? ''} ${selectedReferrer.last_name ?? ''}`.trim(),
        ].filter(Boolean)
      : []

    const relevantCustomers = (customerOptionsData || []).filter((customer: any) => {
      const createdAt = customer.created_at ? new Date(customer.created_at) : null
      if (!createdAt || Number.isNaN(createdAt.getTime())) {
        return false
      }

      if (createdAt < start || createdAt > end) {
        return false
      }

      if (appliedReferrerCustomerId) {
        const referredBy = String(customer.referred_by ?? '').trim()
        if (!referredBy) {
          return false
        }
        return possibleReferrerValues.includes(referredBy)
      }

      return true
    })

    const rows: Array<Record<string, any>> = []
    let weekStart = new Date(start)

    const formatDate = (date: Date) =>
      date.toLocaleDateString('en-AU', { day: 'numeric', month: 'short', year: 'numeric' })

    while (weekStart <= end) {
      const weekEnd = new Date(weekStart)
      weekEnd.setDate(weekEnd.getDate() + 6)
      if (weekEnd > end) {
        weekEnd.setTime(end.getTime())
      }

      const row: Record<string, any> = {
        weekRange: `${formatDate(weekStart)} - ${formatDate(weekEnd)}`,
        nonReferred: relevantCustomers.filter((customer: any) => {
          const createdAt = customer.created_at ? new Date(customer.created_at) : null
          if (!createdAt || Number.isNaN(createdAt.getTime())) {
            return false
          }
          return (
            createdAt >= weekStart &&
            createdAt <= weekEnd &&
            String(customer.referred_by ?? '').trim() === ''
          )
        }).length,
      }

      referralColumns.forEach((column) => {
        row[column] = relevantCustomers.filter((customer: any) => {
          const createdAt = customer.created_at ? new Date(customer.created_at) : null
          if (!createdAt || Number.isNaN(createdAt.getTime())) {
            return false
          }
          return (
            createdAt >= weekStart &&
            createdAt <= weekEnd &&
            String(customer.referred_by ?? '').trim() === column
          )
        }).length
      })

      rows.push(row)

      const nextWeek = new Date(weekEnd)
      nextWeek.setDate(nextWeek.getDate() + 1)
      weekStart = nextWeek
    }

    return rows
  }, [
    customerOptionsData,
    appliedReferralFromDate,
    appliedReferralToDate,
    appliedReferrerCustomerId,
    referralColumns,
  ])

  return (
    <div className="space-y-6 px-1 py-1 w-full">
      <PageHeader
        title="Customer Reports"
        description="Track customer booking patterns and revenue"
        icon={<TrendingUp size={20} />}
        actions={
          <div className="flex gap-2">
            <button className="flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors text-xs font-medium">
              <Download size={14} /> Export CSV
            </button>
            <button className="flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors text-xs font-medium">
              <FileText size={14} /> PDF Report
            </button>
          </div>
        }
      />

      <Card className="p-2">
        <div className="flex flex-wrap gap-2">
          {tabOptions.map((tab) => (
            <button
              key={tab.key}
              type="button"
              onClick={() => setActiveTab(tab.key)}
              className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${
                activeTab === tab.key
                  ? 'bg-primary-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              {tab.label}
            </button>
          ))}
        </div>
      </Card>

      {activeTab === 'booked' && (
        <>

      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <form
          onSubmit={(e) => {
            e.preventDefault()
            setAppliedFromDate(fromDate)
            setAppliedToDate(toDate)
            setAppliedCustomerId(customerId)
            setAppliedMin(min)
            setAppliedMax(max)
          }}
        >
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end mb-4">
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-gray-600">From</label>
              <input
                type="date"
                value={fromDate}
                onChange={(e) => setFromDate(e.target.value)}
                className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500/20"
              />
            </div>
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-gray-600">To</label>
              <input
                type="date"
                value={toDate}
                onChange={(e) => setToDate(e.target.value)}
                className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500/20"
              />
            </div>
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-gray-600">Customers</label>
              <Select
                options={customerOptions}
                value={customerId}
                onChange={setCustomerId}
                placeholder="All Customers"
              />
            </div>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-gray-600">Min</label>
              <Input
                type="number"
                placeholder="Min value"
                value={min}
                onChange={(e) => setMin(e.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-gray-600">Max</label>
              <Input
                type="number"
                placeholder="Max value"
                value={max}
                onChange={(e) => setMax(e.target.value)}
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

      <Card>
        <div className="p-4 border-b border-gray-200 bg-gray-50">
          <h2 className="text-lg font-semibold text-gray-700">All my customers</h2>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
          <div className="flex items-center justify-center">
            <ResponsiveContainer width="100%" height={300}>
              <PieChart>
                <Pie
                  data={customerData}
                  cx="50%"
                  cy="50%"
                  outerRadius={80}
                  fill="#8884d8"
                  dataKey="value"
                >
                  {customerData.map((entry: any, index: number) => (
                    <Cell key={`cell-${index}`} fill={entry.color} />
                  ))}
                </Pie>
                <Tooltip />
                <Legend />
              </PieChart>
            </ResponsiveContainer>
          </div>

          <div>
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse text-sm">
                <thead>
                  <tr className="border-b border-gray-200">
                    <th className="px-4 py-3 font-semibold text-gray-700">Customer</th>
                    <th className="px-4 py-3 font-semibold text-gray-700 text-right">Count</th>
                    <th className="px-4 py-3 font-semibold text-gray-700 text-right">Total Booking Amount</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {customerData.map((entry: any, index: number) => (
                    <tr key={`${entry.name}-${index}`} className="hover:bg-gray-50">
                      <td className="px-4 py-3 text-gray-800 font-medium">{entry.name}</td>
                      <td className="px-4 py-3 text-right font-semibold" style={{ color: entry.color }}>{entry.value}</td>
                      <td className="px-4 py-3 text-right text-gray-700 font-semibold">${Number(entry.total_booking_amount ?? entry.amount ?? 0).toLocaleString()}</td>
                    </tr>
                  ))}
                  {!isLoading && customerData.length === 0 && (
                    <tr>
                      <td colSpan={3} className="px-4 py-6 text-center text-gray-500">No data found for selected filters.</td>
                    </tr>
                  )}
                  {customerData.length > 0 && (
                    <tr className="border-t-2 border-gray-300 bg-gray-50">
                      <td className="px-4 py-3 font-bold text-gray-800">Total</td>
                      <td className="px-4 py-3 text-right font-bold text-gray-800">{customerData.reduce((sum: number, entry: any) => sum + entry.value, 0)}</td>
                      <td className="px-4 py-3 text-right font-bold text-gray-800">${Number(customerData.reduce((sum: number, entry: any) => sum + (entry.total_booking_amount ?? 0), 0)).toLocaleString()}</td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </Card>

      <Card>
        <div className="p-4 border-b border-gray-200 bg-gray-50">
          <h2 className="text-lg font-semibold text-gray-700">Customer Comparision</h2>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
          <div className="flex items-center justify-center">
            <ResponsiveContainer width="100%" height={320}>
              <BarChart data={rows} margin={{ top: 10, right: 30, left: 0, bottom: 60 }}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="weekRange" angle={-45} textAnchor="end" height={80} />
                <YAxis />
                <Tooltip />
                <Legend />
                <Bar dataKey="myCustomerBookings" fill="#8884d8" name="My Bookings" />
                <Bar dataKey="maxCustomerBookings" fill="#82ca9d" name="Max Bookings" />
              </BarChart>
            </ResponsiveContainer>
          </div>

          <div>
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse text-sm">
                <thead>
                  <tr className="border-b border-gray-200">
                    <th className="px-4 py-3 font-semibold text-gray-700">Week</th>
                    <th className="px-4 py-3 font-semibold text-gray-700">My Bookings</th>
                    <th className="px-4 py-3 font-semibold text-gray-700">Max Bookings</th>
                    <th className="px-4 py-3 font-semibold text-gray-700 text-right">My Amount</th>
                    <th className="px-4 py-3 font-semibold text-gray-700 text-right">Max Amount</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {rows.map((row: any, idx: number) => (
                    <tr key={idx} className="hover:bg-gray-50">
                      <td className="px-4 py-3 text-gray-800 font-medium text-xs">{row.weekRange}</td>
                      <td className="px-4 py-3 text-gray-700">{row.myCustomerBookings}</td>
                      <td className="px-4 py-3 text-gray-700">{row.maxCustomerBookings}</td>
                      <td className="px-4 py-3 text-right text-gray-700">${Number(row.myCustomerAmount ?? 0).toLocaleString()}</td>
                      <td className="px-4 py-3 text-right text-gray-700">${Number(row.maxCustomerAmount ?? 0).toLocaleString()}</td>
                    </tr>
                  ))}
                  {!isLoading && rows.length === 0 && (
                    <tr>
                      <td colSpan={5} className="px-4 py-6 text-center text-gray-500">No weekly data found for selected filters.</td>
                    </tr>
                  )}
                  {rows.length > 0 && (
                    <tr className="border-t-2 border-gray-300 bg-gray-50">
                      <td className="px-4 py-3 font-bold text-gray-800">Total</td>
                      <td className="px-4 py-3 font-bold text-gray-800">{rows.reduce((sum: number, row: any) => sum + row.myCustomerBookings, 0)}</td>
                      <td className="px-4 py-3 font-bold text-gray-800">{rows.reduce((sum: number, row: any) => sum + row.maxCustomerBookings, 0)}</td>
                      <td className="px-4 py-3 text-right font-bold text-gray-800">${Number(rows.reduce((sum: number, row: any) => sum + Number(row.myCustomerAmount ?? 0), 0)).toLocaleString()}</td>
                      <td className="px-4 py-3 text-right font-bold text-gray-800">${Number(rows.reduce((sum: number, row: any) => sum + Number(row.maxCustomerAmount ?? 0), 0)).toLocaleString()}</td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </Card>

      <Card>
        <div className="p-4 border-b border-gray-200 bg-gray-50">
          <h2 className="text-lg font-semibold text-gray-700">Customer And Total Booking Amount</h2>
        </div>
        <div className="p-6">
          <div className="h-[320px]">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={customerData} margin={{ top: 10, right: 30, left: 0, bottom: 70 }}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="name" angle={-45} textAnchor="end" height={90} />
                <YAxis />
                <Tooltip formatter={(value: any) => [`$${Number(value || 0).toFixed(2)}`, 'Total Booking Amount']} />
                <Legend />
                <Bar dataKey="amount" fill="#3b82f6" name="Total Booking Amount" />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </Card>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card className="p-4">
            <p className="text-sm text-gray-500">Total Bookings</p>
          <p className="text-2xl font-bold">{summary.total_customer_services}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Total Revenue</p>
          <p className="text-2xl font-bold text-green-600">${Number(summary.total_revenue || 0).toLocaleString()}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Average Customer Amount</p>
          <p className="text-2xl font-bold text-blue-600">
            ${summary.total_customer_services > 0 ? (Number(summary.total_revenue || 0) / summary.total_customer_services).toFixed(2) : '0.00'}
          </p>
        </Card>
      </div>

      <Card>
        <div className="p-4 border-b border-gray-200 bg-gray-50">
          <h2 className="text-lg font-semibold text-gray-700">Total Bookings By Customer</h2>
        </div>
        <div className="p-6">
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse text-sm">
              <thead>
                <tr className="border-b border-gray-200">
                  <th className="px-4 py-3 font-semibold text-gray-700">Customer Name</th>
                  <th className="px-4 py-3 font-semibold text-gray-700 text-right"># No of Bookings</th>
                  <th className="px-4 py-3 font-semibold text-gray-700 text-right">Total Amount</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {customerData.map((entry: any, index: number) => (
                  <tr key={`${entry.name}-${index}`} className="hover:bg-gray-50">
                    <td className="px-4 py-3 text-gray-800 font-medium">{entry.name}</td>
                    <td className="px-4 py-3 text-right font-semibold" style={{ color: entry.color }}>{entry.value}</td>
                    <td className="px-4 py-3 text-right text-gray-700 font-semibold">${Number(entry.total_booking_amount ?? entry.amount ?? 0).toLocaleString()}</td>
                  </tr>
                ))}
                {!isLoading && customerData.length === 0 && (
                  <tr>
                    <td colSpan={3} className="px-4 py-6 text-center text-gray-500">No data found for selected filters.</td>
                  </tr>
                )}
                {customerData.length > 0 && (
                  <tr className="border-t-2 border-gray-300 bg-gray-50">
                    <td className="px-4 py-3 font-bold text-gray-800">Total</td>
                    <td className="px-4 py-3 text-right font-bold text-gray-800">{customerData.reduce((sum: number, entry: any) => sum + entry.value, 0)}</td>
                    <td className="px-4 py-3 text-right font-bold text-gray-800">${Number(customerData.reduce((sum: number, entry: any) => sum + (entry.total_booking_amount ?? entry.amount ?? 0), 0)).toLocaleString()}</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </Card>
        </>
      )}

      {activeTab === 'unbooked' && (
        <>
          <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <form
              onSubmit={(e) => {
                e.preventDefault()
                setAppliedUnbookedFromDate(unbookedFromDate)
                setAppliedUnbookedToDate(unbookedToDate)
                setAppliedUnbookedCustomerId(unbookedCustomerId)
                setAppliedUnbookedMin(unbookedMin)
                setAppliedUnbookedMax(unbookedMax)
                setAppliedUnbookedNumberOfPets(unbookedNumberOfPets)
                setAppliedUnbookedPhone(unbookedPhone)
                setAppliedUnbookedState(unbookedState)
              }}
            >
              <div className="grid grid-cols-1 md:grid-cols-4 gap-6 items-end mb-4">
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-gray-600">From</label>
                  <input
                    type="date"
                    value={unbookedFromDate}
                    onChange={(e) => setUnbookedFromDate(e.target.value)}
                    className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-gray-600">To</label>
                  <input
                    type="date"
                    value={unbookedToDate}
                    onChange={(e) => setUnbookedToDate(e.target.value)}
                    className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-gray-600">Customers</label>
                  <Select
                    options={customerOptions}
                    value={unbookedCustomerId}
                    onChange={setUnbookedCustomerId}
                    placeholder="All Customers"
                  />
                </div>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-gray-600">Min</label>
                  <Input
                    type="number"
                    placeholder="Min value"
                    value={unbookedMin}
                    onChange={(e) => setUnbookedMin(e.target.value)}
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-gray-600">Max</label>
                  <Input
                    type="number"
                    placeholder="Max value"
                    value={unbookedMax}
                    onChange={(e) => setUnbookedMax(e.target.value)}
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-gray-600">Number of Pets</label>
                  <Input
                    type="number"
                    placeholder="No. of pets"
                    value={unbookedNumberOfPets}
                    onChange={(e) => setUnbookedNumberOfPets(e.target.value)}
                  />
                </div>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end mt-4">
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-gray-600">Phone</label>
                  <Input
                    type="text"
                    placeholder="Phone"
                    value={unbookedPhone}
                    onChange={(e) => setUnbookedPhone(e.target.value)}
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-gray-600">Select State</label>
                  <Select
                    options={[
                      { label: 'All States', value: '' },
                      { label: 'NSW', value: 'NSW' },
                      { label: 'VIC', value: 'VIC' },
                      { label: 'QLD', value: 'QLD' },
                      { label: 'SA', value: 'SA' },
                      { label: 'WA', value: 'WA' },
                      { label: 'TAS', value: 'TAS' },
                      { label: 'ACT', value: 'ACT' },
                      { label: 'NT', value: 'NT' },
                    ]}
                    value={unbookedState}
                    onChange={setUnbookedState}
                    placeholder="All States"
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
          <Card>
            <div className="p-4 border-b border-gray-200 bg-gray-50 text-center">
              <h2 className="text-2xl font-semibold text-primary-700">Unbooked Customer Report</h2>
              <p className="text-sm text-gray-500 mt-1">{unbookedFromDate} - {unbookedToDate}</p>
            </div>
            <div className="p-6">
              <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse text-sm">
                  <thead>
                    <tr className="border-b border-gray-200">
                      <th className="px-3 py-3 w-10"></th>
                      <th className="px-3 py-3 font-semibold text-gray-700">SN</th>
                      <th className="px-3 py-3 font-semibold text-gray-700">Name</th>
                      <th className="px-3 py-3 font-semibold text-gray-700">Email</th>
                      <th className="px-3 py-3 font-semibold text-gray-700">Mobile</th>
                      <th className="px-3 py-3 font-semibold text-gray-700">Address</th>
                      <th className="px-3 py-3 font-semibold text-gray-700 text-right">Total Bookings</th>
                      <th className="px-3 py-3 font-semibold text-gray-700 text-right">Future Bookings</th>
                      <th className="px-3 py-3 font-semibold text-gray-700">Registered Date</th>
                      <th className="px-3 py-3 font-semibold text-gray-700">Last Booked Date</th>
                      <th className="px-3 py-3 w-16"></th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100">
                    {unbookedRows.map((row: any, idx: number) => (
                      <tr key={`${row.id}-${idx}`} className="hover:bg-gray-50">
                        <td className="px-3 py-3 align-top">
                          <input type="checkbox" className="rounded border-gray-300" />
                        </td>
                        <td className="px-3 py-3 text-gray-700">{idx + 1}</td>
                        <td className="px-3 py-3 text-gray-800 font-medium">{row.name}</td>
                        <td className="px-3 py-3 text-gray-700">{row.email}</td>
                        <td className="px-3 py-3 text-gray-700">{row.mobile}</td>
                        <td className="px-3 py-3 text-gray-700">{row.address}</td>
                        <td className="px-3 py-3 text-right text-gray-700">{row.total_bookings ?? 0}</td>
                        <td className="px-3 py-3 text-right text-gray-700">{row.future_bookings ?? 0}</td>
                        <td className="px-3 py-3 text-gray-700">
                          {row.registered_date && !Number.isNaN(new Date(row.registered_date).getTime())
                            ? new Date(row.registered_date).toLocaleString()
                            : '-'}
                        </td>
                        <td className="px-3 py-3 text-gray-700">
                          {row.last_booked_date && !Number.isNaN(new Date(row.last_booked_date).getTime())
                            ? new Date(row.last_booked_date).toLocaleDateString()
                            : '-'}
                        </td>
                        <td className="px-3 py-3 text-right text-primary-600">Archive</td>
                      </tr>
                    ))}
                    {!isUnbookedLoading && unbookedRows.length === 0 && (
                      <tr>
                        <td colSpan={11} className="px-4 py-6 text-center text-gray-500">No data found for selected filters.</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          </Card>
        </>
      )}

      {activeTab === 'created' && (
        <>
          <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <form
              onSubmit={(e) => {
                e.preventDefault()
                setAppliedCreatedFromDate(createdFromDate)
                setAppliedCreatedToDate(createdToDate)
                setAppliedCreatedStatus(createdStatus)
                setAppliedCreatedNumberOfPets(createdNumberOfPets)
                setAppliedCreatedSearch(createdSearch)
              }}
            >
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end mb-4">
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-gray-600">From</label>
                  <input
                    type="date"
                    value={createdFromDate}
                    onChange={(e) => setCreatedFromDate(e.target.value)}
                    className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-gray-600">To</label>
                  <input
                    type="date"
                    value={createdToDate}
                    onChange={(e) => setCreatedToDate(e.target.value)}
                    className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-gray-600">Status</label>
                  <Select
                    options={[
                      { label: 'All Status', value: '' },
                      { label: 'Active', value: 'active' },
                      { label: 'Inactive', value: 'inactive' },
                      { label: 'Archived', value: 'archived' },
                    ]}
                    value={createdStatus}
                    onChange={setCreatedStatus}
                    placeholder="All Status"
                  />
                </div>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-gray-600">No of Pets</label>
                  <Input
                    type="number"
                    placeholder="No of pets"
                    value={createdNumberOfPets}
                    onChange={(e) => setCreatedNumberOfPets(e.target.value)}
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-gray-600">Search</label>
                  <Input
                    type="text"
                    placeholder="Search by name, email, phone"
                    value={createdSearch}
                    onChange={(e) => setCreatedSearch(e.target.value)}
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
          <Card>
            <div className="p-4 border-b border-gray-200 bg-gray-50 text-center">
              <h2 className="text-2xl font-semibold text-gray-800">Customer Created Report</h2>
              <p className="text-sm text-gray-500 mt-1">{appliedCreatedFromDate} - {appliedCreatedToDate}</p>
            </div>
            <div className="p-6">
              <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse text-sm">
                  <thead>
                    <tr className="border-b border-gray-200">
                      <th className="px-4 py-3 font-semibold text-gray-700">SN</th>
                      <th className="px-4 py-3 font-semibold text-gray-700">Customer ID</th>
                      <th className="px-4 py-3 font-semibold text-gray-700">Name</th>
                      <th className="px-4 py-3 font-semibold text-gray-700">Email</th>
                      <th className="px-4 py-3 font-semibold text-gray-700">Mobile</th>
                      <th className="px-4 py-3 font-semibold text-gray-700">Address</th>
                      <th className="px-4 py-3 font-semibold text-gray-700">Status</th>
                      <th className="px-4 py-3 font-semibold text-gray-700">Registered Date</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100">
                    {createdRows.map((row: any, idx: number) => (
                      <tr key={`${row.id}-${idx}`} className="hover:bg-gray-50">
                        <td className="px-4 py-3 text-gray-700">{idx + 1}</td>
                        <td className="px-4 py-3 text-gray-700">{row.id}</td>
                        <td className="px-4 py-3 text-gray-800 font-medium">{row.name}</td>
                        <td className="px-4 py-3 text-gray-700">{row.email}</td>
                        <td className="px-4 py-3 text-gray-700">{row.mobile}</td>
                        <td className="px-4 py-3 text-gray-700">{row.address}</td>
                        <td className="px-4 py-3 text-gray-700">{row.status}</td>
                        <td className="px-4 py-3 text-gray-700">
                          {row.registeredDate !== '-' && !Number.isNaN(new Date(row.registeredDate).getTime())
                            ? new Date(row.registeredDate).toLocaleString()
                            : '-'}
                        </td>
                      </tr>
                    ))}
                    {!isLoading && createdRows.length === 0 && (
                      <tr>
                        <td colSpan={8} className="px-4 py-6 text-center text-gray-500">No data found for selected filters.</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          </Card>
        </>
      )}

      {activeTab === 'referral' && (
        <>
          <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <form
              onSubmit={(e) => {
                e.preventDefault()
                applyReferralFilters()
              }}
            >
              <div className="grid grid-cols-1 md:grid-cols-4 gap-6 items-end mb-4">
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-gray-600">From</label>
                  <input
                    type="date"
                    value={referralFromDate}
                    onChange={(e) => setReferralFromDate(e.target.value)}
                    className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-gray-600">To</label>
                  <input
                    type="date"
                    value={referralToDate}
                    onChange={(e) => setReferralToDate(e.target.value)}
                    className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-gray-600">Referrer Customer</label>
                  <Select
                    options={customerOptions}
                    value={referrerCustomerId}
                    onChange={setReferrerCustomerId}
                    placeholder="All Referrers"
                  />
                </div>
                <div className="flex items-end">
                  <Button
                    type="submit"
                    onClick={applyReferralFilters}
                    className="flex items-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-md transition-all shadow-sm font-medium text-sm"
                  >
                    <Filter size={18} />
                    Generate Report
                  </Button>
                </div>
              </div>
            </form>
          </div>
          <Card>
            <div className="p-4 border-b border-gray-200 bg-gray-50 text-center">
              <h2 className="text-2xl font-semibold text-gray-800">Referral Customer Report</h2>
              <p className="text-sm text-gray-500 mt-1">{appliedReferralFromDate} - {appliedReferralToDate}</p>
            </div>
            <div className="p-6">
              <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse text-sm">
                  <thead>
                    <tr className="border-b border-gray-200">
                      <th className="px-4 py-3 font-semibold text-gray-700">Week Range</th>
                      <th className="px-4 py-3 font-semibold text-gray-700 text-right">Non Referred</th>
                      {referralColumns.map((column) => (
                        <th key={column} className="px-4 py-3 font-semibold text-gray-700 text-right">{column}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100">
                    {referralRows.map((row, idx) => (
                      <tr key={`${row.weekRange}-${idx}`} className="hover:bg-gray-50">
                        <td className="px-4 py-3 text-gray-800 font-medium">{row.weekRange}</td>
                        <td className="px-4 py-3 text-right text-gray-700">{row.nonReferred ?? 0}</td>
                        {referralColumns.map((column) => (
                          <td key={`${row.weekRange}-${column}`} className="px-4 py-3 text-right text-gray-700">{row[column] ?? 0}</td>
                        ))}
                      </tr>
                    ))}
                    {!isLoading && referralRows.length === 0 && (
                      <tr>
                        <td colSpan={Math.max(3, referralColumns.length + 2)} className="px-4 py-6 text-center text-gray-500">
                          No data found for selected filters.
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          </Card>
        </>
      )}
    </div>
  )
}
