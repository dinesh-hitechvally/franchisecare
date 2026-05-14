import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { customersApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import { Search, Plus, Menu, Star, MoreVertical, Loader2, Archive, Edit3, History } from 'lucide-react'
import type { Customer } from '../../types'
import { CustomerAuditModal } from '../../components/modals/CustomerAuditModal'
import { PortalMenu } from '../../components/ui/PortalMenu'

export function ListCustomersPage() {
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)
  const [searchTerm, setSearchTerm] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)
  const [auditCustomer, setAuditCustomer] = useState<Customer | null>(null)

  const { data: customers, isLoading, isError } = useQuery({
    queryKey: ['customers', searchTerm, statusFilter],
    queryFn: () => customersApi.getAll({ 
      search: searchTerm, 
      status: statusFilter === 'all' ? undefined : statusFilter 
    }),
  })

  const archiveMutation = useMutation({
    mutationFn: (id: string) => customersApi.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customers'] })
      addToast('Customer archived successfully', 'success')
      setOpenMenuId(null)
      setMenuPos(null)
    },
    onError: () => {
      addToast('Failed to archive customer', 'error')
    }
  })

  const getInitials = (firstName: string, lastName: string) => {
    return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase()
  }

  if (isError) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="text-center">
          <p className="text-red-600 font-medium">Failed to load customers</p>
          <Button 
            variant="ghost" 
            onClick={() => queryClient.invalidateQueries({ queryKey: ['customers'] })}
            className="mt-2"
          >
            Try Again
          </Button>
        </div>
      </div>
    )
  }

  return (
    <div className="flex flex-col lg:flex-row gap-8 min-h-screen">
      {/* Sidebar */}
      <div className="w-full lg:w-64 shrink-0 space-y-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">
            {customers?.length ?? 0} Customers
          </h1>
          <p className="text-sm text-gray-500 mt-1">
            {statusFilter === 'archived' ? 'Showing archived' : 'Active customers'}
          </p>
        </div>
        
        <Link to="/customers/add" className="block">
          <Button className="w-full justify-center bg-indigo-600 hover:bg-indigo-700 text-white gap-2 shadow-sm rounded-md py-2.5">
            <Plus className="w-5 h-5" />
            Add Customer
          </Button>
        </Link>
        
        <nav className="space-y-1 mt-4">
          <button 
            onClick={() => setStatusFilter('all')}
            className={`w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-md transition-colors ${
              statusFilter === 'all' ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:bg-gray-50'
            }`}
          >
            <Menu className="w-5 h-5 text-gray-500" />
            All Customers
          </button>
          <Link 
            to="/customers/archived"
            className="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 rounded-md"
          >
            <Archive className="w-5 h-5 text-gray-400" />
            Archived Customers
          </Link>
        </nav>
      </div>

      {/* Main Content */}
      <div className="flex-1 space-y-6 min-w-0">
        <div className="flex flex-col sm:flex-row gap-4 items-end justify-between">
          <div className="relative w-full max-w-md">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
            <Input 
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              placeholder="Search Customer"
              className="pl-10 w-full"
            />
          </div>
          <div className="flex flex-col">
            <label className="text-xs text-gray-500 mb-1">Customer Status</label>
            <select 
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 outline-none w-36 bg-white shadow-sm"
            >
              <option value="all">All</option>
              <option value="active">Active</option>
              <option value="archived">Archived</option>
            </select>
          </div>
        </div>

        <Card className="overflow-hidden border border-gray-200 shadow-sm rounded-lg">
          {isLoading ? (
            <div className="flex items-center justify-center p-12">
              <Loader2 className="w-8 h-8 text-indigo-600 animate-spin" />
            </div>
          ) : customers?.length === 0 ? (
            <div className="p-12 text-center text-gray-500">
              No customers found.
            </div>
          ) : (
            <div className="divide-y divide-gray-100">
              {customers?.map((c: Customer) => (
                <div key={c.id} className="flex flex-col sm:flex-row sm:items-center p-4 hover:bg-gray-50 transition-colors gap-4 relative group">
                  <div className="flex items-center gap-4 shrink-0">
                    <input type="checkbox" className="w-5 h-5 text-blue-600 rounded border-gray-300 cursor-pointer focus:ring-blue-500" />
                    <button className="text-gray-300 hover:text-yellow-400 focus:outline-none cursor-pointer">
                      <Star className="w-6 h-6" />
                    </button>
                    <div className="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-sm font-medium text-indigo-700 shrink-0 capitalize">
                      {getInitials(c.first_name, c.last_name)}
                    </div>
                  </div>
                  
                  <div className="flex-1 flex flex-wrap items-center gap-y-1 gap-x-2 text-[15px] text-gray-600 px-2 sm:px-0">
                    <span className="font-bold text-gray-900 capitalize">{c.first_name} {c.last_name}</span>
                    <span className="text-gray-300 hidden sm:inline">|</span>
                    <span className="truncate max-w-[200px] xl:max-w-xs block sm:inline">{c.address || 'No address'}</span>
                    
                    {(c.email || c.phone) && <span className="text-gray-300 hidden sm:inline">|</span>}
                    
                    <div className="flex items-center gap-2 text-blue-600 w-full sm:w-auto">
                      {c.email && <a href={`mailto:${c.email}`} className="hover:underline truncate max-w-[150px]">{c.email}</a>}
                      {c.email && c.phone && <span className="text-gray-300 hidden sm:inline">|</span>}
                      {c.phone && (
                        <div className="flex items-center gap-2">
                          <a href={`tel:${c.phone}`} className="hover:underline tracking-wide">{c.phone}</a>
                          {c.pets && c.pets.filter(p => p.isActive).length > 0 && (
                            <span className="text-gray-500 text-xs font-medium">
                              <span className="text-gray-300 mr-2">|</span>
                              [ {c.pets.filter(p => p.isActive).map(p => p.name).join(', ')} ]
                            </span>
                          )}
                        </div>
                      )}
                    </div>
                  </div>
                  
                  <div className="absolute right-4 sm:relative sm:right-0 shrink-0">
                    <button 
                      onClick={(e) => {
                        e.stopPropagation()
                        if (openMenuId === c.id) {
                          setOpenMenuId(null)
                          setMenuPos(null)
                        } else {
                          const rect = (e.currentTarget as HTMLButtonElement).getBoundingClientRect()
                          setMenuPos({ top: rect.bottom + 4, left: rect.right - 192 })
                          setOpenMenuId(c.id)
                        }
                      }}
                      className="text-gray-400 hover:text-gray-600 p-2 focus:outline-none rounded-full hover:bg-gray-100 transition-colors"
                    >
                      <MoreVertical className="w-5 h-5" />
                    </button>

                    <PortalMenu
                      isOpen={openMenuId === c.id}
                      onClose={() => { setOpenMenuId(null); setMenuPos(null) }}
                      position={menuPos}
                      width={192}
                    >
                        <Link 
                          to={`/customers/edit/${c.id}`}
                          className="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                        >
                          <Edit3 className="w-4 h-4 text-gray-400" />
                          View/Edit
                        </Link>
                        <button 
                          onClick={() => {
                            setAuditCustomer(c)
                            setOpenMenuId(null)
                          }}
                          className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                        >
                          <History className="w-4 h-4 text-gray-400" />
                          Audit trail
                        </button>
                        <button 
                          onClick={() => archiveMutation.mutate(c.id)}
                          className="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                        >
                          <Archive className="w-4 h-4 text-red-400" />
                          Archive
                        </button>
                    </PortalMenu>
                  </div>
                </div>
              ))}
            </div>
          )}
        </Card>
      </div>

      <CustomerAuditModal 
        isOpen={!!auditCustomer}
        onClose={() => setAuditCustomer(null)}
        customer={auditCustomer}
      />
    </div>
  )
}
