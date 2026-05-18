import { useState, useEffect, useMemo } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  DndContext,
  DragOverlay,
  closestCorners,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
  DragStartEvent,
  DragEndEvent,
  useDraggable,
  useDroppable,
} from '@dnd-kit/core'
import {
  sortableKeyboardCoordinates,
} from '@dnd-kit/sortable'
import { Card } from '../../components/ui/Card'
import { PageHeader } from '../../components/layout/PageHeader'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { bookingsApi, customersApi, servicesApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import { useAuthStore } from '../../store/authStore'
import type { Booking, Pet } from '../../types'
import { Plus, CalendarDays, CheckCircle, Clock, XCircle, Play, GripVertical, Calendar } from 'lucide-react'
import { format, addDays, startOfWeek, addWeeks, subWeeks } from 'date-fns'
import { TablePagination } from '../../components/ui/TablePagination'
import { formatDisplayDate, formatDisplayTime } from '../../lib/timeFormatUtils'

export function BookingsPage() {
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [viewMode, setViewMode] = useState<'list' | 'calendar'>('list')
  const [currentWeek, setCurrentWeek] = useState(new Date())
  const [searchTerm, setSearchTerm] = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [filterStatus, setFilterStatus] = useState('')
  const [listPage, setListPage] = useState(1)
  const [listPerPage, setListPerPage] = useState(25)
  const [activeDragId, setActiveDragId] = useState<string | null>(null)

  // New Booking Form State
  const [newBooking, setNewBooking] = useState({
    customerId: '',
    petIds: [] as string[],
    serviceIds: [] as string[],
    date: format(new Date(), 'yyyy-MM-dd'),
    startDate: format(new Date(), 'yyyy-MM-dd'),
    startTime: '09:00',
    isRecurring: false,
    recurringFrequency: 'weekly' as const,
    notes: '',
  })

  const { user } = useAuthStore()

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchTerm), 400)
    return () => clearTimeout(t)
  }, [searchTerm])

  useEffect(() => {
    setListPage(1)
  }, [debouncedSearch, filterStatus, viewMode])

  const { data: listPaginated, isLoading: listLoading } = useQuery({
    queryKey: ['bookings', 'manage-list', listPage, listPerPage, debouncedSearch, filterStatus],
    queryFn: () =>
      bookingsApi.getPaginated({
        page: listPage,
        per_page: listPerPage,
        status: filterStatus || undefined,
        ...(debouncedSearch.trim() ? { search: debouncedSearch.trim() } : {}),
      }),
    enabled: viewMode === 'list',
  })

  const weekStart = startOfWeek(currentWeek, { weekStartsOn: 1 })
  const weekStartStr = format(weekStart, 'yyyy-MM-dd')
  const weekEndStr = format(addDays(weekStart, 6), 'yyyy-MM-dd')

  const { data: calendarBookings = [], isLoading: calendarLoading } = useQuery({
    queryKey: ['bookings', 'calendar-week', weekStartStr, filterStatus],
    queryFn: () =>
      bookingsApi.getAll({
        dateFrom: weekStartStr,
        dateTo: weekEndStr,
        ...(filterStatus ? { status: filterStatus } : {}),
      }),
    enabled: viewMode === 'calendar',
  })

  const calendarFiltered = useMemo(
    () =>
      (calendarBookings ?? []).filter((booking) => {
        const matchesSearch =
          searchTerm === '' ||
          `${booking.customer?.first_name} ${booking.customer?.last_name}`.toLowerCase().includes(searchTerm.toLowerCase()) ||
          booking.details?.some((p) => p.pet?.name?.toLowerCase().includes(searchTerm.toLowerCase()))
        const matchesStatus = filterStatus === '' || booking.status === filterStatus
        return matchesSearch && matchesStatus
      }),
    [calendarBookings, searchTerm, filterStatus]
  )

  const listBookings = listPaginated?.data ?? []
  const listMeta = listPaginated?.meta
  const isLoading = viewMode === 'list' ? listLoading : calendarLoading
  const filteredBookings = viewMode === 'list' ? listBookings : calendarFiltered

  const { data: customers } = useQuery({
    queryKey: ['customers'],
    queryFn: () => customersApi.getAll({ franchise_id: user?.franchise_id }),
    enabled: isModalOpen,
  })

  const { data: services } = useQuery({
    queryKey: ['services'],
    queryFn: () => servicesApi.getAll(),
    enabled: isModalOpen,
  })

  const statusMutation = useMutation({
    mutationFn: ({ id, status }: { id: string; status: Booking['status'] }) =>
      bookingsApi.updateStatus(id, status),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bookings'] })
      addToast('Booking status updated', 'success')
    },
  })

  const createBookingMutation = useMutation({
    mutationFn: (data: Omit<Booking, 'id' | 'createdAt' | 'updatedAt' | 'status'>) =>
      bookingsApi.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bookings'] })
      setIsModalOpen(false)
      addToast('Booking created successfully', 'success')
    },
    onError: () => {
      addToast('Failed to create booking', 'error')
    },
  })

  const updateBookingMutation = useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<Booking> }) =>
      bookingsApi.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bookings'] })
      addToast('Booking updated successfully', 'success')
    },
  })

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 5,
      },
    }),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  )

  const handleDragStart = (event: DragStartEvent) => {
    setActiveDragId(event.active.id as string)
  }

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event
    setActiveDragId(null)

    if (!over) return

    const bookingId = active.id as string
    const dropTarget = over.id as string

    // Parse the drop target (format: "time-slot-YYYY-MM-DD-HH:mm")
    if (dropTarget.startsWith('time-slot-')) {
      const parts = dropTarget.replace('time-slot-', '').split('-')
      const timeIndex = parts.length - 2
      const newDate = parts.slice(0, timeIndex).join('-')
      const newTime = parts.slice(timeIndex).join(':')
      
      updateBookingMutation.mutate({
        id: bookingId,
        data: { date: newDate, startTime: newTime }
      })
    }
  }

  const statusConfig: Record<string, { label: string; color: string; icon: any }> = {
    requested: { label: 'Requested', color: 'bg-yellow-100 text-yellow-700 border-yellow-200', icon: Clock },
    confirmed: { label: 'Confirmed', color: 'bg-blue-100 text-blue-700 border-blue-200', icon: CheckCircle },
    in_progress: { label: 'In Progress', color: 'bg-purple-100 text-purple-700 border-purple-200', icon: Play },
    active: { label: 'Active', color: 'bg-blue-100 text-blue-700 border-blue-200', icon: CheckCircle },
    completed: { label: 'Completed', color: 'bg-green-100 text-green-700 border-green-200', icon: CheckCircle },
    cancelled: { label: 'Cancelled', color: 'bg-red-100 text-red-700 border-red-200', icon: XCircle },
    canceled: { label: 'Cancelled', color: 'bg-red-100 text-red-700 border-red-200', icon: XCircle },
    archived: { label: 'Archived', color: 'bg-gray-100 text-gray-700 border-gray-200', icon: XCircle },
  }

  const weekDays = [...Array(7)].map((_, i) => addDays(weekStart, i))

  // Time slots from 8 AM to 6 PM
  const timeSlots = [...Array(11)].map((_, i) => {
    const hour = i + 8
    return `${hour.toString().padStart(2, '0')}:00`
  })

  const activeBooking = activeDragId ? filteredBookings?.find((b) => b.id === activeDragId) : null

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-4">
        <div className="flex-1">
          <PageHeader
            title="Bookings"
            description="Manage all your bookings in list or calendar view"
            icon={<Calendar className="w-5 h-5" />}
          />
        </div>
        <div className="flex gap-3">
          <div className="flex bg-secondary-100 rounded-lg p-1">
            <button
              onClick={() => setViewMode('list')}
              className={`px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${
                viewMode === 'list' ? 'bg-white text-secondary-900 shadow-sm' : 'text-secondary-600'
              }`}
            >
              List
            </button>
            <button
              onClick={() => setViewMode('calendar')}
              className={`px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${
                viewMode === 'calendar' ? 'bg-white text-secondary-900 shadow-sm' : 'text-secondary-600'
              }`}
            >
              Calendar
            </button>
          </div>
          <Button onClick={() => setIsModalOpen(true)} size="sm">
            <Plus className="w-4 h-4 mr-2" />
            New Booking
          </Button>
        </div>
      </div>

      <Card>
        <div className="flex items-center gap-4 mb-6">
          <div className="relative flex-1 max-w-md">
            <CalendarDays className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
            <input
              type="text"
              placeholder="Search bookings..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="input pl-10"
            />
          </div>
          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="input w-40"
          >
            <option value="">All Status</option>
            <option value="requested">Requested</option>
            <option value="confirmed">Confirmed</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="canceled">Canceled</option>
          </select>
        </div>

        {viewMode === 'list' ? (
          <>
          <Table
            columns={[
              { key: 'customer', title: 'Customer', render: (row: Booking) => `${row.customer?.first_name} ${row.customer?.last_name}` },
              { key: 'pets', title: 'Pets', render: (row: Booking) => Array.from(new Set(row.details?.map(p => p.pet?.name).filter(Boolean))).join(', ') },
              { key: 'services', title: 'Services', render: (row: Booking) => row.details?.map(d => d.service?.name).filter(Boolean).join(', ') },
              { key: 'date', title: 'Date', render: (row: Booking) => formatDisplayDate(row.startDate) },
              { key: 'startTime', title: 'Time' },
              { key: 'total', title: 'Total', render: (row: Booking) => `$${row.total}` },
              {
                key: 'status',
                title: 'Status',
                render: (row: Booking) => {
                  const config = statusConfig[row.status] || { label: row.status, color: 'bg-gray-100 text-gray-700' }
                  return (
                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${config.color}`}>
                      {config.label}
                    </span>
                  )
                },
              },
              {
                key: 'actions',
                title: 'Actions',
                render: (row: Booking) => (
                  <div className="flex gap-1">
                    {row.status === 'requested' && (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => statusMutation.mutate({ id: row.id, status: 'confirmed' })}
                      >
                        Confirm
                      </Button>
                    )}
                    {row.status === 'confirmed' && (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => statusMutation.mutate({ id: row.id, status: 'in_progress' })}
                      >
                        Start
                      </Button>
                    )}
                    {row.status === 'in_progress' && (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => statusMutation.mutate({ id: row.id, status: 'completed' })}
                      >
                        Complete
                      </Button>
                    )}
                  </div>
                ),
              },
            ]}
            data={filteredBookings ?? []}
            keyExtractor={(row) => row.id}
            isLoading={isLoading}
          />
          {listMeta && (
            <TablePagination
              meta={listMeta}
              onPageChange={setListPage}
              onPerPageChange={setListPerPage}
              isLoading={listLoading}
            />
          )}
          </>
        ) : (
          <DndContext
            sensors={sensors}
            collisionDetection={closestCorners}
            onDragStart={handleDragStart}
            onDragEnd={handleDragEnd}
          >
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <Button variant="secondary" size="sm" onClick={() => setCurrentWeek(subWeeks(currentWeek, 1))}>
                  Previous Week
                </Button>
                <span className="font-medium">{format(weekStart, 'MMMM yyyy')}</span>
                <Button variant="secondary" size="sm" onClick={() => setCurrentWeek(addWeeks(currentWeek, 1))}>
                  Next Week
                </Button>
              </div>
              
              {/* Calendar Grid with Time Slots */}
              <div className="border rounded-lg overflow-hidden">
                {/* Header Row */}
                <div className="grid grid-cols-8 border-b">
                  <div className="p-2 bg-secondary-50 border-r text-sm font-medium text-secondary-500">Time</div>
                  {weekDays.map((day) => (
                    <div key={day.toISOString()} className="p-2 bg-secondary-50 border-r last:border-r-0 text-center">
                      <p className="text-sm font-medium">{format(day, 'EEE')}</p>
                      <p className="text-xs text-secondary-500">{format(day, 'd')}</p>
                    </div>
                  ))}
                </div>
                
                {/* Time Slot Rows */}
                {timeSlots.map((timeSlot) => (
                  <div key={timeSlot} className="grid grid-cols-8 border-b last:border-b-0">
                    <div className="p-2 bg-secondary-50 border-r text-xs text-secondary-500 flex items-center justify-center">
                      {timeSlot}
                    </div>
                    {weekDays.map((day) => {
                      const dateStr = format(day, 'yyyy-MM-dd')
                      const slotId = `time-slot-${dateStr}-${timeSlot}`
                      const { setNodeRef: setDroppableRef, isOver } = useDroppable({ id: slotId })
                      
                      // Find bookings that match this time slot
                      const slotHour = parseInt(timeSlot.split(':')[0])
                      const dayBookings = filteredBookings?.filter(
                        b => {
                          const bookingDate = format(new Date(b.startDate), 'yyyy-MM-dd')
                          const bookingHour = parseInt(b.startTime.split(':')[0])
                          return bookingDate === dateStr && bookingHour === slotHour
                        }
                      ) || []
                      
                      return (
                        <div
                          key={slotId}
                          ref={setDroppableRef}
                          className={`p-1 min-h-[70px] border-r last:border-r-0 transition-colors relative ${
                            isOver ? 'bg-primary-100' : 'bg-white hover:bg-secondary-50'
                          }`}
                        >
                          {dayBookings.map((booking) => {
                            const { setNodeRef: setDraggableRef, listeners, attributes, transform, isDragging } = useDraggable({ 
                              id: booking.id,
                              data: { booking }
                            })
                            const config = statusConfig[booking.status] || { label: booking.status, color: 'bg-gray-100 text-gray-700' }
                            
                            const style = transform ? {
                              transform: `translate3d(${transform.x}px, ${transform.y}px, 0)`,
                            } : undefined
                            
                            return (
                              <div
                                key={booking.id}
                                ref={setDraggableRef}
                                {...listeners}
                                {...attributes}
                                style={style}
                                className={`text-xs p-2 mb-1 rounded border cursor-move shadow-sm hover:shadow-md transition-all ${config.color} ${
                                  isDragging ? 'opacity-50' : ''
                                }`}
                              >
                                <div className="flex items-center gap-1">
                                  <GripVertical className="w-3 h-3 opacity-50" />
                                  <span className="font-medium">{formatDisplayTime(booking.startTime)}</span>
                                </div>
                                <p className="truncate">{booking.customer?.first_name} {booking.customer?.last_name}</p>
                                <p className="text-xs opacity-75 truncate">{Array.from(new Set(booking.details?.map(p => p.pet?.name).filter(Boolean))).join(', ')}</p>
                              </div>
                            )
                          })}
                        </div>
                      )
                    })}
                  </div>
                ))}
              </div>
              
              <p className="text-sm text-secondary-500">
                Drag and drop bookings to different time slots to reschedule them.
              </p>
            </div>
            
            <DragOverlay>
              {activeBooking ? (
                <div className={`text-xs p-2 rounded border shadow-lg ${(statusConfig[activeBooking.status] || { color: 'bg-gray-100 text-gray-700' }).color} opacity-90`}>
                  <p className="font-medium">{activeBooking.startTime} - {activeBooking.customer?.first_name}</p>
                  <p className="truncate">{Array.from(new Set(activeBooking.details?.map(p => p.pet?.name).filter(Boolean))).join(', ')}</p>
                </div>
              ) : null}
            </DragOverlay>
          </DndContext>
        )}
      </Card>

      {/* Create Booking Modal */}
      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title="Create New Booking"
        size="lg"
        footer={
          <>
            <Button variant="secondary" onClick={() => setIsModalOpen(false)}>
              Cancel
            </Button>
            <Button
              onClick={() => {
                const total = services?.filter(s => newBooking.serviceIds.includes(s.id)).reduce((acc, curr) => acc + curr.price, 0) || 0
                createBookingMutation.mutate({
                  ...newBooking,
                  companyId: user?.companyId || user?.company_id || '1',
                  franchise_id: user?.franchise_id || '1',
                  total,
                })
              }}
              isLoading={createBookingMutation.isPending}
              disabled={!newBooking.customerId || newBooking.petIds.length === 0 || newBooking.serviceIds.length === 0}
            >
              Create Booking
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          <div>
            <label className="text-sm font-medium text-secondary-700">Customer</label>
            <select 
              className="input mt-1"
              value={newBooking.customerId}
              onChange={(e) => {
                const customerId = e.target.value
                setNewBooking({ ...newBooking, customerId, petIds: [] })
              }}
            >
              <option value="">Select Customer</option>
              {customers?.map(c => (
                <option key={c.id} value={c.id}>{c.first_name} {c.last_name}</option>
              ))}
            </select>
          </div>
          
          {newBooking.customerId && (
            <div>
              <label className="text-sm font-medium text-secondary-700">Pets (Select Multiple)</label>
              <div className="grid grid-cols-2 gap-2 mt-1">
                {customers?.find(c => c.id === newBooking.customerId)?.pets?.map((pet: Pet) => (
                  <label key={pet.id} className="flex items-center gap-2 p-2 border rounded-md cursor-pointer hover:bg-secondary-50">
                    <input 
                      type="checkbox"
                      checked={newBooking.petIds.includes(pet.id)}
                      onChange={(e) => {
                        const petIds = e.target.checked 
                          ? [...newBooking.petIds, pet.id]
                          : newBooking.petIds.filter(id => id !== pet.id)
                        setNewBooking({ ...newBooking, petIds })
                      }}
                    />
                    <span className="text-sm">{pet.name} ({pet.breed})</span>
                  </label>
                ))}
              </div>
            </div>
          )}

          <div>
            <label className="text-sm font-medium text-secondary-700">Services (Select Multiple)</label>
            <div className="grid grid-cols-2 gap-2 mt-1">
              {services?.map(service => (
                <label key={service.id} className="flex items-center gap-2 p-2 border rounded-md cursor-pointer hover:bg-secondary-50 font-bold">
                  <input 
                    type="checkbox"
                    checked={newBooking.serviceIds.includes(service.id)}
                    onChange={(e) => {
                      const serviceIds = e.target.checked 
                        ? [...newBooking.serviceIds, service.id]
                        : newBooking.serviceIds.filter(id => id !== service.id)
                      setNewBooking({ ...newBooking, serviceIds })
                    }}
                  />
                  <div className="flex flex-col">
                    <span className="text-sm">{service.name}</span>
                    <span className="text-xs text-secondary-500">${service.price}</span>
                  </div>
                </label>
              ))}
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <Input 
              label="Date" 
              type="date" 
              value={newBooking.startDate}
              onChange={(e) => setNewBooking({ ...newBooking, startDate: e.target.value })}
            />
            <Input 
              label="Time" 
              type="time" 
              value={newBooking.startTime}
              onChange={(e) => setNewBooking({ ...newBooking, startTime: e.target.value })}
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="flex items-center gap-2 mt-6">
              <input 
                type="checkbox" 
                id="isRecurring"
                checked={newBooking.isRecurring}
                onChange={(e) => setNewBooking({ ...newBooking, isRecurring: e.target.checked })}
                className="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
              />
              <label htmlFor="isRecurring" className="text-sm font-medium text-secondary-700">Recurring Booking</label>
            </div>
            {newBooking.isRecurring && (
              <div>
                <label className="text-sm font-medium text-secondary-700">Frequency</label>
                <select 
                  className="input mt-1"
                  value={newBooking.recurringFrequency}
                  onChange={(e) => setNewBooking({ ...newBooking, recurringFrequency: e.target.value as any })}
                >
                  <option value="weekly">Weekly</option>
                  <option value="fortnightly">Fortnightly</option>
                  <option value="monthly">Monthly</option>
                </select>
              </div>
            )}
          </div>
          <div>
            <label className="text-sm font-medium text-secondary-700">Notes</label>
            <textarea 
              className="input mt-1" 
              rows={3} 
              value={newBooking.notes}
              onChange={(e) => setNewBooking({ ...newBooking, notes: e.target.value })}
            />
          </div>
        </div>
      </Modal>
    </div>
  )
}
