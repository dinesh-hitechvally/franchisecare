#!/bin/bash
# Script to update all pages with PageHeader component

# This script will be used to track which pages need PageHeader updates

PAGES_TO_UPDATE=(
  # Booking Pages
  "bookings/ArchivedBookingsPage.tsx"
  "bookings/CancelBookingsPage.tsx"
  "bookings/CancelledRecurringPage.tsx"
  "bookings/CompletedBookingsPage.tsx"
  "bookings/ManageBookingsPage.tsx"
  "bookings/NewBookingsPage.tsx"
  "bookings/RecurringBookingsPage.tsx"
  "bookings/BookingsPage.tsx"

  # Blockouts
  "blockouts/ListBlockoutsPage.tsx"
  "blockouts/NewBlockoutsPage.tsx"
  "blockouts/RecurringBlockoutsPage.tsx"

  # Customers
  "customers/AddCustomerPage.tsx"
  "customers/AddCustomersPage.tsx"
  "customers/ArchivedCustomersPage.tsx"
  "customers/CustomersPage.tsx"
  "customers/InactiveCustomersPage.tsx"
  "customers/ListCustomersPage.tsx"

  # Leads
  "leads/CancellationRequestPage.tsx"
  "leads/CompletedLeadsPage.tsx"
  "leads/LeadsPage.tsx"
  "leads/MessageForOperatorPage.tsx"
  "leads/NewLeadsPage.tsx"
  "leads/SnoozeLeadsPage.tsx"

  # Finance - Income
  "finance/AddIncomePage.tsx"
  "finance/AddIncomeCategoryPage.tsx"
  "finance/IncomeCategoriesPage.tsx"
  "finance/IncomePage.tsx"
  "finance/ListIncomesPage.tsx"
  "finance/RecurringIncomePage.tsx"

  # Finance - Expense
  "finance/AddExpensePage.tsx"
  "finance/ExpenseCategoriesPage.tsx"
  "finance/ExpensePage.tsx"
  "finance/ListExpensePage.tsx"
  "finance/RecurringExpensesPage.tsx"

  # Inventory
  "inventory/InwardGoodsPage.tsx"
  "inventory/InventoryOrdersPage.tsx"
  "inventory/InventoryPage.tsx"
  "inventory/InventoryUsagePage.tsx"
  "inventory/MarketingMaterialsPage.tsx"
  "inventory/OfficeOrderPage.tsx"
  "inventory/PlaceTreatOrderPage.tsx"
  "inventory/PlaceUniformOrderPage.tsx"
  "inventory/ShampooOrderPage.tsx"
  "inventory/StockTakePage.tsx"

  # Communication
  "communication/BuyCreditsPage.tsx"
  "communication/CommunicationPage.tsx"
  "communication/EmailHistoryPage.tsx"
  "communication/MessageTemplatesPage.tsx"
  "communication/SendEmailPage.tsx"
  "communication/SendSMSPage.tsx"
  "communication/SMSHistoryPage.tsx"

  # Documents
  "documents/DocumentsPage.tsx"
  "documents/OtherFilesPage.tsx"
  "documents/SystemManualsPage.tsx"
  "documents/TemplatesPage.tsx"

  # Reports
  "reports/BenchmarkingPage.tsx"
  "reports/BookingReportsPage.tsx"
  "reports/CustomerReportsPage.tsx"
  "reports/DailyDiaryPage.tsx"
  "reports/DetailProfitLossPage.tsx"
  "reports/ExpenseReportsPage.tsx"
  "reports/GSTDetailPage.tsx"
  "reports/GSTSummaryPage.tsx"
  "reports/IncomeForecastPage.tsx"
  "reports/IncomeReportsPage.tsx"
  "reports/ProfitLossPage.tsx"
  "reports/ReportPage.tsx"
  "reports/ServiceReportsPage.tsx"
  "reports/SuburbReportsPage.tsx"
  "reports/TrackingReportPage.tsx"

  # Settings
  "settings/AccountSettingsPage.tsx"
  "settings/ChangePasswordPage.tsx"
  "settings/GeneralSettingsPage.tsx"
  "settings/ServicePricesPage.tsx"
  "settings/SettingsPage.tsx"

  # Training
  "training/ELearningPage.tsx"
  "training/MateTrainingVideosPage.tsx"
  "training/PhysicalMarketingIdeasPage.tsx"

  # Orders
  "orders/CompletedOrdersPage.tsx"
  "orders/SmsPurchaseLogPage.tsx"

  # Other
  "calendar/CalendarPage.tsx"
  "calendar/CalendarAgendaViewPage.tsx"
  "calendar/CalendarDayViewPage.tsx"
  "calendar/CalendarMonthViewPage.tsx"
  "calendar/CalendarWeekViewPage.tsx"
  "dashboard/DashboardPage.tsx"
  "forum/ForumPage.tsx"
  "news/LatestNewsPage.tsx"
  "news/NewsPage.tsx"
  "pets/PetsPage.tsx"
  "support/SupportTicketPage.tsx"
  "app/DownloadAppPage.tsx"
  "system/VersionUpdatePage.tsx"
  "user-profile/UserProfilePage.tsx"
  "website/WebsiteOptionPage.tsx"
)

echo "Total pages to update: ${#PAGES_TO_UPDATE[@]}"
