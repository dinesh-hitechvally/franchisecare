import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { AxiosError } from 'axios'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Select, type SelectOption } from '../../components/ui/Select'
import { PageHeader } from '../../components/layout/PageHeader'
import { xeroApi, type XeroSettings, type XeroAccount, type XeroTaxRate } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import { Link2, Unlink, RefreshCw, CheckCircle, AlertCircle, ExternalLink, Loader2, Settings2, Plus } from 'lucide-react'

function getErrorMessage(error: unknown, fallback: string): string {
  const message = (error as AxiosError<{ error?: string }>)?.response?.data?.error
  return message || fallback
}

// Inline "no results" content for an account-code dropdown: lets the user create that
// account directly in Xero instead of having to go set it up there first.
function CreateAccountInline({
  name,
  isPending,
  onSubmit,
}: {
  name: string
  isPending: boolean
  onSubmit: (code: string) => void
}) {
  const [code, setCode] = useState('')

  return (
    <div className="px-3 py-3 space-y-2" onClick={(e) => e.stopPropagation()}>
      <p className="text-xs text-gray-500">
        No match for "<span className="font-medium text-gray-700">{name}</span>". Add it as a new Xero account:
      </p>
      <input
        type="text"
        value={code}
        onChange={(e) => setCode(e.target.value)}
        placeholder="Account code (e.g. 610)"
        maxLength={10}
        className="w-full px-2.5 py-1.5 text-sm border border-gray-200 rounded-md outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
      />
      <button
        type="button"
        disabled={!code.trim() || isPending}
        onClick={() => onSubmit(code.trim())}
        className="w-full flex items-center justify-center gap-1.5 px-2.5 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        {isPending ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Plus className="w-3.5 h-3.5" />}
        {isPending ? 'Adding...' : `Add "${name}" to Xero`}
      </button>
    </div>
  )
}

// Account-code fields: rendered as a dropdown of the connected org's real Xero accounts,
// filtered to the Xero account Type(s) that field is actually allowed to reference.
const ACCOUNT_CODE_FIELDS: Array<{ key: keyof XeroSettings; label: string; help: string; types: string[] }> = [
  { key: 'bank_account_code', label: 'Bank Account Code', help: 'Account code payments are recorded against', types: ['BANK'] },
  { key: 'inventory_asset_account_code', label: 'Inventory Asset Account Code', help: 'Must be a Xero "Inventory Asset" type account', types: ['INVENTORY'] },
  { key: 'inventory_cogs_account_code', label: 'Inventory COGS Account Code', help: 'Cost of Goods Sold account for stock purchases', types: ['DIRECTCOSTS', 'EXPENSE'] },
  { key: 'inventory_sales_account_code', label: 'Inventory Sales Account Code', help: 'Revenue account for inventory items sold', types: ['SALES', 'REVENUE'] },
  { key: 'service_sales_account_code', label: 'Service Sales Account Code', help: 'Revenue account for services sold', types: ['SALES', 'REVENUE'] },
]

// Plain text fields - not Xero account codes, so no dropdown applies
const TEXT_FIELDS: Array<{ key: keyof XeroSettings; label: string; help: string }> = [
  { key: 'default_supplier_name', label: 'Default Supplier Name', help: 'Xero contact used for internal stock restocking bills' },
]

const EMPTY_SETTINGS: XeroSettings = {
  default_supplier_name: '',
  bank_account_code: '',
  inventory_asset_account_code: '',
  inventory_cogs_account_code: '',
  inventory_sales_account_code: '',
  service_sales_account_code: '',
  default_tax_type: '',
}

export function XeroIntegrationPage() {
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)
  const [isConnecting, setIsConnecting] = useState(false)
  const [settingsForm, setSettingsForm] = useState<XeroSettings>(EMPTY_SETTINGS)

  // Get Xero connection status
  const { data: status, isLoading } = useQuery({
    queryKey: ['xero-status'],
    queryFn: () => xeroApi.getStatus(),
  })

  // Get this company's Xero account code settings (only once connected)
  useQuery({
    queryKey: ['xero-settings'],
    queryFn: async () => {
      const result = await xeroApi.getSettings()
      setSettingsForm(result.settings)
      return result
    },
    enabled: !!status?.connected,
  })

  // Get the connected org's real chart of accounts, to populate account-code dropdowns
  const { data: accountsData, isLoading: accountsLoading } = useQuery({
    queryKey: ['xero-accounts'],
    queryFn: () => xeroApi.getAccounts(),
    enabled: !!status?.connected,
  })
  const accounts: XeroAccount[] = accountsData?.accounts ?? []

  // Get the connected org's real tax rates, to populate the tax type dropdown
  const { data: taxRatesData, isLoading: taxRatesLoading } = useQuery({
    queryKey: ['xero-tax-rates'],
    queryFn: () => xeroApi.getTaxRates(),
    enabled: !!status?.connected,
  })
  const taxRates: XeroTaxRate[] = taxRatesData?.tax_rates ?? []

  // Save settings mutation
  const saveSettingsMutation = useMutation({
    mutationFn: () => xeroApi.updateSettings(settingsForm),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['xero-settings'] })
      addToast('Xero settings saved', 'success')
    },
    onError: () => {
      addToast('Failed to save Xero settings', 'error')
    },
  })

  const handleSettingChange = (key: keyof XeroSettings, value: string) => {
    setSettingsForm((prev) => ({ ...prev, [key]: value }))
  }

  // Create-a-new-Xero-account mutation, triggered from an account-code dropdown's "no results" state
  const createAccountMutation = useMutation({
    mutationFn: (data: { code: string; name: string; type: string }) => xeroApi.createAccount(data),
  })

  const handleCreateAccount = (fieldKey: keyof XeroSettings, type: string, name: string, code: string) => {
    createAccountMutation.mutate(
      { code, name, type },
      {
        onSuccess: (result) => {
          queryClient.invalidateQueries({ queryKey: ['xero-accounts'] })
          handleSettingChange(fieldKey, result.account.Code)
          addToast(`Account "${result.account.Name}" created in Xero`, 'success')
        },
        onError: (error) => {
          addToast(getErrorMessage(error, 'Failed to create account in Xero'), 'error')
        },
      }
    )
  }

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

      // Listen for the finished result posted by the popup's callback page
      const handleMessage = (event: MessageEvent) => {
        if (event.data?.type === 'xero_callback') {
          const { success, error, tenant_name } = event.data

          if (success) {
            addToast(tenant_name ? `Connected to Xero: ${tenant_name}` : 'Connected to Xero', 'success')
            queryClient.invalidateQueries({ queryKey: ['xero-status'] })
          } else {
            addToast(error || 'Failed to connect to Xero', 'error')
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

      {/* Xero Account Settings */}
      {status?.connected && (
        <Card className="p-6 border border-gray-200 shadow-sm">
          <div className="flex items-center gap-2 mb-2">
            <Settings2 className="w-5 h-5 text-gray-700" />
            <h3 className="font-semibold text-gray-900">Xero Account Settings</h3>
          </div>
          <p className="text-sm text-gray-600 mb-5">
            These account codes must match accounts that already exist in your Xero chart of accounts,
            or syncing will fail with a validation error from Xero.
          </p>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
            {ACCOUNT_CODE_FIELDS.map((field) => {
              const currentValue = settingsForm[field.key]
              const matches = accounts.filter((a) => field.types.includes(a.Type))
              const pool = matches.length > 0 ? matches : accounts
              const hasCurrent = pool.some((a) => a.Code === currentValue)

              const options: SelectOption[] = [
                ...(!hasCurrent && currentValue
                  ? [{ value: currentValue, label: `${currentValue} (currently set, not found in Xero)` }]
                  : []),
                ...pool.map((account) => ({ value: account.Code, label: `${account.Code} - ${account.Name}` })),
              ]

              return (
                <div key={field.key}>
                  <label className="block text-sm text-gray-600 mb-1.5">{field.label}</label>
                  {accountsLoading ? (
                    <div className="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm text-gray-400 flex items-center gap-2">
                      <Loader2 className="w-4 h-4 animate-spin" />
                      Loading Xero accounts...
                    </div>
                  ) : accounts.length === 0 ? (
                    // Fall back to free text if the chart of accounts couldn't be fetched
                    <input
                      type="text"
                      value={currentValue}
                      onChange={(e) => handleSettingChange(field.key, e.target.value)}
                      className="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    />
                  ) : (
                    <Select
                      searchable
                      searchPlaceholder="Search accounts..."
                      placeholder="Select an account..."
                      options={options}
                      value={currentValue}
                      onChange={(value) => handleSettingChange(field.key, String(value))}
                      renderNoResults={(query) => (
                        <CreateAccountInline
                          name={query}
                          isPending={createAccountMutation.isPending}
                          onSubmit={(code) => handleCreateAccount(field.key, field.types[0], query, code)}
                        />
                      )}
                    />
                  )}
                  <p className="text-xs text-gray-400 mt-1">{field.help}</p>
                </div>
              )
            })}

            {/* Default Tax Type - dropdown of the connected org's real tax rates */}
            <div>
              <label className="block text-sm text-gray-600 mb-1.5">Default Tax Type</label>
              {taxRatesLoading ? (
                <div className="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm text-gray-400 flex items-center gap-2">
                  <Loader2 className="w-4 h-4 animate-spin" />
                  Loading Xero tax rates...
                </div>
              ) : taxRates.length === 0 ? (
                // Fall back to free text if tax rates couldn't be fetched
                <input
                  type="text"
                  value={settingsForm.default_tax_type}
                  onChange={(e) => handleSettingChange('default_tax_type', e.target.value)}
                  className="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                />
              ) : (
                <Select
                  searchable
                  searchPlaceholder="Search tax types..."
                  placeholder="Select a tax type..."
                  options={[
                    ...(!taxRates.some((t) => t.TaxType === settingsForm.default_tax_type) && settingsForm.default_tax_type
                      ? [{ value: settingsForm.default_tax_type, label: `${settingsForm.default_tax_type} (currently set, not found in Xero)` }]
                      : []),
                    ...taxRates.map((taxRate) => ({ value: taxRate.TaxType, label: `${taxRate.Name} (${taxRate.TaxType})` })),
                  ]}
                  value={settingsForm.default_tax_type}
                  onChange={(value) => handleSettingChange('default_tax_type', String(value))}
                />
              )}
              <p className="text-xs text-gray-400 mt-1">Tax type applied to synced line items - must match your Xero org</p>
            </div>

            {TEXT_FIELDS.map((field) => (
              <div key={field.key}>
                <label className="block text-sm text-gray-600 mb-1.5">{field.label}</label>
                <input
                  type="text"
                  value={settingsForm[field.key]}
                  onChange={(e) => handleSettingChange(field.key, e.target.value)}
                  className="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                />
                <p className="text-xs text-gray-400 mt-1">{field.help}</p>
              </div>
            ))}
          </div>

          <div className="mt-6">
            <Button
              onClick={() => saveSettingsMutation.mutate()}
              disabled={saveSettingsMutation.isPending}
              className="flex items-center gap-2"
            >
              {saveSettingsMutation.isPending && <Loader2 className="w-4 h-4 animate-spin" />}
              {saveSettingsMutation.isPending ? 'Saving...' : 'Save Settings'}
            </Button>
          </div>
        </Card>
      )}

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
