import { useState } from 'react'
import { Filter, ChevronUp, ChevronDown, ChevronLeft, ChevronRight } from 'lucide-react'

interface Booking {
  id: number
  customerName: string
  bookingDate: string
  address: string
  bookingMethod: string
  lastUpdated: string
}

const mockBookings: Booking[] = [
  { id: 1, customerName: 'Sanjog Karki', bookingDate: 'January 24 2026, 06:10 pm', address: 'Newroad gate here dddddgs d', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 24 2026, 06:10 pm' },
  { id: 2, customerName: 'Sanjog Karki', bookingDate: 'January 24 2026, 06:10 pm', address: 'Newroad gate here dddddgs d', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 24 2026, 06:10 pm' },
  { id: 3, customerName: 'Sanjog Karki', bookingDate: 'January 24 2026, 06:10 pm', address: 'Newroad gate here dddddgs d', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 24 2026, 06:10 pm' },
  { id: 4, customerName: 'Sanjog Karki', bookingDate: 'January 24 2026, 04:10 pm', address: 'Newroad gate here dddddgs d', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 24 2026, 04:10 pm' },
  { id: 5, customerName: 'Sanjog Karki', bookingDate: 'January 24 2026, 04:10 pm', address: 'Newroad gate here dddddgs d', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 24 2026, 04:10 pm' },
  { id: 6, customerName: 'Sanjog Karki', bookingDate: 'January 24 2026, 04:10 pm', address: 'Newroad gate here dddddgs d', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 24 2026, 04:10 pm' },
  { id: 7, customerName: 'Stephanie', bookingDate: 'January 26 2026, 11:10 pm', address: '-', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 26 2026, 11:10 pm' },
  { id: 8, customerName: 'Stephanie', bookingDate: 'January 26 2026, 11:10 pm', address: '-', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 26 2026, 11:10 pm' },
  { id: 9, customerName: 'jenni indi', bookingDate: 'January 24 2026, 01:10 am', address: '-', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 24 2026, 01:10 am' },
  { id: 10, customerName: 'Mathew McOnoghie', bookingDate: 'January 22 2026, 02:10 am', address: '-', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 22 2026, 02:10 am' },
  { id: 11, customerName: 'Ron and Win', bookingDate: 'January 21 2026, 05:10 pm', address: '-', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 21 2026, 05:10 pm' },
  { id: 12, customerName: 'Cheryl', bookingDate: 'January 16 2026, 08:10 pm', address: '-', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 16 2026, 08:10 pm' },
  { id: 13, customerName: 'Debbie Patterson', bookingDate: 'January 16 2026, 05:10 pm', address: '-', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 16 2026, 05:10 pm' },
  { id: 14, customerName: 'Kim', bookingDate: 'January 10 2026, 11:10 pm', address: '-', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 10 2026, 11:10 pm' },
  { id: 15, customerName: 'Kim', bookingDate: 'January 10 2026, 11:10 pm', address: '-', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 10 2026, 11:10 pm' },
  { id: 16, customerName: 'David Anderson', bookingDate: 'January 10 2026, 10:10 am', address: '-', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 10 2026, 10:10 am' },
  { id: 17, customerName: 'Christy Lee Anderson', bookingDate: 'January 05 2026, 12:10 pm', address: '-', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 05 2026, 12:10 pm' },
  { id: 18, customerName: 'Tammy Mentzer', bookingDate: 'January 03 2026, 09:10 am', address: '-', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 03 2026, 09:10 am' },
  { id: 19, customerName: 'amanda dd po', bookingDate: 'April 28 2026, 07:10 pm', address: 'power', bookingMethod: 'Mate V2 Web', lastUpdated: 'April 28 2026, 07:10 pm' },
  { id: 20, customerName: 'amanda dd po', bookingDate: 'April 28 2026, 07:10 pm', address: 'power', bookingMethod: 'Mate V2 Web', lastUpdated: 'April 28 2026, 07:10 pm' },
  { id: 21, customerName: 'amanda dd po', bookingDate: 'April 28 2026, 06:10 pm', address: 'power', bookingMethod: 'Mate V2 Web', lastUpdated: 'April 28 2026, 06:10 pm' },
  { id: 22, customerName: 'amanda dd po', bookingDate: 'April 28 2026, 06:10 pm', address: 'power', bookingMethod: 'Mate V2 Web', lastUpdated: 'April 28 2026, 06:10 pm' },
  { id: 23, customerName: 'amanda dd po', bookingDate: 'April 28 2026, 05:10 pm', address: 'power', bookingMethod: 'Mate V2 Web', lastUpdated: 'April 28 2026, 05:10 pm' },
  { id: 24, customerName: 'amanda dd po', bookingDate: 'April 28 2026, 05:10 pm', address: 'power', bookingMethod: 'Mate V2 Web', lastUpdated: 'April 28 2026, 05:10 pm' },
  { id: 25, customerName: 'Michelle', bookingDate: 'January 26 2026, 06:10 pm', address: '-', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 26 2026, 06:10 pm' },
  { id: 26, customerName: 'MJ', bookingDate: 'January 26 2026, 05:10 am', address: '-', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 26 2026, 05:10 am' },
  { id: 27, customerName: 'Paul Kelly', bookingDate: 'January 24 2026, 06:10 am', address: '-', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 24 2026, 06:10 am' },
  { id: 28, customerName: 'Margaret Dillon', bookingDate: 'January 24 2026, 03:10 am', address: '-', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 24 2026, 03:10 am' },
  { id: 29, customerName: 'Margaret Dillon', bookingDate: 'January 24 2026, 03:10 am', address: '-', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 24 2026, 03:10 am' },
  { id: 30, customerName: 'Sue', bookingDate: 'January 21 2026, 03:10 am', address: '-', bookingMethod: 'Mate V2 Web', lastUpdated: 'January 21 2026, 03:10 am' },
]

type SortField = 'customerName' | 'bookingDate' | 'address' | 'bookingMethod' | 'lastUpdated'
type SortOrder = 'asc' | 'desc'

interface FilterState {
  search: string
  dateFrom: string
  dateTo: string
  bookingMethod: string
}

const initialFilters: FilterState = {
  search: '',
  dateFrom: '',
  dateTo: '',
  bookingMethod: ''
}

export function ActiveBookings() {
  const [bookings] = useState<Booking[]>(mockBookings)
  const [sortField, setSortField] = useState<SortField>('bookingDate')
  const [sortOrder, setSortOrder] = useState<SortOrder>('desc')
  const [showFilter, setShowFilter] = useState(false)
  const [filters, setFilters] = useState<FilterState>(initialFilters)
  const [appliedFilters, setAppliedFilters] = useState<FilterState>(initialFilters)
  const [rowsPerPage, setRowsPerPage] = useState(100)
  const [currentPage, setCurrentPage] = useState(1)

  const totalBookings = 371156

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
  const endIndex = Math.min(currentPage * rowsPerPage, totalBookings)

  return (
    <div className="page-content">
      <div className="card">
        <div className="card-header">
          <h2 className="card-title">List Active Bookings</h2>
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
            <div className="grid grid-cols-4 gap-4">
              <div>
                <label className="block text-sm text-gray-600 mb-1">Search</label>
                <input 
                  type="text" 
                  value={filters.search}
                  onChange={(e) => handleFilterChange('search', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  placeholder="Customer name..."
                />
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Date From</label>
                <input 
                  type="date" 
                  value={filters.dateFrom}
                  onChange={(e) => handleFilterChange('dateFrom', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Date To</label>
                <input 
                  type="date" 
                  value={filters.dateTo}
                  onChange={(e) => handleFilterChange('dateTo', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Booking Method</label>
                <select 
                  value={filters.bookingMethod}
                  onChange={(e) => handleFilterChange('bookingMethod', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="Mate V2 Web">Mate V2 Web</option>
                  <option value="Mate V2 App">Mate V2 App</option>
                  <option value="Phone">Phone</option>
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
            {appliedFilters.dateFrom && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                From: {appliedFilters.dateFrom}
                <button 
                  onClick={() => removeFilter('dateFrom')}
                  className="w-4 h-4 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs"
                >
                  ×
                </button>
              </span>
            )}
            {appliedFilters.dateTo && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                To: {appliedFilters.dateTo}
                <button 
                  onClick={() => removeFilter('dateTo')}
                  className="w-4 h-4 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs"
                >
                  ×
                </button>
              </span>
            )}
            {appliedFilters.bookingMethod && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                Method: {appliedFilters.bookingMethod}
                <button 
                  onClick={() => removeFilter('bookingMethod')}
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
                <th className="cursor-pointer" onClick={() => handleSort('customerName')}>
                  <span className="flex items-center">
                    Customer Name <SortIcon field="customerName" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('bookingDate')}>
                  <span className="flex items-center">
                    Booking Date <SortIcon field="bookingDate" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('address')}>
                  <span className="flex items-center">
                    Address <SortIcon field="address" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('bookingMethod')}>
                  <span className="flex items-center">
                    Booking Method <SortIcon field="bookingMethod" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('lastUpdated')}>
                  <span className="flex items-center">
                    Last Updated <SortIcon field="lastUpdated" />
                  </span>
                </th>
              </tr>
            </thead>
            <tbody>
              {bookings.map((booking) => (
                <tr key={booking.id}>
                  <td className="font-medium">{booking.customerName}</td>
                  <td className="whitespace-nowrap">{booking.bookingDate}</td>
                  <td>{booking.address}</td>
                  <td>{booking.bookingMethod}</td>
                  <td className="whitespace-nowrap">{booking.lastUpdated}</td>
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
          <span className="text-sm text-gray-600">{startIndex} - {endIndex} to {totalBookings.toLocaleString()}</span>
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
              disabled={endIndex >= totalBookings}
            >
              <ChevronRight size={18} />
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
