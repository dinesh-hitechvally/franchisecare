import { useState, useCallback, useEffect } from 'react'
import { Modal } from '../ui/Modal'
import { Button } from '../ui/Button'
import { AlertCircle, CheckCircle } from 'lucide-react'

interface BillingInfo {
  first_name: string
  last_name: string
  address: string
  city: string
  state: string
  postal_code: string
  country: string
  email: string
}

interface PaymentModalProps {
  isOpen: boolean
  onClose: () => void
  amount: number
  currency?: string
  initialBilling?: Partial<BillingInfo>
  onSubmit: (paymentData: {
    card_number: string
    expiration_month: string
    expiration_year: string
    cvv: string
    billing: BillingInfo
  }) => Promise<void>
  isLoading?: boolean
  error?: string | null
  onSuccess?: () => void
}

export function PaymentModal({
  isOpen,
  onClose,
  amount,
  currency = 'AUD',
  initialBilling,
  onSubmit,
  isLoading = false,
  error = null,
  onSuccess,
}: PaymentModalProps) {
  const [cardNumber, setCardNumber] = useState('')
  const [expirationMonth, setExpirationMonth] = useState('')
  const [expirationYear, setExpirationYear] = useState('')
  const [cvv, setCvv] = useState('')
  const [showSuccess, setShowSuccess] = useState(false)
  const [billing, setBilling] = useState<BillingInfo>({
    first_name: '',
    last_name: '',
    address: '',
    city: '',
    state: '',
    postal_code: '',
    country: 'AU',
    email: '',
  })

  // Pre-fill billing info when modal opens
  useEffect(() => {
    if (isOpen && initialBilling) {
      setBilling({
        first_name: initialBilling.first_name || '',
        last_name: initialBilling.last_name || '',
        address: initialBilling.address || '',
        city: initialBilling.city || '',
        state: initialBilling.state || '',
        postal_code: initialBilling.postal_code || '',
        country: initialBilling.country || 'AU',
        email: initialBilling.email || '',
      })
    }
    // Reset success state when modal opens
    if (isOpen) {
      setShowSuccess(false)
    }
  }, [isOpen, initialBilling])

  const handleCardNumberChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value.replace(/\D/g, '').slice(0, 16)
    setCardNumber(value)
  }

  const handleMonthChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value.replace(/\D/g, '').slice(0, 2)
    if (value === '' || (parseInt(value) >= 0 && parseInt(value) <= 12)) {
      setExpirationMonth(value)
    }
  }

  const handleYearChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value.replace(/\D/g, '').slice(0, 2)
    setExpirationYear(value)
  }

  const handleCvvChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value.replace(/\D/g, '').slice(0, 4)
    setCvv(value)
  }

  const handleSubmit = useCallback(async () => {
    try {
      // Convert 2-digit year to 4-digit
      const fullYear = expirationYear.length === 2 ? `20${expirationYear}` : expirationYear

      await onSubmit({
        card_number: cardNumber,
        expiration_month: expirationMonth.padStart(2, '0'),
        expiration_year: fullYear,
        cvv,
        billing,
      })

      // Show success modal
      setShowSuccess(true)
    } catch (err) {
      // Error is handled by parent via error prop
    }
  }, [cardNumber, expirationMonth, expirationYear, cvv, billing, onSubmit])

  const isFormValid = () => {
    return (
      cardNumber.length >= 13 &&
      cardNumber.length <= 16 &&
      expirationMonth.length === 2 &&
      expirationYear.length === 2 &&
      cvv.length >= 3 &&
      billing.first_name.trim() !== '' &&
      billing.email.includes('@')
    )
  }

  const handleClose = () => {
    // Reset all fields
    setCardNumber('')
    setExpirationMonth('')
    setExpirationYear('')
    setCvv('')
    setShowSuccess(false)
    setBilling({
      first_name: initialBilling?.first_name || '',
      last_name: initialBilling?.last_name || '',
      address: initialBilling?.address || '',
      city: initialBilling?.city || '',
      state: initialBilling?.state || '',
      postal_code: initialBilling?.postal_code || '',
      country: initialBilling?.country || 'AU',
      email: initialBilling?.email || '',
    })
    onClose()
  }

  const handleSuccessClose = () => {
    handleClose()
    onSuccess?.()
  }

  // Success Modal
  if (showSuccess) {
    return (
      <Modal
        isOpen={isOpen}
        onClose={handleSuccessClose}
        title=""
        size="sm"
      >
        <div className="py-8 text-center">
          <div className="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-6">
            <CheckCircle className="w-10 h-10 text-green-600" />
          </div>
          <h2 className="text-2xl font-bold text-gray-900 mb-2">Thank You!</h2>
          <p className="text-gray-600 mb-6">Your payment has been processed successfully.</p>
          <p className="text-lg font-semibold text-green-600 mb-6">
            Amount Paid: ${amount.toFixed(2)} {currency}
          </p>
          <Button
            onClick={handleSuccessClose}
            className="bg-green-600 hover:bg-green-700 text-white px-8 py-2"
          >
            Done
          </Button>
        </div>
      </Modal>
    )
  }

  // Payment Modal
  return (
    <Modal
      isOpen={isOpen}
      onClose={handleClose}
      title="Mate Payment"
      size="md"
    >
      <div className="py-4">
        {/* Header */}
        <div className="text-center mb-8">
          <h3 className="text-xl font-semibold text-gray-900">Enter your Mate Pay details</h3>
        </div>

        {/* Error Message */}
        {error && (
          <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 flex items-start gap-3">
            <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
            <div>
              <p className="text-sm font-medium text-red-800">Payment Failed</p>
              <p className="text-sm text-red-600 mt-1">{error}</p>
            </div>
          </div>
        )}

        {/* Card Form */}
        <div className="space-y-5 max-w-md mx-auto">
          {/* Card Number */}
          <div>
            <input
              type="text"
              placeholder="Card Number *"
              value={cardNumber}
              onChange={handleCardNumberChange}
              maxLength={16}
              className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 placeholder-gray-500"
              autoComplete="cc-number"
            />
          </div>

          {/* Expiration */}
          <div>
            <label className="block text-sm text-gray-700 mb-2">Expiration (MM/YY) *</label>
            <div className="grid grid-cols-2 gap-4">
              <input
                type="text"
                placeholder="MM *"
                value={expirationMonth}
                onChange={handleMonthChange}
                maxLength={2}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 placeholder-gray-500"
                autoComplete="cc-exp-month"
              />
              <input
                type="text"
                placeholder="YY *"
                value={expirationYear}
                onChange={handleYearChange}
                maxLength={2}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 placeholder-gray-500"
                autoComplete="cc-exp-year"
              />
            </div>
          </div>

          {/* CVV */}
          <div>
            <input
              type="password"
              placeholder="Card Security Code (CVV) *"
              value={cvv}
              onChange={handleCvvChange}
              maxLength={4}
              className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 placeholder-gray-500"
              autoComplete="cc-csc"
            />
          </div>
        </div>

        {/* Footer */}
        <div className="mt-8 flex items-center justify-between max-w-md mx-auto">
          <p className="text-xs text-gray-500 flex-1">
            *Your Mate Pay details are not saved anywhere
          </p>
          <Button
            onClick={handleSubmit}
            disabled={!isFormValid() || isLoading}
            className="bg-gray-200 hover:bg-gray-800 hover:text-white text-gray-700 px-8 py-2 rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isLoading ? 'Processing...' : 'PAY'}
          </Button>
        </div>
      </div>
    </Modal>
  )
}
