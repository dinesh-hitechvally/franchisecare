import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Phone, Globe, ChevronLeft, ChevronRight, MoreVertical } from 'lucide-react'
import { LeadDetailModal } from './LeadDetailModal'
import { useToastStore } from '../../store/toastStore'
import { leadsApi } from '../../api/services'
import type { Lead } from '../../types'

export function NewLeadsPage() {
  const queryClient = useQueryClient()
  const { addToast } = useToastStore()
  const [selectedLead, setSelectedLead] = useState<Lead | null>(null)
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [leads, setLeads] = useState<Lead[]>([])
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)

  const { data: fetchedLeads = [] } = useQuery({
    queryKey: ['leads', 'new'],
    queryFn: () => leadsApi.getAll({ status: 'new' }),
  })

  const updateLeadMutation = useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<Lead> }) => leadsApi.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['leads'] })
    },
  })

  const convertLeadMutation = useMutation({
    mutationFn: (id: string) => leadsApi.convert(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['leads'] })
    },
  })

  useEffect(() => {
    setLeads(fetchedLeads)
  }, [fetchedLeads])

  const handleViewLead = (lead: Lead) => {
    setSelectedLead(lead)
    setIsModalOpen(true)
  }

  const handleCloseModal = () => {
    setIsModalOpen(false)
    setSelectedLead(null)
  }

  const handleComment = (leadId: string, comment: string) => {
    updateLeadMutation.mutate(
      { id: leadId, data: { notes: comment } },
      {
        onSuccess: () => addToast('Comment added successfully', 'success'),
        onError: () => addToast('Failed to save comment', 'error'),
      }
    )
  }

  const handleConvertToCustomer = (leadId: string) => {
    convertLeadMutation.mutate(leadId, {
      onSuccess: () => addToast('Lead converted to customer', 'success'),
      onError: () => addToast('Failed to convert lead', 'error'),
    })
  }

  const handleAddMorePets = (leadId: string) => {
    const lead = leads.find((l) => l.id === leadId)
    if (lead) {
      addToast(`Redirecting to add more pets for "${lead.customerName}"...`, 'success')
      // In real implementation, navigate to add pets page
      handleCloseModal()
    }
  }

  const handleSnooze = (leadId: string, snoozeUntil: string) => {
    updateLeadMutation.mutate(
      {
        id: leadId,
        data: {
          status: 'snoozed',
          snoozedUntil: snoozeUntil,
        },
      },
      {
        onSuccess: () => {
          addToast('Lead snoozed successfully', 'success')
          handleCloseModal()
        },
        onError: () => addToast('Failed to snooze lead', 'error'),
      }
    )
  }

  const formatDate = (dateString: string) => {
    const date = new Date(dateString)
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    const dayName = days[date.getDay()]
    const day = date.getDate()
    const suffix = day === 1 || day === 21 || day === 31 ? 'st' : day === 2 || day === 22 ? 'nd' : day === 3 || day === 23 ? 'rd' : 'th'
    const month = months[date.getMonth()]
    const year = date.getFullYear()
    const hours = date.getHours().toString().padStart(2, '0')
    const minutes = date.getMinutes().toString().padStart(2, '0')
    return `${dayName}, ${day}${suffix} ${month} ${year} ${hours}:${minutes}`
  }

  return (
    <div className="space-y-5 px-1 py-1 w-full">
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">List Of New Leads</h1>
      </div>

      {/* Data Table Card */}
      <Card className="shadow-sm border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse border-b border-gray-200">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="px-3 py-4 w-12 text-center text-sm font-semibold text-gray-800"></th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-32">Name</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-32">Date</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-28">Services</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-36">Phone</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 max-w-xs">Address</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 max-w-[200px]">Notes</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-20 text-center">From</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-24 text-right">Mgmt</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {leads.length === 0 ? (
                <tr>
                  <td colSpan={9} className="px-3 py-10 text-center text-sm text-gray-500">
                    No leads found
                  </td>
                </tr>
              ) : (
                leads.map((lead) => (
                <tr key={lead.id} className="relative group hover:-translate-y-[1px] transition-transform hover:bg-gray-50">
                  <td className="px-3 py-4 align-top text-center">
                    <div className="w-2 h-2 rounded-full bg-red-600 mx-auto mt-2"></div>
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top pr-4">
                    {lead.customerName}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug">
                    {formatDate(lead.createdAt).split(' ').map((part, index) => {
                      if (index === 2) return <span key={index}><br/>{part} </span>
                      if (index === 3) return <span key={index}>{part} </span>
                      return <span key={index}>{part} </span>
                    })}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug">
                    {lead.interestedServices}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug">
                    {lead.phone}
                    {lead.alternatePhone && (
                      <>
                        {' / '}
                        <br />
                        {lead.alternatePhone}
                      </>
                    )}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug pr-8">
                    {lead.address}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug pr-8">
                    {lead.notes}
                  </td>
                  <td className="px-3 py-4 align-top text-center text-blue-700">
                    {lead.leadsFrom === 'phone' ? (
                       <Phone className="w-4 h-4 mx-auto rotate-90" />
                    ) : (
                       <Globe className="w-4 h-4 mx-auto" />
                    )}
                  </td>
                  <td className="px-3 py-4 text-sm align-top leading-relaxed relative">
                    <div className="flex justify-end">
                      <button
                        type="button"
                        onClick={() => setOpenMenuId(openMenuId === lead.id ? null : lead.id)}
                        className="p-1 rounded hover:bg-gray-100"
                      >
                        <MoreVertical className="w-5 h-5 text-gray-600" />
                      </button>
                    </div>
                    {openMenuId === lead.id && (
                      <div className="absolute right-3 mt-1 w-40 bg-white border border-gray-200 rounded-md shadow-lg z-20">
                        <button
                          type="button"
                          onClick={() => {
                            handleViewLead(lead)
                            setOpenMenuId(null)
                          }}
                          className="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        >
                          View
                        </button>
                        <button
                          type="button"
                          onClick={() => {
                            handleSnooze(lead.id, new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString())
                            setOpenMenuId(null)
                          }}
                          className="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        >
                          Snooze
                        </button>
                      </div>
                    )}
                  </td>
                </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination Container */}
        <div className="flex flex-col">
          <div className="px-5 py-3 flex items-center justify-end gap-6 text-sm text-gray-600">
            <div className="flex items-center gap-2">
              <span>Rows per page:</span>
              <select className="border border-gray-200 rounded p-1 outline-none text-gray-700">
                <option>25</option>
                <option>50</option>
                <option>100</option>
              </select>
            </div>
            <span>1-{leads.length} of {leads.length}</span>
            <div className="flex items-center gap-4 text-gray-400">
              <ChevronLeft className="w-5 h-5 cursor-not-allowed" />
              <ChevronRight className="w-5 h-5 cursor-not-allowed" />
            </div>
          </div>
        </div>
      </Card>

      {/* Lead Detail Modal */}
      {selectedLead && (
        <LeadDetailModal
          lead={selectedLead}
          isOpen={isModalOpen}
          onClose={handleCloseModal}
          onComment={handleComment}
          onConvert={handleConvertToCustomer}
          onSnooze={handleSnooze}
          onAddMorePets={handleAddMorePets}
        />
      )}
    </div>
  )
}

