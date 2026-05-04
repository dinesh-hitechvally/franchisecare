# Build All Content Pages from 77 Screenshots

Build all sidebar menu item pages matching the design screenshots in `d:\laragon\www\franchisecare\frontend\franchisee\screenshots\`.

## Scope Analysis

### 77 Screenshots → Page Mapping

Screenshots are grouped by sidebar sections. Many pages already exist but may need redesign. Some pages/routes are missing entirely.

---

### Phase 1: Bookings (7 pages)
| Screenshot | Route | Status |
|---|---|---|
| `active-bookings.jpg` | `/bookings/active` | EXISTS - needs redesign |
| `completed-bookings.jpg` | `/bookings/completed` | EXISTS - needs redesign |
| `recurring-bookings.jpg` | `/bookings/recurring` | EXISTS - needs redesign |
| `cancel-recurring.jpg` | `/bookings/cancelled-recurring` | MISSING route |
| `cancel-bookings.jpg` | `/bookings/cancelled` | EXISTS - needs redesign |
| `archived-bookings.jpg` | `/bookings/archived` | EXISTS - needs redesign |
| `new-bookings.jpg` | `/bookings/new` | EXISTS - needs redesign |

### Phase 2: Block Outs (3 pages)
| Screenshot | Route | Status |
|---|---|---|
| `new-blockouts.jpg` | `/blockouts/new` | EXISTS - needs redesign |
| `list-blockouts.jpg` | `/blockouts` | EXISTS - needs redesign |
| `recurring-blockouts.jpg` | `/blockouts/recurring` | EXISTS - needs redesign |

### Phase 3: Customers (3 pages)
| Screenshot | Route | Status |
|---|---|---|
| `add-customers.jpg` | `/customers/add` | EXISTS - needs redesign |
| `list-customers.jpg` | `/customers` | EXISTS - needs redesign |
| `archived-customers.jpg` | `/customers/archived` | EXISTS - needs redesign |

### Phase 4: Communication (6 pages)
| Screenshot | Route | Status |
|---|---|---|
| `buy-credits.jpg` | `/communication/buy-credits` | MISSING page |
| `send-sms.jpg` | `/communication/sms/send` | EXISTS - needs redesign |
| `send-email.jpg` | `/communication/email/send` | EXISTS - needs redesign |
| `sms-history.jpg` | `/communication/sms/history` | EXISTS - needs redesign |
| `email-history.jpg` | `/communication/email/history` | EXISTS - needs redesign |
| `message-templates.jpg` | `/communication/templates` | EXISTS - needs redesign |

### Phase 5: Inventory (8 pages)
| Screenshot | Route | Status |
|---|---|---|
| `office-order.jpg` | `/inventory/office-order` | MISSING page |
| `shmpoo-order.jpg` | `/inventory/shampoo-order` | MISSING page |
| `place-treat-order.jpg` | `/inventory/order-treats` | EXISTS - needs redesign |
| `place-uniform-order.jpg` | `/inventory/order-uniforms` | EXISTS - needs redesign |
| `business-card-magnet-flyers.jpg` | `/inventory/marketing-materials` | MISSING page |
| `stock-take.jpg` | `/inventory/stock-take` | EXISTS - needs redesign |
| `inward-goods.jpg` | `/inventory/inward` | EXISTS - needs redesign |
| `completed-orders.jpg` | `/orders/completed` | MISSING page |

### Phase 6: Income (5 pages)
| Screenshot | Route | Status |
|---|---|---|
| `add-income.jpg` | `/finance/income/add` | EXISTS - needs redesign |
| `add-income-category.jpg` | `/finance/income/add-category` | MISSING page |
| `income-categories.jpg` | `/finance/income/categories` | EXISTS - needs redesign |
| `list-incomes.jpg` | `/finance/income` | EXISTS - needs redesign |
| `recurring-income.jpg` | `/finance/income/recurring` | MISSING page |

### Phase 7: Expense (4 pages)
| Screenshot | Route | Status |
|---|---|---|
| `add-expense.jpg` | `/finance/expense/add` | EXISTS - needs redesign |
| `expense-categories.jpg` | `/finance/expense/categories` | EXISTS - needs redesign |
| `list-expense.jpg` | `/finance/expense` | EXISTS - needs redesign |
| `recurring-expenses.jpg` | `/finance/expense/recurring` | MISSING page |

### Phase 8: Documents (3 pages)
| Screenshot | Route | Status |
|---|---|---|
| `system-manuals.jpg` | `/documents/manuals` | MISSING page |
| `templates.jpg` | `/documents/templates` | MISSING page |
| `other-files.jpg` | `/documents/other` | MISSING page |

### Phase 9: Reports (14 pages)
| Screenshot | Route | Status |
|---|---|---|
| `daily-dairy.jpg` | `/reports/daily-diary` | MISSING page |
| `benchmarking-reports.jpg` | `/reports/benchmarking` | MISSING page |
| `booking-reports.jpg` | `/reports/bookings` | EXISTS - needs redesign |
| `service-reports.jpg` | `/reports/services` | MISSING page |
| `suburb-reports.jpg` | `/reports/suburbs` | MISSING page |
| `customer-reports.jpg` | `/reports/customers` | EXISTS - needs redesign |
| `income-reports.jpg` | `/reports/income` | EXISTS - needs redesign |
| `income-forecast.jpg` | `/reports/income-forecast` | MISSING page |
| `expense-reports.jpg` | `/reports/expenses` | EXISTS - needs redesign |
| `profit-loss-statement.jpg` | `/reports/profit-loss` | MISSING page |
| `detail-profit-loss-statement.jpg` | `/reports/detail-profit-loss` | MISSING page |
| `gst-report-summary.jpg` | `/reports/gst-summary` | MISSING page |
| `gst-report-detail.jpg` | `/reports/gst-detail` | MISSING page |
| `tracking-report.jpg` | `/reports/tracking` | MISSING page |

### Phase 10: Training (3 pages)
| Screenshot | Route | Status |
|---|---|---|
| `e-learning.jpg` | `/training/e-learning` | MISSING page |
| `mate-training-videos.jpg` | `/training/videos` | MISSING page |
| `physcal-marketing-ideas.jpg` | `/training/marketing` | MISSING page |

### Phase 11: Orders (1 page)
| Screenshot | Route | Status |
|---|---|---|
| `sms-purchase-log.jpg` | `/orders/sms-purchase-log` | MISSING page |

### Phase 12: Setup (4 pages)
| Screenshot | Route | Status |
|---|---|---|
| `manage-bookings.jpg` | `/settings/manage-bookings` | MISSING page |
| `settings.jpg` | `/settings` | EXISTS - needs redesign |
| `inventory-usage.jpg` | `/settings/inventory-usage` | MISSING page |
| `service-price.jpg` | `/settings/service-prices` | MISSING page |

### Phase 13: Calendar (4 views)
| Screenshot | Route | Status |
|---|---|---|
| `calendar-month-view.jpg` | `/calendar` | EXISTS - needs redesign |
| `calendar-week-view.jpg` | `/calendar/week` | EXISTS - needs redesign |
| `calendar-day-view.jpg` | `/calendar/day` | EXISTS - needs redesign |
| `calendar-agenda-view.jpg` | `/calendar/agenda` | EXISTS - needs redesign |

### Phase 14: Other Pages (8 pages)
| Screenshot | Route | Status |
|---|---|---|
| `dashboard.jpg` | `/dashboard` | EXISTS - needs redesign |
| `forum.jpg` | `/forum` | EXISTS - needs redesign |
| `news.jpg` | `/news` | EXISTS - needs redesign |
| `new-leads.jpg` | `/leads/new` | EXISTS |
| `completed-leads.jpg` | `/leads/completed` | EXISTS |
| `snooze-leads.jpg` | `/leads/snooze` | EXISTS |
| `download-app.jpg` | `/download-app` | MISSING page |
| `version-update.jpg` | `/version-update` | MISSING page |
| `support-ticket.jpg` | `/support-tickets` | MISSING page |
| `cancellation-request.jpg` | `/cancellation-requests` | MISSING page |
| `message-for-operator.jpg` | `/operator-messages` | MISSING page |

---

## Summary

- **Total screenshots:** 77
- **Existing pages needing redesign:** ~35
- **New pages to create:** ~30
- **New routes to add:** ~30

## Proposed Approach

1. Work through phases sequentially (Phase 1 → 14)
2. For each page: view the screenshot → build/redesign the page to match
3. Add missing routes to `routes.tsx`
4. Use consistent patterns: page header card, search bars, data tables, form layouts

## Verification Plan

### Automated Tests
- Verify all routes resolve without errors
- Run `npm run build` to check for compile errors

### Manual Verification
- Visual comparison of each page against its screenshot using browser subagent

## Open Questions

> [!IMPORTANT]
> This is a very large task (~65+ pages). Should I:
> 1. **Build all pages in one go** (will take significant time)?
> 2. **Prioritize specific sections first** — which sections are most important?
> 3. **Build with dummy/mock data** or do you have API endpoints I should connect to?
