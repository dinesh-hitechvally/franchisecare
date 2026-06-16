import { useState, useRef, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { Filter, Check, X, ChevronUp, ChevronDown, MoreVertical, Pencil, Trash2, Eye } from 'lucide-react'

interface Post {
  id: number
  title: string
  author: string
  category: string
  views: number
  replies: number
  active: boolean
  createdAt: string
}

const mockPosts: Post[] = [
  { id: 1, title: 'Welcome to our community!', author: 'Admin', category: 'Announcements', views: 1250, replies: 45, active: true, createdAt: 'January 15 2024, 10:30 am' },
  { id: 2, title: 'Best practices for pet grooming', author: 'Dave Laming', category: 'Tips & Tricks', views: 856, replies: 23, active: true, createdAt: 'January 18 2024, 02:15 pm' },
  { id: 3, title: 'How to handle difficult pets', author: 'John Smith', category: 'Support', views: 432, replies: 18, active: true, createdAt: 'February 05 2024, 11:00 am' },
  { id: 4, title: 'New features coming soon', author: 'Admin', category: 'Announcements', views: 678, replies: 12, active: true, createdAt: 'February 10 2024, 09:45 am' },
  { id: 5, title: 'Share your success stories', author: 'Sarah Wilson', category: 'General Discussion', views: 345, replies: 34, active: false, createdAt: 'March 01 2024, 03:30 pm' },
]

type SortField = 'id' | 'title' | 'author' | 'views' | 'replies' | 'active' | 'createdAt'
type SortOrder = 'asc' | 'desc'

export function ListPosts() {
  const navigate = useNavigate()
  const [posts] = useState<Post[]>(mockPosts)
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
      <h1 className="page-title">List Posts</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">Forum Posts</h2>
          <div className="flex gap-2">
            <button className="btn btn-success">
              <Filter size={14} />
              FILTER
            </button>
            <button 
              className="btn btn-primary"
              onClick={() => navigate('/forum/add-posts')}
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
                <th className="cursor-pointer" onClick={() => handleSort('title')}>
                  <span className="flex items-center">
                    Title <SortIcon field="title" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('author')}>
                  <span className="flex items-center">
                    Author <SortIcon field="author" />
                  </span>
                </th>
                <th>Category</th>
                <th className="cursor-pointer" onClick={() => handleSort('views')}>
                  <span className="flex items-center">
                    Views <SortIcon field="views" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('replies')}>
                  <span className="flex items-center">
                    Replies <SortIcon field="replies" />
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
              {posts.map((post) => (
                <tr key={post.id}>
                  <td>{post.id}</td>
                  <td className="font-medium">{post.title}</td>
                  <td>{post.author}</td>
                  <td><span className="px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs">{post.category}</span></td>
                  <td>{post.views}</td>
                  <td>{post.replies}</td>
                  <td>
                    {post.active ? (
                      <Check size={20} className="icon-check" />
                    ) : (
                      <X size={20} className="icon-cross" />
                    )}
                  </td>
                  <td>
                    <div className="relative" ref={openMenuId === post.id ? menuRef : null}>
                      <button 
                        className="p-2 hover:bg-gray-100 rounded-full transition-colors"
                        onClick={() => setOpenMenuId(openMenuId === post.id ? null : post.id)}
                      >
                        <MoreVertical size={18} />
                      </button>
                      {openMenuId === post.id && (
                        <div className="absolute right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 min-w-[120px]">
                          <button 
                            className="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2"
                            onClick={() => {
                              setOpenMenuId(null)
                              navigate(`/forum/view-post/${post.id}`)
                            }}
                          >
                            <Eye size={14} />
                            View
                          </button>
                          <button 
                            className="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2"
                            onClick={() => {
                              setOpenMenuId(null)
                              navigate(`/forum/edit-post/${post.id}`)
                            }}
                          >
                            <Pencil size={14} />
                            Edit
                          </button>
                          <button 
                            className="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                            onClick={() => {
                              setOpenMenuId(null)
                              console.log('Delete post:', post.id)
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
