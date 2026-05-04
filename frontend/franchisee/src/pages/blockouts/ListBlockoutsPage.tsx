import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Input } from '../../components/ui/Input'
import { Search, Plus, Check, ChevronLeft, ChevronRight, MoreVertical, Eye, Edit3, Trash2 } from 'lucide-react'
import { blockoutsApi } from '../../api/services'
import { useAuthStore } from '../../store/authStore'
import { useToastStore } from '../../store/toastStore'
import { format } from 'date-fns'

export function ListBlockoutsPage() {
  const [searchTerm, setSearchTerm] = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(25)
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  
  const { user } = useAuthStore()
  const { addToast } = useToastStore()
  const queryClient = useQueryClient()

  // Debounce search
  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchTerm), 400)
    return () => clearTimeout(t)
  }, [searchTerm])

  const { data: listResult, isLoading } = useQuery({
    queryKey: ['blockouts', user?.companyId, debouncedSearch, page, perPage],
    queryFn: () =>
      blockoutsApi.getPaginated({
        page,
        per_page: perPage,
        company_id: user?.companyId,
        search: debouncedSearch.trim() || undefined,
        is_recurring: false
      }),
  })

  const blockouts = listResult?.data ?? []
  const listMeta = listResult?.meta

  const deleteMutation = useMutation({
    mutationFn: (id: string) => blockoutsApi.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['blockouts'] })
      addToast('Blockout deleted successfully', 'success')
      setOpenMenuId(null)
    },
    onError: () => {
      addToast('Could not delete blockout', 'error')
    },
  })

  const handleDelete = (id: string) => {
    if (confirm('Are you sure you want to delete this blockout?')) {
      deleteMutation.mutate(id)
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">List Blockouts</h1>
      </div>

      <Card>
        <div className="p-4 flex items-center justify-between border-b border-gray-200 bg-white rounded-t-lg">
          <div className="relative w-72">
            <Search className="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <Input
              placeholder="Search blockout"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="pl-10 h-10 w-full"
            />
          </div>
          <Link to="/blockouts/new">
            <Button className="gap-2 shrink-0">
              <Plus className="w-4 h-4" />
              New Blockout
            </Button>
          </Link>
        </div>

        <Table
          columns={[
            { key: 'title', title: 'Name', render: (row) => <span className="font-medium text-gray-900">{row.title}</span> },
            { key: 'location', title: 'Location' },
            { 
              key: 'start', 
              title: 'Start Date/Time',
              render: (row) => (
                <div className="flex flex-col">
                  <span>{format(new Date(row.startDate), 'EEEE, do MMM yyyy')}</span>
                  <span className="text-gray-500 text-xs">{row.startTime}</span>
                </div>
              )
            },
            { 
              key: 'end', 
              title: 'End Date/Time',
              render: (row) => (
                <div className="flex flex-col">
                  <span>{format(new Date(row.endDate), 'EEEE, do MMM yyyy')}</span>
                  <span className="text-gray-500 text-xs">{row.endTime}</span>
                </div>
              )
            },
            { key: 'notes', title: 'Desc', render: (row) => <div className="max-w-[150px] truncate" title={row.notes}>{row.notes}</div> },
            { 
              key: 'active', 
              title: 'Active',
              render: (row) => (
                <div className="flex justify-center">
                  {row.active && <Check className="w-5 h-5 text-green-500" />}
                </div>
              )
            },
            { 
              key: 'isRecurring', 
              title: 'Recur',
              render: (row) => (
                <div className="flex justify-center">
                  {row.isRecurring && <Check className="w-5 h-5 text-green-500" />}
                </div>
              )
            },
            {
              key: 'mgmt',
              title: 'Mgmt',
              render: (row) => (
                <div className="relative">
                  <button
                    onClick={() => setOpenMenuId(openMenuId === row.id ? null : row.id)}
                    className="p-1 hover:bg-gray-100 rounded-full transition-colors"
                  >
                    <MoreVertical className="w-5 h-5 text-gray-400" />
                  </button>

                  {openMenuId === row.id && (
                    <div className="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg z-[100] py-1">
                      <button
                        onClick={() => {
                          setOpenMenuId(null)
                          // View logic
                        }}
                        className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                      >
                        <Eye className="w-4 h-4 text-gray-400" />
                        View
                      </button>
                      <button
                        onClick={() => {
                          setOpenMenuId(null)
                          // Edit logic
                        }}
                        className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                      >
                        <Edit3 className="w-4 h-4 text-gray-400" />
                        Edit
                      </button>
                      <button
                        onClick={() => handleDelete(row.id)}
                        className="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                      >
                        <Trash2 className="w-4 h-4 text-red-500" />
                        Delete
                      </button>
                    </div>
                  )}
                </div>
              )
            }
          ]}
          data={blockouts}
          keyExtractor={(row) => row.id}
          isLoading={isLoading}
        />
        
        {listMeta && (
          <div className="px-4 py-3 border-t border-gray-200 bg-white flex items-center justify-end rounded-b-lg text-sm text-gray-500">
            <div className="flex items-center gap-2">
              <span>Rows per page:</span>
              <select 
                value={perPage}
                onChange={(e) => setPerPage(Number(e.target.value))}
                className="border-none bg-transparent outline-none cursor-pointer"
              >
                <option value={25}>25</option>
                <option value={50}>50</option>
                <option value={100}>100</option>
              </select>
            </div>
            <div className="flex items-center gap-4 ml-6">
              <span>{listMeta.from}-{listMeta.to} of {listMeta.total}</span>
              <div className="flex items-center gap-1">
                <button 
                  disabled={page === 1}
                  onClick={() => setPage(p => Math.max(1, p - 1))}
                  className="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
                >
                  <ChevronLeft className="w-5 h-5" />
                </button>
                <button 
                  disabled={page === listMeta.last_page}
                  onClick={() => setPage(p => p + 1)}
                  className="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
                >
                  <ChevronRight className="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>
        )}
      </Card>
    </div>
  )
}
