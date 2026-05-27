import { useState, useEffect } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { ChevronLeft, ChevronRight, CalendarDays } from 'lucide-react'
import { format, startOfMonth, endOfMonth, eachDayOfInterval, isSameMonth, isSameDay, addMonths, subMonths, startOfWeek, endOfWeek, parseISO, isWithinInterval } from 'date-fns'
import { PageHeader } from '../../components/layout/PageHeader'
import { bookingsApi, blockoutsApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'

interface CalendarItem {
  id: string
  customerName: string
  petName: string
  service: string
  startDate: string
  endDate?: string
  startTime: string
  status: string
  eventType: 'booking' | 'blockout'
  calendarColor?: string
}

export function CalendarMonthViewPage() {
  const [currentMonth, setCurrentMonth] = useState(new Date())
  const [calendarItems, setCalendarItems] = useState<CalendarItem[]>([])
  const [loading, setLoading] = useState(false)
  const addToast = useToastStore((s) => s.addToast)

  const monthStart = startOfMonth(currentMonth)
  const monthEnd = endOfMonth(monthStart)
  const calendarStart = startOfWeek(monthStart)
  const calendarEnd = endOfWeek(monthEnd)
  const days = eachDayOfInterval({ start: calendarStart, end: calendarEnd })

  const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

  useEffect(() => {
    const loadCalendarItems = async () => {
      setLoading(true)
      try {
        const dateFrom = format(monthStart, 'yyyy-MM-dd')
        const dateTo = format(monthEnd, 'yyyy-MM-dd')

        const [bookingPage, blockoutPage] = await Promise.all([
          bookingsApi.getPaginated({ page: 1, per_page: 100, dateFrom, dateTo }),
          blockoutsApi.getPaginated({ page: 1, per_page: 100 }),
        ])

        const bookingItems: CalendarItem[] = (bookingPage?.data || [])
          .filter((b) => b.status === 'active' || b.status === 'completed')
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
              endDate: b.endDate,
              startTime: b.startTime,
              status: b.status || 'active',
              eventType: 'booking' as const,
              calendarColor: b.calendarColor,
            }
          })

        const blockoutItems: CalendarItem[] = (blockoutPage?.data || [])
          .filter((b) => {
            const bDate = new Date(b.startDate)
            const fromDate = new Date(dateFrom)
            const toDate = new Date(dateTo)
            return bDate >= fromDate && bDate <= toDate
          })
          .map((b) => ({
            id: `blockout-${b.id}`,
            customerName: b.title || 'Blockout',
            petName: '',
            service: '',
            startDate: b.startDate,
            endDate: b.endDate,
            startTime: b.startTime,
            status: b.active ? 'active' : 'cancelled',
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
  }, [currentMonth, addToast])

  const getItemsForDay = (day: Date) => {
    return calendarItems.filter((item) => {
      const itemStartDate = parseISO(item.startDate)
      const itemEndDate = item.endDate ? parseISO(item.endDate) : itemStartDate
      return isWithinInterval(day, { start: itemStartDate, end: itemEndDate }) || isSameDay(itemStartDate, day)
    })
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Calendar - Month View"
        description="View monthly schedule overview"
        icon={<CalendarDays className="w-5 h-5" />}
        actions={
          <div className="flex items-center gap-2">
            <Button variant="secondary" size="sm" onClick={() => setCurrentMonth(subMonths(currentMonth, 1))}>
              <ChevronLeft className="w-4 h-4" />
            </Button>
            <span className="text-sm font-medium min-w-[120px] text-center">
              {format(currentMonth, 'MMMM yyyy')}
            </span>
            <Button variant="secondary" size="sm" onClick={() => setCurrentMonth(addMonths(currentMonth, 1))}>
              <ChevronRight className="w-4 h-4" />
            </Button>
          </div>
        }
      />

      <Card>
        {loading ? (
          <div className="p-8 text-center text-gray-500">Loading...</div>
        ) : (
          <div className="grid grid-cols-7 gap-px bg-gray-200">
            {weekDays.map((day) => (
              <div key={day} className="bg-gray-50 p-3 text-center text-sm font-medium text-gray-700">
                {day}
              </div>
            ))}
            {days.map((day) => {
              const isCurrentMonth = isSameMonth(day, currentMonth)
              const isToday = isSameDay(day, new Date())
              const dayItems = getItemsForDay(day)

              return (
                <div
                  key={day.toISOString()}
                  className={`bg-white min-h-[100px] p-2 ${!isCurrentMonth ? 'text-gray-400' : ''}`}
                >
                  <div className={`text-sm font-medium mb-1 ${isToday ? 'bg-blue-500 text-white w-7 h-7 rounded-full flex items-center justify-center' : ''}`}>
                    {format(day, 'd')}
                  </div>
                  <div className="space-y-1">
                    {dayItems.slice(0, 3).map((item) => (
                      <div
                        key={item.id}
                        className={`text-xs px-2 py-1 rounded truncate ${
                          item.eventType === 'blockout'
                            ? 'bg-purple-100 text-purple-700'
                            : 'bg-blue-100 text-blue-700'
                        }`}
                        style={item.calendarColor ? { backgroundColor: `${item.calendarColor}20`, color: item.calendarColor } : undefined}
                        title={`${item.customerName}${item.petName ? ` - ${item.petName}` : ''}${item.service ? ` (${item.service})` : ''}`}
                      >
                        {item.customerName}
                      </div>
                    ))}
                    {dayItems.length > 3 && (
                      <div className="text-xs text-gray-500">
                        +{dayItems.length - 3} more
                      </div>
                    )}
                  </div>
                </div>
              )
            })}
          </div>
        )}
      </Card>
    </div>
  )
}
