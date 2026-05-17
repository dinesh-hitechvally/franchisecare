import { Card } from '../../components/ui/Card'
import { PageHeader } from '../../components/layout/PageHeader'
import { Repeat } from 'lucide-react'

export function RecurringIncomePage() {
  return (
    <div className="space-y-6">
      <PageHeader
        title="Recurring Income"
        icon={<Repeat className="w-5 h-5" />}
      />

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
                <th className="px-6 py-4 font-bold text-gray-900 text-right">Mgmt</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colSpan={8} className="px-6 py-20 text-center text-gray-500 italic bg-white">
                  No Recurring Income Found
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  )
}
