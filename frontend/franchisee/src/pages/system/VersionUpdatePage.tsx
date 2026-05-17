import { useState, useEffect } from 'react'
import { Card } from '../../components/ui/Card'
import { ChevronDown, ChevronUp, RefreshCw } from 'lucide-react'
import { PageHeader } from '../../components/layout/PageHeader'
import { versionUpdatesApi, VersionUpdateGroup } from '../../api/services'

export function VersionUpdatePage() {
  const [expandedItems, setExpandedItems] = useState<string[]>([])
  const [versionUpdates, setVersionUpdates] = useState<VersionUpdateGroup[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const fetchVersions = async () => {
      try {
        const data = await versionUpdatesApi.getAll()
        setVersionUpdates(data)
        // Expand the first item by default
        if (data.length > 0) {
          setExpandedItems([`${data[0].month}-${data[0].year}`])
        }
      } catch (error) {
        console.error('Error fetching version updates:', error)
      } finally {
        setLoading(false)
      }
    }

    fetchVersions()
  }, [])

  const toggleItem = (id: string) => {
    setExpandedItems((prev) =>
      prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]
    )
  }

  if (loading) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Version Updates"
          description="View system updates and release notes"
          icon={<RefreshCw className="w-5 h-5" />}
        />
        <div className="flex items-center justify-center h-64">
          <div className="text-gray-500">Loading version updates...</div>
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Version Updates"
        description="View system updates and release notes"
        icon={<RefreshCw className="w-5 h-5" />}
      />

      <div className="space-y-4">
        {versionUpdates.map((update, index) => {
          const id = `${update.month}-${update.year}`
          const isExpanded = expandedItems.includes(id)

          return (
            <Card key={index} className="overflow-hidden">
              <button
                onClick={() => toggleItem(id)}
                className="w-full flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition-colors text-left"
              >
                <span className="text-gray-900 font-semibold text-lg">
                  {update.month} {update.year}
                </span>
                {isExpanded ? (
                  <ChevronUp className="w-5 h-5 text-gray-500" />
                ) : (
                  <ChevronDown className="w-5 h-5 text-gray-500" />
                )}
              </button>

              {isExpanded && (
                <div className="px-6 pb-4 border-t border-gray-200">
                  {update.versions && update.versions.length > 0 ? (
                    <div className="space-y-6 pt-4">
                      {update.versions.map((version, idx) => (
                        <div key={idx} className="space-y-2">
                          <div className="flex items-start gap-4">
                            <span className="text-sm font-semibold text-gray-700 min-w-[80px]">
                              {version.version}
                            </span>
                            <div className="flex-1">
                              {version.changes.map((change, changeIdx) => (
                                <div key={changeIdx} className="text-sm text-gray-600 leading-relaxed">
                                  - {change}
                                </div>
                              ))}
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="py-4">
                      <p className="text-sm text-gray-500 italic">
                        No updates available for this period.
                      </p>
                    </div>
                  )}
                </div>
              )}
            </Card>
          )
        })}
      </div>

      <div className="text-center text-sm text-gray-500 py-4">
        <p>System updates are released regularly to improve functionality and fix issues.</p>
      </div>
    </div>
  )
}
