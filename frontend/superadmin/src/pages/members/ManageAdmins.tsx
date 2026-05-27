import { useState } from 'react'
import { Filter, Check, X, ChevronUp, ChevronDown } from 'lucide-react'

interface AdminMember {
  id: number
  name: string
  companyName: string
  serviceLocation: string
  type: 'Master Franchisee' | 'Franchisee' | 'Franchisor'
  lastActive: string
  ipad: boolean
  memberActive: boolean
}

const mockAdminMembers: AdminMember[] = [
  { id: 1, name: 'Mate Support', companyName: 'RetailCare Pty Ltd', serviceLocation: 'Jindalee, Mt Ommaney, Riverhills, Westlake, Middle Park & Jamboree Heights', type: 'Master Franchisee', lastActive: 'May 27 2026, 06:38 pm', ipad: true, memberActive: true },
  { id: 2, name: 'Sharon Watt', companyName: 'NSW Support', serviceLocation: 'NSW Support', type: 'Franchisor', lastActive: 'May 23 2026, 05:05 pm', ipad: true, memberActive: true },
  { id: 3, name: 'Dave Laming', companyName: 'Blue Wheelers Mate Support', serviceLocation: 'Mate Support', type: 'Master Franchisee', lastActive: 'March 25 2026, 12:53 pm', ipad: true, memberActive: true },
  { id: 4, name: 'Lawa Joshi', companyName: 'Lawa', serviceLocation: 'Melbourne', type: 'Franchisee', lastActive: 'September 22 2025, 05:11 pm', ipad: true, memberActive: true },
  { id: 5, name: 'May Wilson', companyName: 'Victoria Support', serviceLocation: 'Victoria Support', type: 'Master Franchisee', lastActive: 'July 23 2025, 02:32 pm', ipad: true, memberActive: true },
  { id: 6, name: 'Kellie Tunstall', companyName: 'Blue Wheelers South Yarra', serviceLocation: 'National Support', type: 'Franchisor', lastActive: 'July 21 2025, 02:14 pm', ipad: true, memberActive: true },
  { id: 7, name: 'Tallulah Sellers', companyName: 'Hiram Bryant', serviceLocation: 'Distinctio Eiusmod', type: 'Franchisee', lastActive: 'February 25 2025, 03:01 pm', ipad: true, memberActive: true },
  { id: 8, name: 'Dahlia Mcgee', companyName: 'Quinn Morris', serviceLocation: 'Ut aliquid quia at c', type: 'Franchisee', lastActive: 'February 25 2025, 03:00 pm', ipad: true, memberActive: true },
  { id: 9, name: 'May Wilson', companyName: 'Blue Wheelers Operations', serviceLocation: 'Operations', type: 'Master Franchisee', lastActive: 'April 13 2023, 04:23 pm', ipad: true, memberActive: true },
  { id: 10, name: 'Serena - Marketing Support', companyName: 'Blue Wheelers MARKETING', serviceLocation: 'Melbourne', type: 'Master Franchisee', lastActive: 'September 27 2022, 01:05 pm', ipad: true, memberActive: true },
  { id: 11, name: 'Col Burrow', companyName: 'Blue Wheelers Franchise', serviceLocation: 'Franchise', type: 'Master Franchisee', lastActive: 'September 27 2022, 11:10 am', ipad: true, memberActive: true },
  { id: 12, name: 'Lynda Olsen', companyName: 'Blue Wheelers Operations', serviceLocation: 'National Operations', type: 'Franchisor', lastActive: 'September 19 2022, 04:49 pm', ipad: true, memberActive: true },
  { id: 13, name: 'Bek Collins', companyName: 'Queensland Support', serviceLocation: 'Queensland Support', type: 'Master Franchisee', lastActive: 'September 19 2022, 12:24 pm', ipad: true, memberActive: true },
  { id: 14, name: 'Lexi Bowles', companyName: 'Blue Wheelers Office', serviceLocation: '(Australia)', type: 'Master Franchisee', lastActive: 'September 19 2022, 09:01 am', ipad: true, memberActive: true },
  { id: 15, name: 'Rehanna Halfyard', companyName: 'SA Support', serviceLocation: 'South Australia Support', type: 'Master Franchisee', lastActive: 'June 01 2022, 04:30 pm', ipad: true, memberActive: true },
  { id: 16, name: 'Natalie Gregson', companyName: 'Blue Wheelers National Support Office Manager', serviceLocation: '', type: 'Franchisee', lastActive: 'February 18 2020, 04:01 pm', ipad: true, memberActive: false },
  { id: 17, name: 'Scott Hoffman', companyName: 'Blue Wheelers WA', serviceLocation: 'Western Australia', type: 'Master Franchisee', lastActive: 'May 31 2018, 04:44 pm', ipad: false, memberActive: false },
  { id: 18, name: 'Sian Gildon', companyName: 'NSW Support', serviceLocation: 'NSW Support', type: 'Master Franchisee', lastActive: 'December 19 2016, 05:46 pm', ipad: false, memberActive: false },
  { id: 19, name: 'Janine Hoffman', companyName: 'Blue Wheelers WA', serviceLocation: 'Western Australia', type: 'Master Franchisee', lastActive: 'September 07 2016, 01:38 pm', ipad: false, memberActive: false },
  { id: 20, name: 'Mark Phenna', companyName: 'WA Support Manager', serviceLocation: 'WA', type: 'Master Franchisee', lastActive: '-', ipad: true, memberActive: true },
  { id: 21, name: 'Lynda Olsen', companyName: 'Support Office', serviceLocation: "Don't use this South Yarra", type: 'Franchisor', lastActive: '-', ipad: true, memberActive: true },
]

type SortField = 'name' | 'companyName' | 'serviceLocation' | 'type' | 'lastActive' | 'ipad' | 'memberActive'
type SortOrder = 'asc' | 'desc'

export function ManageAdmins() {
  const [members] = useState<AdminMember[]>(mockAdminMembers)
  const [sortField, setSortField] = useState<SortField>('name')
  const [sortOrder, setSortOrder] = useState<SortOrder>('asc')
  const [rowsPerPage, setRowsPerPage] = useState(100)

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
      <h1 className="page-title">Manage Admins</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">List Admin Members</h2>
          <div className="flex gap-2">
            <button className="btn btn-success">
              <Filter size={14} />
              FILTER
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
