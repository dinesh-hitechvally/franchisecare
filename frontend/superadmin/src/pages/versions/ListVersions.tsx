import { useState, useRef, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { Filter, Check, X, ChevronUp, ChevronDown, MoreVertical, Pencil, Trash2 } from 'lucide-react'

interface Version {
  id: number
  version: string
  title: string
  logout: boolean
  refresh: boolean
  date: string
}

const mockVersions: Version[] = [
  { id: 82, version: '#testVersion', title: 'Test Title', logout: false, refresh: false, date: 'September 20 2024, 04:35 am' },
  { id: 81, version: '2.1.1008', title: 'v2.1.1008', logout: false, refresh: true, date: 'October 25 2021, 09:50 am' },
  { id: 80, version: '2.1.1006', title: 'v2.1.1006', logout: false, refresh: true, date: 'October 21 2021, 03:45 pm' },
  { id: 79, version: '2.1.1005', title: 'v2.1.1005', logout: true, refresh: true, date: 'October 21 2021, 10:05 am' },
  { id: 78, version: '2.1.1004', title: 'v2.1.1004', logout: false, refresh: true, date: 'October 05 2021, 06:29 pm' },
  { id: 77, version: '2.1.1003', title: 'v2.1.1003', logout: false, refresh: true, date: 'October 05 2021, 09:59 am' },
  { id: 76, version: '2.1.1002', title: 'v2.1.1002', logout: false, refresh: true, date: 'October 04 2021, 07:49 pm' },
  { id: 75, version: '2.1.1001', title: 'v2.1.1001', logout: true, refresh: true, date: 'October 04 2021, 05:31 pm' },
]

type SortField = 'id' | 'version' | 'title' | 'logout' | 'refresh' | 'date'
type SortOrder = 'asc' | 'desc'

export function ListVersions() {
  const navigate = useNavigate()
  const [versions] = useState<Version[]>(mockVersions)
  const [sortField, setSortField] = useState<SortField>('id')
  const [sortOrder, setSortOrder] = useState<SortOrder>('desc')
  const [openMenuId, setOpenMenuId] = useState<number | null>(null)
  const menuRef = useRef<HTMLDivElement>(null)

  // Close menu when clicking outside
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
      <h1 className="page-title">List Versions</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">List Versions</h2>
          <div className="flex gap-2">
            <button className="btn btn-success">
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
                <th className="cursor-pointer" onClick={() => handleSort('logout')}>
                  <span className="flex items-center">
                    Logout <SortIcon field="logout" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('refresh')}>
                  <span className="flex items-center">
                    Refresh <SortIcon field="refresh" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('date')}>
                  <span className="flex items-center">
                    Date <SortIcon field="date" />
                  </span>
                </th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {versions.map((version) => (
                <tr key={version.id}>
                  <td>{version.id}</td>
                  <td>{version.version}</td>
                  <td>{version.title}</td>
                  <td>
                    {version.logout ? (
                      <Check size={20} className="icon-check" />
                    ) : (
                      <X size={20} className="icon-cross" />
                    )}
                  </td>
                  <td>
                    {version.refresh ? (
                      <Check size={20} className="icon-check" />
                    ) : (
                      <X size={20} className="icon-cross" />
                    )}
                  </td>
                  <td>{version.date}</td>
                  <td>
                    <div className="relative" ref={openMenuId === version.id ? menuRef : null}>
                      <button 
                        className="p-2 hover:bg-gray-100 rounded-full transition-colors"
                        onClick={() => setOpenMenuId(openMenuId === version.id ? null : version.id)}
                      >
                        <MoreVertical size={18} />
                      </button>
                      {openMenuId === version.id && (
                        <div className="absolute right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 min-w-[120px]">
                          <button 
                            className="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2"
                            onClick={() => {
                              setOpenMenuId(null)
                              navigate(`/versions/edit/${version.id}`)
                            }}
                          >
                            <Pencil size={14} />
                            Edit
                          </button>
                          <button 
                            className="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                            onClick={() => {
                              setOpenMenuId(null)
                              // Handle delete action
                              console.log('Delete version:', version.id)
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
