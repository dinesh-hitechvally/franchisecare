import { useState, useMemo } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Mail, ChevronUp, ChevronDown, Loader2, Users, Calendar, Download, ArrowLeft } from 'lucide-react'
import { customersApi, communicationHistoryApi, bookingsApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import { useAuthStore } from '../../store/authStore'
import { PageHeader } from '../../components/layout/PageHeader'
import { formatDisplayDate } from '../../lib/timeFormatUtils'
import type { Booking } from '../../types'

export function SendEmailPage() {
  const queryClient = useQueryClient()
  const { addToast } = useToastStore()
  const { user } = useAuthStore()
  const [genericExpanded, setGenericExpanded] = useState(true)
  const [bulkExpanded, setBulkExpanded] = useState(true)
  const [bulkGenericExpanded, setBulkGenericExpanded] = useState(true)
  const [bookingListExpanded, setBookingListExpanded] = useState(true)
  
  // Generic email form state
  const [selectedCustomerId, setSelectedCustomerId] = useState('')
  const [toEmail, setToEmail] = useState('')
  const [subject, setSubject] = useState('')
  const [body, setBody] = useState('')

  // Bulk email form state
  const [bulkSelectedCustomers, setBulkSelectedCustomers] = useState<string[]>([])
  const [bulkSubject, setBulkSubject] = useState('')
  const [bulkBody, setBulkBody] = useState('')
  const [selectAllCustomers, setSelectAllCustomers] = useState(false)

  // Booking list form state
  const [bookingListCustomers, setBookingListCustomers] = useState<string[]>([])
  const [_bookingListDateFrom, _setBookingListDateFrom] = useState('')
  const [_bookingListDateTo, _setBookingListDateTo] = useState('')
  const [_includeCompleted, _setIncludeCompleted] = useState(true)
  const [_includeCancelled, _setIncludeCancelled] = useState(false)
  const [_bookingListSubject, _setBookingListSubject] = useState('Your Booking Summary')
  const [_bookingListIntro, _setBookingListIntro] = useState('Here is a summary of your bookings:')
  const [_selectAllBookingCustomers, setSelectAllBookingCustomers] = useState(false)

  // Booking list load state (new UI)
  const [bookingCustomerId, setBookingCustomerId] = useState('')
  const [bookingType, setBookingType] = useState('months')
  const [numberOfMonths, setNumberOfMonths] = useState('6')
  const [loadedBookings, setLoadedBookings] = useState<Booking[]>([])
  const [isLoadingBookings, setIsLoadingBookings] = useState(false)
  const [showBookingsList, setShowBookingsList] = useState(false)

  // Fetch customers for dropdown
  const { data: customers = [] } = useQuery({
    queryKey: ['customers'],
    queryFn: () => customersApi.getAll(),
  })

  // Filter customers with emails
  const customersWithEmail = customers.filter(c => c.email)

  // Send single email mutation
  const sendEmailMutation = useMutation({
    mutationFn: (data: { to_email: string; subject: string; body: string }) =>
      communicationHistoryApi.sendEmail(data),
    onSuccess: () => {
      addToast('Email sent successfully', 'success')
      queryClient.invalidateQueries({ queryKey: ['email-history'] })
      setSelectedCustomerId('')
      setToEmail('')
      setSubject('')
      setBody('')
    },
    onError: () => {
      addToast('Failed to send email', 'error')
    },
  })

  // Send bulk email mutation
  const sendBulkEmailMutation = useMutation({
    mutationFn: (data: { customer_ids: string[]; subject: string; body: string }) =>
      communicationHistoryApi.sendBulkEmail(data),
    onSuccess: (response) => {
      const { results } = response
      addToast(`Bulk email: ${results.sent} sent, ${results.failed} failed, ${results.skipped} skipped`, 
        results.failed > 0 ? 'info' : 'success')
      queryClient.invalidateQueries({ queryKey: ['email-history'] })
      setBulkSelectedCustomers([])
      setBulkSubject('')
      setBulkBody('')
      setSelectAllCustomers(false)
    },
    onError: () => {
      addToast('Failed to send bulk emails', 'error')
    },
  })

  // Send booking list mutation
  const sendBookingListMutation = useMutation({
    mutationFn: (data: { customer_ids: string[]; date_from?: string; date_to?: string; include_completed?: boolean; include_cancelled?: boolean; subject?: string; intro_message?: string }) =>
      communicationHistoryApi.sendBookingList(data),
    onSuccess: (response) => {
      const { results } = response
      addToast(`Booking list emails: ${results.sent} sent, ${results.failed} failed, ${results.skipped} skipped`, 
        results.failed > 0 ? 'info' : 'success')
      queryClient.invalidateQueries({ queryKey: ['email-history'] })
      setBookingListCustomers([])
      setSelectAllBookingCustomers(false)
    },
    onError: () => {
      addToast('Failed to send booking list emails', 'error')
    },
  })

  const handleCustomerChange = (customerId: string) => {
    setSelectedCustomerId(customerId)
    const customer = customers.find(c => String(c.id) === customerId)
    if (customer?.email) {
      setToEmail(customer.email)
    }
  }

  const handleSendEmail = () => {
    if (!toEmail || !subject || !body) {
      addToast('Please fill in all required fields', 'error')
      return
    }

    const customer = customers.find(c => String(c.id) === selectedCustomerId)
    const customerName = customer ? `${customer.first_name} ${customer.last_name}`.trim() : ''

    const htmlBody = generateEmailHtml({
      subject,
      body,
      customerName,
      fromEmail: user?.email || 'no-reply@example.com',
    })

    sendEmailMutation.mutate({
      to_email: toEmail,
      subject,
      body: htmlBody,
    })
  }

  const handleSendBulkEmail = () => {
    if (bulkSelectedCustomers.length === 0) {
      addToast('Please select at least one customer', 'error')
      return
    }
    if (!bulkSubject || !bulkBody) {
      addToast('Please fill in subject and message', 'error')
      return
    }

    const htmlBody = generateBulkEmailHtml({
      subject: bulkSubject,
      body: bulkBody,
      fromEmail: user?.email || 'no-reply@example.com',
    })

    sendBulkEmailMutation.mutate({
      customer_ids: bulkSelectedCustomers,
      subject: bulkSubject,
      body: htmlBody,
    })
  }

  const _handleSendBookingList = () => {
    if (bookingListCustomers.length === 0) {
      addToast('Please select at least one customer', 'error')
      return
    }

    sendBookingListMutation.mutate({
      customer_ids: bookingListCustomers,
      date_from: _bookingListDateFrom || undefined,
      date_to: _bookingListDateTo || undefined,
      include_completed: _includeCompleted,
      include_cancelled: _includeCancelled,
      subject: _bookingListSubject,
      intro_message: _bookingListIntro,
    })
  }

  const handleSelectAllCustomers = (checked: boolean) => {
    setSelectAllCustomers(checked)
    if (checked) {
      setBulkSelectedCustomers(customersWithEmail.map(c => String(c.id)))
    } else {
      setBulkSelectedCustomers([])
    }
  }

  const _handleSelectAllBookingCustomers = (checked: boolean) => {
    setSelectAllBookingCustomers(checked)
    if (checked) {
      setBookingListCustomers(customersWithEmail.map(c => String(c.id)))
    } else {
      setBookingListCustomers([])
    }
  }

  const toggleBulkCustomer = (customerId: string) => {
    setBulkSelectedCustomers(prev => 
      prev.includes(customerId) 
        ? prev.filter(id => id !== customerId)
        : [...prev, customerId]
    )
  }

  const _toggleBookingCustomer = (customerId: string) => {
    setBookingListCustomers(prev => 
      prev.includes(customerId) 
        ? prev.filter(id => id !== customerId)
        : [...prev, customerId]
    )
  }
  // Intentionally unused functions for future UI
  void _handleSendBookingList
  void _handleSelectAllBookingCustomers
  void _toggleBookingCustomer

  // Load bookings for selected customer
  const handleLoadBookings = async () => {
    if (!bookingCustomerId) {
      addToast('Please select a customer', 'error')
      return
    }

    setIsLoadingBookings(true)
    try {
      const months = parseInt(numberOfMonths, 10)
      const dateFrom = new Date()
      dateFrom.setMonth(dateFrom.getMonth() - months)
      
      const bookings = await bookingsApi.getAll({
        customer_id: bookingCustomerId,
        dateFrom: dateFrom.toISOString().split('T')[0],
      })
      
      console.log('Loaded bookings:', bookings)
      setLoadedBookings(bookings || [])
      setShowBookingsList(true)
      
      if (bookings.length === 0) {
        addToast('No bookings found for this customer', 'info')
      } else {
        addToast(`Loaded ${bookings.length} booking(s)`, 'success')
      }
    } catch (error) {
      console.error('Failed to load bookings:', error)
      addToast('Failed to load bookings', 'error')
    } finally {
      setIsLoadingBookings(false)
    }
  }

  // Handle back from booking list view
  const handleBackFromBookings = () => {
    setShowBookingsList(false)
    setLoadedBookings([])
  }

  // Send booking email for loaded bookings
  const handleSendBookingEmail = () => {
    if (loadedBookings.length === 0) {
      addToast('No bookings to send', 'error')
      return
    }

    sendBookingListMutation.mutate({
      customer_ids: [bookingCustomerId],
      include_completed: true,
      include_cancelled: false,
      subject: _bookingListSubject,
      intro_message: _bookingListIntro,
    })
  }

  // Memoized comma-separated emails for bulk selected customers
  const bulkSelectedEmails = useMemo(() => {
    return bulkSelectedCustomers
      .map(id => customers.find(c => String(c.id) === id)?.email)
      .filter(Boolean)
      .join(', ')
  }, [bulkSelectedCustomers, customers])

  // Memoized comma-separated emails for booking list customers
  const _bookingListSelectedEmails = useMemo(() => {
    return bookingListCustomers
      .map(id => customers.find(c => String(c.id) === id)?.email)
      .filter(Boolean)
      .join(', ')
  }, [bookingListCustomers, customers])
  // Intentionally unused for future UI
  void _bookingListSelectedEmails

  // Generate HTML email template
  const generateEmailHtml = ({ subject, body, customerName, fromEmail }: { 
    subject: string
    body: string
    customerName: string
    fromEmail: string 
  }) => {
    const formattedBody = body.replace(/\n/g, '<br>')
    const currentDate = formatDisplayDate(new Date())

    return `
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>${subject}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f8;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f6f8; padding: 40px 20px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); overflow: hidden;">
          <tr>
            <td style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 30px 40px; text-align: center;">
              <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">${subject}</h1>
            </td>
          </tr>
          <tr>
            <td style="padding: 40px;">
              ${customerName ? `<p style="margin: 0 0 20px 0; color: #374151; font-size: 16px;">Dear <strong>${customerName}</strong>,</p>` : ''}
              <div style="color: #4b5563; font-size: 15px; line-height: 1.7;">${formattedBody}</div>
              <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <p style="margin: 0; color: #6b7280; font-size: 14px;">Best regards,</p>
                <p style="margin: 5px 0 0 0; color: #374151; font-size: 14px; font-weight: 600;">The Team</p>
              </div>
            </td>
          </tr>
          <tr>
            <td style="background-color: #f9fafb; padding: 25px 40px; border-top: 1px solid #e5e7eb; text-align: center;">
              <p style="margin: 0 0 10px 0; color: #9ca3af; font-size: 12px;">This email was sent on ${currentDate}</p>
              <p style="margin: 0; color: #9ca3af; font-size: 12px;">Sent from: ${fromEmail}</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>`.trim()
  }

  const generateBulkEmailHtml = ({ subject, body, fromEmail }: { 
    subject: string
    body: string
    fromEmail: string 
  }) => {
    const formattedBody = body.replace(/\n/g, '<br>')
    const currentDate = formatDisplayDate(new Date())

    return `
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>${subject}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f8;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f6f8; padding: 40px 20px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); overflow: hidden;">
          <tr>
            <td style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 30px 40px; text-align: center;">
              <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">${subject}</h1>
            </td>
          </tr>
          <tr>
            <td style="padding: 40px;">
              <p style="margin: 0 0 20px 0; color: #374151; font-size: 16px;">Dear <strong>{{customer_name}}</strong>,</p>
              <div style="color: #4b5563; font-size: 15px; line-height: 1.7;">${formattedBody}</div>
              <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <p style="margin: 0; color: #6b7280; font-size: 14px;">Best regards,</p>
                <p style="margin: 5px 0 0 0; color: #374151; font-size: 14px; font-weight: 600;">The Team</p>
              </div>
            </td>
          </tr>
          <tr>
            <td style="background-color: #f9fafb; padding: 25px 40px; border-top: 1px solid #e5e7eb; text-align: center;">
              <p style="margin: 0 0 10px 0; color: #9ca3af; font-size: 12px;">This email was sent on ${currentDate}</p>
              <p style="margin: 0; color: #9ca3af; font-size: 12px;">Sent from: ${fromEmail}</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>`.trim()
  }
  
  return (
    <div className="space-y-6">
      <PageHeader
        title="Send Email"
        icon={<Mail className="w-5 h-5" />}
      />

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
                  <select 
                    value={selectedCustomerId}
                    onChange={(e) => handleCustomerChange(e.target.value)}
                    className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 outline-none bg-white text-gray-700"
                  >
                    <option value="">Select Customer</option>
                    {customers.map((customer) => (
                      <option key={customer.id} value={customer.id}>
                        {customer.first_name} {customer.last_name} {customer.email ? `- ${customer.email}` : ''}
                      </option>
                    ))}
                  </select>
                </div>
                
                <div>
                  <label className="block text-sm text-gray-700 mb-1">Email *</label>
                  <Input 
                    value={toEmail}
                    onChange={(e) => setToEmail(e.target.value)}
                    type="email"
                    className="border-0 border-b border-gray-300 rounded-none px-0 focus:ring-0 focus:border-blue-600 bg-transparent w-full" 
                  />
                  <p className="text-xs text-gray-500 mt-1 italic">Enter email address if you want to send email</p>
                </div>

                <div>
                  <label className="block text-sm text-gray-700 mb-1">Email Subject *</label>
                  <Input 
                    value={subject}
                    onChange={(e) => setSubject(e.target.value)}
                    className="border-0 border-b border-gray-300 rounded-none px-0 focus:ring-0 focus:border-blue-600 bg-transparent w-full" 
                  />
                  <p className="text-xs text-gray-500 mt-1 italic">Enter subject for your email if you want to send email</p>
                </div>

                <div>
                  <label className="block text-sm text-gray-700 mb-1">Email Text *</label>
                  <textarea 
                    value={body}
                    onChange={(e) => setBody(e.target.value)}
                    className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 outline-none resize-none h-48"
                  />
                </div>

                <Button 
                  onClick={handleSendEmail}
                  disabled={sendEmailMutation.isPending}
                  className="justify-center bg-blue-600 hover:bg-blue-700 text-white gap-2"
                >
                  {sendEmailMutation.isPending ? (
                    <Loader2 className="w-4 h-4 animate-spin" />
                  ) : (
                    <Mail className="w-4 h-4" />
                  )}
                  {sendEmailMutation.isPending ? 'Sending...' : 'Send Email Now'}
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
              <div className="divide-y divide-gray-100">
                {/* Accordion A - Send Bulk Generic Messages */}
                <div>
                  <div 
                    className="bg-gray-50 p-3 flex justify-between items-center cursor-pointer hover:bg-gray-100 transition-colors border-b border-gray-200"
                    onClick={() => setBulkGenericExpanded(!bulkGenericExpanded)}
                  >
                    <div className="flex items-center gap-2">
                      <Users className="w-4 h-4 text-blue-600" />
                      <h3 className="text-sm font-medium text-gray-800">Send Bulk Generic Messages</h3>
                    </div>
                    {bulkGenericExpanded ? <ChevronUp className="w-4 h-4 text-gray-500" /> : <ChevronDown className="w-4 h-4 text-gray-500" />}
                  </div>
                  
                  {bulkGenericExpanded && (
                    <div className="p-4 space-y-4 bg-white">
                      <div>
                        <div className="flex items-center justify-between mb-2">
                          <label className="text-sm font-medium text-gray-700">Select Customers</label>
                          <label className="flex items-center gap-2 text-sm text-gray-600">
                            <input
                              type="checkbox"
                              checked={selectAllCustomers}
                              onChange={(e) => handleSelectAllCustomers(e.target.checked)}
                              className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            Select All ({customersWithEmail.length})
                          </label>
                        </div>
                        <div className="max-h-40 overflow-y-auto border border-gray-200 rounded-md p-2 space-y-1">
                          {customersWithEmail.length === 0 ? (
                            <p className="text-sm text-gray-500 text-center py-2">No customers with email addresses</p>
                          ) : (
                            customersWithEmail.map((customer) => (
                              <label key={customer.id} className="flex items-center gap-2 p-1 hover:bg-gray-50 rounded cursor-pointer">
                                <input
                                  type="checkbox"
                                  checked={bulkSelectedCustomers.includes(String(customer.id))}
                                  onChange={() => toggleBulkCustomer(String(customer.id))}
                                  className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                                <span className="text-sm text-gray-700">
                                  {customer.first_name} {customer.last_name}
                                  <span className="text-gray-400 ml-1">({customer.email})</span>
                                </span>
                              </label>
                            ))
                          )}
                        </div>
                        <p className="text-xs text-gray-500 mt-1">{bulkSelectedCustomers.length} customer(s) selected</p>
                        {bulkSelectedCustomers.length >= 1 && (
                          <p className="text-xs text-gray-600 mt-1 break-all">
                            {bulkSelectedEmails}
                          </p>
                        )}
                      </div>

                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                        <Input 
                          value={bulkSubject}
                          onChange={(e) => setBulkSubject(e.target.value)}
                          placeholder="Enter email subject"
                          className="w-full"
                        />
                      </div>

                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                        <textarea 
                          value={bulkBody}
                          onChange={(e) => setBulkBody(e.target.value)}
                          placeholder="Enter your message. Use {{customer_name}}, {{first_name}}, or {{last_name}} for personalization."
                          className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 outline-none resize-none h-32"
                        />
                        <p className="text-xs text-gray-500 mt-1">Tip: Use {"{{customer_name}}"} to personalize each email</p>
                      </div>

                      <Button 
                        onClick={handleSendBulkEmail}
                        disabled={sendBulkEmailMutation.isPending || bulkSelectedCustomers.length === 0}
                        className="w-full justify-center bg-blue-600 hover:bg-blue-700 text-white gap-2"
                      >
                        {sendBulkEmailMutation.isPending ? (
                          <Loader2 className="w-4 h-4 animate-spin" />
                        ) : (
                          <Users className="w-4 h-4" />
                        )}
                        {sendBulkEmailMutation.isPending ? 'Sending...' : `Send to ${bulkSelectedCustomers.length} Customer(s)`}
                      </Button>
                    </div>
                  )}
                </div>

                {/* Accordion B - Send Booking List to Customers */}
                <div>
                  <div 
                    className="bg-gray-50 p-3 flex justify-between items-center cursor-pointer hover:bg-gray-100 transition-colors"
                    onClick={() => setBookingListExpanded(!bookingListExpanded)}
                  >
                    <div className="flex items-center gap-2">
                      <Calendar className="w-4 h-4 text-green-600" />
                      <h3 className="text-sm font-medium text-gray-800">Send Booking List to Customers</h3>
                    </div>
                    {bookingListExpanded ? <ChevronUp className="w-4 h-4 text-gray-500" /> : <ChevronDown className="w-4 h-4 text-gray-500" />}
                  </div>
                  
                  {bookingListExpanded && (
                    <div className="p-4 space-y-4 bg-white">
                      {/* Customer Selection */}
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Select Customer Name</label>
                        <select
                          value={bookingCustomerId}
                          onChange={(e) => {
                            setBookingCustomerId(e.target.value)
                            setShowBookingsList(false)
                            setLoadedBookings([])
                          }}
                          className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-green-500 focus:border-green-500 outline-none bg-white"
                        >
                          <option value="">Select Customer</option>
                          {customersWithEmail.map((customer) => (
                            <option key={customer.id} value={customer.id}>
                              {customer.first_name} {customer.last_name} - {customer.email}
                            </option>
                          ))}
                        </select>
                      </div>

                      {/* Booking Type and Number of Months */}
                      <div className="grid grid-cols-2 gap-3">
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">Select Booking Type</label>
                          <select
                            value={bookingType}
                            onChange={(e) => setBookingType(e.target.value)}
                            className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-green-500 focus:border-green-500 outline-none bg-white"
                          >
                            <option value="months">Number of Months</option>
                          </select>
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">Number of Months</label>
                          <select
                            value={numberOfMonths}
                            onChange={(e) => setNumberOfMonths(e.target.value)}
                            className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-green-500 focus:border-green-500 outline-none bg-white"
                          >
                            {[1, 2, 3, 4, 5, 6, 9, 12, 18, 24].map((num) => (
                              <option key={num} value={num}>{num}</option>
                            ))}
                          </select>
                        </div>
                      </div>

                      {/* Load Bookings Button */}
                      <Button 
                        onClick={handleLoadBookings}
                        disabled={isLoadingBookings || !bookingCustomerId}
                        className="justify-center bg-blue-600 hover:bg-blue-700 text-white gap-2"
                      >
                        {isLoadingBookings ? (
                          <Loader2 className="w-4 h-4 animate-spin" />
                        ) : (
                          <Download className="w-4 h-4" />
                        )}
                        {isLoadingBookings ? 'Loading...' : 'Load Bookings'}
                      </Button>

                      {/* Booking Lists */}
                      {showBookingsList && (
                        <div className="border border-gray-200 rounded-md">
                          <div 
                            className="bg-gray-50 p-3 flex justify-between items-center cursor-pointer border-b border-gray-200"
                          >
                            <h4 className="text-sm font-medium text-gray-800">Booking Lists</h4>
                            <ChevronUp className="w-4 h-4 text-gray-500" />
                          </div>
                          
                          <div className="p-4">
                            {/* Booking Table */}
                            <div className="overflow-x-auto">
                              <table className="w-full text-sm">
                                <thead>
                                  <tr className="border-b border-gray-200">
                                    <th className="text-left py-2 px-2 font-medium text-gray-700">Full Name</th>
                                    <th className="text-left py-2 px-2 font-medium text-gray-700">Email</th>
                                    <th className="text-left py-2 px-2 font-medium text-gray-700">Service Name</th>
                                    <th className="text-left py-2 px-2 font-medium text-gray-700">Suburb</th>
                                    <th className="text-left py-2 px-2 font-medium text-gray-700">Address</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  {loadedBookings.length === 0 ? (
                                    <tr>
                                      <td colSpan={5} className="text-center py-4 text-gray-500">No Bookings Found</td>
                                    </tr>
                                  ) : (
                                    loadedBookings.map((booking) => {
                                      const customerName = booking.customer 
                                        ? `${booking.customer.first_name || ''} ${booking.customer.last_name || ''}`.trim()
                                        : booking.customerName || '-';
                                      const customerEmail = booking.customer?.email || '-';
                                      const services = booking.details?.map(d => d.service?.name).filter(Boolean).join(', ') 
                                        || booking.petName || '-';
                                      const suburb = booking.customer?.suburb || '-';
                                      const address = booking.customer?.street_address || '-';
                                      
                                      return (
                                        <tr key={booking.id} className="border-b border-gray-100 hover:bg-gray-50">
                                          <td className="py-2 px-2">{customerName}</td>
                                          <td className="py-2 px-2">{customerEmail}</td>
                                          <td className="py-2 px-2">{services}</td>
                                          <td className="py-2 px-2">{suburb}</td>
                                          <td className="py-2 px-2">{address}</td>
                                        </tr>
                                      );
                                    })
                                  )}
                                </tbody>
                              </table>
                            </div>

                            {/* Action Buttons */}
                            <div className="flex justify-between mt-4">
                              <Button 
                                onClick={handleBackFromBookings}
                                variant="secondary"
                                className="gap-2"
                              >
                                <ArrowLeft className="w-4 h-4" />
                                Back
                              </Button>
                              <Button 
                                onClick={handleSendBookingEmail}
                                disabled={sendBookingListMutation.isPending || loadedBookings.length === 0}
                                className="bg-blue-600 hover:bg-blue-700 text-white gap-2"
                              >
                                {sendBookingListMutation.isPending ? (
                                  <Loader2 className="w-4 h-4 animate-spin" />
                                ) : (
                                  <Mail className="w-4 h-4" />
                                )}
                                {sendBookingListMutation.isPending ? 'Sending...' : 'Send Booking Email'}
                              </Button>
                            </div>
                          </div>
                        </div>
                      )}
                    </div>
                  )}
                </div>
              </div>
            )}
          </Card>
        </div>
      </div>
    </div>
  )
}
