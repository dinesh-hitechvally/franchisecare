import { Routes, Route, Navigate } from 'react-router-dom'
import { useAuthStore } from './store/authStore'
import { MainLayout } from './components/layout/MainLayout'
import { LoginPage } from './pages/auth/LoginPage'
import { DashboardPage } from './pages/dashboard/DashboardPage'

// Booking Pages
import { BookingsPage } from './pages/bookings/BookingsPage'
import { ActiveBookingsPage } from './pages/bookings/ActiveBookingsPage'
import { ArchivedBookingsPage } from './pages/bookings/ArchivedBookingsPage'
import { CancelBookingsPage } from './pages/bookings/CancelBookingsPage'
import { CompletedBookingsPage } from './pages/bookings/CompletedBookingsPage'
import { ManageBookingsPage } from './pages/bookings/ManageBookingsPage'
import { NewBookingsPage } from './pages/bookings/NewBookingsPage'
import { RecurringBookingsPage } from './pages/bookings/RecurringBookingsPage'
import { CancelledRecurringPage } from './pages/bookings/CancelledRecurringPage'

// Calendar Pages
import { CalendarPage } from './pages/calendar/CalendarPage'

// Customer Pages
import { AddCustomerPage } from './pages/customers/AddCustomerPage'
import { ListCustomersPage } from './pages/customers/ListCustomersPage'
import { ArchivedCustomersPage } from './pages/customers/ArchivedCustomersPage'
import { InactiveCustomersPage } from './pages/customers/InactiveCustomersPage'

// Leads Pages
import { LeadsPage } from './pages/leads/LeadsPage'
import { NewLeadsPage } from './pages/leads/NewLeadsPage'
import { CompletedLeadsPage } from './pages/leads/CompletedLeadsPage'
import { SnoozeLeadsPage } from './pages/leads/SnoozeLeadsPage'
import { CancellationRequestPage } from './pages/leads/CancellationRequestPage'
import { MessageForOperatorPage } from './pages/leads/MessageForOperatorPage'

// Blockouts Pages
import { ListBlockoutsPage } from './pages/blockouts/ListBlockoutsPage'
import { NewBlockoutsPage } from './pages/blockouts/NewBlockoutsPage'
import { RecurringBlockoutsPage } from './pages/blockouts/RecurringBlockoutsPage'

// Finance Pages
import { ListIncomesPage } from './pages/finance/ListIncomesPage'
import { AddIncomePage } from './pages/finance/AddIncomePage'
import { IncomeCategoriesPage } from './pages/finance/IncomeCategoriesPage'
import { AddIncomeCategoryPage } from './pages/finance/AddIncomeCategoryPage'
import { RecurringIncomePage } from './pages/finance/RecurringIncomePage'
import { ListExpensePage } from './pages/finance/ListExpensePage'
import { AddExpensePage } from './pages/finance/AddExpensePage'
import { ExpenseCategoriesPage } from './pages/finance/ExpenseCategoriesPage'
import { RecurringExpensesPage } from './pages/finance/RecurringExpensesPage'

import { InventoryPage } from './pages/inventory/InventoryPage'
import { InwardGoodsPage } from './pages/inventory/InwardGoodsPage'
import { StockTakePage } from './pages/inventory/StockTakePage'
import { InventoryUsagePage } from './pages/inventory/InventoryUsagePage'
import { PlaceTreatOrderPage } from './pages/inventory/PlaceTreatOrderPage'
import { PlaceUniformOrderPage } from './pages/inventory/PlaceUniformOrderPage'
import { OfficeOrderPage } from './pages/inventory/OfficeOrderPage'
import { ShampooOrderPage } from './pages/inventory/ShampooOrderPage'
import { MarketingMaterialsPage } from './pages/inventory/MarketingMaterialsPage'
import { SystemManualsPage } from './pages/documents/SystemManualsPage'
import { TemplatesPage } from './pages/documents/TemplatesPage'
import { OtherFilesPage } from './pages/documents/OtherFilesPage'

import { CommunicationPage } from './pages/communication/CommunicationPage'
import { BuyCreditsPage } from './pages/communication/BuyCreditsPage'
import { SendEmailPage } from './pages/communication/SendEmailPage'
import { SendSMSPage } from './pages/communication/SendSMSPage'
import { EmailHistoryPage } from './pages/communication/EmailHistoryPage'
import { SMSHistoryPage } from './pages/communication/SMSHistoryPage'
import { MessageTemplatesPage } from './pages/communication/MessageTemplatesPage'

// Reports Pages
import { ReportPage } from './pages/report/ReportPage'
import { BookingReportsPage } from './pages/reports/BookingReportsPage'
import { CustomerReportsPage } from './pages/reports/CustomerReportsPage'
import { IncomeReportsPage } from './pages/reports/IncomeReportsPage'
import { ExpenseReportsPage } from './pages/reports/ExpenseReportsPage'
import { DailyDiaryPage } from './pages/reports/DailyDiaryPage'
import { BenchmarkingPage } from './pages/reports/BenchmarkingPage'
import { ServiceReportsPage } from './pages/reports/ServiceReportsPage'
import { SuburbReportsPage } from './pages/reports/SuburbReportsPage'
import { IncomeForecastPage } from './pages/reports/IncomeForecastPage'
import { ProfitLossPage } from './pages/reports/ProfitLossPage'
import { DetailProfitLossPage } from './pages/reports/DetailProfitLossPage'
import { GSTSummaryPage } from './pages/reports/GSTSummaryPage'
import { GSTDetailPage } from './pages/reports/GSTDetailPage'
import { TrackingReportPage } from './pages/reports/TrackingReportPage'

// Other Pages
import { ForumPage } from './pages/forum/ForumPage'
import { UserProfilePage } from './pages/user-profile/UserProfilePage'
import { LatestNewsPage } from './pages/news/LatestNewsPage'
import { NewsPage } from './pages/news/NewsPage'
import { SettingsPage } from './pages/settings/SettingsPage'
import { AccountSettingsPage } from './pages/settings/AccountSettingsPage'
import { ChangePasswordPage } from './pages/settings/ChangePasswordPage'
import { CompletedOrdersPage } from './pages/orders/CompletedOrdersPage'

function PrivateRoute({ children }: { children: React.ReactNode }) {
  const token = useAuthStore((state) => state.token)
  return token ? <>{children}</> : <Navigate to="/signin" replace />
}

export function AppRoutes() {
  return (
    <Routes>
      <Route path="/signin" element={<LoginPage />} />
      <Route
        path="/"
        element={
          <PrivateRoute>
            <MainLayout />
          </PrivateRoute>
        }
      >
        <Route index element={<Navigate to="/dashboard" replace />} />
        <Route path="dashboard" element={<DashboardPage />} />

        {/* Booking Routes */}
        <Route path="bookings" element={<BookingsPage />} />
        <Route path="bookings/active" element={<ActiveBookingsPage />} />
        <Route path="bookings/archived" element={<ArchivedBookingsPage />} />
        <Route path="bookings/cancelled" element={<CancelBookingsPage />} />
        <Route path="bookings/completed" element={<CompletedBookingsPage />} />
        <Route path="bookings/manage" element={<ManageBookingsPage />} />
        <Route path="bookings/new" element={<NewBookingsPage />} />
        <Route path="bookings/edit/:id" element={<NewBookingsPage />} />
        <Route path="bookings/recurring" element={<RecurringBookingsPage />} />
        <Route path="bookings/recurring/edit/:id" element={<NewBookingsPage />} />
        <Route path="bookings/cancelled-recurring" element={<CancelledRecurringPage />} />

        {/* Calendar Routes */}
        <Route path="calendar" element={<CalendarPage />} />
        <Route path="calendar/month" element={<CalendarPage />} />
        <Route path="calendar/week" element={<CalendarPage />} />
        <Route path="calendar/day" element={<CalendarPage />} />
        <Route path="calendar/agenda" element={<CalendarPage />} />

        {/* Customer Routes */}
        <Route path="customers/list" element={<ListCustomersPage />} />
        <Route path="customers/add" element={<AddCustomerPage />} />
        <Route path="customers/edit/:id" element={<AddCustomerPage />} />
        <Route path="customers/archived" element={<ArchivedCustomersPage />} />
        <Route path="customers/inactive" element={<InactiveCustomersPage />} />

        {/* Leads Routes */}
        <Route path="leads" element={<LeadsPage />} />
        <Route path="leads/new" element={<NewLeadsPage />} />
        <Route path="leads/completed" element={<CompletedLeadsPage />} />
        <Route path="leads/snooze" element={<SnoozeLeadsPage />} />
        <Route path="leads/cancellation-request" element={<CancellationRequestPage />} />
        <Route path="leads/message-operator" element={<MessageForOperatorPage />} />

        {/* Blockouts Routes */}
        <Route path="blockouts" element={<ListBlockoutsPage />} />
        <Route path="blockouts/new" element={<NewBlockoutsPage />} />
        <Route path="blockouts/recurring" element={<RecurringBlockoutsPage />} />

        {/* Finance Routes */}
        <Route path="finance/income" element={<ListIncomesPage />} />
        <Route path="finance/income/add" element={<AddIncomePage />} />
        <Route path="finance/income/view/:id" element={<AddIncomePage />} />
        <Route path="finance/income/edit/:id" element={<AddIncomePage />} />
        <Route path="finance/income/categories" element={<IncomeCategoriesPage />} />
        <Route path="finance/income/add-category" element={<AddIncomeCategoryPage />} />
        <Route path="finance/income/edit-category/:id" element={<AddIncomeCategoryPage />} />
        <Route path="finance/income/recurring" element={<RecurringIncomePage />} />
        <Route path="finance/expense" element={<ListExpensePage />} />
        <Route path="finance/expense/add" element={<AddExpensePage />} />
        <Route path="finance/expense/categories" element={<ExpenseCategoriesPage />} />
        <Route path="finance/expense/recurring" element={<RecurringExpensesPage />} />

        {/* Inventory Routes */}
        <Route path="inventory" element={<InventoryPage />} />
        <Route path="inventory/inward" element={<InwardGoodsPage />} />
        <Route path="inventory/stock-take" element={<StockTakePage />} />
        <Route path="inventory/usage" element={<InventoryUsagePage />} />
        <Route path="inventory/order-treats" element={<PlaceTreatOrderPage />} />
        <Route path="inventory/order-uniforms" element={<PlaceUniformOrderPage />} />
        <Route path="inventory/office-order" element={<OfficeOrderPage />} />
        <Route path="inventory/shampoo-order" element={<ShampooOrderPage />} />
        <Route path="inventory/marketing-materials" element={<MarketingMaterialsPage />} />

        {/* Communication Routes */}
        <Route path="communication" element={<CommunicationPage />} />
        <Route path="communication/buy-credits" element={<BuyCreditsPage />} />
        <Route path="communication/email/send" element={<SendEmailPage />} />
        <Route path="communication/email/history" element={<EmailHistoryPage />} />
        <Route path="communication/sms/send" element={<SendSMSPage />} />
        <Route path="communication/sms/history" element={<SMSHistoryPage />} />
        <Route path="communication/templates" element={<MessageTemplatesPage />} />

        {/* Reports Routes */}
        <Route path="reports" element={<ReportPage />} />
        <Route path="reports/bookings" element={<BookingReportsPage />} />
        <Route path="reports/customers" element={<CustomerReportsPage />} />
        <Route path="reports/income" element={<IncomeReportsPage />} />
        <Route path="reports/expenses" element={<ExpenseReportsPage />} />
        <Route path="reports/daily-diary" element={<DailyDiaryPage />} />
        <Route path="reports/benchmarking" element={<BenchmarkingPage />} />
        <Route path="reports/services" element={<ServiceReportsPage />} />
        <Route path="reports/suburbs" element={<SuburbReportsPage />} />
        <Route path="reports/income-forecast" element={<IncomeForecastPage />} />
        <Route path="reports/profit-loss" element={<ProfitLossPage />} />
        <Route path="reports/detail-profit-loss" element={<DetailProfitLossPage />} />
        <Route path="reports/gst-summary" element={<GSTSummaryPage />} />
        <Route path="reports/gst-detail" element={<GSTDetailPage />} />
        <Route path="reports/tracking" element={<TrackingReportPage />} />

        {/* Other Routes */}
        <Route path="forum" element={<ForumPage />} />
        <Route path="forum/profile" element={<UserProfilePage />} />
        <Route path="forum/settings" element={<AccountSettingsPage />} />
        <Route path="forum/user/:userId" element={<UserProfilePage />} />
        <Route path="news" element={<LatestNewsPage />} />
        <Route path="news/manage" element={<NewsPage />} />
        {/* Documents Routes */}
        <Route path="documents/manuals" element={<SystemManualsPage />} />
        <Route path="documents/templates" element={<TemplatesPage />} />
        <Route path="documents/other" element={<OtherFilesPage />} />
        <Route path="settings" element={<SettingsPage />} />
        <Route path="settings/manage-bookings" element={<ManageBookingsPage />} />
        <Route path="settings/change-password" element={<ChangePasswordPage />} />
        <Route path="orders/completed" element={<CompletedOrdersPage />} />
      </Route>
    </Routes>
  )
}
