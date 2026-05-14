import { useEffect, useRef } from 'react'
import { createPortal } from 'react-dom'

interface PortalMenuProps {
  isOpen: boolean
  onClose: () => void
  /** Coordinates computed from trigger button's getBoundingClientRect() */
  position: { top: number; left: number } | null
  children: React.ReactNode
  width?: number
}

/**
 * Renders a dropdown menu via a React portal fixed to the viewport.
 * Escapes all overflow:hidden/auto scroll containers so the menu is never clipped.
 *
 * Usage:
 *   const [openMenuId, setOpenMenuId] = useState<string | null>(null)
 *   const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)
 *
 *   // In the trigger button onClick:
 *   const rect = (e.currentTarget as HTMLButtonElement).getBoundingClientRect()
 *   setMenuPos({ top: rect.bottom + 4, left: rect.right - (width ?? 192) })
 *   setOpenMenuId(row.id)
 *
 *   // Render:
 *   <PortalMenu isOpen={openMenuId === row.id} onClose={() => { setOpenMenuId(null); setMenuPos(null) }} position={menuPos}>
 *     ...menu items...
 *   </PortalMenu>
 */
export function PortalMenu({ isOpen, onClose, position, children, width = 192 }: PortalMenuProps) {
  const menuRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (!isOpen) return
    const handleClick = (e: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
        onClose()
      }
    }
    document.addEventListener('mousedown', handleClick)
    return () => document.removeEventListener('mousedown', handleClick)
  }, [isOpen, onClose])

  if (!isOpen || !position) return null

  return createPortal(
    <div
      ref={menuRef}
      style={{ position: 'fixed', top: position.top, left: position.left, zIndex: 9999, width }}
      className="bg-white border border-gray-200 rounded-md shadow-lg py-1"
    >
      {children}
    </div>,
    document.body
  )
}
