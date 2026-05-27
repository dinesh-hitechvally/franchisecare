import { useState } from 'react'
import { Filter, Plus, Check, X, ChevronUp, ChevronDown } from 'lucide-react'

interface SuspendedMember {
  id: number
  name: string
  companyName: string
  serviceLocation: string
  type: 'Master Franchisee' | 'Franchisee' | 'Franchisor'
  lastActive: string
  ipad: boolean
  memberActive: boolean
  tscsAccepted: boolean
}

const mockSuspendedMembers: SuspendedMember[] = [
  { id: 1, name: 'Rob James', companyName: 'Blue Wheelers Upper Coomera', serviceLocation: 'Upper Coomera', type: 'Franchisee', lastActive: 'September 23 2024, 01:50 pm', ipad: true, memberActive: true, tscsAccepted: false },
  { id: 2, name: 'Martin Rose', companyName: 'Blue Wheelers Australia', serviceLocation: 'National Support', type: 'Franchisor', lastActive: 'August 07 2024, 04:59 pm', ipad: true, memberActive: true, tscsAccepted: false },
  { id: 3, name: 'Jason Gordon', companyName: 'Blue Wheelers Seaford', serviceLocation: 'Seaford', type: 'Franchisee', lastActive: 'September 27 2022, 10:24 am', ipad: true, memberActive: true, tscsAccepted: false },
  { id: 4, name: 'Kristy McHenry', companyName: 'Blue Wheelers Ormeau', serviceLocation: 'Ormeau', type: 'Franchisee', lastActive: 'September 27 2022, 07:37 am', ipad: true, memberActive: true, tscsAccepted: false },
  { id: 5, name: 'Paula Third', companyName: 'Dash DogWash Wallsend', serviceLocation: 'Wallsend', type: 'Franchisee', lastActive: 'September 27 2022, 06:47 am', ipad: true, memberActive: true, tscsAccepted: false },
  { id: 6, name: 'Rachel Pullen', companyName: 'Dash DogWash Lismore', serviceLocation: 'lismore', type: 'Franchisee', lastActive: 'September 26 2022, 06:58 pm', ipad: true, memberActive: true, tscsAccepted: false },
  { id: 7, name: 'Donna Timms', companyName: 'Blue Wheelers Thornlie', serviceLocation: 'Thornlie', type: 'Franchisee', lastActive: 'September 26 2022, 06:22 pm', ipad: true, memberActive: true, tscsAccepted: false },
  { id: 8, name: 'Toni Read', companyName: 'Blue Wheelers Jindalee', serviceLocation: 'Jindalee', type: 'Franchisee', lastActive: 'September 26 2022, 05:40 pm', ipad: true, memberActive: true, tscsAccepted: false },
  { id: 9, name: 'Julie & Kris Farr', companyName: 'Blue Wheelers Parkdale', serviceLocation: 'Parkdale', type: 'Franchisee', lastActive: 'September 26 2022, 10:38 am', ipad: true, memberActive: true, tscsAccepted: false },
  { id: 10, name: 'Kym Baker', companyName: 'Blue Wheelers Berwick', serviceLocation: 'Berwick', type: 'Franchisee', lastActive: 'September 20 2022, 06:43 pm', ipad: true, memberActive: true, tscsAccepted: false },
  { id: 11, name: 'Mate S', companyName: 'Bluewheelers', serviceLocation: '', type: 'Master Franchisee', lastActive: '-', ipad: true, memberActive: true, tscsAccepted: false },
  { id: 12, name: 'Melinda Thorpe', companyName: 'Dash DogWash Golden Beach', serviceLocation: 'Golden Beach', type: 'Franchisee', lastActive: '-', ipad: true, memberActive: true, tscsAccepted: false },
  { id: 13, name: 'Tim Rule', companyName: 'Blue Wheelers Ringwood North', serviceLocation: 'Ringwood North', type: 'Franchisee', lastActive: '-', ipad: true, memberActive: true, tscsAccepted: false },
  { id: 14, name: 'Esther Noah', companyName: 'Blue Wheelers Bentleigh East', serviceLocation: 'Bentleigh East', type: 'Franchisee', lastActive: '-', ipad: true, memberActive: true, tscsAccepted: false },
  { id: 15, name: 'Mate Admin', companyName: 'Bluewheelers Admin', serviceLocation: '', type: 'Master Franchisee', lastActive: '-', ipad: true, memberActive: true, tscsAccepted: false },
  { id: 16, name: 'Paul Deane', companyName: 'Blue Wheelers Dapto', serviceLocation: 'Dapto', type: 'Franchisee', lastActive: '-', ipad: true, memberActive: true, tscsAccepted: false },
  { id: 17, name: 'Margie Stanton', companyName: 'Blue Wheelers Port Sorell', serviceLocation: 'Port Sorell', type: 'Franchisee', lastActive: '-', ipad: true, memberActive: true, tscsAccepted: false },
  { id: 18, name: 'Steven Kirk', companyName: 'Savannah Cox', serviceLocation: 'Quod est ipsa adipi', type: 'Master Franchisee', lastActive: '-', ipad: true, memberActive: true, tscsAccepted: false },
]

type SortField = 'name' | 'companyName' | 'serviceLocation' | 'type' | 'lastActive' | 'ipad' | 'memberActive' | 'tscsAccepted'
type SortOrder = 'asc' | 'desc'

export function SuspendedLeadMembers() {
  const [members] = useState<SuspendedMember[]>(mockSuspendedMembers)
  const [sortField, setSortField] = useState<SortField>('name')
  const [sortOrder, setSortOrder] = useState<SortOrder>('asc')
  const [rowsPerPage, setRowsPerPage] = useState(20)

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

  return (
    <div className="page-content">
      <h1 className="page-title">Suspended Lead Members</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">Suspended Lead Members</h2>
          <div className="flex gap-2">
            <button className="btn btn-success">
              <Filter size={14} />
              FILTER
            </button>
            <button className="btn btn-primary">
              <Plus size={14} />
              EXPORT
            </button>
          </div>
        </div>

        <div className="card-body p-0">
          <table className="table">
            <thead>
              <tr>
                <th className="cursor-pointer" onClick={() => handleSort('name')}>
                  <span className="flex items-center">
                    Name <SortIcon field="name" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('companyName')}>
                  <span className="flex items-center">
                    Company Name <SortIcon field="companyName" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('serviceLocation')}>
                  <span className="flex items-center">
                    Service Location <SortIcon field="serviceLocation" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('type')}>
                  <span className="flex items-center">
                    Type <SortIcon field="type" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('lastActive')}>
                  <span className="flex items-center">
                    Last Active <SortIcon field="lastActive" />
                  </span>
                </th>
                <th className="cursor-pointer text-center" onClick={() => handleSort('ipad')}>
                  <span className="flex items-center justify-center">
                    iPAD <SortIcon field="ipad" />
                  </span>
                </th>
                <th className="cursor-pointer text-center" onClick={() => handleSort('memberActive')}>
                  <span className="flex items-center justify-center">
                    Member Active <SortIcon field="memberActive" />
                  </span>
                </th>
                <th className="cursor-pointer text-center" onClick={() => handleSort('tscsAccepted')}>
                  <span className="flex items-center justify-center">
                    Ts & Cs Accepted <SortIcon field="tscsAccepted" />
                  </span>
                </th>
              </tr>
            </thead>
            <tbody>
              {members.map((member) => (
                <tr key={member.id}>
                  <td className="font-medium">{member.name}</td>
                  <td>{member.companyName}</td>
                  <td>{member.serviceLocation}</td>
                  <td>{member.type}</td>
                  <td className="whitespace-nowrap">{member.lastActive}</td>
                  <td className="text-center">
                    {member.ipad ? (
                      <Check size={18} className="inline text-purple-600" />
                    ) : (
                      <X size={18} className="inline text-red-500" />
                    )}
                  </td>
                  <td className="text-center">
                    {member.memberActive ? (
                      <Check size={18} className="inline text-purple-600" />
                    ) : (
                      <X size={18} className="inline text-red-500" />
                    )}
                  </td>
                  <td className="text-center">
                    {member.tscsAccepted ? (
                      <Check size={18} className="inline text-purple-600" />
                    ) : (
                      <X size={18} className="inline text-red-500" />
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
              onChange={(e) => setRowsPerPage(Number(e.target.value))}
              className="border-none bg-transparent"
            >
              <option value={10}>10</option>
              <option value={20}>20</option>
              <option value={50}>50</option>
              <option value={100}>100</option>
            </select>
          </div>
          <span className="text-sm text-gray-600">1 - {members.length} to {members.length}</span>
          <div className="flex gap-1">
            <button className="p-1 text-gray-400 hover:text-gray-600">
              <ChevronUp size={18} className="rotate-[-90deg]" />
            </button>
            <button className="p-1 text-gray-400 hover:text-gray-600">
              <ChevronUp size={18} className="rotate-90" />
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
