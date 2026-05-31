import { useEffect } from 'react'
import { useSearchParams } from 'react-router-dom'
import { Loader2 } from 'lucide-react'

export function XeroCallbackPage() {
  const [searchParams] = useSearchParams()

  useEffect(() => {
    const code = searchParams.get('code')
    const state = searchParams.get('state')
    const error = searchParams.get('error')

    if (error) {
      // Send error to opener and close
      if (window.opener) {
        window.opener.postMessage({ type: 'xero_callback', error }, window.location.origin)
      }
      window.close()
      return
    }

    if (code && state) {
      // Send code and state to opener
      if (window.opener) {
        window.opener.postMessage({ type: 'xero_callback', code, state }, window.location.origin)
      }
      // Close will happen after parent processes the callback
    }
  }, [searchParams])

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50">
      <div className="text-center">
        <Loader2 className="w-8 h-8 animate-spin text-blue-600 mx-auto" />
        <p className="mt-4 text-gray-600">Connecting to Xero...</p>
        <p className="mt-2 text-sm text-gray-400">This window will close automatically.</p>
      </div>
    </div>
  )
}
