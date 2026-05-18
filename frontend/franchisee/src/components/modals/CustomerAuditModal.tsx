import React from 'react'
import { Modal } from '../ui/Modal'
import { History, Clock, Check, X, Info, User, Mail, Phone, MapPin } from 'lucide-react'
import { useQuery } from '@tanstack/react-query'
import { customersApi } from '../../api/services'
import { Loader2 } from 'lucide-react'
import { formatDisplayDateTime } from '../../lib/timeFormatUtils'

interface CustomerAuditModalProps {
  isOpen: boolean
  onClose: () => void
  customer: {
    id: string
    first_name: string
    last_name: string
  } | null
}

export const CustomerAuditModal: React.FC<CustomerAuditModalProps> = ({ isOpen, onClose, customer }) => {
  const [page, setPage] = React.useState(1)
  
  const { data: response, isLoading } = useQuery({
    queryKey: ['customer-audits', customer?.id, page],
    queryFn: () => (customer ? customersApi.getAudits(customer.id, page) : Promise.resolve({ data: [] })),
    enabled: !!customer && isOpen,
  })

  // Reset page when modal opens or customer changes
  React.useEffect(() => {
    if (isOpen) setPage(1)
  }, [isOpen, customer?.id])

  const audits = (response as any)?.data || []
  const currentPage = (response as any)?.current_page || 1
  const lastPage = (response as any)?.last_page || 1

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title="Customer Audit Trail"
      size="lg"
    >
      <div className="p-1">
        <div className="flex items-center gap-3 mb-6 p-4 bg-indigo-50 border border-indigo-100 rounded-lg">
          <div className="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-lg">
            <History className="w-6 h-6" />
          </div>
          <div>
            <h3 className="text-lg font-bold text-gray-800">{customer?.first_name} {customer?.last_name} History</h3>
            <p className="text-xs text-gray-500 uppercase font-bold tracking-wider">
              Viewing recorded changes (Page {currentPage} of {lastPage})
            </p>
          </div>
        </div>

        {isLoading ? (
          <div className="flex justify-center p-12">
            <Loader2 className="w-8 h-8 animate-spin text-indigo-600" />
          </div>
        ) : !audits || audits.length === 0 ? (
          <div className="text-center py-12 bg-gray-50 rounded-lg border border-dashed border-gray-200">
            <Info className="w-8 h-8 text-gray-300 mx-auto mb-2" />
            <p className="text-gray-400 italic">No audit history found for this customer.</p>
          </div>
        ) : (
          <>
            <div className="relative border-l-2 border-gray-100 ml-4 pl-8 space-y-8 py-4 min-h-[300px]">
              {audits.map((audit: any) => (
                <div key={audit.id} className="relative">
                  {/* Timeline dot */}
                  <div className={`absolute -left-[41px] top-0 w-6 h-6 rounded-full border-2 border-white shadow-sm flex items-center justify-center ${
                    audit.action_type === 'created' ? 'bg-green-500' : 
                    audit.action_type === 'archived' ? 'bg-amber-500' :
                    audit.action_type === 'restored' ? 'bg-emerald-500' :
                    'bg-blue-500'
                  }`}>
                    <Clock className="w-3 h-3 text-white" />
                  </div>

                  <div className="bg-white border rounded-lg shadow-sm overflow-hidden">
                    <div className="px-4 py-2 bg-gray-50 border-b flex justify-between items-center">
                      <span className={`text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded ${
                        audit.action_type === 'created' ? 'bg-green-100 text-green-700' : 
                        audit.action_type === 'archived' ? 'bg-amber-100 text-amber-700' :
                        audit.action_type === 'restored' ? 'bg-emerald-100 text-emerald-700' :
                        'bg-blue-100 text-blue-700'
                      }`}>
                        {audit.action_type}
                      </span>
                      <span className="text-xs text-gray-400 font-medium">
                        {formatDisplayDateTime(audit.created_at)}
                      </span>
                    </div>
                    
                    <div className="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 text-sm">
                      <div className="flex items-start gap-2">
                        <User className="w-4 h-4 text-gray-400 mt-0.5 shrink-0" />
                        <div>
                          <label className="text-[10px] font-bold text-gray-400 uppercase block mb-0.5">Full Name</label>
                          <p className="font-bold text-gray-700">{audit.first_name} {audit.last_name}</p>
                        </div>
                      </div>

                      <div className="flex items-start gap-2">
                        <Mail className="w-4 h-4 text-gray-400 mt-0.5 shrink-0" />
                        <div>
                          <label className="text-[10px] font-bold text-gray-400 uppercase block mb-0.5">Primary Email</label>
                          <p className="text-gray-700 font-medium">{audit.email || '-'}</p>
                          {audit.other_email && <p className="text-[11px] text-gray-400 mt-0.5 italic">{audit.other_email}</p>}
                        </div>
                      </div>

                      <div className="flex items-start gap-2">
                        <Phone className="w-4 h-4 text-gray-400 mt-0.5 shrink-0" />
                        <div>
                          <label className="text-[10px] font-bold text-gray-400 uppercase block mb-0.5">Contact Number</label>
                          <p className="text-gray-700 font-medium">{audit.phone || '-'}</p>
                          {audit.other_phone && <p className="text-[11px] text-gray-400 mt-0.5 italic">{audit.other_phone}</p>}
                        </div>
                      </div>

                      <div className="flex items-start gap-2 lg:col-span-2">
                        <MapPin className="w-4 h-4 text-gray-400 mt-0.5 shrink-0" />
                        <div>
                          <label className="text-[10px] font-bold text-gray-400 uppercase block mb-0.5">Service Address</label>
                          <p className="text-gray-700 leading-snug">{audit.address || 'No address recorded'}</p>
                          {audit.suburb && <p className="text-[11px] text-gray-400 mt-0.5">{audit.suburb}, {audit.state} {audit.postcode}</p>}
                        </div>
                      </div>

                      <div className="flex items-start gap-2">
                        <Info className="w-4 h-4 text-gray-400 mt-0.5 shrink-0" />
                        <div>
                          <label className="text-[10px] font-bold text-gray-400 uppercase block mb-0.5">Account Status</label>
                          <div className="flex items-center gap-1.5 mt-0.5">
                            {audit.isActive ? (
                              <div className="flex items-center gap-1"><Check className="w-3 h-3 text-green-500" /> <span className="text-green-600 text-xs font-bold">Active</span></div>
                            ) : (
                              <div className="flex items-center gap-1"><X className="w-3 h-3 text-red-500" /> <span className="text-red-600 text-xs font-bold">Inactive</span></div>
                            )}
                            {audit.is_archived && (
                              <span className="text-[10px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded font-bold uppercase ml-2">Archived</span>
                            )}
                          </div>
                        </div>
                      </div>
                    </div>

                    {(audit.notes || audit.referred_by) && (
                      <div className="px-4 pb-4 grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-gray-50 pt-3">
                        {audit.notes && (
                          <div>
                            <label className="text-[10px] font-bold text-gray-400 uppercase">Customer Notes</label>
                            <p className="text-xs text-gray-500 mt-1 italic p-2 bg-gray-50 rounded border border-gray-100 leading-relaxed">
                              {audit.notes}
                            </p>
                          </div>
                        )}
                        {audit.referred_by && (
                          <div>
                            <label className="text-[10px] font-bold text-gray-400 uppercase">Referred By</label>
                            <p className="text-xs text-gray-600 mt-1 font-medium">{audit.referred_by}</p>
                          </div>
                        )}
                      </div>
                    )}
                  </div>
                </div>
              ))}
            </div>

            {/* Pagination Controls */}
            {lastPage > 1 && (
              <div className="mt-8 flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-100">
                <button
                  onClick={() => setPage(p => Math.max(1, p - 1))}
                  disabled={currentPage === 1}
                  className="px-4 py-2 bg-white border rounded shadow-sm text-sm font-bold text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
                >
                  Previous
                </button>
                
                <div className="text-xs font-bold text-gray-400 uppercase tracking-widest text-center flex-1">
                  Page <span className="text-indigo-600">{currentPage}</span> of <span className="text-gray-600">{lastPage}</span>
                </div>

                <button
                  onClick={() => setPage(p => Math.min(lastPage, p + 1))}
                  disabled={currentPage === lastPage}
                  className="px-4 py-2 bg-white border rounded shadow-sm text-sm font-bold text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
                >
                  Next
                </button>
              </div>
            )}
          </>
        )}

        <div className="mt-8 flex justify-end">
          <button
            onClick={onClose}
            className="px-8 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded font-bold text-sm transition-colors"
          >
            Close History
          </button>
        </div>
      </div>
    </Modal>
  )
}
