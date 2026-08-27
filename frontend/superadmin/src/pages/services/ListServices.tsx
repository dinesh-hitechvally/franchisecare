import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { Filter, ChevronUp, ChevronDown, ChevronLeft, ChevronRight, Check, X, MoreVertical } from 'lucide-react'
import { servicesApi, serviceCategoriesApi } from '../../api/services'
import type { Service } from '../../types'

type SortField = 'name' | 'category' | 'sort_order' | 'base_price' | 'duration' | 'status'
type SortOrder = 'asc' | 'desc'

interface FilterState {
  search: string
  activeService: string
  categoryId: string
}

const initialFilters: FilterState = {
  search: '',
  activeService: '',
  categoryId: ''
}

export function ListServices() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [sortField, setSortField] = useState<SortField>('name')
  const [sortOrder, setSortOrder] = useState<SortOrder>('asc')
  const [showFilter, setShowFilter] = useState(false)
  const [filters, setFilters] = useState<FilterState>(initialFilters)
  const [appliedFilters, setAppliedFilters] = useState<FilterState>(initialFilters)
  const [rowsPerPage, setRowsPerPage] = useState(100)
  const [currentPage, setCurrentPage] = useState(1)
  const [openMenuId, setOpenMenuId] = useState<number | null>(null)

  const { data: categories = [] } = useQuery({
    queryKey: ['service-categories', 'all'],
    queryFn: () => serviceCategoriesApi.list(),
  })

  const { data: services = [], isLoading } = useQuery({
    queryKey: ['services', appliedFilters],
    queryFn: () =>
      servicesApi.list({
        search: appliedFilters.search || undefined,
        status: appliedFilters.activeService === 'yes' ? 'ACTIVE' : appliedFilters.activeService === 'no' ? 'INACTIVE' : undefined,
        category_id: appliedFilters.categoryId ? Number(appliedFilters.categoryId) : undefined,
      }),
  })

  const sortedServices = useMemo(() => {
    const list = [...services]
    list.sort((a, b) => {
      let aVal: string | number
      let bVal: string | number
      switch (sortField) {
        case 'category':
          aVal = a.category?.name ?? ''
          bVal = b.category?.name ?? ''
          break
        case 'status':
          aVal = a.status
          bVal = b.status
          break
        default:
          aVal = a[sortField]
          bVal = b[sortField]
      }
      if (aVal < bVal) return sortOrder === 'asc' ? -1 : 1
      if (aVal > bVal) return sortOrder === 'asc' ? 1 : -1
      return 0
    })
    return list
  }, [services, sortField, sortOrder])

  const totalServices = sortedServices.length
  const startIndex = totalServices === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1
  const endIndex = Math.min(currentPage * rowsPerPage, totalServices)
  const pagedServices = sortedServices.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage)

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

  const handleDelete = async (service: Service) => {
    if (!window.confirm(`Delete service "${service.name}"?`)) return
    try {
      await servicesApi.remove(service.id)
      toast.success('Service deleted successfully!')
      queryClient.invalidateQueries({ queryKey: ['services'] })
    } catch (err: any) {
      toast.error(err?.response?.data?.message ?? 'Failed to delete service')
    } finally {
      setOpenMenuId(null)
    }
  }

  return (
    <div className="page-content">
      <h1 className="page-title">List Services</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">List Services</h2>
          <div className="flex gap-2">
            <button
              className="btn btn-success"
              onClick={() => setShowFilter(!showFilter)}
            >
              <Filter size={14} />
              FILTER
            </button>
            <button
              className="btn btn-primary"
              onClick={() => navigate('/services/add')}
            >
              +ADD
            </button>
          </div>
        </div>

        {/* Filter Panel */}
        {showFilter && (
          <div className="p-6 border-b bg-white">
            <div className="grid grid-cols-3 gap-4">
              <div className="col-span-3">
                <label className="block text-sm text-gray-600 mb-1">Search</label>
                <input
                  type="text"
                  value={filters.search}
                  onChange={(e) => handleFilterChange('search', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  placeholder=""
                />
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Active Service</label>
                <select
                  value={filters.activeService}
                  onChange={(e) => handleFilterChange('activeService', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="yes">Yes</option>
                  <option value="no">No</option>
                </select>
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Service Group</label>
                <select
                  value={filters.categoryId}
                  onChange={(e) => handleFilterChange('categoryId', e.target.value)}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  {categories.map(category => (
                    <option key={category.id} value={category.id}>{category.name}</option>
                  ))}
                </select>
              </div>
            </div>
            <div className="flex justify-end gap-3 mt-4">
              <button
                onClick={cancelFilters}
                className="text-red-500 hover:text-red-600 font-medium"
              >
                CANCEL
              </button>
              <button
                onClick={applyFilters}
                className="btn btn-primary"
              >
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
            {appliedFilters.activeService && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                Active: {appliedFilters.activeService}
                <button
                  onClick={() => removeFilter('activeService')}
                  className="w-4 h-4 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs"
                >
                  ×
                </button>
              </span>
            )}
            {appliedFilters.categoryId && (
              <span className="inline-flex items-center gap-1 text-sm bg-white px-2 py-1 rounded border">
                Group: {categories.find(c => String(c.id) === appliedFilters.categoryId)?.name ?? appliedFilters.categoryId}
                <button
                  onClick={() => removeFilter('categoryId')}
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
                <th className="cursor-pointer" onClick={() => handleSort('name')}>
                  <span className="flex items-center">
                    Service Name <SortIcon field="name" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('category')}>
                  <span className="flex items-center">
                    Service Group <SortIcon field="category" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('sort_order')}>
                  <span className="flex items-center">
                    Sort Order <SortIcon field="sort_order" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('base_price')}>
                  <span className="flex items-center">
                    Price <SortIcon field="base_price" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('duration')}>
                  <span className="flex items-center">
                    Duration <SortIcon field="duration" />
                  </span>
                </th>
                <th className="cursor-pointer text-center" onClick={() => handleSort('status')}>
                  <span className="flex items-center justify-center">
                    Active <SortIcon field="status" />
                  </span>
                </th>
                <th className="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {isLoading && (
                <tr>
                  <td colSpan={7} className="text-center py-6 text-gray-500">Loading...</td>
                </tr>
              )}
              {!isLoading && pagedServices.length === 0 && (
                <tr>
                  <td colSpan={7} className="text-center py-6 text-gray-500">No services found</td>
                </tr>
              )}
              {pagedServices.map((service) => (
                <tr key={service.id}>
                  <td className="font-medium">{service.name}</td>
                  <td>{service.category?.name ?? ''}</td>
                  <td>{service.sort_order}</td>
                  <td>${service.base_price}</td>
                  <td>{service.duration} mins</td>
                  <td className="text-center">
                    {service.status === 'ACTIVE' ? (
                      <Check size={18} className="inline text-purple-600" />
                    ) : (
                      <X size={18} className="inline text-red-500" />
                    )}
                  </td>
                  <td className="text-center relative">
                    <button
                      className="p-1 hover:bg-gray-100 rounded"
                      onClick={() => setOpenMenuId(openMenuId === service.id ? null : service.id)}
                    >
                      <MoreVertical size={18} className="text-gray-500" />
                    </button>
                    {openMenuId === service.id && (
                      <div className="absolute right-2 top-full z-10 bg-white border border-gray-200 rounded shadow-md w-28">
                        <button
                          className="block w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
                          onClick={() => {
                            setOpenMenuId(null)
                            navigate(`/services/edit/${service.id}`)
                          }}
                        >
                          Edit
                        </button>
                        <button
                          className="block w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-gray-50"
                          onClick={() => handleDelete(service)}
                        >
                          Delete
                        </button>
                      </div>
                    )}
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
          <span className="text-sm text-gray-600">{startIndex} - {endIndex} to {totalServices}</span>
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
              onClick={() => setCurrentPage(p => p + 1)}
              disabled={endIndex >= totalServices}
            >
              <ChevronRight size={18} />
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
