import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import type { Customer } from '../../types'
import { Plus, Search, Eye } from 'lucide-react'

export function CustomersPage() {
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [searchTerm, setSearchTerm] = useState('')

  const { data: customers, isLoading } = useQuery({
    queryKey: ['customers'],
    queryFn: async () => {
      // Mock data
      return [
        { id: '1', first_name: 'Alice', last_name: 'Johnson', email: 'alice@example.com', phone: '555-0201', address: '123 Main St', franchise_id: '1', notes: '', createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() },
        { id: '2', first_name: 'Bob', last_name: 'Smith', email: 'bob@example.com', phone: '555-0202', address: '456 Oak Ave', franchise_id: '1', notes: '', createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() },
        { id: '3', first_name: 'Carol', last_name: 'White', email: 'carol@example.com', phone: '555-0203', address: '789 Pine Rd', franchise_id: '1', notes: '', createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() },
      ] as Customer[]
    },
  })

  const filteredCustomers = customers?.filter((customer) =>
    searchTerm === '' ||
    customer.first_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    customer.last_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    customer.email.toLowerCase().includes(searchTerm.toLowerCase())
  )

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-secondary-900">Customers</h1>
        <Button onClick={() => setIsModalOpen(true)} size="sm">
          <Plus className="w-4 h-4 mr-2" />
          Add Customer
        </Button>
      </div>

      <Card>
        <div className="flex items-center gap-4 mb-6">
          <div className="relative flex-1 max-w-md">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
            <input
              type="text"
              placeholder="Search customers..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="input pl-10"
            />
          </div>
        </div>

        <Table
          columns={[
            { key: 'name', title: 'Name', render: (row: Customer) => `${row.first_name} ${row.last_name}` },
            { key: 'email', title: 'Email' },
            { key: 'phone', title: 'Phone' },
            { key: 'address', title: 'Address' },
            {
              key: 'actions',
              title: 'Actions',
              render: () => (
                <div className="flex gap-2">
                  <Button variant="ghost" size="sm">
                    <Eye className="w-4 h-4" />
                  </Button>
                  <Button variant="ghost" size="sm">Edit</Button>
                </div>
              ),
            },
          ]}
          data={filteredCustomers ?? []}
          keyExtractor={(row) => row.id}
          isLoading={isLoading}
        />
      </Card>

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title="Add New Customer"
        footer={
          <>
            <Button variant="secondary" onClick={() => setIsModalOpen(false)}>
              Cancel
            </Button>
            <Button onClick={() => setIsModalOpen(false)}>Create Customer</Button>
          </>
        }
      >
        <div className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <Input label="First Name" />
            <Input label="Last Name" />
          </div>
          <Input label="Email" type="email" />
          <Input label="Phone" />
          <Input label="Address" />
          <div>
            <label className="text-sm font-medium text-secondary-700">Notes</label>
            <textarea className="input mt-1" rows={3} />
          </div>
        </div>
      </Modal>
    </div>
  )
}
