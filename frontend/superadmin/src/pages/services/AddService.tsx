import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { servicesApi, serviceCategoriesApi } from '../../api/services'

interface ServiceForm {
  name: string
  description: string
  categoryId: string
  basePrice: string
  duration: string
  icon: string
  active: boolean
}

const initialForm: ServiceForm = {
  name: '',
  description: '',
  categoryId: '',
  basePrice: '0',
  duration: '',
  icon: '',
  active: true
}

function extractErrorMessage(err: any, fallback: string): string {
  const errors = err?.response?.data?.errors
  if (errors) {
    const first = Object.values(errors)[0]
    if (Array.isArray(first) && first.length > 0) return String(first[0])
  }
  return err?.response?.data?.message ?? fallback
}

export function AddService() {
  const navigate = useNavigate()
  const { id } = useParams<{ id: string }>()
  const isEdit = Boolean(id)
  const [form, setForm] = useState<ServiceForm>(initialForm)
  const [errors, setErrors] = useState<Partial<Record<keyof ServiceForm, string>>>({})
  const [submitting, setSubmitting] = useState(false)

  const { data: categories = [] } = useQuery({
    queryKey: ['service-categories', 'all'],
    queryFn: () => serviceCategoriesApi.list(),
  })

  const { data: service, isLoading } = useQuery({
    queryKey: ['services', id],
    queryFn: () => servicesApi.get(Number(id)),
    enabled: isEdit,
  })

  useEffect(() => {
    if (service) {
      setForm({
        name: service.name,
        description: service.description ?? '',
        categoryId: service.category_id != null ? String(service.category_id) : '',
        basePrice: String(service.base_price),
        duration: String(service.duration),
        icon: service.icon ?? '',
        active: service.status === 'active'
      })
    }
  }, [service])

  const handleChange = (field: keyof ServiceForm, value: string | boolean) => {
    setForm(prev => ({ ...prev, [field]: value }))
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: undefined }))
    }
  }

  const validate = (): boolean => {
    const newErrors: Partial<Record<keyof ServiceForm, string>> = {}

    if (!form.name.trim()) {
      newErrors.name = 'Service Name is required'
    }
    if (!form.basePrice.trim()) {
      newErrors.basePrice = 'Default Service Price is required'
    }
    if (!form.duration.trim()) {
      newErrors.duration = 'Service Duration is required'
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
        await servicesApi.update(Number(id), {
          name: form.name,
          description: form.description || null,
          category_id: form.categoryId ? Number(form.categoryId) : null,
          base_price: Number(form.basePrice),
          duration: Number(form.duration),
          icon: form.icon || null,
          status: form.active ? 'active' : 'inactive',
        })
        toast.success('Service updated successfully!')
      } else {
        await servicesApi.create({
          name: form.name,
          description: form.description || undefined,
          category_id: form.categoryId ? Number(form.categoryId) : null,
          base_price: Number(form.basePrice),
          duration: Number(form.duration),
          icon: form.icon || undefined,
        })
        toast.success('Service added successfully!')
      }
      navigate('/services/list')
    } catch (err: any) {
      toast.error(extractErrorMessage(err, isEdit ? 'Failed to update service' : 'Failed to add service'))
    } finally {
      setSubmitting(false)
    }
  }

  const handleCancel = () => {
    navigate('/services/list')
  }

  if (isEdit && isLoading) {
    return (
      <div className="page-content">
        <h1 className="page-title">Edit Service</h1>
        <div className="card p-8 text-center text-gray-400">Loading...</div>
      </div>
    )
  }

  return (
    <div className="page-content">
      <h1 className="page-title">{isEdit ? 'Edit Service' : 'Add Service'}</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">Service Details</h2>
        </div>
        <div className="card-body">
          <form onSubmit={handleSubmit} className="p-6 space-y-5">
            <div>
              <label className="form-label">
                Service Name <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={form.name}
                onChange={(e) => handleChange('name', e.target.value)}
                className={`form-input ${errors.name ? 'border-red-500' : ''}`}
                placeholder="Service name"
              />
              {errors.name && <p className="text-red-500 text-xs mt-1.5">{errors.name}</p>}
            </div>

            <div>
              <label className="form-label">Service Description</label>
              <textarea
                value={form.description}
                onChange={(e) => handleChange('description', e.target.value)}
                rows={4}
                className="form-input form-textarea"
                placeholder="Service description"
              />
              {errors.description && <p className="text-red-500 text-xs mt-1.5">{errors.description}</p>}
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label className="form-label">Service Group</label>
                <select
                  value={form.categoryId}
                  onChange={(e) => handleChange('categoryId', e.target.value)}
                  className="form-input"
                >
                  <option value="">None</option>
                  {categories.map(category => (
                    <option key={category.id} value={category.id}>{category.name}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="form-label">Icon</label>
                <input
                  type="text"
                  value={form.icon}
                  onChange={(e) => handleChange('icon', e.target.value)}
                  className="form-input"
                  placeholder="Icon"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label className="form-label">
                  Default Price <span className="text-red-500">*</span>
                </label>
                <input
                  type="number"
                  value={form.basePrice}
                  onChange={(e) => handleChange('basePrice', e.target.value)}
                  className={`form-input ${errors.basePrice ? 'border-red-500' : ''}`}
                  placeholder="0.00"
                />
                {errors.basePrice && <p className="text-red-500 text-xs mt-1.5">{errors.basePrice}</p>}
              </div>

              <div>
                <label className="form-label">
                  Duration (mins) <span className="text-red-500">*</span>
                </label>
                <input
                  type="number"
                  value={form.duration}
                  onChange={(e) => handleChange('duration', e.target.value)}
                  className={`form-input ${errors.duration ? 'border-red-500' : ''}`}
                  placeholder="0"
                />
                {errors.duration && <p className="text-red-500 text-xs mt-1.5">{errors.duration}</p>}
              </div>
            </div>

            {/* Checkbox - only meaningful once the service exists */}
            {isEdit && (
              <label className="form-checkbox">
                <input
                  type="checkbox"
                  checked={form.active}
                  onChange={(e) => handleChange('active', e.target.checked)}
                />
                <span>Service Active</span>
              </label>
            )}

            <div className="flex justify-end gap-3 pt-2">
              <button type="button" onClick={handleCancel} className="btn btn-outline">
                CANCEL
              </button>
              <button type="submit" className="btn btn-primary" disabled={submitting}>
                {submitting ? 'SAVING...' : isEdit ? 'SAVE SERVICE' : 'ADD SERVICE'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}
