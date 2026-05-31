import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Table } from '../../components/ui/Table'
import { CreditCard, Calendar, DollarSign, Loader2, CheckCircle, XCircle, Clock, RefreshCw } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'
import { formatDisplayDate } from '../../lib/timeFormatUtils'
import { paymentsApi, type PaymentTransaction } from '../../api/services'
import { useState } from 'react'

export function PaymentHistoryPage() {
  const [typeFilter, setTypeFilter] = useState<string>('')
  const [statusFilter, setStatusFilter] = useState<string>('')

  const { data: paymentsData, isLoading } = useQuery({
    queryKey: ['payment-history', typeFilter, statusFilter],
    queryFn: () => paymentsApi.getHistory({ 
      type: typeFilter || undefined, 
      status: statusFilter || undefined,
      per_page: 50 
    }),
  })

  const transactions = paymentsData?.data || []

  // Calculate summary stats
  const totalTransactions = transactions.length
  const completedTransactions = transactions.filter(t => t.status === 'completed').length
  const totalAmount = transactions
    .filter(t => t.status === 'completed')
    .reduce((sum, t) => sum + Number(t.amount || 0), 0)

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'completed':
        return <CheckCircle className="w-4 h-4 text-green-500" />
      case 'failed':
        return <XCircle className="w-4 h-4 text-red-500" />
      case 'pending':
        return <Clock className="w-4 h-4 text-yellow-500" />
      case 'refunded':
        return <RefreshCw className="w-4 h-4 text-blue-500" />
      default:
        return null
    }
  }

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'completed':
        return 'bg-green-100 text-green-700'
      case 'failed':
        return 'bg-red-100 text-red-700'
      case 'pending':
        return 'bg-yellow-100 text-yellow-700'
      case 'refunded':
        return 'bg-blue-100 text-blue-700'
      case 'voided':
        return 'bg-gray-100 text-gray-700'
      default:
        return 'bg-gray-100 text-gray-700'
    }
  }

  const getTypeLabel = (type: string) => {
    switch (type) {
      case 'credit_purchase':
        return 'SMS Credits'
      case 'order':
        return 'Inventory Order'
      case 'booking':
        return 'Booking Payment'
      default:
        return type
    }
  }

  const columns = [
    {
      key: 'date',
      title: 'Date',
      render: (transaction: PaymentTransaction) => (
        <div className="flex items-center gap-2">
          <Calendar className="w-4 h-4 text-gray-400" />
          <span>{transaction.processed_at ? formatDisplayDate(transaction.processed_at) : formatDisplayDate(transaction.created_at)}</span>
        </div>
      ),
    },
    {
      key: 'type',
      title: 'Type',
      render: (transaction: PaymentTransaction) => (
        <span className="font-medium text-gray-800">{getTypeLabel(transaction.type)}</span>
      ),
    },
    {
      key: 'amount',
      title: 'Amount',
      render: (transaction: PaymentTransaction) => (
        <div className="flex items-center gap-1">
          <DollarSign className="w-4 h-4 text-gray-400" />
          <span className="font-semibold">{Number(transaction.amount || 0).toFixed(2)}</span>
          <span className="text-gray-500 text-xs">{transaction.currency}</span>
        </div>
      ),
    },
    {
      key: 'card',
      title: 'Payment Method',
      render: (transaction: PaymentTransaction) => (
        <div className="flex items-center gap-2">
          <CreditCard className="w-4 h-4 text-gray-400" />
          <span>
            {transaction.card_brand ? `${transaction.card_brand} ` : ''}
            {transaction.card_last_four ? `•••• ${transaction.card_last_four}` : 'Credit Card'}
          </span>
        </div>
      ),
    },
    {
      key: 'status',
      title: 'Status',
      render: (transaction: PaymentTransaction) => (
        <div className="flex items-center gap-2">
          {getStatusIcon(transaction.status)}
          <span className={`px-3 py-1 rounded-full text-xs font-medium ${getStatusColor(transaction.status)}`}>
            {transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1)}
          </span>
        </div>
      ),
    },
    {
      key: 'transactionId',
      title: 'Transaction ID',
      render: (transaction: PaymentTransaction) => (
        <span className="text-sm text-gray-600 font-mono">{transaction.transaction_id || '-'}</span>
      ),
    },
  ]

  if (isLoading) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Payment History"
          description="View all payment transactions"
          icon={<CreditCard className="w-5 h-5" />}
        />
        <Card className="p-8">
          <div className="flex items-center justify-center gap-2 text-gray-500">
            <Loader2 className="w-5 h-5 animate-spin" />
            <span>Loading payment history...</span>
          </div>
        </Card>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Payment History"
        description="View all payment transactions"
        icon={<CreditCard className="w-5 h-5" />}
      />

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card className="p-4 bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-blue-700 font-medium">Total Transactions</p>
              <p className="text-2xl font-bold text-blue-900 mt-1">{totalTransactions}</p>
            </div>
            <div className="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
              <CreditCard className="w-6 h-6 text-white" />
            </div>
          </div>
        </Card>

        <Card className="p-4 bg-gradient-to-br from-green-50 to-green-100 border-green-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-green-700 font-medium">Successful Payments</p>
              <p className="text-2xl font-bold text-green-900 mt-1">{completedTransactions}</p>
            </div>
            <div className="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
              <CheckCircle className="w-6 h-6 text-white" />
            </div>
          </div>
        </Card>

        <Card className="p-4 bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-purple-700 font-medium">Total Processed</p>
              <p className="text-2xl font-bold text-purple-900 mt-1">${totalAmount.toFixed(2)}</p>
            </div>
            <div className="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
              <DollarSign className="w-6 h-6 text-white" />
            </div>
          </div>
        </Card>
      </div>

      {/* Filters */}
      <Card className="p-4">
        <div className="flex flex-wrap gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Type</label>
            <select
              value={typeFilter}
              onChange={(e) => setTypeFilter(e.target.value)}
              className="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">All Types</option>
              <option value="credit_purchase">SMS Credits</option>
              <option value="order">Inventory Order</option>
              <option value="booking">Booking Payment</option>
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">All Statuses</option>
              <option value="completed">Completed</option>
              <option value="pending">Pending</option>
              <option value="failed">Failed</option>
              <option value="refunded">Refunded</option>
            </select>
          </div>
        </div>
      </Card>

      {/* Transactions Table */}
      <Card>
        <Table
          data={transactions}
          columns={columns}
          keyExtractor={(transaction) => String(transaction.id)}
          emptyMessage="No payment transactions found"
        />
      </Card>
    </div>
  )
}
