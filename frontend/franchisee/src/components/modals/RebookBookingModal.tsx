import { useEffect, useMemo, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { Modal } from '../ui/Modal'
import { Button } from '../ui/Button'
import { Input } from '../ui/Input'
import { bookingsApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import type { Booking } from '../../types'
import { formatDisplayDate, formatDisplayTime } from '../../lib/timeFormatUtils'

interface RebookBookingModalProps {
  isOpen: boolean
  onClose: () => void
  booking: Booking | null
}

export function RebookBookingModal({ isOpen, onClose, booking }: RebookBookingModalProps) {
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)
  const [startDate, setStartDate] = useState('')
  const [startTime, setStartTime] = useState('')

  useEffect(() => {
    if (!isOpen || !booking) {
      return
    }

    setStartDate(booking.startDate || '')
    setStartTime(booking.startTime || '')
  }, [isOpen, booking])

  const totalDuration = useMemo(() => {
    if (!booking?.details?.length) {
      return 30
    }

    const duration = booking.details.reduce((sum, detail) => sum + Number(detail.service?.duration || 30), 0)
    return Math.max(30, duration)
  }, [booking])

  const rebookMutation = useMutation({
    mutationFn: () => {
      if (!booking) {
        throw new Error('No booking selected')
      }

      return bookingsApi.rebook(booking.id, {
        start_date: startDate,
        start_time: startTime,
        end_time: booking.endTime || undefined,
      })
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bookings'] })
      addToast('Booking rebooked successfully', 'success')
      onClose()
    },
    onError: () => {
      addToast('Failed to rebook booking', 'error')
    },
  })

  if (!booking) {
    return null
  }

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title="Re Book"
      size="lg"
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>
            Cancel
          </Button>
          <Button
            onClick={() => rebookMutation.mutate()}
            disabled={!startDate || !startTime || rebookMutation.isPending}
          >
            {rebookMutation.isPending ? 'Rebooking...' : 'Re Book'}
          </Button>
        </>
      }
    >
      <div className="space-y-5">
        <div className="rounded-lg border border-secondary-200 bg-secondary-50 p-4 text-sm text-secondary-700">
          <div className="font-semibold text-secondary-900">
            {booking.customer?.first_name} {booking.customer?.last_name}
          </div>
          <div className="mt-1">{booking.customer?.address || 'No address'}</div>
          <div className="mt-2 text-xs text-secondary-500">
            Original booking: {booking.startDate ? formatDisplayDate(booking.startDate) : 'No date'} {booking.startTime ? formatDisplayTime(booking.startTime) : 'No time'}
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Input
            label="Booking Date"
            type="date"
            value={startDate}
            onChange={(e) => setStartDate(e.target.value)}
          />
          <Input
            label="Booking Time"
            type="time"
            value={startTime}
            onChange={(e) => setStartTime(e.target.value)}
          />
        </div>

        <div className="rounded-lg border border-secondary-200 p-4">
          <div className="text-sm font-semibold text-secondary-900">Booking Summary</div>
          <div className="mt-3 space-y-2 text-sm text-secondary-700">
            <div>Services: {booking.details?.length || 0}</div>
            <div>Total: ${Number(booking.total || 0).toFixed(2)}</div>
            <div>Duration: {totalDuration} mins</div>
          </div>
        </div>
      </div>
    </Modal>
  )
}