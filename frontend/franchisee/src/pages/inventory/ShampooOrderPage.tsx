import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { ShoppingCart } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'

export function ShampooOrderPage() {
  const [quantities, setQuantities] = useState<Record<string, number>>({
    'SH201': 0,
    'BWFT': 0,
  })

  // Mocks based on screenshot analysis
  const products = [
    { id: 'SH201', name: 'Herbal Deluxe Shampoo', code: '[SH201]', price: 87.35, unit: '5L' },
    { id: 'BWFT', name: 'Flea & Tick rinse', code: '[BWFT]', price: 95.00, unit: '2.5L' },
    { id: 'SH202', name: 'Puppy Shampoo', code: '[SH202]', price: 85.00, unit: '5L' },
    { id: 'OAT20', name: 'Oatmeal Shampoo', code: '[OAT20]', price: 89.50, unit: '5L' },
    { id: 'CON50', name: 'Conditioner', code: '[CON50]', price: 75.00, unit: '5L' },
  ]

  const totalCost = products.reduce((total, p) => total + (quantities[p.id] || 0) * p.price, 0)
  
  const handleQuantityChange = (id: string, value: number) => {
    setQuantities(prev => ({
      ...prev,
      [id]: value >= 0 ? value : 0
    }))
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Shampoo Order"
        icon={<ShoppingCart className="w-5 h-5" />}
      />

      <div className="bg-white p-4 shadow-sm rounded-md border border-gray-200">
        <p className="text-gray-800 text-sm font-medium">All prices are GST included.</p>
      </div>

      <Card className="border border-gray-200 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="bg-gray-900 text-white font-medium">
              <tr>
                <th className="px-6 py-4">Image</th>
                <th className="px-6 py-4">Item Name</th>
                <th className="px-6 py-4 text-center">Order Quantity</th>
                <th className="px-6 py-4 text-right">Cost Per Item</th>
              </tr>
            </thead>
            <tbody>
              {/* Category Divider */}
              <tr className="bg-gray-100 border-b border-gray-200">
                <td colSpan={4} className="px-6 py-2 font-bold text-gray-800 tracking-wider text-xs">
                  Shampoo Orders - Min order 4
                </td>
              </tr>
              
              {/* Items */}
              {products.map(product => (
                <tr key={product.id} className="bg-white border-b border-gray-200 hover:bg-gray-50">
                  <td className="px-6 py-4">
                    <div className="w-16 h-16 bg-gray-100 flex items-center justify-center text-xs text-gray-400 border border-gray-200 rounded">
                      Image
                    </div>
                  </td>
                  <td className="px-6 py-4 font-medium text-gray-800">
                    {product.name} {product.code}
                  </td>
                  <td className="px-6 py-4 text-center">
                    <div className="flex flex-col items-center">
                      <span className="text-xs text-gray-500 mb-1">{product.name}</span>
                      <input 
                        type="number" 
                        min="0"
                        value={quantities[product.id] || 0}
                        onChange={(e) => handleQuantityChange(product.id, parseInt(e.target.value))}
                        className="w-20 text-center border border-gray-300 rounded px-2 py-1 focus:ring-blue-500 focus:border-blue-500 bg-white"
                      />
                      <span className="text-xs text-gray-500 mt-1">{product.unit}</span>
                    </div>
                  </td>
                  <td className="px-6 py-4 text-right text-gray-800 font-medium">
                    ${product.price.toFixed(2)} /{product.unit}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="p-6 border-t border-gray-200 bg-white space-y-6">
          {/* Payment Section */}
          <div>
            <h3 className="font-semibold text-gray-800 mb-3 text-sm">Payment <span className="text-red-500">*</span></h3>
            <label className="flex items-center gap-2 cursor-pointer">
              <input type="radio" checked readOnly className="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" />
              <span className="text-sm text-gray-700">Pay with Mate Pay</span>
            </label>
          </div>

          {/* Financial Summary */}
          <div className="border border-gray-200 rounded-md overflow-hidden max-w-lg ml-auto">
            <div className="divide-y divide-gray-100">
              <div className="flex justify-between px-4 py-3 bg-white">
                <span className="text-sm font-medium text-gray-600">Shipping Cost</span>
                <span className="text-sm font-semibold text-gray-800">$0.00</span>
              </div>
              <div className="flex justify-between px-4 py-3 bg-white">
                <span className="text-sm font-medium text-gray-600">Total Weight</span>
                <span className="text-sm font-semibold text-gray-800">0 Kg</span>
              </div>
              <div className="flex justify-between px-4 py-3 bg-white">
                <span className="text-sm font-medium text-gray-600">Sub Total</span>
                <span className="text-sm font-semibold text-gray-800">${totalCost.toFixed(2)}</span>
              </div>
              <div className="flex justify-between px-4 py-3 bg-white">
                <span className="text-sm font-medium text-gray-600">Pick and Pack Order Fee</span>
                <span className="text-sm font-semibold text-gray-800">$0.00</span>
              </div>
              <div className="flex justify-between px-4 py-3 bg-white">
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
          <div className="flex justify-end gap-4 pt-4 border-t border-gray-100">
            <Button variant="secondary" className="px-6">
              Calculate Shipping
            </Button>
            <Button className="px-8 bg-blue-600 hover:bg-blue-700">
              Review Order & Pay
            </Button>
          </div>
        </div>
      </Card>
    </div>
  )
}
