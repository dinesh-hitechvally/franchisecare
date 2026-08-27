import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { Search, Pencil, Trash2, ChevronLeft, ChevronRight } from 'lucide-react'
import { adminUsersApi } from '../../api/services'
import type { AdminUser } from '../../types'
import { useAuthStore } from '../../store/authStore'

const badgePillClass = 'px-2.5 py-0.5 rounded-full text-[11px] font-semibold capitalize'

const roleBadgeClass: Record<AdminUser['role'], string> = {
  admin: 'bg-purple-100 text-purple-700',
  support: 'bg-blue-100 text-blue-700',
  viewer: 'bg-gray-100 text-gray-700',
}

const statusBadgeClass: Record<AdminUser['status'], string> = {
  active: 'bg-green-100 text-green-700',
  inactive: 'bg-red-100 text-red-600',
}

function formatDate(value: string) {
  if (!value) return '-'
  const date = new Date(value)
  if (isNaN(date.getTime())) return value
  return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}

export function ListAdminUsers() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const currentUser = useAuthStore((s) => s.user)

  const [searchInput, setSearchInput] = useState('')
  const [search, setSearch] = useState('')
  const [role, setRole] = useState('')
  const [status, setStatus] = useState('')
  const [perPage, setPerPage] = useState(10)
  const [page, setPage] = useState(1)

  // Debounce the search box before it hits the query key
  useEffect(() => {
    const timer = setTimeout(() => {
      setSearch(searchInput)
      setPage(1)
    }, 400)
    return () => clearTimeout(timer)
  }, [searchInput])

  const { data, isLoading, isFetching } = useQuery({
    queryKey: ['admin-users', { search, role, status, page, perPage }],
    queryFn: () =>
      adminUsersApi.list({
        search: search || undefined,
        role: role || undefined,
        status: status || undefined,
        page,
        per_page: perPage,
      }),
    placeholderData: keepPreviousData,
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => adminUsersApi.remove(id),
    onSuccess: () => {
      toast.success('Admin user deleted successfully')
      queryClient.invalidateQueries({ queryKey: ['admin-users'] })
    },
    onError: (error: any) => {
      toast.error(error?.response?.data?.message || 'Failed to delete admin user')
    },
  })

  const handleDelete = (user: AdminUser) => {
    if (!window.confirm(`Delete admin user "${user.name}"? This action cannot be undone.`)) return
    deleteMutation.mutate(user.id)
  }

  const handleFilterChange = (setter: (v: string) => void) => (value: string) => {
    setter(value)
    setPage(1)
  }

  const users = data?.data ?? []
  const currentPage = data?.current_page ?? 1
  const lastPage = data?.last_page ?? 1
  const total = data?.total ?? 0
  const startIndex = total === 0 ? 0 : (currentPage - 1) * perPage + 1
  const endIndex = Math.min(currentPage * perPage, total)

  return (
    <div className="page-content">
      <h1 className="page-title">Admin Users</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">List Admin Users</h2>
          <button className="btn btn-primary" onClick={() => navigate('/admin-users/add')}>
            +ADD
          </button>
        </div>

        {/* Filters */}
        <div className="p-6 border-b bg-white grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="relative md:col-span-2">
            <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              value={searchInput}
              onChange={(e) => setSearchInput(e.target.value)}
              placeholder="Search by name or email..."
              className="form-input pl-9"
            />
          </div>
          <div>
            <select
              value={role}
              onChange={(e) => handleFilterChange(setRole)(e.target.value)}
              className="form-input"
            >
              <option value="">All Roles</option>
              <option value="admin">Admin</option>
              <option value="support">Support</option>
              <option value="viewer">Viewer</option>
            </select>
          </div>
          <div>
            <select
              value={status}
              onChange={(e) => handleFilterChange(setStatus)(e.target.value)}
              className="form-input"
            >
              <option value="">All Statuses</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>

        <div className="card-body p-0">
          <table className="table">
            <thead>
              <tr>
                <th></th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Status</th>
                <th>Created At</th>
                <th className="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {isLoading && (
                <tr>
                  <td colSpan={8} className="text-center text-gray-500 py-8">
                    Loading admin users...
                  </td>
                </tr>
              )}
              {!isLoading && users.length === 0 && (
                <tr>
                  <td colSpan={8} className="text-center text-gray-500 py-8">
                    No admin users found.
                  </td>
                </tr>
              )}
              {!isLoading &&
                users.map((user) => (
                  <tr key={user.id}>
                    <td>
                      {user.avatar ? (
                        <img src={user.avatar} alt={user.name} className="w-9 h-9 rounded-full object-cover" />
                      ) : (
                        <div className="w-9 h-9 rounded-full bg-purple-600 text-white flex items-center justify-center font-semibold text-sm">
                          {user.name?.charAt(0)?.toUpperCase() || '?'}
                        </div>
                      )}
                    </td>
                    <td className="font-medium">{user.name}</td>
                    <td>{user.email}</td>
                    <td>{user.phone || '-'}</td>
                    <td>
                      <span className={`${badgePillClass} ${roleBadgeClass[user.role]}`}>
                        {user.role}
                      </span>
                    </td>
                    <td>
                      <span className={`${badgePillClass} ${statusBadgeClass[user.status]}`}>
                        {user.status}
                      </span>
                    </td>
                    <td>{formatDate(user.created_at)}</td>
                    <td className="text-center">
                      <div className="flex items-center justify-center gap-2">
                        <button
                          className="p-1.5 hover:bg-gray-100 rounded text-gray-500 hover:text-purple-600"
                          title="Edit"
                          onClick={() => navigate(`/admin-users/edit/${user.id}`)}
                        >
                          <Pencil size={16} />
                        </button>
                        <button
                          className="p-1.5 hover:bg-gray-100 rounded text-gray-500 hover:text-red-600 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-transparent"
                          title={currentUser?.id === user.id ? "You can't delete your own account" : 'Delete'}
                          disabled={currentUser?.id === user.id || deleteMutation.isPending}
                          onClick={() => handleDelete(user)}
                        >
                          <Trash2 size={16} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
            </tbody>
          </table>
        </div>

        <div className="card-footer flex items-center justify-end gap-4 py-3 px-6">
          {isFetching && !isLoading && <span className="text-xs text-gray-400 mr-auto">Refreshing...</span>}
          <div className="flex items-center gap-2 text-sm text-gray-600">
            <span>Rows per page:</span>
            <select
              value={perPage}
              onChange={(e) => {
                setPerPage(Number(e.target.value))
                setPage(1)
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
            {total === 0 ? '0 - 0' : `${startIndex} - ${endIndex}`} of {total}
          </span>
          <div className="flex gap-1">
            <button
              className="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              disabled={currentPage <= 1}
            >
              <ChevronLeft size={18} />
            </button>
            <button
              className="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
              onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
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
