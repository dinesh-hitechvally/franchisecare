import { useState } from 'react'
import { cn } from '../../lib/utils'
import { ChevronDown, ChevronUp } from 'lucide-react'

interface TableColumn<T> {
  key: string
  title: string
  render?: (row: T) => React.ReactNode
  sortable?: boolean
}

interface TableProps<T> {
  columns: TableColumn<T>[]
  data: T[]
  keyExtractor: (row: T) => string
  isLoading?: boolean
  onSort?: (key: string, direction: 'asc' | 'desc') => void
  sortColumn?: string
  sortDirection?: 'asc' | 'desc'
  emptyMessage?: string
}

export function Table<T>({
  columns,
  data,
  keyExtractor,
  isLoading,
  onSort,
  sortColumn,
  sortDirection,
  emptyMessage = 'No data available',
}: TableProps<T>) {
  const [internalSort, setInternalSort] = useState<{ column: string; direction: 'asc' | 'desc' } | null>(null)

  const handleSort = (columnKey: string) => {
    if (!onSort && !columns.find(c => c.key === columnKey)?.sortable) return
    
    const currentDirection = sortColumn === columnKey || internalSort?.column === columnKey
      ? sortDirection || internalSort?.direction
      : null
    
    const newDirection = currentDirection === 'asc' ? 'desc' : 'asc'
    
    if (onSort) {
      onSort(columnKey, newDirection)
    } else {
      setInternalSort({ column: columnKey, direction: newDirection })
    }
  }

  const getSortIcon = (columnKey: string) => {
    const activeColumn = sortColumn || internalSort?.column
    const activeDirection = sortColumn ? sortDirection : internalSort?.direction
    
    if (activeColumn !== columnKey) return null
    
    return activeDirection === 'asc' ? (
      <ChevronUp className="w-4 h-4" />
    ) : (
      <ChevronDown className="w-4 h-4" />
    )
  }

  if (isLoading) {
    return (
      <div className="w-full">
        {[...Array(5)].map((_, i) => (
          <div key={i} className="flex gap-4 p-4 border-b border-secondary-100">
            {[...Array(columns.length)].map((_, j) => (
              <div key={j} className="flex-1 h-8 bg-secondary-200 rounded animate-pulse" />
            ))}
          </div>
        ))}
      </div>
    )
  }

  if (data.length === 0) {
    return (
      <div className="overflow-x-auto">
        <table className="w-full">
          <thead>
            <tr className="border-b border-secondary-200 bg-secondary-50">
              {columns.map((column) => (
                <th
                  key={column.key}
                  className="px-4 py-3 text-left text-sm font-semibold text-secondary-700"
                >
                  {column.title}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colSpan={columns.length} className="text-center py-12 text-secondary-500">
                {emptyMessage}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    )
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full">
        <thead>
          <tr className="border-b border-secondary-200 bg-secondary-50">
            {columns.map((column) => (
              <th
                key={column.key}
                className={cn(
                  'px-4 py-3 text-left text-sm font-semibold text-secondary-700',
                  (column.sortable || onSort) && 'cursor-pointer hover:bg-secondary-100'
                )}
                onClick={() => handleSort(column.key)}
              >
                <div className="flex items-center gap-1">
                  {column.title}
                  {getSortIcon(column.key)}
                </div>
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {data.map((row, index) => (
            <tr
              key={keyExtractor(row)}
              className={cn(
                'border-b border-secondary-100 hover:bg-secondary-50 transition-colors',
                index % 2 === 0 ? 'bg-white' : 'bg-secondary-50'
              )}
            >
              {columns.map((column) => (
                <td key={column.key} className="px-4 py-3 text-sm text-secondary-700">
                  {column.render ? column.render(row) : (row as Record<string, unknown>)[column.key] as React.ReactNode}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
