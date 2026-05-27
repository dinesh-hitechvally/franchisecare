import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Table } from '../../components/ui/Table'
import { CreditCard, Calendar, DollarSign, MessageSquare, Loader2 } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'
import { formatDisplayDate } from '../../lib/timeFormatUtils'
import { smsCreditsApi, type SmsCreditPurchase } from '../../api/services'

export function SmsPurchaseLogPage() {
  const { data: purchasesData, isLoading } = useQuery({
    queryKey: ['sms-purchases'],
    queryFn: () => smsCreditsApi.history(),
  })

  const purchases = purchasesData?.data || []

  const totalCredits = purchases.reduce((sum, p) => sum + (p.quantity || 0), 0)
  const totalSpent = purchases.reduce((sum, p) => sum + (p.amount || 0), 0)

  const columns = [
    {
      key: 'date',
      title: 'Purchase Date',
      render: (purchase: SmsCreditPurchase) => (
        <div className="flex items-center gap-2">
          <Calendar className="w-4 h-4 text-gray-400" />
          <span>{formatDisplayDate(purchase.purchased_at)}</span>
        </div>
      ),
    },
    {
      key: 'credits',
      title: 'Credits Purchased',
      render: (purchase: SmsCreditPurchase) => (
        <span className="font-semibold text-blue-600">{(purchase.quantity || 0).toLocaleString()}</span>
      ),
    },
    {
      key: 'amount',
      title: 'Amount',
      render: (purchase: SmsCreditPurchase) => (
        <div className="flex items-center gap-1">
          <DollarSign className="w-4 h-4 text-gray-400" />
          <span className="font-medium">${Number(purchase.amount || 0).toFixed(2)}</span>
        </div>
      ),
    },
    {
      key: 'paymentMethod',
      title: 'Payment Method',
      render: () => (
        <div className="flex items-center gap-2">
          <CreditCard className="w-4 h-4 text-gray-400" />
          <span>Credit Card</span>
        </div>
      ),
    },
    {
      key: 'status',
      title: 'Status',
      render: (purchase: SmsCreditPurchase) => (
        <span
          className={`px-3 py-1 rounded-full text-xs font-medium ${
            purchase.status === 'completed'
              ? 'bg-green-100 text-green-700'
              : purchase.status === 'pending'
              ? 'bg-yellow-100 text-yellow-700'
              : 'bg-red-100 text-red-700'
          }`}
        >
          {(purchase.status || 'completed').charAt(0).toUpperCase() + (purchase.status || 'completed').slice(1)}
        </span>
      ),
    },
    {
      key: 'transactionId',
      title: 'Transaction ID',
      render: (purchase: SmsCreditPurchase) => (
        <span className="text-sm text-gray-600 font-mono">{purchase.id || '-'}</span>
      ),
    },
  ]

  if (isLoading) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="SMS Purchase Log"
          description="View history of SMS credit purchases"
          icon={<MessageSquare className="w-5 h-5" />}
        />
        <Card className="p-8">
          <div className="flex items-center justify-center gap-2 text-gray-500">
            <Loader2 className="w-5 h-5 animate-spin" />
            <span>Loading purchase history...</span>
          </div>
        </Card>
      </div>
    )
  }

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
              <p className="text-2xl font-bold text-blue-900 mt-1">{purchases.length}</p>
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
          data={purchases}
          columns={columns}
          keyExtractor={(purchase) => String(purchase.id)}
          emptyMessage="No SMS purchases found"
        />
      </Card>
    </div>
  )
}
