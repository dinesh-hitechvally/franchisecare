import { useState } from 'react'
import { X } from 'lucide-react'
import { cn } from '../../lib/utils'
import { Modal } from '../../components/ui/Modal'
import type { Lead } from '../../types'
import { format } from 'date-fns'

interface LeadDetailModalProps {
  lead: Lead
  isOpen: boolean
  onClose: () => void
  onComment: (leadId: string, comment: string) => void
  onConvert: (leadId: string) => void
  onSnooze: (leadId: string, snoozeUntil: string) => void
  onAddMorePets?: (leadId: string) => void
}

export function LeadDetailModal({
  lead,
  isOpen,
  onClose,
  onComment,
  onConvert,
  onSnooze,
  onAddMorePets,
}: LeadDetailModalProps) {
  const [comment, setComment] = useState(lead.comments?.[0]?.comment || '')
  const [snoozeDate, setSnoozeDate] = useState('')
  const [showSnoozeInput, setShowSnoozeInput] = useState(false)
  const [showCommentBox, setShowCommentBox] = useState(false)

  if (!isOpen) return null

  const handleCommentSubmit = () => {
    if (comment.trim()) {
      onComment(lead.id, comment)
      setShowCommentBox(false)
    }
  }

  const handleSnooze = () => {
    if (snoozeDate) {
      onSnooze(lead.id, snoozeDate)
      setShowSnoozeInput(false)
      setSnoozeDate('')
    } else {
      setShowSnoozeInput(true)
    }
  }

  const isConverted = lead.status === 'converted'

  return (
    <Modal isOpen={isOpen} onClose={onClose} size="lg">
      <div className="-mt-4 -mx-6 -mb-4">
        {/* Top Header */}
        <div className="bg-[#4a5ebc] px-6 py-4 flex justify-between items-center relative">
          <h2 className="text-lg font-bold text-white">View Leads Detail</h2>
          <button onClick={onClose} className="text-white/80 hover:text-white transition-colors">
            <X className="w-6 h-6" />
          </button>

          {/* NEW LEAD Badge */}
          {lead.status === 'new' && (
            <div className="absolute top-0 right-0 w-32 h-32 overflow-hidden pointer-events-none">
              <div className="absolute top-6 right-[-40px] bg-red-600 text-white text-xs font-bold py-1 px-12 rotate-45 shadow-md uppercase tracking-wide">
                New Lead
              </div>
            </div>
          )}
        </div>

        {/* Customer Name */}
        <div className="px-6 py-4 bg-white border-b border-gray-200">
          <h3 className="text-base font-medium text-gray-900">
            {lead.customerName}{' '}
            <span className="text-blue-600">
              {lead.phone}
              {lead.alternatePhone && ` / ${lead.alternatePhone}`}
            </span>
          </h3>
        </div>

        {/* Content */}
        <div className="p-6 bg-white">
          {/* Detail Tab Label */}
          <div className="mb-4 pb-2 border-b border-gray-200">
            <span className="text-sm font-semibold text-gray-700">Detail</span>
          </div>

          {/* Two Column Grid */}
          <div className="grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
            {/* Left Column */}
            <div className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-gray-600 mb-1 uppercase">
                  Interested Services
                </label>
                <p className="text-sm text-gray-900">{lead.interestedServices || '-'}</p>
              </div>

              <div>
                <label className="block text-xs font-semibold text-gray-600 mb-1 uppercase">
                  Address
                </label>
                <p className="text-sm text-gray-900">{lead.address || '-'}</p>
              </div>

              <div>
                <label className="block text-xs font-semibold text-gray-600 mb-1 uppercase">
                  Referred By
                </label>
                <p className="text-sm text-gray-900">{lead.referredBy || '-'}</p>
              </div>

              <div>
                <label className="block text-xs font-semibold text-gray-600 mb-1 uppercase">
                  ID
                </label>
                <p className="text-xs text-gray-600">{lead.id}</p>
              </div>
            </div>

            {/* Right Column */}
            <div className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-gray-600 mb-1 uppercase">
                  Email
                </label>
                <p className="text-sm text-gray-900">{lead.email || '-'}</p>
              </div>

              <div>
                <label className="block text-xs font-semibold text-gray-600 mb-1 uppercase">
                  Pet (Breed)
                </label>
                <p className="text-sm text-gray-900">{lead.petBreed || '-'}</p>
              </div>

              <div>
                <label className="block text-xs font-semibold text-gray-600 mb-1 uppercase">
                  Additional Note
                </label>
                <p className="text-sm text-gray-900 whitespace-pre-wrap">{lead.additionalNote || '-'}</p>
              </div>

              <div>
                <label className="block text-xs font-semibold text-gray-600 mb-1 uppercase">
                  Lead Updated
                </label>
                <p className="text-xs text-gray-600">
                  {format(new Date(lead.updatedAt), 'EEEE, dd MMM yyyy h:mm a')}
                </p>
              </div>
            </div>
          </div>

          {/* Comment Section - Only show when button clicked */}
          {showCommentBox && (
            <div className="mb-6 animate-in fade-in slide-in-from-top-2 duration-200">
              <label className="block text-xs font-semibold text-gray-600 mb-2 uppercase">
                Comment
              </label>
              <textarea
                value={comment}
                onChange={(e) => setComment(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none text-sm"
                rows={3}
                placeholder="Add a comment..."
                autoFocus
              />
              <div className="flex justify-end gap-2 mt-2">
                <button
                  onClick={() => {
                    setShowCommentBox(false)
                    setComment(lead.comments?.[0]?.comment || '')
                  }}
                  className="px-4 py-1.5 text-sm text-gray-600 hover:text-gray-800 transition-colors"
                >
                  Cancel
                </button>
                <button
                  onClick={handleCommentSubmit}
                  disabled={!comment.trim()}
                  className={cn(
                    'px-4 py-1.5 text-sm font-medium rounded transition-colors',
                    comment.trim()
                      ? 'bg-blue-600 text-white hover:bg-blue-700'
                      : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                  )}
                >
                  Save Comment
                </button>
              </div>
            </div>
          )}

          {/* Action Buttons */}
          <div className="flex items-center justify-between pt-4 border-t border-gray-200">
            <button
              onClick={() => setShowCommentBox(!showCommentBox)}
              className="text-blue-600 hover:text-blue-700 font-medium text-sm transition-colors"
            >
              Comment
            </button>

            <div className="flex items-center gap-4">
              {/* Show "Add More Pets" if converted, otherwise show "Convert To Customer" */}
              {isConverted ? (
                <button
                  onClick={() => onAddMorePets?.(lead.id)}
                  className="text-green-600 hover:text-green-700 font-medium text-sm transition-colors"
                >
                  Add More Pets
                </button>
              ) : (
                <button
                  onClick={() => onConvert(lead.id)}
                  className="text-green-600 hover:text-green-700 font-medium text-sm transition-colors"
                >
                  Convert To Customer
                </button>
              )}

              {showSnoozeInput ? (
                <div className="flex items-center gap-2">
                  <input
                    type="datetime-local"
                    value={snoozeDate}
                    onChange={(e) => setSnoozeDate(e.target.value)}
                    className="px-3 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <button
                    onClick={handleSnooze}
                    disabled={!snoozeDate}
                    className={cn(
                      'px-3 py-1.5 text-sm font-medium rounded transition-colors',
                      snoozeDate
                        ? 'bg-blue-600 text-white hover:bg-blue-700'
                        : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                    )}
                  >
                    OK
                  </button>
                  <button
                    onClick={() => setShowSnoozeInput(false)}
                    className="text-gray-500 hover:text-gray-700 text-sm"
                  >
                    Cancel
                  </button>
                </div>
              ) : (
                <button
                  onClick={handleSnooze}
                  className="text-blue-600 hover:text-blue-700 font-medium text-sm transition-colors"
                >
                  Snooze
                </button>
              )}
            </div>
          </div>
        </div>
      </div>
    </Modal>
  )
}



