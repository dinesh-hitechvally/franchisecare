import { useEffect, useState } from 'react'

declare global {
  interface Window {
    paypal?: any
  }
}

const SCRIPT_ID = 'paypal-sdk-script'

/**
 * Lazily loads PayPal's JS SDK (for Smart Buttons) once per client id, and reuses
 * an in-flight/loaded script across every component that needs it.
 */
export function usePaypalSdk(clientId: string | undefined, currency = 'AUD') {
  const [isReady, setIsReady] = useState(!!window.paypal)
  const [loadError, setLoadError] = useState<string | null>(null)

  useEffect(() => {
    if (!clientId) return
    if (window.paypal) {
      setIsReady(true)
      return
    }

    // A bad client id still "loads" as a 200/400 script that throws instead of
    // defining window.paypal, so a load event alone doesn't mean the SDK is usable.
    const handleLoad = () => {
      if (window.paypal) {
        setIsReady(true)
      } else {
        setLoadError('PayPal could not be loaded. Please check the PayPal configuration.')
      }
    }
    const handleError = () => {
      setLoadError('PayPal could not be loaded. Please try again later.')
    }

    const existing = document.getElementById(SCRIPT_ID) as HTMLScriptElement | null
    if (existing) {
      existing.addEventListener('load', handleLoad)
      existing.addEventListener('error', handleError)
      return () => {
        existing.removeEventListener('load', handleLoad)
        existing.removeEventListener('error', handleError)
      }
    }

    const script = document.createElement('script')
    script.id = SCRIPT_ID
    script.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(clientId)}&currency=${encodeURIComponent(currency)}`
    script.addEventListener('load', handleLoad)
    script.addEventListener('error', handleError)
    document.body.appendChild(script)
  }, [clientId, currency])

  return { isReady, paypal: isReady ? window.paypal : null, loadError }
}
