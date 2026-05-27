import { AlertTriangle } from 'lucide-react'

export function TwoFABanner() {
  return (
    <div className="twofa-banner">
      <AlertTriangle size={22} className="icon" />
      <div className="text">
        <div className="title">Two Factor Authentication</div>
        <div className="description">
          To enhance the security of your account, we recommend enabling Two-Factor Authentication (2FA). This additional layer of protection helps prevent unauthorized access to your account.
        </div>
      </div>
      <button className="btn btn-primary" style={{ whiteSpace: 'nowrap' }}>ENABLE NOW</button>
    </div>
  )
}
