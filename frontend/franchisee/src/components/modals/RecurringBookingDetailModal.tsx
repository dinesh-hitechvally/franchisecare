import { formatDisplayDate, formatDisplayTime } from '../../lib/timeFormatUtils'
import {
  X, Edit2,
  ImageIcon
} from 'lucide-react'
import type { Booking, Pet } from '../../types'
import { cn } from '../../lib/utils'

interface RecurringBookingDetailModalProps {
  isOpen: boolean
  onClose: () => void
  recurringBooking: Booking | null
  allBookings: Booking[]
}

export function RecurringBookingDetailModal({ 
  isOpen, 
  onClose, 
  recurringBooking,
  allBookings 
}: RecurringBookingDetailModalProps) {
  if (!isOpen || !recurringBooking) return null

  // Get related bookings for this recurring series
  // Use the nested bookings from the recurring booking object, or fall back to filtering allBookings
  const seriesBookings = (recurringBooking.bookings as any[]) || allBookings.filter(b =>
    (b as any).recurringGroupId === (recurringBooking as any).recurringGroupId ||
    (b as any).recurringParentId === recurringBooking.id ||
    (b.recurringId === recurringBooking.id)
  ) || []

  const bookingCount = seriesBookings.length > 0 ? seriesBookings.length : 1

  // Extract unique pets
  const uniquePets = Array.from(new Set(recurringBooking.details?.map(d => d.pet).filter(Boolean))) as Pet[]

  // Group services by pet
  const groupedDetails = recurringBooking.details?.reduce((acc, detail) => {
    const petId = detail.petId
    if (!acc[petId]) {
      acc[petId] = { pet: detail.pet, services: [] }
    }
    acc[petId].services.push({ ...detail.service, price: detail.price })
    return acc
  }, {} as Record<string, { pet: any, services: any[] }>)

  const groupedArray = Object.values(groupedDetails || {})

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden flex flex-col">
        {/* Blue Header Strip */}
        <div className="bg-[#4a5ebc] px-6 py-4 flex justify-between items-center">
          <h2 className="text-lg font-bold text-white">View Recurring Booking Detail</h2>
          <button onClick={onClose} className="text-white/80 hover:text-white transition-colors">
            <X className="w-6 h-6" />
          </button>
        </div>

        {/* Content */}
        <div className="overflow-y-auto flex-1 p-8">
          {/* Customer Info - Full Width */}
          <div className="flex items-center gap-3 mb-6">
            <h3 className="text-xl font-medium text-[#4a5ebc] capitalize">
              {recurringBooking.customer?.first_name} {recurringBooking.customer?.last_name} - {recurringBooking.customer?.phone}
            </h3>
            <button className="text-gray-400 hover:text-[#4a5ebc] transition-colors">
              <Edit2 className="w-4 h-4" />
            </button>
          </div>

          {recurringBooking.customer?.notes && (
            <div className="w-full p-3 bg-yellow-50 border border-yellow-100 rounded-lg text-sm text-gray-700 shadow-sm mb-6">
              {recurringBooking.customer.notes}
            </div>
          )}

          {/* Two Column Layout: Recurring Schedule + Pets in Booking */}
          <div className="flex flex-col lg:flex-row gap-8 items-start mb-8">
            {/* Left Side: Recurring Schedule */}
            <div className="flex-1 min-w-0">
              <div className="border border-gray-100 rounded-lg overflow-hidden shadow-sm">
                <div className="bg-gray-50 px-6 py-3 border-b border-gray-100">
                  <span className="text-sm font-bold text-gray-700">Recurring Schedule</span>
                </div>
                {/* Green Status Bar */}
                <div className="h-2 bg-[#6df19e] w-full" />
                
                <div className="p-6 space-y-6">
                  {/* Pet Services */}
                  {groupedArray.map((group, idx) => (
                    <div key={idx} className="space-y-2">
                      <div className="flex items-center gap-3">
                        <span className="text-lg font-bold text-gray-800">{group.pet?.name}</span>
                        <button className="text-gray-400 hover:text-blue-500 transition-colors">
                          <Edit2 className="w-4 h-4" />
                        </button>
                      </div>
                      <div className="space-y-1.5 ml-1">
                        {group.services.map((svc: any, sIdx: number) => (
                          <div key={sIdx} className="text-sm text-gray-600 font-medium">
                            [{svc.name} - ${Number(svc.price).toFixed(2)}]
                          </div>
                        ))}
                      </div>
                    </div>
                  ))}

                  <div className="space-y-4 pt-2">
                    <div>
                      <label className="block text-[13px] font-bold text-gray-800 mb-1">Service Location</label>
                      <p className="text-sm text-gray-600">{recurringBooking.customer?.address || 'No location specified'}</p>
                    </div>
                    <div>
                      <label className="block text-[13px] font-bold text-gray-800 mb-1">Start Date/Time</label>
                      <p className="text-sm text-gray-600">
                        {recurringBooking.date ? formatDisplayDate(recurringBooking.date) : 'No date'} {formatDisplayTime(recurringBooking.startTime)}
                      </p>
                    </div>
                    <div>
                      <label className="block text-[13px] font-bold text-gray-800 mb-1">Frequency</label>
                      <p className="text-sm text-gray-900 font-medium uppercase">Every {(recurringBooking as any).frequency || 'N/A'}</p>
                    </div>
                    <div className="flex items-center gap-2">
                      <span className="text-[13px] font-bold text-gray-800">Auto Extend:</span>
                      <span className={cn(
                        "px-3 py-1 rounded-full text-xs font-bold text-white",
                        (recurringBooking as any).autoExtend ? "bg-[#4a5ebc]" : "bg-gray-700"
                      )}>
                        {(recurringBooking as any).autoExtend ? 'Yes' : 'No'}
                      </span>
                    </div>
                    <div className="flex items-center gap-2">
                      <span className="text-[13px] font-bold text-gray-800">Total Bookings:</span>
                      <span className="px-3 py-1 rounded-full text-xs font-bold bg-[#4a5ebc] text-white">
                        {bookingCount}
                      </span>
                    </div>
                    <div className="flex items-center justify-between py-2">
                      <span className="text-[13px] font-bold text-gray-800">Total per Booking</span>
                      <span className="text-sm font-bold text-gray-900">${recurringBooking.total || '0.00'}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Right Side: Pet Images */}
            <div className="w-full lg:w-[240px] shrink-0">
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
            </div>
          </div>

        {/* Associated Bookings - Full Width */}
        <div className="w-full mt-8">
          <div className="border border-gray-100 rounded-lg overflow-hidden shadow-sm">
            <div className="bg-gray-50 px-6 py-3 border-b border-gray-100">
              <span className="text-sm font-bold text-gray-700">Associated Bookings ({bookingCount})</span>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-left">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-4 py-3 text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th className="px-4 py-3 text-xs font-medium text-gray-500 uppercase">Time</th>
                    <th className="px-4 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th className="px-4 py-3 text-xs font-medium text-gray-500 uppercase text-right">Total</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {seriesBookings.length === 0 ? (
                    <tr>
                      <td colSpan={4} className="px-4 py-8 text-center text-gray-500 italic">
                        No bookings found in this series.
                      </td>
                    </tr>
                  ) : (
                    seriesBookings.map((booking) => (
                      <tr key={booking.id} className="hover:bg-gray-50">
                        <td className="px-4 py-3 text-sm text-gray-700">
                          {booking.date ? formatDisplayDate(booking.date) : '-'}
                        </td>
                        <td className="px-4 py-3 text-sm text-gray-700">{formatDisplayTime(booking.startTime)}</td>
                        <td className="px-4 py-3 text-sm">
                          <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                            booking.status === 'completed' ? 'bg-green-100 text-green-700' :
                            booking.status === 'cancelled' ? 'bg-red-100 text-red-700' :
                            booking.status === 'active' ? 'bg-blue-100 text-blue-700' :
                            'bg-yellow-100 text-yellow-700'
                          }`}>
                            {booking.status || 'pending'}
                          </span>
                        </td>
                        <td className="px-4 py-3 text-sm text-gray-900 text-right font-medium">
                          ${booking.total || '0.00'}
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
        </div>

        {/* Footer Info */}
        <div className="px-8 py-5 border-t border-gray-50 flex justify-between items-center text-gray-400">
          <span className="text-[10px] font-medium tracking-tight uppercase">ID: {String(recurringBooking.id).slice(-8)}</span>
          <span className="text-[10px] font-medium italic">
            Recurring Series
          </span>
        </div>
      </div>
    </div>
  )
}
