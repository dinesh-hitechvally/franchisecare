import { useState, useMemo } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { ChevronLeft, ChevronRight, CalendarDays, Loader2 } from 'lucide-react'
import { format, startOfWeek, endOfWeek, addWeeks, subWeeks, eachDayOfInterval, isSameDay, parseISO } from 'date-fns'
import { PageHeader } from '../../components/layout/PageHeader'
import { calendarApi } from '../../api/services'
import { useAuth } from '../../contexts/AuthContext'

interface CalendarEvent {
  id: string
  title?: string
  start_date: string
  start_time?: string
  end_time?: string
  customer?: { first_name?: string; last_name?: string; name?: string }
  service?: { name?: string }
  status?: string
  color?: string
}

export function CalendarWeekViewPage() {
  const { user } = useAuth()
  const companyId = user?.company_id || ''
  const [currentWeek, setCurrentWeek] = useState(new Date())

  const weekStart = startOfWeek(currentWeek)
  const weekEnd = endOfWeek(currentWeek)
  const days = eachDayOfInterval({ start: weekStart, end: weekEnd })

  const { data: events = [], isLoading } = useQuery({
    queryKey: ['calendar-events', companyId, format(weekStart, 'yyyy-MM-dd'), format(weekEnd, 'yyyy-MM-dd')],
    queryFn: () => calendarApi.getEvents({
      company_id: companyId,
      start_date: format(weekStart, 'yyyy-MM-dd'),
      end_date: format(weekEnd, 'yyyy-MM-dd'),
    }),
    enabled: !!companyId,
  })

  const timeSlots = ['9:00 AM', '10:00 AM', '11:00 AM', '12:00 PM', '1:00 PM', '2:00 PM', '3:00 PM', '4:00 PM', '5:00 PM']

  // Group events by day and time
  const eventsByDayAndTime = useMemo(() => {
    const grouped: Record<string, Record<string, CalendarEvent[]>> = {}
    days.forEach(day => {
      const dayKey = format(day, 'yyyy-MM-dd')
      grouped[dayKey] = {}
      timeSlots.forEach(slot => {
        grouped[dayKey][slot] = []
      })
    })

    events.forEach((event: CalendarEvent) => {
      const eventDate = event.start_date
      const eventTime = event.start_time
      if (eventDate && grouped[eventDate]) {
        // Find matching time slot
        const hour = eventTime ? parseInt(eventTime.split(':')[0]) : 9
        const ampm = hour >= 12 ? 'PM' : 'AM'
        const hour12 = hour > 12 ? hour - 12 : (hour === 0 ? 12 : hour)
        const slotKey = `${hour12}:00 ${ampm}`
        if (grouped[eventDate][slotKey]) {
          grouped[eventDate][slotKey].push(event)
        }
      }
    })
    return grouped
  }, [events, days])

  const getCustomerName = (event: CalendarEvent) => {
    if (event.customer?.name) return event.customer.name
    if (event.customer?.first_name) return `${event.customer.first_name} ${event.customer.last_name || ''}`.trim()
    return event.title || 'Unknown'
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Calendar - Week View"
        description="View weekly schedule and appointments"
        icon={<CalendarDays className="w-5 h-5" />}
        actions={
          <div className="flex items-center gap-2">
            <Button variant="secondary" size="sm" onClick={() => setCurrentWeek(subWeeks(currentWeek, 1))}>
              <ChevronLeft className="w-4 h-4" />
            </Button>
            <span className="text-sm font-medium min-w-[160px] text-center">
              {format(weekStart, 'MMM d')} - {format(weekEnd, 'MMM d, yyyy')}
            </span>
            <Button variant="secondary" size="sm" onClick={() => setCurrentWeek(addWeeks(currentWeek, 1))}>
              <ChevronRight className="w-4 h-4" />
            </Button>
          </div>
        }
      />

      {isLoading ? (
        <Card className="p-8">
          <div className="flex items-center justify-center gap-2 text-gray-500">
            <Loader2 className="w-5 h-5 animate-spin" />
            <span>Loading calendar events...</span>
          </div>
        </Card>
      ) : (
      <Card className="overflow-x-auto">
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
                {days.map((day) => {
                  const dayKey = format(day, 'yyyy-MM-dd')
                  const dayEvents = eventsByDayAndTime[dayKey]?.[time] || []
                  return (
                    <div key={`${time}-${dayKey}`} className="bg-white p-2 min-h-[60px] border-t border-gray-100">
                      {dayEvents.map(event => (
                        <div 
                          key={event.id} 
                          className="bg-blue-100 text-blue-800 text-xs p-2 rounded mb-1"
                          style={event.color ? { backgroundColor: `${event.color}20`, color: event.color } : {}}
                        >
                          <p className="font-medium">{getCustomerName(event)}</p>
                          <p>{event.service?.name || '-'}</p>
                        </div>
                      ))}
                    </div>
                  )
                })}
              </>
            ))}
          </div>
        </div>
      </Card>
      )}
    </div>
  )
}
