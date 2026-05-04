import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Input } from '../../components/ui/Input'
import { Search, Filter, BarChart3 } from 'lucide-react'

export function InventoryUsagePage() {
  const [searchTerm, setSearchTerm] = useState('')

  const usageData = [
    { id: '1', itemName: 'Premium Shampoo', usedQty: 12, unit: 'bottles', period: 'March 2026', remaining: 12 },
    { id: '2', itemName: 'Dog Treats', usedQty: 25, unit: 'packs', period: 'March 2026', remaining: 25 },
    { id: '3', itemName: 'Grooming Clippers', usedQty: 2, unit: 'pieces', period: 'March 2026', remaining: 3 },
  ]

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Inventory Usage</h1>
        <div className="flex gap-2">
          <Button variant="secondary" size="sm">
            <Filter className="w-4 h-4 mr-2" />
            Filter
          </Button>
        </div>
      </div>

      <Card>
        <div className="p-4 border-b border-gray-200">
          <div className="flex items-center gap-4">
            <div className="flex-1 relative">
              <Search className="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <Input
                placeholder="Search items..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10"
              />
            </div>
          </div>
        </div>

        <Table
          columns={[
            { key: 'itemName', title: 'Item Name' },
            { key: 'usedQty', title: 'Used Quantity' },
            { key: 'unit', title: 'Unit' },
            { key: 'period', title: 'Period' },
            { key: 'remaining', title: 'Remaining' },
          ]}
          data={usageData}
          keyExtractor={(row) => row.id}
        />
      </Card>
    </div>
  )
}
