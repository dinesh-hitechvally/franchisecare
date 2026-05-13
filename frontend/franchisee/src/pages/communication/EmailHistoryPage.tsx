import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { TablePagination } from '../../components/ui/TablePagination'
import { Modal } from '../../components/ui/Modal'
import { communicationHistoryApi } from '../../api/services'
import type { EmailHistory } from '../../types'
import { format, parseISO } from 'date-fns'

type StatusTab = {
  label: 'SUCCESSFULLY SENT' | 'ON QUEUES'
  status: 'sent' | 'queued'
}

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

export function EmailHistoryPage() {
  const [activeStatus, setActiveStatus] = useState<'sent' | 'queued'>('sent')
  const [perPage, setPerPage] = useState(25)
  const [page, setPage] = useState(1)
  const [viewEmail, setViewEmail] = useState<EmailHistory | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: ['email-history', activeStatus, page, perPage],
    queryFn: () =>
      communicationHistoryApi.getEmailHistory({ status: activeStatus, page, per_page: perPage }),
  })

  const emails: EmailHistory[] = data?.data ?? []
  const meta = data?.meta

  const tabs: StatusTab[] = [
    { label: 'SUCCESSFULLY SENT', status: 'sent' },
    { label: 'ON QUEUES', status: 'queued' },
  ]

  return (
    <div className="space-y-5 px-1 py-1">
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Email History</h1>
      </div>

      <Card className="shadow-sm border-gray-200">
        <div className="flex gap-8 px-5 border-b border-gray-200">
          {tabs.map((tab) => (
            <button
              key={tab.status}
              onClick={() => {
                setActiveStatus(tab.status)
                setPage(1)
              }}
              className={`pb-3 pt-3 text-sm font-semibold tracking-wide transition-colors relative ${
                activeStatus === tab.status
                  ? 'text-pink-600'
                  : 'text-gray-500 hover:text-gray-700'
              }`}
            >
              {tab.label}
              {activeStatus === tab.status && (
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
                <th className="px-5 py-4 text-sm font-semibold text-gray-800">Subject</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800">From Email</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800">To Email</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-right">View Email</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {isLoading ? (
                <tr>
                  <td colSpan={5} className="px-5 py-8 text-center text-gray-500 italic">Loading email history...</td>
                </tr>
              ) : emails.length === 0 ? (
                <tr>
                  <td colSpan={5} className="px-5 py-8 text-center text-gray-500 italic">No email history found</td>
                </tr>
              ) : (
                emails.map((row) => (
                  <tr key={row.id} className="hover:bg-gray-50">
                    <td className="relative px-5 py-4 text-sm text-gray-700 align-top whitespace-nowrap">
                      <div className="absolute left-0 top-0 bottom-0 w-1 bg-green-400" />
                      <div>{formatDateTime(row.sent_at ?? row.created_at)}</div>
                      <div className="text-gray-400 text-xs mt-0.5">{formatTime(row.sent_at ?? row.created_at)}</div>
                    </td>
                    <td className="px-5 py-4 text-sm text-gray-700 align-top">{row.subject}</td>
                    <td className="px-5 py-4 text-sm text-gray-600 align-top">{row.from_email}</td>
                    <td className="px-5 py-4 text-sm text-gray-600 align-top">{row.to_email}</td>
                    <td className="px-5 py-4 align-top text-right">
                      <button
                        onClick={() => setViewEmail(row)}
                        className="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold tracking-wider rounded uppercase transition-colors"
                      >
                        VIEW
                      </button>
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

      {/* Email body viewer modal */}
      {viewEmail && (
        <Modal
          isOpen={!!viewEmail}
          onClose={() => setViewEmail(null)}
          title={viewEmail.subject}
        >
          <div className="space-y-3 text-sm text-gray-700">
            <div className="flex gap-2">
              <span className="font-semibold w-20 shrink-0">From:</span>
              <span>{viewEmail.from_email}</span>
            </div>
            <div className="flex gap-2">
              <span className="font-semibold w-20 shrink-0">To:</span>
              <span>{viewEmail.to_email}</span>
            </div>
            <div className="flex gap-2">
              <span className="font-semibold w-20 shrink-0">Sent:</span>
              <span>{formatDateTime(viewEmail.sent_at ?? viewEmail.created_at)}</span>
            </div>
            <hr />
            <div
              className="prose max-w-none text-gray-800"
              dangerouslySetInnerHTML={{ __html: viewEmail.body ?? '<em>No content</em>' }}
            />
          </div>
        </Modal>
      )}
    </div>
  )
}

