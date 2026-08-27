import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Filter, Check, X, ChevronUp, ChevronDown, ChevronLeft, ChevronRight, Pencil } from 'lucide-react'
import { franchisesApi } from '../../api/services'
import type { Franchise } from '../../types'

type SortField = 'owner_name' | 'name' | 'territory' | 'franchisee_type' | 'status' | 'has_ipad' | 'tscs_accepted'
type SortOrder = 'asc' | 'desc'

interface FilterState {
  search: string
  status: string
  franchisee_type: string
}

const initialFilters: FilterState = {
  search: '',
  status: '',
  franchisee_type: '',
}

const typeLabels: Record<string, string> = {
  master_franchisee: 'Master Franchisee',
  franchisee: 'Franchisee',
  franchisor: 'Franchisor',
}

function formatType(type: Franchise['franchisee_type']) {
  return type ? typeLabels[type] ?? type : '-'
}

export function ListMembers() {
  const navigate = useNavigate()
  const [sortField, setSortField] = useState<SortField>('owner_name')
  const [sortOrder, setSortOrder] = useState<SortOrder>('asc')
  const [showFilter, setShowFilter] = useState(false)
  const [filters, setFilters] = useState<FilterState>(initialFilters)
  const [appliedFilters, setAppliedFilters] = useState<FilterState>(initialFilters)
  const [rowsPerPage, setRowsPerPage] = useState(20)
  const [currentPage, setCurrentPage] = useState(1)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['franchises', appliedFilters.search, appliedFilters.status, sortField, sortOrder, rowsPerPage, currentPage],
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

  const members = (data?.data ?? []).filter((f) =>
    appliedFilters.franchisee_type ? f.franchisee_type === appliedFilters.franchisee_type : true
  )
  const totalMembers = data?.total ?? 0
  const lastPage = data?.last_page ?? 1
  const startIndex = totalMembers === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1
  const endIndex = Math.min(currentPage * rowsPerPage, totalMembers)

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
      <h1 className="page-title">Members</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">List Members</h2>
          <div className="flex gap-2">
            <button className={`btn ${showFilter ? 'btn-primary' : 'btn-success'}`} onClick={() => setShowFilter(!showFilter)}>
              <Filter size={14} />
              FILTER
            </button>
            <button className="btn btn-primary" onClick={() => navigate('/members/add')}>
              +ADD
            </button>
          </div>
        </div>

        {/* Filter Panel */}
        {showFilter && (
          <div className="p-6 border-b bg-white">
            <div className="grid grid-cols-3 gap-4">
              <div>
                <label className="block text-sm text-gray-600 mb-1">Search</label>
                <input
                  type="text"
                  value={filters.search}
                  onChange={(e) => handleFilterChange('search', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  placeholder="Owner, company, email..."
                />
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Active Members</label>
                <select
                  value={filters.status}
                  onChange={(e) => handleFilterChange('status', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value="">All</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="suspended">Suspended</option>
                  <option value="terminated">Terminated</option>
                </select>
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Member Type</label>
                <select
                  value={filters.franchisee_type}
                  onChange={(e) => handleFilterChange('franchisee_type', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value="">All</option>
                  <option value="master_franchisee">Master Franchisee</option>
                  <option value="franchisee">Franchisee</option>
                  <option value="franchisor">Franchisor</option>
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
            {appliedFilters.franchisee_type && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                Type: {typeLabels[appliedFilters.franchisee_type] ?? appliedFilters.franchisee_type}
                <button
                  onClick={() => removeFilter('franchisee_type')}
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
                <th className="cursor-pointer" onClick={() => handleSort('owner_name')}>
                  <span className="flex items-center">
                    Name <SortIcon field="owner_name" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('name')}>
                  <span className="flex items-center">
                    Company Name <SortIcon field="name" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('territory')}>
                  <span className="flex items-center">
                    Service Location <SortIcon field="territory" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('franchisee_type')}>
                  <span className="flex items-center">
                    Type <SortIcon field="franchisee_type" />
                  </span>
                </th>
                <th className="cursor-pointer text-center" onClick={() => handleSort('has_ipad')}>
                  <span className="flex items-center justify-center">
                    iPad <SortIcon field="has_ipad" />
                  </span>
                </th>
                <th className="cursor-pointer text-center" onClick={() => handleSort('status')}>
                  <span className="flex items-center justify-center">
                    Member Active <SortIcon field="status" />
                  </span>
                </th>
                <th className="cursor-pointer text-center" onClick={() => handleSort('tscs_accepted')}>
                  <span className="flex items-center justify-center">
                    Ts & Cs Accepted <SortIcon field="tscs_accepted" />
                  </span>
                </th>
                <th className="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr>
                  <td colSpan={8} className="text-center py-8 text-gray-400">
                    Loading members...
                  </td>
                </tr>
              ) : isError ? (
                <tr>
                  <td colSpan={8} className="text-center py-8 text-red-500">
                    Failed to load members.
                  </td>
                </tr>
              ) : members.length === 0 ? (
                <tr>
                  <td colSpan={8} className="text-center py-8 text-gray-400">
                    No members found.
                  </td>
                </tr>
              ) : (
                members.map((member) => (
                  <tr key={member.id}>
                    <td className="font-medium">{member.owner_name}</td>
                    <td>{member.name}</td>
                    <td className="max-w-xs">{member.territory || '-'}</td>
                    <td>{formatType(member.franchisee_type)}</td>
                    <td className="text-center">
                      {member.has_ipad ? (
                        <Check size={18} className="inline text-purple-600" />
                      ) : (
                        <X size={18} className="inline text-red-500" />
                      )}
                    </td>
                    <td className="text-center">
                      {member.status === 'active' ? (
                        <Check size={18} className="inline text-purple-600" />
                      ) : (
                        <X size={18} className="inline text-red-500" />
                      )}
                    </td>
                    <td className="text-center">
                      {member.tscs_accepted ? (
                        <Check size={18} className="inline text-purple-600" />
                      ) : (
                        <X size={18} className="inline text-red-500" />
                      )}
                    </td>
                    <td className="text-center">
                      <button
                        className="p-1 hover:bg-gray-100 rounded"
                        onClick={() => navigate(`/members/edit/${member.id}`)}
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
            {startIndex} - {endIndex} of {totalMembers}
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
