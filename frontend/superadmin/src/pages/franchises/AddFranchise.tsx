import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { AxiosError } from 'axios'
import toast from 'react-hot-toast'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { franchisesApi } from '../../api/services'
import type { Franchise } from '../../types'

interface FranchiseFormState {
  name: string
  code: string
  owner_name: string
  email: string
  phone: string
  mobile: string
  address: string
  suburb: string
  state: string
  postcode: string
  abn: string
  franchise_fee: string
  royalty_percentage: string
  marketing_fee: string
  start_date: string
  end_date: string
  contract_length: string
  territory: string
  notes: string
}

const emptyForm: FranchiseFormState = {
  name: '',
  code: '',
  owner_name: '',
  email: '',
  phone: '',
  mobile: '',
  address: '',
  suburb: '',
  state: '',
  postcode: '',
  abn: '',
  franchise_fee: '',
  royalty_percentage: '',
  marketing_fee: '',
  start_date: '',
  end_date: '',
  contract_length: '',
  territory: '',
  notes: '',
}

type ValidationErrors = Partial<Record<keyof FranchiseFormState, string[]>>

interface ApiErrorResponse {
  message?: string
  errors?: Record<string, string[]>
}

function getApiErrorMessage(err: unknown): string {
  const axiosErr = err as AxiosError<ApiErrorResponse>
  return axiosErr.response?.data?.message || 'Something went wrong. Please try again.'
}

function getValidationErrors(err: unknown): Record<string, string[]> | null {
  const axiosErr = err as AxiosError<ApiErrorResponse>
  if (axiosErr.response?.status === 422 && axiosErr.response.data?.errors) {
    return axiosErr.response.data.errors
  }
  return null
}

interface FieldProps {
  label: string
  value: string
  onChange: (value: string) => void
  type?: string
  required?: boolean
  step?: string
  placeholder?: string
  error?: string[]
}

function FormField({ label, value, onChange, type = 'text', required, step, placeholder, error }: FieldProps) {
  return (
    <div>
      <label className="form-label">
        {label} {required && <span className="text-red-500">*</span>}
      </label>
      <input
        type={type}
        step={step}
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
      <div className="grid grid-cols-3 gap-4">{children}</div>
    </div>
  )
}

const statusOptions: Franchise['status'][] = ['active', 'inactive', 'suspended', 'terminated']

export function AddFranchise() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { id } = useParams<{ id: string }>()
  const isEditMode = !!id
  const franchiseId = Number(id)

  const [form, setForm] = useState<FranchiseFormState>(emptyForm)
  const [errors, setErrors] = useState<ValidationErrors>({})
  const [statusValue, setStatusValue] = useState<Franchise['status']>('active')
  const [statusReason, setStatusReason] = useState('')
  const [historyPage, setHistoryPage] = useState(1)

  const { data: franchise, isLoading: isFranchiseLoading } = useQuery({
    queryKey: ['franchise', franchiseId],
    queryFn: () => franchisesApi.get(franchiseId),
    enabled: isEditMode,
  })

  const { data: historyData, isLoading: isHistoryLoading } = useQuery({
    queryKey: ['franchise-history', franchiseId, historyPage],
    queryFn: () => franchisesApi.history(franchiseId, historyPage),
    enabled: isEditMode,
    placeholderData: (prev) => prev,
  })

  useEffect(() => {
    if (!franchise) return
    setForm({
      name: franchise.name ?? '',
      code: franchise.code ?? '',
      owner_name: franchise.owner_name ?? '',
      email: franchise.email ?? '',
      phone: franchise.phone ?? '',
      mobile: franchise.mobile ?? '',
      address: franchise.address ?? '',
      suburb: franchise.suburb ?? '',
      state: franchise.state ?? '',
      postcode: franchise.postcode ?? '',
      abn: franchise.abn ?? '',
      franchise_fee: franchise.franchise_fee != null ? String(franchise.franchise_fee) : '',
      royalty_percentage: franchise.royalty_percentage != null ? String(franchise.royalty_percentage) : '',
      marketing_fee: franchise.marketing_fee != null ? String(franchise.marketing_fee) : '',
      start_date: franchise.start_date ? franchise.start_date.slice(0, 10) : '',
      end_date: franchise.end_date ? franchise.end_date.slice(0, 10) : '',
      contract_length: franchise.contract_length != null ? String(franchise.contract_length) : '',
      territory: franchise.territory ?? '',
      notes: franchise.notes ?? '',
    })
    setStatusValue(franchise.status)
  }, [franchise])

  const handleChange = (field: keyof FranchiseFormState, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }))
    if (errors[field]) {
      setErrors((prev) => ({ ...prev, [field]: undefined }))
    }
  }

  const buildPayload = (): Partial<Franchise> => {
    const str = (v: string) => (v === '' ? undefined : v)
    const num = (v: string) => (v === '' ? undefined : Number(v))
    const int = (v: string) => (v === '' ? undefined : parseInt(v, 10))
    return {
      name: form.name,
      code: form.code,
      owner_name: form.owner_name,
      email: form.email,
      phone: str(form.phone),
      mobile: str(form.mobile),
      address: str(form.address),
      suburb: str(form.suburb),
      state: str(form.state),
      postcode: str(form.postcode),
      abn: str(form.abn),
      franchise_fee: num(form.franchise_fee),
      royalty_percentage: num(form.royalty_percentage),
      marketing_fee: num(form.marketing_fee),
      start_date: str(form.start_date),
      end_date: str(form.end_date),
      contract_length: int(form.contract_length),
      territory: str(form.territory),
      notes: str(form.notes),
    }
  }

  const createMutation = useMutation({
    mutationFn: (payload: Partial<Franchise>) => franchisesApi.create(payload),
    onSuccess: () => {
      toast.success('Franchise created successfully')
      queryClient.invalidateQueries({ queryKey: ['franchises'] })
      navigate('/franchises/list')
    },
    onError: (err) => {
      const validation = getValidationErrors(err)
      if (validation) {
        setErrors(validation as ValidationErrors)
        toast.error('Please fix the highlighted errors')
      } else {
        toast.error(getApiErrorMessage(err))
      }
    },
  })

  const updateMutation = useMutation({
    mutationFn: (payload: Partial<Franchise>) => franchisesApi.update(franchiseId, payload),
    onSuccess: () => {
      toast.success('Franchise updated successfully')
      queryClient.invalidateQueries({ queryKey: ['franchises'] })
      queryClient.invalidateQueries({ queryKey: ['franchise', franchiseId] })
    },
    onError: (err) => {
      const validation = getValidationErrors(err)
      if (validation) {
        setErrors(validation as ValidationErrors)
        toast.error('Please fix the highlighted errors')
      } else {
        toast.error(getApiErrorMessage(err))
      }
    },
  })

  const statusMutation = useMutation({
    mutationFn: () => franchisesApi.updateStatus(franchiseId, statusValue, statusReason || undefined),
    onSuccess: () => {
      toast.success('Status updated successfully')
      setStatusReason('')
      queryClient.invalidateQueries({ queryKey: ['franchise', franchiseId] })
      queryClient.invalidateQueries({ queryKey: ['franchises'] })
      queryClient.invalidateQueries({ queryKey: ['franchise-history', franchiseId] })
    },
    onError: (err) => toast.error(getApiErrorMessage(err)),
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setErrors({})
    const payload = buildPayload()
    if (isEditMode) {
      updateMutation.mutate(payload)
    } else {
      createMutation.mutate(payload)
    }
  }

  const isSaving = createMutation.isPending || updateMutation.isPending

  if (isEditMode && isFranchiseLoading) {
    return (
      <div className="page-content">
        <h1 className="page-title">Edit Franchise</h1>
        <div className="card p-8 text-center text-gray-400">Loading franchise...</div>
      </div>
    )
  }

  return (
    <div className="page-content">
      <h1 className="page-title">{isEditMode ? `Edit Franchise${franchise ? ` – ${franchise.name}` : ''}` : 'Add Franchise'}</h1>

      {isEditMode && (
        <div className="card mb-6">
          <div className="card-header">
            <h2 className="card-title">Status</h2>
          </div>
          <div className="card-body p-6">
            <div className="grid grid-cols-3 gap-4 items-end">
              <div>
                <label className="form-label">Franchise Status</label>
                <select
                  value={statusValue}
                  onChange={(e) => setStatusValue(e.target.value as Franchise['status'])}
                  className="form-input"
                >
                  {statusOptions.map((option) => (
                    <option key={option} value={option}>
                      {option.charAt(0).toUpperCase() + option.slice(1)}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="form-label">Reason (optional)</label>
                <input
                  type="text"
                  value={statusReason}
                  onChange={(e) => setStatusReason(e.target.value)}
                  placeholder="Reason for status change"
                  className="form-input"
                />
              </div>
              <div>
                <button
                  type="button"
                  className="btn btn-primary"
                  disabled={statusMutation.isPending || !franchise || statusValue === franchise.status}
                  onClick={() => statusMutation.mutate()}
                >
                  {statusMutation.isPending ? 'UPDATING...' : 'UPDATE STATUS'}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">Franchise Details</h2>
        </div>
        <div className="card-body p-6">
          <form onSubmit={handleSubmit}>
            <Section title="Basic Info">
              <FormField label="Franchise Name" required value={form.name} onChange={(v) => handleChange('name', v)} error={errors.name} />
              <FormField label="Franchise Code" required value={form.code} onChange={(v) => handleChange('code', v)} error={errors.code} />
              <FormField label="Owner Name" required value={form.owner_name} onChange={(v) => handleChange('owner_name', v)} error={errors.owner_name} />
              <FormField label="Territory" value={form.territory} onChange={(v) => handleChange('territory', v)} error={errors.territory} />
            </Section>

            <Section title="Contact">
              <FormField label="Email" required type="email" value={form.email} onChange={(v) => handleChange('email', v)} error={errors.email} />
              <FormField label="Phone" value={form.phone} onChange={(v) => handleChange('phone', v)} error={errors.phone} />
              <FormField label="Mobile" value={form.mobile} onChange={(v) => handleChange('mobile', v)} error={errors.mobile} />
            </Section>

            <Section title="Address">
              <div className="col-span-3">
                <FormField label="Address" value={form.address} onChange={(v) => handleChange('address', v)} error={errors.address} />
              </div>
              <FormField label="Suburb" value={form.suburb} onChange={(v) => handleChange('suburb', v)} error={errors.suburb} />
              <FormField label="State" value={form.state} onChange={(v) => handleChange('state', v)} error={errors.state} />
              <FormField label="Postcode" value={form.postcode} onChange={(v) => handleChange('postcode', v)} error={errors.postcode} />
            </Section>

            <Section title="Financial Terms">
              <FormField label="ABN" value={form.abn} onChange={(v) => handleChange('abn', v)} error={errors.abn} />
              <FormField
                label="Franchise Fee"
                type="number"
                step="0.01"
                value={form.franchise_fee}
                onChange={(v) => handleChange('franchise_fee', v)}
                error={errors.franchise_fee}
              />
              <FormField
                label="Royalty Percentage"
                type="number"
                step="0.01"
                value={form.royalty_percentage}
                onChange={(v) => handleChange('royalty_percentage', v)}
                error={errors.royalty_percentage}
              />
              <FormField
                label="Marketing Fee"
                type="number"
                step="0.01"
                value={form.marketing_fee}
                onChange={(v) => handleChange('marketing_fee', v)}
                error={errors.marketing_fee}
              />
            </Section>

            <Section title="Contract Dates">
              <FormField label="Start Date" type="date" value={form.start_date} onChange={(v) => handleChange('start_date', v)} error={errors.start_date} />
              <FormField label="End Date" type="date" value={form.end_date} onChange={(v) => handleChange('end_date', v)} error={errors.end_date} />
              <FormField
                label="Contract Length (months)"
                type="number"
                value={form.contract_length}
                onChange={(v) => handleChange('contract_length', v)}
                error={errors.contract_length}
              />
            </Section>

            <div className="mb-2">
              <h3 className="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4 pb-2 border-b border-gray-200">
                Notes
              </h3>
              <textarea
                value={form.notes}
                onChange={(e) => handleChange('notes', e.target.value)}
                className="form-input form-textarea w-full"
                rows={4}
                placeholder="Internal notes about this franchise..."
              />
              {errors.notes && <p className="text-red-500 text-xs mt-1">{errors.notes.join(', ')}</p>}
            </div>

            <div className="flex justify-end gap-3 mt-6">
              <button type="button" className="btn btn-outline" onClick={() => navigate('/franchises/list')}>
                CANCEL
              </button>
              <button type="submit" className="btn btn-primary" disabled={isSaving}>
                {isSaving ? 'SAVING...' : isEditMode ? 'SAVE CHANGES' : 'ADD FRANCHISE'}
              </button>
            </div>
          </form>
        </div>
      </div>

      {isEditMode && (
        <div className="card mt-6">
          <div className="card-header">
            <h2 className="card-title">History</h2>
          </div>
          <div className="card-body p-0">
            <table className="table">
              <thead>
                <tr>
                  <th>Action</th>
                  <th>User</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                {isHistoryLoading ? (
                  <tr>
                    <td colSpan={3} className="text-center py-6 text-gray-400">
                      Loading history...
                    </td>
                  </tr>
                ) : !historyData || historyData.data.length === 0 ? (
                  <tr>
                    <td colSpan={3} className="text-center py-6 text-gray-400">
                      No history recorded.
                    </td>
                  </tr>
                ) : (
                  historyData.data.map((entry) => (
                    <tr key={entry.id}>
                      <td className="capitalize">{entry.action.replace(/_/g, ' ')}</td>
                      <td>{entry.user?.name ?? 'System'}</td>
                      <td>{new Date(entry.created_at).toLocaleString()}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
          {historyData && historyData.last_page > 1 && (
            <div className="card-footer flex items-center justify-end gap-4 py-3 px-6">
              <span className="text-sm text-gray-600">
                Page {historyData.current_page} of {historyData.last_page}
              </span>
              <div className="flex gap-1">
                <button
                  className="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
                  onClick={() => setHistoryPage((p) => Math.max(1, p - 1))}
                  disabled={historyPage === 1}
                >
                  <ChevronLeft size={18} />
                </button>
                <button
                  className="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
                  onClick={() => setHistoryPage((p) => Math.min(historyData.last_page, p + 1))}
                  disabled={historyPage >= historyData.last_page}
                >
                  <ChevronRight size={18} />
                </button>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  )
}
