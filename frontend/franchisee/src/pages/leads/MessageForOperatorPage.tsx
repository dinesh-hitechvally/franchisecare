import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Phone, ChevronLeft, ChevronRight, MoreVertical } from 'lucide-react'
import { LeadDetailModal } from './LeadDetailModal'
import { useToastStore } from '../../store/toastStore'
import { leadsApi } from '../../api/services'
import type { Lead } from '../../types'
import { PortalMenu } from '../../components/ui/PortalMenu'

type MessageRow = {
  id: string
  customer: string
  date: string
  ref: string
  phone: string
  address: string
  message: string
  links: string[]
}

export function MessageForOperatorPage() {
  const queryClient = useQueryClient()
  const { addToast } = useToastStore()
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)
  const [selectedLead, setSelectedLead] = useState<Lead | null>(null)
  const [isViewModalOpen, setIsViewModalOpen] = useState(false)
  const [rows, setRows] = useState<MessageRow[]>([])

  const { data: fetchedLeads = [] } = useQuery({
    queryKey: ['leads', 'message_for_operator'],
    queryFn: () => leadsApi.getAll({ status: 'message_for_operator' }),
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
    setRows(
      fetchedLeads.map((lead) => ({
        id: lead.id,
        customer: lead.customerName,
        date: new Date(lead.createdAt).toLocaleString(),
        ref: lead.id,
        phone: [lead.phone, lead.alternatePhone].filter(Boolean).join(' / '),
        address: lead.address || '-',
        message: lead.notes || lead.additionalNote || '-',
        links: ['View'],
      }))
    )
  }, [fetchedLeads])

  const mapRowToLead = (row: MessageRow): Lead => ({
    id: row.id,
    firstName: row.customer.split(' ')[0] || 'Unknown',
    lastName: row.customer.split(' ').slice(1).join(' ') || '-',
    customerName: row.customer,
    email: '',
    phone: row.phone.split(' / ')[0] || '',
    alternatePhone: row.phone.split(' / ')[1] || '',
    interestedServices: 'Operator Message',
    address: row.address,
    suburb: '',
    petBreed: '',
    referredBy: 'Phone',
    additionalNote: row.message,
    notes: row.message,
    source: 'phone',
    leadsFrom: 'phone',
    status: 'message_for_operator',
    comments: [],
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  })

  const handleView = (row: MessageRow) => {
    setSelectedLead(mapRowToLead(row))
    setIsViewModalOpen(true)
    setOpenMenuId(null)
  }

  const handleCloseModal = () => {
    setIsViewModalOpen(false)
    setSelectedLead(null)
  }

  const handleComment = () => {
    if (!selectedLead) return
    updateLeadMutation.mutate(
      { id: selectedLead.id, data: { notes: selectedLead.notes || '' } },
      {
        onSuccess: () => addToast('Comment saved', 'success'),
        onError: () => addToast('Failed to save comment', 'error'),
      }
    )
  }

  const handleConvert = (leadId: string) => {
    convertLeadMutation.mutate(leadId, {
      onSuccess: () => addToast(`Lead ${leadId} converted`, 'success'),
      onError: () => addToast('Failed to convert lead', 'error'),
    })
  }

  const handleSnooze = (leadId: string, snoozeUntil: string) => {
    updateLeadMutation.mutate(
      { id: leadId, data: { status: 'snoozed', snoozedUntil } },
      {
        onSuccess: () => {
          addToast('Lead snoozed', 'success')
          handleCloseModal()
        },
        onError: () => addToast('Failed to snooze lead', 'error'),
      }
    )
  }

  return (
    <div className="space-y-5 px-1 py-1 w-full">
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Message For Operator</h1>
      </div>

      {/* Data Table Card */}
      <Card className="shadow-sm border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse border-b border-gray-200">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="px-3 py-4 w-12 text-center text-sm font-semibold text-gray-800"></th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-36">Name</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-44">Requested At</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-36">Reference</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-48">Phone</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-32">Address</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 max-w-[200px]">Message</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-28 text-center">From</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-20 text-right">Mgmt</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {rows.length === 0 ? (
                <tr>
                  <td colSpan={9} className="px-3 py-10 text-center text-sm text-gray-500">
                    No leads found
                  </td>
                </tr>
              ) : (
                rows.map((row) => (
                <tr key={row.id} className="relative group hover:-translate-y-[1px] transition-transform hover:bg-gray-50">
                  <td className="px-3 py-4 align-top text-center">
                    <div className="w-2.5 h-2.5 rounded-full bg-green-500 mx-auto mt-2"></div>
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top pr-4">
                    {row.customer}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug">
                    {row.date.split(' ').map((part, index) => {
                      if (index === 2) return <span key={index}><br/>{part} </span>
                      if (index === 3) return <span key={index}>{part} </span>
                      return <span key={index}>{part} </span>
                    })}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug">
                    {row.ref}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug">
                    {row.phone.split(' / ').map((part, index) => (
                       <span key={index}>{part}{index === 0 && ' /'}<br/></span>
                    ))}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug">
                    {row.address}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug pr-8 whitespace-pre-line">
                    {row.message}
                  </td>
                  <td className="px-3 py-4 align-top text-center text-blue-700">
                    <Phone className="w-4 h-4 mx-auto rotate-90" />
                  </td>
                  <td className="px-3 py-4 text-sm align-top leading-relaxed relative">
                    <div className="flex justify-end">
                      <button
                        type="button"
                        onClick={(e) => {
                          if (openMenuId === row.id) {
                            setOpenMenuId(null)
                            setMenuPos(null)
                          } else {
                            const rect = (e.currentTarget as HTMLButtonElement).getBoundingClientRect()
                            setMenuPos({ top: rect.bottom + 4, left: rect.right - 160 })
                            setOpenMenuId(row.id)
                          }
                        }}
                        className="p-1 rounded hover:bg-gray-100"
                      >
                        <MoreVertical className="w-5 h-5 text-gray-600" />
                      </button>
                    </div>
                    <PortalMenu
                      isOpen={openMenuId === row.id}
                      onClose={() => { setOpenMenuId(null); setMenuPos(null) }}
                      position={menuPos}
                      width={160}
                    >
                        <button
                          type="button"
                          onClick={() => handleView(row)}
                          className="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        >
                          {row.links[0]}
                        </button>
                    </PortalMenu>
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
            <span>{rows.length === 0 ? '0-0' : `1-${rows.length}`} of {rows.length}</span>
            <div className="flex items-center gap-4 text-gray-400">
              <ChevronLeft className="w-5 h-5 cursor-not-allowed" />
              <ChevronRight className="w-5 h-5 cursor-not-allowed" />
            </div>
          </div>
        </div>
      </Card>

      {selectedLead && (
        <LeadDetailModal
          lead={selectedLead}
          isOpen={isViewModalOpen}
          onClose={handleCloseModal}
          onComment={handleComment}
          onConvert={handleConvert}
          onSnooze={handleSnooze}
        />
      )}
    </div>
  )
}
