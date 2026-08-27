import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { serviceCategoriesApi } from '../../api/services'

interface GroupForm {
  name: string
  description: string
  icon: string
  active: boolean
}

const initialForm: GroupForm = {
  name: '',
  description: '',
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

export function AddGroup() {
  const navigate = useNavigate()
  const { id } = useParams<{ id: string }>()
  const isEdit = Boolean(id)
  const [form, setForm] = useState<GroupForm>(initialForm)
  const [errors, setErrors] = useState<Partial<Record<keyof GroupForm, string>>>({})
  const [submitting, setSubmitting] = useState(false)

  const { data: group, isLoading } = useQuery({
    queryKey: ['service-categories', id],
    queryFn: () => serviceCategoriesApi.get(Number(id)),
    enabled: isEdit,
  })

  useEffect(() => {
    if (group) {
      setForm({
        name: group.name,
        description: group.description ?? '',
        icon: group.icon ?? '',
        active: group.status === 'active'
      })
    }
  }, [group])

  const handleChange = (field: keyof GroupForm, value: string | boolean) => {
    setForm(prev => ({ ...prev, [field]: value }))
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: undefined }))
    }
  }

  const validate = (): boolean => {
    const newErrors: Partial<Record<keyof GroupForm, string>> = {}

    if (!form.name.trim()) {
      newErrors.name = 'Service Group Name is required'
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
        await serviceCategoriesApi.update(Number(id), {
          name: form.name,
          description: form.description || null,
          icon: form.icon || null,
          status: form.active ? 'active' : 'inactive',
        })
        toast.success('Group updated successfully!')
      } else {
        await serviceCategoriesApi.create({
          name: form.name,
          description: form.description || undefined,
          icon: form.icon || undefined,
        })
        toast.success('Group added successfully!')
      }
      navigate('/services/list-groups')
    } catch (err: any) {
      toast.error(extractErrorMessage(err, isEdit ? 'Failed to update group' : 'Failed to add group'))
    } finally {
      setSubmitting(false)
    }
  }

  const handleCancel = () => {
    navigate('/services/list-groups')
  }

  if (isEdit && isLoading) {
    return (
      <div className="page-content">
        <h1 className="page-title">Edit Group</h1>
        <div className="card p-8 text-center text-gray-400">Loading...</div>
      </div>
    )
  }

  return (
    <div className="page-content">
      <h1 className="page-title">{isEdit ? 'Edit Group' : 'Add Group'}</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">Group Details</h2>
        </div>
        <div className="card-body">
          <form onSubmit={handleSubmit} className="p-6 space-y-5">
            <div>
              <label className="form-label">
                Service Group Name <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={form.name}
                onChange={(e) => handleChange('name', e.target.value)}
                className={`form-input ${errors.name ? 'border-red-500' : ''}`}
                placeholder="Service group name"
              />
              {errors.name && <p className="text-red-500 text-xs mt-1.5">{errors.name}</p>}
            </div>

            <div>
              <label className="form-label">Service Group Description</label>
              <textarea
                value={form.description}
                onChange={(e) => handleChange('description', e.target.value)}
                rows={4}
                className="form-input form-textarea"
                placeholder="Service group description"
              />
              {errors.description && <p className="text-red-500 text-xs mt-1.5">{errors.description}</p>}
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

            {/* Checkbox - only meaningful once the group exists */}
            {isEdit && (
              <label className="form-checkbox">
                <input
                  type="checkbox"
                  checked={form.active}
                  onChange={(e) => handleChange('active', e.target.checked)}
                />
                <span>Group Active</span>
              </label>
            )}

            <div className="flex justify-end gap-3 pt-2">
              <button type="button" onClick={handleCancel} className="btn btn-outline">
                CANCEL
              </button>
              <button type="submit" className="btn btn-primary" disabled={submitting}>
                {submitting ? 'SAVING...' : isEdit ? 'SAVE GROUP' : 'ADD GROUP'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}
