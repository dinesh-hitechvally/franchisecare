import { useState, useMemo } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Search, Send, ChevronUp, ChevronDown, Download, Loader2, MessageSquare } from 'lucide-react'
import { customersApi, communicationHistoryApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import { PageHeader } from '../../components/layout/PageHeader'

export function SendSMSPage() {
  const queryClient = useQueryClient()
  const { addToast } = useToastStore()
  const [genericExpanded, setGenericExpanded] = useState(true)
  const [bulkExpanded, setBulkExpanded] = useState(true)
  const [bulkReminderExpanded, setBulkReminderExpanded] = useState(true)
  
  // Generic SMS form state
  const [selectedCustomerId, setSelectedCustomerId] = useState('')
  const [phoneNumber, setPhoneNumber] = useState('')
  const [message, setMessage] = useState('')
  
  // Bulk SMS state
  const [bulkSelectedCustomers, setBulkSelectedCustomers] = useState<string[]>([])
  const [bulkMessage, setBulkMessage] = useState('')
  const [customerSearch, setCustomerSearch] = useState('')

  // Fetch customers
  const { data: customers = [] } = useQuery({
    queryKey: ['customers'],
    queryFn: () => customersApi.getAll(),
  })

  // Filter customers for bulk SMS
  const filteredCustomers = useMemo(() => {
    if (!customerSearch) return customers
    const search = customerSearch.toLowerCase()
    return customers.filter(c => 
      `${c.first_name} ${c.last_name}`.toLowerCase().includes(search) ||
      c.phone?.includes(search)
    )
  }, [customers, customerSearch])

  // Send SMS mutation
  const sendSmsMutation = useMutation({
    mutationFn: (data: { to_number: string; customer_name?: string; message: string }) =>
      communicationHistoryApi.createSmsHistory({
        ...data,
        status: 'sent',
        sent_at: new Date().toISOString(),
      }),
    onSuccess: () => {
      addToast('SMS sent successfully', 'success')
      queryClient.invalidateQueries({ queryKey: ['sms-history'] })
    },
    onError: () => {
      addToast('Failed to send SMS', 'error')
    },
  })

  // Bulk send SMS mutation
  const bulkSendSmsMutation = useMutation({
    mutationFn: async (customerIds: string[]) => {
      const selectedCustomersList = customers.filter(c => customerIds.includes(String(c.id)))
      const promises = selectedCustomersList
        .filter(c => c.phone)
        .map(customer => 
          communicationHistoryApi.createSmsHistory({
            to_number: customer.phone!,
            customer_name: `${customer.first_name} ${customer.last_name}`.trim(),
            message: bulkMessage,
            status: 'sent',
            sent_at: new Date().toISOString(),
          })
        )
      return Promise.all(promises)
    },
    onSuccess: (results) => {
      addToast(`${results.length} SMS messages sent successfully`, 'success')
      queryClient.invalidateQueries({ queryKey: ['sms-history'] })
      setBulkSelectedCustomers([])
      setBulkMessage('')
    },
    onError: () => {
      addToast('Failed to send bulk SMS', 'error')
    },
  })

  const handleCustomerChange = (customerId: string) => {
    setSelectedCustomerId(customerId)
    const customer = customers.find(c => String(c.id) === customerId)
    if (customer?.phone) {
      setPhoneNumber(customer.phone)
    }
  }

  const handleSendSms = () => {
    if (!phoneNumber || !message) {
      addToast('Please fill in phone number and message', 'error')
      return
    }

    const customer = customers.find(c => String(c.id) === selectedCustomerId)
    sendSmsMutation.mutate({
      to_number: phoneNumber,
      customer_name: customer ? `${customer.first_name} ${customer.last_name}`.trim() : undefined,
      message,
    })

    // Reset form on success
    if (!sendSmsMutation.isPending) {
      setSelectedCustomerId('')
      setPhoneNumber('')
      setMessage('')
    }
  }

  const handleBulkSend = () => {
    if (bulkSelectedCustomers.length === 0 || !bulkMessage) {
      addToast('Please select customers and enter a message', 'error')
      return
    }
    bulkSendSmsMutation.mutate(bulkSelectedCustomers)
  }

  const toggleCustomerSelection = (customerId: string) => {
    setBulkSelectedCustomers(prev => 
      prev.includes(customerId) 
        ? prev.filter(id => id !== customerId)
        : [...prev, customerId]
    )
  }

  const toggleSelectAll = () => {
    if (bulkSelectedCustomers.length === filteredCustomers.length) {
      setBulkSelectedCustomers([])
    } else {
      setBulkSelectedCustomers(filteredCustomers.map(c => String(c.id)))
    }
  }

  const messageLength = message.length
  const smsCount = Math.ceil(messageLength / 160) || 1
  
  return (
    <div className="space-y-6">
      <PageHeader
        title="Send SMS"
        icon={<MessageSquare className="w-5 h-5" />}
      />

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
                  <select 
                    value={selectedCustomerId}
                    onChange={(e) => handleCustomerChange(e.target.value)}
                    className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 outline-none bg-white"
                  >
                    <option value="">Select Customer</option>
                    {customers.map((customer) => (
                      <option key={customer.id} value={customer.id}>
                        {customer.first_name} {customer.last_name} {customer.phone ? `- ${customer.phone}` : ''}
                      </option>
                    ))}
                  </select>
                </div>
                
                <div>
                  <label className="block text-sm text-gray-700 mb-1">Mobile Number *</label>
                  <Input 
                    value={phoneNumber}
                    onChange={(e) => setPhoneNumber(e.target.value)}
                    className="border-0 border-b border-gray-300 rounded-none px-0 focus:ring-0 focus:border-blue-600 bg-transparent w-full" 
                  />
                  <p className="text-xs text-gray-500 mt-1 italic">Enter mobile number if you want to SMS</p>
                </div>

                <div>
                  <label className="block text-sm text-gray-700 mb-1">SMS Text *</label>
                  <textarea 
                    value={message}
                    onChange={(e) => setMessage(e.target.value)}
                    maxLength={480}
                    className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 outline-none resize-none h-24"
                  />
                  <p className="text-xs text-gray-500 mt-1">SMS Message you want to send ({160 - (messageLength % 160)} Characters remaining)</p>
                  <p className="text-sm font-medium text-gray-800 mt-2">Number of SMS being sent: {smsCount} SMS</p>
                </div>

                <Button 
                  onClick={handleSendSms}
                  disabled={sendSmsMutation.isPending}
                  className="w-full justify-center bg-blue-600 hover:bg-blue-700 text-white gap-2 h-11"
                >
                  {sendSmsMutation.isPending ? (
                    <Loader2 className="w-4 h-4 animate-spin" />
                  ) : (
                    <Send className="w-4 h-4" />
                  )}
                  {sendSmsMutation.isPending ? 'Sending...' : 'Send SMS Now'}
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
                          <Input 
                            placeholder="Search Customer" 
                            value={customerSearch}
                            onChange={(e) => setCustomerSearch(e.target.value)}
                            className="w-full bg-gray-50 border-gray-200" 
                          />
                          <Search className="w-4 h-4 text-gray-400 absolute right-3 top-1/2 transform -translate-y-1/2" />
                        </div>
                        <div className="flex items-center gap-2 mb-2 p-2 bg-gray-50 rounded">
                          <input 
                            type="checkbox" 
                            checked={bulkSelectedCustomers.length === filteredCustomers.length && filteredCustomers.length > 0}
                            onChange={toggleSelectAll}
                            className="rounded border-gray-300" 
                          />
                          <span className="text-sm font-medium text-gray-700">Select All ({filteredCustomers.length})</span>
                        </div>
                        <div className="space-y-1 max-h-40 overflow-y-auto pl-2">
                          {filteredCustomers.map((customer) => (
                            <div key={customer.id} className="flex items-center gap-2 py-1">
                              <input 
                                type="checkbox" 
                                checked={bulkSelectedCustomers.includes(String(customer.id))}
                                onChange={() => toggleCustomerSelection(String(customer.id))}
                                className="rounded border-gray-300" 
                              />
                              <span className="text-sm text-gray-600">
                                {customer.first_name} {customer.last_name} - {customer.phone || 'No phone'}
                              </span>
                            </div>
                          ))}
                        </div>
                      </div>
                    </div>

                    <div>
                      <label className="block text-sm text-gray-700 mb-1">SMS Text *</label>
                      <textarea 
                        value={bulkMessage}
                        onChange={(e) => setBulkMessage(e.target.value)}
                        maxLength={480}
                        className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 outline-none resize-none h-24"
                      />
                      <p className="text-xs text-gray-500 mt-1">SMS Message you want to send ({160 - (bulkMessage.length % 160)} Characters remaining)</p>
                      <p className="text-sm font-medium text-gray-800 mt-2">
                        Number of SMS being sent: {bulkSelectedCustomers.length * Math.ceil(bulkMessage.length / 160 || 1)} SMS
                      </p>
                    </div>

                    <Button 
                      onClick={handleBulkSend}
                      disabled={bulkSendSmsMutation.isPending}
                      className="bg-blue-600 hover:bg-blue-700 text-white gap-2"
                    >
                      {bulkSendSmsMutation.isPending ? (
                        <Loader2 className="w-4 h-4 animate-spin" />
                      ) : (
                        <Send className="w-4 h-4" />
                      )}
                      {bulkSendSmsMutation.isPending ? 'Sending...' : 'Send Bulk Sms'}
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
                        <Input type="date" className="border-0 border-b border-gray-300 rounded-none px-0 focus:ring-0 focus:border-blue-600 bg-transparent w-full text-gray-700" />
                      </div>
                      <Button className="bg-blue-600 hover:bg-blue-700 text-white gap-2 shrink-0">
                        <Download className="w-4 h-4" />
                        Load Bookings
                      </Button>
                    </div>

                    <div className="space-y-3">
                      <div className="bg-gray-50 p-3 rounded-md flex justify-between items-center cursor-pointer hover:bg-gray-100 transition-colors">
                        <span className="text-sm font-medium text-gray-700">Bookings will appear here</span>
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
