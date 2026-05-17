import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Input } from '../../components/ui/Input'
import { Search, Play, Video } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'

interface TrainingVideo {
  id: string
  title: string
  description: string
  thumbnail: string
  duration: string
}

export function MateTrainingVideosPage() {
  const [searchTerm, setSearchTerm] = useState('')

  const videos: TrainingVideo[] = [
    {
      id: '1',
      title: 'Mate - Intro',
      description: 'Introduction to the Mate system and overview of features',
      thumbnail: '/images/videos/mate-intro.jpg',
      duration: '5:30',
    },
    {
      id: '2',
      title: 'Mate - New Booking',
      description: 'How to create and manage new bookings in the Mate system',
      thumbnail: '/images/videos/mate-new-booking.jpg',
      duration: '8:45',
    },
    {
      id: '3',
      title: 'Mate - New Customer',
      description: 'Adding and managing new customers and their pet profiles',
      thumbnail: '/images/videos/mate-new-customer.jpg',
      duration: '7:20',
    },
    {
      id: '4',
      title: 'Mate - Adjusting preferences settings',
      description: 'Customizing your Mate system preferences and settings',
      thumbnail: '/images/videos/mate-settings.jpg',
      duration: '6:15',
    },
    {
      id: '5',
      title: 'Mate - Download and Read Blues News',
      description: 'Accessing and reading company news and updates',
      thumbnail: '/images/videos/mate-news.jpg',
      duration: '4:30',
    },
    {
      id: '6',
      title: 'Mate - Setting Up and Adjusting Two-Factor Authentication',
      description: 'Security setup and configuration for two-factor authentication',
      thumbnail: '/images/videos/mate-2fa.jpg',
      duration: '5:45',
    },
    {
      id: '7',
      title: 'Mate - How to Place Your Orders on Mate',
      description: 'Ordering supplies and inventory through the Mate system',
      thumbnail: '/images/videos/mate-orders.jpg',
      duration: '9:10',
    },
    {
      id: '8',
      title: 'Mate - Using online waivers',
      description: 'Managing customer waivers and digital signatures',
      thumbnail: '/images/videos/mate-waivers.jpg',
      duration: '6:40',
    },
    {
      id: '9',
      title: 'Mate - Sending a customer a tax invoice / receipt',
      description: 'Generating and sending invoices and receipts to customers',
      thumbnail: '/images/videos/mate-invoices.jpg',
      duration: '7:55',
    },
    {
      id: '10',
      title: 'Mate - How to complete your bookings',
      description: 'Processing and finalizing completed bookings',
      thumbnail: '/images/videos/mate-complete-bookings.jpg',
      duration: '8:20',
    },
  ]

  const filteredVideos = videos.filter((video) =>
    video.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
    video.description.toLowerCase().includes(searchTerm.toLowerCase())
  )

  return (
    <div className="space-y-6">
      <PageHeader
        title="Mate Training Videos"
        description="Learn how to use the Mate system effectively"
        icon={<Video className="w-5 h-5" />}
      />

      <div className="flex gap-4 items-center">
        <div className="flex-1 relative">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
          <Input
            type="text"
            placeholder="Search videos..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="pl-10"
          />
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {filteredVideos.map((video) => (
          <Card
            key={video.id}
            className="hover:shadow-lg transition-shadow cursor-pointer overflow-hidden group"
          >
            <div className="relative bg-gradient-to-br from-blue-600 to-blue-800 h-48 flex items-center justify-center">
              <div className="absolute inset-0 flex items-center justify-center">
                <div className="text-center text-white p-6">
                  <Video className="w-16 h-16 mx-auto mb-2 opacity-50" />
                  <p className="text-sm font-medium">{video.title}</p>
                </div>
              </div>
              <div className="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all flex items-center justify-center">
                <div className="w-16 h-16 bg-white bg-opacity-90 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                  <Play className="w-8 h-8 text-blue-600 ml-1" />
                </div>
              </div>
              <div className="absolute bottom-2 right-2 bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded">
                {video.duration}
              </div>
            </div>
            <div className="p-4">
              <h3 className="font-semibold text-gray-800 mb-2">
                {video.title}
              </h3>
              <p className="text-sm text-gray-600 line-clamp-2">
                {video.description}
              </p>
            </div>
          </Card>
        ))}
      </div>

      {filteredVideos.length === 0 && (
        <Card className="p-8 text-center">
          <Video className="w-12 h-12 text-gray-400 mx-auto mb-3" />
          <p className="text-gray-600">No videos found matching your search.</p>
        </Card>
      )}
    </div>
  )
}
