import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Table } from '../../components/ui/Table'
import { CreditCard, Calendar, DollarSign, MessageSquare } from 'lucide-react'
import { format } from 'date-fns'
import { PageHeader } from '../../components/layout/PageHeader'

interface SmsPurchase {
  id: string
  date: string
  credits: number
  amount: number
  paymentMethod: string
  status: 'completed' | 'pending' | 'failed'
  transactionId: string
}

export function SmsPurchaseLogPage() {
  const { data: purchases } = useQuery({
    queryKey: ['sms-purchases'],
    queryFn: async () => {
      return [
        {
          id: '1',
          date: '2026-05-10',
          credits: 1000,
          amount: 89.00,
          paymentMethod: 'Credit Card',
          status: 'completed',
          transactionId: 'TXN-2026051001',
        },
        {
          id: '2',
          date: '2026-04-15',
          credits: 500,
          amount: 45.00,
          paymentMethod: 'Credit Card',
          status: 'completed',
          transactionId: 'TXN-2026041502',
        },
        {
          id: '3',
          date: '2026-03-20',
          credits: 2000,
          amount: 169.00,
          paymentMethod: 'Credit Card',
          status: 'completed',
          transactionId: 'TXN-2026032003',
        },
        {
          id: '4',
          date: '2026-02-28',
          credits: 1000,
          amount: 89.00,
          paymentMethod: 'Credit Card',
          status: 'completed',
          transactionId: 'TXN-2026022804',
        },
        {
          id: '5',
          date: '2026-01-18',
          credits: 500,
          amount: 45.00,
          paymentMethod: 'Credit Card',
          status: 'completed',
          transactionId: 'TXN-2026011805',
        },
      ] as SmsPurchase[]
    },
  })

  const totalCredits = purchases?.reduce((sum, p) => sum + p.credits, 0) || 0
  const totalSpent = purchases?.reduce((sum, p) => sum + p.amount, 0) || 0

  const columns = [
    {
      key: 'date',
      label: 'Purchase Date',
      render: (purchase: SmsPurchase) => (
        <div className="flex items-center gap-2">
          <Calendar className="w-4 h-4 text-gray-400" />
          <span>{format(new Date(purchase.date), 'dd MMM yyyy')}</span>
        </div>
      ),
    },
    {
      key: 'credits',
      label: 'Credits Purchased',
      render: (purchase: SmsPurchase) => (
        <span className="font-semibold text-blue-600">{purchase.credits.toLocaleString()}</span>
      ),
    },
    {
      key: 'amount',
      label: 'Amount',
      render: (purchase: SmsPurchase) => (
        <div className="flex items-center gap-1">
          <DollarSign className="w-4 h-4 text-gray-400" />
          <span className="font-medium">${purchase.amount.toFixed(2)}</span>
        </div>
      ),
    },
    {
      key: 'paymentMethod',
      label: 'Payment Method',
      render: (purchase: SmsPurchase) => (
        <div className="flex items-center gap-2">
          <CreditCard className="w-4 h-4 text-gray-400" />
          <span>{purchase.paymentMethod}</span>
        </div>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (purchase: SmsPurchase) => (
        <span
          className={`px-3 py-1 rounded-full text-xs font-medium ${
            purchase.status === 'completed'
              ? 'bg-green-100 text-green-700'
              : purchase.status === 'pending'
              ? 'bg-yellow-100 text-yellow-700'
              : 'bg-red-100 text-red-700'
          }`}
        >
          {purchase.status.charAt(0).toUpperCase() + purchase.status.slice(1)}
        </span>
      ),
    },
    {
      key: 'transactionId',
      label: 'Transaction ID',
      render: (purchase: SmsPurchase) => (
        <span className="text-sm text-gray-600 font-mono">{purchase.transactionId}</span>
      ),
    },
  ]

  return (
    <div className="space-y-6">
      <PageHeader
        title="SMS Purchase Log"
        description="View history of SMS credit purchases"
        icon={<MessageSquare className="w-5 h-5" />}
      />

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card className="p-4 bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-blue-700 font-medium">Total Purchases</p>
              <p className="text-2xl font-bold text-blue-900 mt-1">{purchases?.length || 0}</p>
            </div>
            <div className="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
              <CreditCard className="w-6 h-6 text-white" />
            </div>
          </div>
        </Card>

        <Card className="p-4 bg-gradient-to-br from-green-50 to-green-100 border-green-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-green-700 font-medium">Total Credits</p>
              <p className="text-2xl font-bold text-green-900 mt-1">{totalCredits.toLocaleString()}</p>
            </div>
            <div className="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
              <DollarSign className="w-6 h-6 text-white" />
            </div>
          </div>
        </Card>

        <Card className="p-4 bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-purple-700 font-medium">Total Spent</p>
              <p className="text-2xl font-bold text-purple-900 mt-1">${totalSpent.toFixed(2)}</p>
            </div>
            <div className="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
              <CreditCard className="w-6 h-6 text-white" />
            </div>
          </div>
        </Card>
      </div>

      <Card>
        <Table
          data={purchases || []}
          columns={columns}
          emptyMessage="No SMS purchases found"
        />
      </Card>
    </div>
  )
}
