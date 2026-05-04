import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import type { Document } from '../../types'
import { Plus, FileText, Download, Eye, Upload, Trash2 } from 'lucide-react'
import { format } from 'date-fns'

export function DocumentsPage() {
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [filterVisibility, setFilterVisibility] = useState('')

  const { data: documents, isLoading } = useQuery({
    queryKey: ['documents', filterVisibility],
    queryFn: async () => {
      // Mock data
      return [
        { id: '1', title: 'Service Agreement Template', description: 'Standard service agreement for customers', fileUrl: '#', fileType: 'pdf', visibility: 'global', uploadedBy: '1', createdAt: new Date().toISOString() },
        { id: '2', title: 'Staff Handbook', description: 'Employee guidelines and policies', fileUrl: '#', fileType: 'pdf', visibility: 'global', uploadedBy: '1', createdAt: new Date(Date.now() - 86400000).toISOString() },
        { id: '3', title: 'Franchise Operations Manual', description: 'Local franchise procedures', fileUrl: '#', fileType: 'pdf', visibility: 'franchise', franchise_id: '1', uploadedBy: '1', createdAt: new Date(Date.now() - 172800000).toISOString() },
        { id: '4', title: 'Safety Procedures', description: 'Health and safety guidelines', fileUrl: '#', fileType: 'docx', visibility: 'global', uploadedBy: '1', createdAt: new Date(Date.now() - 259200000).toISOString() },
      ] as Document[]
    },
  })

  const visibilityColors = {
    global: 'bg-blue-100 text-blue-700',
    franchise: 'bg-green-100 text-green-700',
  }

  const filteredDocuments = filterVisibility
    ? documents?.filter((d) => d.visibility === filterVisibility)
    : documents

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-secondary-900">Documents</h1>
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
            { key: 'createdAt', title: 'Uploaded', render: (row: Document) => format(new Date(row.created_at), 'MMM d, yyyy') },
            {
              key: 'actions',
              title: 'Actions',
              render: () => (
                <div className="flex gap-1">
                  <Button variant="ghost" size="sm">
                    <Eye className="w-4 h-4" />
                  </Button>
                  <Button variant="ghost" size="sm">
                    <Download className="w-4 h-4" />
                  </Button>
                  <Button variant="ghost" size="sm" className="text-red-600 hover:text-red-700">
                    <Trash2 className="w-4 h-4" />
                  </Button>
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
