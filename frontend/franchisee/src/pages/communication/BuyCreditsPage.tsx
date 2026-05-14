import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { ShoppingCart, CreditCard, History, CheckCircle } from 'lucide-react'
import { smsCreditsApi } from '../../api/services'
import { useState } from 'react'

export function BuyCreditsPage() {
  const queryClient = useQueryClient()
  const [purchasedPackage, setPurchasedPackage] = useState<string | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: ['sms-credits'],
    queryFn: () => smsCreditsApi.get(),
  })

  const purchaseMutation = useMutation({
    mutationFn: (packageId: string) => smsCreditsApi.purchase(packageId),
    onSuccess: (result, packageId) => {
      queryClient.invalidateQueries({ queryKey: ['sms-credits'] })
      setPurchasedPackage(packageId)
      setTimeout(() => setPurchasedPackage(null), 3000)
    },
  })

  const packages = data?.packages ?? []
  const balance = data?.balance ?? 0

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900 bg-white py-4 shadow-sm -mt-6 -mx-8 mb-6 px-8 text-center sm:text-left">
        Buy Credits
      </h1>

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
                  onClick={() => purchaseMutation.mutate(pkg.id)}
                  disabled={purchaseMutation.isPending}
                  className={`w-full justify-center gap-2 mt-auto ${
                    purchasedPackage === pkg.id
                      ? 'bg-green-600 hover:bg-green-600'
                      : 'bg-blue-600 hover:bg-blue-700'
                  } text-white`}
                >
                  {purchasedPackage === pkg.id ? (
                    <>
                      <CheckCircle className="w-4 h-4" />
                      Purchased!
                    </>
                  ) : purchaseMutation.isPending ? (
                    'Processing...'
                  ) : (
                    <>
                      <ShoppingCart className="w-4 h-4" />
                      Buy Now
                    </>
                  )}
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
