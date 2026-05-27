import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import toast from 'react-hot-toast'

export function AddVersion() {
  const navigate = useNavigate()
  const [formData, setFormData] = useState({
    version: '',
    title: '',
    description: '',
    logout: false,
    refresh: false,
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    // API call would go here
    toast.success('Version added successfully')
    navigate('/versions/list')
  }

  return (
    <div className="page-content">
      <h1 className="page-title">Add Version</h1>

      <div style={{ 
        background: 'white', 
        borderRadius: '8px', 
        border: '1px solid #e5e7eb',
        padding: '24px'
      }}>
        <form onSubmit={handleSubmit}>
          {/* Version Number and Title - Side by side */}
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '24px', marginBottom: '24px' }}>
            <div>
              <label style={{ 
                display: 'block', 
                fontSize: '14px', 
                fontWeight: '500', 
                color: '#374151', 
                marginBottom: '8px' 
              }}>
                Version Number
              </label>
              <input
                type="text"
                value={formData.version}
                onChange={(e) => setFormData({ ...formData, version: e.target.value })}
                placeholder="Version Number *"
                style={{
                  width: '100%',
                  padding: '12px 16px',
                  border: '1px solid #d1d5db',
                  borderRadius: '6px',
                  fontSize: '14px',
                  color: '#6b7280',
                  outline: 'none'
                }}
                required
              />
            </div>
            <div>
              <label style={{ 
                display: 'block', 
                fontSize: '14px', 
                fontWeight: '500', 
                color: '#374151', 
                marginBottom: '8px' 
              }}>
                Version Title
              </label>
              <input
                type="text"
                value={formData.title}
                onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                placeholder="Version Title *"
                style={{
                  width: '100%',
                  padding: '12px 16px',
                  border: '1px solid #d1d5db',
                  borderRadius: '6px',
                  fontSize: '14px',
                  color: '#6b7280',
                  outline: 'none'
                }}
                required
              />
            </div>
          </div>

          {/* Version Description */}
          <div style={{ marginBottom: '24px' }}>
            <label style={{ 
              display: 'block', 
              fontSize: '14px', 
              fontWeight: '500', 
              color: '#374151', 
              marginBottom: '8px' 
            }}>
              Version Description
            </label>
            <textarea
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              placeholder="Version Description *"
              rows={5}
              style={{
                width: '100%',
                padding: '12px 16px',
                border: '1px solid #d1d5db',
                borderRadius: '6px',
                fontSize: '14px',
                color: '#6b7280',
                outline: 'none',
                resize: 'vertical',
                minHeight: '120px'
              }}
            />
          </div>

          {/* Checkboxes - Side by side */}
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '24px', marginBottom: '32px' }}>
            <label style={{ display: 'flex', alignItems: 'center', gap: '12px', cursor: 'pointer' }}>
              <input
                type="checkbox"
                checked={formData.logout}
                onChange={(e) => setFormData({ ...formData, logout: e.target.checked })}
                style={{
                  width: '18px',
                  height: '18px',
                  cursor: 'pointer',
                  accentColor: '#7c3aed'
                }}
              />
              <span style={{ fontSize: '14px', color: '#374151' }}>Logout/Login Required</span>
            </label>
            <label style={{ display: 'flex', alignItems: 'center', gap: '12px', cursor: 'pointer' }}>
              <input
                type="checkbox"
                checked={formData.refresh}
                onChange={(e) => setFormData({ ...formData, refresh: e.target.checked })}
                style={{
                  width: '18px',
                  height: '18px',
                  cursor: 'pointer',
                  accentColor: '#7c3aed'
                }}
              />
              <span style={{ fontSize: '14px', color: '#374151' }}>Refresh Required</span>
            </label>
          </div>

          {/* Action Buttons */}
          <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px' }}>
            <button 
              type="button" 
              onClick={() => navigate('/versions/list')}
              style={{
                padding: '10px 24px',
                background: '#ef4444',
                color: 'white',
                border: 'none',
                borderRadius: '4px',
                fontSize: '13px',
                fontWeight: '600',
                cursor: 'pointer',
                textTransform: 'uppercase',
                letterSpacing: '0.5px'
              }}
            >
              CANCEL
            </button>
            <button 
              type="submit"
              style={{
                padding: '10px 24px',
                background: '#7c3aed',
                color: 'white',
                border: 'none',
                borderRadius: '4px',
                fontSize: '13px',
                fontWeight: '600',
                cursor: 'pointer',
                textTransform: 'uppercase',
                letterSpacing: '0.5px'
              }}
            >
              ADD VERSION
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
