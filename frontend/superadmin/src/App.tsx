import { Routes, Route, Navigate } from 'react-router-dom'
import { Toaster } from 'react-hot-toast'
import { MainLayout } from './components/layout/MainLayout'
import { Dashboard } from './pages/Dashboard'
import { ListVersions } from './pages/versions/ListVersions'
import { AddVersion } from './pages/versions/AddVersion'
import { ListMembers, AddMember, ManageAdmins, SuspendedLeadMembers } from './pages/members'

function App() {
  return (
    <>
      <Toaster position="top-right" />
      <Routes>
        <Route path="/" element={<MainLayout />}>
          <Route index element={<Navigate to="/dashboard" replace />} />
          <Route path="dashboard" element={<Dashboard />} />
          <Route path="versions">
            <Route index element={<Navigate to="list" replace />} />
            <Route path="list" element={<ListVersions />} />
            <Route path="add" element={<AddVersion />} />
          </Route>
          <Route path="members">
            <Route index element={<Navigate to="list" replace />} />
            <Route path="list" element={<ListMembers />} />
            <Route path="add" element={<AddMember />} />
            <Route path="edit/:id" element={<AddMember />} />
            <Route path="admins" element={<ManageAdmins />} />
            <Route path="suspended" element={<SuspendedLeadMembers />} />
          </Route>
          <Route path="bookings/*" element={<PlaceholderPage title="Bookings" />} />
          <Route path="customers/*" element={<PlaceholderPage title="Customers" />} />
          <Route path="suburb/*" element={<PlaceholderPage title="Suburb" />} />
          <Route path="services/*" element={<PlaceholderPage title="Services" />} />
          <Route path="forum/*" element={<PlaceholderPage title="Forum" />} />
          <Route path="inventory/*" element={<PlaceholderPage title="Inventory" />} />
          <Route path="uniform/*" element={<PlaceholderPage title="Uniform" />} />
          <Route path="flyers/*" element={<PlaceholderPage title="Flyers" />} />
          <Route path="leads/*" element={<PlaceholderPage title="Leads" />} />
          <Route path="orders/*" element={<PlaceholderPage title="Orders" />} />
          <Route path="financial/*" element={<PlaceholderPage title="Financial" />} />
        </Route>
      </Routes>
    </>
  )
}

function PlaceholderPage({ title }: { title: string }) {
  return (
    <div className="page-content">
      <h1 className="page-title">{title}</h1>
      <div className="card">
        <div className="card-body p-8 text-center text-gray-500">
          This page is under development
        </div>
      </div>
    </div>
  )
}

export default App
