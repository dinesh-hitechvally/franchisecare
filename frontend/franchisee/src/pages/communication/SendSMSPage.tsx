import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Search, Send, ChevronUp, ChevronDown, Download } from 'lucide-react'

export function SendSMSPage() {
  const [genericExpanded, setGenericExpanded] = useState(true)
  const [bulkExpanded, setBulkExpanded] = useState(true)
  const [bulkReminderExpanded, setBulkReminderExpanded] = useState(true)
  
  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900 bg-white py-4 shadow-sm -mt-6 -mx-8 mb-6 px-8 text-center sm:text-left">
        Send SMS
      </h1>

      <div className="flex flex-col lg:flex-row gap-6 items-start">
        {/* Left Column (Generic SMS) */}
        <div className="w-full lg:w-5/12 shrink-0">
          <Card className="border border-gray-200 shadow-sm overflow-hidden">
            <div 
              className="bg-white p-4 border-b border-gray-100 flex justify-between items-center cursor-pointer hover:bg-gray-50 transition-colors"
              onClick={() => setGenericExpanded(!genericExpanded)}
            >
              <h2 className="font-semibold text-gray-800">Send Generic SMS</h2>
              {genericExpanded ? <ChevronUp className="w-5 h-5 text-gray-400" /> : <ChevronDown className="w-5 h-5 text-gray-400" />}
            </div>
            
            {genericExpanded && (
              <div className="p-6 space-y-6">
                <div>
                  <label className="block text-sm text-gray-700 mb-1">Select Customer Name</label>
                  <select className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                    <option>Select Customer</option>
                  </select>
                </div>
                
                <div>
                  <label className="block text-sm text-gray-700 mb-1">Mobile Number *</label>
                  <Input className="border-0 border-b border-gray-300 rounded-none px-0 focus:ring-0 focus:border-blue-600 bg-transparent w-full" />
                  <p className="text-xs text-gray-500 mt-1 italic">Enter mobile number if you want to SMS</p>
                </div>

                <div>
                  <label className="block text-sm text-gray-700 mb-1">Message Template</label>
                  <select className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                    <option>Select Option</option>
                  </select>
                </div>

                <div>
                  <label className="block text-sm text-gray-700 mb-1">SMS Text *</label>
                  <textarea 
                    className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 outline-none resize-none h-24"
                  />
                  <p className="text-xs text-gray-500 mt-1">SMS Message you want to send (160 Characters remaining)</p>
                  <p className="text-sm font-medium text-gray-800 mt-2">Number of SMS being sent: 1 SMS</p>
                </div>

                <div className="flex justify-end">
                  <label className="flex items-center gap-2 cursor-pointer">
                    <span className="text-sm text-gray-700">Save Message as template?</span>
                    <div className="w-10 h-5 bg-gray-300 rounded-full relative">
                      <div className="w-4 h-4 bg-white rounded-full absolute left-0.5 top-0.5 shadow-sm transition-all"></div>
                    </div>
                  </label>
                </div>

                <Button className="w-full justify-center bg-blue-600 hover:bg-blue-700 text-white gap-2 h-11">
                  <Send className="w-4 h-4" />
                  Send SMS Now
                </Button>
              </div>
            )}
          </Card>
        </div>

        {/* Right Column (Bulk SMS) */}
        <div className="w-full lg:w-7/12 space-y-6">
          <Card className="border border-gray-200 shadow-sm overflow-hidden">
            <div className="bg-gray-50 p-4 border-b border-gray-200 flex justify-between items-center">
              <h2 className="font-semibold text-gray-800">Bulk SMS</h2>
            </div>

            <div className="divide-y divide-gray-100">
              {/* Section A */}
              <div>
                <div 
                  className="bg-white p-4 flex justify-between items-center cursor-pointer hover:bg-gray-50 transition-colors"
                  onClick={() => setBulkExpanded(!bulkExpanded)}
                >
                  <h3 className="font-medium text-gray-800">Send Bulk Generic Messages</h3>
                  {bulkExpanded ? <ChevronUp className="w-5 h-5 text-gray-400" /> : <ChevronDown className="w-5 h-5 text-gray-400" />}
                </div>
                
                {bulkExpanded && (
                  <div className="p-6 bg-white space-y-6">
                    <p className="text-sm text-gray-600 italic">Select Customers to send common messages to the bulk clients.</p>
                    
                    <div>
                      <label className="block text-sm text-gray-700 mb-1">Select Customers *</label>
                      <div className="border border-gray-300 rounded-md p-3">
                        <div className="relative mb-3">
                          <Input placeholder="Search Customer" className="w-full bg-gray-50 border-gray-200" />
                          <Search className="w-4 h-4 text-gray-400 absolute right-3 top-1/2 transform -translate-y-1/2" />
                        </div>
                        <div className="flex items-center gap-2 mb-2 p-2 bg-gray-50 rounded">
                          <input type="checkbox" className="rounded border-gray-300" />
                          <span className="text-sm font-medium text-gray-700">Select All</span>
                        </div>
                        <div className="space-y-1 max-h-40 overflow-y-auto pl-2">
                          {/* Mocks */}
                          <div className="flex items-center gap-2 py-1">
                            <input type="checkbox" className="rounded border-gray-300" />
                            <span className="text-sm text-gray-600">2025 1013 - 9878999900</span>
                          </div>
                          <div className="flex items-center gap-2 py-1">
                            <input type="checkbox" className="rounded border-gray-300" />
                            <span className="text-sm text-gray-600">Arthur Avery Converted - 1196828722</span>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div>
                      <label className="block text-sm text-gray-700 mb-1">SMS Text *</label>
                      <textarea 
                        className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 outline-none resize-none h-24"
                      />
                      <p className="text-xs text-gray-500 mt-1">SMS Message you want to send (160 Characters remaining)</p>
                      <p className="text-sm font-medium text-gray-800 mt-2">Number of SMS being sent: 1 SMS</p>
                    </div>

                    <Button className="bg-blue-600 hover:bg-blue-700 text-white gap-2">
                      <Send className="w-4 h-4" />
                      Send Bulk Sms
                    </Button>
                  </div>
                )}
              </div>

              {/* Section B */}
              <div>
                <div 
                  className="bg-white p-4 flex justify-between items-center cursor-pointer hover:bg-gray-50 transition-colors"
                  onClick={() => setBulkReminderExpanded(!bulkReminderExpanded)}
                >
                  <h3 className="font-medium text-gray-800">Send Bulk Booking Reminders</h3>
                  {bulkReminderExpanded ? <ChevronUp className="w-5 h-5 text-gray-400" /> : <ChevronDown className="w-5 h-5 text-gray-400" />}
                </div>
                
                {bulkReminderExpanded && (
                  <div className="p-6 bg-white space-y-6">
                    <p className="text-sm text-gray-600 italic">Select Booking date to load existing bookings for specific date and send Mass text reminder to all your clients...</p>
                    
                    <div className="flex items-end gap-4">
                      <div className="flex-1">
                        <label className="block text-sm text-gray-700 mb-1">Select Booking Date *</label>
                        <Input type="text" value="April 2nd" readOnly className="border-0 border-b border-gray-300 rounded-none px-0 focus:ring-0 focus:border-blue-600 bg-transparent w-full text-gray-700" />
                      </div>
                      <Button className="bg-blue-600 hover:bg-blue-700 text-white gap-2 shrink-0">
                        <Download className="w-4 h-4" />
                        Load Bookings
                      </Button>
                    </div>

                    <div className="space-y-3">
                      <div className="bg-gray-50 p-3 rounded-md flex justify-between items-center cursor-pointer hover:bg-gray-100 transition-colors">
                        <span className="text-sm font-medium text-gray-700">Bookings on 2nd April, 2026</span>
                        <ChevronDown className="w-4 h-4 text-gray-500" />
                      </div>
                      <div className="bg-gray-50 p-3 rounded-md flex justify-between items-center cursor-pointer hover:bg-gray-100 transition-colors">
                        <span className="text-sm font-medium text-gray-700">Type Your Message</span>
                        <ChevronDown className="w-4 h-4 text-gray-500" />
                      </div>
                    </div>
                  </div>
                )}
              </div>

            </div>
          </Card>
        </div>
      </div>
    </div>
  )
}
