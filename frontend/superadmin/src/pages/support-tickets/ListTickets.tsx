import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
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
  UserCheck,
} from 'lucide-react'
import { supportTicketsApi } from '../../api/services'
import type { SupportTicket } from '../../types'

type SortField = 'id' | 'title' | 'franchise' | 'status' | 'priority' | 'created_at' | 'assignedTo'
type SortOrder = 'asc' | 'desc'

interface FilterState {
  search: string
  status: string
  priority: string
}

const initialFilters: FilterState = {
  search: '',
  status: '',
  priority: '',
}

export function ListTickets() {
  const navigate = useNavigate()

  const [sortField, setSortField] = useState<SortField>('id')
  const [sortOrder, setSortOrder] = useState<SortOrder>('desc')
  const [showFilter, setShowFilter] = useState(false)
  const [filters, setFilters] = useState<FilterState>(initialFilters)
  const [appliedFilters, setAppliedFilters] = useState<FilterState>(initialFilters)

  const [currentPage, setCurrentPage] = useState(1)
  const rowsPerPage = 10

  const { data, isLoading, isError } = useQuery({
    queryKey: ['tickets', appliedFilters, currentPage],
    queryFn: () =>
      supportTicketsApi.list({
        search: appliedFilters.search || undefined,
        status: appliedFilters.status || undefined,
        priority: appliedFilters.priority || undefined,
        per_page: rowsPerPage,
        page: currentPage,
      }),
    placeholderData: (prev) => prev,
  })

  const { data: stats } = useQuery({
    queryKey: ['ticket-stats'],
    queryFn: () => supportTicketsApi.stats(),
  })

  const tickets = data?.data ?? []
  const totalTickets = data?.total ?? 0
  const lastPage = data?.last_page ?? 1
  const startIndex = totalTickets === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1
  const endIndex = Math.min(currentPage * rowsPerPage, totalTickets)

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

  const sortValue = (ticket: SupportTicket, field: SortField): string | number => {
    switch (field) {
      case 'id':
        return ticket.id
      case 'title':
        return ticket.title
      case 'franchise':
        return ticket.franchise?.name ?? ''
      case 'status':
        return ticket.status
      case 'priority':
        return ticket.priority
      case 'assignedTo':
        return ticket.assignedTo?.name ?? ''
      case 'created_at':
        return ticket.created_at
      default:
        return ''
    }
  }

  const sortedTickets = [...tickets].sort((a, b) => {
    const av = sortValue(a, sortField)
    const bv = sortValue(b, sortField)
    const comparison = typeof av === 'number' && typeof bv === 'number' ? av - bv : String(av).localeCompare(String(bv))
    return sortOrder === 'asc' ? comparison : -comparison
  })

  const getStatusBadgeClass = (status: SupportTicket['status']) => {
    switch (status) {
      case 'OPEN': return 'bg-red-100 text-red-800'
      case 'IN_PROGRESS': return 'bg-blue-100 text-blue-800'
      case 'WAITING': return 'bg-yellow-100 text-yellow-800'
      case 'RESOLVED': return 'bg-green-100 text-green-800'
      case 'CLOSED': return 'bg-gray-100 text-gray-800'
      default: return 'bg-gray-100 text-gray-800'
    }
  }

  const getPriorityBadgeClass = (priority: SupportTicket['priority']) => {
    switch (priority) {
      case 'URGENT': return 'bg-rose-600 text-white text-[10px] font-bold uppercase px-1.5 py-0.5 rounded'
      case 'HIGH': return 'bg-orange-500 text-white text-[10px] font-bold uppercase px-1.5 py-0.5 rounded'
      case 'MEDIUM': return 'bg-amber-400 text-gray-900 text-[10px] font-bold uppercase px-1.5 py-0.5 rounded'
      case 'LOW': return 'bg-slate-300 text-gray-800 text-[10px] font-bold uppercase px-1.5 py-0.5 rounded'
      default: return 'bg-gray-100 text-gray-800'
    }
  }

  const handleFilterChange = (field: keyof FilterState, value: string) => {
    setFilters((prev) => ({ ...prev, [field]: value }))
  }

  const applyFilters = () => {
    setAppliedFilters(filters)
    setCurrentPage(1)
    setShowFilter(false)
  }

  const cancelFilters = () => {
    setFilters(appliedFilters)
    setShowFilter(false)
  }

  return (
    <div className="page-content">
      <h1 className="page-title">Support Tickets</h1>

      {/* Statistics Cards */}
      <div className="stats-grid">
        <div className="stat-card purple">
          <div className="stat-label">Total Tickets</div>
          <div className="stat-value">{stats?.total ?? 0}</div>
          <div className="stat-description">
            <Inbox size={14} className="inline mr-1" />
            All support tickets
          </div>
        </div>

        <div className="stat-card red">
          <div className="stat-label">Open</div>
          <div className="stat-value">{stats?.open ?? 0}</div>
          <div className="stat-description">
            <AlertCircle size={14} className="inline mr-1" />
            Awaiting response
          </div>
        </div>

        <div className="stat-card blue">
          <div className="stat-label">In Progress</div>
          <div className="stat-value">{stats?.in_progress ?? 0}</div>
          <div className="stat-description">
            <Clock size={14} className="inline mr-1" />
            Being worked on
          </div>
        </div>

        <div className="stat-card cyan">
          <div className="stat-label">Resolved</div>
          <div className="stat-value">{stats?.resolved ?? 0}</div>
          <div className="stat-description">
            <CheckCircle size={14} className="inline mr-1" />
            Completed tickets
          </div>
        </div>
      </div>

      {/* Ticket List Card */}
      <div className="card">
        <div className="card-header">
          <h2 className="card-title">Ticket Queue</h2>
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
          <div className="p-6 border-b bg-white">
            <div className="grid grid-cols-3 gap-4">
              <div>
                <label className="block text-sm text-gray-600 mb-1">Search Subject</label>
                <input
                  type="text"
                  value={filters.search}
                  onChange={(e) => handleFilterChange('search', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  placeholder="Search..."
                />
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Status</label>
                <select
                  value={filters.status}
                  onChange={(e) => handleFilterChange('status', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value="">All Statuses</option>
                  <option value="OPEN">Open</option>
                  <option value="IN_PROGRESS">In Progress</option>
                  <option value="WAITING">Waiting</option>
                  <option value="RESOLVED">Resolved</option>
                  <option value="CLOSED">Closed</option>
                </select>
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Priority</label>
                <select
                  value={filters.priority}
                  onChange={(e) => handleFilterChange('priority', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value="">All Priorities</option>
                  <option value="URGENT">Urgent</option>
                  <option value="HIGH">High</option>
                  <option value="MEDIUM">Medium</option>
                  <option value="LOW">Low</option>
                </select>
              </div>
            </div>
            <div className="flex justify-end gap-3 mt-4">
              <button onClick={cancelFilters} className="text-red-500 hover:text-red-600 font-medium">
                CANCEL
              </button>
              <button onClick={applyFilters} className="btn btn-primary">
                FILTER DATA
              </button>
            </div>
          </div>
        )}

        {/* Table */}
        <div className="card-body p-0">
          <table className="table">
            <thead>
              <tr>
                <th className="cursor-pointer" onClick={() => handleSort('id')}>
                  <span className="flex items-center">
                    ID <SortIcon field="id" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('title')}>
                  <span className="flex items-center">
                    Subject <SortIcon field="title" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('franchise')}>
                  <span className="flex items-center">
                    Franchise <SortIcon field="franchise" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('priority')}>
                  <span className="flex items-center">
                    Priority <SortIcon field="priority" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('status')}>
                  <span className="flex items-center">
                    Status <SortIcon field="status" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('assignedTo')}>
                  <span className="flex items-center">
                    Assignee <SortIcon field="assignedTo" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('created_at')}>
                  <span className="flex items-center">
                    Date Created <SortIcon field="created_at" />
                  </span>
                </th>
                <th className="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr>
                  <td colSpan={8} className="text-center py-8 text-gray-400">
                    Loading tickets...
                  </td>
                </tr>
              ) : isError ? (
                <tr>
                  <td colSpan={8} className="text-center py-8 text-red-500">
                    Failed to load tickets.
                  </td>
                </tr>
              ) : sortedTickets.length > 0 ? (
                sortedTickets.map((ticket) => (
                  <tr key={ticket.id}>
                    <td className="font-medium">#{ticket.id}</td>
                    <td className="max-w-sm truncate">{ticket.title}</td>
                    <td>
                      <div>{ticket.franchise?.name ?? '—'}</div>
                      {ticket.franchise?.code && (
                        <span className="text-[10px] bg-slate-100 text-slate-500 font-semibold px-1 rounded">{ticket.franchise.code}</span>
                      )}
                    </td>
                    <td>
                      <span className={getPriorityBadgeClass(ticket.priority)}>
                        {ticket.priority}
                      </span>
                    </td>
                    <td>
                      <span className={`px-2 py-0.5 rounded-full text-xs font-semibold capitalize ${getStatusBadgeClass(ticket.status)}`}>
                        {ticket.status.replace('_', ' ')}
                      </span>
                    </td>
                    <td>
                      <span className="inline-flex items-center gap-1.5">
                        <UserCheck size={14} className="text-gray-400" />
                        {ticket.assignedTo?.name ?? 'Unassigned'}
                      </span>
                    </td>
                    <td>{new Date(ticket.created_at).toLocaleString()}</td>
                    <td className="text-center">
                      <button
                        onClick={() => navigate(`/support-tickets/${ticket.id}`)}
                        className="btn btn-outline text-xs py-1 px-2"
                      >
                        <Eye size={12} />
                        View
                      </button>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={8} className="text-center py-8 text-gray-400">
                    No tickets found matching current criteria.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {/* Footer / Pagination */}
        <div className="card-footer flex items-center justify-end gap-4 py-3 px-6">
          <span className="text-sm text-gray-600">
            {startIndex} - {endIndex} of {totalTickets}
          </span>
          <div className="flex gap-1">
            <button
              className="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
              onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
              disabled={currentPage === 1}
            >
              <ChevronLeft size={18} />
            </button>
            <button
              className="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
              onClick={() => setCurrentPage((p) => Math.min(lastPage, p + 1))}
              disabled={currentPage >= lastPage}
            >
              <ChevronRight size={18} />
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
