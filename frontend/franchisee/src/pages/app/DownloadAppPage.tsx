import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Apple, Smartphone, Download } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'

export function DownloadAppPage() {
  return (
    <div className="space-y-6">
      <PageHeader
        title="Download Mobile Apps"
        description="Get the mobile app for iOS and Android"
        icon={<Download className="w-5 h-5" />}
      />

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-6xl mx-auto">
        {/* iOS Card */}
        <Card className="p-8">
          <div className="flex items-start justify-between mb-6">
            <div>
              <div className="flex items-center gap-3 mb-2">
                <Apple className="w-8 h-8" />
                <h2 className="text-2xl font-bold text-gray-800">Download App for Apple/iOS</h2>
              </div>
              <p className="text-gray-600">Get the app on your iPhone or iPad</p>
            </div>
            <div className="w-16 h-16 bg-blue-500 rounded-2xl flex items-center justify-center">
              <Apple className="w-10 h-10 text-white" />
            </div>
          </div>

          <div className="mb-6">
            <h3 className="font-semibold text-gray-800 mb-4">Installation Guide</h3>
            <div className="space-y-3">
              <div className="flex items-start gap-3">
                <div className="w-8 h-8 bg-gray-900 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">
                  1
                </div>
                <div className="flex-1 pt-1">
                  <p className="text-gray-700">Click the Download button below</p>
                </div>
                <div className="w-8 h-8 bg-gray-200 text-gray-700 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">
                  2
                </div>
                <div className="flex-1 pt-1">
                  <p className="text-gray-700">Scan the QR code generated</p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="w-8 h-8 bg-gray-900 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">
                  3
                </div>
                <div className="flex-1 pt-1">
                  <p className="text-gray-700">Redeem the installation code</p>
                </div>
                <div className="w-8 h-8 bg-gray-200 text-gray-700 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">
                  4
                </div>
                <div className="flex-1 pt-1">
                  <p className="text-gray-700">Follow installation prompts</p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="w-8 h-8 bg-gray-900 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">
                  5
                </div>
                <div className="flex-1 pt-1">
                  <p className="text-gray-700">Trust developer in Settings (if prompted)</p>
                </div>
              </div>
            </div>
          </div>

          <div className="flex justify-center mb-6">
            <Button className="bg-black hover:bg-gray-800 text-white px-8 py-3 text-base">
              <Apple className="w-5 h-5 mr-2" />
              Download Apple/iOS App
            </Button>
          </div>

          <div className="mb-6">
            <h3 className="font-semibold text-gray-800 mb-3">System Requirements</h3>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <p className="text-sm text-gray-600">OS Version:</p>
                <p className="text-gray-800 font-medium">iOS 14+</p>
              </div>
              <div>
                <p className="text-sm text-gray-600">File Size:</p>
                <p className="text-gray-800 font-medium">45 MB</p>
              </div>
              <div>
                <p className="text-sm text-gray-600">Storage:</p>
                <p className="text-gray-800 font-medium">150 MB</p>
              </div>
            </div>
          </div>

          <div>
            <h3 className="font-semibold text-gray-800 mb-3">How to Check Your Device Specifications</h3>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <div className="flex items-start gap-2">
                  <div className="w-6 h-6 bg-gray-900 text-white rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold">
                    1
                  </div>
                  <div>
                    <p className="font-medium text-gray-800 text-sm">Go to Settings</p>
                    <p className="text-xs text-gray-600">Open Settings app on your iPhone/iPad</p>
                  </div>
                </div>
                <div className="flex items-start gap-2">
                  <div className="w-6 h-6 bg-gray-900 text-white rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold">
                    3
                  </div>
                  <div>
                    <p className="font-medium text-gray-800 text-sm">Tap About</p>
                    <p className="text-xs text-gray-600">Select 'About' to see device information</p>
                  </div>
                </div>
              </div>
              <div className="space-y-2">
                <div className="flex items-start gap-2">
                  <div className="w-6 h-6 bg-gray-200 text-gray-700 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold">
                    2
                  </div>
                  <div>
                    <p className="font-medium text-gray-800 text-sm">Select General</p>
                    <p className="text-xs text-gray-600">Tap on 'General' in the Settings menu</p>
                  </div>
                </div>
                <div className="flex items-start gap-2">
                  <div className="w-6 h-6 bg-gray-200 text-gray-700 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold">
                    4
                  </div>
                  <div>
                    <p className="font-medium text-gray-800 text-sm">Check Details</p>
                    <p className="text-xs text-gray-600">Check iOS version and storage</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </Card>

        {/* Android Card */}
        <Card className="p-8">
          <div className="flex items-start justify-between mb-6">
            <div>
              <div className="flex items-center gap-3 mb-2">
                <Smartphone className="w-8 h-8 text-green-600" />
                <h2 className="text-2xl font-bold text-gray-800">Download App for Samsung/Android</h2>
              </div>
              <p className="text-gray-600">Get the app on your android device</p>
            </div>
            <div className="w-16 h-16 bg-gradient-to-br from-green-400 to-blue-500 rounded-2xl flex items-center justify-center">
              <Download className="w-10 h-10 text-white" />
            </div>
          </div>

          <div className="mb-6">
            <h3 className="font-semibold text-gray-800 mb-4">Installation Guide</h3>
            <div className="space-y-3">
              <div className="flex items-start gap-3">
                <div className="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">
                  1
                </div>
                <div className="flex-1 pt-1">
                  <p className="text-gray-700">Click the Download button below</p>
                </div>
                <div className="w-8 h-8 bg-green-200 text-green-700 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">
                  2
                </div>
                <div className="flex-1 pt-1">
                  <p className="text-gray-700">Scan the QR code generated</p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">
                  3
                </div>
                <div className="flex-1 pt-1">
                  <p className="text-gray-700">Tap 'Install' button</p>
                </div>
                <div className="w-8 h-8 bg-green-200 text-green-700 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">
                  4
                </div>
                <div className="flex-1 pt-1">
                  <p className="text-gray-700">Follow installation prompts</p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">
                  5
                </div>
                <div className="flex-1 pt-1">
                  <p className="text-gray-700">Accept permissions (if prompted)</p>
                </div>
              </div>
            </div>
          </div>

          <div className="flex justify-center mb-6">
            <Button className="bg-green-600 hover:bg-green-700 text-white px-8 py-3 text-base">
              <Smartphone className="w-5 h-5 mr-2" />
              Download Samsung/Android App
            </Button>
          </div>

          <div className="mb-6">
            <h3 className="font-semibold text-gray-800 mb-3">System Requirements</h3>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <p className="text-sm text-gray-600">OS Version:</p>
                <p className="text-gray-800 font-medium">Android 7.0+</p>
              </div>
              <div>
                <p className="text-sm text-gray-600">File Size:</p>
                <p className="text-gray-800 font-medium">~35 MB</p>
              </div>
              <div>
                <p className="text-sm text-gray-600">RAM:</p>
                <p className="text-gray-800 font-medium">2 GB minimum</p>
              </div>
              <div>
                <p className="text-sm text-gray-600">Storage:</p>
                <p className="text-gray-800 font-medium">150 MB</p>
              </div>
            </div>
          </div>

          <div>
            <h3 className="font-semibold text-gray-800 mb-3">How to Check Your Device Specifications</h3>
            <div className="space-y-3">
              <div className="flex items-start gap-2">
                <div className="w-6 h-6 bg-green-600 text-white rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold">
                  1
                </div>
                <div className="flex-1">
                  <p className="font-medium text-gray-800 text-sm">Go to Settings</p>
                  <p className="text-xs text-gray-600">Open Settings app on your Android device</p>
                </div>
                <div className="w-6 h-6 bg-green-200 text-green-700 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold">
                  2
                </div>
                <div className="flex-1">
                  <p className="font-medium text-gray-800 text-sm">Select About Phone</p>
                  <p className="text-xs text-gray-600">Scroll down and tap 'About Phone'</p>
                </div>
              </div>
              <div className="flex items-start gap-2">
                <div className="w-6 h-6 bg-green-600 text-white rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold">
                  3
                </div>
                <div className="flex-1">
                  <p className="font-medium text-gray-800 text-sm">Check Details</p>
                  <p className="text-xs text-gray-600">View Android version, RAM, and storage</p>
                </div>
              </div>
            </div>
          </div>
        </Card>
      </div>
    </div>
  )
}
