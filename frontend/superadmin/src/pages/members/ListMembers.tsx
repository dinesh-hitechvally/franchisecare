import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Filter, Check, X, ChevronUp, ChevronDown, MoreVertical } from 'lucide-react'

interface Member {
  id: number
  name: string
  companyName: string
  serviceLocation: string
  type: 'Master Franchisee' | 'Franchisee' | 'Franchisor'
  lastActive: string
  ipad: boolean
  memberActive: boolean
  tscsAccepted: boolean
}

const mockMembers: Member[] = [
  { id: 1, name: 'Mate Support', companyName: 'RetailCare Pty Ltd', serviceLocation: 'Jindalee, Mt Ommaney, Riverhills, Westlake, Middle Park & Jamboree Heights', type: 'Master Franchisee', lastActive: 'May 27 2026, 06:29 pm', ipad: true, memberActive: true, tscsAccepted: true },
  { id: 2, name: 'Dave Laming', companyName: 'Blue Wheelers Mate Support', serviceLocation: 'Mate Support', type: 'Master Franchisee', lastActive: 'March 25 2026, 12:53 pm', ipad: true, memberActive: true, tscsAccepted: false },
]

type SortField = 'name' | 'companyName' | 'serviceLocation' | 'type' | 'lastActive' | 'ipad' | 'memberActive' | 'tscsAccepted'
type SortOrder = 'asc' | 'desc'

interface FilterState {
  search: string
  outstandingBookings: string
  activeMembers: string
  leadsSuspension: string
  migratedMembers: string
  socialMembers: string
  adminMembers: string
  state: string
  memberType: string
  ipad: string
  stockTake: string
  twoFactorAuth: string
  tscsAccepted: string
}

const initialFilters: FilterState = {
  search: '',
  outstandingBookings: '',
  activeMembers: '',
  leadsSuspension: '',
  migratedMembers: '',
  socialMembers: '',
  adminMembers: '',
  state: '',
  memberType: '',
  ipad: '',
  stockTake: '',
  twoFactorAuth: '',
  tscsAccepted: ''
}

export function ListMembers() {
  const navigate = useNavigate()
  const [members] = useState<Member[]>(mockMembers)
  const [sortField, setSortField] = useState<SortField>('name')
  const [sortOrder, setSortOrder] = useState<SortOrder>('asc')
  const [showFilter, setShowFilter] = useState(false)
  const [filters, setFilters] = useState<FilterState>(initialFilters)
  const [appliedFilters, setAppliedFilters] = useState<FilterState>(initialFilters)
  const [rowsPerPage, setRowsPerPage] = useState(100)

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

  return (
    <div className="page-content">
      <h1 className="page-title">Members</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">List Members</h2>
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
              onClick={() => navigate('/members/add')}
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
                <label className="block text-sm text-gray-600 mb-1">Outstanding Bookings</label>
                <select 
                  value={filters.outstandingBookings}
                  onChange={(e) => handleFilterChange('outstandingBookings', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="yes">Yes</option>
                  <option value="no">No</option>
                </select>
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Active Members</label>
                <select 
                  value={filters.activeMembers}
                  onChange={(e) => handleFilterChange('activeMembers', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="yes">Yes</option>
                  <option value="no">No</option>
                </select>
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Leads Suspension</label>
                <select 
                  value={filters.leadsSuspension}
                  onChange={(e) => handleFilterChange('leadsSuspension', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="yes">Yes</option>
                  <option value="no">No</option>
                </select>
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Migrated Members</label>
                <select 
                  value={filters.migratedMembers}
                  onChange={(e) => handleFilterChange('migratedMembers', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="yes">Yes</option>
                  <option value="no">No</option>
                </select>
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Social Members</label>
                <select 
                  value={filters.socialMembers}
                  onChange={(e) => handleFilterChange('socialMembers', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="yes">Yes</option>
                  <option value="no">No</option>
                </select>
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Admin Members</label>
                <select 
                  value={filters.adminMembers}
                  onChange={(e) => handleFilterChange('adminMembers', e.target.value)}
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
              <div>
                <label className="block text-sm text-gray-600 mb-1">Member Type</label>
                <select 
                  value={filters.memberType}
                  onChange={(e) => handleFilterChange('memberType', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="Master Franchisee">Master Franchisee</option>
                  <option value="Franchisee">Franchisee</option>
                  <option value="Franchisor">Franchisor</option>
                </select>
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">iPad</label>
                <select 
                  value={filters.ipad}
                  onChange={(e) => handleFilterChange('ipad', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="yes">Yes</option>
                  <option value="no">No</option>
                </select>
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Stock Take</label>
                <select 
                  value={filters.stockTake}
                  onChange={(e) => handleFilterChange('stockTake', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="yes">Yes</option>
                  <option value="no">No</option>
                </select>
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Two Factor Auth</label>
                <select 
                  value={filters.twoFactorAuth}
                  onChange={(e) => handleFilterChange('twoFactorAuth', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="yes">Yes</option>
                  <option value="no">No</option>
                </select>
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Ts & Cs Accepted</label>
                <select 
                  value={filters.tscsAccepted}
                  onChange={(e) => handleFilterChange('tscsAccepted', e.target.value)}
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
          <div className="px-6 py-3 border-b bg-gray-50 flex items-center gap-2">
            <span className="text-sm text-gray-600">Filtered:</span>
            {appliedFilters.search && (
              <span className="inline-flex items-center gap-1 text-sm">
                Search: {appliedFilters.search}
                <button 
                  onClick={() => removeFilter('search')}
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
                <th className="cursor-pointer" onClick={() => handleSort('companyName')}>
                  <span className="flex items-center">
                    Company Name <SortIcon field="companyName" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('serviceLocation')}>
                  <span className="flex items-center">
                    Service Location <SortIcon field="serviceLocation" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('type')}>
                  <span className="flex items-center">
                    Type <SortIcon field="type" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('lastActive')}>
                  <span className="flex items-center">
                    Last Active <SortIcon field="lastActive" />
                  </span>
                </th>
                <th className="cursor-pointer text-center" onClick={() => handleSort('ipad')}>
                  <span className="flex items-center justify-center">
                    iPAD <SortIcon field="ipad" />
                  </span>
                </th>
                <th className="cursor-pointer text-center" onClick={() => handleSort('memberActive')}>
                  <span className="flex items-center justify-center">
                    Member Active <SortIcon field="memberActive" />
                  </span>
                </th>
                <th className="cursor-pointer text-center" onClick={() => handleSort('tscsAccepted')}>
                  <span className="flex items-center justify-center">
                    Ts & Cs Accepted <SortIcon field="tscsAccepted" />
                  </span>
                </th>
                <th className="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {members.map((member) => (
                <tr key={member.id}>
                  <td className="font-medium">{member.name}</td>
                  <td>{member.companyName}</td>
                  <td className="max-w-xs">{member.serviceLocation}</td>
                  <td>{member.type}</td>
                  <td className="whitespace-nowrap">{member.lastActive}</td>
                  <td className="text-center">
                    {member.ipad ? (
                      <Check size={18} className="inline text-purple-600" />
                    ) : (
                      <X size={18} className="inline text-red-500" />
                    )}
                  </td>
                  <td className="text-center">
                    {member.memberActive ? (
                      <Check size={18} className="inline text-purple-600" />
                    ) : (
                      <X size={18} className="inline text-red-500" />
                    )}
                  </td>
                  <td className="text-center">
                    {member.tscsAccepted ? (
                      <Check size={18} className="inline text-purple-600" />
                    ) : (
                      <X size={18} className="inline text-red-500" />
                    )}
                  </td>
                  <td className="text-center">
                    <button className="p-1 hover:bg-gray-100 rounded">
                      <MoreVertical size={18} className="text-gray-500" />
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
          <span className="text-sm text-gray-600">1 - {members.length} of {members.length}</span>
          <div className="flex gap-1">
            <button className="p-1 text-gray-400 hover:text-gray-600">
              <ChevronUp size={18} className="rotate-[-90deg]" />
            </button>
            <button className="p-1 text-gray-400 hover:text-gray-600">
              <ChevronUp size={18} className="rotate-90" />
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
