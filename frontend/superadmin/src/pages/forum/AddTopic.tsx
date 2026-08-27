import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { forumTopicsApi, forumCategoriesApi } from '../../api/services'

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

function extractErrorMessage(err: any, fallback: string): string {
  const errors = err?.response?.data?.errors
  if (errors) {
    const first = Object.values(errors)[0]
    if (Array.isArray(first) && first.length > 0) return String(first[0])
  }
  return err?.response?.data?.message ?? fallback
}

export function AddTopic() {
  const navigate = useNavigate()
  const { id } = useParams<{ id: string }>()
  const isEdit = Boolean(id)
  const [form, setForm] = useState<TopicForm>(initialForm)
  const [errors, setErrors] = useState<Partial<Record<keyof TopicForm, string>>>({})
  const [submitting, setSubmitting] = useState(false)

  const { data: categoriesData } = useQuery({
    queryKey: ['forum-categories', 'all'],
    queryFn: () => forumCategoriesApi.list({ per_page: 200 }),
  })
  const categories = categoriesData?.data ?? []

  const { data: topic, isLoading } = useQuery({
    queryKey: ['forum-topics', id],
    queryFn: () => forumTopicsApi.get(Number(id)),
    enabled: isEdit,
  })

  useEffect(() => {
    if (topic) {
      setForm({
        topicName: topic.name,
        topicDescription: topic.description ?? '',
        category: topic.category_id ? String(topic.category_id) : '',
        makeTopicActive: topic.status === 'ACTIVE',
      })
    }
  }, [topic])

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

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!validate()) {
      toast.error('Please fill in all required fields')
      return
    }

    setSubmitting(true)
    try {
      if (isEdit) {
        await forumTopicsApi.update(Number(id), {
          name: form.topicName,
          description: form.topicDescription,
          category_id: form.category ? Number(form.category) : null,
          status: form.makeTopicActive ? 'ACTIVE' : 'INACTIVE',
        })
        toast.success('Topic updated successfully!')
      } else {
        await forumTopicsApi.create({
          name: form.topicName,
          description: form.topicDescription,
          category_id: form.category ? Number(form.category) : null,
        })
        toast.success('Topic added successfully!')
      }
      navigate('/forum/list-topics')
    } catch (err: any) {
      toast.error(extractErrorMessage(err, isEdit ? 'Failed to update topic' : 'Failed to add topic'))
    } finally {
      setSubmitting(false)
    }
  }

  const handleCancel = () => {
    navigate('/forum/list-topics')
  }

  if (isEdit && isLoading) {
    return (
      <div className="page-content">
        <h1 className="page-title">Edit Topic</h1>
        <div className="card p-8 text-center text-gray-400">Loading topic...</div>
      </div>
    )
  }

  return (
    <div className="page-content">
      <h1 className="page-title">{isEdit ? 'Edit Topic' : 'Add Topic'}</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">Topic Details</h2>
        </div>
        <div className="card-body p-6">
          <form onSubmit={handleSubmit}>
            <div className="space-y-4">
              <div>
                <label className="form-label">
                  Topic Name <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={form.topicName}
                  onChange={(e) => handleChange('topicName', e.target.value)}
                  className={`form-input ${errors.topicName ? 'border-red-500' : ''}`}
                  placeholder="Enter topic name"
                />
                {errors.topicName && <p className="text-red-500 text-xs mt-1">{errors.topicName}</p>}
              </div>

              <div>
                <label className="form-label">
                  Topic Description <span className="text-red-500">*</span>
                </label>
                <textarea
                  value={form.topicDescription}
                  onChange={(e) => handleChange('topicDescription', e.target.value)}
                  rows={4}
                  className={`form-input form-textarea ${errors.topicDescription ? 'border-red-500' : ''}`}
                  placeholder="Enter topic description"
                />
                {errors.topicDescription && <p className="text-red-500 text-xs mt-1">{errors.topicDescription}</p>}
              </div>

              <div>
                <label className="form-label">
                  Category <span className="text-red-500">*</span>
                </label>
                <select
                  value={form.category}
                  onChange={(e) => handleChange('category', e.target.value)}
                  className={`form-input ${errors.category ? 'border-red-500' : ''}`}
                >
                  <option value="">Select a category</option>
                  {categories.map(cat => (
                    <option key={cat.id} value={cat.id}>{cat.name}</option>
                  ))}
                </select>
                {errors.category && <p className="text-red-500 text-xs mt-1">{errors.category}</p>}
              </div>

              {/* Checkbox - only meaningful once the topic exists */}
              {isEdit && (
                <label className="form-checkbox">
                  <input
                    type="checkbox"
                    checked={form.makeTopicActive}
                    onChange={(e) => handleChange('makeTopicActive', e.target.checked)}
                  />
                  <span>Make Topic Active</span>
                </label>
              )}
            </div>

            <div className="flex justify-end gap-3 mt-6">
              <button type="button" className="btn btn-outline" onClick={handleCancel}>
                CANCEL
              </button>
              <button type="submit" className="btn btn-primary" disabled={submitting}>
                {submitting ? 'SAVING...' : isEdit ? 'SAVE TOPIC' : 'ADD TOPIC'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}
