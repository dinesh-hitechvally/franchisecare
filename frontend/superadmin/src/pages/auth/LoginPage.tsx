import { useState, FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { Eye, EyeOff } from 'lucide-react'
import toast from 'react-hot-toast'
import { authApi } from '../../api/services'
import { useAuthStore } from '../../store/authStore'

export function LoginPage() {
  const navigate = useNavigate()
  const setAuth = useAuthStore((state) => state.setAuth)
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [showPassword, setShowPassword] = useState(false)
  const [error, setError] = useState('')
  const [isLoading, setIsLoading] = useState(false)

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault()
    setError('')
    setIsLoading(true)
    try {
      const response = await authApi.login(email, password)
      setAuth(response.token, response.user)
      toast.success('Successfully signed in')
      navigate('/dashboard')
    } catch (err: any) {
      let message = 'An unexpected error occurred. Please try again.'
      if (err.response) {
        if (err.response.status === 422 || err.response.status === 401) {
          message = err.response.data?.message || 'The provided credentials are incorrect.'
        } else if (err.response.status >= 500) {
          message = 'Server error. Please contact support if the issue persists.'
        } else {
          message = err.response.data?.message || message
        }
      } else if (err.request) {
        message = 'Cannot connect to the server. Please check your internet connection.'
      }
      setError(message)
      toast.error(message)
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div
      style={{
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: 'linear-gradient(135deg, #7c3aed 0%, #9333ea 100%)',
        padding: '24px',
      }}
    >
      <div className="card" style={{ width: '100%', maxWidth: '400px' }}>
        <div className="card-body" style={{ padding: '32px' }}>
          <h2 style={{ fontSize: '22px', fontWeight: 600, color: '#1f2937', marginBottom: '4px' }}>
            Super Admin Login
          </h2>
          <p style={{ fontSize: '14px', color: '#6b7280', marginBottom: '24px' }}>
            Sign in to access the dashboard
          </p>

          <form onSubmit={onSubmit}>
            <div style={{ marginBottom: '16px' }}>
              <label className="form-label">Email Address</label>
              <input
                type="email"
                className="form-input"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                autoFocus
              />
            </div>

            <div style={{ marginBottom: '8px' }}>
              <label className="form-label">Password</label>
              <div style={{ position: 'relative' }}>
                <input
                  type={showPassword ? 'text' : 'password'}
                  className="form-input"
                  style={{ paddingRight: '40px' }}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  style={{
                    position: 'absolute',
                    right: '12px',
                    top: '50%',
                    transform: 'translateY(-50%)',
                    background: 'none',
                    border: 'none',
                    cursor: 'pointer',
                    color: '#9ca3af',
                    display: 'flex',
                  }}
                >
                  {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                </button>
              </div>
            </div>

            {error && (
              <p className="text-red-500 text-xs mt-1 mb-4">{error}</p>
            )}

            <button
              type="submit"
              className="btn btn-primary"
              disabled={isLoading}
              style={{ width: '100%', justifyContent: 'center', padding: '12px', marginTop: '8px' }}
            >
              {isLoading ? 'Signing in...' : 'Sign In'}
            </button>
          </form>
        </div>
      </div>
    </div>
  )
}
