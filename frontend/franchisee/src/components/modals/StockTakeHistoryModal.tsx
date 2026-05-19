import React from 'react'
import { useQuery } from '@tanstack/react-query'
import { Modal } from '../ui/Modal'
import { X } from 'lucide-react'
import { format, parseISO } from 'date-fns'
import { stockTakeApi } from '../../api/services'
import type { StockMovement } from '../../types'

interface StockTakeHistoryModalProps {
  isOpen: boolean
  onClose: () => void
  categoryId: string
}

export const StockTakeHistoryModal: React.FC<StockTakeHistoryModalProps> = ({ isOpen, onClose, categoryId }) => {
  const { data: history = [], isLoading } = useQuery({
    queryKey: ['stock-take-history', categoryId],
    queryFn: async () => {
      const response = await stockTakeApi.getHistory(categoryId)
      return response.data as StockMovement[]
    },
    enabled: isOpen,
  })

  const formatMovementType = (type?: string) => {
    const types: Record<string, string> = {
      stock_take: 'Stock Take',
      booking_usage: 'Booking Usage',
      adjustment: 'Adjustment',
      inward: 'Inward Goods',
      write_off: 'Write Off',
    }
    return types[type || ''] || type || '-'
  }

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      size="xl"
    >
      <div className="-mt-4 -mx-6 -mb-4">
        {/* Top Header Strip (Blue) */}
        <div className="bg-blue-600 px-6 py-4 flex justify-between items-center">
          <h2 className="text-lg font-bold text-white">Stock Movement History</h2>
          <button onClick={onClose} className="text-white/80 hover:text-white transition-colors">
            <X className="w-6 h-6" />
          </button>
        </div>

        <div className="p-6 bg-white overflow-x-auto">
          {isLoading ? (
            <div className="text-center py-8 text-gray-500">
              <p>Fetching History...</p>
            </div>
          ) : history.length === 0 ? (
            <div className="text-center py-8 text-gray-500">
              <p>No stock movement history found</p>
            </div>
          ) : (
            <table className="w-full text-sm border-collapse">
              <thead>
                <tr className="border-b border-gray-200">
                  <th className="px-4 py-3 text-left font-semibold text-gray-700">Date</th>
                  <th className="px-4 py-3 text-left font-semibold text-gray-700">Item</th>
                  <th className="px-4 py-3 text-center font-semibold text-gray-700">Type</th>
                  <th className="px-4 py-3 text-center font-semibold text-gray-700">Qty Before</th>
                  <th className="px-4 py-3 text-center font-semibold text-gray-700">Qty After</th>
                  <th className="px-4 py-3 text-center font-semibold text-gray-700">Change</th>
                  <th className="px-4 py-3 text-center font-semibold text-gray-700">% Before</th>
                  <th className="px-4 py-3 text-center font-semibold text-gray-700">% After</th>
                  <th className="px-4 py-3 text-left font-semibold text-gray-700">By</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {history.map((movement, index) => (
                  <tr key={movement.id || index} className="hover:bg-gray-50 transition-colors">
                    <td className="px-4 py-3 text-gray-700">
                      {movement.created_at
                        ? format(parseISO(movement.created_at), 'MMM dd, yyyy HH:mm')
                        : '-'}
                    </td>
                    <td className="px-4 py-3 text-gray-700">
                      {movement.inventory?.name || `ID: ${movement.inventory_id}` || '-'}
                    </td>
                    <td className="px-4 py-3 text-center">
                      <span className={`inline-flex px-2 py-1 rounded text-xs font-medium ${
                        movement.movement_type === 'stock_take' ? 'bg-blue-100 text-blue-700' :
                        movement.movement_type === 'inward' ? 'bg-green-100 text-green-700' :
                        movement.movement_type === 'booking_usage' ? 'bg-orange-100 text-orange-700' :
                        movement.movement_type === 'write_off' ? 'bg-red-100 text-red-700' :
                        'bg-gray-100 text-gray-700'
                      }`}>
                        {formatMovementType(movement.movement_type)}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-center text-gray-700">{movement.quantity_before ?? '-'}</td>
                    <td className="px-4 py-3 text-center text-gray-700">{movement.quantity_after ?? '-'}</td>
                    <td className="px-4 py-3 text-center">
                      <span className={`font-medium ${
                        (movement.quantity_change ?? 0) > 0 ? 'text-green-600' :
                        (movement.quantity_change ?? 0) < 0 ? 'text-red-600' :
                        'text-gray-600'
                      }`}>
                        {(movement.quantity_change ?? 0) > 0 ? '+' : ''}{movement.quantity_change ?? 0}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-center text-gray-700">
                      {movement.percentage_before !== undefined ? `${movement.percentage_before}%` : '-'}
                    </td>
                    <td className="px-4 py-3 text-center text-gray-700">
                      {movement.percentage_after !== undefined ? `${movement.percentage_after}%` : '-'}
                    </td>
                    <td className="px-4 py-3 text-gray-700">
                      {movement.performer
                        ? `${movement.performer.first_name || ''} ${movement.performer.last_name || ''}`.trim() || movement.performer.name
                        : '-'}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}

          <div className="mt-6 flex justify-end">
            <button
              onClick={onClose}
              className="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors font-medium"
            >
              CLOSE
            </button>
          </div>
        </div>
      </div>
    </Modal>
  )
}
