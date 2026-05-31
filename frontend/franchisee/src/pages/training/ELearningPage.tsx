import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Input } from '../../components/ui/Input'
import { Search, BookOpen, Clock, Award, GraduationCap, Loader2 } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'
import { trainingApi, TrainingCategory, TrainingCourse } from '../../api/services'

export function ELearningPage() {
  const [searchTerm, setSearchTerm] = useState('')

  const { data, isLoading, error } = useQuery({
    queryKey: ['training', 'elearning'],
    queryFn: async () => {
      const response = await trainingApi.getElearning()
      return response.data
    },
  })

  const categories = data || []

  // Filter courses based on search term
  const filteredCategories = categories
    .map((category: TrainingCategory) => ({
      ...category,
      courses: category.courses.filter(
        (course: TrainingCourse) =>
          course.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
          course.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
          category.name.toLowerCase().includes(searchTerm.toLowerCase())
      ),
    }))
    .filter((category: TrainingCategory) => category.courses.length > 0)

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
          title="E-Learning"
          description="Access training courses and educational materials"
          icon={<GraduationCap className="w-5 h-5" />}
        />
        <Card className="p-8 text-center">
          <p className="text-red-600">Failed to load courses. Please try again later.</p>
        </Card>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="E-Learning"
        description="Access training courses and educational materials"
        icon={<GraduationCap className="w-5 h-5" />}
      />

      <div className="flex gap-4 items-center">
        <div className="flex-1 relative">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
          <Input
            type="text"
            placeholder="Search courses..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="pl-10"
          />
        </div>
      </div>

      <div className="grid grid-cols-1 gap-4">
        {filteredCategories.map((category: TrainingCategory) => (
          <div key={category.id} className="space-y-3">
            <h2 className="text-lg font-semibold text-gray-800">{category.name}</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {category.courses.map((course: TrainingCourse) => (
                <Card
                  key={course.id}
                  className="hover:shadow-lg transition-shadow cursor-pointer overflow-hidden"
                >
                  <div className="bg-blue-500 h-40 flex items-center justify-center relative">
                    <div className="text-center">
                      <div className="bg-white rounded-full w-32 h-24 mx-auto flex items-center justify-center mb-2">
                        <span className="text-blue-600 font-bold text-xl">Blue<br/>Wheelers</span>
                      </div>
                      <div className="text-white font-bold text-lg">Wash, Clip & Groom</div>
                    </div>
                    {course.completed && (
                      <div className="absolute top-2 right-2 bg-green-500 rounded-full p-1">
                        <Award className="w-5 h-5 text-white" />
                      </div>
                    )}
                  </div>
                  <div className="p-4">
                    <h3 className="font-semibold text-gray-800 mb-2 line-clamp-2 min-h-[48px]">
                      {course.title}
                    </h3>
                    <p className="text-sm text-gray-600 mb-3 line-clamp-2">
                      {course.description}
                    </p>
                    <div className="flex items-center justify-between text-sm text-gray-500">
                      <div className="flex items-center gap-1">
                        <Clock className="w-4 h-4" />
                        <span>{course.duration}</span>
                      </div>
                      <div className="flex items-center gap-1">
                        <BookOpen className="w-4 h-4" />
                        <span>View Course</span>
                      </div>
                    </div>
                  </div>
                </Card>
              ))}
            </div>
          </div>
        ))}
      </div>

      {filteredCategories.length === 0 && !isLoading && (
        <Card className="p-8 text-center">
          <BookOpen className="w-12 h-12 text-gray-400 mx-auto mb-3" />
          <p className="text-gray-600">No courses found matching your search.</p>
        </Card>
      )}
    </div>
  )
}
