import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { ShoppingCart, CreditCard, History, ArrowLeft } from 'lucide-react'
import { smsCreditsApi, paymentsApi } from '../../api/services'
import { useState } from 'react'
import { PageHeader } from '../../components/layout/PageHeader'
import { PaymentModal } from '../../components/modals/PaymentModal'
import { useToastStore } from '../../store/toastStore'
import { useAuthStore } from '../../store/authStore'

interface Package {
  id: string
  title: string
  price: number
  quantity: number
  rate: number
}

type Step = 'select' | 'review'

export function BuyCreditsPage() {
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)
  const user = useAuthStore((state) => state.user)
  
  const [step, setStep] = useState<Step>('select')
  const [selectedPackage, setSelectedPackage] = useState<Package | null>(null)
  const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false)
  const [paymentError, setPaymentError] = useState<string | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: ['sms-credits'],
    queryFn: () => smsCreditsApi.get(),
  })

  const paymentMutation = useMutation({
    mutationFn: (paymentData: {
      package_id: string
      card_number: string
      expiration_month: string
      expiration_year: string
      cvv: string
      billing: {
        first_name: string
        last_name: string
        address: string
        city: string
        state: string
        postal_code: string
        country: string
        email: string
      }
    }) => paymentsApi.purchaseSmsCredits(paymentData),
    onSuccess: (result) => {
      if (result.success) {
        queryClient.invalidateQueries({ queryKey: ['sms-credits'] })
        setPaymentError(null)
        // Don't close modal - let PaymentModal show success state
        // The modal's onSuccess callback will handle closing and reset
      } else {
        setPaymentError(result.error || 'Payment failed. Please try again.')
        throw new Error(result.error || 'Payment failed')
      }
    },
    onError: (error: any) => {
      const errorMessage = error?.response?.data?.error || error?.message || 'Payment failed. Please try again.'
      setPaymentError(errorMessage)
      throw error // Re-throw so PaymentModal knows payment failed
    },
  })

  const handleSelectPackage = (pkg: Package) => {
    setSelectedPackage(pkg)
    setStep('review')
  }

  const handleBackToSelect = () => {
    setStep('select')
    setSelectedPackage(null)
  }

  const handlePayClick = () => {
    setPaymentError(null)
    setIsPaymentModalOpen(true)
  }

  // Get user billing info from authenticated user
  const getUserBillingInfo = () => {
    const nameParts = (user?.name || '').split(' ')
    const firstName = nameParts[0] || ''
    const lastName = nameParts.slice(1).join(' ') || ''
    const locationParts = (user?.location || '').split(',').map(s => s.trim())
    
    return {
      first_name: firstName,
      last_name: lastName,
      address: locationParts[0] || '',
      city: locationParts[1] || '',
      state: locationParts[2] || '',
      postal_code: '',
      country: 'AU',
      email: user?.email || '',
    }
  }

  const handlePaymentSubmit = async (paymentData: {
    card_number: string
    expiration_month: string
    expiration_year: string
    cvv: string
    billing: {
      first_name: string
      last_name: string
      address: string
      city: string
      state: string
      postal_code: string
      country: string
      email: string
    }
  }) => {
    if (!selectedPackage) return

    await paymentMutation.mutateAsync({
      package_id: selectedPackage.id,
      ...paymentData,
    })
  }

  const packages = data?.packages ?? []
  const balance = data?.balance ?? 0

  // Review Page
  if (step === 'review' && selectedPackage) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Review Order"
          icon={<CreditCard className="w-5 h-5" />}
        />

        <Card className="border border-gray-200 shadow-sm overflow-hidden">
          <table className="w-full text-sm">
            <tbody className="divide-y divide-gray-200">
              <tr>
                <td className="px-6 py-4 text-gray-600 font-medium">Credits For</td>
                <td className="px-6 py-4 text-gray-900">SMS</td>
              </tr>
              <tr>
                <td className="px-6 py-4 text-gray-600 font-medium">Rate</td>
                <td className="px-6 py-4 text-gray-900">${selectedPackage.rate.toFixed(2)}/SMS</td>
              </tr>
              <tr>
                <td className="px-6 py-4 text-gray-600 font-medium">Quantity</td>
                <td className="px-6 py-4 text-gray-900">{selectedPackage.quantity} SMS</td>
              </tr>
              <tr>
                <td className="px-6 py-4 text-gray-600 font-medium">Total:</td>
                <td className="px-6 py-4 text-gray-900 font-semibold">${selectedPackage.price.toFixed(2)}</td>
              </tr>
            </tbody>
          </table>

          <div className="px-6 py-4 border-t border-gray-200 flex flex-col items-center gap-4">
            <p className="text-sm text-gray-600 text-center">
              Mate Pay accepts Visa, Mastercard credit and Debit cards but does not accept Amex.
            </p>

            <Button
              onClick={handlePayClick}
              className="bg-gray-800 hover:bg-gray-900 text-white px-8 py-3"
            >
              PAY WITH MATE PAY
            </Button>

            <Button
              variant="secondary"
              onClick={handleBackToSelect}
              className="flex items-center gap-2"
            >
              <ArrowLeft className="w-4 h-4" />
              Back to Package Selection
            </Button>
          </div>
        </Card>

        {/* Payment Modal */}
        <PaymentModal
          isOpen={isPaymentModalOpen}
          onClose={() => {
            setIsPaymentModalOpen(false)
            setPaymentError(null)
          }}
          amount={selectedPackage.price}
          initialBilling={getUserBillingInfo()}
          onSubmit={handlePaymentSubmit}
          isLoading={paymentMutation.isPending}
          error={paymentError}
          onSuccess={() => {
            setIsPaymentModalOpen(false)
            setStep('select')
            setSelectedPackage(null)
            addToast('SMS credits purchased successfully!', 'success')
          }}
        />
      </div>
    )
  }

  // Package Selection Page
  return (
    <div className="space-y-6">
      <PageHeader
        title="Buy Credits"
        icon={<CreditCard className="w-5 h-5" />}
      />

      {/* Current Balance */}
      <Card className="p-6 border border-gray-200 shadow-sm bg-gradient-to-r from-blue-50 to-indigo-50">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm font-medium text-gray-600">Current SMS Balance</p>
            <p className="text-3xl font-bold text-blue-600">{balance.toLocaleString()} <span className="text-lg font-normal text-gray-500">credits</span></p>
          </div>
          <CreditCard className="w-12 h-12 text-blue-400" />
        </div>
      </Card>

      <Card className="p-6 border border-gray-200 shadow-sm">
        <p className="text-sm text-gray-700 leading-relaxed mb-4">
          If you want to send booking notifications via SMS message to all your customers, you need to buy SMS credits. You can either send SMS manually or allow system to send SMS automatically on your behalf. If you run out of credits during automated processing, you'll be notified via Email and SMS to topup your sms credits.
        </p>
        <p className="text-sm text-gray-700 leading-relaxed">
          The SMS rates shown, apply for messages up to 160 characters. If your SMS exceeds 160 characters, it will be counted as multiple messages, and the rate will be applied per SMS segment accordingly.
        </p>
      </Card>

      {isLoading ? (
        <div className="text-center py-12 text-gray-400">Loading packages...</div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {packages.map((pkg) => (
            <div key={pkg.id} className="bg-white border border-gray-200 rounded-md overflow-hidden shadow-sm flex flex-col">
              <div className="bg-gray-100 p-8 flex items-center justify-center border-b border-gray-200 h-32">
                <span className="text-blue-600 font-medium text-center">{pkg.title}</span>
              </div>
              <div className="p-6 flex flex-col items-center flex-1">
                <h3 className="font-bold text-gray-900 text-base mb-4 text-center">{pkg.title}</h3>
                <div className="text-center space-y-1 mb-8">
                  <p className="text-sm text-gray-600">Rate: <span className="font-medium text-gray-800">${pkg.rate.toFixed(2)}/Sms</span></p>
                  <p className="text-sm text-gray-600">SMS Quantity: <span className="font-medium text-gray-800">{pkg.quantity}</span></p>
                  <p className="text-lg font-bold text-green-600 mt-2">${pkg.price.toFixed(2)}</p>
                </div>
                <Button
                  onClick={() => handleSelectPackage(pkg)}
                  className="w-full justify-center gap-2 mt-auto bg-blue-600 hover:bg-blue-700 text-white"
                >
                  <ShoppingCart className="w-4 h-4" />
                  Buy Now
                </Button>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Purchase History Link */}
      <Card className="p-4 border border-gray-200 shadow-sm">
        <div className="flex items-center gap-3 text-gray-600">
          <History className="w-5 h-5" />
          <span className="text-sm">Purchase history is available in your account settings.</span>
        </div>
      </Card>
    </div>
  )
}
