import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { 
  Filter, 
  ChevronUp, 
  ChevronDown, 
  ChevronLeft, 
  ChevronRight, 
  CheckCircle, 
  Clock, 
  AlertCircle, 
  Eye,
  Inbox,
  UserCheck
} from 'lucide-react'

export interface Ticket {
  id: number
  title: string
  franchiseName: string
  franchiseCode: string
  status: 'open' | 'in_progress' | 'waiting' | 'resolved' | 'closed'
  priority: 'low' | 'medium' | 'high' | 'urgent'
  created_at: string
  assignedToName: string
}

// We export the mock tickets list so it can be shared or retrieved by ID
export const initialTickets: Ticket[] = [
  { id: 1024, title: 'Unable to sync Xero integration on franchisee dashboard', franchiseName: 'Sydney West', franchiseCode: 'SW-02', status: 'open', priority: 'urgent', created_at: '2026-07-12 09:30 am', assignedToName: 'Unassigned' },
  { id: 1023, title: 'Payment gateway returned error 402 on checkout page', franchiseName: 'Melbourne CBD', franchiseCode: 'MC-01', status: 'in_progress', priority: 'high', created_at: '2026-07-11 04:15 pm', assignedToName: 'John Admin' },
  { id: 1022, title: 'Requesting update to product list and pricing template', franchiseName: 'Brisbane East', franchiseCode: 'BE-04', status: 'waiting', priority: 'medium', created_at: '2026-07-10 11:20 am', assignedToName: 'Sarah Support' },
  { id: 1021, title: 'Franchisee mobile app crashed during booking update', franchiseName: 'Perth Hills', franchiseCode: 'PH-09', status: 'resolved', priority: 'high', created_at: '2026-07-09 03:45 pm', assignedToName: 'John Admin' },
  { id: 1020, title: 'Uniform shipment order tracking number is missing', franchiseName: 'Adelaide South', franchiseCode: 'AS-03', status: 'closed', priority: 'low', created_at: '2026-07-08 10:05 am', assignedToName: 'Unassigned' },
  { id: 1019, title: 'Customer registration API returning 500 error code', franchiseName: 'Gold Coast North', franchiseCode: 'GC-11', status: 'open', priority: 'high', created_at: '2026-07-07 01:10 pm', assignedToName: 'Sarah Support' },
]

type SortField = 'id' | 'title' | 'franchiseName' | 'status' | 'priority' | 'created_at' | 'assignedToName'
type SortOrder = 'asc' | 'desc'

export function ListTickets() {
  const navigate = useNavigate()
  
  // We'll read from localStorage or default to initialTickets so that updates in details persist
  const [tickets] = useState<Ticket[]>(() => {
    const saved = localStorage.getItem('superadmin_support_tickets')
    if (saved) {
      try {
        return JSON.parse(saved)
      } catch (e) {
        // Fallback
      }
    }
    return initialTickets
  })

  const [sortField, setSortField] = useState<SortField>('id')
  const [sortOrder, setSortOrder] = useState<SortOrder>('desc')
  const [showFilter, setShowFilter] = useState(false)
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [priorityFilter, setPriorityFilter] = useState('')
  
  const [currentPage, setCurrentPage] = useState(1)
  const rowsPerPage = 10

  // Calculate statistics
  const totalCount = tickets.length
  const openCount = tickets.filter(t => t.status === 'open').length
  const inProgressCount = tickets.filter(t => t.status === 'in_progress').length
  const waitingCount = tickets.filter(t => t.status === 'waiting').length
  const resolvedCount = tickets.filter(t => t.status === 'resolved').length

  const handleSort = (field: SortField) => {
    if (sortField === field) {
      setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc')
    } else {
      setSortField(field)
      setSortOrder('asc')
    }
  }

  const SortIcon = ({ field }: { field: SortField }) => {
    return (
      <span className="inline-flex flex-col ml-1">
        <ChevronUp size={10} className={sortField === field && sortOrder === 'asc' ? 'text-purple-600' : 'text-gray-400'} />
        <ChevronDown size={10} className={`-mt-1 ${sortField === field && sortOrder === 'desc' ? 'text-purple-600' : 'text-gray-400'}`} />
      </span>
    )
  }

  // Filtered and Sorted Tickets
  const filteredTickets = tickets.filter(ticket => {
    const matchesSearch = ticket.title.toLowerCase().includes(search.toLowerCase()) ||
                          ticket.franchiseName.toLowerCase().includes(search.toLowerCase()) ||
                          ticket.franchiseCode.toLowerCase().includes(search.toLowerCase())
    const matchesStatus = statusFilter === '' || ticket.status === statusFilter
    const matchesPriority = priorityFilter === '' || ticket.priority === priorityFilter
    return matchesSearch && matchesStatus && matchesPriority
  })

  const sortedTickets = [...filteredTickets].sort((a, b) => {
    let comparison = 0
    if (sortField === 'id') {
      comparison = a.id - b.id
    } else {
      comparison = String(a[sortField]).localeCompare(String(b[sortField]))
    }
    return sortOrder === 'asc' ? comparison : -comparison
  })

  // Pagination
  const startIndex = (currentPage - 1) * rowsPerPage
  const paginatedTickets = sortedTickets.slice(startIndex, startIndex + rowsPerPage)
  const totalPages = Math.ceil(sortedTickets.length / rowsPerPage) || 1

  const getStatusBadgeClass = (status: Ticket['status']) => {
    switch (status) {
      case 'open': return 'bg-red-100 text-red-800 border border-red-200'
      case 'in_progress': return 'bg-blue-100 text-blue-800 border border-blue-200'
      case 'waiting': return 'bg-yellow-100 text-yellow-800 border border-yellow-200'
      case 'resolved': return 'bg-green-100 text-green-800 border border-green-200'
      case 'closed': return 'bg-gray-100 text-gray-800 border border-gray-200'
      default: return 'bg-gray-100 text-gray-800'
    }
  }

  const getPriorityBadgeClass = (priority: Ticket['priority']) => {
    switch (priority) {
      case 'urgent': return 'bg-rose-600 text-white text-[10px] font-bold uppercase px-1.5 py-0.5 rounded'
      case 'high': return 'bg-orange-500 text-white text-[10px] font-bold uppercase px-1.5 py-0.5 rounded'
      case 'medium': return 'bg-amber-400 text-gray-900 text-[10px] font-bold uppercase px-1.5 py-0.5 rounded'
      case 'low': return 'bg-slate-300 text-gray-800 text-[10px] font-bold uppercase px-1.5 py-0.5 rounded'
      default: return 'bg-gray-100 text-gray-800'
    }
  }

  return (
    <div className="page-content">
      <h1 className="page-title">Support Tickets</h1>

      {/* Statistics Cards */}
      <div className="grid grid-cols-5 gap-4 mb-6">
        <div className="card p-4 flex items-center gap-4 bg-white border border-gray-100 shadow-sm">
          <div className="p-3 bg-purple-100 text-purple-600 rounded-lg">
            <Inbox size={24} />
          </div>
          <div>
            <div className="text-2xl font-bold text-gray-800">{totalCount}</div>
            <div className="text-xs text-gray-500 font-medium">Total Tickets</div>
          </div>
        </div>

        <div className="card p-4 flex items-center gap-4 bg-white border border-gray-100 shadow-sm">
          <div className="p-3 bg-red-100 text-red-600 rounded-lg">
            <AlertCircle size={24} />
          </div>
          <div>
            <div className="text-2xl font-bold text-gray-800">{openCount}</div>
            <div className="text-xs text-gray-500 font-medium">Open</div>
          </div>
        </div>

        <div className="card p-4 flex items-center gap-4 bg-white border border-gray-100 shadow-sm">
          <div className="p-3 bg-blue-100 text-blue-600 rounded-lg">
            <Clock size={24} />
          </div>
          <div>
            <div className="text-2xl font-bold text-gray-800">{inProgressCount}</div>
            <div className="text-xs text-gray-500 font-medium">In Progress</div>
          </div>
        </div>

        <div className="card p-4 flex items-center gap-4 bg-white border border-gray-100 shadow-sm">
          <div className="p-3 bg-yellow-100 text-yellow-600 rounded-lg">
            <Clock size={24} />
          </div>
          <div>
            <div className="text-2xl font-bold text-gray-800">{waitingCount}</div>
            <div className="text-xs text-gray-500 font-medium">Waiting</div>
          </div>
        </div>

        <div className="card p-4 flex items-center gap-4 bg-white border border-gray-100 shadow-sm">
          <div className="p-3 bg-green-100 text-green-600 rounded-lg">
            <CheckCircle size={24} />
          </div>
          <div>
            <div className="text-2xl font-bold text-gray-800">{resolvedCount}</div>
            <div className="text-xs text-gray-500 font-medium">Resolved</div>
          </div>
        </div>
      </div>

      {/* Ticket List Card */}
      <div className="card shadow-sm border border-gray-100">
        <div className="card-header flex justify-between items-center bg-white py-4 border-b">
          <h2 className="card-title font-semibold text-gray-700">Ticket Queue</h2>
          <div className="flex gap-2">
            <button 
              className={`btn ${showFilter ? 'btn-primary' : 'btn-success'}`}
              onClick={() => setShowFilter(!showFilter)}
            >
              <Filter size={14} />
              FILTER
            </button>
          </div>
        </div>

        {/* Filter Panel */}
        {showFilter && (
          <div className="p-6 border-b bg-gray-50/50">
            <div className="grid grid-cols-3 gap-4">
              <div>
                <label className="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wider">Search Subject / Franchise</label>
                <input 
                  type="text" 
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-purple-500"
                  placeholder="Search..."
                />
              </div>
              <div>
                <label className="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wider">Status</label>
                <select 
                  value={statusFilter}
                  onChange={(e) => setStatusFilter(e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-purple-500"
                >
                  <option value="">All Statuses</option>
                  <option value="open">Open</option>
                  <option value="in_progress">In Progress</option>
                  <option value="waiting">Waiting</option>
                  <option value="resolved">Resolved</option>
                  <option value="closed">Closed</option>
                </select>
              </div>
              <div>
                <label className="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wider">Priority</label>
                <select 
                  value={priorityFilter}
                  onChange={(e) => setPriorityFilter(e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-purple-500"
                >
                  <option value="">All Priorities</option>
                  <option value="urgent">Urgent</option>
                  <option value="high">High</option>
                  <option value="medium">Medium</option>
                  <option value="low">Low</option>
                </select>
              </div>
            </div>
          </div>
        )}

        {/* Table */}
        <div className="card-body p-0 bg-white">
          <table className="table w-full text-left">
            <thead>
              <tr className="bg-gray-50 text-gray-500 text-xs font-semibold border-b">
                <th className="py-3 px-4 cursor-pointer" onClick={() => handleSort('id')}>
                  <span className="flex items-center">
                    ID <SortIcon field="id" />
                  </span>
                </th>
                <th className="py-3 px-4 cursor-pointer" onClick={() => handleSort('title')}>
                  <span className="flex items-center">
                    Subject <SortIcon field="title" />
                  </span>
                </th>
                <th className="py-3 px-4 cursor-pointer" onClick={() => handleSort('franchiseName')}>
                  <span className="flex items-center">
                    Franchise <SortIcon field="franchiseName" />
                  </span>
                </th>
                <th className="py-3 px-4 cursor-pointer" onClick={() => handleSort('priority')}>
                  <span className="flex items-center">
                    Priority <SortIcon field="priority" />
                  </span>
                </th>
                <th className="py-3 px-4 cursor-pointer" onClick={() => handleSort('status')}>
                  <span className="flex items-center">
                    Status <SortIcon field="status" />
                  </span>
                </th>
                <th className="py-3 px-4 cursor-pointer" onClick={() => handleSort('assignedToName')}>
                  <span className="flex items-center">
                    Assignee <SortIcon field="assignedToName" />
                  </span>
                </th>
                <th className="py-3 px-4 cursor-pointer" onClick={() => handleSort('created_at')}>
                  <span className="flex items-center">
                    Date Created <SortIcon field="created_at" />
                  </span>
                </th>
                <th className="py-3 px-4 text-center">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {paginatedTickets.length > 0 ? (
                paginatedTickets.map((ticket) => (
                  <tr key={ticket.id} className="hover:bg-gray-50/50 transition-colors">
                    <td className="py-3 px-4 font-semibold text-gray-600 text-sm">#{ticket.id}</td>
                    <td className="py-3 px-4 text-sm font-medium text-gray-800 max-w-sm truncate">{ticket.title}</td>
                    <td className="py-3 px-4 text-sm text-gray-600">
                      <div>{ticket.franchiseName}</div>
                      <span className="text-[10px] bg-slate-100 text-slate-500 font-semibold px-1 rounded">{ticket.franchiseCode}</span>
                    </td>
                    <td className="py-3 px-4">
                      <span className={getPriorityBadgeClass(ticket.priority)}>
                        {ticket.priority}
                      </span>
                    </td>
                    <td className="py-3 px-4">
                      <span className={`px-2 py-0.5 rounded-full text-xs font-semibold ${getStatusBadgeClass(ticket.status)}`}>
                        {ticket.status.replace('_', ' ')}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-sm text-gray-600 flex items-center gap-1.5 mt-2.5">
                      <UserCheck size={14} className="text-gray-400" />
                      {ticket.assignedToName}
                    </td>
                    <td className="py-3 px-4 text-sm text-gray-500">{ticket.created_at}</td>
                    <td className="py-3 px-4 text-center">
                      <button 
                        onClick={() => navigate(`/support-tickets/${ticket.id}`)}
                        className="btn btn-sm btn-outline-primary inline-flex items-center gap-1 text-xs"
                      >
                        <Eye size={12} />
                        View
                      </button>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={8} className="py-8 text-center text-gray-400 text-sm">
                    No tickets found matching current criteria.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {/* Footer / Pagination */}
        <div className="card-footer bg-white border-t py-4 px-6 flex justify-between items-center">
          <span className="text-sm text-gray-500">
            Showing {filteredTickets.length > 0 ? startIndex + 1 : 0} to {Math.min(startIndex + rowsPerPage, filteredTickets.length)} of {filteredTickets.length} tickets
          </span>
          <div className="flex gap-1">
            <button 
              className="p-1.5 border border-gray-200 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:hover:bg-transparent"
              onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
              disabled={currentPage === 1}
            >
              <ChevronLeft size={16} />
            </button>
            <button 
              className="p-1.5 border border-gray-200 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:hover:bg-transparent"
              onClick={() => setCurrentPage(p => Math.min(totalPages, p + 1))}
              disabled={currentPage === totalPages}
            >
              <ChevronRight size={16} />
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
