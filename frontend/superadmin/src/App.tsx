import { Routes, Route, Navigate } from 'react-router-dom'
import { Toaster } from 'react-hot-toast'
import { MainLayout } from './components/layout/MainLayout'
import { Dashboard } from './pages/Dashboard'
import { ListVersions } from './pages/versions/ListVersions'
import { AddVersion } from './pages/versions/AddVersion'
import { ListMembers, AddMember, ManageAdmins, SuspendedLeadMembers } from './pages/members'
import { ActiveBookings, CompletedBookings, CancelledBookings } from './pages/bookings'
import { ListCustomers } from './pages/customers'
import { ListSuburb, AddSuburb } from './pages/suburb'
import { ListServices, AddService, ListGroups, AddGroup } from './pages/services'
import { ListCategories, AddCategory, ListPosts, AddPost, ListTopics, AddTopic, ListForumGroups, AddForumGroup } from './pages/forum'
import { ListTickets, TicketDetails } from './pages/support-tickets'

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
          <Route path="bookings">
            <Route index element={<Navigate to="active" replace />} />
            <Route path="active" element={<ActiveBookings />} />
            <Route path="completed" element={<CompletedBookings />} />
            <Route path="cancelled" element={<CancelledBookings />} />
          </Route>
          <Route path="customers">
            <Route index element={<Navigate to="list" replace />} />
            <Route path="list" element={<ListCustomers />} />
          </Route>
          <Route path="suburb">
            <Route index element={<Navigate to="list" replace />} />
            <Route path="list" element={<ListSuburb />} />
            <Route path="add" element={<AddSuburb />} />
            <Route path="edit/:id" element={<AddSuburb />} />
          </Route>
          <Route path="services">
            <Route index element={<Navigate to="list" replace />} />
            <Route path="list" element={<ListServices />} />
            <Route path="add" element={<AddService />} />
            <Route path="edit/:id" element={<AddService />} />
            <Route path="list-groups" element={<ListGroups />} />
            <Route path="add-groups" element={<AddGroup />} />
            <Route path="edit-groups/:id" element={<AddGroup />} />
          </Route>
          <Route path="forum">
            <Route index element={<Navigate to="list-categories" replace />} />
            <Route path="list-categories" element={<ListCategories />} />
            <Route path="add-categories" element={<AddCategory />} />
            <Route path="edit-category/:id" element={<AddCategory />} />
            <Route path="list-posts" element={<ListPosts />} />
            <Route path="add-posts" element={<AddPost />} />
            <Route path="edit-post/:id" element={<AddPost />} />
            <Route path="list-topics" element={<ListTopics />} />
            <Route path="add-topics" element={<AddTopic />} />
            <Route path="edit-topic/:id" element={<AddTopic />} />
            <Route path="list-groups" element={<ListForumGroups />} />
            <Route path="add-groups" element={<AddForumGroup />} />
            <Route path="edit-group/:id" element={<AddForumGroup />} />
          </Route>
          <Route path="support-tickets">
            <Route index element={<Navigate to="list" replace />} />
            <Route path="list" element={<ListTickets />} />
            <Route path=":id" element={<TicketDetails />} />
          </Route>
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
