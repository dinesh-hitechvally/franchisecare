import { useState, useRef, useEffect, forwardRef } from 'react'
import { Clock } from 'lucide-react'
import { cn } from '../../lib/utils'

export interface TimePickerProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'onChange' | 'value'> {
  label?: string
  labelClassName?: string
  error?: string
  value?: string
  onChange?: (value: string) => void
  disabled?: boolean
  placeholder?: string
  required?: boolean
}

export const TimePicker = forwardRef<HTMLInputElement, TimePickerProps>(
  ({ className, label, labelClassName, error, value = '', onChange, disabled, placeholder = '--:-- --', required, ...props }, ref) => {
    const [isOpen, setIsOpen] = useState(false)
    const containerRef = useRef<HTMLDivElement>(null)
    const [inputValue, setInputValue] = useState(value)

    // Sync internal state with prop
    useEffect(() => {
      setInputValue(value)
    }, [value])

    const hours = Array.from({ length: 12 }, (_, i) => (i + 1).toString().padStart(2, '0'))
    const minutes = Array.from({ length: 12 }, (_, i) => (i * 5).toString().padStart(2, '0'))
    const periods = ['AM', 'PM']

    // Helper to parse current value into components
    const getComponents = () => {
      // Expecting format "HH:mm AM/PM" or "HH:mm"
      const match = inputValue.match(/(\d{1,2}):(\d{2})\s?(AM|PM)?/i)
      if (match) {
        return {
          h: match[1].padStart(2, '0'),
          m: match[2],
          p: (match[3] || 'AM').toUpperCase()
        }
      }
      return { h: '09', m: '00', p: 'AM' }
    }

    const { h: currentH, m: currentM, p: currentP } = getComponents()

    useEffect(() => {
      const handleClickOutside = (event: MouseEvent) => {
        if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
          setIsOpen(false)
        }
      }
      document.addEventListener('mousedown', handleClickOutside)
      return () => document.removeEventListener('mousedown', handleClickOutside)
    }, [])

    const handleComponentClick = (type: 'h' | 'm' | 'p', val: string) => {
      let newH = currentH
      let newM = currentM
      let newP = currentP

      if (type === 'h') newH = val
      if (type === 'm') newM = val
      if (type === 'p') newP = val

      const newValue = `${newH}:${newM} ${newP}`
      setInputValue(newValue)
      onChange?.(newValue)
    }

    const isBackspace = useRef(false)

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
      let val = e.target.value.toUpperCase()
      
      // Remove any character that isn't a digit, colon, space, A, P, or M
      val = val.replace(/[^0-9: APM]/g, '')

      // Apply strict validation logic while typing
      if (val.length >= 1) {
        if (!/[0-1]/.test(val[0])) val = ''
      }
      if (val.length >= 2) {
        const hour = parseInt(val.slice(0, 2))
        if (hour > 12 || hour === 0) {
           val = val.slice(0, 1)
        }
      }
      if (val.length === 2 && !val.includes(':') && !isBackspace.current) {
        val = val + ':'
      }
      if (val.length >= 4) {
        if (!/[0-5]/.test(val[3])) val = val.slice(0, 3)
      }
      
      // Only auto-suggest AM/PM if not backspacing
      if (val.length === 5 && !val.includes(' ') && !isBackspace.current) {
        val = val + ' AM'
      }

      if (val.length >= 7 && !isBackspace.current) {
        const char = val[6]
        if (char === 'P') {
          val = val.slice(0, 6) + 'PM'
        } else if (char === 'A') {
          val = val.slice(0, 6) + 'AM'
        }
      }

      // Limit length to 8 characters
      if (val.length > 8) {
        val = val.slice(0, 8)
      }

      setInputValue(val)
      onChange?.(val)
    }

    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
      isBackspace.current = e.key === 'Backspace'

      // Handle backspace properly for the auto-inserted characters
      if (e.key === 'Backspace') {
        if (inputValue.length === 3 && inputValue.endsWith(':')) {
          setInputValue(inputValue.slice(0, 1))
          e.preventDefault()
        } else if (inputValue.length === 6 && inputValue.endsWith(' ')) {
          setInputValue(inputValue.slice(0, 4))
          e.preventDefault()
        }
      }
      
      // Toggle AM/PM with 'A' or 'P' keys if cursor is in the period section
      const target = e.target as HTMLInputElement
      const start = target.selectionStart || 0
      if (start >= 6) {
        if (e.key.toLowerCase() === 'a') {
          handleComponentClick('p', 'AM')
          e.preventDefault()
        } else if (e.key.toLowerCase() === 'p') {
          handleComponentClick('p', 'PM')
          e.preventDefault()
        }
      }
    }

    const handleInputClick = (e: React.MouseEvent<HTMLInputElement>) => {
      const target = e.target as HTMLInputElement
      const start = target.selectionStart || 0
      
      // If user clicks on the AM/PM part, open dropdown
      if (start >= 6) {
        setIsOpen(true)
      }
    }

    return (
      <div className="w-full" ref={containerRef}>
        {label && (
          <label className={cn("block text-sm font-medium text-gray-700 mb-1", labelClassName)}>
            {label} {required && <span className="text-red-500">*</span>}
          </label>
        )}
        
        <div className="relative">
          <input
            {...props}
            ref={ref}
            type="text"
            disabled={disabled}
            value={inputValue}
            onChange={handleInputChange}
            onKeyDown={handleKeyDown}
            onClick={handleInputClick}
            placeholder={placeholder}
            className={cn(
              'w-full px-0 py-2 border-0 border-b border-gray-300 rounded-none bg-transparent text-sm font-medium transition-all outline-none min-h-[42px] focus:ring-0 focus:border-blue-600 pr-8',
              disabled ? 'text-gray-400 cursor-not-allowed border-gray-200' : 'cursor-text',
              error ? 'border-red-500' : '',
              className
            )}
          />
          
          <button
            type="button"
            disabled={disabled}
            onClick={() => setIsOpen(!isOpen)}
            className="absolute inset-y-0 right-0 flex items-center pr-1 text-gray-400 hover:text-blue-600 transition-colors"
          >
            <Clock className="w-4 h-4" />
          </button>

          {/* Dropdown Menu */}
          {isOpen && (
            <div className="absolute z-[100] mt-1 right-0 bg-white border border-gray-200 shadow-xl rounded-lg overflow-hidden flex animate-in fade-in zoom-in-95 duration-150 h-64">
              {/* Hours Column */}
              <div className="flex flex-col overflow-y-auto border-r border-gray-100 no-scrollbar w-16">
                <div className="text-[10px] font-bold text-gray-400 uppercase p-2 text-center sticky top-0 bg-white border-b border-gray-50">Hour</div>
                {hours.map((h) => (
                  <button
                    key={h}
                    type="button"
                    onClick={() => handleComponentClick('h', h)}
                    className={cn(
                      'px-3 py-2 text-sm font-medium transition-colors hover:bg-gray-50',
                      currentH === h ? 'bg-blue-50 text-blue-700' : 'text-gray-700'
                    )}
                  >
                    {h}
                  </button>
                ))}
              </div>

              {/* Minutes Column */}
              <div className="flex flex-col overflow-y-auto border-r border-gray-100 no-scrollbar w-16">
                <div className="text-[10px] font-bold text-gray-400 uppercase p-2 text-center sticky top-0 bg-white border-b border-gray-50">Min</div>
                {minutes.map((m) => (
                  <button
                    key={m}
                    type="button"
                    onClick={() => handleComponentClick('m', m)}
                    className={cn(
                      'px-3 py-2 text-sm font-medium transition-colors hover:bg-gray-50',
                      currentM === m ? 'bg-blue-50 text-blue-700' : 'text-gray-700'
                    )}
                  >
                    {m}
                  </button>
                ))}
              </div>

              {/* Period Column */}
              <div className="flex flex-col no-scrollbar w-16">
                <div className="text-[10px] font-bold text-gray-400 uppercase p-2 text-center sticky top-0 bg-white border-b border-gray-50">AM/PM</div>
                {periods.map((p) => (
                  <button
                    key={p}
                    type="button"
                    onClick={() => handleComponentClick('p', p)}
                    className={cn(
                      'px-3 py-2 text-sm font-medium transition-colors hover:bg-gray-50',
                      currentP === p ? 'bg-blue-50 text-blue-700' : 'text-gray-700'
                    )}
                  >
                    {p}
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>
        {error && <p className="mt-1 text-xs text-red-500">{error}</p>}
      </div>
    )
  }
)

TimePicker.displayName = 'TimePicker'
