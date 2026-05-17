import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Search, BarChart3 } from 'lucide-react'
import { reportsApi } from '../../api/services'
import { PageHeader } from '../../components/layout/PageHeader'

type BenchmarkRow = {
  heading: string
  your_details: string
  state_average: string
  national_average: string
  state_comparison: string
  national_comparison: string
}

export function BenchmarkingPage() {
  const now = new Date()
  const defaultFrom = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10)
  const defaultTo = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10)

  const [fromDate, setFromDate] = useState(defaultFrom)
  const [toDate, setToDate] = useState(defaultTo)
  const [appliedFromDate, setAppliedFromDate] = useState(defaultFrom)
  const [appliedToDate, setAppliedToDate] = useState(defaultTo)

  const { data, isLoading } = useQuery({
    queryKey: ['benchmarking-report', appliedFromDate, appliedToDate],
    queryFn: () =>
      reportsApi.getBenchmarking({
        date_from: appliedFromDate,
        date_to: appliedToDate,
      }),
  })

  const rows: BenchmarkRow[] = data?.data ?? []
  const rank = data?.rank ?? '-'
  const message = data?.message ?? ''

  return (
    <div className="space-y-6 px-1 py-1 w-full">
      <PageHeader
        title="Benchmarking"
        description="Compare your performance against state and national averages"
        icon={<BarChart3 size={20} />}
      />

      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
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
          <button
            type="button"
            onClick={() => {
              setAppliedFromDate(fromDate)
              setAppliedToDate(toDate)
            }}
            className="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-md transition-all shadow-sm font-medium text-sm"
          >
            <Search size={18} />
            Generate Benchmarking Report
          </button>
        </div>
      </div>

      <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
          <h2 className="text-sm font-semibold text-gray-700">Benchmarking Report</h2>
          <span className="text-sm text-gray-600">Rank: {rank}</span>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse text-sm">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="px-4 py-3 font-semibold text-gray-700">Heading</th>
                <th className="px-4 py-3 font-semibold text-gray-700">Your Details</th>
                <th className="px-4 py-3 font-semibold text-gray-700">State Average</th>
                <th className="px-4 py-3 font-semibold text-gray-700">National Average</th>
                <th className="px-4 py-3 font-semibold text-gray-700">State Comparison</th>
                <th className="px-4 py-3 font-semibold text-gray-700">National Comparison</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {isLoading ? (
                <tr>
                  <td colSpan={6} className="px-4 py-8 text-center text-gray-500 italic">Loading benchmarking report...</td>
                </tr>
              ) : rows.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-4 py-8 text-center text-gray-500 italic">No benchmarking data found</td>
                </tr>
              ) : (
                rows.map((row, index) => (
                  <tr key={`${row.heading}-${index}`} className="hover:bg-gray-50">
                    <td className="px-4 py-3 text-gray-800 font-medium">{row.heading}</td>
                    <td className="px-4 py-3 text-gray-700">{row.your_details}</td>
                    <td className="px-4 py-3 text-gray-700">{row.state_average}</td>
                    <td className="px-4 py-3 text-gray-700">{row.national_average}</td>
                    <td className="px-4 py-3 text-gray-700">{row.state_comparison}</td>
                    <td className="px-4 py-3 text-gray-700">{row.national_comparison}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {message && (
          <div className="px-5 py-3 border-t border-gray-200 text-xs text-gray-500">
            {message}
          </div>
        )}
      </div>
    </div>
  )
}
