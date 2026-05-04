import { Card } from '../../components/ui/Card'
import { Phone, ChevronLeft, ChevronRight } from 'lucide-react'

const messageData = [
  { id: '1', customer: 'Maisie Head', date: 'Thu, 6th Nov 2025 13:57', ref: '95M4B62', phone: '9803214525 / 1565861436', address: ', SOUTH YARRA', message: 'Please ignore it.(Testing)', links: ['View'] },
  { id: '2', customer: 'Mia Whitney', date: 'Thu, 6th Nov 2025 13:46', ref: '29K5S23', phone: '9806065612 / 1441217259', address: ', SOUTH YARRA', message: 'Please ignore it( Dev testing )', links: ['View'] },
  { id: '3', customer: 'Ivana Roman', date: 'Thu, 6th Nov 2025 13:37', ref: '83G34P4', phone: '9800000000 / 1187123237', address: ', SOUTH YARRA', message: 'Please ignore it(Dev team testing)', links: ['View'] },
  { id: '4', customer: 'Sarah Dickson', date: 'Thu, 16th Oct 2025 14:54', ref: '68G5M71', phone: '1648494474 / 1545104555', address: ', SOUTH YARRA', message: 'Please ignore it.', links: ['View'] },
  { id: '5', customer: 'Test Lead', date: 'Thu, 16th Oct 2025 13:47', ref: '7436A6K', phone: '9147021510 / 9124554464', address: ', SOUTH YARRA', message: '', links: ['View'] },
  { id: '6', customer: 'DevTesting Recent Version', date: 'Thu, 16th Oct 2025 13:44', ref: '6I74X85', phone: '9825451252 / 4666644656', address: ', SOUTH YARRA', message: '', links: ['View'] },
]

export function MessageForOperatorPage() {
  return (
    <div className="space-y-5 px-1 py-1 w-full">
      {/* Top Header Card */}
      <Card className="px-6 py-4 shadow-sm border-gray-200">
        <h1 className="text-xl font-bold text-gray-800">Message For Operator</h1>
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
              {messageData.map((row) => (
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
            <span>1-6 of 6</span>
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
