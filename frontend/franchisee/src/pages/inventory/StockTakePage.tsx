import { useState, useEffect } from 'react'
import { useQuery, useMutation } from '@tanstack/react-query'
import { addMonths, isPast, format, parseISO } from 'date-fns'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { stockTakeApi, inventoryApi } from '../../api/services'
import { StockTakeHistoryModal } from '../../components/modals/StockTakeHistoryModal'
import type { StockTake } from '../../types'

// Static stock items - based on screenshot analysis
const STOCK_ITEMS = [
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

export function StockTakePage() {
  const [quantities, setQuantities] = useState<Record<string, { qty: string; percent: string }>>({})
  const [isLocked, setIsLocked] = useState(false)
  const [nextAvailableDate, setNextAvailableDate] = useState<Date | null>(null)
  const [lastStockTakeDate, setLastStockTakeDate] = useState<Date | null>(null)
  const [showHistory, setShowHistory] = useState(false)

  // Mock category ID - in production this should come from params or context
  const categoryId = 'shampoo-order'

  // Fetch inventory items for current SOH
  const { data: inventoryItems = [] } = useQuery({
    queryKey: ['inventory', 'shampoo'],
    queryFn: () => inventoryApi.getItems({ category: 'shampoo' }),
  })

  // Fetch last stock take
  const { data: lastStockTake } = useQuery({
    queryKey: ['stock-take', categoryId],
    queryFn: () => stockTakeApi.getLast(categoryId),
  })

  // Initialize quantities with current SOH values - only once, or when new items are missing
  useEffect(() => {
    const defaultQuantities = { ...quantities }
    let hasChanges = false

    STOCK_ITEMS.forEach((item) => {
      // If user has already entered a value for this item, skip it
      if (defaultQuantities[item.id] && defaultQuantities[item.id].qty) {
        return
      }

      // Find matching inventory item by ID or SKU
      const inventoryItem = inventoryItems.find(
        (inv) => inv.id === item.id || inv.sku === item.id || inv.sku === item.code.replace(/\[|\]/g, '')
      )

      // Set qty to current SOH or 0 if not found
      const qty = inventoryItem ? String(inventoryItem.quantity) : '0'
      defaultQuantities[item.id] = { qty, percent: '' }
      hasChanges = true
    })

    if (hasChanges) {
      setQuantities(defaultQuantities)
    }
  }, [inventoryItems])

  // Check if 4 months have passed
  useEffect(() => {
    if (lastStockTake?.updated_at) {
      const lastDate = parseISO(lastStockTake.updated_at)
      setLastStockTakeDate(lastDate)

      const nextDate = addMonths(lastDate, 4)
      setNextAvailableDate(nextDate)

      // If next date has passed, unlock; otherwise lock
      const isUnlocked = isPast(nextDate)
      setIsLocked(!isUnlocked)
    } else {
      // If no previous stocktake, allow entry
      setIsLocked(false)
    }
  }, [lastStockTake])

  const handleInputChange = (id: string, field: 'qty' | 'percent', value: string) => {
    let processedValue = value

    if (field === 'qty') {
      // Only allow integers, remove leading zeros
      processedValue = value.replace(/\D/g, '') // Remove non-digits
      if (processedValue.startsWith('0') && processedValue.length > 1) {
        processedValue = processedValue.replace(/^0+/, '') // Remove leading zeros
      }
    } else if (field === 'percent') {
      // Only allow 0-99.99 format (up to 2 decimal places)
      if (value === '') {
        processedValue = ''
      } else {
        // Allow only digits and one decimal point
        processedValue = value.replace(/[^\d.]/g, '')
        
        // Ensure only one decimal point
        const parts = processedValue.split('.')
        if (parts.length > 2) {
          processedValue = parts[0] + '.' + parts.slice(1).join('')
        }

        // Remove leading zeros before decimal point (but allow single 0)
        if (parts[0].length > 1 && parts[0].startsWith('0')) {
          parts[0] = parts[0].replace(/^0+/, '') || '0'
          processedValue = parts.join('.')
        }

        // Limit to 2 decimal places
        if (parts.length === 2 && parts[1].length > 2) {
          processedValue = parts[0] + '.' + parts[1].substring(0, 2)
        }

        // Ensure value doesn't exceed 99.99
        const numValue = parseFloat(processedValue)
        if (!isNaN(numValue) && numValue > 99.99) {
          processedValue = '99.99'
        }
      }
    }

    setQuantities((prev) => ({
      ...prev,
      [id]: { ...(prev[id] || { qty: '', percent: '' }), [field]: processedValue },
    }))
  }

  const submitMutation = useMutation({
    mutationFn: async () => {
      await stockTakeApi.submit(categoryId, quantities)
    },
    onSuccess: () => {
      setQuantities({})
      setIsLocked(true)
      setLastStockTakeDate(new Date())
      setNextAvailableDate(addMonths(new Date(), 4))
    },
  })

  const hasFilledData = Object.values(quantities).some((item) => item.qty || item.percent)

  return (
    <div className="space-y-5 px-1 py-1">
      <Card className="px-6 py-4 shadow-sm border-gray-200">
        <h1 className="text-xl font-bold text-gray-800">Stock Take - Shampoo Order</h1>
      </Card>

      {/* Status Alert and Action Button Row */}
      {isLocked ? (
        <div className="bg-amber-50 p-4 border border-amber-200 rounded-md">
          <p className="text-amber-800 font-semibold text-sm">
            Stock Take is locked. Last updated: {lastStockTakeDate ? format(lastStockTakeDate, 'EEEE, do MMM yyyy') : 'N/A'}
          </p>
          <p className="text-amber-700 italic text-sm mt-1">
            Next available stock take: {nextAvailableDate ? format(nextAvailableDate, 'EEEE, do MMM yyyy') : 'N/A'}
          </p>
        </div>
      ) : (
        <div className="flex items-center justify-between bg-green-50 p-4 border border-green-200 rounded-md">
          <div className="flex-1">
            <p className="text-green-800 font-semibold text-sm">
              Stock Take is available. You can now enter stock quantities.
            </p>
            {lastStockTakeDate && (
              <p className="text-green-700 italic text-sm mt-1">
                Last stock take was: {format(lastStockTakeDate, 'EEEE, do MMM yyyy')}
              </p>
            )}
          </div>
          <Button
            variant="secondary"
            onClick={() => setShowHistory(true)}
            className="bg-gray-100 text-gray-700 hover:bg-gray-200 border-none px-6 ml-4 whitespace-nowrap"
          >
            VIEW STOCK HISTORY
          </Button>
        </div>
      )}

      <Card className="shadow-sm border-gray-200 overflow-visible">
        <div className="overflow-x-auto overflow-y-visible">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="px-6 py-4 text-sm font-semibold text-gray-800">Item Name</th>
                <th className="px-6 py-4 text-sm font-semibold text-gray-800 text-center">Total Quantity (Bottles)</th>
                <th className="px-6 py-4 text-sm font-semibold text-gray-800 text-right">Last used bottle remaining %</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {STOCK_ITEMS.map((item) => (
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
                        className="w-24 text-center border-b border-dotted border-gray-400 bg-transparent outline-none py-1 focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        placeholder="0"
                        disabled={isLocked}
                      />
                    </div>
                  </td>
                  <td className="px-6 py-6 text-right">
                    <div className="flex justify-end flex-col items-end">
                      <input
                        type="text"
                        value={quantities[item.id]?.percent || ''}
                        onChange={(e) => handleInputChange(item.id, 'percent', e.target.value)}
                        className="w-24 text-center border-b border-dotted border-gray-400 bg-transparent outline-none py-1 focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        placeholder="0.00"
                        disabled={isLocked}
                      />
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="p-6 bg-white border-t border-gray-200 flex justify-end gap-3">
          {!isLocked && hasFilledData && (
            <Button
              variant="secondary"
              onClick={() => setQuantities({})}
              className="bg-gray-100 text-gray-700 hover:bg-gray-200 border-none px-6"
            >
              CLEAR ENTRIES
            </Button>
          )}
          <Button
            disabled={isLocked || !hasFilledData || submitMutation.isPending}
            onClick={() => submitMutation.mutate()}
            className={`px-6 ${
              isLocked || !hasFilledData
                ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
                : 'bg-blue-600 text-white hover:bg-blue-700'
            }`}
          >
            {submitMutation.isPending ? 'SUBMITTING...' : 'UPDATE STOCK ON HAND'}
          </Button>
        </div>
      </Card>

      <StockTakeHistoryModal
        isOpen={showHistory}
        onClose={() => setShowHistory(false)}
        categoryId={categoryId}
      />
    </div>
  )
}
