import { useEffect, useMemo, useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { Card } from '../../components/ui/Card'
import { List, ListOrdered, Link, Type, Image as ImageIcon, Heart, MessageSquare, Search, Settings, Plus, X, Users, MoreVertical, Pin, ArrowLeft, Bell, ChevronDown, CheckCheck } from 'lucide-react'
import { forumApi } from '../../api/services'
import { useAuthStore } from '../../store/authStore'
import { useToastStore } from '../../store/toastStore'
import { format, formatDistanceToNow } from 'date-fns'
import { TablePagination } from '../../components/ui/TablePagination'
import { cn } from '../../lib/utils'
import type { ForumThread, ForumNotification } from '../../types'

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

export function ForumPage() {
  const { user } = useAuthStore()
  const { addToast } = useToastStore()
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  
  // Helper function to navigate to profile - own profile goes to /forum/profile, others to /forum/user/:userId
  const navigateToProfile = (userId: string | number) => {
    if (user?.id?.toString() === userId?.toString()) {
      navigate('/forum/profile')
    } else {
      navigate(`/forum/user/${userId}`)
    }
  }
  
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(10)
  const [selectedTopic, setSelectedTopic] = useState<string | undefined>(undefined)
  const [selectedGroup, setSelectedGroup] = useState<string | null | 'daily-chat'>(null)
  const [searchTerm, setSearchTerm] = useState('')
  const [newPostContent, setNewPostContent] = useState('')
  const [commentContents, setCommentContents] = useState<Record<string, string>>({})
  const [replyingTo, setReplyingTo] = useState<string | null>(null)
  const [isCreateGroupModalOpen, setIsCreateGroupModalOpen] = useState(false)
  const [newGroupName, setNewGroupName] = useState('')
  const [newGroupDescription, setNewGroupDescription] = useState('')
  const [showAllGroups, setShowAllGroups] = useState(false)
  const [groupsViewTab, setGroupsViewTab] = useState<'all' | 'my'>('all')
  const [notificationFilter, setNotificationFilter] = useState<'all' | 'unread'>('all')
  const [showMarkAllReadConfirm, setShowMarkAllReadConfirm] = useState(false)
  const [highlightedThreadId, setHighlightedThreadId] = useState<string | null>(null)
  const [highlightedCommentId, setHighlightedCommentId] = useState<string | null>(null)
  const [focusedThread, setFocusedThread] = useState<ForumThread | null>(null)

  // Fetch Groups
  const { data: groups = [] } = useQuery({
    queryKey: ['forum-groups'],
    queryFn: () => forumApi.getGroups(),
  })

  // Fetch My Groups (groups user is a member of)
  const { data: myGroups = [] } = useQuery({
    queryKey: ['forum-groups', 'my'],
    queryFn: () => forumApi.getGroups({ my_groups: true }),
  })

  // Organize groups by type
  const topicGroups = groups.filter(g => g.type === 'topic')
  const stateGroups = groups.filter(g => g.type === 'state')
  const customGroups = groups.filter(g => g.type === 'custom')

  const { data: allNotifications = [] } = useQuery({
    queryKey: ['forum-notifications'],
    queryFn: () => forumApi.getNotifications({ limit: 200 }),
  })

  const currentNotifications = useMemo(() => {
    const unreadOnly = notificationFilter === 'unread'

    if (selectedGroup && selectedGroup !== 'daily-chat') {
      return allNotifications.filter(n => n.group_id === selectedGroup && (!unreadOnly || !n.is_read))
    }

    return allNotifications.filter(n => !n.group_id && (!unreadOnly || !n.is_read))
  }, [allNotifications, selectedGroup, notificationFilter])

  const getNotificationCount = (groupId: string) => {
    return allNotifications.filter(n => n.group_id === groupId && !n.is_read).length
  }

  // Get selected group name and details
  const selectedGroupData = selectedGroup && selectedGroup !== 'daily-chat'
    ? groups.find(g => g.id === selectedGroup)
    : null
  const displayTitle = selectedGroupData
    ? selectedGroupData.name
    : 'Latest Posts (Daily Chat)'

  // Fetch group members when a group is selected
  const { data: groupMembers = [] } = useQuery({
    queryKey: ['forum-group-members', selectedGroup],
    queryFn: () => selectedGroup && selectedGroup !== 'daily-chat'
      ? forumApi.getGroupMembers(selectedGroup)
      : Promise.resolve([]),
    enabled: !!selectedGroup && selectedGroup !== 'daily-chat',
  })

  // Fetch Threads
  const { data: listResult, isLoading } = useQuery({
    queryKey: ['forum-threads', selectedTopic, selectedGroup, searchTerm, page, perPage],
    queryFn: () => forumApi.getThreads({
      topic: selectedTopic,
      search: searchTerm,
      page,
      per_page: perPage,
      group_id: selectedGroup && selectedGroup !== 'daily-chat' ? selectedGroup : undefined,
      no_group: selectedGroup === 'daily-chat',
    }),
  })

  const threads = listResult?.data ?? []
  const listMeta = listResult?.meta

  const displayThreads = useMemo(() => {
    if (!focusedThread) {
      return threads
    }

    if (threads.some((thread) => thread.id === focusedThread.id)) {
      return threads
    }

    return [focusedThread, ...threads]
  }, [threads, focusedThread])

  // Mutations
  const createThreadMutation = useMutation({
    mutationFn: (content: string) => forumApi.createThread({
      content,
      topic: selectedTopic || 'General',
      group_id: selectedGroup && selectedGroup !== 'daily-chat' ? selectedGroup : undefined,
    }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['forum-threads'] })
      queryClient.invalidateQueries({ queryKey: ['forum-notifications'] })
      setNewPostContent('')
      addToast('Post created successfully', 'success')
    },
    onError: () => addToast('Failed to create post', 'error')
  })

  const createGroupMutation = useMutation({
    mutationFn: (data: { name: string; description?: string }) =>
      forumApi.createGroup({ ...data, type: 'custom', is_public: true }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['forum-groups'] })
      setIsCreateGroupModalOpen(false)
      setNewGroupName('')
      setNewGroupDescription('')
      addToast('Group created successfully', 'success')
    },
    onError: () => addToast('Failed to create group', 'error')
  })

  const addCommentMutation = useMutation({
    mutationFn: ({ threadId, content }: { threadId: string, content: string }) => 
      forumApi.addComment(threadId, content),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['forum-threads'] })
      queryClient.invalidateQueries({ queryKey: ['forum-notifications'] })
      setCommentContents({})
      addToast('Comment added', 'success')
    },
    onError: () => addToast('Failed to add comment', 'error')
  })

  const likeThreadMutation = useMutation({
    mutationFn: (threadId: string) => forumApi.likeThread(threadId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['forum-threads'] })
      queryClient.invalidateQueries({ queryKey: ['forum-notifications'] })
    }
  })

  const likeCommentMutation = useMutation({
    mutationFn: (commentId: string) => forumApi.likeComment(commentId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['forum-threads'] })
      queryClient.invalidateQueries({ queryKey: ['forum-notifications'] })
    }
  })

  const replyToCommentMutation = useMutation({
    mutationFn: ({ commentId, content }: { commentId: string, content: string }) =>
      forumApi.replyToComment(commentId, content),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['forum-threads'] })
      queryClient.invalidateQueries({ queryKey: ['forum-notifications'] })
      setCommentContents({})
      setReplyingTo(null)
      addToast('Reply added', 'success')
    },
    onError: () => addToast('Failed to add reply', 'error')
  })

  const markNotificationReadMutation = useMutation({
    mutationFn: (id: string) => forumApi.markNotificationAsRead(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['forum-notifications'] })
    }
  })

  const markAllNotificationsReadMutation = useMutation({
    mutationFn: (params: { group_id?: string; no_group?: boolean }) => forumApi.markAllNotificationsAsRead(params),
    onSuccess: (result) => {
      queryClient.invalidateQueries({ queryKey: ['forum-notifications'] })
      addToast(`${result.updated} notification(s) marked as read`, 'success')
    },
    onError: () => addToast('Failed to mark notifications as read', 'error')
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

  const handleReplyToComment = (commentId: string) => {
    const content = commentContents[`reply-${commentId}`]
    if (!content?.trim()) return
    replyToCommentMutation.mutate({ commentId, content })
  }

  const handleNotificationClick = async (notification: ForumNotification) => {
    const targetGroupId = notification.group_id ?? 'daily-chat'

    setSelectedGroup(targetGroupId)
    setPage(1)
    setHighlightedThreadId(notification.thread_id)
    setHighlightedCommentId(notification.comment_id ?? null)

    if (!notification.is_read) {
      markNotificationReadMutation.mutate(notification.id)
    }

    try {
      const thread = await forumApi.getThread(notification.thread_id)
      setFocusedThread(thread)
    } catch {
      addToast('Could not open related post', 'error')
    }
  }

  const handleMarkAllAsRead = () => {
    setShowMarkAllReadConfirm(false)

    markAllNotificationsReadMutation.mutate({
      group_id: selectedGroup && selectedGroup !== 'daily-chat' ? selectedGroup : undefined,
      no_group: !selectedGroup || selectedGroup === 'daily-chat',
    })
  }

  useEffect(() => {
    if (!highlightedThreadId) {
      return
    }

    const element = document.getElementById(`thread-${highlightedThreadId}`)
    if (!element) {
      return
    }

    element.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }, [displayThreads, highlightedThreadId])

  useEffect(() => {
    if (!highlightedThreadId && !highlightedCommentId) {
      return
    }

    const timeoutId = setTimeout(() => {
      setHighlightedThreadId(null)
      setHighlightedCommentId(null)
    }, 4000)

    return () => clearTimeout(timeoutId)
  }, [highlightedThreadId, highlightedCommentId])

  return (
    <>
    <div className="flex flex-col lg:flex-row gap-6">

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
              <button
                onClick={() => navigate('/forum/profile')}
                className="flex-1 bg-blue-700 text-white text-xs font-semibold py-2 rounded shadow-sm hover:bg-blue-800 transition-colors uppercase"
              >
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
              <h3 className="text-lg text-gray-800">
                Team Members - {selectedGroupData ? groupMembers.length : 201}
              </h3>
            </div>
            {selectedGroupData && groupMembers.length > 0 ? (
              <>
                <div className="grid grid-cols-3 gap-2">
                  {groupMembers.slice(0, 6).map((member) => (
                    <div
                      key={member.id}
                      onClick={() => navigateToProfile(member.id)}
                      className="relative aspect-square rounded-md overflow-hidden group cursor-pointer bg-gray-200 hover:ring-2 hover:ring-blue-500 transition-all"
                    >
                      <img
                        src={member.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(member.name || 'User')}&background=random`}
                        alt={member.name}
                        className="object-cover w-full h-full"
                      />
                      <div className="absolute bottom-0 left-0 w-full bg-black/60 p-1 text-[10px] text-white truncate text-center">
                        {member.name}
                      </div>
                    </div>
                  ))}
                </div>
                {groupMembers.length > 6 && (
                  <button className="text-blue-700 hover:text-blue-800 text-sm mt-4 flex items-center gap-1 font-medium">
                    See all Team Members <span className="text-lg leading-none">&rarr;</span>
                  </button>
                )}
              </>
            ) : selectedGroupData ? (
              <div className="text-sm text-gray-500 py-4 text-center">
                No members yet
              </div>
            ) : (
              <>
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
              </>
            )}
          </div>

          {/* Photos */}
          <div>
            <div className="border-b-2 border-blue-700 pb-2 mb-4 inline-block">
              <h3 className="text-lg text-gray-800">Photos</h3>
            </div>
            {selectedGroupData ? (
              threads.length > 0 ? (
                <div className="grid grid-cols-3 gap-2">
                  {threads.slice(0, 6).map((thread, idx) => (
                    <div key={thread.id || idx} className="aspect-square rounded-md overflow-hidden bg-gray-200">
                      <img
                        src={thread.author?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(thread.author?.name || 'User')}&background=random`}
                        alt="Photo"
                        className="object-cover w-full h-full"
                      />
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-sm text-gray-500 py-4 text-center">
                  No photos yet
                </div>
              )
            ) : (
              <div className="grid grid-cols-3 gap-2">
                {photos.map((photo) => (
                  <div key={photo.id} className="aspect-square rounded-md overflow-hidden bg-gray-200">
                    <img src={photo.url} alt="Photo" className="object-cover w-full h-full" />
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>


        {/* MIDDLE COLUMN */}
        <div className="flex-1 flex flex-col space-y-4">
          <div className="flex items-center justify-between mb-2">
            <div>
              <h2 className="text-2xl text-gray-800">{displayTitle}</h2>
              <div className="w-16 h-1 bg-blue-700 mt-1"></div>
            </div>
            {selectedGroup && selectedGroup !== 'daily-chat' ? (
              <button
                onClick={() => {
                  setSelectedGroup('daily-chat');
                  setPage(1);
                }}
                className="bg-gray-600 hover:bg-gray-700 text-white text-sm font-semibold py-2 px-4 rounded shadow-sm flex items-center gap-2 transition-colors"
              >
                <ArrowLeft className="w-4 h-4" />
                Back to Daily Chat
              </button>
            ) : (
              <button className="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold py-2 px-4 rounded shadow-sm uppercase tracking-wide">
                Forum Etiquette
              </button>
            )}
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
          ) : displayThreads.length > 0 ? (
            displayThreads.map((thread: ForumThread) => (
              <div key={thread.id} id={`thread-${thread.id}`}>
              <Card
                className={cn(
                  "bg-white shadow-sm border",
                  highlightedThreadId === thread.id ? "border-blue-400 ring-2 ring-blue-100" : "border-gray-200"
                )}
              >
                <div className="p-6">
                  {/* Post Header */}
                  <div className="flex items-center gap-3 mb-4">
                    <img
                      src={thread.author?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(thread.author?.name || 'User')}&background=random`}
                      alt={thread.author?.name}
                      onClick={() => thread.author?.id && navigateToProfile(thread.author.id)}
                      className="w-12 h-12 rounded-full cursor-pointer hover:ring-2 hover:ring-blue-500 transition-all"
                    />
                    <div>
                      <h4
                        onClick={() => thread.author?.id && navigateToProfile(thread.author.id)}
                        className="text-gray-800 font-medium cursor-pointer hover:text-blue-600 transition-colors"
                      >
                        {thread.author?.name}
                      </h4>
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
                      <Heart className={cn("w-4 h-4", thread.liked && "fill-red-500 text-red-500")} /> 
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
                        <div key={comment.id} className="space-y-3">
                          {/* Main Comment */}
                          <div className="flex gap-3 items-start">
                            <img
                              src={comment.author?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(comment.author?.name || 'User')}&background=random`}
                              alt={comment.author?.name}
                              onClick={() => comment.author?.id && navigateToProfile(comment.author.id)}
                              className="w-8 h-8 rounded-full cursor-pointer hover:ring-2 hover:ring-blue-500 transition-all"
                            />
                            <div className="flex-1">
                              <div
                                className={cn(
                                  "bg-gray-50 rounded-lg p-3 transition-colors",
                                  highlightedCommentId === comment.id && "bg-yellow-50 ring-1 ring-yellow-300"
                                )}
                              >
                                <div className="flex justify-between items-center mb-1">
                                  <span
                                    onClick={() => comment.author?.id && navigateToProfile(comment.author.id)}
                                    className="text-xs font-bold text-gray-700 cursor-pointer hover:text-blue-600 transition-colors"
                                  >
                                    {comment.author?.name}
                                  </span>
                                  <span className="text-[10px] text-gray-400">{format(new Date(comment.created_at), 'MMM d, h:mm a')}</span>
                                </div>
                                <p className="text-sm text-gray-600 whitespace-pre-wrap">{comment.content}</p>
                              </div>

                              {/* Comment Actions */}
                              <div className="flex gap-4 mt-2 text-xs">
                                <button
                                  onClick={() => likeCommentMutation.mutate(comment.id)}
                                  className="flex items-center gap-1 text-gray-500 hover:text-red-500 transition-colors"
                                >
                                  <Heart className={cn("w-3.5 h-3.5", comment.liked && "fill-red-500 text-red-500")} />
                                  <span>{comment.likes_count || 0}</span>
                                </button>
                                <button
                                  onClick={() => setReplyingTo(replyingTo === comment.id ? null : comment.id)}
                                  className="flex items-center gap-1 text-gray-500 hover:text-blue-500 transition-colors"
                                >
                                  <MessageSquare className="w-3.5 h-3.5" />
                                  <span>Reply</span>
                                </button>
                              </div>

                              {/* Reply Input */}
                              {replyingTo === comment.id && (
                                <div className="mt-3 flex gap-3 items-start">
                                  <div className="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center font-bold text-blue-700 flex-shrink-0 text-xs">
                                    {user?.name?.slice(0, 2).toUpperCase() || 'U'}
                                  </div>
                                  <div className="flex-1 border border-gray-200 rounded-md bg-white">
                                    <textarea
                                      className="w-full text-sm outline-none resize-none placeholder-gray-500 p-2 min-h-[60px] bg-white"
                                      placeholder="Type your reply..."
                                      value={commentContents[`reply-${comment.id}`] || ''}
                                      onChange={(e) => setCommentContents(prev => ({ ...prev, [`reply-${comment.id}`]: e.target.value }))}
                                    ></textarea>
                                    <div className="p-2 flex justify-end gap-2 border-t border-gray-100">
                                      <button
                                        onClick={() => setReplyingTo(null)}
                                        className="text-xs text-gray-500 hover:text-gray-700 px-3 py-1"
                                      >
                                        Cancel
                                      </button>
                                      <button
                                        onClick={() => handleReplyToComment(comment.id)}
                                        disabled={!commentContents[`reply-${comment.id}`]?.trim() || replyToCommentMutation.isPending}
                                        className={cn(
                                          "text-xs font-semibold px-4 py-1 rounded transition-colors",
                                          commentContents[`reply-${comment.id}`]?.trim()
                                            ? "bg-blue-600 text-white hover:bg-blue-700"
                                            : "bg-gray-200 text-gray-400 cursor-not-allowed"
                                        )}
                                      >
                                        {replyToCommentMutation.isPending ? 'Sending...' : 'Reply'}
                                      </button>
                                    </div>
                                  </div>
                                </div>
                              )}

                              {/* Nested Replies */}
                              {comment.replies && comment.replies.length > 0 && (
                                <div className="mt-3 space-y-3 ml-6 border-l-2 border-gray-100 pl-3">
                                  {comment.replies.map((reply) => (
                                    <div key={reply.id} className="flex gap-2 items-start">
                                      <img
                                        src={reply.author?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(reply.author?.name || 'User')}&background=random`}
                                        alt={reply.author?.name}
                                        onClick={() => reply.author?.id && navigateToProfile(reply.author.id)}
                                        className="w-6 h-6 rounded-full cursor-pointer hover:ring-2 hover:ring-blue-500 transition-all"
                                      />
                                      <div className="flex-1">
                                        <div
                                          className={cn(
                                            "bg-blue-50 rounded-lg p-2 transition-colors",
                                            highlightedCommentId === reply.id && "bg-yellow-50 ring-1 ring-yellow-300"
                                          )}
                                        >
                                          <div className="flex justify-between items-center mb-1">
                                            <span
                                              onClick={() => reply.author?.id && navigateToProfile(reply.author.id)}
                                              className="text-[11px] font-bold text-gray-700 cursor-pointer hover:text-blue-600 transition-colors"
                                            >
                                              {reply.author?.name}
                                            </span>
                                            <span className="text-[10px] text-gray-400">{format(new Date(reply.created_at), 'MMM d, h:mm a')}</span>
                                          </div>
                                          <p className="text-xs text-gray-600 whitespace-pre-wrap">{reply.content}</p>
                                        </div>

                                        {/* Reply Actions */}
                                        <div className="flex gap-3 mt-1 text-xs">
                                          <button
                                            onClick={() => likeCommentMutation.mutate(reply.id)}
                                            className="flex items-center gap-1 text-gray-500 hover:text-red-500 transition-colors"
                                          >
                                            <Heart className={cn("w-3 h-3", reply.liked && "fill-red-500 text-red-500")} />
                                            <span>{reply.likes_count || 0}</span>
                                          </button>
                                        </div>
                                      </div>
                                    </div>
                                  ))}
                                </div>
                              )}
                            </div>
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
              </div>
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

          {/* Notifications */}
          <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div className="flex items-center gap-2 px-4 py-3 border-b border-gray-100 bg-gray-50">
              <Bell className="w-4 h-4 text-gray-600" />
              <h3 className="text-sm font-semibold text-gray-800">
                {selectedGroup && selectedGroup !== 'daily-chat'
                  ? `${selectedGroupData?.name} Notifications`
                  : 'Notifications'}
              </h3>
              <div className="ml-auto flex items-center gap-2">
                <select
                  value={notificationFilter}
                  onChange={(e) => setNotificationFilter(e.target.value as 'all' | 'unread')}
                  className="text-[12px] font-medium px-2 py-1 rounded border border-gray-300 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="all">All</option>
                  <option value="unread">Unread</option>
                </select>
                <button
                  onClick={() => setShowMarkAllReadConfirm(true)}
                  disabled={currentNotifications.filter(n => !n.is_read).length === 0 || markAllNotificationsReadMutation.isPending}
                  title="Mark all as read"
                  className={cn(
                    "p-1.5 rounded border",
                    currentNotifications.filter(n => !n.is_read).length > 0
                      ? "bg-white text-blue-700 border-blue-300 hover:bg-blue-50"
                      : "bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed"
                  )}
                >
                  <CheckCheck className="w-4 h-4" />
                </button>
              </div>
            </div>

            <div className="p-3">
              {/* Notifications List */}
              <div className="space-y-2">
                {currentNotifications.length > 0 ? (
                  currentNotifications.map((notification) => (
                    <div
                      key={notification.id}
                      onClick={() => handleNotificationClick(notification)}
                      className={cn(
                        "p-3 rounded-lg border transition-colors cursor-pointer hover:bg-gray-50",
                        notification.is_read
                          ? "bg-white border-gray-200"
                          : "bg-blue-50 border-blue-200"
                      )}
                    >
                      <p className="text-sm text-gray-800 mb-1">{notification.message}</p>
                      <p className="text-xs text-gray-500">
                        {formatDistanceToNow(new Date(notification.created_at), { addSuffix: true })}
                      </p>
                    </div>
                  ))
                ) : (
                  <div className="text-center py-6 text-gray-400">
                    <Bell className="w-8 h-8 mx-auto mb-2 opacity-30" />
                    <p className="text-xs">
                      {notificationFilter === 'unread'
                        ? 'No unread notifications'
                        : selectedGroup && selectedGroup !== 'daily-chat'
                          ? 'No notifications in this group'
                          : 'No notifications'}
                    </p>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Topics */}
          <div>
            <div className="mb-4">
              <h3 className="text-[17px] text-gray-800">Topics</h3>
              <div className="w-12 h-1 bg-blue-700 mt-1"></div>
            </div>
            <div className="flex flex-wrap gap-x-2 gap-y-3">
              {topicGroups.map((group) => {
                const notificationCount = getNotificationCount(group.id)
                return (
                  <div key={group.id} className="relative inline-block mt-2 mr-2">
                    <button
                      onClick={() => { setSelectedGroup(group.id); setPage(1); }}
                      className={cn(
                        "bg-white border text-sm font-medium py-2 px-4 shadow-[0_1px_2px_rgba(0,0,0,0.05)] hover:bg-gray-50 transition-colors",
                        selectedGroup === group.id ? "border-blue-500 text-blue-600" : "border-gray-200 text-gray-800"
                      )}
                    >
                      {group.name}
                    </button>
                    {notificationCount > 0 && (
                      <div className="absolute -top-2.5 -right-2.5 bg-red-600 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center border border-white">
                        {notificationCount}
                      </div>
                    )}
                  </div>
                )
              })}
            </div>
          </div>

          {/* State Groups */}
          <div>
            <div className="mb-4">
              <h3 className="text-[17px] text-gray-800">State Groups</h3>
              <div className="w-12 h-1 bg-blue-700 mt-1"></div>
            </div>
            <div className="flex flex-wrap gap-x-2 gap-y-3">
              {stateGroups.map((group) => {
                const notificationCount = getNotificationCount(group.id)

                return (
                <div key={group.id} className="relative inline-block mt-2 mr-2">
                  <button
                    onClick={() => { setSelectedGroup(group.id); setPage(1); }}
                    className={cn(
                      "bg-white border text-sm font-medium py-2 px-4 shadow-[0_1px_2px_rgba(0,0,0,0.05)] hover:bg-gray-50 transition-colors",
                      selectedGroup === group.id ? "border-blue-500 text-blue-600" : "border-gray-200 text-gray-800"
                    )}
                  >
                    {group.name}
                  </button>
                  {notificationCount > 0 && (
                    <div className="absolute -top-2.5 -right-2.5 bg-red-600 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center border border-white">
                      {notificationCount}
                    </div>
                  )}
                </div>
                )
              })}
            </div>
          </div>

          {/* User Groups */}
          <div>
            <div className="mb-4">
              <h3 className="text-[17px] text-gray-800">User Groups</h3>
              <div className="w-12 h-1 bg-blue-700 mt-1"></div>
            </div>
            {customGroups.length > 0 ? (
              <div className="flex flex-wrap gap-x-2 gap-y-3">
                {customGroups.map((group) => {
                  const notificationCount = getNotificationCount(group.id)

                  return (
                  <div key={group.id} className="relative inline-block mt-2 mr-2">
                    <button
                      onClick={() => { setSelectedGroup(group.id); setPage(1); }}
                      className={cn(
                        "bg-white border text-sm font-medium py-2 px-4 shadow-[0_1px_2px_rgba(0,0,0,0.05)] hover:bg-gray-50 transition-colors",
                        selectedGroup === group.id ? "border-blue-500 text-blue-600" : "border-gray-200 text-gray-800"
                      )}
                    >
                      #{group.name}
                    </button>
                    {notificationCount > 0 && (
                      <div className="absolute -top-2.5 -right-2.5 bg-red-600 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center border border-white">
                        {notificationCount}
                      </div>
                    )}
                  </div>
                  )
                })}
              </div>
            ) : (
              <p className="text-sm text-gray-500 italic py-2">No user groups yet. Create one below!</p>
            )}
          </div>

          {/* All Groups / My Groups Toggle */}
          <div className="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
            <div className="flex border-b border-gray-200">
              <button
                onClick={() => { setGroupsViewTab('all'); setShowAllGroups(true); }}
                className={cn(
                  "flex-1 px-4 py-3 text-sm font-semibold transition-colors",
                  groupsViewTab === 'all'
                    ? "text-blue-600 border-b-2 border-blue-600 bg-blue-50"
                    : "text-gray-500 hover:text-gray-700 hover:bg-gray-50"
                )}
              >
                All Groups
              </button>
              <button
                onClick={() => { setGroupsViewTab('my'); setShowAllGroups(true); }}
                className={cn(
                  "flex-1 px-4 py-3 text-sm font-semibold transition-colors",
                  groupsViewTab === 'my'
                    ? "text-blue-600 border-b-2 border-blue-600 bg-blue-50"
                    : "text-gray-500 hover:text-gray-700 hover:bg-gray-50"
                )}
              >
                My Groups
              </button>
            </div>
          </div>

        </div>

      </div>

      {/* Create Group Modal */}
      {isCreateGroupModalOpen && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <div className="flex items-center justify-between p-4 border-b border-gray-200">
              <h3 className="text-lg font-semibold text-gray-800">Create Custom Group</h3>
              <button
                onClick={() => setIsCreateGroupModalOpen(false)}
                className="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <X className="w-5 h-5" />
              </button>
            </div>
            <div className="p-4 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Group Name</label>
                <input
                  type="text"
                  value={newGroupName}
                  onChange={(e) => setNewGroupName(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter group name"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                <textarea
                  value={newGroupDescription}
                  onChange={(e) => setNewGroupDescription(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                  rows={3}
                  placeholder="Enter group description"
                />
              </div>
            </div>
            <div className="flex items-center justify-end gap-2 p-4 border-t border-gray-200">
              <button
                onClick={() => setIsCreateGroupModalOpen(false)}
                className="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={() => {
                  if (newGroupName.trim()) {
                    createGroupMutation.mutate({
                      name: newGroupName,
                      description: newGroupDescription || undefined,
                    });
                  }
                }}
                disabled={!newGroupName.trim() || createGroupMutation.isPending}
                className={cn(
                  "px-4 py-2 text-sm font-semibold rounded transition-colors",
                  newGroupName.trim()
                    ? "bg-blue-600 text-white hover:bg-blue-700"
                    : "bg-gray-200 text-gray-400 cursor-not-allowed"
                )}
              >
                {createGroupMutation.isPending ? 'Creating...' : 'Create Group'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Mark All Read Confirmation Modal */}
      {showMarkAllReadConfirm && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg shadow-xl w-full max-w-sm overflow-hidden">
            <div className="p-4 border-b border-gray-200">
              <h3 className="text-base font-semibold text-gray-800">Mark all notifications as read?</h3>
              <p className="text-sm text-gray-500 mt-1">
                This will mark all visible unread notifications as read.
              </p>
            </div>
            <div className="flex items-center justify-end gap-2 p-4 border-t border-gray-200">
              <button
                onClick={() => setShowMarkAllReadConfirm(false)}
                className="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={handleMarkAllAsRead}
                disabled={markAllNotificationsReadMutation.isPending}
                className={cn(
                  "px-4 py-2 text-sm font-semibold rounded transition-colors",
                  markAllNotificationsReadMutation.isPending
                    ? "bg-gray-200 text-gray-500 cursor-not-allowed"
                    : "bg-blue-600 text-white hover:bg-blue-700"
                )}
              >
                {markAllNotificationsReadMutation.isPending ? 'Marking...' : 'Yes, mark all'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* All Groups / My Groups Modal */}
      {showAllGroups && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[80vh] overflow-hidden">
            <div className="flex items-center justify-between p-4 border-b border-gray-200">
              <h3 className="text-lg font-semibold text-gray-800">
                {groupsViewTab === 'all' ? 'All Groups' : 'My Groups'}
              </h3>
              <button
                onClick={() => setShowAllGroups(false)}
                className="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Tabs */}
            <div className="flex border-b border-gray-200">
              <button
                onClick={() => setGroupsViewTab('all')}
                className={cn(
                  "flex-1 px-4 py-3 text-sm font-semibold transition-colors",
                  groupsViewTab === 'all'
                    ? "text-blue-600 border-b-2 border-blue-600 bg-blue-50"
                    : "text-gray-500 hover:text-gray-700 hover:bg-gray-50"
                )}
              >
                All Groups
              </button>
              <button
                onClick={() => setGroupsViewTab('my')}
                className={cn(
                  "flex-1 px-4 py-3 text-sm font-semibold transition-colors",
                  groupsViewTab === 'my'
                    ? "text-blue-600 border-b-2 border-blue-600 bg-blue-50"
                    : "text-gray-500 hover:text-gray-700 hover:bg-gray-50"
                )}
              >
                My Groups
              </button>
            </div>

            {/* Groups List */}
            <div className="overflow-y-auto max-h-[calc(80vh-140px)] p-4">
              <div className="space-y-3">
                {(groupsViewTab === 'all' ? groups : myGroups).map((group) => (
                  <div
                    key={group.id}
                    className="flex items-center gap-4 p-4 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow cursor-pointer"
                    onClick={() => {
                      setSelectedGroup(group.id);
                      setShowAllGroups(false);
                      setPage(1);
                    }}
                  >
                    {/* Group Avatar */}
                    <div className="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center flex-shrink-0">
                      {group.icon ? (
                        <img src={group.icon} alt={group.name} className="w-full h-full rounded-full object-cover" />
                      ) : (
                        <Users className="w-6 h-6 text-white" />
                      )}
                    </div>

                    {/* Group Info */}
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2">
                        <h4 className="font-semibold text-gray-900 truncate">
                          {group.type === 'custom' || groupsViewTab === 'my' ? `#${group.name}` : group.name}
                        </h4>
                        {group.type === 'topic' && (
                          <Pin className="w-4 h-4 text-blue-600" />
                        )}
                      </div>
                      <p className="text-xs text-gray-500 mt-0.5">
                        {format(new Date(group.created_at || new Date()), 'MMMM dd yyyy, h:mm a')}
                      </p>
                      <div className="mt-2 inline-block">
                        <span className="px-2 py-1 bg-green-500 text-white text-[10px] font-bold rounded uppercase">
                          {group.members_count || 0} MEMBERS
                        </span>
                      </div>
                    </div>

                    {/* Options Menu */}
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                      }}
                      className="p-2 hover:bg-gray-100 rounded-full transition-colors"
                    >
                      <MoreVertical className="w-5 h-5 text-gray-400" />
                    </button>
                  </div>
                ))}

                {(groupsViewTab === 'all' ? groups : myGroups).length === 0 && (
                  <div className="text-center py-12 text-gray-500">
                    <Users className="w-12 h-12 mx-auto mb-3 text-gray-300" />
                    <p className="text-sm">
                      {groupsViewTab === 'all' ? 'No groups available' : 'You are not a member of any groups yet'}
                    </p>
                  </div>
                )}

                {/* Create Custom Group Button - Only in My Groups tab */}
                {groupsViewTab === 'my' && (
                  <div className="pt-4">
                    <button
                      onClick={() => {
                        setShowAllGroups(false);
                        setIsCreateGroupModalOpen(true);
                      }}
                      className="w-full bg-blue-600 text-white text-sm font-semibold py-3 px-4 rounded-lg shadow-sm hover:bg-blue-700 transition-colors flex items-center justify-center gap-2"
                    >
                      <Plus className="w-4 h-4" />
                      Create Custom Group
                    </button>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  )
}

