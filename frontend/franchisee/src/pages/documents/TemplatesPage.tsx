import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Search, Download, ChevronLeft, ChevronRight } from 'lucide-react'

export function TemplatesPage() {
  const [searchTerm, setSearchTerm] = useState('')

  const templates = [
    {
      id: '1',
      title: 'New Customer Intake Form',
      file: 'Templates',
      description: 'Pet Health Intake Form',
      type: 'pdf',
      lastUpdated: '2025-07-08 04:00:49'
    },
    {
      id: '2',
      title: 'CO-BRANDED WAIVER TO GROOM SENIOR DOGS',
      file: 'Templates',
      description: 'Waiver for grooming pregnant dogs.',
      type: 'pdf',
      lastUpdated: '2025-07-08 04:01:05'
    },
    {
      id: '3',
      title: 'Cologne Waiver',
      file: 'Templates',
      description: 'Customer agreement for scents.',
      type: 'pdf',
      lastUpdated: '2025-07-08 04:01:10'
    }
  ]

  return (
    <div className="space-y-6">
      {/* Top Header Card */}
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Templates</h1>
      </div>

      {/* Search Card */}
      <div className="max-w-4xl mx-auto w-full">
        <Card className="p-6 bg-white border border-gray-100 shadow-sm mb-6">
          <div className="relative max-w-lg mx-auto">
            <Search className="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search Templates"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2 bg-transparent border-b border-gray-200 focus:outline-none focus:border-blue-500 text-gray-700 placeholder-gray-400 transition-colors"
            />
          </div>
        </Card>

        {/* Data Table Card */}
        <Card className="border border-gray-200 shadow-sm overflow-hidden bg-white">
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left border-collapse">
              <thead className="bg-white border-b border-gray-100">
                <tr>
                  <th className="px-6 py-4 font-bold text-gray-400 uppercase text-xs tracking-wider">Title</th>
                  <th className="px-6 py-4 font-bold text-gray-400 uppercase text-xs tracking-wider">File</th>
                  <th className="px-6 py-4 font-bold text-gray-400 uppercase text-xs tracking-wider">Description</th>
                  <th className="px-6 py-4 font-bold text-gray-400 uppercase text-xs tracking-wider">Type</th>
                  <th className="px-6 py-4 font-bold text-gray-400 uppercase text-xs tracking-wider">Last Updated</th>
                  <th className="px-6 py-4 font-bold text-gray-400 uppercase text-xs tracking-wider font-semibold">Download</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50 bg-white">
                {templates.map((template) => (
                  <tr key={template.id} className="hover:bg-gray-50/50 transition-colors">
                    <td className="px-6 py-4 text-gray-800 font-medium">{template.title}</td>
                    <td className="px-6 py-4 text-gray-600">{template.file}</td>
                    <td className="px-6 py-4 text-gray-500 max-w-xs truncate">{template.description}</td>
                    <td className="px-6 py-4">
                      <div className="w-8 h-10 bg-red-50 text-red-500 flex items-center justify-center rounded">
                        <span className="text-[10px] font-bold uppercase">{template.type}</span>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-gray-500 whitespace-nowrap">{template.lastUpdated}</td>
                    <td className="px-6 py-4">
                      <button className="text-blue-500 hover:text-blue-700 transition-colors">
                        <Download className="w-6 h-6" />
                      </button>
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
            <span className="text-sm text-gray-600">1-{templates.length} of {templates.length}</span>
            <div className="flex items-center gap-1">
              <button className="p-1 hover:bg-gray-100 rounded-full disabled:opacity-30" disabled>
                <ChevronLeft className="w-5 h-5" />
              </button>
              <button className="p-1 hover:bg-gray-100 rounded-full disabled:opacity-30" disabled>
                <ChevronRight className="w-5 h-5" />
              </button>
            </div>
          </div>
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
