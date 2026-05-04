import { Card } from '../../components/ui/Card'
import { Table } from '../../components/ui/Table'
import { Link } from 'react-router-dom'

export function MessageTemplatesPage() {
  const templates = [
    { 
      id: '1', 
      name: 'Booking Reminder Mass Email', 
      message: 'Dear ##firstname##, This Is A Reminder About Your Booking On ##datetime##, See You At ##serviceaddress##. Blue Wheelers', 
    },
    { 
      id: '2', 
      name: 'Booking Confirmed', 
      message: 'Dear ##firstname##, Your Booking Is Confirmed On ##datetime##, See You At ##serviceaddress##.', 
    },
    { 
      id: '3', 
      name: 'Booking Reminder', 
      message: 'Dear ##firstname##, This Is A Reminder About Your Booking On ##datetime##, See You At ##serviceaddress##.', 
    },
  ]

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900 bg-white py-4 shadow-sm -mt-6 -mx-8 mb-6 px-8 text-center sm:text-left">
        Message Template
      </h1>
      
      <h2 className="text-xl font-medium text-gray-800">
        Message Template
      </h2>

      <Card className="border border-gray-200 shadow-sm overflow-hidden">
        <Table
          columns={[
            { 
              key: 'name', 
              title: 'Template Name',
              render: (row) => <span className="text-gray-800 font-medium">{row.name}</span>
            },
            { 
              key: 'message', 
              title: 'Message',
              render: (row) => <span className="text-gray-600 block max-w-4xl">{row.message}</span>
            },
            { 
              key: 'mgmt', 
              title: 'Mgmt',
              render: () => (
                <Link to="#" className="text-blue-600 hover:text-blue-800 font-medium whitespace-nowrap">
                  Edit
                </Link>
              )
            },
          ]}
          data={templates}
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
          <span>1-3 of 3</span>
          <div className="flex items-center gap-4">
            <button className="text-gray-400 hover:text-gray-600 focus:outline-none">&lt;</button>
            <button className="text-gray-600 hover:text-gray-800 focus:outline-none">&gt;</button>
          </div>
        </div>
      </Card>
    </div>
  )
}
