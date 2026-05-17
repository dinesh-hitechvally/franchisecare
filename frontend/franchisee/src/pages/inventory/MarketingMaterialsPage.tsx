import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { ShoppingCart } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'

export function MarketingMaterialsPage() {
  const [orders, setOrders] = useState<Record<string, { quantity: number, copies: string }>>({})

  // Mocks based on screenshot analysis
  const products = [
    { id: '10016', name: 'BW Business Cards (1,000)', price: 145.00, code: '[10016]', hasCopies: false },
    { id: '10016-f', name: 'BW Flyers', price: 0, code: '[10016]', hasCopies: true },
    { id: '0016', name: 'BW Magnets (500)', price: 170.00, code: '[0016]', hasCopies: false },
    { id: '10014', name: 'DD Business Cards (1,000)', price: 145.00, code: '[10014]', hasCopies: false },
    { id: '10017', name: 'DD Flyers', price: 0, code: '[10017]', hasCopies: true },
  ]

  const copiesOptions = [
    { label: 'Select No of copies*', value: '' },
    { label: '500 copies (+$80)', value: '500', addCost: 80 },
    { label: '1000 copies (+$120)', value: '1000', addCost: 120 },
    { label: '2500 copies (+$200)', value: '2500', addCost: 200 },
  ]

  const calculateRowTotal = (product: typeof products[0]) => {
    const order = orders[product.id]
    if (!order) return 0
    let cost = product.price
    if (product.hasCopies && order.copies) {
      const option = copiesOptions.find(o => o.value === order.copies)
      if (option && option.addCost) {
        cost += option.addCost
      }
    }
    return cost * (order.quantity || 0)
  }

  const totalCost = products.reduce((total, p) => total + calculateRowTotal(p), 0)

  const handleQuantityChange = (id: string, value: number) => {
    setOrders(prev => ({
      ...prev,
      [id]: { copies: prev[id]?.copies || '', quantity: value >= 0 ? value : 0 }
    }))
  }

  const handleCopiesChange = (id: string, value: string) => {
    setOrders(prev => ({
      ...prev,
      [id]: { quantity: prev[id]?.quantity || 0, copies: value }
    }))
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Business Card, Magnet, Flyers"
        icon={<ShoppingCart className="w-5 h-5" />}
      />

      <div className="bg-white p-4 shadow-sm rounded-md border border-gray-200">
        <p className="text-gray-800 text-sm font-medium">All prices are GST included.</p>
      </div>

      <Card className="border border-gray-200 shadow-sm overflow-hidden border-t-4 border-t-gray-900">
        <div className="bg-gray-100 p-4 border-b border-gray-200">
          <h2 className="font-bold text-gray-800 uppercase tracking-wider text-sm">Business Cards, Flyers, Magnets</h2>
        </div>

        <div className="divide-y divide-gray-100">
          {products.map(product => {
            const currentQty = orders[product.id]?.quantity || 0;
            const currentCopies = orders[product.id]?.copies || '';
            const rowTotal = calculateRowTotal(product);

            return (
              <div key={product.id} className="p-6 bg-white hover:bg-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-6 transition-colors">
                
                {/* Item Details */}
                <div className="flex-1 flex flex-col md:flex-row items-start md:items-center gap-4">
                  <div className="w-16 h-16 bg-gray-100 flex items-center justify-center text-xs text-gray-400 border border-gray-200 rounded shrink-0">
                    Preview
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900 text-base mb-1">
                      {product.name} <span className="text-gray-500 font-normal">{product.code}</span>
                    </h3>
                  </div>
                </div>

                {/* Controls */}
                <div className="flex flex-wrap md:flex-nowrap items-center gap-6">
                  {/* Quantity */}
                  <div className="flex flex-col">
                    <label className="text-xs text-gray-500 mb-1">Add Quantity</label>
                    <input 
                      type="number" 
                      min="0"
                      value={currentQty}
                      onChange={(e) => handleQuantityChange(product.id, parseInt(e.target.value))}
                      className="w-24 border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 bg-white text-center"
                    />
                  </div>

                  {/* Copies Dropdown (Conditional) */}
                  {product.hasCopies && (
                    <div className="flex flex-col">
                      <label className="text-xs text-gray-500 mb-1">Select No of copies<span className="text-red-500">*</span></label>
                      <select
                        value={currentCopies}
                        onChange={(e) => handleCopiesChange(product.id, e.target.value)}
                        className={`w-40 border rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 outline-none ${
                          currentQty > 0 && !currentCopies
                            ? 'border-red-500 bg-red-50' 
                            : 'border-gray-300 bg-white'
                        }`}
                      >
                        {copiesOptions.map(opt => (
                          <option key={opt.value} value={opt.value}>{opt.label}</option>
                        ))}
                      </select>
                    </div>
                  )}

                  {/* Row Total */}
                  <div className="w-24 text-right shrink-0">
                    <span className="font-semibold text-gray-900 text-lg">${rowTotal.toFixed(2)}</span>
                  </div>
                </div>

              </div>
            );
          })}
        </div>

        <div className="p-6 border-t border-gray-200 bg-gray-50 space-y-6">
          {/* Payment Section */}
          <div>
            <h3 className="font-semibold text-gray-800 mb-3 text-sm">Payment <span className="text-red-500">*</span></h3>
            <label className="flex items-center gap-2 cursor-pointer w-max">
              <input type="radio" checked readOnly className="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" />
              <span className="text-sm text-gray-700">Pay with Mate Pay</span>
            </label>
          </div>

          {/* Financial Summary */}
          <div className="border border-gray-200 rounded-md overflow-hidden max-w-lg ml-auto bg-white">
            <div className="divide-y divide-gray-100">
              <div className="flex justify-between px-4 py-3">
                <span className="text-sm font-medium text-gray-600">Shipping Cost</span>
                <span className="text-sm font-semibold text-gray-800">$0.00</span>
              </div>
              <div className="flex justify-between px-4 py-3">
                <span className="text-sm font-medium text-gray-600">Total Weight</span>
                <span className="text-sm font-semibold text-gray-800">0 Kg</span>
              </div>
              <div className="flex justify-between px-4 py-3">
                <span className="text-sm font-medium text-gray-600">Sub Total</span>
                <span className="text-sm font-semibold text-gray-800">${totalCost.toFixed(2)}</span>
              </div>
              <div className="flex justify-between px-4 py-3">
                <span className="text-sm font-medium text-gray-600">Pick and Pack Order Fee</span>
                <span className="text-sm font-semibold text-gray-800">$0.00</span>
              </div>
              <div className="flex justify-between px-4 py-3">
                <span className="text-sm font-medium text-gray-600">SurCharge</span>
                <span className="text-sm font-semibold text-gray-800">$0.00</span>
              </div>
              <div className="flex justify-between px-4 py-3 bg-gray-50 border-t border-gray-200 text-base">
                <span className="font-bold text-gray-900">Total Order</span>
                <span className="font-bold text-blue-600">${totalCost.toFixed(2)}</span>
              </div>
            </div>
          </div>

          {/* Action Buttons */}
          <div className="flex justify-end gap-4 pt-4 border-t border-gray-200">
            <Button variant="secondary" className="px-6 bg-white shrink-0">
              Calculate Shipping
            </Button>
            <Button className="px-8 bg-blue-600 hover:bg-blue-700 shrink-0">
              Review Order & Pay
            </Button>
          </div>
        </div>
      </Card>
    </div>
  )
}
