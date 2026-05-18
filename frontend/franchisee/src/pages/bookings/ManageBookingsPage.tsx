import { useEffect, useMemo, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { format } from 'date-fns'
import { Link } from 'react-router-dom'
import { Card } from '../../components/ui/Card'
import { PageHeader } from '../../components/layout/PageHeader'
import { PortalMenu } from '../../components/ui/PortalMenu'
import { bookingsApi } from '../../api/services'
import { BookingDetailModal } from '../../components/modals/BookingDetailModal'
import type { Booking } from '../../types'
import { TablePagination } from '../../components/ui/TablePagination'
import { Check, MoreVertical, ThumbsUp, X, Eye, Edit3, Mail, FileText, Trash2, CheckCircle, Settings } from 'lucide-react'
import { useToastStore } from '../../store/toastStore'
import { formatDisplayDate, formatDisplayTime } from '../../lib/timeFormatUtils'

function getCustomerName(booking: Booking) {
  return `${booking.customer?.first_name || ''} ${booking.customer?.last_name || ''}`.trim() || 'Unknown'
}

function getPetCount(booking: Booking) {
  const pets = booking.details
    ?.map((detail) => detail.pet?.id)
    .filter((id): id is string => Boolean(id))

  if (!pets || pets.length === 0) {
    return booking.petIds?.length || 0
  }

  return new Set(pets).size
}

function getServiceCount(booking: Booking) {
  const services = booking.details
    ?.map((detail) => detail.service?.id)
    .filter((id): id is string => Boolean(id))

  if (!services || services.length === 0) {
    return booking.serviceIds?.length || 0
  }

  return new Set(services).size
}

export function ManageBookingsPage() {
  const queryClient = useQueryClient()
  const { addToast } = useToastStore()
  const [selectedIds, setSelectedIds] = useState<string[]>([])
  const [viewBooking, setViewBooking] = useState<Booking | null>(null)
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(25)
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)

  const { data: bookingsData, isLoading } = useQuery({
    queryKey: ['bookings', 'manage', 'past-active'],
    queryFn: () =>
      bookingsApi.getAll({
        status: 'active',
        dateTo: format(new Date(), 'yyyy-MM-dd'),
      }),
  })

  const rows = useMemo(() => {
    const source = bookingsData ?? []

    return source
      .filter((booking) => booking.status === 'active')
      .sort((a, b) => {
        const aKey = `${a.startDate} ${a.startTime}`
        const bKey = `${b.startDate} ${b.startTime}`
        return aKey.localeCompare(bKey)
      })
  }, [bookingsData])

  const lastPage = Math.max(1, Math.ceil(rows.length / perPage))

  useEffect(() => {
    if (page > lastPage) {
      setPage(lastPage)
    }
  }, [page, lastPage])

  const pagedRows = useMemo(() => {
    const start = (page - 1) * perPage
    return rows.slice(start, start + perPage)
  }, [rows, page, perPage])

  const paginationMeta = {
    current_page: page,
    last_page: lastPage,
    per_page: perPage,
    total: rows.length,
  }

  const markCompletedMutation = useMutation({
    mutationFn: async (bookingIds: string[]) => {
      await Promise.all(bookingIds.map((id) => bookingsApi.updateStatus(id, 'completed')))
    },
    onSuccess: () => {
      setSelectedIds([])
      queryClient.invalidateQueries({ queryKey: ['bookings'] })
    },
  })

  const sendInvoiceMutation = useMutation({
    mutationFn: (bookingId: string) => bookingsApi.sendInvoice(bookingId),
    onSuccess: (response) => {
      addToast(response.message || 'Invoice sent successfully', 'success')
      setOpenMenuId(null)
    },
    onError: (error: any) => {
      addToast(error?.response?.data?.message || 'Failed to send invoice', 'error')
    },
  })

  const cancelBookingMutation = useMutation({
    mutationFn: (bookingId: string) => bookingsApi.updateStatus(bookingId, 'cancelled'),
    onSuccess: () => {
      addToast('Booking cancelled successfully', 'success')
      queryClient.invalidateQueries({ queryKey: ['bookings'] })
      setOpenMenuId(null)
    },
    onError: (error: any) => {
      addToast(error?.response?.data?.message || 'Failed to cancel booking', 'error')
    },
  })

  const toggleSelect = (id: string) => {
    setSelectedIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]))
  }

  const markFirst25 = () => {
    const firstIds = rows.slice(0, 25).map((booking) => booking.id)
    if (firstIds.length > 0) {
      markCompletedMutation.mutate(firstIds)
    }
  }

  const markSelected = () => {
    if (selectedIds.length > 0) {
      markCompletedMutation.mutate(selectedIds)
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Mark Bookings Completed"
        description="List of bookings scheduled in the past. Mark them complete individually or in bulk."
        icon={<Settings className="w-5 h-5" />}
      />

      <Card className="shadow-sm border-gray-200">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between px-2 py-2">
          <div className="text-sm text-gray-600 leading-6">
            <p>List of booking that were scheduled in the past. You can individually mark them complete or Mark all completed on a single click.</p>
            <p className="font-semibold text-gray-700">Don't forget to remove/cancel last minute cancellations. Please ensure all edits are made before marking complete.</p>
          </div>

          <div className="w-full lg:w-auto flex flex-col gap-3 lg:min-w-[240px]">
            <button
              type="button"
              onClick={markFirst25}
              disabled={markCompletedMutation.isPending || rows.length === 0}
              className="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-200 rounded bg-white text-gray-700 text-sm hover:bg-gray-50 disabled:opacity-60 disabled:cursor-not-allowed"
            >
              <ThumbsUp className="w-4 h-4" />
              Mark First 25 Completed
            </button>

            <button
              type="button"
              onClick={markSelected}
              disabled={markCompletedMutation.isPending || selectedIds.length === 0}
              className="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-200 rounded bg-gray-100 text-gray-500 text-sm disabled:cursor-not-allowed"
            >
              <ThumbsUp className="w-4 h-4" />
              Mark Selected As Completed
            </button>
          </div>
        </div>
      </Card>

      <Card className="shadow-sm border-gray-200 overflow-visible">
        <div className="overflow-visible">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="w-12 px-5 py-4" />
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

            <tbody className="divide-y divide-gray-100 overflow-visible">
              {isLoading ? (
                <tr>
                  <td colSpan={10} className="px-4 py-8 text-center text-gray-500 italic">Loading bookings...</td>
                </tr>
              ) : rows.length === 0 ? (
                <tr>
                  <td colSpan={10} className="px-4 py-8 text-center text-gray-500 italic">No pending past bookings found.</td>
                </tr>
              ) : (
                pagedRows.map((row) => (
                  <tr key={row.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-5 py-4 align-top">
                      <input
                        type="checkbox"
                        checked={selectedIds.includes(row.id)}
                        onChange={() => toggleSelect(row.id)}
                        className="w-4 h-4 border-gray-300 rounded"
                      />
                    </td>
                    <td className="px-5 py-4 text-sm font-bold text-gray-700 align-top">{getCustomerName(row)}</td>
                    <td className="px-5 py-4 text-sm text-gray-700 text-center align-top">{getPetCount(row)}</td>
                    <td className="px-5 py-4 text-sm text-gray-700 text-center align-top">{getServiceCount(row)}</td>
                    <td className="px-5 py-4 text-sm text-gray-700 align-top">
                      {row.startDate ? formatDisplayDate(row.startDate) : '-'}
                    </td>
                    <td className="px-5 py-4 text-sm text-gray-700 align-top text-center">{formatDisplayTime(row.startTime)}</td>
                    <td className="px-5 py-4 text-sm font-bold text-gray-800 align-top text-right">${Number(row.total || 0).toFixed(2)}</td>
                    <td className="px-5 py-4 text-sm text-gray-700 align-top">{row.notes || '-'}</td>
                    <td className="px-5 py-4 align-top text-center">
                      {row.isRecurring ? (
                        <Check className="w-5 h-5 text-emerald-500 mx-auto" />
                      ) : (
                        <X className="w-5 h-5 text-red-500 mx-auto" />
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
                          <button
                            onClick={() => sendInvoiceMutation.mutate(row.id)}
                            className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                          >
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
                          <button
                            onClick={() => {
                              markCompletedMutation.mutate([row.id])
                              setOpenMenuId(null)
                            }}
                            className="w-full flex items-center gap-3 px-4 py-2 text-sm text-emerald-600 hover:bg-emerald-50 transition-colors border-t border-gray-100 mt-1"
                          >
                            <CheckCircle className="w-4 h-4 text-emerald-500" />
                            Mark Complete
                          </button>
                          <button
                            onClick={() => cancelBookingMutation.mutate(row.id)}
                            className="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors border-t border-gray-100 mt-1"
                          >
                            <Trash2 className="w-4 h-4 text-red-400" />
                            Cancel
                          </button>
                      </PortalMenu>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        <TablePagination
          meta={paginationMeta}
          onPageChange={setPage}
          onPerPageChange={setPerPage}
          isLoading={isLoading}
        />
      </Card>

      <BookingDetailModal
        isOpen={!!viewBooking}
        onClose={() => setViewBooking(null)}
        booking={viewBooking}
      />
    </div>
  )
}
