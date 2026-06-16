import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import toast from 'react-hot-toast'
import { FileText, AlignLeft, FolderOpen, MessageSquare } from 'lucide-react'

interface PostForm {
  postTitle: string
  postContent: string
  category: string
  topic: string
  makePostActive: boolean
  allowComments: boolean
}

const initialForm: PostForm = {
  postTitle: '',
  postContent: '',
  category: '',
  topic: '',
  makePostActive: false,
  allowComments: true
}

const mockCategories = [
  { id: 1, name: 'General Discussion' },
  { id: 2, name: 'Announcements' },
  { id: 3, name: 'Tips & Tricks' },
  { id: 4, name: 'Support' },
  { id: 5, name: 'Feedback' },
]

const mockTopics = [
  { id: 1, name: 'Getting Started' },
  { id: 2, name: 'Best Practices' },
  { id: 3, name: 'Troubleshooting' },
  { id: 4, name: 'Feature Requests' },
]

export function AddPost() {
  const navigate = useNavigate()
  const [form, setForm] = useState<PostForm>(initialForm)
  const [errors, setErrors] = useState<Partial<Record<keyof PostForm, string>>>({})

  const handleChange = (field: keyof PostForm, value: string | boolean) => {
    setForm(prev => ({ ...prev, [field]: value }))
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: undefined }))
    }
  }

  const validate = (): boolean => {
    const newErrors: Partial<Record<keyof PostForm, string>> = {}
    
    if (!form.postTitle.trim()) {
      newErrors.postTitle = 'Post Title is required'
    }
    if (!form.postContent.trim()) {
      newErrors.postContent = 'Post Content is required'
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

    toast.success('Post added successfully!')
    navigate('/forum/list-posts')
  }

  const handleCancel = () => {
    navigate('/forum/list-posts')
  }

  return (
    <div className="page-content">
      <h1 className="page-title">Add Post</h1>

      <div className="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
        <form onSubmit={handleSubmit}>
          <div className="p-6 space-y-5">
            {/* Post Title Field */}
            <div className="relative">
              <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                <FileText size={18} />
              </div>
              <input
                type="text"
                id="postTitle"
                value={form.postTitle}
                onChange={(e) => handleChange('postTitle', e.target.value)}
                className={`peer w-full pl-10 pr-4 pt-5 pb-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all ${
                  errors.postTitle ? 'border-red-500 bg-red-50' : 'border-gray-300 hover:border-gray-400'
                }`}
                placeholder=" "
              />
              <label 
                htmlFor="postTitle"
                className={`absolute left-10 transition-all duration-200 pointer-events-none
                  ${form.postTitle ? 'top-1.5 text-xs text-purple-600' : 'top-1/2 -translate-y-1/2 text-sm text-gray-500'}
                  peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-purple-600 peer-focus:translate-y-0`}
              >
                Post Title <span className="text-red-500">*</span>
              </label>
              {errors.postTitle && (
                <p className="text-red-500 text-xs mt-1.5 ml-1">{errors.postTitle}</p>
              )}
            </div>

            {/* Post Content Field */}
            <div className="relative">
              <div className="absolute left-3 top-4 text-gray-400">
                <AlignLeft size={18} />
              </div>
              <textarea
                id="postContent"
                value={form.postContent}
                onChange={(e) => handleChange('postContent', e.target.value)}
                rows={6}
                className={`peer w-full pl-10 pr-4 pt-5 pb-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none ${
                  errors.postContent ? 'border-red-500 bg-red-50' : 'border-gray-300 hover:border-gray-400'
                }`}
                placeholder=" "
              />
              <label 
                htmlFor="postContent"
                className={`absolute left-10 transition-all duration-200 pointer-events-none
                  ${form.postContent ? 'top-1.5 text-xs text-purple-600' : 'top-4 text-sm text-gray-500'}
                  peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-purple-600`}
              >
                Post Content <span className="text-red-500">*</span>
              </label>
              {errors.postContent && (
                <p className="text-red-500 text-xs mt-1.5 ml-1">{errors.postContent}</p>
              )}
            </div>

            {/* Two column layout for selects */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
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

              {/* Topic Select */}
              <div className="relative">
                <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10">
                  <MessageSquare size={18} />
                </div>
                <select
                  id="topic"
                  value={form.topic}
                  onChange={(e) => handleChange('topic', e.target.value)}
                  className={`peer w-full pl-10 pr-10 pt-5 pb-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all hover:border-gray-400 appearance-none bg-white cursor-pointer ${
                    form.topic ? 'text-gray-900' : 'text-gray-500'
                  }`}
                >
                  <option value="">Select a topic</option>
                  {mockTopics.map(topic => (
                    <option key={topic.id} value={topic.id}>{topic.name}</option>
                  ))}
                </select>
                <label 
                  htmlFor="topic"
                  className="absolute left-10 top-1.5 text-xs text-gray-500 transition-all duration-200 pointer-events-none peer-focus:text-purple-600"
                >
                  Topic
                </label>
                <div className="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
            </div>

            {/* Checkboxes */}
            <div className="flex items-center gap-8 pt-2">
              <label 
                htmlFor="makePostActive" 
                className="flex items-center gap-3 cursor-pointer group"
              >
                <div className="relative">
                  <input
                    type="checkbox"
                    id="makePostActive"
                    checked={form.makePostActive}
                    onChange={(e) => handleChange('makePostActive', e.target.checked)}
                    className="sr-only peer"
                  />
                  <div className="w-5 h-5 border-2 border-gray-300 rounded transition-all peer-checked:border-purple-600 peer-checked:bg-purple-600 group-hover:border-purple-400">
                    <svg 
                      className={`w-full h-full text-white p-0.5 transition-transform ${form.makePostActive ? 'scale-100' : 'scale-0'}`}
                      fill="none" 
                      stroke="currentColor" 
                      viewBox="0 0 24 24"
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                </div>
                <span className="text-sm text-gray-700 group-hover:text-gray-900 transition-colors">
                  Make Post Active
                </span>
              </label>

              <label 
                htmlFor="allowComments" 
                className="flex items-center gap-3 cursor-pointer group"
              >
                <div className="relative">
                  <input
                    type="checkbox"
                    id="allowComments"
                    checked={form.allowComments}
                    onChange={(e) => handleChange('allowComments', e.target.checked)}
                    className="sr-only peer"
                  />
                  <div className="w-5 h-5 border-2 border-gray-300 rounded transition-all peer-checked:border-purple-600 peer-checked:bg-purple-600 group-hover:border-purple-400">
                    <svg 
                      className={`w-full h-full text-white p-0.5 transition-transform ${form.allowComments ? 'scale-100' : 'scale-0'}`}
                      fill="none" 
                      stroke="currentColor" 
                      viewBox="0 0 24 24"
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                </div>
                <span className="text-sm text-gray-700 group-hover:text-gray-900 transition-colors">
                  Allow Comments
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
              ADD POST
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
