import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { PortalMenu } from '../../components/ui/PortalMenu'
import { TablePagination } from '../../components/ui/TablePagination'
import { Input } from '../../components/ui/Input'
import { Search, Plus, Check, X, MoreVertical, Eye, Edit3, Trash2 } from 'lucide-react'
import { blockoutsApi } from '../../api/services'
import { useAuthStore } from '../../store/authStore'
import { useToastStore } from '../../store/toastStore'
import { format } from 'date-fns'
import type { Blockout } from '../../types'

export function RecurringBlockoutsPage() {
  const [searchTerm, setSearchTerm] = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(25)
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)

  const { user } = useAuthStore()
  const { addToast } = useToastStore()
  const queryClient = useQueryClient()

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchTerm), 400)
    return () => clearTimeout(t)
  }, [searchTerm])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch])

  const { data: listResult, isLoading } = useQuery({
    queryKey: ['blockouts', 'recurring', user?.companyId, debouncedSearch, page, perPage],
    queryFn: () =>
      blockoutsApi.getPaginated({
        page,
        per_page: perPage,
        company_id: user?.companyId,
        search: debouncedSearch.trim() || undefined,
        is_recurring: true
      }),
  })

  const recurringBlockouts = listResult?.data ?? []
  const meta = listResult?.meta

  const deleteMutation = useMutation({
    mutationFn: (id: string) => blockoutsApi.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['blockouts'] })
      addToast('Recurring blockout deleted successfully', 'success')
      setOpenMenuId(null)
    },
    onError: () => {
      addToast('Could not delete recurring blockout', 'error')
    },
  })

  const handleDelete = (id: string) => {
    if (confirm('Are you sure you want to delete this recurring blockout?')) {
      deleteMutation.mutate(id)
    }
  }

  return (
    <div className="space-y-5 px-1 py-1">
      <Card className="px-6 py-4 shadow-sm border-gray-200">
        <h1 className="text-xl font-bold text-gray-800">Recurring Blockouts</h1>
      </Card>

      <Card className="shadow-sm border-gray-200">
        <div className="flex items-center justify-between px-2 py-1">
          <div className="relative w-full max-w-sm">
            <Search className="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <Input
              type="text"
              placeholder="Search recurring blockout"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
          </div>
          <Link to="/blockouts/new">
            <button className="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 transition-colors">
              <Plus className="w-4 h-4 text-gray-400" />
              <span>New Blockout</span>
            </button>
          </Link>
        </div>
      </Card>

      <Card className="shadow-sm border-gray-200">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 align-top">Title</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 align-top">Location</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 w-40 align-top">Start Date</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-center align-top">Start Time</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 w-40 align-top">End Date</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-center align-top">End Time</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-center align-top">Active</th>
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 text-right align-top">Mgmt</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {isLoading ? (
                <tr>
                  <td colSpan={8} className="px-5 py-8 text-center text-gray-500 italic">Loading recurring blockouts...</td>
                </tr>
              ) : recurringBlockouts.length === 0 ? (
                <tr>
                  <td colSpan={8} className="px-5 py-8 text-center text-gray-500 italic">No recurring blockouts found.</td>
                </tr>
              ) : (
                recurringBlockouts.map((row: Blockout) => (
                  <tr key={row.id} className="hover:bg-gray-50 relative group">
                    <td className="relative px-5 py-4 text-sm font-bold text-gray-700 align-top">
                      <div className="absolute left-0 top-0 bottom-0 w-1 bg-purple-400"></div>
                      {row.title}
                    </td>

                    <td className="px-5 py-4 text-sm text-gray-600 align-top">{row.location || '-'}</td>

                    <td className="px-5 py-4 text-sm text-gray-700 w-40 align-top">
                      {format(new Date(row.startDate), 'EEE, do MMM yyyy')}
                    </td>

                    <td className="px-5 py-4 text-sm text-gray-700 text-center align-top">{row.startTime}</td>

                    <td className="px-5 py-4 text-sm text-gray-700 w-40 align-top">
                      {format(new Date(row.endDate), 'EEE, do MMM yyyy')}
                    </td>

                    <td className="px-5 py-4 text-sm text-gray-700 text-center align-top">{row.endTime}</td>

                    <td className="px-5 py-4 text-center align-top">
                      {row.active ? (
                        <Check className="w-5 h-5 text-green-600 inline" />
                      ) : (
                        <span className="inline-block w-5 h-5"></span>
                      )}
                    </td>

                    <td className="px-5 py-4 text-right align-top">
                        <button
                          onClick={(e) => {
                            e.stopPropagation()
                            if (openMenuId === row.id) {
                              setOpenMenuId(null); setMenuPos(null)
                            } else {
                              const rect = (e.currentTarget as HTMLButtonElement).getBoundingClientRect()
                              setMenuPos({ top: rect.bottom + 4, left: rect.right - 192 })
                              setOpenMenuId(row.id)
                            }
                          }}
                          className="text-gray-400 hover:text-gray-600 p-1.5 focus:outline-none rounded-full hover:bg-gray-100 transition-colors"
                        >
                          <MoreVertical className="w-5 h-5" />
                        </button>

                        <PortalMenu
                          isOpen={openMenuId === row.id}
                          onClose={() => { setOpenMenuId(null); setMenuPos(null) }}
                          position={menuPos}
                        >
                            <button
                              onClick={() => {
                                setOpenMenuId(null)
                              }}
                              className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                            >
                              <Eye className="w-4 h-4 text-gray-400" />
                              View
                            </button>
                            <button
                              onClick={() => {
                                setOpenMenuId(null)
                              }}
                              className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                            >
                              <Edit3 className="w-4 h-4 text-gray-400" />
                              Edit
                            </button>
                            <button
                              onClick={() => handleDelete(row.id)}
                              className="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors border-t border-gray-100 mt-1"
                            >
                              <Trash2 className="w-4 h-4 text-red-400" />
                              Delete
                            </button>
                        </PortalMenu>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {meta && (
          <TablePagination
            meta={meta}
            onPageChange={setPage}
            onPerPageChange={setPerPage}
          />
        )}
      </Card>
    </div>
  )
}
