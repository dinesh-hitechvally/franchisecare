import { useEffect, useState, FormEvent } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { FileText } from 'lucide-react'
import { documentsApi } from '../../api/services'
import type { DocumentItem } from '../../types'

type Category = DocumentItem['category']

interface FormState {
  title: string
  description: string
  category: Category | ''
  status: DocumentItem['status']
}

const initialForm: FormState = {
  title: '',
  description: '',
  category: '',
  status: 'ACTIVE',
}

function formatFileSize(bytes: number): string {
  if (!bytes && bytes !== 0) return '-'
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

export function AddDocument() {
  const navigate = useNavigate()
  const { id } = useParams<{ id: string }>()
  const isEdit = Boolean(id)
  const documentId = id ? Number(id) : undefined

  const [form, setForm] = useState<FormState>(initialForm)
  const [file, setFile] = useState<File | null>(null)
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [submitting, setSubmitting] = useState(false)

  const { data: existingDoc, isLoading } = useQuery({
    queryKey: ['document', documentId],
    queryFn: () => documentsApi.get(documentId as number),
    enabled: isEdit && documentId !== undefined,
  })

  useEffect(() => {
    if (existingDoc) {
      setForm({
        title: existingDoc.title,
        description: existingDoc.description ?? '',
        category: existingDoc.category,
        status: existingDoc.status,
      })
    }
  }, [existingDoc])

  const handleChange = (field: keyof FormState, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }))
    if (errors[field]) {
      setErrors((prev) => {
        const next = { ...prev }
        delete next[field]
        return next
      })
    }
  }

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const selected = e.target.files?.[0] ?? null
    setFile(selected)
    if (errors.file) {
      setErrors((prev) => {
        const next = { ...prev }
        delete next.file
        return next
      })
    }
  }

  const applyServerErrors = (err: any) => {
    if (err?.response?.status === 422 && err.response.data?.errors) {
      const serverErrors: Record<string, string> = {}
      for (const [field, messages] of Object.entries(err.response.data.errors as Record<string, string[]>)) {
        serverErrors[field] = Array.isArray(messages) ? messages[0] : String(messages)
      }
      setErrors(serverErrors)
      toast.error(err.response.data?.message || 'Please fix the errors below')
    } else {
      toast.error(err?.response?.data?.message || 'Something went wrong. Please try again.')
    }
  }

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault()
    setSubmitting(true)
    setErrors({})

    try {
      if (isEdit && documentId !== undefined) {
        await documentsApi.update(documentId, {
          title: form.title,
          description: form.description || null,
          category: form.category || undefined,
          status: form.status,
        })
        toast.success('Document updated successfully')
      } else {
        if (!form.category) {
          setErrors({ category: 'Category is required' })
          setSubmitting(false)
          return
        }
        if (!file) {
          setErrors({ file: 'File is required' })
          setSubmitting(false)
          return
        }
        const fd = new FormData()
        fd.append('title', form.title)
        if (form.description) fd.append('description', form.description)
        fd.append('category', form.category)
        fd.append('file', file)
        await documentsApi.create(fd)
        toast.success('Document added successfully')
      }
      navigate('/documents/list')
    } catch (err) {
      applyServerErrors(err)
    } finally {
      setSubmitting(false)
    }
  }

  if (isEdit && isLoading) {
    return (
      <div className="page-content">
        <h1 className="page-title">Edit Document</h1>
        <div className="card">
          <div className="card-body p-8 text-center text-gray-400">Loading document...</div>
        </div>
      </div>
    )
  }

  return (
    <div className="page-content">
      <h1 className="page-title">{isEdit ? 'Edit Document' : 'Add Document'}</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">Document Details</h2>
        </div>
        <div className="card-body">
          <form onSubmit={handleSubmit} className="p-6 space-y-5">
            <div>
              <label className="form-label">
                Title <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                className="form-input"
                value={form.title}
                onChange={(e) => handleChange('title', e.target.value)}
                placeholder="Document title"
                required
              />
              {errors.title && <p className="text-red-500 text-xs mt-1.5">{errors.title}</p>}
            </div>

            <div>
              <label className="form-label">Description</label>
              <textarea
                className="form-input form-textarea"
                value={form.description}
                onChange={(e) => handleChange('description', e.target.value)}
                placeholder="Optional description"
                rows={4}
              />
              {errors.description && <p className="text-red-500 text-xs mt-1.5">{errors.description}</p>}
            </div>

            <div className={isEdit ? 'grid grid-cols-1 md:grid-cols-2 gap-5' : 'grid grid-cols-1 gap-5'}>
              <div>
                <label className="form-label">
                  Category <span className="text-red-500">*</span>
                </label>
                <select
                  className="form-input"
                  value={form.category}
                  onChange={(e) => handleChange('category', e.target.value)}
                  required
                >
                  <option value="">Select a category</option>
                  <option value="MANUALS">Manuals</option>
                  <option value="TEMPLATES">Templates</option>
                  <option value="OTHER">Other</option>
                </select>
                {errors.category && <p className="text-red-500 text-xs mt-1.5">{errors.category}</p>}
              </div>

              {isEdit && (
                <div>
                  <label className="form-label">Status</label>
                  <select
                    className="form-input"
                    value={form.status}
                    onChange={(e) => handleChange('status', e.target.value)}
                  >
                    <option value="ACTIVE">Active</option>
                    <option value="INACTIVE">Inactive</option>
                  </select>
                  {errors.status && <p className="text-red-500 text-xs mt-1.5">{errors.status}</p>}
                </div>
              )}
            </div>

            {isEdit ? (
              <div>
                <label className="form-label">File</label>
                <div className="flex items-center gap-2 border border-gray-200 rounded-md px-3 py-2.5 bg-gray-50 text-sm text-gray-600">
                  <FileText size={16} className="text-gray-400 flex-shrink-0" />
                  <span className="truncate">{existingDoc?.file_name}</span>
                  {existingDoc?.file_size !== undefined && (
                    <span className="text-gray-400 text-xs whitespace-nowrap ml-auto">
                      {formatFileSize(existingDoc.file_size)}
                    </span>
                  )}
                </div>
                <p className="text-xs text-gray-400 mt-1.5">
                  The file cannot be replaced. Delete this document and upload a new one if needed.
                </p>
              </div>
            ) : (
              <div>
                <label className="form-label">
                  File <span className="text-red-500">*</span>
                </label>
                <input
                  type="file"
                  className="form-input"
                  onChange={handleFileChange}
                  required
                />
                <p className="text-xs text-gray-400 mt-1.5">Maximum file size 10MB.</p>
                {errors.file && <p className="text-red-500 text-xs mt-1.5">{errors.file}</p>}
              </div>
            )}

            <div className="flex justify-end gap-3 pt-2">
              <button
                type="button"
                className="btn btn-outline"
                onClick={() => navigate('/documents/list')}
              >
                CANCEL
              </button>
              <button type="submit" className="btn btn-primary" disabled={submitting}>
                {submitting ? 'SAVING...' : isEdit ? 'SAVE CHANGES' : 'ADD DOCUMENT'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}
