import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { leadsApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import type { Lead } from '../../types'
import { Plus, Search, Upload, ArrowRightLeft } from 'lucide-react'

export function LeadsPage() {
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [isConvertModalOpen, setIsConvertModalOpen] = useState(false)
  const [selectedLead, setSelectedLead] = useState<Lead | null>(null)
  const [filterStatus, setFilterStatus] = useState('')
  const [searchTerm, setSearchTerm] = useState('')

  const { data: leads, isLoading } = useQuery({
    queryKey: ['leads', filterStatus],
    queryFn: async () => {
      // Mock data - replace with actual API
      return [
        { id: '1', firstName: 'John', lastName: 'Doe', email: 'john@example.com', phone: '555-0101', source: 'Website', status: 'new', notes: '', createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() },
        { id: '2', firstName: 'Jane', lastName: 'Smith', email: 'jane@example.com', phone: '555-0102', source: 'Referral', status: 'contacted', notes: '', createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() },
        { id: '3', firstName: 'Bob', lastName: 'Johnson', email: 'bob@example.com', phone: '555-0103', source: 'Social Media', status: 'qualified', notes: '', createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() },
      ] as Lead[]
    },
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

  const handleConvert = (lead: Lead) => {
    setSelectedLead(lead)
    setIsConvertModalOpen(true)
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
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-secondary-900">Leads</h1>
        <div className="flex gap-3">
          <Button variant="secondary" size="sm">
            <Upload className="w-4 h-4 mr-2" />
            Import
          </Button>
          <Button onClick={() => setIsModalOpen(true)} size="sm">
            <Plus className="w-4 h-4 mr-2" />
            Add Lead
          </Button>
        </div>
      </div>

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
              title: 'Actions',
              render: (row: Lead) => (
                <div className="flex gap-2">
                  {row.status !== 'converted' && (
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => handleConvert(row)}
                    >
                      <ArrowRightLeft className="w-4 h-4" />
                    </Button>
                  )}
                  <Button variant="ghost" size="sm">Edit</Button>
                </div>
              ),
            },
          ]}
          data={filteredLeads ?? []}
          keyExtractor={(row) => row.id}
          isLoading={isLoading}
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
            <Button onClick={() => createMutation.mutate({
              firstName: 'New',
              lastName: 'Lead',
              email: 'new@example.com',
              phone: '',
              source: 'Manual',
              status: 'new',
              notes: '',
            } as Omit<Lead, 'id' | 'createdAt' | 'updatedAt'>)}>
              Create Lead
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <Input label="First Name" />
            <Input label="Last Name" />
          </div>
          <Input label="Email" type="email" />
          <Input label="Phone" />
          <div>
            <label className="text-sm font-medium text-secondary-700">Source</label>
            <select className="input mt-1">
              <option>Website</option>
              <option>Referral</option>
              <option>Social Media</option>
              <option>Other</option>
            </select>
          </div>
          <div>
            <label className="text-sm font-medium text-secondary-700">Notes</label>
            <textarea className="input mt-1" rows={3} />
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
    </div>
  )
}
