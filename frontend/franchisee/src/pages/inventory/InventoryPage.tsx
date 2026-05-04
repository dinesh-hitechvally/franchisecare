import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import type { InventoryItem } from '../../types'
import { Plus, Package, AlertTriangle } from 'lucide-react'

export function InventoryPage() {
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [filterCategory, setFilterCategory] = useState('')

  const { data: items, isLoading } = useQuery({
    queryKey: ['inventory-items', filterCategory],
    queryFn: async () => {
      // Mock data
      return [
        { id: '1', name: 'Dog Shampoo - Premium', category: 'shampoo', sku: 'SHMP-001', quantity: 25, minStock: 10, unitPrice: 15.99, franchise_id: '1' },
        { id: '2', name: 'Treats - Chicken Flavor', category: 'treats', sku: 'TRT-001', quantity: 8, minStock: 15, unitPrice: 8.99, franchise_id: '1' },
        { id: '3', name: 'Uniform - Staff Shirt', category: 'uniforms', sku: 'UNI-001', quantity: 12, minStock: 5, unitPrice: 24.99, franchise_id: '1' },
        { id: '4', name: 'Printer Paper A4', category: 'office', sku: 'OFF-001', quantity: 50, minStock: 20, unitPrice: 5.99, franchise_id: '1' },
        { id: '5', name: 'Brochures - Service Menu', category: 'marketing', sku: 'MKT-001', quantity: 100, minStock: 50, unitPrice: 0.5, franchise_id: '1' },
      ] as InventoryItem[]
    },
  })

  const categoryColors = {
    office: 'bg-gray-100 text-gray-700',
    shampoo: 'bg-blue-100 text-blue-700',
    treats: 'bg-green-100 text-green-700',
    uniforms: 'bg-purple-100 text-purple-700',
    marketing: 'bg-orange-100 text-orange-700',
  }

  const categoryLabels = {
    office: 'Office Supplies',
    shampoo: 'Shampoo',
    treats: 'Treats',
    uniforms: 'Uniforms',
    marketing: 'Marketing',
  }

  const filteredItems = filterCategory
    ? items?.filter((item) => item.category === filterCategory)
    : items

  const lowStockItems = items?.filter((item) => item.quantity <= item.minStock)

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-secondary-900">Inventory</h1>
        <Button onClick={() => setIsModalOpen(true)} size="sm">
          <Plus className="w-4 h-4 mr-2" />
          Add Item
        </Button>
      </div>

      {/* Low Stock Alert */}
      {lowStockItems && lowStockItems.length > 0 && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
          <div className="flex items-start gap-3">
            <AlertTriangle className="w-5 h-5 text-red-600 mt-0.5" />
            <div>
              <h3 className="font-medium text-red-800">Low Stock Alert</h3>
              <p className="text-sm text-red-700 mt-1">
                {lowStockItems.length} item(s) are below minimum stock level.
              </p>
            </div>
          </div>
        </div>
      )}

      {/* Category Summary */}
      <div className="grid grid-cols-5 gap-4">
        {Object.entries(categoryLabels).map(([key, label]) => {
          const count = items?.filter((i) => i.category === key).length ?? 0
          return (
            <Card key={key} className="p-4 cursor-pointer" onClick={() => setFilterCategory(filterCategory === key ? '' : key)}>
              <div className="flex items-center gap-3">
                <div className={`w-10 h-10 rounded-lg ${categoryColors[key as keyof typeof categoryColors]} flex items-center justify-center`}>
                  <Package className="w-5 h-5" />
                </div>
                <div>
                  <p className="text-sm text-secondary-500">{label}</p>
                  <p className="text-xl font-bold text-secondary-900">{count}</p>
                </div>
              </div>
            </Card>
          )
        })}
      </div>

      <Card>
        <div className="flex items-center gap-4 mb-6">
          <select
            value={filterCategory}
            onChange={(e) => setFilterCategory(e.target.value)}
            className="input w-48"
          >
            <option value="">All Categories</option>
            {Object.entries(categoryLabels).map(([key, label]) => (
              <option key={key} value={key}>{label}</option>
            ))}
          </select>
        </div>

        <Table
          columns={[
            { key: 'sku', title: 'SKU' },
            { key: 'name', title: 'Item Name' },
            {
              key: 'category',
              title: 'Category',
              render: (row: InventoryItem) => (
                <span className={`px-2 py-1 rounded-full text-xs font-medium ${categoryColors[row.category]}`}>
                  {categoryLabels[row.category]}
                </span>
              ),
            },
            { key: 'quantity', title: 'Stock' },
            { key: 'minStock', title: 'Min Stock' },
            { key: 'unitPrice', title: 'Unit Price', render: (row: InventoryItem) => `$${row.unitPrice.toFixed(2)}` },
            {
              key: 'status',
              title: 'Status',
              render: (row: InventoryItem) => (
                row.quantity <= row.minStock ? (
                  <span className="text-red-600 text-sm font-medium">Low Stock</span>
                ) : (
                  <span className="text-green-600 text-sm font-medium">OK</span>
                )
              ),
            },
            {
              key: 'actions',
              title: 'Actions',
              render: () => (
                <Button variant="ghost" size="sm">Edit</Button>
              ),
            },
          ]}
          data={filteredItems ?? []}
          keyExtractor={(row) => row.id}
          isLoading={isLoading}
        />
      </Card>

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title="Add Inventory Item"
        footer={
          <>
            <Button variant="secondary" onClick={() => setIsModalOpen(false)}>
              Cancel
            </Button>
            <Button onClick={() => setIsModalOpen(false)}>Create Item</Button>
          </>
        }
      >
        <div className="space-y-4">
          <Input label="SKU" />
          <Input label="Item Name" />
          <div>
            <label className="text-sm font-medium text-secondary-700">Category</label>
            <select className="input mt-1">
              {Object.entries(categoryLabels).map(([key, label]) => (
                <option key={key} value={key}>{label}</option>
              ))}
            </select>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <Input label="Current Stock" type="number" />
            <Input label="Min Stock Level" type="number" />
          </div>
          <Input label="Unit Price" type="number" step="0.01" />
        </div>
      </Modal>
    </div>
  )
}
