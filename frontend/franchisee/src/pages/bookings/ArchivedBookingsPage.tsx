import { useState, useEffect } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { PortalMenu } from '../../components/ui/PortalMenu'
import { TablePagination } from '../../components/ui/TablePagination'
import { bookingsApi } from '../../api/services'
import { format } from 'date-fns'
import { Search, Plus, MoreVertical, Eye, Check, X, RotateCcw } from 'lucide-react'
import type { Booking } from '../../types'
import { BookingDetailModal } from '../../components/modals/BookingDetailModal'
import { RebookBookingModal } from '../../components/modals/RebookBookingModal'

export function ArchivedBookingsPage() {
  const [searchTerm, setSearchTerm] = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(25)
  const [viewBooking, setViewBooking] = useState<Booking | null>(null)
  const [rebookBooking, setRebookBooking] = useState<Booking | null>(null)
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchTerm), 400)
    return () => clearTimeout(t)
  }, [searchTerm])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch])

  const { data: listResult, isLoading } = useQuery({
    queryKey: ['bookings', 'archived', debouncedSearch, page, perPage],
    queryFn: () =>
      bookingsApi.getPaginated({
        page,
        per_page: perPage,
        status: 'archived',
        ...(debouncedSearch.trim() ? { search: debouncedSearch.trim() } : {}),
      }),
  })

  const bookings = listResult?.data ?? []
  const meta = listResult?.meta

  return (
    <div className="space-y-5 px-1 py-1">
      <Card className="px-6 py-4 shadow-sm border-gray-200">
        <h1 className="text-xl font-bold text-gray-800">Archived Bookings</h1>
      </Card>

      <Card className="shadow-sm border-gray-200">
        <div className="flex items-center justify-between px-2 py-1">
          <div className="relative w-full max-sm:max-w-xs max-w-sm">
            <Search className="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search archived bookings"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
          </div>
          <button className="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 transition-colors">
            <Plus className="w-4 h-4 text-gray-400" />
            <span>New Booking</span>
          </button>
        </div>
      </Card>

      <Card className="shadow-sm border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse border-b border-gray-200">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="px-5 py-4 text-sm font-semibold text-gray-800">Name</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-center">Pets</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800">Services</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 w-48">Booked Date</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-center">Time</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-right">Total $</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800">Notes</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-center">Recurring</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 w-56 text-right">Mgmt</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {isLoading ? (
                <tr>
                  <td colSpan={9} className="px-5 py-8 text-center text-gray-500 italic">Loading archived bookings...</td>
                </tr>
              ) : bookings.length === 0 ? (
                <tr>
                  <td colSpan={9} className="px-5 py-8 text-center text-gray-500 italic">No archived bookings found.</td>
                </tr>
              ) : (
                bookings.map((row: Booking) => (
                  <tr key={row.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-5 py-4 text-sm font-medium text-gray-700">
                      {row.customer?.first_name} {row.customer?.last_name}
                    </td>
                    <td className="px-5 py-4 text-sm text-center text-gray-600">{row.details?.length || 0}</td>
                    <td className="px-5 py-4 text-sm text-center text-gray-600">{row.details?.length || 0}</td>
                    <td className="px-5 py-4 text-sm text-gray-700">{format(new Date(row.startDate), 'do MMM yyyy')}</td>
                    <td className="px-5 py-4 text-sm text-center text-gray-700">{row.startTime}</td>
                    <td className="px-5 py-4 text-sm text-right font-bold text-gray-900">${row.total}</td>
                    <td className="px-5 py-4 text-sm text-gray-600 truncate max-w-xs">{row.notes}</td>
                    <td className="px-5 py-4 align-top text-center flex justify-center">
                      {row.isRecurring ? (
                        <Check className="w-5 h-5 text-green-600" />
                      ) : (
                        <X className="w-5 h-5 text-red-600" />
                      )}
                    </td>
                    <td className="px-5 py-4 text-sm align-top text-right">
                      <button
                        onClick={(e) => {
                          e.stopPropagation()
                          if (openMenuId === row.id) {
                            setOpenMenuId(null); setMenuPos(null)
                          } else {
                            const rect = (e.currentTarget as HTMLButtonElement).getBoundingClientRect()
                            setMenuPos({ top: rect.bottom + 4, left: rect.right - 192 })
                            setOpenMenuId(row.id)
                          }
                        }}
                        className="text-gray-400 hover:text-gray-600 p-1.5 focus:outline-none rounded-full hover:bg-gray-100 transition-colors"
                      >
                        <MoreVertical className="w-5 h-5" />
                      </button>

                      <PortalMenu
                        isOpen={openMenuId === row.id}
                        onClose={() => { setOpenMenuId(null); setMenuPos(null) }}
                        position={menuPos}
                      >
                        <button
                          onClick={() => {
                            setViewBooking(row)
                            setOpenMenuId(null); setMenuPos(null)
                          }}
                          className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                        >
                          <Eye className="w-4 h-4 text-gray-400" />
                          View
                        </button>
                        <button
                          onClick={() => {
                            setRebookBooking(row)
                            setOpenMenuId(null); setMenuPos(null)
                          }}
                          className="w-full flex items-center gap-3 px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 transition-colors"
                        >
                          <RotateCcw className="w-4 h-4 text-blue-500" />
                          Re Book
                        </button>
                      </PortalMenu>
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

      <RebookBookingModal
        isOpen={!!rebookBooking}
        onClose={() => setRebookBooking(null)}
        booking={rebookBooking}
      />
    </div>
  )
}
