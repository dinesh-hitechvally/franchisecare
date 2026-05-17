import { useMemo, useState, useRef, useEffect } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Eye, Package, X, Plus, MoreVertical, History, Loader2 } from 'lucide-react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Modal } from '../../components/ui/Modal'
import { PortalMenu } from '../../components/ui/PortalMenu'
import { serviceInventoryUsageApi, servicesApi } from '../../api/services'
import type { Service, ServiceInventoryUsage } from '../../types'
import { PageHeader } from '../../components/layout/PageHeader'
import { useToastStore } from '../../store/toastStore'
import { format } from 'date-fns'

type ServiceWithInventory = {
  id: string
  name: string
  inventoryCount: number
  rules: ServiceInventoryUsage[]
}

type InventoryRow = {
  id?: string
  inventoryName: string
  usageAmount: string
  unit: string
  isActive: boolean
}

// Predefined inventory items list
const INVENTORY_ITEMS = [
  'Cologne',
  'HydroClean Sanitiser',
  'Herbal Deluxe Shampoo',
  'Shampoo',
  'Conditioner',
  'Flea Treatment',
  'Ear Cleaner',
  'Nail Polish',
  'Paw Balm',
  'Towels',
  'Other'
]

// Usage amount options (1-5 pumps)
const USAGE_OPTIONS = [
  '1 pump',
  '2 pumps',
  '3 pumps',
  '4 pumps',
  '5 pumps'
]

export function InventoryUsagePage() {
  const queryClient = useQueryClient()
  const { addToast } = useToastStore()
  const [selectedService, setSelectedService] = useState<ServiceWithInventory | null>(null)
  const [isEditModalOpen, setIsEditModalOpen] = useState(false)
  const [isHistoryModalOpen, setIsHistoryModalOpen] = useState(false)
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)
  const menuRef = useRef<HTMLDivElement>(null)
  const [inventoryRows, setInventoryRows] = useState<InventoryRow[]>([
    { inventoryName: '', usageAmount: '', unit: 'pumps', isActive: true }
  ])

  // Close menu when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
        setOpenMenuId(null)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const { data: services = [] } = useQuery({
    queryKey: ['services-list'],
    queryFn: () => servicesApi.getAll(),
  })

  const { data: ruleResponse } = useQuery({
    queryKey: ['service-inventory-usages'],
    queryFn: () => serviceInventoryUsageApi.getAll(),
  })

  const rules = Array.isArray(ruleResponse) ? ruleResponse : ruleResponse?.data ?? []

  // Group rules by service
  const servicesWithInventory = useMemo(() => {
    const serviceList: ServiceWithInventory[] = []
    const serviceMap: Record<string, ServiceWithInventory> = {}

    // Ensure services is an array
    const serviceArray = Array.isArray(services) ? services : []
    const rulesArray = Array.isArray(rules) ? rules : []

    // Initialize with all services
    serviceArray.forEach((service: Service) => {
      serviceMap[service.id] = {
        id: service.id,
        name: service.name,
        inventoryCount: 0,
        rules: [],
      }
    })

    // Add rules to services
    rulesArray.forEach((rule: ServiceInventoryUsage) => {
      const serviceId = rule.service_id
      if (serviceMap[serviceId]) {
        serviceMap[serviceId].rules.push(rule)
        serviceMap[serviceId].inventoryCount = serviceMap[serviceId].rules.length
      }
    })

    // Convert to array
    Object.values(serviceMap).forEach(service => {
      serviceList.push(service)
    })

    return serviceList
  }, [services, rules])

  const saveMutation = useMutation({
    mutationFn: async (data: { serviceId: string; rows: InventoryRow[] }) => {
      // Delete existing rules for this service
      const existingRules = rules.filter((r: ServiceInventoryUsage) => r.service_id === data.serviceId)
      await Promise.all(existingRules.map((rule: ServiceInventoryUsage) =>
        serviceInventoryUsageApi.delete(rule.id)
      ))

      // Create new rules
      const createPromises = data.rows
        .filter(row => row.inventoryName && row.usageAmount)
        .map(row => {
          // Extract number from "X pump(s)" format
          const usageNumber = parseFloat(row.usageAmount.split(' ')[0]) || 0

          return serviceInventoryUsageApi.create({
            service_id: data.serviceId,
            inventory_name: row.inventoryName,
            quantity_per_booking: usageNumber,
            unit: 'pumps',
            notes: '',
            is_active: row.isActive,
            company_id: null,
          })
        })

      return Promise.all(createPromises)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['service-inventory-usages'] })
      addToast('Inventory usage saved successfully', 'success')
      handleCloseModal()
    },
    onError: () => {
      addToast('Failed to save inventory usage', 'error')
    }
  })

  const handleViewRules = (service: ServiceWithInventory) => {
    setSelectedService(service)

    // Load existing rules into form
    if (service.rules.length > 0) {
      setInventoryRows(service.rules.map(rule => {
        const quantity = Math.round(rule.quantity_per_booking)
        const usageAmount = quantity === 1 ? '1 pump' : `${quantity} pumps`

        return {
          id: rule.id,
          inventoryName: rule.inventory_name,
          usageAmount: usageAmount,
          unit: 'pumps',
          isActive: rule.is_active
        }
      }))
    } else {
      setInventoryRows([{ inventoryName: '', usageAmount: '', unit: 'pumps', isActive: true }])
    }

    setIsEditModalOpen(true)
  }

  const handleCloseModal = () => {
    setIsEditModalOpen(false)
    setSelectedService(null)
    setInventoryRows([{ inventoryName: '', usageAmount: '', unit: 'pumps', isActive: true }])
  }

  const handleAddRow = () => {
    setInventoryRows([...inventoryRows, { inventoryName: '', usageAmount: '', unit: 'pumps', isActive: true }])
  }

  const handleRemoveRow = (index: number) => {
    if (inventoryRows.length > 1) {
      setInventoryRows(inventoryRows.filter((_, i) => i !== index))
    }
  }

  const handleRowChange = (index: number, field: keyof InventoryRow, value: string) => {
    const newRows = [...inventoryRows]
    newRows[index] = { ...newRows[index], [field]: value }
    setInventoryRows(newRows)
  }

  const handleSave = () => {
    if (!selectedService) return
    saveMutation.mutate({ serviceId: selectedService.id, rows: inventoryRows })
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Service Inventory Usage"
        description="View inventory items consumed by each service"
        icon={<Package className="w-5 h-5" />}
      />

      <Card className="border border-gray-200 shadow-sm">
        <div className="border-b border-gray-200 bg-gray-50 px-6 py-4">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-lg font-semibold text-gray-900">Services</h2>
              <p className="text-sm text-gray-600 mt-1">
                {servicesWithInventory.length} {servicesWithInventory.length === 1 ? 'service' : 'services'} found
              </p>
            </div>
          </div>
        </div>

        <div className="p-6">
          <div className="overflow-x-auto">
            <table className="w-full border-collapse">
              <thead>
                <tr className="border-b border-gray-200 bg-gray-50">
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900">
                    Service ID
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900">
                    Service Name
                  </th>
                  <th className="px-6 py-4 text-center text-sm font-semibold text-gray-900">
                    Inventory Items
                  </th>
                  <th className="px-6 py-4 text-center text-sm font-semibold text-gray-900">
                    Action
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100 bg-white">
                {servicesWithInventory.length === 0 ? (
                  <tr>
                    <td colSpan={4} className="px-6 py-12 text-center text-gray-500">
                      No services found
                    </td>
                  </tr>
                ) : (
                  servicesWithInventory.map((service, index) => (
                    <tr key={service.id} className="hover:bg-gray-50 transition-colors">
                      <td className="px-6 py-4 text-sm font-mono text-gray-700">
                        {index + 1}
                      </td>
                      <td className="px-6 py-4 text-sm font-medium text-gray-900">
                        {service.name}
                      </td>
                      <td className="px-6 py-4 text-center">
                        <span className={`inline-flex items-center justify-center rounded-full px-3 py-1 text-sm font-semibold ${
                          service.inventoryCount > 0
                            ? 'bg-blue-100 text-blue-700 ring-1 ring-blue-600/20'
                            : 'bg-gray-100 text-gray-600'
                        }`}>
                          {service.inventoryCount}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-center">
                        <button
                          onClick={(e) => {
                            e.stopPropagation()
                            if (openMenuId === service.id) {
                              setOpenMenuId(null)
                              setMenuPos(null)
                            } else {
                              const rect = (e.currentTarget as HTMLButtonElement).getBoundingClientRect()
                              setMenuPos({ top: rect.bottom + 4, left: rect.right - 176 })
                              setOpenMenuId(service.id)
                            }
                          }}
                          className="text-gray-400 hover:text-gray-600 p-1.5 focus:outline-none rounded-full hover:bg-gray-100 transition-colors"
                        >
                          <MoreVertical className="w-5 h-5" />
                        </button>
                        <PortalMenu
                          isOpen={openMenuId === service.id}
                          onClose={() => { setOpenMenuId(null); setMenuPos(null) }}
                          position={menuPos}
                          width={176}
                        >
                          <button
                            onClick={() => {
                              handleViewRules(service)
                              setOpenMenuId(null)
                            }}
                            className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                          >
                            <Eye className="w-4 h-4 text-gray-400" />
                            View Usages
                          </button>
                          <button
                            onClick={() => {
                              setSelectedService(service)
                              setIsHistoryModalOpen(true)
                              setOpenMenuId(null)
                            }}
                            className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                          >
                            <History className="w-4 h-4 text-gray-400" />
                            Usages History
                          </button>
                        </PortalMenu>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </Card>

      {/* Edit Inventory Usage Modal */}
      <Modal
        isOpen={isEditModalOpen}
        onClose={handleCloseModal}
        title="Service Inventory Usage"
        size="lg"
      >
        <div className="space-y-6">
          {/* Service Name */}
          <div>
            <label className="block text-sm font-medium text-blue-600 mb-2">Service</label>
            <input
              type="text"
              value={selectedService?.name || ''}
              disabled
              className="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-700"
            />
          </div>

          {/* Inventory Usage Section */}
          <div>
            <h3 className="text-sm font-medium text-gray-900 mb-4">Inventory Usage</h3>
            <div className="space-y-4">
              {inventoryRows.map((row, index) => (
                <div key={index} className="flex items-end gap-3">
                  <div className="flex-1">
                    <label className="block text-sm text-gray-700 mb-1">
                      Inventory Item Name
                    </label>
                    <select
                      value={row.inventoryName}
                      onChange={(e) => handleRowChange(index, 'inventoryName', e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white"
                    >
                      <option value="">Select inventory item</option>
                      {INVENTORY_ITEMS.map((item) => (
                        <option key={item} value={item}>
                          {item}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div className="w-48">
                    <label className="block text-sm text-gray-700 mb-1">
                      Inventory Usage (in pumps)
                    </label>
                    <select
                      value={row.usageAmount}
                      onChange={(e) => handleRowChange(index, 'usageAmount', e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white"
                    >
                      <option value="">Select usage</option>
                      {USAGE_OPTIONS.map((option) => (
                        <option key={option} value={option}>
                          {option}
                        </option>
                      ))}
                    </select>
                  </div>
                  <button
                    onClick={() => handleRemoveRow(index)}
                    className="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors"
                    disabled={inventoryRows.length === 1}
                  >
                    <X className="w-5 h-5" />
                  </button>
                </div>
              ))}
            </div>

            <button
              onClick={handleAddRow}
              className="mt-4 text-sm text-pink-600 hover:text-pink-700 font-medium flex items-center gap-1"
            >
              <Plus className="w-4 h-4" />
              ADD MORE +
            </button>
          </div>

          {/* Action Buttons */}
          <div className="flex items-center justify-between pt-4 border-t">
            <Button
              variant="secondary"
              onClick={handleCloseModal}
            >
              BACK
            </Button>
            <Button
              onClick={handleSave}
              disabled={saveMutation.isPending}
              className="bg-blue-600 hover:bg-blue-700 text-white"
            >
              {saveMutation.isPending ? 'SAVING...' : 'SAVE'}
            </Button>
          </div>
        </div>
      </Modal>

      {/* Usages History Modal */}
      <Modal
        isOpen={isHistoryModalOpen}
        onClose={() => {
          setIsHistoryModalOpen(false)
          setSelectedService(null)
        }}
        title="Inventory Usage History"
        size="xl"
      >
        <HistoryModalContent selectedService={selectedService} />
        <div className="flex items-center justify-end pt-4 border-t mt-4">
          <Button
            variant="secondary"
            onClick={() => {
              setIsHistoryModalOpen(false)
              setSelectedService(null)
            }}
          >
            CLOSE
          </Button>
        </div>
      </Modal>
    </div>
  )
}

// History Modal Content Component
function HistoryModalContent({ selectedService }: { selectedService: ServiceWithInventory | null }) {
  const { data: history = [], isLoading } = useQuery({
    queryKey: ['service-inventory-history', selectedService?.id],
    queryFn: () => selectedService ? serviceInventoryUsageApi.getHistory(selectedService.id) : Promise.resolve([]),
    enabled: !!selectedService,
  })

  const formatDateTime = (dateStr: string) => {
    try {
      return format(new Date(dateStr), 'dd MMM yyyy, HH:mm')
    } catch {
      return dateStr
    }
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="w-6 h-6 animate-spin text-gray-400" />
        <span className="ml-2 text-gray-600">Loading history...</span>
      </div>
    )
  }

  return (
    <div className="space-y-4">
      <div>
        <label className="block text-sm font-medium text-blue-600 mb-2">Service</label>
        <input
          type="text"
          value={selectedService?.name || ''}
          disabled
          className="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-700"
        />
      </div>

      {history.length === 0 ? (
        <div className="text-center py-8 text-gray-500">
          No inventory usage history found for this service.
        </div>
      ) : (
        <div className="overflow-x-auto border border-gray-200 rounded-lg">
          <table className="w-full text-sm text-left border-collapse">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-4 py-3 font-bold text-gray-900">Booking ID</th>
                <th className="px-4 py-3 font-bold text-gray-900">Customer Name</th>
                <th className="px-4 py-3 font-bold text-gray-900">Inventory Name</th>
                <th className="px-4 py-3 font-bold text-gray-900 text-right">Usage (ml)</th>
                <th className="px-4 py-3 font-bold text-gray-900 text-center">Action</th>
                <th className="px-4 py-3 font-bold text-gray-900">Date & Time</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100 bg-white">
              {history.map((item) => (
                <tr key={item.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 text-gray-700 font-mono">#{item.booking_id}</td>
                  <td className="px-4 py-3 text-gray-700">{item.customer_name}</td>
                  <td className="px-4 py-3 text-gray-700">{item.inventory_name}</td>
                  <td className="px-4 py-3 text-right text-gray-900 font-semibold">
                    {item.quantity_in_ml ? item.quantity_in_ml.toFixed(2) : 'N/A'}
                  </td>
                  <td className="px-4 py-3 text-center">
                    <span
                      className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        item.change_type === 'deducted'
                          ? 'bg-red-100 text-red-700'
                          : 'bg-green-100 text-green-700'
                      }`}
                    >
                      {item.change_type}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-gray-600 whitespace-nowrap">
                    {formatDateTime(item.date_time)}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
