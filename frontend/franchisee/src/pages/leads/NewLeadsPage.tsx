import { Card } from '../../components/ui/Card'
import { Phone, Globe, ChevronLeft, ChevronRight } from 'lucide-react'

// Dummy data matching the screenshot
const newLeads = [
  { id: '1', customer: 'Dev2026 date325', date: 'Wed, 25th Mar 2026 19:06', service: 'Wash only', phone: '9232631262 / 1321651651', address: 'This lead is created from dev team, please ignore if you received., BRISBANE ADELAIDE STREET', notes: 'This lead is created from dev team, please ignore if you received.', from: 'phone', links: ['View', 'Snooze'] },
  { id: '2', customer: 'LeadCreated FromDevTeam', date: 'Tue, 17th Mar 2026 15:51', service: 'Wash only', phone: '7989256464 / 7989256115', address: 'Adipisci officiis et, BRISBANE CITY', notes: 'Test lead created from dev team, please ignore it.', from: 'phone', links: ['View', 'Snooze'] },
  { id: '3', customer: 'test test', date: 'Tue, 24th Feb 2026 13:08', service: 'Clip and wash', phone: '1234567890 / 1234567890', address: 'Kealba, KEALBA', notes: 'This lead is created by Mate team - please ignore', from: 'phone', links: ['View', 'Snooze'] },
  { id: '4', customer: 'Test FromDEVrabi', date: 'Thu, 30th Oct 2025 12:40', service: 'Wash only', phone: '9745100000 / 9745200000', address: 'This lead is created from the dev team, please ignore if you received. Thank you., SOUTH YARRA', notes: 'This lead is created from the dev team, please ignore if you received. Thank you.---Comment', from: 'phone', links: ['View', 'Snooze'] },
  { id: '5', customer: 'Iona Morgan', date: 'Fri, 17th Oct 2025 13:31', service: 'Wash only', phone: '1796183164 / 1595225721', address: 'Amet ex laboris eaq, SOUTH YARRA', notes: 'Please ignore it(Comment)', from: 'phone', links: ['View', 'Snooze'] },
  { id: '6', customer: 'Carly Cochran', date: 'Fri, 17th Oct 2025 13:25', service: 'Wash only', phone: '1355618696 / 1984803568', address: 'Quaerat dolorum est, SOUTH YARRA', notes: 'Please ignore this lead (Comment)', from: 'phone', links: ['View', 'Snooze'] },
  { id: '7', customer: 'first name', date: 'Fri, 17th Oct 2025 12:45', service: 'Wash only', phone: '6666666666', address: 'Australia, SOUTH YARRA', notes: 'Please ignore it. comment.', from: 'phone', links: ['View', 'Snooze'] },
  { id: '8', customer: 'DevTest 20250916', date: 'Tue, 16th Sep 2025 17:42', service: 'Wash only', phone: '9870000000 / 9874100000', address: 'This lead is created from the dev team, please ignore if you received., SOUTH YARRA', notes: 'This is a note or comment from operator', from: 'phone', links: ['View', 'Snooze'] },
  { id: '9', customer: 'Dev Test', date: 'Mon, 15th Sep 2025 12:54', service: 'Wash only', phone: '9800323232 / 9815515215', address: 'This is a test lead created from dev team, please ignore it, SOUTH YARRA', notes: 'This is a test lead created from dev team, please ignore it', from: 'phone', links: ['View', 'Snooze'] },
  { id: '10', customer: 'Dev Test', date: 'Mon, 15th Sep 2025 12:54', service: 'Wash only', phone: '9800323232 / 9815515215', address: 'This is a test lead created from dev team, please ignore it, SOUTH YARRA', notes: 'This is a test lead created from dev team, please ignore it', from: 'phone', links: ['View', 'Snooze'] },
  { id: '11', customer: 'Holly Ashley', date: 'Wed, 10th Sep 2025 14:19', service: 'Wash only', phone: '1656822153 / 1745898725', address: '9802145214, SOUTH YARRA', notes: 'This is comments to an operator/head office', from: 'phone', links: ['View', 'Snooze'] },
  { id: '12', customer: 'Dinesh Ghimimre', date: 'Tue, 9th Sep 2025 03:09', service: 'Wash and Coat Clipped', phone: '9800000000', address: '14 Sims St, Prahran', notes: 'Please ignore it', from: 'globe', links: ['View', 'Snooze'] },
  { id: '13', customer: 'TEST TEST', date: 'Thu, 7th Aug 2025 14:07', service: 'Wash only', phone: '7989256263', address: 'Please ignore this lead, created from dev team., SOUTH YARRA', notes: '', from: 'phone', links: ['View', 'Snooze'] },
]

export function NewLeadsPage() {

  return (
    <div className="space-y-5 px-1 py-1 w-full">
      {/* Top Header Card */}
      <Card className="px-6 py-4 shadow-sm border-gray-200">
        <h1 className="text-xl font-bold text-gray-800">List Of New Leads</h1>
      </Card>

      {/* Data Table Card */}
      <Card className="shadow-sm border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse border-b border-gray-200">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="px-3 py-4 w-12 text-center text-sm font-semibold text-gray-800"></th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-32">Customer Name</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-32">Added Date</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-28">Interested<br/>Services</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-36">Phone</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 max-w-xs">Address</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 max-w-[200px]">Notes</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-20 text-center">Leads<br/>From</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-24">Mgmt</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {newLeads.map((row) => (
                <tr key={row.id} className="relative group hover:-translate-y-[1px] transition-transform hover:bg-gray-50">
                  <td className="px-3 py-4 align-top text-center">
                    <div className="w-2 h-2 rounded-full bg-red-600 mx-auto mt-2"></div>
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
                    {row.service}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug">
                    {row.phone.split(' / ').map((part, index) => (
                       <span key={index}>{part}{index === 0 && ' /'}<br/></span>
                    ))}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug pr-8">
                    {row.address}
                  </td>
                  <td className="px-3 py-4 text-sm text-gray-700 align-top leading-snug pr-8">
                    {row.notes}
                  </td>
                  <td className="px-3 py-4 align-top text-center text-blue-700">
                    {row.from === 'phone' ? (
                       <Phone className="w-4 h-4 mx-auto rotate-90" />
                    ) : (
                       <Globe className="w-4 h-4 mx-auto" />
                    )}
                  </td>
                  <td className="px-3 py-4 text-sm align-top leading-relaxed">
                    <div className="flex flex-col gap-0.5">
                      <button className="text-blue-600 hover:underline text-left">
                        {row.links[0]} |
                      </button>
                      <button className="text-blue-600 hover:underline text-left">
                        {row.links[1]}
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
            <span>1-25 of 33</span>
            <div className="flex items-center gap-4 text-gray-400">
              <ChevronLeft className="w-5 h-5 cursor-not-allowed" />
              <ChevronRight className="w-5 h-5 cursor-pointer hover:text-gray-700" />
            </div>
          </div>
        </div>
      </Card>
    </div>
  )
}
