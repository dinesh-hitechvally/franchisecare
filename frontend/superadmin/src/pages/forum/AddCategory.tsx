import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import toast from 'react-hot-toast'
import { FolderOpen, FileText } from 'lucide-react'

interface CategoryForm {
  categoryName: string
  categoryDescription: string
  makeCategoryActive: boolean
}

const initialForm: CategoryForm = {
  categoryName: '',
  categoryDescription: '',
  makeCategoryActive: false
}

export function AddCategory() {
  const navigate = useNavigate()
  const [form, setForm] = useState<CategoryForm>(initialForm)
  const [errors, setErrors] = useState<Partial<CategoryForm>>({})

  const handleChange = (field: keyof CategoryForm, value: string | boolean) => {
    setForm(prev => ({ ...prev, [field]: value }))
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: undefined }))
    }
  }

  const validate = (): boolean => {
    const newErrors: Partial<CategoryForm> = {}
    
    if (!form.categoryName.trim()) {
      newErrors.categoryName = 'Category Name is required'
    }
    if (!form.categoryDescription.trim()) {
      newErrors.categoryDescription = 'Category Description is required'
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

    toast.success('Category added successfully!')
    navigate('/forum/list-categories')
  }

  const handleCancel = () => {
    navigate('/forum/list-categories')
  }

  return (
    <div className="page-content">
      <h1 className="page-title">Add Category</h1>

      <div className="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
        <form onSubmit={handleSubmit}>
          <div className="p-6 space-y-5">
            {/* Category Name Field */}
            <div className="relative">
              <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                <FolderOpen size={18} />
              </div>
              <input
                type="text"
                id="categoryName"
                value={form.categoryName}
                onChange={(e) => handleChange('categoryName', e.target.value)}
                className={`peer w-full pl-10 pr-4 pt-5 pb-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all ${
                  errors.categoryName ? 'border-red-500 bg-red-50' : 'border-gray-300 hover:border-gray-400'
                }`}
                placeholder=" "
              />
              <label 
                htmlFor="categoryName"
                className={`absolute left-10 transition-all duration-200 pointer-events-none
                  ${form.categoryName ? 'top-1.5 text-xs text-purple-600' : 'top-1/2 -translate-y-1/2 text-sm text-gray-500'}
                  peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-purple-600 peer-focus:translate-y-0`}
              >
                Category Name <span className="text-red-500">*</span>
              </label>
              {errors.categoryName && (
                <p className="text-red-500 text-xs mt-1.5 ml-1">{errors.categoryName}</p>
              )}
            </div>

            {/* Category Description Field */}
            <div className="relative">
              <div className="absolute left-3 top-4 text-gray-400">
                <FileText size={18} />
              </div>
              <textarea
                id="categoryDescription"
                value={form.categoryDescription}
                onChange={(e) => handleChange('categoryDescription', e.target.value)}
                rows={4}
                className={`peer w-full pl-10 pr-4 pt-5 pb-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none ${
                  errors.categoryDescription ? 'border-red-500 bg-red-50' : 'border-gray-300 hover:border-gray-400'
                }`}
                placeholder=" "
              />
              <label 
                htmlFor="categoryDescription"
                className={`absolute left-10 transition-all duration-200 pointer-events-none
                  ${form.categoryDescription ? 'top-1.5 text-xs text-purple-600' : 'top-4 text-sm text-gray-500'}
                  peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-purple-600`}
              >
                Category Description <span className="text-red-500">*</span>
              </label>
              {errors.categoryDescription && (
                <p className="text-red-500 text-xs mt-1.5 ml-1">{errors.categoryDescription}</p>
              )}
            </div>

            {/* Checkbox */}
            <div className="pt-2">
              <label 
                htmlFor="makeCategoryActive" 
                className="flex items-center gap-3 cursor-pointer group"
              >
                <div className="relative">
                  <input
                    type="checkbox"
                    id="makeCategoryActive"
                    checked={form.makeCategoryActive}
                    onChange={(e) => handleChange('makeCategoryActive', e.target.checked)}
                    className="sr-only peer"
                  />
                  <div className="w-5 h-5 border-2 border-gray-300 rounded transition-all peer-checked:border-purple-600 peer-checked:bg-purple-600 group-hover:border-purple-400">
                    <svg 
                      className={`w-full h-full text-white p-0.5 transition-transform ${form.makeCategoryActive ? 'scale-100' : 'scale-0'}`}
                      fill="none" 
                      stroke="currentColor" 
                      viewBox="0 0 24 24"
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                </div>
                <span className="text-sm text-gray-700 group-hover:text-gray-900 transition-colors">
                  Make Category Active
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
              ADD CATEGORY
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
