import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Mail, ChevronUp, ChevronDown, Loader2 } from 'lucide-react'
import { customersApi, communicationHistoryApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import { useAuthStore } from '../../store/authStore'

export function SendEmailPage() {
  const queryClient = useQueryClient()
  const { addToast } = useToastStore()
  const { user } = useAuthStore()
  const [genericExpanded, setGenericExpanded] = useState(true)
  const [bulkExpanded, setBulkExpanded] = useState(true)
  
  // Form state
  const [selectedCustomerId, setSelectedCustomerId] = useState('')
  const [toEmail, setToEmail] = useState('')
  const [subject, setSubject] = useState('')
  const [body, setBody] = useState('')

  // Fetch customers for dropdown
  const { data: customers = [] } = useQuery({
    queryKey: ['customers'],
    queryFn: () => customersApi.getAll(),
  })

  // Send email mutation
  const sendEmailMutation = useMutation({
    mutationFn: (data: { to_email: string; subject: string; body: string; from_email: string }) =>
      communicationHistoryApi.createEmailHistory({
        ...data,
        status: 'sent',
        sent_at: new Date().toISOString(),
      }),
    onSuccess: () => {
      addToast('Email sent successfully', 'success')
      queryClient.invalidateQueries({ queryKey: ['email-history'] })
      // Reset form
      setSelectedCustomerId('')
      setToEmail('')
      setSubject('')
      setBody('')
    },
    onError: () => {
      addToast('Failed to send email', 'error')
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

    // Get customer name if selected
    const customer = customers.find(c => String(c.id) === selectedCustomerId)
    const customerName = customer ? `${customer.first_name} ${customer.last_name}`.trim() : ''

    // Wrap plain text in HTML email template
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
      from_email: user?.email || 'no-reply@example.com',
    })
  }

  // Generate HTML email template
  const generateEmailHtml = ({ subject, body, customerName, fromEmail }: { 
    subject: string
    body: string
    customerName: string
    fromEmail: string 
  }) => {
    const formattedBody = body.replace(/\n/g, '<br>')
    const currentDate = new Date().toLocaleDateString('en-AU', { 
      weekday: 'long', 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric' 
    })

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
          <!-- Header -->
          <tr>
            <td style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 30px 40px; text-align: center;">
              <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">${subject}</h1>
            </td>
          </tr>
          
          <!-- Body -->
          <tr>
            <td style="padding: 40px;">
              ${customerName ? `<p style="margin: 0 0 20px 0; color: #374151; font-size: 16px;">Dear <strong>${customerName}</strong>,</p>` : ''}
              
              <div style="color: #4b5563; font-size: 15px; line-height: 1.7;">
                ${formattedBody}
              </div>
              
              <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <p style="margin: 0; color: #6b7280; font-size: 14px;">Best regards,</p>
                <p style="margin: 5px 0 0 0; color: #374151; font-size: 14px; font-weight: 600;">The Team</p>
              </div>
            </td>
          </tr>
          
          <!-- Footer -->
          <tr>
            <td style="background-color: #f9fafb; padding: 25px 40px; border-top: 1px solid #e5e7eb;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                  <td style="text-align: center;">
                    <p style="margin: 0 0 10px 0; color: #9ca3af; font-size: 12px;">
                      This email was sent on ${currentDate}
                    </p>
                    <p style="margin: 0; color: #9ca3af; font-size: 12px;">
                      Sent from: ${fromEmail}
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
        
        <!-- Unsubscribe footer -->
        <table role="presentation" width="600" cellspacing="0" cellpadding="0">
          <tr>
            <td style="padding: 20px; text-align: center;">
              <p style="margin: 0; color: #9ca3af; font-size: 11px;">
                © ${new Date().getFullYear()} Dog Wash System. All rights reserved.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
    `.trim()
  }
  
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
