import { useState, useEffect, useRef } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { ChevronLeft, ChevronRight, List, MoreVertical, Eye, Edit, Trash2, Copy } from 'lucide-react'
import { format, addWeeks, subWeeks, startOfWeek, endOfWeek, parseISO } from 'date-fns'
import { PageHeader } from '../../components/layout/PageHeader'
import { bookingsApi, blockoutsApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import { useNavigate } from 'react-router-dom'
import { BookingDetailModal } from '../../components/modals/BookingDetailModal'
import { BlockoutDetailModal } from '../../components/modals/BlockoutDetailModal'
import type { Booking as BookingType, Blockout } from '../../types'

interface AgendaItem {
  id: string
  bookingId?: string
  blockoutId?: string
  date: string
  time: string
  customer: string
  pet: string
  service: string
  status: string
  eventType: 'booking' | 'blockout'
}

// Convert time string to display format
const normalizeTime = (time: string): string => {
  if (!time) return ''
  const match = time.match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/)
  if (match) {
    const hour = parseInt(match[1], 10)
    const minute = match[2]
    const period = hour >= 12 ? 'PM' : 'AM'
    const hour12 = hour % 12 === 0 ? 12 : hour % 12
    return `${hour12}:${minute} ${period}`
  }
  return time
}

export function CalendarAgendaViewPage() {
  const [currentWeek, setCurrentWeek] = useState(new Date())
  const [agendaItems, setAgendaItems] = useState<AgendaItem[]>([])
  const [loading, setLoading] = useState(false)
  const [selectedBooking, setSelectedBooking] = useState<BookingType | null>(null)
  const [selectedBlockout, setSelectedBlockout] = useState<Blockout | null>(null)
  const [showBookingModal, setShowBookingModal] = useState(false)
  const [showBlockoutModal, setShowBlockoutModal] = useState(false)
  const addToast = useToastStore((s) => s.addToast)
  const navigate = useNavigate()

  const weekStart = startOfWeek(currentWeek)
  const weekEnd = endOfWeek(currentWeek)

  useEffect(() => {
    const loadAgendaItems = async () => {
      setLoading(true)
      try {
        const dateFrom = format(weekStart, 'yyyy-MM-dd')
        const dateTo = format(weekEnd, 'yyyy-MM-dd')

        const [bookingPage, blockoutPage] = await Promise.all([
          bookingsApi.getPaginated({ page: 1, per_page: 100, dateFrom, dateTo }),
          blockoutsApi.getPaginated({ page: 1, per_page: 100 }),
        ])

        const bookingItems: AgendaItem[] = (bookingPage?.data || [])
          .filter((b) => b.status === 'ACTIVE' || b.status === 'COMPLETED')
          .map((b) => {
            const customerName = b.customerName
              || `${b.customer?.first_name || ''} ${b.customer?.last_name || ''}`.trim()
              || 'Booking'
            const petName = (b.details || []).map((d: any) => d.pet?.name).filter(Boolean).join(', ')
            const serviceName = (b.details || []).map((d: any) => d.service?.name).filter(Boolean).join(', ')

            return {
              id: `booking-${b.id}`,
              bookingId: b.id,
              date: format(parseISO(b.startDate), 'MMM d, yyyy'),
              time: normalizeTime(b.startTime),
              customer: customerName,
              pet: petName || '-',
              service: serviceName || '-',
              status: b.status || 'ACTIVE',
              eventType: 'booking' as const,
            }
          })

        const blockoutItems: AgendaItem[] = (blockoutPage?.data || [])
          .filter((b) => {
            const bDate = new Date(b.startDate)
            const fromDate = new Date(dateFrom)
            const toDate = new Date(dateTo)
            return bDate >= fromDate && bDate <= toDate
          })
          .map((b) => ({
            id: `blockout-${b.id}`,
            blockoutId: b.id,
            date: format(parseISO(b.startDate), 'MMM d, yyyy'),
            time: normalizeTime(b.startTime),
            customer: b.title || 'Blockout',
            pet: '-',
            service: b.location || '-',
            status: b.active ? 'Active' : 'Cancelled',
            eventType: 'blockout' as const,
          }))

        // Sort by date and time
        const allItems = [...bookingItems, ...blockoutItems].sort((a, b) => {
          const dateCompare = a.date.localeCompare(b.date)
          if (dateCompare !== 0) return dateCompare
          return a.time.localeCompare(b.time)
        })

        setAgendaItems(allItems)
      } catch (error) {
        console.error('Error loading agenda data:', error)
        addToast('Failed to load agenda data', 'error')
      } finally {
        setLoading(false)
      }
    }

    loadAgendaItems()
  }, [currentWeek, addToast])

  const getStatusBadge = (status: string, eventType: string) => {
    if (eventType === 'blockout') {
      return (
        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
          Blockout
        </span>
      )
    }
    const statusColors: Record<string, string> = {
      ACTIVE: 'bg-green-100 text-green-800',
      COMPLETED: 'bg-blue-100 text-blue-800',
      CANCELLED: 'bg-red-100 text-red-800',
      pending: 'bg-yellow-100 text-yellow-800',
    }
    return (
      <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${statusColors[status] || 'bg-gray-100 text-gray-800'}`}>
        {status.charAt(0).toUpperCase() + status.slice(1)}
      </span>
    )
  }

  const handleViewItem = async (item: AgendaItem) => {
    if (item.eventType === 'blockout' && item.blockoutId) {
      try {
        const blockoutData = await blockoutsApi.getById(item.blockoutId)
        setSelectedBlockout(blockoutData)
        setShowBlockoutModal(true)
      } catch (error) {
        console.error('Error fetching blockout:', error)
        addToast('Failed to load blockout details', 'error')
      }
    } else if (item.bookingId) {
      try {
        const bookingData = await bookingsApi.getById(item.bookingId)
        setSelectedBooking(bookingData)
        setShowBookingModal(true)
      } catch (error) {
        console.error('Error fetching booking:', error)
        addToast('Failed to load booking details', 'error')
      }
    }
  }

  const handleEditItem = (item: AgendaItem) => {
    if (item.eventType === 'blockout' && item.blockoutId) {
      navigate(`/blockouts/edit/${item.blockoutId}`)
    } else if (item.bookingId) {
      navigate(`/bookings/edit/${item.bookingId}`)
    }
  }

  const handleDuplicateItem = (item: AgendaItem) => {
    if (item.bookingId) {
      navigate(`/bookings/create?duplicate=${item.bookingId}`)
    }
  }

  const handleDeleteItem = async (item: AgendaItem) => {
    if (!confirm('Are you sure you want to delete this item?')) return
    
    try {
      if (item.eventType === 'blockout' && item.blockoutId) {
        await blockoutsApi.delete(item.blockoutId)
        addToast('Blockout deleted successfully', 'success')
      } else if (item.bookingId) {
        await bookingsApi.delete(item.bookingId)
        addToast('Booking deleted successfully', 'success')
      }
      setAgendaItems(agendaItems.filter(a => a.id !== item.id))
    } catch (error) {
      console.error('Error deleting item:', error)
      addToast('Failed to delete item', 'error')
    }
  }

  const ActionsDropdown = ({ item }: { item: AgendaItem }) => {
    const [isOpen, setIsOpen] = useState(false)
    const dropdownRef = useRef<HTMLDivElement>(null)

    useEffect(() => {
      const handleClickOutside = (event: MouseEvent) => {
        if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
          setIsOpen(false)
        }
      }
      document.addEventListener('mousedown', handleClickOutside)
      return () => document.removeEventListener('mousedown', handleClickOutside)
    }, [])

    return (
      <div className="relative" ref={dropdownRef}>
        <button
          onClick={(e) => {
            e.stopPropagation()
            setIsOpen(!isOpen)
          }}
          className="p-1 hover:bg-gray-100 rounded-full transition-colors"
        >
          <MoreVertical className="w-4 h-4 text-gray-500" />
        </button>
        {isOpen && (
          <div className="absolute right-0 mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
            <button
              onClick={(e) => {
                e.stopPropagation()
                setIsOpen(false)
                handleViewItem(item)
              }}
              className="w-full px-3 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2"
            >
              <Eye className="w-4 h-4 text-gray-500" />
              View Details
            </button>
            <button
              onClick={(e) => {
                e.stopPropagation()
                setIsOpen(false)
                handleEditItem(item)
              }}
              className="w-full px-3 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2"
            >
              <Edit className="w-4 h-4 text-gray-500" />
              Edit
            </button>
            {item.eventType !== 'blockout' && (
              <button
                onClick={(e) => {
                  e.stopPropagation()
                  setIsOpen(false)
                  handleDuplicateItem(item)
                }}
                className="w-full px-3 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2"
              >
                <Copy className="w-4 h-4 text-gray-500" />
                Duplicate
              </button>
            )}
            <div className="border-t border-gray-100 my-1" />
            <button
              onClick={(e) => {
                e.stopPropagation()
                setIsOpen(false)
                handleDeleteItem(item)
              }}
              className="w-full px-3 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2 text-red-600"
            >
              <Trash2 className="w-4 h-4" />
              Delete
            </button>
          </div>
        )}
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Calendar - Agenda View"
        description="View your schedule in list format"
        icon={<List className="w-5 h-5" />}
        actions={
          <div className="flex items-center gap-2">
            <Button variant="secondary" size="sm" onClick={() => setCurrentWeek(subWeeks(currentWeek, 1))}>
              <ChevronLeft className="w-4 h-4" />
            </Button>
            <span className="text-sm font-medium min-w-[160px] text-center">
              {format(weekStart, 'MMM d')} - {format(weekEnd, 'MMM d')}
            </span>
            <Button variant="secondary" size="sm" onClick={() => setCurrentWeek(addWeeks(currentWeek, 1))}>
              <ChevronRight className="w-4 h-4" />
            </Button>
          </div>
        }
      />

      <Card>
        {loading ? (
          <div className="p-8 text-center text-gray-500">Loading...</div>
        ) : agendaItems.length === 0 ? (
          <div className="p-8 text-center text-gray-500">No calendar items for this week</div>
        ) : (
          <Table
            columns={[
              { key: 'date', title: 'Date' },
              { key: 'time', title: 'Time' },
              { key: 'customer', title: 'Customer' },
              { key: 'pet', title: 'Pet' },
              { key: 'service', title: 'Service' },
              { 
                key: 'status', 
                title: 'Status',
                render: (row) => getStatusBadge(row.status, row.eventType)
              },
              { 
                key: 'actions', 
                title: '', 
                render: (row) => <ActionsDropdown item={row} />
              },
            ]}
            data={agendaItems}
            keyExtractor={(row) => row.id}
          />
        )}
      </Card>

      {/* Booking Detail Modal */}
      {showBookingModal && selectedBooking && (
        <BookingDetailModal
          isOpen={showBookingModal}
          booking={selectedBooking}
          onClose={() => {
            setShowBookingModal(false)
            setSelectedBooking(null)
          }}
        />
      )}

      {/* Blockout Detail Modal */}
      {showBlockoutModal && selectedBlockout && (
        <BlockoutDetailModal
          isOpen={showBlockoutModal}
          blockout={selectedBlockout}
          onClose={() => {
            setShowBlockoutModal(false)
            setSelectedBlockout(null)
          }}
        />
      )}
    </div>
  )
}
