import { useState } from 'react'
import { Filter, ChevronUp, ChevronDown, ChevronLeft, ChevronRight, Check, X, MoreVertical } from 'lucide-react'

interface Customer {
  id: number
  fullName: string
  suburb: string
  postCode: string
  address: string
  email: string
  stateName: string
  phone: string
  customerActive: boolean
}

const mockCustomers: Customer[] = [
  { id: 1, fullName: 'Rabee Peter786s', suburb: 'DARCH', postCode: '', address: 'Please ignore this lead, created from dev team.', email: '', stateName: 'New South Wales', phone: '1234567887 / 1234567887', customerActive: true },
  { id: 2, fullName: 'Lars Test', suburb: 'Ffgr', postCode: '', address: 'fhnd', email: '', stateName: 'New South Wales', phone: '9941763789', customerActive: true },
  { id: 3, fullName: 'testing the', suburb: 'eggert', postCode: '', address: 'sdff', email: '', stateName: 'New South Wales', phone: '0984534438', customerActive: true },
  { id: 4, fullName: 'The customer ok', suburb: 'eggert', postCode: '', address: 'sdf', email: '', stateName: 'New South Wales', phone: '8993344433', customerActive: true },
  { id: 5, fullName: 'Wishlist Customer', suburb: 'South yarra', postCode: '', address: '11 adam street', email: 'aubadsheet@gmail.com', stateName: 'Queensland', phone: '', customerActive: true },
  { id: 6, fullName: 'Corey West', suburb: 'Bradbury', postCode: '', address: '45 Farm st', email: '', stateName: 'New South Wales', phone: '0432777666', customerActive: true },
  { id: 7, fullName: 'Megan Shaffer', suburb: 'Rerum quod', postCode: '', address: 'Perferendis atti als.', email: '', stateName: 'Victoria', phone: '9993364432', customerActive: true },
  { id: 8, fullName: 'L libby Lucea', suburb: 'Exerci et', postCode: '', address: 'Est consequuntur lab', email: '', stateName: 'New South Wales', phone: '536678789', customerActive: true },
  { id: 9, fullName: 'Ava Rowe', suburb: 'Earque sit quaerat', postCode: '', address: 'Officia sapiente do.', email: '', stateName: 'Western Australia', phone: '456444333', customerActive: true },
  { id: 10, fullName: 'Price Watson', suburb: 'Et tenetur', postCode: '', address: 'Quis ut maximes et', email: '', stateName: 'South Australia', phone: '0945267816', customerActive: true },
  { id: 11, fullName: 'Kylynn Mata', suburb: 'Quaerat incidunt', postCode: '', address: 'Irure incidibunt est', email: '', stateName: 'South Australia', phone: '1234689932', customerActive: true },
  { id: 12, fullName: 'Helen Faulkner', suburb: 'In inventore', postCode: '', address: 'Voluptas repu&lamder', email: '', stateName: 'Australian Capital Territory', phone: '4578847997', customerActive: true },
  { id: 13, fullName: 'Shaine Allen', suburb: 'St nostrui ejcle to', postCode: '', address: 'Laboriosam Edi voli', email: '', stateName: 'Australian Capital Territory', phone: '5797809914', customerActive: false },
  { id: 14, fullName: 'Chaim Watkins', suburb: 'Impedit quae commodo', postCode: '', address: 'Id adipisci veritari', email: '', stateName: 'Australian Capital Territory', phone: '5809880092', customerActive: true },
  { id: 15, fullName: 'Holness Holmes', suburb: 'Architecto perspiciatis', postCode: '', address: 'Rerum dolore proident', email: '', stateName: 'South Australia', phone: '2364376575', customerActive: true },
  { id: 16, fullName: 'aasfi asfi', suburb: 'afsi', postCode: '', address: 'sdifa', email: '', stateName: 'Queensland', phone: '3579034294', customerActive: true },
  { id: 17, fullName: 'test test', suburb: 'test', postCode: '', address: 'test', email: '', stateName: 'New South Wales', phone: '1231231231', customerActive: true },
  { id: 18, fullName: 'sdf d', suburb: 'eggert', postCode: '', address: 'sdf', email: '', stateName: 'Queensland', phone: '8967343294', customerActive: true },
  { id: 19, fullName: 'The name dd', suburb: 'sdhfd', postCode: '', address: 'sd', email: '', stateName: 'Queensland', phone: '3719636737', customerActive: true },
  { id: 20, fullName: 'dfds testi', suburb: 'sdsd', postCode: '', address: 'j', email: '', stateName: 'Queensland', phone: '3432046533', customerActive: true },
  { id: 21, fullName: 'sne test test', suburb: 'testt', postCode: '', address: 'asdd', email: '', stateName: 'Queensland', phone: '3643035884', customerActive: true },
  { id: 22, fullName: 'ht boy dfd', suburb: 'sfd', postCode: '', address: 'sdf', email: 'adbed@ebuyo.com', stateName: 'Queensland', phone: '2364476764', customerActive: true },
  { id: 23, fullName: 'sed wela', suburb: 'Explicabo Good Mood', postCode: '', address: 'Adipisci officia et', email: 'nebap743@transfers135.com', stateName: 'South Australia', phone: '0125284456', customerActive: true },
  { id: 24, fullName: 'test test 3', suburb: 'jhkm', postCode: '', address: 'hngfhgj', email: '', stateName: 'New South Wales', phone: '0000000009', customerActive: true },
  { id: 25, fullName: 'Shaun O\'Riely', suburb: 'sed', postCode: '', address: 'sedf', email: 'cytsydf@dfm.dc', stateName: 'Queensland', phone: '8923495123', customerActive: true },
  { id: 26, fullName: 'Dinesh Ghimire', suburb: 'Lathpur', postCode: '', address: 'Gwarko', email: 'dinesh@kitecheelley.com.au', stateName: 'New South Wales', phone: '3401111111', customerActive: true },
  { id: 27, fullName: 'asdfdsf testing', suburb: 'asppin', postCode: '', address: '123 test st', email: '', stateName: 'New South Wales', phone: '6434444444', customerActive: true },
  { id: 28, fullName: 'grumpy Cat', suburb: 'yarra', postCode: '', address: 'abc street', email: 'gprtyc949@mail.com', stateName: 'Queensland', phone: '9915389345', customerActive: true },
  { id: 29, fullName: 'Frank T', suburb: 'sdf', postCode: '', address: 'sdf', email: 'dbfgfsh@homail.com', stateName: 'Queensland', phone: '9643330562', customerActive: true },
  { id: 30, fullName: 'May Hinson', suburb: 'Ipsa error alia dic', postCode: '', address: 'Dicta ipie lipse inv', email: 'bipape@maifinator.com', stateName: 'Victoria', phone: '', customerActive: true },
]

type SortField = 'fullName' | 'suburb' | 'postCode' | 'address' | 'email' | 'stateName' | 'phone' | 'customerActive'
type SortOrder = 'asc' | 'desc'

interface FilterState {
  search: string
  customerActive: string
  franchisee: string
  state: string
  suburb: string
  petCount: string
}

const initialFilters: FilterState = {
  search: '',
  customerActive: '',
  franchisee: '',
  state: '',
  suburb: '',
  petCount: ''
}

export function ListCustomers() {
  const [customers] = useState<Customer[]>(mockCustomers)
  const [sortField, setSortField] = useState<SortField>('fullName')
  const [sortOrder, setSortOrder] = useState<SortOrder>('asc')
  const [showFilter, setShowFilter] = useState(false)
  const [filters, setFilters] = useState<FilterState>(initialFilters)
  const [appliedFilters, setAppliedFilters] = useState<FilterState>(initialFilters)
  const [rowsPerPage, setRowsPerPage] = useState(100)
  const [currentPage, setCurrentPage] = useState(1)

  const totalCustomers = 130539

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
  const endIndex = Math.min(currentPage * rowsPerPage, totalCustomers)

  return (
    <div className="page-content">
      <h1 className="page-title">Customers</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">List Customers</h2>
          <div className="flex gap-2">
            <button 
              className="btn btn-success"
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
                <label className="block text-sm text-gray-600 mb-1">Customer Active</label>
                <select 
                  value={filters.customerActive}
                  onChange={(e) => handleFilterChange('customerActive', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="yes">Yes</option>
                  <option value="no">No</option>
                </select>
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Franchisee</label>
                <select 
                  value={filters.franchisee}
                  onChange={(e) => handleFilterChange('franchisee', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="1">Franchisee 1</option>
                  <option value="2">Franchisee 2</option>
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
                <label className="block text-sm text-gray-600 mb-1">Suburb</label>
                <input 
                  type="text" 
                  value={filters.suburb}
                  onChange={(e) => handleFilterChange('suburb', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  placeholder=""
                />
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Pet Count</label>
                <select 
                  value={filters.petCount}
                  onChange={(e) => handleFilterChange('petCount', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                  <option value="4">4</option>
                  <option value="5+">5+</option>
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
            {appliedFilters.customerActive && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                Active: {appliedFilters.customerActive}
                <button 
                  onClick={() => removeFilter('customerActive')}
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
                <th className="cursor-pointer" onClick={() => handleSort('fullName')}>
                  <span className="flex items-center">
                    Full Name <SortIcon field="fullName" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('suburb')}>
                  <span className="flex items-center">
                    Suburb <SortIcon field="suburb" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('postCode')}>
                  <span className="flex items-center">
                    Post Code <SortIcon field="postCode" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('address')}>
                  <span className="flex items-center">
                    Address <SortIcon field="address" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('email')}>
                  <span className="flex items-center">
                    Email <SortIcon field="email" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('stateName')}>
                  <span className="flex items-center">
                    State Name <SortIcon field="stateName" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('phone')}>
                  <span className="flex items-center">
                    Phone <SortIcon field="phone" />
                  </span>
                </th>
                <th className="cursor-pointer text-center" onClick={() => handleSort('customerActive')}>
                  <span className="flex items-center justify-center">
                    Customer Active <SortIcon field="customerActive" />
                  </span>
                </th>
                <th className="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {customers.map((customer) => (
                <tr key={customer.id}>
                  <td className="font-medium">{customer.fullName}</td>
                  <td>{customer.suburb}</td>
                  <td>{customer.postCode}</td>
                  <td className="max-w-xs truncate">{customer.address}</td>
                  <td>{customer.email}</td>
                  <td>{customer.stateName}</td>
                  <td className="whitespace-nowrap">{customer.phone}</td>
                  <td className="text-center">
                    {customer.customerActive ? (
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
          <span className="text-sm text-gray-600">{startIndex} - {endIndex} to {totalCustomers.toLocaleString()}</span>
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
              disabled={endIndex >= totalCustomers}
            >
              <ChevronRight size={18} />
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
