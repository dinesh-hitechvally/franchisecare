import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  Filter,
  ChevronLeft,
  ChevronRight,
  Download,
  Pencil,
  Trash2,
  FileText,
} from 'lucide-react'
import { documentsApi } from '../../api/services'
import type { DocumentItem } from '../../types'

interface FilterState {
  search: string
  category: string
}

const initialFilters: FilterState = {
  search: '',
  category: '',
}

const categoryBadgeStyle: Record<DocumentItem['category'], { bg: string; color: string }> = {
  manuals: { bg: '#dbeafe', color: '#1d4ed8' },
  templates: { bg: '#ede9fe', color: '#6d28d9' },
  other: { bg: '#f3f4f6', color: '#4b5563' },
}

function CategoryBadge({ category }: { category: DocumentItem['category'] }) {
  const style = categoryBadgeStyle[category] ?? categoryBadgeStyle.other
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
      {category}
    </span>
  )
}

function StatusBadge({ status }: { status: DocumentItem['status'] }) {
  const active = status === 'active'
  return (
    <span
      style={{
        display: 'inline-block',
        padding: '3px 10px',
        borderRadius: '9999px',
        fontSize: '11px',
        fontWeight: 600,
        textTransform: 'capitalize',
        background: active ? '#dcfce7' : '#fee2e2',
        color: active ? '#166534' : '#b91c1c',
      }}
    >
      {status}
    </span>
  )
}

function formatFileSize(bytes: number): string {
  if (!bytes && bytes !== 0) return '-'
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

export function ListDocuments() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const [showFilter, setShowFilter] = useState(false)
  const [filters, setFilters] = useState<FilterState>(initialFilters)
  const [appliedFilters, setAppliedFilters] = useState<FilterState>(initialFilters)
  const [rowsPerPage, setRowsPerPage] = useState(20)
  const [currentPage, setCurrentPage] = useState(1)
  const [downloadingId, setDownloadingId] = useState<number | null>(null)
  const [deletingId, setDeletingId] = useState<number | null>(null)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['documents', appliedFilters, rowsPerPage, currentPage],
    queryFn: () =>
      documentsApi.list({
        search: appliedFilters.search || undefined,
        category: appliedFilters.category || undefined,
        per_page: rowsPerPage,
        page: currentPage,
      }),
    placeholderData: (prev) => prev,
  })

  const documents = data?.data ?? []
  const totalDocuments = data?.total ?? 0
  const lastPage = data?.last_page ?? 1
  const startIndex = totalDocuments === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1
  const endIndex = Math.min(currentPage * rowsPerPage, totalDocuments)

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

  const handleDownload = async (doc: DocumentItem) => {
    setDownloadingId(doc.id)
    try {
      await documentsApi.download(doc.id, doc.file_name)
    } catch {
      toast.error('Failed to download file')
    } finally {
      setDownloadingId(null)
    }
  }

  const handleDelete = async (doc: DocumentItem) => {
    if (!window.confirm(`Delete "${doc.title}"? This cannot be undone.`)) return
    setDeletingId(doc.id)
    try {
      await documentsApi.remove(doc.id)
      toast.success('Document deleted successfully')
      queryClient.invalidateQueries({ queryKey: ['documents'] })
    } catch {
      toast.error('Failed to delete document')
    } finally {
      setDeletingId(null)
    }
  }

  return (
    <div className="page-content">
      <h1 className="page-title">List Documents</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">List Documents</h2>
          <div className="flex gap-2">
            <button className={`btn ${showFilter ? 'btn-primary' : 'btn-success'}`} onClick={() => setShowFilter(!showFilter)}>
              <Filter size={14} />
              FILTER
            </button>
            <button className="btn btn-primary" onClick={() => navigate('/documents/add')}>
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
                  placeholder="Title, description..."
                />
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Category</label>
                <select
                  value={filters.category}
                  onChange={(e) => handleFilterChange('category', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value="">All</option>
                  <option value="manuals">Manuals</option>
                  <option value="templates">Templates</option>
                  <option value="other">Other</option>
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
            {appliedFilters.category && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                Category: {appliedFilters.category}
                <button
                  onClick={() => removeFilter('category')}
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
                <th>File</th>
                <th>Uploaded By</th>
                <th>Status</th>
                <th>Created</th>
                <th className="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr>
                  <td colSpan={7} className="text-center py-8 text-gray-400">
                    Loading documents...
                  </td>
                </tr>
              ) : isError ? (
                <tr>
                  <td colSpan={7} className="text-center py-8 text-red-500">
                    Failed to load documents.
                  </td>
                </tr>
              ) : documents.length === 0 ? (
                <tr>
                  <td colSpan={7} className="text-center py-8 text-gray-400">
                    No documents found.
                  </td>
                </tr>
              ) : (
                documents.map((doc) => (
                  <tr key={doc.id}>
                    <td className="font-medium">{doc.title}</td>
                    <td>
                      <CategoryBadge category={doc.category} />
                    </td>
                    <td>
                      <div className="flex items-center gap-2">
                        <FileText size={16} className="text-gray-400 flex-shrink-0" />
                        <span className="truncate max-w-[200px]" title={doc.file_name}>
                          {doc.file_name}
                        </span>
                        <span className="text-gray-400 text-xs whitespace-nowrap">
                          ({formatFileSize(doc.file_size)})
                        </span>
                      </div>
                    </td>
                    <td>{doc.uploadedBy?.name ?? '-'}</td>
                    <td>
                      <StatusBadge status={doc.status} />
                    </td>
                    <td>{doc.created_at ? new Date(doc.created_at).toLocaleDateString() : '-'}</td>
                    <td className="text-center">
                      <div className="flex items-center justify-center gap-1">
                        <button
                          className="p-1 hover:bg-gray-100 rounded disabled:opacity-50"
                          title="Download"
                          disabled={downloadingId === doc.id}
                          onClick={() => handleDownload(doc)}
                        >
                          <Download size={16} className="text-gray-500" />
                        </button>
                        <button
                          className="p-1 hover:bg-gray-100 rounded"
                          title="Edit"
                          onClick={() => navigate(`/documents/edit/${doc.id}`)}
                        >
                          <Pencil size={16} className="text-gray-500" />
                        </button>
                        <button
                          className="p-1 hover:bg-gray-100 rounded disabled:opacity-50"
                          title="Delete"
                          disabled={deletingId === doc.id}
                          onClick={() => handleDelete(doc)}
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
            {startIndex} - {endIndex} of {totalDocuments}
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
