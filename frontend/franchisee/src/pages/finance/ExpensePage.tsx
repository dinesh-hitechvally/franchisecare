import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { PageHeader } from '../../components/layout/PageHeader'
import { financeApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import type { Expense } from '../../types'
import { Plus, DollarSign, TrendingDown, Calendar, Repeat, Package } from 'lucide-react'
import { format } from 'date-fns'
import { PieChart, Pie, Cell, ResponsiveContainer, Legend, Tooltip } from 'recharts'

export function ExpensePage() {
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [dateFrom, setDateFrom] = useState('')
  const [dateTo, setDateTo] = useState('')

  const { data: expenses, isLoading } = useQuery({
    queryKey: ['expenses', dateFrom, dateTo],
    queryFn: async () => {
      // Mock data
      return [
        { id: '1', amount: 120, description: 'Inventory Order - Shampoo', category: 'inventory', date: new Date().toISOString(), isRecurring: false, orderId: '1', franchise_id: '1', createdAt: new Date().toISOString() },
        { id: '2', amount: 500, description: 'Monthly Rent', category: 'rent', date: new Date(Date.now() - 86400000).toISOString(), isRecurring: true, franchise_id: '1', createdAt: new Date(Date.now() - 86400000).toISOString() },
        { id: '3', amount: 150, description: 'Utilities', category: 'utilities', date: new Date(Date.now() - 172800000).toISOString(), isRecurring: true, franchise_id: '1', createdAt: new Date(Date.now() - 172800000).toISOString() },
        { id: '4', amount: 80, description: 'Staff Supplies', category: 'supplies', date: new Date(Date.now() - 259200000).toISOString(), isRecurring: false, franchise_id: '1', createdAt: new Date(Date.now() - 259200000).toISOString() },
      ] as Expense[]
    },
  })

  const { data: summary } = useQuery({
    queryKey: ['expense-summary'],
    queryFn: async () => {
      const total = expenses?.reduce((sum, e) => sum + e.amount, 0) ?? 0
      return { total }
    },
  })

  const pieData = [
    { name: 'Inventory', value: expenses?.filter(e => e.category === 'inventory').reduce((sum, e) => sum + e.amount, 0) ?? 0 },
    { name: 'Rent', value: expenses?.filter(e => e.category === 'rent').reduce((sum, e) => sum + e.amount, 0) ?? 0 },
    { name: 'Utilities', value: expenses?.filter(e => e.category === 'utilities').reduce((sum, e) => sum + e.amount, 0) ?? 0 },
    { name: 'Supplies', value: expenses?.filter(e => e.category === 'supplies').reduce((sum, e) => sum + e.amount, 0) ?? 0 },
  ].filter(d => d.value > 0)

  const COLORS = ['#3b82f6', '#ef4444', '#f59e0b', '#10b981']

  const createMutation = useMutation({
    mutationFn: financeApi.createExpense,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['expenses'] })
      setIsModalOpen(false)
      addToast('Expense entry added', 'success')
    },
  })

  return (
    <div className="space-y-6">
      <PageHeader
        title="Expenses"
        icon={<TrendingDown className="w-5 h-5" />}
        actions={
          <Button onClick={() => setIsModalOpen(true)} size="sm">
            <Plus className="w-4 h-4 mr-2" />
            Add Expense
          </Button>
        }
      />

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card className="p-4">
          <div className="flex items-center gap-4">
            <div className="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
              <DollarSign className="w-6 h-6 text-red-700" />
            </div>
            <div>
              <p className="text-sm text-secondary-500">Total Expenses</p>
              <p className="text-2xl font-bold text-secondary-900">${summary?.total.toFixed(2) ?? '0.00'}</p>
            </div>
          </div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-4">
            <div className="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
              <TrendingDown className="w-6 h-6 text-orange-700" />
            </div>
            <div>
              <p className="text-sm text-secondary-500">This Month</p>
              <p className="text-2xl font-bold text-secondary-900">$850.00</p>
            </div>
          </div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-4">
            <div className="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
              <Repeat className="w-6 h-6 text-purple-700" />
            </div>
            <div>
              <p className="text-sm text-secondary-500">Recurring</p>
              <p className="text-2xl font-bold text-secondary-900">{expenses?.filter(e => e.isRecurring).length ?? 0}</p>
            </div>
          </div>
        </Card>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Chart */}
        <Card title="Expenses by Category" className="lg:col-span-1">
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={pieData}
                  cx="50%"
                  cy="50%"
                  outerRadius={80}
                  dataKey="value"
                >
                  {pieData.map((_, index) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip />
                <Legend />
              </PieChart>
            </ResponsiveContainer>
          </div>
        </Card>

        {/* Expense List */}
        <Card title="Recent Expenses" className="lg:col-span-2">
          <div className="flex items-center gap-4 mb-4">
            <div className="flex items-center gap-2">
              <Calendar className="w-4 h-4 text-secondary-400" />
              <input
                type="date"
                value={dateFrom}
                onChange={(e) => setDateFrom(e.target.value)}
                className="input w-40"
              />
              <span className="text-secondary-400">to</span>
              <input
                type="date"
                value={dateTo}
                onChange={(e) => setDateTo(e.target.value)}
                className="input w-40"
              />
            </div>
          </div>

          <Table
            columns={[
              { key: 'date', title: 'Date', render: (row: Expense) => format(new Date(row.date), 'MMM d, yyyy') },
              { key: 'description', title: 'Description' },
              { key: 'category', title: 'Category', render: (row: Expense) => (
                <div className="flex items-center gap-2">
                  {row.orderId && <Package className="w-4 h-4 text-blue-500" />}
                  <span className="capitalize">{row.category}</span>
                </div>
              )},
              { key: 'amount', title: 'Amount', render: (row: Expense) => (
                <span className="font-medium text-red-600">-${row.amount.toFixed(2)}</span>
              )},
              {
                key: 'recurring',
                title: 'Recurring',
                render: (row: Expense) => row.isRecurring ? (
                  <span className="px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                    Yes
                  </span>
                ) : (
                  <span className="text-secondary-400">-</span>
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
            data={expenses ?? []}
            keyExtractor={(row) => row.id}
            isLoading={isLoading}
          />
        </Card>
      </div>

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title="Add Expense Entry"
        footer={
          <>
            <Button variant="secondary" onClick={() => setIsModalOpen(false)}>
              Cancel
            </Button>
            <Button
              onClick={() => createMutation.mutate({
                amount: 50,
                description: 'New expense',
                category: 'supplies',
                date: new Date().toISOString(),
                franchise_id: '1',
                isRecurring: false,
              } as Omit<Expense, 'id' | 'createdAt'>)}
              isLoading={createMutation.isPending}
            >
              Add Expense
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          <Input label="Description" />
          <div className="grid grid-cols-2 gap-4">
            <Input label="Amount" type="number" step="0.01" />
            <Input label="Date" type="date" />
          </div>
          <div>
            <label className="text-sm font-medium text-secondary-700">Category</label>
            <select className="input mt-1">
              <option value="inventory">Inventory</option>
              <option value="rent">Rent</option>
              <option value="utilities">Utilities</option>
              <option value="supplies">Supplies</option>
              <option value="other">Other</option>
            </select>
          </div>
          <label className="flex items-center gap-2">
            <input type="checkbox" className="rounded border-secondary-300" />
            <span className="text-sm text-secondary-700">Recurring expense</span>
          </label>
        </div>
      </Modal>
    </div>
  )
}
