import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Select } from '../../components/ui/Select'
import { Settings, User, Mail, Bell } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'

export function SettingsPage() {
  const [formData, setFormData] = useState({
    businessName: '',
    email: '',
    phone: '',
    address: '',
    timezone: '',
    currency: '',
  })

  return (
    <div className="space-y-6">
      <PageHeader
        title="General Settings"
        description="Configure general application settings"
        icon={<Settings className="w-5 h-5" />}
      />

      <Card>
        <form className="p-6 space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="space-y-2">
              <label className="text-sm font-medium text-gray-700">Business Name</label>
              <div className="relative">
                <Settings className="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                <Input
                  value={formData.businessName}
                  onChange={(e) => setFormData({ ...formData, businessName: e.target.value })}
                  className="pl-10"
                  placeholder="Enter business name"
                />
              </div>
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium text-gray-700">Email</label>
              <div className="relative">
                <Mail className="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                <Input
                  type="email"
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  className="pl-10"
                  placeholder="Enter email"
                />
              </div>
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium text-gray-700">Phone</label>
              <div className="relative">
                <Bell className="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                <Input
                  value={formData.phone}
                  onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                  className="pl-10"
                  placeholder="Enter phone"
                />
              </div>
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium text-gray-700">Address</label>
              <div className="relative">
                <User className="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                <Input
                  value={formData.address}
                  onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                  className="pl-10"
                  placeholder="Enter address"
                />
              </div>
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium text-gray-700">Timezone</label>
              <Select
                value={formData.timezone}
                options={[
                  { label: 'Select Timezone', value: '' },
                  { label: 'Australia/Sydney', value: 'Australia/Sydney' },
                  { label: 'Australia/Melbourne', value: 'Australia/Melbourne' },
                  { label: 'Australia/Brisbane', value: 'Australia/Brisbane' },
                  { label: 'Australia/Perth', value: 'Australia/Perth' },
                ]}
                onChange={(val) => setFormData({ ...formData, timezone: val.toString() })}
              />
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium text-gray-700">Currency</label>
              <Select
                value={formData.currency}
                options={[
                  { label: 'Select Currency', value: '' },
                  { label: 'AUD - Australian Dollar', value: 'AUD' },
                  { label: 'USD - US Dollar', value: 'USD' },
                  { label: 'NZD - New Zealand Dollar', value: 'NZD' },
                ]}
                onChange={(val) => setFormData({ ...formData, currency: val.toString() })}
              />
            </div>
          </div>

          <div className="flex justify-end gap-3">
            <Button variant="secondary">Cancel</Button>
            <Button>Save Settings</Button>
          </div>
        </form>
      </Card>
    </div>
  )
}
