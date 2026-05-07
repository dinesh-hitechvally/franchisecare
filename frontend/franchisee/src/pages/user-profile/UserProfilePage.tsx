import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ArrowLeft, Mail, MapPin, Phone } from 'lucide-react'
import { apiClient } from '../../api/client'
import type { User } from '../../types'

export function UserProfilePage() {
  const { userId } = useParams<{ userId: string }>()
  const navigate = useNavigate()
  const [activeTab, setActiveTab] = useState<'overview' | 'posts' | 'photos'>('overview')

  // Fetch user data
  const { data: user, isLoading } = useQuery({
    queryKey: ['user-profile', userId],
    queryFn: () => apiClient.get<User>(`/users/${userId}`),
    enabled: !!userId,
  })

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-gray-500">Loading...</div>
      </div>
    )
  }

  if (!user) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-gray-500">User not found</div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-[#2d2d2d]">
      {/* Header */}
      <div className="bg-[#2d2d2d] px-6 py-8">
        <button
          onClick={() => navigate(-1)}
          className="text-white hover:text-gray-300 mb-6 inline-flex items-center gap-2"
        >
          <ArrowLeft className="w-5 h-5" />
        </button>

        <div className="flex items-center gap-4">
          <div className="w-20 h-20 rounded-full bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center overflow-hidden">
            {user.avatar ? (
              <img src={user.avatar} alt={user.name} className="w-full h-full object-cover" />
            ) : (
              <div className="w-full h-full flex items-center justify-center bg-gray-600">
                <span className="text-white text-2xl font-bold">
                  {user.name?.charAt(0).toUpperCase() || 'U'}
                </span>
              </div>
            )}
          </div>
          <div>
            <h1 className="text-2xl font-semibold text-white">{user.name}</h1>
            <p className="text-gray-400 text-sm flex items-center gap-1 mt-1">
              <MapPin className="w-4 h-4" />
              {user.location || 'Location not specified'}
            </p>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-6 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Left Column - About */}
          <div className="lg:col-span-2 space-y-6">
            {/* About Section */}
            <div className="bg-white rounded-lg shadow-sm overflow-hidden">
              <div className="border-b border-gray-200">
                <div className="px-6 py-4">
                  <h2 className="text-lg font-semibold text-gray-900">About</h2>
                </div>
                <div className="flex border-t border-gray-200">
                  <button
                    onClick={() => setActiveTab('overview')}
                    className={`flex-1 px-6 py-3 text-sm font-medium transition-colors ${
                      activeTab === 'overview'
                        ? 'text-blue-600 border-b-2 border-blue-600'
                        : 'text-gray-500 hover:text-gray-700'
                    }`}
                  >
                    OVERVIEW
                  </button>
                </div>
              </div>

              {activeTab === 'overview' && (
                <div className="p-6">
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {/* Email */}
                    <div className="flex flex-col items-center text-center">
                      <div className="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center mb-3">
                        <Mail className="w-6 h-6 text-orange-500" />
                      </div>
                      <span className="text-xs font-semibold text-gray-500 mb-1">Email</span>
                      <span className="text-sm text-blue-600 hover:underline cursor-pointer">
                        {user.email || 'Not specified'}
                      </span>
                    </div>

                    {/* Territory */}
                    <div className="flex flex-col items-center text-center">
                      <div className="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center mb-3">
                        <MapPin className="w-6 h-6 text-orange-500" />
                      </div>
                      <span className="text-xs font-semibold text-gray-500 mb-1">Territory</span>
                      <span className="text-sm text-gray-700">
                        {user.location || 'Not specified'}
                      </span>
                    </div>

                    {/* Mobile */}
                    <div className="flex flex-col items-center text-center">
                      <div className="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center mb-3">
                        <Phone className="w-6 h-6 text-orange-500" />
                      </div>
                      <span className="text-xs font-semibold text-gray-500 mb-1">Mobile</span>
                      <span className="text-sm text-gray-700">
                        {user.phone || 'Not specified'}
                      </span>
                    </div>
                  </div>
                </div>
              )}
            </div>

            {/* Timeline Posts */}
            <div className="bg-white rounded-lg shadow-sm p-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-4">Timeline Posts</h2>

              {/* Post Input */}
              <div className="border border-gray-200 rounded-lg overflow-hidden mb-6">
                <div className="flex items-center gap-3 p-4 border-b border-gray-200">
                  <div className="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                    <span className="text-gray-600 text-sm font-semibold">
                      {user.name?.charAt(0).toUpperCase() || 'U'}
                    </span>
                  </div>
                  <input
                    type="text"
                    placeholder="Start a Post..."
                    className="flex-1 outline-none text-sm text-gray-500"
                    readOnly
                  />
                </div>
                <div className="flex items-center justify-between px-4 py-3 bg-gray-50">
                  <button className="text-sm text-gray-600 hover:text-gray-800">
                    Add Photos/Album
                  </button>
                  <button className="px-6 py-2 bg-gray-200 text-gray-400 text-sm font-semibold rounded cursor-not-allowed">
                    POST
                  </button>
                </div>
              </div>

              {/* Posts will be listed here */}
              <div className="text-center text-gray-500 text-sm py-8">
                No posts yet
              </div>
            </div>
          </div>

          {/* Right Column - Photos */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-lg shadow-sm p-6">
              <h3 className="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">
                Photos
              </h3>
              <div className="grid grid-cols-2 gap-3 mb-4">
                {[1, 2, 3, 4, 5, 6].map((i) => (
                  <div
                    key={i}
                    className="aspect-square bg-gray-100 rounded-lg flex items-center justify-center"
                  >
                    <span className="text-gray-300 text-xs">No image</span>
                  </div>
                ))}
              </div>
              <button className="text-blue-600 hover:text-blue-700 text-sm font-medium flex items-center gap-1">
                Go to gallery <span className="text-lg">&rarr;</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
