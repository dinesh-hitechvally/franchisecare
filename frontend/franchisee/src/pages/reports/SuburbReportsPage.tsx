import { useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import {
  PieChart, Pie, Cell,
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer,
} from 'recharts'
import { Filter, Download, FileText } from 'lucide-react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Select } from '../../components/ui/Select'
import { suburbReportsApi } from '../../api/services'

export function SuburbReportsPage() {
  const now = new Date()
  const defaultFrom = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10)
  const defaultTo = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10)

  const [fromDate, setFromDate] = useState(defaultFrom)
  const [toDate, setToDate] = useState(defaultTo)
  const [suburb, setSuburb] = useState('')
  const [min, setMin] = useState('')
  const [max, setMax] = useState('')

  const [appliedFromDate, setAppliedFromDate] = useState(defaultFrom)
  const [appliedToDate, setAppliedToDate] = useState(defaultTo)
  const [appliedSuburb, setAppliedSuburb] = useState('')
  const [appliedMin, setAppliedMin] = useState('')
  const [appliedMax, setAppliedMax] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['suburb-report', appliedFromDate, appliedToDate, appliedSuburb, appliedMin, appliedMax],
    queryFn: () =>
      suburbReportsApi.getReport({
        date_from: appliedFromDate,
        date_to: appliedToDate,
        suburb: appliedSuburb || undefined,
        min: appliedMin || undefined,
        max: appliedMax || undefined,
      }),
  })

  const { data: suburbOptionsData = [] } = useQuery({
    queryKey: ['suburb-report-options', fromDate, toDate],
    queryFn: () =>
      suburbReportsApi.getReport({
        date_from: fromDate,
        date_to: toDate,
      }),
  })

  const suburbData = data?.data || []
  const rows = data?.weekly_data || []
  const summary = data?.summary || { total_suburb_services: 0, total_revenue: 0 }
  const serviceBySuburb = data?.service_by_suburb || []

  const suburbOptions = useMemo(() => {
    const options = (suburbOptionsData?.data || []).map((entry: any) => ({
      label: entry.name,
      value: entry.name,
    }))

    return [{ label: 'All Suburbs', value: '' }, ...options]
  }, [suburbOptionsData])

  return (
    <div className="space-y-6 px-1 py-1 w-full">
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-800">Suburb Reports</h1>
        <div className="flex gap-2">
          <button className="flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors text-xs font-medium">
            <Download size={14} /> Export CSV
          </button>
          <button className="flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors text-xs font-medium">
            <FileText size={14} /> PDF Report
          </button>
        </div>
      </div>

      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <form
          onSubmit={(e) => {
            e.preventDefault()
            setAppliedFromDate(fromDate)
            setAppliedToDate(toDate)
            setAppliedSuburb(suburb)
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
              <label className="text-sm font-medium text-gray-600">Suburbs</label>
              <Select
                options={suburbOptions}
                value={suburb}
                onChange={setSuburb}
                placeholder="All Suburbs"
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
          <h2 className="text-lg font-semibold text-gray-700">All my suburbs</h2>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
          <div className="flex items-center justify-center">
            <ResponsiveContainer width="100%" height={300}>
              <PieChart>
                <Pie
                  data={suburbData}
                  cx="50%"
                  cy="50%"
                  outerRadius={80}
                  fill="#8884d8"
                  dataKey="value"
                >
                  {suburbData.map((entry: any, index: number) => (
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
                    <th className="px-4 py-3 font-semibold text-gray-700">Suburb</th>
                    <th className="px-4 py-3 font-semibold text-gray-700 text-right">Count</th>
                    <th className="px-4 py-3 font-semibold text-gray-700 text-right">Total Booking Amount</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {suburbData.map((entry: any, index: number) => (
                    <tr key={`${entry.name}-${index}`} className="hover:bg-gray-50">
                      <td className="px-4 py-3 text-gray-800 font-medium">{entry.name}</td>
                      <td className="px-4 py-3 text-right font-semibold" style={{ color: entry.color }}>{entry.value}</td>
                      <td className="px-4 py-3 text-right text-gray-700 font-semibold">${Number(entry.total_booking_amount ?? entry.amount ?? 0).toLocaleString()}</td>
                    </tr>
                  ))}
                  {!isLoading && suburbData.length === 0 && (
                    <tr>
                      <td colSpan={3} className="px-4 py-6 text-center text-gray-500">No data found for selected filters.</td>
                    </tr>
                  )}
                  {suburbData.length > 0 && (
                    <tr className="border-t-2 border-gray-300 bg-gray-50">
                      <td className="px-4 py-3 font-bold text-gray-800">Total</td>
                      <td className="px-4 py-3 text-right font-bold text-gray-800">{suburbData.reduce((sum: number, entry: any) => sum + entry.value, 0)}</td>
                      <td className="px-4 py-3 text-right font-bold text-gray-800">${Number(suburbData.reduce((sum: number, entry: any) => sum + (entry.total_booking_amount ?? 0), 0)).toLocaleString()}</td>
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
          <h2 className="text-lg font-semibold text-gray-700">Suburb Comparision</h2>
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
                <Bar dataKey="mySuburbServices" fill="#8884d8" name="My Suburb Services" />
                <Bar dataKey="maxSuburbServices" fill="#82ca9d" name="Max Suburb Services" />
              </BarChart>
            </ResponsiveContainer>
          </div>

          <div>
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse text-sm">
                <thead>
                  <tr className="border-b border-gray-200">
                    <th className="px-4 py-3 font-semibold text-gray-700">Week</th>
                    <th className="px-4 py-3 font-semibold text-gray-700">My Suburb Services</th>
                    <th className="px-4 py-3 font-semibold text-gray-700">Max Suburb Services</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {rows.map((row: any, idx: number) => (
                    <tr key={idx} className="hover:bg-gray-50">
                      <td className="px-4 py-3 text-gray-800 font-medium text-xs">{row.weekRange}</td>
                      <td className="px-4 py-3 text-gray-700">{row.mySuburbServices}</td>
                      <td className="px-4 py-3 text-gray-700">{row.maxSuburbServices}</td>
                    </tr>
                  ))}
                  {!isLoading && rows.length === 0 && (
                    <tr>
                      <td colSpan={3} className="px-4 py-6 text-center text-gray-500">No weekly data found for selected filters.</td>
                    </tr>
                  )}
                  {rows.length > 0 && (
                    <tr className="border-t-2 border-gray-300 bg-gray-50">
                      <td className="px-4 py-3 font-bold text-gray-800">Total</td>
                      <td className="px-4 py-3 font-bold text-gray-800">{rows.reduce((sum: number, row: any) => sum + row.mySuburbServices, 0)}</td>
                      <td className="px-4 py-3 font-bold text-gray-800">{rows.reduce((sum: number, row: any) => sum + row.maxSuburbServices, 0)}</td>
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
          <h2 className="text-lg font-semibold text-gray-700">Suburb And Total Booking Amount</h2>
        </div>
        <div className="p-6">
          <div className="h-[320px]">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={suburbData} margin={{ top: 10, right: 30, left: 0, bottom: 70 }}>
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
          <p className="text-sm text-gray-500">Suburb Booked</p>
          <p className="text-2xl font-bold">{summary.total_suburb_services}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Total Revenue</p>
          <p className="text-2xl font-bold text-green-600">${Number(summary.total_revenue || 0).toLocaleString()}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Average Suburb Amount</p>
          <p className="text-2xl font-bold text-blue-600">
            ${summary.total_suburb_services > 0 ? (Number(summary.total_revenue || 0) / summary.total_suburb_services).toFixed(2) : '0.00'}
          </p>
        </Card>
      </div>

      <Card>
        <div className="p-4 border-b border-gray-200 bg-gray-50">
          <h2 className="text-lg font-semibold text-gray-700">Total Services by Suburb</h2>
        </div>
        <div className="p-6">
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse text-sm">
              <thead>
                <tr className="border-b border-gray-200">
                  <th className="px-4 py-3 font-semibold text-gray-700">Suburb Name</th>
                  <th className="px-4 py-3 font-semibold text-gray-700">Service Name</th>
                  <th className="px-4 py-3 font-semibold text-gray-700 text-right"># No of Services</th>
                  <th className="px-4 py-3 font-semibold text-gray-700 text-right">Total Amount</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {serviceBySuburb.map((entry: any, index: number) => (
                  <tr key={index} className={`hover:bg-gray-50 ${entry.service_name === 'Unknown Service' ? 'bg-yellow-50' : ''}`}>
                    <td className="px-4 py-3 text-gray-800 font-medium">{entry.suburb_name}</td>
                    <td className={`px-4 py-3 ${entry.service_name === 'Unknown Service' ? 'font-semibold text-yellow-700' : 'text-gray-700'}`}>{entry.service_name}</td>
                    <td className="px-4 py-3 text-right font-semibold text-gray-700">{entry.count}</td>
                    <td className="px-4 py-3 text-right text-gray-700 font-semibold">${Number(entry.total_amount ?? 0).toLocaleString()}</td>
                  </tr>
                ))}
                {!isLoading && serviceBySuburb.length === 0 && (
                  <tr>
                    <td colSpan={4} className="px-4 py-6 text-center text-gray-500">No data found for selected filters.</td>
                  </tr>
                )}
                {serviceBySuburb.length > 0 && (
                  <tr className="border-t-2 border-gray-300 bg-gray-50">
                    <td colSpan={2} className="px-4 py-3 font-bold text-gray-800">Total</td>
                    <td className="px-4 py-3 text-right font-bold text-gray-800">{serviceBySuburb.reduce((sum: number, entry: any) => sum + entry.count, 0)}</td>
                    <td className="px-4 py-3 text-right font-bold text-gray-800">${Number(serviceBySuburb.reduce((sum: number, entry: any) => sum + (entry.total_amount ?? 0), 0)).toLocaleString()}</td>
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
