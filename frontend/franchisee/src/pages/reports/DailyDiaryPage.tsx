import { useState } from 'react'
import { Printer, Calendar as CalendarIcon, Search } from 'lucide-react'

interface DiaryEntry {
  time: string
  name: string
  mobile: string
  address: string
  service: string
  amount: string
  notes: string
}

export function DailyDiaryPage() {
  const [fromDate, setFromDate] = useState('2026-04-03')
  const [toDate, setToDate] = useState('2026-04-03')

  const mockEntries: DiaryEntry[] = [
    {
      time: '09:00 AM',
      name: 'John Doe',
      mobile: '0412 345 678',
      address: '123 Bark Lane, Suburbia',
      service: 'Full Groom - Large Dog',
      amount: '$85.00',
      notes: 'Loves treats, a bit nervous around dryers.',
    },
    {
      time: '11:30 AM',
      name: 'Sarah Smith',
      mobile: '0422 555 888',
      address: '45 Paws Street, Dogtown',
      service: 'Bath & Brush',
      amount: '$45.00',
      notes: 'Sensitive skin, use oatmeal shampoo.',
    },
    {
      time: '02:00 PM',
      name: 'Mike Johnson',
      mobile: '0433 999 111',
      address: '78 Woof Avenue, Houndhill',
      service: 'Nail Clipping & Ear Cleaning',
      amount: '$30.00',
      notes: 'Very friendly Golden Retriever.',
    },
  ]

  const handlePrint = () => {
    window.print()
  }

  return (
    <div className="flex flex-col gap-6 p-6 min-h-screen bg-[#f4f6f8]">
      {/* Page Title Card */}
      <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-100 flex items-center justify-between">
        <h1 className="text-xl font-bold text-gray-800">Daily Diary</h1>
        <button 
          onClick={handlePrint}
          className="flex items-center gap-2 px-4 py-2 bg-secondary-100 hover:bg-secondary-200 text-secondary-700 rounded-md transition-colors text-sm font-medium"
        >
          <Printer size={18} />
          Print Diary
        </button>
      </div>

      {/* Filter Card */}
      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-2">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
          <div className="space-y-1.5">
            <label className="text-sm font-medium text-gray-600">From</label>
            <div className="relative">
              <input
                type="date"
                value={fromDate}
                onChange={(e) => setFromDate(e.target.value)}
                className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500/20"
              />
            </div>
          </div>
          <div className="space-y-1.5">
            <label className="text-sm font-medium text-gray-600">To</label>
            <div className="relative">
              <input
                type="date"
                value={toDate}
                onChange={(e) => setToDate(e.target.value)}
                className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500/20"
              />
            </div>
          </div>
          <button className="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-md transition-all shadow-sm font-medium text-sm">
            <Search size={18} />
            Generate Daily Diary Report
          </button>
        </div>
      </div>

      {/* Report Content Card */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden print:shadow-none print:border-none">
        <div className="p-6 bg-gray-50/50 border-b border-gray-100">
          <h2 className="text-lg font-bold text-gray-800">
            Daily Diary ({fromDate} to {toDate})
          </h2>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-gray-50/50">
                <th className="p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">Time</th>
                <th className="p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">Customer Name</th>
                <th className="p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">Mobile</th>
                <th className="p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">Address</th>
                <th className="p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">Service</th>
                <th className="p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 text-right">Amount</th>
                <th className="p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">Notes</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {mockEntries.map((entry, index) => (
                <tr key={index} className="hover:bg-primary-50/30 transition-colors">
                  <td className="p-4 text-sm font-medium text-gray-700 whitespace-nowrap">{entry.startTime}</td>
                  <td className="p-4 text-sm text-gray-600 font-medium">{entry.name}</td>
                  <td className="p-4 text-sm text-gray-600 font-mono">{entry.mobile}</td>
                  <td className="p-4 text-sm text-gray-600 max-w-xs truncate">{entry.address}</td>
                  <td className="p-4">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                      {entry.service}
                    </span>
                  </td>
                  <td className="p-4 text-sm font-bold text-gray-900 text-right">{entry.amount}</td>
                  <td className="p-4 text-sm text-gray-500 italic truncate max-w-xs">"{entry.notes}"</td>
                </tr>
              ))}
            </tbody>
          </table>
          {mockEntries.length === 0 && (
            <div className="p-12 text-center text-gray-400 italic">
              No entries found for the selected date range.
            </div>
          )}
        </div>
      </div>
      
      {/* Printable Styling */}
      <style>{`
        @media print {
          body { background: white !important; }
          .p-6 { padding: 0 !important; }
          .gap-6 { gap: 0 !important; }
          button, label, header, aside, .p-4.rounded-lg.shadow-sm.border.border-gray-100:first-child { display: none !important; }
          .shadow-sm, .border { border: none !important; box-shadow: none !important; }
          .bg-[#f4f6f8] { background: white !important; }
          table { width: 100% !important; border: 1px solid #eee !important; border-collapse: collapse !important; }
          th { background: #f9fafb !important; color: black !important; border-bottom: 2px solid #ddd !important; }
          td { border-bottom: 1px solid #eee !important; }
          h1, h2 { color: black !important; margin-bottom: 20px !important; }
        }
      `}</style>
    </div>
  )
}
