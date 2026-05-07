import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useMutation } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Select } from '../../components/ui/Select'
import { TimePicker } from '../../components/ui/TimePicker'
import { ChevronUp, ChevronDown, Check } from 'lucide-react'
import { blockoutsApi } from '../../api/services'
import { useAuthStore } from '../../store/authStore'
import { useToastStore } from '../../store/toastStore'

export function NewBlockoutsPage() {
  const navigate = useNavigate()
  const { user } = useAuthStore()
  const { addToast } = useToastStore()
  
  const [formData, setFormData] = useState({
    title: '',
    location: '',
    startDate: '',
    startTime: '',
    endDate: '',
    endTime: '',
    repeatEvery: '',
    repeatOn: '',
    repeatUntil: '',
    notes: '',
    isRecurring: false,
  })
  
  const [showRecurring, setShowRecurring] = useState(true)
  const [showConflicts, setShowConflicts] = useState(true)

  const createMutation = useMutation({
    mutationFn: (data: any) => blockoutsApi.create(data),
    onSuccess: () => {
      addToast('Blockout created successfully', 'success')
      navigate('/blockouts')
    },
    onError: () => {
      addToast('Could not create blockout', 'error')
    },
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()

    if (!formData.title || !formData.startDate || !formData.startTime || !formData.endDate || !formData.endTime) {
      addToast('Please fill in all required fields', 'error')
      return
    }

    // Get company_id from user (handle both camelCase and snake_case)
    const companyId = user?.companyId || (user as any)?.company_id

    if (!companyId) {
      addToast('Company information not found. Please log in again.', 'error')
      return
    }

    const payload = {
      title: formData.title,
      location: formData.location,
      start_date: formData.startDate,
      start_time: formData.startTime,
      end_date: formData.endDate,
      end_time: formData.endTime,
      is_recurring: formData.isRecurring,
      repeat_every: formData.repeatEvery || null,
      repeat_on: formData.repeatOn || null,
      repeat_until: formData.repeatUntil || null,
      notes: formData.notes,
      company_id: companyId,
      active: true,
    }

    console.log('Blockout payload:', payload)
    createMutation.mutate(payload)
  }

  return (
    <div className="space-y-6 px-4 py-6 w-full max-w-[1600px] mx-auto bg-gray-50/30 min-h-screen">
      {/* Header Card */}
      <Card className="px-6 py-4 shadow-[0_2px_10px_rgba(0,0,0,0.05)] border-none bg-white text-left">
        <h1 className="text-xl font-bold text-gray-800">New Blockouts</h1>
      </Card>

      <form id="blockout-form" onSubmit={handleSubmit} className="flex flex-col lg:flex-row gap-8 items-start pb-20">
        {/* Left Column: Form */}
        <div className="flex-1 space-y-8 min-w-0 w-full">
          
          {/* Card 1: Basic Information */}
          <Card className="p-5 border-none shadow-[0_4px_20px_rgba(0,0,0,0.08)] bg-white overflow-visible">
            <p className="text-gray-500 mb-6">Start entering your blockout dates by simply selecting start date/Time and end Date/Time</p>
            
            <div className="space-y-6">
              {/* Row 1 */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm text-gray-700 mb-1">Blockout Title *</label>
                  <Input
                    value={formData.title}
                    onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                    className="border-0 border-b border-gray-300 rounded-none px-0 focus:ring-0 focus:border-blue-600 bg-transparent"
                    placeholder="Enter title"
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm text-gray-700 mb-1">Location</label>
                  <Input
                    value={formData.location}
                    onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                    className="border-0 border-b border-gray-300 rounded-none px-0 focus:ring-0 focus:border-blue-600 bg-transparent"
                    placeholder="Enter location"
                  />
                </div>
              </div>

              {/* Row 2: Start */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm text-gray-700 mb-1">Start Date *</label>
                  <Input
                    type="date"
                    value={formData.startDate}
                    onChange={(e) => setFormData({ ...formData, startDate: e.target.value })}
                    className="border-0 border-b border-gray-300 rounded-none px-0 focus:ring-0 focus:border-blue-600 bg-transparent"
                    required
                  />
                </div>
                <div>
                  <TimePicker
                    label="Start Time"
                    value={formData.startTime}
                    onChange={(val) => setFormData({ ...formData, startTime: val })}
                    placeholder="--:-- --"
                    required
                  />
                </div>
              </div>

              {/* Row 3: End */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm text-gray-700 mb-1">End Date *</label>
                  <Input
                    type="date"
                    value={formData.endDate}
                    onChange={(e) => setFormData({ ...formData, endDate: e.target.value })}
                    className="border-0 border-b border-gray-300 rounded-none px-0 focus:ring-0 focus:border-blue-600 bg-transparent"
                    required
                  />
                </div>
                <div>
                  <TimePicker
                    label="End Time"
                    value={formData.endTime}
                    onChange={(val) => setFormData({ ...formData, endTime: val })}
                    placeholder="--:-- --"
                    required
                  />
                </div>
              </div>

              {/* Row 5: Notes */}
              <div>
                <label className="block text-sm text-gray-700 mb-1">Notes</label>
                <textarea
                  value={formData.notes}
                  onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                  className="w-full border-0 border-b border-gray-300 rounded-none px-0 focus:ring-0 focus:border-blue-600 bg-transparent resize-none"
                  rows={3}
                  placeholder="Enter any additional notes"
                />
              </div>
            </div>
          </Card>

          {/* Row 4: Recurring Toggle Area (Moved outside first card) */}
          <div className="space-y-4 px-2">
              <p className="text-[12px] text-gray-500 italic max-w-2xl leading-relaxed">
              This is a part of recurring blockout. Tick here to repeat this blockout. Leave it untick for single occurrence only
            </p>
            <label className="flex items-center gap-4 cursor-pointer w-fit group py-2">
              <span className="relative flex items-center">
                <input 
                  type="checkbox" 
                  id="isRecurring"
                  checked={formData.isRecurring}
                  onChange={(e) => setFormData({ ...formData, isRecurring: e.target.checked })}
                  className="peer w-5 h-5 text-blue-600 rounded border-2 border-blue-600 focus:ring-blue-500 transition-all appearance-none checked:bg-blue-600" 
                />
                <Check className="w-3 h-3 text-white absolute left-1 top-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none" />
              </span>
              <span className={`text-[14px] font-bold transition-colors tracking-tight ${formData.isRecurring ? 'text-blue-600' : 'text-gray-700'}`}>Tick to Add Recurring Settings</span>
            </label>
          </div>

          {/* Card 2: Recurring Settings */}
          {formData.isRecurring && (
            <Card className="border-none shadow-[0_4px_20px_rgba(0,0,0,0.08)] bg-white overflow-visible">
              <div 
                className="flex items-center justify-between p-5 border-b border-gray-50 bg-white cursor-pointer hover:bg-gray-50/50 transition-colors"
                onClick={() => setShowRecurring(!showRecurring)}
              >
                <div className="flex items-center gap-2">
                  <span className="text-[13px] font-bold text-gray-800 tracking-wider uppercase">RECURRING SETTINGS</span>
                </div>
                {showRecurring ? <ChevronUp className="w-5 h-5 text-gray-400" /> : <ChevronDown className="w-5 h-5 text-gray-400" />}
              </div>
              
              {showRecurring && (
                <div className="p-5 bg-white space-y-8 animate-in fade-in slide-in-from-top-2 duration-300">
                  <p className="text-[13px] text-gray-900 font-bold leading-relaxed pr-8">
                    The recurring settings are to repeat this blockout for future dates. Please ensure the base blockout dates above are also correct.
                  </p>
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4 items-end">
                    <div className="flex-1">
                      <label className="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Repeat Every</label>
                      <Select
                        value={formData.repeatEvery}
                        options={[
                          { label: 'Select', value: '' },
                          { label: '1 Week', value: '1' },
                          { label: '2 Weeks', value: '2' },
                          { label: '3 Weeks', value: '3' },
                          { label: '4 Weeks', value: '4' },
                          { label: '5 Weeks', value: '5' },
                          { label: '6 Weeks', value: '6' },
                          { label: '7 Weeks', value: '7' },
                          { label: '8 Weeks', value: '8' },
                          { label: '9 Weeks', value: '9' },
                          { label: '10 Weeks', value: '10' },
                          { label: '11 Weeks', value: '11' },
                          { label: '12 Weeks', value: '12' },
                          { label: '13 Weeks', value: '13' },
                          { label: '14 Weeks', value: '14' },
                          { label: '15 Weeks', value: '15' },
                          { label: '16 Weeks', value: '16' },
                          { label: '17 Weeks', value: '17' },
                          { label: '18 Weeks', value: '18' },
                          { label: '19 Weeks', value: '19' },
                          { label: '20 Weeks', value: '20' },
                        ]}
                        onChange={(val) => setFormData({ ...formData, repeatEvery: val.toString() })}
                        className="border-0 border-b border-gray-200 rounded-none px-0 py-2 bg-transparent w-full text-[14px] focus:ring-0 focus:border-blue-500 cursor-pointer"
                      />
                    </div>
                    <div className="flex-1">
                      <label className="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Repeat On</label>
                      <Select
                        value={formData.repeatOn}
                        options={[
                          { label: 'Select', value: '' },
                          { label: 'Monday', value: 'Monday' },
                          { label: 'Tuesday', value: 'Tuesday' },
                          { label: 'Wednesday', value: 'Wednesday' },
                          { label: 'Thursday', value: 'Thursday' },
                          { label: 'Friday', value: 'Friday' },
                          { label: 'Saturday', value: 'Saturday' },
                          { label: 'Sunday', value: 'Sunday' },
                        ]}
                        onChange={(val) => setFormData({ ...formData, repeatOn: val.toString() })}
                        className="border-0 border-b border-gray-200 rounded-none px-0 py-2 bg-transparent w-full text-[14px] focus:ring-0 focus:border-blue-500 cursor-pointer"
                      />
                    </div>
                    <div className="flex-1">
                      <label className="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Repeat Until</label>
                      <Input
                        type="date"
                        value={formData.repeatUntil}
                        onChange={(e) => setFormData({ ...formData, repeatUntil: e.target.value })}
                        className="border-0 border-b border-gray-200 rounded-none px-0 py-2 bg-transparent w-full text-[14px] transition-all focus:ring-0 focus:border-blue-500"
                      />
                    </div>
                  </div>
                </div>
              )}
            </Card>
          )}
        </div>

        {/* Right Column: Conflicts Mock */}
        <div className="w-full lg:w-[480px] space-y-6 shrink-0 lg:sticky lg:top-8">
          <Card className="border-none shadow-[0_4px_15px_rgba(0,0,0,0.06)] bg-white overflow-hidden">
            <div 
              className="p-5 flex items-center justify-between cursor-pointer border-b border-gray-100"
              onClick={() => setShowConflicts(!showConflicts)}
            >
              <span className="text-[13px] font-bold text-gray-800 tracking-tight">Check Conflict</span>
              {showConflicts ? <ChevronUp className="w-4 h-4 text-gray-400" /> : <ChevronDown className="w-4 h-4 text-gray-400" />}
            </div>
            
            {showConflicts && (
              <div className="p-6 text-left text-gray-500">
                <p className="text-[14px] font-medium">No conflicts found for the selected time range.</p>
              </div>
            )}
          </Card>
        </div>
      </form>

      <div className="grid grid-cols-1 lg:grid-cols-[1fr_480px] gap-8 mt-6">
         <div className="flex justify-end gap-4">
           <Button
             type="button"
             variant="secondary"
             onClick={() => navigate('/blockouts')}
           >
             Cancel
           </Button>
           <Button
             type="submit"
             form="blockout-form"
             isLoading={createMutation.isPending}
           >
             Save Blockout
           </Button>
         </div>
         {/* Spacer to align with the sidebar in desktop view */}
         <div className="hidden lg:block"></div>
       </div>
     </div>
  )
}
