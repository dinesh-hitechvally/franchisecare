import { useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Search, MoreVertical, FileText } from 'lucide-react'
import { bookingsApi } from '../../api/services'
import type { Booking } from '../../types'
import { PortalMenu } from '../../components/ui/PortalMenu'
import { PageHeader } from '../../components/layout/PageHeader'
import { formatDisplayDate } from '../../lib/timeFormatUtils'

interface DiaryEntry {
  id: string
  time: string
  customerName: string
  petName: string
  servicesCount: string
  total: string
  address: string
  phone: string
  email: string
  customerNotes: string
  petNotes: string
  petBreed: string
  bookingNotes: string
}

export function DailyDiaryPage() {
  const today = new Date().toISOString().slice(0, 10)
  const [fromDate, setFromDate] = useState(today)
  const [toDate, setToDate] = useState(today)
  const [appliedFromDate, setAppliedFromDate] = useState(today)
  const [appliedToDate, setAppliedToDate] = useState(today)
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)
  const [visibleColumns, setVisibleColumns] = useState({
    name: true,
    pets: true,
    services: true,
    address: true,
    phone: true,
    email: true,
    customerNotes: true,
    petNotes: true,
    breeds: true,
    bookingNotes: true,
  })

  const { data: bookings = [], isLoading } = useQuery({
    queryKey: ['daily-diary', appliedFromDate, appliedToDate],
    queryFn: () =>
      bookingsApi.getAll({
        dateFrom: appliedFromDate,
        dateTo: appliedToDate,
      }),
  })

  const eligibleBookings = bookings.filter(
    (booking: Booking) => booking.status === 'active' || booking.status === 'completed'
  )

  const to12Hour = (time?: string) => {
    if (!time) return '-'
    const [hh, mm] = time.split(':')
    const hour24 = Number(hh)
    if (!Number.isFinite(hour24)) return time
    const suffix = hour24 >= 12 ? 'PM' : 'AM'
    const hour12 = hour24 % 12 || 12
    return `${hour12}:${mm ?? '00'} ${suffix}`
  }

  const diaryEntries = useMemo<DiaryEntry[]>(() => {
    return eligibleBookings.map((booking: Booking) => {
      const customer = booking.customer as any
      const customerName =
        `${customer?.first_name ?? ''} ${customer?.last_name ?? ''}`.trim() || customer?.name || '-'

      const pets = (booking.details ?? [])
        .map((detail: any) => detail.pet?.name)
        .filter(Boolean)
      const petBreeds = (booking.details ?? [])
        .map((detail: any) => detail.pet?.breed)
        .filter(Boolean)
      const petNotes = (booking.details ?? [])
        .map((detail: any) => detail.pet?.notes)
        .filter(Boolean)
      const serviceNames = (booking.details ?? [])
        .map((detail: any) => detail.service?.name)
        .filter(Boolean)

      const street = customer?.street_address || customer?.address || ''
      const suburb = customer?.suburb || ''
      const postcode = customer?.postcode || ''
      const address = [street, suburb, postcode].filter(Boolean).join(' ').trim() || '-'

      return {
        id: String(booking.id),
        time: to12Hour(booking.startTime),
        customerName,
        petName: pets.length > 0 ? pets.join(', ') : '-',
        servicesCount: `${booking.details?.length ?? 0}${serviceNames.length ? ` [${serviceNames.join(', ')}]` : ''}`,
        total: `$${Number(booking.total ?? 0).toFixed(2)}`,
        address,
        phone: customer?.phone || '-',
        email: customer?.email || '-',
        customerNotes: customer?.notes || '-',
        petNotes: petNotes.length > 0 ? petNotes.join(', ') : '-',
        petBreed: petBreeds.length > 0 ? petBreeds.join(', ') : '-',
        bookingNotes: booking.notes || '-',
      }
    })
  }, [eligibleBookings])

  const formattedHeadingDate = formatDisplayDate(appliedFromDate)

  const totalAmount = diaryEntries.reduce((sum, entry) => {
    const numeric = Number(entry.total.replace(/[^0-9.-]+/g, ''))
    return sum + (Number.isFinite(numeric) ? numeric : 0)
  }, 0)

  const visibleDataColumnCount =
    3 + Object.values(visibleColumns).filter(Boolean).length // Time + Total + Mgmt + selected columns

  return (
    <div className="space-y-6 px-1 py-1 w-full">
      <PageHeader
        title="Daily Diary"
        description="Print daily diary sheet for upcoming bookings"
        icon={<FileText size={20} />}
      />

      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <p className="text-sm text-gray-600 mb-6">
          Print daily diary sheet (for bookings that are scheduled within next 6 months)
        </p>

        <div className="grid grid-cols-1 lg:grid-cols-[1fr_1fr_auto] gap-6 items-end mb-6">
          <div>
            <label className="text-sm text-gray-500 block mb-1">From</label>
            <input
              type="date"
              value={fromDate}
              onChange={(e) => setFromDate(e.target.value)}
              className="w-full bg-transparent border-b border-gray-300 py-2 outline-none text-gray-800"
            />
          </div>
          <div>
            <label className="text-sm text-gray-500 block mb-1">To</label>
            <input
              type="date"
              value={toDate}
              onChange={(e) => setToDate(e.target.value)}
              className="w-full bg-transparent border-b border-gray-300 py-2 outline-none text-gray-800"
            />
          </div>
          <button
            type="button"
            onClick={() => {
              setAppliedFromDate(fromDate)
              setAppliedToDate(toDate)
            }}
            className="h-10 px-4 bg-white border border-gray-300 shadow-sm rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
          >
            <Search className="w-4 h-4" />
            General Daily Sheet
          </button>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-gray-700 text-sm">
          <label className="flex items-center gap-2"><input type="checkbox" checked={visibleColumns.name} onChange={(e) => setVisibleColumns((prev) => ({ ...prev, name: e.target.checked }))} /> Name</label>
          <label className="flex items-center gap-2"><input type="checkbox" checked={visibleColumns.pets} onChange={(e) => setVisibleColumns((prev) => ({ ...prev, pets: e.target.checked }))} /> Pets</label>
          <label className="flex items-center gap-2"><input type="checkbox" checked={visibleColumns.services} onChange={(e) => setVisibleColumns((prev) => ({ ...prev, services: e.target.checked }))} /> Services</label>
          <label className="flex items-center gap-2"><input type="checkbox" checked={visibleColumns.address} onChange={(e) => setVisibleColumns((prev) => ({ ...prev, address: e.target.checked }))} /> Address</label>
          <label className="flex items-center gap-2"><input type="checkbox" checked={visibleColumns.phone} onChange={(e) => setVisibleColumns((prev) => ({ ...prev, phone: e.target.checked }))} /> Phone</label>
          <label className="flex items-center gap-2"><input type="checkbox" checked={visibleColumns.email} onChange={(e) => setVisibleColumns((prev) => ({ ...prev, email: e.target.checked }))} /> Email</label>
          <label className="flex items-center gap-2"><input type="checkbox" checked={visibleColumns.customerNotes} onChange={(e) => setVisibleColumns((prev) => ({ ...prev, customerNotes: e.target.checked }))} /> Customer Notes</label>
          <label className="flex items-center gap-2"><input type="checkbox" checked={visibleColumns.petNotes} onChange={(e) => setVisibleColumns((prev) => ({ ...prev, petNotes: e.target.checked }))} /> Pet Notes</label>
          <label className="flex items-center gap-2"><input type="checkbox" checked={visibleColumns.breeds} onChange={(e) => setVisibleColumns((prev) => ({ ...prev, breeds: e.target.checked }))} /> Breeds</label>
          <label className="flex items-center gap-2"><input type="checkbox" checked={visibleColumns.bookingNotes} onChange={(e) => setVisibleColumns((prev) => ({ ...prev, bookingNotes: e.target.checked }))} /> Booking Notes</label>
        </div>
      </div>

      <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse text-sm">
            <thead>
              <tr className="border-b border-gray-200 bg-white text-gray-700">
                <th className="px-4 py-4 font-semibold">Time</th>
                {visibleColumns.name && <th className="px-4 py-4 font-semibold">Name</th>}
                {visibleColumns.pets && <th className="px-4 py-4 font-semibold">Pets</th>}
                {visibleColumns.services && <th className="px-4 py-4 font-semibold">Services</th>}
                <th className="px-4 py-4 font-semibold">Total $</th>
                {visibleColumns.address && <th className="px-4 py-4 font-semibold">Address</th>}
                {visibleColumns.phone && <th className="px-4 py-4 font-semibold">Phone</th>}
                {visibleColumns.email && <th className="px-4 py-4 font-semibold">Email</th>}
                {visibleColumns.customerNotes && <th className="px-4 py-4 font-semibold">Customer Notes</th>}
                {visibleColumns.petNotes && <th className="px-4 py-4 font-semibold">Pet Notes</th>}
                {visibleColumns.breeds && <th className="px-4 py-4 font-semibold">Breeds</th>}
                {visibleColumns.bookingNotes && <th className="px-4 py-4 font-semibold">Booking Notes</th>}
                <th className="px-4 py-4 font-semibold text-center">Mgmt</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              <tr>
                <td colSpan={visibleDataColumnCount} className="px-4 py-3 text-center font-semibold text-gray-700 bg-gray-50">
                  {formattedHeadingDate}
                </td>
              </tr>
              {isLoading && (
                <tr>
                  <td colSpan={visibleDataColumnCount} className="px-4 py-8 text-center text-gray-500 italic">
                    Loading daily diary...
                  </td>
                </tr>
              )}
              {diaryEntries.map((entry) => (
                <tr key={entry.id} className="hover:bg-gray-50">
                  <td className="px-4 py-4 text-gray-700 whitespace-nowrap">{entry.time}</td>
                  {visibleColumns.name && <td className="px-4 py-4 text-gray-800 font-medium">{entry.customerName}</td>}
                  {visibleColumns.pets && <td className="px-4 py-4 text-gray-700">{entry.petName}</td>}
                  {visibleColumns.services && <td className="px-4 py-4 text-gray-700">{entry.servicesCount}</td>}
                  <td className="px-4 py-4 text-gray-800 font-semibold whitespace-nowrap">{entry.total}</td>
                  {visibleColumns.address && <td className="px-4 py-4 text-gray-700">{entry.address}</td>}
                  {visibleColumns.phone && <td className="px-4 py-4 text-blue-700">{entry.phone || '-'}</td>}
                  {visibleColumns.email && <td className="px-4 py-4 text-blue-700">{entry.email || '-'}</td>}
                  {visibleColumns.customerNotes && <td className="px-4 py-4 text-gray-700">{entry.customerNotes || '-'}</td>}
                  {visibleColumns.petNotes && <td className="px-4 py-4 text-gray-700">{entry.petNotes || '-'}</td>}
                  {visibleColumns.breeds && <td className="px-4 py-4 text-gray-700">{entry.petBreed || '-'}</td>}
                  {visibleColumns.bookingNotes && <td className="px-4 py-4 text-gray-700">{entry.bookingNotes || '-'}</td>}
                  <td className="px-4 py-4 text-center relative">
                    <button
                      type="button"
                      onClick={(e) => {
                        if (openMenuId === entry.id) {
                          setOpenMenuId(null)
                          setMenuPos(null)
                        } else {
                          const rect = (e.currentTarget as HTMLButtonElement).getBoundingClientRect()
                          setMenuPos({ top: rect.bottom + 4, left: rect.right - 160 })
                          setOpenMenuId(entry.id)
                        }
                      }}
                      className="p-1 rounded hover:bg-gray-100"
                    >
                      <MoreVertical className="w-5 h-5 text-gray-600" />
                    </button>
                    <PortalMenu
                      isOpen={openMenuId === entry.id}
                      onClose={() => { setOpenMenuId(null); setMenuPos(null) }}
                      position={menuPos}
                      width={160}
                    >
                        <button type="button" className="w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                          View
                        </button>
                    </PortalMenu>
                  </td>
                </tr>
              ))}
              <tr className="bg-gray-50 border-t-2 border-gray-200">
                <td colSpan={visibleDataColumnCount} className="px-4 py-4 text-center font-semibold text-gray-900 uppercase">
                  Total: ${totalAmount.toFixed(2)}
                </td>
              </tr>
            </tbody>
          </table>
          {!isLoading && diaryEntries.length === 0 && (
            <div className="p-12 text-center text-gray-400 italic">
              No entries found for the selected date range.
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
