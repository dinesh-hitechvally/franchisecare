import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'

export function AddIncomeCategoryPage() {
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    inclusiveGst: false,
    is_active: true,
  })

  return (
    <div className="space-y-6">
      {/* Top Header Banner */}
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Add Income Category</h1>
      </div>

      <div className="max-w-4xl mx-auto">
        <Card className="p-8 shadow-md border border-gray-100 bg-white">
          <div className="mb-8">
            <h2 className="text-3xl font-light text-gray-400">Add Income Category</h2>
          </div>

          <form className="space-y-10">
            {/* Category Name */}
            <div className="relative">
              <label className="block text-sm font-medium text-gray-500 mb-1">
                Category Name <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                placeholder="Enter category name"
                className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 placeholder-gray-300"
              />
            </div>

            {/* Category Description */}
            <div className="relative">
              <label className="block text-sm font-medium text-gray-500 mb-1">
                Category Description <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                placeholder="Enter category description"
                className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 placeholder-gray-300"
              />
            </div>

            {/* Checkboxes */}
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <input
                  type="checkbox"
                  id="inclusiveGst"
                  checked={formData.inclusiveGst}
                  onChange={(e) => setFormData({ ...formData, inclusiveGst: e.target.checked })}
                  className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer"
                />
                <label htmlFor="inclusiveGst" className="text-sm font-medium text-gray-600 cursor-pointer">
                  Category Inclusive of GST
                </label>
              </div>

              <div className="flex items-center gap-3">
                <input
                  type="checkbox"
                  id="isActive"
                  checked={formData.isActive}
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
              <Button className="bg-blue-600 hover:bg-blue-700 text-white px-10 py-3 rounded shadow-lg transition-all transform active:scale-95">
                Save Category
              </Button>
            </div>
          </form>
        </Card>
      </div>

      {/* Footer Support Info */}
      <div className="text-center text-xs text-gray-400 pt-10 pb-6 border-t border-gray-100 flex flex-col md:flex-row justify-between gap-4">
        <span>Copyright FranchiseCare © 2026</span>
        <div className="flex flex-col text-right">
          <span>For Mate Support, please call 03 9514 9606</span>
          <span>Monday – Friday: 9:00 AM – 10:30 PM | Saturday – Sunday: 9:00 AM – 6:00 PM</span>
        </div>
      </div>
    </div>
  )
}
