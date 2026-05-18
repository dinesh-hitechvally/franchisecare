import React from 'react'
import { Modal } from '../ui/Modal'
import { X, MapPin, Calendar, Clock, RotateCcw, FileText } from 'lucide-react'
import { formatDisplayDate, formatDisplayTime } from '../../lib/timeFormatUtils'
import type { Blockout } from '../../types'

interface BlockoutDetailModalProps {
  isOpen: boolean
  onClose: () => void
  blockout: Blockout | null
}

export const BlockoutDetailModal: React.FC<BlockoutDetailModalProps> = ({ isOpen, onClose, blockout }) => {
  if (!blockout) return null

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      size="lg"
    >
      <div className="-mt-4 -mx-6 -mb-4">
        {/* Top Header Strip (Purple) */}
        <div className="bg-purple-600 px-6 py-4 flex justify-between items-center">
          <h2 className="text-lg font-bold text-white">Blockout Details</h2>
          <button onClick={onClose} className="text-white/80 hover:text-white transition-colors">
            <X className="w-6 h-6" />
          </button>
        </div>

        <div className="p-8 bg-white">
          <div className="space-y-6">
            {/* Title Section */}
            <div>
              <h3 className="text-2xl font-bold text-gray-900">{blockout.title}</h3>
              {blockout.location && (
                <div className="flex items-center gap-2 text-gray-600 mt-2">
                  <MapPin className="w-4 h-4" />
                  <span>{blockout.location}</span>
                </div>
              )}
            </div>

            {/* Date & Time Section */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-6 rounded-lg">
              <div>
                <div className="flex items-center gap-2 text-sm text-gray-500 mb-2">
                  <Calendar className="w-4 h-4" />
                  <span className="font-semibold">Start Date & Time</span>
                </div>
                <p className="text-lg font-medium text-gray-900">
                  {formatDisplayDate(blockout.startDate)}
                </p>
                <p className="text-gray-600 flex items-center gap-1 mt-1">
                  <Clock className="w-4 h-4" />
                  {formatDisplayTime(blockout.startTime)}
                </p>
              </div>

              <div>
                <div className="flex items-center gap-2 text-sm text-gray-500 mb-2">
                  <Calendar className="w-4 h-4" />
                  <span className="font-semibold">End Date & Time</span>
                </div>
                <p className="text-lg font-medium text-gray-900">
                  {formatDisplayDate(blockout.endDate)}
                </p>
                <p className="text-gray-600 flex items-center gap-1 mt-1">
                  <Clock className="w-4 h-4" />
                  {formatDisplayTime(blockout.endTime)}
                </p>
              </div>
            </div>

            {/* Recurring Information */}
            {blockout.isRecurring && (
              <div className="bg-purple-50 p-6 rounded-lg border border-purple-200">
                <div className="flex items-center gap-2 text-purple-700 font-semibold mb-4">
                  <RotateCcw className="w-5 h-5" />
                  <span>Recurring Blockout</span>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                  {blockout.repeatEvery && (
                    <div>
                      <p className="text-gray-500">Repeat Every</p>
                      <p className="font-medium text-gray-900">{blockout.repeatEvery} {blockout.repeatEvery === '1' ? 'Week' : 'Weeks'}</p>
                    </div>
                  )}
                  {blockout.repeatOn && (
                    <div>
                      <p className="text-gray-500">Repeat On</p>
                      <p className="font-medium text-gray-900">{blockout.repeatOn}</p>
                    </div>
                  )}
                  {blockout.repeatUntil && (
                    <div>
                      <p className="text-gray-500">Repeat Until</p>
                      <p className="font-medium text-gray-900">{formatDisplayDate(blockout.repeatUntil)}</p>
                    </div>
                  )}
                </div>
              </div>
            )}

            {/* Notes Section */}
            {blockout.notes && (
              <div>
                <div className="flex items-center gap-2 text-sm text-gray-500 mb-3">
                  <FileText className="w-4 h-4" />
                  <span className="font-semibold">Notes</span>
                </div>
                <p className="text-gray-700 bg-gray-50 p-4 rounded-lg whitespace-pre-wrap">
                  {blockout.notes}
                </p>
              </div>
            )}

            {/* Status Badge */}
            <div className="flex items-center gap-3 pt-4 border-t border-gray-200">
              <span className="text-sm text-gray-500">Status:</span>
              <span className={`px-3 py-1 rounded-full text-sm font-medium ${
                blockout.active
                  ? 'bg-green-100 text-green-800'
                  : 'bg-gray-100 text-gray-800'
              }`}>
                {blockout.active ? 'Active' : 'Inactive'}
              </span>
            </div>
          </div>
        </div>
      </div>
    </Modal>
  )
}
