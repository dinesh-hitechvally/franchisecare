import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { TablePagination } from '../../components/ui/TablePagination'
import { newsApi } from '../../api/services'
import { format } from 'date-fns'
import { cn } from '../../lib/utils'

const categories = [
  { name: 'All News', description: 'All News', color: 'bg-[#4a5ebc]' },
  { name: 'General', description: 'General Information News', color: 'bg-[#17a2b8]' },
  { name: 'Notice', description: 'Important Notices', color: 'bg-[#ffc107]' },
  { name: 'Mate', description: 'Mate News', color: 'bg-[#e83e8c]' },
]

export function LatestNewsPage() {
  const [activeTab, setActiveTab] = useState('All News')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(10)

  const { data: listResult, isLoading } = useQuery({
    queryKey: ['news-paginated', activeTab, page, perPage],
    queryFn: () => newsApi.getPaginated({
      page,
      per_page: perPage,
      isPublished: true,
      category: activeTab !== 'All News' ? activeTab : undefined
    }),
  })

  const articles = listResult?.data ?? []
  const listMeta = listResult?.meta

  const handleTabChange = (tabName: string) => {
    setActiveTab(tabName)
    setPage(1)
  }

  return (
    <div className="space-y-6">
      {/* Category Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {categories.map((cat) => (
          <button
            key={cat.name}
            onClick={() => handleTabChange(cat.name)}
            className={cn(
              "p-4 rounded-lg text-white transition-all transform hover:scale-[1.02] text-left relative overflow-hidden h-24",
              cat.color,
              activeTab === cat.name ? "ring-4 ring-white/30" : "opacity-90"
            )}
          >
            <div className="relative z-10">
              <h3 className="text-lg font-bold">{cat.name}</h3>
              <p className="text-xs opacity-90">{cat.description}</p>
            </div>
            {/* Subtle background decoration */}
            <div className="absolute right-[-10%] bottom-[-20%] opacity-20 transform rotate-12">
               <svg width="100" height="100" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19 20H5V20C3.89543 20 3 19.1046 3 18V5C3 3.89543 3.89543 3 5 3H19C20.1046 3 21 3.89543 21 5V18C21 19.1046 20.1046 20 19 20Z" stroke="white" strokeWidth="2" />
               </svg>
            </div>
          </button>
        ))}
      </div>

      {/* Main Content Card */}
      <Card className="border-none shadow-sm overflow-hidden bg-white">
        <div className="border-b border-gray-100">
          <div className="flex items-center gap-2 px-6 py-4">
            <div className="w-2 h-2 bg-black rounded-full" />
            <h2 className="text-sm font-bold text-gray-800 uppercase tracking-wider">Latest News</h2>
          </div>
          
          {/* Sub-navigation tabs */}
          <div className="flex px-6 border-t border-gray-50 overflow-x-auto no-scrollbar">
            {categories.map((cat) => (
              <button
                key={cat.name}
                onClick={() => handleTabChange(cat.name)}
                className={cn(
                  "px-8 py-3 text-[11px] font-bold uppercase tracking-widest transition-colors relative whitespace-nowrap",
                  activeTab === cat.name 
                    ? "text-blue-600 after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-blue-600" 
                    : "text-gray-400 hover:text-gray-600"
                )}
              >
                {cat.name}
              </button>
            ))}
          </div>
        </div>

        <div className="divide-y divide-gray-50">
          {isLoading ? (
            <div className="p-12 text-center text-gray-400 italic text-sm">
              Loading news...
            </div>
          ) : articles.length > 0 ? (
            articles.map((article) => (
              <div key={article.id} className="flex gap-4 p-6 hover:bg-gray-50/50 transition-colors group">
                <div className="flex-shrink-0 pt-1">
                  <div className={cn(
                    "w-12 h-12 rounded flex items-center justify-center text-white font-bold text-xs uppercase shadow-sm transform group-hover:rotate-3 transition-transform",
                    article.category === 'Notice' ? 'bg-[#ffc107]' : 
                    article.category === 'General' ? 'bg-[#17a2b8]' : 
                    article.category === 'Mate' ? 'bg-[#e83e8c]' : 'bg-[#4a5ebc]'
                  )}>
                    {(article.category || 'GENE').slice(0, 4)}
                  </div>
                </div>
                <div className="flex-1 space-y-1">
                  <div className="flex items-center justify-between">
                    <span className={cn(
                      "text-[10px] font-bold uppercase tracking-widest",
                      article.category === 'Notice' ? 'text-[#ffc107]' : 
                      article.category === 'General' ? 'text-[#17a2b8]' : 
                      article.category === 'Mate' ? 'text-[#e83e8c]' : 'text-[#4a5ebc]'
                    )}>
                      {article.category}
                    </span>
                    <span className="text-[10px] text-gray-400 font-medium">
                      {article.published_at ? format(new Date(article.published_at), 'EEEE, do MMM yyyy') : format(new Date(article.created_at), 'EEEE, do MMM yyyy')}
                    </span>
                  </div>
                  <h3 className="text-sm font-bold text-gray-800 group-hover:text-blue-600 transition-colors">
                    {article.title}
                  </h3>
                  <p className="text-xs text-gray-500 leading-relaxed max-w-4xl">
                    {article.content}
                  </p>
                </div>
              </div>
            ))
          ) : (
            <div className="p-12 text-left text-gray-400 italic text-sm">
              No news articles found in this category.
            </div>
          )}
        </div>

        {listMeta && (
          <div className="px-6 py-4 border-t border-gray-50">
            <TablePagination
              meta={listMeta}
              onPageChange={setPage}
              onPerPageChange={setPerPage}
              isLoading={isLoading}
            />
          </div>
        )}
      </Card>
    </div>
  )
}
