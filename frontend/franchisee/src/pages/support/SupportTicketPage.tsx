import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Input } from '../../components/ui/Input'
import { Modal } from '../../components/ui/Modal'
import { Search, Headphones } from 'lucide-react'
import { useToastStore } from '../../store/toastStore'
import { PageHeader } from '../../components/layout/PageHeader'

interface SupportTicket {
  id: string
  ticketId: string
  subject: string
  department: 'bugs' | 'enhancement' | 'admin' | 'urgent'
  createdBy: string
  lastUpdatedBy: string
  created: string
  status: 'open' | 'in-progress' | 'closed'
}

export function SupportTicketPage() {
  const [activeTab, setActiveTab] = useState<'bugs' | 'enhancement' | 'admin' | 'urgent' | 'closed'>('bugs')
  const [searchTerm, setSearchTerm] = useState('')
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false)
  const addToast = useToastStore((state) => state.addToast)

  const { data: tickets } = useQuery({
    queryKey: ['support-tickets', activeTab],
    queryFn: async () => {
      return [
        {
          id: '1268',
          ticketId: '69cbc99ba5c4d',
          subject: 'Block outs',
          department: 'bugs',
          createdBy: 'Sue De Swardt',
          lastUpdatedBy: 'Sharon Watt',
          created: new Date(Date.now() - 172800000).toISOString(),
          status: 'open',
        },
        {
          id: '1266',
          ticketId: '69c39dc735c6f',
          subject: "Screen won't scroll up",
          department: 'bugs',
          createdBy: 'Tony Owen',
          lastUpdatedBy: 'Sharon Watt',
          created: new Date(Date.now() - 604800000).toISOString(),
          status: 'open',
        },
        {
          id: '1239',
          ticketId: '698a0fb79964d',
          subject: 'Slow processing',
          department: 'bugs',
          createdBy: 'Chris Fryer',
          lastUpdatedBy: 'Mate Support',
          created: new Date(Date.now() - 5184000000).toISOString(),
          status: 'open',
        },
        {
          id: '1233',
          ticketId: '6979400ca9bbf',
          subject: 'Phone Verification Error',
          department: 'bugs',
          createdBy: 'Sandra (Sammi) Wright',
          lastUpdatedBy: 'Mate Support',
          created: new Date(Date.now() - 5184000000).toISOString(),
          status: 'open',
        },
        {
          id: '1214',
          ticketId: '6959866d1e199',
          subject: 'Reoccurring issue.',
          department: 'bugs',
          createdBy: 'Cheryl Allan',
          lastUpdatedBy: 'Cheryl Allan',
          created: new Date(Date.now() - 7776000000).toISOString(),
          status: 'open',
        },
        {
          id: '1153',
          ticketId: '6875efc4e8c71',
          subject: 'Price change on Recurring bookings that have edited booking time',
          department: 'bugs',
          createdBy: 'Mark & Kristie Phenna',
          lastUpdatedBy: 'Mate Support',
          created: new Date(Date.now() - 23328000000).toISOString(),
          status: 'open',
        },
      ] as SupportTicket[]
    },
  })

  const filteredTickets = tickets?.filter((ticket) =>
    ticket.subject.toLowerCase().includes(searchTerm.toLowerCase()) ||
    ticket.ticketId.toLowerCase().includes(searchTerm.toLowerCase()) ||
    ticket.createdBy.toLowerCase().includes(searchTerm.toLowerCase())
  ) || []

  const tabs = [
    { key: 'bugs', label: 'Bugs' },
    { key: 'enhancement', label: 'Enhancement Requests' },
    { key: 'admin', label: 'Admin Tickets' },
    { key: 'urgent', label: 'Urgent Tickets' },
    { key: 'closed', label: 'Closed Tickets' },
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
        <span className="capitalize">{ticket.department === 'bugs' ? 'Bugs' : ticket.department}</span>
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
        if (daysAgo < 7) return `${daysAgo} days ago`
        if (daysAgo < 30) return `${Math.floor(daysAgo / 7)} week${Math.floor(daysAgo / 7) > 1 ? 's' : ''} ago`
        return `${Math.floor(daysAgo / 30)} month${Math.floor(daysAgo / 30) > 1 ? 's' : ''} ago`
      },
    },
    {
      key: 'status',
      title: 'Status',
      render: (ticket: SupportTicket) => (
        <span className={`px-3 py-1 rounded-full text-xs font-medium ${
          ticket.status === 'open' ? 'bg-green-100 text-green-700' :
          ticket.status === 'in-progress' ? 'bg-yellow-100 text-yellow-700' :
          'bg-gray-100 text-gray-700'
        }`}>
          {ticket.status === 'open' ? 'Open' : ticket.status === 'in-progress' ? 'In Progress' : 'Closed'}
        </span>
      ),
    },
    {
      key: 'actions',
      title: '',
      render: () => (
        <div className="flex gap-2">
          <Button size="sm" variant="secondary" className="bg-green-500 hover:bg-green-600 text-white text-xs">
            View Ticket
          </Button>
          <Button size="sm" variant="secondary" className="bg-orange-500 hover:bg-orange-600 text-white text-xs">
            Edit
          </Button>
          <Button size="sm" variant="secondary" className="bg-red-500 hover:bg-red-600 text-white text-xs">
            Close
          </Button>
          <Button size="sm" variant="secondary" className="bg-red-600 hover:bg-red-700 text-white text-xs">
            Mark Urgent
          </Button>
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

      <Card className="overflow-hidden">
        <div className="bg-gradient-to-r from-blue-600 to-indigo-700 px-6 py-3">
          <div className="flex gap-1">
            {tabs.map((tab) => (
              <button
                key={tab.key}
                onClick={() => setActiveTab(tab.key as any)}
                className={`px-4 py-2 text-sm font-medium transition-colors ${
                  activeTab === tab.key
                    ? 'bg-blue-900 text-white'
                    : 'text-blue-100 hover:text-white hover:bg-blue-700'
                }`}
              >
                {tab.label}
              </button>
            ))}
            <button
              onClick={() => setIsCreateModalOpen(true)}
              className="px-4 py-2 text-sm font-medium text-pink-100 hover:text-white hover:bg-blue-700 transition-colors"
            >
              Create Ticket
            </button>
            <button className="px-4 py-2 text-sm font-medium text-blue-100 hover:text-white hover:bg-blue-700 transition-colors">
              Create Department
            </button>
          </div>
        </div>

        <div className="p-6">
          <p className="text-gray-600 mb-4">
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

          <Table
            data={filteredTickets}
            columns={columns}
            keyExtractor={(ticket) => ticket.id}
            emptyMessage="No tickets found"
          />

          <div className="mt-4 text-sm text-gray-600">
            Showing 1 to {filteredTickets.length} of {filteredTickets.length} entries
          </div>
        </div>
      </Card>

      <Modal
        isOpen={isCreateModalOpen}
        onClose={() => setIsCreateModalOpen(false)}
        title="Create Support Ticket"
      >
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Subject</label>
            <Input type="text" placeholder="Enter ticket subject" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Department</label>
            <select className="w-full border border-gray-300 rounded-lg px-3 py-2">
              <option value="bugs">Bugs</option>
              <option value="enhancement">Enhancement Request</option>
              <option value="admin">Admin Ticket</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea
              className="w-full border border-gray-300 rounded-lg px-3 py-2 h-32"
              placeholder="Describe your issue..."
            />
          </div>
          <div className="flex justify-end gap-2 mt-6">
            <Button variant="secondary" onClick={() => setIsCreateModalOpen(false)}>
              Cancel
            </Button>
            <Button onClick={() => {
              setIsCreateModalOpen(false)
              addToast('Ticket created successfully', 'success')
            }}>
              Create Ticket
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  )
}
