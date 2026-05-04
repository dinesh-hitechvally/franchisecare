import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Mail, ChevronUp, ChevronDown } from 'lucide-react'

export function SendEmailPage() {
  const [genericExpanded, setGenericExpanded] = useState(true)
  const [bulkExpanded, setBulkExpanded] = useState(true)
  
  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900 bg-white py-4 shadow-sm -mt-6 -mx-8 mb-6 px-8 text-center sm:text-left">
        Send Email
      </h1>

      <div className="flex flex-col lg:flex-row gap-6 items-start">
        {/* Left Column (Send Generic Email) */}
        <div className="w-full lg:w-7/12 shrink-0">
          <Card className="border border-gray-200 shadow-sm overflow-hidden">
            <div 
              className="bg-white p-4 border-b border-gray-100 flex justify-between items-center cursor-pointer hover:bg-gray-50 transition-colors"
              onClick={() => setGenericExpanded(!genericExpanded)}
            >
              <h2 className="font-semibold text-gray-800">Send Generic Email</h2>
              {genericExpanded ? <ChevronUp className="w-5 h-5 text-gray-400" /> : <ChevronDown className="w-5 h-5 text-gray-400" />}
            </div>
            
            {genericExpanded && (
              <div className="p-6 space-y-6">
                <div>
                  <label className="block text-sm text-gray-700 mb-1">Select Customer Name</label>
                  <select className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 outline-none bg-white text-gray-500">
                    <option>Select Customer</option>
                  </select>
                </div>
                
                <div>
                  <label className="block text-sm text-gray-700 mb-1">Email *</label>
                  <Input className="border-0 border-b border-gray-300 rounded-none px-0 focus:ring-0 focus:border-blue-600 bg-transparent w-full" />
                  <p className="text-xs text-gray-500 mt-1 italic">Enter email address if you want to send email</p>
                </div>

                <div>
                  <label className="block text-sm text-gray-700 mb-1">Email Subject *</label>
                  <Input className="border-0 border-b border-gray-300 rounded-none px-0 focus:ring-0 focus:border-blue-600 bg-transparent w-full" />
                  <p className="text-xs text-gray-500 mt-1 italic">Enter subject for your email if you want to send email</p>
                </div>

                <div>
                  <label className="block text-sm text-gray-700 mb-1">Email Text *</label>
                  <textarea 
                    className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 outline-none resize-none h-48"
                  />
                </div>

                <Button className="justify-center bg-blue-600 hover:bg-blue-700 text-white gap-2">
                  <Mail className="w-4 h-4" />
                  Send Email Now
                </Button>
              </div>
            )}
          </Card>
        </div>

        {/* Right Column (Bulk Email) */}
        <div className="w-full lg:w-5/12 space-y-6">
          <Card className="border border-gray-200 shadow-sm overflow-hidden">
            <div className="bg-gray-50 p-4 border-b border-gray-200 flex justify-between items-center">
              <h2 className="font-semibold text-gray-800">Bulk Email</h2>
              {bulkExpanded ? (
                <ChevronUp className="w-5 h-5 text-gray-400 cursor-pointer" onClick={() => setBulkExpanded(false)} />
              ) : (
                <ChevronDown className="w-5 h-5 text-gray-400 cursor-pointer" onClick={() => setBulkExpanded(true)} />
              )}
            </div>

            {bulkExpanded && (
              <div className="divide-y divide-gray-100 p-4 space-y-4">
                {/* Accordion A */}
                <div className="bg-gray-50 p-3 rounded-md flex justify-between items-center cursor-pointer hover:bg-gray-100 transition-colors border border-gray-200">
                  <h3 className="text-sm font-medium text-gray-800">Send Bulk Generic Messages</h3>
                  <ChevronDown className="w-4 h-4 text-gray-500" />
                </div>

                {/* Accordion B */}
                <div className="bg-gray-50 p-3 rounded-md flex justify-between items-center cursor-pointer hover:bg-gray-100 transition-colors border border-gray-200">
                  <h3 className="text-sm font-medium text-gray-800">Send Booking List to Customers</h3>
                  <ChevronDown className="w-4 h-4 text-gray-500" />
                </div>
              </div>
            )}
          </Card>
        </div>
      </div>
    </div>
  )
}
