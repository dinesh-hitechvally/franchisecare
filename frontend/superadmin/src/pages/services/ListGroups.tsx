import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Filter, ChevronLeft, ChevronRight, Check, X } from 'lucide-react'

interface ServiceGroup {
  id: number
  groupName: string
  groupDescription: string
  active: boolean
}

const mockGroups: ServiceGroup[] = [
  { id: 1, groupName: 'Accessories', groupDescription: 'Accessories such as brushes, ribbons, leads etc', active: true },
  { id: 2, groupName: 'Flea Treatments', groupDescription: 'Provision of flea treatment to clients', active: true },
  { id: 3, groupName: 'Other', groupDescription: 'other services', active: true },
  { id: 4, groupName: 'Treats', groupDescription: 'Treat sales', active: true },
  { id: 5, groupName: 'Washing', groupDescription: 'Wash services', active: true },
  { id: 6, groupName: 'Grooming', groupDescription: 'Grooming Services', active: true },
]

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
  const [groups] = useState<ServiceGroup[]>(mockGroups)
  const [showFilter, setShowFilter] = useState(false)
  const [filters, setFilters] = useState<FilterState>(initialFilters)
  const [appliedFilters, setAppliedFilters] = useState<FilterState>(initialFilters)
  const [rowsPerPage, setRowsPerPage] = useState(10)
  const [currentPage, setCurrentPage] = useState(1)

  const totalGroups = 6

  const handleFilterChange = (field: keyof FilterState, value: string) => {
    setFilters(prev => ({ ...prev, [field]: value }))
  }

  const applyFilters = () => {
    setAppliedFilters(filters)
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
  }

  const hasActiveFilters = Object.values(appliedFilters).some(v => v !== '')

  const startIndex = (currentPage - 1) * rowsPerPage + 1
  const endIndex = Math.min(currentPage * rowsPerPage, totalGroups)

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
                <th className="text-center">Active</th>
                <th className="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {groups.map((group) => (
                <tr key={group.id}>
                  <td className="font-medium">{group.groupName}</td>
                  <td>{group.groupDescription}</td>
                  <td className="text-center">
                    {group.active ? (
                      <Check size={18} className="inline text-purple-600" />
                    ) : (
                      <X size={18} className="inline text-red-500" />
                    )}
                  </td>
                  <td className="text-center">
                    <button className="btn btn-primary text-xs py-1 px-3">
                      EDIT
                    </button>
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
              onChange={(e) => setRowsPerPage(Number(e.target.value))}
              className="border-none bg-transparent"
            >
              <option value={10}>10</option>
              <option value={20}>20</option>
              <option value={50}>50</option>
              <option value={100}>100</option>
            </select>
          </div>
          <span className="text-sm text-gray-600">{startIndex}-{endIndex} of {totalGroups}</span>
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
