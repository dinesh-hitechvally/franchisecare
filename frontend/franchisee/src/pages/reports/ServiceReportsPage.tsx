import { useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { 
  PieChart, Pie, Cell, 
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer 
} from 'recharts'
import { Filter } from 'lucide-react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Select } from '../../components/ui/Select'
import { serviceReportsApi, servicesApi } from '../../api/services'

export function ServiceReportsPage() {
  const now = new Date()
  const defaultFrom = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10)
  const defaultTo = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10)

  const [fromDate, setFromDate] = useState(defaultFrom)
  const [toDate, setToDate] = useState(defaultTo)
  const [serviceId, setServiceId] = useState('')
  const [min, setMin] = useState('')
  const [max, setMax] = useState('')

  const [appliedFromDate, setAppliedFromDate] = useState(defaultFrom)
  const [appliedToDate, setAppliedToDate] = useState(defaultTo)
  const [appliedServiceId, setAppliedServiceId] = useState('')
  const [appliedMin, setAppliedMin] = useState('')
  const [appliedMax, setAppliedMax] = useState('')

  const { data: serviceOptionsData = [] } = useQuery({
    queryKey: ['report-service-options'],
    queryFn: () => servicesApi.getAll(),
  })

  const serviceOptions = useMemo(() => {
    const options = serviceOptionsData.map((service: any) => ({
      label: service.name,
      value: String(service.id),
    }))

    return [{ label: 'All Services', value: '' }, ...options]
  }, [serviceOptionsData])

  const { data, isLoading } = useQuery({
    queryKey: ['service-report', appliedFromDate, appliedToDate, appliedServiceId, appliedMin, appliedMax],
    queryFn: () => serviceReportsApi.getReport({
      date_from: appliedFromDate,
      date_to: appliedToDate,
      service_id: appliedServiceId || undefined,
      min: appliedMin || undefined,
      max: appliedMax || undefined,
    }),
  })

  const serviceData = data?.data || []
  const rows = data?.weekly_data || []
  const summary = data?.summary || { total_services: 0, total_revenue: 0 }

  return (
    <div className="space-y-6 px-1 py-1 w-full">
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Service Reports</h1>
      </div>

      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <form
          onSubmit={(e) => {
            e.preventDefault()
            setAppliedFromDate(fromDate)
            setAppliedToDate(toDate)
            setAppliedServiceId(serviceId)
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
              <label className="text-sm font-medium text-gray-600">Services</label>
              <Select
                options={serviceOptions}
                value={serviceId}
                onChange={setServiceId}
                placeholder="All Services"
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
                onChange={e => setMin(e.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-gray-600">Max</label>
              <Input
                type="number"
                placeholder="Max value"
                value={max}
                onChange={e => setMax(e.target.value)}
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
          <h2 className="text-lg font-semibold text-gray-700">All my services</h2>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
          <div className="flex items-center justify-center">
            <ResponsiveContainer width="100%" height={300}>
              <PieChart>
                <Pie
                  data={serviceData}
                  cx="50%"
                  cy="50%"
                  outerRadius={80}
                  fill="#8884d8"
                  dataKey="value"
                >
                  {serviceData.map((entry: any, index: number) => (
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
                    <th className="px-4 py-3 font-semibold text-gray-700">Service</th>
                    <th className="px-4 py-3 font-semibold text-gray-700 text-right">Count</th>
                    <th className="px-4 py-3 font-semibold text-gray-700 text-right">Total Booking Amount</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {serviceData.map((entry: any, index: number) => (
                    <tr key={`${entry.name}-${index}`} className="hover:bg-gray-50">
                      <td className="px-4 py-3 text-gray-800 font-medium">{entry.name}</td>
                      <td className="px-4 py-3 text-right font-semibold" style={{ color: entry.color }}>{entry.value}</td>
                      <td className="px-4 py-3 text-right text-gray-700 font-semibold">${Number(entry.total_booking_amount ?? entry.amount ?? 0).toLocaleString()}</td>
                    </tr>
                  ))}
                  {!isLoading && serviceData.length === 0 && (
                    <tr>
                      <td colSpan={3} className="px-4 py-6 text-center text-gray-500">No data found for selected filters.</td>
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
          <h2 className="text-lg font-semibold text-gray-700">Service Comparision</h2>
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
                <Bar dataKey="myServices" fill="#8884d8" name="My Services" />
                <Bar dataKey="maxServices" fill="#82ca9d" name="Max Services" />
              </BarChart>
            </ResponsiveContainer>
          </div>

          <div>
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse text-sm">
                <thead>
                  <tr className="border-b border-gray-200">
                    <th className="px-4 py-3 font-semibold text-gray-700">Week</th>
                    <th className="px-4 py-3 font-semibold text-gray-700">My Services</th>
                    <th className="px-4 py-3 font-semibold text-gray-700">Max Services</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {rows.map((row: any, idx: number) => (
                    <tr key={idx} className="hover:bg-gray-50">
                      <td className="px-4 py-3 text-gray-800 font-medium text-xs">{row.weekRange}</td>
                      <td className="px-4 py-3 text-gray-700">{row.myServices}</td>
                      <td className="px-4 py-3 text-gray-700">{row.maxServices}</td>
                    </tr>
                  ))}
                  {!isLoading && rows.length === 0 && (
                    <tr>
                      <td colSpan={3} className="px-4 py-6 text-center text-gray-500">No weekly data found for selected filters.</td>
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
          <h2 className="text-lg font-semibold text-gray-700">Service And Total Booking Amount</h2>
        </div>
        <div className="p-6">
          <div className="h-[320px]">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={serviceData} margin={{ top: 10, right: 30, left: 0, bottom: 70 }}>
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
          <p className="text-sm text-gray-500">Service Booked</p>
          <p className="text-2xl font-bold">{summary.total_services}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Total Revenue</p>
          <p className="text-2xl font-bold text-green-600">${Number(summary.total_revenue || 0).toLocaleString()}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Average Service Amount</p>
          <p className="text-2xl font-bold text-blue-600">
            ${summary.total_services > 0 ? (Number(summary.total_revenue || 0) / summary.total_services).toFixed(2) : '0.00'}
          </p>
        </Card>
      </div>
    </div>
  )
}
