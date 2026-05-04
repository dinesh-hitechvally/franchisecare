import { Card } from '../../components/ui/Card'
import { Phone, Globe, ChevronLeft, ChevronRight } from 'lucide-react'

// Dummy data matching the screenshot
const completedLeads = [
  { id: '1', customer: 'Rabee Subedi2026', date: 'Tue, 24th Mar 2026 17:13', service: 'Wash only', phone: '9124185262 / 9246526556', address: 'Germany is a Western European country, BRISBANE ADELAIDE STREET', notes: 'Please ignore it.....', from: 'phone', links: ['View', 'Snooze'] },
  { id: '2', customer: 'test test2162026', date: 'Mon, 16th Feb 2026 16:40', service: 'Wash only', phone: '0213215416 / 9801312132', address: 'This is a lead created from dev team, please ignore if you received it., BRISBANE ADELAIDE STREET', notes: 'This lead is created from dev team, please ignore if you received.', from: 'phone', links: ['View', 'Snooze'] },
  { id: '3', customer: '1247 11545test', date: 'Tue, 27th Jan 2026 19:35', service: 'Wash only', phone: '1213416546 / 5455446465', address: 'This is a lead created from dev team, please ignore if you received it., BRISBANE ADELAIDE STREET', notes: 'This is a lead created from dev team, please ignore if you received it.', from: 'phone', links: ['View', 'Snooze'] },
  { id: '4', customer: 'TestLeadForHahah Pet', date: 'Tue, 27th Jan 2026 18:59', service: 'Wash only', phone: '0245655565 / 0245466656', address: 'This is a lead created from dev team, please ignore if you received it., BERALA', notes: 'This is a lead created from dev team, please ignore if you received it.', from: 'phone', links: ['View', 'Snooze'] },
  { id: '5', customer: 'TestForConverLead ByDevTeamRabi', date: 'Wed, 7th Jan 2026 13:27', service: 'Wash only', phone: '0756559889 / 0658979845', address: 'This is the lead created from dev team, please ignore if you received it., STRATHFIELD', notes: 'This is the lead created from dev team, please ignore if you received it.', from: 'phone', links: ['View', 'Snooze'] },
  { id: '6', customer: 'Laura Rach', date: 'Sat, 3rd Jan 2026 16:30', service: 'Clip and wash', phone: '0419895960', address: 'CLOUDES ST, SOUTH YARRA', notes: 'Pls call back re inquiry for a dog wash and also discussing other options in area for wash and clip.', from: 'phone', links: ['View', 'Snooze'] },
  { id: '7', customer: 'testlead pratik6', date: 'Wed, 3rd Dec 2025 17:35', service: 'Wash only', phone: '8565787867 / 4567686564', address: 'This is a lead created from dev team, please ignore if you received., SOUTH YARRA', notes: 'This is a lead created from dev team, please ignore if you received.', from: 'phone', links: ['View', 'Snooze'] },
  { id: '8', customer: 'TestLeadFrom Pratikdev5', date: 'Wed, 3rd Dec 2025 17:34', service: 'Clip and wash', phone: '4567863786 / 6786786865', address: 'This is a lead created from dev team, please ignore if you received., SOUTH YARRA', notes: 'This is a lead created from dev team, please ignore if you received.', from: 'phone', links: ['View', 'Snooze'] },
  { id: '9', customer: 'testlead pratik4', date: 'Wed, 3rd Dec 2025 16:49', service: 'Wash only', phone: '4245637868 / 4165465464', address: 'This is a lead created from dev team, please ignore if you received., SOUTH YARRA', notes: 'This is a lead created from dev team, please ignore if you received.', from: 'phone', links: ['View', 'Snooze'] },
  { id: '10', customer: 'testfromdev pratik3', date: 'Wed, 3rd Dec 2025 16:48', service: 'Wash only', phone: '2115616541 / 2165465465', address: 'This is a lead created from dev team, please ignore if you received., SOUTH YARRA', notes: 'This is a lead created from dev team, please ignore if you received.', from: 'phone', links: ['View', 'Snooze'] },
  { id: '11', customer: 'TestLead Pratik2', date: 'Wed, 3rd Dec 2025 12:46', service: 'Wash only', phone: '2652324651 / 4654215641', address: 'This is a lead created from dev team, please ignore if you received., SOUTH YARRA', notes: 'This is a lead created from dev team, please ignore if you received.', from: 'phone', links: ['View', 'Snooze'] },
  { id: '12', customer: 'TestDevTeam Pratik1', date: 'Wed, 3rd Dec 2025 12:45', service: 'Wash only', phone: '1616546546 / 5464654616', address: 'This is a lead created from dev team, please ignore if you received., SOUTH YARRA', notes: 'This is a lead created from dev team, please ignore if you received.', from: 'phone', links: ['View', 'Snooze'] },
]

export function CompletedLeadsPage() {

  return (
    <div className="space-y-5 px-1 py-1 w-full">
      {/* Top Header Card */}
      <Card className="px-6 py-4 shadow-sm border-gray-200">
        <h1 className="text-xl font-bold text-gray-800">Completed Leads</h1>
      </Card>

      {/* Data Table Card */}
      <Card className="shadow-sm border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse border-b border-gray-200">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="px-5 py-4 text-sm font-semibold text-gray-800 w-40">Customer Name</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-32">Added Date</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-28">Interested<br/>Services</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-40">Phone</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 max-w-sm">Address</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 max-w-[200px]">Notes</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-20 text-center">Leads<br/>From</th>
                <th className="px-3 py-4 text-sm font-semibold text-gray-800 w-24">Mgmt</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {completedLeads.map((row) => (
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
            <span>1-25 of 74</span>
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
