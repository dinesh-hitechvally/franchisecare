import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Filter, ChevronUp, ChevronDown, ChevronLeft, ChevronRight, Check, X, MoreVertical } from 'lucide-react'

interface Service {
  id: number
  serviceName: string
  serviceGroup: string
  petSize: string
  displayPosition: number
  servicePrice: string
  serviceDuration: string
  stockItemsUsed: number
  serviceActive: boolean
}

const mockServices: Service[] = [
  { id: 1, serviceName: 'Washing Only Small - Long Hair', serviceGroup: 'Washing', petSize: 'Small', displayPosition: 1, servicePrice: '$20', serviceDuration: '60 mins', stockItemsUsed: 0, serviceActive: true },
  { id: 2, serviceName: 'Washing Only Large - Long Hair', serviceGroup: 'Washing', petSize: 'Large', displayPosition: 2, servicePrice: '$0', serviceDuration: '60 mins', stockItemsUsed: 0, serviceActive: true },
  { id: 3, serviceName: 'Washing Only Toy - Long Hair', serviceGroup: 'Washing', petSize: 'Toy', displayPosition: 3, servicePrice: '$10', serviceDuration: '60 mins', stockItemsUsed: 0, serviceActive: true },
  { id: 4, serviceName: 'Washing Only Medium - Long Hair', serviceGroup: 'Washing', petSize: 'Medium', displayPosition: 4, servicePrice: '$70', serviceDuration: '40 mins', stockItemsUsed: 0, serviceActive: true },
  { id: 5, serviceName: 'Accessories', serviceGroup: 'Accessories', petSize: '', displayPosition: 5, servicePrice: '$0', serviceDuration: '0 mins', stockItemsUsed: 1, serviceActive: true },
  { id: 6, serviceName: 'Other', serviceGroup: 'Other', petSize: '', displayPosition: 6, servicePrice: '$0', serviceDuration: '1 mins', stockItemsUsed: 0, serviceActive: true },
  { id: 7, serviceName: 'Card Surcharge', serviceGroup: 'Other', petSize: '', displayPosition: 7, servicePrice: '$0', serviceDuration: '0 mins', stockItemsUsed: 0, serviceActive: true },
  { id: 8, serviceName: 'Deshed', serviceGroup: 'Other', petSize: '', displayPosition: 8, servicePrice: '$10', serviceDuration: '5 mins', stockItemsUsed: 3, serviceActive: true },
  { id: 9, serviceName: 'Nail Clipping', serviceGroup: 'Other', petSize: '', displayPosition: 9, servicePrice: '$20', serviceDuration: '10 mins', stockItemsUsed: 0, serviceActive: true },
  { id: 10, serviceName: 'Flea Treatments', serviceGroup: 'Flea Treatments', petSize: '', displayPosition: 10, servicePrice: '$15', serviceDuration: '5 mins', stockItemsUsed: 1, serviceActive: true },
  { id: 11, serviceName: 'Blue Wheelers Treats', serviceGroup: 'Treats', petSize: '', displayPosition: 11, servicePrice: '$10', serviceDuration: '0 mins', stockItemsUsed: 0, serviceActive: true },
  { id: 12, serviceName: 'Medicated Wash (90min)', serviceGroup: '', petSize: '', displayPosition: 12, servicePrice: '$92', serviceDuration: '90 mins', stockItemsUsed: 3, serviceActive: false },
  { id: 13, serviceName: 'Flea Wash (90min)', serviceGroup: '', petSize: '', displayPosition: 13, servicePrice: '$90', serviceDuration: '90 mins', stockItemsUsed: 3, serviceActive: false },
  { id: 14, serviceName: 'Full Groom Large', serviceGroup: 'Grooming', petSize: 'Large', displayPosition: 14, servicePrice: '$90', serviceDuration: '90 mins', stockItemsUsed: 3, serviceActive: true },
  { id: 15, serviceName: 'Medicated Wash (45min)', serviceGroup: '', petSize: '', displayPosition: 15, servicePrice: '$67', serviceDuration: '45 mins', stockItemsUsed: 3, serviceActive: false },
  { id: 16, serviceName: 'Flea Wash (45min)', serviceGroup: '', petSize: '', displayPosition: 16, servicePrice: '$65', serviceDuration: '45 mins', stockItemsUsed: 3, serviceActive: false },
  { id: 17, serviceName: 'Hygiene Clip Large', serviceGroup: 'Grooming', petSize: 'Large', displayPosition: 17, servicePrice: '$65', serviceDuration: '45 mins', stockItemsUsed: 3, serviceActive: true },
  { id: 18, serviceName: 'Medicated Wash (35-45min)', serviceGroup: '', petSize: '', displayPosition: 18, servicePrice: '$57', serviceDuration: '35 mins', stockItemsUsed: 3, serviceActive: false },
  { id: 19, serviceName: 'Flea Wash (35-50min)', serviceGroup: '', petSize: '', displayPosition: 19, servicePrice: '$55', serviceDuration: '35 mins', stockItemsUsed: 3, serviceActive: false },
  { id: 20, serviceName: 'Washing Only Large', serviceGroup: 'Washing', petSize: 'Large', displayPosition: 20, servicePrice: '$55', serviceDuration: '35 mins', stockItemsUsed: 3, serviceActive: true },
  { id: 21, serviceName: 'Medicated Wash (70-90min)', serviceGroup: '', petSize: '', displayPosition: 21, servicePrice: '$72', serviceDuration: '9 mins', stockItemsUsed: 3, serviceActive: false },
  { id: 22, serviceName: 'Flea Wash (70-90min)', serviceGroup: '', petSize: '', displayPosition: 22, servicePrice: '$70', serviceDuration: '90 mins', stockItemsUsed: 3, serviceActive: false },
  { id: 23, serviceName: 'Full Groom Medium', serviceGroup: 'Grooming', petSize: 'Medium', displayPosition: 23, servicePrice: '$70', serviceDuration: '90 mins', stockItemsUsed: 3, serviceActive: true },
  { id: 24, serviceName: 'Medicated Wash (40-50min)', serviceGroup: '', petSize: '', displayPosition: 24, servicePrice: '$57', serviceDuration: '40 mins', stockItemsUsed: 3, serviceActive: false },
  { id: 25, serviceName: 'Flea Wash (40-50min)', serviceGroup: '', petSize: '', displayPosition: 25, servicePrice: '$55', serviceDuration: '40 mins', stockItemsUsed: 3, serviceActive: false },
  { id: 26, serviceName: 'Hygiene Clip Medium', serviceGroup: 'Grooming', petSize: 'Medium', displayPosition: 26, servicePrice: '$55', serviceDuration: '40 mins', stockItemsUsed: 3, serviceActive: true },
  { id: 27, serviceName: 'Medicated Wash (35min)', serviceGroup: '', petSize: '', displayPosition: 27, servicePrice: '$47', serviceDuration: '35 mins', stockItemsUsed: 3, serviceActive: false },
  { id: 28, serviceName: 'Flea Wash (35min)', serviceGroup: '', petSize: '', displayPosition: 28, servicePrice: '$45', serviceDuration: '35 mins', stockItemsUsed: 3, serviceActive: false },
  { id: 29, serviceName: 'Washing Only Medium', serviceGroup: 'Washing', petSize: 'Medium', displayPosition: 29, servicePrice: '$45', serviceDuration: '35 mins', stockItemsUsed: 3, serviceActive: true },
  { id: 30, serviceName: 'Full Groom Small', serviceGroup: 'Grooming', petSize: 'Small', displayPosition: 30, servicePrice: '$65', serviceDuration: '90 mins', stockItemsUsed: 3, serviceActive: true },
]

type SortField = 'serviceName' | 'serviceGroup' | 'petSize' | 'displayPosition' | 'servicePrice' | 'serviceDuration' | 'stockItemsUsed' | 'serviceActive'
type SortOrder = 'asc' | 'desc'

interface FilterState {
  search: string
  activeService: string
  servicePetType: string
  serviceGroup: string
  servicePrice: string
  serviceDuration: string
  stockUsed: string
}

const initialFilters: FilterState = {
  search: '',
  activeService: '',
  servicePetType: '',
  serviceGroup: '',
  servicePrice: '',
  serviceDuration: '',
  stockUsed: ''
}

export function ListServices() {
  const navigate = useNavigate()
  const [services] = useState<Service[]>(mockServices)
  const [sortField, setSortField] = useState<SortField>('serviceName')
  const [sortOrder, setSortOrder] = useState<SortOrder>('asc')
  const [showFilter, setShowFilter] = useState(false)
  const [filters, setFilters] = useState<FilterState>(initialFilters)
  const [appliedFilters, setAppliedFilters] = useState<FilterState>(initialFilters)
  const [rowsPerPage, setRowsPerPage] = useState(100)
  const [currentPage, setCurrentPage] = useState(1)

  const totalServices = 43

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
  const endIndex = Math.min(currentPage * rowsPerPage, totalServices)

  return (
    <div className="page-content">
      <h1 className="page-title">List Services</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">List Services</h2>
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
              onClick={() => navigate('/services/add')}
            >
              +ADD
            </button>
          </div>
        </div>

        {/* Filter Panel */}
        {showFilter && (
          <div className="p-6 border-b bg-white">
            <div className="grid grid-cols-3 gap-4">
              <div className="col-span-3">
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
                <label className="block text-sm text-gray-600 mb-1">Active Service</label>
                <select 
                  value={filters.activeService}
                  onChange={(e) => handleFilterChange('activeService', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="yes">Yes</option>
                  <option value="no">No</option>
                </select>
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Service Pet Type</label>
                <select 
                  value={filters.servicePetType}
                  onChange={(e) => handleFilterChange('servicePetType', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="Small">Small</option>
                  <option value="Medium">Medium</option>
                  <option value="Large">Large</option>
                  <option value="Toy">Toy</option>
                </select>
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Service Group</label>
                <select 
                  value={filters.serviceGroup}
                  onChange={(e) => handleFilterChange('serviceGroup', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="Washing">Washing</option>
                  <option value="Grooming">Grooming</option>
                  <option value="Accessories">Accessories</option>
                  <option value="Flea Treatments">Flea Treatments</option>
                  <option value="Treats">Treats</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Service Price</label>
                <input 
                  type="text" 
                  value={filters.servicePrice}
                  onChange={(e) => handleFilterChange('servicePrice', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  placeholder=""
                />
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Service Duration</label>
                <input 
                  type="text" 
                  value={filters.serviceDuration}
                  onChange={(e) => handleFilterChange('serviceDuration', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  placeholder=""
                />
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Stock Used</label>
                <input 
                  type="text" 
                  value={filters.stockUsed}
                  onChange={(e) => handleFilterChange('stockUsed', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  placeholder=""
                />
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
            {appliedFilters.activeService && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                Active: {appliedFilters.activeService}
                <button 
                  onClick={() => removeFilter('activeService')}
                  className="w-4 h-4 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs"
                >
                  ×
                </button>
              </span>
            )}
            {appliedFilters.serviceGroup && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                Group: {appliedFilters.serviceGroup}
                <button 
                  onClick={() => removeFilter('serviceGroup')}
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
                <th className="cursor-pointer" onClick={() => handleSort('serviceName')}>
                  <span className="flex items-center">
                    Service Name <SortIcon field="serviceName" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('serviceGroup')}>
                  <span className="flex items-center">
                    Service Group <SortIcon field="serviceGroup" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('petSize')}>
                  <span className="flex items-center">
                    Pet Size <SortIcon field="petSize" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('displayPosition')}>
                  <span className="flex items-center">
                    Display Position <SortIcon field="displayPosition" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('servicePrice')}>
                  <span className="flex items-center">
                    Service Price <SortIcon field="servicePrice" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('serviceDuration')}>
                  <span className="flex items-center">
                    Service Duration <SortIcon field="serviceDuration" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('stockItemsUsed')}>
                  <span className="flex items-center">
                    # of Stock Items Used <SortIcon field="stockItemsUsed" />
                  </span>
                </th>
                <th className="cursor-pointer text-center" onClick={() => handleSort('serviceActive')}>
                  <span className="flex items-center justify-center">
                    Service Active <SortIcon field="serviceActive" />
                  </span>
                </th>
                <th className="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {services.map((service) => (
                <tr key={service.id}>
                  <td className="font-medium">{service.serviceName}</td>
                  <td>{service.serviceGroup}</td>
                  <td>{service.petSize}</td>
                  <td>{service.displayPosition}</td>
                  <td>{service.servicePrice}</td>
                  <td>{service.serviceDuration}</td>
                  <td>{service.stockItemsUsed}</td>
                  <td className="text-center">
                    {service.serviceActive ? (
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
          <span className="text-sm text-gray-600">{startIndex} - {endIndex} to {totalServices}</span>
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
              disabled={endIndex >= totalServices}
            >
              <ChevronRight size={18} />
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
