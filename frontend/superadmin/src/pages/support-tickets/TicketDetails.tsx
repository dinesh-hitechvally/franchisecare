import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { AxiosError } from 'axios'
import toast from 'react-hot-toast'
import {
  ArrowLeft,
  Send,
  UserCheck,
  ExternalLink,
} from 'lucide-react'
import { supportTicketsApi, adminUsersApi } from '../../api/services'
import type { SupportTicket, SupportTicketReply } from '../../types'
import { useAuthStore } from '../../store/authStore'

function getErrorMessage(error: unknown, fallback: string): string {
  const err = error as AxiosError<{ errors?: Record<string, string[]>; message?: string }>
  const errors = err?.response?.data?.errors
  if (errors) {
    const firstField = Object.values(errors)[0]
    if (Array.isArray(firstField) && firstField[0]) {
      return firstField[0]
    }
  }
  return err?.response?.data?.message ?? fallback
}

export function TicketDetails() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const currentUser = useAuthStore((s) => s.user)
  const ticketId = Number(id)

  const [replyText, setReplyText] = useState('')

  const { data: ticket, isLoading, isError } = useQuery({
    queryKey: ['ticket', ticketId],
    queryFn: () => supportTicketsApi.get(ticketId),
    enabled: Number.isFinite(ticketId),
  })

  const { data: adminUsersPage } = useQuery({
    queryKey: ['admin-users', 'for-assign'],
    queryFn: () => adminUsersApi.list({ per_page: 100 }),
  })
  const adminUsers = adminUsersPage?.data ?? []

  const invalidateTicket = () => {
    queryClient.invalidateQueries({ queryKey: ['ticket', ticketId] })
    queryClient.invalidateQueries({ queryKey: ['tickets'] })
    queryClient.invalidateQueries({ queryKey: ['ticket-stats'] })
  }

  const statusMutation = useMutation({
    mutationFn: (status: SupportTicket['status']) => supportTicketsApi.update(ticketId, { status }),
    onSuccess: () => {
      toast.success('Ticket status updated')
      invalidateTicket()
    },
    onError: (error) => toast.error(getErrorMessage(error, 'Failed to update status')),
  })

  const priorityMutation = useMutation({
    mutationFn: (priority: SupportTicket['priority']) => supportTicketsApi.update(ticketId, { priority }),
    onSuccess: () => {
      toast.success('Ticket priority updated')
      invalidateTicket()
    },
    onError: (error) => toast.error(getErrorMessage(error, 'Failed to update priority')),
  })

  const assignMutation = useMutation({
    mutationFn: (assignedTo: number | null) =>
      assignedTo === null
        ? supportTicketsApi.update(ticketId, { assigned_to: null })
        : supportTicketsApi.assign(ticketId, assignedTo),
    onSuccess: () => {
      toast.success('Ticket assignment updated')
      invalidateTicket()
    },
    onError: (error) => toast.error(getErrorMessage(error, 'Failed to update assignee')),
  })

  const replyMutation = useMutation({
    mutationFn: (message: string) => supportTicketsApi.reply(ticketId, message),
    onSuccess: () => {
      setReplyText('')
      invalidateTicket()
    },
    onError: (error) => toast.error(getErrorMessage(error, 'Failed to send reply')),
  })

  const handleStatusChange = (newStatus: SupportTicket['status']) => {
    statusMutation.mutate(newStatus)
  }

  const handlePriorityChange = (newPriority: SupportTicket['priority']) => {
    priorityMutation.mutate(newPriority)
  }

  const handleAssigneeChange = (value: string) => {
    assignMutation.mutate(value === '' ? null : Number(value))
  }

  const handleSendReply = (e: React.FormEvent) => {
    e.preventDefault()
    if (!replyText.trim()) return
    replyMutation.mutate(replyText)
  }

  const replySenderLabel = (reply: SupportTicketReply) => {
    const isAdmin = reply.user_type === 'admin'
    if (reply.user_id === currentUser?.id) {
      return currentUser?.name ?? 'Admin'
    }
    return isAdmin ? 'Admin' : 'Franchisee'
  }

  if (isLoading) {
    return <div className="p-8 text-center text-gray-500">Loading ticket #{ticketId}...</div>
  }

  if (isError || !ticket) {
    return (
      <div className="page-content">
        <button
          onClick={() => navigate('/support-tickets/list')}
          className="flex items-center gap-2 text-gray-500 hover:text-gray-800 font-medium mb-4 text-sm transition-colors"
        >
          <ArrowLeft size={16} />
          Back to Ticket List
        </button>
        <div className="p-8 text-center text-red-500">Ticket not found or failed to load.</div>
      </div>
    )
  }

  const replies = ticket.replies ?? []

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
        <div className="col-span-2 flex flex-col card overflow-hidden h-[600px]">

          {/* Conversation Header */}
          <div className="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
            <div>
              <div className="flex items-center gap-2">
                <span className="text-lg font-bold text-gray-800">Ticket #{ticket.id}</span>
                <span className={`px-2 py-0.5 rounded-full text-xs font-semibold capitalize ${
                  ticket.status === 'open' ? 'bg-red-100 text-red-800' :
                  ticket.status === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                  ticket.status === 'waiting' ? 'bg-yellow-100 text-yellow-800' :
                  ticket.status === 'closed' ? 'bg-gray-100 text-gray-800' :
                  'bg-green-100 text-green-800'
                }`}>
                  {ticket.status.replace('_', ' ')}
                </span>
              </div>
              <h1 className="text-sm font-medium text-gray-600 mt-1">{ticket.title}</h1>
              {ticket.description && (
                <p className="text-xs text-gray-500 mt-1 max-w-xl">{ticket.description}</p>
              )}
            </div>
            <span className="text-xs text-gray-400">Created: {new Date(ticket.created_at).toLocaleString()}</span>
          </div>

          {/* Conversation Messages */}
          <div className="flex-1 p-6 overflow-y-auto space-y-4 bg-gray-50">
            {replies.length === 0 ? (
              <div className="text-center text-sm text-gray-400 py-8">No replies yet.</div>
            ) : (
              replies.map((reply) => {
                const isAdmin = reply.user_type === 'admin'
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
                        {replySenderLabel(reply)} • {new Date(reply.created_at).toLocaleString()}
                      </div>
                      <div className={`p-3 rounded-xl text-sm leading-relaxed ${
                        isAdmin ? 'bg-purple-600 text-white rounded-tr-none' : 'bg-white border border-gray-200 text-gray-800 rounded-tl-none shadow-sm'
                      }`}>
                        {reply.message}
                      </div>
                    </div>
                  </div>
                )
              })
            )}
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
              disabled={!replyText.trim() || replyMutation.isPending}
              className="p-2.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-40 disabled:hover:bg-purple-600 transition-colors"
            >
              <Send size={18} />
            </button>
          </form>
        </div>

        {/* Right 1 Column: Ticket Settings/Metadata */}
        <div className="space-y-6">

          {/* Control Settings Card */}
          <div className="card p-6 space-y-4">
            <h2 className="text-sm font-bold text-gray-700 uppercase tracking-wider border-b pb-2">Ticket Actions</h2>

            {/* Status Selector */}
            <div>
              <label className="block text-xs font-semibold text-gray-500 uppercase mb-1">Update Status</label>
              <select
                value={ticket.status}
                onChange={(e) => handleStatusChange(e.target.value as SupportTicket['status'])}
                disabled={statusMutation.isPending}
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
                onChange={(e) => handlePriorityChange(e.target.value as SupportTicket['priority'])}
                disabled={priorityMutation.isPending}
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
                value={ticket.assigned_to ?? ''}
                onChange={(e) => handleAssigneeChange(e.target.value)}
                disabled={assignMutation.isPending}
                className="w-full border border-gray-300 rounded px-2.5 py-1.5 text-sm focus:outline-none focus:border-purple-600"
              >
                <option value="">Unassigned</option>
                {adminUsers.map((admin) => (
                  <option key={admin.id} value={admin.id}>{admin.name}</option>
                ))}
              </select>
            </div>
          </div>

          {/* Franchise / Customer Details Card */}
          <div className="card p-6 space-y-4">
            <h2 className="text-sm font-bold text-gray-700 uppercase tracking-wider border-b pb-2">Franchise Info</h2>

            <div className="space-y-3 text-sm">
              <div className="flex justify-between">
                <span className="text-gray-400 font-medium">Franchise:</span>
                <span className="text-gray-700 font-semibold">{ticket.franchise?.name ?? '—'}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-400 font-medium">Code:</span>
                <span className="text-gray-700 font-mono font-semibold">{ticket.franchise?.code ?? '—'}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-400 font-medium">Assigned to:</span>
                <span className="text-gray-700 font-medium flex items-center gap-1">
                  <UserCheck size={14} className="text-gray-500" />
                  {ticket.assignedTo?.name ?? 'Unassigned'}
                </span>
              </div>
              {ticket.resolved_at && (
                <div className="flex justify-between">
                  <span className="text-gray-400 font-medium">Resolved:</span>
                  <span className="text-gray-700 font-medium">{new Date(ticket.resolved_at).toLocaleString()}</span>
                </div>
              )}
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
