import React, { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Modal } from '../ui/Modal'
import { format, addDays } from 'date-fns'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { bookingsApi } from '../../api/services'
import {
  X, Edit2, Trash2, Eye, Mail, XCircle,
  ChevronDown, ChevronUp, FileText,
  RotateCcw, ImageIcon, Calendar, Clock, Loader2
} from 'lucide-react'
import type { Booking, Pet } from '../../types'
import { cn } from '../../lib/utils'
import { PetWaiverModal } from './PetWaiverModal'

interface BookingDetailModalProps {
  isOpen: boolean
  onClose: () => void
  booking: Booking | null
  onRebook?: (booking: Booking) => void
}

export const BookingDetailModal: React.FC<BookingDetailModalProps> = ({ isOpen, onClose, booking, onRebook }) => {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [isOptionsOpen, setIsOptionsOpen] = useState(false)
  const [isWaiverModalOpen, setIsWaiverModalOpen] = useState(false)
  const [selectedPetForWaiver, setSelectedPetForWaiver] = useState<Pet | null>(null)

  const { data: upcomingBookings, isLoading: isUpcomingLoading } = useQuery({
    queryKey: ['bookings', 'upcoming', booking?.customer?.id],
    queryFn: () => booking?.customer?.id ? bookingsApi.getAll({
      customer_id: booking.customer.id,
      dateFrom: format(addDays(new Date(), 1), 'yyyy-MM-dd')
    }) : Promise.resolve([]),
    enabled: !!booking?.customer?.id && isOpen,
  })

  const markCompleteMutation = useMutation({
    mutationFn: (bookingId: string) => bookingsApi.updateStatus(bookingId, 'completed'),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bookings'] })
      onClose()
    },
  })

  const cancelBookingMutation = useMutation({
    mutationFn: (bookingId: string) => bookingsApi.updateStatus(bookingId, 'cancelled'),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bookings'] })
      onClose()
    },
  })

  const sendSMSMutation = useMutation({
    mutationFn: (bookingId: string) => 
      fetch(`/api/bookings/${bookingId}/send-sms`, { method: 'POST' })
        .then(res => res.json()),
    onSuccess: () => {
      alert('SMS confirmation sent successfully!')
      queryClient.invalidateQueries({ queryKey: ['bookings'] })
    },
  })

  const sendEmailMutation = useMutation({
    mutationFn: (bookingId: string) => 
      fetch(`/api/bookings/${bookingId}/send-email`, { method: 'POST' })
        .then(res => res.json()),
    onSuccess: () => {
      alert('Email confirmation sent successfully!')
      queryClient.invalidateQueries({ queryKey: ['bookings'] })
    },
  })

  if (!booking) return null

  // Group services by pet
  const groupedDetails = booking.details?.reduce((acc, detail) => {
    const petId = detail.petId
    if (!acc[petId]) {
      acc[petId] = {
        pet: detail.pet,
        services: []
      }
    }
    acc[petId].services.push({
      ...detail.service,
      price: detail.price
    })
    return acc
  }, {} as Record<string, { pet: any, services: any[] }>)

  const groupedArray = Object.values(groupedDetails || {})

  // Extract unique pets for image display
  const uniquePets = Array.from(new Set(booking.details?.map(d => d.pet).filter(Boolean))) as Pet[]

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      size="xl"
    >
      <div className="-mt-4 -mx-6 -mb-4">
        {/* Top Header Strip (Blue) */}
        <div className="bg-[#4a5ebc] px-6 py-4 flex justify-between items-center">
          <h2 className="text-lg font-bold text-white">View Booking Detail</h2>
          <button onClick={onClose} className="text-white/80 hover:text-white transition-colors">
            <X className="w-6 h-6" />
          </button>
        </div>

        <div className="p-8 bg-white">
          <div className="flex flex-col lg:flex-row gap-8 items-start">
            {/* Left Side: Booking Details */}
            <div className="flex-1 space-y-8 min-w-0">
              {/* Customer and Ref Section */}
              <div className="w-full">
                <div className="space-y-3">
                  <div className="flex items-center gap-3">
                    <h3 className="text-xl font-medium text-[#4a5ebc] capitalize">
                      {booking.customer?.first_name} {booking.customer?.last_name} - {booking.customer?.phone}
                    </h3>
                    <button 
                      onClick={() => navigate(`/bookings/edit/${booking.id}`)}
                      className="text-gray-400 hover:text-[#4a5ebc] transition-colors">
                      <Edit2 className="w-4 h-4" />
                    </button>
                  </div>
                  {booking.customer?.notes && (
                    <div className="w-full p-3 bg-yellow-50 border border-yellow-100 rounded-lg text-sm text-gray-700 shadow-sm">
                      {booking.customer.notes}
                    </div>
                  )}
                </div>
              </div>

              {/* Booking Detail Section */}
              <div className="border border-gray-100 rounded-lg overflow-hidden shadow-sm">
                <div className="bg-gray-50 px-6 py-3 border-b border-gray-100">
                  <span className="text-sm font-bold text-gray-700">Booking Detail</span>
                </div>

                {/* Green Status Bar */}
                <div className="h-2 bg-[#6df19e] w-full" />

                <div className="p-6 space-y-8">
                  {groupedArray.map((group, idx) => (
                    <div key={idx} className="space-y-4">
                      <div className="flex items-center gap-3">
                        <span className="text-lg font-bold text-gray-800">{group.pet?.name}</span>
                        <button className="text-gray-400 hover:text-blue-500 transition-colors">
                          <Edit2 className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => {
                            setSelectedPetForWaiver(group.pet as Pet)
                            setIsWaiverModalOpen(true)
                          }}
                          className="ml-auto flex items-center gap-2 px-4 py-1.5 border border-[#4a5ebc] text-[#4a5ebc] text-[11px] font-bold rounded hover:bg-blue-50 transition-colors uppercase"
                        >
                          Waivers
                        </button>
                      </div>

                      <div className="space-y-1.5 ml-1">
                        {group.services.map((svc, sIdx) => (
                          <div key={sIdx} className="text-sm text-gray-600 font-medium">
                            [{svc.name} - ${Number(svc.price).toFixed(2)}]
                          </div>
                        ))}
                      </div>
                    </div>
                  ))}

                  <div className="space-y-6 pt-2">
                    <div>
                      <label className="block text-[13px] font-bold text-gray-800 mb-2">Service Location</label>
                      <p className="text-sm text-gray-600 leading-snug">
                        {booking.customer?.address || 'No location specified'}
                      </p>
                    </div>

                    <div>
                      <label className="block text-[13px] font-bold text-gray-800 mb-2">Date/Time</label>
                      <p className="text-sm text-gray-600">
                        {booking.startDate && !isNaN(new Date(booking.startDate).getTime())
                          ? format(new Date(booking.startDate), 'EEEE, do MMM yyyy')
                          : 'No date'} {booking.startTime || 'No time'}
                      </p>
                    </div>

                    <div>
                      <label className="block text-[13px] font-bold text-gray-800 mb-2">Booking Comments</label>
                      <p className="text-sm text-gray-500 italic mt-1">
                        {booking.notes || 'No comments'}
                      </p>
                    </div>

                    <div className="flex items-center gap-2">
                      <span className="text-[13px] font-bold text-gray-800">Recurring Booking:</span>
                      <span className={cn(
                        "px-3 py-1 rounded-full text-xs font-bold text-white",
                        booking.isRecurring ? "bg-[#4a5ebc]" : "bg-gray-700"
                      )}>
                        {booking.isRecurring ? 'Yes' : 'No'}
                      </span>
                    </div>

                    <div className="flex items-center gap-2">
                      <span className="text-[13px] font-bold text-gray-800">Booking Status:</span>
                      <span className="px-3 py-1 rounded-full text-xs font-bold bg-[#6df19e] text-emerald-800 capitalize">
                        {booking.status}
                      </span>
                    </div>

                    <div className="flex items-center justify-between py-2">
                      <span className="text-[13px] font-bold text-gray-800">Calendar Color</span>
                      <div className="flex items-center gap-3">
                        <div
                          className="w-6 h-6 rounded border border-gray-200 shadow-sm"
                          style={{ backgroundColor: booking.calendarColor || '#4a5ebc' }}
                        />
                        <span className="text-xs font-mono text-gray-500 uppercase tracking-tight">
                          {booking.calendarColor || '#4a5ebc'}
                        </span>
                      </div>
                    </div>
                  </div>

                  {/* Action Buttons */}
                  <div className="flex items-center gap-12 pt-4 justify-center underline-offset-4">
                    {/* Edit button - only show if not cancelled or completed */}
                    {booking.status !== 'cancelled' && booking.status !== 'completed' && (
                      <button
                        onClick={() => navigate(`/bookings/edit/${booking.id}`)}
                        className="px-10 py-2.5 bg-[#4a5ebc] text-white rounded font-bold text-sm hover:bg-blue-700 transition-colors shadow-sm"
                      >
                        Edit
                      </button>
                    )}

                    {/* Mark Complete button - only show if not completed */}
                    {booking.status !== 'completed' && booking.status !== 'cancelled' && (
                      <button
                        onClick={() => markCompleteMutation.mutate(booking.id)}
                        disabled={markCompleteMutation.isPending}
                        className="text-[#4a5ebc] text-sm font-bold hover:underline disabled:opacity-50"
                      >
                        {markCompleteMutation.isPending ? 'Processing...' : 'Mark Complete'}
                      </button>
                    )}

                    <button
                      onClick={() => onRebook?.(booking)}
                      className="flex items-center gap-2 text-[#4a5ebc] text-sm font-bold hover:underline"
                    >
                      Rebook <RotateCcw className="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>

              {/* More Options Accordion */}
              <div className="border border-gray-100 rounded-lg overflow-hidden shadow-sm">
                <button
                  onClick={() => setIsOptionsOpen(!isOptionsOpen)}
                  className="w-full flex items-center justify-between px-6 py-4 bg-white hover:bg-gray-50 transition-colors text-left"
                >
                  <span className="text-sm font-bold text-gray-700">More Options</span>
                  {isOptionsOpen ? <ChevronUp className="w-5 h-5 text-gray-400" /> : <ChevronDown className="w-5 h-5 text-gray-400" />}
                </button>

                {isOptionsOpen && (
                  <div className="p-8 grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50/30 animate-in fade-in slide-in-from-top-2 duration-300">
                    {/* Cancel button - only show if not cancelled */}
                    {booking.status !== 'cancelled' && (
                      <button
                        onClick={() => {
                          if (confirm('Are you sure you want to cancel this booking?')) {
                            cancelBookingMutation.mutate(booking.id)
                          }
                        }}
                        disabled={cancelBookingMutation.isPending}
                        className="flex items-center justify-center gap-3 px-6 py-2.5 bg-red-600 text-white rounded font-bold text-xs hover:bg-red-700 transition-colors uppercase tracking-wider disabled:opacity-50"
                      >
                        {cancelBookingMutation.isPending ? 'Cancelling...' : 'Cancel Booking'} <XCircle className="w-4 h-4" />
                      </button>
                    )}

                    <button
                      onClick={() => bookingsApi.generateInvoice(booking.id)}
                      className="flex items-center justify-center gap-3 px-6 py-2.5 bg-[#4a5ebc] text-white rounded font-bold text-xs hover:bg-blue-700 transition-colors uppercase tracking-wider"
                    >
                      Preview Invoice <Eye className="w-4 h-4" />
                    </button>
                    <button
                      onClick={() => sendSMSMutation.mutate(booking.id)}
                      disabled={sendSMSMutation.isPending}
                      className="flex items-center justify-center gap-3 px-6 py-2.5 bg-[#4a5ebc] text-white rounded font-bold text-xs hover:bg-blue-700 transition-colors uppercase tracking-wider disabled:opacity-50"
                    >
                      {sendSMSMutation.isPending ? 'Sending...' : 'Send Sms Confirmation'} <FileText className="w-4 h-4" />
                    </button>
                    <button
                      onClick={() => sendEmailMutation.mutate(booking.id)}
                      disabled={sendEmailMutation.isPending}
                      className="flex items-center justify-center gap-3 px-6 py-2.5 bg-[#4a5ebc] text-white rounded font-bold text-xs hover:bg-blue-700 transition-colors uppercase tracking-wider disabled:opacity-50"
                    >
                      {sendEmailMutation.isPending ? 'Sending...' : 'Send Email Confirmation'} <Mail className="w-4 h-4" />
                    </button>
                    <div className="col-span-full flex justify-center pt-2">
                      <button className="text-gray-300 flex flex-col items-center gap-1">
                        <div className="h-0.5 w-6 bg-current rounded-full" />
                        <div className="h-0.5 w-4 bg-current rounded-full" />
                        <div className="h-0.5 w-6 bg-current rounded-full" />
                      </button>
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* Right Side: Images and Upcoming Bookings */}
            <div className="w-full lg:w-[240px] shrink-0 space-y-8">
              {/* Pet Images Stack */}
              <div className="space-y-4">
                <label className="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Pets in Booking</label>
                <div className={cn(
                  "grid gap-4",
                  uniquePets.length > 1 ? "grid-cols-2" : "grid-cols-1"
                )}>
                  {uniquePets.length > 0 ? uniquePets.map(pet => (
                    <div key={pet.id} className="aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-100 shadow-sm relative group">
                      {pet.image ? (
                        <img
                          src={pet.image}
                          alt={pet.name}
                          className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                        />
                      ) : (
                        <div className="w-full h-full flex flex-col items-center justify-center text-gray-300 gap-2">
                          <ImageIcon className="w-8 h-8 opacity-40" />
                          <span className="text-[10px] font-bold uppercase tracking-widest">{pet.name}</span>
                        </div>
                      )}
                      <div className="absolute bottom-0 left-0 right-0 bg-black/50 p-2 text-white text-[10px] font-bold backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap overflow-hidden text-ellipsis">
                        {pet.name}
                      </div>
                    </div>
                  )) : (
                    <div className="col-span-full aspect-square bg-gray-50 rounded-lg border border-dashed border-gray-200 flex items-center justify-center text-gray-300 italic text-xs">
                      No pet data found
                    </div>
                  )}
                </div>
              </div>

              {/* Upcoming Bookings Sidebar */}
              <div className="space-y-4">
                <label className="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Upcoming Bookings</label>
                <div className="space-y-3">
                  {isUpcomingLoading ? (
                    <div className="flex items-center gap-3 text-gray-400 text-sm italic py-4">
                      <Loader2 className="w-4 h-4 animate-spin" />
                      Loading future dates...
                    </div>
                  ) : !upcomingBookings || upcomingBookings.length === 0 ? (
                    <div className="p-4 bg-gray-50 rounded-lg border border-gray-100 text-center">
                      <p className="text-[13px] text-gray-400 italic">No upcomming booking exists</p>
                    </div>
                  ) : (
                    <div className="space-y-2">
                      {upcomingBookings.slice(0, 5).map(b => {
                        const bookingDate = b.startDate ? new Date(b.startDate) : null
                        const isValidDate = bookingDate && !isNaN(bookingDate.getTime())

                        return (
                          <div key={b.id} className="flex items-center gap-3 p-3 bg-blue-50/50 border border-blue-100/50 rounded-lg group transition-all hover:bg-blue-50">
                            <div className="w-8 h-8 rounded-full bg-white flex items-center justify-center shadow-sm text-blue-500">
                              <Calendar className="w-3.5 h-3.5" />
                            </div>
                            <div className="flex-1 min-w-0">
                              <p className="text-[13px] font-bold text-gray-800">
                                {isValidDate ? format(bookingDate, 'MMM do, yyyy') : 'No date'}
                              </p>
                              <p className="text-[11px] text-gray-500 font-medium flex items-center gap-1.5">
                                <Clock className="w-3 h-3" /> {b.startTime || 'No time'}
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

          <div className="mt-8 pt-6 border-t border-gray-50 flex justify-between items-center text-gray-400">
            <span className="text-[10px] font-medium tracking-tight uppercase">ID: {String(booking.id).slice(-8)}</span>
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
  )
}
