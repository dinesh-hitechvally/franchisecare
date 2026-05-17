import { ReactNode } from 'react'
import { Card } from '../ui/Card'

interface PageHeaderProps {
  title: string
  description?: string
  actions?: ReactNode
  icon?: ReactNode
  variant?: 'default' | 'compact'
}

export function PageHeader({
  title,
  description,
  actions,
  icon,
  variant = 'default'
}: PageHeaderProps) {
  const isCompact = variant === 'compact'

  return (
    <Card className={`shadow-sm border-gray-200 ${isCompact ? 'px-6 py-3' : 'px-6 py-4'}`}>
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          {icon && (
            <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white flex-shrink-0">
              {icon}
            </div>
          )}
          <div>
            <h1 className={`font-bold text-gray-800 ${isCompact ? 'text-xl' : 'text-2xl'}`}>
              {title}
            </h1>
            {description && (
              <p className="text-sm text-gray-600 mt-1">{description}</p>
            )}
          </div>
        </div>
        {actions && (
          <div className="flex items-center gap-2">
            {actions}
          </div>
        )}
      </div>
    </Card>
  )
}
