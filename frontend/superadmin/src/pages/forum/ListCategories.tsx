import { useState, useRef, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { Filter, Check, X, ChevronUp, ChevronDown, MoreVertical, Pencil, Trash2 } from 'lucide-react'

interface Category {
  id: number
  name: string
  description: string
  postsCount: number
  active: boolean
  createdAt: string
}

const mockCategories: Category[] = [
  { id: 1, name: 'General Discussion', description: 'General topics and discussions', postsCount: 145, active: true, createdAt: 'January 15 2024, 10:30 am' },
  { id: 2, name: 'Announcements', description: 'Official announcements and updates', postsCount: 32, active: true, createdAt: 'January 10 2024, 09:00 am' },
  { id: 3, name: 'Tips & Tricks', description: 'Share your best practices', postsCount: 89, active: true, createdAt: 'February 05 2024, 02:15 pm' },
  { id: 4, name: 'Support', description: 'Get help from the community', postsCount: 256, active: true, createdAt: 'January 20 2024, 11:45 am' },
  { id: 5, name: 'Feedback', description: 'Share your feedback and suggestions', postsCount: 67, active: false, createdAt: 'March 01 2024, 03:30 pm' },
]

type SortField = 'id' | 'name' | 'postsCount' | 'active' | 'createdAt'
type SortOrder = 'asc' | 'desc'

export function ListCategories() {
  const navigate = useNavigate()
  const [categories] = useState<Category[]>(mockCategories)
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

  return (
    <div className="page-content">
      <h1 className="page-title">List Categories</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">Forum Categories</h2>
          <div className="flex gap-2">
            <button className="btn btn-success">
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
                <th className="cursor-pointer" onClick={() => handleSort('postsCount')}>
                  <span className="flex items-center">
                    Posts <SortIcon field="postsCount" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('active')}>
                  <span className="flex items-center">
                    Active <SortIcon field="active" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('createdAt')}>
                  <span className="flex items-center">
                    Created <SortIcon field="createdAt" />
                  </span>
                </th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {categories.map((category) => (
                <tr key={category.id}>
                  <td>{category.id}</td>
                  <td className="font-medium">{category.name}</td>
                  <td className="text-gray-500 text-sm">{category.description}</td>
                  <td>{category.postsCount}</td>
                  <td>
                    {category.active ? (
                      <Check size={20} className="icon-check" />
                    ) : (
                      <X size={20} className="icon-cross" />
                    )}
                  </td>
                  <td>{category.createdAt}</td>
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
                              console.log('Delete category:', category.id)
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
