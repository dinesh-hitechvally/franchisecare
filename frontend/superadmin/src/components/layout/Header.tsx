import { ShoppingCart } from 'lucide-react'

export function Header() {
  return (
    <header className="header">
      <div className="header-logo">
        {/* Blue Wheelers Logo */}
        <div style={{ 
          background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)', 
          borderRadius: '8px', 
          padding: '6px 10px',
          display: 'flex',
          alignItems: 'center',
          gap: '6px'
        }}>
          <div style={{
            background: '#60a5fa',
            borderRadius: '50%',
            width: '32px',
            height: '32px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center'
          }}>
            <span style={{ fontSize: '18px' }}>🚐</span>
          </div>
          <div style={{ color: 'white', fontWeight: 'bold', fontSize: '11px', lineHeight: '1.2' }}>
            <div>BLUE</div>
            <div>WHEELERS</div>
          </div>
        </div>
        {/* DASH Logo */}
        <div style={{
          background: 'linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%)',
          borderRadius: '8px',
          padding: '8px 14px',
          fontWeight: 'bold',
          fontSize: '18px',
          fontStyle: 'italic',
          color: '#dc2626',
          fontFamily: 'Arial Black, sans-serif',
          textShadow: '1px 1px 0 #fff'
        }}>
          DASH
        </div>
      </div>
      
      <div className="header-badge">
        <ShoppingCart size={20} />
        <span className="badge-count">99+</span>
      </div>
    </header>
  )
}
