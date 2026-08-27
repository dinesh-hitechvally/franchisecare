import { useState, useEffect } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { ChevronLeft, ChevronRight, Clock } from 'lucide-react'
import { format, addDays, subDays, parseISO, isSameDay } from 'date-fns'
import { PageHeader } from '../../components/layout/PageHeader'
import { bookingsApi, blockoutsApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'

interface CalendarItem {
  id: string
  customerName: string
  petName: string
  service: string
  startDate: string
  startTime: string
  endTime?: string
  status: string
  duration: number
  eventType: 'booking' | 'blockout'
  calendarColor?: string
}

// Convert time string to display format
const normalizeTime = (time: string): string => {
  if (!time) return ''
  // Handle H:i:s format
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

// Calculate duration from start and end times
const calculateDuration = (startTime: string, endTime?: string): number => {
  if (!endTime) return 60
  const startMatch = startTime.match(/^(\d{1,2}):(\d{2})/)
  const endMatch = endTime.match(/^(\d{1,2}):(\d{2})/)
  if (startMatch && endMatch) {
    const startMinutes = parseInt(startMatch[1], 10) * 60 + parseInt(startMatch[2], 10)
    const endMinutes = parseInt(endMatch[1], 10) * 60 + parseInt(endMatch[2], 10)
    return Math.max(30, endMinutes - startMinutes)
  }
  return 60
}

export function CalendarDayViewPage() {
  const [currentDate, setCurrentDate] = useState(new Date())
  const [calendarItems, setCalendarItems] = useState<CalendarItem[]>([])
  const [loading, setLoading] = useState(false)
  const addToast = useToastStore((s) => s.addToast)

  const timeSlots = [
    '9:00 AM', '9:30 AM', '10:00 AM', '10:30 AM', '11:00 AM', '11:30 AM',
    '12:00 PM', '12:30 PM', '1:00 PM', '1:30 PM', '2:00 PM', '2:30 PM',
    '3:00 PM', '3:30 PM', '4:00 PM', '4:30 PM', '5:00 PM'
  ]

  useEffect(() => {
    const loadCalendarItems = async () => {
      setLoading(true)
      try {
        const dateFrom = format(currentDate, 'yyyy-MM-dd')
        const dateTo = dateFrom

        const [bookingPage, blockoutPage] = await Promise.all([
          bookingsApi.getPaginated({ page: 1, per_page: 100, dateFrom, dateTo }),
          blockoutsApi.getPaginated({ page: 1, per_page: 100 }),
        ])

        const bookingItems: CalendarItem[] = (bookingPage?.data || [])
          .filter((b) => b.status === 'ACTIVE' || b.status === 'COMPLETED')
          .map((b) => {
            const customerName = b.customerName
              || `${b.customer?.first_name || ''} ${b.customer?.last_name || ''}`.trim()
              || 'Booking'
            const petName = (b.details || []).map((d: any) => d.pet?.name).filter(Boolean).join(', ')
            const serviceName = (b.details || []).map((d: any) => d.service?.name).filter(Boolean).join(', ')

            return {
              id: `booking-${b.id}`,
              customerName,
              petName: petName || '',
              service: serviceName || '',
              startDate: b.startDate,
              startTime: b.startTime,
              endTime: b.endTime,
              status: b.status || 'ACTIVE',
              duration: calculateDuration(b.startTime, b.endTime),
              eventType: 'booking' as const,
              calendarColor: b.calendarColor,
            }
          })

        const blockoutItems: CalendarItem[] = (blockoutPage?.data || [])
          .filter((b) => isSameDay(parseISO(b.startDate), currentDate))
          .map((b) => ({
            id: `blockout-${b.id}`,
            customerName: b.title || 'Blockout',
            petName: '',
            service: b.location || '',
            startDate: b.startDate,
            startTime: b.startTime,
            endTime: b.endTime,
            status: b.active ? 'ACTIVE' : 'CANCELLED',
            duration: calculateDuration(b.startTime, b.endTime),
            eventType: 'blockout' as const,
            calendarColor: '#9333ea',
          }))

        setCalendarItems([...bookingItems, ...blockoutItems])
      } catch (error) {
        console.error('Error loading calendar data:', error)
        addToast('Failed to load calendar data', 'error')
      } finally {
        setLoading(false)
      }
    }

    loadCalendarItems()
  }, [currentDate, addToast])

  const getItemForTimeSlot = (time: string) => {
    return calendarItems.find((item) => normalizeTime(item.startTime) === time)
  }

  const formatDuration = (minutes: number): string => {
    if (minutes >= 60) {
      const hours = Math.floor(minutes / 60)
      const mins = minutes % 60
      return mins > 0 ? `${hours} hr ${mins} min` : `${hours} hr`
    }
    return `${minutes} min`
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Calendar - Day View"
        description="View daily schedule and appointments"
        icon={<Clock className="w-5 h-5" />}
        actions={
          <div className="flex items-center gap-2">
            <Button variant="secondary" size="sm" onClick={() => setCurrentDate(subDays(currentDate, 1))}>
              <ChevronLeft className="w-4 h-4" />
            </Button>
            <span className="text-sm font-medium min-w-[120px] text-center">
              {format(currentDate, 'EEEE, MMM d')}
            </span>
            <Button variant="secondary" size="sm" onClick={() => setCurrentDate(addDays(currentDate, 1))}>
              <ChevronRight className="w-4 h-4" />
            </Button>
          </div>
        }
      />

      <Card>
        {loading ? (
          <div className="p-8 text-center text-gray-500">Loading...</div>
        ) : (
          <div className="divide-y divide-gray-200">
            {timeSlots.map((time) => {
              const item = getItemForTimeSlot(time)
              return (
                <div key={time} className="flex items-center gap-4 p-3 hover:bg-gray-50">
                  <div className="w-20 text-sm text-gray-500 font-medium">{time}</div>
                  {item ? (
                    <div
                      className={`flex-1 border-l-4 p-3 rounded ${
                        item.eventType === 'blockout'
                          ? 'bg-purple-50 border-purple-500'
                          : 'bg-blue-50 border-blue-500'
                      }`}
                      style={item.calendarColor ? { borderColor: item.calendarColor, backgroundColor: `${item.calendarColor}10` } : undefined}
                    >
                      <div className="flex items-center justify-between">
                        <div>
                          <p className="font-medium text-gray-900">
                            {item.customerName}{item.petName ? ` - ${item.petName}` : ''}
                          </p>
                          {item.service && (
                            <p className="text-sm text-gray-500">{item.service}</p>
                          )}
                        </div>
                        <span className={`text-xs px-2 py-1 rounded ${
                          item.eventType === 'blockout'
                            ? 'bg-purple-100 text-purple-700'
                            : 'bg-blue-100 text-blue-700'
                        }`}>
                          {formatDuration(item.duration)}
                        </span>
                      </div>
                    </div>
                  ) : (
                    <div className="flex-1" />
                  )}
                </div>
              )
            })}
          </div>
        )}
      </Card>
    </div>
  )
}
