import { useState, useMemo, useEffect, useRef } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { PageHeader } from '../../components/layout/PageHeader'
import { Button } from '../../components/ui/Button'
import { Search, ChevronDown, ChevronUp, XCircle, Edit2, Check, Plus } from 'lucide-react'
import { Input } from '../../components/ui/Input'
import { Select } from '../../components/ui/Select'
import { TimePicker } from '../../components/ui/TimePicker'
import { customersApi, servicesApi, bookingsApi, recurringBookingsApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import { useNavigate, Link, useParams, useLocation } from 'react-router-dom'
import type { Customer } from '../../types'

export function NewBookingsPage() {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const location = useLocation()
  const { id: editBookingId } = useParams<{ id: string }>()
  const isEditMode = Boolean(editBookingId)
  const isRecurringEdit = isEditMode && location.pathname.includes('/recurring/')
  const { addToast } = useToastStore()
  const hasInitializedEditForm = useRef(false)

  const [recurringSettingsOpen, setRecurringSettingsOpen] = useState(true)
  const [searchTerm, setSearchTerm] = useState('')
  const [showSearchResults, setShowSearchResults] = useState(false)
  const searchContainerRef = useRef<HTMLDivElement>(null)
  
  const [selectedCustomer, setSelectedCustomer] = useState<Customer | null>(null)
  const [petServices, setPetServices] = useState<Record<string, { serviceId: string; price: number }[]>>({})
  const [expandedPetIds, setExpandedPetIds] = useState<string[]>([])
  
  const [bookingDate, setBookingDate] = useState('')
  const [bookingStartTime, setBookingStartTime] = useState('')
  const [bookingEndTime, setBookingEndTime] = useState('')
  const [bookingNotes, setBookingNotes] = useState('')
  const [sendSmsConfirmation, setSendSmsConfirmation] = useState(false)
  const [sendEmailConfirmation, setSendEmailConfirmation] = useState(false)
  const [calendarColor, setCalendarColor] = useState('#4F46E5')

  const [isRecurring, setIsRecurring] = useState(false)
  const [recurringFrequency, setRecurringFrequency] = useState('1')
  const [recurringRepeatDay, setRecurringRepeatDay] = useState('')
  const [recurringRepeatTime, setRecurringRepeatTime] = useState('')
  const [recurringRepeatUntil, setRecurringRepeatUntil] = useState('')
  const [recurringAutoExtend, setRecurringAutoExtend] = useState(false)

  const minRecurringDate = useMemo(() => new Date().toISOString().split('T')[0], []);

  const maxRecurringDate = useMemo(() => {
    const nextYearPlusOne = new Date().getFullYear() + 2;
    return `${nextYearPlusOne}-12-31`;
  }, []);

  // Fetch Services
  const { data: allServices } = useQuery({
    queryKey: ['services'],
    queryFn: () => servicesApi.getAll(),
  })

  // Fetch Customers for Search
  const { data: searchResults } = useQuery({
    queryKey: ['customers', 'search', searchTerm],
    queryFn: () => customersApi.getAll({
      search: searchTerm,
    }),
    enabled: searchTerm.length > 2 && !selectedCustomer,
  })

  // Fetch Existing Bookings for Sidebar
  const { data: existingBookings } = useQuery({
    queryKey: ['bookings', 'date', bookingDate],
    queryFn: () => bookingsApi.getAll({
      dateFrom: bookingDate,
      dateTo: bookingDate,
    }),
    enabled: !!bookingDate,
  })

  const { data: bookingToEdit } = useQuery({
    queryKey: ['bookings', 'edit', editBookingId, isRecurringEdit],
    queryFn: () => isRecurringEdit 
      ? recurringBookingsApi.getById(String(editBookingId))
      : bookingsApi.getById(String(editBookingId)),
    enabled: !!editBookingId,
  })

  const { data: editCustomerData } = useQuery({
    queryKey: ['customers', 'edit', bookingToEdit?.customerId || bookingToEdit?.customer_id],
    queryFn: () => customersApi.getById(String(bookingToEdit?.customerId || bookingToEdit?.customer_id)),
    enabled: !!(bookingToEdit?.customerId || bookingToEdit?.customer_id),
  })

  // Handle click outside for search results
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (searchContainerRef.current && !searchContainerRef.current.contains(event.target as Node)) {
        setShowSearchResults(false)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const createBookingMutation = useMutation({
    mutationFn: (data: any) => bookingsApi.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bookings'] })
      addToast('Booking created successfully', 'success')
      navigate('/bookings')
    },
    onError: () => addToast('Failed to create booking', 'error')
  })

  const createRecurringBookingMutation = useMutation({
    mutationFn: (data: any) => recurringBookingsApi.create(data),
    onSuccess: (response: any) => {
      queryClient.invalidateQueries({ queryKey: ['recurring-bookings'] })
      addToast(`Recurring booking created with ${response?.bookings?.length || 0} bookings`, 'success')
      navigate('/bookings/recurring')
    },
    onError: () => addToast('Failed to create recurring booking', 'error')
  })

  const updateBookingMutation = useMutation({
    mutationFn: ({ bookingId, data }: { bookingId: string; data: any }) =>
      bookingsApi.update(bookingId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bookings'] })
      addToast('Booking updated successfully', 'success')
      navigate('/bookings/manage')
    },
    onError: () => addToast('Failed to update booking', 'error'),
  })

  const updateRecurringBookingMutation = useMutation({
    mutationFn: ({ bookingId, data }: { bookingId: string; data: any }) =>
      recurringBookingsApi.update(bookingId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['recurring-bookings'] })
      addToast('Recurring booking updated successfully', 'success')
      navigate('/bookings/recurring')
    },
    onError: () => addToast('Failed to update recurring booking', 'error'),
  })

  useEffect(() => {
    if (!isEditMode || !bookingToEdit || hasInitializedEditForm.current) {
      return
    }

    const prefillCustomer = (editCustomerData || bookingToEdit.customer) as Customer | undefined
    if (prefillCustomer) {
      setSelectedCustomer(prefillCustomer)
      setSearchTerm('')
      setShowSearchResults(false)
    }

    setBookingDate(bookingToEdit.startDate || bookingToEdit.start_date || '')
    setBookingStartTime(bookingToEdit.startTime || bookingToEdit.start_time || '')
    setBookingEndTime(bookingToEdit.endTime || bookingToEdit.end_time || '')
    setBookingNotes(bookingToEdit.notes || '')
    setSendSmsConfirmation(!!bookingToEdit.sendSms || !!bookingToEdit.send_sms)
    setSendEmailConfirmation(!!bookingToEdit.sendEmail || !!bookingToEdit.send_email)
    setCalendarColor(bookingToEdit.calendarColor || bookingToEdit.calendar_color || '#4F46E5')
    setIsRecurring(isRecurringEdit || !!bookingToEdit.isRecurring)

    // Handle recurring booking specific fields
    if (isRecurringEdit) {
      setRecurringFrequency(String(bookingToEdit.frequency || bookingToEdit.recurringFrequency || '1'))
      setRecurringRepeatDay(bookingToEdit.repeat_day || bookingToEdit.repeatDay || '')
      setRecurringRepeatTime(bookingToEdit.repeat_time || bookingToEdit.repeatTime || '')
      setRecurringRepeatUntil(bookingToEdit.repeat_until || bookingToEdit.repeatUntil || '')
      setRecurringAutoExtend(!!bookingToEdit.auto_extend || !!bookingToEdit.autoExtend)
    }

    const detailMap = (bookingToEdit.details || []).reduce((acc: Record<string, { serviceId: string; price: number }[]>, detail: any) => {
      const petId = String(detail.petId || detail.pet_id || detail.item_id)
      if (!petId || !(detail.serviceId || detail.service_id)) {
        return acc
      }

      if (!acc[petId]) {
        acc[petId] = []
      }

      acc[petId].push({
        serviceId: String(detail.serviceId || detail.service_id),
        price: Number(detail.price || detail.service_price || 0),
      })

      return acc
    }, {} as Record<string, { serviceId: string; price: number }[]>)

    setPetServices(detailMap)
    setExpandedPetIds(Object.keys(detailMap))

    hasInitializedEditForm.current = true
  }, [isEditMode, isRecurringEdit, bookingToEdit, editCustomerData])

  const bookingTotal = useMemo(() => {
    let total = 0
    Object.values(petServices).forEach(services => {
      services.forEach(s => {
        total += Number(s.price)
      })
    })
    return total.toFixed(2)
  }, [petServices])

  const handleCreateBooking = () => {
    const activePetIds = Object.keys(petServices).filter(id => petServices[id].length > 0)
    
    if (!selectedCustomer || activePetIds.length === 0 || !bookingDate || !bookingStartTime) {
      addToast('Please select a customer, at least one pet with services, date, and start time', 'error')
      return
    }

    const allServiceIds = Array.from(new Set(Object.values(petServices).flat().map(s => s.serviceId)))
    
    // Calculate total duration from all selected services
    const totalDuration = allServices?.reduce((sum, service) => {
      if (allServiceIds.includes(String(service.id))) {
        return sum + (service.duration || 30)
      }
      return sum
    }, 0) || 30

    // Build a map of service durations
    const serviceDurationMap: Record<string, number> = {}
    allServices?.forEach(s => {
      serviceDurationMap[String(s.id)] = s.duration || 30
    })

    // Transform petServices into services array format
    const services = Object.entries(petServices).flatMap(([itemId, serviceList]) =>
      serviceList.map(s => ({
        item_id: itemId,
        service_id: s.serviceId,
        service_price: Number(s.price),
        duration: serviceDurationMap[s.serviceId] || 30
      }))
    )

    if (isEditMode) {
      if (isRecurringEdit) {
        updateRecurringBookingMutation.mutate({
          bookingId: String(editBookingId),
          data: {
            customer_id: selectedCustomer.id,
            services: services,
            start_date: bookingDate,
            start_time: bookingStartTime,
            end_time: bookingEndTime,
            calendar_color: calendarColor,
            status: bookingToEdit?.status || 'active',
            total: parseFloat(bookingTotal),
            duration: totalDuration,
            notes: bookingNotes,
            frequency: parseInt(recurringFrequency),
            repeat_day: recurringRepeatDay,
            repeat_time: recurringRepeatTime,
            repeat_until: recurringRepeatUntil,
            auto_extend: recurringAutoExtend,
          },
        })
      } else {
        updateBookingMutation.mutate({
          bookingId: String(editBookingId),
          data: {
            customer_id: selectedCustomer.id,
            services: services,
            start_date: bookingDate,
            start_time: bookingStartTime,
            end_time: bookingEndTime,
            calendar_color: calendarColor,
            status: bookingToEdit?.status || 'active',
            total: parseFloat(bookingTotal),
            duration: totalDuration,
            notes: bookingNotes,
            send_sms: sendSmsConfirmation,
            send_email: sendEmailConfirmation,
          },
        })
      }
      return
    }

    if (isRecurring) {
      // Create recurring booking via BookingRecurringController
      createRecurringBookingMutation.mutate({
        customer_id: selectedCustomer.id,
        start_date: bookingDate,
        services: services,
        is_recurring: true,
        recurring: {
          frequency: parseInt(recurringFrequency),
          repeat_day: recurringRepeatDay,
          repeat_time: recurringRepeatTime,
          repeat_until: recurringRepeatUntil,
          auto_extend: recurringAutoExtend,
        },
        color: calendarColor,
        notes: bookingNotes,
      })
    } else {
      // Create single booking via BookingController
      createBookingMutation.mutate({
        customer_id: selectedCustomer.id,
        services: services,
        start_date: bookingDate,
        start_time: bookingStartTime,
        end_time: bookingEndTime,
        calendar_color: calendarColor,
        status: 'active',
        total: parseFloat(bookingTotal),
        duration: totalDuration,
        notes: bookingNotes,
        send_sms: sendSmsConfirmation,
        send_email: sendEmailConfirmation,
      })
    }
  }

  const clearSelection = () => {
    setSelectedCustomer(null)
    setSearchTerm('')
    setPetServices({})
    setExpandedPetIds([])
  }

  const togglePetAccordion = (petId: string) => {
    setExpandedPetIds(prev => 
      prev.includes(petId) 
        ? prev.filter(id => id !== petId) 
        : [...prev, petId]
    )
  }

  const handleServiceToggle = (petId: string, serviceId: string, checked: boolean, defaultPrice: number) => {
    setPetServices(prev => {
      const currentServices = prev[petId] || []
      const newServices = checked 
        ? [...currentServices, { serviceId, price: defaultPrice }]
        : currentServices.filter(s => s.serviceId !== serviceId)
      
      return { ...prev, [petId]: newServices }
    })
  }

  const handleServicePriceChange = (petId: string, serviceId: string, newPrice: number) => {
    setPetServices(prev => {
      const currentServices = prev[petId] || []
      return {
        ...prev,
        [petId]: currentServices.map(s => 
          s.serviceId === serviceId ? { ...s, price: newPrice } : s
        )
      }
    })
  }

  return (
    <div className="space-y-6 px-4 py-6 w-full max-w-[1600px] mx-auto bg-gray-50/30 min-h-screen">
      <PageHeader
        title={isEditMode ? 'Edit Booking' : 'New Bookings'}
        description={isEditMode ? 'Update booking details and services' : 'Create a new booking for your customers'}
        icon={<Plus className="w-5 h-5" />}
      />

      <div className="flex flex-col lg:flex-row gap-8 items-start">
        <div className="flex-1 space-y-8 min-w-0 w-full">
          {/* Main Booking Form Card */}
          <Card className="p-5 border-none shadow-[0_4px_20px_rgba(0,0,0,0.08)] bg-white overflow-visible">
            <p className="text-[13px] text-gray-500 mb-8 leading-relaxed">
              {isEditMode
                ? 'Update booking details below. Adjust pet services, date/time, notes, and confirmation settings before saving.'
                : 'Start your booking by simply searching customer by name or pet name. Once Pet/Services are assigned, you can choose to create recurring booking'}
            </p>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8 mb-10">
              <div className="relative col-span-full" ref={searchContainerRef}>
                <div className="flex flex-col md:flex-row items-start md:items-center gap-4">
                  <div className="w-40">
                    <label className="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Search By</label>
                    <Select 
                      value="Customer"
                      options={[
                        { label: 'Customer', value: 'Customer' },
                        { label: 'New Customer', value: 'New_Customer' }
                      ]}
                      onChange={(v) => { if (v === 'New_Customer') navigate('/customers/add') }}
                      className="border-0 border-b border-gray-200 rounded-none px-0 py-2 bg-transparent w-full text-[14px] focus:ring-0 focus:border-blue-500 cursor-pointer"
                    />
                  </div>
                  <div className="relative flex-1 w-full">
                    <label className="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Search Customer / Pet</label>
                    <Input 
                      value={selectedCustomer ? `${selectedCustomer.first_name || ''} ${selectedCustomer.last_name || ''} [${selectedCustomer.address || selectedCustomer.street_address || 'No Address'}] [${selectedCustomer.pets?.filter(p => p.isActive).map(p=>p.name).join(', ') || 'No Pets'}]` : searchTerm}
                      onChange={(e) => {
                        if (selectedCustomer) return
                        setSearchTerm(e.target.value)
                        setShowSearchResults(true)
                      }}
                      onFocus={() => !selectedCustomer && setShowSearchResults(true)}
                      placeholder="Search by Name, Pet, Phone, Email..."
                      readOnly={!!selectedCustomer}
                      className={`border-0 border-b border-gray-200 rounded-none px-0 py-2 bg-transparent w-full text-[14px] transition-all focus:ring-0 focus:border-blue-500 pr-10 ${selectedCustomer ? 'font-medium text-gray-800 cursor-default' : ''}`}
                    />
                    {(searchTerm || selectedCustomer) && (
                      <div className="absolute right-0 top-1/2 mt-3 -translate-y-1/2 flex items-center gap-2">
                        <XCircle 
                          className="w-4 h-4 text-gray-300 cursor-pointer hover:text-gray-500 transition-colors" 
                          onClick={clearSelection}
                        />
                        {selectedCustomer && (
                          <Link to={`/customers/edit/${String(selectedCustomer.id)}`}>
                            <Edit2 className="w-4 h-4 text-blue-500 hover:text-blue-700 cursor-pointer transition-colors" />
                          </Link>
                        )}
                      </div>
                    )}
                  </div>
                </div>

                {/* Search Results Dropdown */}
                {showSearchResults && !selectedCustomer && searchTerm.length > 2 && (
                  <div className="absolute z-[100] left-0 right-0 mt-2 bg-white border border-gray-100 rounded-lg shadow-[0_10px_40px_rgba(0,0,0,0.15)] max-h-72 overflow-y-auto overflow-x-hidden">
                    {searchResults && searchResults.length > 0 ? (
                      searchResults.map(c => (
                        <div 
                          key={c.id} 
                          className="p-4 hover:bg-gray-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors"
                          onClick={() => {
                            setSelectedCustomer(c)
                            setShowSearchResults(false)
                            const activePets = c.pets?.filter(p => p.isActive) || []
                            if (activePets.length > 0 && activePets[0].id) {
                              setExpandedPetIds([String(activePets[0].id)])
                            }
                          }}
                        >
                          <div className="flex items-center justify-between gap-4">
                            <div>
                              <div className="font-semibold text-sm text-gray-800">{c.first_name} {c.last_name}</div>
                              <div className="text-[11px] text-gray-400 mt-0.5">{c.phone} | {c.email} | {c.address || c.street_address}</div>
                            </div>
                            {c.pets && c.pets.filter(p => p.isActive).length > 0 && (
                              <div className="text-[10px] bg-blue-50 text-blue-600 px-2.5 py-1 rounded-full font-bold tracking-tight">
                                {c.pets.filter(p => p.isActive).map(p => p.name).join(', ')}
                              </div>
                            )}
                          </div>
                        </div>
                      ))
                    ) : (
                      <div className="p-6 text-center">
                        <p className="text-sm text-gray-400 italic">No customers found</p>
                      </div>
                    )}
                  </div>
                )}
              </div>

              <div className="col-span-full grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                  <label className="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Booking Date *</label>
                  <Input 
                    type="date"
                    value={bookingDate}
                    onChange={(e) => setBookingDate(e.target.value)}
                    className="border-0 border-b border-gray-200 rounded-none px-0 py-2 bg-transparent w-full text-[14px] focus:ring-0 focus:border-blue-500"
                  />
                </div>
                <div>
                  <TimePicker
                    label="Booking Start Time"
                    labelClassName="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2"
                    value={bookingStartTime}
                    onChange={setBookingStartTime}
                    required
                  />
                </div>
                <div>
                  <TimePicker
                    label="Booking End Time"
                    labelClassName="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2"
                    value={bookingEndTime}
                    onChange={setBookingEndTime}
                  />
                </div>
              </div>
            </div>

            {/* Pets Accordion List */}
            <div className="space-y-4 mt-12 bg-gray-50/50 rounded-xl">
              {selectedCustomer ? (
                selectedCustomer.pets && selectedCustomer.pets.length > 0 ? (
                  selectedCustomer.pets.filter(p => !!p.isActive).map(pet => (
                    <div key={pet.id} className="bg-white border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:shadow-md">
                      <div 
                        className="flex items-center justify-between p-5 cursor-pointer hover:bg-gray-50/50 group"
                        onClick={() => togglePetAccordion(String(pet.id))}
                      >
                        <div className="flex items-center gap-4">
                          <span className="text-[14px] font-bold text-gray-800 tracking-tight">
                            {pet.name}{pet.breed ? ` | ${pet.breed}` : ''}{pet.size ? ` | ${pet.size}` : ''}
                          </span>
                          <Edit2 className="w-3.5 h-3.5 text-blue-500 opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                        {expandedPetIds.includes(String(pet.id)) ? (
                          <ChevronUp className="w-5 h-5 text-gray-400" />
                        ) : (
                          <ChevronDown className="w-5 h-5 text-gray-400" />
                        )}
                      </div>

                      {expandedPetIds.includes(String(pet.id)) && (
                        <div className="p-5 pt-2 border-t border-gray-50 animate-in fade-in slide-in-from-top-2 duration-300">
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-x-16 gap-y-5">
                            {allServices?.map(service => {
                              const isChecked = (petServices[String(pet.id)] || []).some(s => s.serviceId === String(service.id));
                              const currentPrice = (petServices[String(pet.id)] || []).find(s => s.serviceId === String(service.id))?.price ?? Number(service.price);
                              return (
                                <div key={service.id} className="flex items-center justify-between text-[13px] text-gray-600 hover:text-gray-900 transition-all group whitespace-nowrap">
                                  <label className="flex items-center cursor-pointer relative flex-1">
                                    <input 
                                      type="checkbox" 
                                      checked={isChecked}
                                      onChange={(e) => handleServiceToggle(String(pet.id), String(service.id), e.target.checked, Number(service.price))}
                                      className="peer w-5 h-5 mr-4 border-2 border-gray-300 rounded focus:ring-blue-500 text-blue-600 transition-all appearance-none checked:bg-blue-600 checked:border-blue-600" 
                                    />
                                    <Check className="w-3 h-3 text-white absolute left-1 top-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none" />
                                    <span className="font-medium">{service.name}</span>
                                  </label>
                                  <div className="flex items-center w-24 justify-end">
                                    <span className="text-gray-400 mr-1">$</span>
                                    {isChecked ? (
                                      <Input
                                        type="number"
                                        value={currentPrice}
                                        onChange={(e) => handleServicePriceChange(String(pet.id), String(service.id), parseFloat(e.target.value) || 0)}
                                        className="border-b border-dotted border-gray-400 focus:border-blue-500 rounded-none bg-transparent w-16 text-right px-1 py-0 text-gray-800 font-bold"
                                        step="0.01"
                                      />
                                    ) : (
                                      <span className="font-bold border-b border-dotted border-transparent pb-0.5 text-gray-500 w-16 text-right cursor-default select-none">
                                        {Number(service.price).toFixed(2)}
                                      </span>
                                    )}
                                  </div>
                                </div>
                              );
                            })}
                          </div>
                        </div>
                      )}
                    </div>
                  ))
                ) : (
                  <div className="py-12 text-center bg-white rounded-xl border border-dashed border-gray-300">
                    <p className="text-sm text-gray-500 font-medium tracking-tight">This customer has no active pets.</p>
                  </div>
                )
              ) : (
                <div className="py-20 text-center bg-white rounded-xl border border-dashed border-gray-300">
                  <Search className="w-10 h-10 text-gray-200 mx-auto mb-4" />
                  <p className="text-sm text-gray-500 font-bold tracking-tight">Please search and select a customer above</p>
                  <p className="text-xs text-gray-400 mt-2 italic px-8">Once selected, you can assign services for each pet here.</p>
                </div>
              )}
            </div>
          </Card>

          {/* Recurring Toggle Area */}
          <div className="space-y-4">
             <p className="text-[12px] text-gray-500 italic max-w-2xl leading-relaxed">
              This is a part of recurring booking. Tick here to edit all active/future bookings. Leave it untick to edit this occurance/booking only
            </p>
            <label className="flex items-center gap-4 cursor-pointer w-fit group py-2">
              <span className="relative flex items-center">
                <input 
                  type="checkbox" 
                  checked={isRecurring}
                  onChange={(e) => setIsRecurring(e.target.checked)}
                  className="peer w-5 h-5 text-blue-600 rounded border-2 border-blue-600 focus:ring-blue-500 transition-all appearance-none checked:bg-blue-600" 
                />
                <Check className="w-3 h-3 text-white absolute left-1 top-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none" />
              </span>
              <span className={`text-[14px] font-bold transition-colors tracking-tight ${isRecurring ? 'text-blue-600' : 'text-gray-700'}`}>Tick to Add Recurring Settings</span>
            </label>
          </div>

          {/* Recurring Settings Card */}
          {isRecurring && (
            <Card className="border-none shadow-[0_4px_20px_rgba(0,0,0,0.08)] bg-white overflow-visible">
              <div 
                className="flex items-center justify-between p-5 border-b border-gray-50 bg-white cursor-pointer hover:bg-gray-50/50 transition-colors"
                onClick={() => setRecurringSettingsOpen(!recurringSettingsOpen)}
              >
                <div className="flex items-center gap-2">
                  <span className="text-[13px] font-bold text-gray-800 tracking-wider">RECURRING SETTINGS</span>
                </div>
                {recurringSettingsOpen ? <ChevronUp className="w-5 h-5 text-gray-400" /> : <ChevronDown className="w-5 h-5 text-gray-400" />}
              </div>
              
              {recurringSettingsOpen && (
                <div className="p-5 bg-white space-y-8 animate-in fade-in slide-in-from-top-2 duration-300">
                  <p className="text-[13px] text-gray-900 font-bold leading-relaxed pr-8">
                    The recurring settings are to update future bookings only. Please ensure THIS booking as per above date are also correct. Both THIS booking and all FUTURE bookings will update when you save. Any active bookings prior to this date will not be changed.
                  </p>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-12 mt-4 items-end">
                    <div className="flex items-center gap-6">
                      <div className="flex-1">
                        <label className="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Repeat Every</label>
                        <Select 
                          value={recurringFrequency}
                          options={Array.from({ length: 20 }, (_, i) => ({
                            label: `Every ${i + 1} Week${i === 0 ? '' : 's'}`,
                            value: String(i + 1)
                          }))}
                          onChange={(val) => setRecurringFrequency(String(val))}
                          className="border-0 border-b border-gray-200 rounded-none px-0 py-2 bg-transparent w-full text-[14px] focus:ring-0 focus:border-blue-500 cursor-pointer"
                        />
                      </div>
                      <div className="flex-1">
                        <label className="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">On</label>
                        <Select 
                          value={recurringRepeatDay}
                          options={[
                            { label: 'Monday', value: 'Monday' },
                            { label: 'Tuesday', value: 'Tuesday' },
                            { label: 'Wednesday', value: 'Wednesday' },
                            { label: 'Thursday', value: 'Thursday' },
                            { label: 'Friday', value: 'Friday' },
                            { label: 'Saturday', value: 'Saturday' },
                            { label: 'Sunday', value: 'Sunday' },
                          ]}
                          onChange={(val) => setRecurringRepeatDay(String(val))}
                          className="border-0 border-b border-gray-200 rounded-none px-0 py-2 bg-transparent w-full text-[14px] focus:ring-0 focus:border-blue-500 cursor-pointer"
                        />
                      </div>
                    </div>
                    <div className="flex items-center gap-6">
                       <div className="flex-1">
                        <label className="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Time</label>
                        <Input 
                          type="time"
                          value={recurringRepeatTime}
                          onChange={(e) => setRecurringRepeatTime(e.target.value)}
                          className="border-0 border-b border-gray-200 rounded-none px-0 py-2 bg-transparent w-full text-[14px] focus:ring-0 focus:border-blue-500"
                        />
                       </div>
                       <div className="flex-1">
                          <label className="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Until</label>
                          <Input 
                            type="date"
                            value={recurringRepeatUntil}
                            onChange={(e) => setRecurringRepeatUntil(e.target.value)}
                            min={minRecurringDate}
                            max={maxRecurringDate}
                            className="border-0 border-b border-gray-200 border-dotted rounded-none px-0 py-2 bg-transparent w-full text-[14px] text-gray-700 focus:ring-0 focus:border-blue-500 cursor-pointer"
                          />
                       </div>
                    </div>
                  </div>
                  <label className="flex items-center gap-3 cursor-pointer mt-4">
                    <input 
                      type="checkbox" 
                      className="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500" 
                      checked={recurringAutoExtend}
                      onChange={(e) => setRecurringAutoExtend(e.target.checked)}
                    />
                    <span className="text-[14px] text-gray-700 font-medium">Auto extend recurring bookings until i manually cancel the recurring setting.</span>
                  </label>
                </div>
              )}
            </Card>
          )}

          {/* Submission and Confirmation Card */}
          <Card className="p-5 border-none shadow-[0_4px_25px_rgba(0,0,0,0.1)] bg-white space-y-12">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-20">
              <div className="space-y-6">
                <div>
                  <label className="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Booking Total ($)</label>
                  <div className="border-b border-gray-200 pb-2">
                    <span className="text-4xl font-black text-gray-900 tracking-tight">{bookingTotal}</span>
                  </div>
                  <p className="text-[11px] text-gray-400 mt-2 italic leading-relaxed">To edit booking total, change service price above, per pet</p>
                </div>
                
                <div className="pt-4">
                   <label className="block text-[14px] font-bold text-gray-600 mb-4 tracking-tight">Booking Notes</label>
                   <div className="relative">
                    <textarea 
                      value={bookingNotes}
                      onChange={(e) => setBookingNotes(e.target.value)}
                      placeholder="Notes about this Booking"
                      className="w-full border-b border-gray-200 p-0 pb-2 bg-transparent focus:ring-0 focus:border-blue-500 outline-none resize-none h-16 text-[14px] transition-all"
                    />
                   </div>
                </div>

                  <div className="flex items-center gap-4 pt-4">
                    <span className="text-[14px] font-bold text-gray-700">Calendar Color:</span>
                    <input
                      type="color"
                      value={calendarColor}
                      onChange={(e) => setCalendarColor(e.target.value)}
                      className="w-10 h-10 p-1 border border-gray-200 rounded cursor-pointer bg-transparent"
                    />
                  </div>
                </div>

                <div className="space-y-8">
                  <label className={`flex items-center gap-4 cursor-pointer group ${!selectedCustomer?.phone ? 'opacity-50 pointer-events-none' : ''}`}>
                    <span className="relative flex items-center">
                      <input 
                        type="checkbox" 
                        checked={sendSmsConfirmation}
                        onChange={(e) => setSendSmsConfirmation(e.target.checked)}
                        disabled={!selectedCustomer?.phone}
                        className="peer w-5 h-5 text-indigo-900 rounded border-2 border-indigo-900 focus:ring-0 transition-all appearance-none checked:bg-indigo-900 disabled:border-gray-300 disabled:bg-gray-100" 
                      />
                      <Check className="w-3 h-3 text-white absolute left-1 top-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none" />
                    </span>
                    <span className="text-[15px] font-semibold text-gray-700 tracking-tight">Send Confirmation via SMS ({selectedCustomer?.phone || 'No phone'})</span>
                  </label>

                  <label className={`flex items-center gap-4 cursor-pointer group ${!selectedCustomer?.email ? 'opacity-50 pointer-events-none' : ''}`}>
                    <span className="relative flex items-center">
                      <input 
                        type="checkbox" 
                        checked={sendEmailConfirmation}
                        onChange={(e) => setSendEmailConfirmation(e.target.checked)}
                        disabled={!selectedCustomer?.email}
                        className="peer w-5 h-5 text-indigo-900 rounded border-2 border-indigo-900 focus:ring-0 transition-all appearance-none checked:bg-indigo-900 disabled:border-gray-300 disabled:bg-gray-100" 
                      />
                      <Check className="w-3 h-3 text-white absolute left-1 top-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none" />
                    </span>
                    <span className="text-[15px] font-semibold text-gray-700 tracking-tight">Send Confirmation via Email ({selectedCustomer?.email || 'No email'})</span>
                  </label>
                </div>
              </div>
            </Card>
        </div>

        {/* Right Sidebar */}
        <div className="w-full lg:w-[480px] space-y-6 shrink-0 lg:sticky lg:top-8">
          {/* Notes Card */}
          <Card className="border-none shadow-[0_4px_15px_rgba(0,0,0,0.06)] bg-white overflow-hidden">
            <div className="flex items-center justify-between p-5 border-b border-gray-100">
               <span className="text-[13px] font-bold text-gray-800 tracking-tight">Notes</span>
               <ChevronUp className="w-4 h-4 text-gray-400" />
            </div>
            <div className="p-6">
              <p className="text-[14px] text-gray-600 leading-relaxed font-medium">
                {selectedCustomer?.notes || 'Customer notes not found'}
              </p>
            </div>
          </Card>

          {/* Bookings Table Card */}
          <Card className="border-none shadow-[0_4px_15px_rgba(0,0,0,0.06)] bg-white overflow-hidden">
             <div className="flex items-center justify-between p-5 border-b border-gray-100">
               <span className="text-[13px] font-bold text-gray-800 tracking-tight">Booking Scheduled on Selected Date</span>
               <ChevronUp className="w-4 h-4 text-gray-400" />
            </div>
            <div className="p-6">
              {existingBookings && existingBookings.length > 0 ? (
                <div className="space-y-4">
                  {existingBookings.map((b: any) => (
                    <div key={b.id} className="text-[14px] leading-relaxed flex flex-col gap-1">
                      <div className="flex items-center gap-2 group">
                        <span className="text-emerald-500 font-bold whitespace-nowrap">{b.startTime}</span>
                        <span className="text-emerald-500 font-bold uppercase tracking-wide">KTM</span>
                        <span className="text-emerald-500 font-bold truncate">
                          {b.details?.map((d: any) => d.service?.name).filter(Boolean).join(', ')}
                        </span>
                        <span className="text-emerald-500 font-bold ml-1">[{b.customer?.id}]</span>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-[13px] text-gray-400 italic">No bookings found for the selected date.</p>
              )}
            </div>
          </Card>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-[1fr_480px] gap-8 mt-6">
         <div className="flex justify-end gap-4">
           <Button
             type="button"
             variant="secondary"
             onClick={() => navigate('/bookings')}
             className="px-8 py-3 rounded-lg font-bold text-[14px]"
           >
             Cancel
           </Button>
           <Button
             onClick={handleCreateBooking}
             isLoading={createBookingMutation.isPending || createRecurringBookingMutation.isPending || updateBookingMutation.isPending}
             disabled={!selectedCustomer || !bookingDate || !bookingStartTime || Object.values(petServices).every(services => !services || services.length === 0)}
             className={`px-10 py-3 rounded-lg font-bold text-[14px] transition-all ${
               (!selectedCustomer || !bookingDate || !bookingStartTime || Object.values(petServices).every(services => !services || services.length === 0))
                 ? 'bg-gray-200 text-gray-500 cursor-not-allowed shadow-none'
                 : 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-lg active:scale-95'
             }`}
           >
             {isEditMode ? 'Save Booking Changes' : 'Create New Booking'}
           </Button>
         </div>
         {/* Spacer to align with the sidebar in desktop view */}
         <div className="hidden lg:block"></div>
       </div>
     </div>
  )
}
