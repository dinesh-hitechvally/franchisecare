import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'

export function AddExpensePage() {
  const [formData, setFormData] = useState({
    date: '2026-04-02',
    type: '',
    category: '',
    title: '',
    amount: '',
    gst: 'GST Included',
    totalExpense: '',
    description: '',
    isRecurring: false,
  })

  return (
    <div className="space-y-6">
      {/* Top Header Banner */}
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Add Expense</h1>
      </div>

      <div className="flex flex-col xl:flex-row gap-6 w-full">
        {/* Left Column - Form */}
        <div className="flex-1 min-w-0">
          <Card className="p-8 shadow-md border border-gray-100 bg-white min-h-[600px]">
            <div className="mb-10">
              <h2 className="text-3xl font-light text-gray-400 uppercase tracking-tight">Add Expense</h2>
            </div>

            <form className="space-y-10">
              {/* Expense Date */}
              <div className="relative">
                <label className="block text-sm font-medium text-gray-500 mb-1">
                  Expense Date <span className="text-red-500">*</span>
                </label>
                <input
                  type="date"
                  value={formData.date}
                  onChange={(e) => setFormData({ ...formData, date: e.target.value })}
                  className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800"
                />
              </div>

              {/* Expense Type */}
              <div className="relative">
                <label className="block text-sm font-medium text-gray-500 mb-1">
                  Expense Type <span className="text-red-500">*</span>
                </label>
                <select
                  value={formData.type}
                  onChange={(e) => setFormData({ ...formData, type: e.target.value })}
                  className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 appearance-none"
                >
                  <option value="">Select Type</option>
                  <option value="Operating">Operating</option>
                  <option value="Non-Operating">Non-Operating</option>
                </select>
                <div className="absolute right-0 bottom-3 pointer-events-none text-gray-400">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
              </div>

              {/* Expense Category */}
              <div className="relative">
                <label className="block text-sm font-medium text-gray-500 mb-1">
                  Expense Category <span className="text-red-500">*</span>
                </label>
                <select
                  value={formData.category}
                  onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                  className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 appearance-none"
                >
                  <option value="">Select Category</option>
                  <option value="Fuel">Fuel</option>
                  <option value="Marketing">Marketing</option>
                  <option value="Maintenance">Maintenance</option>
                </select>
                <div className="absolute right-0 bottom-3 pointer-events-none text-gray-400">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
              </div>

              {/* Expense Title */}
              <div className="relative">
                <label className="block text-sm font-medium text-gray-500 mb-1">
                  Expense Title
                </label>
                <input
                  type="text"
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  placeholder="Enter expense title"
                  className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 placeholder-gray-300"
                />
              </div>

              {/* Amount and GST Row */}
              <div className="flex flex-col md:flex-row gap-10">
                <div className="flex-1 relative">
                  <label className="block text-sm font-medium text-gray-500 mb-1">
                    Expense Amount ($) <span className="text-red-500">*</span>
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
                  </select>
                  <div className="absolute right-0 bottom-3 pointer-events-none text-gray-400">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                  </div>
                </div>
              </div>

              {/* Total Expense */}
              <div className="relative">
                <label className="block text-sm font-medium text-gray-500 mb-1">
                  Total Expense
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
                  Expense Description
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
                  Recurring Expense ?
                </label>
              </div>

              {/* Submit Button */}
              <div className="pt-6">
                <Button className="bg-blue-600 hover:bg-blue-700 text-white px-10 py-3 rounded shadow-lg transition-all transform active:scale-95 uppercase tracking-wider font-semibold">
                  Save Expense
                </Button>
              </div>
            </form>
          </Card>
        </div>

        {/* Right Column - Receipt */}
        <div className="xl:w-[360px] xl:flex-none">
          <Card className="p-8 shadow-md border border-gray-100 bg-white h-fit">
            <h2 className="text-3xl font-light text-gray-400 uppercase tracking-tight mb-8">Add/Update Receipt</h2>
            
            <div className="space-y-4">
              <div className="flex flex-col gap-2">
                <div className="flex items-center gap-4">
                  <button className="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm py-2 px-4 rounded border border-gray-300 transition-colors">
                    Browse...
                  </button>
                  <span className="text-sm text-gray-500">No file selected.</span>
                </div>
                <p className="text-xs text-gray-400 mt-2 italic">Allowed formats: JPG, PNG, PDF (Max 5MB)</p>
              </div>
            </div>
          </Card>
        </div>
      </div>
    </div>
  )
}
