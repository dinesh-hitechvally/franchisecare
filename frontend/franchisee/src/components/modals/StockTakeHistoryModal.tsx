import React from 'react'
import { useQuery } from '@tanstack/react-query'
import { Modal } from '../ui/Modal'
import { X } from 'lucide-react'
import { format, parseISO } from 'date-fns'
import { stockTakeApi } from '../../api/services'

interface StockTakeHistoryModalProps {
  isOpen: boolean
  onClose: () => void
  categoryId: string
}

export const StockTakeHistoryModal: React.FC<StockTakeHistoryModalProps> = ({ isOpen, onClose, categoryId }) => {
  const { data: history = [], isLoading } = useQuery({
    queryKey: ['stock-take-history', categoryId],
    queryFn: () => stockTakeApi.getHistory(categoryId),
    enabled: isOpen,
  })

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      size="xl"
    >
      <div className="-mt-4 -mx-6 -mb-4">
        {/* Top Header Strip (Blue) */}
        <div className="bg-blue-600 px-6 py-4 flex justify-between items-center">
          <h2 className="text-lg font-bold text-white">Stock Take History</h2>
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
              <p>No stock take history found</p>
            </div>
          ) : (
            <table className="w-full text-sm border-collapse">
              <thead>
                <tr className="border-b border-gray-200">
                  <th className="px-4 py-3 text-left font-semibold text-gray-700">Log ID</th>
                  <th className="px-4 py-3 text-left font-semibold text-gray-700">Inventory ID</th>
                  <th className="px-4 py-3 text-left font-semibold text-gray-700">Inventory Item Name</th>
                  <th className="px-4 py-3 text-center font-semibold text-gray-700">No. of Bookings</th>
                  <th className="px-4 py-3 text-center font-semibold text-gray-700">Current Stock</th>
                  <th className="px-4 py-3 text-center font-semibold text-gray-700">Remaining Percent</th>
                  <th className="px-4 py-3 text-center font-semibold text-gray-700">Log Quantity</th>
                  <th className="px-4 py-3 text-center font-semibold text-gray-700">Log Remaining Percent</th>
                  <th className="px-4 py-3 text-left font-semibold text-gray-700">Log Created At</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {history.map((log, index) => (
                  <tr key={log.id || index} className="hover:bg-gray-50 transition-colors">
                    <td className="px-4 py-3 text-gray-700">{log.logID || log.id || '-'}</td>
                    <td className="px-4 py-3 text-gray-700">{log.inventory_id || '-'}</td>
                    <td className="px-4 py-3 text-gray-700">{log.inventory_item_name || '-'}</td>
                    <td className="px-4 py-3 text-center text-gray-700">{log.no_of_bookings ?? '-'}</td>
                    <td className="px-4 py-3 text-center text-gray-700">{log.current_stock ?? '-'}</td>
                    <td className="px-4 py-3 text-center text-gray-700">
                      {log.remaining_percent !== undefined ? `${log.remaining_percent}%` : '-'}
                    </td>
                    <td className="px-4 py-3 text-center text-gray-700">{log.log_quantity ?? '-'}</td>
                    <td className="px-4 py-3 text-center text-gray-700">
                      {log.log_remaining_percent !== undefined ? `${log.log_remaining_percent}%` : '-'}
                    </td>
                    <td className="px-4 py-3 text-gray-700">
                      {log.log_created_at || log.createdAt
                        ? format(parseISO(log.log_created_at || log.createdAt!), 'MMM dd, yyyy HH:mm')
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
