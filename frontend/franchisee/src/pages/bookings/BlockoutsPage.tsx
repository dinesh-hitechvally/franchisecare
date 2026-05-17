import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { PageHeader } from '../../components/layout/PageHeader'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { blockoutsApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import type { Blockout } from '../../types'
import { Plus, Ban, Trash2 } from 'lucide-react'
import { format } from 'date-fns'

export function BlockoutsPage() {
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)
  const [isModalOpen, setIsModalOpen] = useState(false)

  const { data: blockouts, isLoading } = useQuery({
    queryKey: ['blockouts'],
    queryFn: async () => {
      // Mock data
      return [
        { id: '1', franchise_id: '1', startDate: new Date().toISOString(), endDate: addDays(new Date(), 1).toISOString(), reason: 'Staff Training', createdAt: new Date().toISOString() },
        { id: '2', franchise_id: '1', startDate: addDays(new Date(), 5).toISOString(), endDate: addDays(new Date(), 5).toISOString(), reason: 'Public Holiday', createdAt: new Date().toISOString() },
      ] as Blockout[]
    },
  })

  const createMutation = useMutation({
    mutationFn: blockoutsApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['blockouts'] })
      setIsModalOpen(false)
      addToast('Blockout created successfully', 'success')
    },
  })

  const deleteMutation = useMutation({
    mutationFn: blockoutsApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['blockouts'] })
      addToast('Blockout deleted', 'success')
    },
  })

  function addDays(date: Date, days: number): Date {
    const result = new Date(date)
    result.setDate(result.getDate() + days)
    return result
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Blockouts"
        description="Manage blockout periods to prevent bookings during specific times"
        icon={<Ban className="w-5 h-5" />}
        actions={
          <Button onClick={() => setIsModalOpen(true)} size="sm">
            <Plus className="w-4 h-4 mr-2" />
            Add Blockout
          </Button>
        }
      />

      <Card>
        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
          <div className="flex items-start gap-3">
            <Ban className="w-5 h-5 text-yellow-600 mt-0.5" />
            <div>
              <h3 className="font-medium text-yellow-800">About Blockouts</h3>
              <p className="text-sm text-yellow-700 mt-1">
                Blockouts prevent new bookings during specific time periods. Existing bookings will not be affected.
              </p>
            </div>
          </div>
        </div>

        <Table
          columns={[
            { key: 'startDate', title: 'Start Date', render: (row: Blockout) => format(new Date(row.startDate), 'PPP') },
            { key: 'endDate', title: 'End Date', render: (row: Blockout) => format(new Date(row.endDate), 'PPP') },
            { key: 'reason', title: 'Reason' },
            {
              key: 'actions',
              title: 'Actions',
              render: (row: Blockout) => (
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => deleteMutation.mutate(row.id)}
                  className="text-red-600 hover:text-red-700"
                >
                  <Trash2 className="w-4 h-4" />
                </Button>
              ),
            },
          ]}
          data={blockouts ?? []}
          keyExtractor={(row) => row.id}
          isLoading={isLoading}
        />
      </Card>

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title="Add Blockout Period"
        footer={
          <>
            <Button variant="secondary" onClick={() => setIsModalOpen(false)}>
              Cancel
            </Button>
            <Button
              onClick={() => createMutation.mutate({
                franchise_id: '1',
                startDate: new Date().toISOString(),
                endDate: addDays(new Date(), 1).toISOString(),
                reason: 'New blockout',
              } as Omit<Blockout, 'id' | 'createdAt'>)}
              isLoading={createMutation.isPending}
            >
              Create Blockout
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <Input label="Start Date" type="date" />
            <Input label="End Date" type="date" />
          </div>
          <div>
            <label className="text-sm font-medium text-secondary-700">Franchise</label>
            <select className="input mt-1">
              <option>Main Franchise</option>
              <option>North Branch</option>
            </select>
          </div>
          <Input label="Reason" placeholder="e.g., Staff Training, Public Holiday" />
        </div>
      </Modal>
    </div>
  )
}
