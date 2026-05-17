import React, { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { format, addDays } from 'date-fns'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  Boxes,
  Calendar,
  CheckCircle2,
  Clock,
  Edit2,
  Eye,
  FileText,
  History,
  ImageIcon,
  Loader2,
  Mail,
  Receipt,
  RotateCcw,
  Send,
  X,
  XCircle,
} from 'lucide-react'
import { Modal } from '../ui/Modal'
import { bookingsApi } from '../../api/services'
import type { Booking, Pet } from '../../types'
import { cn } from '../../lib/utils'
import { PetWaiverModal } from './PetWaiverModal'
import { BookingAuditModal } from './BookingAuditModal'
import { BookingInventoryAuditModal } from './BookingInventoryAuditModal'
import { useToastStore } from '../../store/toastStore'

interface BookingDetailModalProps {
  isOpen: boolean
  onClose: () => void
  booking: Booking | null
  onRebook?: (booking: Booking) => void
}

function getStatusTone(status: Booking['status']) {
  switch (status) {
    case 'completed':
      return 'bg-emerald-100 text-emerald-700'
    case 'cancelled':
      return 'bg-red-100 text-red-700'
    case 'archived':
      return 'bg-slate-200 text-slate-700'
    default:
      return 'bg-blue-100 text-blue-700'
  }
}

function ActionButton({
  label,
  icon: Icon,
  onClick,
  disabled,
  loading,
  variant = 'primary',
}: {
  label: string
  icon: React.ElementType
  onClick: () => void
  disabled?: boolean
  loading?: boolean
  variant?: 'primary' | 'danger' | 'secondary' | 'ghost'
}) {
  const styles = {
    primary: 'border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100',
    danger: 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100',
    secondary: 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100',
    ghost: 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50',
  }

  return (
    <button
      onClick={onClick}
      disabled={disabled || loading}
      className={cn(
        'flex items-center justify-between rounded-xl border px-4 py-3 text-left transition-colors disabled:cursor-not-allowed disabled:opacity-60',
        styles[variant]
      )}
    >
      <span className="flex items-center gap-3 text-sm font-bold">
        <Icon className="h-4 w-4" />
        {loading ? 'Processing...' : label}
      </span>
      <span className="text-xs uppercase tracking-wider opacity-60">Action</span>
    </button>
  )
}

export const BookingDetailModal: React.FC<BookingDetailModalProps> = ({ isOpen, onClose, booking, onRebook }) => {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)
  const [isWaiverModalOpen, setIsWaiverModalOpen] = useState(false)
  const [selectedPetForWaiver, setSelectedPetForWaiver] = useState<Pet | null>(null)
  const [isAuditModalOpen, setIsAuditModalOpen] = useState(false)
  const [isInventoryAuditModalOpen, setIsInventoryAuditModalOpen] = useState(false)

  const { data: upcomingBookings, isLoading: isUpcomingLoading } = useQuery({
    queryKey: ['bookings', 'upcoming', booking?.customer?.id],
    queryFn: () => booking?.customer?.id ? bookingsApi.getAll({
      customer_id: booking.customer.id,
      dateFrom: format(addDays(new Date(), 1), 'yyyy-MM-dd'),
    }) : Promise.resolve([]),
    enabled: !!booking?.customer?.id && isOpen,
  })

  const statusMutation = useMutation({
    mutationFn: ({ bookingId, status }: { bookingId: string; status: Booking['status'] }) => bookingsApi.updateStatus(bookingId, status),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['bookings'] })
      addToast(`Booking ${variables.status === 'active' ? 'restored' : variables.status} successfully`, 'success')
      onClose()
    },
    onError: (error: any) => {
      addToast(error?.response?.data?.message || 'Failed to update booking status', 'error')
    },
  })

  const sendInvoiceMutation = useMutation({
    mutationFn: (bookingId: string) => bookingsApi.sendInvoice(bookingId),
    onSuccess: (response) => addToast(response.message || 'Invoice queued successfully', 'success'),
    onError: (error: any) => addToast(error?.response?.data?.message || 'Failed to queue invoice', 'error'),
  })

  const sendReceiptMutation = useMutation({
    mutationFn: (bookingId: string) => bookingsApi.sendReceipt(bookingId),
    onSuccess: (response) => addToast(response.message || 'Receipt queued successfully', 'success'),
    onError: (error: any) => addToast(error?.response?.data?.message || 'Failed to queue receipt', 'error'),
  })

  const sendSmsMutation = useMutation({
    mutationFn: (bookingId: string) => bookingsApi.sendSmsConfirmation(bookingId),
    onSuccess: (response) => addToast(response.message || 'SMS confirmation queued successfully', 'success'),
    onError: (error: any) => addToast(error?.response?.data?.message || 'Failed to queue SMS confirmation', 'error'),
  })

  const sendEmailMutation = useMutation({
    mutationFn: (bookingId: string) => bookingsApi.sendEmailConfirmation(bookingId),
    onSuccess: (response) => addToast(response.message || 'Email confirmation queued successfully', 'success'),
    onError: (error: any) => addToast(error?.response?.data?.message || 'Failed to queue email confirmation', 'error'),
  })

  const groupedArray = useMemo(() => {
    if (!booking) return []

    const groupedDetails = booking.details?.reduce((acc, detail) => {
      const petId = detail.petId
      if (!acc[petId]) {
        acc[petId] = {
          pet: detail.pet,
          services: [],
        }
      }
      acc[petId].services.push({
        ...detail.service,
        price: detail.price,
      })
      return acc
    }, {} as Record<string, { pet: Pet | undefined; services: Array<{ name?: string; price?: number }> }>)

    return Object.values(groupedDetails || {})
  }, [booking])

  const uniquePets = useMemo(() => {
    if (!booking) return []
    return Array.from(new Set(booking.details?.map((detail) => detail.pet).filter(Boolean))) as Pet[]
  }, [booking])

  if (!booking) return null

  const handleEdit = () => {
    onClose()
    navigate(`/bookings/edit/${booking.id}`)
  }

  const statusActions = {
    active: [
      { label: 'Edit', icon: Edit2, onClick: handleEdit, variant: 'primary' as const, loading: undefined },
      {
        label: 'Mark Complete',
        icon: CheckCircle2,
        onClick: () => statusMutation.mutate({ bookingId: booking.id, status: 'completed' }),
        variant: 'secondary' as const,
        loading: statusMutation.isPending,
      },
      { label: 'Rebook', icon: RotateCcw, onClick: () => onRebook?.(booking), variant: 'primary' as const, loading: undefined },
      {
        label: 'Cancel',
        icon: XCircle,
        onClick: () => statusMutation.mutate({ bookingId: booking.id, status: 'cancelled' }),
        variant: 'danger' as const,
        loading: statusMutation.isPending,
      },
      { label: 'Preview Invoice', icon: Eye, onClick: () => bookingsApi.generateInvoice(booking.id), variant: 'ghost' as const, loading: undefined },
      {
        label: 'Send Invoice',
        icon: Send,
        onClick: () => sendInvoiceMutation.mutate(booking.id),
        variant: 'ghost' as const,
        loading: sendInvoiceMutation.isPending,
      },
      {
        label: 'Send SMS Confirmation',
        icon: FileText,
        onClick: () => sendSmsMutation.mutate(booking.id),
        variant: 'ghost' as const,
        loading: sendSmsMutation.isPending,
      },
      {
        label: 'Send Email Confirmation',
        icon: Mail,
        onClick: () => sendEmailMutation.mutate(booking.id),
        variant: 'ghost' as const,
        loading: sendEmailMutation.isPending,
      },
    ],
    completed: [
      {
        label: 'Restore',
        icon: RotateCcw,
        onClick: () => statusMutation.mutate({ bookingId: booking.id, status: 'active' }),
        variant: 'secondary' as const,
        loading: statusMutation.isPending,
      },
      {
        label: 'Cancel',
        icon: XCircle,
        onClick: () => statusMutation.mutate({ bookingId: booking.id, status: 'cancelled' }),
        variant: 'danger' as const,
        loading: statusMutation.isPending,
      },
      { label: 'Rebook', icon: RotateCcw, onClick: () => onRebook?.(booking), variant: 'primary' as const, loading: undefined },
      { label: 'Preview Receipt', icon: Receipt, onClick: () => bookingsApi.generateReceipt(booking.id), variant: 'ghost' as const, loading: undefined },
      {
        label: 'Send Receipt',
        icon: Send,
        onClick: () => sendReceiptMutation.mutate(booking.id),
        variant: 'ghost' as const,
        loading: sendReceiptMutation.isPending,
      },
    ],
    cancelled: [
      {
        label: 'Restore',
        icon: RotateCcw,
        onClick: () => statusMutation.mutate({ bookingId: booking.id, status: 'active' }),
        variant: 'secondary' as const,
        loading: statusMutation.isPending,
      },
      { label: 'Rebook', icon: RotateCcw, onClick: () => onRebook?.(booking), variant: 'primary' as const, loading: undefined },
    ],
    archived: [
      { label: 'Rebook', icon: RotateCcw, onClick: () => onRebook?.(booking), variant: 'primary' as const, loading: undefined },
    ],
    requested: [
      { label: 'Edit', icon: Edit2, onClick: handleEdit, variant: 'primary' as const, loading: undefined },
      {
        label: 'Confirm',
        icon: CheckCircle2,
        onClick: () => statusMutation.mutate({ bookingId: booking.id, status: 'confirmed' }),
        variant: 'secondary' as const,
        loading: statusMutation.isPending,
      },
      {
        label: 'Cancel',
        icon: XCircle,
        onClick: () => statusMutation.mutate({ bookingId: booking.id, status: 'cancelled' }),
        variant: 'danger' as const,
        loading: statusMutation.isPending,
      },
    ],
    confirmed: [
      { label: 'Edit', icon: Edit2, onClick: handleEdit, variant: 'primary' as const, loading: undefined },
      {
        label: 'Start',
        icon: CheckCircle2,
        onClick: () => statusMutation.mutate({ bookingId: booking.id, status: 'in_progress' }),
        variant: 'secondary' as const,
        loading: statusMutation.isPending,
      },
      {
        label: 'Cancel',
        icon: XCircle,
        onClick: () => statusMutation.mutate({ bookingId: booking.id, status: 'cancelled' }),
        variant: 'danger' as const,
        loading: statusMutation.isPending,
      },
    ],
    in_progress: [
      { label: 'Edit', icon: Edit2, onClick: handleEdit, variant: 'primary' as const, loading: undefined },
      {
        label: 'Mark Complete',
        icon: CheckCircle2,
        onClick: () => statusMutation.mutate({ bookingId: booking.id, status: 'completed' }),
        variant: 'secondary' as const,
        loading: statusMutation.isPending,
      },
      {
        label: 'Cancel',
        icon: XCircle,
        onClick: () => statusMutation.mutate({ bookingId: booking.id, status: 'cancelled' }),
        variant: 'danger' as const,
        loading: statusMutation.isPending,
      },
    ],
  }[booking.status]

  const trailActions = [
    { label: 'Audit Trail', icon: History, onClick: () => setIsAuditModalOpen(true) },
    { label: 'Inventory Audit Trail', icon: Boxes, onClick: () => setIsInventoryAuditModalOpen(true) },
  ]

  return (
    <>
      <Modal isOpen={isOpen} onClose={onClose} size="xl">
        <div className="-mx-6 -mb-4 -mt-4">
          <div className="flex items-center justify-between bg-[#4a5ebc] px-6 py-4">
            <div>
              <h2 className="text-lg font-bold text-white">View Booking Detail</h2>
              <p className="mt-1 text-xs font-semibold uppercase tracking-wider text-white/70">Booking management by status</p>
            </div>
            <button onClick={onClose} className="text-white/80 transition-colors hover:text-white">
              <X className="h-6 w-6" />
            </button>
          </div>

          <div className="bg-white p-8">
            <div className="flex flex-col items-start gap-8 lg:flex-row">
              <div className="min-w-0 flex-1 space-y-8">
                <div className="space-y-3">
                  <div className="flex items-center gap-3">
                    <h3 className="text-xl font-medium capitalize text-[#4a5ebc]">
                      {booking.customer?.first_name} {booking.customer?.last_name} - {booking.customer?.phone}
                    </h3>
                    {booking.status === 'active' ? (
                      <button onClick={handleEdit} className="text-gray-400 transition-colors hover:text-[#4a5ebc]">
                        <Edit2 className="h-4 w-4" />
                      </button>
                    ) : null}
                  </div>
                  {booking.customer?.notes ? (
                    <div className="w-full rounded-lg border border-yellow-100 bg-yellow-50 p-3 text-sm text-gray-700 shadow-sm">
                      {booking.customer.notes}
                    </div>
                  ) : null}
                </div>

                <div className="overflow-hidden rounded-lg border border-gray-100 shadow-sm">
                  <div className="border-b border-gray-100 bg-gray-50 px-6 py-3">
                    <span className="text-sm font-bold text-gray-700">Booking Detail</span>
                  </div>
                  <div className={cn('h-2 w-full', booking.status === 'cancelled' ? 'bg-red-300' : booking.status === 'archived' ? 'bg-slate-300' : 'bg-[#6df19e]')} />

                  <div className="space-y-8 p-6">
                    {groupedArray.map((group, idx) => (
                      <div key={idx} className="space-y-4">
                        <div className="flex items-center gap-3">
                          <span className="text-lg font-bold text-gray-800">{group.pet?.name}</span>
                          <button
                            onClick={() => {
                              if (group.pet) {
                                setSelectedPetForWaiver(group.pet)
                                setIsWaiverModalOpen(true)
                              }
                            }}
                            className="ml-auto rounded border border-[#4a5ebc] px-4 py-1.5 text-[11px] font-bold uppercase text-[#4a5ebc] transition-colors hover:bg-blue-50"
                          >
                            Waivers
                          </button>
                        </div>

                        <div className="ml-1 space-y-1.5">
                          {group.services.map((service, serviceIndex) => (
                            <div key={serviceIndex} className="text-sm font-medium text-gray-600">
                              [{service.name} - ${Number(service.price).toFixed(2)}]
                            </div>
                          ))}
                        </div>
                      </div>
                    ))}

                    <div className="space-y-6 pt-2">
                      <div>
                        <label className="mb-2 block text-[13px] font-bold text-gray-800">Service Location</label>
                        <p className="text-sm leading-snug text-gray-600">
                          {booking.customer?.address || 'No location specified'}
                        </p>
                      </div>

                      <div>
                        <label className="mb-2 block text-[13px] font-bold text-gray-800">Date/Time</label>
                        <p className="text-sm text-gray-600">
                          {booking.startDate && !isNaN(new Date(booking.startDate).getTime())
                            ? format(new Date(booking.startDate), 'EEEE, do MMM yyyy')
                            : 'No date'} {booking.startTime || 'No time'}
                        </p>
                      </div>

                      <div>
                        <label className="mb-2 block text-[13px] font-bold text-gray-800">Booking Comments</label>
                        <p className="mt-1 text-sm italic text-gray-500">
                          {booking.notes || 'No comments'}
                        </p>
                      </div>

                      <div className="flex items-center gap-2">
                        <span className="text-[13px] font-bold text-gray-800">Recurring Booking:</span>
                        <span className={cn('rounded-full px-3 py-1 text-xs font-bold text-white', booking.isRecurring ? 'bg-[#4a5ebc]' : 'bg-gray-700')}>
                          {booking.isRecurring ? 'Yes' : 'No'}
                        </span>
                      </div>

                      <div className="flex items-center gap-2">
                        <span className="text-[13px] font-bold text-gray-800">Booking Status:</span>
                        <span className={cn('rounded-full px-3 py-1 text-xs font-bold capitalize', getStatusTone(booking.status))}>
                          {booking.status}
                        </span>
                      </div>

                      <div className="flex items-center justify-between py-2">
                        <span className="text-[13px] font-bold text-gray-800">Calendar Color</span>
                        <div className="flex items-center gap-3">
                          <div className="h-6 w-6 rounded border border-gray-200 shadow-sm" style={{ backgroundColor: booking.calendarColor || '#4a5ebc' }} />
                          <span className="text-xs uppercase tracking-tight text-gray-500">{booking.calendarColor || '#4a5ebc'}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="overflow-hidden rounded-lg border border-gray-100 shadow-sm">
                  <div className="border-b border-gray-100 bg-gray-50 px-6 py-4">
                    <span className="text-sm font-bold text-gray-700">Manage Actions</span>
                  </div>
                  <div className="space-y-6 p-6">
                    <div>
                      <p className="mb-3 text-[11px] font-bold uppercase tracking-wider text-gray-400">{booking.status} booking actions</p>
                      <div className="grid gap-3 md:grid-cols-2">
                        {statusActions?.map((action) => (
                          <ActionButton
                            key={action.label}
                            label={action.label}
                            icon={action.icon}
                            onClick={action.onClick}
                            variant={action.variant}
                            loading={action.loading}
                          />
                        ))}
                      </div>
                    </div>

                    <div>
                      <p className="mb-3 text-[11px] font-bold uppercase tracking-wider text-gray-400">History</p>
                      <div className="grid gap-3 md:grid-cols-2">
                        {trailActions.map((action) => (
                          <ActionButton
                            key={action.label}
                            label={action.label}
                            icon={action.icon}
                            onClick={action.onClick}
                            variant="ghost"
                          />
                        ))}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div className="w-full shrink-0 space-y-8 lg:w-[240px]">
                <div className="space-y-4">
                  <label className="block text-[11px] font-bold uppercase tracking-wider text-gray-400">Pets in Booking</label>
                  <div className={cn('grid gap-4', uniquePets.length > 1 ? 'grid-cols-2' : 'grid-cols-1')}>
                    {uniquePets.length > 0 ? uniquePets.map((pet) => (
                      <div key={pet.id} className="group relative aspect-square overflow-hidden rounded-lg border border-gray-100 bg-gray-100 shadow-sm">
                        {pet.image ? (
                          <img src={pet.image} alt={pet.name} className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110" />
                        ) : (
                          <div className="flex h-full w-full flex-col items-center justify-center gap-2 text-gray-300">
                            <ImageIcon className="h-8 w-8 opacity-40" />
                            <span className="text-[10px] font-bold uppercase tracking-widest">{pet.name}</span>
                          </div>
                        )}
                        <div className="absolute bottom-0 left-0 right-0 overflow-hidden text-ellipsis whitespace-nowrap bg-black/50 p-2 text-[10px] font-bold text-white opacity-0 backdrop-blur-sm transition-opacity group-hover:opacity-100">
                          {pet.name}
                        </div>
                      </div>
                    )) : (
                      <div className="col-span-full flex aspect-square items-center justify-center rounded-lg border border-dashed border-gray-200 bg-gray-50 text-xs italic text-gray-300">
                        No pet data found
                      </div>
                    )}
                  </div>
                </div>

                <div className="space-y-4">
                  <label className="block text-[11px] font-bold uppercase tracking-wider text-gray-400">Upcoming Bookings</label>
                  <div className="space-y-3">
                    {isUpcomingLoading ? (
                      <div className="flex items-center gap-3 py-4 text-sm italic text-gray-400">
                        <Loader2 className="h-4 w-4 animate-spin" />
                        Loading future dates...
                      </div>
                    ) : !upcomingBookings || upcomingBookings.length === 0 ? (
                      <div className="rounded-lg border border-gray-100 bg-gray-50 p-4 text-center">
                        <p className="text-[13px] italic text-gray-400">No upcoming booking exists</p>
                      </div>
                    ) : (
                      <div className="space-y-2">
                        {upcomingBookings.slice(0, 5).map((upcomingBooking) => {
                          const bookingDate = upcomingBooking.startDate ? new Date(upcomingBooking.startDate) : null
                          const isValidDate = bookingDate && !isNaN(bookingDate.getTime())

                          return (
                            <div key={upcomingBooking.id} className="group flex items-center gap-3 rounded-lg border border-blue-100/50 bg-blue-50/50 p-3 transition-all hover:bg-blue-50">
                              <div className="flex h-8 w-8 items-center justify-center rounded-full bg-white text-blue-500 shadow-sm">
                                <Calendar className="h-3.5 w-3.5" />
                              </div>
                              <div className="min-w-0 flex-1">
                                <p className="text-[13px] font-bold text-gray-800">
                                  {isValidDate ? format(bookingDate, 'MMM do, yyyy') : 'No date'}
                                </p>
                                <p className="flex items-center gap-1.5 text-[11px] font-medium text-gray-500">
                                  <Clock className="h-3 w-3" /> {upcomingBooking.startTime || 'No time'}
                                </p>
                              </div>
                            </div>
                          )
                        })}
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </div>

            <div className="mt-8 flex items-center justify-between border-t border-gray-50 pt-6 text-gray-400">
              <span className="text-[10px] font-medium uppercase tracking-tight">ID: {String(booking.id).slice(-8)}</span>
              <span className="text-[10px] font-medium italic">
                Last Updated: {booking.updatedAt && !isNaN(new Date(booking.updatedAt).getTime())
                  ? format(new Date(booking.updatedAt), 'MMMM do yyyy, p')
                  : 'Unknown'}
              </span>
            </div>
          </div>
        </div>

        <PetWaiverModal
          isOpen={isWaiverModalOpen}
          onClose={() => setIsWaiverModalOpen(false)}
          pet={selectedPetForWaiver}
          customer={booking.customer as any}
        />
      </Modal>

      <BookingAuditModal
        isOpen={isAuditModalOpen}
        onClose={() => setIsAuditModalOpen(false)}
        booking={booking}
      />

      <BookingInventoryAuditModal
        isOpen={isInventoryAuditModalOpen}
        onClose={() => setIsInventoryAuditModalOpen(false)}
        booking={booking}
      />
    </>
  )
}
