import React, { useState, useRef } from 'react'
import SignatureCanvas from 'react-signature-canvas'
import { Modal } from '../ui/Modal'
import { Button } from '../ui/Button'
import { Input } from '../ui/Input'
import { Check, X, Calendar } from 'lucide-react'
import { useToastStore } from '../../store/toastStore'
import { petsApi } from '../../api/services'

interface CustomerIntakeFormProps {
  isOpen: boolean
  onClose: () => void
  customerId: string
  petId: string
  petName: string
  ownerName: string
  phone: string
  email: string
  breed: string
  waiverType?: string
  customerAddress?: string
  franchiseeName?: string
  initialData?: any
}

export function CustomerIntakeForm({ 
  isOpen, 
  onClose, 
  customerId,
  petId,
  petName, 
  ownerName, 
  phone, 
  email,
  breed,
  waiverType = 'intake',
  customerAddress = '',
  franchiseeName = '',
  initialData
}: CustomerIntakeFormProps) {
  const addToast = useToastStore((state) => state.addToast)
  const sigCanvas = useRef<SignatureCanvas>(null)
  const formRef = useRef<HTMLDivElement>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  const [formData, setFormData] = useState({
    age: '',
    weight: '',
    isVaccinated: false,
    healthConditions: {
      arthritis: false,
      epilepsy: false,
      collapsingTrachea: false,
      heartDisease: false,
      diabetes: false,
      chronicSkinIssues: false,
      chronicEarIssues: false,
      allergies: false,
    },
    otherHealth: '',
    skinSensitivities: {
      sensitiveSkin: 'no',
      productAllergies: 'no',
      veterinaryAdvice: 'no',
    },
    behavioural: {
      fearful: false,
      aggressive: false,
      anxious: false,
      noneKnown: false,
    },
    behaviouralOther: '',
    dislikeTouched: {
      head: false,
      paws: false,
      tail: false,
      other: false,
    },
    groomingExperience: {
      professionallyGroomed: 'no',
      previousIssues: 'no',
    },
    tickPrevention: 'no',
    acceptedExpectations: false,
    cologneReason: '',
    shampooReason: '',
    deliveryDate: '',
  })

  React.useEffect(() => {
    if (initialData) {
      setFormData({
        age: initialData.age || '',
        weight: initialData.weight || '',
        isVaccinated: !!initialData.is_vaccinated,
        healthConditions: {
          arthritis: !!initialData.health_arthritis,
          epilepsy: !!initialData.health_epilepsy,
          collapsingTrachea: !!initialData.health_collapsing_trachea,
          heartDisease: !!initialData.health_heart_disease,
          diabetes: !!initialData.health_diabetes,
          chronicSkinIssues: !!initialData.health_chronic_skin,
          chronicEarIssues: !!initialData.health_chronic_ear,
          allergies: !!initialData.health_allergies,
        },
        otherHealth: initialData.other_health || '',
        skinSensitivities: {
          sensitiveSkin: initialData.sensitivity_skin || 'no',
          productAllergies: initialData.sensitivity_products || 'no',
          veterinaryAdvice: initialData.sensitivity_vet_advice || 'no',
        },
        behavioural: {
          fearful: !!initialData.behavioural_fearful,
          aggressive: !!initialData.behavioural_aggressive,
          anxious: !!initialData.behavioural_anxious,
          noneKnown: !!initialData.behavioural_none_known,
        },
        behaviouralOther: initialData.behavioural_others || '',
        dislikeTouched: {
          head: !!initialData.dislike_head,
          paws: !!initialData.dislike_paws,
          tail: !!initialData.dislike_tail,
          other: !!initialData.dislike_other,
        },
        groomingExperience: {
          professionallyGroomed: initialData.grooming_prof_groomed || 'no',
          previousIssues: initialData.grooming_prev_issues || 'no',
        },
        tickPrevention: initialData.tick_prevention || 'no',
        acceptedExpectations: !!initialData.accepted_expectations,
        cologneReason: initialData.cologne_decline_reason || '',
        shampooReason: initialData.shampoo_own_reason || '',
        deliveryDate: initialData.delivery_date || '',
      })
    }
  }, [initialData])

  const handleCheckboxChange = (section: keyof typeof formData, field: string) => {
    setFormData(prev => ({
      ...prev,
      [section]: {
        ...(prev[section] as any),
        [field]: !(prev[section] as any)[field]
      }
    }))
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (waiverType === 'intake' && !formData.acceptedExpectations) {
      addToast('Please accept the expectations acknowledgment', 'error')
      return
    }
    if (waiverType === 'cologne' && !formData.cologneReason) {
      addToast('Please provide a reason for declining the cologne', 'error')
      return
    }
    if (waiverType === 'shampoo' && !formData.shampooReason) {
      addToast('Please provide a reason for using your own shampoo', 'error')
      return
    }
    if (waiverType === 'pregnant' && !formData.deliveryDate) {
      addToast('Please provide the expected delivery date', 'error')
      return
    }
    if (waiverType === 'senior' && !formData.age) {
      addToast('Please provide the dog\'s age for the senior waiver', 'error')
      return
    }
    if (sigCanvas.current?.isEmpty()) {
      addToast('Please provide a signature', 'error')
      return
    }

    setIsSubmitting(true)
    try {
      const payload = {
        customer_id: customerId,
        pet_id: petId,
        waiver_type: waiverType,
        ownerName,
        petName,
        phone,
        email,
        breed,
        form_data: {
          ...formData,
          customerAddress,
          franchiseeName
        },
        signature: sigCanvas.current?.toDataURL(),
      }

      addToast('Submitting intake form...', 'success')
      
      const response: any = await petsApi.saveIntakeForm(payload)
      
      if (response.data.pdf_url) {
        // Automatically download the PDF
        const link = document.createElement('a')
        link.href = response.data.pdf_url
        link.download = `${petName}-${waiverType}-waiver.pdf`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
      }

      addToast('Waiver saved & PDF generated!', 'success')
      onClose()
    } catch (error) {
      console.error('Submission failed:', error)
      addToast('Failed to save intake form', 'error')
    } finally {
      setIsSubmitting(false)
    }
  }

  const clearSignature = () => {
    sigCanvas.current?.clear()
  }

  const RadioGroup = ({ label, name, value, onChange }: { label: string, name: string, value: string, onChange: (val: string) => void }) => (
    <div className="space-y-2">
      <p className="text-sm font-medium text-gray-700">{label}</p>
      <div className="flex gap-4">
        <label className="flex items-center gap-2 cursor-pointer">
          <input type="radio" name={name} value="yes" checked={value === 'yes'} onChange={() => onChange('yes')} className="w-4 h-4 text-indigo-600 focus:ring-indigo-500" />
          <span className="text-sm text-gray-600">Yes</span>
        </label>
        <label className="flex items-center gap-2 cursor-pointer">
          <input type="radio" name={name} value="no" checked={value === 'no'} onChange={() => onChange('no')} className="w-4 h-4 text-indigo-600 focus:ring-indigo-500" />
          <span className="text-sm text-gray-600">No</span>
        </label>
      </div>
    </div>
  )

  const CheckboxField = ({ label, checked, onChange }: { label: string, checked: boolean, onChange: () => void }) => (
    <label className="flex items-center gap-3 cursor-pointer py-2">
      <div className={`w-5 h-5 border rounded flex items-center justify-center transition-colors ${checked ? 'bg-indigo-600 border-indigo-600' : 'bg-white border-gray-300'}`}>
        {checked && <Check className="w-3.5 h-3.5 text-white" />}
      </div>
      <input type="checkbox" className="hidden" checked={checked} onChange={onChange} />
      <span className="text-sm text-gray-600">{label}</span>
    </label>
  )

  return (
    <Modal isOpen={isOpen} onClose={onClose} title={
      waiverType === 'cologne' ? 'Cologne Waiver' : 
      waiverType === 'shampoo' ? 'Shampoo Waiver' : 
      waiverType === 'matted' ? 'Matted Dog Waiver' : 
      waiverType === 'clipping' ? 'Double Coated Dog Waiver' : 
      waiverType === 'pregnant' ? 'Pregnant Dog Waiver' : 
      waiverType === 'senior' ? 'Senior Dog Waiver' : 
      'Customer Intake Form'} size="lg">
      <div className="bg-gray-50 -mx-6 -mt-4 p-6 overflow-y-auto max-h-[80vh]">
        <div ref={formRef} className="bg-white p-10 shadow-sm rounded-lg max-w-2xl mx-auto space-y-10">
          <div className="text-center border-b pb-8">
            <h1 className="text-3xl font-bold text-indigo-900 mb-2">
              {waiverType === 'cologne' ? 'Cologne Waiver' : 
               waiverType === 'shampoo' ? 'Shampoo Waiver' : 
               waiverType === 'matted' ? 'Matted Dog Waiver' : 
               waiverType === 'clipping' ? 'Double Coated Dog Waiver' : 
               waiverType === 'pregnant' ? 'Pregnant Dog Waiver' : 
               waiverType === 'senior' ? 'Senior Dog Waiver' : 
               'New Customer Intake Form'}
            </h1>
            <p className="text-gray-500">
              {waiverType === 'cologne' ? 'Please complete this waiver regarding cologne use.' : 
               waiverType === 'shampoo' ? 'Please complete this waiver regarding using your own shampoo.' : 
               waiverType === 'matted' ? 'Please complete this waiver regarding your severely matted pet.' : 
               waiverType === 'clipping' ? 'Please complete this waiver regarding peeling/shaving a double coated breed.' : 
               waiverType === 'pregnant' ? 'Please complete this waiver regarding grooming a pregnant dog.' : 
               waiverType === 'senior' ? 'Please complete this waiver regarding grooming an elderly pet.' : 
               'Please complete all sections for your pet\'s safety and comfort.'}
            </p>
          </div>

          {waiverType === 'cologne' && (
            <div className="space-y-8">
              <section className="space-y-4">
                <h2 className="text-xl font-bold text-indigo-600 border-b-2 border-indigo-100 pb-2">Customer Information</h2>
                <div className="space-y-4">
                  <div className="space-y-1 relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Franchisee Name *</label>
                    <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{franchiseeName}</div>
                  </div>
                  <div className="space-y-1 relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Customer Name *</label>
                    <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{ownerName}</div>
                  </div>
                  <div className="space-y-1 relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Customer Address</label>
                    <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{customerAddress}</div>
                  </div>
                </div>

                <div className="bg-gray-50 p-6 rounded border border-gray-100 text-sm text-gray-700 leading-relaxed font-medium shadow-inner mt-6">
                  <p className="mb-4">Dear Blue Wheelers / Dash DogWash Franchisee,</p>
                  <p>I, <strong>{ownerName}</strong>, acknowledge that although the cologne is part of the Blue Wheelers and Dash DogWash service and I have been told that it will make my dog smell good for up to two weeks, I choose not to have it used on my dog for the following reasons:</p>
                </div>
              </section>

              <section className="space-y-4">
                <h2 className="text-xl font-bold text-indigo-600 border-b-2 border-indigo-100 pb-2">Reason for Declining Cologne Service</h2>
                <div className="space-y-1">
                  <label className="text-xs font-bold text-gray-500 mb-1 block">Reason(s) for declining *</label>
                  <textarea 
                    className="w-full p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 min-h-[120px] text-sm text-gray-700 resize-y"
                    value={formData.cologneReason}
                    onChange={(e) => setFormData(p => ({...p, cologneReason: e.target.value}))}
                  />
                </div>
              </section>

              <div className="text-xs text-gray-600 py-4">
                I understand that this is my choice and the franchisee has explained the disadvantages in not using the cologne.
              </div>
            </div>
          )}

          {waiverType === 'shampoo' && (
            <div className="space-y-8">
              <section className="space-y-4">
                <h2 className="text-xl font-bold text-indigo-600 border-b-2 border-indigo-100 pb-2">Customer Information</h2>
                <div className="space-y-4">
                  <div className="space-y-1 relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Franchisee Name *</label>
                    <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{franchiseeName}</div>
                  </div>
                  <div className="space-y-1 relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Customer Name *</label>
                    <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{ownerName}</div>
                  </div>
                  <div className="space-y-1 relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Customer Address</label>
                    <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{customerAddress}</div>
                  </div>
                </div>

                <div className="bg-gray-50 p-6 rounded border border-gray-100 text-sm text-gray-700 leading-relaxed font-medium shadow-inner mt-6">
                  <p className="mb-4">Dear Blue Wheelers / Dash DogWash Franchisee,</p>
                  <p>I, <strong>{ownerName}</strong>, acknowledge that I have specifically requested that you use the shampoo I have provided, instead of your Blue Wheelers shampoos to wash my dog(s) for the following reasons:</p>
                </div>
              </section>

              <section className="space-y-4">
                <h2 className="text-xl font-bold text-indigo-600 border-b-2 border-indigo-100 pb-2">Reason for Using Own Shampoo</h2>
                <div className="space-y-1">
                  <label className="text-xs font-bold text-gray-500 mb-1 block">Reason(s) for using own shampoo *</label>
                  <textarea 
                    className="w-full p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 min-h-[120px] text-sm text-gray-700 resize-y"
                    value={formData.shampooReason}
                    onChange={(e) => setFormData(p => ({...p, shampooReason: e.target.value}))}
                  />
                </div>
              </section>

              <div className="text-xs text-gray-600 py-4 leading-relaxed">
                In doing this, I understand that I take on the full responsibility if my dog(s) are impacted in any way as a result of the shampoo. I also understand that the use of my shampoo will void Blue Wheelers' or Dash DogWash's insurance cover for any skin conditions that may appear as a result of the use of this shampoo.
              </div>
            </div>
          )}

          {waiverType === 'matted' && (
            <div className="space-y-8">
              <section className="space-y-4">
                <h2 className="text-xl font-bold text-indigo-600 border-b-2 border-indigo-100 pb-2">Customer Information</h2>
                <div className="space-y-4">
                  <div className="space-y-1 relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Franchisee Name *</label>
                    <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{franchiseeName}</div>
                  </div>
                  <div className="space-y-1 relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Customer Name *</label>
                    <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{ownerName}</div>
                  </div>
                  <div className="space-y-1 relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Customer Address</label>
                    <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{customerAddress}</div>
                  </div>

                  <div className="flex gap-4">
                    <div className="w-1/2 space-y-1 relative">
                      <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Dog's Name</label>
                      <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{petName}</div>
                    </div>
                    <div className="w-1/2 space-y-1 relative">
                      <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Dog's Breed</label>
                      <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{breed}</div>
                    </div>
                  </div>
                </div>

                <div className="bg-gray-50 p-6 rounded border-l-4 border-l-indigo-500 border border-t-gray-100 border-r-gray-100 border-b-gray-100 text-xs text-gray-700 leading-relaxed font-medium shadow-inner mt-6 space-y-4">
                  <p className="font-bold">Dear Blue Wheelers / Dash Franchisee,</p>
                  <p>I, <strong>{ownerName}</strong>, acknowledge that I have been well advised that my dog's coat is severely matted, that matted coats can cause a variety of skin & health problems.</p>
                  
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900">
                    Matted fur does not allow for air circulation to the skin, causing hot spots, bacterial and fungal infections. Fleas, ticks, maggots, and other parasites may be lurking in the coat, causing further skin infections.
                  </div>
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900">
                    Matted fur pulls and binds, restricting blood flow and causing pain to your pet when they move or lay on mats. The skin underneath is usually raw and inflamed. Matted coats will not dry properly and can lead to rotting fur and skin.
                  </div>
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900">
                    The matted hair rests tightly against the skin – the only way of removing mats is to use a short blade to clip between the skin and mats.
                  </div>

                  <ul className="list-disc pl-5 space-y-3 mt-4 text-gray-600">
                    <li>Your groomer will show all care to avoid injury, however as we need to work so closely to the skin to remove the matted coat, your dog may be nicked. The chances of injury are increased when a pet's coat is severely matted.</li>
                    <li>Shaving may cause irritation and/or rash.</li>
                    <li>Your dog may show signs of skin irritation and sores due to the matting, wet undercoat and dirty coat.</li>
                    <li>As de-matting pets is often too stressful for the animal and too dangerous and harmful to the skin, we will not attempt to remove severe matting by combing or brushing.</li>
                    <li>If your dog's ears are badly matted, the chance of them experiencing hematomas once the hair is removed/shaved (due to circulation being cut off by the mats), is increased. Hematomas are exhibited by blood pooling at the ends of the ear leather. This can occur hours after the initial groom and can be triggered through the shaking of the ears. <strong>If your pet shows prolonged, excessive head shaking after the groom, consider vet treatment to avoid any further issues caused from the shaking.</strong></li>
                  </ul>

                  <p className="pt-2 font-bold text-gray-800">I accept full responsibility for my dog's health, I understand the risks and information above and authorize the provision of services required to remove the coat in its entirety.</p>
                </div>
              </section>
            </div>
          )}

          {waiverType === 'clipping' && (
            <div className="space-y-8">
              <section className="space-y-4">
                <h2 className="text-xl font-bold text-indigo-600 border-b-2 border-indigo-100 pb-2">Customer Information</h2>
                <div className="space-y-4">
                  <div className="space-y-1 relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Franchisee Name *</label>
                    <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{franchiseeName}</div>
                  </div>
                  <div className="space-y-1 relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Customer Name *</label>
                    <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{ownerName}</div>
                  </div>
                  <div className="space-y-1 relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Customer Address</label>
                    <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{customerAddress}</div>
                  </div>

                  <div className="flex gap-4">
                    <div className="w-1/2 space-y-1 relative">
                      <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Dog's Name</label>
                      <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{petName}</div>
                    </div>
                    <div className="w-1/2 space-y-1 relative">
                      <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Dog's Breed</label>
                      <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{breed}</div>
                    </div>
                  </div>
                </div>

                <div className="bg-gray-50 p-6 rounded border-l-4 border-l-indigo-500 border border-t-gray-100 border-r-gray-100 border-b-gray-100 text-xs text-gray-700 leading-relaxed font-medium shadow-inner mt-6 space-y-4">
                  <h3 className="text-center font-bold uppercase underline text-sm mb-4 mt-2 tracking-wide text-indigo-900">WAIVER TO CLIP MY DOUBLE COATED DOG/S</h3>
                  
                  <p>Dear Blue Wheelers / Dash Franchisee,</p>
                  <p>I, {ownerName}, acknowledge that I have been well advised not to clip my double coated dog.</p>
                  
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900 italic">
                    • We cannot guarantee that the dog's hair will grow back after shaving
                  </div>
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900 italic">
                    • Shaving may cause irritation and/or rash
                  </div>
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900 italic">
                    • Shaving the coat does not necessarily make the dog feel cooler
                  </div>
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900 italic">
                    • Shaving does not reduce shedding; it only makes the shedding coat shorter
                  </div>

                  <p className="pt-4 font-bold text-gray-800">Despite this, I still choose to continue with my requested service and have my dog clipped.</p>
                  
                  <div className="flex font-bold pt-4 text-gray-800">
                    <span className="w-1/2">Dog/s name: <span className="font-normal">{petName}</span></span>
                    <span className="w-1/2">Dog/s breed: <span className="font-normal">[{breed !== 'Not specified' ? breed : "Dog's Breed"}]</span></span>
                  </div>
                </div>
              </section>
            </div>
          )}

          {waiverType === 'pregnant' && (
            <div className="space-y-8">
              <section className="space-y-4">
                <h2 className="text-xl font-bold text-indigo-600 border-b-2 border-indigo-100 pb-2">Customer Information</h2>
                <div className="space-y-4">
                  <div className="space-y-1 relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Franchisee Name *</label>
                    <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{franchiseeName}</div>
                  </div>
                  <div className="space-y-1 relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Customer Name *</label>
                    <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{ownerName}</div>
                  </div>
                  <div className="space-y-1 relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Customer Address</label>
                    <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{customerAddress}</div>
                  </div>

                  <div className="flex gap-4">
                    <div className="w-1/2 space-y-1 relative">
                      <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Dog's Name</label>
                      <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{petName}</div>
                    </div>
                    <div className="w-1/2 space-y-1 relative">
                      <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Dog's Breed</label>
                      <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{breed}</div>
                    </div>
                  </div>
                </div>

                <div className="pt-4">
                  <h3 className="text-sm font-bold text-gray-700 mb-4">Expected Delivery Date</h3>
                  <div className="relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Date *</label>
                    <div className="relative">
                      <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                      <input 
                        type="date"
                        className="w-full pl-10 p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm text-gray-700"
                        value={formData.deliveryDate}
                        onChange={(e) => setFormData(p => ({...p, deliveryDate: e.target.value}))}
                      />
                    </div>
                    <div className="text-[10px] text-gray-400 pl-1 mt-1">Select your date here</div>
                  </div>
                </div>

                <div className="bg-gray-50 p-6 rounded border-l-4 border-l-indigo-500 border border-t-gray-100 border-r-gray-100 border-b-gray-100 text-xs text-gray-700 leading-relaxed font-medium shadow-inner mt-6 space-y-4">
                  <h3 className="text-center font-bold uppercase underline text-sm mb-4 mt-2 tracking-wide text-indigo-900">WAIVER TO CLIP MY PREGNANT DOG</h3>
                  
                  <p>Dear Blue Wheelers / Dash Franchisee,</p>
                  <p>I, {ownerName}, acknowledge that I have been well advised of the risks to grooming a pregnant dog.</p>
                  
                  <p className="pt-2 font-bold text-gray-800">Despite this, I accept full responsibility for the health of my dog and still choose to continue with my requested service and have my dog clipped.</p>
                  
                  <p>I understand that:</p>
                  
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900 italic">
                    • Grooming pregnant dogs is not recommended less than 2 weeks before the birthing date.
                  </div>
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900 italic">
                    • My dog will receive a maternity hygiene clip and not full groom.
                  </div>
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900 italic">
                    • Bathing and drying my dog will not be done less than 2 weeks prior to birthing.
                  </div>
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900 italic">
                    • The grooming session may be halted if the pregnant dog is showing stress (at any stage of the pregnancy).
                  </div>

                  <div className="flex justify-between font-bold pt-4 text-gray-800">
                    <span className="w-1/3">Dog/s name: <span className="font-normal">{petName}</span></span>
                    <span className="w-1/3 text-center">Dog/s breed: <span className="font-normal">[{breed !== 'Not specified' ? breed : "Dog's Breed"}]</span></span>
                    <span className="w-1/3 text-right">Delivery date: <span className="font-normal">[{formData.deliveryDate || 'Delivery Date'}]</span></span>
                  </div>
                </div>
              </section>
            </div>
          )}

          {waiverType === 'senior' && (
            <div className="space-y-8">
              <section className="space-y-4">
                <h2 className="text-xl font-bold text-indigo-600 border-b-2 border-indigo-100 pb-2">Customer Information</h2>
                <div className="space-y-4">
                  <div className="space-y-1 relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Franchisee Name *</label>
                    <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{franchiseeName}</div>
                  </div>
                  <div className="space-y-1 relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Customer Name *</label>
                    <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{ownerName}</div>
                  </div>
                  <div className="space-y-1 relative">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Customer Address</label>
                    <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{customerAddress}</div>
                  </div>

                  <div className="flex gap-4">
                    <div className="w-1/2 space-y-1 relative">
                      <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Dog's Name</label>
                      <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{petName}</div>
                    </div>
                    <div className="w-1/2 space-y-1 relative">
                      <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Dog's Breed</label>
                      <div className="p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-sm text-gray-700 bg-white min-h-[42px]">{breed}</div>
                    </div>
                  </div>

                  <div className="space-y-1 relative mt-4">
                    <label className="absolute -top-2.5 left-3 bg-white px-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider z-10">Dog's Age *</label>
                    <input
                      type="text"
                      className="w-full p-3 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm text-gray-700"
                      value={formData.age}
                      onChange={(e) => setFormData(p => ({...p, age: e.target.value}))}
                    />
                    <div className="text-[10px] text-gray-400 pl-1 mt-1">Enter dog's age in years</div>
                  </div>
                </div>

                <div className="bg-gray-50 p-6 rounded border-l-4 border-l-indigo-500 border border-t-gray-100 border-r-gray-100 border-b-gray-100 text-xs text-gray-700 leading-relaxed font-medium shadow-inner mt-6 space-y-4">
                  <h3 className="text-center font-bold uppercase underline text-sm mb-4 mt-2 tracking-wide text-indigo-900">SENIOR WAIVER (for pets over 7 years old)</h3>
                  
                  <p>Dear Blue Wheelers / Dash Franchisee,</p>
                  <p>I, {ownerName}, acknowledge that I have been well advised of the risks to grooming a senior dog.</p>
                  <p>I understand that:</p>
                  
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900 italic">
                    • Standing for long periods of time may be difficult for a senior pet, so they may (in most cases) require a longer appointment.
                  </div>
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900 italic">
                    • As pets age their coat / fur can change, thus there is no guarantee that a groom on a senior pet will result in the desired style.
                  </div>
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900 italic">
                    • Older pets are prone to a variety of lumps and bumps. Please let us know to the best of your abilities where these are on your pets' body. Fatty tumors, cysts, warts and other miscellaneous skin lesions/bumps can cause an uneven haircut at times and can be easily nicked, resulting in injury
                  </div>
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900 italic">
                    • Skin becomes thin and loose as pets age, which can be problematic when grooming. While we use extreme caution and care in all situations, possible problems could occur including cuts, nicks, & scratches. In the event an accident does occur you will be notified immediately
                  </div>
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900 italic">
                    • Nails can become thick and brittle as pets age, thus trimming nails can result in split and/or broken nails.
                  </div>
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900 italic">
                    • Lungs lose their elasticity during the aging process and the ability of the lungs to oxygenate the blood may be decreased. In the event that your groomer suspects that your dog is experiencing respiratory stress or infection, they may refuse services with your pets' best interest in mind.
                  </div>
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900 italic">
                    • As pets age their abilities to withhold bowels and urine can decrease. This can make the grooming process challenging. We will wash your dog to the best of our abilities, but will not repeatedly wash them and put them under any unnecessary stress.
                  </div>
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900 italic">
                    • Having a senior dog groomed by somebody new in a new environment may be stressful for them; particularly if they are very elderly or have health complications.
                  </div>
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900 italic">
                    • While Blue Wheelers / Dash DogWash practice quality care, there is a possibility for a senior pet to have seizures or heart attacks. In the event of a medical emergency of this nature you will be contacted immediately
                  </div>
                  <div className="bg-yellow-50/50 border border-yellow-200 p-4 rounded text-yellow-900 italic">
                    • If your pet has an issue that we feel needs urgent medical treatment, the groom will be stopped and we will advise you to take your dog to your regular veterinarian.
                  </div>

                  <p className="pt-4 font-bold text-gray-800">I accept full responsibility for the health of my dog, understand the information above and choose to continue with my requested service and have my dog groomed.</p>
                  
                  <p className="text-gray-600">I have advised the groomer of any medical or health issues with my dog.</p>
                  
                  <p className="text-gray-600">I accept that if the groomer notices any form of stress, they may decide to stop the service at any time; in the best interests of my pet.</p>

                  <div className="flex justify-between font-bold pt-4 pb-2 text-gray-800">
                    <span className="w-1/3">Dog/s name: <span className="font-normal text-gray-600">{petName}</span></span>
                    <span className="w-1/3 text-center">Dog/s breed: <span className="font-normal text-gray-600">[{breed !== 'Not specified' ? breed : "Dog's Breed"}]</span></span>
                    <span className="w-1/3 text-right">Dog's age: <span className="font-normal text-gray-600">[{formData.age || "Dog's Age"}]</span></span>
                  </div>
                </div>
              </section>
            </div>
          )}

          {waiverType === 'intake' && (
            <>

          {/* Section 1: Information */}
          <section className="space-y-6">
            <h2 className="text-lg font-bold text-indigo-800 border-b-2 border-indigo-100 pb-2">Customer & Pet Information</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="space-y-1">
                <label className="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Owner Name</label>
                <div className="p-3 bg-gray-50 border rounded text-sm text-gray-700">{ownerName}</div>
              </div>
              <div className="space-y-1">
                <label className="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Phone / Email</label>
                <div className="p-3 bg-gray-50 border rounded text-sm text-gray-700">{phone} / {email}</div>
              </div>
              <div className="space-y-1">
                <label className="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pet Name</label>
                <div className="p-3 bg-gray-50 border rounded text-sm text-gray-700">{petName}</div>
              </div>
              <div className="space-y-1">
                <label className="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Breed</label>
                <div className="p-3 bg-gray-50 border rounded text-sm text-gray-700">{breed || '-'}</div>
              </div>
              <Input 
                label="Age" 
                placeholder="Years/Months" 
                value={formData.age} 
                onChange={(e) => setFormData(p => ({...p, age: e.target.value}))} 
              />
              <Input 
                label="Weight (approx)" 
                placeholder="kg" 
                value={formData.weight} 
                onChange={(e) => setFormData(p => ({...p, weight: e.target.value}))} 
              />
            </div>
          </section>

          {/* Section 2: Vaccinations */}
          <section className="space-y-4">
            <h2 className="text-lg font-bold text-indigo-800 border-b-2 border-indigo-100 pb-2">Vaccinations</h2>
            <CheckboxField 
              label="Is your pet fully vaccinated?" 
              checked={formData.isVaccinated} 
              onChange={() => setFormData(p => ({...p, isVaccinated: !p.isVaccinated}))} 
            />
          </section>

          {/* Section 3: Health History */}
          <section className="space-y-4">
            <h2 className="text-lg font-bold text-indigo-800 border-b-2 border-indigo-100 pb-2">Health History</h2>
            <p className="text-xs text-gray-500 italic pb-2">Please indicate any pre-existing medical conditions:</p>
            <div className="grid grid-cols-2 gap-x-8 gap-y-2">
              <CheckboxField label="Arthritis" checked={formData.healthConditions.arthritis} onChange={() => handleCheckboxChange('healthConditions', 'arthritis')} />
              <CheckboxField label="Epilepsy" checked={formData.healthConditions.epilepsy} onChange={() => handleCheckboxChange('healthConditions', 'epilepsy')} />
              <CheckboxField label="Collapsing Trachea" checked={formData.healthConditions.collapsingTrachea} onChange={() => handleCheckboxChange('healthConditions', 'collapsingTrachea')} />
              <CheckboxField label="Heart Disease" checked={formData.healthConditions.heartDisease} onChange={() => handleCheckboxChange('healthConditions', 'heartDisease')} />
              <CheckboxField label="Diabetes" checked={formData.healthConditions.diabetes} onChange={() => handleCheckboxChange('healthConditions', 'diabetes')} />
              <CheckboxField label="Chronic Skin Issues" checked={formData.healthConditions.chronicSkinIssues} onChange={() => handleCheckboxChange('healthConditions', 'chronicSkinIssues')} />
              <CheckboxField label="Chronic Ear Issues" checked={formData.healthConditions.chronicEarIssues} onChange={() => handleCheckboxChange('healthConditions', 'chronicEarIssues')} />
              <CheckboxField label="Allergies" checked={formData.healthConditions.allergies} onChange={() => handleCheckboxChange('healthConditions', 'allergies')} />
            </div>
            <div className="mt-4">
              <Input 
                label="Other Health Notes" 
                placeholder="Any other conditions..." 
                value={formData.otherHealth} 
                onChange={(e) => setFormData(p => ({...p, otherHealth: e.target.value}))} 
              />
            </div>
          </section>

          {/* Section 4: Sensitivities */}
          <section className="space-y-6">
            <h2 className="text-lg font-bold text-indigo-800 border-b-2 border-indigo-100 pb-2">Skin & Product Sensitivities</h2>
            <RadioGroup 
              label="Does your pet have sensitive skin?" 
              name="sensitive_skin" 
              value={formData.skinSensitivities.sensitiveSkin} 
              onChange={(val) => setFormData(p => ({...p, skinSensitivities: {...p.skinSensitivities, sensitiveSkin: val}}))} 
            />
            <RadioGroup 
              label="Are you or your pet allergic or reactive to any grooming products (e.g. shampoos, conditioners)?" 
              name="product_allergies" 
              value={formData.skinSensitivities.productAllergies} 
              onChange={(val) => setFormData(p => ({...p, skinSensitivities: {...p.skinSensitivities, productAllergies: val}}))} 
            />
            <RadioGroup 
              label="Has your vet advised not to wash your pet's head or ears?" 
              name="vet_advice" 
              value={formData.skinSensitivities.veterinaryAdvice} 
              onChange={(val) => setFormData(p => ({...p, skinSensitivities: {...p.skinSensitivities, veterinaryAdvice: val}}))} 
            />
          </section>

          {/* Section 5: Behavioural */}
          <section className="space-y-6">
            <h2 className="text-lg font-bold text-indigo-800 border-b-2 border-indigo-100 pb-2">Behavioural Assessment</h2>
            <p className="text-xs text-gray-500 italic pb-2">Does your pet have any known behavioural concerns?</p>
            <div className="grid grid-cols-2 gap-4">
              <CheckboxField label="Fearful" checked={formData.behavioural.fearful} onChange={() => handleCheckboxChange('behavioural', 'fearful')} />
              <CheckboxField label="Aggressive" checked={formData.behavioural.aggressive} onChange={() => handleCheckboxChange('behavioural', 'aggressive')} />
              <CheckboxField label="Anxious" checked={formData.behavioural.anxious} onChange={() => handleCheckboxChange('behavioural', 'anxious')} />
              <CheckboxField label="None known" checked={formData.behavioural.noneKnown} onChange={() => handleCheckboxChange('behavioural', 'noneKnown')} />
            </div>
            <Input 
              label="Other behavioural concerns" 
              value={formData.behaviouralOther} 
              onChange={(e) => setFormData(p => ({...p, behaviouralOther: e.target.value}))} 
            />
            
            <p className="text-xs text-gray-500 italic pb-2">Are there any areas your pet dislikes being touched?</p>
            <div className="grid grid-cols-2 gap-4">
              <CheckboxField label="Head" checked={formData.dislikeTouched.head} onChange={() => handleCheckboxChange('dislikeTouched', 'head')} />
              <CheckboxField label="Paws" checked={formData.dislikeTouched.paws} onChange={() => handleCheckboxChange('dislikeTouched', 'paws')} />
              <CheckboxField label="Tail" checked={formData.dislikeTouched.tail} onChange={() => handleCheckboxChange('dislikeTouched', 'tail')} />
              <CheckboxField label="Other" checked={formData.dislikeTouched.other} onChange={() => handleCheckboxChange('dislikeTouched', 'other')} />
            </div>
          </section>

          {/* Section 6: Previous Experience */}
          <section className="space-y-6">
            <h2 className="text-lg font-bold text-indigo-800 border-b-2 border-indigo-100 pb-2">Previous Grooming Experience</h2>
            <RadioGroup 
              label="Has your pet ever been professionally groomed before?" 
              name="prof_groomed" 
              value={formData.groomingExperience.professionallyGroomed} 
              onChange={(val) => setFormData(p => ({...p, groomingExperience: {...p.groomingExperience, professionallyGroomed: val}}))} 
            />
            <RadioGroup 
              label="Did the previous groomer report any issues or difficulties?" 
              name="prev_issues" 
              value={formData.groomingExperience.previousIssues} 
              onChange={(val) => setFormData(p => ({...p, groomingExperience: {...p.groomingExperience, previousIssues: val}}))} 
            />
          </section>

          {/* Section 7: Tick & Parasite */}
          <section className="space-y-6">
            <h2 className="text-lg font-bold text-indigo-800 border-b-2 border-indigo-100 pb-2">Tick & Parasite Prevention</h2>
            <RadioGroup 
              label="Is your pet currently on tick prevention?" 
              name="tick_prevention" 
              value={formData.tickPrevention} 
              onChange={(val) => setFormData(p => ({...p, tickPrevention: val}))} 
            />
          </section>

          {/* Section 8: Expectations */}
          <section className="space-y-6 p-6 bg-gray-50 rounded-lg border border-gray-100">
            <h2 className="text-sm font-bold text-gray-700 uppercase tracking-widest">Expectations</h2>
            <p className="text-sm text-gray-600 leading-relaxed italic">
              "Overall your pet's health and comfort is at the heart of everything we do. We will make every effort to provide you with the best service, however the dog's welfare always comes first. A groom may be paused or stopped at any point for health or behavioural reasons."
            </p>
            <CheckboxField 
              label="I accept and acknowledge the need to put my pet's best interest first and confirm that I have fully disclosed all health and behavioural information." 
              checked={formData.acceptedExpectations} 
              onChange={() => setFormData(p => ({...p, acceptedExpectations: !p.acceptedExpectations}))} 
            />
          </section>
          </>
          )}

          {/* Section 9: Signature */}
          <section className="space-y-4">
            <h2 className="text-xl font-bold text-indigo-600 border-b-2 border-indigo-100 pb-2">Signature and Date</h2>
            <div className="border rounded-lg bg-gray-50 overflow-hidden">
              <div className="p-3 border-b bg-white flex justify-between items-center">
                <p className="text-xs font-medium text-gray-500 italic">Please draw your signature below</p>
                <Button variant="ghost" size="sm" onClick={clearSignature} className="text-red-500 hover:text-red-600">
                  <X className="w-4 h-4 mr-1" />
                  Clear
                </Button>
              </div>
              <SignatureCanvas 
                ref={sigCanvas}
                penColor='black'
                canvasProps={{width: 600, height: 200, className: 'sigCanvas w-full cursor-crosshair bg-white'}}
              />
            </div>
            <div className="flex items-center gap-2 text-gray-400 text-xs mt-1">
              <Check className="w-3 h-3" />
              <span>Signature Date: {new Date().toLocaleDateString('en-AU', { day: 'numeric', month: 'long', year: 'numeric' })}</span>
            </div>
          </section>

          <div className="pt-10 border-t flex flex-col items-center gap-4">
            <Button 
              size="lg" 
              className="w-full h-14 text-lg font-bold uppercase tracking-widest shadow-lg shadow-indigo-100"
              onClick={handleSubmit}
              isLoading={isSubmitting}
            >
              {(['cologne', 'shampoo', 'matted', 'clipping', 'pregnant', 'senior'].includes(waiverType || '')) ? 'Submit Waiver' : 'Submit Form'}
            </Button>
            <button 
              onClick={onClose}
              className="text-gray-400 hover:text-gray-600 text-sm font-medium transition-colors"
            >
              Cancel and go back
            </button>
          </div>
        </div>
      </div>
    </Modal>
  )
}
