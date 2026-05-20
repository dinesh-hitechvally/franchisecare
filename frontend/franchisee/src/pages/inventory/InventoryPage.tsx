import { useEffect, useMemo, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { AlertTriangle, Package, Plus, Search, Trash2 } from 'lucide-react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { inventoryApi } from '../../api/services'
import type { InventoryItem } from '../../types'
import { PageHeader } from '../../components/layout/PageHeader'
import { getCategoryColor, getCategoryLabel, INVENTORY_CATEGORY_LABELS } from '../../constants/inventory'

type InventoryFormState = {
  name: string
  category: string
  sku: string
  quantity: string
  minStock: string
  unitPrice: string
  unit: string
}

const EMPTY_FORM: InventoryFormState = {
  name: '',
  category: 'General',
  sku: '',
  quantity: '0',
  minStock: '0',
  unitPrice: '0',
  unit: 'units',
}

export function InventoryPage() {
  const queryClient = useQueryClient()
  const [filterCategory, setFilterCategory] = useState('')
  const [searchTerm, setSearchTerm] = useState('')
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [editingItem, setEditingItem] = useState<InventoryItem | null>(null)
  const [form, setForm] = useState<InventoryFormState>(EMPTY_FORM)

  const { data: items = [], isLoading } = useQuery({
    queryKey: ['inventory-items', filterCategory],
    queryFn: () => inventoryApi.getItems({ category: filterCategory || undefined }),
  })

  const filteredItems = useMemo(() => {
    const term = searchTerm.trim().toLowerCase()

    if (!term) {
      return items
    }

    return items.filter((item) => {
      return [item.name, item.sku, item.category]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(term))
    })
  }, [items, searchTerm])

  const lowStockItems = filteredItems.filter((item) => item.quantity <= item.minStock)

  const createMutation = useMutation({
    mutationFn: (payload: InventoryFormState) =>
      inventoryApi.createItem({
        name: payload.name,
        category: payload.category,
        sku: payload.sku,
        quantity: Number(payload.quantity),
        minStock: Number(payload.minStock),
        unitPrice: Number(payload.unitPrice),
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['inventory-items'] })
      closeModal()
    },
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: string; payload: InventoryFormState }) =>
      inventoryApi.updateItem(id, {
        name: payload.name,
        category: payload.category,
        sku: payload.sku,
        quantity: Number(payload.quantity),
        minStock: Number(payload.minStock),
        unitPrice: Number(payload.unitPrice),
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['inventory-items'] })
      closeModal()
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id: string) => inventoryApi.deleteItem(id),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['inventory-items'] })
    },
  })

  useEffect(() => {
    if (!isModalOpen) {
      setEditingItem(null)
      setForm(EMPTY_FORM)
    }
  }, [isModalOpen])

  function closeModal() {
    setIsModalOpen(false)
    setEditingItem(null)
    setForm(EMPTY_FORM)
  }

  function openCreateModal() {
    setEditingItem(null)
    setForm(EMPTY_FORM)
    setIsModalOpen(true)
  }

  function openEditModal(item: InventoryItem) {
    setEditingItem(item)
    setForm({
      name: item.name,
      category: item.category,
      sku: item.sku,
      quantity: String(item.quantity),
      minStock: String(item.minStock),
      unitPrice: String(item.unitPrice),
      unit: 'units',
    })
    setIsModalOpen(true)
  }

  function saveItem() {
    if (editingItem) {
      updateMutation.mutate({ id: editingItem.id, payload: form })
      return
    }

    createMutation.mutate(form)
  }

  // Build dynamic categories from actual inventory items
  const uniqueCategories = useMemo(() => {
    const categories = [...new Set(items.map(item => item.category))].filter(Boolean)
    // Also include predefined categories for the form dropdown
    const allCategories = [...new Set([...categories, ...Object.keys(INVENTORY_CATEGORY_LABELS)])]
    return allCategories.sort()
  }, [items])

  const summaryCards = useMemo(() => {
    // Build summary from actual items in database
    const categoryMap = new Map<string, number>()
    items.forEach(item => {
      const count = categoryMap.get(item.category) || 0
      categoryMap.set(item.category, count + 1)
    })
    
    return Array.from(categoryMap.entries()).map(([category, count]) => ({
      category,
      label: getCategoryLabel(category),
      count,
    }))
  }, [items])

  return (
    <div className="space-y-6 px-1 py-1">
      <PageHeader
        title="Inventory"
        description="Track company-level stock items, stock on hand, and low-stock alerts."
        icon={<Package className="w-5 h-5" />}
        actions={
          <Button onClick={openCreateModal} size="sm">
            <Plus className="mr-2 h-4 w-4" />
            Add Item
          </Button>
        }
      />

      {lowStockItems.length > 0 && (
        <div className="rounded-lg border border-red-200 bg-red-50 p-4">
          <div className="flex items-start gap-3">
            <AlertTriangle className="mt-0.5 h-5 w-5 text-red-600" />
            <div>
              <h3 className="font-medium text-red-800">Low Stock Alert</h3>
              <p className="mt-1 text-sm text-red-700">{lowStockItems.length} item(s) are below minimum stock.</p>
            </div>
          </div>
        </div>
      )}

      {summaryCards.length > 0 && (
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
          {summaryCards.map((card) => (
            <div key={card.category} className="cursor-pointer" onClick={() => setFilterCategory(filterCategory === card.category ? '' : card.category)}>
              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className={`flex h-10 w-10 items-center justify-center rounded-lg ${getCategoryColor(card.category)}`}>
                    <Package className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-sm text-secondary-500">{card.label}</p>
                    <p className="text-xl font-bold text-secondary-900">{card.count}</p>
                  </div>
                </div>
              </Card>
            </div>
          ))}
        </div>
      )}

      <Card className="border border-gray-200 shadow-sm">
        <div className="border-b border-gray-200 p-4">
          <div className="flex items-center gap-4">
            <div className="relative flex-1">
              <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
              <Input
                placeholder="Search inventory items..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10"
              />
            </div>

            <select
              value={filterCategory}
              onChange={(e) => setFilterCategory(e.target.value)}
              className="input w-48"
            >
              <option value="">All Categories</option>
              {uniqueCategories.map((category) => (
                <option key={category} value={category}>{getCategoryLabel(category)}</option>
              ))}
            </select>
          </div>
        </div>

        <Table
          columns={[
            { key: 'sku', title: 'SKU' },
            { key: 'name', title: 'Item Name' },
            {
              key: 'category',
              title: 'Category',
              render: (row: InventoryItem) => (
                <span className={`rounded-full px-2 py-1 text-xs font-medium ${getCategoryColor(row.category)}`}>
                  {getCategoryLabel(row.category)}
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
                  <span className="text-sm font-medium text-red-600">Low Stock</span>
                ) : (
                  <span className="text-sm font-medium text-green-600">OK</span>
                )
              ),
            },
            {
              key: 'actions',
              title: 'Actions',
              render: (row: InventoryItem) => (
                <div className="flex gap-2">
                  <Button variant="ghost" size="sm" onClick={() => openEditModal(row)}>Edit</Button>
                  <Button variant="ghost" size="sm" onClick={() => deleteMutation.mutate(row.id)}>
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              ),
            },
          ]}
          data={filteredItems}
          keyExtractor={(row) => row.id}
          isLoading={isLoading}
          emptyMessage="No inventory items found"
        />
      </Card>

      <Modal
        isOpen={isModalOpen}
        onClose={closeModal}
        title={editingItem ? 'Edit Inventory Item' : 'Add Inventory Item'}
        footer={
          <>
            <Button variant="secondary" onClick={closeModal}>Cancel</Button>
            <Button onClick={saveItem}>{editingItem ? 'Update Item' : 'Create Item'}</Button>
          </>
        }
      >
        <div className="space-y-4">
          <Input
            label="Item Name"
            value={form.name}
            onChange={(e) => setForm((current) => ({ ...current, name: e.target.value }))}
          />
          <Input
            label="SKU"
            value={form.sku}
            onChange={(e) => setForm((current) => ({ ...current, sku: e.target.value }))}
          />
          <div>
            <label className="text-sm font-medium text-secondary-700">Category</label>
            <select
              className="input mt-1"
              value={form.category}
              onChange={(e) => setForm((current) => ({ ...current, category: e.target.value }))}
            >
              {uniqueCategories.map((category) => (
                <option key={category} value={category}>{getCategoryLabel(category)}</option>
              ))}
            </select>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <Input
              label="Current Stock"
              type="number"
              step="0.01"
              value={form.quantity}
              onChange={(e) => setForm((current) => ({ ...current, quantity: e.target.value }))}
            />
            <Input
              label="Min Stock Level"
              type="number"
              step="0.01"
              value={form.minStock}
              onChange={(e) => setForm((current) => ({ ...current, minStock: e.target.value }))}
            />
          </div>
          <Input
            label="Unit Price"
            type="number"
            step="0.01"
            value={form.unitPrice}
            onChange={(e) => setForm((current) => ({ ...current, unitPrice: e.target.value }))}
          />
          <Input
            label="Unit"
            value={form.unit}
            onChange={(e) => setForm((current) => ({ ...current, unit: e.target.value }))}
          />
        </div>
      </Modal>
    </div>
  )
}
