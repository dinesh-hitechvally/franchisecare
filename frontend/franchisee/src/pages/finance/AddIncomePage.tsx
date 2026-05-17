import { useMemo, useState, useEffect } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useNavigate, useParams } from 'react-router-dom'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { PageHeader } from '../../components/layout/PageHeader'
import { incomeCategoriesApi, incomesApi } from '../../api/services'
import { Loader2, DollarSign } from 'lucide-react'

export function AddIncomePage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { id } = useParams()
  const isEditMode = !!id && !window.location.pathname.includes('view')
  const isViewMode = !!id && window.location.pathname.includes('view')
  
  const [formData, setFormData] = useState({
    date: new Date().toISOString().split('T')[0],
    categoryId: '',
    title: '',
    amount: '',
    description: '',
    isRecurring: false,
    recurringStartDate: new Date().toISOString().split('T')[0],
    recurringEvery: 'weekly',
    autoExtendRecurring: false,
  })

  const { data: categories = [] } = useQuery({
    queryKey: ['income-categories'],
    queryFn: () => incomeCategoriesApi.getAll(),
  })

  // Fetch income data if editing or viewing
  const { data: incomeData, isLoading: isIncomeLoading } = useQuery({
    queryKey: ['income', id],
    queryFn: () => incomesApi.getById(id as string),
    enabled: !!id,
  })

  // Pre-populate form with income data
  useEffect(() => {
    if (incomeData && (isEditMode || isViewMode)) {
      setFormData({
        date: incomeData.income_date,
        categoryId: incomeData.income_category_id || '',
        title: incomeData.title,
        amount: String(incomeData.amount),
        description: incomeData.description || '',
        isRecurring: !!incomeData.recurring_income_id,
        recurringStartDate: incomeData.income_date,
        recurringEvery: 'weekly',
        autoExtendRecurring: false,
      })
    }
  }, [incomeData, isEditMode, isViewMode])

  const createIncomeMutation = useMutation({
    mutationFn: () => {
      const baseData = {
        income_category_id: formData.categoryId || undefined,
        title: formData.title,
        description: formData.description || undefined,
        amount: Number(formData.amount || 0),
        income_date: formData.date,
        is_active: true,
        is_recurring: formData.isRecurring,
        recurring_frequency: formData.recurringEvery,
        auto_extend_recurring: formData.autoExtendRecurring,
      }

      if (isEditMode && id) {
        return incomesApi.update(id, baseData)
      } else {
        return incomesApi.create(baseData)
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['incomes'] })
      navigate('/finance/income')
    },
  })

  const totalIncome = useMemo(() => {
    const amount = Number(formData.amount || 0)
    if (!Number.isFinite(amount)) return '0.00'
    return amount.toFixed(2)
  }, [formData.amount])

  const selectedCategory = useMemo(
    () => categories.find((category) => String(category.id) === String(formData.categoryId)),
    [categories, formData.categoryId],
  )

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (isViewMode) return
    if (!formData.date || !formData.categoryId || !formData.title || !formData.amount) return
    createIncomeMutation.mutate()
  }

  if (!!id && isIncomeLoading) {
    return (
      <div className="flex items-center justify-center h-screen">
        <Loader2 className="w-8 h-8 animate-spin text-gray-400" />
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={isViewMode ? 'View Income' : isEditMode ? 'Edit Income' : 'Add Income'}
        icon={<DollarSign className="w-5 h-5" />}
      />

      <div className="w-full grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        <Card className="p-8 shadow-md border border-gray-100 bg-white xl:col-span-2">
          <div className="mb-8">
            <h2 className="text-3xl font-light text-gray-400">
              {isViewMode ? 'View Income Details' : isEditMode ? 'Edit Income' : 'Add Income'}
            </h2>
          </div>

          <form className="space-y-8" onSubmit={handleSubmit}>
            {/* Income Date */}
            <div className="relative">
              <label className="block text-sm font-medium text-gray-500 mb-1">
                Income Date <span className="text-red-500">*</span>
              </label>
              <input
                type="date"
                value={formData.date}
                onChange={(e) => setFormData({ ...formData, date: e.target.value })}
                disabled={isViewMode}
                className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 disabled:text-gray-400 disabled:cursor-not-allowed"
              />
            </div>

            {/* Income Category */}
            <div className="relative">
              <label className="block text-sm font-medium text-gray-500 mb-1">
                Income Category <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.categoryId}
                onChange={(e) => setFormData({ ...formData, categoryId: e.target.value })}
                disabled={isViewMode}
                className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 appearance-none disabled:text-gray-400 disabled:cursor-not-allowed"
              >
                <option value="">Select Category</option>
                {categories.map((category) => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                  </option>
                ))}
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
                disabled={isViewMode}
                className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 placeholder-gray-300 disabled:text-gray-400 disabled:cursor-not-allowed"
              />
            </div>

            {/* Income Amount */}
            <div className="relative">
              <label className="block text-sm font-medium text-gray-500 mb-1">
                Income Amount ($) <span className="text-red-500">*</span>
              </label>
              <input
                type="number"
                value={formData.amount}
                onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
                disabled={isViewMode}
                className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 disabled:text-gray-400 disabled:cursor-not-allowed"
              />
            </div>

            {/* Total Income */}
            <div className="relative">
              <label className="block text-sm font-medium text-gray-500 mb-1">
                Total Income
              </label>
              <input
                type="text"
                readOnly
                value={totalIncome}
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
                disabled={isViewMode}
                className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 placeholder-gray-300 min-h-[40px] resize-none disabled:text-gray-400 disabled:cursor-not-allowed"
              ></textarea>
            </div>

            {/* Recurring Checkbox */}
            <div className="flex items-center gap-3">
              <input
                type="checkbox"
                id="isRecurring"
                checked={formData.isRecurring}
                onChange={(e) => setFormData({ ...formData, isRecurring: e.target.checked })}
                disabled={isViewMode}
                className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer disabled:cursor-not-allowed"
              />
              <label htmlFor="isRecurring" className="text-sm font-medium text-gray-600 cursor-pointer">
                Recurring Income ?
              </label>
            </div>

            {/* Recurring Details */}
            {formData.isRecurring && (
              <Card className="p-6 border border-gray-200 bg-gray-50/40">
                <div className="space-y-6">
                  <div className="relative">
                    <label className="block text-xs font-medium text-gray-500 mb-1">
                      Recurring Starts From Date* (Changing this date will change Income Date)
                    </label>
                    <input
                      type="date"
                      value={formData.recurringStartDate}
                      onChange={(e) => setFormData({ ...formData, recurringStartDate: e.target.value, date: e.target.value })}
                      disabled={isViewMode}
                      className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 disabled:text-gray-400 disabled:cursor-not-allowed"
                    />
                  </div>

                  <div className="relative max-w-[220px]">
                    <label className="block text-sm font-medium text-gray-500 mb-1">Every</label>
                    <select
                      value={formData.recurringEvery}
                      onChange={(e) => setFormData({ ...formData, recurringEvery: e.target.value })}
                      disabled={isViewMode}
                      className="w-full bg-transparent border-b border-gray-300 py-2 outline-none focus:border-blue-500 transition-colors text-gray-800 appearance-none disabled:text-gray-400 disabled:cursor-not-allowed"
                    >
                      <option value="daily">Daily</option>
                      <option value="weekly">Weekly</option>
                      <option value="monthly">Monthly</option>
                      <option value="yearly">Yearly</option>
                    </select>
                    <div className="absolute right-0 bottom-3 pointer-events-none text-gray-400">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                  </div>

                  <div className="flex items-center gap-3">
                    <input
                      type="checkbox"
                      id="autoExtendRecurring"
                      checked={formData.autoExtendRecurring}
                      onChange={(e) => setFormData({ ...formData, autoExtendRecurring: e.target.checked })}
                      disabled={isViewMode}
                      className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer disabled:cursor-not-allowed"
                    />
                    <label htmlFor="autoExtendRecurring" className="text-sm font-medium text-gray-600 cursor-pointer">
                      Auto extend recurring Income List until i manually cancel the recurring setting.?
                    </label>
                  </div>
                </div>
              </Card>
            )}

            {/* Action Buttons */}
            <div className="pt-6 flex gap-3">
              {isViewMode ? (
                <>
                  <Button
                    type="button"
                    onClick={() => navigate(`/finance/income/edit/${id}`)}
                    className="bg-blue-600 hover:bg-blue-700 text-white px-10 py-3 rounded shadow-lg transition-all transform active:scale-95"
                  >
                    Edit Income
                  </Button>
                  <Button
                    type="button"
                    onClick={() => navigate('/finance/income')}
                    className="bg-gray-600 hover:bg-gray-700 text-white px-10 py-3 rounded shadow-lg transition-all transform active:scale-95"
                  >
                    Back
                  </Button>
                </>
              ) : (
                <>
                  <Button
                    type="submit"
                    disabled={createIncomeMutation.isPending}
                    className="bg-blue-600 hover:bg-blue-700 text-white px-10 py-3 rounded shadow-lg transition-all transform active:scale-95 disabled:opacity-60"
                  >
                    {createIncomeMutation.isPending ? 'Saving...' : isEditMode ? 'Update Income' : 'Save Income'}
                  </Button>
                  <Button
                    type="button"
                    onClick={() => navigate('/finance/income')}
                    className="bg-gray-600 hover:bg-gray-700 text-white px-10 py-3 rounded shadow-lg transition-all transform active:scale-95"
                  >
                    Cancel
                  </Button>
                </>
              )}
            </div>
          </form>
        </Card>

        <Card className="p-6 shadow-md border border-gray-100 bg-white xl:sticky xl:top-6">
          <h3 className="text-lg font-semibold text-gray-800">Income Snapshot</h3>
          <p className="text-xs text-gray-500 mt-1">Live preview of the income entry details.</p>

          <div className="mt-6 space-y-4">
            <div className="pb-3 border-b border-gray-100">
              <p className="text-[11px] uppercase tracking-wide text-gray-400">Category</p>
              <p className="text-sm font-medium text-gray-800 mt-1">{selectedCategory?.name || 'Not selected'}</p>
            </div>

            <div className="pb-3 border-b border-gray-100">
              <p className="text-[11px] uppercase tracking-wide text-gray-400">Title</p>
              <p className="text-sm font-medium text-gray-800 mt-1">{formData.title || 'Not entered'}</p>
            </div>

            <div className="pb-3 border-b border-gray-100">
              <p className="text-[11px] uppercase tracking-wide text-gray-400">Income Date</p>
              <p className="text-sm font-medium text-gray-800 mt-1">{formData.date || '-'}</p>
            </div>

            <div className="pb-3 border-b border-gray-100">
              <p className="text-[11px] uppercase tracking-wide text-gray-400">Amount</p>
              <p className="text-xl font-bold text-gray-900 mt-1">${totalIncome}</p>
            </div>

            <div className="pb-3 border-b border-gray-100">
              <p className="text-[11px] uppercase tracking-wide text-gray-400">Recurring</p>
              <p className="text-sm font-medium text-gray-800 mt-1">
                {formData.isRecurring ? `Yes, ${formData.recurringEvery}` : 'No'}
              </p>
            </div>

            <div>
              <p className="text-[11px] uppercase tracking-wide text-gray-400">Required Checklist</p>
              <ul className="mt-2 space-y-1 text-sm">
                <li className={formData.date ? 'text-green-600' : 'text-gray-400'}>Income date</li>
                <li className={formData.categoryId ? 'text-green-600' : 'text-gray-400'}>Income category</li>
                <li className={formData.title ? 'text-green-600' : 'text-gray-400'}>Income title</li>
                <li className={formData.amount ? 'text-green-600' : 'text-gray-400'}>Income amount</li>
              </ul>
            </div>
          </div>
        </Card>
      </div>
    </div>
  )
}
