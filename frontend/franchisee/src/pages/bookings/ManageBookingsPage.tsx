import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Input } from '../../components/ui/Input'
import { Search, Printer, FileSpreadsheet, Mail, MessageSquare, FileText, Filter, Plus, Edit, Eye, Trash2 } from 'lucide-react'

export function ManageBookingsPage() {
  const [searchTerm, setSearchTerm] = useState('')

  const bookings = [
    { id: '1', customerName: 'John Doe', petName: 'Max', service: 'Full Groom', date: '2026-04-02', time: '10:00 AM', status: 'Confirmed', groomer: 'Sarah Smith', total: '$65.00' },
    { id: '2', customerName: 'Jane Smith', petName: 'Bella', service: 'Bath & Dry', date: '2026-04-02', time: '11:30 AM', status: 'Pending', groomer: 'Mike Johnson', total: '$45.00' },
    { id: '3', customerName: 'Mike Johnson', petName: 'Charlie', service: 'Full Groom', date: '2026-04-02', time: '2:00 PM', status: 'Confirmed', groomer: 'Sarah Smith', total: '$65.00' },
  ]

  const renderGroomerCell = (groomer: string) => (
    <div className="flex items-center gap-2">
      <img 
        src={`https://ui-avatars.com/api/?name=${encodeURIComponent(groomer)}&background=3b82f6&color=fff&size=24`}
        alt={groomer}
        className="w-6 h-6 rounded-full"
      />
      <span>{groomer}</span>
    </div>
  )

  const renderStatusCell = (status: string) => (
    <span className={`px-2 py-1 rounded text-xs font-medium ${
      status === 'Confirmed' ? 'bg-green-100 text-green-700' :
      status === 'Pending' ? 'bg-yellow-100 text-yellow-700' :
      'bg-gray-100 text-gray-700'
    }`}>
      {status}
    </span>
  )

  const renderActionsCell = () => (
    <div className="flex items-center gap-2">
      <button className="p-1 text-blue-600 hover:bg-blue-50 rounded">
        <Eye className="w-4 h-4" />
      </button>
      <button className="p-1 text-green-600 hover:bg-green-50 rounded">
        <Edit className="w-4 h-4" />
      </button>
      <button className="p-1 text-red-600 hover:bg-red-50 rounded">
        <Trash2 className="w-4 h-4" />
      </button>
    </div>
  )

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Manage Bookings</h1>
        <div className="flex gap-2 flex-wrap">
          <Button variant="secondary" size="sm">
            <Printer className="w-4 h-4 mr-2" />
            Print
          </Button>
          <Button variant="secondary" size="sm">
            <FileSpreadsheet className="w-4 h-4 mr-2" />
            Excel
          </Button>
          <Button variant="secondary" size="sm">
            <Mail className="w-4 h-4 mr-2" />
            Email
          </Button>
          <Button variant="secondary" size="sm">
            <MessageSquare className="w-4 h-4 mr-2" />
            SMS
          </Button>
          <Button variant="secondary" size="sm">
            <FileText className="w-4 h-4 mr-2" />
            PDF
          </Button>
          <Button variant="secondary" size="sm">
            <Filter className="w-4 h-4 mr-2" />
            Filter
          </Button>
          <Button size="sm">
            <Plus className="w-4 h-4 mr-2" />
            Add Booking
          </Button>
        </div>
      </div>

      <Card>
        <div className="p-4 border-b border-gray-200">
          <div className="flex items-center gap-4">
            <div className="flex-1 relative max-w-md">
              <Search className="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <Input
                placeholder="Search bookings..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10"
              />
            </div>
          </div>
        </div>

        <Table
          columns={[
            { key: 'customerName', title: 'Customer' },
            { key: 'petName', title: 'Pet' },
            { key: 'service', title: 'Service' },
            { key: 'date', title: 'Date' },
            { key: 'time', title: 'Time' },
            { key: 'status', title: 'Status', render: renderStatusCell },
            { key: 'groomer', title: 'Groomer', render: renderGroomerCell },
            { key: 'total', title: 'Total' },
            { key: 'actions', title: 'Actions', render: renderActionsCell },
          ]}
          data={bookings}
          keyExtractor={(row) => row.id}
        />
      </Card>
    </div>
  )
}
