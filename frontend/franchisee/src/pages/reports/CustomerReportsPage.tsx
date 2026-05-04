import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Input } from '../../components/ui/Input'
import { Search, Filter, Users } from 'lucide-react'

export function CustomerReportsPage() {
  const [searchTerm, setSearchTerm] = useState('')

  const reportData = [
    { id: '1', customerName: 'John Doe', totalBookings: 15, totalSpent: 975, lastVisit: '2026-04-02', avgRating: 4.8 },
    { id: '2', customerName: 'Jane Smith', totalBookings: 8, totalSpent: 520, lastVisit: '2026-03-28', avgRating: 4.9 },
    { id: '3', customerName: 'Mike Johnson', totalBookings: 22, totalSpent: 1430, lastVisit: '2026-04-01', avgRating: 4.7 },
  ]

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Customer Reports</h1>
        <Button variant="secondary" size="sm">
          <Filter className="w-4 h-4 mr-2" />
          Filter
        </Button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card className="p-4">
          <p className="text-sm text-gray-500">Total Customers</p>
          <p className="text-2xl font-bold">156</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Active Customers</p>
          <p className="text-2xl font-bold text-green-600">142</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">New This Month</p>
          <p className="text-2xl font-bold text-blue-600">12</p>
        </Card>
      </div>

      <Card>
        <div className="p-4 border-b border-gray-200">
          <div className="flex items-center gap-4">
            <div className="flex-1 relative">
              <Search className="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <Input
                placeholder="Search customers..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10"
              />
            </div>
          </div>
        </div>

        <Table
          columns={[
            { key: 'customerName', title: 'Customer Name' },
            { key: 'totalBookings', title: 'Total Bookings' },
            { key: 'totalSpent', title: 'Total Spent ($)' },
            { key: 'lastVisit', title: 'Last Visit' },
            { key: 'avgRating', title: 'Avg Rating' },
          ]}
          data={reportData}
          keyExtractor={(row) => row.id}
        />
      </Card>
    </div>
  )
}
