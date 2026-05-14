import { useState, useEffect } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { PortalMenu } from '../../components/ui/PortalMenu'
import { TablePagination } from '../../components/ui/TablePagination'
import { recurringBookingsApi } from '../../api/services'
import { useAuthStore } from '../../store/authStore'
import { useToastStore } from '../../store/toastStore'
import { format } from 'date-fns'
import { Search, Plus, Check, X, MoreVertical, Eye, Edit3, Trash2, RefreshCcw } from 'lucide-react'
import type { Booking } from '../../types'
import { RecurringBookingDetailModal } from '../../components/modals/RecurringBookingDetailModal'

export function RecurringBookingsPage() {
  const [searchTerm, setSearchTerm] = useState('')
  const [hideExpired, setHideExpired] = useState(true)
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(25)
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)
  const [recurringModalOpen, setRecurringModalOpen] = useState(false)
  const [selectedRecurringBooking, setSelectedRecurringBooking] = useState<Booking | null>(null)
  const { user } = useAuthStore()
  const { addToast } = useToastStore()
  const queryClient = useQueryClient()

  // Debounce search
  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchTerm), 400)
    return () => clearTimeout(t)
  }, [searchTerm])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, hideExpired])

  const { data: listResult, isLoading } = useQuery({
    queryKey: ['booking-recurrings', user?.companyId, 'active', debouncedSearch, hideExpired, page, perPage],
    queryFn: () =>
      recurringBookingsApi.getPaginated({
        page,
        per_page: perPage,
        status: 'active',
        companyId: user?.companyId,
        ...(debouncedSearch.trim() ? { search: debouncedSearch.trim() } : {}),
        hide_expired: hideExpired,
      }),
  })

  const recurringBookings = listResult?.data ?? []
  const listMeta = listResult?.meta

  // Cancel recurring booking mutation
  const cancelRecurringBookingMutation = useMutation({
    mutationFn: (id: string) => recurringBookingsApi.cancel(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['booking-recurrings'] })
      queryClient.invalidateQueries({ queryKey: ['bookings'] })
      setOpenMenuId(null)
      setMenuPos(null)
      addToast('Recurring booking cancelled successfully', 'success')
    },
    onError: () => {
      addToast('Could not cancel recurring booking', 'error')
    },
  })

  // Toggle auto extend mutation
  const toggleAutoExtendMutation = useMutation({
    mutationFn: ({ id, autoExtend }: { id: string; autoExtend: boolean }) =>
      recurringBookingsApi.update(id, {
        recurring: { auto_extend: autoExtend }
      }),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['booking-recurrings'] })
      setOpenMenuId(null)
      setMenuPos(null)
      addToast(`Auto extend ${variables.autoExtend ? 'enabled' : 'disabled'} successfully`, 'success')
    },
    onError: () => {
      addToast('Could not update auto extend status', 'error')
    },
  })

  const handleCancel = (id: string) => {
    if (confirm('Are you sure you want to cancel this recurring booking?')) {
      cancelRecurringBookingMutation.mutate(id)
    }
  }

  const handleToggleAutoExtend = (id: string, currentStatus: boolean) => {
    toggleAutoExtendMutation.mutate({ id, autoExtend: !currentStatus })
  }

  return (
    <div className="space-y-5 px-1 py-1">
      {/* Top Header Card */}
      <Card className="px-6 py-4 shadow-sm border-gray-200">
        <h1 className="text-xl font-bold text-gray-800">Recurring Bookings</h1>
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
            <label className="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
              <input 
                type="checkbox" 
                checked={hideExpired} 
                onChange={(e) => setHideExpired(e.target.checked)}
                className="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
              />
              Hide recurring with repeat-until before today
            </label>
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
                  <td colSpan={11} className="px-5 py-8 text-center text-gray-500 italic">Loading recurring bookings...</td>
                </tr>
              ) : recurringBookings?.length === 0 ? (
                <tr>
                  <td colSpan={11} className="px-5 py-8 text-center text-gray-500 italic">No recurring bookings found.</td>
                </tr>
              ) : (
                recurringBookings?.map((row: Booking) => (
                  <tr key={row.id} className="hover:bg-gray-50 transition-colors">
                    <td className="relative px-5 py-4 text-sm font-bold text-gray-700 align-top">
                      <div className="absolute left-0 top-0 bottom-0 w-[14px] bg-blue-400 opacity-50"></div>
                      <div className="pl-2">{row.customer?.first_name} {row.customer?.last_name}</div>
                    </td>
                    
                    <td className="px-5 py-4 align-top text-center text-sm text-gray-600">
                      {row.details?.length || 0}
                    </td>
                    
                    <td className="px-5 py-4 align-top text-center text-sm text-gray-600">
                      {row.details?.length || 0}
                    </td>
                    
                    <td className="px-5 py-4 text-sm text-gray-700 align-top text-center">
                      Every {(row as any).recurringFrequency || 1} week{(row as any).recurringFrequency > 1 ? 's' : ''}
                    </td>
                    
                    <td className="px-5 py-4 text-sm text-gray-700 align-top text-center">
                      {row.startDate ? format(new Date(row.startDate), 'EEEE') : '-'}
                    </td>
                    
                    <td className="px-5 py-4 text-sm text-gray-700 align-top text-center">
                      {(row as any).repeatTime || '-'}
                    </td>
                    
                    <td className="px-5 py-4 text-sm text-gray-700 align-top text-center">
                      {(row as any).repeatUntil
                        ? format(new Date((row as any).repeatUntil), 'dd MMM yyyy')
                        : '-'}
                    </td>
                    
                    <td className="px-5 py-4 text-sm font-bold text-gray-900 align-top text-right">${row.total || '0.00'}</td>

                    <td className="px-5 py-4 align-top text-center">
                      <span
                          className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                            row.status === 'completed'
                              ? 'bg-green-100 text-green-700'
                              : row.status === 'cancelled'
                              ? 'bg-red-100 text-red-700'
                              : 'bg-gray-100 text-gray-700'
                          }`}
                        >
                        {row.status || 'pending'}
                      </span>
                    </td>

                    <td className="px-5 py-4 align-top text-center">
                      {(row as any).autoExtend ? (
                        <Check className="w-5 h-5 text-green-600 mx-auto" />
                      ) : (
                        <X className="w-5 h-5 text-red-600 mx-auto" />
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
                            setMenuPos({ top: rect.bottom + 4, left: rect.right - 224 })
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
                        width={224}
                      >
                          <button
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
                            onClick={() => {
                              setOpenMenuId(null)
                              // TODO: Navigate to edit
                            }}
                            className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                          >
                            <Edit3 className="w-4 h-4 text-gray-400" />
                            Edit
                          </button>
                          
                          <button
                            onClick={() => handleToggleAutoExtend(row.id, (row as any).autoExtend)}
                            disabled={toggleAutoExtendMutation.isPending}
                            className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                          >
                            <RefreshCcw className={`w-4 h-4 text-gray-400 ${toggleAutoExtendMutation.isPending ? 'animate-spin' : ''}`} />
                            {(row as any).autoExtend ? 'Disable' : 'Enable'} Auto Extend
                          </button>

                          {row.status === 'active' && (
                            <button
                              className="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors border-t border-gray-100 mt-1"
                              onClick={() => handleCancel(row.id)}
                              disabled={cancelRecurringBookingMutation.isPending}
                            >
                              <Trash2 className="w-4 h-4 text-red-400" />
                              Cancel
                            </button>
                          )}
                      </PortalMenu>
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
        allBookings={recurringBookings || []}
      />
    </div>
  )
}
