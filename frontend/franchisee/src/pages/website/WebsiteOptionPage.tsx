import { useState, useEffect } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Globe, ExternalLink, Save, CheckCircle } from 'lucide-react'
import { websiteSettingsApi, WebsiteSettings } from '../../api/services'
import { PageHeader } from '../../components/layout/PageHeader'

export function WebsiteOptionPage() {
  const queryClient = useQueryClient()
  const [saved, setSaved] = useState(false)
  const [formData, setFormData] = useState<WebsiteSettings>({
    site_title: '',
    tagline: '',
    contact_email: '',
    enable_online_booking: true,
    show_pricing: true,
    website_url: '',
    meta_title: '',
    meta_keywords: '',
    meta_description: '',
  })

  const { data, isLoading } = useQuery({
    queryKey: ['website-settings'],
    queryFn: () => websiteSettingsApi.get(),
  })

  useEffect(() => {
    if (data) {
      setFormData(data)
    }
  }, [data])

  const saveMutation = useMutation({
    mutationFn: (data: Partial<WebsiteSettings>) => websiteSettingsApi.update(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['website-settings'] })
      setSaved(true)
      setTimeout(() => setSaved(false), 3000)
    },
  })

  const handleChange = (field: keyof WebsiteSettings, value: string | boolean) => {
    setFormData(prev => ({ ...prev, [field]: value }))
  }

  const handleSave = () => {
    saveMutation.mutate(formData)
  }

  const handleVisitWebsite = () => {
    if (formData.website_url) {
      window.open(formData.website_url, '_blank')
    }
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <p className="text-gray-400">Loading settings...</p>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Website Options"
        description="Configure website settings and SEO"
        icon={<Globe className="w-5 h-5" />}
      />
      
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card title="Website Settings" className="p-6">
          <div className="space-y-4">
            <div>
              <label className="text-sm font-medium text-secondary-700">Site Title</label>
              <input
                type="text"
                value={formData.site_title}
                onChange={(e) => handleChange('site_title', e.target.value)}
                className="input mt-1 w-full"
                placeholder="Your Business Name"
              />
            </div>
            <div>
              <label className="text-sm font-medium text-secondary-700">Tagline</label>
              <input
                type="text"
                value={formData.tagline}
                onChange={(e) => handleChange('tagline', e.target.value)}
                className="input mt-1 w-full"
                placeholder="Professional Pet Grooming Services"
              />
            </div>
            <div>
              <label className="text-sm font-medium text-secondary-700">Contact Email</label>
              <input
                type="email"
                value={formData.contact_email}
                onChange={(e) => handleChange('contact_email', e.target.value)}
                className="input mt-1 w-full"
                placeholder="info@example.com"
              />
            </div>
            <div className="flex items-center justify-between pt-2">
              <span className="text-sm text-secondary-700">Enable Online Booking</span>
              <input
                type="checkbox"
                checked={formData.enable_online_booking}
                onChange={(e) => handleChange('enable_online_booking', e.target.checked)}
                className="w-5 h-5 text-primary-600"
              />
            </div>
            <div className="flex items-center justify-between">
              <span className="text-sm text-secondary-700">Show Pricing</span>
              <input
                type="checkbox"
                checked={formData.show_pricing}
                onChange={(e) => handleChange('show_pricing', e.target.checked)}
                className="w-5 h-5 text-primary-600"
              />
            </div>
            <Button
              onClick={handleSave}
              disabled={saveMutation.isPending}
              className={`w-full ${saved ? 'bg-green-600 hover:bg-green-600' : ''}`}
            >
              {saved ? (
                <>
                  <CheckCircle className="w-4 h-4 mr-2" />
                  Saved!
                </>
              ) : saveMutation.isPending ? (
                'Saving...'
              ) : (
                <>
                  <Save className="w-4 h-4 mr-2" />
                  Save Settings
                </>
              )}
            </Button>
          </div>
        </Card>
        
        <Card title="Live Preview" className="p-6">
          <div className="aspect-video bg-secondary-100 rounded-lg flex items-center justify-center mb-4">
            <Globe className="w-16 h-16 text-secondary-400" />
          </div>
          <div className="space-y-2">
            <p className="font-medium">Website URL</p>
            <div className="flex gap-2">
              <input
                type="text"
                value={formData.website_url}
                onChange={(e) => handleChange('website_url', e.target.value)}
                className="input flex-1"
                placeholder="https://yoursite.com"
              />
              <Button
                variant="secondary"
                onClick={handleVisitWebsite}
                disabled={!formData.website_url}
              >
                <ExternalLink className="w-4 h-4" />
              </Button>
            </div>
            <Button
              onClick={handleVisitWebsite}
              disabled={!formData.website_url}
              className="w-full mt-4"
            >
              Visit Website
            </Button>
          </div>
        </Card>
      </div>
      
      <Card title="SEO Settings" className="p-6">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="text-sm font-medium text-secondary-700">Meta Title</label>
            <input
              type="text"
              value={formData.meta_title}
              onChange={(e) => handleChange('meta_title', e.target.value)}
              className="input mt-1 w-full"
              placeholder="Page title for search engines"
            />
          </div>
          <div>
            <label className="text-sm font-medium text-secondary-700">Meta Keywords</label>
            <input
              type="text"
              value={formData.meta_keywords}
              onChange={(e) => handleChange('meta_keywords', e.target.value)}
              className="input mt-1 w-full"
              placeholder="dog grooming, pet wash, pet care"
            />
          </div>
          <div className="md:col-span-2">
            <label className="text-sm font-medium text-secondary-700">Meta Description</label>
            <textarea
              value={formData.meta_description}
              onChange={(e) => handleChange('meta_description', e.target.value)}
              className="input mt-1 w-full"
              rows={3}
              placeholder="Brief description for search engine results"
            />
          </div>
          <div className="md:col-span-2">
            <Button
              onClick={handleSave}
              disabled={saveMutation.isPending}
              className={saved ? 'bg-green-600 hover:bg-green-600' : ''}
            >
              {saved ? (
                <>
                  <CheckCircle className="w-4 h-4 mr-2" />
                  SEO Settings Saved!
                </>
              ) : (
                <>
                  <Save className="w-4 h-4 mr-2" />
                  Save SEO Settings
                </>
              )}
            </Button>
          </div>
        </div>
      </Card>
    </div>
  )
}
