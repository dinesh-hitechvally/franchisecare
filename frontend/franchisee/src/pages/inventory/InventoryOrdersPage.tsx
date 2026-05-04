import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { inventoryApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import type { InventoryOrder } from '../../types'
import { Plus, Package, CheckCircle, Truck, Clock } from 'lucide-react'
import { format } from 'date-fns'

export function InventoryOrdersPage() {
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [filterStatus, setFilterStatus] = useState('')

  const { data: orders, isLoading } = useQuery({
    queryKey: ['inventory-orders', filterStatus],
    queryFn: async () => {
      // Mock data
      return [
        { id: '1', type: 'shampoo', items: [{ itemId: '1', quantity: 10, unitCost: 12 }], status: 'ordered', totalCost: 120, franchise_id: '1', createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() },
        { id: '2', type: 'treats', items: [{ itemId: '2', quantity: 50, unitCost: 6 }], status: 'pending', totalCost: 300, franchise_id: '1', createdAt: new Date(Date.now() - 86400000).toISOString(), updatedAt: new Date(Date.now() - 86400000).toISOString() },
        { id: '3', type: 'office', items: [{ itemId: '4', quantity: 20, unitCost: 4 }], status: 'completed', totalCost: 80, franchise_id: '1', createdAt: new Date(Date.now() - 172800000).toISOString(), updatedAt: new Date(Date.now() - 172800000).toISOString() },
      ] as InventoryOrder[]
    },
  })

  const statusMutation = useMutation({
    mutationFn: ({ id, status }: { id: string; status: InventoryOrder['status'] }) =>
      inventoryApi.updateOrderStatus(id, status),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inventory-orders'] })
      addToast('Order status updated', 'success')
    },
  })

  const typeLabels = {
    office: 'Office Supplies',
    shampoo: 'Shampoo',
    treats: 'Treats',
    uniforms: 'Uniforms',
    marketing: 'Marketing',
  }

  const statusConfig = {
    pending: { label: 'Pending', color: 'bg-yellow-100 text-yellow-700', icon: Clock },
    ordered: { label: 'Ordered', color: 'bg-blue-100 text-blue-700', icon: Package },
    received: { label: 'Received', color: 'bg-purple-100 text-purple-700', icon: Truck },
    completed: { label: 'Completed', color: 'bg-green-100 text-green-700', icon: CheckCircle },
  }

  const filteredOrders = filterStatus
    ? orders?.filter((o) => o.status === filterStatus)
    : orders

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-secondary-900">Inventory Orders</h1>
        <Button onClick={() => setIsModalOpen(true)} size="sm">
          <Plus className="w-4 h-4 mr-2" />
          Create Order
        </Button>
      </div>

      <Card>
        <div className="flex items-center gap-4 mb-6">
          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="input w-48"
          >
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="ordered">Ordered</option>
            <option value="received">Received</option>
            <option value="completed">Completed</option>
          </select>
        </div>

        <Table
          columns={[
            { key: 'id', title: 'Order ID', render: (row: InventoryOrder) => `#${row.id}` },
            { key: 'type', title: 'Type', render: (row: InventoryOrder) => typeLabels[row.type] },
            { key: 'items', title: 'Items', render: (row: InventoryOrder) => `${row.items.length} item(s)` },
            { key: 'totalCost', title: 'Total', render: (row: InventoryOrder) => `$${row.totalCost.toFixed(2)}` },
            { key: 'createdAt', title: 'Created', render: (row: InventoryOrder) => format(new Date(row.created_at), 'MMM d, yyyy') },
            {
              key: 'status',
              title: 'Status',
              render: (row: InventoryOrder) => {
                const config = statusConfig[row.status]
                return (
                  <span className={`px-2 py-1 rounded-full text-xs font-medium ${config.color}`}>
                    {config.label}
                  </span>
                )
              },
            },
            {
              key: 'actions',
              title: 'Actions',
              render: (row: InventoryOrder) => (
                <div className="flex gap-1">
                  {row.status === 'pending' && (
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => statusMutation.mutate({ id: row.id, status: 'ordered' })}
                    >
                      Mark Ordered
                    </Button>
                  )}
                  {row.status === 'ordered' && (
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => statusMutation.mutate({ id: row.id, status: 'received' })}
                    >
                      Mark Received
                    </Button>
                  )}
                  {row.status === 'received' && (
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => statusMutation.mutate({ id: row.id, status: 'completed' })}
                    >
                      Complete
                    </Button>
                  )}
                </div>
              ),
            },
          ]}
          data={filteredOrders ?? []}
          keyExtractor={(row) => row.id}
          isLoading={isLoading}
        />
      </Card>

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title="Create Inventory Order"
        size="lg"
        footer={
          <>
            <Button variant="secondary" onClick={() => setIsModalOpen(false)}>
              Cancel
            </Button>
            <Button onClick={() => setIsModalOpen(false)}>Create Order</Button>
          </>
        }
      >
        <div className="space-y-4">
          <div>
            <label className="text-sm font-medium text-secondary-700">Order Type</label>
            <select className="input mt-1">
              {Object.entries(typeLabels).map(([key, label]) => (
                <option key={key} value={key}>{label}</option>
              ))}
            </select>
          </div>
          <div>
            <label className="text-sm font-medium text-secondary-700">Items</label>
            <div className="mt-2 space-y-2">
              <div className="flex items-center gap-2 p-2 border rounded-lg">
                <span className="flex-1">Dog Shampoo - Premium</span>
                <input type="number" className="input w-24" placeholder="Qty" />
                <button className="text-red-500 hover:text-red-700">×</button>
              </div>
              <Button variant="secondary" size="sm">+ Add Item</Button>
            </div>
          </div>
          <div>
            <label className="text-sm font-medium text-secondary-700">Notes</label>
            <textarea className="input mt-1" rows={3} />
          </div>
        </div>
      </Modal>
    </div>
  )
}
