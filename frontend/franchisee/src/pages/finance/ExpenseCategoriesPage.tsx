import { Card } from '../../components/ui/Card'
import { Check, X } from 'lucide-react'

export function ExpenseCategoriesPage() {
  const expenseTypes = [
    {
      type: 'Administration',
      categories: [
        { name: 'Accountancy', description: 'Fees for accounting services', entries: 12, gst: true, status: true },
        { name: 'Bank Fees', description: 'Monthly service fees', entries: 45, gst: false, status: true },
        { name: 'Donations', description: 'Charitable contributions', entries: 2, gst: false, status: true },
      ]
    },
    {
      type: 'Motor Vehicle',
      categories: [
        { name: 'Fuel', description: 'Fuel and oil for van', entries: 203, gst: true, status: true },
        { name: 'Maintenance', description: 'Repairs and servicing', entries: 18, gst: true, status: true },
        { name: 'Registration', description: 'Vehicle registration fees', entries: 1, gst: false, status: true },
      ]
    },
    {
      type: 'Trailer/Mobile Salon',
      categories: [
        { name: 'Shampoos/Sanitiser/Cologne', description: 'Consumable supplies', entries: 87, gst: true, status: true },
        { name: 'Equipment Repairs', description: 'Repairs for dryer, tub, etc.', entries: 5, gst: true, status: true },
      ]
    }
  ]

  return (
    <div className="space-y-6">
      {/* Top Header Banner */}
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Expense Categories</h1>
      </div>

      <div className="bg-white py-4 px-8 border-b border-gray-200 -mx-8 bg-gray-50/50">
        <h2 className="text-xl font-bold text-gray-700">Expense Categories</h2>
      </div>

      <Card className="border border-gray-200 shadow-sm overflow-hidden bg-white">
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left border-collapse">
            <thead className="bg-white border-b border-gray-200">
              <tr>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100 uppercase text-xs tracking-wider">Expense Type</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100 uppercase text-xs tracking-wider">Expense Category</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100 uppercase text-xs tracking-wider">Description</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center border-r border-gray-100 uppercase text-xs tracking-wider"># of Entries</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center border-r border-gray-100 uppercase text-xs tracking-wider">Gst Inclusive</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center uppercase text-xs tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100 bg-white">
              {expenseTypes.map((typeGroup) => (
                typeGroup.categories.map((cat, index) => (
                  <tr key={`${typeGroup.type}-${cat.name}`} className="hover:bg-gray-50/50 transition-colors">
                    {index === 0 && (
                      <td 
                        rowSpan={typeGroup.categories.length} 
                        className="px-6 py-4 text-gray-900 font-bold border-r border-gray-100 bg-gray-50/30 align-top"
                      >
                        {typeGroup.type}
                      </td>
                    )}
                    <td className="px-6 py-4 text-gray-700 font-medium border-r border-gray-100">{cat.name}</td>
                    <td className="px-6 py-4 text-gray-600 border-r border-gray-100">{cat.description}</td>
                    <td className="px-6 py-4 text-center text-gray-700 font-medium border-r border-gray-100">{cat.entries}</td>
                    <td className="px-6 py-4 border-r border-gray-100">
                      <div className="flex justify-center">
                        {cat.gst ? (
                          <Check className="w-5 h-5 text-green-500" strokeWidth={3} />
                        ) : (
                          <X className="w-5 h-5 text-gray-400" strokeWidth={3} />
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex justify-center">
                        <Check className="w-5 h-5 text-green-500" strokeWidth={3} />
                      </div>
                    </td>
                  </tr>
                ))
              ))}
            </tbody>
          </table>
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
