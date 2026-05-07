import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Phone, Globe, ChevronLeft, ChevronRight } from 'lucide-react'
import { LeadDetailModal } from './LeadDetailModal'
import { useToastStore } from '../../store/toastStore'
import type { Lead } from '../../types'

// Dummy data matching the screenshot - converted to proper Lead objects
const newLeads: Lead[] = [
  {
    id: '19579',
    firstName: 'Mate',
    lastName: 'dognew',
    customerName: 'Mate dognew',
    phone: '4563786376',
    alternatePhone: '7453783783',
    interestedServices: 'Wash only',
    email: '',
    address: 'afg, DARCH, DARCH',
    suburb: 'DARCH',
    petBreed: '- (qwerty)',
    referredBy: 'Internet',
    additionalNote: '',
    notes: 'Created from dev team, please ignore it if you received it.',
    source: 'internet',
    leadsFrom: 'internet',
    status: 'new',
    comments: [],
    createdAt: '2026-03-25T19:08:00Z',
    updatedAt: '2026-03-25T19:09:00Z',
  },
  {
    id: '2',
    firstName: 'Latham',
    lastName: 'Peterson',
    customerName: 'Latham Peterson',
    phone: '9876543210',
    alternatePhone: '',
    interestedServices: 'Wash and Coat Clipped',
    email: 'latham@example.com',
    address: '123 Main St, BRISBANE CITY',
    suburb: 'BRISBANE CITY',
    petBreed: 'Golden Retriever',
    referredBy: 'Website',
    additionalNote: 'Prefers morning appointments',
    notes: 'Lead created',
    source: 'internet',
    leadsFrom: 'phone',
    status: 'new',
    comments: [],
    createdAt: '2026-03-19T15:58:00Z',
    updatedAt: '2026-03-19T15:58:00Z',
  },
  {
    id: '3',
    firstName: 'Rabee',
    lastName: 'Peter',
    customerName: 'Rabee Peter',
    phone: '9842354545',
    alternatePhone: '',
    interestedServices: 'Wash only',
    email: '',
    address: 'Testing dev team, DARCH',
    suburb: 'DARCH',
    petBreed: '',
    referredBy: 'Internet',
    additionalNote: '',
    notes: 'Test message',
    source: 'internet',
    leadsFrom: 'internet',
    status: 'new',
    comments: [
      {
        id: 'c1',
        leadId: '3',
        comment: 'Called and left voicemail',
        createdBy: 'Mate Support',
        createdAt: '2026-03-18T14:00:00Z',
      },
    ],
    createdAt: '2026-03-18T13:06:00Z',
    updatedAt: '2026-03-18T14:00:00Z',
  },
]

export function NewLeadsPage() {
  const { addToast } = useToastStore()
  const [selectedLead, setSelectedLead] = useState<Lead | null>(null)
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [leads, setLeads] = useState<Lead[]>(newLeads)

  const handleViewLead = (lead: Lead) => {
    setSelectedLead(lead)
    setIsModalOpen(true)
  }

  const handleCloseModal = () => {
    setIsModalOpen(false)
    setSelectedLead(null)
  }

  const handleComment = (leadId: string, comment: string) => {
    setLeads((prevLeads) =>
      prevLeads.map((lead) =>
        lead.id === leadId
          ? {
              ...lead,
              comments: [
                ...(lead.comments || []),
                {
                  id: `c${Date.now()}`,
                  leadId,
                  comment,
                  createdBy: 'Current User',
                  createdAt: new Date().toISOString(),
                },
              ],
              updatedAt: new Date().toISOString(),
            }
          : lead
      )
    )
    // Update selected lead to reflect changes
    if (selectedLead?.id === leadId) {
      setSelectedLead((prev) =>
        prev
          ? {
              ...prev,
              comments: [
                ...(prev.comments || []),
                {
                  id: `c${Date.now()}`,
                  leadId,
                  comment,
                  createdBy: 'Current User',
                  createdAt: new Date().toISOString(),
                },
              ],
              updatedAt: new Date().toISOString(),
            }
          : null
      )
    }
    addToast('Comment added successfully', 'success')
  }

  const handleConvertToCustomer = (leadId: string) => {
    // In real implementation, this would call an API to convert the lead
    const lead = leads.find((l) => l.id === leadId)
    if (lead) {
      addToast(`Converting "${lead.customerName}" to customer...`, 'success')
      // Update lead status to converted instead of removing
      setLeads((prevLeads) =>
        prevLeads.map((l) =>
          l.id === leadId ? { ...l, status: 'converted' as const, updatedAt: new Date().toISOString() } : l
        )
      )
      // Update selected lead if it's the one being converted
      if (selectedLead?.id === leadId) {
        setSelectedLead((prev) => (prev ? { ...prev, status: 'converted' as const } : null))
      }
    }
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
    setLeads((prevLeads) =>
      prevLeads.map((lead) =>
        lead.id === leadId
          ? {
              ...lead,
              status: 'snoozed',
              snoozedUntil: snoozeUntil,
              updatedAt: new Date().toISOString(),
            }
          : lead
      )
    )
    addToast('Lead snoozed successfully', 'success')
    handleCloseModal()
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
      {/* Top Header Card */}
      <Card className="px-6 py-4 shadow-sm border-gray-200">
        <h1 className="text-xl font-bold text-gray-800">List Of New Leads</h1>
      </Card>

      {/* Data Table Card */}
      <Card className="shadow-sm border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse border-b border-gray-200">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="px-3 py-4 w-12 text-center text-sm font-semibold text-gray-800"></th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-32">Customer Name</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-32">Added Date</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-28">Interested<br/>Services</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-36">Phone</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 max-w-xs">Address</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 max-w-[200px]">Notes</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-20 text-center">Leads<br/>From</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-24">Mgmt</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {leads.map((lead) => (
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
                  <td className="px-3 py-4 text-sm align-top leading-relaxed">
                    <div className="flex flex-col gap-0.5">
                      <button
                        onClick={() => handleViewLead(lead)}
                        className="text-blue-600 hover:underline text-left"
                      >
                        View |
                      </button>
                      <button
                        onClick={() => {
                          handleSnooze(lead.id, new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString())
                        }}
                        className="text-blue-600 hover:underline text-left"
                      >
                        Snooze
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
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

