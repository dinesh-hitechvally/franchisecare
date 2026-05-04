import { Card } from '../../components/ui/Card'

export function RecurringIncomePage() {
  return (
    <div className="space-y-6">
      {/* Top Header Card */}
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Recurring Income</h1>
      </div>

      <div className="py-2">
        <h2 className="text-xl font-medium text-gray-700">Recurring Income</h2>
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
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Recur</th>
                <th className="px-6 py-4 font-bold text-gray-900">Active</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colSpan={7} className="px-6 py-20 text-center text-gray-500 italic bg-white">
                  No Recurring Income Found
                </td>
              </tr>
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
