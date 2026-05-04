import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'

export function AddIncomePage() {
  const [formData, setFormData] = useState({
    date: '2026-04-02',
    category: '',
    title: '',
    amount: '',
    gst: 'GST Included',
    totalIncome: '',
    description: '',
    isRecurring: false,
  })

  return (
    <div className="space-y-6">
      {/* Top Header Banner */}
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Add Income</h1>
      </div>

      <div className="max-w-4xl mx-auto">
        <Card className="p-8 shadow-md border border-gray-100 bg-white">
          <div className="mb-8">
            <h2 className="text-3xl font-light text-gray-400">Add Income</h2>
          </div>

          <form className="space-y-10">
            {/* Income Date */}
            <div className="relative">
              <label className="block text-sm font-medium text-gray-500 mb-1">
                Income Date <span className="text-red-500">*</span>
              </label>
              <input
                type="date"
                value={formData.date}
                onChange={(e) => setFormData({ ...formData, date: e.target.value })}
                className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800"
              />
            </div>

            {/* Income Category */}
            <div className="relative">
              <label className="block text-sm font-medium text-gray-500 mb-1">
                Income Category <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.category}
                onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 appearance-none"
              >
                <option value="">Select Category</option>
                <option value="Service Revenue">Service Revenue</option>
                <option value="Product Sales">Product Sales</option>
                <option value="Other Income">Other Income</option>
              </select>
              <div className="absolute right-0 bottom-3 pointer-events-none text-gray-400">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
              </div>
            </div>

            {/* Income Title */}
            <div className="relative">
              <label className="block text-sm font-medium text-gray-500 mb-1">
                Income Title <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.title}
                onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                placeholder="Enter income title"
                className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 placeholder-gray-300"
              />
            </div>

            {/* Amount and GST Row */}
            <div className="flex flex-col md:flex-row gap-10">
              <div className="flex-1 relative">
                <label className="block text-sm font-medium text-gray-500 mb-1">
                  Income Amount ($) <span className="text-red-500">*</span>
                </label>
                <input
                  type="number"
                  value={formData.amount}
                  onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
                  placeholder="0.00"
                  className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 placeholder-gray-300"
                />
              </div>
              <div className="flex-1 relative">
                <label className="block text-sm font-medium text-gray-500 mb-1">
                  GST
                </label>
                <select
                  value={formData.gst}
                  onChange={(e) => setFormData({ ...formData, gst: e.target.value })}
                  className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 appearance-none font-medium"
                >
                  <option value="GST Included">GST Included</option>
                  <option value="GST Excluded">GST Excluded</option>
                  <option value="No GST">No GST</option>
                </select>
                <div className="absolute right-0 bottom-3 pointer-events-none text-gray-400">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
              </div>
            </div>

            {/* Total Income */}
            <div className="relative">
              <label className="block text-sm font-medium text-gray-500 mb-1">
                Total Income
              </label>
              <input
                type="text"
                readOnly
                value={formData.amount}
                className="w-full bg-transparent border-b border-gray-300 py-2 outline-none text-gray-400 cursor-not-allowed"
              />
            </div>

            {/* Description */}
            <div className="relative">
              <label className="block text-sm font-medium text-gray-500 mb-1">
                Income Description
              </label>
              <textarea
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                placeholder="Enter description"
                className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 placeholder-gray-300 min-h-[40px] resize-none"
              ></textarea>
            </div>

            {/* Recurring Checkbox */}
            <div className="flex items-center gap-3">
              <input
                type="checkbox"
                id="isRecurring"
                checked={formData.isRecurring}
                onChange={(e) => setFormData({ ...formData, isRecurring: e.target.checked })}
                className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer"
              />
              <label htmlFor="isRecurring" className="text-sm font-medium text-gray-600 cursor-pointer">
                Recurring Income?
              </label>
            </div>

            {/* Submit Button */}
            <div className="pt-6">
              <Button className="bg-blue-600 hover:bg-blue-700 text-white px-10 py-3 rounded shadow-lg transition-all transform active:scale-95">
                Save Income
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
