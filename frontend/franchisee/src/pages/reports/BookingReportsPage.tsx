import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Input } from '../../components/ui/Input'
import { Search, Filter, BarChart3 } from 'lucide-react'

export function BookingReportsPage() {
  const [searchTerm, setSearchTerm] = useState('')

  const reportData = [
    { id: '1', date: '2026-04-02', totalBookings: 8, completed: 6, cancelled: 1, noShow: 1, revenue: 420 },
    { id: '2', date: '2026-04-01', totalBookings: 10, completed: 9, cancelled: 0, noShow: 1, revenue: 585 },
    { id: '3', date: '2026-03-31', totalBookings: 7, completed: 7, cancelled: 0, noShow: 0, revenue: 455 },
  ]

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Booking Reports</h1>
        <Button variant="secondary" size="sm">
          <Filter className="w-4 h-4 mr-2" />
          Filter
        </Button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <p className="text-sm text-gray-500">Total Bookings</p>
          <p className="text-2xl font-bold">25</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Completed</p>
          <p className="text-2xl font-bold text-green-600">22</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Cancelled</p>
          <p className="text-2xl font-bold text-red-600">1</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Revenue</p>
          <p className="text-2xl font-bold">$1,460</p>
        </Card>
      </div>

      <Card>
        <div className="p-4 border-b border-gray-200">
          <div className="flex items-center gap-4">
            <div className="flex-1 relative">
              <Search className="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <Input
                placeholder="Search reports..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10"
              />
            </div>
          </div>
        </div>

        <Table
          columns={[
            { key: 'date', title: 'Date' },
            { key: 'totalBookings', title: 'Total Bookings' },
            { key: 'completed', title: 'Completed' },
            { key: 'cancelled', title: 'Cancelled' },
            { key: 'noShow', title: 'No Show' },
            { key: 'revenue', title: 'Revenue ($)' },
          ]}
          data={reportData}
          keyExtractor={(row) => row.id}
        />
      </Card>
    </div>
  )
}
