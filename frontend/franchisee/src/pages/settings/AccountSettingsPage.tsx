import { useState, useRef } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, Settings, User, Upload } from 'lucide-react'
import { useAuthStore } from '../../store/authStore'
import { useToastStore } from '../../store/toastStore'
import { apiClient } from '../../api/client'

interface UserProfile {
  id: number
  first_name: string
  last_name: string
  email: string
  phone: string
  address1: string
  address2: string
  suburb: string
  avatar: string | null
}

export function AccountSettingsPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const user = useAuthStore((state) => state.user)
  const setUser = useAuthStore((state) => state.setUser)
  const addToast = useToastStore((state) => state.addToast)
  const fileInputRef = useRef<HTMLInputElement>(null)
  
  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    address1: '',
    address2: '',
    suburb: '',
  })
  const [avatarPreview, setAvatarPreview] = useState<string | null>(null)
  const [avatarFile, setAvatarFile] = useState<File | null>(null)

  // Fetch user profile
  const { isLoading } = useQuery({
    queryKey: ['user-profile'],
    queryFn: async () => {
      const response = await apiClient.get('/user')
      const userData = response.data
      setFormData({
        first_name: userData.first_name || userData.name?.split(' ')[0] || '',
        last_name: userData.last_name || userData.name?.split(' ')[1] || '',
        email: userData.email || '',
        phone: userData.phone || '',
        address1: userData.address1 || '',
        address2: userData.address2 || '',
        suburb: userData.suburb || '',
      })
      if (userData.avatar) {
        setAvatarPreview(userData.avatar)
      }
      return userData
    },
  })

  // Update profile mutation
  const updateProfileMutation = useMutation({
    mutationFn: async (data: typeof formData) => {
      const formDataToSend = new FormData()
      Object.entries(data).forEach(([key, value]) => {
        formDataToSend.append(key, value)
      })
      if (avatarFile) {
        formDataToSend.append('avatar', avatarFile)
      }
      const response = await apiClient.post('/user/profile', formDataToSend, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      return response.data
    },
    onSuccess: (data) => {
      addToast({ type: 'success', message: 'Profile updated successfully!' })
      queryClient.invalidateQueries({ queryKey: ['user-profile'] })
      if (data.user) {
        setUser(data.user)
      }
    },
    onError: () => {
      addToast({ type: 'error', message: 'Failed to update profile' })
    },
  })

  const handleInputChange = (field: string, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }))
  }

  const handleImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) {
      setAvatarFile(file)
      const reader = new FileReader()
      reader.onloadend = () => {
        setAvatarPreview(reader.result as string)
      }
      reader.readAsDataURL(file)
    }
  }

  const handleResetImage = () => {
    setAvatarPreview(null)
    setAvatarFile(null)
    if (fileInputRef.current) {
      fileInputRef.current.value = ''
    }
  }

  const handleSave = () => {
    updateProfileMutation.mutate(formData)
  }

  const handleCancel = () => {
    navigate(-1)
  }

  return (
    <div className="min-h-[calc(100vh-64px)] bg-gray-50">
      {/* Back Button */}
      <div className="px-6 py-4">
        <button
          onClick={() => navigate(-1)}
          className="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors shadow-sm"
        >
          <ArrowLeft className="w-4 h-4" />
          BACK
        </button>
      </div>

      {/* Main Content */}
      <div className="px-6 pb-8">
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          {/* Header */}
          <div className="px-6 py-4 border-b border-gray-100">
            <div className="flex items-center gap-3">
              <h1 className="text-xl font-semibold text-gray-800">Account Settings</h1>
              <Settings className="w-5 h-5 text-gray-400" />
            </div>
          </div>

          {/* Form Content */}
          <div className="p-6">
            {isLoading ? (
              <div className="flex justify-center py-12">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
              </div>
            ) : (
              <div className="flex flex-col lg:flex-row gap-8">
                {/* Avatar Section */}
                <div className="flex flex-col items-center">
                  <div 
                    className="w-40 h-48 border-2 border-dashed border-blue-300 rounded-lg flex items-center justify-center bg-blue-50/50 cursor-pointer hover:bg-blue-50 transition-colors overflow-hidden"
                    onClick={() => fileInputRef.current?.click()}
                  >
                    {avatarPreview ? (
                      <img 
                        src={avatarPreview} 
                        alt="Profile" 
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <User className="w-20 h-20 text-blue-200" />
                    )}
                  </div>
                  <input
                    ref={fileInputRef}
                    type="file"
                    accept="image/*"
                    className="hidden"
                    onChange={handleImageChange}
                  />
                  <button
                    onClick={handleResetImage}
                    className="mt-4 px-4 py-2 bg-red-700 text-white text-sm font-medium rounded-md hover:bg-red-800 transition-colors"
                  >
                    RESET IMAGE
                  </button>
                </div>

                {/* Form Fields */}
                <div className="flex-1 space-y-5">
                  {/* Row 1: First Name & Last Name */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                      <label className="block text-sm text-gray-600 mb-1.5">FirstName</label>
                      <input
                        type="text"
                        value={formData.first_name}
                        onChange={(e) => handleInputChange('first_name', e.target.value)}
                        className="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="First Name"
                      />
                    </div>
                    <div>
                      <label className="block text-sm text-gray-600 mb-1.5">LastName</label>
                      <input
                        type="text"
                        value={formData.last_name}
                        onChange={(e) => handleInputChange('last_name', e.target.value)}
                        className="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="Last Name"
                      />
                    </div>
                  </div>

                  {/* Row 2: Address1 & Address2 */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                      <label className="block text-sm text-gray-600 mb-1.5">Address1</label>
                      <input
                        type="text"
                        value={formData.address1}
                        onChange={(e) => handleInputChange('address1', e.target.value)}
                        className="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="Address Line 1"
                      />
                    </div>
                    <div>
                      <label className="block text-sm text-gray-600 mb-1.5">Address2</label>
                      <input
                        type="text"
                        value={formData.address2}
                        onChange={(e) => handleInputChange('address2', e.target.value)}
                        className="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="Address Line 2"
                      />
                    </div>
                  </div>

                  {/* Row 3: Suburb & Email */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                      <label className="block text-sm text-gray-600 mb-1.5">Suburb</label>
                      <input
                        type="text"
                        value={formData.suburb}
                        onChange={(e) => handleInputChange('suburb', e.target.value)}
                        className="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="Suburb"
                      />
                    </div>
                    <div>
                      <label className="block text-sm text-gray-600 mb-1.5">Email</label>
                      <input
                        type="email"
                        value={formData.email}
                        onChange={(e) => handleInputChange('email', e.target.value)}
                        className="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50"
                        placeholder="Email"
                        readOnly
                      />
                    </div>
                  </div>

                  {/* Row 4: Phone Number */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                      <label className="block text-sm text-gray-600 mb-1.5">Phone Number</label>
                      <input
                        type="tel"
                        value={formData.phone}
                        onChange={(e) => handleInputChange('phone', e.target.value)}
                        className="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="Phone Number"
                      />
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Footer Buttons */}
          <div className="px-6 py-4 border-t border-gray-100 flex justify-center gap-3">
            <button
              onClick={handleSave}
              disabled={updateProfileMutation.isPending}
              className="px-6 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {updateProfileMutation.isPending ? 'Saving...' : 'Save'}
            </button>
            <button
              onClick={handleCancel}
              className="px-6 py-2.5 bg-gray-700 text-white font-medium rounded-lg hover:bg-gray-800 transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
