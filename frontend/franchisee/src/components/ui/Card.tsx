import { cn } from '../../lib/utils'

interface CardProps {
  children: React.ReactNode
  className?: string
  title?: string
  action?: React.ReactNode
  footer?: React.ReactNode
}

export function Card({ children, className, title, action, footer }: CardProps) {
  return (
    <div className={cn('card overflow-hidden', className)}>
      {(title || action) && (
        <div className="p-4 border-b border-secondary-200 flex items-center justify-between">
          {title && <h3 className="text-lg font-semibold text-secondary-900">{title}</h3>}
          {action && <div>{action}</div>}
        </div>
      )}
      <div className="card-content p-4">{children}</div>
      {footer && (
        <div className="p-4 border-t border-secondary-200 bg-secondary-50">
          {footer}
        </div>
      )}
    </div>
  )
}
