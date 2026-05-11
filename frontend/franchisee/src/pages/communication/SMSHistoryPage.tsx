import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { TablePagination } from '../../components/ui/TablePagination'
import { communicationHistoryApi } from '../../api/services'
import type { SmsHistory } from '../../types'
import { format, parseISO } from 'date-fns'

function formatDateTime(dateStr: string | null | undefined): string {
  if (!dateStr) return '—'
  try {
    return format(parseISO(dateStr), 'EEEE, do MMM yyyy')
  } catch {
    return dateStr
  }
}

function formatTime(dateStr: string | null | undefined): string {
  if (!dateStr) return ''
  try {
    return format(parseISO(dateStr), 'h:mm a')
  } catch {
    return ''
  }
}

export function SMSHistoryPage() {
  const [activeTab, setActiveTab] = useState<'SUCCESSFULLY SENT' | 'ON QUEUES'>('SUCCESSFULLY SENT')
  const [perPage, setPerPage] = useState(25)
  const [page, setPage] = useState(1)

  const status = activeTab === 'SUCCESSFULLY SENT' ? 'sent' : 'queued'

  const { data, isLoading } = useQuery({
    queryKey: ['sms-history', status, page, perPage],
    queryFn: () =>
      communicationHistoryApi.getSmsHistory({ status, page, per_page: perPage }),
  })

  const messages: SmsHistory[] = data?.data?.data ?? []
  const meta = data?.data?.meta

  const tabs = ['SUCCESSFULLY SENT', 'ON QUEUES'] as const

  return (
    <div className="space-y-5 px-1 py-1">
      <Card className="px-6 py-4 shadow-sm border-gray-200">
        <h1 className="text-xl font-bold text-gray-800">SMS History</h1>
      </Card>

      <Card className="shadow-sm border-gray-200">
        <div className="flex gap-8 px-5 border-b border-gray-200">
          {tabs.map((tab) => (
            <button
              key={tab}
              onClick={() => { setActiveTab(tab); setPage(1) }}
              className={`pb-3 pt-3 text-sm font-semibold tracking-wide transition-colors relative ${
                activeTab === tab
                  ? 'text-pink-600'
                  : 'text-gray-500 hover:text-gray-700'
              }`}
            >
              {tab}
              {activeTab === tab && (
                <span className="absolute bottom-0 left-0 w-full h-0.5 bg-pink-600" />
              )}
            </button>
          ))}
        </div>
      </Card>

      <Card className="shadow-sm border-gray-200 overflow-visible">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="px-5 py-4 text-sm font-semibold text-gray-800">Date/Time</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800">Sent to Number</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800">Customer Name</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800">Message</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {isLoading ? (
                <tr>
                  <td colSpan={5} className="px-5 py-8 text-center text-gray-500 italic">Loading SMS history...</td>
                </tr>
              ) : messages.length === 0 ? (
                <tr>
                  <td colSpan={5} className="px-5 py-8 text-center text-gray-500 italic">No SMS history found.</td>
                </tr>
              ) : (
                messages.map((row) => (
                  <tr key={row.id} className="hover:bg-gray-50">
                    <td className="relative px-5 py-4 text-sm text-gray-700 align-top whitespace-nowrap">
                      <div className="absolute left-0 top-0 bottom-0 w-1 bg-green-400" />
                      <div>{formatDateTime(row.sent_at ?? row.created_at)}</div>
                      <div className="text-gray-400 text-xs mt-0.5">{formatTime(row.sent_at ?? row.created_at)}</div>
                    </td>
                    <td className="px-5 py-4 text-sm font-semibold text-gray-700 align-top">{row.to_number}</td>
                    <td className="px-5 py-4 text-sm text-gray-700 align-top">{row.customer_name ?? '—'}</td>
                    <td className="px-5 py-4 text-sm text-gray-600 align-top max-w-sm">{row.message}</td>
                    <td className={`px-5 py-4 text-sm font-medium align-top ${
                      row.status === 'sent' ? 'text-green-700' : row.status === 'failed' ? 'text-red-600' : 'text-yellow-600'
                    }`}>
                      {row.status === 'sent' ? 'Sent to Gateway' : row.status === 'queued' ? 'On Queue' : 'Failed'}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {meta && (
          <TablePagination
            meta={meta}
            onPageChange={setPage}
            onPerPageChange={setPerPage}
            isLoading={isLoading}
          />
        )}
      </Card>
    </div>
  )
}

