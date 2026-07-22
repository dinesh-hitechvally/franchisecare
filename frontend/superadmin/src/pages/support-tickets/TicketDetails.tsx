import { useState, useEffect } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { 
  ArrowLeft, 
  Send, 
  UserCheck, 
  ExternalLink
} from 'lucide-react'
import { Ticket, initialTickets } from './ListTickets'

interface Reply {
  id: number
  senderName: string
  senderType: 'admin' | 'franchisee'
  message: string
  created_at: string
}

// Initial mock replies mapped by ticket ID
const initialReplies: Record<number, Reply[]> = {
  1024: [
    { id: 1, senderName: 'Rabee (Sydney West)', senderType: 'franchisee', message: 'Hi support team, we are trying to sync our franchisee invoices to Xero, but it fails with error: "Unauthorized oauth_problem=token_rejected". We have cleared our cache and reconnected, but it still fails. Please help.', created_at: '2026-07-12 09:30 am' }
  ],
  1023: [
    { id: 1, senderName: 'Lars (Melbourne CBD)', senderType: 'franchisee', message: 'Hello, several customers are reporting they cannot complete payments. The checkout page displays a popup saying "Error 402: Payment required / Gateway rejection". Can you verify if the gateway credentials are active?', created_at: '2026-07-11 04:15 pm' },
    { id: 2, senderName: 'John Admin', senderType: 'admin', message: 'Hi Lars, we are looking into the Cybersource gateway settings for Melbourne CBD. It seems your merchant profile was in test mode. We are switching it to production now.', created_at: '2026-07-11 04:45 pm' }
  ],
  1022: [
    { id: 1, senderName: 'Megan (Brisbane East)', senderType: 'franchisee', message: 'Could we request the addition of the new "Premium Shampoo Service" item in the service groups for QLD? Our pricing template needs to be updated with the $45.00 base rate.', created_at: '2026-07-10 11:20 am' }
  ],
  1021: [
    { id: 1, senderName: 'Ava (Perth Hills)', senderType: 'franchisee', message: 'The iPad app keeps crashing when updating a booking detail to a different therapist or time. It happens right after hitting "Save booking". Here is the crash report: RangeError: Maximum call stack size exceeded.', created_at: '2026-07-09 03:45 pm' },
    { id: 2, senderName: 'John Admin', senderType: 'admin', message: 'Thank you for the logs, Ava. We found a recursive call in the booking sync module. We released app version 2.1.1009 to address this. Please check if you can download the version update.', created_at: '2026-07-10 10:00 am' },
    { id: 3, senderName: 'Ava (Perth Hills)', senderType: 'franchisee', message: 'Awesome, we downloaded the new version and it is working perfectly now! Closing the issue.', created_at: '2026-07-10 02:30 pm' }
  ],
  1020: [
    { id: 1, senderName: 'Libby (Adelaide South)', senderType: 'franchisee', message: 'Hi, our uniform order #U-94002 was marked as shipped but there is no Australia Post tracking number logged in the orders module. Could you please provide the tracking number?', created_at: '2026-07-08 10:05 am' }
  ],
  1019: [
    { id: 1, senderName: 'May (Gold Coast North)', senderType: 'franchisee', message: 'Hi, we cannot register new customers through the intake form. The API returns a 500 server error on the suburb autocomplete field. Suburb code: 4215.', created_at: '2026-07-07 01:10 pm' }
  ]
}

export function TicketDetails() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const ticketId = Number(id)

  const [tickets, setTickets] = useState<Ticket[]>([])
  const [ticket, setTicket] = useState<Ticket | null>(null)
  const [replies, setReplies] = useState<Reply[]>([])
  const [replyText, setReplyText] = useState('')

  // Load ticket and replies
  useEffect(() => {
    // 1. Load tickets list
    let currentTickets = initialTickets
    const savedTickets = localStorage.getItem('superadmin_support_tickets')
    if (savedTickets) {
      try {
        currentTickets = JSON.parse(savedTickets)
      } catch (e) {
        // Fallback
      }
    } else {
      localStorage.setItem('superadmin_support_tickets', JSON.stringify(initialTickets))
    }
    setTickets(currentTickets)

    // Find the specific ticket
    const currentTicket = currentTickets.find(t => t.id === ticketId)
    if (currentTicket) {
      setTicket(currentTicket)
    } else {
      navigate('/support-tickets/list')
      return
    }

    // 2. Load replies
    let ticketReplies: Reply[] = []
    const savedReplies = localStorage.getItem(`superadmin_ticket_replies_${ticketId}`)
    if (savedReplies) {
      try {
        ticketReplies = JSON.parse(savedReplies)
      } catch (e) {
        // Fallback
      }
    } else {
      ticketReplies = initialReplies[ticketId] || []
      localStorage.setItem(`superadmin_ticket_replies_${ticketId}`, JSON.stringify(ticketReplies))
    }
    setReplies(ticketReplies)
  }, [ticketId, navigate])

  const updateTicketInStorage = (updatedTicket: Ticket) => {
    setTicket(updatedTicket)
    const updatedList = tickets.map(t => t.id === updatedTicket.id ? updatedTicket : t)
    setTickets(updatedList)
    localStorage.setItem('superadmin_support_tickets', JSON.stringify(updatedList))
  }

  const handleStatusChange = (newStatus: Ticket['status']) => {
    if (!ticket) return
    const updated = { ...ticket, status: newStatus }
    updateTicketInStorage(updated)
  }

  const handlePriorityChange = (newPriority: Ticket['priority']) => {
    if (!ticket) return
    const updated = { ...ticket, priority: newPriority }
    updateTicketInStorage(updated)
  }

  const handleAssigneeChange = (newAssignee: string) => {
    if (!ticket) return
    const updated = { ...ticket, assignedToName: newAssignee }
    // If it was open/unassigned, automatically mark it in progress when assigned
    if (newAssignee !== 'Unassigned' && updated.status === 'open') {
      updated.status = 'in_progress'
    }
    updateTicketInStorage(updated)
  }

  const handleSendReply = (e: React.FormEvent) => {
    e.preventDefault()
    if (!replyText.trim() || !ticket) return

    const newReply: Reply = {
      id: Date.now(),
      senderName: 'John Admin (Superadmin)', // Simulate logged in superadmin name
      senderType: 'admin',
      message: replyText,
      created_at: new Date().toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      })
    }

    const updatedReplies = [...replies, newReply]
    setReplies(updatedReplies)
    localStorage.setItem(`superadmin_ticket_replies_${ticketId}`, JSON.stringify(updatedReplies))

    // Automatically transition ticket status: if waiting or open, mark in_progress
    let updatedTicket = { ...ticket }
    if (ticket.status === 'open' || ticket.status === 'waiting') {
      updatedTicket.status = 'in_progress'
    }
    updateTicketInStorage(updatedTicket)
    setReplyText('')
  }

  if (!ticket) {
    return <div className="p-8 text-center text-gray-500">Loading ticket #{ticketId}...</div>
  }

  return (
    <div className="page-content">
      {/* Back to List */}
      <button 
        onClick={() => navigate('/support-tickets/list')}
        className="flex items-center gap-2 text-gray-500 hover:text-gray-800 font-medium mb-4 text-sm transition-colors"
      >
        <ArrowLeft size={16} />
        Back to Ticket List
      </button>

      {/* Main Layout Grid */}
      <div className="grid grid-cols-3 gap-6">
        
        {/* Left 2 Columns: Messaging Conversation */}
        <div className="col-span-2 flex flex-col bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden h-[600px]">
          
          {/* Conversation Header */}
          <div className="px-6 py-4 border-b bg-gray-50/50 flex justify-between items-center">
            <div>
              <div className="flex items-center gap-2">
                <span className="text-lg font-bold text-gray-800">Ticket #{ticket.id}</span>
                <span className={`px-2 py-0.5 rounded-full text-xs font-semibold uppercase ${
                  ticket.status === 'open' ? 'bg-red-100 text-red-800' :
                  ticket.status === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                  ticket.status === 'waiting' ? 'bg-yellow-100 text-yellow-800' :
                  'bg-green-100 text-green-800'
                }`}>
                  {ticket.status.replace('_', ' ')}
                </span>
              </div>
              <h1 className="text-sm font-medium text-gray-600 mt-1">{ticket.title}</h1>
            </div>
            <span className="text-xs text-gray-400">Created: {ticket.created_at}</span>
          </div>

          {/* Conversation Messages */}
          <div className="flex-1 p-6 overflow-y-auto space-y-4 bg-slate-50/50">
            {replies.map((reply) => {
              const isAdmin = reply.senderType === 'admin'
              return (
                <div 
                  key={reply.id} 
                  className={`flex gap-3 max-w-[85%] ${isAdmin ? 'ml-auto flex-row-reverse' : 'mr-auto'}`}
                >
                  {/* Avatar */}
                  <div className={`w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shrink-0 select-none ${
                    isAdmin ? 'bg-purple-600 text-white' : 'bg-gray-300 text-gray-700'
                  }`}>
                    {isAdmin ? 'A' : 'F'}
                  </div>

                  {/* Bubble */}
                  <div>
                    <div className={`text-[10px] text-gray-400 mb-0.5 ${isAdmin ? 'text-right' : 'text-left'}`}>
                      {reply.senderName} • {reply.created_at}
                    </div>
                    <div className={`p-3 rounded-xl text-sm leading-relaxed ${
                      isAdmin ? 'bg-purple-600 text-white rounded-tr-none' : 'bg-white border border-gray-200 text-gray-800 rounded-tl-none shadow-sm'
                    }`}>
                      {reply.message}
                    </div>
                  </div>
                </div>
              )
            })}
          </div>

          {/* Send Box Form */}
          <form onSubmit={handleSendReply} className="p-4 border-t bg-white flex gap-3 items-center">
            <textarea
              value={replyText}
              onChange={(e) => setReplyText(e.target.value)}
              placeholder="Write a response to the franchisee..."
              rows={1}
              className="flex-grow border border-gray-300 rounded-lg px-3 py-2.5 text-sm resize-none focus:outline-none focus:border-purple-600"
              onKeyDown={(e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                  e.preventDefault()
                  handleSendReply(e)
                }
              }}
            />
            <button 
              type="submit"
              disabled={!replyText.trim()}
              className="p-2.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-40 disabled:hover:bg-purple-600 transition-colors"
            >
              <Send size={18} />
            </button>
          </form>
        </div>

        {/* Right 1 Column: Ticket Settings/Metadata */}
        <div className="space-y-6">
          
          {/* Control Settings Card */}
          <div className="card bg-white p-6 border border-gray-100 rounded-xl shadow-sm space-y-4">
            <h2 className="text-sm font-bold text-gray-700 uppercase tracking-wider border-b pb-2">Ticket Actions</h2>
            
            {/* Status Selector */}
            <div>
              <label className="block text-xs font-semibold text-gray-500 uppercase mb-1">Update Status</label>
              <select 
                value={ticket.status}
                onChange={(e) => handleStatusChange(e.target.value as Ticket['status'])}
                className="w-full border border-gray-300 rounded px-2.5 py-1.5 text-sm focus:outline-none focus:border-purple-600"
              >
                <option value="open">Open</option>
                <option value="in_progress">In Progress</option>
                <option value="waiting">Waiting Franchisee</option>
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
              </select>
            </div>

            {/* Priority Selector */}
            <div>
              <label className="block text-xs font-semibold text-gray-500 uppercase mb-1">Set Priority</label>
              <select 
                value={ticket.priority}
                onChange={(e) => handlePriorityChange(e.target.value as Ticket['priority'])}
                className="w-full border border-gray-300 rounded px-2.5 py-1.5 text-sm focus:outline-none focus:border-purple-600"
              >
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>

            {/* Assignee Selector */}
            <div>
              <label className="block text-xs font-semibold text-gray-500 uppercase mb-1">Assign Ticket</label>
              <select 
                value={ticket.assignedToName}
                onChange={(e) => handleAssigneeChange(e.target.value)}
                className="w-full border border-gray-300 rounded px-2.5 py-1.5 text-sm focus:outline-none focus:border-purple-600"
              >
                <option value="Unassigned">Unassigned</option>
                <option value="John Admin">John Admin</option>
                <option value="Sarah Support">Sarah Support</option>
              </select>
            </div>
          </div>

          {/* Franchise / Customer Details Card */}
          <div className="card bg-white p-6 border border-gray-100 rounded-xl shadow-sm space-y-4">
            <h2 className="text-sm font-bold text-gray-700 uppercase tracking-wider border-b pb-2">Franchise Info</h2>
            
            <div className="space-y-3 text-sm">
              <div className="flex justify-between">
                <span className="text-gray-400 font-medium">Franchise:</span>
                <span className="text-gray-700 font-semibold">{ticket.franchiseName}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-400 font-medium">Code:</span>
                <span className="text-gray-700 font-mono font-semibold">{ticket.franchiseCode}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-400 font-medium">Assigned to:</span>
                <span className="text-gray-700 font-medium flex items-center gap-1">
                  <UserCheck size={14} className="text-gray-500" />
                  {ticket.assignedToName}
                </span>
              </div>
              <div className="pt-2 border-t flex justify-end">
                <button 
                  onClick={() => navigate('/members/list')}
                  className="text-xs text-purple-600 hover:text-purple-700 font-bold flex items-center gap-1 transition-colors"
                >
                  View Franchise Member <ExternalLink size={10} />
                </button>
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>
  )
}
