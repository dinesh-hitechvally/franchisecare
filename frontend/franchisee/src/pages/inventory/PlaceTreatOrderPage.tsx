import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { ShoppingCart } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'

export function PlaceTreatOrderPage() {
  const [quantities, setQuantities] = useState<Record<string, number>>({})

  // Mocks based on screenshot analysis
  const products = [
    { id: 'BW111', name: 'Baked Bones Beef 1kg', code: '[BW111]', price: 11.70, unit: '1KG' },
    { id: 'BW106', name: 'Baked Bone Biscuits - Cheese 1kg', code: '[BW106]', price: 11.70, unit: '1KG' },
    { id: 'BW107', name: 'Baked Bone Biscuits - Peanut Butter 1kg', code: '[BW107]', price: 11.70, unit: '1KG' },
    { id: 'BW101', name: 'Chicken Jerky 500g', code: '[BW101]', price: 11.50, unit: '500g' },
    { id: 'BW114', name: 'Just Roo 1kg', code: '[BW114]', price: 41.10, unit: '1KG' },
    { id: 'BW115', name: 'Beef Liver Treats 150g', code: '[BW115]', price: 6.90, unit: '150g' },
    { id: 'BW116', name: 'Beef Liver Treats 1kg', code: '[BW116]', price: 39.80, unit: '1KG' },
    { id: 'BW102', name: 'Roo Jerky 500g', code: '[BW102]', price: 11.50, unit: '500g' },
    { id: 'BW112', name: 'Roo Sticks (25pc) 1.5kg', code: '[BW112]', price: 38.65, unit: '1.5kg' },
    { id: 'BW117', name: 'Pig Ears (4pc) 160g', code: '[BW117]', price: 12.45, unit: '160g' },
    { id: 'BW142', name: 'Chicken Breast 1kg', code: '[BW142]', price: 56.00, unit: '1KG' },
    { id: 'BW134', name: 'Grain Free Chicken Mini Sticks 500g', code: '[BW134]', price: 17.40, unit: '500g' },
    { id: 'BW148', name: 'Beef Training Treats (Pellets) 1kg', code: '[BW148]', price: 22.50, unit: '1KG' },
    { id: 'BW149', name: 'Chicken Training Treats (Pellets) 1kg', code: '[BW149]', price: 22.50, unit: '1KG' },
    { id: 'BW150', name: 'Roo Training Treats (Pellets) 1kg', code: '[BW150]', price: 21.30, unit: '1KG' },
    { id: 'BW156', name: 'Pig Snouts 1kg', code: '[BW156]', price: 62.15, unit: '1KG' },
    { id: 'BW160', name: 'Pig Ears (25pc) 1kg', code: '[BW160]', price: 67.80, unit: '1KG' },
    { id: 'BW161', name: 'Lamb Puff Cubes 50g', code: '[BW161]', price: 5.60, unit: '50g' },
    { id: 'BW162', name: 'Lamb Puff Cubes 1kg', code: '[BW162]', price: 63.00, unit: '1KG' },
    { id: 'BW168', name: 'Australian Veggie Tubes 500g', code: '[BW168]', price: 15.25, unit: '500g' },
    { id: 'BW165', name: 'Beef Micro Bones 500g', code: '[BW165]', price: 21.10, unit: '500g' },
    { id: 'BW166', name: 'Chicken Micro Bones 500g', code: '[BW166]', price: 21.10, unit: '500g' },
    { id: 'BW167', name: 'Kangaroo Micro Bones 500g', code: '[BW167]', price: 21.10, unit: '500g' },
    { id: 'BW169', name: 'Golden Paws 500g', code: '[BW169]', price: 31.65, unit: '500g' },
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
        title="Place Treat Order"
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
                  Treats - Over 10kg will attract double freight costs (min order of 5KG)
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
                      <span className="text-xs text-gray-500 mb-1 line-clamp-1 max-w-[120px]" title={product.name}>{product.name}</span>
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
