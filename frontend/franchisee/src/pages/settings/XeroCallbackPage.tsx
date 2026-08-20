import { useEffect, useRef, useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { CheckCircle, AlertCircle, Loader2 } from 'lucide-react'
import { xeroApi } from '../../api/services'

type CallbackStatus = 'processing' | 'success' | 'error'

interface CallbackResult {
  type: 'xero_callback'
  success: boolean
  message?: string
  error?: string
  tenant_name?: string
}

export function XeroCallbackPage() {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const hasRun = useRef(false)
  const [status, setStatus] = useState<CallbackStatus>('processing')
  const [message, setMessage] = useState('Connecting to Xero...')

  useEffect(() => {
    if (hasRun.current) return
    hasRun.current = true

    const code = searchParams.get('code')
    const state = searchParams.get('state')
    const error = searchParams.get('error')

    const finish = (result: CallbackResult) => {
      if (window.opener) {
        window.opener.postMessage(result, window.location.origin)
        window.close()
        return
      }

      if (result.success) {
        setStatus('success')
        setMessage(result.tenant_name ? `Connected to Xero: ${result.tenant_name}` : (result.message || 'Connected to Xero'))
      } else {
        setStatus('error')
        setMessage(result.error || 'Failed to connect to Xero')
      }

      setTimeout(() => navigate('/integration/xero', { replace: true }), 2000)
    }

    if (error) {
      finish({ type: 'xero_callback', success: false, error })
      return
    }

    if (!code || !state) {
      finish({ type: 'xero_callback', success: false, error: 'Missing authorization code or state from Xero' })
      return
    }

    xeroApi.callback(code, state)
      .then((result) => finish({
        type: 'xero_callback',
        success: result.success,
        message: result.message,
        error: result.error,
        tenant_name: result.tenant_name,
      }))
      .catch(() => finish({ type: 'xero_callback', success: false, error: 'Failed to complete Xero connection' }))
  }, [searchParams, navigate])

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50">
      <div className="text-center">
        {status === 'processing' && <Loader2 className="w-8 h-8 animate-spin text-blue-600 mx-auto" />}
        {status === 'success' && <CheckCircle className="w-8 h-8 text-green-600 mx-auto" />}
        {status === 'error' && <AlertCircle className="w-8 h-8 text-red-600 mx-auto" />}
        <p className="mt-4 text-gray-600">{message}</p>
        {status === 'processing' && (
          <p className="mt-2 text-sm text-gray-400">This window will close automatically.</p>
        )}
      </div>
    </div>
  )
}
