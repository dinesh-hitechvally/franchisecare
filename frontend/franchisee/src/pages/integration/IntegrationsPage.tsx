import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Link2, Mail, Save, TestTube } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'

export function IntegrationsPage() {
  const [xeroConfig, setXeroConfig] = useState({
    clientId: '',
    clientSecret: '',
    tenantId: '',
    isConnected: false,
  })

  const [smtpConfig, setSmtpConfig] = useState({
    host: '',
    port: '587',
    username: '',
    password: '',
    fromEmail: '',
    fromName: '',
    encryption: 'tls',
  })

  const handleXeroConnect = () => {
    console.log('Connecting to Xero...', xeroConfig)
  }

  const handleSmtpSave = () => {
    console.log('Saving SMTP configuration...', smtpConfig)
  }

  const handleSmtpTest = () => {
    console.log('Testing SMTP connection...', smtpConfig)
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Integrations"
        description="Manage third-party integrations and email configurations"
        icon={<Link2 className="w-5 h-5" />}
      />

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Xero Integration - Left Side */}
        <Card className="border border-gray-200 shadow-sm">
          <div className="border-b border-gray-200 bg-gray-50 px-6 py-4">
            <h2 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <Link2 className="w-5 h-5 text-blue-600" />
              Xero Integration
            </h2>
            <p className="text-sm text-gray-600 mt-1">
              Connect your Xero account for accounting sync
            </p>
          </div>

          <div className="p-6 space-y-6">
            <div className="space-y-4">
              <div className="space-y-2">
                <label className="text-sm font-medium text-gray-700">Client ID</label>
                <Input
                  value={xeroConfig.clientId}
                  onChange={(e) => setXeroConfig({ ...xeroConfig, clientId: e.target.value })}
                  placeholder="Enter Xero Client ID"
                  className="border-gray-300"
                />
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium text-gray-700">Client Secret</label>
                <Input
                  type="password"
                  value={xeroConfig.clientSecret}
                  onChange={(e) => setXeroConfig({ ...xeroConfig, clientSecret: e.target.value })}
                  placeholder="Enter Xero Client Secret"
                  className="border-gray-300"
                />
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium text-gray-700">Tenant ID</label>
                <Input
                  value={xeroConfig.tenantId}
                  onChange={(e) => setXeroConfig({ ...xeroConfig, tenantId: e.target.value })}
                  placeholder="Enter Xero Tenant ID"
                  className="border-gray-300"
                />
              </div>
            </div>

            <div className="flex items-center justify-between pt-4 border-t">
              <div className="flex items-center gap-2">
                <div className={`w-2 h-2 rounded-full ${xeroConfig.isConnected ? 'bg-green-500' : 'bg-gray-300'}`}></div>
                <span className="text-sm text-gray-600">
                  {xeroConfig.isConnected ? 'Connected' : 'Not Connected'}
                </span>
              </div>
              <Button
                onClick={handleXeroConnect}
                className="bg-blue-600 hover:bg-blue-700 text-white"
              >
                Connect to Xero
              </Button>
            </div>
          </div>
        </Card>

        {/* SMTP Configuration - Right Side */}
        <Card className="border border-gray-200 shadow-sm">
          <div className="border-b border-gray-200 bg-gray-50 px-6 py-4">
            <h2 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <Mail className="w-5 h-5 text-green-600" />
              SMTP Configuration
            </h2>
            <p className="text-sm text-gray-600 mt-1">
              Configure email server settings
            </p>
          </div>

          <div className="p-6 space-y-6">
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2 col-span-2 sm:col-span-1">
                  <label className="text-sm font-medium text-gray-700">SMTP Host</label>
                  <Input
                    value={smtpConfig.host}
                    onChange={(e) => setSmtpConfig({ ...smtpConfig, host: e.target.value })}
                    placeholder="smtp.example.com"
                    className="border-gray-300"
                  />
                </div>

                <div className="space-y-2 col-span-2 sm:col-span-1">
                  <label className="text-sm font-medium text-gray-700">Port</label>
                  <Input
                    value={smtpConfig.port}
                    onChange={(e) => setSmtpConfig({ ...smtpConfig, port: e.target.value })}
                    placeholder="587"
                    className="border-gray-300"
                  />
                </div>
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium text-gray-700">Username</label>
                <Input
                  value={smtpConfig.username}
                  onChange={(e) => setSmtpConfig({ ...smtpConfig, username: e.target.value })}
                  placeholder="your-email@example.com"
                  className="border-gray-300"
                />
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium text-gray-700">Password</label>
                <Input
                  type="password"
                  value={smtpConfig.password}
                  onChange={(e) => setSmtpConfig({ ...smtpConfig, password: e.target.value })}
                  placeholder="Enter SMTP password"
                  className="border-gray-300"
                />
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium text-gray-700">From Email</label>
                <Input
                  type="email"
                  value={smtpConfig.fromEmail}
                  onChange={(e) => setSmtpConfig({ ...smtpConfig, fromEmail: e.target.value })}
                  placeholder="noreply@example.com"
                  className="border-gray-300"
                />
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium text-gray-700">From Name</label>
                <Input
                  value={smtpConfig.fromName}
                  onChange={(e) => setSmtpConfig({ ...smtpConfig, fromName: e.target.value })}
                  placeholder="Your Company Name"
                  className="border-gray-300"
                />
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium text-gray-700">Encryption</label>
                <select
                  value={smtpConfig.encryption}
                  onChange={(e) => setSmtpConfig({ ...smtpConfig, encryption: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white"
                >
                  <option value="tls">TLS</option>
                  <option value="ssl">SSL</option>
                  <option value="none">None</option>
                </select>
              </div>
            </div>

            <div className="flex items-center gap-3 pt-4 border-t">
              <Button
                onClick={handleSmtpTest}
                variant="secondary"
                className="flex items-center gap-2"
              >
                <TestTube className="w-4 h-4" />
                Test Connection
              </Button>
              <Button
                onClick={handleSmtpSave}
                className="bg-green-600 hover:bg-green-700 text-white flex items-center gap-2"
              >
                <Save className="w-4 h-4" />
                Save Configuration
              </Button>
            </div>
          </div>
        </Card>
      </div>
    </div>
  )
}
