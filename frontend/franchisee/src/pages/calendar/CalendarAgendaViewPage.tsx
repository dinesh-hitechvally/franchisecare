import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { ChevronLeft, ChevronRight, List } from 'lucide-react'
import { format, addWeeks, subWeeks, startOfWeek, endOfWeek } from 'date-fns'

export function CalendarAgendaViewPage() {
  const [currentWeek, setCurrentWeek] = useState(new Date())

  const weekStart = startOfWeek(currentWeek)
  const weekEnd = endOfWeek(currentWeek)

  const agendaItems = [
    { id: '1', date: '2026-04-02', time: '9:00 AM', customer: 'John Doe', pet: 'Max', service: 'Full Groom', status: 'Confirmed' },
    { id: '2', date: '2026-04-02', time: '10:30 AM', customer: 'Jane Smith', pet: 'Bella', service: 'Bath & Dry', status: 'Confirmed' },
    { id: '3', date: '2026-04-02', time: '2:00 PM', customer: 'Mike Johnson', pet: 'Charlie', service: 'Full Groom', status: 'Pending' },
    { id: '4', date: '2026-04-03', time: '9:00 AM', customer: 'Sarah Williams', pet: 'Luna', service: 'Nail Trim', status: 'Confirmed' },
  ]

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Calendar - Agenda View</h1>
        <div className="flex items-center gap-2">
          <Button variant="secondary" size="sm" onClick={() => setCurrentWeek(subWeeks(currentWeek, 1))}>
            <ChevronLeft className="w-4 h-4" />
          </Button>
          <span className="text-lg font-medium min-w-[200px] text-center">
            {format(weekStart, 'MMM d')} - {format(weekEnd, 'MMM d')}
          </span>
          <Button variant="secondary" size="sm" onClick={() => setCurrentWeek(addWeeks(currentWeek, 1))}>
            <ChevronRight className="w-4 h-4" />
          </Button>
        </div>
      </div>

      <Card>
        <Table
          columns={[
            { key: 'date', title: 'Date' },
            { key: 'time', title: 'Time' },
            { key: 'customer', title: 'Customer' },
            { key: 'pet', title: 'Pet' },
            { key: 'service', title: 'Service' },
            { key: 'status', title: 'Status' },
          ]}
          data={agendaItems}
          keyExtractor={(row) => row.id}
        />
      </Card>
    </div>
  )
}
