import { Calendar, Clock, Edit2, RefreshCw, X, XCircle } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { Modal } from '../ui/Modal'
import type { Booking } from '../../types'
import { formatDisplayDate, formatDisplayDateTime, formatDisplayTime } from '../../lib/timeFormatUtils'
import { cn } from '../../lib/utils'

interface WaitlistDetailModalProps {
  isOpen: boolean
  onClose: () => void
  booking: Booking | null
  onConvert?: (bookingId: string) => void
  onCancel?: (bookingId: string) => void
  isConverting?: boolean
  isCancelling?: boolean
}

function getStatusLabel(status: string) {
  return status.charAt(0).toUpperCase() + status.slice(1)
}

function getStatusTone(status: string) {
  if (status === 'completed') return 'bg-emerald-100 text-emerald-700'
  if (status === 'cancelled') return 'bg-red-100 text-red-700'
  if (status === 'expired') return 'bg-amber-100 text-amber-700'
  return 'bg-blue-100 text-blue-700'
}

function isActionableStatus(status: string) {
  return status === 'active'
}

function ActionButton({
  label,
  icon: Icon,
  onClick,
  disabled,
  loading,
  variant = 'primary',
}: {
  label: string
  icon: React.ElementType
  onClick: () => void
  disabled?: boolean
  loading?: boolean
  variant?: 'primary' | 'danger' | 'secondary'
}) {
  const styles = {
    primary: 'border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100',
    danger: 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100',
    secondary: 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100',
  }

  return (
    <button
      onClick={onClick}
      disabled={disabled || loading}
      className={cn(
        'flex items-center justify-between rounded-xl border px-4 py-3 text-left transition-colors disabled:cursor-not-allowed disabled:opacity-60',
        styles[variant]
      )}
    >
      <span className="flex items-center gap-3 text-sm font-bold">
        <Icon className="h-4 w-4" />
        {loading ? 'Processing...' : label}
      </span>
      <span className="text-xs uppercase tracking-wider opacity-60">Action</span>
    </button>
  )
}

export function WaitlistDetailModal({
  isOpen,
  onClose,
  booking,
  onConvert,
  onCancel,
  isConverting,
  isCancelling,
}: WaitlistDetailModalProps) {
  const navigate = useNavigate()

  if (!booking) return null

  const details = booking.details || []
  const firstDetail = details[0]
  const serviceLabel = details
    .map((detail) => detail.service?.name)
    .filter((name): name is string => Boolean(name))
    .join(', ')
  const serviceLocation = booking.customer?.address || booking.customer?.street_address || '-'
  const canManage = isActionableStatus(booking.status)
  const showManageActions = booking.status !== 'completed' && booking.status !== 'archived'
  const customerName = `${booking.customer?.first_name || ''} ${booking.customer?.last_name || ''}`.trim() || 'Unknown customer'

  const handleEdit = () => {
    onClose()
    navigate(`/bookings/waitlist/edit/${booking.id}`)
  }

  return (
    <Modal isOpen={isOpen} onClose={onClose} size="xl">
      <div className="-mx-6 -mb-4 -mt-4">
        <div className="flex items-center justify-between bg-[#4a5ebc] px-6 py-4">
          <div>
            <h2 className="text-lg font-bold text-white">View Waitlist Detail</h2>
            <p className="mt-1 text-xs font-semibold uppercase tracking-wider text-white/70">Waitlist management by status</p>
          </div>
          <button onClick={onClose} className="text-white/80 transition-colors hover:text-white">
            <X className="h-6 w-6" />
          </button>
        </div>

        <div className="bg-white p-8">
          <div className="flex flex-col items-start gap-8 lg:flex-row">
            <div className="min-w-0 flex-1 space-y-8">
              <div className="space-y-3">
                <div className="flex items-center gap-3">
                  <h3 className="text-xl font-medium text-[#4a5ebc]">{customerName}</h3>
                  {booking.customer?.email ? <span className="text-sm text-[#4a5ebc]">{booking.customer.email}</span> : null}
                </div>
                <div className="w-full rounded-lg border border-yellow-100 bg-yellow-50 p-3 text-sm text-gray-700 shadow-sm">
                  {booking.notes || 'No notes available'}
                </div>
              </div>

              <div className="overflow-hidden rounded-lg border border-gray-100 shadow-sm">
                <div className="border-b border-gray-100 bg-gray-50 px-6 py-3">
                  <span className="text-sm font-bold text-gray-700">Waitlist Detail</span>
                </div>
                <div className={cn('h-2 w-full', booking.status === 'cancelled' ? 'bg-red-300' : booking.status === 'archived' ? 'bg-amber-300' : 'bg-[#6df19e]')} />

                <div className="space-y-6 p-6">
                  <div>
                    <label className="mb-1 block text-[13px] font-bold text-gray-800">Pet Name</label>
                    <p className="text-sm text-gray-700">{firstDetail?.pet?.name || '-'}</p>
                  </div>

                  <div>
                    <label className="mb-1 block text-[13px] font-bold text-gray-800">Services</label>
                    <p className="text-sm text-gray-700">{serviceLabel || '-'}</p>
                  </div>

                  <div>
                    <label className="mb-1 block text-[13px] font-bold text-gray-800">Service Location</label>
                    <p className="text-sm text-gray-700">{serviceLocation}</p>
                  </div>

                  <div>
                    <label className="mb-1 block text-[13px] font-bold text-gray-800">Date Range</label>
                    <p className="text-sm text-gray-700">From: {formatDisplayDate(booking.startDate)}</p>
                    <p className="text-sm text-gray-700">To: {formatDisplayDate(booking.endDate || booking.startDate)}</p>
                  </div>

                  <div className="grid gap-4 md:grid-cols-2">
                    <div className="flex items-center gap-2 text-sm text-gray-700">
                      <Clock className="h-4 w-4 text-gray-400" />
                      <span>{formatDisplayTime(booking.startTime) || '-'}</span>
                    </div>
                    <div className="flex items-center gap-2 text-sm text-gray-700">
                      <Calendar className="h-4 w-4 text-gray-400" />
                      <span>Total Cost: ${Number(booking.total || 0).toFixed(2)}</span>
                    </div>
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="text-[13px] font-bold text-gray-800">Status:</span>
                    <span className={cn('rounded-full px-3 py-1 text-xs font-bold capitalize', getStatusTone(booking.status))}>
                      {getStatusLabel(booking.status)}
                    </span>
                  </div>
                </div>
              </div>

              {showManageActions && (
                <div className="overflow-hidden rounded-lg border border-gray-100 shadow-sm">
                  <div className="border-b border-gray-100 bg-gray-50 px-6 py-4">
                    <span className="text-sm font-bold text-gray-700">Manage Actions</span>
                  </div>
                  <div className="space-y-6 p-6">
                    <div>
                      <p className="mb-3 text-[11px] font-bold uppercase tracking-wider text-gray-400">waitlist actions</p>
                      <div className="grid gap-3 md:grid-cols-2">
                        <ActionButton
                          label="Edit"
                          icon={Edit2}
                          onClick={handleEdit}
                          variant="primary"
                        />
                        <ActionButton
                          label="Convert To Booking"
                          icon={RefreshCw}
                          onClick={() => onConvert?.(booking.id)}
                          disabled={!canManage}
                          loading={isConverting}
                          variant="secondary"
                        />
                        <ActionButton
                          label="Cancel Waitlist"
                          icon={XCircle}
                          onClick={() => onCancel?.(booking.id)}
                          disabled={!canManage}
                          loading={isCancelling}
                          variant="danger"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              )}

              <div className="flex items-center justify-between border-t border-gray-50 pt-4 text-gray-400">
                <span className="text-[10px] font-medium uppercase tracking-tight">ID: {booking.id}</span>
                <span className="text-[10px] font-medium italic">Last Updated: {formatDisplayDateTime(booking.updatedAt)}</span>
              </div>
            </div>

            <div className="w-full shrink-0 space-y-4 lg:w-[240px]">
              <label className="block text-[11px] font-bold uppercase tracking-wider text-gray-400">Quick Summary</label>
              <div className="rounded-lg border border-gray-100 bg-gray-50 p-4 space-y-2">
                <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Customer</p>
                <p className="text-sm font-medium text-gray-700">{customerName}</p>
                <p className="text-xs font-semibold uppercase tracking-wide text-gray-500 pt-2">Time</p>
                <p className="text-sm text-gray-700">{formatDisplayTime(booking.startTime) || '-'}</p>
                <p className="text-xs font-semibold uppercase tracking-wide text-gray-500 pt-2">Range</p>
                <p className="text-sm text-gray-700">{formatDisplayDate(booking.startDate)}</p>
                <p className="text-sm text-gray-700">{formatDisplayDate(booking.endDate || booking.startDate)}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Modal>
  )
}
