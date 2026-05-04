import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Input } from '../../components/ui/Input'
import { Search, Filter, DollarSign } from 'lucide-react'

export function IncomeReportsPage() {
  const [searchTerm, setSearchTerm] = useState('')

  const reportData = [
    { id: '1', date: '2026-04-02', serviceRevenue: 650, productSales: 125, other: 50, total: 825 },
    { id: '2', date: '2026-04-01', serviceRevenue: 585, productSales: 200, other: 0, total: 785 },
    { id: '3', date: '2026-03-31', serviceRevenue: 455, productSales: 75, other: 25, total: 555 },
  ]

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Income Reports</h1>
        <Button variant="secondary" size="sm">
          <Filter className="w-4 h-4 mr-2" />
          Filter
        </Button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <p className="text-sm text-gray-500">Service Revenue</p>
          <p className="text-2xl font-bold">$1,690</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Product Sales</p>
          <p className="text-2xl font-bold">$400</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Other Income</p>
          <p className="text-2xl font-bold">$75</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Total Income</p>
          <p className="text-2xl font-bold text-green-600">$2,165</p>
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
            { key: 'serviceRevenue', title: 'Service Revenue ($)' },
            { key: 'productSales', title: 'Product Sales ($)' },
            { key: 'other', title: 'Other ($)' },
            { key: 'total', title: 'Total ($)' },
          ]}
          data={reportData}
          keyExtractor={(row) => row.id}
        />
      </Card>
    </div>
  )
}
