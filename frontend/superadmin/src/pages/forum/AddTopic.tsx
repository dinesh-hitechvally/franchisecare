import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import toast from 'react-hot-toast'
import { MessageSquare, FileText, FolderOpen } from 'lucide-react'

interface TopicForm {
  topicName: string
  topicDescription: string
  category: string
  makeTopicActive: boolean
}

const initialForm: TopicForm = {
  topicName: '',
  topicDescription: '',
  category: '',
  makeTopicActive: false
}

const mockCategories = [
  { id: 1, name: 'General Discussion' },
  { id: 2, name: 'Announcements' },
  { id: 3, name: 'Tips & Tricks' },
  { id: 4, name: 'Support' },
  { id: 5, name: 'Feedback' },
]

export function AddTopic() {
  const navigate = useNavigate()
  const [form, setForm] = useState<TopicForm>(initialForm)
  const [errors, setErrors] = useState<Partial<Record<keyof TopicForm, string>>>({})

  const handleChange = (field: keyof TopicForm, value: string | boolean) => {
    setForm(prev => ({ ...prev, [field]: value }))
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: undefined }))
    }
  }

  const validate = (): boolean => {
    const newErrors: Partial<Record<keyof TopicForm, string>> = {}
    
    if (!form.topicName.trim()) {
      newErrors.topicName = 'Topic Name is required'
    }
    if (!form.topicDescription.trim()) {
      newErrors.topicDescription = 'Topic Description is required'
    }
    if (!form.category) {
      newErrors.category = 'Category is required'
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

    toast.success('Topic added successfully!')
    navigate('/forum/list-topics')
  }

  const handleCancel = () => {
    navigate('/forum/list-topics')
  }

  return (
    <div className="page-content">
      <h1 className="page-title">Add Topic</h1>

      <div className="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
        <form onSubmit={handleSubmit}>
          <div className="p-6 space-y-5">
            {/* Topic Name Field */}
            <div className="relative">
              <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                <MessageSquare size={18} />
              </div>
              <input
                type="text"
                id="topicName"
                value={form.topicName}
                onChange={(e) => handleChange('topicName', e.target.value)}
                className={`peer w-full pl-10 pr-4 pt-5 pb-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all ${
                  errors.topicName ? 'border-red-500 bg-red-50' : 'border-gray-300 hover:border-gray-400'
                }`}
                placeholder=" "
              />
              <label 
                htmlFor="topicName"
                className={`absolute left-10 transition-all duration-200 pointer-events-none
                  ${form.topicName ? 'top-1.5 text-xs text-purple-600' : 'top-1/2 -translate-y-1/2 text-sm text-gray-500'}
                  peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-purple-600 peer-focus:translate-y-0`}
              >
                Topic Name <span className="text-red-500">*</span>
              </label>
              {errors.topicName && (
                <p className="text-red-500 text-xs mt-1.5 ml-1">{errors.topicName}</p>
              )}
            </div>

            {/* Topic Description Field */}
            <div className="relative">
              <div className="absolute left-3 top-4 text-gray-400">
                <FileText size={18} />
              </div>
              <textarea
                id="topicDescription"
                value={form.topicDescription}
                onChange={(e) => handleChange('topicDescription', e.target.value)}
                rows={4}
                className={`peer w-full pl-10 pr-4 pt-5 pb-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none ${
                  errors.topicDescription ? 'border-red-500 bg-red-50' : 'border-gray-300 hover:border-gray-400'
                }`}
                placeholder=" "
              />
              <label 
                htmlFor="topicDescription"
                className={`absolute left-10 transition-all duration-200 pointer-events-none
                  ${form.topicDescription ? 'top-1.5 text-xs text-purple-600' : 'top-4 text-sm text-gray-500'}
                  peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-purple-600`}
              >
                Topic Description <span className="text-red-500">*</span>
              </label>
              {errors.topicDescription && (
                <p className="text-red-500 text-xs mt-1.5 ml-1">{errors.topicDescription}</p>
              )}
            </div>

            {/* Category Select */}
            <div className="relative">
              <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10">
                <FolderOpen size={18} />
              </div>
              <select
                id="category"
                value={form.category}
                onChange={(e) => handleChange('category', e.target.value)}
                className={`peer w-full pl-10 pr-10 pt-5 pb-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all hover:border-gray-400 appearance-none bg-white cursor-pointer ${
                  form.category ? 'text-gray-900' : 'text-gray-500'
                } ${errors.category ? 'border-red-500 bg-red-50' : 'border-gray-300'}`}
              >
                <option value="">Select a category</option>
                {mockCategories.map(cat => (
                  <option key={cat.id} value={cat.id}>{cat.name}</option>
                ))}
              </select>
              <label 
                htmlFor="category"
                className="absolute left-10 top-1.5 text-xs text-gray-500 transition-all duration-200 pointer-events-none peer-focus:text-purple-600"
              >
                Category <span className="text-red-500">*</span>
              </label>
              <div className="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
              </div>
              {errors.category && (
                <p className="text-red-500 text-xs mt-1.5 ml-1">{errors.category}</p>
              )}
            </div>

            {/* Checkbox */}
            <div className="pt-2">
              <label 
                htmlFor="makeTopicActive" 
                className="flex items-center gap-3 cursor-pointer group"
              >
                <div className="relative">
                  <input
                    type="checkbox"
                    id="makeTopicActive"
                    checked={form.makeTopicActive}
                    onChange={(e) => handleChange('makeTopicActive', e.target.checked)}
                    className="sr-only peer"
                  />
                  <div className="w-5 h-5 border-2 border-gray-300 rounded transition-all peer-checked:border-purple-600 peer-checked:bg-purple-600 group-hover:border-purple-400">
                    <svg 
                      className={`w-full h-full text-white p-0.5 transition-transform ${form.makeTopicActive ? 'scale-100' : 'scale-0'}`}
                      fill="none" 
                      stroke="currentColor" 
                      viewBox="0 0 24 24"
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                </div>
                <span className="text-sm text-gray-700 group-hover:text-gray-900 transition-colors">
                  Make Topic Active
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
              ADD TOPIC
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
