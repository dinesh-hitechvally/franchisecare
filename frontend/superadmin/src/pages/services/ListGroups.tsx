import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { Filter, ChevronLeft, ChevronRight, Check, X } from 'lucide-react'
import { serviceCategoriesApi } from '../../api/services'
import type { ServiceCategory } from '../../types'

interface FilterState {
  search: string
  activeServiceGroup: string
}

const initialFilters: FilterState = {
  search: '',
  activeServiceGroup: ''
}

export function ListGroups() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [showFilter, setShowFilter] = useState(false)
  const [filters, setFilters] = useState<FilterState>(initialFilters)
  const [appliedFilters, setAppliedFilters] = useState<FilterState>(initialFilters)
  const [rowsPerPage, setRowsPerPage] = useState(10)
  const [currentPage, setCurrentPage] = useState(1)

  const { data: groups = [], isLoading } = useQuery({
    queryKey: ['service-categories', appliedFilters],
    queryFn: () =>
      serviceCategoriesApi.list({
        search: appliedFilters.search || undefined,
        status: appliedFilters.activeServiceGroup === 'yes' ? 'ACTIVE' : appliedFilters.activeServiceGroup === 'no' ? 'INACTIVE' : undefined,
      }),
  })

  const totalGroups = groups.length
  const startIndex = totalGroups === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1
  const endIndex = Math.min(currentPage * rowsPerPage, totalGroups)
  const pagedGroups = groups.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage)

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

  const handleDelete = async (group: ServiceCategory) => {
    if (!window.confirm(`Delete group "${group.name}"?`)) return
    try {
      await serviceCategoriesApi.remove(group.id)
      toast.success('Group deleted successfully!')
      queryClient.invalidateQueries({ queryKey: ['service-categories'] })
    } catch (err: any) {
      toast.error(err?.response?.data?.message ?? 'Failed to delete group')
    }
  }

  return (
    <div className="page-content">
      <h1 className="page-title">List Groups</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">List Groups</h2>
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
              onClick={() => navigate('/services/add-groups')}
            >
              +ADD
            </button>
          </div>
        </div>

        {/* Filter Panel */}
        {showFilter && (
          <div className="p-6 border-b bg-white">
            <div className="grid grid-cols-2 gap-4">
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
                <label className="block text-sm text-gray-600 mb-1">Active Service Group</label>
                <select
                  value={filters.activeServiceGroup}
                  onChange={(e) => handleFilterChange('activeServiceGroup', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="yes">Yes</option>
                  <option value="no">No</option>
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
            {appliedFilters.activeServiceGroup && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                Active: {appliedFilters.activeServiceGroup}
                <button
                  onClick={() => removeFilter('activeServiceGroup')}
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
                <th>Group Name</th>
                <th>Group Description</th>
                <th>Sort Order</th>
                <th className="text-center">Active</th>
                <th className="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {isLoading && (
                <tr>
                  <td colSpan={5} className="text-center py-6 text-gray-500">Loading...</td>
                </tr>
              )}
              {!isLoading && pagedGroups.length === 0 && (
                <tr>
                  <td colSpan={5} className="text-center py-6 text-gray-500">No groups found</td>
                </tr>
              )}
              {pagedGroups.map((group) => (
                <tr key={group.id}>
                  <td className="font-medium">{group.name}</td>
                  <td>{group.description}</td>
                  <td>{group.sort_order}</td>
                  <td className="text-center">
                    {group.status === 'ACTIVE' ? (
                      <Check size={18} className="inline text-purple-600" />
                    ) : (
                      <X size={18} className="inline text-red-500" />
                    )}
                  </td>
                  <td className="text-center">
                    <div className="inline-flex gap-2">
                      <button
                        className="btn btn-primary text-xs py-1 px-3"
                        onClick={() => navigate(`/services/edit-groups/${group.id}`)}
                      >
                        EDIT
                      </button>
                      <button
                        className="btn btn-danger text-xs py-1 px-3"
                        onClick={() => handleDelete(group)}
                      >
                        DELETE
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
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
          <span className="text-sm text-gray-600">{startIndex} - {endIndex} of {totalGroups}</span>
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
              onClick={() => setCurrentPage(p => p + 1)}
              disabled={endIndex >= totalGroups}
            >
              <ChevronRight size={18} />
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
