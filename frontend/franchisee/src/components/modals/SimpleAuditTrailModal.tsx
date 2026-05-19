import { useEffect, useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { History, Info, Loader2 } from 'lucide-react'
import { Modal } from '../ui/Modal'
import { formatDisplayDate, formatDisplayDateTime, formatDisplayTime } from '../../lib/timeFormatUtils'

type AuditResponse = {
  data: any[]
  current_page: number
  last_page: number
}

interface SimpleAuditTrailModalProps {
  isOpen: boolean
  onClose: () => void
  entityId?: string | number
  title: string
  subtitle?: string
  contextFields?: Array<{ label: string; value: string }>
  dynamicColumns?: boolean
  dynamicColumnPriority?: string[]
  dynamicColumnExclude?: string[]
  tableColumns?: Array<{
    key: string
    header: string
    render: (audit: any) => string
    className?: string
  }>
  queryKeyPrefix: string
  fetchAudits: (id: string, page: number) => Promise<AuditResponse>
}

function prettyAction(actionType?: string) {
  if (!actionType) return '-'
  return actionType.replace(/_/g, ' ')
}

const labelMap: Record<string, string> = {
  title: 'Title',
  location: 'Location',
  start_date: 'Start Date',
  start_time: 'Start Time',
  end_date: 'End Date',
  end_time: 'End Time',
  is_recurring: 'Recurring',
  repeat_every: 'Repeat Every',
  repeat_on: 'Repeat On',
  repeat_until: 'Repeat Until',
  notes: 'Notes',
  active: 'Active',
  status: 'Status',
  previous_status: 'Previous Status',
  frequency: 'Frequency',
  repeat_day: 'Repeat Day',
  repeat_time: 'Repeat Time',
  auto_extend: 'Auto Extend',
  cancelled_date: 'Cancelled Date',
  cancellation_reason: 'Cancellation Reason',
  color: 'Color',
  service_id: 'Service',
  item_id: 'Pet',
  price: 'Price',
  duration: 'Duration (min)',
  income_category_id: 'Income Category',
  expense_category_id: 'Expense Category',
  booking_id: 'Booking',
  income_date: 'Income Date',
  expense_date: 'Expense Date',
  amount: 'Amount',
  is_active: 'Active',
  recurring_income_id: 'Recurring Income',
  recurring_expense_id: 'Recurring Expense',
}

function toHumanLabel(key: string) {
  if (labelMap[key]) return labelMap[key]

  return key
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase())
}

function formatAuditValue(key: string, value: unknown): string {
  if (value === null || value === undefined || value === '') return '-'

  if (typeof value === 'boolean') {
    return value ? 'Yes' : 'No'
  }

  if (typeof value === 'number' && (key === 'amount' || key === 'price' || key === 'total')) {
    return `$${value.toFixed(2)}`
  }

  if (typeof value === 'string' && key.endsWith('_time')) {
    return formatDisplayTime(value)
  }

  if (typeof value === 'string' && key.endsWith('_date')) {
    return formatDisplayDate(value)
  }

  if (Array.isArray(value)) {
    return value.join(', ')
  }

  if (typeof value === 'object') {
    try {
      return JSON.stringify(value)
    } catch {
      return String(value)
    }
  }

  return String(value)
}

function getSnapshotEntries(audit: any): Array<[string, any]> {
  const ignored = new Set([
    'id',
    'action_at',
    'created_at',
    'updated_at',
    'action_type',
    'performed_by',
    'performed_by_name',
    'company_id',
    'blockout_id',
    'booking_detail_id',
    'booking_recurring_id',
    'booking_recurring_detail_id',
    'income_id',
    'expense_id',
  ])

  return Object.entries(audit || {}).filter(([key, value]) => {
    if (ignored.has(key)) return false
    if (value === null || value === undefined || value === '') return false
    return true
  })
}

function getAuditSummary(audit: any): string {
  const entries = getSnapshotEntries(audit)

  if (entries.length === 0) {
    return '-'
  }

  return entries
    .slice(0, 2)
    .map(([key, value]) => `${toHumanLabel(key)}: ${formatAuditValue(key, value)}`)
    .join(' | ')
}

function getPerformerLabel(audit: any): string {
  if (audit?.performed_by_name) return audit.performed_by_name
  if (audit?.performed_by) return `User #${audit.performed_by}`
  return '-'
}

function getActionTimestamp(audit: any): string {
  return audit?.action_at || audit?.created_at || ''
}

export function SimpleAuditTrailModal({
  isOpen,
  onClose,
  entityId,
  title,
  subtitle,
  contextFields,
  dynamicColumns,
  dynamicColumnPriority,
  dynamicColumnExclude,
  tableColumns,
  queryKeyPrefix,
  fetchAudits,
}: SimpleAuditTrailModalProps) {
  const [page, setPage] = useState(1)
  const resolvedEntityId = entityId === null || entityId === undefined ? '' : String(entityId)

  useEffect(() => {
    if (isOpen) {
      setPage(1)
    }
  }, [isOpen, resolvedEntityId])

  const { data: response, isLoading } = useQuery({
    queryKey: [queryKeyPrefix, resolvedEntityId, page],
    queryFn: () => (resolvedEntityId ? fetchAudits(resolvedEntityId, page) : Promise.resolve({ data: [], current_page: 1, last_page: 1 })),
    enabled: isOpen && resolvedEntityId.length > 0,
    refetchOnMount: 'always',
  })

  const rows = response?.data ?? []
  const currentPage = response?.current_page ?? 1
  const lastPage = response?.last_page ?? 1

  const dynamicColumnKeys = useMemo(() => {
    if (!dynamicColumns || rows.length === 0) return []

    const exclude = new Set(dynamicColumnExclude || [])
    const keys = new Set<string>()

    rows.forEach((row: any) => {
      Object.keys(row || {}).forEach((key) => {
        if (!exclude.has(key)) {
          keys.add(key)
        }
      })
    })

    const allKeys = Array.from(keys)
    if (!dynamicColumnPriority || dynamicColumnPriority.length === 0) {
      return allKeys
    }

    const priority = dynamicColumnPriority.filter((key) => keys.has(key))
    const rest = allKeys.filter((key) => !priority.includes(key))
    return [...priority, ...rest]
  }, [dynamicColumns, dynamicColumnExclude, dynamicColumnPriority, rows])

  const activeColumns =
    tableColumns && tableColumns.length > 0
      ? tableColumns
      : dynamicColumns && dynamicColumnKeys.length > 0
      ? dynamicColumnKeys.map((key) => ({
          key,
          header: toHumanLabel(key),
          render: (audit: any) => {
            const value = audit?.[key]
            if (key === 'action_type') {
              return prettyAction(value)
            }
            if (key.endsWith('_at') && typeof value === 'string') {
              return formatDisplayDateTime(value)
            }
            return formatAuditValue(key, value)
          },
          className: 'px-5 py-4 text-sm text-gray-700 align-top',
        }))
      : [
          {
            key: 'action',
            header: 'Action',
            render: (audit: any) => prettyAction(audit.action_type),
            className: 'px-5 py-4 text-sm text-gray-700 align-top',
          },
          {
            key: 'action_at',
            header: 'Action At',
            render: (audit: any) => formatDisplayDateTime(getActionTimestamp(audit)) || '-',
            className: 'px-5 py-4 text-sm text-gray-700 align-top',
          },
          {
            key: 'performed_by',
            header: 'Performed By',
            render: (audit: any) => getPerformerLabel(audit),
            className: 'px-5 py-4 text-sm text-gray-700 align-top',
          },
          {
            key: 'summary',
            header: 'Summary',
            render: (audit: any) => getAuditSummary(audit),
            className: 'max-w-[420px] px-5 py-4 text-sm text-gray-600 align-top',
          },
        ]

  return (
    <Modal isOpen={isOpen} onClose={onClose} title={title} size="full">
      <div className="flex flex-col gap-6">
        <div className="flex items-center gap-3 rounded-lg border border-blue-100 bg-blue-50 p-4">
          <div className="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-700">
            <History className="h-6 w-6" />
          </div>
          <div>
            <h3 className="text-lg font-bold text-gray-800">Audit Trail</h3>
            <p className="text-xs font-bold uppercase tracking-wider text-gray-500">
              {subtitle || 'Create, update, and delete events'}
            </p>
          </div>
        </div>

        {contextFields && contextFields.length > 0 ? (
          <div className="rounded-lg border border-gray-200 bg-white p-4">
            <div className="mb-3 text-xs font-bold uppercase tracking-wider text-gray-500">Booking Details</div>
            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
              {contextFields.map((field) => (
                <div key={field.label} className="min-w-0">
                  <div className="text-[11px] font-bold uppercase tracking-wider text-gray-400">{field.label}</div>
                  <div className="truncate text-sm text-gray-700" title={field.value}>{field.value}</div>
                </div>
              ))}
            </div>
          </div>
        ) : null}

        {isLoading ? (
          <div className="flex justify-center py-12">
            <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
          </div>
        ) : rows.length === 0 ? (
          <div className="rounded-lg border border-dashed border-gray-200 bg-gray-50 py-12 text-center">
            <Info className="mx-auto mb-3 h-8 w-8 text-gray-300" />
            <p className="text-sm italic text-gray-400">No audit history recorded yet.</p>
          </div>
        ) : (
          <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="border-b border-gray-200">
                    {activeColumns.map((column) => (
                      <th key={column.key} className="px-5 py-4 text-sm font-semibold text-gray-800 align-top">
                        {column.header}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {rows.map((audit: any) => (
                    <tr key={audit.id} className="hover:bg-gray-50 relative group">
                      {activeColumns.map((column) => {
                        const value = column.render(audit) || '-'
                        return (
                          <td key={`${audit.id}-${column.key}`} className={column.className || 'px-5 py-4 text-sm text-gray-700 align-top'}>
                            <p className="truncate" title={value}>{value}</p>
                          </td>
                        )
                      })}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {lastPage > 1 ? (
          <div className="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
            <button
              onClick={() => setPage((current) => Math.max(1, current - 1))}
              disabled={currentPage === 1}
              className="rounded border bg-white px-4 py-2 text-sm font-bold text-gray-600 disabled:opacity-50"
            >
              Previous
            </button>
            <span className="text-xs font-bold uppercase tracking-wider text-gray-400">
              Page {currentPage} of {lastPage}
            </span>
            <button
              onClick={() => setPage((current) => Math.min(lastPage, current + 1))}
              disabled={currentPage === lastPage}
              className="rounded border bg-white px-4 py-2 text-sm font-bold text-gray-600 disabled:opacity-50"
            >
              Next
            </button>
          </div>
        ) : null}

      </div>
    </Modal>
  )
}
