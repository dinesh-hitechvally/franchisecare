import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Filter, ChevronUp, ChevronDown, ChevronLeft, ChevronRight, Check, X } from 'lucide-react'

interface Suburb {
  id: number
  suburbName: string
  companyName: string
  postCode: string
  member: string
  active: boolean
}

const mockSuburbs: Suburb[] = [
  { id: 1, suburbName: 'NORTH POLE', companyName: '', postCode: '9999', member: '', active: true },
  { id: 2, suburbName: 'JIBJIBYO', companyName: '', postCode: '0891', member: '', active: true },
  { id: 3, suburbName: 'ELFANGABLE', companyName: '', postCode: '5861', member: '', active: true },
  { id: 4, suburbName: 'GAPUWIYAK', companyName: '', postCode: '0880', member: '', active: true },
  { id: 5, suburbName: 'YIRRKALA', companyName: '', postCode: '0880', member: '', active: true },
  { id: 6, suburbName: 'NHULUNBUY', companyName: '', postCode: '0880', member: '', active: true },
  { id: 7, suburbName: 'ELWAGARANA', companyName: '', postCode: '0880', member: '', active: true },
  { id: 8, suburbName: 'FLYNX', companyName: '', postCode: '4700', member: '', active: true },
  { id: 9, suburbName: 'DROGOLAN', companyName: '', postCode: '4574', member: '', active: true },
  { id: 10, suburbName: 'BELFAL', companyName: '', postCode: '4579', member: '', active: true },
  { id: 11, suburbName: 'BOGRI JEONG', companyName: '', postCode: '4579', member: '', active: true },
  { id: 12, suburbName: 'CURRIMULLTY', companyName: '', postCode: '4573', member: '', active: true },
  { id: 13, suburbName: 'ARRAWPPA', companyName: '', postCode: '4593', member: '', active: true },
  { id: 14, suburbName: 'MARTINCULRA', companyName: '', postCode: '4593', member: '', active: true },
  { id: 15, suburbName: 'BOON', companyName: '', postCode: '4593', member: '', active: true },
  { id: 16, suburbName: 'VALCAPR', companyName: '', postCode: '4593', member: '', active: true },
  { id: 17, suburbName: 'ILKINDRI', companyName: '', postCode: '4573', member: '', active: true },
  { id: 18, suburbName: 'ELLIOTTLE SKYOW', companyName: '', postCode: '4573', member: '', active: true },
  { id: 19, suburbName: 'MANDEBBRA', companyName: '', postCode: '4572', member: '', active: true },
  { id: 20, suburbName: 'WHALIEBILTGA', companyName: '', postCode: '4572', member: '', active: true },
  { id: 21, suburbName: 'MARRANNINO', companyName: '', postCode: '4577', member: '', active: true },
  { id: 22, suburbName: 'EVIPRI', companyName: '', postCode: '4577', member: '', active: true },
  { id: 23, suburbName: 'BRIMATLA', companyName: '', postCode: '4594', member: '', active: true },
  { id: 24, suburbName: 'PTHARET FLASHNI', companyName: '', postCode: '4577', member: '', active: true },
  { id: 25, suburbName: 'MULLLEDNA SHANIAIN', companyName: '', postCode: '4573', member: '', active: true },
  { id: 26, suburbName: 'SWANDO POLAR LEGANA', companyName: '', postCode: '4573', member: '', active: true },
  { id: 27, suburbName: 'HOVAPRS', companyName: '', postCode: '4573', member: '', active: true },
  { id: 28, suburbName: 'PRAGIA', companyName: '', postCode: '4573', member: '', active: true },
  { id: 29, suburbName: 'NATKUMA', companyName: '', postCode: '4577', member: '', active: true },
  { id: 30, suburbName: 'WHAITLA', companyName: '', postCode: '4577', member: '', active: true },
]

type SortField = 'suburbName' | 'companyName' | 'postCode' | 'member' | 'active'
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
  const [suburbs] = useState<Suburb[]>(mockSuburbs)
  const [sortField, setSortField] = useState<SortField>('suburbName')
  const [sortOrder, setSortOrder] = useState<SortOrder>('asc')
  const [showFilter, setShowFilter] = useState(false)
  const [filters, setFilters] = useState<FilterState>(initialFilters)
  const [appliedFilters, setAppliedFilters] = useState<FilterState>(initialFilters)
  const [rowsPerPage, setRowsPerPage] = useState(100)
  const [currentPage, setCurrentPage] = useState(1)

  const totalSuburbs = 18774

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

  const startIndex = (currentPage - 1) * rowsPerPage + 1
  const endIndex = Math.min(currentPage * rowsPerPage, totalSuburbs)

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
                <th className="cursor-pointer" onClick={() => handleSort('suburbName')}>
                  <span className="flex items-center">
                    Suburb Name <SortIcon field="suburbName" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('companyName')}>
                  <span className="flex items-center">
                    Company Name <SortIcon field="companyName" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('postCode')}>
                  <span className="flex items-center">
                    Post Code <SortIcon field="postCode" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('member')}>
                  <span className="flex items-center">
                    Member <SortIcon field="member" />
                  </span>
                </th>
                <th className="cursor-pointer text-center" onClick={() => handleSort('active')}>
                  <span className="flex items-center justify-center">
                    Active <SortIcon field="active" />
                  </span>
                </th>
                <th className="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {suburbs.map((suburb) => (
                <tr key={suburb.id}>
                  <td className="font-medium">{suburb.suburbName}</td>
                  <td>{suburb.companyName}</td>
                  <td>{suburb.postCode}</td>
                  <td>{suburb.member}</td>
                  <td className="text-center">
                    {suburb.active ? (
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
          <span className="text-sm text-gray-600">{startIndex} - {endIndex} to {totalSuburbs.toLocaleString()}</span>
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
              disabled={endIndex >= totalSuburbs}
            >
              <ChevronRight size={18} />
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
