import { useState, useRef, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { Filter, Check, X, ChevronUp, ChevronDown, MoreVertical, Pencil, Trash2 } from 'lucide-react'

interface ForumGroup {
  id: number
  name: string
  description: string
  membersCount: number
  permissions: string
  active: boolean
  createdAt: string
}

const mockGroups: ForumGroup[] = [
  { id: 1, name: 'Administrators', description: 'Full access to all features', membersCount: 5, permissions: 'Full Access', active: true, createdAt: 'January 01 2024, 10:00 am' },
  { id: 2, name: 'Moderators', description: 'Can moderate posts and users', membersCount: 12, permissions: 'Moderate', active: true, createdAt: 'January 05 2024, 02:15 pm' },
  { id: 3, name: 'Members', description: 'Standard member access', membersCount: 456, permissions: 'Read/Write', active: true, createdAt: 'January 10 2024, 11:00 am' },
  { id: 4, name: 'VIP Members', description: 'Premium member benefits', membersCount: 34, permissions: 'Read/Write', active: true, createdAt: 'February 15 2024, 09:45 am' },
  { id: 5, name: 'Guests', description: 'Limited read access', membersCount: 0, permissions: 'Read Only', active: false, createdAt: 'March 01 2024, 03:30 pm' },
]

type SortField = 'id' | 'name' | 'membersCount' | 'permissions' | 'active' | 'createdAt'
type SortOrder = 'asc' | 'desc'

export function ListForumGroups() {
  const navigate = useNavigate()
  const [groups] = useState<ForumGroup[]>(mockGroups)
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
      <span className="inline-flex flex-col ml-1 opacity-50">
        <ChevronUp size={10} className={sortField === field && sortOrder === 'asc' ? 'opacity-100' : 'opacity-40'} />
        <ChevronDown size={10} className={`-mt-1 ${sortField === field && sortOrder === 'desc' ? 'opacity-100' : 'opacity-40'}`} />
      </span>
    )
  }

  const getPermissionBadgeColor = (permission: string) => {
    switch (permission) {
      case 'Full Access': return 'bg-red-100 text-red-700'
      case 'Moderate': return 'bg-orange-100 text-orange-700'
      case 'Read/Write': return 'bg-green-100 text-green-700'
      case 'Read Only': return 'bg-gray-100 text-gray-700'
      default: return 'bg-purple-100 text-purple-700'
    }
  }

  return (
    <div className="page-content">
      <h1 className="page-title">List Groups</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">Forum Groups</h2>
          <div className="flex gap-2">
            <button className="btn btn-success">
              <Filter size={14} />
              FILTER
            </button>
            <button 
              className="btn btn-primary"
              onClick={() => navigate('/forum/add-groups')}
            >
              +ADD
            </button>
          </div>
        </div>
        <div className="card-body">
          <table className="table">
            <thead>
              <tr>
                <th className="cursor-pointer" onClick={() => handleSort('id')}>
                  <span className="flex items-center">
                    ID <SortIcon field="id" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('name')}>
                  <span className="flex items-center">
                    Name <SortIcon field="name" />
                  </span>
                </th>
                <th>Description</th>
                <th className="cursor-pointer" onClick={() => handleSort('membersCount')}>
                  <span className="flex items-center">
                    Members <SortIcon field="membersCount" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('permissions')}>
                  <span className="flex items-center">
                    Permissions <SortIcon field="permissions" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('active')}>
                  <span className="flex items-center">
                    Active <SortIcon field="active" />
                  </span>
                </th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {groups.map((group) => (
                <tr key={group.id}>
                  <td>{group.id}</td>
                  <td className="font-medium">{group.name}</td>
                  <td className="text-gray-500 text-sm">{group.description}</td>
                  <td>{group.membersCount}</td>
                  <td>
                    <span className={`px-2 py-1 rounded text-xs ${getPermissionBadgeColor(group.permissions)}`}>
                      {group.permissions}
                    </span>
                  </td>
                  <td>
                    {group.active ? (
                      <Check size={20} className="icon-check" />
                    ) : (
                      <X size={20} className="icon-cross" />
                    )}
                  </td>
                  <td>
                    <div className="relative" ref={openMenuId === group.id ? menuRef : null}>
                      <button 
                        className="p-2 hover:bg-gray-100 rounded-full transition-colors"
                        onClick={() => setOpenMenuId(openMenuId === group.id ? null : group.id)}
                      >
                        <MoreVertical size={18} />
                      </button>
                      {openMenuId === group.id && (
                        <div className="absolute right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 min-w-[120px]">
                          <button 
                            className="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2"
                            onClick={() => {
                              setOpenMenuId(null)
                              navigate(`/forum/edit-group/${group.id}`)
                            }}
                          >
                            <Pencil size={14} />
                            Edit
                          </button>
                          <button 
                            className="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                            onClick={() => {
                              setOpenMenuId(null)
                              console.log('Delete group:', group.id)
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
      </div>
    </div>
  )
}
