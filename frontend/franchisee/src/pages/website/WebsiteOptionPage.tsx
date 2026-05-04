import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Globe, ExternalLink } from 'lucide-react'

export function WebsiteOptionPage() {
  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-secondary-900">Website Options</h1>
      
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card title="Website Settings" className="p-6">
          <div className="space-y-4">
            <div>
              <label className="text-sm font-medium text-secondary-700">Site Title</label>
              <input type="text" defaultValue="Dog Wash Pro" className="input mt-1 w-full" />
            </div>
            <div>
              <label className="text-sm font-medium text-secondary-700">Tagline</label>
              <input type="text" defaultValue="Professional Pet Grooming Services" className="input mt-1 w-full" />
            </div>
            <div>
              <label className="text-sm font-medium text-secondary-700">Contact Email</label>
              <input type="email" defaultValue="info@dogwashpro.com" className="input mt-1 w-full" />
            </div>
            <div className="flex items-center justify-between pt-2">
              <span className="text-sm text-secondary-700">Enable Online Booking</span>
              <input type="checkbox" defaultChecked className="w-5 h-5 text-primary-600" />
            </div>
            <div className="flex items-center justify-between">
              <span className="text-sm text-secondary-700">Show Pricing</span>
              <input type="checkbox" defaultChecked className="w-5 h-5 text-primary-600" />
            </div>
            <Button className="w-full">Save Settings</Button>
          </div>
        </Card>
        
        <Card title="Live Preview" className="p-6">
          <div className="aspect-video bg-secondary-100 rounded-lg flex items-center justify-center mb-4">
            <Globe className="w-16 h-16 text-secondary-400" />
          </div>
          <div className="space-y-2">
            <p className="font-medium">Website URL</p>
            <div className="flex gap-2">
              <input type="text" defaultValue="https://dogwashpro.com" className="input flex-1" readOnly />
              <Button variant="secondary">
                <ExternalLink className="w-4 h-4" />
              </Button>
            </div>
            <Button className="w-full mt-4">Visit Website</Button>
          </div>
        </Card>
      </div>
      
      <Card title="SEO Settings" className="p-6">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="text-sm font-medium text-secondary-700">Meta Title</label>
            <input type="text" defaultValue="Dog Wash Pro - Professional Pet Grooming" className="input mt-1 w-full" />
          </div>
          <div>
            <label className="text-sm font-medium text-secondary-700">Meta Keywords</label>
            <input type="text" defaultValue="dog grooming, pet wash, dog bath, pet care" className="input mt-1 w-full" />
          </div>
          <div className="md:col-span-2">
            <label className="text-sm font-medium text-secondary-700">Meta Description</label>
            <textarea className="input mt-1 w-full" rows={3} defaultValue="Professional dog grooming and washing services. Book your appointment online today!" />
          </div>
        </div>
      </Card>
    </div>
  )
}
