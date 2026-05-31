import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Input } from '../../components/ui/Input'
import { Search, Play, Video, Loader2 } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'
import { trainingApi, TrainingVideo } from '../../api/services'

export function MateTrainingVideosPage() {
  const [searchTerm, setSearchTerm] = useState('')

  const { data, isLoading, error } = useQuery({
    queryKey: ['training', 'videos'],
    queryFn: async () => {
      const response = await trainingApi.getVideos()
      return response.data
    },
  })

  const videos = data || []

  const filteredVideos = videos.filter((video: TrainingVideo) =>
    video.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
    video.description.toLowerCase().includes(searchTerm.toLowerCase())
  )

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
          title="Mate Training Videos"
          description="Learn how to use the Mate system effectively"
          icon={<Video className="w-5 h-5" />}
        />
        <Card className="p-8 text-center">
          <p className="text-red-600">Failed to load videos. Please try again later.</p>
        </Card>
      </div>
    )
  }

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
        {filteredVideos.map((video: TrainingVideo) => (
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

      {filteredVideos.length === 0 && !isLoading && (
        <Card className="p-8 text-center">
          <Video className="w-12 h-12 text-gray-400 mx-auto mb-3" />
          <p className="text-gray-600">No videos found matching your search.</p>
        </Card>
      )}
    </div>
  )
}
