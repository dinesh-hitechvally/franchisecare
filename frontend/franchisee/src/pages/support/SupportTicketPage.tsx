import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Input } from '../../components/ui/Input'
import { Modal } from '../../components/ui/Modal'
import { PortalMenu } from '../../components/ui/PortalMenu'
import { 
  Search, Headphones, MoreVertical, Eye, CheckCircle, AlertTriangle, 
  Clock, User, Tag, Smartphone, ArrowLeft, Edit3
} from 'lucide-react'
import { useToastStore } from '../../store/toastStore'
import { PageHeader } from '../../components/layout/PageHeader'
import { supportTicketsApi, supportDepartmentsApi, SupportTicket, SupportDepartment } from '../../api/services'

export function SupportTicketPage() {
  const [activeTab, setActiveTab] = useState<string>('BUGS')
  const [searchTerm, setSearchTerm] = useState('')
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false)
  const [isCreateDeptModalOpen, setIsCreateDeptModalOpen] = useState(false)
  const [editingTicket, setEditingTicket] = useState<SupportTicket | null>(null)
  const [selectedTicketId, setSelectedTicketId] = useState<string | null>(null)
  
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)
  
  const addToast = useToastStore((state) => state.addToast)
  const queryClient = useQueryClient()

  // Form States (Create Ticket)
  const [subject, setSubject] = useState('')
  const [department, setDepartment] = useState<string>('BUGS')
  const [description, setDescription] = useState('')

  // Form States (Edit Ticket)
  const [editSubject, setEditSubject] = useState('')
  const [editDepartment, setEditDepartment] = useState<string>('BUGS')
  const [editDescription, setEditDescription] = useState('')

  // Form States (Create Department)
  const [deptName, setDeptName] = useState('')

  // Reply States
  const [replyMessage, setReplyMessage] = useState('')
  const [closeTicketOnReply, setCloseTicketOnReply] = useState(false)

  // Fetch Tickets from API
  const { data: tickets, isLoading } = useQuery({
    queryKey: ['support-tickets', activeTab],
    queryFn: async () => {
      return supportTicketsApi.getAll({ department: activeTab })
    },
  })

  // Fetch Departments from API
  const { data: departments } = useQuery({
    queryKey: ['support-departments'],
    queryFn: async () => {
      return supportDepartmentsApi.getAll()
    },
  })

  // Fetch Single Ticket Details (for view layout)
  const { data: ticketDetail, isLoading: isLoadingDetail } = useQuery({
    queryKey: ['support-ticket', selectedTicketId],
    queryFn: async () => {
      return supportTicketsApi.getById(selectedTicketId!)
    },
    enabled: !!selectedTicketId,
  })

  // Create Ticket Mutation
  const createTicketMutation = useMutation({
    mutationFn: (newTicket: { subject: string; department: string; description: string }) => {
      return supportTicketsApi.create(newTicket)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['support-tickets'] })
      addToast('Ticket created successfully', 'success')
      setIsCreateModalOpen(false)
      setSubject('')
      setDescription('')
    },
    onError: () => {
      addToast('Failed to create ticket', 'error')
    }
  })

  // Create Department Mutation
  const createDeptMutation = useMutation({
    mutationFn: (name: string) => {
      return supportDepartmentsApi.create({ name })
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['support-departments'] })
      addToast('Department created successfully', 'success')
      setIsCreateDeptModalOpen(false)
      setDeptName('')
    },
    onError: () => {
      addToast('Failed to create department', 'error')
    }
  })

  // Update Ticket Mutation (edit / status change / urgency)
  const updateTicketMutation = useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<SupportTicket> }) => {
      return supportTicketsApi.update(id, data)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['support-tickets'] })
      queryClient.invalidateQueries({ queryKey: ['support-ticket'] })
      addToast('Ticket updated successfully', 'success')
      setEditingTicket(null)
    },
    onError: () => {
      addToast('Failed to update ticket', 'error')
    }
  })

  // Add Reply Mutation
  const addReplyMutation = useMutation({
    mutationFn: ({ id, message, close_ticket }: { id: string; message: string; close_ticket?: boolean }) => {
      return supportTicketsApi.addReply(id, { message, close_ticket })
    },
    onSuccess: (res: any) => {
      queryClient.invalidateQueries({ queryKey: ['support-ticket', selectedTicketId] })
      queryClient.invalidateQueries({ queryKey: ['support-tickets'] })
      addToast('Reply sent successfully', 'success')
      setReplyMessage('')
      setCloseTicketOnReply(false)
      if (res?.ticketStatus === 'CLOSED') {
        setSelectedTicketId(null)
      }
    },
    onError: () => {
      addToast('Failed to send reply', 'error')
    }
  })

  const handleCreateTicket = (e: React.FormEvent) => {
    e.preventDefault()
    if (!subject.trim() || !description.trim()) {
      addToast('Please fill in all fields', 'error')
      return
    }
    createTicketMutation.mutate({ subject, department, description })
  }

  const handleCreateDept = (e: React.FormEvent) => {
    e.preventDefault()
    if (!deptName.trim()) {
      addToast('Please enter department name', 'error')
      return
    }
    createDeptMutation.mutate(deptName)
  }

  const handleEditTicketSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (!editSubject.trim() || !editDescription.trim()) {
      addToast('Please fill in all fields', 'error')
      return
    }
    if (editingTicket) {
      updateTicketMutation.mutate({
        id: editingTicket.id,
        data: {
          subject: editSubject,
          department: editDepartment,
          description: editDescription
        }
      })
    }
  }

  const startEditing = (ticket: SupportTicket) => {
    setEditingTicket(ticket)
    setEditSubject(ticket.subject)
    setEditDepartment(ticket.department)
    setEditDescription(ticket.description || '')
  }

  const filteredTickets = tickets?.filter((ticket: SupportTicket) =>
    ticket.subject.toLowerCase().includes(searchTerm.toLowerCase()) ||
    ticket.ticketId.toLowerCase().includes(searchTerm.toLowerCase()) ||
    ticket.createdBy.toLowerCase().includes(searchTerm.toLowerCase())
  ) || []

  const dynamicTabs = departments
    ? [
        ...departments.map((d: SupportDepartment) => ({ key: d.code, label: d.name })),
        { key: 'CLOSED', label: 'Closed Tickets' }
      ]
    : [
        { key: 'BUGS', label: 'Bugs' },
        { key: 'ENHANCEMENT', label: 'Enhancement Requests' },
        { key: 'ADMIN', label: 'Admin Tickets' },
        { key: 'URGENT', label: 'Urgent Tickets' },
        { key: 'CLOSED', label: 'Closed Tickets' }
      ]

  const columns = [
    {
      key: 'id',
      title: 'S/N',
      render: (ticket: SupportTicket) => <span className="text-gray-800">{ticket.id}</span>,
    },
    {
      key: 'ticketId',
      title: 'Ticket ID',
      render: (ticket: SupportTicket) => <span className="text-gray-600 font-mono text-sm">{ticket.ticketId}</span>,
    },
    {
      key: 'subject',
      title: 'Subject',
      render: (ticket: SupportTicket) => <span className="text-gray-800">{ticket.subject}</span>,
    },
    {
      key: 'department',
      title: 'Department',
      render: (ticket: SupportTicket) => (
        <span className="capitalize">{ticket.department?.toLowerCase()}</span>
      ),
    },
    {
      key: 'createdBy',
      title: 'Created By',
      render: (ticket: SupportTicket) => <span className="text-gray-700">{ticket.createdBy}</span>,
    },
    {
      key: 'lastUpdatedBy',
      title: 'Last Updated By',
      render: (ticket: SupportTicket) => <span className="text-gray-700">{ticket.lastUpdatedBy}</span>,
    },
    {
      key: 'created',
      title: 'Created',
      render: (ticket: SupportTicket) => {
        const daysAgo = Math.floor((Date.now() - new Date(ticket.created).getTime()) / (1000 * 60 * 60 * 24))
        if (isNaN(daysAgo) || daysAgo < 0) return 'Just now'
        if (daysAgo < 1) return 'Today'
        if (daysAgo < 7) return `${daysAgo} day${daysAgo > 1 ? 's' : ''} ago`
        if (daysAgo < 30) return `${Math.floor(daysAgo / 7)} week${Math.floor(daysAgo / 7) > 1 ? 's' : ''} ago`
        return `${Math.floor(daysAgo / 30)} month${Math.floor(daysAgo / 30) > 1 ? 's' : ''} ago`
      },
    },
    {
      key: 'status',
      title: 'Status',
      render: (ticket: SupportTicket) => (
        <span className={`px-3 py-1 rounded-full text-xs font-medium capitalize ${
          ticket.status === 'OPEN' ? 'bg-green-100 text-green-700' :
          ticket.status === 'IN-PROGRESS' ? 'bg-yellow-100 text-yellow-700' :
          'bg-gray-100 text-gray-700'
        }`}>
          {ticket.status.replace('-', ' ').toLowerCase()}
        </span>
      ),
    },
    {
      key: 'actions',
      title: 'Actions',
      render: (ticket: SupportTicket) => (
        <div className="flex justify-end pr-2 relative">
          <button
            onClick={(e) => {
              e.stopPropagation()
              if (openMenuId === ticket.id) {
                setOpenMenuId(null); setMenuPos(null)
              } else {
                const rect = (e.currentTarget as HTMLButtonElement).getBoundingClientRect()
                setMenuPos({ top: rect.bottom + 4, left: rect.right - 192 })
                setOpenMenuId(ticket.id)
              }
            }}
            className="text-gray-400 hover:text-gray-600 p-1.5 focus:outline-none rounded-full hover:bg-gray-100 transition-colors"
          >
            <MoreVertical className="w-5 h-5" />
          </button>

          <PortalMenu
            isOpen={openMenuId === ticket.id}
            onClose={() => { setOpenMenuId(null); setMenuPos(null) }}
            position={menuPos}
          >
            <button
              onClick={() => {
                setOpenMenuId(null)
                setMenuPos(null)
                setSelectedTicketId(ticket.id)
              }}
              className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors text-left"
            >
              <Eye className="w-4 h-4 text-gray-400" />
              View
            </button>
            <button
              onClick={() => {
                setOpenMenuId(null)
                setMenuPos(null)
                startEditing(ticket)
              }}
              className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors text-left"
            >
              <Edit3 className="w-4 h-4 text-gray-400" />
              Edit
            </button>
            <button
              onClick={() => {
                setOpenMenuId(null)
                setMenuPos(null)
                updateTicketMutation.mutate({ id: ticket.id, data: { status: 'CLOSED' } })
              }}
              disabled={ticket.status === 'CLOSED'}
              className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors text-left disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <CheckCircle className="w-4 h-4 text-green-500" />
              Close
            </button>
            <button
              onClick={() => {
                setOpenMenuId(null)
                setMenuPos(null)
                updateTicketMutation.mutate({ id: ticket.id, data: { department: 'URGENT' } })
              }}
              disabled={ticket.department === 'URGENT' || ticket.status === 'CLOSED'}
              className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors text-left disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <AlertTriangle className="w-4 h-4 text-orange-500" />
              Mark Urgent
            </button>
          </PortalMenu>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-6">
      <PageHeader
        title="Mate Helpdesk System"
        description="View and manage support tickets"
        icon={<Headphones className="w-5 h-5" />}
      />

      <Card className="overflow-hidden shadow-sm border-gray-200">
        {/* Color Scheme Heading Tab Container matching staging.franchise.care */}
        <div className="bg-[#3b5998] px-4 py-2 flex flex-wrap gap-2 items-center">
          {dynamicTabs.map((tab) => (
            <button
              key={tab.key}
              onClick={() => { setSelectedTicketId(null); setActiveTab(tab.key) }}
              className={`px-3 py-1.5 rounded text-sm font-semibold transition-colors ${
                activeTab === tab.key && !selectedTicketId ? 'bg-[#2d4373] text-white' : 'text-white hover:bg-[#2d4373]'
              }`}
            >
              {tab.label}
            </button>
          ))}
          <button
            onClick={() => setIsCreateModalOpen(true)}
            className="px-3 py-1.5 rounded text-sm font-semibold text-pink-200 hover:text-white hover:bg-[#2d4373] transition-colors"
          >
            Create Ticket
          </button>
          <button
            onClick={() => setIsCreateDeptModalOpen(true)}
            className="px-3 py-1.5 rounded text-sm font-semibold text-blue-200 hover:text-white hover:bg-[#2d4373] transition-colors"
          >
            Create Department
          </button>
        </div>

        <div className="p-6 bg-white">
          {selectedTicketId ? (
            isLoadingDetail ? (
              <div className="p-8 text-center text-gray-500">Loading ticket details...</div>
            ) : !ticketDetail ? (
              <div className="p-8 text-center text-red-500">Ticket not found.</div>
            ) : (
              <div className="space-y-6">
                {/* View Layout Header matching screenshot */}
                <div className="flex items-center justify-between bg-gray-50 px-4 py-3 rounded border border-gray-200">
                  <div className="flex items-center gap-3">
                    <span className={`px-2.5 py-1 rounded text-xs font-semibold uppercase ${
                      ticketDetail.status === 'OPEN' ? 'bg-green-600 text-white' :
                      ticketDetail.status === 'IN-PROGRESS' ? 'bg-yellow-500 text-white' :
                      'bg-gray-600 text-white'
                    }`}>
                      {ticketDetail.status}
                    </span>
                    <h2 className="text-lg font-bold text-gray-900">{ticketDetail.subject}</h2>
                    <span className="text-sm text-gray-500">department - {ticketDetail.id}</span>
                  </div>
                  <button
                    onClick={() => setSelectedTicketId(null)}
                    className="flex items-center gap-1 text-sm font-semibold text-blue-600 hover:text-blue-800 transition-colors"
                  >
                    <ArrowLeft className="w-4 h-4" />
                    <span>Back</span>
                  </button>
                </div>

                {/* Ticket Description Card matching screenshot */}
                <div className="border border-gray-200 rounded overflow-hidden bg-white">
                  <div className="p-6 text-sm text-gray-800 whitespace-pre-line leading-relaxed min-h-[80px]">
                    {ticketDetail.description || 'No description provided.'}
                  </div>
                  
                  {/* Footer Banner matching screenshot */}
                  <div className="bg-gray-50 px-6 py-3 border-t border-gray-200 flex flex-wrap gap-6 text-xs text-gray-600 items-center">
                    <div className="flex items-center gap-1.5">
                      <Clock className="w-4 h-4 text-gray-400" />
                      <span>
                        {(() => {
                          const daysAgo = Math.floor((Date.now() - new Date(ticketDetail.created).getTime()) / (1000 * 60 * 60 * 24))
                          if (isNaN(daysAgo) || daysAgo < 0) return 'Just now'
                          if (daysAgo < 1) return 'Today'
                          if (daysAgo < 7) return `${daysAgo} day${daysAgo > 1 ? 's' : ''} ago`
                          if (daysAgo < 30) return `${Math.floor(daysAgo / 7)} week${Math.floor(daysAgo / 7) > 1 ? 's' : ''} ago`
                          return `${Math.floor(daysAgo / 30)} month${Math.floor(daysAgo / 30) > 1 ? 's' : ''} ago`
                        })()}
                      </span>
                    </div>
                    <div className="flex items-center gap-1.5">
                      <User className="w-4 h-4 text-gray-400" />
                      <span>{ticketDetail.createdBy}</span>
                    </div>
                    <div className="flex items-center gap-1.5">
                      <Tag className="w-4 h-4 text-gray-400" />
                      <span className="capitalize">{ticketDetail.department?.toLowerCase()}</span>
                    </div>
                    <div className="flex items-center gap-1.5">
                      <Smartphone className="w-4 h-4 text-gray-400" />
                      <span>Issue on Device : Web Portal</span>
                    </div>
                  </div>
                </div>

                {/* Ticket Replies Thread */}
                {ticketDetail.replies && ticketDetail.replies.length > 0 && (
                  <div className="space-y-4 pt-4">
                    <h3 className="text-xs font-semibold text-gray-400 uppercase tracking-wider">Replies</h3>
                    <div className="space-y-3">
                      {ticketDetail.replies.map((reply: any) => (
                        <div key={reply.id} className="border border-gray-200 rounded p-4 bg-gray-50 flex flex-col gap-2">
                          <div className="flex items-center justify-between text-xs text-gray-500">
                            <span className="font-semibold text-gray-700">{reply.userName}</span>
                            <span>{new Date(reply.created).toLocaleString()}</span>
                          </div>
                          <div className="text-sm text-gray-800 whitespace-pre-line">
                            {reply.message}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Reply Form matching screenshot */}
                <form 
                  onSubmit={(e) => {
                    e.preventDefault()
                    if (!replyMessage.trim()) return
                    addReplyMutation.mutate({
                      id: ticketDetail.id,
                      message: replyMessage,
                      close_ticket: closeTicketOnReply
                    })
                  }}
                  className="space-y-4 pt-6 border-t border-gray-200"
                >
                  <div>
                    <textarea
                      className="w-full border border-gray-300 rounded p-3 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
                      rows={4}
                      placeholder="Enter your reply..."
                      value={replyMessage}
                      onChange={(e) => setReplyMessage(e.target.value)}
                      required
                    />
                  </div>

                  <div className="flex flex-col gap-3">
                    <div className="flex items-center text-sm">
                      <input
                        type="file"
                        className="text-xs text-gray-500 file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
                        disabled
                      />
                      <span className="text-xs text-gray-400 ml-2">(attachment logs to attachments table)</span>
                    </div>

                    <div className="flex items-center">
                      <input
                        id="closeTicketCheck"
                        type="checkbox"
                        checked={closeTicketOnReply}
                        onChange={(e) => setCloseTicketOnReply(e.target.checked)}
                        className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                      />
                      <label htmlFor="closeTicketCheck" className="text-red-600 font-bold ml-2 text-sm select-none cursor-pointer">
                        Check to Close Ticket
                      </label>
                    </div>
                  </div>

                  <div>
                    <button
                      type="submit"
                      disabled={addReplyMutation.isPending}
                      className="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2 rounded text-sm transition-colors disabled:opacity-50"
                    >
                      {addReplyMutation.isPending ? 'Sending...' : 'Reply'}
                    </button>
                  </div>
                </form>
              </div>
            )
          ) : (
            <>
              <p className="text-gray-600 mb-4 font-normal text-sm">
                View and manage tickets that may have responses from support team.
              </p>

              <div className="flex justify-between items-center mb-4">
                <div className="flex items-center gap-2">
                  <span className="text-sm text-gray-700">Show</span>
                  <select className="border border-gray-300 rounded px-3 py-1 text-sm">
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
                  </select>
                  <span className="text-sm text-gray-700">entries</span>
                </div>

                <div className="relative">
                  <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                  <Input
                    type="text"
                    placeholder="Search..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="pl-10 w-64"
                  />
                </div>
              </div>

              {isLoading ? (
                <div className="p-8 text-center text-gray-500">Loading tickets...</div>
              ) : (
                <Table
                  data={filteredTickets}
                  columns={columns}
                  keyExtractor={(ticket) => ticket.id}
                  emptyMessage="No tickets found"
                />
              )}

              <div className="mt-4 text-sm text-gray-600">
                Showing 1 to {filteredTickets.length} of {filteredTickets.length} entries
              </div>
            </>
          )}
        </div>
      </Card>

      {/* Create Ticket Modal */}
      <Modal
        isOpen={isCreateModalOpen}
        onClose={() => setIsCreateModalOpen(false)}
        title="Create Support Ticket"
      >
        <form onSubmit={handleCreateTicket} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Subject</label>
            <Input 
              type="text" 
              placeholder="Enter ticket subject" 
              value={subject}
              onChange={(e) => setSubject(e.target.value)}
              required
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Department</label>
            <select 
              className="w-full border border-gray-300 rounded-lg px-3 py-2 capitalize"
              value={department}
              onChange={(e) => setDepartment(e.target.value)}
            >
              {departments?.map((d: SupportDepartment) => (
                <option key={d.id} value={d.code}>{d.name}</option>
              )) ?? (
                <>
                  <option value="BUGS">Bugs</option>
                  <option value="ENHANCEMENT">Enhancement Request</option>
                  <option value="ADMIN">Admin Ticket</option>
                  <option value="URGENT">Urgent</option>
                </>
              )}
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea
              className="w-full border border-gray-300 rounded-lg px-3 py-2 h-32"
              placeholder="Describe your issue..."
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              required
            />
          </div>
          <div className="flex justify-end gap-2 mt-6">
            <Button variant="secondary" onClick={() => setIsCreateModalOpen(false)}>
              Cancel
            </Button>
            <Button type="submit" disabled={createTicketMutation.isPending}>
              {createTicketMutation.isPending ? 'Creating...' : 'Create Ticket'}
            </Button>
          </div>
        </form>
      </Modal>

      {/* Edit Ticket Modal */}
      <Modal
        isOpen={!!editingTicket}
        onClose={() => setEditingTicket(null)}
        title="Edit Support Ticket"
      >
        <form onSubmit={handleEditTicketSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Subject</label>
            <Input 
              type="text" 
              placeholder="Enter ticket subject" 
              value={editSubject}
              onChange={(e) => setEditSubject(e.target.value)}
              required
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Department</label>
            <select 
              className="w-full border border-gray-300 rounded-lg px-3 py-2 capitalize"
              value={editDepartment}
              onChange={(e) => setEditDepartment(e.target.value)}
            >
              {departments?.map((d: SupportDepartment) => (
                <option key={d.id} value={d.code}>{d.name}</option>
              )) ?? (
                <>
                  <option value="BUGS">Bugs</option>
                  <option value="ENHANCEMENT">Enhancement Request</option>
                  <option value="ADMIN">Admin Ticket</option>
                  <option value="URGENT">Urgent</option>
                </>
              )}
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea
              className="w-full border border-gray-300 rounded-lg px-3 py-2 h-32"
              placeholder="Describe your issue..."
              value={editDescription}
              onChange={(e) => setEditDescription(e.target.value)}
              required
            />
          </div>
          <div className="flex justify-end gap-2 mt-6">
            <Button variant="secondary" onClick={() => setEditingTicket(null)}>
              Cancel
            </Button>
            <Button type="submit" disabled={updateTicketMutation.isPending}>
              {updateTicketMutation.isPending ? 'Saving...' : 'Save Changes'}
            </Button>
          </div>
        </form>
      </Modal>

      {/* Create Department Modal */}
      <Modal
        isOpen={isCreateDeptModalOpen}
        onClose={() => setIsCreateDeptModalOpen(false)}
        title="Create Support Department"
      >
        <form onSubmit={handleCreateDept} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Department Name</label>
            <Input 
              type="text" 
              placeholder="e.g. Billing Support" 
              value={deptName}
              onChange={(e) => setDeptName(e.target.value)}
              required
            />
          </div>
          <div className="flex justify-end gap-2 mt-6">
            <Button variant="secondary" onClick={() => setIsCreateDeptModalOpen(false)}>
              Cancel
            </Button>
            <Button type="submit" disabled={createDeptMutation.isPending}>
              {createDeptMutation.isPending ? 'Creating...' : 'Create Department'}
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  )
}
