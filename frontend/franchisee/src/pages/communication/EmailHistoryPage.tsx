import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Table } from '../../components/ui/Table'
import { Button } from '../../components/ui/Button'

export function EmailHistoryPage() {
  const [activeTab, setActiveTab] = useState('SUCCESSFULLY SENT')

  const emails = [
    { 
      id: '1', 
      dateTime: 'Wednesday, 1st Apr 2026 \n 4:25 pm', 
      subject: 'Booking Remainder: Your booking information', 
      fromEmail: 'noreply@franchisecare.com.au', 
      toEmail: 'rabi@hitechvalley.com.au'
    },
    { 
      id: '2', 
      dateTime: 'Tuesday, 31st Mar 2026 \n 9:15 am', 
      subject: 'Test Email', 
      fromEmail: 'support@franchisecare.com.au', 
      toEmail: 'testuser@example.com' 
    },
    { 
      id: '3', 
      dateTime: 'Monday, 30th Mar 2026 \n 2:40 pm', 
      subject: 'New Mate Lead Added', 
      fromEmail: 'system@franchisecare.com.au', 
      toEmail: 'admin@franchisecare.com.au' 
    },
  ]

  const tabs = ['SUCCESSFULLY SENT', 'ON QUEUES']

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900 bg-white py-4 shadow-sm -mt-6 -mx-8 mb-6 px-8 text-center sm:text-left">
        Email History
      </h1>

      <div className="flex border-b border-gray-200 gap-8 px-2">
        {tabs.map((tab) => (
          <button
            key={tab}
            onClick={() => setActiveTab(tab)}
            className={`pb-4 text-sm font-semibold tracking-wide transition-colors relative ${
              activeTab === tab 
                ? 'text-pink-600' 
                : 'text-gray-500 hover:text-gray-700'
            }`}
          >
            {tab}
            {activeTab === tab && (
              <span className="absolute bottom-0 left-0 w-full h-0.5 bg-pink-600"></span>
            )}
          </button>
        ))}
      </div>

      <Card className="border border-gray-200 shadow-sm overflow-hidden">
        <Table
          columns={[
            { 
              key: 'dateTime', 
              title: 'Date/Time',
              render: (row) => <span className="text-gray-600 text-sm whitespace-pre-line leading-relaxed">{row.dateTime}</span>
            },
            { 
              key: 'subject', 
              title: 'Subject',
              render: (row) => <span className="text-gray-800 text-sm">{row.subject}</span>
            },
            { 
              key: 'fromEmail', 
              title: 'From Email',
              render: (row) => <span className="text-gray-800 text-sm">{row.fromEmail}</span>
            },
            { 
              key: 'toEmail', 
              title: 'To Email',
              render: (row) => <span className="text-gray-800 text-sm">{row.toEmail}</span>
            },
            { 
              key: 'viewEmail', 
              title: 'View Email',
              render: () => (
                <Button size="sm" className="bg-blue-600 hover:bg-blue-700 text-white font-bold tracking-wider text-xs px-6 py-[0.4rem] rounded uppercase">
                  VIEW
                </Button>
              )
            },
          ]}
          data={emails}
          keyExtractor={(row) => row.id}
        />

        {/* Footer/Pagination mockup for matching design */}
        <div className="p-4 border-t border-gray-100 bg-white flex justify-end items-center gap-6 text-sm text-gray-600">
          <div className="flex items-center gap-2">
            <span>Rows per page:</span>
            <select className="border-0 bg-transparent text-gray-800 focus:ring-0 cursor-pointer">
              <option>25</option>
              <option>50</option>
              <option>100</option>
            </select>
          </div>
          <span>1-25 of 200</span>
          <div className="flex items-center gap-4">
            <button className="text-gray-400 hover:text-gray-600 focus:outline-none">&lt;</button>
            <button className="text-gray-600 hover:text-gray-800 focus:outline-none">&gt;</button>
          </div>
        </div>
      </Card>
    </div>
  )
}
