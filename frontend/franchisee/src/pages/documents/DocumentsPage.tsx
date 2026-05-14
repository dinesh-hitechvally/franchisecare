import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { documentsApi } from '../../api/services'
import type { Document } from '../../types'
import { Plus, FileText, Download, Upload, MoreVertical } from 'lucide-react'
import { format } from 'date-fns'
import { PortalMenu } from '../../components/ui/PortalMenu'

export function DocumentsPage() {
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [filterVisibility, setFilterVisibility] = useState('')
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)

  const { data: documents, isLoading } = useQuery({
    queryKey: ['documents', filterVisibility],
    queryFn: () =>
      documentsApi.getAll(
        filterVisibility
          ? { visibility: filterVisibility as Document['visibility'] }
          : undefined
      ),
  })

  const visibilityColors = {
    global: 'bg-blue-100 text-blue-700',
    franchise: 'bg-green-100 text-green-700',
  }

  const filteredDocuments = documents ?? []

  return (
    <div className="space-y-6">
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6 flex items-center justify-between gap-4">
        <h1 className="text-2xl font-bold text-gray-800">Documents</h1>
        <Button onClick={() => setIsModalOpen(true)} size="sm">
          <Plus className="w-4 h-4 mr-2" />
          Upload Document
        </Button>
      </div>

      <Card>
        <div className="flex items-center gap-4 mb-6">
          <select
            value={filterVisibility}
            onChange={(e) => setFilterVisibility(e.target.value)}
            className="input w-48"
          >
            <option value="">All Visibility</option>
            <option value="global">Global</option>
            <option value="franchise">Franchise Only</option>
          </select>
        </div>

        <Table
          columns={[
            {
              key: 'icon',
              title: '',
              render: (row: Document) => (
                <div className="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                  <FileText className="w-5 h-5 text-red-600" />
                </div>
              ),
            },
            { key: 'title', title: 'Title' },
            { key: 'description', title: 'Description', render: (row: Document) => (
              <span className="text-secondary-500 truncate max-w-xs block">{row.description}</span>
            )},
            { key: 'fileType', title: 'Type', render: (row: Document) => (
              <span className="uppercase text-xs font-medium bg-secondary-100 px-2 py-1 rounded">{row.fileType}</span>
            )},
            { key: 'visibility', title: 'Visibility', render: (row: Document) => (
              <span className={`px-2 py-1 rounded-full text-xs font-medium ${visibilityColors[row.visibility]}`}>
                {row.visibility}
              </span>
            )},
            { key: 'createdAt', title: 'Uploaded', render: (row: Document) => format(new Date(row.createdAt), 'MMM d, yyyy') },
            {
              key: 'actions',
              title: 'Mgmt',
              render: (row: Document) => (
                <div className="relative flex justify-center">
                  <button
                    type="button"
                    onClick={(e) => {
                      if (openMenuId === row.id) {
                        setOpenMenuId(null)
                        setMenuPos(null)
                      } else {
                        const rect = (e.currentTarget as HTMLButtonElement).getBoundingClientRect()
                        setMenuPos({ top: rect.bottom + 4, left: rect.right - 160 })
                        setOpenMenuId(row.id)
                      }
                    }}
                    className="p-1 rounded hover:bg-gray-100"
                  >
                    <MoreVertical className="w-5 h-5 text-gray-600" />
                  </button>
                  <PortalMenu
                    isOpen={openMenuId === row.id}
                    onClose={() => { setOpenMenuId(null); setMenuPos(null) }}
                    position={menuPos}
                    width={160}
                  >
                      <button type="button" className="w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        View
                      </button>
                      <button type="button" className="w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                        <Download className="w-4 h-4" /> Download
                      </button>
                      <button type="button" className="w-full px-3 py-2 text-sm text-red-600 hover:bg-gray-50">
                        Delete
                      </button>
                  </PortalMenu>
                </div>
              ),
            },
          ]}
          data={filteredDocuments ?? []}
          keyExtractor={(row) => row.id}
          isLoading={isLoading}
        />
      </Card>

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title="Upload Document"
        footer={
          <>
            <Button variant="secondary" onClick={() => setIsModalOpen(false)}>
              Cancel
            </Button>
            <Button onClick={() => setIsModalOpen(false)}>
              <Upload className="w-4 h-4 mr-2" />
              Upload
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          <div className="border-2 border-dashed border-secondary-300 rounded-lg p-8 text-center">
            <Upload className="w-10 h-10 text-secondary-400 mx-auto mb-3" />
            <p className="text-sm text-secondary-600 mb-2">
              Drag and drop your file here, or click to browse
            </p>
            <p className="text-xs text-secondary-400">PDF, DOC, DOCX up to 10MB</p>
          </div>
          <Input label="Document Title" />
          <div>
            <label className="text-sm font-medium text-secondary-700">Description</label>
            <textarea className="input mt-1" rows={3} />
          </div>
          <div>
            <label className="text-sm font-medium text-secondary-700">Visibility</label>
            <select className="input mt-1">
              <option value="global">Global - All Franchises</option>
              <option value="franchise">Franchise Only</option>
            </select>
          </div>
        </div>
      </Modal>
    </div>
  )
}
