import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Check, X } from 'lucide-react'

export function IncomeCategoriesPage() {
  const [categories] = useState([
    { id: '1', name: 'Booking Income', description: 'Income from Bookings', entries: 1489, gst: true, status: true, isSystem: true },
    { id: '2', name: 'Test', description: 'This is a testing tax inc.', entries: 0, gst: true, status: true, isSystem: false },
    { id: '3', name: 'Test Category', description: 'Testing category description', entries: 0, gst: false, status: true, isSystem: false },
  ])

  return (
    <div className="space-y-6">
      {/* Top Header Banner */}
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Income Categories</h1>
      </div>

      <div className="bg-white py-4 px-8 border-b border-gray-200 -mx-8 bg-gray-50/50">
        <h2 className="text-xl font-bold text-gray-700">Income Categories</h2>
      </div>

      <Card className="border border-gray-200 shadow-sm overflow-hidden bg-white">
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left border-collapse">
            <thead className="bg-white border-b border-gray-200">
              <tr>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Income Category</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Description</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center border-r border-gray-100"># of Entries</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center border-r border-gray-100">GST Inclusive</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center border-r border-gray-100">Status</th>
                <th className="px-6 py-4 font-bold text-gray-900">Mgmt</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100 bg-white">
              {categories.map((cat) => (
                <tr key={cat.id} className="hover:bg-gray-50 transition-colors">
                  <td className="px-6 py-4 text-gray-700 font-medium">{cat.name}</td>
                  <td className="px-6 py-4 text-gray-600">{cat.description}</td>
                  <td className="px-6 py-4 text-center text-gray-700 font-medium">{cat.entries}</td>
                  <td className="px-6 py-4">
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
                      {cat.status ? (
                        <Check className="w-5 h-5 text-green-500" strokeWidth={3} />
                      ) : (
                        <X className="w-5 h-5 text-gray-400" strokeWidth={3} />
                      )}
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    {!cat.isSystem && (
                      <div className="flex items-center gap-1">
                        <button className="text-blue-600 hover:underline font-semibold">Edit</button>
                        <span className="text-gray-400 font-light"> | </span>
                        <button className="text-blue-600 hover:underline font-semibold">Delete</button>
                      </div>
                    )}
                  </td>
                </tr>
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
