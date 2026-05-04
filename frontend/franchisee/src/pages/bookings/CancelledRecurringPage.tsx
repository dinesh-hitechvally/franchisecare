import { useState, useRef, useEffect } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { TablePagination } from '../../components/ui/TablePagination'
import { recurringBookingsApi } from '../../api/services'
import { useAuthStore } from '../../store/authStore'
import { useToastStore } from '../../store/toastStore'
import { format } from 'date-fns'
import { Search, Plus, Check, X, MoreVertical, Eye, Trash2 } from 'lucide-react'
import type { Booking } from '../../types'
import { RecurringBookingDetailModal } from '../../components/modals/RecurringBookingDetailModal'

export function CancelledRecurringPage() {
  const [searchTerm, setSearchTerm] = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(25)
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [recurringModalOpen, setRecurringModalOpen] = useState(false)
  const [selectedRecurringBooking, setSelectedRecurringBooking] = useState<Booking | null>(null)
  const menuRef = useRef<HTMLDivElement>(null)
  const { user } = useAuthStore()
  const { addToast } = useToastStore()
  const queryClient = useQueryClient()

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchTerm), 400)
    return () => clearTimeout(t)
  }, [searchTerm])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch])

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
        setOpenMenuId(null)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const deleteRecurringMutation = useMutation({
    mutationFn: (id: string) => recurringBookingsApi.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['booking-recurrings'] })
      queryClient.invalidateQueries({ queryKey: ['bookings'] })
      setOpenMenuId(null)
      addToast('Recurring booking removed', 'success')
    },
    onError: () => {
      addToast('Could not remove recurring booking', 'error')
    },
  })

  const handleDelete = (id: string) => {
    if (confirm('Remove this cancelled recurring booking from the list?')) {
      deleteRecurringMutation.mutate(id)
    }
  }

  const { data: listResult, isLoading } = useQuery({
    queryKey: ['booking-recurrings', user?.companyId, 'cancelled', debouncedSearch, page, perPage],
    queryFn: () =>
      recurringBookingsApi.getPaginated({
        page,
        per_page: perPage,
        status: 'cancelled',
        companyId: user?.companyId,
        ...(debouncedSearch.trim() ? { search: debouncedSearch.trim() } : {}),
      }),
  })

  const cancelledRecurring = listResult?.data ?? []
  const listMeta = listResult?.meta

  return (
    <div className="space-y-5 px-1 py-1">
      {/* Top Header Card */}
      <Card className="px-6 py-4 shadow-sm border-gray-200">
        <h1 className="text-xl font-bold text-gray-800">Cancelled Recurring</h1>
      </Card>

      {/* Search and Action Bar */}
      <Card className="shadow-sm border-gray-200">
        <div className="flex items-center justify-between px-2 py-1">
          <div className="relative w-full max-w-sm">
            <Search className="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search recurring bookings"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
          </div>
          <div className="flex items-center gap-6">
            <button className="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 transition-colors">
              <Plus className="w-4 h-4 text-gray-400" />
              <span>New Booking</span>
            </button>
          </div>
        </div>
      </Card>

      {/* Data Table Card */}
      <Card className="shadow-sm border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse border-b border-gray-200">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="px-5 py-4 text-sm font-semibold text-gray-800">Name</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-center">Pets</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-center">Services</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-center">Repeat Every</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-center">Repeat ON</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-center">Time</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-center">Repeat Until</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-right">Total</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-center">Status</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-center">Auto Extend</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-right">Mgmt</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {isLoading ? (
                <tr>
                  <td colSpan={11} className="px-5 py-8 text-center text-gray-500 italic">Loading canceled recurring bookings...</td>
                </tr>
              ) : cancelledRecurring?.length === 0 ? (
                <tr>
                  <td colSpan={11} className="px-5 py-8 text-center text-gray-500 italic">No canceled recurring bookings found.</td>
                </tr>
              ) : (
                cancelledRecurring?.map((row: Booking) => (
                  <tr key={row.id} className="hover:bg-gray-50 transition-colors">
                    <td className="relative px-5 py-4 text-sm font-bold text-gray-700 align-top">
                      <div className="absolute left-0 top-0 bottom-0 w-[14px] bg-red-400 opacity-50"></div>
                      <div className="pl-2">{row.customer?.first_name} {row.customer?.last_name}</div>
                    </td>
                    
                    <td className="px-5 py-4 text-sm text-gray-700 align-top text-center">
                      {row.details?.length || 0}
                    </td>
                    
                    <td className="px-5 py-4 text-sm text-gray-700 align-top text-center">
                      {row.details?.length || 0}
                    </td>

                    <td className="px-5 py-4 text-sm text-gray-700 text-center align-top">
                      Every {(row as any).recurringFrequency || (row as any).frequency || '-'} week
                    </td>
                    
                    <td className="px-5 py-4 align-top text-center text-sm text-gray-700">
                      {(row as any).repeatDay || '-'}
                    </td>
                    
                    <td className="px-5 py-4 align-top text-center text-sm text-gray-700">
                      {(row as any).repeatTime || '-'}
                    </td>
                    
                    <td className="px-5 py-4 align-top text-center text-sm text-gray-700">
                      {(row as any).repeatUntil ? format(new Date((row as any).repeatUntil), 'dd MMM yyyy') : '-'}
                    </td>
                    
                    <td className="px-5 py-4 align-top text-right text-sm font-medium text-gray-800">
                      ${row.total || '0.00'}
                    </td>
                    
                    <td className="px-5 py-4 align-top text-center">
                      <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                        row.status === 'active' ? 'bg-green-100 text-green-700' :
                        row.status === 'cancelled' ? 'bg-red-100 text-red-700' :
                        'bg-yellow-100 text-yellow-700'
                      }`}>
                        {row.status || '-'}
                      </span>
                    </td>
                    
                    <td className="px-5 py-4 align-top text-center">
                      {(row as any).autoExtend ? (
                        <Check className="w-4 h-4 mx-auto text-green-600" />
                      ) : (
                        <X className="w-4 h-4 mx-auto text-red-500" />
                      )}
                    </td>
                    
                    <td className="px-5 py-4 text-sm align-top text-right relative">
                      <button
                        type="button"
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
                          className="absolute right-4 mt-1 w-48 bg-white border border-gray-200 rounded-md shadow-lg z-[100] py-1"
                        >
                          <button
                            type="button"
                            onClick={() => {
                              setSelectedRecurringBooking(row)
                              setRecurringModalOpen(true)
                              setOpenMenuId(null)
                            }}
                            className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                          >
                            <Eye className="w-4 h-4 text-gray-400" />
                            View
                          </button>
                          <button
                            type="button"
                            className="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors border-t border-gray-100 mt-1"
                            onClick={() => {
                              setOpenMenuId(null)
                              handleDelete(row.id)
                            }}
                            disabled={deleteRecurringMutation.isPending}
                          >
                            <Trash2 className="w-4 h-4 text-red-400" />
                            Delete
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

        {listMeta && (
          <TablePagination
            meta={listMeta}
            onPageChange={setPage}
            onPerPageChange={setPerPage}
            isLoading={isLoading}
          />
        )}
      </Card>

      <RecurringBookingDetailModal
        isOpen={recurringModalOpen}
        onClose={() => setRecurringModalOpen(false)}
        recurringBooking={selectedRecurringBooking}
        allBookings={cancelledRecurring || []}
      />
    </div>
  )
}
