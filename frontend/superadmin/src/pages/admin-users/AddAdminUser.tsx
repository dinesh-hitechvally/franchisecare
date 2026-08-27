import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { adminUsersApi } from '../../api/services'
import type { AdminUser } from '../../types'

interface FormState {
  name: string
  email: string
  password: string
  phone: string
  role: AdminUser['role'] | ''
  status: AdminUser['status']
}

const initialForm: FormState = {
  name: '',
  email: '',
  password: '',
  phone: '',
  role: '',
  status: 'active',
}

export function AddAdminUser() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { id } = useParams<{ id: string }>()
  const isEdit = Boolean(id)

  const [form, setForm] = useState<FormState>(initialForm)
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({})

  const { data: existingUser, isLoading } = useQuery({
    queryKey: ['admin-user', id],
    queryFn: () => adminUsersApi.get(Number(id)),
    enabled: isEdit,
  })

  useEffect(() => {
    if (existingUser) {
      setForm({
        name: existingUser.name || '',
        email: existingUser.email || '',
        password: '',
        phone: existingUser.phone || '',
        role: existingUser.role,
        status: existingUser.status,
      })
    }
  }, [existingUser])

  const handleChange = (field: keyof FormState, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }))
    if (fieldErrors[field]) {
      setFieldErrors((prev) => {
        const next = { ...prev }
        delete next[field]
        return next
      })
    }
  }

  const handleMutationError = (error: any) => {
    if (error?.response?.status === 422 && error.response?.data?.errors) {
      setFieldErrors(error.response.data.errors)
      toast.error('Please fix the errors below')
    } else {
      toast.error(error?.response?.data?.message || 'Something went wrong')
    }
  }

  const createMutation = useMutation({
    mutationFn: () =>
      adminUsersApi.create({
        name: form.name,
        email: form.email,
        password: form.password,
        phone: form.phone || undefined,
        role: form.role,
      }),
    onSuccess: () => {
      toast.success('Admin user added successfully')
      queryClient.invalidateQueries({ queryKey: ['admin-users'] })
      navigate('/admin-users/list')
    },
    onError: handleMutationError,
  })

  const updateMutation = useMutation({
    mutationFn: () =>
      adminUsersApi.update(Number(id), {
        name: form.name,
        email: form.email,
        phone: form.phone || undefined,
        role: form.role as AdminUser['role'],
        status: form.status,
      }),
    onSuccess: () => {
      toast.success('Admin user updated successfully')
      queryClient.invalidateQueries({ queryKey: ['admin-users'] })
      queryClient.invalidateQueries({ queryKey: ['admin-user', id] })
      navigate('/admin-users/list')
    },
    onError: handleMutationError,
  })

  const isSaving = createMutation.isPending || updateMutation.isPending

  const validate = (): boolean => {
    const errors: Record<string, string[]> = {}
    if (!form.name.trim()) errors.name = ['Name is required']
    if (!form.email.trim()) errors.email = ['Email is required']
    if (!form.role) errors.role = ['Role is required']
    if (!isEdit && form.password.length < 8) {
      errors.password = ['Password must be at least 8 characters']
    }
    setFieldErrors(errors)
    return Object.keys(errors).length === 0
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (!validate()) {
      toast.error('Please fill in all required fields')
      return
    }

    if (isEdit) {
      updateMutation.mutate()
    } else {
      createMutation.mutate()
    }
  }

  const handleCancel = () => {
    navigate('/admin-users/list')
  }

  const FieldError = ({ name }: { name: string }) => {
    const message = fieldErrors[name]?.[0]
    if (!message) return null
    return <p className="text-red-500 text-xs mt-1">{message}</p>
  }

  if (isEdit && isLoading) {
    return (
      <div className="page-content">
        <h1 className="page-title">Edit Admin User</h1>
        <div className="card">
          <div className="card-body p-8 text-center text-gray-500">Loading admin user...</div>
        </div>
      </div>
    )
  }

  return (
    <div className="page-content">
      <h1 className="page-title">{isEdit ? 'Edit Admin User' : 'Add Admin User'}</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">{isEdit ? 'Edit Admin User Details' : 'New Admin User Details'}</h2>
        </div>
        <div className="card-body p-6">
          <form onSubmit={handleSubmit}>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-5">
              <div>
                <label className="form-label">
                  Name <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={form.name}
                  onChange={(e) => handleChange('name', e.target.value)}
                  placeholder="Full name"
                  className="form-input"
                />
                <FieldError name="name" />
              </div>
              <div>
                <label className="form-label">
                  Email <span className="text-red-500">*</span>
                </label>
                <input
                  type="email"
                  value={form.email}
                  onChange={(e) => handleChange('email', e.target.value)}
                  placeholder="name@example.com"
                  className="form-input"
                />
                <FieldError name="email" />
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-5">
              <div>
                <label className="form-label">Phone</label>
                <input
                  type="text"
                  value={form.phone}
                  onChange={(e) => handleChange('phone', e.target.value)}
                  placeholder="Phone number"
                  className="form-input"
                />
                <FieldError name="phone" />
              </div>
              <div>
                <label className="form-label">
                  Role <span className="text-red-500">*</span>
                </label>
                <select
                  value={form.role}
                  onChange={(e) => handleChange('role', e.target.value)}
                  className="form-input"
                >
                  <option value="">Select a role</option>
                  <option value="admin">Admin</option>
                  <option value="support">Support</option>
                  <option value="viewer">Viewer</option>
                </select>
                <FieldError name="role" />
              </div>
            </div>

            {!isEdit && (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-5">
                <div>
                  <label className="form-label">
                    Password <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="password"
                    value={form.password}
                    onChange={(e) => handleChange('password', e.target.value)}
                    placeholder="Minimum 8 characters"
                    className="form-input"
                  />
                  <FieldError name="password" />
                </div>
              </div>
            )}

            {isEdit && (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-5">
                <div>
                  <label className="form-label">Status</label>
                  <select
                    value={form.status}
                    onChange={(e) => handleChange('status', e.target.value)}
                    className="form-input"
                  >
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                  <FieldError name="status" />
                </div>
                <div className="flex items-end">
                  <p className="text-sm text-gray-500">
                    Password can only be changed by the user themselves, from their own account settings.
                  </p>
                </div>
              </div>
            )}

            <div className="flex justify-end gap-3 mt-8">
              <button type="button" onClick={handleCancel} className="btn btn-outline" disabled={isSaving}>
                CANCEL
              </button>
              <button type="submit" className="btn btn-primary" disabled={isSaving}>
                {isSaving ? 'SAVING...' : isEdit ? 'UPDATE ADMIN USER' : 'ADD ADMIN USER'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}
