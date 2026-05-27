import { useState } from 'react'

interface StatCard {
  label: string
  value: number
  description: string
  color: 'cyan' | 'blue' | 'red' | 'purple'
}

const memberStats: StatCard[] = [
  { label: 'Active Members', value: 228, description: 'List of active members', color: 'cyan' },
  { label: 'Social', value: 236, description: 'Total members active for social interaction', color: 'blue' },
  { label: 'Admin Members', value: 21, description: 'Members with admin access', color: 'red' },
  { label: 'Ts and Cs', value: 220, description: "Members who hasn't accepted Ts and Cs", color: 'purple' },
]

const memberStats2: StatCard[] = [
  { label: 'Total Members', value: 432, description: 'Total registered members', color: 'cyan' },
  { label: 'Migrated Members', value: 90, description: 'Members who have sold the business', color: 'blue' },
  { label: 'Inactive Members', value: 204, description: 'Deactivated members', color: 'red' },
]

const stateStats: StatCard[] = [
  { label: 'Australian Capital Territory', value: 6, description: 'Total active ACT members', color: 'cyan' },
  { label: 'New South Wales', value: 108, description: 'Total active NSW members', color: 'blue' },
  { label: 'Northern Territory', value: 11, description: 'Total active NT members', color: 'red' },
  { label: 'Queensland', value: 102, description: 'Total active QLD members', color: 'purple' },
]

export function Dashboard() {
  const [activeTab, setActiveTab] = useState('members')
  const [stateTab, setStateTab] = useState('members')

  return (
    <div className="page-content">
      <h1 className="page-title">Dashboard</h1>

      {/* Stats Tabs */}
      <div className="tabs">
        {['MEMBERS', 'BOOKINGS', 'CUSTOMERS', 'PETS'].map((tab) => (
          <div
            key={tab}
            className={`tab ${activeTab === tab.toLowerCase() ? 'active' : ''}`}
            onClick={() => setActiveTab(tab.toLowerCase())}
          >
            {tab}
          </div>
        ))}
      </div>

      {/* Stats Grid - Row 1 */}
      <div className="stats-grid">
        {memberStats.map((stat) => (
          <div key={stat.label} className={`stat-card ${stat.color}`}>
            <div className="stat-label">{stat.label}</div>
            <div className="stat-value">{stat.value}</div>
            <div className="stat-description">{stat.description}</div>
          </div>
        ))}
      </div>

      {/* Stats Grid - Row 2 (3 columns) */}
      <div className="stats-grid" style={{ gridTemplateColumns: 'repeat(3, 1fr)' }}>
        {memberStats2.map((stat) => (
          <div key={stat.label} className={`stat-card ${stat.color}`}>
            <div className="stat-label">{stat.label}</div>
            <div className="stat-value">{stat.value}</div>
            <div className="stat-description">{stat.description}</div>
          </div>
        ))}
      </div>

      {/* Statewise Distribution */}
      <h2 className="text-lg font-semibold text-gray-800 mb-4 mt-8">
        Statewise distribution of active members and customers
      </h2>

      <div className="tabs">
        {['MEMBERS', 'CUSTOMERS', 'PETS'].map((tab) => (
          <div
            key={tab}
            className={`tab ${stateTab === tab.toLowerCase() ? 'active' : ''}`}
            onClick={() => setStateTab(tab.toLowerCase())}
          >
            {tab}
          </div>
        ))}
      </div>

      <div className="stats-grid">
        {stateStats.map((stat) => (
          <div key={stat.label} className={`stat-card ${stat.color}`}>
            <div className="stat-label">{stat.label}</div>
            <div className="stat-value">{stat.value}</div>
            <div className="stat-description">{stat.description}</div>
          </div>
        ))}
      </div>
    </div>
  )
}
