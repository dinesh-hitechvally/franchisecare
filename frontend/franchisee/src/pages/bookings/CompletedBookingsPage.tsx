import { useState, useRef, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { format } from 'date-fns'
import { Card } from '../../components/ui/Card'
import { TablePagination } from '../../components/ui/TablePagination'
import { bookingsApi } from '../../api/services'
import { BookingDetailModal } from '../../components/modals/BookingDetailModal'
import { Search, Plus, MoreVertical, Eye, Edit, XCircle, FileText, Send, Check, X } from 'lucide-react'
import type { Booking } from '../../types'
import { createPortal } from 'react-dom'

const PAGE_SIZE_OPTIONS = [10, 25, 50, 100]

function getCustomerName(booking: Booking) {
  return `${booking.customer?.first_name || ''} ${booking.customer?.last_name || ''}`.trim() || 'Unknown customer'
}

function getServiceNames(booking: Booking) {
  const names = booking.details
    ?.map((detail) => detail.service?.name)
    .filter((name): name is string => Boolean(name))

  return names && names.length > 0 ? names : []
}

// Dropdown Menu Component
function DropdownMenu({
  isOpen,
  onClose,
  children,
  triggerRef,
}: {
  isOpen: boolean
  onClose: () => void
  children: React.ReactNode
  triggerRef: React.RefObject<HTMLButtonElement | null>
}) {
  const menuRef = useRef<HTMLDivElement>(null)
  const [position, setPosition] = useState<{ top: number; left: number } | null>(null)

  useEffect(() => {
    if (isOpen && triggerRef.current) {
      const rect = triggerRef.current.getBoundingClientRect()
      setPosition({ top: rect.bottom + 4, left: rect.right - 192 })
    }

    function handleClickOutside(event: MouseEvent) {
      if (
        menuRef.current &&
        !menuRef.current.contains(event.target as Node) &&
        triggerRef.current &&
        !triggerRef.current.contains(event.target as Node)
      ) {
        onClose()
      }
    }

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside)
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside)
    }
  }, [isOpen, onClose, triggerRef])

  if (!isOpen) return null

  return createPortal(
    <div
      ref={menuRef}
      className="fixed w-48 bg-white border border-gray-200 rounded-md shadow-lg z-[100] py-1"
      style={position ? { top: `${position.top}px`, left: `${position.left}px` } : undefined}
    >
      {children}
    </div>
    ,
    document.body
  )
}

function DropdownItem({
  onClick,
  icon: Icon,
  children,
  className = '',
}: {
  onClick: () => void
  icon: React.ElementType
  children: React.ReactNode
  className?: string
}) {
  return (
    <button
      onClick={onClick}
      className={`w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors ${className}`}
    >
      <Icon className="w-4 h-4" />
      {children}
    </button>
  )
}

export function CompletedBookingsPage() {
  const [searchTerm, setSearchTerm] = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [perPage, setPerPage] = useState(25)
  const [page, setPage] = useState(1)
  const [viewBooking, setViewBooking] = useState<Booking | null>(null)
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const menuTriggerRef = useRef<HTMLButtonElement>(null)

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchTerm), 400)
    return () => clearTimeout(t)
  }, [searchTerm])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch])

  const { data: listResult, isLoading } = useQuery({
    queryKey: ['bookings', 'completed', debouncedSearch, page, perPage],
    queryFn: () =>
      bookingsApi.getPaginated({
        page,
        per_page: perPage,
        status: 'completed',
        ...(debouncedSearch.trim() ? { search: debouncedSearch.trim() } : {}),
      }),
  })

  const bookings = listResult?.data ?? []
  const meta = listResult?.meta

  return (
    <div className="space-y-5 px-1 py-1">
      <Card className="px-6 py-4 shadow-sm border-gray-200">
        <h1 className="text-xl font-bold text-gray-800">Completed Bookings</h1>
      </Card>

      <Card className="shadow-sm border-gray-200">
        <div className="flex items-center justify-between gap-4 px-2 py-1">
          <div className="relative w-full max-w-sm">
            <Search className="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search customer, pet (item), service, notes"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
          </div>
          <Link to="/bookings/new">
            <button className="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 transition-colors whitespace-nowrap">
              <Plus className="w-4 h-4 text-gray-400" />
              <span>New Booking</span>
            </button>
          </Link>
        </div>
      </Card>

      <Card className="shadow-sm border-gray-200">
        <div className="overflow-x-auto overflow-y-visible">
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
                  <td colSpan={9} className="px-5 py-8 text-center text-gray-500 italic">
                    Loading completed bookings...
                  </td>
                </tr>
              ) : bookings.length === 0 ? (
                <tr>
                  <td colSpan={9} className="px-5 py-8 text-center text-gray-500 italic">
                    No completed bookings found.
                  </td>
                </tr>
              ) : (
                bookings.map((row) => {
                  const services = getServiceNames(row)
                  const petsCount = row.details?.length || 0

                  return (
                    <tr key={row.id} className="hover:bg-gray-50 transition-colors">
                      <td className="relative px-5 py-4 text-sm font-bold text-gray-700 align-top">
                        <div className="absolute left-0 top-0 bottom-0 w-1 bg-green-400" />
                        <div className="pl-2">{getCustomerName(row)}</div>
                      </td>

                      <td className="px-5 py-4 align-top text-center text-sm text-gray-600">{petsCount}</td>

                      <td className="px-5 py-4 align-top text-sm text-gray-600">
                        {services.length > 0 ? (
                          <div className="space-y-1">
                            {services.slice(0, 2).map((service) => (
                              <div key={service}>{service}</div>
                            ))}
                            {services.length > 2 ? (
                              <div className="text-xs text-gray-400">+{services.length - 2} more</div>
                            ) : null}
                          </div>
                        ) : (
                          <span className="text-gray-400 italic">No services</span>
                        )}
                      </td>

                      <td className="px-5 py-4 text-sm text-gray-700 align-top">
                        {row.startDate ? format(new Date(row.startDate), 'EEEE, do MMM yyyy') : 'No date'}
                      </td>

                      <td className="px-5 py-4 text-sm text-gray-700 align-top text-center">
                        {row.startTime || '-'}
                      </td>

                      <td className="px-5 py-4 text-sm font-bold text-gray-900 align-top text-right">
                        ${Number(row.total || 0).toFixed(2)}
                      </td>

                      <td className="px-5 py-4 text-sm text-gray-700 leading-snug align-top pr-8">
                        {row.notes || <span className="text-gray-400 italic">No notes</span>}
                      </td>

                      <td className="px-5 py-4 align-top text-center">
                        {row.isRecurring ? (
                          <Check className="w-5 h-5 text-green-600 mx-auto" />
                        ) : (
                          <X className="w-5 h-5 text-red-600 mx-auto" />
                        )}
                      </td>

                      <td className="px-5 py-4 text-sm align-top text-right">
                        <div className="relative inline-block">
                          <button
                            ref={menuTriggerRef}
                            onClick={() => setOpenMenuId((current) => (current === row.id ? null : row.id))}
                            className="p-1 hover:bg-gray-100 rounded transition-colors"
                          >
                            <MoreVertical className="w-5 h-5 text-gray-500" />
                          </button>
                          <DropdownMenu
                            isOpen={openMenuId === row.id}
                            onClose={() => setOpenMenuId(null)}
                            triggerRef={menuTriggerRef}
                          >
                            <DropdownItem
                              onClick={() => {
                                setViewBooking(row)
                                setOpenMenuId(null)
                              }}
                              icon={Eye}
                            >
                              View
                            </DropdownItem>
                            <DropdownItem
                              onClick={() => {
                                setOpenMenuId(null)
                                // TODO: Navigate to edit booking
                              }}
                              icon={Edit}
                            >
                              Edit
                            </DropdownItem>
                            <DropdownItem
                              onClick={() => {
                                setOpenMenuId(null)
                                // TODO: Cancel booking
                              }}
                              icon={XCircle}
                              className="text-red-600 hover:text-red-700"
                            >
                              Cancel
                            </DropdownItem>
                            <div className="border-t border-gray-200 my-1" />
                            <DropdownItem
                              onClick={() => {
                                setOpenMenuId(null)
                                bookingsApi.generateReceipt(row.id)
                              }}
                              icon={FileText}
                            >
                              View Receipt
                            </DropdownItem>
                            <DropdownItem
                              onClick={() => {
                                setOpenMenuId(null)
                                // TODO: Send receipt
                              }}
                              icon={Send}
                            >
                              Send Receipt
                            </DropdownItem>
                          </DropdownMenu>
                        </div>
                      </td>
                    </tr>
                  )
                })
              )}
            </tbody>
          </table>
        </div>

        {meta && (
          <TablePagination
            meta={meta}
            onPageChange={setPage}
            onPerPageChange={setPerPage}
            pageSizeOptions={PAGE_SIZE_OPTIONS}
            isLoading={isLoading}
          />
        )}
      </Card>

      <BookingDetailModal
        isOpen={!!viewBooking}
        onClose={() => setViewBooking(null)}
        booking={viewBooking}
      />
    </div>
  )
}
