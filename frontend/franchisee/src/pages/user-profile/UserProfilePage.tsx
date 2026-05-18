import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  ArrowLeft,
  Mail,
  Home,
  Phone,
  Image as ImageIcon,
  Trash2,
  Edit2,
  Heart,
  MessageSquare,
  Bold,
  Italic,
  Underline,
  Strikethrough,
  List,
  ListOrdered,
  Link2,
  Type,
  Search,
  Settings,
  ArrowRight,
} from 'lucide-react'
import { forumApi, usersApi } from '../../api/services'
import { useAuthStore } from '../../store/authStore'
import { useToastStore } from '../../store/toastStore'
import type { ForumThread } from '../../types'
import { cn } from '../../lib/utils'
import { formatDisplayDateTime } from '../../lib/timeFormatUtils'

// Sample photos for the sidebar
const samplePhotos = [
  { id: 1, url: '/images/operational-manual.png', alt: 'Operational Manual' },
  { id: 2, url: '/images/blue-wheelers.png', alt: 'Blue Wheelers' },
  { id: 3, url: '/images/promo.png', alt: 'Promo' },
]

export function UserProfilePage() {
  const { userId: paramUserId } = useParams<{ userId: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const authUser = useAuthStore((s) => s.user)
  const addToast = useToastStore((s) => s.addToast)

  // Handle both /forum/profile (no param) and /forum/user/:userId
  const userId = paramUserId || authUser?.id
  const isOwnProfileRoute = !paramUserId || userId === authUser?.id?.toString()

  const [activeTab, setActiveTab] = useState<'about' | 'overview'>('about')
  const [postContent, setPostContent] = useState('')
  const [editingPostId, setEditingPostId] = useState<string | number | null>(null)
  const [editContent, setEditContent] = useState('')
  const [commentContents, setCommentContents] = useState<Record<string, string>>({})
  const [replyingTo, setReplyingTo] = useState<string | number | null>(null)

  const { data: user, isLoading } = useQuery({
    queryKey: ['user-profile', userId],
    queryFn: () => usersApi.getById(userId as string),
    enabled: !!userId,
  })

  const { data: timelineResult } = useQuery({
    queryKey: ['user-profile-posts', userId],
    queryFn: () => usersApi.getPosts(userId as string, { per_page: 50 }),
    enabled: !!userId,
  })

  const timelinePosts = timelineResult?.data ?? []

  const createPostMutation = useMutation({
    mutationFn: (data: { content: string; topic?: string; group_id?: string | number }) => usersApi.createPost(data),
    onSuccess: () => {
      setPostContent('')
      queryClient.invalidateQueries({ queryKey: ['user-profile-posts', userId] })
      addToast('Post created successfully!', 'success')
    },
    onError: (error: any) => {
      addToast(error?.response?.data?.message || 'Failed to create post', 'error')
    },
  })

  const updatePostMutation = useMutation({
    mutationFn: (data: { threadId: string | number; content: string; topic?: string }) =>
      usersApi.updatePost(data.threadId, { content: data.content, topic: data.topic }),
    onSuccess: () => {
      setEditingPostId(null)
      setEditContent('')
      queryClient.invalidateQueries({ queryKey: ['user-profile-posts', userId] })
      addToast('Post updated successfully!', 'success')
    },
    onError: (error: any) => {
      addToast(error?.response?.data?.message || 'Failed to update post', 'error')
    },
  })

  const deletePostMutation = useMutation({
    mutationFn: (threadId: string | number) => forumApi.deleteThread(threadId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['user-profile-posts', userId] })
      addToast('Post deleted successfully!', 'success')
    },
    onError: (error: any) => {
      addToast(error?.response?.data?.message || 'Failed to delete post', 'error')
    },
  })

  const addCommentMutation = useMutation({
    mutationFn: (data: { threadId: string | number; content: string }) =>
      forumApi.addComment(String(data.threadId), data.content),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['user-profile-posts', userId] })
    },
  })

  const likeThreadMutation = useMutation({
    mutationFn: (threadId: string | number) => forumApi.likeThread(String(threadId)),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['user-profile-posts', userId] })
    },
  })

  const likeCommentMutation = useMutation({
    mutationFn: (commentId: string | number) => forumApi.likeComment(String(commentId)),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['user-profile-posts', userId] })
    },
  })

  const replyToCommentMutation = useMutation({
    mutationFn: (data: { commentId: string | number; content: string }) =>
      forumApi.replyToComment(String(data.commentId), data.content),
    onSuccess: () => {
      setReplyingTo(null)
      queryClient.invalidateQueries({ queryKey: ['user-profile-posts', userId] })
    },
  })

  const handleCreatePost = () => {
    if (!postContent.trim()) {
      addToast('Post content cannot be empty', 'error')
      return
    }
    createPostMutation.mutate({
      content: postContent,
      topic: undefined,
      group_id: undefined,
    })
  }

  const handleEditPost = (post: ForumThread) => {
    setEditingPostId(post.id)
    setEditContent(post.content)
  }

  const handleUpdatePost = () => {
    if (!editContent.trim()) {
      addToast('Post content cannot be empty', 'error')
      return
    }
    if (editingPostId) {
      updatePostMutation.mutate({
        threadId: editingPostId,
        content: editContent,
        topic: undefined,
      })
    }
  }

  const handleDeletePost = (postId: string | number) => {
    if (confirm('Are you sure you want to delete this post?')) {
      deletePostMutation.mutate(postId)
    }
  }

  const handleAddComment = (threadId: string | number) => {
    const content = commentContents[String(threadId)]?.trim()
    if (!content) return

    addCommentMutation.mutate({ threadId, content })
    setCommentContents((prev) => ({ ...prev, [String(threadId)]: '' }))
  }

  const handleReplyToComment = (commentId: string | number) => {
    const key = `reply-${commentId}`
    const content = commentContents[key]?.trim()
    if (!content) return

    replyToCommentMutation.mutate({ commentId, content })
    setCommentContents((prev) => ({ ...prev, [key]: '' }))
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <div className="text-gray-500">Loading...</div>
      </div>
    )
  }

  if (!user) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] gap-3">
        <div className="text-gray-500">User not found</div>
        <button
          onClick={() => navigate(-1)}
          className="text-sm text-blue-600 hover:text-blue-700 font-medium"
        >
          Go back
        </button>
      </div>
    )
  }

  return (
    <div className="min-h-[calc(100vh-64px)] bg-[#f3f5f8]">
      {/* Banner Profile Header */}
      <div className="bg-white shadow-sm mb-6">
        {/* Cover Banner */}
        <div className="relative h-48 md:h-64 bg-gradient-to-r from-amber-400 via-orange-400 to-red-400 overflow-hidden">
          {/* Back Button - Floating */}
          <button
            onClick={() => navigate(-1)}
            className="absolute top-4 left-4 z-10 w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center text-gray-700 hover:bg-white shadow-lg transition-all"
            aria-label="Go back"
          >
            <ArrowLeft className="w-5 h-5" />
          </button>

          {/* Search and Settings - Floating */}
          <div className="absolute top-4 right-4 z-10 flex items-center gap-2">
            <button className="w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center text-gray-700 hover:bg-white shadow-lg transition-all">
              <Search className="w-5 h-5" />
            </button>
            {isOwnProfileRoute && (
              <button
                onClick={() => navigate('/forum/settings')}
                className="flex items-center gap-2 px-4 py-2 bg-white/90 backdrop-blur-sm rounded-full text-gray-700 hover:bg-white shadow-lg transition-all text-sm font-medium"
              >
                <Settings className="w-4 h-4" />
                <span className="hidden md:inline">Account settings</span>
              </button>
            )}
          </div>

          {/* Decorative Pattern */}
          <div className="absolute inset-0 opacity-10">
            <div className="absolute top-0 right-0 w-96 h-96 bg-white rounded-full -translate-y-1/2 translate-x-1/2" />
            <div className="absolute bottom-0 left-0 w-64 h-64 bg-white rounded-full translate-y-1/2 -translate-x-1/2" />
          </div>
        </div>

        {/* Profile Info Section */}
        <div className="relative px-4 md:px-8 pb-6">
          {/* Avatar - Overlapping Banner */}
          <div className="flex flex-col md:flex-row md:items-end gap-4 -mt-16 md:-mt-20">
            <div className="relative">
              <img
                src={user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name || 'User')}&background=f59e0b&color=fff&size=160`}
                alt={user.name}
                className="w-32 h-32 md:w-40 md:h-40 rounded-full border-4 border-white shadow-xl bg-amber-100 object-cover"
              />
              {isOwnProfileRoute && (
                <button className="absolute bottom-2 right-2 w-8 h-8 bg-white rounded-full shadow-lg flex items-center justify-center text-gray-600 hover:text-amber-600 transition-colors border border-gray-200">
                  <Edit2 className="w-4 h-4" />
                </button>
              )}
            </div>

            {/* Name and Territory */}
            <div className="flex-1 md:pb-2">
              <h1 className="text-2xl md:text-3xl font-bold text-gray-900">{user.name}</h1>
              <p className="text-amber-600 font-medium mt-1">{user.territory || user.location || 'No territory set'}</p>
            </div>

            {/* Action Buttons */}
            <div className="flex gap-2 md:pb-2">
              <button className="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-medium text-sm shadow-sm transition-colors flex items-center gap-2">
                <Mail className="w-4 h-4" />
                Message
              </button>
              <button className="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium text-sm transition-colors">
                Follow
              </button>
            </div>
          </div>
        </div>
      </div>

      <div className="px-4 md:px-8 pb-6">
        {/* Main Content Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Left Column: About + Timeline */}
          <div className="lg:col-span-2 space-y-6">
            {/* About Card with Tabs */}
            <div className="bg-white rounded-lg border border-gray-200 shadow-sm">
              {/* Tabs Header */}
              <div className="flex border-b border-gray-200">
                <div className="flex-1 px-6 py-4">
                  <h2 className="text-lg font-semibold text-gray-800">About</h2>
                </div>
                <div className="flex">
                  <button
                    onClick={() => setActiveTab('overview')}
                    className={cn(
                      'px-6 py-4 text-sm font-medium border-b-2 transition-colors',
                      activeTab === 'overview'
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700'
                    )}
                  >
                    OVERVIEW
                  </button>
                </div>
              </div>

              {/* About Content */}
              <div className="p-6">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                  {/* Email */}
                  <div className="flex flex-col items-center text-center">
                    <div className="w-12 h-12 rounded-full bg-amber-50 flex items-center justify-center mb-3">
                      <Mail className="w-6 h-6 text-amber-500" />
                    </div>
                    <span className="text-xs text-amber-500 font-medium mb-1">Email</span>
                    <span className="text-sm text-gray-700">{user.email || 'No email'}</span>
                  </div>

                  {/* Territory */}
                  <div className="flex flex-col items-center text-center">
                    <div className="w-12 h-12 rounded-full bg-amber-50 flex items-center justify-center mb-3">
                      <Home className="w-6 h-6 text-amber-500" />
                    </div>
                    <span className="text-xs text-amber-500 font-medium mb-1">Territory</span>
                    <span className="text-sm text-gray-700">{user.territory || user.location || 'No territory set'}</span>
                  </div>

                  {/* Mobile */}
                  <div className="flex flex-col items-center text-center">
                    <div className="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center mb-3">
                      <Phone className="w-6 h-6 text-gray-400" />
                    </div>
                    <span className="text-xs text-gray-400 font-medium mb-1">Mobile</span>
                    <span className="text-sm text-gray-700">{user.phone || 'No phone'}</span>
                  </div>
                </div>
              </div>
            </div>

            {/* Timeline Posts Section - Header & Composer */}
            <div className="bg-white rounded-lg border border-gray-200 shadow-sm">
              <div className="px-6 py-4 border-b border-gray-200">
                <h2 className="text-lg font-semibold text-gray-800">Timeline Posts</h2>
              </div>

              {/* Post Composer (only for own profile) */}
              {isOwnProfileRoute && (
                <div className="p-6">
                  <div className="flex gap-4">
                    <div className="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                      <img
                        src={authUser?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(authUser?.name || 'User')}&background=9ca3af&color=fff`}
                        alt={authUser?.name}
                        className="w-12 h-12 rounded-full"
                      />
                    </div>
                    <div className="flex-1">
                      {/* Formatting Toolbar */}
                      <div className="flex flex-wrap gap-1 items-center text-gray-500 mb-3 border-b border-gray-100 pb-3">
                        <button className="p-1.5 hover:bg-gray-100 rounded" title="Bold">
                          <Bold className="w-4 h-4" />
                        </button>
                        <button className="p-1.5 hover:bg-gray-100 rounded" title="Italic">
                          <Italic className="w-4 h-4" />
                        </button>
                        <button className="p-1.5 hover:bg-gray-100 rounded" title="Underline">
                          <Underline className="w-4 h-4" />
                        </button>
                        <button className="p-1.5 hover:bg-gray-100 rounded" title="Strikethrough">
                          <Strikethrough className="w-4 h-4" />
                        </button>
                        <div className="w-px h-5 bg-gray-200 mx-1" />
                        <button className="p-1.5 hover:bg-gray-100 rounded" title="Heading 1">
                          <span className="text-xs font-bold">H<sub>1</sub></span>
                        </button>
                        <button className="p-1.5 hover:bg-gray-100 rounded" title="Heading 2">
                          <span className="text-xs font-bold">H<sub>2</sub></span>
                        </button>
                        <div className="w-px h-5 bg-gray-200 mx-1" />
                        <button className="p-1.5 hover:bg-gray-100 rounded" title="Bullet List">
                          <List className="w-4 h-4" />
                        </button>
                        <button className="p-1.5 hover:bg-gray-100 rounded" title="Numbered List">
                          <ListOrdered className="w-4 h-4" />
                        </button>
                        <button className="p-1.5 hover:bg-gray-100 rounded" title="Link">
                          <Link2 className="w-4 h-4" />
                        </button>
                        <button className="p-1.5 hover:bg-gray-100 rounded" title="Clear Formatting">
                          <Type className="w-4 h-4" />
                        </button>
                      </div>

                      {/* Textarea */}
                      <textarea
                        className="w-full text-sm outline-none resize-none placeholder-gray-400 min-h-[80px]"
                        placeholder="Start a Post..."
                        value={postContent}
                        onChange={(e) => setPostContent(e.target.value)}
                      />
                    </div>
                  </div>

                  {/* Post Actions */}
                  <div className="flex justify-between items-center mt-4 pt-4 border-t border-gray-100">
                    <button className="text-gray-500 font-medium text-sm flex items-center gap-2 hover:text-gray-700">
                      <ImageIcon className="w-5 h-5" /> Add Photos/Album
                    </button>
                    <button
                      onClick={handleCreatePost}
                      disabled={!postContent.trim() || createPostMutation.isPending}
                      className={cn(
                        'uppercase font-semibold text-sm tracking-wide px-4 py-2 rounded transition-colors',
                        postContent.trim()
                          ? 'text-gray-400 hover:text-blue-600'
                          : 'text-gray-300 cursor-not-allowed'
                      )}
                    >
                      {createPostMutation.isPending ? 'Posting...' : 'POST'}
                    </button>
                  </div>
                </div>
              )}
            </div>

            {/* Edit Post Form - Separate Card */}
            {editingPostId && (
              <div className="bg-white rounded-lg border border-orange-300 shadow-sm overflow-hidden">
                <div className="px-6 py-4 border-b border-orange-200 bg-orange-50 flex items-center justify-between">
                  <h4 className="text-sm font-semibold text-gray-900">Edit Post</h4>
                  <button
                    type="button"
                    onClick={() => setEditingPostId(null)}
                    className="text-gray-400 hover:text-gray-600 text-lg"
                  >
                    ×
                  </button>
                </div>
                <div className="p-6">
                  <textarea
                    value={editContent}
                    onChange={(e) => setEditContent(e.target.value)}
                    className="w-full h-28 px-3 py-2 border border-gray-200 rounded text-sm resize-none"
                    placeholder="Update your post..."
                  />
                  <div className="flex items-center justify-end gap-2 mt-3">
                    <button
                      type="button"
                      onClick={() => setEditingPostId(null)}
                      className="px-4 py-2 text-sm text-gray-600 hover:text-gray-900"
                    >
                      Cancel
                    </button>
                    <button
                      onClick={handleUpdatePost}
                      disabled={updatePostMutation.isPending || !editContent.trim()}
                      className="px-5 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                      {updatePostMutation.isPending ? 'Saving...' : 'Update'}
                    </button>
                  </div>
                </div>
              </div>
            )}

            {/* Individual Post Cards */}
            {timelinePosts.length > 0 ? (
              timelinePosts.map((thread) => (
                <div key={thread.id} className="bg-white rounded-lg border border-gray-200 shadow-sm">
                  <div className="p-6">
                    {/* Post Header */}
                    <div className="flex items-center gap-3 mb-4">
                      <img
                        src={thread.author?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(thread.author?.name || 'User')}&background=random`}
                        alt={thread.author?.name}
                        onClick={() => thread.author?.id && navigate(`/forum/user/${thread.author.id}`)}
                        className="w-12 h-12 rounded-full cursor-pointer hover:ring-2 hover:ring-blue-500 transition-all"
                      />
                      <div className="flex-1">
                        <h4
                          onClick={() => thread.author?.id && navigate(`/forum/user/${thread.author.id}`)}
                          className="text-gray-800 font-medium cursor-pointer hover:text-blue-600 transition-colors"
                        >
                          {thread.author?.name}
                        </h4>
                        <div className="text-gray-400 text-xs">
                          {thread.created_at ? formatDisplayDateTime(thread.created_at) : ''}
                        </div>
                      </div>
                      {isOwnProfileRoute && thread.author?.id?.toString() === authUser?.id?.toString() && (
                          <div className="flex gap-2">
                            <button
                              onClick={() => handleEditPost(thread)}
                              className="text-gray-400 hover:text-blue-600 p-1"
                              title="Edit post"
                            >
                              <Edit2 className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => handleDeletePost(thread.id)}
                              className="text-gray-400 hover:text-red-600 p-1"
                              title="Delete post"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          </div>
                        )}
                      </div>

                      {/* Post Content */}
                      <div className="text-gray-800 space-y-4 mb-6 text-[15px] leading-relaxed">
                        <p className="whitespace-pre-wrap">{thread.content}</p>
                      </div>

                      {/* Post Actions */}
                      <div className="flex items-center gap-6 text-sm text-gray-500 font-medium mb-6">
                        <button
                          onClick={() => likeThreadMutation.mutate(thread.id)}
                          className="flex items-center gap-1.5 hover:text-red-500 transition-colors"
                        >
                          <Heart className={cn('w-4 h-4', thread.liked && 'fill-red-500 text-red-500')} />
                          {thread.likes_count || 0} Likes
                        </button>
                        <button className="flex items-center gap-1.5 hover:text-blue-600 text-blue-500">
                          <MessageSquare className="w-4 h-4 fill-current" />
                          {thread.comments?.length || 0} Comments
                        </button>
                      </div>

                      {/* Comments */}
                      {thread.comments && thread.comments.length > 0 && (
                        <div className="space-y-4 mb-6 ml-4 border-l-2 border-gray-100 pl-4">
                          {thread.comments.map((comment) => (
                            <div key={comment.id} className="space-y-3">
                              <div className="flex gap-3 items-start">
                                <img
                                  src={comment.author?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(comment.author?.name || 'User')}&background=random`}
                                  alt={comment.author?.name}
                                  onClick={() => comment.author?.id && navigate(`/forum/user/${comment.author.id}`)}
                                  className="w-8 h-8 rounded-full cursor-pointer hover:ring-2 hover:ring-blue-500 transition-all"
                                />
                                <div className="flex-1">
                                  <div className="bg-gray-50 rounded-lg p-3">
                                    <div className="flex justify-between items-center mb-1">
                                      <span
                                        onClick={() => comment.author?.id && navigate(`/forum/user/${comment.author.id}`)}
                                        className="text-xs font-bold text-gray-700 cursor-pointer hover:text-blue-600 transition-colors"
                                      >
                                        {comment.author?.name}
                                      </span>
                                      <span className="text-[10px] text-gray-400">
                                        {formatDisplayDateTime(comment.created_at)}
                                      </span>
                                    </div>
                                    <p className="text-sm text-gray-600 whitespace-pre-wrap">{comment.content}</p>
                                  </div>

                                  <div className="flex gap-4 mt-2 text-xs">
                                    <button
                                      onClick={() => likeCommentMutation.mutate(comment.id)}
                                      className="flex items-center gap-1 text-gray-500 hover:text-red-500 transition-colors"
                                    >
                                      <Heart className={cn('w-3.5 h-3.5', comment.liked && 'fill-red-500 text-red-500')} />
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

                                  {/* Reply Form */}
                                  {replyingTo === comment.id && (
                                    <div className="mt-3 flex gap-3 items-start">
                                      <div className="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center font-bold text-blue-700 flex-shrink-0 text-xs">
                                        {authUser?.name?.slice(0, 2).toUpperCase() || 'U'}
                                      </div>
                                      <div className="flex-1 border border-gray-200 rounded-md bg-white">
                                        <textarea
                                          className="w-full text-sm outline-none resize-none placeholder-gray-500 p-2 min-h-[60px]"
                                          placeholder="Type your reply..."
                                          value={commentContents[`reply-${comment.id}`] || ''}
                                          onChange={(e) =>
                                            setCommentContents((prev) => ({
                                              ...prev,
                                              [`reply-${comment.id}`]: e.target.value,
                                            }))
                                          }
                                        />
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
                                              'text-xs font-semibold px-4 py-1 rounded transition-colors',
                                              commentContents[`reply-${comment.id}`]?.trim()
                                                ? 'bg-blue-600 text-white hover:bg-blue-700'
                                                : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                            )}
                                          >
                                            {replyToCommentMutation.isPending ? 'Sending...' : 'Reply'}
                                          </button>
                                        </div>
                                      </div>
                                    </div>
                                  )}

                                  {/* Replies */}
                                  {comment.replies && comment.replies.length > 0 && (
                                    <div className="mt-3 space-y-3 ml-6 border-l-2 border-gray-100 pl-3">
                                      {comment.replies.map((reply) => (
                                        <div key={reply.id} className="flex gap-2 items-start">
                                          <img
                                            src={reply.author?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(reply.author?.name || 'User')}&background=random`}
                                            alt={reply.author?.name}
                                            onClick={() => reply.author?.id && navigate(`/forum/user/${reply.author.id}`)}
                                            className="w-6 h-6 rounded-full cursor-pointer hover:ring-2 hover:ring-blue-500 transition-all"
                                          />
                                          <div className="flex-1">
                                            <div className="bg-blue-50 rounded-lg p-2">
                                              <div className="flex justify-between items-center mb-1">
                                                <span
                                                  onClick={() => reply.author?.id && navigate(`/forum/user/${reply.author.id}`)}
                                                  className="text-[11px] font-bold text-gray-700 cursor-pointer hover:text-blue-600 transition-colors"
                                                >
                                                  {reply.author?.name}
                                                </span>
                                                <span className="text-[10px] text-gray-400">
                                                  {formatDisplayDateTime(reply.created_at)}
                                                </span>
                                              </div>
                                              <p className="text-xs text-gray-600 whitespace-pre-wrap">{reply.content}</p>
                                            </div>
                                            <div className="flex gap-3 mt-1 text-xs">
                                              <button
                                                onClick={() => likeCommentMutation.mutate(reply.id)}
                                                className="flex items-center gap-1 text-gray-500 hover:text-red-500 transition-colors"
                                              >
                                                <Heart className={cn('w-3 h-3', reply.liked && 'fill-red-500 text-red-500')} />
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

                      {/* Add Comment */}
                      <div className="flex gap-4 items-start">
                        <div className="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center font-bold text-blue-700 flex-shrink-0 italic text-sm">
                          {authUser?.name?.slice(0, 2).toUpperCase() || 'BW'}
                        </div>
                        <div className="flex-1 border border-gray-200 rounded-md bg-white">
                          <div className="flex flex-wrap gap-2 p-2 border-b border-gray-100 text-gray-600">
                            <button className="p-1 hover:bg-gray-100 rounded text-xs font-bold w-6 h-6 flex justify-center items-center">B</button>
                            <button className="p-1 hover:bg-gray-100 rounded text-xs italic w-6 h-6 flex justify-center items-center">I</button>
                            <button className="p-1 hover:bg-gray-100 rounded text-xs underline w-6 h-6 flex justify-center items-center">U</button>
                            <div className="w-px h-4 bg-gray-200 mx-1 mt-1" />
                            <button className="p-1 hover:bg-gray-100 rounded"><ImageIcon className="w-3.5 h-3.5" /></button>
                          </div>
                          <textarea
                            className="w-full text-sm outline-none resize-none placeholder-gray-500 p-3 min-h-[50px] bg-white border-b border-gray-100"
                            placeholder="Type Comments"
                            value={commentContents[String(thread.id)] || ''}
                            onChange={(e) =>
                              setCommentContents((prev) => ({
                                ...prev,
                                [String(thread.id)]: e.target.value,
                              }))
                            }
                          />
                          <div className="p-3 flex justify-end">
                            <button
                              onClick={() => handleAddComment(thread.id)}
                              disabled={!commentContents[String(thread.id)]?.trim() || addCommentMutation.isPending}
                              className={cn(
                                'uppercase font-semibold px-6 py-2 rounded text-xs tracking-wide transition-colors',
                                commentContents[String(thread.id)]?.trim()
                                  ? 'text-blue-600 hover:text-blue-700'
                                  : 'text-gray-300 cursor-not-allowed bg-gray-50'
                              )}
                            >
                              {addCommentMutation.isPending ? 'Posting...' : 'Post'}
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                ))
            ) : (
              <div className="bg-white rounded-lg border border-gray-200 shadow-sm p-12 text-center text-gray-400 italic">
                No posts found.
              </div>
            )}
          </div>

          {/* Right Column: Photos */}
          <div className="space-y-6">
            <div className="bg-white rounded-lg border border-gray-200 shadow-sm">
              <div className="px-6 py-4 border-b border-gray-200">
                <h2 className="text-sm font-semibold text-gray-800 uppercase tracking-wide">Photos</h2>
              </div>
              <div className="p-4">
                <div className="grid grid-cols-3 gap-2">
                  {samplePhotos.map((photo) => (
                    <div
                      key={photo.id}
                      className="aspect-square bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center"
                    >
                      <ImageIcon className="w-8 h-8 text-gray-300" />
                    </div>
                  ))}
                  {[...Array(3)].map((_, i) => (
                    <div
                      key={`empty-${i}`}
                      className="aspect-square bg-gray-50 rounded-lg border-2 border-dashed border-gray-200 flex items-center justify-center"
                    >
                      <ImageIcon className="w-6 h-6 text-gray-300" />
                    </div>
                  ))}
                </div>
                <button className="flex items-center gap-2 text-amber-600 hover:text-amber-700 text-sm font-medium mt-4">
                  Go to gallery <ArrowRight className="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
