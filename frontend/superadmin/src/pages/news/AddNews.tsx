import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { isAxiosError } from 'axios'
import toast from 'react-hot-toast'
import { newsApi } from '../../api/services'
import type { NewsItem } from '../../types'

interface FormState {
  title: string
  content: string
  category: string
  status: NewsItem['status']
}

const initialForm: FormState = {
  title: '',
  content: '',
  category: '',
  status: 'DRAFT',
}

type FormErrors = Partial<Record<keyof FormState | 'image', string>>

export function AddNews() {
  const { id } = useParams<{ id: string }>()
  const isEdit = Boolean(id)
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const [form, setForm] = useState<FormState>(initialForm)
  const [imageFile, setImageFile] = useState<File | null>(null)
  const [existingImage, setExistingImage] = useState<string | null>(null)
  const [errors, setErrors] = useState<FormErrors>({})

  const { data: existingNews, isLoading } = useQuery({
    queryKey: ['news', id],
    queryFn: () => newsApi.get(Number(id)),
    enabled: isEdit,
  })

  useEffect(() => {
    if (existingNews) {
      setForm({
        title: existingNews.title,
        content: existingNews.content,
        category: existingNews.category ?? '',
        status: existingNews.status,
      })
      setExistingImage(existingNews.image)
    }
  }, [existingNews])

  const handleChange = (field: keyof FormState, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }))
    if (errors[field]) {
      setErrors((prev) => ({ ...prev, [field]: undefined }))
    }
  }

  const handleImageChange = (file: File | null) => {
    setImageFile(file)
    if (errors.image) {
      setErrors((prev) => ({ ...prev, image: undefined }))
    }
  }

  const validate = (): boolean => {
    const newErrors: FormErrors = {}
    if (!form.title.trim()) {
      newErrors.title = 'Title is required'
    }
    if (!form.content.trim()) {
      newErrors.content = 'Content is required'
    }
    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  const applyServerErrors = (error: unknown) => {
    if (isAxiosError(error) && error.response?.status === 422) {
      const serverErrors = error.response.data?.errors as Record<string, string[]> | undefined
      if (serverErrors) {
        const mapped: FormErrors = {}
        Object.entries(serverErrors).forEach(([field, messages]) => {
          mapped[field as keyof FormErrors] = messages[0]
        })
        setErrors((prev) => ({ ...prev, ...mapped }))
      }
      toast.error('Please correct the errors and try again')
    } else {
      toast.error(isEdit ? 'Failed to update news' : 'Failed to add news')
    }
  }

  const buildFormData = () => {
    const fd = new FormData()
    fd.append('title', form.title)
    fd.append('content', form.content)
    if (form.category) fd.append('category', form.category)
    fd.append('status', form.status)
    if (imageFile) fd.append('image', imageFile)
    return fd
  }

  const createMutation = useMutation({
    mutationFn: (fd: FormData) => newsApi.create(fd),
    onSuccess: () => {
      toast.success('News added successfully')
      queryClient.invalidateQueries({ queryKey: ['news'] })
      navigate('/news/list')
    },
    onError: applyServerErrors,
  })

  const updateMutation = useMutation({
    mutationFn: (fd: FormData) => newsApi.update(Number(id), fd),
    onSuccess: () => {
      toast.success('News updated successfully')
      queryClient.invalidateQueries({ queryKey: ['news'] })
      navigate('/news/list')
    },
    onError: applyServerErrors,
  })

  const isSaving = createMutation.isPending || updateMutation.isPending

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()

    if (!validate()) {
      toast.error('Please fill in all required fields')
      return
    }

    const fd = buildFormData()
    if (isEdit) {
      updateMutation.mutate(fd)
    } else {
      createMutation.mutate(fd)
    }
  }

  const handleCancel = () => {
    navigate('/news/list')
  }

  if (isEdit && isLoading) {
    return (
      <div className="page-content">
        <h1 className="page-title">{isEdit ? 'Edit News' : 'Add News'}</h1>
        <div className="card">
          <div className="card-body p-8 text-center text-gray-400">Loading news...</div>
        </div>
      </div>
    )
  }

  return (
    <div className="page-content">
      <h1 className="page-title">{isEdit ? 'Edit News' : 'Add News'}</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">News Details</h2>
        </div>
        <div className="card-body">
          <form onSubmit={handleSubmit} className="p-6 space-y-5">
            <div>
              <label className="form-label">
                Title <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={form.title}
                onChange={(e) => handleChange('title', e.target.value)}
                className="form-input"
                placeholder="News title"
              />
              {errors.title && <p className="text-red-500 text-xs mt-1.5">{errors.title}</p>}
            </div>

            <div>
              <label className="form-label">
                Content <span className="text-red-500">*</span>
              </label>
              <textarea
                value={form.content}
                onChange={(e) => handleChange('content', e.target.value)}
                rows={10}
                className="form-input form-textarea"
                placeholder="News content"
              />
              {errors.content && <p className="text-red-500 text-xs mt-1.5">{errors.content}</p>}
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label className="form-label">Category</label>
                <input
                  type="text"
                  value={form.category}
                  onChange={(e) => handleChange('category', e.target.value)}
                  className="form-input"
                  placeholder="e.g. Announcement (optional)"
                  maxLength={50}
                />
                {errors.category && <p className="text-red-500 text-xs mt-1.5">{errors.category}</p>}
              </div>

              <div>
                <label className="form-label">
                  Status <span className="text-red-500">*</span>
                </label>
                <select
                  value={form.status}
                  onChange={(e) => handleChange('status', e.target.value)}
                  className="form-input"
                >
                  <option value="DRAFT">Draft</option>
                  <option value="PUBLISHED">Published</option>
                </select>
                {errors.status && <p className="text-red-500 text-xs mt-1.5">{errors.status}</p>}
              </div>
            </div>

            <div>
              <label className="form-label">Image</label>
              <input
                type="file"
                accept="image/*"
                onChange={(e) => handleImageChange(e.target.files?.[0] ?? null)}
                className="form-input"
              />
              {existingImage && !imageFile && (
                <p className="text-xs text-gray-500 mt-1.5">Current image: {existingImage.split('/').pop()}</p>
              )}
              {imageFile && <p className="text-xs text-gray-500 mt-1.5">Selected: {imageFile.name}</p>}
              {errors.image && <p className="text-red-500 text-xs mt-1.5">{errors.image}</p>}
            </div>

            <div className="flex justify-end gap-3 pt-2">
              <button type="button" onClick={handleCancel} className="btn btn-outline">
                CANCEL
              </button>
              <button type="submit" className="btn btn-primary" disabled={isSaving}>
                {isSaving ? 'SAVING...' : isEdit ? 'UPDATE NEWS' : 'ADD NEWS'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}
