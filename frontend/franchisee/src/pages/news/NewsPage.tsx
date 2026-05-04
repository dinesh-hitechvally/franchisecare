import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { newsApi } from '../../api/services'
import { useToastStore } from '../../store/toastStore'
import type { NewsItem } from '../../types'
import { Plus, Newspaper, Eye, Edit2, Trash2, CheckCircle } from 'lucide-react'
import { format } from 'date-fns'

export function NewsPage() {
  const queryClient = useQueryClient()
  const addToast = useToastStore((state) => state.addToast)
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [isEditing, setIsEditing] = useState(false)
  const [selectedNews, setSelectedNews] = useState<NewsItem | null>(null)

  const { data: news } = useQuery({
    queryKey: ['news'],
    queryFn: async () => {
      return [
        {
          id: '1',
          title: 'New Service Package Available',
          content: 'We are excited to announce our new Premium Grooming Package which includes bath, haircut, nail trim, and teeth cleaning at a discounted rate.',
          authorId: '1',
          isPublished: true,
          publishedAt: new Date().toISOString(),
          createdAt: new Date(Date.now() - 86400000).toISOString(),
        },
        {
          id: '2',
          title: 'Holiday Hours Update',
          content: 'Please note our special holiday hours. We will be closed on Christmas Day and New Year\'s Day.',
          authorId: '1',
          isPublished: true,
          publishedAt: new Date(Date.now() - 172800000).toISOString(),
          createdAt: new Date(Date.now() - 172800000).toISOString(),
        },
        {
          id: '3',
          title: 'Staff Training Day - Draft',
          content: 'Upcoming staff training day scheduled for next month. More details to follow.',
          authorId: '1',
          isPublished: false,
          createdAt: new Date(Date.now() - 259200000).toISOString(),
        },
      ] as NewsItem[]
    },
  })

  const publishMutation = useMutation({
    mutationFn: newsApi.publish,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['news'] })
      addToast('News published', 'success')
    },
  })

  const createMutation = useMutation({
    mutationFn: newsApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['news'] })
      setIsModalOpen(false)
      addToast('News created', 'success')
    },
  })

  const deleteMutation = useMutation({
    mutationFn: newsApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['news'] })
      addToast('News deleted', 'success')
    },
  })

  const handleEdit = (item: NewsItem) => {
    setSelectedNews(item)
    setIsEditing(true)
    setIsModalOpen(true)
  }

  const handleCreate = () => {
    setSelectedNews(null)
    setIsEditing(false)
    setIsModalOpen(true)
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-secondary-900">News</h1>
          <p className="text-secondary-500">Manage and publish news updates</p>
        </div>
        <Button onClick={handleCreate} size="sm">
          <Plus className="w-4 h-4 mr-2" />
          New Article
        </Button>
      </div>

      <Card>
        <Table
          columns={[
            {
              key: 'icon',
              title: '',
              render: () => (
                <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                  <Newspaper className="w-5 h-5 text-blue-600" />
                </div>
              ),
            },
            { key: 'title', title: 'Title' },
            { key: 'content', title: 'Content', render: (row: NewsItem) => (
              <span className="text-secondary-500 truncate max-w-xs block">{row.content}</span>
            )},
            {
              key: 'status',
              title: 'Status',
              render: (row: NewsItem) => row.isPublished ? (
                <span className="flex items-center gap-1 text-green-600 text-sm font-medium">
                  <CheckCircle className="w-4 h-4" /> Published
                </span>
              ) : (
                <span className="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                  Draft
                </span>
              ),
            },
            { key: 'publishedAt', title: 'Published', render: (row: NewsItem) =>
              row.publishedAt ? format(new Date(row.publishedAt), 'MMM d, yyyy') : '-'
            },
            { key: 'createdAt', title: 'Created', render: (row: NewsItem) => format(new Date(row.created_at), 'MMM d, yyyy') },
            {
              key: 'actions',
              title: 'Actions',
              render: (row: NewsItem) => (
                <div className="flex gap-1">
                  {!row.isPublished && (
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => publishMutation.mutate(row.id)}
                      isLoading={publishMutation.isPending}
                    >
                      <Eye className="w-4 h-4" />
                    </Button>
                  )}
                  <Button variant="ghost" size="sm" onClick={() => handleEdit(row)}>
                    <Edit2 className="w-4 h-4" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    className="text-red-600 hover:text-red-700"
                    onClick={() => deleteMutation.mutate(row.id)}
                  >
                    <Trash2 className="w-4 h-4" />
                  </Button>
                </div>
              ),
            },
          ]}
          data={news ?? []}
          keyExtractor={(row) => row.id}
        />
      </Card>

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title={isEditing ? 'Edit News' : 'Create News'}
        footer={
          <>
            <Button variant="secondary" onClick={() => setIsModalOpen(false)}>
              Cancel
            </Button>
            <Button
              onClick={() => isEditing
                ? setIsModalOpen(false)
                : createMutation.mutate({
                    title: 'New News',
                    content: 'Content',
                    authorId: '1',
                    isPublished: false,
                  } as Omit<NewsItem, 'id' | 'createdAt' | 'updatedAt'>)
              }
              isLoading={createMutation.isPending}
            >
              {isEditing ? 'Save Changes' : 'Create'}
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          <Input
            label="Title"
            defaultValue={selectedNews?.title}
          />
          <div>
            <label className="text-sm font-medium text-secondary-700">Content</label>
            <textarea
              className="input mt-1"
              rows={5}
              defaultValue={selectedNews?.content}
            />
          </div>
          <label className="flex items-center gap-2">
            <input
              type="checkbox"
              defaultChecked={selectedNews?.isPublished}
              className="rounded border-secondary-300"
            />
            <span className="text-sm text-secondary-700">Publish immediately</span>
          </label>
        </div>
      </Modal>
    </div>
  )
}
