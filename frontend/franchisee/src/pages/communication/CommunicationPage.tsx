import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { communicationApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import type { CommunicationTemplate, CommunicationLog, Customer } from '../../types'
import { Plus, Send, Mail, MessageSquare, Clock, Eye } from 'lucide-react'
import { format } from 'date-fns'
import { PageHeader } from '../../components/layout/PageHeader'

export function CommunicationPage() {
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)
  const [activeTab, setActiveTab] = useState<'templates' | 'logs' | 'send'>('templates')
  const [isTemplateModalOpen, setIsTemplateModalOpen] = useState(false)
  const [isSendModalOpen, setIsSendModalOpen] = useState(false)

  const { data: templates } = useQuery({
    queryKey: ['communication-templates'],
    queryFn: async () => {
      return [
        { id: '1', name: 'Booking Confirmation', type: 'email', subject: 'Your booking is confirmed', body: 'Dear {{customerName}}, your booking for {{petName}} on {{date}} is confirmed.', variables: ['customerName', 'petName', 'date'] },
        { id: '2', name: 'Appointment Reminder', type: 'sms', body: 'Reminder: {{petName}} has an appointment tomorrow at {{time}}. Reply CONFIRM to confirm.', variables: ['petName', 'time'] },
        { id: '3', name: 'Service Complete', type: 'email', subject: '{{petName}} is ready!', body: 'Hi {{customerName}}, {{petName}} is all done and ready for pickup!', variables: ['customerName', 'petName'] },
      ] as CommunicationTemplate[]
    },
  })

  const { data: logs } = useQuery({
    queryKey: ['communication-logs'],
    queryFn: async () => {
      return [
        { id: '1', templateId: '1', recipientIds: ['1'], type: 'email', status: 'sent', sentAt: new Date().toISOString(), createdAt: new Date().toISOString() },
        { id: '2', templateId: '2', recipientIds: ['2'], type: 'sms', status: 'pending', createdAt: new Date(Date.now() - 3600000).toISOString() },
        { id: '3', recipientIds: ['3'], type: 'email', status: 'failed', createdAt: new Date(Date.now() - 7200000).toISOString() },
      ] as CommunicationLog[]
    },
  })

  const { data: customers } = useQuery({
    queryKey: ['customers-for-communication'],
    queryFn: async () => {
      return [
        { id: '1', first_name: 'Alice', last_name: 'Johnson', email: 'alice@example.com', phone: '555-0201' },
        { id: '2', first_name: 'Bob', last_name: 'Smith', email: 'bob@example.com', phone: '555-0202' },
        { id: '3', first_name: 'Carol', last_name: 'White', email: 'carol@example.com', phone: '555-0203' },
      ] as Customer[]
    },
  })

  const sendMutation = useMutation({
    mutationFn: communicationApi.send,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['communication-logs'] })
      setIsSendModalOpen(false)
      addToast('Message sent successfully', 'success')
    },
  })

  const statusColors = {
    pending: 'bg-yellow-100 text-yellow-700',
    sent: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
  }

  const typeIcons = {
    email: Mail,
    sms: MessageSquare,
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Communication"
        icon={<MessageSquare className="w-5 h-5" />}
        actions={
          <Button onClick={() => setIsSendModalOpen(true)} size="sm">
            <Send className="w-4 h-4 mr-2" />
            Send Message
          </Button>
        }
      />

      {/* Tabs */}
      <div className="flex gap-1 bg-secondary-100 p-1 rounded-lg w-fit">
        {(['templates', 'logs', 'send'] as const).map((tab) => (
          <button
            key={tab}
            onClick={() => setActiveTab(tab)}
            className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${
              activeTab === tab
                ? 'bg-white text-secondary-900 shadow-sm'
                : 'text-secondary-600 hover:text-secondary-900'
            }`}
          >
            {tab.charAt(0).toUpperCase() + tab.slice(1)}
          </button>
        ))}
      </div>

      {activeTab === 'templates' && (
        <Card>
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-lg font-semibold">Message Templates</h3>
            <Button onClick={() => setIsTemplateModalOpen(true)} size="sm">
              <Plus className="w-4 h-4 mr-2" />
              New Template
            </Button>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {templates?.map((template) => {
              const Icon = typeIcons[template.type]
              return (
                <div key={template.id} className="border border-secondary-200 rounded-lg p-4 hover:border-primary-300 transition-colors">
                  <div className="flex items-start justify-between mb-3">
                    <div className="flex items-center gap-2">
                      <div className="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center">
                        <Icon className="w-4 h-4 text-primary-700" />
                      </div>
                      <span className="text-xs font-medium uppercase bg-secondary-100 px-2 py-0.5 rounded">
                        {template.type}
                      </span>
                    </div>
                    <Button variant="ghost" size="sm">Edit</Button>
                  </div>
                  <h4 className="font-medium text-secondary-900">{template.name}</h4>
                  {template.subject && (
                    <p className="text-sm text-secondary-500 mt-1">{template.subject}</p>
                  )}
                  <div className="mt-3 flex flex-wrap gap-1">
                    {template.variables.map((v) => (
                      <span key={v} className="text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded">
                        {`{{${v}}}`}
                      </span>
                    ))}
                  </div>
                </div>
              )
            })}
          </div>
        </Card>
      )}

      {activeTab === 'logs' && (
        <Card title="Communication History">
          <Table
            columns={[
              {
                key: 'type',
                title: 'Type',
                render: (row: CommunicationLog) => {
                  const Icon = typeIcons[row.type]
                  return <Icon className="w-4 h-4 text-secondary-500" />
                },
              },
              { key: 'recipients', title: 'Recipients', render: (row: CommunicationLog) => `${row.recipientIds.length} recipient(s)` },
              { key: 'template', title: 'Template', render: (row: CommunicationLog) => row.templateId ? 'Yes' : 'Custom' },
              {
                key: 'status',
                title: 'Status',
                render: (row: CommunicationLog) => (
                  <span className={`px-2 py-1 rounded-full text-xs font-medium ${statusColors[row.status]}`}>
                    {row.status}
                  </span>
                ),
              },
              {
                key: 'sentAt',
                title: 'Sent',
                render: (row: CommunicationLog) => row.sentAt ? format(new Date(row.sentAt), 'MMM d, h:mm a') : '-',
              },
              {
                key: 'actions',
                title: '',
                render: () => (
                  <Button variant="ghost" size="sm">
                    <Eye className="w-4 h-4" />
                  </Button>
                ),
              },
            ]}
            data={logs ?? []}
            keyExtractor={(row) => row.id}
          />
        </Card>
      )}

      {activeTab === 'send' && (
        <Card title="Quick Send">
          <div className="max-w-lg space-y-4">
            <div>
              <label className="text-sm font-medium text-secondary-700">Recipients</label>
              <select className="input mt-1" multiple size={4}>
                {customers?.map((c) => (
                  <option key={c.id} value={c.id}>{c.first_name} {c.last_name} - {c.email}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="text-sm font-medium text-secondary-700">Message Type</label>
              <div className="flex gap-4 mt-2">
                <label className="flex items-center gap-2">
                  <input type="radio" name="type" value="email" defaultChecked className="text-primary-600" />
                  <Mail className="w-4 h-4" /> Email
                </label>
                <label className="flex items-center gap-2">
                  <input type="radio" name="type" value="sms" className="text-primary-600" />
                  <MessageSquare className="w-4 h-4" /> SMS
                </label>
              </div>
            </div>
            <div>
              <label className="text-sm font-medium text-secondary-700">Use Template</label>
              <select className="input mt-1">
                <option value="">Custom Message</option>
                {templates?.map((t) => (
                  <option key={t.id} value={t.id}>{t.name}</option>
                ))}
              </select>
            </div>
            <Input label="Subject" />
            <div>
              <label className="text-sm font-medium text-secondary-700">Message</label>
              <textarea className="input mt-1" rows={4} />
            </div>
            <div className="flex items-center gap-2">
              <Clock className="w-4 h-4 text-secondary-400" />
              <label className="flex items-center gap-2 text-sm text-secondary-700">
                <input type="checkbox" className="rounded border-secondary-300" />
                Schedule for later
              </label>
            </div>
            <Button onClick={() => sendMutation.mutate({ recipientIds: ['1'], type: 'email' })} isLoading={sendMutation.isPending}>
              <Send className="w-4 h-4 mr-2" />
              Send Message
            </Button>
          </div>
        </Card>
      )}

      {/* New Template Modal */}
      <Modal
        isOpen={isTemplateModalOpen}
        onClose={() => setIsTemplateModalOpen(false)}
        title="New Template"
        footer={
          <>
            <Button variant="secondary" onClick={() => setIsTemplateModalOpen(false)}>
              Cancel
            </Button>
            <Button onClick={() => setIsTemplateModalOpen(false)}>Save Template</Button>
          </>
        }
      >
        <div className="space-y-4">
          <Input label="Template Name" />
          <div>
            <label className="text-sm font-medium text-secondary-700">Type</label>
            <select className="input mt-1">
              <option value="email">Email</option>
              <option value="sms">SMS</option>
            </select>
          </div>
          <Input label="Subject" />
          <div>
            <label className="text-sm font-medium text-secondary-700">Body</label>
            <textarea className="input mt-1" rows={4} />
          </div>
          <p className="text-xs text-secondary-500">
            Use {'{{variableName}}'} for dynamic content
          </p>
        </div>
      </Modal>

      {/* Send Modal */}
      <Modal
        isOpen={isSendModalOpen}
        onClose={() => setIsSendModalOpen(false)}
        title="Send Message"
        footer={
          <>
            <Button variant="secondary" onClick={() => setIsSendModalOpen(false)}>
              Cancel
            </Button>
            <Button
              onClick={() => sendMutation.mutate({ recipientIds: ['1'], type: 'email' })}
              isLoading={sendMutation.isPending}
            >
              <Send className="w-4 h-4 mr-2" />
              Send
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          <div>
            <label className="text-sm font-medium text-secondary-700">Recipients</label>
            <select className="input mt-1" multiple>
              {customers?.map((c) => (
                <option key={c.id} value={c.id}>{c.first_name} {c.last_name}</option>
              ))}
            </select>
          </div>
          <div>
            <label className="text-sm font-medium text-secondary-700">Template</label>
            <select className="input mt-1">
              <option value="">Custom Message</option>
              {templates?.map((t) => (
                <option key={t.id} value={t.id}>{t.name}</option>
              ))}
            </select>
          </div>
          <Input label="Subject" />
          <div>
            <label className="text-sm font-medium text-secondary-700">Message</label>
            <textarea className="input mt-1" rows={4} />
          </div>
        </div>
      </Modal>
    </div>
  )
}
