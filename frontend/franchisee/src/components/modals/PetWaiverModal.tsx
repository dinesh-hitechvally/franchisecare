import React, { useState } from 'react'
import { Modal } from '../ui/Modal'
import { Button } from '../ui/Button'
import { CustomerIntakeForm } from './CustomerIntakeForm'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { petsApi } from '../../api/services'
import { format } from 'date-fns'
import { MoreVertical, PenLine, Plus, History, Loader2, FileText } from 'lucide-react'
import { useToastStore } from '../../store/toastStore'
import type { Pet, Customer } from '../../types'

interface PetWaiverModalProps {
  isOpen: boolean
  onClose: () => void
  pet: Pet | null
  customer: Customer | null
}

export const PetWaiverModal: React.FC<PetWaiverModalProps> = ({ isOpen, onClose, pet, customer }) => {
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)
  
  const [activeWaiverMenuId, setActiveWaiverMenuId] = useState<string | null>(null)
  const [isIntakeModalOpen, setIsIntakeModalOpen] = useState(false)
  const [selectedWaiverData, setSelectedWaiverData] = useState<any>(null)
  const [selectedWaiverType, setSelectedWaiverType] = useState<string>('intake')
  const [isHistoryModalOpen, setIsHistoryModalOpen] = useState(false)
  const [historyWaiverType, setHistoryWaiverType] = useState<string>('')
  const [historyData, setHistoryData] = useState<any[]>([])
  const [isLoadingHistory, setIsLoadingHistory] = useState(false)

  const { data: petWaivers } = useQuery({
    queryKey: ['pet-waivers', pet?.id],
    queryFn: () => petsApi.getWaivers(pet!.id.toString()) as Promise<Record<string, any>>,
    enabled: !!pet && isOpen,
  })

  if (!pet || !customer) return null

  return (
    <>
      <Modal
        isOpen={isOpen}
        onClose={onClose}
        title="Waivers"
        size="lg"
      >
        <div className="p-1 min-h-[400px]">
          <div className="flex items-center gap-3 mb-6 p-4 bg-indigo-50 border border-indigo-100 rounded-lg">
            <div className="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-lg">
              {pet.name.charAt(0)}
            </div>
            <div>
              <h3 className="text-lg font-bold text-gray-800">{pet.name}</h3>
              <p className="text-xs text-gray-500 uppercase font-bold tracking-wider">{pet.breed || 'Unknown Breed'} • {pet.size}</p>
            </div>
          </div>

          <div className="overflow-visible">
            <table className="w-full">
              <thead>
                <tr className="text-left text-xs font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                  <th className="pb-3 px-1">Document Type</th>
                  <th className="pb-3 px-1 text-center font-bold">Last Submitted</th>
                  <th className="pb-3 px-1 text-right font-bold">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100/50">
                {[
                  { label: 'Customer Intake form', type: 'intake' },
                  { label: 'Cologne waiver', type: 'cologne' },
                  { label: 'Shampoo waiver', type: 'shampoo' },
                  { label: 'Matted dog waiver', type: 'matted' },
                  { label: 'Clipping double coated dog waiver', type: 'clipping' },
                  { label: 'Pregnant dog waiver', type: 'pregnant' },
                  { label: 'Senior dog waiver', type: 'senior' }
                ].map((waiver) => {
                  const savedWaiver = (petWaivers || {})[waiver.type]
                  let lastSubmitted = '-'

                  try {
                    if (savedWaiver && savedWaiver.created_at) {
                      lastSubmitted = format(new Date(savedWaiver.created_at), 'dd/MM/yyyy')
                    }
                  } catch (e) {
                    console.error('Error formatting date:', e)
                  }

                  return (
                    <tr key={waiver.type} className="hover:bg-gray-50/50 transition-colors group">
                      <td className="py-4 px-1 text-gray-600 font-medium">{waiver.label}</td>
                      <td className="py-4 px-1 text-center text-gray-400 font-medium">{lastSubmitted}</td>
                      <td className="py-4 px-1 text-right relative">
                        <button
                          onClick={(e) => {
                            e.stopPropagation()
                            setActiveWaiverMenuId(activeWaiverMenuId === waiver.type ? null : waiver.type)
                          }}
                          className={`p-1 rounded-full transition-colors ${activeWaiverMenuId === waiver.type ? 'bg-indigo-100 text-indigo-600' : 'text-gray-400 hover:bg-gray-100 hover:text-indigo-600'}`}
                        >
                          <MoreVertical className="w-5 h-5" />
                        </button>

                        {activeWaiverMenuId === waiver.type && (
                          <>
                            <div className="fixed inset-0 z-[60]" onClick={() => setActiveWaiverMenuId(null)}></div>
                            <div className="absolute right-0 mt-1 w-56 bg-white rounded-lg shadow-2xl border border-gray-100 py-1 z-[70] overflow-hidden animate-in fade-in slide-in-from-top-2">
                              {savedWaiver ? (
                                <button
                                  onClick={() => {
                                    setSelectedWaiverData(savedWaiver)
                                    setSelectedWaiverType(waiver.type)
                                    setIsIntakeModalOpen(true)
                                    setActiveWaiverMenuId(null)
                                  }}
                                  className="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors"
                                >
                                  <PenLine className="w-4 h-4 mr-3 text-indigo-500" />
                                  Edit Waiver
                                </button>
                              ) : (
                                <button
                                  onClick={() => {
                                    setSelectedWaiverData(null)
                                    setSelectedWaiverType(waiver.type)
                                    setIsIntakeModalOpen(true)
                                    setActiveWaiverMenuId(null)
                                  }}
                                  className="flex items-center w-full px-4 py-3 text-sm text-indigo-600 hover:bg-indigo-50 font-bold transition-colors"
                                >
                                  <Plus className="w-4 h-4 mr-3" />
                                  Sign Waiver
                                </button>
                              )}

                              <button
                                onClick={async () => {
                                  setActiveWaiverMenuId(null)
                                  setIsLoadingHistory(true)
                                  setHistoryWaiverType(waiver.label)
                                  setIsHistoryModalOpen(true)
                                  try {
                                    const res = await petsApi.getHistory(pet.id.toString(), waiver.type)
                                    setHistoryData(Array.isArray(res) ? res : [])
                                  } catch (err) {
                                    addToast('Failed to fetch history', 'error')
                                  } finally {
                                    setIsLoadingHistory(false)
                                  }
                                }}
                                className="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors border-t border-gray-100"
                              >
                                <History className="w-4 h-4 mr-3 text-gray-400" />
                                Versions
                              </button>

                              {savedWaiver && (
                                <button
                                  onClick={() => {
                                    setActiveWaiverMenuId(null)
                                    const baseUrl = ((import.meta as any).env.VITE_API_URL || 'http://localhost:8000/api').replace('/api', '')
                                    const path = savedWaiver.pdf_path.startsWith('http') ? savedWaiver.pdf_path : `${baseUrl}/${savedWaiver.pdf_path}`
                                    window.open(path, '_blank')
                                  }}
                                  className="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors border-t border-gray-100"
                                >
                                  <FileText className="w-4 h-4 mr-3 text-gray-400" />
                                  View PDF
                                </button>
                              )}
                            </div>
                          </>
                        )}
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>

          <div className="mt-8 pt-4 border-t border-gray-100 flex justify-end">
            <Button
              onClick={onClose}
              variant="secondary"
              className="font-bold uppercase text-xs tracking-widest text-red-500 border-red-100 hover:bg-red-50"
            >
              Close
            </Button>
          </div>
        </div>
      </Modal>

      {/* Versions Modal */}
      <Modal
        isOpen={isHistoryModalOpen}
        onClose={() => setIsHistoryModalOpen(false)}
        title={`Signed Versions - ${historyWaiverType}`}
        size="lg"
      >
        <div className="p-0">
          {isLoadingHistory ? (
            <div className="flex flex-col items-center justify-center py-20">
              <Loader2 className="w-10 h-10 animate-spin text-indigo-600 mb-4" />
              <p className="text-gray-400 font-medium tracking-wide">Loading Versions...</p>
            </div>
          ) : historyData.length > 0 ? (
            <div className="overflow-hidden">
              <table className="w-full border-collapse">
                <thead>
                  <tr className="text-left text-xs font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 bg-white">
                    <th className="py-3 px-4 font-bold whitespace-nowrap">VERSION</th>
                    <th className="py-3 px-4 font-bold whitespace-nowrap">SIGNED DATE</th>
                    <th className="py-3 px-4 font-bold whitespace-nowrap">CUSTOMER NAME</th>
                    <th className="py-3 px-4 text-right font-bold whitespace-nowrap">VIEW</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {historyData.map((item, index) => (
                    <tr key={item.id} className="hover:bg-gray-50 even:bg-gray-50/50 transition-colors">
                      <td className="py-3 px-4 whitespace-nowrap">
                        <span className="font-bold text-gray-800">
                          Version {historyData.length - index}
                          {index === 0 && (
                            <span className="ml-2 text-gray-400 font-medium">(Current)</span>
                          )}
                        </span>
                      </td>
                      <td className="py-3 px-4 text-gray-600 font-medium whitespace-nowrap">
                        {format(new Date(item.created_at), 'dd/MM/yyyy HH:mm')}
                      </td>
                      <td className="py-3 px-4 text-gray-600 font-medium capitalize whitespace-nowrap">
                        {customer ? `${customer.first_name} ${customer.last_name}` : 'System Record'}
                      </td>
                      <td className="py-3 px-4 text-right whitespace-nowrap">
                        <button
                          onClick={() => {
                            const baseUrl = ((import.meta as any).env.VITE_API_URL || 'http://localhost:8000/api').replace('/api', '')
                            const path = item.pdf_path.startsWith('http') ? item.pdf_path : `${baseUrl}/${item.pdf_path}`
                            window.open(path, '_blank')
                          }}
                          className="px-4 py-2 border border-indigo-200 text-indigo-600 rounded-md text-xs font-bold uppercase tracking-wider hover:bg-indigo-50 transition-colors"
                        >
                          VIEW PDF
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="text-center py-20">
              <History className="w-12 h-12 text-gray-200 mx-auto mb-4" />
              <p className="text-gray-400 font-medium">No versions found for this waiver.</p>
            </div>
          )}

          <div className="mt-8 pt-4 border-t border-gray-100 flex justify-end">
            <button
              onClick={() => setIsHistoryModalOpen(false)}
              className="text-[#FF4081] hover:text-[#E91E63] font-bold uppercase text-sm tracking-widest px-4 py-2 transition-colors"
            >
              CLOSE
            </button>
          </div>
        </div>
      </Modal>

      <CustomerIntakeForm
        isOpen={isIntakeModalOpen}
        onClose={() => {
          setIsIntakeModalOpen(false)
          setSelectedWaiverData(null)
          queryClient.invalidateQueries({ queryKey: ['pet-waivers', pet.id] })
        }}
        customerId={customer.id || ''}
        petId={pet.id || ''}
        petName={pet.name || ''}
        ownerName={`${customer.first_name || ''} ${customer.last_name || ''}`}
        phone={customer.phone || ''}
        email={customer.email || ''}
        breed={pet.breed || ''}
        waiverType={selectedWaiverType}
        customerAddress={[
          customer.street_address,
          customer.suburb,
          customer.state,
          'Australia',
          customer.postcode
        ].filter(Boolean).join(', ')}
        franchiseeName="Mate Support"
        initialData={selectedWaiverData}
      />
    </>
  )
}
