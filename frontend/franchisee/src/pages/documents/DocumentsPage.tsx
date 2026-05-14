import { useState, useRef } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { documentsApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import type { Document } from '../../types'
import { Plus, FileText, Download, Upload, MoreVertical, Eye, Trash2 } from 'lucide-react'
import { format } from 'date-fns'
import { PortalMenu } from '../../components/ui/PortalMenu'

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export function DocumentsPage() {
  const queryClient = useQueryClient()
  const { addToast } = useToastStore()
  const fileInputRef = useRef<HTMLInputElement>(null)
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [viewDocument, setViewDocument] = useState<Document | null>(null)
  const [filterVisibility, setFilterVisibility] = useState('')
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)
  
  // Upload form state
  const [uploadFile, setUploadFile] = useState<File | null>(null)
  const [uploadTitle, setUploadTitle] = useState('')
  const [uploadDescription, setUploadDescription] = useState('')
  const [uploadVisibility, setUploadVisibility] = useState<'global' | 'franchise'>('franchise')

  const { data: documents, isLoading } = useQuery({
    queryKey: ['documents', filterVisibility],
    queryFn: () =>
      documentsApi.getAll(
        filterVisibility
          ? { visibility: filterVisibility as Document['visibility'] }
          : undefined
      ),
  })

  const uploadMutation = useMutation({
    mutationFn: (data: { file: File; title: string; description: string; visibility: string }) =>
      documentsApi.upload(data.file, {
        title: data.title,
        description: data.description,
        visibility: data.visibility,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['documents'] })
      addToast('Document uploaded successfully', 'success')
      resetUploadForm()
      setIsModalOpen(false)
    },
    onError: (error: any) => {
      addToast(error?.response?.data?.message || 'Failed to upload document', 'error')
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id: string) => documentsApi.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['documents'] })
      addToast('Document deleted successfully', 'success')
      setOpenMenuId(null)
    },
    onError: (error: any) => {
      addToast(error?.response?.data?.message || 'Failed to delete document', 'error')
    },
  })

  const resetUploadForm = () => {
    setUploadFile(null)
    setUploadTitle('')
    setUploadDescription('')
    setUploadVisibility('franchise')
    if (fileInputRef.current) {
      fileInputRef.current.value = ''
    }
  }

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) {
      setUploadFile(file)
      if (!uploadTitle) {
        setUploadTitle(file.name.replace(/\.[^/.]+$/, ''))
      }
    }
  }

  const handleUpload = () => {
    if (!uploadFile) {
      addToast('Please select a file', 'error')
      return
    }
    if (!uploadTitle.trim()) {
      addToast('Please enter a title', 'error')
      return
    }
    uploadMutation.mutate({
      file: uploadFile,
      title: uploadTitle,
      description: uploadDescription,
      visibility: uploadVisibility,
    })
  }

  const handleDownload = (doc: Document) => {
    if (doc.fileUrl) {
      // Construct full URL for download
      const downloadUrl = doc.fileUrl.startsWith('http') 
        ? doc.fileUrl 
        : `${API_BASE_URL.replace('/api', '')}${doc.fileUrl}`
      window.open(downloadUrl, '_blank')
    } else {
      addToast('No file available for download', 'error')
    }
    setOpenMenuId(null)
  }

  const handleView = (doc: Document) => {
    setViewDocument(doc)
    setOpenMenuId(null)
  }

  const handleDelete = (id: string) => {
    if (confirm('Are you sure you want to delete this document?')) {
      deleteMutation.mutate(id)
    }
  }

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
                      <button
                        type="button"
                        onClick={() => handleView(row)}
                        className="w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                      >
                        <Eye className="w-4 h-4" /> View
                      </button>
                      <button
                        type="button"
                        onClick={() => handleDownload(row)}
                        className="w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                      >
                        <Download className="w-4 h-4" /> Download
                      </button>
                      <button
                        type="button"
                        onClick={() => handleDelete(row.id)}
                        className="w-full px-3 py-2 text-sm text-red-600 hover:bg-gray-50 flex items-center gap-2"
                      >
                        <Trash2 className="w-4 h-4" /> Delete
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
        onClose={() => { setIsModalOpen(false); resetUploadForm() }}
        title="Upload Document"
        footer={
          <>
            <Button variant="secondary" onClick={() => { setIsModalOpen(false); resetUploadForm() }}>
              Cancel
            </Button>
            <Button onClick={handleUpload} disabled={uploadMutation.isPending}>
              <Upload className="w-4 h-4 mr-2" />
              {uploadMutation.isPending ? 'Uploading...' : 'Upload'}
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          <div
            className="border-2 border-dashed border-secondary-300 rounded-lg p-8 text-center cursor-pointer hover:border-primary-400 transition-colors"
            onClick={() => fileInputRef.current?.click()}
          >
            <input
              ref={fileInputRef}
              type="file"
              className="hidden"
              accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.png,.jpg,.jpeg"
              onChange={handleFileSelect}
            />
            <Upload className="w-10 h-10 text-secondary-400 mx-auto mb-3" />
            {uploadFile ? (
              <p className="text-sm text-primary-600 font-medium mb-2">{uploadFile.name}</p>
            ) : (
              <p className="text-sm text-secondary-600 mb-2">
                Drag and drop your file here, or click to browse
              </p>
            )}
            <p className="text-xs text-secondary-400">PDF, DOC, DOCX, XLS, XLSX, images up to 10MB</p>
          </div>
          <Input
            label="Document Title"
            value={uploadTitle}
            onChange={(e) => setUploadTitle(e.target.value)}
            required
          />
          <div>
            <label className="text-sm font-medium text-secondary-700">Description</label>
            <textarea
              className="input mt-1"
              rows={3}
              value={uploadDescription}
              onChange={(e) => setUploadDescription(e.target.value)}
              placeholder="Optional description..."
            />
          </div>
          <div>
            <label className="text-sm font-medium text-secondary-700">Visibility</label>
            <select
              className="input mt-1"
              value={uploadVisibility}
              onChange={(e) => setUploadVisibility(e.target.value as any)}
            >
              <option value="global">Global - All Franchises</option>
              <option value="franchise">Franchise Only</option>
            </select>
          </div>
        </div>
      </Modal>

      {/* View Document Modal */}
      {viewDocument && (
        <Modal
          isOpen={!!viewDocument}
          onClose={() => setViewDocument(null)}
          title={viewDocument.title}
          size="lg"
          footer={
            <Button variant="secondary" onClick={() => setViewDocument(null)}>
              Close
            </Button>
          }
        >
          <div className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <span className="text-sm text-gray-500">Type</span>
                <p className="font-medium uppercase">{viewDocument.fileType}</p>
              </div>
              <div>
                <span className="text-sm text-gray-500">Visibility</span>
                <p className="font-medium capitalize">{viewDocument.visibility}</p>
              </div>
              <div>
                <span className="text-sm text-gray-500">Uploaded</span>
                <p className="font-medium">{format(new Date(viewDocument.createdAt), 'PPP')}</p>
              </div>
            </div>
            {viewDocument.description && (
              <div>
                <span className="text-sm text-gray-500">Description</span>
                <p className="mt-1">{viewDocument.description}</p>
              </div>
            )}
            {viewDocument.fileUrl && (
              <div className="pt-4 border-t">
                <Button onClick={() => handleDownload(viewDocument)}>
                  <Download className="w-4 h-4 mr-2" />
                  Download File
                </Button>
              </div>
            )}
          </div>
        </Modal>
      )}
    </div>
  )
}
