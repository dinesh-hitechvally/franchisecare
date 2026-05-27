import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { ChevronDown, ChevronUp, Upload } from 'lucide-react'

interface FormData {
  companyName: string
  abnNumber: string
  affiliatedToBusiness: string
  firstName: string
  lastName: string
  emailAddress: string
  password: string
  personalPhone: string
  mobile: string
  country: string
  state: string
  address1: string
  address2: string
  suburb: string
  timeZone: string
  shippingZone: string
  postCode: string
  serviceLocation: string
  dateOfBirth: string
  warningSuspension: string
  appLevelAdmin: string
}

const initialFormData: FormData = {
  companyName: '',
  abnNumber: '',
  affiliatedToBusiness: '',
  firstName: '',
  lastName: '',
  emailAddress: '',
  password: '',
  personalPhone: '',
  mobile: '',
  country: '',
  state: '',
  address1: '',
  address2: '',
  suburb: '',
  timeZone: '',
  shippingZone: '',
  postCode: '',
  serviceLocation: '',
  dateOfBirth: '',
  warningSuspension: '',
  appLevelAdmin: ''
}

const franchiseOptions = [
  'Bek Collins (Queensland)',
  'Binita Support (Victoria)',
  'Col Burrow (New South Wales)',
  'Dave Laming (South Australia)',
  'Frontend Support (Victoria)',
  'Lexi Bowles (Victoria)',
  'Mark Phenna (Western Australia)',
  'Mate Support (New South Wales)',
  'Mate Admin (Victoria)',
  'Mate S (Victoria)',
  'May Wilson (Victoria)',
  'May Wilson (Victoria)',
  'Rehanna Halfyard (South Australia)',
  'Serena - Marketing Support (Victoria)',
  'Steven Kirk (New South Wales)'
]

const stateGroups = [
  'Australian Capital Territory',
  'Tasmania',
  'Victoria',
  'South Australia',
  'Western Australia',
  'Northern Territory',
  'Queensland',
  'New South Wales',
  'All States'
]

const serviceFields = [
  { label: 'Washing Only Small - Long Hair', key: 'washSmallLong', default: '20' },
  { label: 'Washing Only Large - Long Hair', key: 'washLargeLong', default: '0' },
  { label: 'Washing Only Toy - Long Hair', key: 'washToyLong', default: '10' },
  { label: 'Washing Only Medium - Long Hair', key: 'washMediumLong', default: '70' },
  { label: 'Accessories', key: 'accessories', default: '0' },
  { label: 'Other', key: 'other', default: '0' },
  { label: 'Card Surcharge', key: 'cardSurcharge', default: '0' },
  { label: 'Deshed', key: 'deshed', default: '10' },
  { label: 'Nail Clipping', key: 'nailClipping', default: '20' },
  { label: 'Flea Treatments', key: 'fleaTreatments', default: '15' },
  { label: 'Blue Wheelers Treats', key: 'treats', default: '10' },
  { label: 'Medicated Wash (90min)', key: 'medicatedWash90', default: '92' },
  { label: 'Flea Wash (90min)', key: 'fleaWash90', default: '90' },
  { label: 'Full Groom Large', key: 'fullGroomLarge', default: '90' },
  { label: 'Medicated Wash (45min)', key: 'medicatedWash45', default: '67' },
  { label: 'Flea Wash (45min)', key: 'fleaWash45', default: '65' },
  { label: 'Hygiene Clip Large', key: 'hygieneClipLarge', default: '45' },
  { label: 'Medicated Wash (35-45min)', key: 'medicatedWash35', default: '57' },
  { label: 'Flea Wash (35-50min)', key: 'fleaWash35', default: '55' },
  { label: 'Washing Only Large', key: 'washOnlyLarge', default: '55' },
]

export function AddMember() {
  const navigate = useNavigate()
  const [formData, setFormData] = useState<FormData>(initialFormData)
  const [expandedSections, setExpandedSections] = useState({
    companyAccount: true,
    franchiseOwnedBy: true,
    socialSystemGroups: true,
    reactivateStockTake: false,
    services: true
  })
  const [selectedFranchises, setSelectedFranchises] = useState<string[]>([])
  const [selectedStateGroups, setSelectedStateGroups] = useState<string[]>([])
  const [serviceValues, setServiceValues] = useState<Record<string, string>>(
    Object.fromEntries(serviceFields.map(f => [f.key, f.default]))
  )
  const [checkboxes, setCheckboxes] = useState({
    warningSet: false,
    registerForTax: false,
    deregisterForTax: false,
    companyIsActive: false,
    ticketAdmin: false,
    loginToAdmin: false,
    memberSocialActive: false,
    memberLeadsActive: false
  })

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target
    setFormData(prev => ({ ...prev, [name]: value }))
  }

  const toggleSection = (section: keyof typeof expandedSections) => {
    setExpandedSections(prev => ({ ...prev, [section]: !prev[section] }))
  }

  const toggleFranchise = (franchise: string) => {
    setSelectedFranchises(prev => 
      prev.includes(franchise) 
        ? prev.filter(f => f !== franchise)
        : [...prev, franchise]
    )
  }

  const toggleStateGroup = (group: string) => {
    setSelectedStateGroups(prev => 
      prev.includes(group) 
        ? prev.filter(g => g !== group)
        : [...prev, group]
    )
  }

  const toggleCheckbox = (key: keyof typeof checkboxes) => {
    setCheckboxes(prev => ({ ...prev, [key]: !prev[key] }))
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    console.log('Form submitted:', { formData, selectedFranchises, selectedStateGroups, serviceValues, checkboxes })
    navigate('/members/list')
  }

  const SectionHeader = ({ title, section, expanded }: { title: string; section: keyof typeof expandedSections; expanded: boolean }) => (
    <div 
      className="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50 border-b"
      onClick={() => toggleSection(section)}
    >
      <h3 className="text-sm font-medium text-gray-700">{title}</h3>
      {expanded ? <ChevronUp size={20} className="text-gray-400" /> : <ChevronDown size={20} className="text-gray-400" />}
    </div>
  )

  return (
    <div className="page-content">
      <h1 className="page-title">Add Member Company</h1>

      <form onSubmit={handleSubmit}>
        {/* Create New Company Account */}
        <div className="card mb-4">
          <SectionHeader title="Create New Company Account" section="companyAccount" expanded={expandedSections.companyAccount} />
          
          {expandedSections.companyAccount && (
            <div className="p-6">
              <div className="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Company Name *</label>
                  <input
                    type="text"
                    name="companyName"
                    value={formData.companyName}
                    onChange={handleChange}
                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  />
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">ABN Number *</label>
                  <input
                    type="text"
                    name="abnNumber"
                    value={formData.abnNumber}
                    onChange={handleChange}
                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  />
                </div>
              </div>

              <div className="mb-4">
                <label className="block text-sm text-gray-600 mb-1">Affiliated To Business *</label>
                <select
                  name="affiliatedToBusiness"
                  value={formData.affiliatedToBusiness}
                  onChange={handleChange}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="Blue Wheelers">Blue Wheelers</option>
                  <option value="Dash DogWash">Dash DogWash</option>
                </select>
              </div>

              <div className="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <label className="block text-sm text-gray-600 mb-1">First Name *</label>
                  <input
                    type="text"
                    name="firstName"
                    value={formData.firstName}
                    onChange={handleChange}
                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  />
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Last Name *</label>
                  <input
                    type="text"
                    name="lastName"
                    value={formData.lastName}
                    onChange={handleChange}
                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Email Address *</label>
                  <input
                    type="email"
                    name="emailAddress"
                    value={formData.emailAddress}
                    onChange={handleChange}
                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  />
                  <span className="text-xs text-gray-400">Please enter a valid Email</span>
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Password *</label>
                  <input
                    type="password"
                    name="password"
                    value={formData.password}
                    onChange={handleChange}
                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Personal Phone</label>
                  <input
                    type="tel"
                    name="personalPhone"
                    value={formData.personalPhone}
                    onChange={handleChange}
                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  />
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Mobile *</label>
                  <input
                    type="tel"
                    name="mobile"
                    value={formData.mobile}
                    onChange={handleChange}
                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  />
                </div>
              </div>

              <div className="mb-4">
                <label className="block text-sm text-gray-600 mb-1">Country *</label>
                <select
                  name="country"
                  value={formData.country}
                  onChange={handleChange}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="Australia">Australia</option>
                  <option value="New Zealand">New Zealand</option>
                </select>
              </div>

              <div className="mb-4">
                <label className="block text-sm text-gray-600 mb-1">State *</label>
                <select
                  name="state"
                  value={formData.state}
                  onChange={handleChange}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="NSW">New South Wales</option>
                  <option value="VIC">Victoria</option>
                  <option value="QLD">Queensland</option>
                  <option value="SA">South Australia</option>
                  <option value="WA">Western Australia</option>
                  <option value="TAS">Tasmania</option>
                  <option value="NT">Northern Territory</option>
                  <option value="ACT">Australian Capital Territory</option>
                </select>
              </div>

              <div className="mb-4">
                <label className="block text-sm text-gray-600 mb-1">Address 1 *</label>
                <input
                  type="text"
                  name="address1"
                  value={formData.address1}
                  onChange={handleChange}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                />
              </div>

              <div className="mb-4">
                <label className="block text-sm text-gray-600 mb-1">Address 2</label>
                <input
                  type="text"
                  name="address2"
                  value={formData.address2}
                  onChange={handleChange}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                />
              </div>

              <div className="mb-4">
                <label className="block text-sm text-gray-600 mb-1">Suburb *</label>
                <input
                  type="text"
                  name="suburb"
                  value={formData.suburb}
                  onChange={handleChange}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                />
              </div>

              <div className="mb-4">
                <label className="block text-sm text-gray-600 mb-1">TimeZone *</label>
                <select
                  name="timeZone"
                  value={formData.timeZone}
                  onChange={handleChange}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="Australia/Sydney">Australia/Sydney</option>
                  <option value="Australia/Melbourne">Australia/Melbourne</option>
                  <option value="Australia/Brisbane">Australia/Brisbane</option>
                  <option value="Australia/Perth">Australia/Perth</option>
                  <option value="Australia/Adelaide">Australia/Adelaide</option>
                </select>
              </div>

              <div className="mb-4">
                <label className="block text-sm text-gray-600 mb-1">Shipping Zone *</label>
                <select
                  name="shippingZone"
                  value={formData.shippingZone}
                  onChange={handleChange}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="Zone 1">Zone 1</option>
                  <option value="Zone 2">Zone 2</option>
                  <option value="Zone 3">Zone 3</option>
                </select>
              </div>

              <div className="mb-4">
                <label className="block text-sm text-gray-600 mb-1">Post Code *</label>
                <input
                  type="text"
                  name="postCode"
                  value={formData.postCode}
                  onChange={handleChange}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                />
              </div>

              <div className="mb-4">
                <label className="block text-sm text-gray-600 mb-1">Service Location</label>
                <div className="flex gap-2">
                  <input
                    type="text"
                    name="serviceLocation"
                    value={formData.serviceLocation}
                    onChange={handleChange}
                    className="flex-1 border border-gray-300 rounded px-3 py-2 text-sm"
                  />
                  <button type="button" className="btn btn-primary whitespace-nowrap">
                    ADD ADDITIONAL SUBURB
                  </button>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Date of Birth</label>
                  <input
                    type="date"
                    name="dateOfBirth"
                    value={formData.dateOfBirth}
                    onChange={handleChange}
                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                    placeholder="mm / dd / yyyy"
                  />
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Warning Suspension</label>
                  <input
                    type="date"
                    name="warningSuspension"
                    value={formData.warningSuspension}
                    onChange={handleChange}
                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                    placeholder="mm / dd / yyyy"
                  />
                </div>
              </div>

              <div className="mb-4">
                <label className="block text-sm text-gray-600 mb-1">App Level Admin *</label>
                <select
                  name="appLevelAdmin"
                  value={formData.appLevelAdmin}
                  onChange={handleChange}
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                >
                  <option value=""></option>
                  <option value="Super Admin">Super Admin</option>
                  <option value="Admin">Admin</option>
                  <option value="Manager">Manager</option>
                  <option value="Support">Support</option>
                </select>
              </div>

              <div className="mb-4">
                <label className="block text-sm text-gray-600 mb-2">Upload Image here *</label>
                <div className="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                  <Upload size={32} className="mx-auto text-gray-400 mb-2" />
                  <p className="text-sm text-gray-500">Upload Image here *</p>
                </div>
                <button type="button" className="mt-2 text-sm text-gray-600 border border-gray-300 px-4 py-2 rounded">
                  RESET IMAGE
                </button>
              </div>
            </div>
          )}
        </div>

        {/* Franchise Owned By */}
        <div className="card mb-4">
          <SectionHeader title="Franchise Owned By" section="franchiseOwnedBy" expanded={expandedSections.franchiseOwnedBy} />
          
          {expandedSections.franchiseOwnedBy && (
            <div className="p-6">
              <div className="grid grid-cols-2 gap-x-8 gap-y-2">
                {franchiseOptions.map((franchise) => (
                  <label key={franchise} className="flex items-center gap-2 py-1 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={selectedFranchises.includes(franchise)}
                      onChange={() => toggleFranchise(franchise)}
                      className="w-4 h-4 rounded border-gray-300"
                    />
                    <span className="text-sm text-gray-700">{franchise}</span>
                  </label>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Add User to Social System Groups */}
        <div className="card mb-4">
          <SectionHeader title="Add User to Social System Groups" section="socialSystemGroups" expanded={expandedSections.socialSystemGroups} />
          
          {expandedSections.socialSystemGroups && (
            <div className="p-6">
              <div className="grid grid-cols-2 gap-x-8 gap-y-2">
                {stateGroups.map((group) => (
                  <label key={group} className="flex items-center gap-2 py-1 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={selectedStateGroups.includes(group)}
                      onChange={() => toggleStateGroup(group)}
                      className="w-4 h-4 rounded border-gray-300"
                    />
                    <span className="text-sm text-gray-700">{group}</span>
                  </label>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Reactivate Stock Take */}
        <div className="card mb-4">
          <SectionHeader title="Reactivate Stock Take" section="reactivateStockTake" expanded={expandedSections.reactivateStockTake} />
          
          {expandedSections.reactivateStockTake && (
            <div className="p-6">
              <p className="text-sm text-gray-500">Stock take reactivation options will appear here.</p>
            </div>
          )}
        </div>

        {/* Services */}
        <div className="card mb-4">
          <SectionHeader title="Services" section="services" expanded={expandedSections.services} />
          
          {expandedSections.services && (
            <div className="p-6">
              <div className="grid grid-cols-2 gap-4">
                {serviceFields.map((field) => (
                  <div key={field.key}>
                    <label className="block text-sm text-gray-600 mb-1">{field.label}</label>
                    <input
                      type="number"
                      value={serviceValues[field.key]}
                      onChange={(e) => setServiceValues(prev => ({ ...prev, [field.key]: e.target.value }))}
                      className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                    />
                  </div>
                ))}
              </div>

              <div className="mt-6">
                <label className="block text-sm text-gray-600 mb-1">Service Type</label>
                <select className="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                  <option value="Wash Only">Wash Only</option>
                  <option value="Full Groom">Full Groom</option>
                </select>
              </div>

              <div className="mt-4">
                <label className="block text-sm text-gray-600 mb-1">Suspend Leads *</label>
                <select className="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                  <option value=""></option>
                  <option value="Yes">Yes</option>
                  <option value="No">No</option>
                </select>
              </div>

              <div className="mt-4">
                <label className="block text-sm text-gray-600 mb-1">Forward Lead SMS to</label>
                <input
                  type="text"
                  className="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                />
              </div>

              <div className="mt-6 grid grid-cols-2 gap-x-8 gap-y-2">
                <label className="flex items-center gap-2 py-1 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={checkboxes.warningSet}
                    onChange={() => toggleCheckbox('warningSet')}
                    className="w-4 h-4 rounded border-gray-300"
                  />
                  <span className="text-sm text-gray-700">Warning Set</span>
                </label>
                <label className="flex items-center gap-2 py-1 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={checkboxes.registerForTax}
                    onChange={() => toggleCheckbox('registerForTax')}
                    className="w-4 h-4 rounded border-gray-300"
                  />
                  <span className="text-sm text-gray-700">Register for Tax</span>
                </label>
                <label className="flex items-center gap-2 py-1 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={checkboxes.deregisterForTax}
                    onChange={() => toggleCheckbox('deregisterForTax')}
                    className="w-4 h-4 rounded border-gray-300"
                  />
                  <span className="text-sm text-gray-700">Deregister for Tax</span>
                </label>
                <label className="flex items-center gap-2 py-1 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={checkboxes.companyIsActive}
                    onChange={() => toggleCheckbox('companyIsActive')}
                    className="w-4 h-4 rounded border-gray-300"
                  />
                  <span className="text-sm text-gray-700">Company is Active</span>
                </label>
                <label className="flex items-center gap-2 py-1 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={checkboxes.ticketAdmin}
                    onChange={() => toggleCheckbox('ticketAdmin')}
                    className="w-4 h-4 rounded border-gray-300"
                  />
                  <span className="text-sm text-gray-700">Ticket Admin</span>
                </label>
                <label className="flex items-center gap-2 py-1 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={checkboxes.loginToAdmin}
                    onChange={() => toggleCheckbox('loginToAdmin')}
                    className="w-4 h-4 rounded border-gray-300"
                  />
                  <span className="text-sm text-gray-700">Login to Admin</span>
                </label>
                <label className="flex items-center gap-2 py-1 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={checkboxes.memberSocialActive}
                    onChange={() => toggleCheckbox('memberSocialActive')}
                    className="w-4 h-4 rounded border-gray-300"
                  />
                  <span className="text-sm text-gray-700">Member Social Active</span>
                </label>
                <label className="flex items-center gap-2 py-1 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={checkboxes.memberLeadsActive}
                    onChange={() => toggleCheckbox('memberLeadsActive')}
                    className="w-4 h-4 rounded border-gray-300"
                  />
                  <span className="text-sm text-gray-700">Member Leads Active</span>
                </label>
              </div>
            </div>
          )}
        </div>

        {/* Action Buttons */}
        <div className="flex justify-end gap-3 mt-6">
          <button
            type="button"
            onClick={() => navigate('/members/list')}
            className="px-6 py-2 border border-purple-600 text-purple-600 rounded font-medium hover:bg-purple-50"
          >
            CANCEL
          </button>
          <button
            type="submit"
            className="btn btn-primary px-6"
          >
            SUBMIT
          </button>
        </div>
      </form>
    </div>
  )
}
