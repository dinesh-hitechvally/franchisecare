import { useEffect } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { bookingsApi } from '../../api/services'
import type { Booking } from '../../types'
import { SimpleAuditTrailModal } from './SimpleAuditTrailModal'
import { formatDisplayDateTime } from '../../lib/timeFormatUtils'

interface BookingAuditModalProps {
  isOpen: boolean
  onClose: () => void
  booking: Booking | null
}

export const BookingAuditModal: React.FC<BookingAuditModalProps> = ({ isOpen, onClose, booking }) => {
  const bookingId =
    booking?.id ??
    (booking as any)?.booking_id ??
    (booking as any)?.bookingId ??
    undefined

  const queryClient = useQueryClient()

  useEffect(() => {
    if (isOpen && bookingId) {
      queryClient.invalidateQueries({ queryKey: ['booking-audits', String(bookingId)] })
    }
  }, [isOpen, bookingId, queryClient])

  const customerName = `${booking?.customer?.first_name || ''} ${booking?.customer?.last_name || ''}`.trim() || '-'

  const tableColumns = [
    {
      key: 'id',
      header: 'ID',
      render: (audit: any) => String(audit?.id ?? '-'),
    },
    {
      key: 'name',
      header: 'Name',
      render: (audit: any) => String(audit?.customer_name || customerName),
    },
    {
      key: 'address',
      header: 'Address',
      render: (audit: any) => audit?.customer_address || booking?.customer?.address || '-',
    },
    {
      key: 'email',
      header: 'Email',
      render: (audit: any) => audit?.customer_email || booking?.customer?.email || '-',
    },
    {
      key: 'contact_number',
      header: 'Contact Number',
      render: (audit: any) => audit?.customer_phone || booking?.customer?.phone || '-',
    },
    {
      key: 'action_type',
      header: 'Action Type',
      render: (audit: any) => (audit?.action_type ? String(audit.action_type).replace(/_/g, ' ') : '-'),
    },
    {
      key: 'action_at',
      header: 'Action At',
      render: (audit: any) => {
        const value = audit?.action_at || audit?.created_at
        return value ? formatDisplayDateTime(String(value)) : '-'
      },
    },
  ]

  return (
    <SimpleAuditTrailModal
      isOpen={isOpen}
      onClose={onClose}
      entityId={bookingId}
      title="Booking Audit Trail"
      subtitle="Create, update, status, and communication actions for this booking"
      tableColumns={tableColumns}
      queryKeyPrefix="booking-audits"
      fetchAudits={(id, page) => bookingsApi.getAudits(id, page) as Promise<any>}
    />
  )
}
