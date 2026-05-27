import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Filter, Plus, Check, X, ChevronUp, ChevronDown } from 'lucide-react'

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
                    <div className="flex gap-2">
                      <button className="btn btn-outline">EDIT</button>
                      <button className="btn btn-danger">DELETE</button>
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
