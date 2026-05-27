import { Outlet } from 'react-router-dom'
import { Sidebar } from './Sidebar'
import { Header } from './Header'
import { TwoFABanner } from './TwoFABanner'
import { Footer } from './Footer'

export function MainLayout() {
  return (
    <div className="min-h-screen bg-gray-100">
      <Sidebar />
      <div className="main-content">
        <Header />
        <TwoFABanner />
        <Outlet />
        <Footer />
      </div>
    </div>
  )
}
