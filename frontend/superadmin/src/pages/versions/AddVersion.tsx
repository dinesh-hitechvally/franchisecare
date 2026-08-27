import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { isAxiosError } from 'axios'
import toast from 'react-hot-toast'
import { appVersionsApi } from '../../api/services'

interface FormState {
  version: string
  title: string
  description: string
  logout: boolean
  refresh: boolean
}

const initialForm: FormState = {
  version: '',
  title: '',
  description: '',
  logout: false,
  refresh: false,
}

type FormErrors = Partial<Record<'version' | 'title' | 'description', string>>

export function AddVersion() {
  const { id } = useParams<{ id: string }>()
  const isEdit = Boolean(id)
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const [formData, setFormData] = useState<FormState>(initialForm)
  const [errors, setErrors] = useState<FormErrors>({})

  const { data: existingVersion, isLoading } = useQuery({
    queryKey: ['app-versions', id],
    queryFn: () => appVersionsApi.get(Number(id)),
    enabled: isEdit,
  })

  useEffect(() => {
    if (existingVersion) {
      setFormData({
        version: existingVersion.version,
        title: existingVersion.title,
        description: existingVersion.description ?? '',
        logout: existingVersion.logout_required,
        refresh: existingVersion.refresh_required,
      })
    }
  }, [existingVersion])

  const applyServerErrors = (error: unknown) => {
    if (isAxiosError(error) && error.response?.status === 422) {
      const serverErrors = error.response.data?.errors as Record<string, string[]> | undefined
      if (serverErrors) {
        const mapped: FormErrors = {}
        Object.entries(serverErrors).forEach(([field, messages]) => {
          mapped[field as keyof FormErrors] = messages[0]
        })
        setErrors((prev) => ({ ...prev, ...mapped }))
        const firstMessage = Object.values(serverErrors)[0]?.[0]
        toast.error(firstMessage ?? 'Please correct the errors and try again')
      } else {
        toast.error('Please correct the errors and try again')
      }
    } else {
      toast.error(isEdit ? 'Failed to update version' : 'Failed to add version')
    }
  }

  const buildPayload = () => ({
    version: formData.version,
    title: formData.title,
    description: formData.description || undefined,
    logout_required: formData.logout,
    refresh_required: formData.refresh,
  })

  const createMutation = useMutation({
    mutationFn: () => appVersionsApi.create(buildPayload()),
    onSuccess: () => {
      toast.success('Version added successfully')
      queryClient.invalidateQueries({ queryKey: ['app-versions'] })
      navigate('/versions/list')
    },
    onError: applyServerErrors,
  })

  const updateMutation = useMutation({
    mutationFn: () => appVersionsApi.update(Number(id), buildPayload()),
    onSuccess: () => {
      toast.success('Version updated successfully')
      queryClient.invalidateQueries({ queryKey: ['app-versions'] })
      navigate('/versions/list')
    },
    onError: applyServerErrors,
  })

  const isSaving = createMutation.isPending || updateMutation.isPending

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setErrors({})

    if (isEdit) {
      updateMutation.mutate()
    } else {
      createMutation.mutate()
    }
  }

  if (isEdit && isLoading) {
    return (
      <div className="page-content">
        <h1 className="page-title">{isEdit ? 'Edit Version' : 'Add Version'}</h1>
        <div className="card">
          <div className="card-body p-8 text-center text-gray-400">Loading version...</div>
        </div>
      </div>
    )
  }

  return (
    <div className="page-content">
      <h1 className="page-title">{isEdit ? 'Edit Version' : 'Add Version'}</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">Version Details</h2>
        </div>
        <div className="card-body p-6">
          <form onSubmit={handleSubmit}>
            {/* Version Number and Title - Side by side */}
            <div className="grid grid-cols-2 gap-6 mb-6">
              <div>
                <label className="form-label">
                  Version Number <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={formData.version}
                  onChange={(e) => setFormData({ ...formData, version: e.target.value })}
                  placeholder="e.g. 1.2.0"
                  className="form-input"
                  required
                />
                {errors.version && <p className="text-red-500 text-xs mt-1">{errors.version}</p>}
              </div>
              <div>
                <label className="form-label">
                  Version Title <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  placeholder="e.g. Bug fixes and improvements"
                  className="form-input"
                  required
                />
                {errors.title && <p className="text-red-500 text-xs mt-1">{errors.title}</p>}
              </div>
            </div>

            {/* Version Description */}
            <div className="mb-6">
              <label className="form-label">Version Description</label>
              <textarea
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                placeholder="Describe what changed in this version..."
                rows={5}
                className="form-input form-textarea"
              />
              {errors.description && <p className="text-red-500 text-xs mt-1">{errors.description}</p>}
            </div>

            {/* Checkboxes - Side by side */}
            <div className="grid grid-cols-2 gap-6 mb-8">
              <label className="form-checkbox">
                <input
                  type="checkbox"
                  checked={formData.logout}
                  onChange={(e) => setFormData({ ...formData, logout: e.target.checked })}
                />
                <span>Logout/Login Required</span>
              </label>
              <label className="form-checkbox">
                <input
                  type="checkbox"
                  checked={formData.refresh}
                  onChange={(e) => setFormData({ ...formData, refresh: e.target.checked })}
                />
                <span>Refresh Required</span>
              </label>
            </div>

            {/* Action Buttons */}
            <div className="flex justify-end gap-3">
              <button type="button" onClick={() => navigate('/versions/list')} className="btn btn-outline">
                CANCEL
              </button>
              <button type="submit" disabled={isSaving} className="btn btn-primary">
                {isSaving ? 'SAVING...' : isEdit ? 'UPDATE VERSION' : 'ADD VERSION'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}
