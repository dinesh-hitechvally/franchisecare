import { useState } from 'react'
import { BarChart3, TrendingUp, Users, ShoppingBag, FlaskConical, Search } from 'lucide-react'

interface BenchmarkMetric {
  title: string
  yourValue: number | string
  avgValue: number | string
  prefix?: string
  suffix?: string
  icon: any
  color: string
}

export function BenchmarkingPage() {
  const [year, setYear] = useState('2026')
  const [month, setMonth] = useState('April')

  const metrics: BenchmarkMetric[] = [
    {
      title: 'Total Sales',
      yourValue: 12450.50,
      avgValue: 10200.00,
      prefix: '$',
      icon: BarChart3,
      color: 'bg-blue-500',
    },
    {
      title: 'Average Sale',
      yourValue: 85.20,
      avgValue: 78.50,
      prefix: '$',
      icon: TrendingUp,
      color: 'bg-green-500',
    },
    {
      title: 'Number of Services',
      yourValue: 146,
      avgValue: 130,
      icon: Users,
      color: 'bg-purple-500',
    },
    {
      title: 'Active Customers',
      yourValue: 88,
      avgValue: 75,
      icon: Users,
      color: 'bg-orange-500',
    },
    {
      title: 'Product Sales',
      yourValue: 1250.00,
      avgValue: 980.00,
      prefix: '$',
      icon: ShoppingBag,
      color: 'bg-pink-500',
    },
    {
      title: 'Product Usage',
      yourValue: 450.25,
      avgValue: 520.00,
      prefix: '$',
      icon: FlaskConical,
      color: 'bg-teal-500',
    },
  ]

  const formatValue = (metric: BenchmarkMetric, value: number | string) => {
    if (typeof value === 'number') {
      return `${metric.prefix || ''}${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}${metric.suffix || ''}`
    }
    return value
  }

  const getComparisonColor = (your: number | string, avg: number | string, higherIsBetter = true) => {
    const y = typeof your === 'string' ? parseFloat(your) : your
    const a = typeof avg === 'string' ? parseFloat(avg) : avg
    if (y === a) return 'text-gray-500'
    const isHigher = y > a
    return (isHigher === higherIsBetter) ? 'text-green-600' : 'text-red-600'
  }

  return (
    <div className="flex flex-col gap-6 p-6 min-h-screen bg-[#f4f6f8]">
      {/* Page Title Card */}
      <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-100 mb-2">
        <h1 className="text-xl font-bold text-gray-800">Benchmarking</h1>
      </div>

      {/* Filter Card */}
      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-2">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
          <div className="space-y-1.5">
            <label className="text-sm font-medium text-gray-600">Year</label>
            <select 
              value={year}
              onChange={(e) => setYear(e.target.value)}
              className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
              <option value="2026">2026</option>
              <option value="2025">2025</option>
              <option value="2024">2024</option>
            </select>
          </div>
          <div className="space-y-1.5">
            <label className="text-sm font-medium text-gray-600">Month</label>
            <select 
              value={month}
              onChange={(e) => setMonth(e.target.value)}
              className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
              <option value="January">January</option>
              <option value="February">February</option>
              <option value="March">March</option>
              <option value="April">April</option>
              <option value="May">May</option>
              <option value="June">June</option>
              <option value="July">July</option>
              <option value="August">August</option>
              <option value="September">September</option>
              <option value="October">October</option>
              <option value="November">November</option>
              <option value="December">December</option>
            </select>
          </div>
          <button className="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-md transition-all shadow-sm font-medium text-sm">
            <Search size={18} />
            Generate Benchmarking Report
          </button>
        </div>
      </div>

      {/* Benchmarking Content */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {metrics.map((metric, index) => {
          const Icon = metric.icon
          const yourVal = typeof metric.yourValue === 'string' ? parseFloat(metric.yourValue) : metric.yourValue
          const avgVal = typeof metric.avgValue === 'string' ? parseFloat(metric.avgValue) : metric.avgValue
          const diff = ((yourVal - avgVal) / avgVal) * 100
          
          return (
            <div key={index} className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all">
              <div className="p-5">
                <div className="flex items-center justify-between mb-6">
                  <div className={`p-2.5 rounded-lg ${metric.color} bg-opacity-10 text-white`}>
                    <Icon className={`${metric.color.replace('bg-', 'text-')}`} size={22} />
                  </div>
                  <span className={`text-xs font-bold px-2 py-1 rounded ${diff >= 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}`}>
                    {diff >= 0 ? '+' : ''}{diff.toFixed(1)}% vs Avg
                  </span>
                </div>
                
                <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">{metric.title}</h3>
                
                <div className="flex flex-col gap-4">
                  {/* Your Value */}
                  <div className="flex items-baseline justify-between">
                    <span className="text-xs text-gray-400 font-medium">Your Value</span>
                    <span className="text-xl font-bold text-gray-900">{formatValue(metric, metric.yourValue)}</span>
                  </div>
                  
                  {/* Progress Bar Container */}
                  <div className="space-y-2">
                    <div className="h-2 w-full bg-gray-100 rounded-full overflow-hidden flex">
                      <div 
                        className={`h-full ${metric.color}`} 
                        style={{ width: `${Math.min((yourVal / Math.max(yourVal, avgVal)) * 100, 100)}%` }}
                      ></div>
                    </div>
                    <div className="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden flex">
                      <div 
                        className="h-full bg-gray-300" 
                        style={{ width: `${Math.min((avgVal / Math.max(yourVal, avgVal)) * 100, 100)}%` }}
                      ></div>
                    </div>
                  </div>

                  {/* Average Value */}
                  <div className="flex items-baseline justify-between">
                    <span className="text-xs text-gray-400 font-medium">Network Average</span>
                    <span className="text-sm font-semibold text-gray-500">{formatValue(metric, metric.avgValue)}</span>
                  </div>
                </div>
              </div>
              
              {/* Card Footer Info */}
              <div className="px-5 py-3 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                <span className="text-[10px] text-gray-400 font-medium italic">Data from {month} {year}</span>
                <TrendingUp size={14} className={diff >= 0 ? 'text-green-500' : 'text-red-500'} />
              </div>
            </div>
          )
        })}
      </div>
    </div>
  )
}
