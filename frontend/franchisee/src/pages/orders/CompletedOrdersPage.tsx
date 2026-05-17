import { Card } from '../../components/ui/Card'
import { Check, X, ChevronLeft, ChevronRight, ShoppingBag } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'

export function CompletedOrdersPage() {
  // Mocks based on screenshot analysis
  const orders = [
    { 
      id: 'BWO-2024-12650', 
      date: 'Wednesday, 9th Oct 2024', 
      items: 4, 
      total: 216.60, 
      orderStatus: 'Completed', 
      paymentStatus: 'Not Paid', 
      editable: true 
    },
    { 
      id: 'BWO-2024-12582', 
      date: 'Monday, 30th Sep 2024', 
      items: 2, 
      total: 85.00, 
      orderStatus: 'Completed', 
      paymentStatus: 'Paid', 
      editable: false 
    },
    { 
      id: 'BWO-2024-12490', 
      date: 'Friday, 20th Sep 2024', 
      items: 6, 
      total: 412.30, 
      orderStatus: 'Completed', 
      paymentStatus: 'Paid', 
      editable: false 
    },
  ]

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
              {orders.map((order) => (
                <tr key={order.id} className="hover:bg-gray-50 transition-colors">
                  <td className="px-6 py-4 text-gray-700 font-medium whitespace-nowrap">{order.id}</td>
                  <td className="px-6 py-4 text-gray-600 whitespace-nowrap">{order.date}</td>
                  <td className="px-6 py-4 text-center text-gray-700 font-medium">{order.items}</td>
                  <td className="px-6 py-4 text-right text-gray-900 font-semibold">${order.total.toFixed(2)}</td>
                  <td className="px-6 py-4">
                    <span className="text-xs font-bold text-green-600 uppercase">
                      {order.orderStatus}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <span className={`text-sm ${order.paymentStatus === 'Paid' ? 'text-gray-800' : 'text-red-500 font-medium'}`}>
                      {order.paymentStatus}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex justify-center">
                      {order.editable ? (
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
              ))}
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
          <span className="text-sm text-gray-600">1-3 of 31</span>
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
    </div>
  )
}
