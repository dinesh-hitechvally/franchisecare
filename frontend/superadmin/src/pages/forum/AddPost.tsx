import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { forumPostsApi, forumCategoriesApi, forumTopicsApi } from '../../api/services'
import { useAuthStore } from '../../store/authStore'

interface PostForm {
  postTitle: string
  postContent: string
  authorName: string
  category: string
  topic: string
  makePostActive: boolean
}

function extractErrorMessage(err: any, fallback: string): string {
  const errors = err?.response?.data?.errors
  if (errors) {
    const first = Object.values(errors)[0]
    if (Array.isArray(first) && first.length > 0) return String(first[0])
  }
  return err?.response?.data?.message ?? fallback
}

export function AddPost() {
  const navigate = useNavigate()
  const { id } = useParams<{ id: string }>()
  const isEdit = Boolean(id)
  const currentUser = useAuthStore((s) => s.user)

  const initialForm: PostForm = {
    postTitle: '',
    postContent: '',
    authorName: currentUser?.name ?? '',
    category: '',
    topic: '',
    makePostActive: false,
  }

  const [form, setForm] = useState<PostForm>(initialForm)
  const [errors, setErrors] = useState<Partial<Record<keyof PostForm, string>>>({})
  const [submitting, setSubmitting] = useState(false)

  const { data: categoriesData } = useQuery({
    queryKey: ['forum-categories', 'all'],
    queryFn: () => forumCategoriesApi.list({ per_page: 200 }),
  })
  const categories = categoriesData?.data ?? []

  const { data: topicsData } = useQuery({
    queryKey: ['forum-topics', 'by-category', form.category],
    queryFn: () => forumTopicsApi.list({ category_id: Number(form.category), per_page: 200 }),
    enabled: Boolean(form.category),
  })
  const topics = topicsData?.data ?? []

  const { data: post, isLoading } = useQuery({
    queryKey: ['forum-posts', id],
    queryFn: () => forumPostsApi.get(Number(id)),
    enabled: isEdit,
  })

  useEffect(() => {
    if (post) {
      setForm({
        postTitle: post.title,
        postContent: post.content ?? '',
        authorName: post.author_name ?? '',
        category: post.category_id ? String(post.category_id) : '',
        topic: post.topic_id ? String(post.topic_id) : '',
        makePostActive: post.status === 'active',
      })
    }
  }, [post])

  const handleChange = (field: keyof PostForm, value: string | boolean) => {
    setForm(prev => {
      const next = { ...prev, [field]: value }
      // Reset topic selection when the category changes, since topics are scoped to a category
      if (field === 'category') next.topic = ''
      return next
    })
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

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!validate()) {
      toast.error('Please fill in all required fields')
      return
    }

    setSubmitting(true)
    try {
      if (isEdit) {
        await forumPostsApi.update(Number(id), {
          title: form.postTitle,
          content: form.postContent,
          author_name: form.authorName || null,
          category_id: form.category ? Number(form.category) : null,
          topic_id: form.topic ? Number(form.topic) : null,
          status: form.makePostActive ? 'active' : 'inactive',
        })
        toast.success('Post updated successfully!')
      } else {
        await forumPostsApi.create({
          title: form.postTitle,
          content: form.postContent,
          author_name: form.authorName || undefined,
          category_id: form.category ? Number(form.category) : null,
          topic_id: form.topic ? Number(form.topic) : null,
        })
        toast.success('Post added successfully!')
      }
      navigate('/forum/list-posts')
    } catch (err: any) {
      toast.error(extractErrorMessage(err, isEdit ? 'Failed to update post' : 'Failed to add post'))
    } finally {
      setSubmitting(false)
    }
  }

  const handleCancel = () => {
    navigate('/forum/list-posts')
  }

  if (isEdit && isLoading) {
    return (
      <div className="page-content">
        <h1 className="page-title">Edit Post</h1>
        <div className="card p-8 text-center text-gray-400">Loading post...</div>
      </div>
    )
  }

  return (
    <div className="page-content">
      <h1 className="page-title">{isEdit ? 'Edit Post' : 'Add Post'}</h1>

      <div className="card">
        <div className="card-header">
          <h2 className="card-title">Post Details</h2>
        </div>
        <div className="card-body p-6">
          <form onSubmit={handleSubmit}>
            <div className="space-y-4">
              <div>
                <label className="form-label">
                  Post Title <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={form.postTitle}
                  onChange={(e) => handleChange('postTitle', e.target.value)}
                  className={`form-input ${errors.postTitle ? 'border-red-500' : ''}`}
                  placeholder="Enter post title"
                />
                {errors.postTitle && <p className="text-red-500 text-xs mt-1">{errors.postTitle}</p>}
              </div>

              <div>
                <label className="form-label">
                  Post Content <span className="text-red-500">*</span>
                </label>
                <textarea
                  value={form.postContent}
                  onChange={(e) => handleChange('postContent', e.target.value)}
                  rows={6}
                  className={`form-input form-textarea ${errors.postContent ? 'border-red-500' : ''}`}
                  placeholder="Enter post content"
                />
                {errors.postContent && <p className="text-red-500 text-xs mt-1">{errors.postContent}</p>}
              </div>

              <div>
                <label className="form-label">Author Name</label>
                <input
                  type="text"
                  value={form.authorName}
                  onChange={(e) => handleChange('authorName', e.target.value)}
                  className="form-input"
                  placeholder="Enter author name"
                />
              </div>

              {/* Category + Topic cascading selects */}
              <div className="grid grid-cols-2 gap-4">
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

                <div>
                  <label className="form-label">Topic</label>
                  <select
                    value={form.topic}
                    onChange={(e) => handleChange('topic', e.target.value)}
                    disabled={!form.category}
                    className="form-input disabled:bg-gray-50 disabled:cursor-not-allowed"
                  >
                    <option value="">{form.category ? 'Select a topic' : 'Select a category first'}</option>
                    {topics.map(t => (
                      <option key={t.id} value={t.id}>{t.name}</option>
                    ))}
                  </select>
                </div>
              </div>

              {/* Checkbox - only meaningful once the post exists */}
              {isEdit && (
                <label className="form-checkbox">
                  <input
                    type="checkbox"
                    checked={form.makePostActive}
                    onChange={(e) => handleChange('makePostActive', e.target.checked)}
                  />
                  <span>Make Post Active</span>
                </label>
              )}
            </div>

            <div className="flex justify-end gap-3 mt-6">
              <button type="button" className="btn btn-outline" onClick={handleCancel}>
                CANCEL
              </button>
              <button type="submit" className="btn btn-primary" disabled={submitting}>
                {submitting ? 'SAVING...' : isEdit ? 'SAVE POST' : 'ADD POST'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}
