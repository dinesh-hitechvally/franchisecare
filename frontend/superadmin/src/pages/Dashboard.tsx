import { useEffect } from 'react'
import { useQuery } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  Building2,
  Users,
  LifeBuoy,
  ArrowUpRight,
  ArrowDownRight,
  Store,
  Ticket,
} from 'lucide-react'
import {
  ResponsiveContainer,
  AreaChart,
  Area,
  CartesianGrid,
  XAxis,
  YAxis,
  Tooltip,
} from 'recharts'
import { format, formatDistanceToNow, parseISO } from 'date-fns'
import { dashboardApi } from '../api/services'
import type { Franchise } from '../types'

const currencyFormatter = new Intl.NumberFormat('en-AU', {
  style: 'currency',
  currency: 'AUD',
  maximumFractionDigits: 0,
})

// Matches the badge convention used in ListFranchises.tsx
const statusBadgeStyle: Record<Franchise['status'], { bg: string; color: string }> = {
  ACTIVE: { bg: '#dcfce7', color: '#166534' },
  INACTIVE: { bg: '#f3f4f6', color: '#4b5563' },
  SUSPENDED: { bg: '#ffedd5', color: '#c2410c' },
  TERMINATED: { bg: '#fee2e2', color: '#b91c1c' },
}

// Fixed status palette (never repurposed for series color) — mirrors the badge hues
const statusBarColor: Record<string, string> = {
  ACTIVE: '#0ca30c',
  INACTIVE: '#9ca3af',
  SUSPENDED: '#fab219',
  TERMINATED: '#d03b3b',
}

function StatusBadge({ status }: { status: Franchise['status'] }) {
  const style = statusBadgeStyle[status] ?? statusBadgeStyle.INACTIVE
  return (
    <span
      style={{
        display: 'inline-block',
        padding: '3px 10px',
        borderRadius: '9999px',
        fontSize: '11px',
        fontWeight: 600,
        textTransform: 'capitalize',
        background: style.bg,
        color: style.color,
      }}
    >
      {status}
    </span>
  )
}

function useErrorToast(isError: boolean, message: string) {
  useEffect(() => {
    if (isError) toast.error(message)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isError])
}

function formatMonthLabel(month: string) {
  try {
    return format(parseISO(`${month}-01`), 'MMM yyyy')
  } catch {
    return month
  }
}

function formatActivityDate(date: string) {
  try {
    return formatDistanceToNow(parseISO(date), { addSuffix: true })
  } catch {
    return date
  }
}

export function Dashboard() {
  const metricsQuery = useQuery({
    queryKey: ['dashboard', 'metrics'],
    queryFn: () => dashboardApi.metrics(),
  })
  useErrorToast(metricsQuery.isError, 'Failed to load dashboard metrics')

  const revenueQuery = useQuery({
    queryKey: ['dashboard', 'revenue-chart'],
    queryFn: () => dashboardApi.revenueChart(12),
  })
  useErrorToast(revenueQuery.isError, 'Failed to load revenue chart')

  const statusQuery = useQuery({
    queryKey: ['dashboard', 'franchises-by-status'],
    queryFn: () => dashboardApi.franchisesByStatus(),
  })
  useErrorToast(statusQuery.isError, 'Failed to load franchise status breakdown')

  const activitiesQuery = useQuery({
    queryKey: ['dashboard', 'recent-activities'],
    queryFn: () => dashboardApi.recentActivities(),
  })
  useErrorToast(activitiesQuery.isError, 'Failed to load recent activity')

  const topFranchisesQuery = useQuery({
    queryKey: ['dashboard', 'top-franchises'],
    queryFn: () => dashboardApi.topFranchises(),
  })
  useErrorToast(topFranchisesQuery.isError, 'Failed to load top franchises')

  const metrics = metricsQuery.data
  const revenueUp = (metrics?.revenue_change ?? 0) >= 0
  const statusData = statusQuery.data ?? []
  const statusTotal = statusData.reduce((sum, s) => sum + s.count, 0)

  return (
    <div className="page-content">
      <h1 className="page-title">Dashboard</h1>

      {/* Top metrics */}
      <div className="stats-grid">
        <div className="stat-card cyan">
          <div className="stat-label">Total Franchises</div>
          <div className="stat-value">{metricsQuery.isLoading ? '—' : metrics?.total_franchises ?? 0}</div>
          <div className="stat-description">
            <Building2 size={14} className="inline mr-1" />
            Registered franchise locations
          </div>
        </div>
        <div className="stat-card blue">
          <div className="stat-label">Active Franchises</div>
          <div className="stat-value">{metricsQuery.isLoading ? '—' : metrics?.active_franchises ?? 0}</div>
          <div className="stat-description">
            <Store size={14} className="inline mr-1" />
            Currently operating
          </div>
        </div>
        <div className="stat-card purple">
          <div className="stat-label">Total Users</div>
          <div className="stat-value">{metricsQuery.isLoading ? '—' : metrics?.total_users ?? 0}</div>
          <div className="stat-description">
            <Users size={14} className="inline mr-1" />
            Across all franchises
          </div>
        </div>
        <div className="stat-card red">
          <div className="stat-label">Open Tickets</div>
          <div className="stat-value">{metricsQuery.isLoading ? '—' : metrics?.open_tickets ?? 0}</div>
          <div className="stat-description">
            <LifeBuoy size={14} className="inline mr-1" />
            Awaiting support resolution
          </div>
        </div>
      </div>

      {metricsQuery.isError && (
        <div className="text-sm text-red-500 mb-4">Failed to load metrics.</div>
      )}

      {/* Revenue highlight */}
      <div className="card mb-6">
        <div className="card-body p-6 flex items-center justify-between flex-wrap gap-4">
          <div>
            <div className="text-sm text-gray-500 font-medium mb-1">Monthly Revenue</div>
            <div className="text-3xl font-bold text-gray-900">
              {metricsQuery.isLoading || !metrics ? '—' : currencyFormatter.format(metrics.monthly_revenue)}
            </div>
          </div>
          {!metricsQuery.isLoading && metrics && (
            <div
              className={`flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-semibold ${
                revenueUp ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'
              }`}
            >
              {revenueUp ? <ArrowUpRight size={16} /> : <ArrowDownRight size={16} />}
              {Math.abs(metrics.revenue_change)}% vs last month
            </div>
          )}
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {/* Revenue chart */}
        <div className="card lg:col-span-2">
          <div className="card-header">
            <h2 className="card-title">Revenue (Last 12 Months)</h2>
          </div>
          <div className="card-body p-5">
            {revenueQuery.isLoading ? (
              <div className="text-center py-16 text-gray-400">Loading...</div>
            ) : revenueQuery.isError ? (
              <div className="text-center py-16 text-red-500">Failed to load revenue chart.</div>
            ) : !revenueQuery.data || revenueQuery.data.length === 0 ? (
              <div className="text-center py-16 text-gray-400">No revenue data available.</div>
            ) : (
              <ResponsiveContainer width="100%" height={300}>
                <AreaChart data={revenueQuery.data} margin={{ top: 10, right: 16, left: 0, bottom: 0 }}>
                  <defs>
                    <linearGradient id="revenueFill" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stopColor="#7c3aed" stopOpacity={0.25} />
                      <stop offset="100%" stopColor="#7c3aed" stopOpacity={0} />
                    </linearGradient>
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" vertical={false} />
                  <XAxis
                    dataKey="month"
                    tickFormatter={formatMonthLabel}
                    tick={{ fontSize: 12, fill: '#9ca3af' }}
                    axisLine={{ stroke: '#d1d5db' }}
                    tickLine={false}
                  />
                  <YAxis
                    tick={{ fontSize: 12, fill: '#9ca3af' }}
                    axisLine={false}
                    tickLine={false}
                    tickFormatter={(value) => currencyFormatter.format(Number(value))}
                    width={90}
                  />
                  <Tooltip
                    formatter={(value) => [currencyFormatter.format(Number(value)), 'Revenue']}
                    labelFormatter={(label) => formatMonthLabel(String(label))}
                    contentStyle={{ borderRadius: 8, border: '1px solid #e5e7eb', fontSize: 13 }}
                  />
                  <Area
                    type="monotone"
                    dataKey="total"
                    stroke="#7c3aed"
                    strokeWidth={2}
                    fill="url(#revenueFill)"
                    dot={{ r: 3, fill: '#7c3aed', strokeWidth: 0 }}
                    activeDot={{ r: 5 }}
                  />
                </AreaChart>
              </ResponsiveContainer>
            )}
          </div>
        </div>

        {/* Franchises by status */}
        <div className="card">
          <div className="card-header">
            <h2 className="card-title">Franchises by Status</h2>
          </div>
          <div className="card-body p-5">
            {statusQuery.isLoading ? (
              <div className="text-center py-8 text-gray-400">Loading...</div>
            ) : statusQuery.isError ? (
              <div className="text-center py-8 text-red-500">Failed to load.</div>
            ) : statusData.length === 0 ? (
              <div className="text-center py-8 text-gray-400">No data available.</div>
            ) : (
              <div className="flex flex-col gap-4">
                {statusData.map((item) => {
                  const pct = statusTotal > 0 ? Math.round((item.count / statusTotal) * 100) : 0
                  const color = statusBarColor[item.status] ?? '#7c3aed'
                  return (
                    <div key={item.status}>
                      <div className="flex items-center justify-between text-sm mb-1">
                        <span className="capitalize font-medium text-gray-700">{item.status}</span>
                        <span className="text-gray-500">{item.count} ({pct}%)</span>
                      </div>
                      <div className="w-full h-2 rounded-full bg-gray-100 overflow-hidden">
                        <div
                          className="h-full rounded-full"
                          style={{ width: `${pct}%`, background: color }}
                        />
                      </div>
                    </div>
                  )
                })}
              </div>
            )}
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {/* Recent activity */}
        <div className="card">
          <div className="card-header">
            <h2 className="card-title">Recent Activity</h2>
          </div>
          <div className="card-body">
            {activitiesQuery.isLoading ? (
              <div className="text-center py-8 text-gray-400">Loading...</div>
            ) : activitiesQuery.isError ? (
              <div className="text-center py-8 text-red-500">Failed to load recent activity.</div>
            ) : !activitiesQuery.data || activitiesQuery.data.length === 0 ? (
              <div className="text-center py-8 text-gray-400">No recent activity.</div>
            ) : (
              <ul className="divide-y divide-gray-100">
                {activitiesQuery.data.map((activity, idx) => (
                  <li key={idx} className="flex items-start gap-3 px-5 py-3">
                    <span
                      className={`mt-0.5 flex items-center justify-center w-8 h-8 rounded-full flex-shrink-0 ${
                        activity.type === 'ticket_created' ? 'bg-red-50 text-red-500' : 'bg-purple-50 text-purple-600'
                      }`}
                    >
                      {activity.type === 'ticket_created' ? <Ticket size={16} /> : <Store size={16} />}
                    </span>
                    <div className="min-w-0">
                      <div className="text-sm text-gray-700">{activity.message}</div>
                      <div className="text-xs text-gray-400 mt-0.5">{formatActivityDate(activity.date)}</div>
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>

        {/* Top franchises */}
        <div className="card lg:col-span-2">
          <div className="card-header">
            <h2 className="card-title">Top Franchises by Revenue</h2>
          </div>
          <div className="card-body p-0">
            <table className="table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Code</th>
                  <th>Owner</th>
                  <th>State</th>
                  <th>Status</th>
                  <th>Total Revenue</th>
                </tr>
              </thead>
              <tbody>
                {topFranchisesQuery.isLoading ? (
                  <tr>
                    <td colSpan={6} className="text-center py-8 text-gray-400">Loading...</td>
                  </tr>
                ) : topFranchisesQuery.isError ? (
                  <tr>
                    <td colSpan={6} className="text-center py-8 text-red-500">Failed to load top franchises.</td>
                  </tr>
                ) : !topFranchisesQuery.data || topFranchisesQuery.data.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="text-center py-8 text-gray-400">No franchise revenue data.</td>
                  </tr>
                ) : (
                  topFranchisesQuery.data.map((franchise) => (
                    <tr key={franchise.id}>
                      <td className="font-medium">{franchise.name}</td>
                      <td>{franchise.code}</td>
                      <td>{franchise.owner_name}</td>
                      <td>{franchise.state ?? '—'}</td>
                      <td><StatusBadge status={franchise.status} /></td>
                      <td className="font-medium">{currencyFormatter.format(franchise.total_revenue)}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  )
}
