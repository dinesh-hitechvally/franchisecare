import { useState, useEffect, useRef } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { TablePagination } from '../../components/ui/TablePagination'
import { bookingsApi } from '../../api/services'
import { format } from 'date-fns'
import { Search, Plus, MoreVertical, Eye, Edit3, Trash2, Mail, FileText, Check, X } from 'lucide-react'
import type { Booking } from '../../types'
import { Link } from 'react-router-dom'
import { BookingDetailModal } from '../../components/modals/BookingDetailModal'

export function ActiveBookingsPage() {
  const [searchTerm, setSearchTerm] = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(25)
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [viewBooking, setViewBooking] = useState<Booking | null>(null)
  const menuRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchTerm), 400)
    return () => clearTimeout(t)
  }, [searchTerm])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch])

  const { data: listResult, isLoading } = useQuery({
    queryKey: ['bookings', 'active', debouncedSearch, page, perPage],
    queryFn: () =>
      bookingsApi.getPaginated({
        page,
        per_page: perPage,
        status: 'active',
        ...(debouncedSearch.trim() ? { search: debouncedSearch.trim() } : {}),
      }),
  })

  const bookings = listResult?.data ?? []
  const meta = listResult?.meta

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
        setOpenMenuId(null)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  return (
    <div className="space-y-5 px-1 py-1">
      <Card className="px-6 py-4 shadow-sm border-gray-200">
        <h1 className="text-xl font-bold text-gray-800">Active Bookings</h1>
      </Card>

      <Card className="shadow-sm border-gray-200">
        <div className="flex items-center justify-between px-2 py-1">
          <div className="relative w-full max-w-sm">
            <Search className="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search active bookings"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
          </div>
          <Link to="/bookings/new">
            <button className="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 transition-colors">
              <Plus className="w-4 h-4 text-gray-400" />
              <span>New Booking</span>
            </button>
          </Link>
        </div>
      </Card>

      <Card className="shadow-sm border-gray-200 overflow-visible">
        <div className="overflow-x-auto overflow-y-visible">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="px-5 py-4 text-sm font-semibold text-gray-800">Name</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-center">Pets</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-center">Services</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 w-40">Booked Date</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-center">Time</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-right">Total $</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 w-64">Notes</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-center">Recurring</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-right">Mgmt</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {isLoading ? (
                <tr>
                  <td colSpan={9} className="px-5 py-8 text-center text-gray-500 italic">Loading active bookings...</td>
                </tr>
              ) : bookings.length === 0 ? (
                <tr>
                  <td colSpan={9} className="px-5 py-8 text-center text-gray-500 italic">No active bookings found.</td>
                </tr>
              ) : (
                bookings.map((row: Booking) => (
                  <tr key={row.id} className="hover:bg-gray-50 relative group">
                    <td className="relative px-5 py-4 text-sm font-bold text-gray-700 align-top">
                      <div className="absolute left-0 top-0 bottom-0 w-1 bg-green-400"></div>
                      {row.customer?.first_name} {row.customer?.last_name}
                    </td>

                    <td className="px-5 py-4 align-top text-center text-sm text-gray-600">
                      {row.details?.length || 0}
                    </td>

                    <td className="px-5 py-4 align-top text-center text-sm text-gray-600">
                      {row.details?.length || 0}
                    </td>

                    <td className="px-5 py-4 text-sm text-gray-700 whitespace-normal min-w-[120px] align-top leading-snug">
                      {format(new Date(row.startDate), 'EEEE, do MMM yyyy')}
                    </td>

                    <td className="px-5 py-4 text-sm text-gray-700 align-top text-center">{row.startTime}</td>

                    <td className="px-5 py-4 text-sm font-bold text-gray-900 align-top text-right">${row.total}</td>

                    <td className="px-5 py-4 text-sm text-gray-700 leading-snug align-top pr-8">{row.notes}</td>

                    <td className="px-5 py-4 align-top text-center flex justify-center">
                      {row.isRecurring ? (
                        <Check className="w-5 h-5 text-green-600" />
                      ) : (
                        <X className="w-5 h-5 text-red-600" />
                      )}
                    </td>

                    <td className="px-5 py-4 text-sm align-top text-right relative">
                      <button
                        onClick={(e) => {
                          e.stopPropagation()
                          setOpenMenuId(openMenuId === row.id ? null : row.id)
                        }}
                        className="text-gray-400 hover:text-gray-600 p-1.5 focus:outline-none rounded-full hover:bg-gray-100 transition-colors"
                      >
                        <MoreVertical className="w-5 h-5" />
                      </button>

                      {openMenuId === row.id && (
                        <div
                          ref={menuRef}
                          className="absolute right-4 mt-1 w-48 bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1"
                        >
                          <button
                            onClick={() => {
                              setViewBooking(row)
                              setOpenMenuId(null)
                            }}
                            className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                          >
                            <Eye className="w-4 h-4 text-gray-400" />
                            View
                          </button>
                          <Link
                            to={`/bookings/edit/${row.id}`}
                            className="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                          >
                            <Edit3 className="w-4 h-4 text-gray-400" />
                            Edit
                          </Link>
                          <button className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                            <Mail className="w-4 h-4 text-gray-400" />
                            Email Invoice
                          </button>
                          <button
                            onClick={() => bookingsApi.generateInvoice(row.id)}
                            className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                          >
                            <FileText className="w-4 h-4 text-gray-400" />
                            View Invoice
                          </button>
                          <button className="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors border-t border-gray-100 mt-1">
                            <Trash2 className="w-4 h-4 text-red-400" />
                            Cancel
                          </button>
                        </div>
                      )}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {meta && (
          <TablePagination
            meta={meta}
            onPageChange={setPage}
            onPerPageChange={setPerPage}
            isLoading={isLoading}
          />
        )}
      </Card>

      <BookingDetailModal isOpen={!!viewBooking} onClose={() => setViewBooking(null)} booking={viewBooking} />
    </div>
  )
}
