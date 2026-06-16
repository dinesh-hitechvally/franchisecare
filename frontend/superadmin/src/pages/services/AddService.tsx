import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import toast from 'react-hot-toast'
import { Package, FileText, PawPrint, Layers, Hash, DollarSign, Clock } from 'lucide-react'

interface ServiceForm {
  serviceName: string
  serviceDescription: string
  petSize: string
  serviceGroup: string
  displayPosition: string
  defaultServicePrice: string
  serviceDuration: string
  shampooRequired: boolean
  makeServiceActive: boolean
}

const initialForm: ServiceForm = {
  serviceName: '',
  serviceDescription: '',
  petSize: '',
  serviceGroup: '',
  displayPosition: '',
  defaultServicePrice: '0',
  serviceDuration: '',
  shampooRequired: false,
  makeServiceActive: false
}

const mockServiceGroups = [
  { id: 1, name: 'Washing' },
  { id: 2, name: 'Grooming' },
  { id: 3, name: 'Accessories' },
  { id: 4, name: 'Flea Treatments' },
  { id: 5, name: 'Treats' },
  { id: 6, name: 'Other' },
]

const petSizes = [
  { id: 'all', name: 'All Pet Sizes' },
  { id: 'toy', name: 'Toy' },
  { id: 'small', name: 'Small' },
  { id: 'medium', name: 'Medium' },
  { id: 'large', name: 'Large' },
]

export function AddService() {
  const navigate = useNavigate()
  const [form, setForm] = useState<ServiceForm>(initialForm)
  const [errors, setErrors] = useState<Partial<Record<keyof ServiceForm, string>>>({})

  const handleChange = (field: keyof ServiceForm, value: string | boolean) => {
    setForm(prev => ({ ...prev, [field]: value }))
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: undefined }))
    }
  }

  const validate = (): boolean => {
    const newErrors: Partial<Record<keyof ServiceForm, string>> = {}
    
    if (!form.serviceName.trim()) {
      newErrors.serviceName = 'Service Name is required'
    }
    if (!form.serviceDescription.trim()) {
      newErrors.serviceDescription = 'Service Description is required'
    }
    if (!form.displayPosition.trim()) {
      newErrors.displayPosition = 'Display Position is required'
    }
    if (!form.defaultServicePrice.trim()) {
      newErrors.defaultServicePrice = 'Default Service Price is required'
    }
    if (!form.serviceDuration.trim()) {
      newErrors.serviceDuration = 'Service Duration is required'
    }

    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    
    if (!validate()) {
      toast.error('Please fill in all required fields')
      return
    }

    // Mock API call
    toast.success('Service added successfully!')
    navigate('/services/list')
  }

  const handleCancel = () => {
    navigate('/services/list')
  }

  return (
    <div className="page-content">
      <h1 className="page-title">Add Service</h1>

      <div className="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
        <form onSubmit={handleSubmit}>
          <div className="p-6 space-y-5">
            {/* Service Name Field */}
            <div className="relative">
              <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                <Package size={18} />
              </div>
              <input
                type="text"
                id="serviceName"
                value={form.serviceName}
                onChange={(e) => handleChange('serviceName', e.target.value)}
                className={`peer w-full pl-10 pr-4 pt-5 pb-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all ${
                  errors.serviceName ? 'border-red-500 bg-red-50' : 'border-gray-300 hover:border-gray-400'
                }`}
                placeholder=" "
              />
              <label 
                htmlFor="serviceName"
                className={`absolute left-10 transition-all duration-200 pointer-events-none
                  ${form.serviceName ? 'top-1.5 text-xs text-purple-600' : 'top-1/2 -translate-y-1/2 text-sm text-gray-500'}
                  peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-purple-600 peer-focus:translate-y-0`}
              >
                Service Name <span className="text-red-500">*</span>
              </label>
              {errors.serviceName && (
                <p className="text-red-500 text-xs mt-1.5 ml-1">{errors.serviceName}</p>
              )}
            </div>

            {/* Service Description Field */}
            <div className="relative">
              <div className="absolute left-3 top-4 text-gray-400">
                <FileText size={18} />
              </div>
              <textarea
                id="serviceDescription"
                value={form.serviceDescription}
                onChange={(e) => handleChange('serviceDescription', e.target.value)}
                rows={4}
                className={`peer w-full pl-10 pr-4 pt-5 pb-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none ${
                  errors.serviceDescription ? 'border-red-500 bg-red-50' : 'border-gray-300 hover:border-gray-400'
                }`}
                placeholder=" "
              />
              <label 
                htmlFor="serviceDescription"
                className={`absolute left-10 transition-all duration-200 pointer-events-none
                  ${form.serviceDescription ? 'top-1.5 text-xs text-purple-600' : 'top-4 text-sm text-gray-500'}
                  peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-purple-600`}
              >
                Service Description <span className="text-red-500">*</span>
              </label>
              {errors.serviceDescription && (
                <p className="text-red-500 text-xs mt-1.5 ml-1">{errors.serviceDescription}</p>
              )}
            </div>

            {/* Two column layout for selects */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
              {/* Pet Size Select */}
              <div className="relative">
                <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10">
                  <PawPrint size={18} />
                </div>
                <select
                  id="petSize"
                  value={form.petSize}
                  onChange={(e) => handleChange('petSize', e.target.value)}
                  className={`peer w-full pl-10 pr-10 pt-5 pb-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all hover:border-gray-400 appearance-none bg-white cursor-pointer ${
                    form.petSize ? 'text-gray-900' : 'text-gray-500'
                  }`}
                >
                  <option value="">Select pet size</option>
                  {petSizes.map(size => (
                    <option key={size.id} value={size.id}>{size.name}</option>
                  ))}
                </select>
                <label 
                  htmlFor="petSize"
                  className="absolute left-10 top-1.5 text-xs text-gray-500 transition-all duration-200 pointer-events-none peer-focus:text-purple-600"
                >
                  Pet Size
                </label>
                <div className="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>

              {/* Service Group Select */}
              <div className="relative">
                <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10">
                  <Layers size={18} />
                </div>
                <select
                  id="serviceGroup"
                  value={form.serviceGroup}
                  onChange={(e) => handleChange('serviceGroup', e.target.value)}
                  className={`peer w-full pl-10 pr-10 pt-5 pb-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all hover:border-gray-400 appearance-none bg-white cursor-pointer ${
                    form.serviceGroup ? 'text-gray-900' : 'text-gray-500'
                  }`}
                >
                  <option value="">Select service group</option>
                  {mockServiceGroups.map(group => (
                    <option key={group.id} value={group.id}>{group.name}</option>
                  ))}
                </select>
                <label 
                  htmlFor="serviceGroup"
                  className="absolute left-10 top-1.5 text-xs text-gray-500 transition-all duration-200 pointer-events-none peer-focus:text-purple-600"
                >
                  Service Group
                </label>
                <div className="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
            </div>

            {/* Three column layout for numbers */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
              {/* Display Position Field */}
              <div className="relative">
                <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                  <Hash size={18} />
                </div>
                <input
                  type="number"
                  id="displayPosition"
                  value={form.displayPosition}
                  onChange={(e) => handleChange('displayPosition', e.target.value)}
                  className={`peer w-full pl-10 pr-4 pt-5 pb-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all ${
                    errors.displayPosition ? 'border-red-500 bg-red-50' : 'border-gray-300 hover:border-gray-400'
                  }`}
                  placeholder=" "
                />
                <label 
                  htmlFor="displayPosition"
                  className={`absolute left-10 transition-all duration-200 pointer-events-none
                    ${form.displayPosition ? 'top-1.5 text-xs text-purple-600' : 'top-1/2 -translate-y-1/2 text-sm text-gray-500'}
                    peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-purple-600 peer-focus:translate-y-0`}
                >
                  Display Position <span className="text-red-500">*</span>
                </label>
                {errors.displayPosition && (
                  <p className="text-red-500 text-xs mt-1.5 ml-1">{errors.displayPosition}</p>
                )}
              </div>

              {/* Default Service Price Field */}
              <div className="relative">
                <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                  <DollarSign size={18} />
                </div>
                <input
                  type="number"
                  id="defaultServicePrice"
                  value={form.defaultServicePrice}
                  onChange={(e) => handleChange('defaultServicePrice', e.target.value)}
                  className={`peer w-full pl-10 pr-4 pt-5 pb-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all ${
                    errors.defaultServicePrice ? 'border-red-500 bg-red-50' : 'border-gray-300 hover:border-gray-400'
                  }`}
                  placeholder=" "
                />
                <label 
                  htmlFor="defaultServicePrice"
                  className={`absolute left-10 transition-all duration-200 pointer-events-none
                    ${form.defaultServicePrice ? 'top-1.5 text-xs text-purple-600' : 'top-1/2 -translate-y-1/2 text-sm text-gray-500'}
                    peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-purple-600 peer-focus:translate-y-0`}
                >
                  Default Price <span className="text-red-500">*</span>
                </label>
                {errors.defaultServicePrice && (
                  <p className="text-red-500 text-xs mt-1.5 ml-1">{errors.defaultServicePrice}</p>
                )}
              </div>

              {/* Service Duration Field */}
              <div className="relative">
                <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                  <Clock size={18} />
                </div>
                <input
                  type="number"
                  id="serviceDuration"
                  value={form.serviceDuration}
                  onChange={(e) => handleChange('serviceDuration', e.target.value)}
                  className={`peer w-full pl-10 pr-4 pt-5 pb-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all ${
                    errors.serviceDuration ? 'border-red-500 bg-red-50' : 'border-gray-300 hover:border-gray-400'
                  }`}
                  placeholder=" "
                />
                <label 
                  htmlFor="serviceDuration"
                  className={`absolute left-10 transition-all duration-200 pointer-events-none
                    ${form.serviceDuration ? 'top-1.5 text-xs text-purple-600' : 'top-1/2 -translate-y-1/2 text-sm text-gray-500'}
                    peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-purple-600 peer-focus:translate-y-0`}
                >
                  Duration (mins) <span className="text-red-500">*</span>
                </label>
                {errors.serviceDuration && (
                  <p className="text-red-500 text-xs mt-1.5 ml-1">{errors.serviceDuration}</p>
                )}
              </div>
            </div>

            {/* Checkboxes */}
            <div className="flex items-center gap-8 pt-2">
              <label 
                htmlFor="shampooRequired" 
                className="flex items-center gap-3 cursor-pointer group"
              >
                <div className="relative">
                  <input
                    type="checkbox"
                    id="shampooRequired"
                    checked={form.shampooRequired}
                    onChange={(e) => handleChange('shampooRequired', e.target.checked)}
                    className="sr-only peer"
                  />
                  <div className="w-5 h-5 border-2 border-gray-300 rounded transition-all peer-checked:border-purple-600 peer-checked:bg-purple-600 group-hover:border-purple-400">
                    <svg 
                      className={`w-full h-full text-white p-0.5 transition-transform ${form.shampooRequired ? 'scale-100' : 'scale-0'}`}
                      fill="none" 
                      stroke="currentColor" 
                      viewBox="0 0 24 24"
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                </div>
                <span className="text-sm text-gray-700 group-hover:text-gray-900 transition-colors">
                  Shampoo Required
                </span>
              </label>

              <label 
                htmlFor="makeServiceActive" 
                className="flex items-center gap-3 cursor-pointer group"
              >
                <div className="relative">
                  <input
                    type="checkbox"
                    id="makeServiceActive"
                    checked={form.makeServiceActive}
                    onChange={(e) => handleChange('makeServiceActive', e.target.checked)}
                    className="sr-only peer"
                  />
                  <div className="w-5 h-5 border-2 border-gray-300 rounded transition-all peer-checked:border-purple-600 peer-checked:bg-purple-600 group-hover:border-purple-400">
                    <svg 
                      className={`w-full h-full text-white p-0.5 transition-transform ${form.makeServiceActive ? 'scale-100' : 'scale-0'}`}
                      fill="none" 
                      stroke="currentColor" 
                      viewBox="0 0 24 24"
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                </div>
                <span className="text-sm text-gray-700 group-hover:text-gray-900 transition-colors">
                  Make Service Active
                </span>
              </label>
            </div>
          </div>

          {/* Footer */}
          <div className="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
            <button
              type="button"
              onClick={handleCancel}
              className="px-5 py-2.5 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all"
            >
              CANCEL
            </button>
            <button
              type="submit"
              className="px-5 py-2.5 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all"
            >
              ADD SERVICE
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
