import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { franchiseSuburbsApi, franchisesApi } from '../../api/services'

interface SuburbForm {
  suburbName: string
  postCode: string
  state: string
  franchiseId: string
  makeSuburbActive: boolean
}

const initialForm: SuburbForm = {
  suburbName: '',
  postCode: '',
  state: '',
  franchiseId: '',
  makeSuburbActive: true
}

const auStates = ['NSW', 'VIC', 'QLD', 'SA', 'WA', 'TAS', 'NT', 'ACT']

function extractErrorMessage(err: any, fallback: string): string {
  const errors = err?.response?.data?.errors
  if (errors) {
    const first = Object.values(errors)[0]
    if (Array.isArray(first) && first.length > 0) return String(first[0])
  }
  return err?.response?.data?.message ?? fallback
}

export function AddSuburb() {
  const navigate = useNavigate()
  const { id } = useParams<{ id: string }>()
  const isEdit = Boolean(id)
  const [form, setForm] = useState<SuburbForm>(initialForm)
  const [errors, setErrors] = useState<Partial<Record<keyof SuburbForm, string>>>({})
  const [submitting, setSubmitting] = useState(false)

  const { data: franchisesData } = useQuery({
    queryKey: ['franchises-picker'],
    queryFn: () => franchisesApi.list({ per_page: 200 }),
  })
  const franchises = franchisesData?.data ?? []

  const { data: suburb, isLoading } = useQuery({
    queryKey: ['franchise-suburbs', id],
    queryFn: () => franchiseSuburbsApi.get(Number(id)),
    enabled: isEdit,
  })

  useEffect(() => {
    if (suburb) {
      setForm({
        suburbName: suburb.suburb_name,
        postCode: suburb.postcode,
        state: suburb.state ?? '',
        franchiseId: suburb.franchise_id != null ? String(suburb.franchise_id) : '',
        makeSuburbActive: suburb.status === 'ACTIVE'
      })
    }
  }, [suburb])

  const handleChange = (field: keyof SuburbForm, value: string | boolean) => {
    setForm(prev => ({ ...prev, [field]: value }))
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: undefined }))
    }
  }

  const validate = (): boolean => {
    const newErrors: Partial<Record<keyof SuburbForm, string>> = {}

    if (!form.suburbName.trim()) {
      newErrors.suburbName = 'Suburb Name is required'
    }
    if (!form.postCode.trim()) {
      newErrors.postCode = 'Post Code is required'
    }
    if (!form.state.trim()) {
      newErrors.state = 'State is required'
    }
    if (!form.franchiseId) {
      newErrors.franchiseId = 'Company is required'
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
        await franchiseSuburbsApi.update(Number(id), {
          franchise_id: Number(form.franchiseId),
          suburb_name: form.suburbName,
          postcode: form.postCode,
          state: form.state,
          status: form.makeSuburbActive ? 'ACTIVE' : 'INACTIVE',
        })
        toast.success('Suburb updated successfully!')
      } else {
        await franchiseSuburbsApi.create({
          franchise_id: Number(form.franchiseId),
          suburb_name: form.suburbName,
          postcode: form.postCode,
          state: form.state,
        })
        toast.success('Suburb added successfully!')
      }
      navigate('/suburb/list')
    } catch (err: any) {
      toast.error(extractErrorMessage(err, isEdit ? 'Failed to update suburb' : 'Failed to add suburb'))
    } finally {
      setSubmitting(false)
    }
  }

  const handleCancel = () => {
    navigate('/suburb/list')
  }

  if (isEdit && isLoading) {
    return (
      <div className="page-content">
        <h1 className="page-title">Edit Suburb</h1>
        <div className="card p-8 text-center text-gray-400">Loading suburb...</div>
      </div>
    )
  }

  return (
    <div className="page-content">
      <h1 className="page-title">{isEdit ? 'Edit Suburb' : 'Add Suburb'}</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">Suburb Details</h2>
        </div>
        <div className="card-body p-6">
          <form onSubmit={handleSubmit}>
            <div className="grid grid-cols-3 gap-4">
              {/* Suburb Name Field */}
              <div>
                <label className="form-label">
                  Suburb Name <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={form.suburbName}
                  onChange={(e) => handleChange('suburbName', e.target.value)}
                  className={`form-input ${errors.suburbName ? 'border-red-500' : ''}`}
                />
                {errors.suburbName && <p className="text-red-500 text-xs mt-1">{errors.suburbName}</p>}
              </div>

              {/* Post Code Field */}
              <div>
                <label className="form-label">
                  Post Code <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={form.postCode}
                  onChange={(e) => handleChange('postCode', e.target.value)}
                  className={`form-input ${errors.postCode ? 'border-red-500' : ''}`}
                />
                {errors.postCode && <p className="text-red-500 text-xs mt-1">{errors.postCode}</p>}
              </div>

              {/* State Select */}
              <div>
                <label className="form-label">
                  State <span className="text-red-500">*</span>
                </label>
                <select
                  value={form.state}
                  onChange={(e) => handleChange('state', e.target.value)}
                  className={`form-input ${errors.state ? 'border-red-500' : ''}`}
                >
                  <option value="">Select a state</option>
                  {auStates.map(state => (
                    <option key={state} value={state}>{state}</option>
                  ))}
                </select>
                {errors.state && <p className="text-red-500 text-xs mt-1">{errors.state}</p>}
              </div>

              {/* Company (Franchise) Select */}
              <div className="col-span-3">
                <label className="form-label">
                  Company <span className="text-red-500">*</span>
                </label>
                <select
                  value={form.franchiseId}
                  onChange={(e) => handleChange('franchiseId', e.target.value)}
                  className={`form-input ${errors.franchiseId ? 'border-red-500' : ''}`}
                >
                  <option value="">Select a company</option>
                  {franchises.map(franchise => (
                    <option key={franchise.id} value={franchise.id}>{franchise.name}</option>
                  ))}
                </select>
                {errors.franchiseId && <p className="text-red-500 text-xs mt-1">{errors.franchiseId}</p>}
              </div>
            </div>

            {/* Checkbox - only meaningful once the suburb exists */}
            {isEdit && (
              <div className="mt-6">
                <label className="form-checkbox">
                  <input
                    type="checkbox"
                    checked={form.makeSuburbActive}
                    onChange={(e) => handleChange('makeSuburbActive', e.target.checked)}
                  />
                  <span>Make Suburb Active</span>
                </label>
              </div>
            )}

            <div className="flex justify-end gap-3 mt-6">
              <button type="button" onClick={handleCancel} className="btn btn-outline">
                CANCEL
              </button>
              <button type="submit" className="btn btn-primary" disabled={submitting}>
                {submitting ? 'SAVING...' : isEdit ? 'SAVE SUBURB' : 'ADD SUBURB'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}
