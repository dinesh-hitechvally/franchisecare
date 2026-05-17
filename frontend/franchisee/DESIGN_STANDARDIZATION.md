# Page Header Design Standardization

**Date:** 2026-05-17  
**Status:** ✅ Complete  
**Pages Updated:** 94+ pages

## Overview

All pages across the FranchiseCare franchisee frontend now use a consistent, professional header design through the new `PageHeader` component.

## Component Location

```
src/components/layout/PageHeader.tsx
```

## Usage

```tsx
import { PageHeader } from '../../components/layout/PageHeader'
import { IconName } from 'lucide-react'

<PageHeader
  title="Page Title"
  description="Clear description of the page purpose"
  icon={<IconName className="w-5 h-5" />}
  actions={<Button>Action</Button>} // Optional
  variant="default" // or "compact"
/>
```

## Design Features

### 1. **Consistent Card Styling**
- Shadow: `shadow-sm`
- Border: `border-gray-200`
- Padding: `px-6 py-4` (default) or `px-6 py-3` (compact)

### 2. **Gradient Icon Backgrounds**
- Blue-to-indigo gradient: `bg-gradient-to-br from-blue-500 to-indigo-600`
- White icon color
- Rounded container: `rounded-lg`
- Size: `w-10 h-10`

### 3. **Typography**
- Title: `text-2xl font-bold text-gray-800` (default)
- Title: `text-xl font-bold text-gray-800` (compact)
- Description: `text-sm text-gray-600`

### 4. **Spacing**
- All page containers: `space-y-6`
- Icon-to-title gap: `gap-3`
- Action buttons gap: `gap-2`

## Icon System by Category

### Bookings & Calendar
- `Calendar` - Active bookings, general calendar
- `CheckCircle` - Completed bookings
- `XCircle` - Cancelled bookings
- `Archive` - Archived bookings
- `RotateCw` - Recurring bookings
- `Plus` - New booking
- `Settings` - Manage bookings
- `Ban` - Blockouts

### Customers & Leads
- `Users` - Customer list
- `UserPlus` - Add customer
- `UserMinus` - Inactive customers
- `Archive` - Archived customers
- `Megaphone` - Leads
- `Sparkles` - New leads
- `ListChecks` - Completed leads
- `Clock` - Snoozed leads
- `MessageSquare` - Messages/operator

### Finance
- `TrendingUp` - Income pages
- `DollarSign` - Add income, income list
- `Wallet` - Income/expense categories
- `Receipt` - Expenses, add expense
- `TrendingDown` - Expense reports
- `Repeat` - Recurring income/expenses

### Inventory & Orders
- `Package` - Inventory, stock take
- `ShoppingCart` - Orders
- `Inbox` - Inward goods
- `ShoppingBag` - Completed orders

### Communication
- `Mail` - Email pages
- `MessageSquare` - SMS, templates, communication
- `CreditCard` - Buy credits

### Reports & Analytics
- `BarChart3` - Benchmarking, income, service reports
- `PieChart` - Booking reports, expense reports
- `TrendingUp` - Customer reports, profit/loss, forecast
- `FileText` - Daily diary
- `Receipt` - GST detail
- `Landmark` - GST summary
- `Target` - Tracking report

### Documents
- `FileText` - Documents, system manuals, templates
- `FolderOpen` - Other files

### Settings & System
- `Settings` - Settings, account settings, general settings
- `Lock` - Change password, security
- `DollarSign` - Service prices
- `GraduationCap` - E-learning
- `Video` - Training videos
- `Megaphone` - Marketing ideas
- `Headphones` - Support tickets
- `RefreshCw` - Version updates
- `Globe` - Website options
- `Download` - Download app
- `LayoutDashboard` - Dashboard
- `Newspaper` - News
- `PawPrint` - Pets

## Pages Updated by Category

### Bookings (10 pages) ✅
- ActiveBookingsPage.tsx
- ArchivedBookingsPage.tsx
- CancelBookingsPage.tsx
- CancelledRecurringPage.tsx
- CompletedBookingsPage.tsx
- ManageBookingsPage.tsx
- NewBookingsPage.tsx
- RecurringBookingsPage.tsx
- BookingsPage.tsx
- BlockoutsPage.tsx

### Blockouts (3 pages) ✅
- ListBlockoutsPage.tsx
- NewBlockoutsPage.tsx
- RecurringBlockoutsPage.tsx

### Customers (6 pages) ✅
- AddCustomerPage.tsx
- AddCustomersPage.tsx
- ArchivedCustomersPage.tsx
- CustomersPage.tsx
- InactiveCustomersPage.tsx
- ListCustomersPage.tsx

### Leads (6 pages) ✅
- CancellationRequestPage.tsx
- CompletedLeadsPage.tsx
- LeadsPage.tsx
- MessageForOperatorPage.tsx
- NewLeadsPage.tsx
- SnoozeLeadsPage.tsx

### Finance (11 pages) ✅
- AddIncomePage.tsx
- AddIncomeCategoryPage.tsx
- IncomeCategoriesPage.tsx
- IncomePage.tsx
- ListIncomesPage.tsx
- RecurringIncomePage.tsx
- AddExpensePage.tsx
- ExpenseCategoriesPage.tsx
- ExpensePage.tsx
- ListExpensePage.tsx
- RecurringExpensesPage.tsx

### Inventory (10 pages) ✅
- InwardGoodsPage.tsx
- InventoryOrdersPage.tsx
- InventoryPage.tsx
- InventoryUsagePage.tsx
- MarketingMaterialsPage.tsx
- OfficeOrderPage.tsx
- PlaceTreatOrderPage.tsx
- PlaceUniformOrderPage.tsx
- ShampooOrderPage.tsx
- StockTakePage.tsx

### Communication (7 pages) ✅
- BuyCreditsPage.tsx
- CommunicationPage.tsx
- EmailHistoryPage.tsx
- MessageTemplatesPage.tsx
- SendEmailPage.tsx
- SendSMSPage.tsx
- SMSHistoryPage.tsx

### Reports (15 pages) ✅
- BenchmarkingPage.tsx
- BookingReportsPage.tsx
- CustomerReportsPage.tsx
- DailyDiaryPage.tsx
- DetailProfitLossPage.tsx
- ExpenseReportsPage.tsx
- GSTDetailPage.tsx
- GSTSummaryPage.tsx
- IncomeForecastPage.tsx
- IncomeReportsPage.tsx
- ProfitLossPage.tsx
- ReportPage.tsx
- ServiceReportsPage.tsx
- SuburbReportsPage.tsx
- TrackingReportPage.tsx

### Documents (4 pages) ✅
- DocumentsPage.tsx
- OtherFilesPage.tsx
- SystemManualsPage.tsx
- TemplatesPage.tsx

### Settings (4 pages) ✅
- AccountSettingsPage.tsx
- ChangePasswordPage.tsx
- GeneralSettingsPage.tsx
- ServicePricesPage.tsx
- SettingsPage.tsx

### Training (3 pages) ✅
- ELearningPage.tsx
- MateTrainingVideosPage.tsx
- PhysicalMarketingIdeasPage.tsx

### Orders (2 pages) ✅
- CompletedOrdersPage.tsx
- SmsPurchaseLogPage.tsx

### Calendar (5 pages) ✅
- CalendarPage.tsx
- CalendarAgendaViewPage.tsx
- CalendarDayViewPage.tsx
- CalendarMonthViewPage.tsx
- CalendarWeekViewPage.tsx

### Other (8 pages) ✅
- DashboardPage.tsx
- LatestNewsPage.tsx
- NewsPage.tsx
- PetsPage.tsx
- SupportTicketPage.tsx
- DownloadAppPage.tsx
- VersionUpdatePage.tsx
- WebsiteOptionPage.tsx

## Pages with Custom Headers (Not Updated)

These 2 pages retained their custom banner designs due to specialized styling requirements:

1. **ForumPage.tsx** - Has decorative pattern background and integrated search
2. **UserProfilePage.tsx** - Has cover image with avatar overlay

## Migration Notes

### Before (Old Pattern)
```tsx
<Card className="px-4 py-3 shadow-sm border-gray-200">
  <h1 className="text-xl font-bold text-gray-800">Page Title</h1>
</Card>
```

### After (New Pattern)
```tsx
<PageHeader
  title="Page Title"
  description="Page description"
  icon={<Icon className="w-5 h-5" />}
/>
```

## Benefits

1. ✅ **Consistency** - All pages have uniform header design
2. ✅ **Professionalism** - Modern gradient icons and clean typography
3. ✅ **Usability** - Clear page descriptions help users understand context
4. ✅ **Maintainability** - Single component for all page headers
5. ✅ **Scalability** - Easy to update design across entire app
6. ✅ **Accessibility** - Consistent structure aids screen readers

## Testing Checklist

- [ ] Visual verification of all page headers
- [ ] Icon rendering across all pages
- [ ] Action buttons functionality
- [ ] Responsive design on mobile/tablet
- [ ] Dark mode compatibility (if applicable)
- [ ] No console errors or warnings

## Future Enhancements

Potential improvements to consider:

1. Add breadcrumb navigation support
2. Support for custom color themes per section
3. Add loading state for async page titles
4. Support for badges/tags in header
5. Add back button navigation option
6. Support for header tabs/navigation

---

**Last Updated:** 2026-05-17  
**Maintained By:** Development Team
