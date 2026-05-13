import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { format, startOfWeek, endOfWeek, addWeeks, subWeeks, eachDayOfInterval, isSameDay } from 'date-fns'

export function CalendarWeekViewPage() {
  const [currentWeek, setCurrentWeek] = useState(new Date())

  const weekStart = startOfWeek(currentWeek)
  const weekEnd = endOfWeek(currentWeek)
  const days = eachDayOfInterval({ start: weekStart, end: weekEnd })

  const timeSlots = ['9:00 AM', '10:00 AM', '11:00 AM', '12:00 PM', '1:00 PM', '2:00 PM', '3:00 PM', '4:00 PM', '5:00 PM']

  const weekBookings = [
    { day: 1, time: '10:00 AM', customer: 'John Doe', service: 'Full Groom' },
    { day: 2, time: '11:00 AM', customer: 'Jane Smith', service: 'Bath & Dry' },
    { day: 3, time: '2:00 PM', customer: 'Mike Johnson', service: 'Full Groom' },
  ]

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-4">
        <Card className="px-4 py-3 shadow-sm border-gray-200 flex-1">
          <h1 className="text-xl font-bold text-gray-800">Calendar - Week View</h1>
        </Card>
        <div className="flex items-center gap-2 bg-white px-4 py-3 rounded-md border border-gray-200 shadow-sm">
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
      </div>

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
                {days.map((day, dayIndex) => {
                  const booking = weekBookings.find(b => b.day === dayIndex && b.startTime === time)
                  return (
                    <div key={`${time}-${dayIndex}`} className="bg-white p-2 min-h-[60px] border-t border-gray-100">
                      {booking && (
                        <div className="bg-blue-100 text-blue-800 text-xs p-2 rounded">
                          <p className="font-medium">{booking.customer}</p>
                          <p>{booking.service}</p>
                        </div>
                      )}
                    </div>
                  )
                })}
              </>
            ))}
          </div>
        </div>
      </Card>
    </div>
  )
}
