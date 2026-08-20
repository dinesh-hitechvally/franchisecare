import { useState, useRef, useEffect, forwardRef } from 'react'
import { ChevronDown, Check, Search } from 'lucide-react'
import { cn } from '../../lib/utils'

export interface SelectOption {
  label: string
  value: string | number
}

export interface SelectProps extends Omit<React.HTMLAttributes<HTMLDivElement>, 'onChange'> {
  label?: string
  error?: string
  options: SelectOption[]
  value?: string | number
  onChange?: (value: string | number) => void
  disabled?: boolean
  placeholder?: string
  /** Show a search input inside the dropdown panel to filter options by label */
  searchable?: boolean
  searchPlaceholder?: string
}

export const Select = forwardRef<HTMLDivElement, SelectProps>(
  ({ className, label, error, options = [], value, onChange, disabled, placeholder = 'Select an option', searchable = false, searchPlaceholder = 'Search...', ...props }, ref) => {
    const [isOpen, setIsOpen] = useState(false)
    const [query, setQuery] = useState('')
    const containerRef = useRef<HTMLDivElement>(null)
    const searchInputRef = useRef<HTMLInputElement>(null)

    // Close when clicking outside
    useEffect(() => {
      const handleClickOutside = (event: MouseEvent) => {
        if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
          setIsOpen(false)
        }
      }
      document.addEventListener('mousedown', handleClickOutside)
      return () => document.removeEventListener('mousedown', handleClickOutside)
    }, [])

    // Reset the search query each time the dropdown opens, and focus the search input
    useEffect(() => {
      if (isOpen && searchable) {
        setQuery('')
        searchInputRef.current?.focus()
      }
    }, [isOpen, searchable])

    const selectedOption = options.find(opt => opt.value === value)

    const visibleOptions = searchable && query
      ? options.filter(opt => opt.label.toLowerCase().includes(query.toLowerCase()))
      : options

    const handleSelect = (optionValue: string | number) => {
      if (disabled) return
      onChange?.(optionValue)
      setIsOpen(false)
    }

    return (
      <div className="w-full" ref={containerRef}>
        {label && (
          <label className="block text-sm font-medium text-gray-700 mb-1">
            {label}
          </label>
        )}
        
        <div className="relative" ref={ref} {...props}>
          {/* Trigger Button */}
          <button
            type="button"
            disabled={disabled}
            onClick={() => setIsOpen(!isOpen)}
            className={cn(
              'w-full flex justify-between items-center px-3 py-2 pr-10 border rounded-lg bg-white text-sm font-medium transition-all text-left outline-none min-h-[42px]',
              isOpen ? 'ring-2 ring-indigo-500 border-transparent shadow-sm' : 'border-gray-300 hover:border-gray-400',
              disabled ? 'bg-gray-50 text-gray-400 cursor-not-allowed border-gray-200' : 'cursor-pointer',
              error ? 'border-red-500 ring-red-500' : '',
              className
            )}
          >
            <span className={cn('block truncate', !selectedOption && 'text-gray-400')}>
              {selectedOption ? selectedOption.label : placeholder}
            </span>
            <span className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
              <ChevronDown className={cn('w-4 h-4 transition-transform duration-200', isOpen && 'rotate-180')} />
            </span>
          </button>

          {/* Dropdown Menu */}
          {isOpen && (
            <div className="absolute z-[100] mt-1 w-full bg-white border border-gray-100 shadow-xl rounded-lg py-1.5 animate-in fade-in zoom-in-95 duration-150">
              {searchable && (
                <div className="px-2 pb-1.5 mb-1 border-b border-gray-100">
                  <div className="relative">
                    <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                    <input
                      ref={searchInputRef}
                      type="text"
                      value={query}
                      onChange={(e) => setQuery(e.target.value)}
                      onClick={(e) => e.stopPropagation()}
                      placeholder={searchPlaceholder}
                      className="w-full pl-8 pr-2 py-1.5 text-sm border border-gray-200 rounded-md outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                  </div>
                </div>
              )}

              <div className="overflow-auto max-h-52">
                {visibleOptions.length === 0 ? (
                  <div className="px-3 py-10 text-center text-xs text-gray-400 italic">No options found</div>
                ) : (
                  visibleOptions.map((option) => {
                    const isSelected = option.value === value
                    return (
                      <button
                        key={option.value}
                        type="button"
                        onClick={() => handleSelect(option.value)}
                        className={cn(
                          'w-full flex items-center justify-between px-3 py-2 text-sm font-medium transition-colors text-left',
                          isSelected
                            ? 'bg-indigo-50 text-indigo-700'
                            : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'
                        )}
                      >
                        <span className="truncate">{option.label}</span>
                        {isSelected && <Check className="w-4 h-4" />}
                      </button>
                    )
                  })
                )}
              </div>
            </div>
          )}
        </div>
        {error && <p className="mt-1 text-sm text-red-500">{error}</p>}
      </div>
    )
  }
)

Select.displayName = 'Select'
