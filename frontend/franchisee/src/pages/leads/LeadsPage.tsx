import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { PageHeader } from '../../components/layout/PageHeader'
import { leadsApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import type { Lead } from '../../types'
import { Plus, Search, Upload, ArrowRightLeft, MoreVertical, Megaphone } from 'lucide-react'
import { LeadDetailModal } from './LeadDetailModal'
import { PortalMenu } from '../../components/ui/PortalMenu'

export function LeadsPage() {
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [isConvertModalOpen, setIsConvertModalOpen] = useState(false)
  const [selectedLead, setSelectedLead] = useState<Lead | null>(null)
  const [detailLead, setDetailLead] = useState<Lead | null>(null)
  const [isDetailModalOpen, setIsDetailModalOpen] = useState(false)
  const [filterStatus, setFilterStatus] = useState('')
  const [searchTerm, setSearchTerm] = useState('')
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)
  const [formData, setFormData] = useState({
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    source: 'internet' as Lead['source'],
    notes: '',
  })

  const { data: leads, isLoading } = useQuery({
    queryKey: ['leads', filterStatus],
    queryFn: () => leadsApi.getAll(filterStatus ? { status: filterStatus } : undefined),
  })

  const createMutation = useMutation({
    mutationFn: leadsApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['leads'] })
      setIsModalOpen(false)
      addToast('Lead created successfully', 'success')
    },
  })

  const convertMutation = useMutation({
    mutationFn: ({ id, franchiseId }: { id: string; franchiseId: string }) =>
      leadsApi.convert(id, franchiseId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['leads'] })
      setIsConvertModalOpen(false)
      addToast('Lead converted to customer', 'success')
    },
  })

  const updateLeadMutation = useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<Lead> }) => leadsApi.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['leads'] })
    },
  })

  const handleConvert = (lead: Lead) => {
    setSelectedLead(lead)
    setIsConvertModalOpen(true)
  }

  const handleViewLead = (lead: Lead) => {
    setDetailLead(lead)
    setIsDetailModalOpen(true)
  }

  const handleCloseDetailModal = () => {
    setIsDetailModalOpen(false)
    setDetailLead(null)
  }

  const handleDetailComment = (leadId: string, comment: string) => {
    updateLeadMutation.mutate(
      { id: leadId, data: { notes: comment } },
      {
        onSuccess: () => addToast(`Comment saved for lead ${leadId}`, 'success'),
        onError: () => addToast('Failed to save comment', 'error'),
      }
    )
  }

  const handleDetailConvert = (leadId: string) => {
    const lead = leads?.find((item) => item.id === leadId)
    if (lead) {
      handleConvert(lead)
    }
  }

  const handleDetailSnooze = (leadId: string) => {
    updateLeadMutation.mutate(
      {
        id: leadId,
        data: {
          status: 'snoozed',
          snoozedUntil: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString(),
        },
      },
      {
        onSuccess: () => {
          addToast(`Lead ${leadId} snoozed`, 'success')
          handleCloseDetailModal()
        },
        onError: () => addToast('Failed to snooze lead', 'error'),
      }
    )
  }

  const filteredLeads = leads?.filter((lead) =>
    searchTerm === '' ||
    lead.firstName.toLowerCase().includes(searchTerm.toLowerCase()) ||
    lead.lastName.toLowerCase().includes(searchTerm.toLowerCase()) ||
    lead.email.toLowerCase().includes(searchTerm.toLowerCase())
  )

  const statusColors = {
    new: 'bg-blue-100 text-blue-700',
    contacted: 'bg-yellow-100 text-yellow-700',
    qualified: 'bg-green-100 text-green-700',
    converted: 'bg-purple-100 text-purple-700',
    lost: 'bg-red-100 text-red-700',
    snoozed: 'bg-gray-100 text-gray-700',
    completed: 'bg-green-100 text-green-700',
    cancellation_request: 'bg-orange-100 text-orange-700',
    message_for_operator: 'bg-indigo-100 text-indigo-700',
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Leads"
        icon={<Megaphone className="w-5 h-5" />}
        actions={
          <>
            <Button variant="secondary" size="sm">
              <Upload className="w-4 h-4 mr-2" />
              Import
            </Button>
            <Button onClick={() => setIsModalOpen(true)} size="sm">
              <Plus className="w-4 h-4 mr-2" />
              Add Lead
            </Button>
          </>
        }
      />

      <Card>
        <div className="flex items-center gap-4 mb-6">
          <div className="relative flex-1 max-w-md">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
            <input
              type="text"
              placeholder="Search leads..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="input pl-10"
            />
          </div>
          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="input w-40"
          >
            <option value="">All Status</option>
            <option value="new">New</option>
            <option value="contacted">Contacted</option>
            <option value="qualified">Qualified</option>
            <option value="converted">Converted</option>
            <option value="lost">Lost</option>
          </select>
        </div>

        <Table
          columns={[
            { key: 'name', title: 'Name', render: (row: Lead) => `${row.firstName} ${row.lastName}` },
            { key: 'email', title: 'Email' },
            { key: 'phone', title: 'Phone' },
            { key: 'source', title: 'Source' },
            {
              key: 'status',
              title: 'Status',
              render: (row: Lead) => (
                <span className={`px-2 py-1 rounded-full text-xs font-medium ${statusColors[row.status]}`}>
                  {row.status}
                </span>
              ),
            },
            {
              key: 'actions',
              title: 'Mgmt',
              render: (row: Lead) => (
                <div className="relative flex justify-end">
                  <button
                    type="button"
                    onClick={(e) => {
                      if (openMenuId === row.id) {
                        setOpenMenuId(null)
                        setMenuPos(null)
                      } else {
                        const rect = (e.currentTarget as HTMLButtonElement).getBoundingClientRect()
                        setMenuPos({ top: rect.bottom + 4, left: rect.right - 160 })
                        setOpenMenuId(row.id)
                      }
                    }}
                    className="p-1 rounded hover:bg-gray-100"
                  >
                    <MoreVertical className="w-5 h-5 text-gray-600" />
                  </button>
                  <PortalMenu
                    isOpen={openMenuId === row.id}
                    onClose={() => { setOpenMenuId(null); setMenuPos(null) }}
                    position={menuPos}
                    width={160}
                  >
                      <button
                        type="button"
                        onClick={() => {
                          handleViewLead(row)
                          setOpenMenuId(null)
                          setMenuPos(null)
                        }}
                        className="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                      >
                        View
                      </button>
                      <button
                        type="button"
                        onClick={() => { setOpenMenuId(null); setMenuPos(null) }}
                        className="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                      >
                        Edit
                      </button>
                      {row.status !== 'converted' && (
                        <button
                          type="button"
                          onClick={() => {
                            handleConvert(row)
                            setOpenMenuId(null)
                            setMenuPos(null)
                          }}
                          className="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        >
                          Convert
                        </button>
                      )}
                  </PortalMenu>
                </div>
              ),
            },
          ]}
          data={filteredLeads ?? []}
          keyExtractor={(row) => row.id}
          isLoading={isLoading}
          emptyMessage="No leads found"
        />
      </Card>

      {/* Create Lead Modal */}
      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title="Add New Lead"
        footer={
          <>
            <Button variant="secondary" onClick={() => setIsModalOpen(false)}>
              Cancel
            </Button>
            <Button
              onClick={() =>
                createMutation.mutate({
                  firstName: formData.firstName,
                  lastName: formData.lastName,
                  customerName: `${formData.firstName} ${formData.lastName}`.trim(),
                  email: formData.email,
                  phone: formData.phone,
                  interestedServices: '',
                  address: '',
                  source: formData.source,
                  leadsFrom: formData.source === 'phone' ? 'phone' : 'internet',
                  status: 'new',
                  notes: formData.notes,
                } as Omit<Lead, 'id' | 'createdAt' | 'updatedAt'>)
              }
              isLoading={createMutation.isPending}
            >
              Create Lead
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <Input
              label="First Name"
              value={formData.firstName}
              onChange={(e) => setFormData((prev) => ({ ...prev, firstName: e.target.value }))}
            />
            <Input
              label="Last Name"
              value={formData.lastName}
              onChange={(e) => setFormData((prev) => ({ ...prev, lastName: e.target.value }))}
            />
          </div>
          <Input
            label="Email"
            type="email"
            value={formData.email}
            onChange={(e) => setFormData((prev) => ({ ...prev, email: e.target.value }))}
          />
          <Input
            label="Phone"
            value={formData.phone}
            onChange={(e) => setFormData((prev) => ({ ...prev, phone: e.target.value }))}
          />
          <div>
            <label className="text-sm font-medium text-secondary-700">Source</label>
            <select
              className="input mt-1"
              value={formData.source}
              onChange={(e) => setFormData((prev) => ({ ...prev, source: e.target.value as Lead['source'] }))}
            >
              <option value="internet">Internet</option>
              <option value="phone">Phone</option>
              <option value="walk-in">Walk In</option>
              <option value="referral">Referral</option>
            </select>
          </div>
          <div>
            <label className="text-sm font-medium text-secondary-700">Notes</label>
            <textarea
              className="input mt-1"
              rows={3}
              value={formData.notes}
              onChange={(e) => setFormData((prev) => ({ ...prev, notes: e.target.value }))}
            />
          </div>
        </div>
      </Modal>

      {/* Convert Lead Modal */}
      <Modal
        isOpen={isConvertModalOpen}
        onClose={() => setIsConvertModalOpen(false)}
        title="Convert Lead to Customer"
        footer={
          <>
            <Button variant="secondary" onClick={() => setIsConvertModalOpen(false)}>
              Cancel
            </Button>
            <Button
              onClick={() => selectedLead && convertMutation.mutate({ id: selectedLead.id, franchiseId: '1' })}
              isLoading={convertMutation.isPending}
            >
              Convert
            </Button>
          </>
        }
      >
        <p className="text-secondary-600 mb-4">
          Converting lead: <strong>{selectedLead?.firstName} {selectedLead?.lastName}</strong>
        </p>
        <div className="space-y-4">
          <div>
            <label className="text-sm font-medium text-secondary-700">Assign to Franchise</label>
            <select className="input mt-1">
              <option>Main Franchise</option>
              <option>North Branch</option>
              <option>South Branch</option>
            </select>
          </div>
          <div>
            <label className="text-sm font-medium text-secondary-700">Assign Staff</label>
            <select className="input mt-1">
              <option>Auto-assign</option>
              <option>John Doe</option>
              <option>Jane Smith</option>
            </select>
          </div>
        </div>
      </Modal>

      {detailLead && (
        <LeadDetailModal
          lead={detailLead}
          isOpen={isDetailModalOpen}
          onClose={handleCloseDetailModal}
          onComment={handleDetailComment}
          onConvert={handleDetailConvert}
          onSnooze={handleDetailSnooze}
        />
      )}
    </div>
  )
}
