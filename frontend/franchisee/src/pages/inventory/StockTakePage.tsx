import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'

export function StockTakePage() {
  const [quantities, setQuantities] = useState<Record<string, { qty: string, percent: string }>>({})

  // Mocks based on screenshot analysis
  const stockItems = [
    { id: 'SH201', name: 'Herbal Deluxe Shampoo', code: '[SH201]' },
    { id: 'SH202', name: 'Whitening Deluxe Shampoo', code: '[SH202]' },
    { id: 'SH203', name: 'HydroClean Sanitiser', code: '[SH203]' },
    { id: 'SH206', name: 'Medicated Wash', code: '[SH206]' },
    { id: 'BWD', name: 'Blue Wheelers Detangler (5Lt)', code: '[BWD]' },
    { id: 'BWDS', name: 'Blue Wheelers NEW Deshed', code: '[BWDS]' },
    { id: 'Conditioner', name: 'Conditioner', code: '[Conditioner]' },
    { id: 'SH204', name: 'Cologne (NEW)', code: '[SH204]' },
    { id: 'BWFT', name: 'Flea & Tick rinse', code: '[BWFT]' },
  ]

  const handleInputChange = (id: string, field: 'qty' | 'percent', value: string) => {
    setQuantities(prev => ({
      ...prev,
      [id]: { ...(prev[id] || { qty: '', percent: '' }), [field]: value }
    }))
  }

  return (
    <div className="space-y-6">
      {/* Header Bar */}
      <div className="bg-blue-600 text-white font-bold py-2 px-8 -mt-6 -mx-8 shadow-sm">
        SHAMPOO ORDER
      </div>

      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8">
        <h1 className="text-2xl font-bold text-gray-800">
          Stock Take - Shampoo Order
        </h1>
      </div>

      {/* Action Buttons Row */}
      <div className="flex justify-end">
        <Button variant="secondary" className="bg-gray-100 text-gray-700 hover:bg-gray-200 border-none px-6">
          VIEW STOCK HISTORY
        </Button>
      </div>

      {/* Informational Alert */}
      <div className="bg-gray-100 p-4 border border-gray-200 rounded-md">
        <p className="text-gray-700 italic text-sm">
          Shampoo Order has already been updated. Please contact admin if you want to do stock take again.
        </p>
      </div>

      <Card className="border border-gray-200 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left border-collapse">
            <thead className="bg-gray-900 text-white font-medium">
              <tr>
                <th className="px-6 py-4 font-semibold tracking-wider">Item Name</th>
                <th className="px-6 py-4 font-semibold tracking-wider text-center">Total Quantity (Bottles)</th>
                <th className="px-6 py-4 font-semibold tracking-wider text-right">Last used bottle remaining %</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {stockItems.map((item) => (
                <tr key={item.id} className="bg-white">
                  <td className="px-6 py-6 text-gray-500 font-medium">
                    {item.name} {item.code}
                  </td>
                  <td className="px-6 py-6 text-center">
                    <div className="flex justify-center flex-col items-center">
                      <input
                        type="text"
                        value={quantities[item.id]?.qty || ''}
                        onChange={(e) => handleInputChange(item.id, 'qty', e.target.value)}
                        className="w-24 text-center border-b border-dotted border-gray-400 bg-transparent outline-none py-1 focus:border-blue-500"
                        placeholder="..."
                        disabled
                      />
                    </div>
                  </td>
                  <td className="px-6 py-6 text-right">
                    <div className="flex justify-end flex-col items-end">
                      <input
                        type="text"
                        value={quantities[item.id]?.percent || ''}
                        onChange={(e) => handleInputChange(item.id, 'percent', e.target.value)}
                        className="w-24 text-center border-b border-dotted border-gray-400 bg-transparent outline-none py-1 focus:border-blue-500"
                        placeholder="..."
                        disabled
                      />
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="p-6 bg-white border-t border-gray-200 flex justify-end">
          <Button disabled className="bg-gray-200 text-gray-400 cursor-not-allowed px-6">
            UPDATE STOCK ON HAND
          </Button>
        </div>
      </Card>
    </div>
  )
}
