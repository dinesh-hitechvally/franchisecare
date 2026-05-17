import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Search, Download, ChevronLeft, ChevronRight, MoreVertical, FolderOpen } from 'lucide-react'
import { format } from 'date-fns'
import { documentsApi } from '../../api/services'
import type { Document } from '../../types'
import { PortalMenu } from '../../components/ui/PortalMenu'
import { PageHeader } from '../../components/layout/PageHeader'

export function OtherFilesPage() {
  const [searchTerm, setSearchTerm] = useState('')
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)

  const { data: files = [], isLoading } = useQuery({
    queryKey: ['documents', 'other'],
    queryFn: () => documentsApi.getAll({ category: 'other' }),
  })

  const filteredFiles = files.filter((file) => {
    const q = searchTerm.trim().toLowerCase()
    if (!q) return true
    return (
      file.title.toLowerCase().includes(q) ||
      file.description.toLowerCase().includes(q)
    )
  })

  return (
    <div className="space-y-6">
      <PageHeader
        title="Other Files"
        description="Access miscellaneous files and documents"
        icon={<FolderOpen size={20} />}
      />

      {/* Search Card */}
      <div className="w-full">
        <Card className="p-6 bg-white border border-gray-100 shadow-sm mb-6">
          <div className="relative max-w-lg">
            <Search className="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search Other Files"
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
                    <td colSpan={6} className="px-6 py-10 text-center text-gray-500 italic">Loading files...</td>
                  </tr>
                ) : filteredFiles.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="px-6 py-10 text-center text-gray-500 italic">No files found</td>
                  </tr>
                ) : filteredFiles.map((file: Document) => (
                  <tr key={file.id} className="hover:bg-gray-50/50 transition-colors">
                    <td className="px-6 py-4 text-gray-800 font-medium">{file.title}</td>
                    <td className="px-6 py-4 text-gray-600">Files</td>
                    <td className="px-6 py-4 text-gray-500 max-w-xs truncate">{file.description}</td>
                    <td className="px-6 py-4">
                      <div className={`w-8 h-10 flex items-center justify-center rounded ${
                        file.fileType === 'pdf' ? 'bg-red-50 text-red-500' : 
                        file.fileType === 'jpg' ? 'bg-pink-50 text-pink-500' : 
                        'bg-purple-50 text-purple-500'
                      }`}>
                        <span className="text-[10px] font-bold uppercase">{file.fileType}</span>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-gray-500 whitespace-nowrap">{format(new Date(file.createdAt), 'yyyy-MM-dd HH:mm:ss')}</td>
                    <td className="px-6 py-4 text-center relative">
                      <button
                        type="button"
                        onClick={(e) => {
                          if (openMenuId === file.id) {
                            setOpenMenuId(null)
                            setMenuPos(null)
                          } else {
                            const rect = (e.currentTarget as HTMLButtonElement).getBoundingClientRect()
                            setMenuPos({ top: rect.bottom + 4, left: rect.right - 144 })
                            setOpenMenuId(file.id)
                          }
                        }}
                        className="p-1 rounded hover:bg-gray-100"
                      >
                        <MoreVertical className="w-5 h-5 text-gray-600" />
                      </button>
                      <PortalMenu
                        isOpen={openMenuId === file.id}
                        onClose={() => { setOpenMenuId(null); setMenuPos(null) }}
                        position={menuPos}
                        width={144}
                      >
                          <button type="button" className="w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            View
                          </button>
                          <button type="button" className="w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                            <Download className="w-4 h-4" /> Download
                          </button>
                      </PortalMenu>
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
              <select className="bg-transparent border-none text-sm font-medium focus:ring-0 outline-none cursor-pointer" defaultValue="25">
                <option>25</option>
                <option>50</option>
                <option>100</option>
              </select>
            </div>
            <span className="text-sm text-gray-600">{filteredFiles.length === 0 ? '0-0' : `1-${filteredFiles.length}`} of {filteredFiles.length}</span>
            <div className="flex items-center gap-1">
              <button className="p-1 hover:bg-gray-100 rounded-full">
                <ChevronLeft className="w-5 h-5" />
              </button>
              <button className="p-1 hover:bg-gray-100 rounded-full">
                <ChevronRight className="w-5 h-5" />
              </button>
            </div>
          </div>
        </Card>
      </div>
    </div>
  )
}
