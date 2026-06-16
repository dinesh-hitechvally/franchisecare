import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import toast from 'react-hot-toast'
import { Users, FileText, Shield } from 'lucide-react'

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
  { id: 'full', name: 'Full Access' },
  { id: 'moderate', name: 'Moderate' },
  { id: 'readwrite', name: 'Read/Write' },
  { id: 'readonly', name: 'Read Only' },
]

export function AddForumGroup() {
  const navigate = useNavigate()
  const [form, setForm] = useState<ForumGroupForm>(initialForm)
  const [errors, setErrors] = useState<Partial<Record<keyof ForumGroupForm, string>>>({})

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

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    
    if (!validate()) {
      toast.error('Please fill in all required fields')
      return
    }

    toast.success('Forum group added successfully!')
    navigate('/forum/list-groups')
  }

  const handleCancel = () => {
    navigate('/forum/list-groups')
  }

  return (
    <div className="page-content">
      <h1 className="page-title">Add Forum Group</h1>

      <div className="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
        <form onSubmit={handleSubmit}>
          <div className="p-6 space-y-5">
            {/* Group Name Field */}
            <div className="relative">
              <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                <Users size={18} />
              </div>
              <input
                type="text"
                id="groupName"
                value={form.groupName}
                onChange={(e) => handleChange('groupName', e.target.value)}
                className={`peer w-full pl-10 pr-4 pt-5 pb-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all ${
                  errors.groupName ? 'border-red-500 bg-red-50' : 'border-gray-300 hover:border-gray-400'
                }`}
                placeholder=" "
              />
              <label 
                htmlFor="groupName"
                className={`absolute left-10 transition-all duration-200 pointer-events-none
                  ${form.groupName ? 'top-1.5 text-xs text-purple-600' : 'top-1/2 -translate-y-1/2 text-sm text-gray-500'}
                  peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-purple-600 peer-focus:translate-y-0`}
              >
                Group Name <span className="text-red-500">*</span>
              </label>
              {errors.groupName && (
                <p className="text-red-500 text-xs mt-1.5 ml-1">{errors.groupName}</p>
              )}
            </div>

            {/* Group Description Field */}
            <div className="relative">
              <div className="absolute left-3 top-4 text-gray-400">
                <FileText size={18} />
              </div>
              <textarea
                id="groupDescription"
                value={form.groupDescription}
                onChange={(e) => handleChange('groupDescription', e.target.value)}
                rows={4}
                className={`peer w-full pl-10 pr-4 pt-5 pb-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none ${
                  errors.groupDescription ? 'border-red-500 bg-red-50' : 'border-gray-300 hover:border-gray-400'
                }`}
                placeholder=" "
              />
              <label 
                htmlFor="groupDescription"
                className={`absolute left-10 transition-all duration-200 pointer-events-none
                  ${form.groupDescription ? 'top-1.5 text-xs text-purple-600' : 'top-4 text-sm text-gray-500'}
                  peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-purple-600`}
              >
                Group Description <span className="text-red-500">*</span>
              </label>
              {errors.groupDescription && (
                <p className="text-red-500 text-xs mt-1.5 ml-1">{errors.groupDescription}</p>
              )}
            </div>

            {/* Permissions Select */}
            <div className="relative">
              <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10">
                <Shield size={18} />
              </div>
              <select
                id="permissions"
                value={form.permissions}
                onChange={(e) => handleChange('permissions', e.target.value)}
                className={`peer w-full pl-10 pr-10 pt-5 pb-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all hover:border-gray-400 appearance-none bg-white cursor-pointer ${
                  form.permissions ? 'text-gray-900' : 'text-gray-500'
                } ${errors.permissions ? 'border-red-500 bg-red-50' : 'border-gray-300'}`}
              >
                <option value="">Select permissions</option>
                {permissionOptions.map(perm => (
                  <option key={perm.id} value={perm.id}>{perm.name}</option>
                ))}
              </select>
              <label 
                htmlFor="permissions"
                className="absolute left-10 top-1.5 text-xs text-gray-500 transition-all duration-200 pointer-events-none peer-focus:text-purple-600"
              >
                Permissions <span className="text-red-500">*</span>
              </label>
              <div className="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
              </div>
              {errors.permissions && (
                <p className="text-red-500 text-xs mt-1.5 ml-1">{errors.permissions}</p>
              )}
            </div>

            {/* Checkbox */}
            <div className="pt-2">
              <label 
                htmlFor="makeGroupActive" 
                className="flex items-center gap-3 cursor-pointer group"
              >
                <div className="relative">
                  <input
                    type="checkbox"
                    id="makeGroupActive"
                    checked={form.makeGroupActive}
                    onChange={(e) => handleChange('makeGroupActive', e.target.checked)}
                    className="sr-only peer"
                  />
                  <div className="w-5 h-5 border-2 border-gray-300 rounded transition-all peer-checked:border-purple-600 peer-checked:bg-purple-600 group-hover:border-purple-400">
                    <svg 
                      className={`w-full h-full text-white p-0.5 transition-transform ${form.makeGroupActive ? 'scale-100' : 'scale-0'}`}
                      fill="none" 
                      stroke="currentColor" 
                      viewBox="0 0 24 24"
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                </div>
                <span className="text-sm text-gray-700 group-hover:text-gray-900 transition-colors">
                  Make Group Active
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
              ADD GROUP
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
