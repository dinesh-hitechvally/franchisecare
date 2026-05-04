import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { List, ListOrdered, Link, Type, Image as ImageIcon, Heart, MessageSquare, Search, Settings } from 'lucide-react'
import { forumApi } from '../../api/services'
import { useAuthStore } from '../../store/authStore'
import { useToastStore } from '../../store/toastStore'
import { format } from 'date-fns'
import { TablePagination } from '../../components/ui/TablePagination'
import { cn } from '../../lib/utils'
import type { ForumThread } from '../../types'

// Mock Data for sidebar (could be dynamic later)
const teamMembers = [
  { id: 1, name: 'Aimee & ...', image: 'https://ui-avatars.com/api/?name=Aimee&background=random' },
  { id: 2, name: 'Alan Bates', image: 'https://ui-avatars.com/api/?name=Alan&background=random' },
  { id: 3, name: 'Alex Stef...', image: 'https://ui-avatars.com/api/?name=Alex&background=random' },
  { id: 4, name: 'Alexandr...', image: 'https://ui-avatars.com/api/?name=Alexandr&background=random' },
  { id: 5, name: 'Alicia Goss', image: 'https://ui-avatars.com/api/?name=Alicia&background=random' },
  { id: 6, name: 'Alison M...', image: 'https://ui-avatars.com/api/?name=Alison&background=random' },
]

const photos = [
  { id: 1, url: 'https://picsum.photos/seed/1/100/100' },
  { id: 2, url: 'https://picsum.photos/seed/2/100/100' },
  { id: 3, url: 'https://picsum.photos/seed/3/100/100' },
]

const topics = [
  { name: 'Grooming', count: 6 },
  { name: 'Maintenance & Trailers', count: 24 },
  { name: 'Marketing', count: null },
  { name: 'Mate', count: null },
  { name: 'Operations..', count: 3 },
  { name: 'Products', count: 10 },
]

const stateGroups = [
  { name: 'ACT', count: null },
  { name: 'ACT - Service Providers And Tradies', count: null },
  { name: 'NSW - Service Providers And Tradies', count: null },
  { name: 'NT', count: 3 },
  { name: 'NT - Service Providers And Tradies', count: null },
  { name: 'QLD', count: 9 },
  { name: 'QLD - Service Providers And Tradies', count: 2 },
  { name: 'SA', count: 9 },
  { name: 'SA - Service Providers And Tradies', count: 1 },
  { name: 'TAS', count: 1 },
]

export function ForumPage() {
  const { user } = useAuthStore()
  const { addToast } = useToastStore()
  const queryClient = useQueryClient()
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(10)
  const [selectedTopic, setSelectedTopic] = useState<string | undefined>(undefined)
  const [searchTerm, setSearchTerm] = useState('')
  const [newPostContent, setNewPostContent] = useState('')
  const [commentContents, setCommentContents] = useState<Record<string, string>>({})

  // Fetch Threads
  const { data: listResult, isLoading } = useQuery({
    queryKey: ['forum-threads', selectedTopic, searchTerm, page, perPage],
    queryFn: () => forumApi.getThreads({
      topic: selectedTopic,
      search: searchTerm,
      page,
      per_page: perPage
    }),
  })

  const threads = listResult?.data ?? []
  const listMeta = listResult?.meta

  // Mutations
  const createThreadMutation = useMutation({
    mutationFn: (content: string) => forumApi.createThread({ content, topic: selectedTopic || 'General' }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['forum-threads'] })
      setNewPostContent('')
      addToast('Post created successfully', 'success')
    },
    onError: () => addToast('Failed to create post', 'error')
  })

  const addCommentMutation = useMutation({
    mutationFn: ({ threadId, content }: { threadId: string, content: string }) => 
      forumApi.addComment(threadId, content),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['forum-threads'] })
      setCommentContents({})
      addToast('Comment added', 'success')
    },
    onError: () => addToast('Failed to add comment', 'error')
  })

  const likeThreadMutation = useMutation({
    mutationFn: (threadId: string) => forumApi.likeThread(threadId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['forum-threads'] })
    }
  })

  const handleCreatePost = () => {
    if (!newPostContent.trim()) return
    createThreadMutation.mutate(newPostContent)
  }

  const handleAddComment = (threadId: string) => {
    const content = commentContents[threadId]
    if (!content?.trim()) return
    addCommentMutation.mutate({ threadId, content })
  }

  return (
    <div className="w-full flex-grow p-4 lg:p-6 pb-20 bg-[#f4f6f8]">
      <div className="max-w-[1400px] mx-auto flex flex-col lg:flex-row gap-6">

        {/* LEFT COLUMN */}
        <div className="w-full lg:w-[280px] flex-shrink-0 space-y-6">
          {/* Main User Card */}
          <div className="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-100 relative">
            <div className="h-40 bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center p-4">
              <div className="flex gap-2 items-center opacity-80">
                <span className="text-blue-500 font-extrabold text-2xl italic tracking-tighter">BLUE WHEELERS</span>
                <span className="text-red-500 font-extrabold text-2xl tracking-tighter mix-blend-multiply">DASH DOG WASH</span>
              </div>
            </div>
            <div className="h-16 bg-gradient-to-t from-gray-900 to-transparent absolute bottom-[72px] w-full" />
            <div className="absolute bottom-[72px] left-0 p-4 text-white">
              <div className="font-semibold text-sm">{user?.name || 'Mate Support'}</div>
              <div className="text-xs text-gray-200">South Yarra</div>
            </div>
            <div className="p-4 flex gap-2 border-t border-gray-100 items-center">
              <button className="p-2 text-gray-600 hover:bg-gray-100 rounded">
                <Settings className="w-4 h-4 text-gray-500" />
              </button>
              <button className="flex-1 bg-blue-700 text-white text-xs font-semibold py-2 rounded shadow-sm hover:bg-blue-800 transition-colors uppercase">
                My Profile
              </button>
              <div className="relative flex-1">
                <input 
                  type="text" 
                  placeholder="SEARCH"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="w-full bg-blue-700 text-white text-xs font-semibold py-2 px-3 rounded shadow-sm hover:bg-blue-800 transition-colors focus:outline-none placeholder-blue-300"
                />
                <Search className="w-3 h-3 text-blue-300 absolute right-2 top-1/2 -translate-y-1/2" />
              </div>
            </div>
          </div>

          {/* Team Members */}
          <div>
            <div className="border-b-2 border-blue-700 pb-2 mb-4 inline-block">
              <h3 className="text-lg text-gray-800">Team Members - 201</h3>
            </div>
            <div className="grid grid-cols-3 gap-2">
              {teamMembers.map((member) => (
                <div key={member.id} className="relative aspect-square rounded-md overflow-hidden group cursor-pointer bg-gray-200">
                  <img src={member.image} alt={member.name} className="object-cover w-full h-full" />
                  <div className="absolute bottom-0 left-0 w-full bg-black/60 p-1 text-[10px] text-white truncate text-center">
                    {member.name}
                  </div>
                </div>
              ))}
            </div>
            <button className="text-blue-700 hover:text-blue-800 text-sm mt-4 flex items-center gap-1 font-medium">
              See all Team Members <span className="text-lg leading-none">&rarr;</span>
            </button>
          </div>

          {/* Photos */}
          <div>
            <div className="border-b-2 border-blue-700 pb-2 mb-4 inline-block">
              <h3 className="text-lg text-gray-800">Photos</h3>
            </div>
            <div className="grid grid-cols-3 gap-2">
              {photos.map((photo) => (
                <div key={photo.id} className="aspect-square rounded-md overflow-hidden bg-gray-200">
                  <img src={photo.url} alt="Photo" className="object-cover w-full h-full" />
                </div>
              ))}
            </div>
          </div>
        </div>


        {/* MIDDLE COLUMN */}
        <div className="flex-1 flex flex-col space-y-4">
          <div className="flex items-center justify-between mb-2">
            <h2 className="text-2xl text-gray-800">Latest Posts (Daily Chat)</h2>
            <button className="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold py-2 px-4 rounded shadow-sm uppercase tracking-wide">
              Forum Etiquette
            </button>
          </div>

          {/* Create Post Card */}
          <Card className="p-0 overflow-hidden bg-white shadow-sm border border-gray-200">
            <div className="flex p-4 gap-4 items-start border-b border-gray-100">
              <div className="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center font-bold text-blue-700 flex-shrink-0 italic">
                {user?.name?.slice(0, 2).toUpperCase() || 'BW'}
              </div>
              <div className="flex-1">
                {/* Rich Text Editor Toolbar Mockup */}
                <div className="flex flex-wrap gap-2 mb-3 text-gray-600 border-b border-gray-100 pb-2">
                  <button className="p-1 hover:bg-gray-100 rounded text-sm font-bold w-6 h-6 flex justify-center items-center">B</button>
                  <button className="p-1 hover:bg-gray-100 rounded text-sm italic w-6 h-6 flex justify-center items-center">I</button>
                  <button className="p-1 hover:bg-gray-100 rounded text-sm underline w-6 h-6 flex justify-center items-center">U</button>
                  <button className="p-1 hover:bg-gray-100 rounded text-sm line-through w-6 h-6 flex justify-center items-center">S</button>
                  <div className="w-px h-5 bg-gray-200 mx-1"></div>
                  <button className="p-1 hover:bg-gray-100 rounded text-sm font-bold">H1</button>
                  <button className="p-1 hover:bg-gray-100 rounded text-sm font-bold">H2</button>
                  <div className="w-px h-5 bg-gray-200 mx-1"></div>
                  <button className="p-1 hover:bg-gray-100 rounded"><List className="w-4 h-4" /></button>
                  <button className="p-1 hover:bg-gray-100 rounded"><ListOrdered className="w-4 h-4" /></button>
                  <div className="w-px h-5 bg-gray-200 mx-1"></div>
                  <button className="p-1 hover:bg-gray-100 rounded"><Link className="w-4 h-4" /></button>
                  <button className="p-1 hover:bg-gray-100 rounded"><Type className="w-4 h-4" /></button>
                </div>
                <textarea 
                  className="w-full text-base outline-none resize-none placeholder-gray-400 min-h-[60px]"
                  placeholder="Start a Post..."
                  value={newPostContent}
                  onChange={(e) => setNewPostContent(e.target.value)}
                ></textarea>
              </div>
            </div>
            {/* Create Post Actions */}
            <div className="flex justify-between items-center p-3 px-5 bg-white">
              <button className="text-gray-500 font-medium text-xs flex items-center gap-2 hover:text-gray-700">
                <ImageIcon className="w-5 h-5" /> Add Photos/Album
              </button>
              <button 
                onClick={handleCreatePost}
                disabled={!newPostContent.trim() || createThreadMutation.isPending}
                className={cn(
                  "uppercase font-semibold text-sm tracking-wide transition-colors",
                  newPostContent.trim() ? "text-blue-600 hover:text-blue-700" : "text-gray-300 cursor-not-allowed"
                )}
              >
                {createThreadMutation.isPending ? 'Posting...' : 'Post'}
              </button>
            </div>
          </Card>

          {/* Feed Card */}
          {isLoading ? (
            <div className="p-12 text-center text-gray-400 italic">Loading posts...</div>
          ) : threads.length > 0 ? (
            threads.map((thread: ForumThread) => (
              <Card key={thread.id} className="bg-white shadow-sm border border-gray-200">
                <div className="p-6">
                  {/* Post Header */}
                  <div className="flex items-center gap-3 mb-4">
                    <img 
                      src={thread.author?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(thread.author?.name || 'User')}&background=random`} 
                      alt={thread.author?.name} 
                      className="w-12 h-12 rounded-full" 
                    />
                    <div>
                      <h4 className="text-gray-800 font-medium">{thread.author?.name}</h4>
                      <div className="text-gray-400 text-xs">{format(new Date(thread.created_at), 'MMMM do yyyy, h:mm a')}</div>
                    </div>
                  </div>

                  {/* Post Content */}
                  <div className="text-gray-800 space-y-4 mb-6 text-[15px] leading-relaxed">
                    <p className="whitespace-pre-wrap">{thread.content}</p>
                  </div>

                  {/* Post Stats */}
                  <div className="flex items-center gap-6 text-sm text-gray-500 font-medium mb-6">
                    <button 
                      onClick={() => likeThreadMutation.mutate(thread.id)}
                      className="flex items-center gap-1.5 hover:text-red-500 transition-colors"
                    >
                      <Heart className={cn("w-4 h-4", thread.likes_count > 0 && "fill-red-500 text-red-500")} /> 
                      {thread.likes_count} Likes
                    </button>
                    <button className="flex items-center gap-1.5 hover:text-blue-600 text-blue-500">
                      <MessageSquare className="w-4 h-4 fill-current" /> 
                      {thread.comments?.length || 0} Comments
                    </button>
                  </div>

                  {/* Comments List */}
                  {thread.comments && thread.comments.length > 0 && (
                    <div className="space-y-4 mb-6 ml-4 border-l-2 border-gray-100 pl-4">
                      {thread.comments.map((comment) => (
                        <div key={comment.id} className="flex gap-3 items-start">
                          <img 
                            src={comment.author?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(comment.author?.name || 'User')}&background=random`} 
                            alt={comment.author?.name} 
                            className="w-8 h-8 rounded-full" 
                          />
                          <div className="flex-1 bg-gray-50 rounded-lg p-3">
                            <div className="flex justify-between items-center mb-1">
                              <span className="text-xs font-bold text-gray-700">{comment.author?.name}</span>
                              <span className="text-[10px] text-gray-400">{format(new Date(comment.created_at), 'MMM d, h:mm a')}</span>
                            </div>
                            <p className="text-sm text-gray-600 whitespace-pre-wrap">{comment.content}</p>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}

                  {/* Comment Box */}
                  <div className="flex gap-4 items-start">
                    <div className="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center font-bold text-blue-700 flex-shrink-0 italic text-sm">
                      {user?.name?.slice(0, 2).toUpperCase() || 'BW'}
                    </div>
                    <div className="flex-1 border border-gray-200 rounded-md bg-white">
                      <div className="flex flex-wrap gap-2 p-2 border-b border-gray-100 text-gray-600">
                        {/* Toolbar mockup */}
                        <button className="p-1 hover:bg-gray-100 rounded text-xs font-bold w-6 h-6 flex justify-center items-center">B</button>
                        <button className="p-1 hover:bg-gray-100 rounded text-xs italic w-6 h-6 flex justify-center items-center">I</button>
                        <button className="p-1 hover:bg-gray-100 rounded text-xs underline w-6 h-6 flex justify-center items-center">U</button>
                        <div className="w-px h-4 bg-gray-200 mx-1 mt-1"></div>
                        <button className="p-1 hover:bg-gray-100 rounded"><ImageIcon className="w-3.5 h-3.5" /></button>
                      </div>
                      <textarea 
                        className="w-full text-sm outline-none resize-none placeholder-gray-500 p-3 min-h-[50px] bg-white border-b border-gray-100"
                        placeholder="Type Comments"
                        value={commentContents[thread.id] || ''}
                        onChange={(e) => setCommentContents(prev => ({ ...prev, [thread.id]: e.target.value }))}
                      ></textarea>
                      <div className="p-3 flex justify-end">
                        <button 
                          onClick={() => handleAddComment(thread.id)}
                          disabled={!commentContents[thread.id]?.trim() || addCommentMutation.isPending}
                          className={cn(
                            "uppercase font-semibold px-6 py-2 rounded text-xs tracking-wide transition-colors",
                            commentContents[thread.id]?.trim() ? "text-blue-600 hover:text-blue-700" : "text-gray-300 cursor-not-allowed bg-gray-50"
                          )}
                        >
                          Post
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </Card>
            ))
          ) : (
            <div className="p-12 text-left text-gray-400 italic">No posts found.</div>
          )}

          {/* Pagination */}
          {listMeta && (
            <div className="mt-4">
              <TablePagination
                meta={listMeta}
                onPageChange={setPage}
                onPerPageChange={setPerPage}
                isLoading={isLoading}
              />
            </div>
          )}
        </div>


        {/* RIGHT COLUMN */}
        <div className="w-full lg:w-[280px] flex-shrink-0 space-y-8 mt-2">
          
          {/* Topics */}
          <div>
            <div className="border-b border-gray-200 pb-2 mb-4">
              <h3 className="text-[17px] text-gray-800">Topics</h3>
            </div>
            <div className="flex flex-wrap gap-x-2 gap-y-3">
              <button 
                onClick={() => { setSelectedTopic(undefined); setPage(1); }}
                className={cn(
                  "bg-white border text-sm font-medium py-2 px-4 shadow-[0_1px_2px_rgba(0,0,0,0.05)] hover:bg-gray-50 transition-colors",
                  !selectedTopic ? "border-blue-500 text-blue-600" : "border-gray-200 text-gray-800"
                )}
              >
                All Posts
              </button>
              {topics.map((topic, i) => (
                <div key={i} className="relative inline-block mt-2 mr-2">
                  <button 
                    onClick={() => { setSelectedTopic(topic.name); setPage(1); }}
                    className={cn(
                      "bg-white border text-sm font-medium py-2 px-4 shadow-[0_1px_2px_rgba(0,0,0,0.05)] hover:bg-gray-50 transition-colors",
                      selectedTopic === topic.name ? "border-blue-500 text-blue-600" : "border-gray-200 text-gray-800"
                    )}
                  >
                    {topic.name}
                  </button>
                  {topic.count !== null && (
                    <div className="absolute -top-2.5 -right-2.5 bg-red-600 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center border border-white">
                      {topic.count}
                    </div>
                  )}
                </div>
              ))}
            </div>
          </div>

          {/* State Groups */}
          <div>
            <div className="border-b border-gray-200 pb-2 mb-4">
              <h3 className="text-[17px] text-gray-800">State Groups</h3>
            </div>
            <div className="flex flex-wrap gap-x-2 gap-y-3">
              {stateGroups.map((group, i) => (
                <div key={i} className="relative inline-block mt-2 mr-2">
                  <button className="bg-white border border-gray-200 text-gray-800 text-sm font-medium py-2 px-4 shadow-[0_1px_2px_rgba(0,0,0,0.05)] hover:bg-gray-50 transition-colors">
                    {group.name}
                  </button>
                  {group.count !== null && (
                    <div className="absolute -top-2.5 -right-2.5 bg-red-600 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center border border-white">
                      {group.count}
                    </div>
                  )}
                </div>
              ))}
            </div>
          </div>

        </div>

      </div>
    </div>
  )
}

