import { useEffect, useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { Save, Inbox, Loader2 } from 'lucide-react'
import { settingsApi } from '../../api/services'
import type { SystemSetting } from '../../types'

type InputKind = 'boolean' | 'number' | 'json' | 'text'
type EditValue = string | boolean

function humanize(text: string): string {
  if (!text) return text
  const spaced = text
    .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
    .replace(/[_-]+/g, ' ')
    .trim()
  return spaced
    .split(' ')
    .filter(Boolean)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join(' ')
}

function getInputKind(setting: SystemSetting): InputKind {
  const declaredType = (setting.type || '').toLowerCase()
  if (declaredType === 'boolean' || declaredType === 'bool') return 'boolean'
  if (declaredType === 'number' || declaredType === 'integer' || declaredType === 'float' || declaredType === 'int') return 'number'
  if (declaredType === 'json' || declaredType === 'array' || declaredType === 'object') return 'json'
  if (declaredType === 'string' || declaredType === 'text') return 'text'

  // No usable declared type - infer from the value itself.
  const value = setting.value
  if (typeof value === 'boolean') return 'boolean'
  if (typeof value === 'number') return 'number'
  if (typeof value === 'string') return 'text'
  if (value !== null && typeof value === 'object') return 'json'
  return 'text'
}

function toEditValue(kind: InputKind, value: unknown): EditValue {
  if (kind === 'boolean') return Boolean(value)
  if (kind === 'number') return value === null || value === undefined ? '' : String(value)
  if (kind === 'text') return value === null || value === undefined ? '' : String(value)
  // json (or anything we couldn't confidently type) - fall back to a stringified representation
  try {
    return JSON.stringify(value, null, 2) ?? ''
  } catch {
    return String(value)
  }
}

function fromEditValue(kind: InputKind, edit: EditValue): unknown {
  if (kind === 'boolean') return Boolean(edit)
  if (kind === 'number') {
    const num = Number(edit)
    return Number.isNaN(num) ? edit : num
  }
  if (kind === 'json') {
    try {
      return JSON.parse(edit as string)
    } catch {
      // Couldn't parse it back into JSON - send the raw text rather than crash.
      return edit
    }
  }
  return edit
}

function buildEditValues(settings: SystemSetting[]): Record<string, EditValue> {
  const values: Record<string, EditValue> = {}
  for (const setting of settings) {
    values[setting.key] = toEditValue(getInputKind(setting), setting.value)
  }
  return values
}

export function SettingsPage() {
  const queryClient = useQueryClient()
  const [activeGroup, setActiveGroup] = useState<string | null>(null)
  const [editValues, setEditValues] = useState<Record<string, EditValue>>({})
  const [initialValues, setInitialValues] = useState<Record<string, EditValue>>({})
  const [saving, setSaving] = useState(false)

  const {
    data: groups,
    isLoading: groupsLoading,
    isError: groupsError,
  } = useQuery({
    queryKey: ['settings-groups'],
    queryFn: () => settingsApi.groups(),
  })

  useEffect(() => {
    if (groups && groups.length > 0 && (!activeGroup || !groups.includes(activeGroup))) {
      setActiveGroup(groups[0])
    }
    if (groups && groups.length === 0) {
      setActiveGroup(null)
    }
  }, [groups, activeGroup])

  const {
    data: settings,
    isLoading: settingsLoading,
  } = useQuery({
    queryKey: ['settings-by-group', activeGroup],
    queryFn: () => settingsApi.byGroup(activeGroup as string),
    enabled: !!activeGroup,
  })

  useEffect(() => {
    if (settings) {
      const values = buildEditValues(settings)
      setEditValues(values)
      setInitialValues(values)
    }
  }, [settings])

  const handleChange = (key: string, value: EditValue) => {
    setEditValues((prev) => ({ ...prev, [key]: value }))
  }

  const changedSettings = (settings ?? []).filter((s) => editValues[s.key] !== initialValues[s.key])
  const hasChanges = changedSettings.length > 0

  const handleSave = async () => {
    if (!settings || changedSettings.length === 0) return

    setSaving(true)
    try {
      const payload = changedSettings.map((setting) => ({
        key: setting.key,
        value: fromEditValue(getInputKind(setting), editValues[setting.key]),
      }))
      await settingsApi.updateBulk(payload)
      toast.success('Settings updated successfully')
      await queryClient.invalidateQueries({ queryKey: ['settings-by-group', activeGroup] })
    } catch (error) {
      toast.error('Failed to update settings')
    } finally {
      setSaving(false)
    }
  }

  const renderInput = (setting: SystemSetting) => {
    const kind = getInputKind(setting)
    const label = setting.description || humanize(setting.key)

    if (kind === 'boolean') {
      return (
        <div className="mb-5" key={setting.key}>
          <label className="form-checkbox">
            <input
              type="checkbox"
              checked={Boolean(editValues[setting.key])}
              onChange={(e) => handleChange(setting.key, e.target.checked)}
            />
            <span>{label}</span>
          </label>
          <p className="text-xs text-gray-400 mt-1 ml-7">{setting.key}</p>
        </div>
      )
    }

    return (
      <div className="mb-5" key={setting.key}>
        <label className="form-label">{label}</label>
        {kind === 'json' ? (
          <textarea
            className="form-input form-textarea font-mono text-sm"
            rows={6}
            value={(editValues[setting.key] as string) ?? ''}
            onChange={(e) => handleChange(setting.key, e.target.value)}
          />
        ) : (
          <input
            type={kind === 'number' ? 'number' : 'text'}
            className="form-input"
            value={(editValues[setting.key] as string) ?? ''}
            onChange={(e) => handleChange(setting.key, e.target.value)}
          />
        )}
        <p className="text-xs text-gray-400 mt-1">{setting.key}</p>
      </div>
    )
  }

  return (
    <div className="page-content">
      <h1 className="page-title">Settings</h1>

      {groupsLoading && (
        <div className="card">
          <div className="card-body p-10 flex items-center justify-center gap-2 text-gray-500">
            <Loader2 size={18} className="animate-spin" />
            Loading settings...
          </div>
        </div>
      )}

      {!groupsLoading && groupsError && (
        <div className="card">
          <div className="card-body p-10 text-center text-red-500">
            Failed to load settings. Please try again later.
          </div>
        </div>
      )}

      {!groupsLoading && !groupsError && groups && groups.length === 0 && (
        <div className="card">
          <div className="card-body p-10 flex flex-col items-center justify-center text-center gap-2 text-gray-500">
            <Inbox size={32} className="text-gray-300" />
            <p className="font-medium text-gray-600">No settings configured yet</p>
            <p className="text-sm text-gray-400">System settings will appear here once they have been added.</p>
          </div>
        </div>
      )}

      {!groupsLoading && !groupsError && groups && groups.length > 0 && (
        <>
          <div className="tabs">
            {groups.map((group) => (
              <div
                key={group}
                className={`tab ${group === activeGroup ? 'active' : ''}`}
                onClick={() => setActiveGroup(group)}
              >
                {humanize(group)}
              </div>
            ))}
          </div>

          <div className="card">
            <div className="card-header">
              <h2 className="card-title">{activeGroup ? humanize(activeGroup) : ''} Settings</h2>
            </div>
            <div className="card-body p-6">
              {settingsLoading ? (
                <div className="flex items-center justify-center gap-2 text-gray-500 py-10">
                  <Loader2 size={18} className="animate-spin" />
                  Loading...
                </div>
              ) : !settings || settings.length === 0 ? (
                <div className="flex flex-col items-center justify-center text-center gap-2 text-gray-500 py-10">
                  <Inbox size={32} className="text-gray-300" />
                  <p className="font-medium text-gray-600">No settings in this group</p>
                </div>
              ) : (
                <>
                  {settings.map((setting) => renderInput(setting))}
                  <div className="flex justify-end mt-6">
                    <button
                      type="button"
                      className="btn btn-primary"
                      disabled={!hasChanges || saving}
                      onClick={handleSave}
                    >
                      <Save size={14} />
                      {saving ? 'Saving...' : 'Save Changes'}
                    </button>
                  </div>
                </>
              )}
            </div>
          </div>
        </>
      )}
    </div>
  )
}
