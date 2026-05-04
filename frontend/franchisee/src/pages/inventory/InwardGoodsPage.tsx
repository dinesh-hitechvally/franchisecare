import { Card } from '../../components/ui/Card'
import { Check, X, ChevronLeft, ChevronRight } from 'lucide-react'

export function InwardGoodsPage() {
  // Mocks based on screenshot analysis
  const orders = [
    { 
      id: 'BWO-2025-13328', 
      date: 'Wednesday, 11th Jun 2025', 
      items: 1, 
      total: 33.90, 
      orderStatus: 'Order Locked', 
      paymentStatus: 'Paid', 
      editable: false 
    },
    { 
      id: 'BWO-2025-13245', 
      date: 'Monday, 2nd Jun 2025', 
      items: 3, 
      total: 140.00, 
      orderStatus: 'Shipped', 
      paymentStatus: 'Paid', 
      editable: false 
    },
    { 
      id: 'BWO-2025-13110', 
      date: 'Friday, 23rd May 2025', 
      items: 5, 
      total: 615.35, 
      orderStatus: 'Order Locked', 
      paymentStatus: 'Paid', 
      editable: true 
    },
  ]

  return (
    <div className="space-y-6">
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Inward Goods</h1>
      </div>

      <div className="bg-white p-4 shadow-sm rounded-md border border-gray-200">
        <p className="text-gray-800 text-sm font-medium">All prices are GST included.</p>
      </div>

      <Card className="border border-gray-200 shadow-sm overflow-hidden bg-white">
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left border-collapse">
            <thead className="bg-white border-b border-gray-200">
              <tr>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Order Number/Code</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Order Date</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center border-r border-gray-100"># of Items Ordered</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-right border-r border-gray-100">Total Amount ($)</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Order Status</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Payment Status</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center border-r border-gray-100">Editable?</th>
                <th className="px-6 py-4 font-bold text-gray-900">Mgmt</th>
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
                    <span className={`text-xs font-semibold ${order.orderStatus === 'Shipped' ? 'text-blue-600' : 'text-gray-700'}`}>
                      {order.orderStatus}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-gray-700">{order.paymentStatus}</td>
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
                    <div className="flex items-center gap-1">
                      <button className="text-blue-600 hover:underline font-semibold">View</button>
                      {order.editable && (
                        <>
                          <span className="text-gray-400 font-light"> | </span>
                          <button className="text-blue-600 hover:underline font-semibold">Cancel</button>
                        </>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* Pagination/Footer */}
        <div className="px-6 py-4 bg-white border-t border-gray-100 flex items-center justify-end gap-6">
          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-500">Rows per page:</span>
            <select className="bg-transparent border-none text-sm font-medium focus:ring-0 outline-none cursor-pointer">
              <option>25</option>
              <option>50</option>
              <option>100</option>
            </select>
          </div>
          <span className="text-sm text-gray-600">1-3 of 3</span>
          <div className="flex items-center gap-1">
            <button disabled className="p-1 hover:bg-gray-100 rounded-full disabled:opacity-30 disabled:cursor-not-allowed">
              <ChevronLeft className="w-5 h-5" />
            </button>
            <button disabled className="p-1 hover:bg-gray-100 rounded-full disabled:opacity-30 disabled:cursor-not-allowed">
              <ChevronRight className="w-5 h-5" />
            </button>
          </div>
        </div>
      </Card>
    </div>
  )
}
