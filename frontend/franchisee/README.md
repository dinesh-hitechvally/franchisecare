# Dog Wash System - React Frontend

A comprehensive React frontend for franchise dog wash operations, built with TypeScript, Tailwind CSS, and modern React patterns.

## Features

### Core Modules
- **Authentication**: JWT-based login with secure route guards
- **Dashboard**: Metrics cards, news, activities timeline, booking schedule
- **Leads**: Lead management with conversion to customers
- **Customers & Pets**: Full CRM with pet profiles and signature capture
- **Bookings**: Calendar view, availability management, blockouts
- **Inventory**: Stock tracking, orders, low-stock alerts
- **Finance**: Income/expense tracking with charts
- **Documents**: File upload and management
- **Communication**: Email/SMS templates and sending
- **Forum**: Discussion boards for staff
- **News**: Internal news publishing
- **Settings**: User preferences and security

## Tech Stack

- **Framework**: React 18 + TypeScript
- **Build Tool**: Vite
- **Routing**: React Router v6
- **State Management**: Zustand (Auth), React Query (Server state)
- **Styling**: Tailwind CSS
- **Forms**: React Hook Form + Yup
- **Icons**: Lucide React
- **Charts**: Recharts
- **Date Handling**: date-fns

## Project Structure

```
src/
├── api/
│   ├── client.ts       # Axios client with interceptors
│   └── services.ts     # API service methods
├── components/
│   ├── layout/         # MainLayout, Sidebar, Header
│   └── ui/             # Card, Button, Table, Modal, Toast
├── lib/
│   └── utils.ts        # Utility functions (cn helper)
├── pages/
│   ├── auth/           # LoginPage
│   ├── dashboard/      # DashboardPage
│   ├── leads/          # LeadsPage
│   ├── customers/      # CustomersPage
│   ├── pets/           # PetsPage
│   ├── bookings/       # BookingsPage, BlockoutsPage
│   ├── inventory/      # InventoryPage, InventoryOrdersPage
│   ├── finance/        # IncomePage, ExpensePage
│   ├── documents/      # DocumentsPage
│   ├── communication/  # CommunicationPage
│   ├── forum/          # ForumPage
│   ├── news/           # NewsPage
│   └── settings/       # SettingsPage
├── store/
│   ├── authStore.ts    # Authentication state
│   └── toastStore.ts   # Toast notifications
├── types/
│   └── index.ts        # TypeScript interfaces
├── routes.tsx          # Route configuration
├── App.tsx             # Main app component
└── main.tsx            # Entry point
```

## Getting Started

### Prerequisites
- Node.js 18+ 
- npm or yarn

### Installation

```bash
cd dogwash-system
npm install
```

### Development

```bash
npm run dev
```

The app will be available at `http://localhost:3000`

### Build

```bash
npm run build
```

## Demo Credentials

The login page accepts any email/password for demo purposes. It will mock a successful login.

## API Integration

The app uses mock data by default. To connect to a real backend:

1. Set the API URL in a `.env` file:
```
VITE_API_URL=http://your-api.com/api
```

2. Uncomment the actual API calls in the service methods (marked with `// Mock data` comments)

## Key Features Implemented

### From Plan Requirements

1. **Login Screen**: Split layout with form + brand panel, password reveal toggle
2. **Dashboard Layout**: Sidebar nav + top header with quick actions
3. **Leads**: Full CRUD with status filtering, import option, conversion modal
4. **Customers**: Searchable list, profile modal
5. **Pets**: Signature capture canvas, owner linking
6. **Bookings**: List/calendar views, status workflow (requested → confirmed → in-progress → completed)
7. **Blockouts**: Date range blocking with reason
8. **Inventory**: Category dashboard, low-stock alerts, order workflow
9. **Finance**: Bar charts (income) + Pie charts (expenses), recurring items
10. **Documents**: Upload modal, visibility controls
11. **Communication**: Templates with variables, send dialog, history log
12. **Forum**: Thread list, comments, likes, pinned posts
13. **News**: Draft/published states, publish workflow
14. **Settings**: Profile, security (2FA), notifications, regional preferences

## Roadmap

See `plan-dogWashSystem.prompt.md` for the full implementation roadmap.

## License

MIT
