import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { AxiosError } from 'axios'
import toast from 'react-hot-toast'
import { franchisesApi, franchiseUsersApi } from '../../api/services'
import type { Franchise } from '../../types'

interface FormState {
  companyName: string
  code: string
  abnNumber: string
  franchiseeType: '' | Exclude<Franchise['franchisee_type'], null>
  firstName: string
  lastName: string
  emailAddress: string
  password: string
  personalPhone: string
  mobile: string
  state: string
  address1: string
  suburb: string
  postCode: string
  serviceLocation: string
  hasIpad: boolean
}

const initialFormState: FormState = {
  companyName: '',
  code: '',
  abnNumber: '',
  franchiseeType: '',
  firstName: '',
  lastName: '',
  emailAddress: '',
  password: '',
  personalPhone: '',
  mobile: '',
  state: '',
  address1: '',
  suburb: '',
  postCode: '',
  serviceLocation: '',
  hasIpad: false,
}

type ValidationErrors = Record<string, string[]>

interface ApiErrorResponse {
  message?: string
  errors?: Record<string, string[]>
}

function getApiErrorMessage(err: unknown): string {
  const axiosErr = err as AxiosError<ApiErrorResponse>
  return axiosErr.response?.data?.message || 'Something went wrong. Please try again.'
}

function getValidationErrors(err: unknown): ValidationErrors | null {
  const axiosErr = err as AxiosError<ApiErrorResponse>
  if (axiosErr.response?.status === 422 && axiosErr.response.data?.errors) {
    return axiosErr.response.data.errors
  }
  return null
}

// Thrown when the franchise (company) was created/updated successfully but the
// owner login (FranchiseUser) step failed — the two are separate API calls, so
// we need to tell the admin the company record did save even though this failed.
class OwnerAccountError extends Error {
  franchiseId: number
  cause: unknown
  constructor(franchiseId: number, cause: unknown) {
    super('Franchise saved, but the owner login could not be saved')
    this.franchiseId = franchiseId
    this.cause = cause
  }
}

interface FieldProps {
  label: string
  value: string
  onChange: (value: string) => void
  type?: string
  required?: boolean
  placeholder?: string
  error?: string[]
}

function FormField({ label, value, onChange, type = 'text', required, placeholder, error }: FieldProps) {
  return (
    <div>
      <label className="form-label">
        {label} {required && <span className="text-red-500">*</span>}
      </label>
      <input
        type={type}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        className={`form-input ${error ? 'border-red-500' : ''}`}
      />
      {error && <p className="text-red-500 text-xs mt-1">{error.join(', ')}</p>}
    </div>
  )
}

interface SectionProps {
  title: string
  children: React.ReactNode
}

function Section({ title, children }: SectionProps) {
  return (
    <div className="mb-8">
      <h3 className="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4 pb-2 border-b border-gray-200">
        {title}
      </h3>
      <div className="grid grid-cols-2 gap-4">{children}</div>
    </div>
  )
}

const stateOptions = [
  { value: 'NSW', label: 'New South Wales' },
  { value: 'VIC', label: 'Victoria' },
  { value: 'QLD', label: 'Queensland' },
  { value: 'SA', label: 'South Australia' },
  { value: 'WA', label: 'Western Australia' },
  { value: 'TAS', label: 'Tasmania' },
  { value: 'NT', label: 'Northern Territory' },
  { value: 'ACT', label: 'Australian Capital Territory' },
]

export function AddMember() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { id } = useParams<{ id: string }>()
  const isEditMode = !!id
  const franchiseId = Number(id)

  const [form, setForm] = useState<FormState>(initialFormState)
  const [errors, setErrors] = useState<ValidationErrors>({})
  const [existingUserId, setExistingUserId] = useState<number | null>(null)

  const { data: franchise, isLoading: isFranchiseLoading } = useQuery({
    queryKey: ['franchise', franchiseId],
    queryFn: () => franchisesApi.get(franchiseId),
    enabled: isEditMode,
  })

  const { data: franchiseUsers } = useQuery({
    queryKey: ['franchise-users', franchiseId],
    queryFn: () => franchiseUsersApi.getByFranchise(franchiseId),
    enabled: isEditMode,
  })

  useEffect(() => {
    if (!franchise) return
    const ownerUser = franchiseUsers?.find((u) => u.role === 'OWNER') ?? franchiseUsers?.[0] ?? null
    setExistingUserId(ownerUser?.id ?? null)

    const nameParts = (franchise.owner_name || '').trim().split(/\s+/)
    const firstName = nameParts[0] ?? ''
    const lastName = nameParts.slice(1).join(' ')

    setForm({
      companyName: franchise.name ?? '',
      code: franchise.code ?? '',
      abnNumber: franchise.abn ?? '',
      franchiseeType: (franchise.franchisee_type as FormState['franchiseeType']) ?? '',
      firstName,
      lastName,
      emailAddress: ownerUser?.email ?? franchise.email ?? '',
      password: '',
      personalPhone: ownerUser?.phone ?? franchise.phone ?? '',
      mobile: franchise.mobile ?? '',
      state: franchise.state ?? '',
      address1: franchise.address ?? '',
      suburb: franchise.suburb ?? '',
      postCode: franchise.postcode ?? '',
      serviceLocation: franchise.territory ?? '',
      hasIpad: franchise.has_ipad ?? false,
    })
  }, [franchise, franchiseUsers])

  const handleChange = (field: keyof FormState, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }))
    if (errors[field]) {
      setErrors((prev) => ({ ...prev, [field]: [] }))
    }
  }

  const needsOwnerCreation = isEditMode && existingUserId === null

  const buildFranchisePayload = (): Partial<Franchise> => {
    const str = (v: string) => (v === '' ? undefined : v)
    return {
      name: form.companyName,
      code: form.code,
      owner_name: `${form.firstName} ${form.lastName}`.trim(),
      email: form.emailAddress,
      phone: str(form.personalPhone),
      mobile: str(form.mobile),
      address: str(form.address1),
      suburb: str(form.suburb),
      state: str(form.state),
      postcode: str(form.postCode),
      abn: str(form.abnNumber),
      territory: str(form.serviceLocation),
      franchisee_type: form.franchiseeType ? form.franchiseeType : null,
      has_ipad: form.hasIpad,
    }
  }

  const applyErrors = (err: unknown) => {
    if (err instanceof OwnerAccountError) {
      toast.error(
        'The company account was saved, but the owner login could not be saved. Fix the details below and save again.'
      )
      const validation = getValidationErrors(err.cause)
      if (validation) setErrors(validation)
      return
    }
    const validation = getValidationErrors(err)
    if (validation) {
      setErrors(validation)
      toast.error('Please fix the highlighted errors')
    } else {
      toast.error(getApiErrorMessage(err))
    }
  }

  const createMutation = useMutation({
    mutationFn: async () => {
      const newFranchise = await franchisesApi.create(buildFranchisePayload())
      try {
        await franchiseUsersApi.create({
          franchise_id: newFranchise.id,
          name: `${form.firstName} ${form.lastName}`.trim(),
          email: form.emailAddress,
          password: form.password,
          phone: form.personalPhone || undefined,
          role: 'OWNER',
        })
      } catch (err) {
        throw new OwnerAccountError(newFranchise.id, err)
      }
      return newFranchise
    },
    onSuccess: () => {
      toast.success('Member added successfully')
      queryClient.invalidateQueries({ queryKey: ['franchises'] })
      navigate('/members/list')
    },
    onError: (err) => {
      if (err instanceof OwnerAccountError) {
        queryClient.invalidateQueries({ queryKey: ['franchises'] })
        applyErrors(err)
        navigate(`/members/edit/${err.franchiseId}`)
        return
      }
      applyErrors(err)
    },
  })

  const updateMutation = useMutation({
    mutationFn: async () => {
      const updated = await franchisesApi.update(franchiseId, buildFranchisePayload())
      if (existingUserId) {
        await franchiseUsersApi.update(existingUserId, {
          name: `${form.firstName} ${form.lastName}`.trim(),
          email: form.emailAddress,
          phone: form.personalPhone || undefined,
        })
      } else if (needsOwnerCreation && form.password) {
        await franchiseUsersApi.create({
          franchise_id: franchiseId,
          name: `${form.firstName} ${form.lastName}`.trim(),
          email: form.emailAddress,
          password: form.password,
          phone: form.personalPhone || undefined,
          role: 'OWNER',
        })
      }
      return updated
    },
    onSuccess: () => {
      toast.success('Member updated successfully')
      queryClient.invalidateQueries({ queryKey: ['franchises'] })
      queryClient.invalidateQueries({ queryKey: ['franchise', franchiseId] })
      queryClient.invalidateQueries({ queryKey: ['franchise-users', franchiseId] })
      setForm((prev) => ({ ...prev, password: '' }))
    },
    onError: applyErrors,
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setErrors({})
    if (isEditMode) {
      updateMutation.mutate()
    } else {
      createMutation.mutate()
    }
  }

  const isSaving = createMutation.isPending || updateMutation.isPending
  const showPasswordField = !isEditMode || needsOwnerCreation

  if (isEditMode && isFranchiseLoading) {
    return (
      <div className="page-content">
        <h1 className="page-title">Edit Member</h1>
        <div className="card p-8 text-center text-gray-400">Loading member...</div>
      </div>
    )
  }

  return (
    <div className="page-content">
      <h1 className="page-title">{isEditMode ? 'Edit Member Company' : 'Add Member Company'}</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">{isEditMode ? 'Member Details' : 'New Member Details'}</h2>
        </div>
        <div className="card-body p-6">
          <form onSubmit={handleSubmit}>
            <Section title="Company Details">
              <FormField label="Company Name" required value={form.companyName} onChange={(v) => handleChange('companyName', v)} error={errors.name} />
              <FormField label="Franchise Code" required value={form.code} onChange={(v) => handleChange('code', v)} error={errors.code} />
              <FormField label="ABN Number" value={form.abnNumber} onChange={(v) => handleChange('abnNumber', v)} error={errors.abn} />
              <div>
                <label className="form-label">Member Type</label>
                <select
                  value={form.franchiseeType}
                  onChange={(e) => handleChange('franchiseeType', e.target.value)}
                  className={`form-input ${errors.franchisee_type ? 'border-red-500' : ''}`}
                >
                  <option value=""></option>
                  <option value="MASTER_FRANCHISEE">Master Franchisee</option>
                  <option value="FRANCHISEE">Franchisee</option>
                  <option value="FRANCHISOR">Franchisor</option>
                </select>
                {errors.franchisee_type && <p className="text-red-500 text-xs mt-1">{errors.franchisee_type.join(', ')}</p>}
              </div>
            </Section>

            <Section title="Owner / Login Details">
              <FormField label="First Name" required value={form.firstName} onChange={(v) => handleChange('firstName', v)} error={errors.owner_name} />
              <FormField label="Last Name" required value={form.lastName} onChange={(v) => handleChange('lastName', v)} />
              <FormField label="Email Address" required type="email" value={form.emailAddress} onChange={(v) => handleChange('emailAddress', v)} error={errors.email} />
              {showPasswordField && (
                <FormField
                  label="Password"
                  required={!isEditMode}
                  type="password"
                  value={form.password}
                  onChange={(v) => handleChange('password', v)}
                  placeholder="Minimum 8 characters"
                  error={errors.password}
                />
              )}
            </Section>

            {isEditMode && !showPasswordField && (
              <p className="text-sm text-gray-500 -mt-6 mb-8">
                Password can only be changed by the owner themselves, from their own account settings.
              </p>
            )}
            {isEditMode && needsOwnerCreation && (
              <p className="text-sm text-gray-500 -mt-6 mb-8">
                No owner login exists for this franchise yet. Enter a password above to create one when you save.
              </p>
            )}

            <Section title="Contact">
              <FormField label="Personal Phone" value={form.personalPhone} onChange={(v) => handleChange('personalPhone', v)} error={errors.phone} />
              <FormField label="Mobile" value={form.mobile} onChange={(v) => handleChange('mobile', v)} error={errors.mobile} />
            </Section>

            <Section title="Address">
              <div className="col-span-2">
                <FormField label="Address" value={form.address1} onChange={(v) => handleChange('address1', v)} error={errors.address} />
              </div>
              <FormField label="Suburb" value={form.suburb} onChange={(v) => handleChange('suburb', v)} error={errors.suburb} />
              <FormField label="Post Code" value={form.postCode} onChange={(v) => handleChange('postCode', v)} error={errors.postcode} />
              <div>
                <label className="form-label">State</label>
                <select
                  value={form.state}
                  onChange={(e) => handleChange('state', e.target.value)}
                  className={`form-input ${errors.state ? 'border-red-500' : ''}`}
                >
                  <option value=""></option>
                  {stateOptions.map((s) => (
                    <option key={s.value} value={s.value}>
                      {s.label}
                    </option>
                  ))}
                </select>
                {errors.state && <p className="text-red-500 text-xs mt-1">{errors.state.join(', ')}</p>}
              </div>
              <FormField label="Service Location" value={form.serviceLocation} onChange={(v) => handleChange('serviceLocation', v)} error={errors.territory} />
            </Section>

            <div className="mb-8">
              <h3 className="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4 pb-2 border-b border-gray-200">
                Options
              </h3>
              <label className="form-checkbox">
                <input
                  type="checkbox"
                  checked={form.hasIpad}
                  onChange={(e) => setForm((prev) => ({ ...prev, hasIpad: e.target.checked }))}
                />
                <span>Has iPad</span>
              </label>
            </div>

            <div className="flex justify-end gap-3 mt-6">
              <button type="button" onClick={() => navigate('/members/list')} className="btn btn-outline">
                CANCEL
              </button>
              <button type="submit" className="btn btn-primary px-6" disabled={isSaving}>
                {isSaving ? 'SAVING...' : isEditMode ? 'SAVE CHANGES' : 'SUBMIT'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}
