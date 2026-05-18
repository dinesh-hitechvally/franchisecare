import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { PageHeader } from '../../components/layout/PageHeader'
import { Phone, Globe, ChevronLeft, ChevronRight, MoreVertical, ListChecks } from 'lucide-react'
import { useToastStore } from '../../store/toastStore'
import { leadsApi } from '../../api/services'
import { LeadDetailModal } from './LeadDetailModal'
import type { Lead } from '../../types'
import { PortalMenu } from '../../components/ui/PortalMenu'
import { formatDisplayDateTime } from '../../lib/timeFormatUtils'

type CompletedRow = {
  id: string
  customer: string
  date: string
  service: string
  phone: string
  address: string
  notes: string
  from: string
  links: string[]
}

export function CompletedLeadsPage() {
  const queryClient = useQueryClient()
  const { addToast } = useToastStore()
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)
  const [rows, setRows] = useState<CompletedRow[]>([])
  const [selectedLead, setSelectedLead] = useState<Lead | null>(null)
  const [isModalOpen, setIsModalOpen] = useState(false)

  const { data: fetchedLeads = [] } = useQuery({
    queryKey: ['leads', 'completed'],
    queryFn: () => leadsApi.getAll({ status: 'completed' }),
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
        date: formatDisplayDateTime(lead.createdAt),
        service: lead.interestedServices || '-',
        phone: [lead.phone, lead.alternatePhone].filter(Boolean).join(' / '),
        address: lead.address || '-',
        notes: lead.notes || '-',
        from: lead.leadsFrom,
        links: ['View', 'Snooze'],
      }))
    )
  }, [fetchedLeads])

  const mapRowToLead = (row: CompletedRow): Lead => ({
    id: row.id,
    firstName: row.customer.split(' ')[0] || 'Unknown',
    lastName: row.customer.split(' ').slice(1).join(' ') || '-',
    customerName: row.customer,
    email: '',
    phone: row.phone.split(' / ')[0] || '',
    alternatePhone: row.phone.split(' / ')[1] || '',
    interestedServices: row.service,
    address: row.address,
    suburb: '',
    petBreed: '',
    referredBy: row.from === 'phone' ? 'Phone' : 'Internet',
    additionalNote: row.notes,
    notes: row.notes,
    source: row.from === 'phone' ? 'phone' : 'internet',
    leadsFrom: row.from === 'phone' ? 'phone' : 'internet',
    status: 'completed',
    comments: [],
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  })

  const handleView = (rowId: string) => {
    const row = rows.find((item) => item.id === rowId)
    if (!row) return
    setSelectedLead(mapRowToLead(row))
    setIsModalOpen(true)
    setOpenMenuId(null)
  }

  const handleCloseModal = () => {
    setIsModalOpen(false)
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

  const handleSnoozeFromModal = async (leadId: string) => {
    await handleSnooze(leadId)
    handleCloseModal()
  }

  const handleSnooze = async (rowId: string) => {
    const row = rows.find((item) => item.id === rowId)
    if (!row) return
    try {
      await leadsApi.update(rowId, { status: 'snoozed' })
      setRows((prev) => prev.filter((item) => item.id !== rowId))
      addToast(`Lead ${row.customer} moved to snooze`, 'success')
    } catch {
      setRows((prev) => prev.filter((item) => item.id !== rowId))
      addToast('Lead API unavailable, updated only on current screen', 'info')
    }
    setOpenMenuId(null)
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Completed Leads"
        icon={<ListChecks className="w-5 h-5" />}
      />

      {/* Data Table Card */}
      <Card className="shadow-sm border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse border-b border-gray-200">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 w-40">Name</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-32">Date</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-28">Services</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-40">Phone</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 max-w-sm">Address</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 max-w-[200px]">Notes</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-20 text-center">From</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-24 text-right">Mgmt</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {rows.length === 0 ? (
                <tr>
                  <td colSpan={8} className="px-3 py-10 text-center text-sm text-gray-500">
                    No leads found
                  </td>
                </tr>
              ) : (
                rows.map((row) => (
                <tr key={row.id} className="relative group hover:-translate-y-[1px] transition-transform hover:bg-gray-50">
                  <td className="px-5 py-4 text-sm text-gray-700 align-top pr-4">
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
                    {row.service}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug">
                    {row.phone.split(' / ').map((part, index) => (
                       <span key={index}>{part}{index === 0 && ' /'}<br/></span>
                    ))}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug pr-8">
                    {row.address}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug pr-8">
                    {row.notes}
                  </td>
                  <td className="px-3 py-4 align-top text-center text-blue-700">
                    {row.from === 'phone' ? (
                       <Phone className="w-4 h-4 mx-auto rotate-90" />
                    ) : (
                       <Globe className="w-4 h-4 mx-auto" />
                    )}
                  </td>
                  <td className="px-3 py-4 text-sm align-top leading-relaxed relative">
                    <div className="flex justify-end">
                      <button
                        type="button"
                        onClick={(e) => {
                          if (openMenuId === row.id) {
                            setOpenMenuId(null); setMenuPos(null)
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
                          onClick={() => handleView(row.id)}
                          className="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        >
                          {row.links[0]}
                        </button>
                        <button
                          type="button"
                          onClick={() => void handleSnooze(row.id)}
                          className="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        >
                          {row.links[1]}
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
              <ChevronRight className="w-5 h-5 cursor-pointer hover:text-gray-700" />
            </div>
          </div>
        </div>
      </Card>

      {selectedLead && (
        <LeadDetailModal
          lead={selectedLead}
          isOpen={isModalOpen}
          onClose={handleCloseModal}
          onComment={handleComment}
          onConvert={handleConvert}
          onSnooze={handleSnoozeFromModal}
        />
      )}
    </div>
  )
}
