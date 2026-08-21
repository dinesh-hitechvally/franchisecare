import { useEffect, useRef } from 'react'
import { usePaypalSdk } from '../../hooks/usePaypalSdk'

interface PaypalPaymentButtonProps {
  clientId: string
  currency?: string
  onCreateOrder: () => Promise<{ transaction_id: number; paypal_order_id: string }>
  onCaptureOrder: (transactionId: number, paypalOrderId: string) => Promise<void>
  onSuccess: () => void
  onError: (message: string) => void
  disabled?: boolean
}

/**
 * Renders PayPal's own Smart Buttons directly on the page (they're their own trigger -
 * PayPal doesn't need to be launched from a separate "Pay" button like the card flow does).
 */
export function PaypalPaymentButton({
  clientId,
  currency = 'AUD',
  onCreateOrder,
  onCaptureOrder,
  onSuccess,
  onError,
  disabled = false,
}: PaypalPaymentButtonProps) {
  const { isReady, paypal, loadError } = usePaypalSdk(clientId, currency)
  const containerRef = useRef<HTMLDivElement>(null)
  const renderedRef = useRef(false)
  const pendingOrderRef = useRef<{ transactionId: number; orderId: string } | null>(null)

  useEffect(() => {
    if (!isReady || !paypal || !containerRef.current || renderedRef.current) return

    const buttons = paypal.Buttons({
      style: { layout: 'horizontal', height: 45, tagline: false },
      createOrder: async () => {
        const result = await onCreateOrder()
        pendingOrderRef.current = { transactionId: result.transaction_id, orderId: result.paypal_order_id }
        return result.paypal_order_id
      },
      onApprove: async () => {
        if (!pendingOrderRef.current) return
        try {
          await onCaptureOrder(pendingOrderRef.current.transactionId, pendingOrderRef.current.orderId)
          onSuccess()
        } catch (err: any) {
          onError(err?.message || 'PayPal payment failed. Please try again.')
        }
      },
      onError: () => {
        onError('PayPal payment failed. Please try again.')
      },
    })

    buttons.render(containerRef.current)
    renderedRef.current = true
  }, [isReady, paypal, onCreateOrder, onCaptureOrder, onSuccess, onError])

  if (loadError) {
    return <p className="text-sm text-red-600 text-center py-2">{loadError}</p>
  }

  return (
    <div className="w-full max-w-xs">
      {!isReady && <p className="text-sm text-gray-400 text-center py-2">Loading PayPal...</p>}
      <div ref={containerRef} className={disabled ? 'opacity-50 pointer-events-none' : ''} />
    </div>
  )
}
