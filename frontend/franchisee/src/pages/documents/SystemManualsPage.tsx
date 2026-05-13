import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Search, Download, ChevronLeft, ChevronRight, MoreVertical } from 'lucide-react'
import { format } from 'date-fns'
import { documentsApi } from '../../api/services'
import type { Document } from '../../types'

export function SystemManualsPage() {
  const [searchTerm, setSearchTerm] = useState('')
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)

  const { data: manuals = [], isLoading } = useQuery({
    queryKey: ['documents', 'manuals'],
    queryFn: () => documentsApi.getAll({ category: 'manual' }),
  })

  const filteredManuals = manuals.filter((manual) => {
    const q = searchTerm.trim().toLowerCase()
    if (!q) return true
    return (
      manual.title.toLowerCase().includes(q) ||
      manual.description.toLowerCase().includes(q)
    )
  })

  return (
    <div className="space-y-6">
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">System Manuals</h1>
      </div>

      {/* Search Card */}
      <div className="w-full">
        <Card className="p-6 bg-white border border-gray-100 shadow-sm mb-6">
          <div className="relative max-w-lg">
            <Search className="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search System Manuals"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2 bg-transparent border-b border-gray-200 focus:outline-none focus:border-blue-500 text-gray-700 placeholder-gray-400 transition-colors"
            />
          </div>
        </Card>

        {/* Data Table Card */}
        <Card className="border border-gray-200 shadow-sm overflow-hidden bg-white">
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left border-collapse">
              <thead className="bg-white border-b border-gray-100">
                <tr>
                  <th className="px-6 py-4 font-bold text-gray-400 uppercase text-xs tracking-wider">Title</th>
                  <th className="px-6 py-4 font-bold text-gray-400 uppercase text-xs tracking-wider">File</th>
                  <th className="px-6 py-4 font-bold text-gray-400 uppercase text-xs tracking-wider">Description</th>
                  <th className="px-6 py-4 font-bold text-gray-400 uppercase text-xs tracking-wider">Type</th>
                  <th className="px-6 py-4 font-bold text-gray-400 uppercase text-xs tracking-wider">Last Updated</th>
                  <th className="px-6 py-4 font-bold text-gray-400 uppercase text-xs tracking-wider text-center">Mgmt</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50 bg-white">
                {isLoading ? (
                  <tr>
                    <td colSpan={6} className="px-6 py-10 text-center text-gray-500 italic">Loading manuals...</td>
                  </tr>
                ) : filteredManuals.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="px-6 py-10 text-center text-gray-500 italic">No manuals found</td>
                  </tr>
                ) : filteredManuals.map((manual: Document) => (
                  <tr key={manual.id} className="hover:bg-gray-50/50 transition-colors">
                    <td className="px-6 py-4 text-gray-800 font-medium">{manual.title}</td>
                    <td className="px-6 py-4 text-gray-600">Manual</td>
                    <td className="px-6 py-4 text-gray-500 max-w-xs truncate">{manual.description}</td>
                    <td className="px-6 py-4">
                      <div className="w-8 h-10 bg-red-50 text-red-500 flex items-center justify-center rounded">
                        <span className="text-[10px] font-bold">{manual.fileType.toUpperCase()}</span>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-gray-500 whitespace-nowrap">{format(new Date(manual.createdAt), 'yyyy-MM-dd HH:mm:ss')}</td>
                    <td className="px-6 py-4 text-center relative">
                      <button
                        type="button"
                        onClick={() => setOpenMenuId(openMenuId === manual.id ? null : manual.id)}
                        className="p-1 rounded hover:bg-gray-100"
                      >
                        <MoreVertical className="w-5 h-5 text-gray-600" />
                      </button>
                      {openMenuId === manual.id && (
                        <div className="absolute right-6 mt-1 w-36 bg-white border border-gray-200 rounded-md shadow-lg z-20 text-left">
                          <button type="button" className="w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            View
                          </button>
                          <button type="button" className="w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                            <Download className="w-4 h-4" /> Download
                          </button>
                        </div>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Pagination */}
          <div className="px-6 py-4 bg-white border-t border-gray-100 flex items-center justify-end gap-6">
            <div className="flex items-center gap-2">
              <span className="text-sm text-gray-500">Rows per page:</span>
              <select className="bg-transparent border-none text-sm font-medium focus:ring-0 outline-none cursor-pointer">
                <option>25</option>
                <option>50</option>
                <option>100</option>
              </select>
            </div>
            <span className="text-sm text-gray-600">{filteredManuals.length === 0 ? '0-0' : `1-${filteredManuals.length}`} of {filteredManuals.length}</span>
            <div className="flex items-center gap-1">
              <button className="p-1 hover:bg-gray-100 rounded-full disabled:opacity-30" disabled>
                <ChevronLeft className="w-5 h-5" />
              </button>
              <button className="p-1 hover:bg-gray-100 rounded-full disabled:opacity-30" disabled>
                <ChevronRight className="w-5 h-5" />
              </button>
            </div>
          </div>
        </Card>
      </div>
    </div>
  )
}
