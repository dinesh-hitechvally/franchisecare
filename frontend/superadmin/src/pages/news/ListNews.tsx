import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { Filter, ChevronLeft, ChevronRight, Pencil, Trash2, Send, Image as ImageIcon } from 'lucide-react'
import { newsApi } from '../../api/services'
import type { NewsItem } from '../../types'

interface FilterState {
  search: string
  status: string
}

const initialFilters: FilterState = {
  search: '',
  status: '',
}

const statusBadgeStyle: Record<NewsItem['status'], { bg: string; color: string }> = {
  draft: { bg: '#f3f4f6', color: '#4b5563' },
  published: { bg: '#dcfce7', color: '#166534' },
}

function StatusBadge({ status }: { status: NewsItem['status'] }) {
  const style = statusBadgeStyle[status] ?? statusBadgeStyle.draft
  return (
    <span
      style={{
        display: 'inline-block',
        padding: '3px 10px',
        borderRadius: '9999px',
        fontSize: '11px',
        fontWeight: 600,
        textTransform: 'capitalize',
        background: style.bg,
        color: style.color,
      }}
    >
      {status}
    </span>
  )
}

export function ListNews() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const [showFilter, setShowFilter] = useState(false)
  const [filters, setFilters] = useState<FilterState>(initialFilters)
  const [appliedFilters, setAppliedFilters] = useState<FilterState>(initialFilters)
  const [rowsPerPage, setRowsPerPage] = useState(20)
  const [currentPage, setCurrentPage] = useState(1)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['news', appliedFilters, rowsPerPage, currentPage],
    queryFn: () =>
      newsApi.list({
        search: appliedFilters.search || undefined,
        status: appliedFilters.status || undefined,
        per_page: rowsPerPage,
        page: currentPage,
      }),
    placeholderData: (prev) => prev,
  })

  const news = data?.data ?? []
  const totalNews = data?.total ?? 0
  const lastPage = data?.last_page ?? 1
  const startIndex = totalNews === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1
  const endIndex = Math.min(currentPage * rowsPerPage, totalNews)

  const publishMutation = useMutation({
    mutationFn: (id: number) => newsApi.publish(id),
    onSuccess: () => {
      toast.success('News published')
      queryClient.invalidateQueries({ queryKey: ['news'] })
    },
    onError: () => {
      toast.error('Failed to publish news')
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => newsApi.remove(id),
    onSuccess: () => {
      toast.success('News deleted')
      queryClient.invalidateQueries({ queryKey: ['news'] })
    },
    onError: () => {
      toast.error('Failed to delete news')
    },
  })

  const handleDelete = (id: number) => {
    if (window.confirm('Are you sure you want to delete this news item?')) {
      deleteMutation.mutate(id)
    }
  }

  const handleFilterChange = (field: keyof FilterState, value: string) => {
    setFilters((prev) => ({ ...prev, [field]: value }))
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

  const hasActiveFilters = Object.values(appliedFilters).some((v) => v !== '')

  return (
    <div className="page-content">
      <h1 className="page-title">List News</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">List News</h2>
          <div className="flex gap-2">
            <button className={`btn ${showFilter ? 'btn-primary' : 'btn-success'}`} onClick={() => setShowFilter(!showFilter)}>
              <Filter size={14} />
              FILTER
            </button>
            <button className="btn btn-primary" onClick={() => navigate('/news/add')}>
              +ADD
            </button>
          </div>
        </div>

        {/* Filter Panel */}
        {showFilter && (
          <div className="p-6 border-b bg-white">
            <div className="grid grid-cols-3 gap-4">
              <div className="col-span-2">
                <label className="block text-sm text-gray-600 mb-1">Search</label>
                <input
                  type="text"
                  value={filters.search}
                  onChange={(e) => handleFilterChange('search', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  placeholder="Title or content..."
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
                  <option value="draft">Draft</option>
                  <option value="published">Published</option>
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

        {/* Applied Filters */}
        {hasActiveFilters && (
          <div className="px-6 py-3 border-b bg-gray-50 flex items-center gap-2 flex-wrap">
            <span className="text-sm text-gray-600">Filtered:</span>
            {appliedFilters.search && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                Search: {appliedFilters.search}
                <button
                  onClick={() => removeFilter('search')}
                  className="w-4 h-4 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs"
                >
                  ×
                </button>
              </span>
            )}
            {appliedFilters.status && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                Status: {appliedFilters.status}
                <button
                  onClick={() => removeFilter('status')}
                  className="w-4 h-4 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs"
                >
                  ×
                </button>
              </span>
            )}
          </div>
        )}

        <div className="card-body p-0">
          <table className="table">
            <thead>
              <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Status</th>
                <th>Author</th>
                <th>Published / Created</th>
                <th className="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr>
                  <td colSpan={6} className="text-center py-8 text-gray-400">
                    Loading news...
                  </td>
                </tr>
              ) : isError ? (
                <tr>
                  <td colSpan={6} className="text-center py-8 text-red-500">
                    Failed to load news.
                  </td>
                </tr>
              ) : news.length === 0 ? (
                <tr>
                  <td colSpan={6} className="text-center py-8 text-gray-400">
                    No news found.
                  </td>
                </tr>
              ) : (
                news.map((item) => (
                  <tr key={item.id}>
                    <td className="font-medium">
                      <span className="inline-flex items-center gap-2">
                        {item.title}
                        {item.image && (
                          <span title="Has image">
                            <ImageIcon size={14} className="text-gray-400" />
                          </span>
                        )}
                      </span>
                    </td>
                    <td>{item.category || '-'}</td>
                    <td>
                      <StatusBadge status={item.status} />
                    </td>
                    <td>{item.author?.name ?? '-'}</td>
                    <td>
                      {item.published_at
                        ? new Date(item.published_at).toLocaleDateString()
                        : item.created_at
                        ? new Date(item.created_at).toLocaleDateString()
                        : '-'}
                    </td>
                    <td className="text-center">
                      <div className="flex items-center justify-center gap-1">
                        {item.status === 'draft' && (
                          <button
                            className="btn btn-success text-xs py-1 px-2"
                            onClick={() => publishMutation.mutate(item.id)}
                            disabled={publishMutation.isPending}
                            title="Publish"
                          >
                            <Send size={12} />
                            PUBLISH
                          </button>
                        )}
                        <button
                          className="p-1 hover:bg-gray-100 rounded"
                          onClick={() => navigate(`/news/edit/${item.id}`)}
                          title="Edit"
                        >
                          <Pencil size={16} className="text-gray-500" />
                        </button>
                        <button
                          className="p-1 hover:bg-gray-100 rounded"
                          onClick={() => handleDelete(item.id)}
                          disabled={deleteMutation.isPending}
                          title="Delete"
                        >
                          <Trash2 size={16} className="text-red-500" />
                        </button>
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
            {startIndex} - {endIndex} of {totalNews}
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
