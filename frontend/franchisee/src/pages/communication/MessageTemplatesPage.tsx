import { useMemo, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Table } from '../../components/ui/Table'
import { Button } from '../../components/ui/Button'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { communicationApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import type { CommunicationTemplate } from '../../types'
import { MessageSquare } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'

export function MessageTemplatesPage() {
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [editingTemplate, setEditingTemplate] = useState<CommunicationTemplate | null>(null)
  const [formData, setFormData] = useState({
    name: '',
    type: 'email' as CommunicationTemplate['type'],
    subject: '',
    body: '',
    variables: '',
  })

  const { data: templates = [], isLoading } = useQuery({
    queryKey: ['communication-templates'],
    queryFn: communicationApi.getTemplates,
  })

  const upsertTemplateMutation = useMutation({
    mutationFn: async () => {
      const payload: Omit<CommunicationTemplate, 'id'> = {
        name: formData.name,
        type: formData.type,
        subject: formData.subject.trim() || undefined,
        body: formData.body,
        variables: formData.variables
          .split(',')
          .map((item) => item.trim())
          .filter(Boolean),
      }

      if (editingTemplate) {
        return communicationApi.updateTemplate(editingTemplate.id, payload)
      }

      return communicationApi.createTemplate(payload)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['communication-templates'] })
      addToast(editingTemplate ? 'Template updated' : 'Template created', 'success')
      closeModal()
    },
    onError: () => {
      addToast('Failed to save template', 'error')
    },
  })

  const mappedTemplates = useMemo(
    () =>
      templates.map((template) => ({
        ...template,
        message: template.body,
      })),
    [templates]
  )

  const openCreateModal = () => {
    setEditingTemplate(null)
    setFormData({
      name: '',
      type: 'email',
      subject: '',
      body: '',
      variables: '',
    })
    setIsModalOpen(true)
  }

  const openEditModal = (template: CommunicationTemplate) => {
    setEditingTemplate(template)
    setFormData({
      name: template.name,
      type: template.type,
      subject: template.subject ?? '',
      body: template.body,
      variables: template.variables.join(', '),
    })
    setIsModalOpen(true)
  }

  const closeModal = () => {
    setIsModalOpen(false)
    setEditingTemplate(null)
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Message Template"
        icon={<MessageSquare className="w-5 h-5" />}
        actions={
          <Button size="sm" onClick={openCreateModal}>New Template</Button>
        }
      />

      <Card className="border border-gray-200 shadow-sm overflow-hidden">
        <Table
          columns={[
            {
              key: 'name',
              title: 'Template Name',
              render: (row: CommunicationTemplate) => <span className="text-gray-800 font-medium">{row.name}</span>,
            },
            {
              key: 'type',
              title: 'Type',
              render: (row: CommunicationTemplate) => <span className="uppercase text-xs font-semibold text-gray-600">{row.type}</span>,
            },
            {
              key: 'message',
              title: 'Message',
              render: (row: CommunicationTemplate) => <span className="text-gray-600 block max-w-4xl truncate">{row.body}</span>,
            },
            {
              key: 'mgmt',
              title: 'Mgmt',
              render: (row: CommunicationTemplate) => (
                <button
                  type="button"
                  onClick={() => openEditModal(row)}
                  className="text-blue-600 hover:text-blue-800 font-medium whitespace-nowrap"
                >
                  Edit
                </button>
              ),
            },
          ]}
          data={mappedTemplates}
          keyExtractor={(row: CommunicationTemplate) => row.id}
          isLoading={isLoading}
          emptyMessage="No message templates found"
        />
        
        <div className="p-4 border-t border-gray-100 bg-white flex justify-end items-center gap-6 text-sm text-gray-600">
          <div className="flex items-center gap-2">
            <span>Rows per page:</span>
            <select className="border-0 bg-transparent text-gray-800 focus:ring-0 cursor-pointer">
              <option>25</option>
              <option>50</option>
              <option>100</option>
            </select>
          </div>
          <span>{mappedTemplates.length === 0 ? '0-0' : `1-${mappedTemplates.length}`} of {mappedTemplates.length}</span>
          <div className="flex items-center gap-4">
            <button className="text-gray-400 hover:text-gray-600 focus:outline-none">&lt;</button>
            <button className="text-gray-600 hover:text-gray-800 focus:outline-none">&gt;</button>
          </div>
        </div>
      </Card>

      <Modal
        isOpen={isModalOpen}
        onClose={closeModal}
        title={editingTemplate ? 'Edit Message Template' : 'New Message Template'}
        footer={
          <>
            <Button variant="secondary" onClick={closeModal}>Cancel</Button>
            <Button
              onClick={() => upsertTemplateMutation.mutate()}
              isLoading={upsertTemplateMutation.isPending}
              disabled={!formData.name.trim() || !formData.body.trim()}
            >
              Save
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          <Input
            label="Template Name"
            value={formData.name}
            onChange={(e) => setFormData((prev) => ({ ...prev, name: e.target.value }))}
          />

          <div>
            <label className="text-sm font-medium text-secondary-700">Type</label>
            <select
              className="input mt-1"
              value={formData.type}
              onChange={(e) => setFormData((prev) => ({ ...prev, type: e.target.value as CommunicationTemplate['type'] }))}
            >
              <option value="email">Email</option>
              <option value="sms">SMS</option>
            </select>
          </div>

          <Input
            label="Subject"
            value={formData.subject}
            onChange={(e) => setFormData((prev) => ({ ...prev, subject: e.target.value }))}
          />

          <div>
            <label className="text-sm font-medium text-secondary-700">Body</label>
            <textarea
              className="input mt-1"
              rows={4}
              value={formData.body}
              onChange={(e) => setFormData((prev) => ({ ...prev, body: e.target.value }))}
            />
          </div>

          <Input
            label="Variables (comma separated)"
            value={formData.variables}
            onChange={(e) => setFormData((prev) => ({ ...prev, variables: e.target.value }))}
          />
        </div>
      </Modal>
    </div>
  )
}
