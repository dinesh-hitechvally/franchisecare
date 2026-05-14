import { useEffect, useMemo, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { format } from 'date-fns'
import { AlertTriangle, BarChart3, Filter, Plus, Search, Trash2 } from 'lucide-react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { bookingsApi, serviceInventoryUsageApi, servicesApi } from '../../api/services'
import type { Booking, Service, ServiceInventoryUsage } from '../../types'

type UsageForecastRow = {
  id: string
  serviceName: string
  inventoryName: string
  bookingCount: number
  quantityPerBooking: number
  unit: string
  totalDeduction: number
  bookingStatuses: string
}

type RuleFormState = {
  serviceId: string
  inventoryName: string
  quantityPerBooking: string
  unit: string
  notes: string
  isActive: boolean
}

const EMPTY_FORM: RuleFormState = {
  serviceId: '',
  inventoryName: '',
  quantityPerBooking: '',
  unit: '',
  notes: '',
  isActive: true,
}

export function InventoryUsagePage() {
  const queryClient = useQueryClient()
  const [searchTerm, setSearchTerm] = useState('')
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [editingRule, setEditingRule] = useState<ServiceInventoryUsage | null>(null)
  const [form, setForm] = useState<RuleFormState>(EMPTY_FORM)

  const { data: services = [] } = useQuery({
    queryKey: ['services-list'],
    queryFn: () => servicesApi.getAll(),
  })

  const { data: ruleResponse } = useQuery({
    queryKey: ['service-inventory-usages'],
    queryFn: () => serviceInventoryUsageApi.getAll(),
  })

  const { data: bookings = [] } = useQuery({
    queryKey: ['usage-bookings'],
    queryFn: () => bookingsApi.getAll(),
  })

  const rules = Array.isArray(ruleResponse) ? ruleResponse : ruleResponse?.data ?? []

  const activeBookings = useMemo(() => {
    return (bookings as Booking[]).filter((booking) => booking.status === 'active' || booking.status === 'completed')
  }, [bookings])

  const forecast = useMemo<UsageForecastRow[]>(() => {
    const totals = new Map<string, UsageForecastRow & { statuses: Record<string, number> }>()

    activeBookings.forEach((booking) => {
      booking.details?.forEach((detail) => {
        const matchedRules = rules.filter((rule) => rule.is_active && rule.service_id === detail.serviceId)

        matchedRules.forEach((rule) => {
          const key = `${rule.service_id}:${rule.inventory_name}`
          const current = totals.get(key) || {
            id: key,
            serviceName: detail.service?.name || 'Unknown service',
            inventoryName: rule.inventory_name,
            bookingCount: 0,
            quantityPerBooking: Number(rule.quantity_per_booking) || 0,
            unit: rule.unit,
            totalDeduction: 0,
            bookingStatuses: '',
            statuses: {},
          }

          current.bookingCount += 1
          current.totalDeduction += Number(rule.quantity_per_booking) || 0
          current.statuses[booking.status] = (current.statuses[booking.status] || 0) + 1
          totals.set(key, current)
        })
      })
    })

    return Array.from(totals.values()).map(({ statuses, ...row }) => ({
      ...row,
      bookingStatuses: Object.entries(statuses).map(([status, count]) => `${status}: ${count}`).join(' | '),
    }))
  }, [activeBookings, rules])

  const filteredRules = rules.filter((rule) => {
    if (!searchTerm.trim()) return true

    const haystack = `${rule.service?.name || ''} ${rule.inventory_name} ${rule.unit} ${rule.notes || ''}`.toLowerCase()
    return haystack.includes(searchTerm.trim().toLowerCase())
  })

  const createMutation = useMutation({
    mutationFn: (payload: RuleFormState) =>
      serviceInventoryUsageApi.create({
        service_id: payload.serviceId,
        inventory_name: payload.inventoryName,
        quantity_per_booking: Number(payload.quantityPerBooking),
        unit: payload.unit,
        notes: payload.notes,
        is_active: payload.isActive,
        company_id: null,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['service-inventory-usages'] })
      closeModal()
    },
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: string; payload: RuleFormState }) =>
      serviceInventoryUsageApi.update(id, {
        service_id: payload.serviceId,
        inventory_name: payload.inventoryName,
        quantity_per_booking: Number(payload.quantityPerBooking),
        unit: payload.unit,
        notes: payload.notes,
        is_active: payload.isActive,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['service-inventory-usages'] })
      closeModal()
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id: string) => serviceInventoryUsageApi.delete(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['service-inventory-usages'] }),
  })

  useEffect(() => {
    if (!isModalOpen) {
      setEditingRule(null)
      setForm(EMPTY_FORM)
    }
  }, [isModalOpen])

  function closeModal() {
    setIsModalOpen(false)
    setEditingRule(null)
    setForm(EMPTY_FORM)
  }

  function openCreateModal() {
    setEditingRule(null)
    setForm(EMPTY_FORM)
    setIsModalOpen(true)
  }

  function openEditModal(rule: ServiceInventoryUsage) {
    setEditingRule(rule)
    setForm({
      serviceId: rule.service_id,
      inventoryName: rule.inventory_name,
      quantityPerBooking: String(rule.quantity_per_booking),
      unit: rule.unit,
      notes: rule.notes || '',
      isActive: rule.is_active,
    })
    setIsModalOpen(true)
  }

  function saveRule() {
    if (editingRule) {
      updateMutation.mutate({ id: editingRule.id, payload: form })
      return
    }

    createMutation.mutate(form)
  }

  const summaryCards = [
    { label: 'Rules', value: rules.length },
    { label: 'Active Rules', value: rules.filter((rule) => rule.is_active).length },
    { label: 'Services', value: new Set(rules.map((rule) => rule.service_id)).size },
    { label: 'Forecast Rows', value: forecast.length },
  ]

  const serviceOptions = services as Service[]

  return (
    <div className="space-y-6 px-1 py-1">
      <div className="flex items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Inventory Usage</h1>
          <p className="mt-1 text-sm text-gray-500">Define how much inventory each service consumes and forecast deductions from bookings.</p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" size="sm">
            <Filter className="w-4 h-4 mr-2" />
            View Rules
          </Button>
          <Button size="sm" onClick={openCreateModal}>
            <Plus className="w-4 h-4 mr-2" />
            Add Rule
          </Button>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
        {summaryCards.map((card) => (
          <Card key={card.label} className="p-4">
            <p className="text-sm text-gray-500">{card.label}</p>
            <p className="mt-1 text-2xl font-bold text-gray-900">{card.value}</p>
          </Card>
        ))}
      </div>

      {forecast.length === 0 && rules.length === 0 && (
        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4">
          <div className="flex items-start gap-3">
            <AlertTriangle className="mt-0.5 h-5 w-5 text-amber-600" />
            <div>
              <h3 className="font-medium text-amber-900">No usage rules yet</h3>
              <p className="mt-1 text-sm text-amber-800">Add a service rule to begin calculating inventory usage from bookings.</p>
            </div>
          </div>
        </div>
      )}

      <Card className="border border-gray-200 shadow-sm">
        <div className="border-b border-gray-200 p-4">
          <div className="flex items-center gap-4">
            <div className="relative flex-1">
              <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
              <Input
                className="pl-10"
                placeholder="Search rules or services"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
          </div>
        </div>

        <Table
          columns={[
            { key: 'service', title: 'Service', render: (row: ServiceInventoryUsage) => row.service?.name || 'Unknown service' },
            { key: 'inventory_name', title: 'Inventory Item' },
            { key: 'quantity_per_booking', title: 'Qty / Booking', render: (row: ServiceInventoryUsage) => `${Number(row.quantity_per_booking).toFixed(2)} ${row.unit}` },
            {
              key: 'status',
              title: 'Status',
              render: (row: ServiceInventoryUsage) => (
                <span className={`rounded-full px-2 py-1 text-xs font-semibold ${row.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}>
                  {row.is_active ? 'Active' : 'Inactive'}
                </span>
              ),
            },
            { key: 'notes', title: 'Notes', render: (row: ServiceInventoryUsage) => row.notes || '-' },
            {
              key: 'actions',
              title: 'Actions',
              render: (row: ServiceInventoryUsage) => (
                <div className="flex gap-2">
                  <Button variant="ghost" size="sm" onClick={() => openEditModal(row)}>Edit</Button>
                  <Button variant="ghost" size="sm" onClick={() => deleteMutation.mutate(row.id)}>
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              ),
            },
          ]}
          data={filteredRules}
          keyExtractor={(row) => row.id}
          emptyMessage="No service inventory rules found"
        />
      </Card>

      <Card className="border border-gray-200 shadow-sm">
        <div className="border-b border-gray-200 p-4 flex items-center justify-between gap-4">
          <div>
            <h2 className="text-lg font-bold text-gray-900">Usage Forecast</h2>
            <p className="text-sm text-gray-500">Calculated from active rules and active/completed bookings.</p>
          </div>
          <Button
            variant="secondary"
            size="sm"
            onClick={() => queryClient.invalidateQueries({ queryKey: ['usage-bookings'] })}
          >
            <BarChart3 className="mr-2 h-4 w-4" />
            Recalculate
          </Button>
        </div>

        <Table
          columns={[
            { key: 'serviceName', title: 'Service' },
            { key: 'inventoryName', title: 'Inventory Item' },
            { key: 'bookingCount', title: 'Bookings' },
            { key: 'quantityPerBooking', title: 'Qty / Booking', render: (row: UsageForecastRow) => `${row.quantityPerBooking} ${row.unit}` },
            { key: 'totalDeduction', title: 'Total Deduction', render: (row: UsageForecastRow) => `${row.totalDeduction.toFixed(2)} ${row.unit}` },
            { key: 'bookingStatuses', title: 'Status Breakdown' },
          ]}
          data={forecast}
          keyExtractor={(row) => row.id}
          emptyMessage="Add usage rules to generate forecast rows"
        />
      </Card>

      <Card className="border border-gray-200 shadow-sm">
        <div className="border-b border-gray-200 p-4">
          <h2 className="text-lg font-bold text-gray-900">Bookings Used for Forecasting</h2>
          <p className="text-sm text-gray-500">This list shows the bookings that currently feed inventory deduction estimates.</p>
        </div>
        <Table
          columns={[
            { key: 'id', title: 'Booking', render: (row: Booking) => `#${row.id}` },
            { key: 'customer', title: 'Customer', render: (row: Booking) => `${row.customer?.first_name || ''} ${row.customer?.last_name || ''}`.trim() || 'Unknown' },
            { key: 'date', title: 'Date', render: (row: Booking) => format(new Date(row.startDate), 'EEE, dd MMM yyyy') },
            { key: 'status', title: 'Status' },
            { key: 'services', title: 'Services', render: (row: Booking) => row.details?.map((detail) => detail.service?.name).filter(Boolean).join(', ') || '-' },
          ]}
          data={activeBookings}
          keyExtractor={(row) => row.id}
          emptyMessage="No bookings available"
        />
      </Card>

      <Modal
        isOpen={isModalOpen}
        onClose={closeModal}
        title={editingRule ? 'Edit Inventory Usage Rule' : 'Add Inventory Usage Rule'}
        footer={
          <>
            <Button variant="secondary" onClick={closeModal}>Cancel</Button>
            <Button onClick={saveRule}>{editingRule ? 'Update Rule' : 'Create Rule'}</Button>
          </>
        }
      >
        <div className="space-y-4">
          <div>
            <label className="text-sm font-medium text-secondary-700">Service</label>
            <select
              className="input mt-1"
              value={form.serviceId}
              onChange={(e) => setForm((current) => ({ ...current, serviceId: e.target.value }))}
            >
              <option value="">Select a service</option>
              {serviceOptions.map((service) => (
                <option key={service.id} value={service.id}>{service.name}</option>
              ))}
            </select>
          </div>

          <Input
            label="Inventory Item"
            value={form.inventoryName}
            onChange={(e) => setForm((current) => ({ ...current, inventoryName: e.target.value }))}
            placeholder="Shampoo, towels, wipes..."
          />

          <div className="grid grid-cols-2 gap-4">
            <Input
              label="Quantity per Booking"
              type="number"
              step="0.01"
              value={form.quantityPerBooking}
              onChange={(e) => setForm((current) => ({ ...current, quantityPerBooking: e.target.value }))}
            />
            <Input
              label="Unit"
              value={form.unit}
              onChange={(e) => setForm((current) => ({ ...current, unit: e.target.value }))}
              placeholder="bottles, packs, sheets"
            />
          </div>

          <Input
            label="Notes"
            value={form.notes}
            onChange={(e) => setForm((current) => ({ ...current, notes: e.target.value }))}
            placeholder="Optional notes"
          />

          <label className="flex items-center gap-2 text-sm text-secondary-700">
            <input
              type="checkbox"
              checked={form.isActive}
              onChange={(e) => setForm((current) => ({ ...current, isActive: e.target.checked }))}
            />
            Active rule
          </label>
        </div>
      </Modal>
    </div>
  )
}
