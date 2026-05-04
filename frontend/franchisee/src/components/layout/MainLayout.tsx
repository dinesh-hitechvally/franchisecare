import { Outlet } from 'react-router-dom'
import { Sidebar } from './Sidebar'
import { Header } from './Header'
import { Footer } from './Footer'
import { ToastContainer } from '../ui/Toast'

export function MainLayout() {
  return (
    <div className="min-h-screen bg-gray-100 flex">
      <Sidebar />
      <div className="flex-1 flex flex-col min-w-0">
        <Header />
        <main className="flex-1 p-5 overflow-auto">
          <Outlet />
        </main>
        <Footer />
      </div>
      <ToastContainer />
    </div>
  )
}
