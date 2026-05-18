import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { inventoryApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import type { InventoryOrder } from '../../types'
import { Plus, Package, CheckCircle, Truck, Clock, XCircle, ShoppingCart } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'
import { formatDisplayDate } from '../../lib/timeFormatUtils'

interface OrderItemForm {
  product_name: string
  product_sku?: string
  inventory_item_id?: string
  quantity: number
  unit_price: number
}

export function InventoryOrdersPage() {
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [filterStatus, setFilterStatus] = useState('')
  const [orderType, setOrderType] = useState<'inventory' | 'treats' | 'marketing'>('inventory')
  const [orderNotes, setOrderNotes] = useState('')
  const [orderItems, setOrderItems] = useState<OrderItemForm[]>([{ product_name: '', quantity: 1, unit_price: 0 }])

  const { data: ordersData, isLoading } = useQuery({
    queryKey: ['inventory-orders', filterStatus],
    queryFn: () => inventoryApi.getOrders(filterStatus ? { status: filterStatus } : undefined),
  })

  const orders = ordersData?.data ?? []

  const { data: inventoryItems } = useQuery({
    queryKey: ['inventory-items'],
    queryFn: () => inventoryApi.getItems(),
  })

  const createOrderMutation = useMutation({
    mutationFn: (data: { type: string; notes?: string; items: OrderItemForm[] }) =>
      inventoryApi.createOrder(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inventory-orders'] })
      addToast('Order created successfully', 'success')
      setIsModalOpen(false)
      resetForm()
    },
    onError: (error: any) => {
      addToast(error?.response?.data?.message || 'Failed to create order', 'error')
    },
  })

  const updateStatusMutation = useMutation({
    mutationFn: ({ id, status }: { id: string; status: string }) =>
      inventoryApi.updateOrder(id, { status }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inventory-orders'] })
      addToast('Order status updated', 'success')
    },
    onError: (error: any) => {
      addToast(error?.response?.data?.message || 'Failed to update order', 'error')
    },
  })

  const resetForm = () => {
    setOrderType('inventory')
    setOrderNotes('')
    setOrderItems([{ product_name: '', quantity: 1, unit_price: 0 }])
  }

  const addItem = () => {
    setOrderItems([...orderItems, { product_name: '', quantity: 1, unit_price: 0 }])
  }

  const removeItem = (index: number) => {
    setOrderItems(orderItems.filter((_, i) => i !== index))
  }

  const updateItem = (index: number, field: keyof OrderItemForm, value: any) => {
    const newItems = [...orderItems]
    newItems[index] = { ...newItems[index], [field]: value }
    setOrderItems(newItems)
  }

  const selectInventoryItem = (index: number, itemId: string) => {
    const item = inventoryItems?.find((i) => i.id === itemId)
    if (item) {
      const newItems = [...orderItems]
      newItems[index] = {
        ...newItems[index],
        inventory_item_id: itemId,
        product_name: item.name,
        product_sku: item.sku,
        unit_price: item.unitPrice,
      }
      setOrderItems(newItems)
    }
  }

  const handleSubmit = () => {
    const validItems = orderItems.filter((item) => item.product_name && item.quantity > 0)
    if (validItems.length === 0) {
      addToast('Please add at least one item', 'error')
      return
    }
    createOrderMutation.mutate({
      type: orderType,
      notes: orderNotes || undefined,
      items: validItems,
    })
  }

  const typeLabels: Record<string, string> = {
    inventory: 'Inventory',
    treats: 'Treats',
    marketing: 'Marketing',
  }

  const statusConfig: Record<string, { label: string; color: string; icon: any }> = {
    pending: { label: 'Pending', color: 'bg-yellow-100 text-yellow-700', icon: Clock },
    confirmed: { label: 'Confirmed', color: 'bg-blue-100 text-blue-700', icon: Package },
    shipped: { label: 'Shipped', color: 'bg-purple-100 text-purple-700', icon: Truck },
    delivered: { label: 'Delivered', color: 'bg-green-100 text-green-700', icon: CheckCircle },
    cancelled: { label: 'Cancelled', color: 'bg-red-100 text-red-700', icon: XCircle },
  }

  const orderTotal = orderItems.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0)

  return (
    <div className="space-y-6">
      <PageHeader
        title="Inventory Orders"
        icon={<ShoppingCart className="w-5 h-5" />}
        actions={
          <Button onClick={() => setIsModalOpen(true)} size="sm">
            <Plus className="w-4 h-4 mr-2" />
            Create Order
          </Button>
        }
      />

      <Card>
        <div className="flex items-center gap-4 mb-6">
          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="input w-48"
          >
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <Table
          columns={[
            { key: 'order_number', title: 'Order #', render: (row: InventoryOrder) => row.order_number },
            { key: 'type', title: 'Type', render: (row: InventoryOrder) => typeLabels[row.type] || row.type },
            { key: 'items', title: 'Items', render: (row: InventoryOrder) => `${row.items?.length || 0} item(s)` },
            { key: 'total', title: 'Total', render: (row: InventoryOrder) => `$${Number(row.total).toFixed(2)}` },
            { key: 'created_at', title: 'Created', render: (row: InventoryOrder) => formatDisplayDate(row.created_at) },
            {
              key: 'status',
              title: 'Status',
              render: (row: InventoryOrder) => {
                const config = statusConfig[row.status] || statusConfig.pending
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
                      onClick={() => updateStatusMutation.mutate({ id: row.id, status: 'confirmed' })}
                    >
                      Confirm
                    </Button>
                  )}
                  {row.status === 'confirmed' && (
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => updateStatusMutation.mutate({ id: row.id, status: 'shipped' })}
                    >
                      Mark Shipped
                    </Button>
                  )}
                  {row.status === 'shipped' && (
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => updateStatusMutation.mutate({ id: row.id, status: 'delivered' })}
                    >
                      Mark Delivered
                    </Button>
                  )}
                  {row.status === 'pending' && (
                    <Button
                      variant="ghost"
                      size="sm"
                      className="text-red-600"
                      onClick={() => updateStatusMutation.mutate({ id: row.id, status: 'cancelled' })}
                    >
                      Cancel
                    </Button>
                  )}
                </div>
              ),
            },
          ]}
          data={orders}
          keyExtractor={(row) => row.id}
          isLoading={isLoading}
        />
      </Card>

      <Modal
        isOpen={isModalOpen}
        onClose={() => { setIsModalOpen(false); resetForm() }}
        title="Create Inventory Order"
        size="lg"
        footer={
          <>
            <Button variant="secondary" onClick={() => { setIsModalOpen(false); resetForm() }}>
              Cancel
            </Button>
            <Button onClick={handleSubmit} disabled={createOrderMutation.isPending}>
              {createOrderMutation.isPending ? 'Creating...' : 'Create Order'}
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          <div>
            <label className="text-sm font-medium text-secondary-700">Order Type</label>
            <select 
              className="input mt-1" 
              value={orderType}
              onChange={(e) => setOrderType(e.target.value as any)}
            >
              <option value="inventory">Inventory</option>
              <option value="treats">Treats</option>
              <option value="marketing">Marketing Materials</option>
            </select>
          </div>
          <div>
            <label className="text-sm font-medium text-secondary-700">Items</label>
            <div className="mt-2 space-y-2">
              {orderItems.map((item, index) => (
                <div key={index} className="flex items-center gap-2 p-3 border rounded-lg bg-gray-50">
                  <div className="flex-1">
                    <select
                      className="input w-full mb-2"
                      value={item.inventory_item_id || ''}
                      onChange={(e) => {
                        if (e.target.value) {
                          selectInventoryItem(index, e.target.value)
                        } else {
                          updateItem(index, 'inventory_item_id', undefined)
                          updateItem(index, 'product_name', '')
                        }
                      }}
                    >
                      <option value="">-- Select from inventory --</option>
                      {inventoryItems?.map((invItem) => (
                        <option key={invItem.id} value={invItem.id}>
                          {invItem.name} {invItem.sku ? `(${invItem.sku})` : ''}
                        </option>
                      ))}
                    </select>
                    <input
                      type="text"
                      className="input w-full"
                      placeholder="Or enter product name"
                      value={item.product_name}
                      onChange={(e) => updateItem(index, 'product_name', e.target.value)}
                    />
                  </div>
                  <input
                    type="number"
                    className="input w-20"
                    placeholder="Qty"
                    min="1"
                    value={item.quantity}
                    onChange={(e) => updateItem(index, 'quantity', parseInt(e.target.value) || 1)}
                  />
                  <input
                    type="number"
                    className="input w-24"
                    placeholder="Price"
                    min="0"
                    step="0.01"
                    value={item.unit_price}
                    onChange={(e) => updateItem(index, 'unit_price', parseFloat(e.target.value) || 0)}
                  />
                  <span className="text-sm font-medium w-20 text-right">
                    ${(item.quantity * item.unit_price).toFixed(2)}
                  </span>
                  {orderItems.length > 1 && (
                    <button
                      type="button"
                      onClick={() => removeItem(index)}
                      className="text-red-500 hover:text-red-700 text-xl"
                    >
                      ×
                    </button>
                  )}
                </div>
              ))}
              <Button variant="secondary" size="sm" onClick={addItem}>
                + Add Item
              </Button>
            </div>
          </div>
          <div className="flex justify-end text-lg font-semibold">
            Total: ${orderTotal.toFixed(2)}
          </div>
          <div>
            <label className="text-sm font-medium text-secondary-700">Notes</label>
            <textarea
              className="input mt-1"
              rows={3}
              value={orderNotes}
              onChange={(e) => setOrderNotes(e.target.value)}
              placeholder="Optional notes..."
            />
          </div>
        </div>
      </Modal>
    </div>
  )
}
