import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { ChevronLeft, ChevronRight, Clock } from 'lucide-react'
import { format, addDays, subDays, startOfDay } from 'date-fns'

export function CalendarDayViewPage() {
  const [currentDate, setCurrentDate] = useState(new Date())

  const timeSlots = [
    '9:00 AM', '9:30 AM', '10:00 AM', '10:30 AM', '11:00 AM', '11:30 AM',
    '12:00 PM', '12:30 PM', '1:00 PM', '1:30 PM', '2:00 PM', '2:30 PM',
    '3:00 PM', '3:30 PM', '4:00 PM', '4:30 PM', '5:00 PM'
  ]

  const dayBookings = [
    { id: '1', time: '10:00 AM', customer: 'John Doe', pet: 'Max', service: 'Full Groom', duration: '1.5 hrs' },
    { id: '2', time: '11:30 AM', customer: 'Jane Smith', pet: 'Bella', service: 'Bath & Dry', duration: '1 hr' },
    { id: '3', time: '2:00 PM', customer: 'Mike Johnson', pet: 'Charlie', service: 'Full Groom', duration: '1.5 hrs' },
  ]

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-4">
        <Card className="px-4 py-3 shadow-sm border-gray-200 flex-1">
          <h1 className="text-xl font-bold text-gray-800">Calendar - Day View</h1>
        </Card>
        <div className="flex items-center gap-2 bg-white px-4 py-3 rounded-md border border-gray-200 shadow-sm">
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
      </div>

      <Card>
        <div className="divide-y divide-gray-200">
          {timeSlots.map((time) => {
            const booking = dayBookings.find(b => b.startTime === time)
            return (
              <div key={time} className="flex items-center gap-4 p-3 hover:bg-gray-50">
                <div className="w-20 text-sm text-gray-500 font-medium">{time}</div>
                {booking ? (
                  <div className="flex-1 bg-blue-50 border-l-4 border-blue-500 p-3 rounded">
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="font-medium text-gray-900">{booking.customer} - {booking.pet}</p>
                        <p className="text-sm text-gray-500">{booking.service}</p>
                      </div>
                      <span className="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">{booking.duration}</span>
                    </div>
                  </div>
                ) : (
                  <div className="flex-1" />
                )}
              </div>
            )
          })}
        </div>
      </Card>
    </div>
  )
}
