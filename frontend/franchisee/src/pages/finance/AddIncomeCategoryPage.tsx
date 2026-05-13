import { useState, useEffect } from 'react'
import { useMutation, useQuery } from '@tanstack/react-query'
import { useNavigate, useParams } from 'react-router-dom'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { incomeCategoriesApi } from '../../api/services'
import { Loader2 } from 'lucide-react'

export function AddIncomeCategoryPage() {
  const navigate = useNavigate()
  const { id } = useParams()
  const isEditMode = !!id

  const [formData, setFormData] = useState({
    name: '',
    description: '',
    gst_inclusive: false,
    is_active: true,
  })
  const [errors, setErrors] = useState<Record<string, string>>({})

  // Fetch category data if editing
  const { data: categoryData, isLoading: isCategoryLoading } = useQuery({
    queryKey: ['income-category', id],
    queryFn: () => incomeCategoriesApi.getById(id as string),
    enabled: isEditMode,
  })

  // Pre-populate form with category data
  useEffect(() => {
    if (categoryData && isEditMode) {
      setFormData({
        name: categoryData.name,
        description: categoryData.description || '',
        gst_inclusive: categoryData.gst_inclusive,
        is_active: categoryData.is_active,
      })
    }
  }, [categoryData, isEditMode])

  const saveMutation = useMutation({
    mutationFn: async () => {
      if (!formData.name.trim()) {
        setErrors({ name: 'Category name is required' })
        return
      }
      if (!formData.description.trim()) {
        setErrors({ description: 'Category description is required' })
        return
      }

      if (isEditMode && id) {
        return await incomeCategoriesApi.update(id, {
          name: formData.name,
          description: formData.description,
          gst_inclusive: formData.gst_inclusive,
          is_active: formData.is_active,
        })
      } else {
        return await incomeCategoriesApi.create({
          name: formData.name,
          description: formData.description,
          gst_inclusive: formData.gst_inclusive,
          is_active: formData.is_active,
        })
      }
    },
    onSuccess: () => {
      navigate('/finance/income/categories')
    },
    onError: (error: any) => {
      const errorMessage = error?.response?.data?.message || 'Failed to save category'
      setErrors({ submit: errorMessage })
    },
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setErrors({})
    saveMutation.mutate()
  }

  if (isEditMode && isCategoryLoading) {
    return (
      <div className="flex items-center justify-center h-screen">
        <Loader2 className="w-8 h-8 animate-spin text-gray-400" />
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Top Header Banner */}
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">
          {isEditMode ? 'Edit Income Category' : 'Add Income Category'}
        </h1>
      </div>

      {/* Two-Column Layout */}
      <div className="w-full grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        {/* Left Column - Form Card */}
        <Card className="p-8 shadow-md border border-gray-100 bg-white xl:col-span-2">
          <form className="space-y-10" onSubmit={handleSubmit}>
            {/* Error Message */}
            {errors.submit && (
              <div className="bg-red-50 border border-red-200 rounded-md p-4">
                <p className="text-sm text-red-700">{errors.submit}</p>
              </div>
            )}

            {/* Category Name */}
            <div className="relative">
              <label className="block text-sm font-medium text-gray-500 mb-1">
                Category Name <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.name}
                onChange={(e) => {
                  setFormData({ ...formData, name: e.target.value })
                  if (errors.name) setErrors({ ...errors, name: '' })
                }}
                placeholder="Enter category name"
                className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 placeholder-gray-300"
              />
              {errors.name && <p className="text-xs text-red-600 mt-1">{errors.name}</p>}
            </div>

            {/* Category Description */}
            <div className="relative">
              <label className="block text-sm font-medium text-gray-500 mb-1">
                Category Description <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.description}
                onChange={(e) => {
                  setFormData({ ...formData, description: e.target.value })
                  if (errors.description) setErrors({ ...errors, description: '' })
                }}
                placeholder="Enter category description"
                className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 placeholder-gray-300"
              />
              {errors.description && <p className="text-xs text-red-600 mt-1">{errors.description}</p>}
            </div>

            {/* Checkboxes */}
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <input
                  type="checkbox"
                  id="gstInclusive"
                  checked={formData.gst_inclusive}
                  onChange={(e) => setFormData({ ...formData, gst_inclusive: e.target.checked })}
                  className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer"
                />
                <label htmlFor="gstInclusive" className="text-sm font-medium text-gray-600 cursor-pointer">
                  Category Inclusive of GST
                </label>
              </div>

              <div className="flex items-center gap-3">
                <input
                  type="checkbox"
                  id="isActive"
                  checked={formData.is_active}
                  onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                  className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer"
                />
                <label htmlFor="isActive" className="text-sm font-medium text-gray-600 cursor-pointer">
                  Make Category Active
                </label>
              </div>
            </div>

            {/* Submit Button */}
            <div className="pt-6">
              <Button
                type="submit"
                disabled={saveMutation.isPending}
                className={`px-10 py-3 rounded shadow-lg transition-all transform active:scale-95 ${
                  saveMutation.isPending
                    ? 'bg-blue-400 text-white cursor-not-allowed'
                    : 'bg-blue-600 hover:bg-blue-700 text-white'
                }`}
              >
                {saveMutation.isPending ? 'Saving...' : isEditMode ? 'Update Category' : 'Save Category'}
              </Button>
            </div>
          </form>
        </Card>

        {/* Right Column - Category Preview Card */}
        <Card className="p-6 border border-gray-200 bg-gray-50/40 sticky top-6">
          <h3 className="text-sm font-semibold text-gray-700 mb-4">Category Preview</h3>
          
          <div className="space-y-4">
            {/* Category Name */}
            <div>
              <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Name</p>
              <p className="text-base font-semibold text-gray-800">
                {formData.name || '(Not specified)'}
              </p>
            </div>

            {/* Category Description */}
            <div>
              <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Description</p>
              <p className="text-sm text-gray-600">
                {formData.description || '(No description)'}
              </p>
            </div>

            {/* Status Badges */}
            <div className="pt-4 border-t border-gray-200 space-y-2">
              {/* GST Status */}
              <div className="flex items-center justify-between">
                <span className="text-xs text-gray-600">GST Inclusive</span>
                <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                  formData.gst_inclusive
                    ? 'bg-green-100 text-green-700'
                    : 'bg-gray-200 text-gray-600'
                }`}>
                  {formData.gst_inclusive ? 'Yes' : 'No'}
                </span>
              </div>

              {/* Active Status */}
              <div className="flex items-center justify-between">
                <span className="text-xs text-gray-600">Status</span>
                <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                  formData.is_active
                    ? 'bg-blue-100 text-blue-700'
                    : 'bg-gray-200 text-gray-600'
                }`}>
                  {formData.is_active ? 'Active' : 'Inactive'}
                </span>
              </div>
            </div>

            {/* Required Fields Checklist */}
            <div className="pt-4 border-t border-gray-200">
              <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">Required</p>
              <div className="space-y-2">
                <div className="flex items-start gap-2">
                  <span className={`mt-1 ${formData.name ? 'text-green-600' : 'text-gray-300'}`}>✓</span>
                  <span className={`text-xs ${formData.name ? 'text-gray-700' : 'text-gray-400'}`}>
                    Category Name
                  </span>
                </div>
                <div className="flex items-start gap-2">
                  <span className={`mt-1 ${formData.description ? 'text-green-600' : 'text-gray-300'}`}>✓</span>
                  <span className={`text-xs ${formData.description ? 'text-gray-700' : 'text-gray-400'}`}>
                    Category Description
                  </span>
                </div>
              </div>
            </div>
          </div>
        </Card>
      </div>
    </div>
  )
}
