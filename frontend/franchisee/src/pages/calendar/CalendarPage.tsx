import { useState, useRef, useCallback, useEffect } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Input } from '../../components/ui/Input'
import { Search, Filter, ChevronLeft, ChevronRight, CalendarDays, Clock, List, GripVertical, Calendar } from 'lucide-react'
import { format, startOfMonth, endOfMonth, eachDayOfInterval, isSameMonth, isSameDay, addMonths, subMonths, startOfWeek, endOfWeek, addWeeks, subWeeks, addDays, subDays, parseISO, isWithinInterval, differenceInCalendarDays } from 'date-fns'
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
import { BookingDetailModal } from '../../components/modals/BookingDetailModal'
import { BlockoutDetailModal } from '../../components/modals/BlockoutDetailModal'
import { bookingsApi, blockoutsApi, settingsApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import type { Booking as BookingType, Blockout } from '../../types'
import { PageHeader } from '../../components/layout/PageHeader'
import { useNavigate } from 'react-router-dom'

type ViewMode = 'month' | 'week' | 'day' | 'agenda'

const CALENDAR_START_MINUTES = 0
const CALENDAR_END_MINUTES = (24 * 60) - 1
const TIME_STEP_MINUTES = 15
const SLOT_HEIGHT_PX = 28

// Get time format preference (default: 24-hour format)
const getTimeFormatPreference = (): 'H:i:s' | '12h' => {
  const pref = localStorage.getItem('timeFormat')
  return (pref === '12h' ? '12h' : 'H:i:s')
}

// Convert minutes to H:i:s format (for API)
const minutesToHiS = (totalMinutes: number): string => {
  const safe = Math.max(0, Math.min(23 * 60 + 59, totalMinutes))
  const hour = Math.floor(safe / 60)
  const minute = safe % 60
  const second = 0
  return `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}:${String(second).padStart(2, '0')}`
}

// Convert time to display format based on preference
const toDisplayTime = (totalMinutes: number | string | undefined): string => {
  let minutes: number | null = null
  
  if (typeof totalMinutes === 'number') {
    minutes = totalMinutes
  } else if (typeof totalMinutes === 'string') {
    minutes = toMinutesSinceMidnight(totalMinutes)
  }
  
  if (minutes === null) return ''
  
  const format = getTimeFormatPreference()
  if (format === 'H:i:s') {
    return minutesToHiS(minutes)
  } else {
    // 12-hour format
    const safe = Math.max(0, Math.min(23 * 60 + 59, minutes))
    const hour24 = Math.floor(safe / 60)
    const minute = safe % 60
    const period = hour24 >= 12 ? 'PM' : 'AM'
    const hour12 = hour24 % 12 === 0 ? 12 : hour24 % 12
    return `${hour12}:${String(minute).padStart(2, '0')} ${period}`
  }
}

const toMinutesSinceMidnight = (time: string): number | null => {
  // Accept both 12-hour format (e.g. 9:05 AM) and 24-hour format (e.g. 09:05 or 09:05:00)
  const twelveHourMatch = time.match(/^(\d{1,2}):(\d{2})\s?(AM|PM)$/i)
  if (twelveHourMatch) {
    const rawHour = parseInt(twelveHourMatch[1], 10)
    const minute = parseInt(twelveHourMatch[2], 10)
    const period = twelveHourMatch[3].toUpperCase()

    if (Number.isNaN(rawHour) || Number.isNaN(minute) || rawHour < 1 || rawHour > 12 || minute < 0 || minute > 59) {
      return null
    }

    const hour24 = period === 'PM'
      ? (rawHour % 12) + 12
      : rawHour % 12

    return (hour24 * 60) + minute
  }

  const twentyFourHourMatch = time.match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/)
  if (twentyFourHourMatch) {
    const hour24 = parseInt(twentyFourHourMatch[1], 10)
    const minute = parseInt(twentyFourHourMatch[2], 10)
    if (Number.isNaN(hour24) || Number.isNaN(minute) || hour24 < 0 || hour24 > 23 || minute < 0 || minute > 59) {
      return null
    }
    return (hour24 * 60) + minute
  }

  return null
}

const fromMinutesSinceMidnight = (totalMinutes: number): string => {
  return toDisplayTime(totalMinutes)
}

const buildTimeSlots = (): string[] => {
  const slots: string[] = []

  for (let m = CALENDAR_START_MINUTES; m < 24 * 60; m += TIME_STEP_MINUTES) {
    slots.push(fromMinutesSinceMidnight(m))
  }

  return slots
}

const getEndTimeFromDuration = (startTime: string, duration: number): string => {
  const startMinutes = toMinutesSinceMidnight(startTime)

  if (startMinutes === null) {
    return startTime
  }

  return fromMinutesSinceMidnight(startMinutes + Math.max(TIME_STEP_MINUTES, duration))
}

const getDurationFromStartEnd = (startTime: string, endTime?: string): number | null => {
  if (!endTime) return null

  const startMinutes = toMinutesSinceMidnight(startTime)
  const endMinutes = toMinutesSinceMidnight(endTime)

  if (startMinutes === null || endMinutes === null) return null

  // If end time is earlier than start time, treat it as crossing midnight.
  const delta = endMinutes >= startMinutes
    ? endMinutes - startMinutes
    : (24 * 60 - startMinutes) + endMinutes

  return Math.max(TIME_STEP_MINUTES, delta)
}

const normalizeDisplayTime = (time?: string): string | undefined => {
  if (!time) return undefined
  const minutes = toMinutesSinceMidnight(time)
  return minutes === null ? time : fromMinutesSinceMidnight(minutes)
}

interface Booking {
  id: string
  customerName: string
  petName: string
  service: string
  startDate: string // start date
  endDate?: string // end date for multi-day bookings
  startTime: string
  endTime?: string
  status: string
  duration: number // in minutes (for single day) or days (if multi-day)
  isMultiDay?: boolean
  eventType?: 'booking' | 'blockout'
  bookingId?: string
  blockoutId?: string
  title?: string
  location?: string
  calendarColor?: string
  bookingCost?: string | number
  address?: string
  breed?: string
  pet?: string
}

interface DayBooking {
  id: string
  startTime: string
  endTime?: string
  time?: string
  customer: string
  customerName: string
  pet: string
  petName: string
  service: string
  status: string
  duration: number
  startDate: string
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
  isLastDay = true,
  onClick,
  calendarSettings = {
    showBookingTotal: true,
    showCustomerName: true,
    showCustomerAddress: true,
    showPetName: true,
    showPetBreed: true,
    showServicesName: true,
  }
}: {
  booking: Booking | DayBooking;
  isDayView?: boolean;
  style?: React.CSSProperties;
  onResize?: (id: string, nextStartTime: string, newDuration: number) => void;
  slotHeight?: number;
  isMultiDay?: boolean;
  isFirstDay?: boolean;
  isLastDay?: boolean;
  onClick?: (booking: Booking | DayBooking) => void;
  calendarSettings?: {
    showBookingTotal: boolean;
    showCustomerName: boolean;
    showCustomerAddress: boolean;
    showPetName: boolean;
    showPetBreed: boolean;
    showServicesName: boolean;
  };
}) {
  const { attributes, listeners, setNodeRef, transform, isDragging } = useDraggable({
    id: booking.id,
    data: booking,
  })

  const resolveInitialDuration = useCallback((source: Booking | DayBooking) => {
    const derived = getDurationFromStartEnd(source.startTime, source.endTime)
    if (derived !== null) return derived
    if (source.isMultiDay) return 60
    return Math.max(TIME_STEP_MINUTES, (source as DayBooking).duration || 60)
  }, [])

  const [isResizing, setIsResizing] = useState(false)
  const [currentStartTime, setCurrentStartTime] = useState(booking.startTime)
  const [currentDuration, setCurrentDuration] = useState(resolveInitialDuration(booking))
  const resizeStartY = useRef(0)
  const resizeEdge = useRef<'start' | 'end'>('end')
  const resizeStartTimeMinutes = useRef(0)
  const resizeStartDuration = useRef(0)
  const bookingIdRef = useRef(booking.id)

  useEffect(() => {
    if (bookingIdRef.current !== booking.id) {
      setCurrentStartTime(booking.startTime)
      setCurrentDuration(resolveInitialDuration(booking))
      bookingIdRef.current = booking.id
    }
  }, [booking, resolveInitialDuration])

  const bookingStartMinutes = toMinutesSinceMidnight(booking.startTime)
  const currentStartMinutes = toMinutesSinceMidnight(currentStartTime)
  const liveDurationHeightPx = Math.max(slotHeight, Math.ceil(currentDuration / TIME_STEP_MINUTES) * slotHeight)
  const liveTopOffsetPx = bookingStartMinutes !== null && currentStartMinutes !== null
    ? Math.round((currentStartMinutes - bookingStartMinutes) / TIME_STEP_MINUTES) * slotHeight
    : 0

  const style: React.CSSProperties = {
    ...(transform ? {
      transform: CSS.Translate.toString(transform),
      opacity: isDragging || isResizing ? 0.8 : 1,
      cursor: isResizing ? 'ns-resize' : 'grab',
    } : {
      cursor: isResizing ? 'ns-resize' : 'grab',
    }),
    ...customStyle,
    ...(typeof customStyle?.height !== 'undefined' ? {
      height: `${liveDurationHeightPx - (isDayView ? 10 : 4)}px`,
    } : {}),
    ...(typeof customStyle?.top !== 'undefined' ? {
      top: `${liveTopOffsetPx + 2}px`,
    } : {}),
  }

  const handleResizeStart = useCallback((edge: 'start' | 'end') => (e: React.MouseEvent) => {
    e.preventDefault()
    e.stopPropagation()
    setIsResizing(true)
    resizeEdge.current = edge
    resizeStartY.current = e.clientY
    resizeStartTimeMinutes.current = toMinutesSinceMidnight(currentStartTime) ?? CALENDAR_START_MINUTES
    resizeStartDuration.current = currentDuration
  }, [currentDuration, currentStartTime])

  const handleResizeMove = useCallback((e: MouseEvent) => {
    if (!isResizing) return
    
    const deltaY = e.clientY - resizeStartY.current
    const slotsChanged = Math.round(deltaY / slotHeight)
    const deltaMinutes = slotsChanged * TIME_STEP_MINUTES

    if (resizeEdge.current === 'start') {
      const maxStartMinutes = resizeStartTimeMinutes.current + resizeStartDuration.current - TIME_STEP_MINUTES
      const nextStartMinutes = Math.max(
        CALENDAR_START_MINUTES,
        Math.min(maxStartMinutes, resizeStartTimeMinutes.current + deltaMinutes)
      )
      const nextDuration = Math.max(TIME_STEP_MINUTES, resizeStartDuration.current - (nextStartMinutes - resizeStartTimeMinutes.current))

      setCurrentStartTime(fromMinutesSinceMidnight(nextStartMinutes))
      setCurrentDuration(nextDuration)
      return
    }

    const maxEndMinutes = CALENDAR_END_MINUTES - resizeStartTimeMinutes.current
    const newDuration = Math.max(
      TIME_STEP_MINUTES,
      Math.min(maxEndMinutes, resizeStartDuration.current + deltaMinutes)
    )

    setCurrentDuration(newDuration)
  }, [isResizing, slotHeight])

  const handleResizeEnd = useCallback(() => {
    if (isResizing) {
      setIsResizing(false)
      if (onResize) {
        onResize(booking.id, currentStartTime, currentDuration)
      }
    }
  }, [isResizing, currentDuration, currentStartTime, booking.id, onResize])

  useEffect(() => {
    if (isResizing) {
      document.body.style.cursor = 'ns-resize'
      document.body.style.userSelect = 'none'
      window.addEventListener('mousemove', handleResizeMove)
      window.addEventListener('mouseup', handleResizeEnd)
      return () => {
        document.body.style.cursor = ''
        document.body.style.userSelect = ''
        window.removeEventListener('mousemove', handleResizeMove)
        window.removeEventListener('mouseup', handleResizeEnd)
      }
    }
  }, [isResizing, handleResizeMove, handleResizeEnd])

  if (isDayView) {
    const dayBooking = booking as DayBooking
    const isDayBookingMultiDay = Boolean(dayBooking.isMultiDay)
    const endTime = dayBooking.endTime || (isDayBookingMultiDay
      ? currentStartTime
      : getEndTimeFromDuration(currentStartTime, currentDuration))
    const durationText = isDayBookingMultiDay
      ? `${dayBooking.duration}d`
      : currentDuration >= 60
      ? `${Math.floor(currentDuration / 60)}h ${currentDuration % 60}m`
      : `${currentDuration}m`

    const isBlockout = (booking as any).eventType === 'blockout'
    const bookingColor = (booking as any).calendarColor || (isBlockout ? '#9333ea' : '#3b82f6')

    const bgStyle = { backgroundColor: bookingColor, color: '#ffffff' }
    const borderStyle = { borderLeftColor: '#ffffff' }
    const badgeStyle = { backgroundColor: '#ffffff', color: bookingColor }

    return (
      <div
        ref={setNodeRef}
        {...attributes}
        style={{ ...style, ...bgStyle, ...borderStyle }}
        className="relative flex-1 border-l-4 rounded hover:opacity-90 transition-colors overflow-visible group"
        onClick={(e) => {
          if (!isDragging && !isResizing && onClick) {
            e.stopPropagation()
            onClick(booking)
          }
        }}
      >
        {/* Resize handles - positioned absolutely and independent */}
        <div
          onMouseDown={handleResizeStart('start')}
          className={`absolute top-0 left-0 right-0 h-4 cursor-ns-resize flex items-center justify-center transition-opacity z-20 pointer-events-auto ${isResizing ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'}`}
          style={{ backgroundColor: bookingColor }}
        >
          <div className="w-8 h-1 rounded-full bg-white" />
        </div>

        {/* Main content - draggable area */}
        <div
          {...listeners}
          className="p-3 h-full cursor-grab active:cursor-grabbing"
        >
          <div className="flex items-center justify-between h-full">
            <div className="flex items-center gap-2">
              <GripVertical className="w-4 h-4 text-white/80 flex-shrink-0" />
              <div>
                <p className="font-medium text-white text-sm">{dayBooking.customer || (booking as any).title || 'Blockout'} {dayBooking.pet ? `- ${dayBooking.pet}` : ''}</p>
                <p className="text-xs text-white/90">{`${currentStartTime} - ${endTime}${dayBooking.service ? ` | ${dayBooking.service}` : ''}`}</p>
              </div>
            </div>
            <span className="text-xs px-2 py-1 rounded flex-shrink-0 font-medium" style={badgeStyle}>{durationText}</span>
          </div>
        </div>

        {/* Resize handle */}
        <div
          onMouseDown={handleResizeStart('end')}
          className={`absolute bottom-0 left-0 right-0 h-4 cursor-ns-resize flex items-center justify-center transition-opacity z-20 pointer-events-auto ${isResizing ? 'opacity-100' : 'opacity-70 group-hover:opacity-100'}`}
          style={{ backgroundColor: bookingColor }}
        >
          <div className="w-8 h-1 rounded-full bg-white" />
        </div>
      </div>
    )
  }

  const regularBooking = booking as Booking
  const isBookingMultiDay = isMultiDay || regularBooking.isMultiDay
  const isBlockout = regularBooking.eventType === 'blockout'

  // Get booking color
  const bookingColor = regularBooking.calendarColor || (isBlockout ? '#9333ea' : '#3b82f6')

  const bgStyle = {
    backgroundColor: bookingColor,
    borderColor: bookingColor,
    color: '#ffffff'
  }

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
    <span className="text-[10px] px-1 rounded ml-1 bg-white/20 text-white">
      {isFirstDay && isLastDay ? '' : isFirstDay ? '→' : isLastDay ? '←' : '↔'}
    </span>
  )

  // Display name - for blockouts show title, for bookings show customer name if available
  let displayName = isBlockout ? (regularBooking.title || 'Blockout') : (calendarSettings.showCustomerName ? regularBooking.customerName : 'Booking')

  const displayEndTime = getEndTimeFromDuration(currentStartTime, currentDuration)
  
  // Construct details array similar to preview card
  const detailsArray: string[] = []
  if (isBlockout) {
    // Blockout: show location
    if (regularBooking.location) {
      detailsArray.push(regularBooking.location)
    }
  } else {
    // Booking: show details based on settings (excluding customer name as it's in the title)
    if (calendarSettings.showBookingTotal && (regularBooking as any).bookingCost) {
      detailsArray.push(`$${(regularBooking as any).bookingCost}`)
    }
    if (calendarSettings.showCustomerAddress && (regularBooking as any).address) {
      detailsArray.push((regularBooking as any).address)
    }
    if (calendarSettings.showPetName && (regularBooking as any).pet) {
      detailsArray.push((regularBooking as any).pet)
    }
    if (calendarSettings.showPetBreed && (regularBooking as any).breed) {
      detailsArray.push((regularBooking as any).breed)
    }
    if (calendarSettings.showServicesName && regularBooking.service) {
      detailsArray.push(regularBooking.service)
    }
  }

  return (
    <div
      ref={setNodeRef}
      {...attributes}
      style={{ ...style, ...bgStyle }}
      className={`relative group text-xs ${borderRadiusClass} hover:opacity-80 transition-colors ${isBookingMultiDay || isBlockout ? 'border' : ''}`}
      onClick={(e) => {
        if (!isDragging && onClick) {
          e.stopPropagation()
          onClick(booking)
        }
      }}
    >
      {!isBookingMultiDay && (
        <div
          onMouseDown={handleResizeStart('start')}
          className={`absolute top-0 left-0 right-0 h-3 cursor-ns-resize transition-opacity bg-white/20 z-20 pointer-events-auto ${isResizing ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'}`}
        />
      )}
      <div {...listeners} className="flex items-stretch gap-2 p-2 cursor-grab active:cursor-grabbing h-full">
        <GripVertical className="w-3 h-3 flex-shrink-0 text-white/80 mt-1" />
        <div className="flex-1 min-w-0 space-y-0.5 text-[11px]">
          <p className="font-medium">{displayName} {multiDayIndicator}</p>
          <p className="text-white/80">{currentStartTime} - {displayEndTime}</p>
          {detailsArray.map((detail, idx) => (
            <p key={idx} className="text-white/80 truncate">{detail}</p>
          ))}
          {isBookingMultiDay && regularBooking.endDate && (
            <p className="text-[10px] text-white/80">
              {format(parseISO(regularBooking.startDate), 'MMM d')} - {format(parseISO(regularBooking.endDate), 'MMM d')}
            </p>
          )}
        </div>
      </div>
      {!isBookingMultiDay && (
        <div
          onMouseDown={handleResizeStart('end')}
          className={`absolute bottom-0 left-0 right-0 h-3 cursor-ns-resize transition-opacity bg-white/20 z-20 pointer-events-auto ${isResizing ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'}`}
        />
      )}
    </div>
  )
}

// Droppable Day Cell Component
function DroppableDayCell({ date, children, isCurrentMonth, isToday, onClick }: { 
  date: Date
  children: React.ReactNode
  isCurrentMonth: boolean
  isToday: boolean
  onClick?: (date: Date) => void
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
      onClick={() => onClick?.(date)}
    >
      <div className={`text-sm font-medium mb-1 ${isToday ? 'bg-blue-500 text-white w-7 h-7 rounded-full flex items-center justify-center' : ''}`}>
        {format(date, 'd')}
      </div>
      {children}
    </div>
  )
}

// Droppable Time Slot Component
function DroppableTimeSlot({ time, date, children, onClick, showQuarterHourLine = true }: {
  time: string
  date: Date
  children: React.ReactNode
  onClick?: (date: Date, time: string) => void
  showQuarterHourLine?: boolean
}) {
  const { isOver, setNodeRef } = useDroppable({
    id: `slot-${time}-${date.toISOString()}`,
    data: { time, date },
  })
  const isQuarterHourLine = ((toMinutesSinceMidnight(time) ?? 0) % 15) === 0

  return (
    <div
      ref={setNodeRef}
      className={`relative overflow-visible bg-white p-2 min-h-[40px] transition-colors ${
        showQuarterHourLine && isQuarterHourLine ? 'border-t border-gray-200' : ''
      } ${
        isOver ? 'bg-green-50 ring-2 ring-green-400 ring-inset' : ''
      }`}
      onClick={() => onClick?.(date, time)}
    >
      {children}
    </div>
  )
}

export function CalendarPage() {
  const navigate = useNavigate()
  const { addToast } = useToastStore()
  const [viewMode, setViewMode] = useState<ViewMode>('week')
  const [currentDate, setCurrentDate] = useState(new Date())
  const [searchTerm, setSearchTerm] = useState('')
  const [activeDragId, setActiveDragId] = useState<string | null>(null)
  const [selectedBooking, setSelectedBooking] = useState<BookingType | null>(null)
  const [selectedBlockout, setSelectedBlockout] = useState<Blockout | null>(null)
  const [isBookingModalOpen, setIsBookingModalOpen] = useState(false)
  const [isBlockoutModalOpen, setIsBlockoutModalOpen] = useState(false)
  const [isLoadingDetails, setIsLoadingDetails] = useState(false)
  const [bookings, setBookings] = useState<Booking[]>([])
  const [calendarSettings, setCalendarSettings] = useState({
    showBookingTotal: true,
    showCustomerName: true,
    showCustomerAddress: true,
    showPetName: true,
    showPetBreed: true,
    showServicesName: true,
  })

  // Fetch calendar settings on component mount
  useEffect(() => {
    const fetchCalendarSettings = async () => {
      try {
        const settings = await settingsApi.getCalendarSettings()
        setCalendarSettings({
          showBookingTotal: settings.show_booking_total,
          showCustomerName: settings.show_customer_name,
          showCustomerAddress: settings.show_customer_address,
          showPetName: settings.show_pet_name,
          showPetBreed: settings.show_pet_breed,
          showServicesName: settings.show_services_name,
        })
      } catch (error) {
        console.error('Error fetching calendar settings:', error)
      }
    }
    fetchCalendarSettings()
  }, [])

  useEffect(() => {
    let cancelled = false

    const loadCalendarData = async () => {
      try {
        // Calculate date range based on view mode
        let dateFrom: string
        let dateTo: string

        if (viewMode === 'month') {
          const monthStart = startOfMonth(currentDate)
          const monthEnd = endOfMonth(currentDate)
          dateFrom = format(monthStart, 'yyyy-MM-dd')
          dateTo = format(monthEnd, 'yyyy-MM-dd')
        } else if (viewMode === 'week') {
          const weekStart = startOfWeek(currentDate)
          const weekEnd = endOfWeek(currentDate)
          dateFrom = format(weekStart, 'yyyy-MM-dd')
          dateTo = format(weekEnd, 'yyyy-MM-dd')
        } else {
          // day or agenda view
          dateFrom = format(currentDate, 'yyyy-MM-dd')
          dateTo = format(currentDate, 'yyyy-MM-dd')
        }

        const [bookingPage, blockoutPage] = await Promise.all([
          bookingsApi.getPaginated({ page: 1, per_page: 100, dateFrom, dateTo }),
          blockoutsApi.getPaginated({ page: 1, per_page: 100 }),
        ])

        const bookingEvents: Booking[] = (bookingPage?.data || [])
          .filter((b) => b.status === 'active' || b.status === 'completed')
          .map((b) => {
          const customerName = b.customerName
            || `${b.customer?.first_name || ''} ${b.customer?.last_name || ''}`.trim()
            || 'Booking'
          const petName = b.petName
            || (b.details || []).map((d) => d.pet?.name).filter(Boolean).join(', ')
          const serviceName = (b.details || []).map((d) => d.service?.name).filter(Boolean).join(', ')
          const petBreed = (b.details || []).map((d) => d.pet?.breed).filter(Boolean).join(', ')
          const address = (b.customer as any)?.street_address || (b.customer as any)?.address || ''
          const bookingCost = (b as any)?.total || (b as any)?.cost || ''
          const startTime = normalizeDisplayTime(b.startTime) || b.startTime
          const endTime = normalizeDisplayTime(b.endTime)
          const duration = getDurationFromStartEnd(startTime, endTime)
            ?? Math.max(TIME_STEP_MINUTES, (b as any).duration || TIME_STEP_MINUTES)

          return {
            id: `booking-${b.id}`,
            customerName,
            petName: petName || '',
            service: serviceName || '',
            startDate: b.startDate,
            endDate: b.endDate,
            startTime,
            endTime,
            status: b.status || 'active',
            duration,
            isMultiDay: b.isMultiDay,
            eventType: 'booking',
            bookingId: b.id,
            calendarColor: b.calendarColor,
            bookingCost,
            address,
            breed: petBreed,
            pet: petName || '',
          }
        })

        const blockoutEvents: Booking[] = (blockoutPage?.data || [])
          .filter((b) => {
            const bDate = new Date(b.startDate)
            const fromDate = new Date(dateFrom)
            const toDate = new Date(dateTo)
            return bDate >= fromDate && bDate <= toDate
          })
          .map((b) => {
          const startTime = normalizeDisplayTime(b.startTime) || b.startTime
          const endTime = normalizeDisplayTime(b.endTime)
          const duration = getDurationFromStartEnd(startTime, endTime)
            ?? Math.max(TIME_STEP_MINUTES, TIME_STEP_MINUTES)

          return {
            id: `blockout-${b.id}`,
            customerName: b.title || 'Blockout',
            petName: '',
            service: '',
            title: b.title,
            location: b.location,
            startDate: b.startDate,
            endDate: b.endDate,
            startTime,
            endTime,
            status: b.active ? 'active' : 'cancelled',
            duration,
            eventType: 'blockout',
            blockoutId: b.id,
            calendarColor: '#9333ea',
          }
        })

        if (!cancelled) {
          setBookings([...bookingEvents, ...blockoutEvents])
        }
      } catch (error) {
        console.error('Error loading calendar data:', error)
        if (!cancelled) {
          addToast('Failed to load calendar data', 'error')
        }
      }
    }

    loadCalendarData()

    return () => {
      cancelled = true
    }
  }, [currentDate, viewMode, addToast])

  const timeSlots = buildTimeSlots()

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

  const handleDragEnd = async (event: DragEndEvent) => {
    const { active, over } = event
    setActiveDragId(null)

    if (!over) return

    const bookingId = active.id as string
    const overId = over.id as string
    const movedBooking = bookings.find((b) => b.id === bookingId)

    if (!movedBooking) return

    let newDateStr = movedBooking.startDate
    let newTime = movedBooking.startTime
    let newEndDate = movedBooking.endDate

    if (overId.startsWith('day-')) {
      const newDate = over.data.current?.date as Date
      if (newDate) {
        newDateStr = format(newDate, 'yyyy-MM-dd')
        newEndDate =
          movedBooking.isMultiDay && movedBooking.endDate
            ? format(addDays(newDate, differenceInCalendarDays(parseISO(movedBooking.endDate), parseISO(movedBooking.startDate))), 'yyyy-MM-dd')
            : movedBooking.endDate
      }
    } else if (overId.startsWith('slot-')) {
      const slotTime = over.data.current?.time as string
      const newDate = over.data.current?.date as Date
      if (slotTime && newDate) {
        newDateStr = format(newDate, 'yyyy-MM-dd')
        const baseMinutes = toMinutesSinceMidnight(movedBooking.startTime) ?? toMinutesSinceMidnight(slotTime)
        const minuteDelta = Math.round(event.delta.y / SLOT_HEIGHT_PX) * TIME_STEP_MINUTES
        const snappedMinutes = baseMinutes !== null
          ? Math.max(CALENDAR_START_MINUTES, Math.min(CALENDAR_END_MINUTES, baseMinutes + minuteDelta))
          : null
        newTime = snappedMinutes !== null ? fromMinutesSinceMidnight(snappedMinutes) : slotTime
        newEndDate =
          movedBooking.isMultiDay && movedBooking.endDate
            ? format(addDays(newDate, differenceInCalendarDays(parseISO(movedBooking.endDate), parseISO(movedBooking.startDate))), 'yyyy-MM-dd')
            : movedBooking.endDate
      }
    }

    // Update local state optimistically
    setBookings(prev => prev.map(b =>
      b.id === bookingId
        ? {
            ...b,
            startDate: newDateStr,
            startTime: newTime,
            endTime: b.isMultiDay
              ? b.endTime
              : getEndTimeFromDuration(newTime, b.duration),
            endDate: newEndDate,
          }
        : b
    ))

    // Save to API
    try {
      // Convert time to H:i:s format for API
      const apiTime = toMinutesSinceMidnight(newTime)
      const apiStartTime = apiTime !== null ? minutesToHiS(apiTime) : newTime
      
      if (movedBooking.eventType === 'blockout' && movedBooking.blockoutId) {
        await blockoutsApi.update(movedBooking.blockoutId, {
          start_date: newDateStr,
          start_time: apiStartTime,
          end_date: newEndDate,
        } as any)
      } else if (movedBooking.bookingId) {
        await bookingsApi.update(movedBooking.bookingId, {
          startDate: newDateStr,
          startTime: apiStartTime,
          endDate: newEndDate,
        } as any)
      }
      addToast('Changes saved successfully', 'success')
    } catch (error) {
      console.error('Error saving booking/blockout:', error)
      addToast('Failed to save changes', 'error')
      // Revert local state on error
      setBookings(prev => prev.map(b =>
        b.id === bookingId
          ? {
              ...b,
              startDate: movedBooking.startDate,
              startTime: movedBooking.startTime,
              endTime: movedBooking.endTime,
              endDate: movedBooking.endDate,
            }
          : b
      ))
    }
  }

  const handleBookingResize = async (id: string, nextStartTime: string, newDuration: number) => {
    const booking = bookings.find(b => b.id === id)
    if (!booking) return

    // Update local state optimistically
    setBookings(prev => prev.map(b =>
      b.id === id
        ? {
            ...b,
            startTime: nextStartTime,
            duration: newDuration,
            endTime: getEndTimeFromDuration(nextStartTime, newDuration),
          }
        : b
    ))

    // Save to API
    try {
      // Convert time to H:i:s format for API
      const apiTime = toMinutesSinceMidnight(nextStartTime)
      const apiStartTime = apiTime !== null ? minutesToHiS(apiTime) : nextStartTime
      
      if (booking.eventType === 'blockout' && booking.blockoutId) {
        await blockoutsApi.update(booking.blockoutId, {
          start_time: apiStartTime,
          duration: newDuration,
        } as any)
      } else if (booking.bookingId) {
        await bookingsApi.update(booking.bookingId, {
          startTime: apiStartTime,
          duration: newDuration,
        } as any)
      }
      addToast('Changes saved successfully', 'success')
    } catch (error) {
      console.error('Error saving booking/blockout:', error)
      addToast('Failed to save changes', 'error')
      // Revert local state on error
      setBookings(prev => prev.map(b =>
        b.id === id
          ? {
              ...b,
              startTime: booking.startTime,
              duration: booking.duration,
              endTime: booking.endTime,
            }
          : b
      ))
    }
  }

  const navigateToNewBooking = (date: Date, time?: string) => {
    const params = new URLSearchParams({
      date: format(date, 'yyyy-MM-dd'),
    })

    if (time) {
      params.set('time', time)
    }

    navigate(`/bookings/new?${params.toString()}`)
  }

  const handleCalendarItemClick = async (item: Booking) => {
    if (isLoadingDetails) return // Prevent multiple clicks

    try {
      setIsLoadingDetails(true)

      if (item.eventType === 'blockout' && item.blockoutId) {
        // Fetch full blockout data from API
        const blockoutData = await blockoutsApi.getById(item.blockoutId)
        setSelectedBlockout(blockoutData)
        setIsBlockoutModalOpen(true)
      } else if (item.bookingId) {
        // Fetch full booking data from API
        const bookingData = await bookingsApi.getById(item.bookingId)
        setSelectedBooking(bookingData)
        setIsBookingModalOpen(true)
      }
    } catch (error) {
      console.error('Error fetching calendar item details:', error)
      addToast('Could not load details', 'error')
    } finally {
      setIsLoadingDetails(false)
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
              onClick={(clickedDate) => navigateToNewBooking(clickedDate)}
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
                        onResize={handleBookingResize}
                        onClick={handleCalendarItemClick}
                        calendarSettings={calendarSettings}
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
    const slotHeight = SLOT_HEIGHT_PX

    return (
      <div className="min-w-[800px]">
        <div className="grid grid-cols-8 gap-x-px gap-y-0 bg-gray-200">
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
            <div key={`week-row-${time}`} className="contents">
              <div
                className={`bg-white px-3 text-sm text-gray-500 font-medium flex items-start ${
                  ((toMinutesSinceMidnight(time) ?? 0) % 15) === 0 ? 'border-t border-gray-200' : ''
                }`}
                style={{ minHeight: `${slotHeight}px` }}
              >
                {(toMinutesSinceMidnight(time) ?? 0) % 15 === 0 ? time : ''}
              </div>
              {days.map((day, dayIndex) => {
                const booking = bookings.find((b: Booking) => {
                  const bDay = parseISO(b.startDate).getDay()
                  return bDay === day.getDay() && b.startTime === time && isSameWeek(parseISO(b.startDate), currentDate)
                })
                
                // Check if there's a multi-day booking spanning this day
                const multiDayBooking = bookings.find((b: Booking) => {
                  if (!b.isMultiDay || !b.endDate) return false
                  try {
                    const bookingStart = parseISO(b.startDate)
                    const bookingEnd = parseISO(b.endDate)
                    // Validate dates before using isWithinInterval
                    if (isNaN(bookingStart.getTime()) || isNaN(bookingEnd.getTime())) return false
                    if (bookingStart > bookingEnd) return false
                    return isWithinInterval(day, { start: bookingStart, end: bookingEnd }) && b.startTime === time
                  } catch {
                    return false
                  }
                })
                
                const displayBooking = booking || multiDayBooking
                const shouldSpanDuration = Boolean(displayBooking && !displayBooking.isMultiDay)
                const displayDurationMinutes = displayBooking
                  ? (displayBooking.isMultiDay
                    ? TIME_STEP_MINUTES
                    : (getDurationFromStartEnd(displayBooking.startTime, displayBooking.endTime)
                      ?? Math.max(TIME_STEP_MINUTES, displayBooking.duration)))
                  : TIME_STEP_MINUTES
                const bookingHeightPx = displayBooking && shouldSpanDuration
                  ? Math.max(slotHeight, Math.ceil(displayDurationMinutes / TIME_STEP_MINUTES) * slotHeight)
                  : slotHeight
                
                return (
                  <DroppableTimeSlot
                    key={`${time}-${dayIndex}`}
                    time={time}
                    date={day}
                    onClick={(clickedDate, clickedTime) => navigateToNewBooking(clickedDate, clickedTime)}
                  >
                    {displayBooking && (
                      <ResizableDraggableBooking
                        booking={displayBooking}
                        style={shouldSpanDuration ? {
                          position: 'absolute',
                          top: '2px',
                          left: '2px',
                          right: '2px',
                          height: `${bookingHeightPx - 4}px`,
                          zIndex: 20,
                        } : {
                          position: 'relative',
                          zIndex: 20,
                        }}
                        isMultiDay={displayBooking.isMultiDay}
                        isFirstDay={isSameDay(parseISO(displayBooking.startDate), day)}
                        isLastDay={displayBooking.endDate ? isSameDay(parseISO(displayBooking.endDate), day) : true}
                        onResize={handleBookingResize}
                        onClick={handleCalendarItemClick}
                        calendarSettings={calendarSettings}
                      />
                    )}
                  </DroppableTimeSlot>
                )
              })}
            </div>
          ))}
        </div>
      </div>
    )
  }

  const renderDayView = () => {
    const dayBookingsList = bookings.filter((b: Booking) => isSameDay(parseISO(b.startDate), currentDate))
    const slotHeight = SLOT_HEIGHT_PX // pixels per 5-min slot
    
    return (
      <div>
        {timeSlots.map((time) => {
          const booking = dayBookingsList.find((b: Booking) => b.startTime === time)
          const bookingDurationMinutes = booking
            ? (booking.isMultiDay
              ? TIME_STEP_MINUTES
              : (getDurationFromStartEnd(booking.startTime, booking.endTime) ?? Math.max(TIME_STEP_MINUTES, booking.duration)))
            : TIME_STEP_MINUTES
          const bookingHeightPx = Math.max(slotHeight, Math.ceil(bookingDurationMinutes / TIME_STEP_MINUTES) * slotHeight)
          
          return (
            <div
              key={time}
              className={`flex items-start gap-4 p-3 hover:bg-gray-50 ${
                ((toMinutesSinceMidnight(time) ?? 0) % 15) === 0 ? 'border-t border-gray-200' : ''
              }`}
              style={{ minHeight: `${slotHeight}px` }}
            >
              <div className="w-20 text-sm text-gray-500 font-medium pt-1">{(toMinutesSinceMidnight(time) ?? 0) % 15 === 0 ? time : ''}</div>
              <DroppableTimeSlot
                time={time}
                date={currentDate}
                showQuarterHourLine={false}
                onClick={(clickedDate, clickedTime) => navigateToNewBooking(clickedDate, clickedTime)}
              >
                {booking ? (
                  <ResizableDraggableBooking
                    booking={{
                      id: booking.id,
                      startTime: booking.startTime,
                      endTime: booking.endTime,
                      time: booking.startTime,
                      customer: booking.customerName,
                      customerName: booking.customerName,
                      pet: booking.petName,
                      petName: booking.petName,
                      service: booking.service,
                      status: booking.status,
                      duration: booking.duration,
                      startDate: booking.startDate,
                      isMultiDay: booking.isMultiDay,
                    }}
                    isDayView={true}
                    style={{
                      position: 'absolute',
                      top: '2px',
                      left: '2px',
                      right: '2px',
                      height: `${bookingHeightPx - 4}px`,
                      zIndex: 20,
                    }}
                    onResize={booking.isMultiDay ? undefined : handleBookingResize}
                    slotHeight={slotHeight}
                    onClick={() => handleCalendarItemClick(booking)}
                    calendarSettings={calendarSettings}
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
        <PageHeader
          title="Calendar"
          description="Manage your bookings and schedule"
          icon={<Calendar className="w-5 h-5" />}
          actions={
            <Button variant="secondary" size="sm" className="flex-shrink-0">
              <Filter className="w-4 h-4 mr-2" />
              Filter
            </Button>
          }
        />

        {/* View Mode Tabs & Navigation */}
        <div className="flex items-center justify-between bg-white rounded-lg shadow-sm border border-gray-200 p-2">
          <div className="flex items-center gap-2">
            <Button variant="secondary" size="sm" onClick={() => setCurrentDate(new Date())}>
              Today
            </Button>
            <Button variant="secondary" size="sm" onClick={navigatePrev} className="gap-1">
              <ChevronLeft className="w-4 h-4" />
              Back
            </Button>
            <Button variant="secondary" size="sm" onClick={navigateNext} className="gap-1">
              Next
              <ChevronRight className="w-4 h-4" />
            </Button>
            <span className="text-lg font-medium min-w-[200px] text-left pl-2">
              {getTitle()}
            </span>
          </div>

          <div className="flex items-center justify-end gap-1">
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

      {/* Booking Detail Modal */}
      <BookingDetailModal
        isOpen={isBookingModalOpen}
        onClose={() => {
          setIsBookingModalOpen(false)
          setSelectedBooking(null)
        }}
        booking={selectedBooking}
      />

      {/* Blockout Detail Modal */}
      <BlockoutDetailModal
        isOpen={isBlockoutModalOpen}
        onClose={() => {
          setIsBlockoutModalOpen(false)
          setSelectedBlockout(null)
        }}
        blockout={selectedBlockout}
      />
    </DndContext>
  )
}
