import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { yupResolver } from '@hookform/resolvers/yup'
import * as yup from 'yup'
import { useNavigate } from 'react-router-dom'
import { Eye, EyeOff } from 'lucide-react'
import { useToastStore } from '../../store/toastStore'
import { useAuthStore } from '../../store/authStore'
import { authApi } from '../../api/services'

const changePasswordSchema = yup.object({
  current_password: yup.string().required('Current password is required'),
  new_password: yup.string().min(8, 'New password must be at least 8 characters').required('New password is required'),
  new_password_confirmation: yup.string()
    .oneOf([yup.ref('new_password')], 'Passwords must match')
    .required('Please confirm your new password'),
})

type ChangePasswordForm = yup.InferType<typeof changePasswordSchema>

export function ChangePasswordPage() {
  const navigate = useNavigate()
  const { user } = useAuthStore()
  const addToast = useToastStore((state) => state.addToast)
  const [isLoading, setIsLoading] = useState(false)
  
  // Password Visibility States
  const [showCurrent, setShowCurrent] = useState(false)
  const [showNew, setShowNew] = useState(false)
  const [showConfirm, setShowConfirm] = useState(false)

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors },
  } = useForm<ChangePasswordForm>({
    resolver: yupResolver(changePasswordSchema),
  })

  const onSubmit = async (data: ChangePasswordForm) => {
    const clearToasts = useToastStore.getState().clearToasts
    clearToasts()
    setIsLoading(true)
    try {
      const response: any = await authApi.changePassword(data)
      addToast(response.message || 'Password changed successfully', 'success')
      reset()
      navigate('/dashboard')
    } catch (error: any) {
      console.error('Change password error:', error)
      
      let message = 'Failed to change password. Please try again.'
      if (error.response?.status === 422) {
        const apiErrors = error.response.data.errors
        if (apiErrors) {
          Object.keys(apiErrors).forEach((field) => {
            setError(field as any, { type: 'server', message: apiErrors[field][0] })
          })
          message = error.response.data.message || 'Please check the form for errors.'
        }
      }
      addToast(message, 'error')
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className="flex flex-col gap-6 p-6 min-h-screen bg-[#f4f6f8]">
      {/* Page Title Card */}
      <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
        <h1 className="text-xl font-bold text-gray-800">Change Password</h1>
      </div>

      {/* Form Card Container - Align Left */}
      <div className="flex justify-start">
        <div className="bg-white w-full max-w-lg rounded-lg shadow-sm border border-gray-100 overflow-hidden">
          <form onSubmit={handleSubmit(onSubmit)} className="p-8 space-y-10">
            <h2 className="text-gray-600 text-lg font-medium mb-8">Change your existing Password</h2>

            {/* Read-only Username Field */}
            <div className="space-y-1">
              <label className="text-xs text-secondary-400 font-medium uppercase tracking-wider">Username</label>
              <div className="text-secondary-400 text-sm py-2 border-b border-dotted border-secondary-300">
                {user?.email || 'user@example.com'}
              </div>
            </div>

            {/* Current Password Field */}
            <div className="space-y-1">
              <label className="text-xs text-secondary-500 font-medium uppercase tracking-wider">Current Password</label>
              <div className="relative group">
                <input
                  type={showCurrent ? 'text' : 'password'}
                  {...register('current_password')}
                  className="w-full text-sm py-2 border-b border-secondary-300 outline-none focus:border-primary-500 transition-all bg-primary-50/30 pr-10"
                  placeholder="••••••••"
                />
                <button
                  type="button"
                  onClick={() => setShowCurrent(!showCurrent)}
                  className="absolute right-1 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-primary-600 transition-colors p-1"
                >
                  {showCurrent ? <EyeOff size={18} /> : <Eye size={18} />}
                </button>
              </div>
              {errors.current_password && (
                <p className="text-xs text-error-600 mt-1">{errors.current_password.message}</p>
              )}
            </div>

            {/* New Password Field */}
            <div className="space-y-1">
              <label className="text-xs text-secondary-500 font-medium uppercase tracking-wider">New Password</label>
              <div className="relative group">
                <input
                  type={showNew ? 'text' : 'password'}
                  {...register('new_password')}
                  className="w-full text-sm py-2 border-b border-secondary-300 outline-none focus:border-primary-500 transition-all bg-transparent pr-10"
                  placeholder=""
                />
                <button
                  type="button"
                  onClick={() => setShowNew(!showNew)}
                  className="absolute right-1 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-primary-600 transition-colors p-1"
                >
                  {showNew ? <EyeOff size={18} /> : <Eye size={18} />}
                </button>
              </div>
              {errors.new_password && (
                <p className="text-xs text-error-600 mt-1">{errors.new_password.message}</p>
              )}
            </div>

            {/* Confirm New Password Field */}
            <div className="space-y-1">
              <label className="text-xs text-secondary-500 font-medium uppercase tracking-wider">Confirm New Password</label>
              <div className="relative group">
                <input
                  type={showConfirm ? 'text' : 'password'}
                  {...register('new_password_confirmation')}
                  className="w-full text-sm py-2 border-b border-secondary-300 outline-none focus:border-primary-500 transition-all bg-transparent pr-10"
                  placeholder=""
                />
                <button
                  type="button"
                  onClick={() => setShowConfirm(!showConfirm)}
                  className="absolute right-1 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-primary-600 transition-colors p-1"
                >
                  {showConfirm ? <EyeOff size={18} /> : <Eye size={18} />}
                </button>
              </div>
              {errors.new_password_confirmation && (
                <p className="text-xs text-error-600 mt-1">{errors.new_password_confirmation.message}</p>
              )}
            </div>

            {/* Submit Button */}
            <div className="pt-6">
              <button
                type="submit"
                disabled={isLoading}
                className="w-full bg-[#3f51b5] hover:bg-[#303f9f] text-white py-3 rounded-md font-bold text-sm uppercase tracking-widest transition-all shadow-md active:scale-[0.98] disabled:opacity-70 flex items-center justify-center gap-2"
              >
                {isLoading && (
                  <svg className="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                )}
                Change Password
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}
