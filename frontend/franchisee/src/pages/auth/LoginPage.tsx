import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { yupResolver } from '@hookform/resolvers/yup'
import * as yup from 'yup'
import { useNavigate } from 'react-router-dom'
import { Eye, EyeOff } from 'lucide-react'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { useAuthStore } from '../../store/authStore'
import { useToastStore } from '../../store/toastStore'
import { authApi } from '../../api/services'

const loginSchema = yup.object({
  email: yup.string().email('Invalid email').required('Email is required'),
  password: yup.string().required('Password is required'),
})

type LoginForm = yup.InferType<typeof loginSchema>

export function LoginPage() {
  const navigate = useNavigate()
  const setAuth = useAuthStore((state) => state.setAuth)
  const addToast = useToastStore((state) => state.addToast)
  const [showPassword, setShowPassword] = useState(false)
  const [isLoading, setIsLoading] = useState(false)

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<LoginForm>({
    resolver: yupResolver(loginSchema),
  })

  const onSubmit = async (data: LoginForm) => {
    const clearToasts = useToastStore.getState().clearToasts
    clearToasts()
    setIsLoading(true)
    try {
      const response = await authApi.login(data.email, data.password)
      
      setAuth(response.access_token, response.user)
      addToast('Successfully signed in', 'success')
      navigate('/dashboard')
    } catch (error: any) {
      console.error('Login error:', error)
      
      let message = 'An unexpected error occurred. Please try again.'
      
      if (error.response) {
        if (error.response.status === 422) {
          const apiErrors = error.response.data.errors
          if (apiErrors) {
            // Map backend validation errors to fields
            Object.keys(apiErrors).forEach((field) => {
              setError(field as any, {
                type: 'server',
                message: apiErrors[field][0]
              })
            })
            message = error.response.data.message || 'Please check the form for errors.'
          } else {
            message = 'The provided credentials are incorrect.'
            setError('email', { type: 'server', message })
          }
        } else if (error.response.status === 401) {
          message = 'The provided credentials are incorrect.'
          setError('email', { type: 'server', message })
        } else if (error.response.status >= 500) {
          message = 'Server error. Please contact support if the issue persists.'
        } else {
          message = error.response.data.message || message
        }
      } else if (error.request) {
        message = 'Cannot connect to the server. Please check your internet connection.'
      }
      
      addToast(message, 'error')
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-primary-50 to-secondary-100 flex items-center justify-center p-4">
      <div className="w-full max-w-4xl bg-white rounded-2xl shadow-xl overflow-hidden flex">
        {/* Form Side */}
        <div className="flex-1 p-8 lg:p-12">
          <div className="max-w-sm mx-auto">
            <h2 className="text-2xl font-bold text-secondary-900 mb-2">Welcome Back</h2>
            <p className="text-secondary-500 mb-8">Sign in to access your dashboard</p>

            <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
              <Input
                label="Email Address"
                type="email"
                {...register('email')}
                error={errors.email?.message}
              />

              <div className="space-y-1.5">
                <label className="text-sm font-medium text-secondary-700">Password</label>
                <div className="relative">
                  <input
                    type={showPassword ? 'text' : 'password'}
                    {...register('password')}
                    className="input pr-10"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600"
                  >
                    {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                  </button>
                </div>
                {errors.password && (
                  <p className="text-sm text-red-600">{errors.password.message}</p>
                )}
              </div>

              <div className="flex items-center justify-between text-sm">
                <a href="#" className="text-primary-600 hover:text-primary-700">
                  Forgot password?
                </a>
              </div>

              <Button type="submit" isLoading={isLoading} className="w-full">
                Sign In
              </Button>
            </form>

            <p className="mt-6 text-xs text-secondary-400 text-center">
              Need help? Contact support at support@dogwash.com
            </p>
          </div>
        </div>

        {/* Brand Side */}
        <div className="hidden lg:flex flex-1 bg-primary-600 items-center justify-center p-12">
          <div className="text-center text-white">
            <div className="w-20 h-20 bg-white/20 rounded-2xl mx-auto mb-6 flex items-center justify-center">
              <span className="text-4xl font-bold">DW</span>
            </div>
            <h1 className="text-3xl font-bold mb-2">Dog Wash System</h1>
            <p className="text-primary-100">Complete franchise management solution</p>
          </div>
        </div>
      </div>

      <div className="fixed bottom-4 left-0 right-0 text-center text-xs text-secondary-400">
        Version 1.0.0 | © 2024 Dog Wash System
      </div>
    </div>
  )
}
