import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Input } from '../../components/ui/Input'
import { Search, Filter } from 'lucide-react'

export function ExpenseReportsPage() {
  const [searchTerm, setSearchTerm] = useState('')

  const reportData = [
    { id: '1', date: '2026-04-02', supplies: 85, utilities: 0, marketing: 0, wages: 0, other: 25, total: 110 },
    { id: '2', date: '2026-04-01', supplies: 0, utilities: 245, marketing: 0, wages: 0, other: 0, total: 245 },
    { id: '3', date: '2026-03-31', supplies: 50, utilities: 0, marketing: 120, wages: 0, other: 10, total: 180 },
  ]

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Expense Reports</h1>
        <Button variant="secondary" size="sm">
          <Filter className="w-4 h-4 mr-2" />
          Filter
        </Button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <p className="text-sm text-gray-500">Supplies</p>
          <p className="text-2xl font-bold">$135</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Utilities</p>
          <p className="text-2xl font-bold">$245</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Marketing</p>
          <p className="text-2xl font-bold">$120</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Total Expenses</p>
          <p className="text-2xl font-bold text-red-600">$535</p>
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
            { key: 'supplies', title: 'Supplies ($)' },
            { key: 'utilities', title: 'Utilities ($)' },
            { key: 'marketing', title: 'Marketing ($)' },
            { key: 'wages', title: 'Wages ($)' },
            { key: 'total', title: 'Total ($)' },
          ]}
          data={reportData}
          keyExtractor={(row) => row.id}
        />
      </Card>
    </div>
  )
}
