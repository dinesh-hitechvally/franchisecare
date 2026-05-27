export function Footer() {
  return (
    <footer className="footer">
      <div style={{ color: '#6b7280' }}>Ver: 1.0</div>
      <div className="flex items-center gap-3">
        <div style={{ 
          background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)', 
          borderRadius: '4px', 
          padding: '3px 6px',
          display: 'flex',
          alignItems: 'center',
          gap: '4px'
        }}>
          <div style={{
            background: '#60a5fa',
            borderRadius: '50%',
            width: '18px',
            height: '18px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            fontSize: '10px'
          }}>
            🚐
          </div>
          <div style={{ color: 'white', fontWeight: 'bold', fontSize: '8px', lineHeight: '1.1' }}>
            <div>BLUE</div>
            <div>WHEELERS</div>
          </div>
        </div>
        <span style={{ color: '#9ca3af' }}>Copyright © FranchiseCare-2026</span>
      </div>
    </footer>
  )
}
