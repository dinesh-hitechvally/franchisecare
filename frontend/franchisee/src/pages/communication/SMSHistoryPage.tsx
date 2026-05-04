import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Table } from '../../components/ui/Table'

export function SMSHistoryPage() {
  const [activeTab, setActiveTab] = useState('SUCCESSFULLY SENT')

  const messages = [
    { 
      id: '1', 
      dateTime: 'Wednesday, 25th Mar 2026 \n 7:06 pm',
      number: '61430831237', 
      customerName: 'Lawa Joshi',
      message: 'New Lead has been added into Mate System for Lawa Joshi on 25-03-2026 19:06:49 https://tinyurl.com/xyz', 
      status: 'Sent to Gateway' 
    },
    { 
      id: '2', 
      dateTime: 'Wednesday, 25th Mar 2026 \n 7:06 pm',
      number: '61430831238', 
      customerName: 'Rabee (Account) Subedi',
      message: 'Your SMS credit balance is low, please recharge soon.', 
      status: 'Sent to Gateway' 
    },
    { 
      id: '3', 
      dateTime: 'Tuesday, 24th Mar 2026 \n 10:15 am',
      number: '61430831239', 
      customerName: 'Rabee Dev Test Lead',
      message: 'Dear Rabee, new booking has been scheduled for you on 26-03-2026 10:00:00', 
      status: 'Sent to Gateway' 
    },
  ]

  const tabs = ['SUCCESSFULLY SENT', 'ON QUEUES']

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900 bg-white py-4 shadow-sm -mt-6 -mx-8 mb-6 px-8 text-center sm:text-left">
        SMS History
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
              key: 'number', 
              title: 'Sent to Number',
              render: (row) => <span className="text-gray-800 text-sm font-medium">{row.number}</span>
            },
            { 
              key: 'customerName', 
              title: 'Customer Name',
              render: (row) => <span className="text-gray-800 text-sm">{row.customerName}</span>
            },
            { 
              key: 'message', 
              title: 'Message',
              render: (row) => <span className="text-gray-600 text-sm">{row.message}</span>
            },
            { 
              key: 'status', 
              title: 'Status',
              render: (row) => <span className="text-gray-800 text-sm">{row.status}</span>
            },
          ]}
          data={messages}
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
          <span>1-25 of 253</span>
          <div className="flex items-center gap-4">
            <button className="text-gray-400 hover:text-gray-600 focus:outline-none">&lt;</button>
            <button className="text-gray-600 hover:text-gray-800 focus:outline-none">&gt;</button>
          </div>
        </div>
      </Card>
    </div>
  )
}
