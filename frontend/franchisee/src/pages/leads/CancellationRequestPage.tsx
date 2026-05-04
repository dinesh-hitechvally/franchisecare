import { Card } from '../../components/ui/Card'
import { Phone, ChevronLeft, ChevronRight } from 'lucide-react'

const cancellationData = [
  { id: '1', customer: 'Willis O\'Reilly', date: 'Thu, 6th Nov 2025 13:57', ref: '95M4B62', phone: '9803214525 / 1565861436', address: ', SOUTH YARRA', message: 'Cancellation reason: Test', links: ['View'] },
]

export function CancellationRequestPage() {
  return (
    <div className="space-y-5 px-1 py-1 w-full">
      {/* Top Header Card */}
      <Card className="px-6 py-4 shadow-sm border-gray-200">
        <h1 className="text-xl font-bold text-gray-800">Cancellation Request</h1>
      </Card>

      {/* Data Table Card */}
      <Card className="shadow-sm border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse border-b border-gray-200">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="px-3 py-4 w-12 text-center text-sm font-semibold text-gray-800"></th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-36">Customer Name</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-44">Cancellation Requested At</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-36">Reference Number</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-48">Phone</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-32">Address</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 max-w-[200px]">Message</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-28 text-center">Received From</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-20">Mgmt</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {cancellationData.map((row) => (
                <tr key={row.id} className="relative group hover:-translate-y-[1px] transition-transform hover:bg-gray-50">
                  <td className="px-3 py-4 align-top text-center">
                    <div className="w-2.5 h-2.5 rounded-full bg-green-500 mx-auto mt-2"></div>
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top pr-4">
                    {row.customer}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug">
                    {row.date.split(' ').map((part, index) => {
                      if (index === 2) return <span key={index}><br/>{part} </span>
                      if (index === 3) return <span key={index}>{part} </span>
                      return <span key={index}>{part} </span>
                    })}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug">
                    {row.ref}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug">
                    {row.phone.split(' / ').map((part, index) => (
                       <span key={index}>{part}{index === 0 && ' /'}<br/></span>
                    ))}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug">
                    {row.address}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug pr-8 whitespace-pre-line">
                    {row.message}
                  </td>
                  <td className="px-3 py-4 align-top text-center text-blue-700">
                    <Phone className="w-4 h-4 mx-auto rotate-90" />
                  </td>
                  <td className="px-3 py-4 text-sm align-top leading-relaxed">
                    <button className="text-blue-600 hover:underline text-left">
                      {row.links[0]}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* Pagination Container */}
        <div className="flex flex-col">
          <div className="px-5 py-3 flex items-center justify-end gap-6 text-sm text-gray-600">
            <div className="flex items-center gap-2">
              <span>Rows per page:</span>
              <select className="border border-gray-200 rounded p-1 outline-none text-gray-700">
                <option>25</option>
                <option>50</option>
                <option>100</option>
              </select>
            </div>
            <span>1-1 of 1</span>
            <div className="flex items-center gap-4 text-gray-400">
              <ChevronLeft className="w-5 h-5 cursor-not-allowed" />
              <ChevronRight className="w-5 h-5 cursor-not-allowed" />
            </div>
          </div>
        </div>
      </Card>
    </div>
  )
}
