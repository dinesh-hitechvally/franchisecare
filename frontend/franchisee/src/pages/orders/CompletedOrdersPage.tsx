import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Check, X, ChevronLeft, ChevronRight, ShoppingBag, Loader2 } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'
import { inventoryApi } from '../../api/services'

export function CompletedOrdersPage() {
  const { data: ordersData, isLoading } = useQuery({
    queryKey: ['completed-orders'],
    queryFn: () => inventoryApi.getOrders({ status: 'completed' }),
  })

  const orders = ordersData?.data || []

  const formatDate = (dateStr: string) => {
    if (!dateStr) return '-'
    const date = new Date(dateStr)
    const day = date.getDate()
    const suffix = day === 1 || day === 21 || day === 31 ? 'st' : day === 2 || day === 22 ? 'nd' : day === 3 || day === 23 ? 'rd' : 'th'
    const weekday = date.toLocaleDateString('en-AU', { weekday: 'long' })
    const month = date.toLocaleDateString('en-AU', { month: 'short', year: 'numeric' })
    return `${weekday}, ${day}${suffix} ${month}`
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Completed Orders"
        description="View your completed order history"
        icon={<ShoppingBag className="w-5 h-5" />}
      />

      <div className="bg-white p-4 shadow-sm rounded-md border border-gray-200">
        <p className="text-gray-800 text-sm font-medium">All prices are GST included.</p>
      </div>

      {isLoading ? (
        <Card className="border border-gray-200 shadow-sm bg-white p-8">
          <div className="flex items-center justify-center gap-2 text-gray-500">
            <Loader2 className="w-5 h-5 animate-spin" />
            <span>Loading completed orders...</span>
          </div>
        </Card>
      ) : (
      <Card className="border border-gray-200 shadow-sm overflow-hidden bg-white">
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left border-collapse">
            <thead className="bg-white border-b border-gray-200">
              <tr>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100 text-xs uppercase tracking-wider">Order Number/Code</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100 text-xs uppercase tracking-wider">Order Date</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center border-r border-gray-100 text-xs uppercase tracking-wider"># of Items Ordered</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-right border-r border-gray-100 text-xs uppercase tracking-wider">Total Amount ($)</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100 text-xs uppercase tracking-wider">Order Status</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100 text-xs uppercase tracking-wider">Payment Status</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center border-r border-gray-100 text-xs uppercase tracking-wider">Editable?</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-xs uppercase tracking-wider">Mgmt</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100 bg-white">
              {orders.length === 0 ? (
                <tr>
                  <td colSpan={8} className="px-6 py-8 text-center text-gray-500">
                    No completed orders found
                  </td>
                </tr>
              ) : (
              orders.map((order) => (
                <tr key={order.id} className="hover:bg-gray-50 transition-colors">
                  <td className="px-6 py-4 text-gray-700 font-medium whitespace-nowrap">{order.order_number || order.id}</td>
                  <td className="px-6 py-4 text-gray-600 whitespace-nowrap">{formatDate(order.order_date || order.created_at)}</td>
                  <td className="px-6 py-4 text-center text-gray-700 font-medium">{order.items_count || order.items?.length || 0}</td>
                  <td className="px-6 py-4 text-right text-gray-900 font-semibold">${Number(order.total_amount || 0).toFixed(2)}</td>
                  <td className="px-6 py-4">
                    <span className="text-xs font-bold text-green-600 uppercase">
                      {order.status || 'Completed'}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <span className={`text-sm ${order.payment_status === 'paid' ? 'text-gray-800' : 'text-red-500 font-medium'}`}>
                      {order.payment_status === 'paid' ? 'Paid' : 'Not Paid'}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex justify-center">
                      {order.is_editable ? (
                        <Check className="w-5 h-5 text-green-500" strokeWidth={3} />
                      ) : (
                        <X className="w-5 h-5 text-gray-400" strokeWidth={3} />
                      )}
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <button className="text-blue-600 hover:underline font-semibold">View</button>
                  </td>
                </tr>
              ))
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        <div className="px-6 py-4 bg-white border-t border-gray-100 flex items-center justify-end gap-6">
          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-500">Rows per page:</span>
            <select className="bg-transparent border-none text-sm font-medium focus:ring-0 outline-none cursor-pointer">
              <option>25</option>
              <option>50</option>
              <option>100</option>
            </select>
          </div>
          <span className="text-sm text-gray-600">1-{orders.length} of {ordersData?.meta?.total || orders.length}</span>
          <div className="flex items-center gap-1">
            <button disabled className="p-1 hover:bg-gray-100 rounded-full disabled:opacity-30 disabled:cursor-not-allowed">
              <ChevronLeft className="w-5 h-5" />
            </button>
            <button className="p-1 hover:bg-gray-100 rounded-full">
              <ChevronRight className="w-5 h-5" />
            </button>
          </div>
        </div>
      </Card>
      )}
    </div>
  )
}
