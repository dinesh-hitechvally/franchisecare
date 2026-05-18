import React from 'react'
import { Modal } from '../ui/Modal'
import { History, Clock, Check, X, Info } from 'lucide-react'
import { useQuery } from '@tanstack/react-query'
import { petsApi } from '../../api/services'
import { Loader2 } from 'lucide-react'
import { formatDisplayDate, formatDisplayDateTime } from '../../lib/timeFormatUtils'

interface PetAuditModalProps {
  isOpen: boolean
  onClose: () => void
  pet: {
    id: string
    name: string
    breed?: string
    size?: string
  } | null
}

export const PetAuditModal: React.FC<PetAuditModalProps> = ({ isOpen, onClose, pet }) => {
  const [page, setPage] = React.useState(1)
  
  const { data: response, isLoading } = useQuery({
    queryKey: ['pet-audits', pet?.id, page],
    queryFn: () => (pet ? petsApi.getAudits(pet.id, page) : Promise.resolve({ data: [] })),
    enabled: !!pet && isOpen,
  })

  // Reset page when modal opens or pet changes
  React.useEffect(() => {
    if (isOpen) setPage(1)
  }, [isOpen, pet?.id])

  // response from API is already the Laravel paginator object
  const audits = (response as any)?.data || []
  const currentPage = (response as any)?.current_page || 1
  const lastPage = (response as any)?.last_page || 1

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title="Pet Audit Trail"
      size="lg"
    >
      <div className="p-1">
        <div className="flex items-center gap-3 mb-6 p-4 bg-amber-50 border border-amber-100 rounded-lg">
          <div className="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 font-bold text-lg">
            <History className="w-6 h-6" />
          </div>
          <div>
            <h3 className="text-lg font-bold text-gray-800">{pet?.name} History</h3>
            <p className="text-xs text-gray-500 uppercase font-bold tracking-wider">
              Viewing recorded changes (Page {currentPage} of {lastPage})
            </p>
          </div>
        </div>

        {isLoading ? (
          <div className="flex justify-center p-12">
            <Loader2 className="w-8 h-8 animate-spin text-amber-600" />
          </div>
        ) : !audits || audits.length === 0 ? (
          <div className="text-center py-12 bg-gray-50 rounded-lg border border-dashed border-gray-200">
            <Info className="w-8 h-8 text-gray-300 mx-auto mb-2" />
            <p className="text-gray-400 italic">No audit history found for this pet.</p>
          </div>
        ) : (
          <>
            <div className="relative border-l-2 border-gray-100 ml-4 pl-8 space-y-8 py-4 min-h-[300px]">
              {audits.map((audit: any) => (
                <div key={audit.id} className="relative">
                  {/* Timeline dot */}
                  <div className={`absolute -left-[41px] top-0 w-6 h-6 rounded-full border-2 border-white shadow-sm flex items-center justify-center ${
                    audit.action_type === 'created' ? 'bg-green-500' : 'bg-blue-500'
                  }`}>
                    <Clock className="w-3 h-3 text-white" />
                  </div>

                  <div className="bg-white border rounded-lg shadow-sm overflow-hidden">
                    <div className="px-4 py-2 bg-gray-50 border-b flex justify-between items-center">
                      <span className={`text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded ${
                        audit.action_type === 'created' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'
                      }`}>
                        {audit.action_type}
                      </span>
                      <span className="text-xs text-gray-400 font-medium">
                        {formatDisplayDateTime(audit.created_at)}
                      </span>
                    </div>
                    
                    <div className="p-4 grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                      <div>
                        <label className="text-[10px] font-bold text-gray-400 uppercase">Pet Name</label>
                        <p className="font-bold text-gray-700">{audit.name}</p>
                      </div>
                      <div>
                        <label className="text-[10px] font-bold text-gray-400 uppercase">Gender</label>
                        <p className="text-gray-600 capitalize">{audit.gender || '-'}</p>
                      </div>
                      <div>
                        <label className="text-[10px] font-bold text-gray-400 uppercase">Breed</label>
                        <p className="text-gray-600">{audit.breed || '-'}</p>
                      </div>
                      <div>
                        <label className="text-[10px] font-bold text-gray-400 uppercase">Size</label>
                        <p className="text-gray-600 capitalize">{audit.size}</p>
                      </div>
                      <div>
                        <label className="text-[10px] font-bold text-gray-400 uppercase">Status</label>
                        <div className="flex items-center gap-1">
                          {audit.isActive ? (
                            <><Check className="w-3 h-3 text-green-500" /> <span className="text-green-600 text-xs font-bold">Active</span></>
                          ) : (
                            <><X className="w-3 h-3 text-red-500" /> <span className="text-red-600 text-xs font-bold">Inactive</span></>
                          )}
                        </div>
                      </div>
                      <div>
                        <label className="text-[10px] font-bold text-gray-400 uppercase">Birthday</label>
                        <p className="text-gray-600">{audit.birth_date ? formatDisplayDate(audit.birth_date) : '-'}</p>
                      </div>
                    </div>

                    {audit.notes && (
                      <div className="px-4 pb-4">
                        <label className="text-[10px] font-bold text-gray-400 uppercase">Internal Notes</label>
                        <p className="text-xs text-gray-500 mt-1 italic p-2 bg-gray-50 rounded border border-gray-100 leading-relaxed">
                          {audit.notes}
                        </p>
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
                
                <div className="text-xs font-bold text-gray-400 uppercase tracking-widest">
                  Page <span className="text-amber-600">{currentPage}</span> of <span className="text-gray-600">{lastPage}</span>
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
            className="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded font-bold text-sm transition-colors"
          >
            Close History
          </button>
        </div>
      </div>
    </Modal>
  )
}
