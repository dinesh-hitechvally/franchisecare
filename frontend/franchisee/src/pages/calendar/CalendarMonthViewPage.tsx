import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { ChevronLeft, ChevronRight, CalendarDays } from 'lucide-react'
import { format, startOfMonth, endOfMonth, eachDayOfInterval, isSameMonth, isSameDay, addMonths, subMonths, startOfWeek, endOfWeek } from 'date-fns'
import { PageHeader } from '../../components/layout/PageHeader'

export function CalendarMonthViewPage() {
  const [currentMonth, setCurrentMonth] = useState(new Date())

  const monthStart = startOfMonth(currentMonth)
  const monthEnd = endOfMonth(monthStart)
  const calendarStart = startOfWeek(monthStart)
  const calendarEnd = endOfWeek(monthEnd)
  const days = eachDayOfInterval({ start: calendarStart, end: calendarEnd })

  const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

  const bookings = [
    { date: new Date(), count: 3 },
    { date: addMonths(new Date(), 0), count: 5 },
  ]

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
        <div className="grid grid-cols-7 gap-px bg-gray-200">
          {weekDays.map((day) => (
            <div key={day} className="bg-gray-50 p-3 text-center text-sm font-medium text-gray-700">
              {day}
            </div>
          ))}
          {days.map((day) => {
            const isCurrentMonth = isSameMonth(day, currentMonth)
            const isToday = isSameDay(day, new Date())
            const dayBookings = bookings.find(b => isSameDay(b.date, day))

            return (
              <div
                key={day.toISOString()}
                className={`bg-white min-h-[100px] p-2 ${!isCurrentMonth ? 'text-gray-400' : ''}`}
              >
                <div className={`text-sm font-medium mb-1 ${isToday ? 'bg-blue-500 text-white w-7 h-7 rounded-full flex items-center justify-center' : ''}`}>
                  {format(day, 'd')}
                </div>
                {dayBookings && (
                  <div className="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">
                    {dayBookings.count} bookings
                  </div>
                )}
              </div>
            )
          })}
        </div>
      </Card>
    </div>
  )
}
