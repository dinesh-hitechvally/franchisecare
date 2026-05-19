import React from 'react'
import { customersApi } from '../../api/services'
import { SimpleAuditTrailModal } from './SimpleAuditTrailModal'

interface CustomerAuditModalProps {
  isOpen: boolean
  onClose: () => void
  customer: {
    id: string
    first_name: string
    last_name: string
  } | null
}

export const CustomerAuditModal: React.FC<CustomerAuditModalProps> = ({ isOpen, onClose, customer }) => {
  const tableColumns = [
    {
      key: 'id',
      header: 'ID',
      render: (audit: any) => String(audit?.id ?? '-'),
    },
    {
      key: 'name',
      header: 'Name',
      render: (audit: any) => `${audit?.first_name || customer?.first_name || ''} ${audit?.last_name || customer?.last_name || ''}`.trim() || '-',
    },
    {
      key: 'email',
      header: 'Email',
      render: (audit: any) => audit?.email || '-',
    },
    {
      key: 'phone',
      header: 'Phone',
      render: (audit: any) => audit?.phone || '-',
    },
    {
      key: 'action_type',
      header: 'Action Type',
      render: (audit: any) => audit?.action_type ? String(audit.action_type).replace(/_/g, ' ') : '-',
    },
    {
      key: 'action_at',
      header: 'Action At',
      render: (audit: any) => audit?.action_at ? new Date(audit.action_at).toLocaleString() : '-',
    },
  ];
  return (
    <SimpleAuditTrailModal
      isOpen={isOpen}
      onClose={onClose}
      entityId={customer?.id}
      title="Customer Audit Trail"
      subtitle="Create, update, archive and restore actions for this customer"
      queryKeyPrefix="customer-audits"
      fetchAudits={(id, page) => customersApi.getAudits(id, page) as Promise<any>}
      tableColumns={tableColumns}
    />
  )
}
