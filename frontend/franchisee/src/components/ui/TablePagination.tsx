import { ChevronLeft, ChevronRight } from 'lucide-react'
import type { PaginationMeta } from '../../api/services'

type TablePaginationProps = {
  meta: PaginationMeta
  onPageChange: (page: number) => void
  onPerPageChange: (perPage: number) => void
  pageSizeOptions?: number[]
  isLoading?: boolean
}

const DEFAULT_PAGE_SIZES = [10, 25, 50, 100]

export function TablePagination({
  meta,
  onPageChange,
  onPerPageChange,
  pageSizeOptions = DEFAULT_PAGE_SIZES,
  isLoading = false,
}: TablePaginationProps) {
  const { current_page, last_page, per_page, total } = meta
  const from = total === 0 ? 0 : (current_page - 1) * per_page + 1
  const to = Math.min(current_page * per_page, total)
  const canPrev = current_page > 1 && !isLoading
  const canNext = current_page < last_page && !isLoading

  return (
    <div className="px-5 py-3 border-t border-gray-200 flex flex-wrap items-center justify-end gap-4 text-sm text-gray-600">
      <div className="flex items-center gap-2">
        <span>Rows per page:</span>
        <select
          value={per_page}
          onChange={(e) => {
            onPerPageChange(Number(e.target.value))
            onPageChange(1)
          }}
          disabled={isLoading}
          className="border border-gray-200 rounded p-1 outline-none text-gray-700 disabled:opacity-50"
        >
          {pageSizeOptions.map((n) => (
            <option key={n} value={n}>
              {n}
            </option>
          ))}
        </select>
      </div>
      <span className="whitespace-nowrap">
        {total === 0 ? '0 items' : `Showing ${from}–${to} of ${total}`}
      </span>
      <div className="flex items-center gap-2">
        <button
          type="button"
          aria-label="Previous page"
          onClick={() => canPrev && onPageChange(current_page - 1)}
          disabled={!canPrev}
          className={`p-1 rounded ${canPrev ? 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' : 'text-gray-300 cursor-not-allowed'}`}
        >
          <ChevronLeft className="w-5 h-5" />
        </button>
        <span className="min-w-[5rem] text-center tabular-nums">
          Page {current_page} of {Math.max(last_page, 1)}
        </span>
        <button
          type="button"
          aria-label="Next page"
          onClick={() => canNext && onPageChange(current_page + 1)}
          disabled={!canNext}
          className={`p-1 rounded ${canNext ? 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' : 'text-gray-300 cursor-not-allowed'}`}
        >
          <ChevronRight className="w-5 h-5" />
        </button>
      </div>
    </div>
  )
}
