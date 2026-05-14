import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Globe, ChevronLeft, ChevronRight, Search, MoreVertical } from 'lucide-react'
import { useToastStore } from '../../store/toastStore'
import { leadsApi } from '../../api/services'
import { LeadDetailModal } from './LeadDetailModal'
import type { Lead } from '../../types'
import { PortalMenu } from '../../components/ui/PortalMenu'

type SnoozeRow = {
  id: string
  customer: string
  date: string
  service: string
  phone: string
  address: string
  notes: string
  snoozeNotes: string
  from: string
  links: string[]
}

export function SnoozeLeadsPage() {
  const queryClient = useQueryClient()
  const { addToast } = useToastStore()
  const [searchTerm, setSearchTerm] = useState('')
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)
  const [rows, setRows] = useState<SnoozeRow[]>([])
  const [selectedLead, setSelectedLead] = useState<Lead | null>(null)
  const [isModalOpen, setIsModalOpen] = useState(false)

  const { data: fetchedLeads = [] } = useQuery({
    queryKey: ['leads', 'snoozed'],
    queryFn: () => leadsApi.getAll({ status: 'snoozed' }),
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
        service: lead.interestedServices || '-',
        phone: lead.phone,
        address: lead.address || '-',
        notes: lead.notes || '-',
        snoozeNotes: lead.additionalNote || '-',
        from: lead.leadsFrom,
        links: ['View', 'Re-activate'],
      }))
    )
  }, [fetchedLeads])

  const mapRowToLead = (row: SnoozeRow): Lead => ({
    id: row.id,
    firstName: row.customer.split(' ')[0] || 'Unknown',
    lastName: row.customer.split(' ').slice(1).join(' ') || '-',
    customerName: row.customer,
    email: '',
    phone: row.phone,
    interestedServices: row.service,
    address: row.address,
    suburb: '',
    petBreed: '',
    referredBy: 'Internet',
    additionalNote: row.snoozeNotes,
    notes: row.notes,
    source: 'internet',
    leadsFrom: 'internet',
    status: 'snoozed',
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

  const handleSnoozeFromModal = (leadId: string, snoozeUntil: string) => {
    updateLeadMutation.mutate(
      { id: leadId, data: { status: 'snoozed', snoozedUntil } },
      {
        onSuccess: () => addToast('Snooze time updated', 'success'),
        onError: () => addToast('Failed to update snooze time', 'error'),
      }
    )
  }

  const handleReactivate = async (rowId: string) => {
    const row = rows.find((item) => item.id === rowId)
    if (!row) return
    try {
      await leadsApi.update(rowId, { status: 'new' })
      setRows((prev) => prev.filter((item) => item.id !== rowId))
      addToast(`Lead ${row.customer} re-activated`, 'success')
    } catch {
      setRows((prev) => prev.filter((item) => item.id !== rowId))
      addToast('Lead API unavailable, updated only on current screen', 'info')
    }
    setOpenMenuId(null)
  }

  const filteredRows = rows.filter((row) => {
    const q = searchTerm.trim().toLowerCase()
    if (!q) return true
    return (
      row.customer.toLowerCase().includes(q) ||
      row.phone.toLowerCase().includes(q) ||
      row.address.toLowerCase().includes(q) ||
      row.notes.toLowerCase().includes(q)
    )
  })

  return (
    <div className="space-y-5 px-1 py-1 w-full">
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Snooze Leads</h1>
      </div>

      {/* Search Bar Card */}
      <Card className="px-6 py-8 shadow-sm border-gray-200">
        <div className="relative max-w-lg">
          <Search className="w-5 h-5 absolute left-0 top-1/2 transform -translate-y-1/2 text-gray-400" />
          <input
            type="text"
            placeholder="Search snoozed leads"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full pl-8 pr-4 py-2 border-b border-gray-200 focus:outline-none focus:border-blue-500 text-sm text-gray-700 bg-transparent"
          />
        </div>
      </Card>

      {/* Data Table Card */}
      <Card className="shadow-sm border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse border-b border-gray-200">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 w-36">Name</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-32">Date</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-28">Services</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-36">Phone</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-40">Address</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 max-w-[200px]">Notes</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-40">Snooze Notes</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-16 text-center">From</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-24 text-right">Mgmt</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {filteredRows.length === 0 ? (
                <tr>
                  <td colSpan={9} className="px-3 py-10 text-center text-sm text-gray-500">
                    No leads found
                  </td>
                </tr>
              ) : (
                filteredRows.map((row) => (
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
                    {row.phone}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug pr-4">
                    {row.address}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug pr-4 whitespace-pre-line">
                    {row.notes}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug pr-4 whitespace-pre-line">
                    {row.snoozeNotes}
                  </td>
                  <td className="px-3 py-4 align-top text-center text-blue-700">
                    <Globe className="w-4 h-4 mx-auto" />
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
                          onClick={() => void handleReactivate(row.id)}
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
            <span>{filteredRows.length === 0 ? '0-0' : `1-${filteredRows.length}`} of {filteredRows.length}</span>
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
