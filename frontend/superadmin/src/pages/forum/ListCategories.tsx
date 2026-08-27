import { useState, useRef, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { Filter, Check, X, ChevronUp, ChevronDown, ChevronLeft, ChevronRight, MoreVertical, Pencil, Trash2 } from 'lucide-react'
import { forumCategoriesApi } from '../../api/services'
import type { ForumCategory } from '../../types'

type SortField = 'id' | 'name' | 'posts_count' | 'status' | 'created_at'
type SortOrder = 'asc' | 'desc'

interface FilterState {
  search: string
  status: string
}

const initialFilters: FilterState = { search: '', status: '' }

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleString('en-US', {
    year: 'numeric', month: 'long', day: '2-digit', hour: 'numeric', minute: '2-digit', hour12: true,
  })
}

export function ListCategories() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [sortField, setSortField] = useState<SortField>('id')
  const [sortOrder, setSortOrder] = useState<SortOrder>('desc')
  const [openMenuId, setOpenMenuId] = useState<number | null>(null)
  const menuRef = useRef<HTMLDivElement>(null)
  const [showFilter, setShowFilter] = useState(false)
  const [filters, setFilters] = useState<FilterState>(initialFilters)
  const [appliedFilters, setAppliedFilters] = useState<FilterState>(initialFilters)
  const [rowsPerPage, setRowsPerPage] = useState(10)
  const [currentPage, setCurrentPage] = useState(1)

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
    queryKey: ['forum-categories', appliedFilters, rowsPerPage, currentPage],
    queryFn: () =>
      forumCategoriesApi.list({
        search: appliedFilters.search || undefined,
        status: appliedFilters.status || undefined,
        per_page: rowsPerPage,
        page: currentPage,
      }),
    placeholderData: (prev) => prev,
  })

  const categories = data?.data ?? []
  const totalCategories = data?.total ?? 0
  const lastPage = data?.last_page ?? 1
  const startIndex = totalCategories === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1
  const endIndex = Math.min(currentPage * rowsPerPage, totalCategories)

  const sortedCategories = [...categories].sort((a, b) => {
    let cmp = 0
    if (sortField === 'posts_count') cmp = (a.posts_count ?? 0) - (b.posts_count ?? 0)
    else if (sortField === 'status') cmp = a.status.localeCompare(b.status)
    else if (sortField === 'created_at') cmp = new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
    else if (sortField === 'id') cmp = a.id - b.id
    else cmp = a.name.localeCompare(b.name)
    return sortOrder === 'asc' ? cmp : -cmp
  })

  const handleSort = (field: SortField) => {
    if (sortField === field) {
      setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc')
    } else {
      setSortField(field)
      setSortOrder('asc')
    }
  }

  const SortIcon = ({ field }: { field: SortField }) => {
    return (
      <span className="inline-flex flex-col ml-1">
        <ChevronUp size={10} className={sortField === field && sortOrder === 'asc' ? 'text-purple-600' : 'text-gray-400'} />
        <ChevronDown size={10} className={`-mt-1 ${sortField === field && sortOrder === 'desc' ? 'text-purple-600' : 'text-gray-400'}`} />
      </span>
    )
  }

  const handleFilterChange = (field: keyof FilterState, value: string) => {
    setFilters(prev => ({ ...prev, [field]: value }))
  }

  const applyFilters = () => {
    setAppliedFilters(filters)
    setCurrentPage(1)
    setShowFilter(false)
  }

  const cancelFilters = () => {
    setFilters(appliedFilters)
    setShowFilter(false)
  }

  const removeFilter = (field: keyof FilterState) => {
    const newFilters = { ...appliedFilters, [field]: '' }
    setAppliedFilters(newFilters)
    setFilters(newFilters)
    setCurrentPage(1)
  }

  const hasActiveFilters = Object.values(appliedFilters).some(v => v !== '')

  const handleDelete = async (category: ForumCategory) => {
    if (!window.confirm(`Delete category "${category.name}"?`)) return
    try {
      await forumCategoriesApi.remove(category.id)
      toast.success('Category deleted successfully!')
      queryClient.invalidateQueries({ queryKey: ['forum-categories'] })
    } catch (err: any) {
      toast.error(err?.response?.data?.message ?? 'Failed to delete category')
    }
  }

  return (
    <div className="page-content">
      <h1 className="page-title">List Categories</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">Forum Categories</h2>
          <div className="flex gap-2">
            <button className={`btn ${showFilter ? 'btn-primary' : 'btn-success'}`} onClick={() => setShowFilter(!showFilter)}>
              <Filter size={14} />
              FILTER
            </button>
            <button
              className="btn btn-primary"
              onClick={() => navigate('/forum/add-categories')}
            >
              +ADD
            </button>
          </div>
        </div>

        {showFilter && (
          <div className="p-6 border-b bg-white">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm text-gray-600 mb-1">Search</label>
                <input
                  type="text"
                  value={filters.search}
                  onChange={(e) => handleFilterChange('search', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  placeholder="Name or description..."
                />
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Status</label>
                <select
                  value={filters.status}
                  onChange={(e) => handleFilterChange('status', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value="">All</option>
                  <option value="ACTIVE">Active</option>
                  <option value="INACTIVE">Inactive</option>
                </select>
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

        {hasActiveFilters && (
          <div className="px-6 py-3 border-b bg-gray-50 flex items-center gap-2 flex-wrap">
            <span className="text-sm text-gray-600">Filtered:</span>
            {appliedFilters.search && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                Search: {appliedFilters.search}
                <button onClick={() => removeFilter('search')} className="w-4 h-4 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs">×</button>
              </span>
            )}
            {appliedFilters.status && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                Status: {appliedFilters.status}
                <button onClick={() => removeFilter('status')} className="w-4 h-4 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs">×</button>
              </span>
            )}
          </div>
        )}

        <div className="card-body p-0">
          <table className="table">
            <thead>
              <tr>
                <th className="cursor-pointer" onClick={() => handleSort('id')}>
                  <span className="flex items-center">ID <SortIcon field="id" /></span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('name')}>
                  <span className="flex items-center">Name <SortIcon field="name" /></span>
                </th>
                <th>Description</th>
                <th className="cursor-pointer" onClick={() => handleSort('posts_count')}>
                  <span className="flex items-center">Posts <SortIcon field="posts_count" /></span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('status')}>
                  <span className="flex items-center">Active <SortIcon field="status" /></span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('created_at')}>
                  <span className="flex items-center">Created <SortIcon field="created_at" /></span>
                </th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {isLoading && (
                <tr><td colSpan={7} className="text-center py-6 text-gray-500">Loading...</td></tr>
              )}
              {isError && (
                <tr><td colSpan={7} className="text-center py-6 text-red-500">Failed to load categories.</td></tr>
              )}
              {!isLoading && !isError && sortedCategories.length === 0 && (
                <tr><td colSpan={7} className="text-center py-6 text-gray-500">No categories found</td></tr>
              )}
              {sortedCategories.map((category) => (
                <tr key={category.id}>
                  <td>{category.id}</td>
                  <td className="font-medium">{category.name}</td>
                  <td className="text-gray-500 text-sm">{category.description}</td>
                  <td>{category.posts_count ?? 0}</td>
                  <td>
                    {category.status === 'ACTIVE' ? (
                      <Check size={20} className="icon-check" />
                    ) : (
                      <X size={20} className="icon-cross" />
                    )}
                  </td>
                  <td>{formatDate(category.created_at)}</td>
                  <td>
                    <div className="relative" ref={openMenuId === category.id ? menuRef : null}>
                      <button
                        className="p-2 hover:bg-gray-100 rounded-full transition-colors"
                        onClick={() => setOpenMenuId(openMenuId === category.id ? null : category.id)}
                      >
                        <MoreVertical size={18} />
                      </button>
                      {openMenuId === category.id && (
                        <div className="absolute right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 min-w-[120px]">
                          <button
                            className="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2"
                            onClick={() => {
                              setOpenMenuId(null)
                              navigate(`/forum/edit-category/${category.id}`)
                            }}
                          >
                            <Pencil size={14} />
                            Edit
                          </button>
                          <button
                            className="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                            onClick={() => {
                              setOpenMenuId(null)
                              handleDelete(category)
                            }}
                          >
                            <Trash2 size={14} />
                            Delete
                          </button>
                        </div>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="card-footer flex items-center justify-end gap-4 py-3 px-6">
          <div className="flex items-center gap-2 text-sm text-gray-600">
            <span>Rows per page:</span>
            <select
              value={rowsPerPage}
              onChange={(e) => { setRowsPerPage(Number(e.target.value)); setCurrentPage(1) }}
              className="border-none bg-transparent"
            >
              <option value={10}>10</option>
              <option value={20}>20</option>
              <option value={50}>50</option>
              <option value={100}>100</option>
            </select>
          </div>
          <span className="text-sm text-gray-600">{startIndex}-{endIndex} of {totalCategories}</span>
          <div className="flex gap-1">
            <button
              className="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
              onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
              disabled={currentPage === 1}
            >
              <ChevronLeft size={18} />
            </button>
            <button
              className="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
              onClick={() => setCurrentPage(p => Math.min(lastPage, p + 1))}
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
