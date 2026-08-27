import { useMemo, useRef, useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { Filter, Check, X, ChevronUp, ChevronDown, ChevronLeft, ChevronRight, MoreVertical, Pencil, Trash2 } from 'lucide-react'
import { appVersionsApi } from '../../api/services'
import type { AppVersion } from '../../types'

type SortField = 'id' | 'version' | 'title' | 'logout_required' | 'refresh_required' | 'created_at'
type SortOrder = 'asc' | 'desc'

export function ListVersions() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const [showFilter, setShowFilter] = useState(false)
  const [search, setSearch] = useState('')
  const [appliedSearch, setAppliedSearch] = useState('')
  const [rowsPerPage, setRowsPerPage] = useState(20)
  const [currentPage, setCurrentPage] = useState(1)
  const [sortField, setSortField] = useState<SortField>('id')
  const [sortOrder, setSortOrder] = useState<SortOrder>('desc')
  const [openMenuId, setOpenMenuId] = useState<number | null>(null)
  const menuRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
        setOpenMenuId(null)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const { data, isLoading, isError } = useQuery({
    queryKey: ['app-versions', appliedSearch, rowsPerPage, currentPage],
    queryFn: () =>
      appVersionsApi.list({
        search: appliedSearch || undefined,
        per_page: rowsPerPage,
        page: currentPage,
      }),
    placeholderData: (prev) => prev,
  })

  const versions = data?.data ?? []
  const totalVersions = data?.total ?? 0
  const lastPage = data?.last_page ?? 1
  const startIndex = totalVersions === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1
  const endIndex = Math.min(currentPage * rowsPerPage, totalVersions)

  const sortedVersions = useMemo(() => {
    const sorted = [...versions]
    sorted.sort((a, b) => {
      let cmp = 0
      switch (sortField) {
        case 'id':
          cmp = a.id - b.id
          break
        case 'version':
          cmp = a.version.localeCompare(b.version)
          break
        case 'title':
          cmp = a.title.localeCompare(b.title)
          break
        case 'logout_required':
          cmp = Number(a.logout_required) - Number(b.logout_required)
          break
        case 'refresh_required':
          cmp = Number(a.refresh_required) - Number(b.refresh_required)
          break
        case 'created_at':
          cmp = new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
          break
      }
      return sortOrder === 'asc' ? cmp : -cmp
    })
    return sorted
  }, [versions, sortField, sortOrder])

  const deleteMutation = useMutation({
    mutationFn: (id: number) => appVersionsApi.remove(id),
    onSuccess: () => {
      toast.success('Version deleted')
      queryClient.invalidateQueries({ queryKey: ['app-versions'] })
    },
    onError: () => {
      toast.error('Failed to delete version')
    },
  })

  const handleDelete = (id: number) => {
    if (window.confirm('Are you sure you want to delete this version?')) {
      deleteMutation.mutate(id)
    }
  }

  const handleSort = (field: SortField) => {
    if (sortField === field) {
      setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc')
    } else {
      setSortField(field)
      setSortOrder('asc')
    }
  }

  const applyFilters = () => {
    setAppliedSearch(search)
    setCurrentPage(1)
    setShowFilter(false)
  }

  const cancelFilters = () => {
    setSearch(appliedSearch)
    setShowFilter(false)
  }

  const removeFilter = () => {
    setAppliedSearch('')
    setSearch('')
    setCurrentPage(1)
  }

  const SortIcon = ({ field }: { field: SortField }) => {
    return (
      <span className="inline-flex flex-col ml-1">
        <ChevronUp size={10} className={sortField === field && sortOrder === 'asc' ? 'text-purple-600' : 'text-gray-400'} />
        <ChevronDown size={10} className={`-mt-1 ${sortField === field && sortOrder === 'desc' ? 'text-purple-600' : 'text-gray-400'}`} />
      </span>
    )
  }

  const formatDate = (value: string) => {
    const d = new Date(value)
    if (Number.isNaN(d.getTime())) return value
    return d.toLocaleString(undefined, {
      year: 'numeric',
      month: 'long',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  return (
    <div className="page-content">
      <h1 className="page-title">List Versions</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">List Versions</h2>
          <div className="flex gap-2">
            <button className={`btn ${showFilter ? 'btn-primary' : 'btn-success'}`} onClick={() => setShowFilter(!showFilter)}>
              <Filter size={14} />
              FILTER
            </button>
            <button
              className="btn btn-primary"
              onClick={() => navigate('/versions/add')}
            >
              +ADD
            </button>
          </div>
        </div>

        {showFilter && (
          <div className="p-6 border-b bg-white">
            <div className="grid grid-cols-3 gap-4">
              <div className="col-span-2">
                <label className="block text-sm text-gray-600 mb-1">Search</label>
                <input
                  type="text"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  placeholder="Title or version..."
                />
              </div>
            </div>
            <div className="flex justify-end gap-3 mt-4">
              <button onClick={cancelFilters} className="text-red-500 hover:text-red-600 font-medium">
                CANCEL
              </button>
              <button onClick={applyFilters} className="btn btn-primary">
                FILTER DATA
              </button>
            </div>
          </div>
        )}

        {appliedSearch && (
          <div className="px-6 py-3 border-b bg-gray-50 flex items-center gap-2 flex-wrap">
            <span className="text-sm text-gray-600">Filtered:</span>
            <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
              Search: {appliedSearch}
              <button
                onClick={removeFilter}
                className="w-4 h-4 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs"
              >
                ×
              </button>
            </span>
          </div>
        )}

        <div className="card-body">
          <table className="table">
            <thead>
              <tr>
                <th className="cursor-pointer" onClick={() => handleSort('id')}>
                  <span className="flex items-center">
                    ID <SortIcon field="id" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('version')}>
                  <span className="flex items-center">
                    Version <SortIcon field="version" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('title')}>
                  <span className="flex items-center">
                    Title <SortIcon field="title" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('logout_required')}>
                  <span className="flex items-center">
                    Logout <SortIcon field="logout_required" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('refresh_required')}>
                  <span className="flex items-center">
                    Refresh <SortIcon field="refresh_required" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('created_at')}>
                  <span className="flex items-center">
                    Date <SortIcon field="created_at" />
                  </span>
                </th>
                <th className="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr>
                  <td colSpan={7} className="text-center py-8 text-gray-400">
                    Loading versions...
                  </td>
                </tr>
              ) : isError ? (
                <tr>
                  <td colSpan={7} className="text-center py-8 text-red-500">
                    Failed to load versions.
                  </td>
                </tr>
              ) : sortedVersions.length === 0 ? (
                <tr>
                  <td colSpan={7} className="text-center py-8 text-gray-400">
                    No versions found.
                  </td>
                </tr>
              ) : (
                sortedVersions.map((version: AppVersion) => (
                  <tr key={version.id}>
                    <td>{version.id}</td>
                    <td>{version.version}</td>
                    <td>{version.title}</td>
                    <td>
                      {version.logout_required ? (
                        <Check size={18} className="inline text-purple-600" />
                      ) : (
                        <X size={18} className="inline text-red-500" />
                      )}
                    </td>
                    <td>
                      {version.refresh_required ? (
                        <Check size={18} className="inline text-purple-600" />
                      ) : (
                        <X size={18} className="inline text-red-500" />
                      )}
                    </td>
                    <td>{formatDate(version.created_at)}</td>
                    <td className="text-center relative">
                      <div className="inline-block relative" ref={openMenuId === version.id ? menuRef : null}>
                        <button
                          className="p-1 hover:bg-gray-100 rounded"
                          onClick={() => setOpenMenuId(openMenuId === version.id ? null : version.id)}
                        >
                          <MoreVertical size={18} className="text-gray-500" />
                        </button>
                        {openMenuId === version.id && (
                          <div className="absolute right-2 top-full z-10 bg-white border border-gray-200 rounded shadow-md w-32">
                            <button
                              className="w-full px-3 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2"
                              onClick={() => {
                                setOpenMenuId(null)
                                navigate(`/versions/edit/${version.id}`)
                              }}
                            >
                              <Pencil size={14} />
                              Edit
                            </button>
                            <button
                              className="w-full px-3 py-2 text-left text-sm text-red-600 hover:bg-gray-50 flex items-center gap-2"
                              onClick={() => {
                                setOpenMenuId(null)
                                handleDelete(version.id)
                              }}
                              disabled={deleteMutation.isPending}
                            >
                              <Trash2 size={14} />
                              Delete
                            </button>
                          </div>
                        )}
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        <div className="card-footer flex items-center justify-end gap-4 py-3 px-6">
          <div className="flex items-center gap-2 text-sm text-gray-600">
            <span>Rows per page:</span>
            <select
              value={rowsPerPage}
              onChange={(e) => {
                setRowsPerPage(Number(e.target.value))
                setCurrentPage(1)
              }}
              className="border-none bg-transparent"
            >
              <option value={10}>10</option>
              <option value={20}>20</option>
              <option value={50}>50</option>
              <option value={100}>100</option>
            </select>
          </div>
          <span className="text-sm text-gray-600">
            {startIndex} - {endIndex} of {totalVersions}
          </span>
          <div className="flex gap-1">
            <button
              className="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
              onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
              disabled={currentPage === 1}
            >
              <ChevronLeft size={18} />
            </button>
            <button
              className="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
              onClick={() => setCurrentPage((p) => Math.min(lastPage, p + 1))}
              disabled={currentPage >= lastPage}
            >
              <ChevronRight size={18} />
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
