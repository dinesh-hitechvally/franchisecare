import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { ExternalLink, MapPin, Users, FileText, Megaphone, Loader2 } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'
import { trainingApi, MarketingCategory, MarketingItem } from '../../api/services'

export function PhysicalMarketingIdeasPage() {
  const { data, isLoading, error } = useQuery({
    queryKey: ['training', 'marketing'],
    queryFn: async () => {
      const response = await trainingApi.getMarketing()
      return response.data
    },
  })

  const categories = data || []

  // Find specific categories
  const physicalMarketing = categories.find((c: MarketingCategory) => c.slug === 'physical-marketing-ideas')
  const googleBusiness = categories.find((c: MarketingCategory) => c.slug === 'google-my-business')
  const facebookMarketing = categories.find((c: MarketingCategory) => c.slug === 'facebook-groups')

  // Find featured marketing course
  const featuredCourse = facebookMarketing?.items?.find((item: MarketingItem) => item.instructor && item.highlights)

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <Loader2 className="w-8 h-8 animate-spin text-blue-500" />
      </div>
    )
  }

  if (error) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Physical Marketing Ideas"
          description="Marketing strategies and resources to grow your business"
          icon={<Megaphone className="w-5 h-5" />}
        />
        <Card className="p-8 text-center">
          <p className="text-red-600">Failed to load marketing resources. Please try again later.</p>
        </Card>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Physical Marketing Ideas"
        description="Marketing strategies and resources to grow your business"
        icon={<Megaphone className="w-5 h-5" />}
      />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Physical Marketing Column */}
        <Card className="p-6">
          <h2 className="text-lg font-semibold text-gray-800 mb-4">Physical Marketing Ideas</h2>
          <div className="space-y-4">
            <div className="bg-blue-50 p-4 rounded-lg">
              <h3 className="font-medium text-gray-800 mb-2">Support for your business</h3>
              <p className="text-sm text-gray-600">ARA - Australian Retailers Association</p>
            </div>

            {physicalMarketing?.items?.map((item: MarketingItem) => (
              <div key={item.id} className="border-l-4 border-orange-400 pl-4 py-2">
                <div className="flex items-start gap-3">
                  <FileText className="w-5 h-5 text-orange-500 mt-1 flex-shrink-0" />
                  <div>
                    <h3 className="font-medium text-gray-800 mb-1">{item.title}</h3>
                    <p className="text-sm text-gray-600">{item.description}</p>
                  </div>
                </div>
              </div>
            ))}

            <div className="mt-6">
              <h3 className="font-medium text-gray-800 mb-3">Marketing Materials Available</h3>
              <div className="space-y-2 text-sm">
                <div className="flex items-center gap-2 text-gray-700">
                  <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                  <span>Business Cards</span>
                </div>
                <div className="flex items-center gap-2 text-gray-700">
                  <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                  <span>Fridge Magnets</span>
                </div>
                <div className="flex items-center gap-2 text-gray-700">
                  <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                  <span>Flyers & Brochures</span>
                </div>
                <div className="flex items-center gap-2 text-gray-700">
                  <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                  <span>Window Decals</span>
                </div>
              </div>
            </div>
          </div>
        </Card>

        {/* Google My Business Column */}
        <Card className="p-6">
          <div className="flex items-center gap-2 mb-4">
            <MapPin className="w-5 h-5 text-blue-600" />
            <h2 className="text-lg font-semibold text-gray-800">Google My Business</h2>
          </div>

          <div className="bg-blue-50 p-4 rounded-lg mb-4">
            <img
              src="/images/google-my-business-logo.png"
              alt="Google My Business"
              className="w-full h-32 object-contain mb-2"
              onError={(e) => {
                e.currentTarget.style.display = 'none'
              }}
            />
            <p className="text-sm text-gray-600 text-center">
              Manage your business presence across Google Search and Maps
            </p>
          </div>

          <div className="space-y-4">
            {googleBusiness?.items?.map((item: MarketingItem) => (
              <div key={item.id} className="border-b border-gray-200 pb-3 last:border-0">
                <h3 className="font-medium text-gray-800 mb-1 text-sm">{item.title}</h3>
                <p className="text-xs text-gray-600">{item.description}</p>
              </div>
            ))}
          </div>

          <a
            href="https://business.google.com"
            target="_blank"
            rel="noopener noreferrer"
            className="mt-4 flex items-center justify-center gap-2 w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition-colors text-sm"
          >
            <ExternalLink className="w-4 h-4" />
            <span>Manage Your Business</span>
          </a>
        </Card>

        {/* Facebook Groups Column */}
        <Card className="p-6">
          <div className="flex items-center gap-2 mb-4">
            <Users className="w-5 h-5 text-blue-600" />
            <h2 className="text-lg font-semibold text-gray-800">Facebook Groups</h2>
          </div>

          <div className="bg-blue-50 p-4 rounded-lg mb-4">
            <div className="flex items-center gap-3 mb-2">
              <div className="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold">
                f
              </div>
              <div>
                <p className="font-medium text-gray-800">Friends' groups</p>
                <p className="text-xs text-gray-600">Manage your friends are in</p>
              </div>
            </div>
            <p className="text-sm text-gray-600">
              Connect with local pet communities and dog owners
            </p>
          </div>

          <div className="space-y-4">
            {facebookMarketing?.items?.filter((item: MarketingItem) => !item.instructor).map((item: MarketingItem) => (
              <div key={item.id} className="border-b border-gray-200 pb-3 last:border-0">
                <h3 className="font-medium text-gray-800 mb-1 text-sm">{item.title}</h3>
                <p className="text-xs text-gray-600">{item.description}</p>
              </div>
            ))}
          </div>

          <div className="mt-4 bg-gradient-to-r from-purple-50 to-blue-50 p-3 rounded-lg">
            <p className="text-xs text-gray-700">
              <strong>Suggested groups:</strong> Australian mobile groomers, Melbourne Plant Buy Swap, and local pet communities
            </p>
          </div>
        </Card>
      </div>

      {/* Marketing Course Section */}
      {featuredCourse && (
        <Card className="p-6">
          <div className="flex flex-col md:flex-row gap-6">
            <div className="md:w-1/3">
              <div className="bg-gradient-to-br from-blue-600 to-blue-800 rounded-lg p-6 text-white">
                <h3 className="font-bold text-xl mb-2">Marketing and business</h3>
                <div className="aspect-video bg-white/10 rounded mt-4 flex items-center justify-center">
                  <Users className="w-16 h-16 opacity-50" />
                </div>
              </div>
            </div>
            <div className="md:w-2/3">
              <h2 className="text-xl font-bold text-gray-800 mb-2">{featuredCourse.title}</h2>
              <p className="text-sm text-blue-600 mb-3">{featuredCourse.instructor}</p>
              <p className="text-gray-700 mb-4">{featuredCourse.description}</p>
              {featuredCourse.highlights && featuredCourse.highlights.length > 0 && (
                <div className="space-y-2">
                  <p className="font-medium text-gray-800 text-sm">What you'll expect:</p>
                  <ul className="space-y-1">
                    {featuredCourse.highlights.map((highlight: string, index: number) => (
                      <li key={index} className="text-sm text-gray-600 flex items-start gap-2">
                        <span className="text-blue-500 mt-1">•</span>
                        <span>{highlight}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          </div>
        </Card>
      )}
    </div>
  )
}
