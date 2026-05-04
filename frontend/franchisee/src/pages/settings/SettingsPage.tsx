import { useState } from 'react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { useAuthStore } from '../../store/authStore'
import { Shield, Bell, Lock, User, Mail, Smartphone, Moon, Globe } from 'lucide-react'

export function SettingsPage() {
  const user = useAuthStore((state) => state.user)
  const [activeSection, setActiveSection] = useState<'profile' | 'security' | 'notifications' | 'system'>('profile')

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-secondary-900">Settings</h1>

      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {/* Sidebar */}
        <div className="lg:col-span-1">
          <Card className="p-2">
            <nav className="space-y-1">
              {[
                { id: 'profile', label: 'Profile', icon: User },
                { id: 'security', label: 'Security', icon: Shield },
                { id: 'notifications', label: 'Notifications', icon: Bell },
                { id: 'system', label: 'System', icon: Globe },
              ].map((section) => {
                const Icon = section.icon
                return (
                  <button
                    key={section.id}
                    onClick={() => setActiveSection(section.id as typeof activeSection)}
                    className={`w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                      activeSection === section.id
                        ? 'bg-primary-50 text-primary-700'
                        : 'text-secondary-700 hover:bg-secondary-100'
                    }`}
                  >
                    <Icon className="w-4 h-4" />
                    {section.label}
                  </button>
                )
              })}
            </nav>
          </Card>
        </div>

        {/* Content */}
        <div className="lg:col-span-3">
          {activeSection === 'profile' && (
            <Card title="Profile Settings">
              <div className="space-y-6 max-w-lg">
                <div className="flex items-center gap-4">
                  <div className="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center">
                    <User className="w-8 h-8 text-primary-700" />
                  </div>
                  <div>
                    <Button variant="secondary" size="sm">Change Avatar</Button>
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <Input label="First Name" defaultValue={user?.name?.split(' ')[0]} />
                  <Input label="Last Name" defaultValue={user?.name?.split(' ')[1]} />
                </div>
                <Input label="Email" type="email" defaultValue={user?.email} />
                <Input label="Phone" />
                <div>
                  <label className="text-sm font-medium text-secondary-700">Role</label>
                  <input
                    type="text"
                    value={user?.role ?? ''}
                    disabled
                    className="input mt-1 bg-secondary-50"
                  />
                </div>
                <Button>Save Changes</Button>
              </div>
            </Card>
          )}

          {activeSection === 'security' && (
            <div className="space-y-6">
              <Card title="Password">
                <div className="space-y-4 max-w-lg">
                  <Input label="Current Password" type="password" />
                  <Input label="New Password" type="password" />
                  <Input label="Confirm New Password" type="password" />
                  <Button>Update Password</Button>
                </div>
              </Card>

              <Card title="Two-Factor Authentication">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                      <Shield className="w-5 h-5 text-green-600" />
                    </div>
                    <div>
                      <p className="font-medium text-secondary-900">2FA is enabled</p>
                      <p className="text-sm text-secondary-500">Your account is protected with Google Authenticator</p>
                    </div>
                  </div>
                  <Button variant="secondary" size="sm">Configure</Button>
                </div>
              </Card>

              <Card title="Session Management">
                <div className="space-y-3">
                  <div className="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                    <div className="flex items-center gap-3">
                      <Lock className="w-4 h-4 text-secondary-500" />
                      <div>
                        <p className="text-sm font-medium text-secondary-900">Current Session</p>
                        <p className="text-xs text-secondary-500">Chrome on Windows · Started 2 hours ago</p>
                      </div>
                    </div>
                    <span className="text-xs font-medium text-green-600">Active</span>
                  </div>
                </div>
              </Card>
            </div>
          )}

          {activeSection === 'notifications' && (
            <Card title="Notification Preferences">
              <div className="space-y-4 max-w-lg">
                <label className="flex items-center justify-between p-3 hover:bg-secondary-50 rounded-lg cursor-pointer">
                  <div className="flex items-center gap-3">
                    <Mail className="w-5 h-5 text-secondary-500" />
                    <div>
                      <p className="font-medium text-secondary-900">Email Notifications</p>
                      <p className="text-sm text-secondary-500">Receive booking confirmations and updates</p>
                    </div>
                  </div>
                  <input type="checkbox" defaultChecked className="rounded border-secondary-300 text-primary-600" />
                </label>

                <label className="flex items-center justify-between p-3 hover:bg-secondary-50 rounded-lg cursor-pointer">
                  <div className="flex items-center gap-3">
                    <Smartphone className="w-5 h-5 text-secondary-500" />
                    <div>
                      <p className="font-medium text-secondary-900">SMS Notifications</p>
                      <p className="text-sm text-secondary-500">Get text alerts for urgent updates</p>
                    </div>
                  </div>
                  <input type="checkbox" className="rounded border-secondary-300 text-primary-600" />
                </label>

                <label className="flex items-center justify-between p-3 hover:bg-secondary-50 rounded-lg cursor-pointer">
                  <div className="flex items-center gap-3">
                    <Bell className="w-5 h-5 text-secondary-500" />
                    <div>
                      <p className="font-medium text-secondary-900">Push Notifications</p>
                      <p className="text-sm text-secondary-500">Browser notifications for new bookings</p>
                    </div>
                  </div>
                  <input type="checkbox" defaultChecked className="rounded border-secondary-300 text-primary-600" />
                </label>
              </div>
            </Card>
          )}

          {activeSection === 'system' && (
            <div className="space-y-6">
              <Card title="Appearance">
                <div className="space-y-4 max-w-lg">
                  <label className="flex items-center justify-between p-3 hover:bg-secondary-50 rounded-lg cursor-pointer">
                    <div className="flex items-center gap-3">
                      <Moon className="w-5 h-5 text-secondary-500" />
                      <div>
                        <p className="font-medium text-secondary-900">Dark Mode</p>
                        <p className="text-sm text-secondary-500">Toggle dark theme</p>
                      </div>
                    </div>
                    <input type="checkbox" className="rounded border-secondary-300 text-primary-600" />
                  </label>
                </div>
              </Card>

              <Card title="Regional">
                <div className="space-y-4 max-w-lg">
                  <div>
                    <label className="text-sm font-medium text-secondary-700">Language</label>
                    <select className="input mt-1">
                      <option>English (US)</option>
                      <option>English (UK)</option>
                      <option>Spanish</option>
                    </select>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-secondary-700">Time Zone</label>
                    <select className="input mt-1">
                      <option>UTC-05:00 Eastern Time</option>
                      <option>UTC-06:00 Central Time</option>
                      <option>UTC-07:00 Mountain Time</option>
                      <option>UTC-08:00 Pacific Time</option>
                    </select>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-secondary-700">Date Format</label>
                    <select className="input mt-1">
                      <option>MM/DD/YYYY</option>
                      <option>DD/MM/YYYY</option>
                      <option>YYYY-MM-DD</option>
                    </select>
                  </div>
                </div>
              </Card>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
