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
  size: string
  unitPrice: number
}

export function PlaceUniformOrderPage() {
  const addToast = useToastStore((state) => state.addToast)
  const user = useAuthStore((state) => state.user)
  
  const [step, setStep] = useState<Step>('select')
  const [orders, setOrders] = useState<Record<string, { quantity: number, size: string }>>({})
  const [orderItems, setOrderItems] = useState<OrderItem[]>([])
  const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false)
  const [paymentError, setPaymentError] = useState<string | null>(null)
  const [createdOrderId, setCreatedOrderId] = useState<number | null>(null)

  // Fetch uniforms products from inventory_items API
  const { data: products = [], isLoading } = useQuery({
    queryKey: ['inventory-items', 'uniforms'],
    queryFn: () => inventoryApi.getItems({ category: 'uniforms' }),
  })

  const totalCost = products.reduce((total, p) => {
    const order = orders[p.id]
    return total + (order?.quantity || 0) * p.unitPrice
  }, 0)

  const sizes = ['Select Size', 'XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL']
  
  const handleQuantityChange = (id: string, value: number) => {
    setOrders(prev => ({
      ...prev,
      [id]: { size: prev[id]?.size || '', quantity: value >= 0 ? value : 0 }
    }))
  }

  const handleSizeChange = (id: string, value: string) => {
    setOrders(prev => ({
      ...prev,
      [id]: { quantity: prev[id]?.quantity || 0, size: value }
    }))
  }

  // Create order mutation
  const createOrderMutation = useMutation({
    mutationFn: (items: Array<{ product_name: string; product_sku?: string; inventory_item_id?: string; quantity: number; unit_price: number }>) =>
      inventoryApi.createOrder({ type: 'uniforms', items }),
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
        // Don't close modal - let PaymentModal show success state
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

  // Validate order items have sizes selected
  const validateOrder = () => {
    const itemsWithQuantity = products.filter(p => {
      const order = orders[p.id]
      return order && order.quantity > 0
    })
    
    const missingSize = itemsWithQuantity.some(p => {
      const order = orders[p.id]
      return !order?.size || order.size === ''
    })

    if (itemsWithQuantity.length === 0) {
      addToast('Please add at least one item to your order', 'error')
      return false
    }

    if (missingSize) {
      addToast('Please select a size for all items', 'error')
      return false
    }

    return true
  }

  // Handle review order click
  const handleReviewOrder = () => {
    if (!validateOrder()) return

    // Collect order items
    const items: OrderItem[] = products
      .filter(p => orders[p.id]?.quantity > 0)
      .map(p => ({
        productId: p.id,
        productName: p.name,
        productSku: p.sku || '',
        quantity: orders[p.id].quantity,
        size: orders[p.id].size,
        unitPrice: p.unitPrice,
      }))

    setOrderItems(items)
    setStep('review')
  }

  // Handle back to selection
  const handleBackToSelect = () => {
    setStep('select')
    setCreatedOrderId(null)
    setPaymentError(null)
  }

  // Handle pay click - create order first if not exists
  const handlePayClick = async () => {
    setPaymentError(null)

    // Create order if not already created
    if (!createdOrderId) {
      const items = orderItems.map(item => ({
        product_name: `${item.productName} (Size: ${item.size})`,
        product_sku: item.productSku,
        inventory_item_id: item.productId,
        quantity: item.quantity,
        unit_price: item.unitPrice,
      }))

      try {
        const result = await createOrderMutation.mutateAsync(items)
        setCreatedOrderId(Number(result.data.id))
      } catch {
        return // Error handled in mutation
      }
    }

    setIsPaymentModalOpen(true)
  }

  // Handle payment submit
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
    if (!createdOrderId) {
      throw new Error('Order not created')
    }

    await paymentMutation.mutateAsync({
      order_id: createdOrderId,
      ...paymentData,
    })
  }

  // Handle payment success
  const handlePaymentSuccess = () => {
    setIsPaymentModalOpen(false)
    setStep('select')
    setOrders({})
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
          variant="compact"
        />

        <Card className="border border-gray-200 shadow-sm overflow-hidden border-t-4 border-t-gray-900">
          <div className="bg-gray-100 p-4 border-b border-gray-200">
            <h2 className="font-bold text-gray-800 uppercase tracking-wider text-sm">Order Summary</h2>
          </div>

          <div className="divide-y divide-gray-100">
            {/* Header Row */}
            <div className="grid grid-cols-5 gap-4 px-6 py-3 bg-gray-50 text-sm font-medium text-gray-600">
              <div className="col-span-2">Item</div>
              <div className="text-center">Size</div>
              <div className="text-center">Qty</div>
              <div className="text-right">Total</div>
            </div>

            {/* Order Items */}
            {orderItems.map((item, index) => (
              <div key={index} className="grid grid-cols-5 gap-4 px-6 py-4 items-center">
                <div className="col-span-2">
                  <p className="font-medium text-gray-900">{item.productName}</p>
                  {item.productSku && <p className="text-sm text-gray-500">{item.productSku}</p>}
                  <p className="text-sm text-gray-500">${item.unitPrice.toFixed(2)} each</p>
                </div>
                <div className="text-center text-gray-700">{item.size}</div>
                <div className="text-center text-gray-700">{item.quantity}</div>
                <div className="text-right font-semibold text-gray-900">
                  ${(item.quantity * item.unitPrice).toFixed(2)}
                </div>
              </div>
            ))}
          </div>

          {/* Financial Summary */}
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

          {/* Payment Section */}
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

        {/* Payment Modal */}
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
        title="Uniform Order"
        icon={<ShoppingCart className="w-5 h-5" />}
        variant="compact"
      />

      <div className="bg-white p-4 shadow-sm rounded-md border border-gray-200">
        <p className="text-gray-800 text-sm font-medium">All prices are GST included.</p>
      </div>

      <Card className="border border-gray-200 shadow-sm overflow-hidden border-t-4 border-t-gray-900">
        <div className="bg-gray-100 p-4 border-b border-gray-200">
          <h2 className="font-bold text-gray-800 uppercase tracking-wider text-sm">Uniform</h2>
        </div>

        {isLoading ? (
          <div className="p-8 text-center">
            <Loader2 className="w-6 h-6 animate-spin mx-auto text-gray-400" />
            <p className="mt-2 text-sm text-gray-500">Loading products...</p>
          </div>
        ) : products.length === 0 ? (
          <div className="p-8 text-center text-gray-500">
            No uniform products found. Add items with category "uniforms" in Inventory.
          </div>
        ) : (
          <div className="divide-y divide-gray-100">
            {products.map(product => {
              const currentQty = orders[product.id]?.quantity || 0;
              const currentSize = orders[product.id]?.size || '';
              const rowTotal = currentQty * product.unitPrice;

              return (
                <div key={product.id} className="p-6 bg-white hover:bg-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-6 transition-colors">
                  
                  {/* Item Details */}
                  <div className="flex-1">
                    <h3 className="font-semibold text-gray-900 text-base mb-1">
                      {product.name} - ${product.unitPrice.toFixed(2)} {product.sku ? <span className="text-gray-500 font-normal">[{product.sku}]</span> : ''}
                    </h3>
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

                    {/* Size Dropdown */}
                    <div className="flex flex-col">
                      <label className="text-xs text-gray-500 mb-1">Select Size<span className="text-red-500">*</span></label>
                      <select
                        value={currentSize}
                        onChange={(e) => handleSizeChange(product.id, e.target.value)}
                        className={`w-32 border rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 outline-none ${
                          currentQty > 0 && !currentSize && currentSize !== 'Select Size' 
                            ? 'border-red-500 bg-red-50' 
                            : 'border-gray-300 bg-white'
                        }`}
                      >
                        {sizes.map(size => (
                          <option key={size} value={size === 'Select Size' ? '' : size}>{size}</option>
                        ))}
                      </select>
                    </div>

                    {/* Row Total */}
                    <div className="w-24 text-right">
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
            <Button 
              onClick={handleReviewOrder}
              className="px-8 bg-blue-600 hover:bg-blue-700 shrink-0"
            >
              Review Order & Pay
            </Button>
          </div>
        </div>
      </Card>
    </div>
  )
}
