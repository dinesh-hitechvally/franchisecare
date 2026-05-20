import { useEffect } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { waitlistApi } from '../../api/services'
import type { Booking } from '../../types'
import { SimpleAuditTrailModal } from './SimpleAuditTrailModal'
import { formatDisplayDateTime } from '../../lib/timeFormatUtils'

interface WaitlistAuditModalProps {
  isOpen: boolean
  onClose: () => void
  waitlist: Booking | null
}

export const WaitlistAuditModal: React.FC<WaitlistAuditModalProps> = ({ isOpen, onClose, waitlist }) => {
  const waitlistId =
    waitlist?.id ??
    (waitlist as any)?.waitlist_id ??
    (waitlist as any)?.waitlistId ??
    undefined

  const queryClient = useQueryClient()

  useEffect(() => {
    if (isOpen && waitlistId) {
      queryClient.invalidateQueries({ queryKey: ['waitlist-audits', String(waitlistId)] })
    }
  }, [isOpen, waitlistId, queryClient])

  const customerName = `${waitlist?.customer?.first_name || ''} ${waitlist?.customer?.last_name || ''}`.trim() || '-'

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
      render: (audit: any) => audit?.customer_address || waitlist?.customer?.address || '-',
    },
    {
      key: 'email',
      header: 'Email',
      render: (audit: any) => audit?.customer_email || waitlist?.customer?.email || '-',
    },
    {
      key: 'contact_number',
      header: 'Contact Number',
      render: (audit: any) => audit?.customer_phone || waitlist?.customer?.phone || '-',
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
      entityId={waitlistId}
      title="Waitlist Audit Trail"
      subtitle="Create, update, status, and communication actions for this waitlist"
      tableColumns={tableColumns}
      queryKeyPrefix="waitlist-audits"
      fetchAudits={(id, page) => waitlistApi.getAudits(id, page) as Promise<any>}
    />
  )
}
