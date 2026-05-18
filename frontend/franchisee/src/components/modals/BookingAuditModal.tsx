import React from 'react'
import { useQuery } from '@tanstack/react-query'
import { Clock3, History, Info, Loader2 } from 'lucide-react'
import { Modal } from '../ui/Modal'
import { bookingsApi } from '../../api/services'
import type { Booking, BookingAuditEntry } from '../../types'
import { formatDisplayDate, formatDisplayDateTime, formatDisplayTime } from '../../lib/timeFormatUtils'

interface BookingAuditModalProps {
  isOpen: boolean
  onClose: () => void
  booking: Booking | null
}

function prettifyAction(action: string) {
  return action.replace(/_/g, ' ')
}

export const BookingAuditModal: React.FC<BookingAuditModalProps> = ({ isOpen, onClose, booking }) => {
  const [page, setPage] = React.useState(1)

  const { data: response, isLoading } = useQuery({
    queryKey: ['booking-audits', booking?.id, page],
    queryFn: () => (booking ? bookingsApi.getAudits(booking.id, page) : Promise.resolve({ data: [], current_page: 1, last_page: 1 })),
    enabled: !!booking && isOpen,
  })

  React.useEffect(() => {
    if (isOpen) setPage(1)
  }, [isOpen, booking?.id])

  const audits = response?.data ?? []
  const currentPage = response?.current_page ?? 1
  const lastPage = response?.last_page ?? 1

  return (
    <Modal isOpen={isOpen} onClose={onClose} title="Booking Audit Trail" size="lg">
      <div className="space-y-6">
        <div className="flex items-center gap-3 rounded-lg border border-blue-100 bg-blue-50 p-4">
          <div className="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-700">
            <History className="h-6 w-6" />
          </div>
          <div>
            <h3 className="text-lg font-bold text-gray-800">Booking #{booking?.id}</h3>
            <p className="text-xs font-bold uppercase tracking-wider text-gray-500">
              History of status, rebook, and communication actions
            </p>
          </div>
        </div>

        {isLoading ? (
          <div className="flex justify-center py-12">
            <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
          </div>
        ) : audits.length === 0 ? (
          <div className="rounded-lg border border-dashed border-gray-200 bg-gray-50 py-12 text-center">
            <Info className="mx-auto mb-3 h-8 w-8 text-gray-300" />
            <p className="text-sm italic text-gray-400">No booking history recorded yet.</p>
          </div>
        ) : (
          <div className="space-y-4">
            {audits.map((audit: BookingAuditEntry) => (
              <div key={audit.id} className="rounded-lg border border-gray-100 bg-white shadow-sm">
                <div className="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-4 py-3">
                  <div className="flex items-center gap-2">
                    <span className="rounded-full bg-blue-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-blue-700">
                      {prettifyAction(audit.action_type)}
                    </span>
                    {audit.previous_status && audit.status && audit.previous_status !== audit.status ? (
                      <span className="text-xs text-gray-500">
                        {audit.previous_status} to {audit.status}
                      </span>
                    ) : null}
                  </div>
                  <span className="text-xs text-gray-400">
                    {formatDisplayDateTime(audit.created_at)}
                  </span>
                </div>

                <div className="grid gap-4 px-4 py-4 md:grid-cols-2">
                  <div>
                    <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400">Schedule</p>
                    <p className="text-sm font-medium text-gray-700">
                      {audit.start_date ? formatDisplayDate(audit.start_date) : 'No date'}
                    </p>
                    <p className="mt-1 flex items-center gap-1 text-sm text-gray-500">
                      <Clock3 className="h-3.5 w-3.5" />
                      {audit.start_time ? formatDisplayTime(audit.start_time) : 'No start time'}
                      {audit.end_time ? ` - ${formatDisplayTime(audit.end_time)}` : ''}
                    </p>
                  </div>

                  <div>
                    <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400">Snapshot</p>
                    <p className="text-sm text-gray-700">Status: <span className="font-semibold capitalize">{audit.status || '-'}</span></p>
                    <p className="text-sm text-gray-700">Total: <span className="font-semibold">${Number(audit.total || 0).toFixed(2)}</span></p>
                    <p className="text-sm text-gray-700">Duration: <span className="font-semibold">{audit.duration || 0} min</span></p>
                  </div>
                </div>

                {audit.details_summary && audit.details_summary.length > 0 ? (
                  <div className="border-t border-gray-50 px-4 py-4">
                    <p className="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Services</p>
                    <div className="flex flex-wrap gap-2">
                      {audit.details_summary.map((detail, index) => (
                        <span key={`${audit.id}-${index}`} className="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600">
                          {(detail.pet_name || 'Pet')} - {(detail.service_name || 'Service')}
                        </span>
                      ))}
                    </div>
                  </div>
                ) : null}
              </div>
            ))}
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