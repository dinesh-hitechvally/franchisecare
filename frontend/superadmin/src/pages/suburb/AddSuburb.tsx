import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import toast from 'react-hot-toast'
import { MapPin, Hash, Building2, User } from 'lucide-react'

interface SuburbForm {
  suburbName: string
  postCode: string
  company: string
  member: string
  makeSuburbActive: boolean
}

const initialForm: SuburbForm = {
  suburbName: '',
  postCode: '',
  company: '',
  member: '',
  makeSuburbActive: false
}

const mockCompanies = [
  { id: 1, name: 'Blue Wheelers Pty Ltd' },
  { id: 2, name: 'RetailCare Pty Ltd' },
  { id: 3, name: 'Pet Care Services' },
]

const mockMembers = [
  { id: 1, name: 'Mate Support' },
  { id: 2, name: 'Dave Laming' },
  { id: 3, name: 'John Smith' },
]

export function AddSuburb() {
  const navigate = useNavigate()
  const [form, setForm] = useState<SuburbForm>(initialForm)
  const [errors, setErrors] = useState<Partial<SuburbForm>>({})

  const handleChange = (field: keyof SuburbForm, value: string | boolean) => {
    setForm(prev => ({ ...prev, [field]: value }))
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: undefined }))
    }
  }

  const validate = (): boolean => {
    const newErrors: Partial<SuburbForm> = {}
    
    if (!form.suburbName.trim()) {
      newErrors.suburbName = 'Suburb Name is required'
    }
    if (!form.postCode.trim()) {
      newErrors.postCode = 'Post Code is required'
    }

    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    
    if (!validate()) {
      toast.error('Please fill in all required fields')
      return
    }

    // Mock API call
    toast.success('Suburb added successfully!')
    navigate('/suburb/list')
  }

  const handleCancel = () => {
    navigate('/suburb/list')
  }

  return (
    <div className="page-content">
      <h1 className="page-title">Add Suburb</h1>

      <div className="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
          <form onSubmit={handleSubmit}>
            <div className="p-6 space-y-5">
              {/* Suburb Name Field */}
              <div className="relative">
                <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                  <MapPin size={18} />
                </div>
                <input
                  type="text"
                  id="suburbName"
                  value={form.suburbName}
                  onChange={(e) => handleChange('suburbName', e.target.value)}
                  className={`peer w-full pl-10 pr-4 pt-5 pb-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all ${
                    errors.suburbName ? 'border-red-500 bg-red-50' : 'border-gray-300 hover:border-gray-400'
                  }`}
                  placeholder=" "
                />
                <label 
                  htmlFor="suburbName"
                  className={`absolute left-10 transition-all duration-200 pointer-events-none
                    ${form.suburbName ? 'top-1.5 text-xs text-purple-600' : 'top-1/2 -translate-y-1/2 text-sm text-gray-500'}
                    peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-purple-600 peer-focus:translate-y-0`}
                >
                  Suburb Name <span className="text-red-500">*</span>
                </label>
                {errors.suburbName && (
                  <p className="text-red-500 text-xs mt-1.5 ml-1">{errors.suburbName}</p>
                )}
              </div>

              {/* Post Code Field */}
              <div className="relative">
                <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                  <Hash size={18} />
                </div>
                <input
                  type="text"
                  id="postCode"
                  value={form.postCode}
                  onChange={(e) => handleChange('postCode', e.target.value)}
                  className={`peer w-full pl-10 pr-4 pt-5 pb-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all ${
                    errors.postCode ? 'border-red-500 bg-red-50' : 'border-gray-300 hover:border-gray-400'
                  }`}
                  placeholder=" "
                />
                <label 
                  htmlFor="postCode"
                  className={`absolute left-10 transition-all duration-200 pointer-events-none
                    ${form.postCode ? 'top-1.5 text-xs text-purple-600' : 'top-1/2 -translate-y-1/2 text-sm text-gray-500'}
                    peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-purple-600 peer-focus:translate-y-0`}
                >
                  Post Code <span className="text-red-500">*</span>
                </label>
                {errors.postCode && (
                  <p className="text-red-500 text-xs mt-1.5 ml-1">{errors.postCode}</p>
                )}
              </div>

              {/* Company Select */}
              <div className="relative">
                <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10">
                  <Building2 size={18} />
                </div>
                <select
                  id="company"
                  value={form.company}
                  onChange={(e) => handleChange('company', e.target.value)}
                  className={`peer w-full pl-10 pr-10 pt-5 pb-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all hover:border-gray-400 appearance-none bg-white cursor-pointer ${
                    form.company ? 'text-gray-900' : 'text-gray-500'
                  }`}
                >
                  <option value="">Select a company</option>
                  {mockCompanies.map(company => (
                    <option key={company.id} value={company.id}>{company.name}</option>
                  ))}
                </select>
                <label 
                  htmlFor="company"
                  className={`absolute left-10 transition-all duration-200 pointer-events-none
                    ${form.company ? 'top-1.5 text-xs text-purple-600' : 'top-1.5 text-xs text-gray-500'}`}
                >
                  Company
                </label>
                <div className="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>

              {/* Member Select */}
              <div className="relative">
                <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10">
                  <User size={18} />
                </div>
                <select
                  id="member"
                  value={form.member}
                  onChange={(e) => handleChange('member', e.target.value)}
                  className={`peer w-full pl-10 pr-10 pt-5 pb-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all hover:border-gray-400 appearance-none bg-white cursor-pointer ${
                    form.member ? 'text-gray-900' : 'text-gray-500'
                  }`}
                >
                  <option value="">Select a member</option>
                  {mockMembers.map(member => (
                    <option key={member.id} value={member.id}>{member.name}</option>
                  ))}
                </select>
                <label 
                  htmlFor="member"
                  className={`absolute left-10 transition-all duration-200 pointer-events-none
                    ${form.member ? 'top-1.5 text-xs text-purple-600' : 'top-1.5 text-xs text-gray-500'}`}
                >
                  Member
                </label>
                <div className="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>

              {/* Checkbox */}
              <div className="pt-2">
                <label 
                  htmlFor="makeSuburbActive" 
                  className="flex items-center gap-3 cursor-pointer group"
                >
                  <div className="relative">
                    <input
                      type="checkbox"
                      id="makeSuburbActive"
                      checked={form.makeSuburbActive}
                      onChange={(e) => handleChange('makeSuburbActive', e.target.checked)}
                      className="sr-only peer"
                    />
                    <div className="w-5 h-5 border-2 border-gray-300 rounded transition-all peer-checked:border-purple-600 peer-checked:bg-purple-600 group-hover:border-purple-400">
                      <svg 
                        className={`w-full h-full text-white p-0.5 transition-transform ${form.makeSuburbActive ? 'scale-100' : 'scale-0'}`}
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                      >
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                      </svg>
                    </div>
                  </div>
                  <span className="text-sm text-gray-700 group-hover:text-gray-900 transition-colors">
                    Make Suburb Active
                  </span>
                </label>
              </div>
            </div>

            {/* Footer */}
            <div className="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
              <button
                type="button"
                onClick={handleCancel}
                className="px-5 py-2.5 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all"
              >
                CANCEL
              </button>
              <button
                type="submit"
                className="px-5 py-2.5 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all"
              >
                ADD SUBURB
              </button>
            </div>
          </form>
        </div>
    </div>
  )
}
