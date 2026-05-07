# Calendar API Documentation

## Overview

The Calendar API provides unified access to booking and blockout events for calendar views. All calendar event data is stored in a dedicated `calendar_events` table that denormalizes data from both bookings and blockouts for optimized querying.

## Database Schema

### calendar_events Table

```sql
CREATE TABLE calendar_events (
  id BIGINT PRIMARY KEY
  company_id BIGINT FOREIGN KEY (companies)
  event_type ENUM('booking', 'blockout')
  title VARCHAR(255)
  description TEXT
  start_date DATE
  start_time TIME
  end_date DATE
  end_time TIME
  color VARCHAR(255)  -- Hex color code (#3b82f6, #9333ea, etc)
  location VARCHAR(255)
  customer_id BIGINT FOREIGN KEY (customers) - nullable
  booking_id BIGINT FOREIGN KEY (bookings) - nullable
  blockout_id BIGINT FOREIGN KEY (blockouts) - nullable
  is_recurring BOOLEAN
  is_active BOOLEAN
  created_at TIMESTAMP
  updated_at TIMESTAMP
)

Indexes:
- (company_id, start_date)
- (event_type, company_id)
```

## API Endpoints

### 1. Get Calendar Events by Date Range

**Endpoint:** `GET /api/calendar-events`

**Parameters:**
- `company_id` (required): Company ID
- `start_date` (required): Start date (YYYY-MM-DD)
- `end_date` (required): End date (YYYY-MM-DD)
- `event_type` (optional): Filter by 'booking' or 'blockout'

**Example Request:**
```bash
GET /api/calendar-events?company_id=1&start_date=2026-04-01&end_date=2026-05-31&event_type=booking
```

**Response:**
```json
[
  {
    "id": 11,
    "company_id": 1,
    "event_type": "booking",
    "title": "John Doe",
    "description": "Sample booking notes",
    "start_date": "2026-04-27",
    "start_time": "09:00:00",
    "end_date": "2026-04-27",
    "end_time": "11:00:00",
    "color": "#10b981",
    "location": null,
    "customer_id": 3,
    "booking_id": 1,
    "blockout_id": null,
    "is_recurring": false,
    "is_active": true,
    "created_at": "2026-05-07T06:13:55Z",
    "updated_at": "2026-05-07T06:13:55Z"
  },
  ...
]
```

### 2. Get Calendar Events by Month

**Endpoint:** `GET /api/calendar-events/month`

**Parameters:**
- `company_id` (required): Company ID
- `year` (required): Year (2000-2100)
- `month` (required): Month (1-12)

**Example Request:**
```bash
GET /api/calendar-events/month?company_id=1&year=2026&month=5
```

### 3. Create Calendar Event

**Endpoint:** `POST /api/calendar-events`

**Request Body:**
```json
{
  "company_id": 1,
  "event_type": "booking|blockout",
  "title": "Event Title",
  "description": "Optional description",
  "start_date": "2026-05-10",
  "start_time": "10:00:00",
  "end_date": "2026-05-10",
  "end_time": "12:00:00",
  "color": "#3b82f6",
  "location": "Optional location",
  "customer_id": 3,
  "booking_id": null,
  "blockout_id": null,
  "is_recurring": false
}
```

### 4. Update Calendar Event

**Endpoint:** `PUT /api/calendar-events/{id}`

**Request Body:** (Same structure as POST, all fields optional)

### 5. Delete Calendar Event

**Endpoint:** `DELETE /api/calendar-events/{id}`

**Response:** 204 No Content

### 6. Get Single Calendar Event

**Endpoint:** `GET /api/calendar-events/{id}`

**Response:** Calendar event object with related data

### 7. Sync Calendar Events

**Endpoint:** `POST /api/calendar-events/sync`

This endpoint syncs all calendar events from bookings and blockouts tables for a company.

**Request Body:**
```json
{
  "company_id": 1
}
```

**Response:**
```json
{
  "message": "Calendar events synced successfully"
}
```

## Frontend Usage

### Using the Calendar API in React

```typescript
import { calendarApi } from '@/api/services'
import { useQuery } from '@tanstack/react-query'

// Get events for a date range
const { data: events } = useQuery({
  queryKey: ['calendar-events', companyId, startDate, endDate],
  queryFn: () => calendarApi.getEvents({
    company_id: companyId,
    start_date: startDate,
    end_date: endDate,
    event_type: 'booking' // optional
  })
})

// Get events for a month
const { data: monthEvents } = useQuery({
  queryKey: ['calendar-events-month', companyId, year, month],
  queryFn: () => calendarApi.getByMonth({
    company_id: companyId,
    year: 2026,
    month: 5
  })
})

// Sync calendar events
const syncMutation = useMutation({
  mutationFn: () => calendarApi.sync(companyId),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['calendar-events'] })
  }
})
```

## Event Types

### Booking Events
- `event_type`: 'booking'
- `color`: Calendar color from booking (e.g., #10b981)
- `title`: Customer name
- `customer_id`: Set to booking customer
- `booking_id`: Reference to booking record
- `start_date` = `end_date`: Same as booking start date
- `start_time` and `end_time`: From booking

### Blockout Events
- `event_type`: 'blockout'
- `color`: Always #9333ea (purple)
- `title`: Blockout title
- `location`: Blockout location
- `blockout_id`: Reference to blockout record
- `start_date` and `end_date`: From blockout
- `start_time` and `end_time`: From blockout

## Time Format

- **Storage Format**: 24-hour format (HH:MM:SS)
- **Input Formats Supported**: 
  - 24-hour: "14:30:00"
  - 12-hour: "2:30 PM" (auto-converted to 24-hour)
- **Output Format**: 24-hour (HH:MM:SS)

## Synchronization

### Auto-sync via Command

```bash
# Sync for all companies
php artisan app:sync-calendar-events

# Sync for specific company
php artisan app:sync-calendar-events --company_id=1
```

### Manual Sync via API

```bash
POST /api/calendar-events/sync
Content-Type: application/json

{
  "company_id": 1
}
```

## Observations & Listeners

Currently, the calendar events are synced using:
1. Console command: `app:sync-calendar-events`
2. API endpoint: `POST /api/calendar-events/sync`

To implement real-time sync when bookings/blockouts are created/updated, add model observers to the Booking and Blockout models.

## Color Codes

| Event Type | Color | Usage |
|-----------|-------|-------|
| Booking | Variable | Uses calendar_color from booking |
| Blockout | #9333ea | Purple for all blockouts |
| Default Booking | #3b82f6 | Blue (fallback) |

## Notes

- All times are stored in 24-hour format in the database
- The calendar_events table denormalizes data for performance optimization
- Use the sync endpoint to refresh calendar data after bulk operations
- Indexes are optimized for date-range and event-type queries
- is_active flag filters out cancelled bookings and inactive blockouts
