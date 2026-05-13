import { Card } from '../../components/ui/Card'
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts'

const data = [
  { name: 'Jan', revenue: 4000 },
  { name: 'Feb', revenue: 3000 },
  { name: 'Mar', revenue: 5000 },
  { name: 'Apr', revenue: 4500 },
  { name: 'May', revenue: 6000 },
  { name: 'Jun', revenue: 5500 },
]

const pieData = [
  { name: 'Grooming', value: 400 },
  { name: 'Bathing', value: 300 },
  { name: 'Nail Trim', value: 200 },
  { name: 'Other', value: 100 },
]

const COLORS = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444']

export function ReportPage() {
  return (
    <div className="space-y-6">
      <Card className="px-4 py-3 shadow-sm border-gray-200">
        <h1 className="text-xl font-bold text-gray-800">Reports</h1>
      </Card>
      
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card title="Revenue Overview" className="p-4">
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={data}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="name" />
                <YAxis />
                <Tooltip />
                <Bar dataKey="revenue" fill="#3b82f6" />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </Card>
        
        <Card title="Service Distribution" className="p-4">
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={pieData}
                  cx="50%"
                  cy="50%"
                  outerRadius={80}
                  dataKey="value"
                  label
                >
                  {pieData.map((_, index) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip />
              </PieChart>
            </ResponsiveContainer>
          </div>
        </Card>
      </div>
      
      <Card title="Summary Statistics" className="p-6">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div className="text-center">
            <p className="text-3xl font-bold text-primary-600">156</p>
            <p className="text-sm text-secondary-500">Total Bookings</p>
          </div>
          <div className="text-center">
            <p className="text-3xl font-bold text-green-600">$24,500</p>
            <p className="text-sm text-secondary-500">Total Revenue</p>
          </div>
          <div className="text-center">
            <p className="text-3xl font-bold text-blue-600">89</p>
            <p className="text-sm text-secondary-500">Active Customers</p>
          </div>
          <div className="text-center">
            <p className="text-3xl font-bold text-purple-600">12</p>
            <p className="text-sm text-secondary-500">New Leads</p>
          </div>
        </div>
      </Card>
    </div>
  )
}
