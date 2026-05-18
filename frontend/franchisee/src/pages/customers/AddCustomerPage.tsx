import { useState, useMemo, useEffect } from 'react'
import { PetAuditModal } from '../../components/modals/PetAuditModal'
import { PetWaiverModal } from '../../components/modals/PetWaiverModal'
import { useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Select } from '../../components/ui/Select'
import { Modal } from '../../components/ui/Modal'
import { PageHeader } from '../../components/layout/PageHeader'
import { ChevronDown, Loader2, X, Check, Plus, MoreVertical, Trash2, PenLine, History, UserPlus } from 'lucide-react'
import { customersApi, petsApi, bookingsApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import { differenceInMonths, differenceInDays } from 'date-fns'
import type { Customer, Booking, Pet } from '../../types'
import { formatDisplayDate, formatDisplayDateTime } from '../../lib/timeFormatUtils'

type AccordionSection = 'pets' | 'add-pet' | 'completed' | 'future'

export function AddCustomerPage() {
  const { id } = useParams<{ id: string }>()
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)

  const [expandedSection, setExpandedSection] = useState<AccordionSection | null>(id ? 'pets' : null)
  const [savedCustomer, setSavedCustomer] = useState<Customer | null>(null)
  const [editingPetId, setEditingPetId] = useState<string | null>(null)
  const [viewingPetImage, setViewingPetImage] = useState<string | null>(null)
  const [activePetMenuId, setActivePetMenuId] = useState<string | null>(null)
  const [petToDeleteId, setPetToDeleteId] = useState<string | null>(null)
  const [isWaiverModalOpen, setIsWaiverModalOpen] = useState(false)
  const [selectedPetForWaiver, setSelectedPetForWaiver] = useState<Pet | null>(null)
  const [isPetAuditModalOpen, setIsPetAuditModalOpen] = useState(false)
  const [selectedPetForAudit, setSelectedPetForAudit] = useState<Pet | null>(null)

  const stateOptions = [
    { label: 'Victoria', value: 'Victoria' },
    { label: 'New South Wales', value: 'New South Wales' },
    { label: 'Queensland', value: 'Queensland' },
    { label: 'Western Australia', value: 'Western Australia' },
    { label: 'South Australia', value: 'South Australia' },
    { label: 'Tasmania', value: 'Tasmania' },
  ]

  const genderOptions = [
    { label: 'Gender', value: '' },
    { label: 'Male', value: 'male' },
    { label: 'Female', value: 'female' },
  ]

  const sizeOptions = [
    { label: 'Toy', value: 'Toy' },
    { label: 'Small', value: 'Small' },
    { label: 'Medium', value: 'Medium' },
    { label: 'Large', value: 'Large' },
  ]

  const dayOptions = [
    { label: 'Day', value: '' },
    ...Array.from({ length: 31 }, (_, i) => ({ label: (i + 1).toString(), value: (i + 1).toString() }))
  ]

  const monthOptions = [
    { label: 'Month', value: '' },
    ...['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'].map((m, i) => ({ label: m, value: (i + 1).toString() }))
  ]

  const yearOptions = [
    { label: 'Year', value: '' },
    ...Array.from({ length: 30 }, (_, i) => ({ label: (new Date().getFullYear() - i).toString(), value: (new Date().getFullYear() - i).toString() }))
  ]

  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    street_address: '',
    suburb: '',
    postcode: '',
    state: 'Victoria',
    mobile: '',
    email: '',
    customerNotes: '',
    otherPhones: '',
    otherEmails: '',
    referred_by: '',
    is_ndis: false,
    is_subscribed: false,
    is_active: true,
  })

  // Fetch customer if editing
  const { data: customerData, isLoading: loadingCustomer } = useQuery({
    queryKey: ['customer', id],
    queryFn: () => customersApi.getById(id!),
    enabled: !!id,
  })

  useEffect(() => {
    if (customerData) {
      setSavedCustomer(customerData)

      // Parse address if possible
      const addressParts = (customerData.address || '').split(', ')
      const suburbPostState = addressParts[1] ? addressParts[1].split(' ') : []

      setFormData({
        first_name: customerData.first_name,
        last_name: customerData.last_name,
        street_address: customerData.street_address || addressParts[0] || '',
        suburb: customerData.suburb || suburbPostState[0] || '',
        postcode: customerData.postcode || suburbPostState[1] || '',
        state: customerData.state || suburbPostState.slice(2).join(' ') || 'Victoria',
        mobile: customerData.phone,
        email: customerData.email,
        otherPhones: customerData.other_phone || '',
        otherEmails: customerData.other_email || '',
        referred_by: customerData.referred_by || '',
        is_ndis: customerData.is_ndis || false,
        is_subscribed: customerData.is_subscribed || false,
        is_active: customerData.is_active ?? true,
        customerNotes: customerData.notes || '',
      })
    }
  }, [customerData])

  const [petFormData, setPetFormData] = useState({
    name: '',
    gender: '' as 'male' | 'female' | '',
    size: 'Small' as 'Toy' | 'Small' | 'Medium' | 'Large' | 'Extra Large',
    breed: '',
    notes: '',
    birthYear: '',
    birthMonth: '',
    birthDay: '',
    is_active: true,
    image: null as File | null,
    existingImage: null as string | null,
    removeImage: false,
  })

  // Queries for sidebar data
  const customerId = savedCustomer?.id
  const { data: pets, isLoading: loadingPets } = useQuery({
    queryKey: ['customer-pets', customerId],
    queryFn: () => petsApi.getByCustomer(customerId!),
    enabled: !!customerId,
  })

  const { data: bookings, isLoading: loadingBookings } = useQuery({
    queryKey: ['customer-bookings', customerId],
    queryFn: () => bookingsApi.getAll({ customer_id: customerId! }),
    enabled: !!customerId,
  })

  const futureBookings = useMemo(() => bookings?.filter(b => ['requested', 'confirmed'].includes(b.status)) || [], [bookings])
  const completedBookings = useMemo(() => bookings?.filter(b => b.status === 'completed') || [], [bookings])


  const saveMutation = useMutation({
    mutationFn: (data: any) => id ? customersApi.update(id, data) : customersApi.create(data),
    onSuccess: (newCustomer) => {
      queryClient.invalidateQueries({ queryKey: ['customers'] })
      addToast(id ? 'Customer updated successfully' : 'Customer created successfully', 'success')
      setSavedCustomer(newCustomer)
      if (!id) {
        setExpandedSection('add-pet')
      }
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Failed to save customer'
      addToast(message, 'error')
    }
  })

  const petMutation = useMutation({
    mutationFn: (data: any) => editingPetId ? petsApi.update(editingPetId, data) : petsApi.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customer-pets', customerId] })
      addToast(editingPetId ? 'Pet updated successfully' : 'Pet added successfully', 'success')
      setPetFormData({
        name: '',
        gender: '',
        size: 'Small',
        breed: '',
        notes: '',
        birthYear: '',
        birthMonth: '',
        birthDay: '',
        is_active: true,
        image: null,
        existingImage: null,
        removeImage: false,
      })
      setEditingPetId(null)
      setExpandedSection('pets')
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Failed to save pet'
      addToast(message, 'error')
    }
  })

  const deletePetMutation = useMutation({
    mutationFn: (petId: string) => petsApi.delete(petId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customer-pets', customerId] })
      addToast('Pet deleted successfully', 'success')
      setPetToDeleteId(null)
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Failed to delete pet'
      addToast(message, 'error')
    }
  })

  const handleSaveCustomer = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!formData.first_name) {
      addToast('First name is required', 'error')
      return
    }
    if (!formData.mobile && !formData.email) {
      addToast('Either Mobile or Email is compulsory', 'error')
      return
    }
    const payload = {
      first_name: formData.first_name,
      last_name: formData.last_name,
      email: formData.email,
      phone: formData.mobile,
      street_address: formData.street_address,
      suburb: formData.suburb,
      postcode: formData.postcode,
      state: formData.state,
      other_phone: formData.otherPhones,
      other_email: formData.otherEmails,
      referred_by: formData.referred_by,
      is_ndis: formData.is_ndis,
      is_subscribed: formData.is_subscribed,
      is_active: formData.is_active,
      franchise_id: '1',
      notes: formData.customerNotes,
    }
    saveMutation.mutate(payload)
  }

  const handleSavePet = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!savedCustomer) return

    if (!petFormData.name) {
      addToast('Pet name is required', 'error')
      return
    }

    const petData = new FormData()
    petData.append('name', petFormData.name)
    petData.append('gender', petFormData.gender)
    petData.append('size', petFormData.size)
    petData.append('breed', petFormData.breed)
    petData.append('notes', petFormData.notes)
    petData.append('customer_id', savedCustomer.id)
    petData.append('is_active', petFormData.is_active ? '1' : '0')

    const birthDate = petFormData.birthYear && petFormData.birthMonth && petFormData.birthDay
      ? `${petFormData.birthYear}-${petFormData.birthMonth.padStart(2, '0')}-${petFormData.birthDay.padStart(2, '0')}`
      : null
    if (birthDate) petData.append('birth_date', birthDate)

    if (petFormData.image) {
      petData.append('image', petFormData.image)
    } else if (petFormData.removeImage) {
      petData.append('remove_image', '1')
    }

    petMutation.mutate(petData)
  }

  const activeTime = useMemo(() => {
    if (!savedCustomer) return ''
    const start = new Date(savedCustomer.created_at)
    const now = new Date()
    const months = differenceInMonths(now, start)
    const days = differenceInDays(now, start) % 30
    return `${months}months, ${days}days`
  }, [savedCustomer])

  const AccordionHeader = ({ title, section, isLast = false }: { title: string, section: AccordionSection, isLast?: boolean }) => {
    const isExpanded = expandedSection === section
    return (
      <button
        disabled={!savedCustomer}
        onClick={() => setExpandedSection(isExpanded ? null : section)}
        className={`w-full p-3 flex justify-between items-center transition-colors ${!savedCustomer ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'} ${!isLast ? 'border-b' : ''} ${isExpanded ? 'bg-indigo-50 border-indigo-100' : 'bg-white'}`}
      >
        <span className={`text-sm font-bold ${isExpanded ? 'text-indigo-700' : 'text-gray-700'}`}>{title}</span>
        <ChevronDown className={`w-4 h-4 text-gray-400 transition-transform ${isExpanded ? 'rotate-180' : ''}`} />
      </button>
    )
  }

  if (id && loadingCustomer) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <Loader2 className="w-8 h-8 text-indigo-600 animate-spin" />
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={savedCustomer ? `Customer: ${savedCustomer.first_name} ${savedCustomer.last_name}` : 'Add Customers/Pet'}
        icon={<UserPlus className="w-5 h-5" />}
      />

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Left Side: Customer Form */}
        <div className="min-w-0">
          <Card className="p-6">
            <div className="flex justify-between items-center mb-6 border-b pb-4">
              <h2 className="text-xl font-bold text-gray-800">
                {id ? 'Edit Customers/Pet' : 'Add Customers/Pet'}
              </h2>
            </div>

            <form onSubmit={handleSaveCustomer} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <label className="block text-xs uppercase font-bold text-gray-400 mb-1">First Name *</label>
                  <Input
                    required
                    value={formData.first_name}
                    onChange={e => setFormData({ ...formData, first_name: e.target.value })}
                    className="border-0 border-b border-gray-300 rounded-none pr-8 pl-0 focus:ring-0 focus:border-indigo-600 bg-transparent w-full text-sm font-medium"
                  />
                </div>
                <div>
                  <label className="block text-xs uppercase font-bold text-gray-400 mb-1">Last Name</label>
                  <Input
                    value={formData.last_name}
                    onChange={e => setFormData({ ...formData, last_name: e.target.value })}
                    className="border-0 border-b border-gray-300 rounded-none pr-8 pl-0 focus:ring-0 focus:border-indigo-600 bg-transparent w-full text-sm font-medium"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <label className="block text-xs uppercase font-bold text-gray-400 mb-1">Street Address *</label>
                  <Input
                    value={formData.street_address}
                    onChange={e => setFormData({ ...formData, street_address: e.target.value })}
                    className="border-0 border-b border-gray-300 rounded-none pr-8 pl-0 focus:ring-0 focus:border-indigo-600 bg-transparent w-full text-sm font-medium"
                  />
                </div>
                <div>
                  <label className="block text-xs uppercase font-bold text-gray-400 mb-1">Suburb *</label>
                  <Input
                    value={formData.suburb}
                    onChange={e => setFormData({ ...formData, suburb: e.target.value })}
                    className="border-0 border-b border-gray-300 rounded-none pr-8 pl-0 focus:ring-0 focus:border-indigo-600 bg-transparent w-full text-sm font-medium"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <label className="block text-xs uppercase font-bold text-gray-400 mb-1">Post Code</label>
                  <Input
                    value={formData.postcode}
                    onChange={e => setFormData({ ...formData, postcode: e.target.value })}
                    className="border-0 border-b border-gray-300 rounded-none pr-8 pl-0 focus:ring-0 focus:border-indigo-600 bg-transparent w-full text-sm font-medium"
                  />
                </div>
                <div>
                  <label className="block text-xs uppercase font-bold text-gray-400 mb-1">Select State *</label>
                  <Select
                    value={formData.state}
                    options={stateOptions}
                    onChange={val => setFormData({ ...formData, state: val.toString() })}
                    className="border-0 border-b border-gray-300 rounded-none pr-8 pl-0 focus:ring-0 focus:border-indigo-600 bg-transparent w-full text-sm font-medium"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <label className="block text-xs uppercase font-bold text-gray-400 mb-1">Mobile **</label>
                  <Input
                    value={formData.mobile}
                    onChange={e => setFormData({ ...formData, mobile: e.target.value })}
                    className="border-0 border-b border-gray-300 rounded-none pr-8 pl-0 focus:ring-0 focus:border-indigo-600 bg-transparent w-full text-sm font-medium"
                  />
                </div>
                <div>
                  <label className="block text-xs uppercase font-bold text-gray-400 mb-1">Email Address **</label>
                  <Input
                    type="email"
                    value={formData.email}
                    onChange={e => setFormData({ ...formData, email: e.target.value })}
                    className="border-0 border-b border-gray-300 rounded-none pr-8 pl-0 focus:ring-0 focus:border-indigo-600 bg-transparent w-full text-sm font-medium"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <label className="block text-xs uppercase font-bold text-gray-400 mb-1">Other Phone</label>
                  <Input
                    value={formData.otherPhones}
                    onChange={e => setFormData({ ...formData, otherPhones: e.target.value })}
                    className="border-0 border-b border-gray-300 rounded-none pr-8 pl-0 focus:ring-0 focus:border-indigo-600 bg-transparent w-full text-sm font-medium"
                  />
                </div>
                <div>
                  <label className="block text-xs uppercase font-bold text-gray-400 mb-1">Other Email</label>
                  <Input
                    type="email"
                    value={formData.otherEmails}
                    onChange={e => setFormData({ ...formData, otherEmails: e.target.value })}
                    className="border-0 border-b border-gray-300 rounded-none pr-8 pl-0 focus:ring-0 focus:border-indigo-600 bg-transparent w-full text-sm font-medium"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs uppercase font-bold text-gray-400 mb-1">Referred By</label>
                <Input
                  value={formData.referred_by}
                  onChange={e => setFormData({ ...formData, referred_by: e.target.value })}
                  className="border-0 border-b border-gray-300 rounded-none pr-8 pl-0 focus:ring-0 focus:border-indigo-600 bg-transparent w-full text-sm font-medium"
                />
              </div>

              <div>
                <label className="block text-xs uppercase font-bold text-gray-400 mb-1">Customer Notes</label>
                <textarea
                  value={formData.customerNotes}
                  onChange={e => setFormData({ ...formData, customerNotes: e.target.value })}
                  className="border-0 border-b border-gray-300 rounded-none px-0 py-2 focus:ring-0 focus:border-blue-600 bg-transparent w-full resize-none h-12 text-sm font-medium"
                />
              </div>

              <div className="flex flex-wrap gap-x-8 gap-y-4">
                <label className="flex items-center gap-3 cursor-pointer group">
                  <div className={`w-5 h-5 rounded border flex items-center justify-center transition-colors ${formData.is_ndis ? 'bg-indigo-600 border-indigo-600' : 'bg-white border-gray-300 group-hover:border-indigo-400'}`}>
                    {formData.is_ndis && <Check className="w-3.5 h-3.5 text-white stroke-[3px]" />}
                  </div>
                  <input
                    type="checkbox"
                    className="hidden"
                    checked={formData.is_ndis}
                    onChange={e => setFormData({ ...formData, is_ndis: e.target.checked })}
                  />
                  <span className="text-sm font-bold text-gray-700">NDIS customer</span>
                </label>

                <label className="flex items-center gap-3 cursor-pointer group">
                  <div className={`w-5 h-5 rounded border flex items-center justify-center transition-colors ${formData.is_subscribed ? 'bg-indigo-600 border-indigo-600' : 'bg-white border-gray-300 group-hover:border-indigo-400'}`}>
                    {formData.is_subscribed && <Check className="w-3.5 h-3.5 text-white stroke-[3px]" />}
                  </div>
                  <input
                    type="checkbox"
                    className="hidden"
                    checked={formData.is_subscribed}
                    onChange={e => setFormData({ ...formData, is_subscribed: e.target.checked })}
                  />
                  <span className="text-sm font-bold text-gray-700">Subscribed to newsletter</span>
                </label>

                <label className="flex items-center gap-3 cursor-pointer group">
                  <div className={`w-5 h-5 rounded border flex items-center justify-center transition-colors ${formData.is_active ? 'bg-indigo-600 border-indigo-600' : 'bg-white border-gray-300 group-hover:border-indigo-400'}`}>
                    {formData.is_active && <Check className="w-3.5 h-3.5 text-white stroke-[3px]" />}
                  </div>
                  <input
                    type="checkbox"
                    className="hidden"
                    checked={formData.is_active}
                    onChange={e => setFormData({ ...formData, is_active: e.target.checked })}
                  />
                  <span className="text-sm font-bold text-gray-700">Customer active</span>
                </label>
              </div>

              <div className="pt-6 flex flex-col md:flex-row md:items-end justify-between gap-6">
                <Button
                  type="submit"
                  disabled={saveMutation.isPending}
                  className="bg-indigo-700 hover:bg-indigo-800 text-white font-bold px-10 py-2 rounded shadow-md"
                >
                  {saveMutation.isPending ? (
                    <Loader2 className="w-5 h-5 animate-spin" />
                  ) : id ? 'Update Customer' : 'Save New Customer'}
                </Button>
              </div>

              {savedCustomer && (
                <div className="pt-6 border-t border-gray-100 italic text-xs text-gray-400">
                  Created on {formatDisplayDateTime(savedCustomer.created_at)}
                </div>
              )}
            </form>
          </Card>
        </div>

        {/* Right Side: Accordion Sidebar */}
        <div className="space-y-4 min-w-0">
          <div className="border rounded shadow-sm bg-white">
            {/* 1. Current Pets */}
            <AccordionHeader title="Current Pets" section="pets" />
            {expandedSection === 'pets' && (
              <div className="p-4 bg-white border-b">
                {loadingPets ? (
                  <div className="flex justify-center p-8"><Loader2 className="w-6 h-6 animate-spin text-indigo-600" /></div>
                ) : (
                  <div className="overflow-visible">
                    <table className="w-full text-left text-sm">
                      <thead className="text-gray-700 border-b">
                        <tr>
                          <th className="pb-3 px-1 font-bold text-left w-16">Image</th>
                          <th className="pb-3 px-1 pl-4 font-bold text-left">Name</th>
                          <th className="pb-3 px-1 font-bold text-left">Breed</th>
                          <th className="pb-3 px-1 font-bold text-left">Size</th>
                          <th className="pb-3 px-1 font-bold text-left">Active?</th>
                          <th className="pb-3 px-1 font-bold text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-gray-100">
                        {pets?.map((pet: Pet) => (
                          <tr key={pet.id} className="hover:bg-gray-50">
                            <td className="py-2 px-1">
                              <div
                                className={`w-10 h-10 rounded border border-gray-100 flex items-center justify-center overflow-hidden bg-gray-50 ${pet.image ? 'cursor-pointer hover:border-indigo-400 transition-colors' : ''}`}
                                onClick={() => pet.image && setViewingPetImage(pet.image)}
                              >
                                {pet.image ? (
                                  <img src={pet.image} alt={pet.name} className="w-full h-full object-cover" />
                                ) : (
                                  <div className="flex flex-col items-center justify-center text-[8px] text-gray-400 font-bold uppercase leading-tight text-center px-1">
                                    <span>No</span>
                                    <span>Image</span>
                                  </div>
                                )}
                              </div>
                            </td>
                            <td className="py-2 px-1 pl-4 text-left text-gray-600 font-bold">{pet.name}</td>
                            <td className="py-2 px-1 text-left text-gray-600">{pet.breed || '-'}</td>
                            <td className="py-2 px-1 text-left text-gray-600">{pet.size}</td>
                            <td className="py-3 px-1 text-left">
                              {pet.isActive ? <Check className="w-4 h-4 text-green-500" /> : <X className="w-4 h-4 text-red-500" />}
                            </td>
                            <td className="py-2 px-1 text-right relative">
                              <button
                                type="button"
                                onClick={() => setActivePetMenuId(activePetMenuId === pet.id ? null : pet.id)}
                                className="p-1 hover:bg-gray-100 rounded-full transition-colors text-gray-400 hover:text-gray-600"
                              >
                                <MoreVertical className="w-5 h-5" />
                              </button>

                              {activePetMenuId === pet.id && (
                                <>
                                  <div className="fixed inset-0 z-10" onClick={() => setActivePetMenuId(null)}></div>
                                  <div className="absolute right-0 mt-1 w-48 bg-white rounded-md shadow-xl border border-gray-100 py-1 z-30">
                                    <button
                                      onClick={() => {
                                        setEditingPetId(pet.id)
                                        setPetFormData({
                                          name: pet.name,
                                          gender: pet.gender || '',
                                          size: pet.size,
                                          breed: pet.breed || '',
                                          notes: pet.notes || '',
                                          birthYear: pet.birthDate ? pet.birthDate.split('-')[0] : '',
                                          birthMonth: pet.birthDate ? parseInt(pet.birthDate.split('-')[1]).toString() : '',
                                          birthDay: pet.birthDate ? parseInt(pet.birthDate.split('-')[2]).toString() : '',
                                          is_active: pet.isActive,
                                          image: null,
                                          existingImage: pet.image || null,
                                          removeImage: false,
                                        })
                                        setExpandedSection('add-pet')
                                        setActivePetMenuId(null)
                                      }}
                                      className="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700"
                                    >
                                      <PenLine className="w-4 h-4 mr-3" />
                                      Edit
                                    </button>
                                    <button
                                      onClick={() => {
                                        setPetToDeleteId(pet.id)
                                        setActivePetMenuId(null)
                                      }}
                                      className="flex items-center w-full px-4 py-3 text-sm text-red-600 hover:bg-red-50"
                                    >
                                      <Trash2 className="w-4 h-4 mr-3" />
                                      Delete
                                    </button>
                                    <button
                                      onClick={() => {
                                        setSelectedPetForWaiver(pet)
                                        setIsWaiverModalOpen(true)
                                        setActivePetMenuId(null)
                                      }}
                                      className="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 border-t border-gray-100"
                                    >
                                      <Check className="w-4 h-4 mr-3" />
                                      Waivers
                                    </button>
                                    <button
                                      onClick={() => {
                                        setSelectedPetForAudit(pet)
                                        setIsPetAuditModalOpen(true)
                                        setActivePetMenuId(null)
                                      }}
                                      className="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-700"
                                    >
                                      <History className="w-4 h-4 mr-3" />
                                      Audit trail
                                    </button>
                                  </div>
                                </>
                              )}
                            </td>
                          </tr>
                        ))}
                        {(!pets || pets.length === 0) && (
                          <tr>
                            <td colSpan={6} className="py-10 text-center text-gray-400 italic">No Pets Found</td>
                          </tr>
                        )}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            )}

            {/* 2. Add/Edit Pet */}
            <AccordionHeader title={editingPetId ? 'Edit Pet' : 'Add Pet'} section="add-pet" />
            {expandedSection === 'add-pet' && (
              <div className="p-6 bg-white border-b">
                <form onSubmit={handleSavePet} className="space-y-6">
                  <div className="grid grid-cols-2 gap-6">
                    <div>
                      <label className="block text-xs uppercase font-bold text-gray-400 mb-1">Pet Name *</label>
                      <Input
                        required
                        value={petFormData.name}
                        onChange={e => setPetFormData({ ...petFormData, name: e.target.value })}
                        className="border-0 border-b border-gray-300 rounded-none pr-8 pl-0 focus:ring-0 focus:border-indigo-600 bg-transparent w-full text-sm font-medium"
                      />
                    </div>
                    <div>
                      <label className="block text-xs uppercase font-bold text-gray-400 mb-1">Gender</label>
                      <Select
                        value={petFormData.gender}
                        options={genderOptions}
                        onChange={val => setPetFormData({ ...petFormData, gender: val as any })}
                        className="border-0 border-b border-gray-300 rounded-none pr-8 pl-0 focus:ring-0 focus:border-indigo-600 bg-transparent w-full text-sm font-medium"
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-6">
                    <div>
                      <label className="block text-xs uppercase font-bold text-gray-400 mb-1">Pet Size</label>
                      <Select
                        value={petFormData.size}
                        options={sizeOptions}
                        onChange={val => setPetFormData({ ...petFormData, size: val as any })}
                        className="border-0 border-b border-gray-300 rounded-none pr-8 pl-0 focus:ring-0 focus:border-indigo-600 bg-transparent w-full text-sm font-medium"
                      />
                    </div>
                    <div>
                      <label className="block text-xs uppercase font-bold text-gray-400 mb-1">Pet Breed</label>
                      <Input
                        value={petFormData.breed}
                        onChange={e => setPetFormData({ ...petFormData, breed: e.target.value })}
                        className="border-0 border-b border-gray-300 rounded-none pr-8 pl-0 focus:ring-0 focus:border-indigo-600 bg-transparent w-full text-sm font-medium"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-xs uppercase font-bold text-gray-400 mb-1">Pet Image</label>
                    <div className="flex items-center gap-4">
                      {petFormData.image ? (
                        <div className="relative w-16 h-16 rounded overflow-hidden border">
                          <img
                            src={URL.createObjectURL(petFormData.image)}
                            alt="Preview"
                            className="w-full h-full object-cover"
                          />
                          <button
                            type="button"
                            onClick={() => setPetFormData({ ...petFormData, image: null })}
                            className="absolute top-0 right-0 bg-red-500 text-white p-0.5"
                          >
                            <X className="w-3 h-3" />
                          </button>
                        </div>
                      ) : (petFormData.existingImage && !petFormData.removeImage) ? (
                        <div className="relative w-16 h-16 rounded overflow-hidden border">
                          <img
                            src={petFormData.existingImage}
                            alt="Existing"
                            className="w-full h-full object-cover"
                          />
                          <button
                            type="button"
                            onClick={() => setPetFormData({ ...petFormData, removeImage: true })}
                            className="absolute top-0 right-0 bg-red-500 text-white p-0.5"
                          >
                            <X className="w-3 h-3" />
                          </button>
                        </div>
                      ) : (
                        <label className="w-16 h-16 flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded cursor-pointer hover:border-indigo-400 text-gray-400 transition-colors">
                          <Plus className="w-6 h-6" />
                          <span className="text-[10px]">Photo</span>
                          <input
                            type="file"
                            accept="image/*"
                            className="hidden"
                            onChange={e => {
                              const file = e.target.files?.[0]
                              if (file) setPetFormData({ ...petFormData, image: file, removeImage: false })
                            }}
                          />
                        </label>
                      )}
                      <span className="text-xs text-gray-500">
                        {petFormData.image ? petFormData.image.name :
                          (petFormData.existingImage && !petFormData.removeImage) ? 'Existing image' :
                            'No image selected'}
                      </span>
                    </div>
                  </div>

                  <div>
                    <label className="block text-xs uppercase font-bold text-gray-400 mb-1">Pet Notes</label>
                    <textarea
                      value={petFormData.notes}
                      onChange={e => setPetFormData({ ...petFormData, notes: e.target.value })}
                      className="border-0 border-b border-gray-300 rounded-none px-0 py-2 focus:ring-0 focus:border-blue-600 bg-transparent w-full resize-none h-16 text-sm font-medium"
                    />
                  </div>

                  <div>
                    <label className="block text-xs uppercase font-bold text-gray-400 mb-1">Date of Birth</label>
                    <div className="flex gap-4">
                      <div className="w-[120px]">
                        <Select
                          value={petFormData.birthYear}
                          options={yearOptions}
                          onChange={val => setPetFormData({ ...petFormData, birthYear: val.toString() })}
                          className="border-0 border-b border-gray-300 rounded-none pr-8 pl-0 focus:ring-0 focus:border-indigo-600 bg-transparent w-full text-sm font-medium"
                        />
                      </div>
                      <div className="w-[120px]">
                        <Select
                          value={petFormData.birthMonth}
                          options={monthOptions}
                          onChange={val => setPetFormData({ ...petFormData, birthMonth: val.toString() })}
                          className="border-0 border-b border-gray-300 rounded-none pr-8 pl-0 focus:ring-0 focus:border-indigo-600 bg-transparent w-full text-sm font-medium"
                        />
                      </div>
                      <div className="w-[120px]">
                        <Select
                          value={petFormData.birthDay}
                          options={dayOptions}
                          onChange={val => setPetFormData({ ...petFormData, birthDay: val.toString() })}
                          className="border-0 border-b border-gray-300 rounded-none pr-8 pl-0 focus:ring-0 focus:border-indigo-600 bg-transparent w-full text-sm font-medium"
                        />
                      </div>
                    </div>
                  </div>

                  <div className="flex items-center justify-between">
                    <label className="flex items-center gap-3 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={petFormData.is_active}
                        onChange={e => setPetFormData({ ...petFormData, is_active: e.target.checked })}
                        className="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                      />
                      <span className="text-sm font-bold text-gray-700">Pet is Active</span>
                    </label>

                    <Button
                      type="submit"
                      disabled={petMutation.isPending}
                      className="bg-indigo-700 hover:bg-indigo-800 text-white font-bold px-6 py-2 rounded shadow-md"
                    >
                      {petMutation.isPending ? <Loader2 className="w-5 h-5 animate-spin" /> : editingPetId ? 'Update Pet' : 'Save New Pet'}
                    </Button>
                  </div>
                </form>
              </div>
            )}

            {/* 3. Completed Bookings */}
            <AccordionHeader title="Completed Bookings" section="completed" />
            {expandedSection === 'completed' && (
              <div className="p-4 bg-white border-b">
                {loadingBookings ? (
                  <div className="flex justify-center p-8"><Loader2 className="w-6 h-6 animate-spin text-indigo-600" /></div>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                      <thead className="text-gray-700 border-b">
                        <tr>
                          <th className="pb-3 px-1 font-bold text-center"># of Services</th>
                          <th className="pb-3 px-1 font-bold text-center">Booked Date</th>
                          <th className="pb-3 px-1 font-bold text-center">Booked Time</th>
                          <th className="pb-3 px-1 font-bold text-center">Total $</th>
                          <th className="pb-3 px-1 font-bold text-right"></th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-gray-100">
                        {completedBookings.map((b: Booking) => (
                          <tr key={b.id}>
                            <td className="py-3 px-1 text-center text-gray-600">{(b as any).details?.length || 0}</td>
                            <td className="py-3 px-1 text-center text-gray-600">{b.date ? formatDisplayDate(b.date) : 'N/A'}</td>
                            <td className="py-3 px-1 text-center text-gray-600">{b.startTime}</td>
                            <td className="py-3 px-1 text-center text-gray-600">${b.total}</td>
                            <td className="py-3 px-1 text-right">
                              <button className="text-[10px] uppercase font-bold text-indigo-600 hover:underline">View</button>
                            </td>
                          </tr>
                        ))}
                        {completedBookings.length === 0 && (
                          <tr>
                            <td colSpan={5} className="py-10 text-center text-gray-400 italic">No Completed Bookings Found</td>
                          </tr>
                        )}
                      </tbody>
                    </table>
                    {completedBookings.length > 0 && (
                      <div className="flex justify-between items-center mt-4 text-[10px] text-gray-500 border-t pt-4">
                        <span>Rows per page: 10</span>
                        <span>1-{completedBookings.length} of {completedBookings.length}</span>
                        <div className="flex gap-2">
                          <button disabled className="p-1 text-gray-300"><ChevronDown className="w-4 h-4 rotate-90" /></button>
                          <button disabled className="p-1 text-gray-300"><ChevronDown className="w-4 h-4 -rotate-90" /></button>
                        </div>
                      </div>
                    )}
                  </div>
                )}
              </div>
            )}

            {/* 4. Future Bookings */}
            <AccordionHeader title="Future Bookings" section="future" isLast />
            {expandedSection === 'future' && (
              <div className="p-4 bg-white">
                {loadingBookings ? (
                  <div className="flex justify-center p-8"><Loader2 className="w-6 h-6 animate-spin text-indigo-600" /></div>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                      <thead className="text-gray-700 border-b">
                        <tr>
                          <th className="pb-3 px-1 font-bold text-center"># of Services</th>
                          <th className="pb-3 px-1 font-bold text-center">Booked Date</th>
                          <th className="pb-3 px-1 font-bold text-center">Booked Time</th>
                          <th className="pb-3 px-1 font-bold text-center">Total $</th>
                          <th className="pb-3 px-1 font-bold text-right"></th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-gray-100">
                        {futureBookings.map((b: Booking) => (
                          <tr key={b.id}>
                            <td className="py-3 px-1 text-center text-gray-600">{(b as any).details?.length || 0}</td>
                            <td className="py-3 px-1 text-center text-gray-600">{b.date ? formatDisplayDate(b.date) : 'N/A'}</td>
                            <td className="py-3 px-1 text-center text-gray-600">{b.startTime}</td>
                            <td className="py-3 px-1 text-center text-gray-600">${b.total}</td>
                            <td className="py-3 px-1 text-right">
                              <button className="text-[10px] uppercase font-bold text-indigo-600 hover:underline">View</button>
                            </td>
                          </tr>
                        ))}
                        {futureBookings.length === 0 && (
                          <tr>
                            <td colSpan={5} className="py-10 text-center text-gray-400 italic">No Scheduled Bookings Found</td>
                          </tr>
                        )}
                      </tbody>
                    </table>
                    {futureBookings.length > 0 && (
                      <div className="flex justify-between items-center mt-4 text-[10px] text-gray-500 border-t pt-4">
                        <span>Rows per page: 10</span>
                        <span>1-{futureBookings.length} of {futureBookings.length}</span>
                        <div className="flex gap-2">
                          <button disabled className="p-1 text-gray-300"><ChevronDown className="w-4 h-4 rotate-90" /></button>
                          <button disabled className="p-1 text-gray-300"><ChevronDown className="w-4 h-4 -rotate-90" /></button>
                        </div>
                      </div>
                    )}
                  </div>
                )}
              </div>
            )}
          </div>

          {savedCustomer && (
            <div className="flex justify-between items-center text-xs text-gray-500 font-medium px-1 mt-6">
              <span>Registered Date: {formatDisplayDateTime(savedCustomer.created_at)}</span>
              <span className="font-bold">Active For: {activeTime}</span>
            </div>
          )}
        </div>
      </div>

      <Modal
        isOpen={!!viewingPetImage}
        onClose={() => setViewingPetImage(null)}
        title="Pet Image"
      >
        <div className="flex justify-center p-2">
          {viewingPetImage && (
            <img
              src={viewingPetImage}
              alt="Pet"
              className="max-w-full max-h-[70vh] rounded shadow-sm object-contain"
            />
          )}
        </div>
        <div className="mt-6 flex justify-end">
          <Button onClick={() => setViewingPetImage(null)}>Close</Button>
        </div>
      </Modal>

      <Modal
        isOpen={!!petToDeleteId}
        onClose={() => setPetToDeleteId(null)}
        title="Delete Pet"
      >
        <div className="p-1">
          <p className="text-gray-600 mb-6">Are you sure you want to delete this pet? This action cannot be undone.</p>
          <div className="flex justify-end gap-3">
            <Button variant="secondary" onClick={() => setPetToDeleteId(null)}>Cancel</Button>
            <Button
              className="bg-red-600 hover:bg-red-700 text-white font-bold"
              onClick={() => petToDeleteId && deletePetMutation.mutate(petToDeleteId)}
              disabled={deletePetMutation.isPending}
            >
              {deletePetMutation.isPending ? <Loader2 className="w-4 h-4 animate-spin" /> : 'Delete Pet'}
            </Button>
          </div>
        </div>
      </Modal>

      <PetWaiverModal
        isOpen={isWaiverModalOpen}
        onClose={() => setIsWaiverModalOpen(false)}
        pet={selectedPetForWaiver}
        customer={savedCustomer}
      />

      <PetAuditModal
        isOpen={isPetAuditModalOpen}
        onClose={() => setIsPetAuditModalOpen(false)}
        pet={selectedPetForAudit}
      />
    </div>
  )
}
