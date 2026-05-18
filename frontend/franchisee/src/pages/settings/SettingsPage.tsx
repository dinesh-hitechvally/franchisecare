import { useState, useEffect } from 'react'
import { Card } from '../../components/ui/Card'
import { PageHeader } from '../../components/layout/PageHeader'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { TimePicker } from '../../components/ui/TimePicker'
import { Settings } from 'lucide-react'
import { settingsApi } from '../../api/services'
import { policiesApi, CancellationPolicyRecord } from '../../api/policies'
import { toast } from 'react-hot-toast'

export function SettingsPage() {
  // Preferences state
  const [preferences, setPreferences] = useState({
    displayCustomerNotes: true,
    hideExpiredBookings: true,
    hideBookingCashNotifications: true,
    hidePastBookings: false,
    filterServicesByPetSize: false,
    displayBookingEndTime: true,
    showAddressInInvoice: true,
    showPersonalPhone: true,
  })

  // Time and date format state
  const TIME_FORMAT_OPTIONS = [
    { value: 'h:i A', label: '01:30 PM (12-hour)' },
    { value: 'H:i', label: '13:30 (24-hour)' },
    { value: 'g:i A', label: '1:30 PM (12-hour, no leading zero)' },
  ]
  const DATE_FORMAT_OPTIONS = [
    { value: 'd/m/Y', label: '18/05/2026 (Australian)' },
    { value: 'm/d/Y', label: '05/18/2026 (US)' },
    { value: 'Y-m-d', label: '2026-05-18 (ISO)' },
    { value: 'd.m.Y', label: '18.05.2026 (European)' },
    { value: 'M d, Y', label: 'May 18, 2026 (Long Month)' },
    { value: 'd M Y', label: '18 May 2026 (Day Month Year)' },
    { value: 'D, M d, Y', label: 'Mon, May 18, 2026 (Short Weekday)' },
  ]
  const [timeFormat, setTimeFormat] = useState('H:i')
  const [dateFormat, setDateFormat] = useState('d/m/Y')

  // SMS Settings state
  const [smsSettings, setSmsSettings] = useState({
    sender: 'D4951496-75',
  })

  // Cancellation Policy state
  const [cancellationPolicy, setCancellationPolicy] = useState({
    attachPolicy: false,
    cancelBefore: 'Hours',
    hours: '267',
    cutoffTime: '09:00 AM',
    cancellationFeeType: '%',
    cancellationFeeValue: '180',
    penalty: '% (Percent)',
    policyId: undefined as number | undefined,
  })
  const [policies, setPolicies] = useState<CancellationPolicyRecord[]>([])

  // Booking to income settings
  const [incomeTemplate, setIncomeTemplate] = useState({
    incomeFrom: 'Income from {{customername}} - {{date}}',
    invoiceTemplate: 'income from {{customername}} - {{date}}',
  })

  // Send booking reminder state
  const [reminderOption, setReminderOption] = useState('email-only')
  const [confirmationHours, setConfirmationHours] = useState('24')

  // Calendar settings state
  const [calendarSettings, setCalendarSettings] = useState({
    showBookingTotal: true,
    showCustomerName: true,
    showCustomerAddress: true,
    showPetName: true,
    showPetBreed: true,
    showServicesName: true,
  })

  // App Calendar settings state
  const [appCalendarSettings, setAppCalendarSettings] = useState({
    showCustomerName: true,
    showCustomerAddress: true,
    showBookingTotal: true,
    showTime: true,
    showPetName: true,
    showServicesName: true,
    showPetBreed: true,
  })

  const [loading, setLoading] = useState(false)
  const [saving, setSaving] = useState<string | null>(null)

  // Fetch settings on mount
  useEffect(() => {
    const fetchSettings = async () => {
      setLoading(true)
      try {
        const [prefs, income, calendar, cancellation, reminder, appCalendar] = await Promise.all([
          settingsApi.getPreferences(),
          settingsApi.getIncomeTemplates(),
          settingsApi.getCalendarSettings(),
          settingsApi.getCancellationPolicy(),
          settingsApi.getReminderSettings(),
          settingsApi.getAppCalendarSettings(),
        ])

        // Map preferences from API (snake_case) to state (camelCase)
        setPreferences({
          displayCustomerNotes: prefs.display_customer_notes,
          hideExpiredBookings: prefs.hide_expired_bookings,
          hideBookingCashNotifications: prefs.hide_booking_cash_notifications,
          hidePastBookings: prefs.hide_past_bookings,
          filterServicesByPetSize: prefs.filter_services_by_pet_size,
          displayBookingEndTime: prefs.display_booking_end_time,
          showAddressInInvoice: prefs.show_address_in_invoice,
          showPersonalPhone: prefs.show_personal_phone,
        })

        setTimeFormat(prefs.time_format || 'H:i')
        setDateFormat(prefs.date_format || 'd/m/Y')

        setIncomeTemplate({
          incomeFrom: income.income_title_template,
          invoiceTemplate: income.invoice_statement_template,
        })

        setCalendarSettings({
          showBookingTotal: calendar.show_booking_total,
          showCustomerName: calendar.show_customer_name,
          showCustomerAddress: calendar.show_customer_address,
          showPetName: calendar.show_pet_name,
          showPetBreed: calendar.show_pet_breed,
          showServicesName: calendar.show_services_name,
        })

        // Convert 24-hour format (HH:mm) to 12-hour format (HH:mm AM/PM)
        const convertTo12Hour = (time: string) => {
          if (!time) return '09:00 AM'
          const [hours, minutes] = time.split(':')
          const hour = parseInt(hours)
          const period = hour >= 12 ? 'PM' : 'AM'
          const displayHour = hour % 12 || 12
          return `${displayHour.toString().padStart(2, '0')}:${minutes} ${period}`
        }

        setCancellationPolicy({
          attachPolicy: cancellation.attach_policy ?? false,
          cancelBefore: cancellation.cancel_before_unit === 'hours' ? 'Hours' : 'Cutoff Time',
          hours: String(cancellation.cancel_before_value),
          cutoffTime: convertTo12Hour(cancellation.cancel_cutoff_time),
          cancellationFeeType: '%',
          cancellationFeeValue: String(cancellation.cancellation_fee_value),
          penalty: cancellation.penalty_type === 'percent' ? '% (Percent)' : '$ (Fixed)',
          policyId: cancellation.policy_id,
        })

        setReminderOption(reminder.reminder_method)
        setConfirmationHours(String(reminder.send_before_hours))

        setAppCalendarSettings({
          showCustomerName: appCalendar.show_customer_name,
          showCustomerAddress: appCalendar.show_customer_address,
          showBookingTotal: appCalendar.show_booking_total,
          showTime: appCalendar.show_time,
          showPetName: appCalendar.show_pet_name,
          showServicesName: appCalendar.show_services_name,
          showPetBreed: appCalendar.show_pet_breed,
        })
      } catch (error) {
        console.error('Error fetching settings:', error)
        toast.error('Failed to load settings')
      } finally {
        setLoading(false)
      }
    }

    fetchSettings()
    // Fetch all cancellation policies
    policiesApi.getAll().then(setPolicies).catch(() => setPolicies([]))
  }, [])

  // Helper function to replace policy templates with actual values
  const getPolicyDescriptionWithValues = (description: string) => {
    // Generate time template based on cancel before unit
    let timeTemplate = ''
    if (cancellationPolicy.cancelBefore === 'Hours') {
      timeTemplate = `we kindly require ${cancellationPolicy.hours} hours' notice for any cancellations or reschedules.`
    } else {
      // cutoffTime is already in 12-hour format (HH:mm AM/PM)
      timeTemplate = `notice of cancellation or reschedules must be received by ${cancellationPolicy.cutoffTime} the business day prior.`
    }

    // Generate fee template based on penalty type
    let feeTemplate = ''
    if (cancellationPolicy.penalty === '% (Percent)') {
      feeTemplate = `${cancellationPolicy.cancellationFeeValue}% of the service cost`
    } else {
      feeTemplate = `$${cancellationPolicy.cancellationFeeValue}`
    }

    // Replace placeholders in policy description
    return description.replace('{{timeTemplate}}', timeTemplate).replace('{{feeTemplate}}', feeTemplate)
  }
  // Save handlers
  const handleSavePreferences = async () => {
    setSaving('preferences')
    try {
      await settingsApi.savePreferences({
        display_customer_notes: preferences.displayCustomerNotes,
        hide_expired_bookings: preferences.hideExpiredBookings,
        hide_booking_cash_notifications: preferences.hideBookingCashNotifications,
        hide_past_bookings: preferences.hidePastBookings,
        filter_services_by_pet_size: preferences.filterServicesByPetSize,
        display_booking_end_time: preferences.displayBookingEndTime,
        show_address_in_invoice: preferences.showAddressInInvoice,
        show_personal_phone: preferences.showPersonalPhone,
        time_format: timeFormat,
        date_format: dateFormat,
      })
      // Update localStorage with time format preference
      localStorage.setItem('timeFormat', timeFormat)
      localStorage.setItem('dateFormat', dateFormat)
      toast.success('Preferences saved successfully')
    } catch (error) {
      console.error('Error saving preferences:', error)
      toast.error('Failed to save preferences')
    } finally {
      setSaving(null)
    }
  }

  const handleSaveIncomeTemplates = async () => {
    setSaving('income')
    try {
      await settingsApi.saveIncomeTemplates({
        income_title_template: incomeTemplate.incomeFrom,
        invoice_statement_template: incomeTemplate.invoiceTemplate,
      })
      toast.success('Income templates saved successfully')
    } catch (error) {
      console.error('Error saving income templates:', error)
      toast.error('Failed to save income templates')
    } finally {
      setSaving(null)
    }
  }

  const handleSaveCalendarSettings = async () => {
    setSaving('calendar')
    try {
      await settingsApi.saveCalendarSettings({
        show_booking_total: calendarSettings.showBookingTotal,
        show_customer_name: calendarSettings.showCustomerName,
        show_customer_address: calendarSettings.showCustomerAddress,
        show_pet_name: calendarSettings.showPetName,
        show_pet_breed: calendarSettings.showPetBreed,
        show_services_name: calendarSettings.showServicesName,
      })
      toast.success('Calendar settings saved successfully')
    } catch (error) {
      console.error('Error saving calendar settings:', error)
      toast.error('Failed to save calendar settings')
    } finally {
      setSaving(null)
    }
  }

  const handleSaveCancellationPolicy = async () => {
    setSaving('cancellation')
    try {
      // Convert 12-hour format (HH:mm AM/PM) to 24-hour format (HH:mm)
      const convertTo24Hour = (time: string) => {
        if (!time) return '09:00'
        const match = time.match(/(\d{1,2}):(\d{2})\s?(AM|PM)/i)
        if (!match) return '09:00'
        let [, hours, minutes, period] = match
        let hour = parseInt(hours)
        if (period.toUpperCase() === 'PM' && hour !== 12) hour += 12
        if (period.toUpperCase() === 'AM' && hour === 12) hour = 0
        return `${hour.toString().padStart(2, '0')}:${minutes}`
      }

      await settingsApi.saveCancellationPolicy({
        attach_policy: cancellationPolicy.attachPolicy,
        cancel_before_unit: cancellationPolicy.cancelBefore === 'Hours' ? 'hours' : 'cutoff',
        cancel_before_value: parseInt(cancellationPolicy.hours),
        cancel_cutoff_time: cancellationPolicy.cancelBefore === 'Cutoff Time' ? convertTo24Hour(cancellationPolicy.cutoffTime) : undefined,
        cancellation_fee_value: parseFloat(cancellationPolicy.cancellationFeeValue),
        penalty_type: cancellationPolicy.penalty === '% (Percent)' ? 'percent' : 'fixed',
        policy_id: cancellationPolicy.policyId,
      })
      toast.success('Cancellation policy saved successfully')
    } catch (error) {
      console.error('Error saving cancellation policy:', error)
      toast.error('Failed to save cancellation policy')
    } finally {
      setSaving(null)
    }
  }

  const handleSaveReminderSettings = async () => {
    setSaving('reminder')
    try {
      await settingsApi.saveReminderSettings({
        reminder_method: reminderOption as any,
        send_before_hours: parseInt(confirmationHours),
      })
      toast.success('Reminder settings saved successfully')
    } catch (error) {
      console.error('Error saving reminder settings:', error)
      toast.error('Failed to save reminder settings')
    } finally {
      setSaving(null)
    }
  }

  const handleSaveAppCalendarSettings = async () => {
    setSaving('appCalendar')
    try {
      await settingsApi.saveAppCalendarSettings({
        show_customer_name: appCalendarSettings.showCustomerName,
        show_customer_address: appCalendarSettings.showCustomerAddress,
        show_booking_total: appCalendarSettings.showBookingTotal,
        show_time: appCalendarSettings.showTime,
        show_pet_name: appCalendarSettings.showPetName,
        show_services_name: appCalendarSettings.showServicesName,
        show_pet_breed: appCalendarSettings.showPetBreed,
      })
      toast.success('App calendar settings saved successfully')
    } catch (error) {
      console.error('Error saving app calendar settings:', error)
      toast.error('Failed to save app calendar settings')
    } finally {
      setSaving(null)
    }
  }

  const togglePreference = (key: keyof typeof preferences) => {
    setPreferences(prev => ({ ...prev, [key]: !prev[key] }))
  }

  const toggleCalendarSetting = (key: keyof typeof calendarSettings) => {
    setCalendarSettings(prev => ({ ...prev, [key]: !prev[key] }))
  }

  const toggleAppCalendarSetting = (key: keyof typeof appCalendarSettings) => {
    setAppCalendarSettings(prev => ({ ...prev, [key]: !prev[key] }))
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Settings"
        icon={<Settings className="w-5 h-5" />}
      />

      {loading ? (
        <div className="flex items-center justify-center h-64">
          <div className="text-gray-500">Loading settings...</div>
        </div>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {/* Left Column */}
        <div className="space-y-4">
          {/* Preferences Section */}
          <Card className="border border-gray-200 shadow-sm">
            <div className="p-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Preferences</h3>
              <div className="space-y-4">
                {[
                  { key: 'displayCustomerNotes', label: 'Display Customer Notes on Booking', icon: '📋' },
                  { key: 'hideExpiredBookings', label: 'Hide Expired/ Completed Recurring Bookings', icon: '🔁' },
                  { key: 'hideBookingCashNotifications', label: 'Hide Booking Cash Notifications', icon: '🔔' },
                  { key: 'hidePastBookings', label: 'Hide Past Bookings from Calendar Search', icon: '📅' },
                  { key: 'filterServicesByPetSize', label: 'Filter Services based on pet size', icon: '🐾' },
                  { key: 'displayBookingEndTime', label: 'Display Booking End Time', icon: '⏰' },
                  { key: 'showAddressInInvoice', label: 'Show Address In Invoice', icon: '📍' },
                  { key: 'showPersonalPhone', label: 'Show Personal Phone in Invoice', icon: '📞' },
                ].map(({ key, label, icon }) => (
                  <div key={key} className="flex items-center justify-between py-2">
                    <div className="flex items-center gap-3">
                      <span className="text-xl">{icon}</span>
                      <span className="text-sm text-gray-700">{label}</span>
                    </div>
                    <button
                      onClick={() => togglePreference(key as keyof typeof preferences)}
                      className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                        preferences[key as keyof typeof preferences] ? 'bg-indigo-600' : 'bg-gray-300'
                      }`}
                    >
                      <span
                        className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                          preferences[key as keyof typeof preferences] ? 'translate-x-6' : 'translate-x-1'
                        }`}
                      />
                    </button>
                  </div>
                ))}
                <div className="flex items-center justify-between py-2">
                  <div className="flex items-center gap-3">
                    <span className="text-xl">⏰</span>
                    <span className="text-sm text-gray-700">Time Format</span>
                  </div>
                  <select
                    value={timeFormat}
                    onChange={e => setTimeFormat(e.target.value)}
                    className="border rounded px-2 py-1 text-sm"
                  >
                    {TIME_FORMAT_OPTIONS.map(opt => (
                      <option key={opt.value} value={opt.value}>{opt.label}</option>
                    ))}
                  </select>
                </div>
                <div className="flex items-center justify-between py-2">
                  <div className="flex items-center gap-3">
                    <span className="text-xl">📅</span>
                    <span className="text-sm text-gray-700">Date Format</span>
                  </div>
                  <select
                    value={dateFormat}
                    onChange={e => setDateFormat(e.target.value)}
                    className="border rounded px-2 py-1 text-sm"
                  >
                    {DATE_FORMAT_OPTIONS.map(opt => (
                      <option key={opt.value} value={opt.value}>{opt.label}</option>
                    ))}
                  </select>
                </div>
              </div>
              <div className="flex justify-end">
                <Button
                  onClick={handleSavePreferences}
                  disabled={saving === 'preferences'}
                  className="bg-indigo-600 hover:bg-indigo-700 text-white"
                >
                  {saving === 'preferences' ? 'Saving...' : 'SAVE'}
                </Button>
              </div>
            </div>
          </Card>

          {/* Booking to income settings */}
          <Card className="border border-gray-200 shadow-sm">
            <div className="p-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Booking to income settings</h3>
              <div className="space-y-4">
                <div>
                  <label className="text-sm font-medium text-gray-700 mb-2 block">Income Title Template</label>
                  <Input
                    value={incomeTemplate.incomeFrom}
                    onChange={(e) => setIncomeTemplate({ ...incomeTemplate, incomeFrom: e.target.value })}
                    className="border-gray-300 font-mono text-sm"
                  />
                  <p className="text-xs text-gray-500 mt-1">
                    {'{{name}} and {{customername}} will be replaced as actual date and customer name.'}
                  </p>
                </div>
                <div>
                  <label className="text-sm font-medium text-gray-700 mb-2 block">Invoice Statement</label>
                  <Input
                    value={incomeTemplate.invoiceTemplate}
                    onChange={(e) => setIncomeTemplate({ ...incomeTemplate, invoiceTemplate: e.target.value })}
                    className="border-gray-300 font-mono text-sm"
                  />
                  <p className="text-xs text-gray-500 mt-1">
                    {'{{totaldue}} and {{customername}} will be replaced as actual date and customer name.'}
                  </p>
                </div>
                <div className="flex justify-end">
                  <Button
                    onClick={handleSaveIncomeTemplates}
                    disabled={saving === 'income'}
                    className="bg-indigo-600 hover:bg-indigo-700 text-white"
                  >
                    {saving === 'income' ? 'Saving...' : 'SAVE'}
                  </Button>
                </div>
              </div>
            </div>
          </Card>

          {/* Calendar Settings */}
          <Card className="border border-gray-200 shadow-sm">
            <div className="p-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Calendar Settings</h3>
              <div className="space-y-3">
                {[
                  { key: 'showBookingTotal', label: 'Show Booking Total' },
                  { key: 'showCustomerName', label: 'Show Customer Name' },
                  { key: 'showCustomerAddress', label: 'Show Customer Address' },
                  { key: 'showPetName', label: 'Show Pet Name' },
                  { key: 'showPetBreed', label: 'Show Pet Breed' },
                  { key: 'showServicesName', label: 'Show Services Name' },
                ].map(({ key, label }) => (
                  <div key={key} className="flex items-center justify-between py-1">
                    <span className="text-sm text-gray-700">{label}</span>
                    <button
                      onClick={() => toggleCalendarSetting(key as keyof typeof calendarSettings)}
                      className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                        calendarSettings[key as keyof typeof calendarSettings] ? 'bg-indigo-600' : 'bg-gray-300'
                      }`}
                    >
                      <span
                        className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                          calendarSettings[key as keyof typeof calendarSettings] ? 'translate-x-6' : 'translate-x-1'
                        }`}
                      />
                    </button>
                  </div>
                ))}
              </div>
              <div className="mt-6 bg-indigo-600 p-4 rounded-lg text-white space-y-1 text-sm">
                <p className="font-semibold">Preview</p>
                <p>Email Cost</p>
                <p>Firstname Lastname, Passowrd</p>
                <p>Per Name</p>
                <p>Per Name</p>
                <p>Services</p>
              </div>
              <div className="flex justify-end mt-4">
                <Button
                  onClick={handleSaveCalendarSettings}
                  disabled={saving === 'calendar'}
                  className="bg-indigo-600 hover:bg-indigo-700 text-white"
                >
                  {saving === 'calendar' ? 'Saving...' : 'SAVE'}
                </Button>
              </div>
            </div>
          </Card>
        </div>

        {/* Right Column */}
        <div className="space-y-4">
          {/* SMS Settings Section */}
          <Card className="border border-gray-200 shadow-sm">
            <div className="p-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">SMS Settings</h3>
              <div className="space-y-4">
                <div>
                  <label className="text-sm font-medium text-gray-700 mb-2 block">SMS Sender</label>
                  <Input
                    value={smsSettings.sender}
                    onChange={(e) => setSmsSettings({ ...smsSettings, sender: e.target.value })}
                    className="border-gray-300"
                  />
                  <p className="text-xs text-gray-500 mt-1">
                    All your SMS will be sent using this number. You currently are not allowed to change this number.
                  </p>
                </div>
              </div>
              <div className="flex justify-end">
                <Button className="bg-indigo-600 hover:bg-indigo-700 text-white">
                  SAVE
                </Button>
              </div>
            </div>
          </Card>

          {/* Cancellation Policy Section */}
          <Card className="border border-gray-200 shadow-sm">
            <div className="p-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Cancellation Policy</h3>
              <div className="space-y-4">
                {/* Attach Policy Toggle */}
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <span className="text-xl">⚙️</span>
                    <span className="text-sm text-gray-700">Attach Cancellation Policy</span>
                  </div>
                  <button
                    onClick={() => setCancellationPolicy({ ...cancellationPolicy, attachPolicy: !cancellationPolicy.attachPolicy })}
                    className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                      cancellationPolicy.attachPolicy ? 'bg-indigo-600' : 'bg-gray-300'
                    }`}
                  >
                    <span
                      className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                        cancellationPolicy.attachPolicy ? 'translate-x-6' : 'translate-x-1'
                      }`}
                    />
                  </button>
                </div>

                {/* Cancellation Policy Settings */}
                <div>
                  <label className="text-sm font-semibold text-gray-900 mb-3 block">Cancellation Policy Settings</label>
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="text-xs text-gray-600 mb-1 block">Cancel Before</label>
                      <select
                        value={cancellationPolicy.cancelBefore}
                        onChange={(e) => setCancellationPolicy({ ...cancellationPolicy, cancelBefore: e.target.value })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                      >
                        <option>Hours</option>
                        <option>Cutoff Time</option>
                      </select>
                    </div>
                    {cancellationPolicy.cancelBefore === 'Hours' ? (
                      <div>
                        <label className="text-xs text-gray-600 mb-1 block">Hours</label>
                        <Input
                          value={cancellationPolicy.hours}
                          onChange={(e) => setCancellationPolicy({ ...cancellationPolicy, hours: e.target.value })}
                          className="border-gray-300"
                        />
                      </div>
                    ) : (
                      <TimePicker
                        label="Cutoff Time"
                        value={cancellationPolicy.cutoffTime}
                        onChange={(val) => setCancellationPolicy({ ...cancellationPolicy, cutoffTime: val })}
                        placeholder="--:-- --"
                      />
                    )}
                    <div>
                      <label className="text-xs text-gray-600 mb-1 block">Cancellation Fee Value</label>
                      <Input
                        value={cancellationPolicy.cancellationFeeValue}
                        onChange={(e) => setCancellationPolicy({ ...cancellationPolicy, cancellationFeeValue: e.target.value })}
                        className="border-gray-300"
                      />
                    </div>
                    <div>
                      <label className="text-xs text-gray-600 mb-1 block">Penalty</label>
                      <select
                        value={cancellationPolicy.penalty}
                        onChange={(e) => setCancellationPolicy({ ...cancellationPolicy, penalty: e.target.value })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                      >
                        <option>% (Percent)</option>
                        <option>$ (Fixed)</option>
                      </select>
                    </div>
                  </div>
                </div>

                {/* Select Policy Section */}
                <div>
                  {policies.length === 0 ? (
                    <div className="text-gray-500 text-sm">No policies found.</div>
                  ) : (
                    <div className="space-y-2">
                      {policies.map((policy) => (
                        <button
                          key={policy.id}
                          onClick={() => setCancellationPolicy({ ...cancellationPolicy, policyId: policy.id })}
                          className={`w-full text-left p-3 rounded-lg border-2 transition-colors ${
                            cancellationPolicy.policyId === policy.id
                              ? 'bg-indigo-100 border-indigo-600'
                              : 'bg-gray-50 border-gray-200 hover:border-gray-300'
                          }`}
                        >
                          <strong className="text-sm text-gray-900 block">{policy.name}</strong>
                          <div 
                            className="text-xs text-gray-700 mt-2 leading-relaxed prose prose-sm max-w-none [&_p]:mb-2 [&_p]:text-xs [&_ul]:list-disc [&_ul]:ml-4 [&_ul]:my-1 [&_li]:text-xs [&_li]:mb-1 [&_li]:text-gray-700"
                            dangerouslySetInnerHTML={{ __html: getPolicyDescriptionWithValues(policy.description) }} 
                          />
                        </button>
                      ))}
                    </div>
                  )}
                </div>

                <div className="flex justify-end">
                  <Button
                    onClick={handleSaveCancellationPolicy}
                    disabled={saving === 'cancellation'}
                    className="bg-indigo-600 hover:bg-indigo-700 text-white"
                  >
                    {saving === 'cancellation' ? 'Saving...' : 'SAVE'}
                  </Button>
                </div>
              </div>
            </div>
          </Card>

          {/* Send booking reminder */}
          <Card className="border border-gray-200 shadow-sm">
            <div className="p-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Send booking reminder</h3>
              <div className="space-y-3">
                {[
                  { value: 'no-send', label: 'Do not send booking reminder' },
                  { value: 'email-sms', label: 'Send email and SMS for all customers' },
                  { value: 'email-only', label: 'Send email only, do not send SMS' },
                  { value: 'sms-only', label: 'Send SMS only, do not send email' },
                  { value: 'email-if-found', label: 'Send by email but if not found, send SMS' },
                  { value: 'sms-if-no-mobile', label: 'Send by SMS but if no mobile number, send email' },
                ].map(({ value, label }) => (
                  <div key={value} className="flex items-center justify-between py-2">
                    <span className="text-sm text-gray-700">{label}</span>
                    <button
                      onClick={() => setReminderOption(value)}
                      className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                        reminderOption === value ? 'bg-indigo-600' : 'bg-gray-300'
                      }`}
                    >
                      <span
                        className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                          reminderOption === value ? 'translate-x-6' : 'translate-x-1'
                        }`}
                      />
                    </button>
                  </div>
                ))}
                <div className="flex items-center justify-between pt-2 w-full">
                  <span className="text-sm text-gray-700">Send confirmation 24 hours before booking</span>
                  <div className="flex items-center gap-2">
                    <Input
                      value={confirmationHours}
                      onChange={(e) => setConfirmationHours(e.target.value)}
                      className="w-20 border-gray-300"
                    />
                    <span className="text-sm text-gray-600">Hrs</span>
                  </div>
                </div>
              </div>
              <div className="flex justify-end mt-4">
                <Button
                  onClick={handleSaveReminderSettings}
                  disabled={saving === 'reminder'}
                  className="bg-indigo-600 hover:bg-indigo-700 text-white"
                >
                  {saving === 'reminder' ? 'Saving...' : 'SAVE'}
                </Button>
              </div>
            </div>
          </Card>

          {/* App Calendar Settings */}
          <Card className="border border-gray-200 shadow-sm">
            <div className="p-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">App Calendar Settings</h3>
              <p className="text-sm text-gray-600 mb-4">
                Drag the icon to re-order items. Toggle switches to show/hide in app calendar.
              </p>
              <div className="space-y-3">
                {[
                  { key: 'showCustomerName', label: 'Show Customer Name' },
                  { key: 'showCustomerAddress', label: 'Show Customer Address' },
                  { key: 'showBookingTotal', label: 'Show Booking Total' },
                  { key: 'showTime', label: 'Show Time' },
                  { key: 'showPetName', label: 'Show Pet Name' },
                  { key: 'showServicesName', label: 'Show Services Name' },
                  { key: 'showPetBreed', label: 'Show Pet Breed' },
                ].map(({ key, label }) => (
                  <div key={key} className="flex items-center justify-between py-1">
                    <div className="flex items-center gap-3">
                      <span className="text-gray-400">☰</span>
                      <span className="text-sm text-gray-700">{label}</span>
                    </div>
                    <button
                      onClick={() => toggleAppCalendarSetting(key as keyof typeof appCalendarSettings)}
                      className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                        appCalendarSettings[key as keyof typeof appCalendarSettings] ? 'bg-indigo-600' : 'bg-gray-300'
                      }`}
                    >
                      <span
                        className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                          appCalendarSettings[key as keyof typeof appCalendarSettings] ? 'translate-x-6' : 'translate-x-1'
                        }`}
                      />
                    </button>
                  </div>
                ))}
              </div>
              <div className="mt-6 bg-indigo-600 p-4 rounded-lg text-white space-y-1 text-sm">
                <p className="font-semibold">Preview</p>
                <p>Customer Name</p>
                <p>Liza Boim @ Sydney 2000</p>
                <p>$250.00</p>
                <p>10:30 AM</p>
                <p>Pet Name</p>
                <p>Full Grooming</p>
              </div>
              <div className="flex justify-end mt-4">
                <Button
                  onClick={handleSaveAppCalendarSettings}
                  disabled={saving === 'appCalendar'}
                  className="bg-indigo-600 hover:bg-indigo-700 text-white"
                >
                  {saving === 'appCalendar' ? 'Saving...' : 'SAVE'}
                </Button>
              </div>
            </div>
          </Card>
        </div>
      </div>
      )}
    </div>
  )
}
