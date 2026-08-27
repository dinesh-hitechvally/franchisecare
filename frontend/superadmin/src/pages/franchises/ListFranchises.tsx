import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Filter, ChevronUp, ChevronDown, ChevronLeft, ChevronRight, Pencil } from 'lucide-react'
import { franchisesApi } from '../../api/services'
import type { Franchise } from '../../types'

type SortField = 'name' | 'code' | 'owner_name' | 'email' | 'state' | 'status' | 'created_at'
type SortOrder = 'asc' | 'desc'

interface FilterState {
  search: string
  status: string
}

const initialFilters: FilterState = {
  search: '',
  status: '',
}

const statusBadgeStyle: Record<Franchise['status'], { bg: string; color: string }> = {
  ACTIVE: { bg: '#dcfce7', color: '#166534' },
  INACTIVE: { bg: '#f3f4f6', color: '#4b5563' },
  SUSPENDED: { bg: '#ffedd5', color: '#c2410c' },
  TERMINATED: { bg: '#fee2e2', color: '#b91c1c' },
}

function StatusBadge({ status }: { status: Franchise['status'] }) {
  const style = statusBadgeStyle[status] ?? statusBadgeStyle.INACTIVE
  return (
    <span
      style={{
        display: 'inline-block',
        padding: '3px 10px',
        borderRadius: '9999px',
        fontSize: '11px',
        fontWeight: 600,
        textTransform: 'capitalize',
        background: style.bg,
        color: style.color,
      }}
    >
      {status}
    </span>
  )
}

export function ListFranchises() {
  const navigate = useNavigate()
  const [sortField, setSortField] = useState<SortField>('created_at')
  const [sortOrder, setSortOrder] = useState<SortOrder>('desc')
  const [showFilter, setShowFilter] = useState(false)
  const [filters, setFilters] = useState<FilterState>(initialFilters)
  const [appliedFilters, setAppliedFilters] = useState<FilterState>(initialFilters)
  const [rowsPerPage, setRowsPerPage] = useState(20)
  const [currentPage, setCurrentPage] = useState(1)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['franchises', appliedFilters, sortField, sortOrder, rowsPerPage, currentPage],
    queryFn: () =>
      franchisesApi.list({
        search: appliedFilters.search || undefined,
        status: appliedFilters.status || undefined,
        sort_by: sortField,
        sort_order: sortOrder,
        per_page: rowsPerPage,
        page: currentPage,
      }),
    placeholderData: (prev) => prev,
  })

  const franchises = data?.data ?? []
  const totalFranchises = data?.total ?? 0
  const lastPage = data?.last_page ?? 1
  const startIndex = totalFranchises === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1
  const endIndex = Math.min(currentPage * rowsPerPage, totalFranchises)

  const handleSort = (field: SortField) => {
    if (sortField === field) {
      setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc')
    } else {
      setSortField(field)
      setSortOrder('asc')
    }
    setCurrentPage(1)
  }

  const SortIcon = ({ field }: { field: SortField }) => {
    return (
      <span className="inline-flex flex-col ml-1">
        <ChevronUp size={10} className={sortField === field && sortOrder === 'asc' ? 'text-purple-600' : 'text-gray-400'} />
        <ChevronDown size={10} className={`-mt-1 ${sortField === field && sortOrder === 'desc' ? 'text-purple-600' : 'text-gray-400'}`} />
      </span>
    )
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

  const removeFilter = (field: keyof FilterState) => {
    const newFilters = { ...appliedFilters, [field]: '' }
    setAppliedFilters(newFilters)
    setFilters(newFilters)
    setCurrentPage(1)
  }

  const hasActiveFilters = Object.values(appliedFilters).some((v) => v !== '')

  return (
    <div className="page-content">
      <h1 className="page-title">Franchises</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">List Franchises</h2>
          <div className="flex gap-2">
            <button className={`btn ${showFilter ? 'btn-primary' : 'btn-success'}`} onClick={() => setShowFilter(!showFilter)}>
              <Filter size={14} />
              FILTER
            </button>
            <button className="btn btn-primary" onClick={() => navigate('/franchises/add')}>
              +ADD
            </button>
          </div>
        </div>

        {/* Filter Panel */}
        {showFilter && (
          <div className="p-6 border-b bg-white">
            <div className="grid grid-cols-3 gap-4">
              <div className="col-span-2">
                <label className="block text-sm text-gray-600 mb-1">Search</label>
                <input
                  type="text"
                  value={filters.search}
                  onChange={(e) => handleFilterChange('search', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  placeholder="Name, code, owner, email..."
                />
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Status</label>
                <select
                  value={filters.status}
                  onChange={(e) => handleFilterChange('status', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value="">All</option>
                  <option value="ACTIVE">Active</option>
                  <option value="INACTIVE">Inactive</option>
                  <option value="SUSPENDED">Suspended</option>
                  <option value="TERMINATED">Terminated</option>
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

        {/* Applied Filters */}
        {hasActiveFilters && (
          <div className="px-6 py-3 border-b bg-gray-50 flex items-center gap-2 flex-wrap">
            <span className="text-sm text-gray-600">Filtered:</span>
            {appliedFilters.search && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                Search: {appliedFilters.search}
                <button
                  onClick={() => removeFilter('search')}
                  className="w-4 h-4 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs"
                >
                  ×
                </button>
              </span>
            )}
            {appliedFilters.status && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                Status: {appliedFilters.status}
                <button
                  onClick={() => removeFilter('status')}
                  className="w-4 h-4 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs"
                >
                  ×
                </button>
              </span>
            )}
          </div>
        )}

        <div className="card-body p-0">
          <table className="table">
            <thead>
              <tr>
                <th className="cursor-pointer" onClick={() => handleSort('name')}>
                  <span className="flex items-center">
                    Name <SortIcon field="name" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('code')}>
                  <span className="flex items-center">
                    Code <SortIcon field="code" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('owner_name')}>
                  <span className="flex items-center">
                    Owner <SortIcon field="owner_name" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('email')}>
                  <span className="flex items-center">
                    Email <SortIcon field="email" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('state')}>
                  <span className="flex items-center">
                    State <SortIcon field="state" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('status')}>
                  <span className="flex items-center">
                    Status <SortIcon field="status" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('created_at')}>
                  <span className="flex items-center">
                    Created <SortIcon field="created_at" />
                  </span>
                </th>
                <th className="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr>
                  <td colSpan={8} className="text-center py-8 text-gray-400">
                    Loading franchises...
                  </td>
                </tr>
              ) : isError ? (
                <tr>
                  <td colSpan={8} className="text-center py-8 text-red-500">
                    Failed to load franchises.
                  </td>
                </tr>
              ) : franchises.length === 0 ? (
                <tr>
                  <td colSpan={8} className="text-center py-8 text-gray-400">
                    No franchises found.
                  </td>
                </tr>
              ) : (
                franchises.map((franchise) => (
                  <tr
                    key={franchise.id}
                    className="cursor-pointer"
                    onClick={() => navigate(`/franchises/edit/${franchise.id}`)}
                  >
                    <td className="font-medium">{franchise.name}</td>
                    <td>{franchise.code}</td>
                    <td>{franchise.owner_name}</td>
                    <td>{franchise.email}</td>
                    <td>{franchise.state || '-'}</td>
                    <td>
                      <StatusBadge status={franchise.status} />
                    </td>
                    <td>{franchise.created_at ? new Date(franchise.created_at).toLocaleDateString() : '-'}</td>
                    <td className="text-center">
                      <button
                        className="p-1 hover:bg-gray-100 rounded"
                        onClick={(e) => {
                          e.stopPropagation()
                          navigate(`/franchises/edit/${franchise.id}`)
                        }}
                      >
                        <Pencil size={16} className="text-gray-500" />
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        <div className="card-footer flex items-center justify-end gap-4 py-3 px-6">
          <div className="flex items-center gap-2 text-sm text-gray-600">
            <span>Rows per page:</span>
            <select
              value={rowsPerPage}
              onChange={(e) => {
                setRowsPerPage(Number(e.target.value))
                setCurrentPage(1)
              }}
              className="border-none bg-transparent"
            >
              <option value={10}>10</option>
              <option value={20}>20</option>
              <option value={50}>50</option>
              <option value={100}>100</option>
            </select>
          </div>
          <span className="text-sm text-gray-600">
            {startIndex} - {endIndex} of {totalFranchises}
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
