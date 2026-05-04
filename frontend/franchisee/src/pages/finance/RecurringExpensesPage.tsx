import { Card } from '../../components/ui/Card'
import { Check, ChevronLeft, ChevronRight } from 'lucide-react'

export function RecurringExpensesPage() {
  const recurringExpenses = [
    {
      id: '1',
      title: 'Car Loan',
      description: 'Monthly car loan repayment',
      category: 'Motor Vehicle > Loan',
      created: '31st Jul, 2023',
      amount: 200.75,
      recurStart: '9th Jul, 2023',
      recurRule: 'Every Week on Monday until 30th Jul, 2024',
      active: true
    },
    {
      id: '2',
      title: 'Mate Fees',
      description: 'Weekly service fees',
      category: 'Administration > Fees',
      created: '15th Apr, 2024',
      amount: 150.00,
      recurStart: '15th Apr, 2024',
      recurRule: 'Every Fortnight on Monday until 31st Dec, 2024',
      active: true
    }
  ]

  return (
    <div className="space-y-6">
      {/* Top Header Card */}
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Recurring Expenses</h1>
      </div>

      <div className="py-2">
        <h2 className="text-xl font-bold text-gray-700">Recurring Expenses</h2>
      </div>

      <Card className="border border-gray-200 shadow-sm overflow-hidden bg-white">
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left border-collapse">
            <thead className="bg-white border-b border-gray-200">
              <tr>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Title</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Description</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Category</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Created</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Amount</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Recur Start</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Recur</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100 text-center">Active</th>
                <th className="px-6 py-4 font-bold text-gray-900">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100 bg-white">
              {recurringExpenses.map((exp) => (
                <tr key={exp.id} className="hover:bg-gray-50 transition-colors">
                  <td className="px-6 py-4 text-gray-900 font-medium">{exp.title}</td>
                  <td className="px-6 py-4 text-gray-500">{exp.description}</td>
                  <td className="px-6 py-4 text-gray-600">{exp.category}</td>
                  <td className="px-6 py-4 text-gray-600 whitespace-nowrap">{exp.created}</td>
                  <td className="px-6 py-4 text-gray-900 font-bold">${exp.amount.toFixed(2)}</td>
                  <td className="px-6 py-4 text-gray-600 whitespace-nowrap">{exp.recurStart}</td>
                  <td className="px-6 py-4 text-gray-600 text-xs italic">{exp.recurRule}</td>
                  <td className="px-6 py-4">
                    <div className="flex justify-center">
                      {exp.active ? (
                        <Check className="w-5 h-5 text-green-500" strokeWidth={3} />
                      ) : (
                        <span className="text-gray-300">X</span>
                      )}
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <button className="text-blue-600 hover:underline font-semibold">Delete</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        <div className="px-6 py-4 bg-white border-t border-gray-100 flex items-center justify-end gap-6">
          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-500">Rows per page:</span>
            <select className="bg-transparent border-none text-sm font-medium focus:ring-0 outline-none cursor-pointer" defaultValue="25">
              <option>25</option>
              <option>50</option>
              <option>100</option>
            </select>
          </div>
          <span className="text-sm text-gray-600">1-24 of 24</span>
          <div className="flex items-center gap-1">
            <button className="p-1 hover:bg-gray-100 rounded-full">
              <ChevronLeft className="w-5 h-5" />
            </button>
            <button className="p-1 hover:bg-gray-100 rounded-full">
              <ChevronRight className="w-5 h-5" />
            </button>
          </div>
        </div>
      </Card>

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
