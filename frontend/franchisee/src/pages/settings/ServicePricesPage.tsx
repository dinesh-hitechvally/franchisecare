import { useState, useEffect } from 'react'
import { useMutation } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Save, DollarSign, Clock } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'
import { companyServicesApi, CompanyService } from '../../api/services'
import toast from 'react-hot-toast'

export function ServicePricesPage() {
  const [prices, setPrices] = useState<CompanyService[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const fetchPrices = async () => {
      try {
        const data = await companyServicesApi.getAll()
        setPrices(data)
      } catch (error) {
        console.error('Error fetching service prices:', error)
        toast.error('Failed to load service prices')
      } finally {
        setLoading(false)
      }
    }

    fetchPrices()
  }, [])

  const saveMutation = useMutation({
    mutationFn: async (data: CompanyService[]) => {
      return await companyServicesApi.updateAll(data)
    },
    onSuccess: () => {
      toast.success('Service prices updated successfully')
    },
    onError: () => {
      toast.error('Failed to update service prices')
    },
  })

  const handlePriceChange = (service_id: number, field: 'my_price' | 'my_time', value: number) => {
    setPrices(prev =>
      prev.map(item =>
        item.service_id === service_id ? { ...item, [field]: value } : item
      )
    )
  }

  const handleColorChange = (service_id: number, color: string) => {
    setPrices(prev =>
      prev.map(item =>
        item.service_id === service_id ? { ...item, color } : item
      )
    )
  }

  const handleSave = () => {
    saveMutation.mutate(prices)
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-gray-600">Loading service prices...</div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Service Prices"
        description="Manage pricing and duration for services"
        icon={<DollarSign className="w-5 h-5" />}
      />

      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="bg-gray-900 text-white">
                <th className="text-left px-4 py-3 font-semibold">Service Name</th>
                <th className="text-left px-4 py-3 font-semibold">My Price ($)</th>
                <th className="text-left px-4 py-3 font-semibold">Default Price ($)</th>
                <th className="text-left px-4 py-3 font-semibold">Service Colour</th>
                <th className="text-left px-4 py-3 font-semibold">My Time (min)</th>
                <th className="text-left px-4 py-3 font-semibold">Default Time (min)</th>
              </tr>
            </thead>
            <tbody>
              {prices.map((service, index) => (
                <tr
                  key={service.service_id}
                  className={`border-b border-gray-200 hover:bg-gray-50 ${
                    index % 2 === 0 ? 'bg-white' : 'bg-gray-50'
                  }`}
                >
                  <td className="px-4 py-3 text-gray-800">{service.name}</td>
                  <td className="px-4 py-3">
                    <Input
                      type="number"
                      value={Number(service.my_price)}
                      onChange={(e) => handlePriceChange(service.service_id, 'my_price', parseFloat(e.target.value) || 0)}
                      step="0.01"
                      min="0"
                      className="w-24"
                    />
                  </td>
                  <td className="px-4 py-3 text-gray-600">${Number(service.default_price).toFixed(2)}</td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <input
                        type="color"
                        value={service.color}
                        onChange={(e) => handleColorChange(service.service_id, e.target.value)}
                        className="w-10 h-8 rounded cursor-pointer border border-gray-300"
                      />
                      <div
                        className="w-16 h-6 rounded"
                        style={{ backgroundColor: service.color }}
                      />
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <Input
                      type="number"
                      value={Number(service.my_time)}
                      onChange={(e) => handlePriceChange(service.service_id, 'my_time', parseInt(e.target.value) || 0)}
                      min="0"
                      className="w-20"
                    />
                  </td>
                  <td className="px-4 py-3 text-gray-600">{Number(service.default_time)} min</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="px-4 py-3 bg-gray-50 border-t border-gray-200">
          <p className="text-xs text-gray-600 mb-3">
            Changing Service colour do not update on existing Calendar entries. They are only updated from future events
          </p>
          <Button
            onClick={handleSave}
            disabled={saveMutation.isPending}
            className="flex items-center gap-2"
          >
            <Save className="w-4 h-4" />
            {saveMutation.isPending ? 'Saving...' : 'Save'}
          </Button>
        </div>
      </Card>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <Card className="p-4 bg-blue-50 border-blue-200">
          <div className="flex items-start gap-3">
            <div className="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center flex-shrink-0">
              <DollarSign className="w-5 h-5 text-white" />
            </div>
            <div>
              <h3 className="font-semibold text-gray-800 mb-1">Pricing Tips</h3>
              <p className="text-sm text-gray-600">
                Set competitive prices based on your location and target market. Consider offering package deals for regular customers.
              </p>
            </div>
          </div>
        </Card>

        <Card className="p-4 bg-green-50 border-green-200">
          <div className="flex items-start gap-3">
            <div className="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center flex-shrink-0">
              <Clock className="w-5 h-5 text-white" />
            </div>
            <div>
              <h3 className="font-semibold text-gray-800 mb-1">Time Management</h3>
              <p className="text-sm text-gray-600">
                Accurate service duration helps with scheduling and managing customer expectations. Adjust times based on your experience.
              </p>
            </div>
          </div>
        </Card>
      </div>
    </div>
  )
}
