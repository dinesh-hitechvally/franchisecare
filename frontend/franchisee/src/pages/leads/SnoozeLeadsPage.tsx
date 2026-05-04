import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Globe, ChevronLeft, ChevronRight, Search } from 'lucide-react'

// Dummy data matching the screenshot
const snoozeLeads = [
  { id: '1', customer: '44 55', date: 'Fri, 6th Jun 2025 05:00', service: 'Wash', phone: '0311387785', address: 'South Yarra, South Yarra', notes: 'db lead create', snoozeNotes: 'I need to update THIS on other\nlead section', from: 'globe', links: ['View', 'Re-activate'] },
  { id: '2', customer: 'DB One Ten', date: 'Fri, 6th Jun 2025 05:00', service: 'Wash', phone: '0311689567', address: 'South Yarra, South Yarra', notes: 'db lead create', snoozeNotes: 'Phone Rang out', from: 'globe', links: ['View', 'Re-activate'] },
  { id: '3', customer: 'Dinesh Ghimire', date: 'Sun, 14th Jul 2024 10:00', service: 'Wash', phone: '9800000000', address: 'South Yarra, South Yarra', notes: 'Notes2', snoozeNotes: 'Phone Rang out', from: 'globe', links: ['View', 'Re-activate'] },
  { id: '4', customer: 'Maureen McKenzie', date: 'Wed, 2nd Feb 2022 11:58', service: 'Wash Only', phone: '+61418826058', address: 'Unit 8, 2-8 Barnet Road,,', notes: 'I have a Mini Labradoodle named River\nshe is 18mths old. She had a bad\nexperience having her toe nails cut at a\nsalon. I hope you will be a better fit.', snoozeNotes: 'Phone Rang out', from: 'globe', links: ['View', 'Re-activate'] },
  { id: '5', customer: 'Louisa Lovo', date: 'Wed, 2nd Feb 2022 11:28', service: 'Wash and Coat Clipped', phone: '0478171879', address: '53 Casuarina Circuit,', notes: 'Hi, we have a 20kg Labradoodle and we\nare looking for a groomer. We live in\nHeathwood.', snoozeNotes: 'Phone Rang out', from: 'globe', links: ['View', 'Re-activate'] },
]

export function SnoozeLeadsPage() {
  const [searchTerm, setSearchTerm] = useState('')

  return (
    <div className="space-y-5 px-1 py-1 w-full">
      {/* Top Header Card */}
      <Card className="px-6 py-4 shadow-sm border-gray-200">
        <h1 className="text-xl font-bold text-gray-800">Snooze Leads</h1>
      </Card>

      {/* Search Bar Card */}
      <Card className="px-6 py-8 shadow-sm border-gray-200">
        <div className="relative max-w-lg">
          <Search className="w-5 h-5 absolute left-0 top-1/2 transform -translate-y-1/2 text-gray-400" />
          <input
            type="text"
            placeholder="Search snoozed leads"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full pl-8 pr-4 py-2 border-b border-gray-200 focus:outline-none focus:border-blue-500 text-sm text-gray-700 bg-transparent"
          />
        </div>
      </Card>

      {/* Data Table Card */}
      <Card className="shadow-sm border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse border-b border-gray-200">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 w-36">Customer<br/>Name</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-32">Added Date</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-28">Interested<br/>Services</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-36">Phone</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-40">Address</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 max-w-[200px]">Notes</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-40">Snooze Notes</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-16 text-center">Leads<br/>From</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-24">Mgmt</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {snoozeLeads.map((row) => (
                <tr key={row.id} className="relative group hover:-translate-y-[1px] transition-transform hover:bg-gray-50">
                  <td className="px-5 py-4 text-sm text-gray-700 align-top pr-4">
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
                    {row.service}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug">
                    {row.phone}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug pr-4">
                    {row.address}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug pr-4 whitespace-pre-line">
                    {row.notes}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug pr-4 whitespace-pre-line">
                    {row.snoozeNotes}
                  </td>
                  <td className="px-3 py-4 align-top text-center text-blue-700">
                    <Globe className="w-4 h-4 mx-auto" />
                  </td>
                  <td className="px-3 py-4 text-sm align-top leading-relaxed">
                    <div className="flex flex-col gap-0.5">
                      <button className="text-blue-600 hover:underline text-left whitespace-nowrap">
                        {row.links[0]} | <span className="text-blue-600 hover:underline">{row.links[1]}</span>
                      </button>
                    </div>
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
            <span>1-5 of 5</span>
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
