import { useState } from 'react'
import { Filter, ChevronUp, ChevronDown, ChevronLeft, ChevronRight } from 'lucide-react'

interface Booking {
  id: number
  customerName: string
  bookingDate: string
  address: string
  bookingMethod: string
  cancelledDate: string
  cancellationReason: string
}

const mockBookings: Booking[] = [
  { id: 1, customerName: 'Alex Thompson', bookingDate: 'January 22 2026, 09:00 am', address: '111 First Street, Sydney', bookingMethod: 'Mate V2 Web', cancelledDate: 'January 21 2026, 05:30 pm', cancellationReason: 'Customer request' },
  { id: 2, customerName: 'Maria Garcia', bookingDate: 'January 21 2026, 11:00 am', address: '222 Second Avenue, Melbourne', bookingMethod: 'Mate V2 App', cancelledDate: 'January 20 2026, 08:00 pm', cancellationReason: 'Schedule conflict' },
  { id: 3, customerName: 'Chris Lee', bookingDate: 'January 20 2026, 02:00 pm', address: '333 Third Road, Brisbane', bookingMethod: 'Mate V2 Web', cancelledDate: 'January 19 2026, 10:15 am', cancellationReason: 'Weather conditions' },
  { id: 4, customerName: 'Amanda White', bookingDate: 'January 19 2026, 10:00 am', address: '444 Fourth Lane, Perth', bookingMethod: 'Phone', cancelledDate: 'January 18 2026, 03:45 pm', cancellationReason: 'Personal emergency' },
  { id: 5, customerName: 'Kevin Brown', bookingDate: 'January 18 2026, 03:00 pm', address: '555 Fifth Court, Adelaide', bookingMethod: 'Mate V2 Web', cancelledDate: 'January 17 2026, 09:30 am', cancellationReason: 'Pet illness' },
  { id: 6, customerName: 'Linda Davis', bookingDate: 'January 17 2026, 08:00 am', address: '666 Sixth Drive, Hobart', bookingMethod: 'Mate V2 Web', cancelledDate: 'January 16 2026, 06:00 pm', cancellationReason: 'Customer request' },
  { id: 7, customerName: 'Steven Miller', bookingDate: 'January 16 2026, 01:00 pm', address: '777 Seventh Way, Darwin', bookingMethod: 'Mate V2 App', cancelledDate: 'January 15 2026, 11:20 am', cancellationReason: 'No longer needed' },
  { id: 8, customerName: 'Nancy Wilson', bookingDate: 'January 15 2026, 11:00 am', address: '888 Eighth Place, Canberra', bookingMethod: 'Mate V2 Web', cancelledDate: 'January 14 2026, 04:00 pm', cancellationReason: 'Moving house' },
  { id: 9, customerName: 'Daniel Moore', bookingDate: 'January 14 2026, 04:00 pm', address: '999 Ninth Boulevard, Gold Coast', bookingMethod: 'Mate V2 Web', cancelledDate: 'January 13 2026, 02:15 pm', cancellationReason: 'Schedule conflict' },
  { id: 10, customerName: 'Patricia Taylor', bookingDate: 'January 13 2026, 09:30 am', address: '1010 Tenth Street, Newcastle', bookingMethod: 'Phone', cancelledDate: 'January 12 2026, 07:30 am', cancellationReason: 'Financial reasons' },
]

type SortField = 'customerName' | 'bookingDate' | 'address' | 'bookingMethod' | 'cancelledDate' | 'cancellationReason'
type SortOrder = 'asc' | 'desc'

interface FilterState {
  search: string
  dateFrom: string
  dateTo: string
  bookingMethod: string
  cancellationReason: string
}

const initialFilters: FilterState = {
  search: '',
  dateFrom: '',
  dateTo: '',
  bookingMethod: '',
  cancellationReason: ''
}

export function CancelledBookings() {
  const [bookings] = useState<Booking[]>(mockBookings)
  const [sortField, setSortField] = useState<SortField>('cancelledDate')
  const [sortOrder, setSortOrder] = useState<SortOrder>('desc')
  const [showFilter, setShowFilter] = useState(false)
  const [filters, setFilters] = useState<FilterState>(initialFilters)
  const [appliedFilters, setAppliedFilters] = useState<FilterState>(initialFilters)
  const [rowsPerPage, setRowsPerPage] = useState(100)
  const [currentPage, setCurrentPage] = useState(1)

  const totalBookings = 45678

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
          <h2 className="card-title">List Cancelled Bookings</h2>
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
            <div className="grid grid-cols-5 gap-4">
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
              <div>
                <label className="block text-sm text-gray-600 mb-1">Cancellation Reason</label>
                <select 
                  value={filters.cancellationReason}
                  onChange={(e) => handleFilterChange('cancellationReason', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="Customer request">Customer request</option>
                  <option value="Schedule conflict">Schedule conflict</option>
                  <option value="Weather conditions">Weather conditions</option>
                  <option value="Personal emergency">Personal emergency</option>
                  <option value="Pet illness">Pet illness</option>
                  <option value="No longer needed">No longer needed</option>
                  <option value="Moving house">Moving house</option>
                  <option value="Financial reasons">Financial reasons</option>
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
            {appliedFilters.cancellationReason && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                Reason: {appliedFilters.cancellationReason}
                <button 
                  onClick={() => removeFilter('cancellationReason')}
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
                <th className="cursor-pointer" onClick={() => handleSort('cancelledDate')}>
                  <span className="flex items-center">
                    Cancelled Date <SortIcon field="cancelledDate" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('cancellationReason')}>
                  <span className="flex items-center">
                    Reason <SortIcon field="cancellationReason" />
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
                  <td className="whitespace-nowrap">{booking.cancelledDate}</td>
                  <td>{booking.cancellationReason}</td>
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
