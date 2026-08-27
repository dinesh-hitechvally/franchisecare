import { useEffect, useMemo, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { PageHeader } from '../../components/layout/PageHeader'
import { Button } from '../../components/ui/Button'
import { TablePagination } from '../../components/ui/TablePagination'
import { WaitlistDetailModal } from '../../components/modals/WaitlistDetailModal'
import { WaitlistAuditModal } from '../../components/modals/WaitlistAuditModal'
import { waitlistApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import { formatDisplayDate, formatDisplayTime } from '../../lib/timeFormatUtils'
import { Calendar, CalendarDays, Check, Edit3, Eye, History, Mail, MoreVertical, Plus, Trash2 } from 'lucide-react'
import type { Booking } from '../../types'

type WaitlistStatusFilter = 'all' | 'ACTIVE' | 'CANCELLED' | 'COMPLETED' | 'EXPIRED'

const STATUS_OPTIONS: { label: string; value: WaitlistStatusFilter }[] = [
  { label: 'All', value: 'all' },
  { label: 'Active', value: 'ACTIVE' },
  { label: 'Cancelled', value: 'CANCELLED' },
  { label: 'Completed', value: 'COMPLETED' },
  { label: 'Expired', value: 'EXPIRED' },
]

function mapWaitlistFilterToApiStatus(value: WaitlistStatusFilter): string | undefined {
  if (value === 'all') return undefined
  return value
}

function getCustomerName(booking: Booking) {
  return `${booking.customer?.first_name || ''} ${booking.customer?.last_name || ''}`.trim() || 'Unknown customer'
}

function getStatusBadgeClass(status: string) {
  if (status === 'ACTIVE') return 'bg-green-100 text-green-700'
  if (status === 'COMPLETED') return 'bg-blue-100 text-blue-700'
  if (status === 'CANCELLED') return 'bg-red-100 text-red-700'
  if (status === 'EXPIRED') return 'bg-amber-100 text-amber-700'
  return 'bg-gray-100 text-gray-700'
}

function getStatusLabel(status: string) {
  const lower = status.toLowerCase()
  return lower.charAt(0).toUpperCase() + lower.slice(1)
}

function getMenuAccessLevel(status: string): 'all' | 'view-edit' | 'view-only' {
  if (status === 'CANCELLED') return 'view-edit'
  if (status === 'COMPLETED' || status === 'EXPIRED') return 'view-only'
  return 'all'
}

export function WaitlistBookingsPage() {
  const queryClient = useQueryClient()
  const { addToast } = useToastStore()
  const [searchTerm, setSearchTerm] = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState<WaitlistStatusFilter>('all')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(25)
  const [selectedBookingIds, setSelectedBookingIds] = useState<string[]>([])
  const [viewBooking, setViewBooking] = useState<Booking | null>(null)
  const [auditWaitlist, setAuditWaitlist] = useState<Booking | null>(null)
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)
  const menuRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const timeout = setTimeout(() => setDebouncedSearch(searchTerm.trim()), 300)
    return () => clearTimeout(timeout)
  }, [searchTerm])

  useEffect(() => {
    setPage(1)
    setSelectedBookingIds([])
  }, [debouncedSearch, statusFilter])

  const apiStatus = useMemo(() => mapWaitlistFilterToApiStatus(statusFilter), [statusFilter])

  const { data: listResult, isLoading } = useQuery({
    queryKey: ['waitlists', debouncedSearch, statusFilter, page, perPage],
    queryFn: () =>
      waitlistApi.getPaginated({
        page,
        per_page: perPage,
        ...(apiStatus ? { status: apiStatus } : {}),
        ...(debouncedSearch ? { search: debouncedSearch } : {}),
      }),
  })

  const sendEmailsMutation = useMutation({
    mutationFn: async (bookingIds: string[]) => {
      await Promise.all(bookingIds.map((bookingId) => waitlistApi.sendEmailConfirmation(bookingId)))
    },
    onSuccess: (_, bookingIds) => {
      addToast(`Email confirmation sent for ${bookingIds.length} waitlist booking(s).`, 'success')
      setSelectedBookingIds([])
      queryClient.invalidateQueries({ queryKey: ['waitlists'] })
    },
    onError: () => {
      addToast('Failed to send one or more email confirmations.', 'error')
    },
  })

  const cancelBookingMutation = useMutation({
    mutationFn: (bookingId: string) => waitlistApi.updateStatus(bookingId, 'CANCELLED'),
    onSuccess: () => {
      addToast('Waitlist booking cancelled successfully.', 'success')
      queryClient.invalidateQueries({ queryKey: ['waitlists'] })
      setOpenMenuId(null)
      setMenuPos(null)
    },
    onError: () => {
      addToast('Failed to cancel waitlist booking.', 'error')
    },
  })

  const convertToBookingMutation = useMutation({
    mutationFn: (bookingId: string) => waitlistApi.convertToBooking(bookingId),
    onSuccess: () => {
      addToast('Waitlist converted to booking successfully.', 'success')
      queryClient.invalidateQueries({ queryKey: ['waitlists'] })
      queryClient.invalidateQueries({ queryKey: ['bookings'] })
      setViewBooking(null)
      setOpenMenuId(null)
      setMenuPos(null)
    },
    onError: () => {
      addToast('Failed to convert waitlist to booking.', 'error')
    },
  })

  const sendSingleEmailMutation = useMutation({
    mutationFn: (bookingId: string) => waitlistApi.sendEmailConfirmation(bookingId),
    onSuccess: () => {
      addToast('Email confirmation sent successfully.', 'success')
      setOpenMenuId(null)
      setMenuPos(null)
    },
    onError: () => {
      addToast('Failed to send email confirmation.', 'error')
    },
  })

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
        setOpenMenuId(null)
        setMenuPos(null)
      }
    }

    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const bookings = listResult?.data ?? []
  const meta = listResult?.meta

  const allVisibleSelected = bookings.length > 0 && bookings.every((booking) => selectedBookingIds.includes(booking.id))

  function toggleSelectAll() {
    if (allVisibleSelected) {
      setSelectedBookingIds((current) => current.filter((id) => !bookings.some((booking) => booking.id === id)))
      return
    }

    const visibleIds = bookings.map((booking) => booking.id)
    setSelectedBookingIds((current) => Array.from(new Set([...current, ...visibleIds])))
  }

  function toggleSingleSelection(bookingId: string) {
    setSelectedBookingIds((current) =>
      current.includes(bookingId)
        ? current.filter((id) => id !== bookingId)
        : [...current, bookingId]
    )
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Waitlists"
        description="Manage waitlist bookings with booking-style tools"
        icon={<Calendar className="w-5 h-5" />}
      />

      <Card>
        <div className="flex items-center gap-4 mb-6">
          <div className="relative flex-1 max-w-md">
            <CalendarDays className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
            <input
              type="text"
              placeholder="Search waitlist bookings..."
              value={searchTerm}
              onChange={(event) => setSearchTerm(event.target.value)}
              className="input pl-10"
            />
          </div>

          <select
            value={statusFilter}
            onChange={(event) => setStatusFilter(event.target.value as WaitlistStatusFilter)}
            className="input w-44"
          >
            {STATUS_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>{option.label}</option>
            ))}
          </select>

          <Link to="/bookings/new-waitlist">
            <Button variant="secondary" size="sm">
              <Plus className="w-4 h-4" />
              New Waitlist
            </Button>
          </Link>

          <Button
            variant="secondary"
            size="sm"
            disabled={selectedBookingIds.length === 0 || sendEmailsMutation.isPending}
            onClick={() => sendEmailsMutation.mutate(selectedBookingIds)}
          >
              <Mail className="w-4 h-4" />
              SEND EMAIL ({selectedBookingIds.length})
          </Button>
        </div>
      </Card>

      <Card>
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b">
                <th className="px-4 py-3">
                  <input
                    type="checkbox"
                    checked={allVisibleSelected}
                    onChange={toggleSelectAll}
                    className="h-4 w-4 rounded border-gray-300"
                  />
                </th>
                <th className="px-4 py-3 text-left text-sm font-semibold text-secondary-700">Customer Name</th>
                <th className="px-4 py-3 text-left text-sm font-semibold text-secondary-700"># of Pets</th>
                <th className="px-4 py-3 text-left text-sm font-semibold text-secondary-700"># of Services</th>
                <th className="px-4 py-3 text-left text-sm font-semibold text-secondary-700">From Date</th>
                <th className="px-4 py-3 text-left text-sm font-semibold text-secondary-700">To Date</th>
                <th className="px-4 py-3 text-left text-sm font-semibold text-secondary-700">Booked Time</th>
                <th className="px-4 py-3 text-right text-sm font-semibold text-secondary-700">Total $</th>
                <th className="px-4 py-3 text-left text-sm font-semibold text-secondary-700">Notes</th>
                <th className="px-4 py-3 text-left text-sm font-semibold text-secondary-700">Status</th>
                <th className="px-4 py-3 text-left text-sm font-semibold text-secondary-700">Mgmt</th>
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr>
                  <td colSpan={11} className="text-center py-12 text-secondary-500">Loading waitlist bookings...</td>
                </tr>
              ) : bookings.length === 0 ? (
                <tr>
                  <td colSpan={11} className="text-center py-12 text-secondary-500">No waitlist bookings found.</td>
                </tr>
              ) : (
                bookings.map((booking, index) => (
                  
                  <tr key={booking.id} className={index % 2 === 0 ? 'bg-white border-b border-secondary-100 hover:bg-secondary-50 transition-colors' : 'bg-secondary-50 border-b border-secondary-100 hover:bg-secondary-100 transition-colors'}>
                    <td className="px-4 py-3 align-top">
                      <input
                        type="checkbox"
                        checked={selectedBookingIds.includes(booking.id)}
                        onChange={() => toggleSingleSelection(booking.id)}
                        className="mt-1 h-4 w-4 rounded border-gray-300"
                      />
                    </td>
                    <td className="px-4 py-3 text-sm text-secondary-700 align-top">{getCustomerName(booking)}</td>
                    <td className="px-4 py-3 text-sm text-secondary-700 align-top">{booking.details?.length ?? 0}</td>
                    <td className="px-4 py-3 text-sm text-secondary-700 align-top">{booking.details?.length ?? 0}</td>
                    <td className="px-4 py-3 text-sm text-secondary-700 align-top">{formatDisplayDate(booking.startDate)}</td>
                    <td className="px-4 py-3 text-sm text-secondary-700 align-top">{formatDisplayDate(booking.endDate || booking.startDate)}</td>
                    <td className="px-4 py-3 text-sm text-secondary-700 align-top">{formatDisplayTime(booking.startTime)}</td>
                    <td className="px-4 py-3 text-sm font-semibold text-secondary-900 align-top text-right">${Number(booking.total || 0).toFixed(2)}</td>
                    <td className="px-4 py-3 text-sm text-secondary-700 align-top max-w-[260px] truncate" title={booking.notes || ''}>
                      {booking.notes || '-'}
                    </td>
                    <td className="px-4 py-3 align-top">
                      <span className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${getStatusBadgeClass(booking.status)}`}>
                        {getStatusLabel(booking.status)}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-sm align-top whitespace-nowrap text-secondary-700">
                      {(() => {
                        const menuAccess = getMenuAccessLevel(booking.status)

                        return (
                          <>
                            <button
                              onClick={(event) => {
                                event.stopPropagation()
                                if (openMenuId === booking.id) {
                                  setOpenMenuId(null)
                                  setMenuPos(null)
                                  return
                                }

                                const rect = (event.currentTarget as HTMLButtonElement).getBoundingClientRect()
                                setMenuPos({ top: rect.bottom + 4, left: rect.right - 190 })
                                setOpenMenuId(booking.id)
                              }}
                              className="text-secondary-500 hover:text-secondary-700 p-1.5 rounded-full hover:bg-secondary-100 transition-colors"
                            >
                              <MoreVertical className="w-4 h-4" />
                            </button>

                            {openMenuId === booking.id && menuPos && createPortal(
                              <div
                                ref={menuRef}
                                style={{ position: 'fixed', top: menuPos.top, left: menuPos.left, zIndex: 9999 }}
                                className="w-48 bg-white border border-gray-200 rounded-md shadow-lg py-1"
                              >
                                <Link
                                  to="#"
                                  className="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                  onClick={(event) => {
                                    event.preventDefault()
                                    setViewBooking(booking)
                                    setOpenMenuId(null)
                                    setMenuPos(null)
                                  }}
                                >
                                  <Eye className="w-4 h-4 text-gray-400" />
                                  View
                                </Link>

                                {(menuAccess === 'all' || menuAccess === 'view-edit') && (
                                  <Link
                                    to={`/bookings/waitlist/edit/${booking.id}`}
                                    className="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                    onClick={() => {
                                      setOpenMenuId(null)
                                      setMenuPos(null)
                                    }}
                                  >
                                    <Edit3 className="w-4 h-4 text-gray-400" />
                                    Edit
                                  </Link>
                                )}

                                <button
                                  onClick={() => {
                                    setAuditWaitlist(booking)
                                    setOpenMenuId(null)
                                    setMenuPos(null)
                                  }}
                                  className="w-full flex items-center gap-3 px-4 py-2 text-sm text-blue-700 hover:bg-blue-50 transition-colors"
                                >
                                  <History className="w-4 h-4 text-blue-400" />
                                  Audit Trail
                                </button>

                                {menuAccess === 'all' && (
                                  <>
                                    <button
                                      onClick={() => sendSingleEmailMutation.mutate(booking.id)}
                                      className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                    >
                                      <Mail className="w-4 h-4 text-gray-400" />
                                      Send Email
                                    </button>
                                    <button
                                      onClick={() => convertToBookingMutation.mutate(booking.id)}
                                      className="w-full flex items-center gap-3 px-4 py-2 text-sm text-emerald-600 hover:bg-emerald-50 transition-colors border-t border-gray-100 mt-1"
                                    >
                                      <Check className="w-4 h-4 text-emerald-500" />
                                      Convert to booking
                                    </button>
                                    <button
                                      onClick={() => cancelBookingMutation.mutate(booking.id)}
                                      className="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors border-t border-gray-100 mt-1"
                                    >
                                      <Trash2 className="w-4 h-4 text-red-400" />
                                      Cancel
                                    </button>
                                  </>
                                )}
                              </div>,
                              document.body
                            )}
                          </>
                        )
                      })()}
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

      <WaitlistDetailModal
        isOpen={!!viewBooking}
        onClose={() => setViewBooking(null)}
        booking={viewBooking}
        onConvert={(bookingId) => convertToBookingMutation.mutate(bookingId)}
        onCancel={(bookingId) => cancelBookingMutation.mutate(bookingId)}
        isConverting={convertToBookingMutation.isPending}
        isCancelling={cancelBookingMutation.isPending}
      />

      <WaitlistAuditModal
        isOpen={!!auditWaitlist}
        onClose={() => setAuditWaitlist(null)}
        waitlist={auditWaitlist}
      />
    </div>
  )
}
