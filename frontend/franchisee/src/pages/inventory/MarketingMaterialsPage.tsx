import { useState } from 'react'
import { useQuery, useMutation } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { ShoppingCart, Loader2, ArrowLeft } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'
import { PaymentModal } from '../../components/modals/PaymentModal'
import { PaypalPaymentButton } from '../../components/payments/PaypalPaymentButton'
import { PaymentMethodSelector, type PaymentMethod } from '../../components/payments/PaymentMethodSelector'
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
  copies?: string
  copiesAddCost?: number
}

export function MarketingMaterialsPage() {
  const addToast = useToastStore((state) => state.addToast)
  const user = useAuthStore((state) => state.user)
  
  const [step, setStep] = useState<Step>('select')
  const [orders, setOrders] = useState<Record<string, { quantity: number, copies: string }>>({})
  const [orderItems, setOrderItems] = useState<OrderItem[]>([])
  const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false)
  const [paymentError, setPaymentError] = useState<string | null>(null)
  const [createdOrderId, setCreatedOrderId] = useState<number | null>(null)
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod>('card')

  // Fetch marketing products from inventory_items API
  const { data: products = [], isLoading } = useQuery({
    queryKey: ['inventory-items', 'marketing'],
    queryFn: () => inventoryApi.getItems({ category: 'marketing' }),
  })

  const { data: paymentConfig } = useQuery({
    queryKey: ['payments-config'],
    queryFn: () => paymentsApi.getConfig(),
  })

  const copiesOptions = [
    { label: 'Select No of copies*', value: '' },
    { label: '500 copies (+$80)', value: '500', addCost: 80 },
    { label: '1000 copies (+$120)', value: '1000', addCost: 120 },
    { label: '2500 copies (+$200)', value: '2500', addCost: 200 },
  ]

  // Check if product name contains "flyer" (case insensitive) to show copies dropdown
  const hasCopies = (name: string) => name.toLowerCase().includes('flyer')

  const calculateRowTotal = (product: typeof products[0]) => {
    const order = orders[product.id]
    if (!order) return 0
    let cost = product.unitPrice
    if (hasCopies(product.name) && order.copies) {
      const option = copiesOptions.find(o => o.value === order.copies)
      if (option && option.addCost) {
        cost += option.addCost
      }
    }
    return cost * (order.quantity || 0)
  }

  const totalCost = products.reduce((total, p) => total + calculateRowTotal(p), 0)

  const handleQuantityChange = (id: string, value: number) => {
    setOrders(prev => ({
      ...prev,
      [id]: { copies: prev[id]?.copies || '', quantity: value >= 0 ? value : 0 }
    }))
  }

  const handleCopiesChange = (id: string, value: string) => {
    setOrders(prev => ({
      ...prev,
      [id]: { quantity: prev[id]?.quantity || 0, copies: value }
    }))
  }

  // Create order mutation
  const createOrderMutation = useMutation({
    mutationFn: (items: Array<{ product_name: string; product_sku?: string; inventory_item_id?: string; quantity: number; unit_price: number }>) =>
      inventoryApi.createOrder({ type: 'marketing', items }),
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
    const itemsWithQuantity = products.filter(p => {
      const order = orders[p.id]
      return order && order.quantity > 0
    })
    
    if (itemsWithQuantity.length === 0) {
      addToast('Please add at least one item to your order', 'error')
      return
    }

    // Check if flyer products have copies selected
    const missingCopies = itemsWithQuantity.some(p => {
      if (hasCopies(p.name)) {
        const order = orders[p.id]
        return !order?.copies
      }
      return false
    })

    if (missingCopies) {
      addToast('Please select number of copies for flyer items', 'error')
      return
    }

    const items: OrderItem[] = itemsWithQuantity.map(p => {
      const order = orders[p.id]
      const copiesOption = copiesOptions.find(o => o.value === order?.copies)
      return {
        productId: p.id,
        productName: p.name,
        productSku: p.sku || '',
        quantity: order.quantity,
        unitPrice: p.unitPrice,
        copies: order?.copies,
        copiesAddCost: copiesOption?.addCost,
      }
    })

    setOrderItems(items)
    setStep('review')
  }

  const handleBackToSelect = () => {
    setStep('select')
    setCreatedOrderId(null)
    setPaymentError(null)
  }

  // Creates the inventory order on first use (whichever payment method gets there first -
  // card or PayPal), then reuses the same order id for every subsequent attempt.
  const ensureOrderCreated = async (): Promise<number> => {
    if (createdOrderId) return createdOrderId

    const items = orderItems.map(item => {
      const effectivePrice = item.unitPrice + (item.copiesAddCost || 0)
      const productName = item.copies
        ? `${item.productName} (${item.copies} copies)`
        : item.productName
      return {
        product_name: productName,
        product_sku: item.productSku,
        inventory_item_id: item.productId,
        quantity: item.quantity,
        unit_price: effectivePrice,
      }
    })

    const result = await createOrderMutation.mutateAsync(items)
    const orderId = Number(result.data.id)
    setCreatedOrderId(orderId)
    return orderId
  }

  const handlePayClick = async () => {
    setPaymentError(null)

    try {
      await ensureOrderCreated()
    } catch {
      return
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

  const handleCreatePaypalOrder = async () => {
    const orderId = await ensureOrderCreated()

    const result = await paymentsApi.createPaypalOrder({
      type: 'inventory_order',
      order_id: orderId,
    })

    if (!result.success || !result.transaction_id || !result.paypal_order_id) {
      throw new Error(result.error || 'Failed to start PayPal payment')
    }

    return { transaction_id: result.transaction_id, paypal_order_id: result.paypal_order_id }
  }

  const handleCapturePaypalOrder = async (transactionId: number, paypalOrderId: string) => {
    const result = await paymentsApi.capturePaypalOrder({
      transaction_id: transactionId,
      paypal_order_id: paypalOrderId,
    })

    if (!result.success) {
      setPaymentError(result.error || 'PayPal payment failed. Please try again.')
      throw new Error(result.error || 'PayPal payment failed')
    }

    setPaymentError(null)
  }

  const resetOrderState = () => {
    setStep('select')
    setOrders({})
    setOrderItems([])
    setCreatedOrderId(null)
    addToast('Order placed successfully!', 'success')
  }

  const handlePaymentSuccess = () => {
    setIsPaymentModalOpen(false)
    resetOrderState()
  }

  const handlePaypalSuccess = () => {
    resetOrderState()
  }

  // Review Page
  if (step === 'review') {
    const orderTotal = orderItems.reduce((sum, item) => {
      const effectivePrice = item.unitPrice + (item.copiesAddCost || 0)
      return sum + item.quantity * effectivePrice
    }, 0)

    return (
      <div className="space-y-6">
        <PageHeader
          title="Review Order"
          icon={<ShoppingCart className="w-5 h-5" />}
        />

        <Card className="border border-gray-200 shadow-sm overflow-hidden border-t-4 border-t-gray-900">
          <div className="bg-gray-100 p-4 border-b border-gray-200">
            <h2 className="font-bold text-gray-800 uppercase tracking-wider text-sm">Order Summary - Marketing Materials</h2>
          </div>

          <div className="divide-y divide-gray-100">
            <div className="grid grid-cols-5 gap-4 px-6 py-3 bg-gray-50 text-sm font-medium text-gray-600">
              <div className="col-span-2">Item</div>
              <div className="text-center">Copies</div>
              <div className="text-center">Qty</div>
              <div className="text-right">Total</div>
            </div>

            {orderItems.map((item, index) => {
              const effectivePrice = item.unitPrice + (item.copiesAddCost || 0)
              return (
                <div key={index} className="grid grid-cols-5 gap-4 px-6 py-4 items-center">
                  <div className="col-span-2">
                    <p className="font-medium text-gray-900">{item.productName}</p>
                    {item.productSku && <p className="text-sm text-gray-500">{item.productSku}</p>}
                    <p className="text-sm text-gray-500">${effectivePrice.toFixed(2)} each</p>
                  </div>
                  <div className="text-center text-gray-700">{item.copies || '-'}</div>
                  <div className="text-center text-gray-700">{item.quantity}</div>
                  <div className="text-right font-semibold text-gray-900">
                    ${(item.quantity * effectivePrice).toFixed(2)}
                  </div>
                </div>
              )
            })}
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

            <PaymentMethodSelector
              value={paymentMethod}
              onChange={setPaymentMethod}
              showPaypal={!!paymentConfig?.paypal_configured}
            />

            {paymentError && (
              <p className="text-sm text-red-600 text-center">{paymentError}</p>
            )}

            {paymentMethod === 'paypal' && paymentConfig?.paypal_configured ? (
              <PaypalPaymentButton
                clientId={paymentConfig.paypal_client_id}
                onCreateOrder={handleCreatePaypalOrder}
                onCaptureOrder={handleCapturePaypalOrder}
                onSuccess={handlePaypalSuccess}
                onError={setPaymentError}
              />
            ) : (
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
            )}

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
        title="Business Card, Magnet, Flyers"
        icon={<ShoppingCart className="w-5 h-5" />}
      />

      <div className="bg-white p-4 shadow-sm rounded-md border border-gray-200">
        <p className="text-gray-800 text-sm font-medium">All prices are GST included.</p>
      </div>

      <Card className="border border-gray-200 shadow-sm overflow-hidden border-t-4 border-t-gray-900">
        <div className="bg-gray-100 p-4 border-b border-gray-200">
          <h2 className="font-bold text-gray-800 uppercase tracking-wider text-sm">Business Cards, Flyers, Magnets</h2>
        </div>

        {isLoading ? (
          <div className="p-8 text-center">
            <Loader2 className="w-6 h-6 animate-spin mx-auto text-gray-400" />
            <p className="mt-2 text-sm text-gray-500">Loading products...</p>
          </div>
        ) : products.length === 0 ? (
          <div className="p-8 text-center text-gray-500">
            No marketing products found. Add items with category "marketing" in Inventory.
          </div>
        ) : (
          <div className="divide-y divide-gray-100">
            {products.map(product => {
              const currentQty = orders[product.id]?.quantity || 0;
              const currentCopies = orders[product.id]?.copies || '';
              const rowTotal = calculateRowTotal(product);
              const showCopies = hasCopies(product.name);

              return (
                <div key={product.id} className="p-6 bg-white hover:bg-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-6 transition-colors">
                  
                  {/* Item Details */}
                  <div className="flex-1 flex flex-col md:flex-row items-start md:items-center gap-4">
                    <div className="w-16 h-16 bg-gray-100 flex items-center justify-center text-xs text-gray-400 border border-gray-200 rounded shrink-0">
                      Preview
                    </div>
                    <div>
                      <h3 className="font-semibold text-gray-900 text-base mb-1">
                        {product.name} {product.sku ? <span className="text-gray-500 font-normal">[{product.sku}]</span> : ''}
                      </h3>
                    </div>
                  </div>

                  {/* Controls */}
                  <div className="flex flex-wrap md:flex-nowrap items-center gap-6">
                    {/* Quantity */}
                    <div className="flex flex-col">
                      <label className="text-xs text-gray-500 mb-1">Add Quantity</label>
                      <input 
                        type="number" 
                        min="0"
                        value={currentQty}
                        onChange={(e) => handleQuantityChange(product.id, parseInt(e.target.value) || 0)}
                        className="w-24 border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 bg-white text-center"
                      />
                    </div>

                    {/* Copies Dropdown (Conditional) */}
                    {showCopies && (
                      <div className="flex flex-col">
                        <label className="text-xs text-gray-500 mb-1">Select No of copies<span className="text-red-500">*</span></label>
                        <select
                          value={currentCopies}
                          onChange={(e) => handleCopiesChange(product.id, e.target.value)}
                          className={`w-40 border rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 outline-none ${
                            currentQty > 0 && !currentCopies
                              ? 'border-red-500 bg-red-50' 
                              : 'border-gray-300 bg-white'
                          }`}
                        >
                          {copiesOptions.map(opt => (
                            <option key={opt.value} value={opt.value}>{opt.label}</option>
                          ))}
                        </select>
                      </div>
                    )}

                    {/* Row Total */}
                    <div className="w-24 text-right shrink-0">
                      <span className="font-semibold text-gray-900 text-lg">${rowTotal.toFixed(2)}</span>
                    </div>
                  </div>

                </div>
              );
            })}
          </div>
        )}

        <div className="p-6 border-t border-gray-200 bg-gray-50 space-y-6">
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
          <div className="flex justify-end gap-4 pt-4 border-t border-gray-200">
            <Button variant="secondary" className="px-6 bg-white shrink-0">
              Calculate Shipping
            </Button>
            <Button onClick={handleReviewOrder} className="px-8 bg-blue-600 hover:bg-blue-700 shrink-0">
              Review Order & Pay
            </Button>
          </div>
        </div>
      </Card>
    </div>
  )
}
