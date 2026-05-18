// Get time format preference from localStorage (default: 24-hour format)
export const getTimeFormatPreference = (): string => {
  return localStorage.getItem('timeFormat') || 'H:i';
}

// Parse time string to minutes since midnight
export const toMinutesSinceMidnight = (time: string): number | null => {
  // Accept both 12-hour format (e.g. 9:05 AM) and 24-hour format (e.g. 09:05 or 09:05:00)
  const twelveHourMatch = time.match(/^(\d{1,2}):(\d{2})\s?(AM|PM)$/i)
  if (twelveHourMatch) {
    const rawHour = parseInt(twelveHourMatch[1], 10)
    const minute = parseInt(twelveHourMatch[2], 10)
    const period = twelveHourMatch[3].toUpperCase()

    if (Number.isNaN(rawHour) || Number.isNaN(minute) || rawHour < 1 || rawHour > 12 || minute < 0 || minute > 59) {
      return null
    }

    const hour24 = period === 'PM'
      ? (rawHour % 12) + 12
      : rawHour % 12

    return (hour24 * 60) + minute
  }

  const twentyFourHourMatch = time.match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/)
  if (twentyFourHourMatch) {
    const hour24 = parseInt(twentyFourHourMatch[1], 10)
    const minute = parseInt(twentyFourHourMatch[2], 10)
    if (Number.isNaN(hour24) || Number.isNaN(minute) || hour24 < 0 || hour24 > 23 || minute < 0 || minute > 59) {
      return null
    }
    return (hour24 * 60) + minute
  }

  return null
}

// Format display time using PHP-like format string
export const formatDisplayTime = (time?: string): string => {
  if (!time) return ''
  const minutes = toMinutesSinceMidnight(time)
  if (minutes === null) return time
  const format = getTimeFormatPreference()
  // Map PHP format to JS
  let jsFormat = format
    .replace('H', 'HH')
    .replace('i', 'mm')
    .replace('h', 'hh')
    .replace('g', 'h')
    .replace('A', 'a')
  // Build a Date object for today at the given time
  const now = new Date()
  now.setHours(Math.floor(minutes / 60), minutes % 60, 0, 0)
  // Use Intl.DateTimeFormat for best compatibility
  // Fallback to manual formatting if needed
  try {
    // Only support common formats
    if (jsFormat === 'HH:mm') return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false })
    if (jsFormat === 'hh:mm a') return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true })
    if (jsFormat === 'h:mm a') return now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true })
    // Add more as needed
  } catch {}
  // Fallback: return as H:i
  return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`
}

// Format display date using PHP-like format string
export const formatDisplayDate = (date: Date | string): string => {
  const d = typeof date === 'string' ? new Date(date) : date
  if (Number.isNaN(d.getTime())) {
    return typeof date === 'string' ? date : ''
  }
  const format = getDateFormatPreference();
  // Map PHP format to JS and handle all selectbox options
  switch (format) {
    case 'd/m/Y': // 18/05/2026 (Australian)
      return d.toLocaleDateString('en-AU');
    case 'm/d/Y': // 05/18/2026 (US)
      return d.toLocaleDateString('en-US');
    case 'Y-m-d': // 2026-05-18 (ISO)
      return d.toISOString().slice(0, 10);
    case 'd.m.Y': // 18.05.2026 (European)
      return `${String(d.getDate()).padStart(2, '0')}.${String(d.getMonth() + 1).padStart(2, '0')}.${d.getFullYear()}`;
    case 'M d, Y': // May 18, 2026 (Long Month)
      return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    case 'd M Y': // 18 May 2026 (Day Month Year)
      return `${String(d.getDate()).padStart(2, '0')} ${d.toLocaleDateString('en-US', { month: 'long' })} ${d.getFullYear()}`;
    case 'D, M d, Y': // Mon, May 18, 2026 (Short Weekday)
      return d.toLocaleDateString('en-US', { weekday: 'short', month: 'long', day: 'numeric', year: 'numeric' });
    default:
      return d.toLocaleDateString();
  }
}

export const formatDisplayDateTime = (dateTime?: Date | string): string => {
  if (!dateTime) return ''

  const d = typeof dateTime === 'string' ? new Date(dateTime) : dateTime
  if (Number.isNaN(d.getTime())) return typeof dateTime === 'string' ? dateTime : ''

  const yyyy = d.getFullYear()
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  const hh = String(d.getHours()).padStart(2, '0')
  const min = String(d.getMinutes()).padStart(2, '0')
  const ss = String(d.getSeconds()).padStart(2, '0')

  const formattedDate = formatDisplayDate(`${yyyy}-${mm}-${dd}`)
  const formattedTime = formatDisplayTime(`${hh}:${min}:${ss}`)

  return `${formattedDate} ${formattedTime}`.trim()
}

export const formatTimeRange = (startTime?: string, endTime?: string): string => {
  if (!startTime) return ''
  const start = formatDisplayTime(startTime)
  if (!endTime) return start
  const end = formatDisplayTime(endTime)
  return `${start} - ${end}`
}

export const getDateFormatPreference = (): string => {
  return localStorage.getItem('dateFormat') || 'd/m/Y'
}
