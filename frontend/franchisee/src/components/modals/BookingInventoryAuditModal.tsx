import React from 'react'
import { useQuery } from '@tanstack/react-query'
import { Boxes, Info, Loader2 } from 'lucide-react'
import { Modal } from '../ui/Modal'
import { bookingsApi } from '../../api/services'
import type { Booking, BookingInventoryAuditEntry } from '../../types'
import { formatDisplayDateTime } from '../../lib/timeFormatUtils'

interface BookingInventoryAuditModalProps {
  isOpen: boolean
  onClose: () => void
  booking: Booking | null
}

function prettifyChangeType(changeType: string) {
  return changeType.replace(/_/g, ' ')
}

export const BookingInventoryAuditModal: React.FC<BookingInventoryAuditModalProps> = ({ isOpen, onClose, booking }) => {
  const [page, setPage] = React.useState(1)

  const { data: response, isLoading } = useQuery({
    queryKey: ['booking-inventory-audits', booking?.id, page],
    queryFn: () => (booking ? bookingsApi.getInventoryAudits(booking.id, page) : Promise.resolve({ data: [], current_page: 1, last_page: 1 })),
    enabled: !!booking && isOpen,
  })

  React.useEffect(() => {
    if (isOpen) setPage(1)
  }, [isOpen, booking?.id])

  const audits = response?.data ?? []
  const currentPage = response?.current_page ?? 1
  const lastPage = response?.last_page ?? 1

  return (
    <Modal isOpen={isOpen} onClose={onClose} title="Inventory Audit Trail" size="lg">
      <div className="space-y-6">
        <div className="flex items-center gap-3 rounded-lg border border-amber-100 bg-amber-50 p-4">
          <div className="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-700">
            <Boxes className="h-6 w-6" />
          </div>
          <div>
            <h3 className="text-lg font-bold text-gray-800">Booking #{booking?.id}</h3>
            <p className="text-xs font-bold uppercase tracking-wider text-gray-500">
              Booking-linked inventory history and operational stock notes
            </p>
          </div>
        </div>

        {isLoading ? (
          <div className="flex justify-center py-12">
            <Loader2 className="h-8 w-8 animate-spin text-amber-600" />
          </div>
        ) : audits.length === 0 ? (
          <div className="rounded-lg border border-dashed border-gray-200 bg-gray-50 py-12 text-center">
            <Info className="mx-auto mb-3 h-8 w-8 text-gray-300" />
            <p className="text-sm italic text-gray-400">No inventory-related history has been recorded for this booking yet.</p>
          </div>
        ) : (
          <div className="space-y-4">
            {audits.map((audit: BookingInventoryAuditEntry) => (
              <div key={audit.id} className="rounded-lg border border-gray-100 bg-white p-4 shadow-sm">
                <div className="mb-3 flex items-center justify-between">
                  <span className="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-700">
                    {prettifyChangeType(audit.change_type)}
                  </span>
                  <span className="text-xs text-gray-400">{formatDisplayDateTime(audit.created_at)}</span>
                </div>
                <div className="grid gap-4 md:grid-cols-2">
                  <div>
                    <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400">Inventory Item</p>
                    <p className="text-sm font-medium text-gray-700">{audit.inventory_item_name || 'Unknown item'}</p>
                  </div>
                  <div>
                    <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400">Quantity Change</p>
                    <p className="text-sm text-gray-700">
                      {audit.quantity_before ?? '-'} to {audit.quantity_after ?? '-'}
                      {audit.quantity_change !== undefined && audit.quantity_change !== null ? ` (${audit.quantity_change > 0 ? '+' : ''}${audit.quantity_change})` : ''}
                    </p>
                  </div>
                </div>
                {audit.notes ? (
                  <div className="mt-3 rounded-lg bg-gray-50 p-3 text-sm text-gray-600">
                    {audit.notes}
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