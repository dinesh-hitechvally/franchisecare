import { CreditCard, Wallet } from 'lucide-react'

export type PaymentMethod = 'card' | 'paypal'

interface PaymentMethodSelectorProps {
  value: PaymentMethod
  onChange: (method: PaymentMethod) => void
  showPaypal: boolean
}

/**
 * Lets the customer pick a payment gateway before any payment action is shown -
 * used on every "review order" screen (SMS credits, each inventory order type).
 */
export function PaymentMethodSelector({ value, onChange, showPaypal }: PaymentMethodSelectorProps) {
  return (
    <div className="w-full max-w-md">
      <p className="text-sm font-medium text-gray-700 mb-3 text-center">Select Payment Method</p>
      <div className={`grid ${showPaypal ? 'grid-cols-2' : 'grid-cols-1'} gap-3`}>
        <button
          type="button"
          onClick={() => onChange('card')}
          className={`flex items-center justify-center gap-2 px-4 py-3 rounded-lg border-2 text-sm font-medium transition-colors ${
            value === 'card'
              ? 'border-blue-600 bg-blue-50 text-blue-700'
              : 'border-gray-200 text-gray-600 hover:border-gray-300'
          }`}
        >
          <CreditCard className="w-4 h-4" />
          MatePay
        </button>

        {showPaypal && (
          <button
            type="button"
            onClick={() => onChange('paypal')}
            className={`flex items-center justify-center gap-2 px-4 py-3 rounded-lg border-2 text-sm font-medium transition-colors ${
              value === 'paypal'
                ? 'border-blue-600 bg-blue-50 text-blue-700'
                : 'border-gray-200 text-gray-600 hover:border-gray-300'
            }`}
          >
            <Wallet className="w-4 h-4" />
            PayPal
          </button>
        )}
      </div>
    </div>
  )
}
