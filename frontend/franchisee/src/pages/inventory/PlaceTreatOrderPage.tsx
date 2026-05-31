import { useState } from 'react'
import { useQuery, useMutation } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { ShoppingCart, Loader2, ArrowLeft } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'
import { PaymentModal } from '../../components/modals/PaymentModal'
import { inventoryApi, paymentsApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import { useAuthStore } from '../../store/authStore'

type Step = 'select' | 'review'

interface OrderItem {
  productId: string
  productName: string
  productSku: string
  quantity: number
  unitPrice: number
  unit: string
}

export function PlaceTreatOrderPage() {
  const addToast = useToastStore((state) => state.addToast)
  const user = useAuthStore((state) => state.user)
  
  const [step, setStep] = useState<Step>('select')
  const [quantities, setQuantities] = useState<Record<string, number>>({})
  const [orderItems, setOrderItems] = useState<OrderItem[]>([])
  const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false)
  const [paymentError, setPaymentError] = useState<string | null>(null)
  const [createdOrderId, setCreatedOrderId] = useState<number | null>(null)

  // Fetch treats products from inventory_items API
  const { data: products = [], isLoading } = useQuery({
    queryKey: ['inventory-items', 'treats'],
    queryFn: () => inventoryApi.getItems({ category: 'treats' }),
  })

  const totalCost = products.reduce((total, p) => total + (quantities[p.id] || 0) * p.unitPrice, 0)
  
  const handleQuantityChange = (id: string, value: number) => {
    setQuantities(prev => ({
      ...prev,
      [id]: value >= 0 ? value : 0
    }))
  }

  // Create order mutation
  const createOrderMutation = useMutation({
    mutationFn: (items: Array<{ product_name: string; product_sku?: string; inventory_item_id?: string; quantity: number; unit_price: number }>) =>
      inventoryApi.createOrder({ type: 'treats', items }),
    onSuccess: (result) => {
      setCreatedOrderId(Number(result.data.id))
    },
    onError: (error: any) => {
      const errorMessage = error?.response?.data?.message || error?.message || 'Failed to create order'
      addToast(errorMessage, 'error')
    },
  })

  // Payment mutation
  const paymentMutation = useMutation({
    mutationFn: (paymentData: {
      order_id: number
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
    }) => paymentsApi.payInventoryOrder(paymentData),
    onSuccess: (result) => {
      if (result.success) {
        setPaymentError(null)
      } else {
        setPaymentError(result.error || 'Payment failed. Please try again.')
        throw new Error(result.error || 'Payment failed')
      }
    },
    onError: (error: any) => {
      const errorMessage = error?.response?.data?.error || error?.message || 'Payment failed. Please try again.'
      setPaymentError(errorMessage)
      throw error
    },
  })

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

  const handleReviewOrder = () => {
    const itemsWithQuantity = products.filter(p => quantities[p.id] > 0)
    
    if (itemsWithQuantity.length === 0) {
      addToast('Please add at least one item to your order', 'error')
      return
    }

    const items: OrderItem[] = itemsWithQuantity.map(p => ({
      productId: p.id,
      productName: p.name,
      productSku: p.sku || '',
      quantity: quantities[p.id],
      unitPrice: p.unitPrice,
      unit: p.unit || 'unit',
    }))

    setOrderItems(items)
    setStep('review')
  }

  const handleBackToSelect = () => {
    setStep('select')
    setCreatedOrderId(null)
    setPaymentError(null)
  }

  const handlePayClick = async () => {
    setPaymentError(null)

    if (!createdOrderId) {
      const items = orderItems.map(item => ({
        product_name: item.productName,
        product_sku: item.productSku,
        inventory_item_id: item.productId,
        quantity: item.quantity,
        unit_price: item.unitPrice,
      }))

      try {
        const result = await createOrderMutation.mutateAsync(items)
        setCreatedOrderId(Number(result.data.id))
      } catch {
        return
      }
    }

    setIsPaymentModalOpen(true)
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
    if (!createdOrderId) throw new Error('Order not created')

    await paymentMutation.mutateAsync({
      order_id: createdOrderId,
      ...paymentData,
    })
  }

  const handlePaymentSuccess = () => {
    setIsPaymentModalOpen(false)
    setStep('select')
    setQuantities({})
    setOrderItems([])
    setCreatedOrderId(null)
    addToast('Order placed successfully!', 'success')
  }

  // Review Page
  if (step === 'review') {
    const orderTotal = orderItems.reduce((sum, item) => sum + item.quantity * item.unitPrice, 0)

    return (
      <div className="space-y-6">
        <PageHeader
          title="Review Order"
          icon={<ShoppingCart className="w-5 h-5" />}
        />

        <Card className="border border-gray-200 shadow-sm overflow-hidden">
          <div className="bg-gray-900 p-4">
            <h2 className="font-bold text-white uppercase tracking-wider text-sm">Order Summary - Treats</h2>
          </div>

          <div className="divide-y divide-gray-100">
            <div className="grid grid-cols-4 gap-4 px-6 py-3 bg-gray-50 text-sm font-medium text-gray-600">
              <div className="col-span-2">Item</div>
              <div className="text-center">Qty</div>
              <div className="text-right">Total</div>
            </div>

            {orderItems.map((item, index) => (
              <div key={index} className="grid grid-cols-4 gap-4 px-6 py-4 items-center">
                <div className="col-span-2">
                  <p className="font-medium text-gray-900">{item.productName}</p>
                  {item.productSku && <p className="text-sm text-gray-500">{item.productSku}</p>}
                  <p className="text-sm text-gray-500">${item.unitPrice.toFixed(2)} /{item.unit}</p>
                </div>
                <div className="text-center text-gray-700">{item.quantity}</div>
                <div className="text-right font-semibold text-gray-900">
                  ${(item.quantity * item.unitPrice).toFixed(2)}
                </div>
              </div>
            ))}
          </div>

          <div className="border-t border-gray-200 bg-gray-50 p-6">
            <div className="max-w-lg ml-auto border border-gray-200 rounded-md overflow-hidden bg-white">
              <div className="divide-y divide-gray-100">
                <div className="flex justify-between px-4 py-3">
                  <span className="text-sm font-medium text-gray-600">Shipping Cost</span>
                  <span className="text-sm font-semibold text-gray-800">$0.00</span>
                </div>
                <div className="flex justify-between px-4 py-3">
                  <span className="text-sm font-medium text-gray-600">Pick and Pack Order Fee</span>
                  <span className="text-sm font-semibold text-gray-800">$0.00</span>
                </div>
                <div className="flex justify-between px-4 py-3 bg-gray-50 border-t border-gray-200 text-base">
                  <span className="font-bold text-gray-900">Total Order</span>
                  <span className="font-bold text-blue-600">${orderTotal.toFixed(2)}</span>
                </div>
              </div>
            </div>
          </div>

          <div className="p-6 border-t border-gray-200 flex flex-col items-center gap-4">
            <p className="text-sm text-gray-600 text-center">
              Mate Pay accepts Visa, Mastercard credit and Debit cards but does not accept Amex.
            </p>

            <Button
              onClick={handlePayClick}
              disabled={createOrderMutation.isPending}
              className="bg-gray-800 hover:bg-gray-900 text-white px-8 py-3"
            >
              {createOrderMutation.isPending ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin mr-2" />
                  Creating Order...
                </>
              ) : (
                'PAY WITH MATE PAY'
              )}
            </Button>

            <Button
              variant="secondary"
              onClick={handleBackToSelect}
              className="flex items-center gap-2"
            >
              <ArrowLeft className="w-4 h-4" />
              Back to Item Selection
            </Button>
          </div>
        </Card>

        <PaymentModal
          isOpen={isPaymentModalOpen}
          onClose={() => {
            setIsPaymentModalOpen(false)
            setPaymentError(null)
          }}
          amount={orderTotal}
          initialBilling={getUserBillingInfo()}
          onSubmit={handlePaymentSubmit}
          isLoading={paymentMutation.isPending}
          error={paymentError}
          onSuccess={handlePaymentSuccess}
        />
      </div>
    )
  }

  // Selection Page
  return (
    <div className="space-y-6">
      <PageHeader
        title="Place Treat Order"
        icon={<ShoppingCart className="w-5 h-5" />}
      />

      <div className="bg-white p-4 shadow-sm rounded-md border border-gray-200">
        <p className="text-gray-800 text-sm font-medium">All prices are GST included.</p>
      </div>

      <Card className="border border-gray-200 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="bg-gray-900 text-white font-medium">
              <tr>
                <th className="px-6 py-4">Image</th>
                <th className="px-6 py-4">Item Name</th>
                <th className="px-6 py-4 text-center">Order Quantity</th>
                <th className="px-6 py-4 text-right">Cost Per Item</th>
              </tr>
            </thead>
            <tbody>
              {/* Category Divider */}
              <tr className="bg-gray-100 border-b border-gray-200">
                <td colSpan={4} className="px-6 py-2 font-bold text-gray-800 tracking-wider text-xs">
                  Treats - Over 10kg will attract double freight costs (min order of 5KG)
                </td>
              </tr>
              
              {isLoading ? (
                <tr>
                  <td colSpan={4} className="px-6 py-8 text-center">
                    <Loader2 className="w-6 h-6 animate-spin mx-auto text-gray-400" />
                    <p className="mt-2 text-sm text-gray-500">Loading products...</p>
                  </td>
                </tr>
              ) : products.length === 0 ? (
                <tr>
                  <td colSpan={4} className="px-6 py-8 text-center text-gray-500">
                    No treats products found. Add items with category "treats" in Inventory.
                  </td>
                </tr>
              ) : (
                products.map(product => (
                  <tr key={product.id} className="bg-white border-b border-gray-200 hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <div className="w-16 h-16 bg-gray-100 flex items-center justify-center text-xs text-gray-400 border border-gray-200 rounded">
                        Image
                      </div>
                    </td>
                    <td className="px-6 py-4 font-medium text-gray-800">
                      {product.name} {product.sku ? `[${product.sku}]` : ''}
                    </td>
                    <td className="px-6 py-4 text-center">
                      <div className="flex flex-col items-center">
                        <span className="text-xs text-gray-500 mb-1 line-clamp-1 max-w-[120px]" title={product.name}>{product.name}</span>
                        <input 
                          type="number" 
                          min="0"
                          value={quantities[product.id] || 0}
                          onChange={(e) => handleQuantityChange(product.id, parseInt(e.target.value) || 0)}
                          className="w-20 text-center border border-gray-300 rounded px-2 py-1 focus:ring-blue-500 focus:border-blue-500 bg-white"
                        />
                        <span className="text-xs text-gray-500 mt-1">{product.unit || 'units'}</span>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-right text-gray-800 font-medium">
                      ${product.unitPrice.toFixed(2)} /{product.unit || 'unit'}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        <div className="p-6 border-t border-gray-200 bg-white space-y-6">
          {/* Financial Summary */}
          <div className="divide-y divide-gray-200">
            <div className="flex justify-between py-3">
              <span className="text-sm text-gray-700">Shipping Cost:</span>
              <span className="text-sm text-gray-900">$0</span>
            </div>
            <div className="flex justify-between py-3">
              <span className="text-sm text-gray-700">Total Weight:</span>
              <span className="text-sm text-gray-900">0 Kg</span>
            </div>
            <div className="flex justify-between py-3">
              <span className="text-sm text-gray-700">Sub Total</span>
              <span className="text-sm text-gray-900">${totalCost.toFixed(2)}</span>
            </div>
            <div className="flex justify-between py-3">
              <span className="text-sm text-gray-700">Pick and Pack Order Fee</span>
              <span className="text-sm text-gray-900">$0</span>
            </div>
            <div className="flex justify-between py-3">
              <span className="text-sm text-gray-700">SurCharge:</span>
              <span className="text-sm text-gray-900">$0</span>
            </div>
            <div className="flex justify-between py-3">
              <span className="font-bold text-gray-900">Total Order</span>
              <span className="font-bold text-gray-900">${totalCost.toFixed(2)}</span>
            </div>
          </div>

          {/* Action Buttons */}
          <div className="flex justify-end gap-4 pt-4 border-t border-gray-100">
            <Button variant="secondary" className="px-6">
              Calculate Shipping
            </Button>
            <Button onClick={handleReviewOrder} className="px-8 bg-blue-600 hover:bg-blue-700">
              Review Order & Pay
            </Button>
          </div>
        </div>
      </Card>
    </div>
  )
}
