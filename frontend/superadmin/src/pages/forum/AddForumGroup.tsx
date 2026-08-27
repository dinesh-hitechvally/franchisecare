import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { forumGroupsApi } from '../../api/services'

interface ForumGroupForm {
  groupName: string
  groupDescription: string
  permissions: string
  makeGroupActive: boolean
}

const initialForm: ForumGroupForm = {
  groupName: '',
  groupDescription: '',
  permissions: '',
  makeGroupActive: false
}

const permissionOptions = [
  { id: 'Full Access', name: 'Full Access' },
  { id: 'Moderate', name: 'Moderate' },
  { id: 'Read/Write', name: 'Read/Write' },
  { id: 'Read Only', name: 'Read Only' },
]

function extractErrorMessage(err: any, fallback: string): string {
  const errors = err?.response?.data?.errors
  if (errors) {
    const first = Object.values(errors)[0]
    if (Array.isArray(first) && first.length > 0) return String(first[0])
  }
  return err?.response?.data?.message ?? fallback
}

export function AddForumGroup() {
  const navigate = useNavigate()
  const { id } = useParams<{ id: string }>()
  const isEdit = Boolean(id)
  const [form, setForm] = useState<ForumGroupForm>(initialForm)
  const [errors, setErrors] = useState<Partial<Record<keyof ForumGroupForm, string>>>({})
  const [submitting, setSubmitting] = useState(false)

  const { data: group, isLoading } = useQuery({
    queryKey: ['forum-groups', id],
    queryFn: () => forumGroupsApi.get(Number(id)),
    enabled: isEdit,
  })

  useEffect(() => {
    if (group) {
      setForm({
        groupName: group.name,
        groupDescription: group.description ?? '',
        permissions: group.permissions ?? '',
        makeGroupActive: group.status === 'ACTIVE',
      })
    }
  }, [group])

  const handleChange = (field: keyof ForumGroupForm, value: string | boolean) => {
    setForm(prev => ({ ...prev, [field]: value }))
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: undefined }))
    }
  }

  const validate = (): boolean => {
    const newErrors: Partial<Record<keyof ForumGroupForm, string>> = {}

    if (!form.groupName.trim()) {
      newErrors.groupName = 'Group Name is required'
    }
    if (!form.groupDescription.trim()) {
      newErrors.groupDescription = 'Group Description is required'
    }
    if (!form.permissions) {
      newErrors.permissions = 'Permissions is required'
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
        await forumGroupsApi.update(Number(id), {
          name: form.groupName,
          description: form.groupDescription,
          permissions: form.permissions,
          status: form.makeGroupActive ? 'ACTIVE' : 'INACTIVE',
        })
        toast.success('Forum group updated successfully!')
      } else {
        await forumGroupsApi.create({
          name: form.groupName,
          description: form.groupDescription,
          permissions: form.permissions,
        })
        toast.success('Forum group added successfully!')
      }
      navigate('/forum/list-groups')
    } catch (err: any) {
      toast.error(extractErrorMessage(err, isEdit ? 'Failed to update group' : 'Failed to add group'))
    } finally {
      setSubmitting(false)
    }
  }

  const handleCancel = () => {
    navigate('/forum/list-groups')
  }

  if (isEdit && isLoading) {
    return (
      <div className="page-content">
        <h1 className="page-title">Edit Forum Group</h1>
        <div className="card p-8 text-center text-gray-400">Loading group...</div>
      </div>
    )
  }

  return (
    <div className="page-content">
      <h1 className="page-title">{isEdit ? 'Edit Forum Group' : 'Add Forum Group'}</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">Group Details</h2>
        </div>
        <div className="card-body p-6">
          <form onSubmit={handleSubmit}>
            <div className="space-y-4">
              <div>
                <label className="form-label">
                  Group Name <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={form.groupName}
                  onChange={(e) => handleChange('groupName', e.target.value)}
                  className={`form-input ${errors.groupName ? 'border-red-500' : ''}`}
                  placeholder="Enter group name"
                />
                {errors.groupName && <p className="text-red-500 text-xs mt-1">{errors.groupName}</p>}
              </div>

              <div>
                <label className="form-label">
                  Group Description <span className="text-red-500">*</span>
                </label>
                <textarea
                  value={form.groupDescription}
                  onChange={(e) => handleChange('groupDescription', e.target.value)}
                  rows={4}
                  className={`form-input form-textarea ${errors.groupDescription ? 'border-red-500' : ''}`}
                  placeholder="Enter group description"
                />
                {errors.groupDescription && <p className="text-red-500 text-xs mt-1">{errors.groupDescription}</p>}
              </div>

              <div>
                <label className="form-label">
                  Permissions <span className="text-red-500">*</span>
                </label>
                <select
                  value={form.permissions}
                  onChange={(e) => handleChange('permissions', e.target.value)}
                  className={`form-input ${errors.permissions ? 'border-red-500' : ''}`}
                >
                  <option value="">Select permissions</option>
                  {permissionOptions.map(perm => (
                    <option key={perm.id} value={perm.id}>{perm.name}</option>
                  ))}
                </select>
                {errors.permissions && <p className="text-red-500 text-xs mt-1">{errors.permissions}</p>}
              </div>

              {/* Checkbox - only meaningful once the group exists */}
              {isEdit && (
                <label className="form-checkbox">
                  <input
                    type="checkbox"
                    checked={form.makeGroupActive}
                    onChange={(e) => handleChange('makeGroupActive', e.target.checked)}
                  />
                  <span>Make Group Active</span>
                </label>
              )}
            </div>

            <div className="flex justify-end gap-3 mt-6">
              <button type="button" className="btn btn-outline" onClick={handleCancel}>
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
