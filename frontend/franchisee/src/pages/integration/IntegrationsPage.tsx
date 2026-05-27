import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Link2 } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'

export function IntegrationsPage() {
  const [xeroConfig, setXeroConfig] = useState({
    clientId: '',
    clientSecret: '',
    tenantId: '',
    isConnected: false,
  })

  const handleXeroConnect = () => {
    console.log('Connecting to Xero...', xeroConfig)
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Integrations"
        description="Manage third-party integrations"
        icon={<Link2 className="w-5 h-5" />}
      />

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Xero Integration */}
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
      </div>
    </div>
  )
}
