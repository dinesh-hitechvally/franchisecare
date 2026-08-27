import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { forumCategoriesApi } from '../../api/services'

interface CategoryForm {
  categoryName: string
  categoryDescription: string
  makeCategoryActive: boolean
}

const initialForm: CategoryForm = {
  categoryName: '',
  categoryDescription: '',
  makeCategoryActive: false
}

function extractErrorMessage(err: any, fallback: string): string {
  const errors = err?.response?.data?.errors
  if (errors) {
    const first = Object.values(errors)[0]
    if (Array.isArray(first) && first.length > 0) return String(first[0])
  }
  return err?.response?.data?.message ?? fallback
}

export function AddCategory() {
  const navigate = useNavigate()
  const { id } = useParams<{ id: string }>()
  const isEdit = Boolean(id)
  const [form, setForm] = useState<CategoryForm>(initialForm)
  const [errors, setErrors] = useState<Partial<CategoryForm>>({})
  const [submitting, setSubmitting] = useState(false)

  const { data: category, isLoading } = useQuery({
    queryKey: ['forum-categories', id],
    queryFn: () => forumCategoriesApi.get(Number(id)),
    enabled: isEdit,
  })

  useEffect(() => {
    if (category) {
      setForm({
        categoryName: category.name,
        categoryDescription: category.description ?? '',
        makeCategoryActive: category.status === 'active',
      })
    }
  }, [category])

  const handleChange = (field: keyof CategoryForm, value: string | boolean) => {
    setForm(prev => ({ ...prev, [field]: value }))
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: undefined }))
    }
  }

  const validate = (): boolean => {
    const newErrors: Partial<CategoryForm> = {}

    if (!form.categoryName.trim()) {
      newErrors.categoryName = 'Category Name is required'
    }
    if (!form.categoryDescription.trim()) {
      newErrors.categoryDescription = 'Category Description is required'
    }

    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!validate()) {
      toast.error('Please fill in all required fields')
      return
    }

    setSubmitting(true)
    try {
      if (isEdit) {
        await forumCategoriesApi.update(Number(id), {
          name: form.categoryName,
          description: form.categoryDescription,
          status: form.makeCategoryActive ? 'active' : 'inactive',
        })
        toast.success('Category updated successfully!')
      } else {
        await forumCategoriesApi.create({
          name: form.categoryName,
          description: form.categoryDescription,
        })
        toast.success('Category added successfully!')
      }
      navigate('/forum/list-categories')
    } catch (err: any) {
      toast.error(extractErrorMessage(err, isEdit ? 'Failed to update category' : 'Failed to add category'))
    } finally {
      setSubmitting(false)
    }
  }

  const handleCancel = () => {
    navigate('/forum/list-categories')
  }

  if (isEdit && isLoading) {
    return (
      <div className="page-content">
        <h1 className="page-title">Edit Category</h1>
        <div className="card p-8 text-center text-gray-400">Loading category...</div>
      </div>
    )
  }

  return (
    <div className="page-content">
      <h1 className="page-title">{isEdit ? 'Edit Category' : 'Add Category'}</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">Category Details</h2>
        </div>
        <div className="card-body p-6">
          <form onSubmit={handleSubmit}>
            <div className="space-y-4">
              <div>
                <label className="form-label">
                  Category Name <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={form.categoryName}
                  onChange={(e) => handleChange('categoryName', e.target.value)}
                  className={`form-input ${errors.categoryName ? 'border-red-500' : ''}`}
                  placeholder="Enter category name"
                />
                {errors.categoryName && <p className="text-red-500 text-xs mt-1">{errors.categoryName}</p>}
              </div>

              <div>
                <label className="form-label">
                  Category Description <span className="text-red-500">*</span>
                </label>
                <textarea
                  value={form.categoryDescription}
                  onChange={(e) => handleChange('categoryDescription', e.target.value)}
                  rows={4}
                  className={`form-input form-textarea ${errors.categoryDescription ? 'border-red-500' : ''}`}
                  placeholder="Enter category description"
                />
                {errors.categoryDescription && <p className="text-red-500 text-xs mt-1">{errors.categoryDescription}</p>}
              </div>

              {/* Checkbox - only meaningful once the category exists */}
              {isEdit && (
                <label className="form-checkbox">
                  <input
                    type="checkbox"
                    checked={form.makeCategoryActive}
                    onChange={(e) => handleChange('makeCategoryActive', e.target.checked)}
                  />
                  <span>Make Category Active</span>
                </label>
              )}
            </div>

            <div className="flex justify-end gap-3 mt-6">
              <button type="button" className="btn btn-outline" onClick={handleCancel}>
                CANCEL
              </button>
              <button type="submit" className="btn btn-primary" disabled={submitting}>
                {submitting ? 'SAVING...' : isEdit ? 'SAVE CATEGORY' : 'ADD CATEGORY'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}
