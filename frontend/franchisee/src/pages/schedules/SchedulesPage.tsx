import { Card } from '../../components/ui/Card'
import { CalendarDays } from 'lucide-react'
import { format, addDays, startOfWeek } from 'date-fns'

export function SchedulesPage() {
  const weekStart = startOfWeek(new Date(), { weekStartsOn: 1 })
  const weekDays = [...Array(7)].map((_, i) => addDays(weekStart, i))

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-secondary-900">Schedules</h1>
      
      <Card className="p-6">
        <div className="flex items-center gap-4 mb-6">
          <CalendarDays className="w-6 h-6 text-primary-600" />
          <div>
            <p className="text-sm text-secondary-500">Current Week</p>
            <p className="font-medium">{format(weekStart, 'MMMM d')} - {format(addDays(weekStart, 6), 'MMMM d, yyyy')}</p>
          </div>
        </div>
        
        <div className="grid grid-cols-7 gap-2">
          {weekDays.map((day) => (
            <div key={day.toISOString()} className="border rounded-lg p-3 text-center">
              <p className="text-sm font-medium text-secondary-500">{format(day, 'EEE')}</p>
              <p className="text-lg font-bold text-secondary-900">{format(day, 'd')}</p>
              <div className="mt-2 space-y-1">
                <div className="text-xs bg-primary-100 text-primary-700 rounded px-2 py-1">
                  9:00 AM - Available
                </div>
                <div className="text-xs bg-green-100 text-green-700 rounded px-2 py-1">
                  2:00 PM - Booked
                </div>
              </div>
            </div>
          ))}
        </div>
      </Card>
      
      <Card title="Staff Schedule" className="p-6">
        <div className="space-y-4">
          {['John Smith', 'Jane Doe', 'Mike Johnson'].map((staff) => (
            <div key={staff} className="flex items-center justify-between border-b pb-3 last:border-0">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                  <span className="text-sm font-medium text-primary-700">{staff.charAt(0)}</span>
                </div>
                <div>
                  <p className="font-medium">{staff}</p>
                  <p className="text-sm text-secondary-500">Groomer</p>
                </div>
              </div>
              <div className="text-sm text-secondary-600">
                Mon-Fri: 9:00 AM - 6:00 PM
              </div>
            </div>
          ))}
        </div>
      </Card>
    </div>
  )
}
