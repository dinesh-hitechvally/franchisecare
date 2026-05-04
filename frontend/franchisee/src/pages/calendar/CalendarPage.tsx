import { useState, useRef, useCallback } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Input } from '../../components/ui/Input'
import { Search, Filter, ChevronLeft, ChevronRight, CalendarDays, Clock, List, GripVertical } from 'lucide-react'
import { format, startOfMonth, endOfMonth, eachDayOfInterval, isSameMonth, isSameDay, addMonths, subMonths, startOfWeek, endOfWeek, addWeeks, subWeeks, addDays, subDays, parseISO, isWithinInterval, max, min } from 'date-fns'
import {
  DndContext,
  DragOverlay,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
  DragStartEvent,
  DragEndEvent,
  useDraggable,
  useDroppable,
} from '@dnd-kit/core'
import { CSS } from '@dnd-kit/utilities'

type ViewMode = 'month' | 'week' | 'day' | 'agenda'

interface Booking {
  id: string
  customerName: string
  petName: string
  service: string
  date: string // start date
  endDate?: string // end date for multi-day bookings
  time: string
  status: string
  duration: number // in minutes (for single day) or days (if multi-day)
  isMultiDay?: boolean
}

interface DayBooking {
  id: string
  time: string
  customer: string
  pet: string
  service: string
  duration: number
  date: string
  endDate?: string
  isMultiDay?: boolean
  dayIndex?: number
  totalDays?: number
}

// Draggable Booking Component with Resize Support
function ResizableDraggableBooking({ 
  booking, 
  isDayView = false, 
  style: customStyle,
  onResize,
  slotHeight = 60,
  isMultiDay = false,
  isFirstDay = true,
  isLastDay = true
}: { 
  booking: Booking | DayBooking; 
  isDayView?: boolean; 
  style?: React.CSSProperties;
  onResize?: (id: string, newDuration: number) => void;
  slotHeight?: number;
  isMultiDay?: boolean;
  isFirstDay?: boolean;
  isLastDay?: boolean;
}) {
  const { attributes, listeners, setNodeRef, transform, isDragging } = useDraggable({
    id: booking.id,
    data: booking,
  })

  const [isResizing, setIsResizing] = useState(false)
  const [currentDuration, setCurrentDuration] = useState((booking as DayBooking).duration || 60)
  const resizeStartY = useRef(0)
  const resizeStartDuration = useRef(0)

  const style: React.CSSProperties = {
    ...(transform ? {
      transform: CSS.Translate.toString(transform),
      opacity: isDragging || isResizing ? 0.8 : 1,
      cursor: isResizing ? 'ns-resize' : 'grab',
    } : {
      cursor: isResizing ? 'ns-resize' : 'grab',
    }),
    ...customStyle,
  }

  const handleResizeStart = useCallback((e: React.MouseEvent) => {
    e.preventDefault()
    e.stopPropagation()
    setIsResizing(true)
    resizeStartY.current = e.clientY
    resizeStartDuration.current = currentDuration
  }, [currentDuration])

  const handleResizeMove = useCallback((e: MouseEvent) => {
    if (!isResizing) return
    
    const deltaY = e.clientY - resizeStartY.current
    const slotsChanged = Math.round(deltaY / slotHeight)
    const newDuration = Math.max(15, resizeStartDuration.current + (slotsChanged * 15))
    
    setCurrentDuration(newDuration)
  }, [isResizing, slotHeight])

  const handleResizeEnd = useCallback(() => {
    if (isResizing) {
      setIsResizing(false)
      if (onResize) {
        onResize(booking.id, currentDuration)
      }
    }
  }, [isResizing, currentDuration, booking.id, onResize])

  useState(() => {
    if (isResizing) {
      window.addEventListener('mousemove', handleResizeMove)
      window.addEventListener('mouseup', handleResizeEnd)
      return () => {
        window.removeEventListener('mousemove', handleResizeMove)
        window.removeEventListener('mouseup', handleResizeEnd)
      }
    }
  })

  if (isDayView) {
    const dayBooking = booking as DayBooking
    const durationText = currentDuration >= 60 
      ? `${Math.floor(currentDuration / 60)}h ${currentDuration % 60}m`
      : `${currentDuration}m`
    
    return (
      <div
        ref={setNodeRef}
        {...attributes}
        style={style}
        className="relative flex-1 bg-blue-50 border-l-4 border-blue-500 rounded hover:bg-blue-100 transition-colors overflow-hidden group"
      >
        {/* Main content - draggable area */}
        <div 
          {...listeners}
          className="p-3 h-full"
        >
          <div className="flex items-center justify-between h-full">
            <div className="flex items-center gap-2">
              <GripVertical className="w-4 h-4 text-gray-400 flex-shrink-0" />
              <div>
                <p className="font-medium text-gray-900 text-sm">{dayBooking.customer} - {dayBooking.pet}</p>
                <p className="text-xs text-gray-500">{dayBooking.service}</p>
              </div>
            </div>
            <span className="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded flex-shrink-0">{durationText}</span>
          </div>
        </div>
        
        {/* Resize handle */}
        <div
          onMouseDown={handleResizeStart}
          className="absolute bottom-0 left-0 right-0 h-3 bg-blue-300 hover:bg-blue-400 cursor-ns-resize flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
        >
          <div className="w-8 h-1 bg-blue-600 rounded-full" />
        </div>
      </div>
    )
  }

  const regularBooking = booking as Booking
  const isBookingMultiDay = isMultiDay || regularBooking.isMultiDay
  
  // Determine border radius based on multi-day position
  const borderRadiusClass = isBookingMultiDay
    ? isFirstDay && isLastDay
      ? 'rounded' // Single day
      : isFirstDay
      ? 'rounded-l rounded-r-none' // First day - round left only
      : isLastDay
      ? 'rounded-r rounded-l-none' // Last day - round right only
      : 'rounded-none' // Middle day - no rounding
    : 'rounded'

  // Determine multi-day indicator
  const multiDayIndicator = isBookingMultiDay && (
    <span className="text-[10px] bg-purple-100 text-purple-700 px-1 rounded ml-1">
      {isFirstDay && isLastDay ? '' : isFirstDay ? '→' : isLastDay ? '←' : '↔'}
    </span>
  )

  return (
    <div
      ref={setNodeRef}
      {...listeners}
      {...attributes}
      style={style}
      className={`${isBookingMultiDay ? 'bg-purple-100 text-purple-800 border-purple-300' : 'bg-blue-100 text-blue-800'} text-xs p-2 ${borderRadiusClass} hover:opacity-80 transition-colors ${isBookingMultiDay ? 'border border-purple-300' : ''}`}
    >
      <div className="flex items-center gap-1">
        <GripVertical className="w-3 h-3 flex-shrink-0" />
        <div className="truncate">
          <p className="font-medium truncate">{regularBooking.customerName} {multiDayIndicator}</p>
          <p className="truncate">{regularBooking.startTime} - {regularBooking.service}</p>
          {isBookingMultiDay && regularBooking.endDate && (
            <p className="text-[10px] opacity-75">
              {format(parseISO(regularBooking.startDate), 'MMM d')} - {format(parseISO(regularBooking.endDate), 'MMM d')}
            </p>
          )}
        </div>
      </div>
    </div>
  )
}

// Droppable Day Cell Component
function DroppableDayCell({ date, children, isCurrentMonth, isToday }: { 
  date: Date
  children: React.ReactNode
  isCurrentMonth: boolean
  isToday: boolean
}) {
  const { isOver, setNodeRef } = useDroppable({
    id: `day-${date.toISOString()}`,
    data: { date },
  })

  return (
    <div
      ref={setNodeRef}
      className={`bg-white min-h-[100px] p-2 transition-colors ${
        !isCurrentMonth ? 'text-gray-400' : ''
      } ${isOver ? 'bg-green-50 ring-2 ring-green-400 ring-inset' : ''}`}
    >
      <div className={`text-sm font-medium mb-1 ${isToday ? 'bg-blue-500 text-white w-7 h-7 rounded-full flex items-center justify-center' : ''}`}>
        {format(date, 'd')}
      </div>
      {children}
    </div>
  )
}

// Droppable Time Slot Component
function DroppableTimeSlot({ time, date, children }: { 
  time: string
  date: Date
  children: React.ReactNode
}) {
  const { isOver, setNodeRef } = useDroppable({
    id: `slot-${time}-${date.toISOString()}`,
    data: { time, date },
  })

  return (
    <div
      ref={setNodeRef}
      className={`bg-white p-2 min-h-[60px] border-t border-gray-100 transition-colors ${
        isOver ? 'bg-green-50 ring-2 ring-green-400 ring-inset' : ''
      }`}
    >
      {children}
    </div>
  )
}

export function CalendarPage() {
  const [viewMode, setViewMode] = useState<ViewMode>('week')
  const [currentDate, setCurrentDate] = useState(new Date())
  const [searchTerm, setSearchTerm] = useState('')
  const [activeDragId, setActiveDragId] = useState<string | null>(null)
  const [bookings, setBookings] = useState<Booking[]>([
    { id: '1', customerName: 'John Doe', petName: 'Max', service: 'Full Groom', date: '2026-04-02', time: '9:00 AM', status: 'Confirmed', duration: 90 },
    { id: '2', customerName: 'Jane Smith', petName: 'Bella', service: 'Bath & Dry', date: '2026-04-02', time: '10:30 AM', status: 'Confirmed', duration: 60 },
    { id: '3', customerName: 'Mike Johnson', petName: 'Charlie', service: 'Full Groom', date: '2026-04-02', time: '2:00 PM', status: 'Pending', duration: 90 },
    { id: '4', customerName: 'Alice Cooper', petName: 'Fluffy', service: 'Nail Trim', date: format(new Date(), 'yyyy-MM-dd'), time: '11:00 AM', status: 'Confirmed', duration: 30 },
    { id: '5', customerName: 'Bob Dylan', petName: 'Buddy', service: 'Spa Package', date: format(new Date(), 'yyyy-MM-dd'), time: '1:00 PM', status: 'Confirmed', duration: 180 },
    // Multi-day bookings
    { id: '6', customerName: 'Carol King', petName: 'Coco', service: '3-Day Training Camp', date: format(new Date(), 'yyyy-MM-dd'), endDate: format(addDays(new Date(), 2), 'yyyy-MM-dd'), time: '9:00 AM', status: 'Confirmed', duration: 3, isMultiDay: true },
    { id: '7', customerName: 'David Bowie', petName: 'Stardust', service: 'Overnight Boarding', date: format(addDays(new Date(), 3), 'yyyy-MM-dd'), endDate: format(addDays(new Date(), 4), 'yyyy-MM-dd'), time: '5:00 PM', status: 'Confirmed', duration: 2, isMultiDay: true },
  ])

  const timeSlots = [
    '9:00 AM', '9:15 AM', '9:30 AM', '9:45 AM',
    '10:00 AM', '10:15 AM', '10:30 AM', '10:45 AM',
    '11:00 AM', '11:15 AM', '11:30 AM', '11:45 AM',
    '12:00 PM', '12:15 PM', '12:30 PM', '12:45 PM',
    '1:00 PM', '1:15 PM', '1:30 PM', '1:45 PM',
    '2:00 PM', '2:15 PM', '2:30 PM', '2:45 PM',
    '3:00 PM', '3:15 PM', '3:30 PM', '3:45 PM',
    '4:00 PM', '4:15 PM', '4:30 PM', '4:45 PM',
    '5:00 PM'
  ]

  const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 5,
      },
    }),
    useSensor(KeyboardSensor)
  )

  const handleDragStart = (event: DragStartEvent) => {
    setActiveDragId(event.active.id as string)
  }

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event
    setActiveDragId(null)

    if (!over) return

    const bookingId = active.id as string
    const overId = over.id as string

    if (overId.startsWith('day-')) {
      const newDate = over.data.current?.date as Date
      if (newDate) {
        const newDateStr = format(newDate, 'yyyy-MM-dd')
        setBookings(prev => prev.map(b => 
          b.id === bookingId ? { ...b, date: newDateStr } : b
        ))
      }
    } else if (overId.startsWith('slot-')) {
      const newTime = over.data.current?.startTime as string
      const newDate = over.data.current?.date as Date
      if (newTime && newDate) {
        const newDateStr = format(newDate, 'yyyy-MM-dd')
        setBookings(prev => prev.map(b => 
          b.id === bookingId ? { ...b, date: newDateStr, time: newTime } : b
        ))
      }
    }
  }

  const getActiveBooking = () => {
    return bookings.find(b => b.id === activeDragId)
  }

  function isSameWeek(date1: Date, date2: Date): boolean {
    const week1Start = startOfWeek(date1)
    const week2Start = startOfWeek(date2)
    return isSameDay(week1Start, week2Start)
  }

  const renderMonthView = () => {
    const monthStart = startOfMonth(currentDate)
    const monthEnd = endOfMonth(monthStart)
    const calendarStart = startOfWeek(monthStart)
    const calendarEnd = endOfWeek(monthEnd)
    const days = eachDayOfInterval({ start: calendarStart, end: calendarEnd })

    return (
      <div className="grid grid-cols-7 gap-px bg-gray-200">
        {weekDays.map((dayName) => (
          <div key={dayName} className="bg-gray-50 p-3 text-center text-sm font-medium text-gray-700">
            {dayName}
          </div>
        ))}
        {days.map((day) => {
          const isCurrentMonth = isSameMonth(day, currentDate)
          const isToday = isSameDay(day, new Date())
          
          // Get all bookings for this day (including multi-day bookings that span across this day)
          const dayBookingsList = bookings.filter((b: Booking) => {
            const bookingStart = parseISO(b.startDate)
            const bookingEnd = b.endDate ? parseISO(b.endDate) : bookingStart
            return isWithinInterval(day, { start: bookingStart, end: bookingEnd })
          })

          return (
            <DroppableDayCell
              key={day.toISOString()}
              date={day}
              isCurrentMonth={isCurrentMonth}
              isToday={isToday}
            >
              {dayBookingsList.length > 0 && (
                <div className="space-y-1">
                  {dayBookingsList.map((booking: Booking) => {
                    const isFirstDay = isSameDay(parseISO(booking.startDate), day)
                    const isLastDay = booking.endDate ? isSameDay(parseISO(booking.endDate), day) : true
                    
                    return (
                      <ResizableDraggableBooking 
                        key={`${booking.id}-${day.toISOString()}`} 
                        booking={booking}
                        isMultiDay={booking.isMultiDay}
                        isFirstDay={isFirstDay}
                        isLastDay={isLastDay}
                      />
                    )
                  })}
                </div>
              )}
            </DroppableDayCell>
          )
        })}
      </div>
    )
  }

  const renderWeekView = () => {
    const weekStart = startOfWeek(currentDate)
    const weekEnd = endOfWeek(currentDate)
    const days = eachDayOfInterval({ start: weekStart, end: weekEnd })

    return (
      <div className="min-w-[800px]">
        <div className="grid grid-cols-8 gap-px bg-gray-200">
          <div className="bg-gray-50 p-3"></div>
          {days.map((day) => (
            <div key={day.toISOString()} className={`bg-gray-50 p-3 text-center ${isSameDay(day, new Date()) ? 'bg-blue-50' : ''}`}>
              <p className="text-sm font-medium text-gray-900">{format(day, 'EEE')}</p>
              <p className={`text-lg font-bold ${isSameDay(day, new Date()) ? 'text-blue-600' : 'text-gray-700'}`}>
                {format(day, 'd')}
              </p>
            </div>
          ))}

          {timeSlots.map((time) => (
            <>
              <div key={time} className="bg-white p-3 text-sm text-gray-500 font-medium">
                {time}
              </div>
              {days.map((day, dayIndex) => {
                const booking = bookings.find((b: Booking) => {
                  const bDay = parseISO(b.startDate).getDay()
                  return bDay === day.getDay() && b.startTime === time && isSameWeek(parseISO(b.startDate), currentDate)
                })
                
                // Check if there's a multi-day booking spanning this day
                const multiDayBooking = bookings.find((b: Booking) => {
                  if (!b.isMultiDay || !b.endDate) return false
                  const bookingStart = parseISO(b.startDate)
                  const bookingEnd = parseISO(b.endDate)
                  return isWithinInterval(day, { start: bookingStart, end: bookingEnd }) && b.startTime === time
                })
                
                const displayBooking = booking || multiDayBooking
                
                return (
                  <DroppableTimeSlot
                    key={`${time}-${dayIndex}`}
                    time={time}
                    date={day}
                  >
                    {displayBooking && (
                      <ResizableDraggableBooking 
                        booking={displayBooking}
                        isMultiDay={displayBooking.isMultiDay}
                        isFirstDay={isSameDay(parseISO(displayBooking.startDate), day)}
                        isLastDay={displayBooking.endDate ? isSameDay(parseISO(displayBooking.endDate), day) : true}
                      />
                    )}
                  </DroppableTimeSlot>
                )
              })}
            </>
          ))}
        </div>
      </div>
    )
  }

  const renderDayView = () => {
    const dayBookingsList = bookings.filter((b: Booking) => isSameDay(parseISO(b.startDate), currentDate))
    const slotHeight = 60 // pixels per 15-min slot
    
    return (
      <div className="divide-y divide-gray-200">
        {timeSlots.map((time) => {
          const booking = dayBookingsList.find((b: Booking) => b.startTime === time)
          const slotSpan = booking ? Math.ceil(booking.duration / 15) : 1
          const heightPx = slotSpan * slotHeight
          
          return (
            <div key={time} className="flex items-start gap-4 p-3 hover:bg-gray-50" style={{ minHeight: `${slotHeight}px` }}>
              <div className="w-20 text-sm text-gray-500 font-medium pt-1">{time}</div>
              <DroppableTimeSlot
                time={time}
                date={currentDate}
              >
                {booking ? (
                  <ResizableDraggableBooking 
                    booking={{
                      id: booking.id,
                      time: booking.startTime,
                      customer: booking.customerName,
                      pet: booking.petName,
                      service: booking.service,
                      duration: booking.duration,
                      date: booking.startDate,
                    }} 
                    isDayView={true} 
                    style={{ height: `${heightPx - 10}px` }}
                    onResize={(id, newDuration) => {
                      setBookings(prev => prev.map(b => 
                        b.id === id ? { ...b, duration: newDuration } : b
                      ))
                    }}
                    slotHeight={slotHeight}
                  />
                ) : (
                  <div className="flex-1" style={{ minHeight: `${slotHeight - 10}px` }} />
                )}
              </DroppableTimeSlot>
            </div>
          )
        })}
      </div>
    )
  }

  const renderAgendaView = () => {
    return (
      <Table
        columns={[
          { key: 'date', title: 'Date' },
          { key: 'time', title: 'Time' },
          { key: 'customerName', title: 'Customer' },
          { key: 'petName', title: 'Pet' },
          { key: 'service', title: 'Service' },
          { key: 'status', title: 'Status' },
        ]}
        data={bookings.slice(1)}
        keyExtractor={(row) => row.id}
      />
    )
  }

  const getTitle = () => {
    switch (viewMode) {
      case 'month':
        return format(currentDate, 'MMMM yyyy')
      case 'week':
        return `${format(startOfWeek(currentDate), 'MMM d')} - ${format(endOfWeek(currentDate), 'MMM d, yyyy')}`
      case 'day':
        return format(currentDate, 'EEEE, MMM d')
      case 'agenda':
        return `${format(startOfWeek(currentDate), 'MMM d')} - ${format(endOfWeek(currentDate), 'MMM d')}`
    }
  }

  const navigatePrev = () => {
    switch (viewMode) {
      case 'month':
        setCurrentDate(subMonths(currentDate, 1))
        break
      case 'week':
        setCurrentDate(subWeeks(currentDate, 1))
        break
      case 'day':
        setCurrentDate(subDays(currentDate, 1))
        break
      case 'agenda':
        setCurrentDate(subWeeks(currentDate, 1))
        break
    }
  }

  const navigateNext = () => {
    switch (viewMode) {
      case 'month':
        setCurrentDate(addMonths(currentDate, 1))
        break
      case 'week':
        setCurrentDate(addWeeks(currentDate, 1))
        break
      case 'day':
        setCurrentDate(addDays(currentDate, 1))
        break
      case 'agenda':
        setCurrentDate(addWeeks(currentDate, 1))
        break
    }
  }

  return (
    <DndContext
      sensors={sensors}
      collisionDetection={closestCenter}
      onDragStart={handleDragStart}
      onDragEnd={handleDragEnd}
    >
      <div className="space-y-6">
        {/* Header with View Tabs */}
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold text-gray-900">Calendar</h1>
          <div className="flex items-center gap-2">
            <Button variant="secondary" size="sm">
              <Filter className="w-4 h-4 mr-2" />
              Filter
            </Button>
          </div>
        </div>

        {/* View Mode Tabs */}
        <div className="flex items-center justify-between bg-white rounded-lg shadow-sm border border-gray-200 p-2">
          <div className="flex items-center gap-1">
            <Button
              variant={viewMode === 'month' ? 'primary' : 'ghost'}
              size="sm"
              onClick={() => setViewMode('month')}
              className="gap-2"
            >
              <CalendarDays className="w-4 h-4" />
              Month
            </Button>
            <Button
              variant={viewMode === 'week' ? 'primary' : 'ghost'}
              size="sm"
              onClick={() => setViewMode('week')}
              className="gap-2"
            >
              <CalendarDays className="w-4 h-4" />
              Week
            </Button>
            <Button
              variant={viewMode === 'day' ? 'primary' : 'ghost'}
              size="sm"
              onClick={() => setViewMode('day')}
              className="gap-2"
            >
              <Clock className="w-4 h-4" />
              Day
            </Button>
            <Button
              variant={viewMode === 'agenda' ? 'primary' : 'ghost'}
              size="sm"
              onClick={() => setViewMode('agenda')}
              className="gap-2"
            >
              <List className="w-4 h-4" />
              Agenda
            </Button>
          </div>

          <div className="flex items-center gap-2">
            <Button variant="secondary" size="sm" onClick={navigatePrev}>
              <ChevronLeft className="w-4 h-4" />
            </Button>
            <span className="text-lg font-medium min-w-[200px] text-center">
              {getTitle()}
            </span>
            <Button variant="secondary" size="sm" onClick={navigateNext}>
              <ChevronRight className="w-4 h-4" />
            </Button>
          </div>
        </div>

        {/* Search */}
        <div className="flex items-center gap-4">
          <div className="flex-1 relative max-w-md">
            <Search className="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <Input
              placeholder="Search bookings..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="pl-10"
            />
          </div>
        </div>

        {/* Calendar Content */}
        <Card className={viewMode === 'week' ? 'overflow-x-auto' : ''}>
          {viewMode === 'month' && renderMonthView()}
          {viewMode === 'week' && renderWeekView()}
          {viewMode === 'day' && renderDayView()}
          {viewMode === 'agenda' && renderAgendaView()}
        </Card>
      </div>

      <DragOverlay>
        {activeDragId ? (
          <div className="bg-blue-100 text-blue-800 text-xs p-2 rounded shadow-lg opacity-80">
            <div className="flex items-center gap-1">
              <GripVertical className="w-3 h-3 text-blue-600" />
              <p className="font-medium">{getActiveBooking()?.customerName}</p>
            </div>
          </div>
        ) : null}
      </DragOverlay>
    </DndContext>
  )
}
