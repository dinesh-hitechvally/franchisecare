import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { PageHeader } from '../../components/layout/PageHeader'
import { xeroApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import { Link2, Unlink, RefreshCw, CheckCircle, AlertCircle, ExternalLink, Loader2 } from 'lucide-react'

export function XeroIntegrationPage() {
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)
  const [isConnecting, setIsConnecting] = useState(false)

  // Get Xero connection status
  const { data: status, isLoading } = useQuery({
    queryKey: ['xero-status'],
    queryFn: () => xeroApi.getStatus(),
  })

  // Disconnect mutation
  const disconnectMutation = useMutation({
    mutationFn: () => xeroApi.disconnect(),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['xero-status'] })
      addToast('Disconnected from Xero', 'success')
    },
    onError: () => {
      addToast('Failed to disconnect from Xero', 'error')
    },
  })

  // Test connection mutation
  const testMutation = useMutation({
    mutationFn: () => xeroApi.test(),
    onSuccess: (result) => {
      if (result.success) {
        addToast(`Connection successful: ${result.organization}`, 'success')
      } else {
        addToast(result.message || 'Connection test failed', 'error')
      }
    },
    onError: () => {
      addToast('Connection test failed', 'error')
    },
  })

  // Handle connect to Xero
  const handleConnect = async () => {
    setIsConnecting(true)
    try {
      const { auth_url } = await xeroApi.getAuthUrl()
      
      // Open Xero authorization in a popup
      const width = 600
      const height = 700
      const left = window.screenX + (window.outerWidth - width) / 2
      const top = window.screenY + (window.outerHeight - height) / 2
      
      const popup = window.open(
        auth_url,
        'xero_auth',
        `width=${width},height=${height},left=${left},top=${top},scrollbars=yes`
      )

      // Listen for message from popup
      const handleMessage = async (event: MessageEvent) => {
        if (event.data?.type === 'xero_callback') {
          const { code, state } = event.data
          
          try {
            const result = await xeroApi.callback(code, state)
            
            if (result.success) {
              addToast(`Connected to Xero: ${result.tenant_name}`, 'success')
              queryClient.invalidateQueries({ queryKey: ['xero-status'] })
            } else {
              addToast(result.error || 'Failed to connect', 'error')
            }
          } catch (error) {
            addToast('Failed to complete Xero connection', 'error')
          }
          
          popup?.close()
          window.removeEventListener('message', handleMessage)
          setIsConnecting(false)
        }
      }

      window.addEventListener('message', handleMessage)

      // Check if popup was closed without completing
      const checkClosed = setInterval(() => {
        if (popup?.closed) {
          clearInterval(checkClosed)
          window.removeEventListener('message', handleMessage)
          setIsConnecting(false)
        }
      }, 500)

    } catch (error) {
      addToast('Failed to start Xero connection', 'error')
      setIsConnecting(false)
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Xero Integration"
        icon={<Link2 className="w-5 h-5" />}
      />

      <Card className="p-6 border border-gray-200 shadow-sm">
        <div className="flex items-start gap-4">
          {/* Xero Logo */}
          <div className="w-16 h-16 bg-[#13B5EA] rounded-lg flex items-center justify-center shrink-0">
            <span className="text-white font-bold text-2xl">X</span>
          </div>

          <div className="flex-1">
            <h2 className="text-lg font-semibold text-gray-900">Xero Accounting</h2>
            <p className="text-sm text-gray-600 mt-1">
              Connect your Xero account to automatically sync invoices and payments.
            </p>

            {isLoading ? (
              <div className="mt-4 flex items-center gap-2 text-gray-500">
                <Loader2 className="w-4 h-4 animate-spin" />
                <span className="text-sm">Checking connection status...</span>
              </div>
            ) : status?.connected ? (
              <div className="mt-4 space-y-4">
                {/* Connected Status */}
                <div className="flex items-center gap-2 text-green-600">
                  <CheckCircle className="w-5 h-5" />
                  <span className="font-medium">Connected to {status.organization || status.tenant_name}</span>
                </div>

                {status.last_synced_at && (
                  <p className="text-sm text-gray-500">
                    Last synced: {new Date(status.last_synced_at).toLocaleString()}
                  </p>
                )}

                {/* Action Buttons */}
                <div className="flex flex-wrap gap-3">
                  <Button
                    variant="secondary"
                    onClick={() => testMutation.mutate()}
                    disabled={testMutation.isPending}
                    className="flex items-center gap-2"
                  >
                    {testMutation.isPending ? (
                      <Loader2 className="w-4 h-4 animate-spin" />
                    ) : (
                      <RefreshCw className="w-4 h-4" />
                    )}
                    Test Connection
                  </Button>

                  <Button
                    variant="secondary"
                    onClick={() => disconnectMutation.mutate()}
                    disabled={disconnectMutation.isPending}
                    className="flex items-center gap-2 text-red-600 hover:text-red-700 hover:bg-red-50"
                  >
                    {disconnectMutation.isPending ? (
                      <Loader2 className="w-4 h-4 animate-spin" />
                    ) : (
                      <Unlink className="w-4 h-4" />
                    )}
                    Disconnect
                  </Button>
                </div>
              </div>
            ) : (
              <div className="mt-4 space-y-4">
                {/* Not Connected Status */}
                <div className="flex items-center gap-2 text-gray-500">
                  <AlertCircle className="w-5 h-5" />
                  <span>{status?.message || 'Not connected to Xero'}</span>
                </div>

                {/* Connect Button */}
                <Button
                  onClick={handleConnect}
                  disabled={isConnecting}
                  className="flex items-center gap-2 bg-[#13B5EA] hover:bg-[#0da8db]"
                >
                  {isConnecting ? (
                    <Loader2 className="w-4 h-4 animate-spin" />
                  ) : (
                    <ExternalLink className="w-4 h-4" />
                  )}
                  {isConnecting ? 'Connecting...' : 'Connect to Xero'}
                </Button>
              </div>
            )}
          </div>
        </div>
      </Card>

      {/* How it works */}
      <Card className="p-6 border border-gray-200 shadow-sm">
        <h3 className="font-semibold text-gray-900 mb-4">How it works</h3>
        <ul className="space-y-3 text-sm text-gray-600">
          <li className="flex items-start gap-2">
            <span className="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-medium shrink-0">1</span>
            <span>Click "Connect to Xero" to authorize this app with your Xero account</span>
          </li>
          <li className="flex items-start gap-2">
            <span className="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-medium shrink-0">2</span>
            <span>Log in to Xero and allow access to your organization</span>
          </li>
          <li className="flex items-start gap-2">
            <span className="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-medium shrink-0">3</span>
            <span>Your invoices and payments will automatically sync to Xero</span>
          </li>
        </ul>
      </Card>

      {/* Sync Info */}
      {status?.connected && (
        <Card className="p-6 border border-gray-200 shadow-sm">
          <h3 className="font-semibold text-gray-900 mb-4">What gets synced</h3>
          <ul className="space-y-2 text-sm text-gray-600">
            <li className="flex items-center gap-2">
              <CheckCircle className="w-4 h-4 text-green-500" />
              Customer contacts are created/updated in Xero
            </li>
            <li className="flex items-center gap-2">
              <CheckCircle className="w-4 h-4 text-green-500" />
              Booking invoices are created as sales invoices
            </li>
            <li className="flex items-center gap-2">
              <CheckCircle className="w-4 h-4 text-green-500" />
              Payments are recorded against invoices
            </li>
          </ul>
        </Card>
      )}
    </div>
  )
}
