import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { Filter, ChevronUp, ChevronDown, ChevronLeft, ChevronRight, Check, X } from 'lucide-react'
import { franchiseSuburbsApi } from '../../api/services'
import type { FranchiseSuburb } from '../../types'

type SortField = 'suburb_name' | 'franchise' | 'postcode' | 'state' | 'status'
type SortOrder = 'asc' | 'desc'

interface FilterState {
  search: string
  activeSuburb: string
  state: string
}

const initialFilters: FilterState = {
  search: '',
  activeSuburb: '',
  state: ''
}

export function ListSuburb() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [sortField, setSortField] = useState<SortField>('suburb_name')
  const [sortOrder, setSortOrder] = useState<SortOrder>('asc')
  const [showFilter, setShowFilter] = useState(false)
  const [filters, setFilters] = useState<FilterState>(initialFilters)
  const [appliedFilters, setAppliedFilters] = useState<FilterState>(initialFilters)
  const [rowsPerPage, setRowsPerPage] = useState(100)
  const [currentPage, setCurrentPage] = useState(1)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['franchise-suburbs', appliedFilters, rowsPerPage, currentPage],
    queryFn: () =>
      franchiseSuburbsApi.list({
        search: appliedFilters.search || undefined,
        state: appliedFilters.state || undefined,
        status: appliedFilters.activeSuburb === 'yes' ? 'ACTIVE' : appliedFilters.activeSuburb === 'no' ? 'INACTIVE' : undefined,
        per_page: rowsPerPage,
        page: currentPage,
      }),
    placeholderData: (prev) => prev,
  })

  const suburbs = data?.data ?? []
  const totalSuburbs = data?.total ?? 0
  const lastPage = data?.last_page ?? 1
  const startIndex = totalSuburbs === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1
  const endIndex = Math.min(currentPage * rowsPerPage, totalSuburbs)

  const sortedSuburbs = useMemo(() => {
    const list = [...suburbs]
    list.sort((a, b) => {
      let aVal: string
      let bVal: string
      if (sortField === 'franchise') {
        aVal = a.franchise?.name ?? ''
        bVal = b.franchise?.name ?? ''
      } else {
        aVal = String(a[sortField] ?? '')
        bVal = String(b[sortField] ?? '')
      }
      if (aVal < bVal) return sortOrder === 'asc' ? -1 : 1
      if (aVal > bVal) return sortOrder === 'asc' ? 1 : -1
      return 0
    })
    return list
  }, [suburbs, sortField, sortOrder])

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

  const handleFilterChange = (field: keyof FilterState, value: string) => {
    setFilters(prev => ({ ...prev, [field]: value }))
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

  const hasActiveFilters = Object.values(appliedFilters).some(v => v !== '')

  const handleDelete = async (suburb: FranchiseSuburb) => {
    if (!window.confirm(`Delete suburb "${suburb.suburb_name}"?`)) return
    try {
      await franchiseSuburbsApi.remove(suburb.id)
      toast.success('Suburb deleted successfully!')
      queryClient.invalidateQueries({ queryKey: ['franchise-suburbs'] })
    } catch (err: any) {
      toast.error(err?.response?.data?.message ?? 'Failed to delete suburb')
    }
  }

  return (
    <div className="page-content">
      <h1 className="page-title">List Suburb</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">List Suburb</h2>
          <div className="flex gap-2">
            <button
              className="btn btn-success"
              onClick={() => setShowFilter(!showFilter)}
            >
              <Filter size={14} />
              FILTER
            </button>
            <button
              className="btn btn-primary"
              onClick={() => navigate('/suburb/add')}
            >
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
                  placeholder=""
                />
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Active Suburb</label>
                <select
                  value={filters.activeSuburb}
                  onChange={(e) => handleFilterChange('activeSuburb', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="yes">Yes</option>
                  <option value="no">No</option>
                </select>
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">State</label>
                <select
                  value={filters.state}
                  onChange={(e) => handleFilterChange('state', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="NSW">NSW</option>
                  <option value="VIC">VIC</option>
                  <option value="QLD">QLD</option>
                  <option value="SA">SA</option>
                  <option value="WA">WA</option>
                  <option value="TAS">TAS</option>
                  <option value="NT">NT</option>
                  <option value="ACT">ACT</option>
                </select>
              </div>
            </div>
            <div className="flex justify-end gap-3 mt-4">
              <button
                onClick={cancelFilters}
                className="text-red-500 hover:text-red-600 font-medium"
              >
                CANCEL
              </button>
              <button
                onClick={applyFilters}
                className="btn btn-primary"
              >
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
            {appliedFilters.activeSuburb && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                Active: {appliedFilters.activeSuburb}
                <button
                  onClick={() => removeFilter('activeSuburb')}
                  className="w-4 h-4 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs"
                >
                  ×
                </button>
              </span>
            )}
            {appliedFilters.state && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                State: {appliedFilters.state}
                <button
                  onClick={() => removeFilter('state')}
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
                <th className="cursor-pointer" onClick={() => handleSort('suburb_name')}>
                  <span className="flex items-center">
                    Suburb Name <SortIcon field="suburb_name" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('franchise')}>
                  <span className="flex items-center">
                    Company Name <SortIcon field="franchise" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('postcode')}>
                  <span className="flex items-center">
                    Post Code <SortIcon field="postcode" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('state')}>
                  <span className="flex items-center">
                    State <SortIcon field="state" />
                  </span>
                </th>
                <th className="cursor-pointer text-center" onClick={() => handleSort('status')}>
                  <span className="flex items-center justify-center">
                    Active <SortIcon field="status" />
                  </span>
                </th>
                <th className="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr>
                  <td colSpan={6} className="text-center py-8 text-gray-400">
                    Loading suburbs...
                  </td>
                </tr>
              ) : isError ? (
                <tr>
                  <td colSpan={6} className="text-center py-8 text-red-500">
                    Failed to load suburbs.
                  </td>
                </tr>
              ) : sortedSuburbs.length === 0 ? (
                <tr>
                  <td colSpan={6} className="text-center py-8 text-gray-400">
                    No suburbs found.
                  </td>
                </tr>
              ) : (
                sortedSuburbs.map((suburb) => (
                  <tr key={suburb.id}>
                    <td className="font-medium">{suburb.suburb_name}</td>
                    <td>{suburb.franchise?.name ?? ''}</td>
                    <td>{suburb.postcode}</td>
                    <td>{suburb.state}</td>
                    <td className="text-center">
                      {suburb.status === 'ACTIVE' ? (
                        <Check size={18} className="inline text-purple-600" />
                      ) : (
                        <X size={18} className="inline text-red-500" />
                      )}
                    </td>
                    <td className="text-center">
                      <div className="inline-flex gap-2">
                        <button
                          className="btn btn-primary text-xs py-1 px-3"
                          onClick={() => navigate(`/suburb/edit/${suburb.id}`)}
                        >
                          EDIT
                        </button>
                        <button
                          className="btn btn-danger text-xs py-1 px-3"
                          onClick={() => handleDelete(suburb)}
                        >
                          DELETE
                        </button>
                      </div>
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
              onChange={(e) => { setRowsPerPage(Number(e.target.value)); setCurrentPage(1) }}
              className="border-none bg-transparent"
            >
              <option value={10}>10</option>
              <option value={20}>20</option>
              <option value={50}>50</option>
              <option value={100}>100</option>
            </select>
          </div>
          <span className="text-sm text-gray-600">{startIndex} - {endIndex} of {totalSuburbs.toLocaleString()}</span>
          <div className="flex gap-1">
            <button
              className="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
              onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
              disabled={currentPage === 1}
            >
              <ChevronLeft size={18} />
            </button>
            <button
              className="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
              onClick={() => setCurrentPage(p => Math.min(lastPage, p + 1))}
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
