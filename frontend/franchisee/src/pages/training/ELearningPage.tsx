import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Input } from '../../components/ui/Input'
import { Search, BookOpen, Clock, Award, GraduationCap } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'

interface Course {
  id: string
  title: string
  description: string
  duration: string
  image: string
  category: string
  completed?: boolean
}

export function ELearningPage() {
  const [searchTerm, setSearchTerm] = useState('')

  const courses: Course[] = [
    {
      id: '1',
      title: 'Bernese Mountain Dog - Ears',
      description: 'Learn proper ear cleaning and grooming techniques for Bernese Mountain Dogs',
      duration: '15 min',
      image: '/images/courses/blue-wheelers-logo.jpg',
      category: 'Breed Specific',
    },
    {
      id: '2',
      title: 'Bernese Mountain Dog - Feet',
      description: 'Paw care and grooming for Bernese Mountain Dogs',
      duration: '12 min',
      image: '/images/courses/blue-wheelers-logo.jpg',
      category: 'Breed Specific',
    },
    {
      id: '3',
      title: 'Shi Tzu X - Head Set Up',
      description: 'Complete head grooming techniques for Shih Tzu crossbreeds',
      duration: '20 min',
      image: '/images/courses/blue-wheelers-logo.jpg',
      category: 'Breed Specific',
    },
    {
      id: '4',
      title: 'King Charles Cavalier - Face Feet & Tail',
      description: 'Grooming essentials for Cavalier King Charles Spaniels',
      duration: '18 min',
      image: '/images/courses/blue-wheelers-logo.jpg',
      category: 'Breed Specific',
    },
    {
      id: '5',
      title: 'Poodle - Nails, Poodle Foot',
      description: 'Nail trimming and foot grooming for Poodles',
      duration: '14 min',
      image: '/images/courses/blue-wheelers-logo.jpg',
      category: 'Breed Specific',
    },
    {
      id: '6',
      title: 'Poodle - Poodle Face',
      description: 'Face grooming and styling for Poodles',
      duration: '16 min',
      image: '/images/courses/blue-wheelers-logo.jpg',
      category: 'Breed Specific',
    },
    {
      id: '7',
      title: 'Poodle - Tail',
      description: 'Tail grooming and styling for Poodles',
      duration: '10 min',
      image: '/images/courses/blue-wheelers-logo.jpg',
      category: 'Breed Specific',
    },
    {
      id: '8',
      title: 'Golden Retriever - Feet & Tail',
      description: 'Grooming techniques for Golden Retriever paws and tail',
      duration: '15 min',
      image: '/images/courses/blue-wheelers-logo.jpg',
      category: 'Breed Specific',
    },
    {
      id: '9',
      title: 'Cavoodle - Head, Face, Ears, Legs & Feet',
      description: 'Complete grooming guide for Cavoodles',
      duration: '25 min',
      image: '/images/courses/blue-wheelers-logo.jpg',
      category: 'Breed Specific',
    },
    {
      id: '10',
      title: 'West Highland White Terrier - Head, Face & Ears',
      description: 'Grooming essentials for Westies',
      duration: '17 min',
      image: '/images/courses/blue-wheelers-logo.jpg',
      category: 'Breed Specific',
    },
    {
      id: '11',
      title: 'Schnauzer - Head & Body Set',
      description: 'Complete grooming techniques for Schnauzers',
      duration: '22 min',
      image: '/images/courses/blue-wheelers-logo.jpg',
      category: 'Breed Specific',
    },
    {
      id: '12',
      title: 'BW Trailer Maintenance',
      description: 'Essential trailer maintenance and care',
      duration: '30 min',
      image: '/images/courses/blue-wheelers-logo.jpg',
      category: 'Operations',
    },
    {
      id: '13',
      title: 'Simple Science of Grooming',
      description: 'Understanding the fundamentals of dog grooming',
      duration: '35 min',
      image: '/images/courses/blue-wheelers-logo.jpg',
      category: 'Fundamentals',
    },
    {
      id: '14',
      title: 'Basic Recap of Blue Wheelers',
      description: 'Review of Blue Wheelers standards and procedures',
      duration: '20 min',
      image: '/images/courses/blue-wheelers-logo.jpg',
      category: 'Fundamentals',
    },
    {
      id: '15',
      title: 'Amber and Sharon Grooming',
      description: 'Advanced grooming techniques demonstration',
      duration: '28 min',
      image: '/images/courses/blue-wheelers-logo.jpg',
      category: 'Advanced',
    },
    {
      id: '16',
      title: 'Beyond the Groom: Emergency First Aid for Groomers with Dr Lynn',
      description: 'First aid and emergency response training',
      duration: '45 min',
      image: '/images/courses/blue-wheelers-logo.jpg',
      category: 'Safety',
    },
    {
      id: '17',
      title: 'Beyond the Fur: Understanding Dog Behaviour with K9 Consulting',
      description: 'Dog behavior and psychology for groomers',
      duration: '40 min',
      image: '/images/courses/blue-wheelers-logo.jpg',
      category: 'Behavior',
    },
    {
      id: '18',
      title: 'Mental Health and Wellbeing in Business - Strawberry Seed Consulting',
      description: 'Mental health awareness for business owners',
      duration: '32 min',
      image: '/images/courses/blue-wheelers-logo.jpg',
      category: 'Wellbeing',
    },
  ]

  const filteredCourses = courses.filter((course) =>
    course.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
    course.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
    course.category.toLowerCase().includes(searchTerm.toLowerCase())
  )

  const categories = Array.from(new Set(courses.map(c => c.category)))

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
        {categories.map((category) => {
          const categoryCourses = filteredCourses.filter(c => c.category === category)
          if (categoryCourses.length === 0) return null

          return (
            <div key={category} className="space-y-3">
              <h2 className="text-lg font-semibold text-gray-800">{category}</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {categoryCourses.map((course) => (
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
          )
        })}
      </div>

      {filteredCourses.length === 0 && (
        <Card className="p-8 text-center">
          <BookOpen className="w-12 h-12 text-gray-400 mx-auto mb-3" />
          <p className="text-gray-600">No courses found matching your search.</p>
        </Card>
      )}
    </div>
  )
}
