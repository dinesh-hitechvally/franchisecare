import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Filter, Check, X, ChevronUp, ChevronDown } from 'lucide-react'
import { franchisesApi } from '../../api/services'
import type { Franchise } from '../../types'

type SortField = 'owner_name' | 'name' | 'territory' | 'franchisee_type' | 'has_ipad' | 'status'
type SortOrder = 'asc' | 'desc'

const typeLabels: Record<string, string> = {
  MASTER_FRANCHISEE: 'Master Franchisee',
  FRANCHISEE: 'Franchisee',
  FRANCHISOR: 'Franchisor',
}

function formatType(type: Franchise['franchisee_type']) {
  return type ? typeLabels[type] ?? type : '-'
}

export function SuspendedLeadMembers() {
  const [sortField, setSortField] = useState<SortField>('owner_name')
  const [sortOrder, setSortOrder] = useState<SortOrder>('asc')

  // This view is the "needs attention" queue of franchises that haven't accepted the T&Cs yet.
  // There's no server-side filter for tscs_accepted, so we fetch a large page and filter client-side.
  const { data, isLoading, isError } = useQuery({
    queryKey: ['franchises', 'suspended-leads'],
    queryFn: () => franchisesApi.list({ per_page: 100, sort_by: 'owner_name', sort_order: 'asc' }),
  })

  const members = (data?.data ?? []).filter((f) => f.tscs_accepted === false)

  const sortedMembers = [...members].sort((a, b) => {
    const av = a[sortField]
    const bv = b[sortField]
    const aStr = typeof av === 'boolean' ? String(av) : av ?? ''
    const bStr = typeof bv === 'boolean' ? String(bv) : bv ?? ''
    if (aStr < bStr) return sortOrder === 'asc' ? -1 : 1
    if (aStr > bStr) return sortOrder === 'asc' ? 1 : -1
    return 0
  })

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
              EXPORT
            </button>
          </div>
        </div>

        <div className="card-body p-0">
          <table className="table">
            <thead>
              <tr>
                <th className="cursor-pointer" onClick={() => handleSort('owner_name')}>
                  <span className="flex items-center">
                    Name <SortIcon field="owner_name" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('name')}>
                  <span className="flex items-center">
                    Company Name <SortIcon field="name" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('territory')}>
                  <span className="flex items-center">
                    Service Location <SortIcon field="territory" />
                  </span>
                </th>
                <th className="cursor-pointer" onClick={() => handleSort('franchisee_type')}>
                  <span className="flex items-center">
                    Type <SortIcon field="franchisee_type" />
                  </span>
                </th>
                <th className="cursor-pointer text-center" onClick={() => handleSort('has_ipad')}>
                  <span className="flex items-center justify-center">
                    iPad <SortIcon field="has_ipad" />
                  </span>
                </th>
                <th className="cursor-pointer text-center" onClick={() => handleSort('status')}>
                  <span className="flex items-center justify-center">
                    Member Active <SortIcon field="status" />
                  </span>
                </th>
                <th className="text-center">Ts & Cs Accepted</th>
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr>
                  <td colSpan={7} className="text-center py-8 text-gray-400">
                    Loading members...
                  </td>
                </tr>
              ) : isError ? (
                <tr>
                  <td colSpan={7} className="text-center py-8 text-red-500">
                    Failed to load members.
                  </td>
                </tr>
              ) : sortedMembers.length === 0 ? (
                <tr>
                  <td colSpan={7} className="text-center py-8 text-gray-400">
                    No members with outstanding Ts &amp; Cs.
                  </td>
                </tr>
              ) : (
                sortedMembers.map((member) => (
                  <tr key={member.id}>
                    <td className="font-medium">{member.owner_name}</td>
                    <td>{member.name}</td>
                    <td>{member.territory || '-'}</td>
                    <td>{formatType(member.franchisee_type)}</td>
                    <td className="text-center">
                      {member.has_ipad ? (
                        <Check size={18} className="inline text-purple-600" />
                      ) : (
                        <X size={18} className="inline text-red-500" />
                      )}
                    </td>
                    <td className="text-center">
                      {member.status === 'ACTIVE' ? (
                        <Check size={18} className="inline text-purple-600" />
                      ) : (
                        <X size={18} className="inline text-red-500" />
                      )}
                    </td>
                    <td className="text-center">
                      <X size={18} className="inline text-red-500" />
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        <div className="card-footer flex items-center justify-end gap-4 py-3 px-6">
          <span className="text-sm text-gray-600">{sortedMembers.length} member(s) with outstanding Ts &amp; Cs</span>
        </div>
      </div>
    </div>
  )
}
