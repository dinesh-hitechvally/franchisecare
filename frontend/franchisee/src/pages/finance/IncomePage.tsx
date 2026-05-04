import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { financeApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import type { Income } from '../../types'
import { Plus, DollarSign, TrendingUp, Calendar, Repeat } from 'lucide-react'
import { format } from 'date-fns'
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts'

export function IncomePage() {
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [dateFrom, setDateFrom] = useState('')
  const [dateTo, setDateTo] = useState('')

  const { data: income, isLoading } = useQuery({
    queryKey: ['income', dateFrom, dateTo],
    queryFn: async () => {
      // Mock data
      return [
        { id: '1', amount: 150, description: 'Booking #1234 - Full Groom', category: 'services', date: new Date().toISOString(), isRecurring: false, franchise_id: '1', createdAt: new Date().toISOString() },
        { id: '2', amount: 75, description: 'Booking #1235 - Bath & Dry', category: 'services', date: new Date(Date.now() - 86400000).toISOString(), isRecurring: false, franchise_id: '1', createdAt: new Date(Date.now() - 86400000).toISOString() },
        { id: '3', amount: 200, description: 'Monthly Membership', category: 'membership', date: new Date(Date.now() - 172800000).toISOString(), isRecurring: true, franchise_id: '1', createdAt: new Date(Date.now() - 172800000).toISOString() },
        { id: '4', amount: 45, description: 'Retail - Dog Shampoo', category: 'retail', date: new Date(Date.now() - 259200000).toISOString(), isRecurring: false, franchise_id: '1', createdAt: new Date(Date.now() - 259200000).toISOString() },
      ] as Income[]
    },
  })

  const { data: summary } = useQuery({
    queryKey: ['income-summary'],
    queryFn: async () => {
      const total = income?.reduce((sum, i) => sum + i.amount, 0) ?? 0
      return { total }
    },
  })

  const chartData = [
    { name: 'Services', value: income?.filter(i => i.category === 'services').reduce((sum, i) => sum + i.amount, 0) ?? 0 },
    { name: 'Retail', value: income?.filter(i => i.category === 'retail').reduce((sum, i) => sum + i.amount, 0) ?? 0 },
    { name: 'Membership', value: income?.filter(i => i.category === 'membership').reduce((sum, i) => sum + i.amount, 0) ?? 0 },
    { name: 'Other', value: income?.filter(i => i.category === 'other').reduce((sum, i) => sum + i.amount, 0) ?? 0 },
  ]

  const createMutation = useMutation({
    mutationFn: financeApi.createIncome,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['income'] })
      setIsModalOpen(false)
      addToast('Income entry added', 'success')
    },
  })

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-secondary-900">Income</h1>
        <Button onClick={() => setIsModalOpen(true)} size="sm">
          <Plus className="w-4 h-4 mr-2" />
          Add Income
        </Button>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card className="p-4">
          <div className="flex items-center gap-4">
            <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <DollarSign className="w-6 h-6 text-green-700" />
            </div>
            <div>
              <p className="text-sm text-secondary-500">Total Income</p>
              <p className="text-2xl font-bold text-secondary-900">${summary?.total.toFixed(2) ?? '0.00'}</p>
            </div>
          </div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-4">
            <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <TrendingUp className="w-6 h-6 text-blue-700" />
            </div>
            <div>
              <p className="text-sm text-secondary-500">This Month</p>
              <p className="text-2xl font-bold text-secondary-900">$2,450.00</p>
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
              <p className="text-2xl font-bold text-secondary-900">{income?.filter(i => i.isRecurring).length ?? 0}</p>
            </div>
          </div>
        </Card>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Chart */}
        <Card title="Income by Category" className="lg:col-span-1">
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={chartData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="name" />
                <YAxis />
                <Tooltip />
                <Bar dataKey="value" fill="#3b82f6" />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </Card>

        {/* Income List */}
        <Card title="Recent Income" className="lg:col-span-2">
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
              { key: 'date', title: 'Date', render: (row: Income) => format(new Date(row.date), 'MMM d, yyyy') },
              { key: 'description', title: 'Description' },
              { key: 'category', title: 'Category', render: (row: Income) => (
                <span className="capitalize">{row.category}</span>
              )},
              { key: 'amount', title: 'Amount', render: (row: Income) => (
                <span className="font-medium text-green-600">+${row.amount.toFixed(2)}</span>
              )},
              {
                key: 'recurring',
                title: 'Recurring',
                render: (row: Income) => row.isRecurring ? (
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
            data={income ?? []}
            keyExtractor={(row) => row.id}
            isLoading={isLoading}
          />
        </Card>
      </div>

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title="Add Income Entry"
        footer={
          <>
            <Button variant="secondary" onClick={() => setIsModalOpen(false)}>
              Cancel
            </Button>
            <Button
              onClick={() => createMutation.mutate({
                amount: 100,
                description: 'New income',
                category: 'services',
                date: new Date().toISOString(),
                franchise_id: '1',
                isRecurring: false,
              } as Omit<Income, 'id' | 'createdAt'>)}
              isLoading={createMutation.isPending}
            >
              Add Income
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
              <option value="services">Services</option>
              <option value="retail">Retail</option>
              <option value="membership">Membership</option>
              <option value="other">Other</option>
            </select>
          </div>
          <label className="flex items-center gap-2">
            <input type="checkbox" className="rounded border-secondary-300" />
            <span className="text-sm text-secondary-700">Recurring income</span>
          </label>
        </div>
      </Modal>
    </div>
  )
}
