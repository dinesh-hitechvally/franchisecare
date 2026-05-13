
import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Card } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Table } from '../../components/ui/Table';
import { Input } from '../../components/ui/Input';
import { Select } from '../../components/ui/Select';
import { Search, Filter } from 'lucide-react';
import { bookingReportsApi } from '../../api/services';
import { useEffect } from 'react';
import {
  BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Legend, CartesianGrid, PieChart, Pie, Cell,
} from 'recharts'; // recharts components for charts

export function BookingReportsPage() {
  const now = new Date();
  const defaultFrom = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10);
  const defaultTo = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10);

  const [fromDate, setFromDate] = useState(defaultFrom);
  const [toDate, setToDate] = useState(defaultTo);
  const [appliedFromDate, setAppliedFromDate] = useState(defaultFrom);
  const [appliedToDate, setAppliedToDate] = useState(defaultTo);
  const [searchTerm, setSearchTerm] = useState('');
  const [status, setStatus] = useState('');
  const [customer, setCustomer] = useState('');
  const [customerOptions, setCustomerOptions] = useState<{ label: string; value: string }[]>([]);
  const [min, setMin] = useState('');
  const [max, setMax] = useState('');
  const [hiddenPieSegments, setHiddenPieSegments] = useState<Set<string>>(new Set());

  // Fetch customer options for dropdown (simulate API call)
  useEffect(() => {
    // Replace with real API call if available
    // Example: customersApi.getAll().then(...)
    setCustomerOptions([
      { label: 'All', value: '' },
      { label: 'John Doe', value: '1' },
      { label: 'Jane Smith', value: '2' },
      { label: 'Acme Corp', value: '3' },
    ]);
  }, []);

  const { data, isLoading } = useQuery({
    queryKey: ['booking-report', appliedFromDate, appliedToDate, status, customer, min, max],
    queryFn: () => bookingReportsApi.getReport({
      date_from: appliedFromDate,
      date_to: appliedToDate,
      status: status || undefined,
      customer_id: customer || undefined,
      min: min || undefined,
      max: max || undefined,
    }),
  });

  const summary = data?.summary || { total_bookings: 0, completed: 0, cancelled: 0, no_show: 0, revenue: 0 };
  let rows = data?.data || [];
  if (searchTerm) {
    rows = rows.filter((row: any) =>
      row.date?.toLowerCase().includes(searchTerm.toLowerCase())
    );
  }

  // Prepare pie chart data for "All my bookings"
  const bookingStatusData = [
    { name: 'Completed', value: summary.completed },
    { name: 'Pending', value: summary.total_bookings - summary.completed - summary.cancelled - summary.no_show },
    { name: 'Cancelled', value: summary.cancelled },
    { name: 'Deleted', value: summary.no_show },
  ];
  const COLORS = ['#10b981', '#3b82f6', '#ef4444', '#6b7280'];

  // Filter pie data based on hidden segments
  const filteredPieData = bookingStatusData.filter(item => !hiddenPieSegments.has(item.name));

  // Handle legend click
  const handleLegendClick = (name: string) => {
    setHiddenPieSegments(prev => {
      const newSet = new Set(prev);
      if (newSet.has(name)) {
        newSet.delete(name);
      } else {
        newSet.add(name);
      }
      return newSet;
    });
  };

  // Custom legend renderer
  const renderCustomLegend = () => {
    return (
      <div className="flex flex-wrap gap-4 justify-center mt-4">
        {bookingStatusData.map((item, index) => {
          const isHidden = hiddenPieSegments.has(item.name);
          const color = COLORS[index % COLORS.length];
          return (
            <div
              key={`legend-${index}`}
              onClick={() => handleLegendClick(item.name)}
              style={{ cursor: 'pointer' }}
              className="flex items-center gap-2 px-3 py-1 rounded transition-all"
            >
              <span
                style={{
                  display: 'inline-block',
                  width: 12,
                  height: 12,
                  backgroundColor: isHidden ? '#d1d5db' : color,
                  borderRadius: '2px',
                  transition: 'background-color 0.2s',
                }}
              />
              <span style={{ fontSize: '12px', fontWeight: 500, color: isHidden ? '#9ca3af' : '#374151', transition: 'color 0.2s' }}>
                {item.name}
              </span>
            </div>
          );
        })}
      </div>
    );
  };

  // Filter section
  return (
    <div className="space-y-6 px-1 py-1 w-full">
      <div className="bg-white py-4 shadow-sm rounded-md border border-gray-200 px-8 -mt-6 -mx-8 mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Booking Reports</h1>
      </div>

      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <form
          onSubmit={e => {
            e.preventDefault();
            setAppliedFromDate(fromDate);
            setAppliedToDate(toDate);
          }}
        >
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end mb-4">
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-gray-600">From</label>
              <input
                type="date"
                value={fromDate}
                onChange={(e) => setFromDate(e.target.value)}
                className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500/20"
              />
            </div>
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-gray-600">To</label>
              <input
                type="date"
                value={toDate}
                onChange={(e) => setToDate(e.target.value)}
                className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500/20"
              />
            </div>
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-gray-600">Customer</label>
              <Select
                options={customerOptions}
                value={customer}
                onChange={setCustomer}
                placeholder="All Customers"
              />
            </div>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-gray-600">Min</label>
              <Input
                type="number"
                placeholder="Min value"
                value={min}
                onChange={e => setMin(e.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-gray-600">Max</label>
              <Input
                type="number"
                placeholder="Max value"
                value={max}
                onChange={e => setMax(e.target.value)}
              />
            </div>
            <div className="flex items-end">
              <Button
                type="submit"
                className="flex items-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-md transition-all shadow-sm font-medium text-sm"
              >
                <Filter size={18} />
                Generate Report
              </Button>
            </div>
          </div>
        </form>
      </div>

      {/* All my bookings - Pie Chart + Table Section */}
      <Card>
        <div className="p-4 border-b border-gray-200 bg-gray-50">
          <h2 className="text-lg font-semibold text-gray-700">All my bookings</h2>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
          {/* Pie Chart */}
          <div className="flex items-center justify-center">
            <ResponsiveContainer width="100%" height={300}>
              <PieChart>
                <Pie
                  data={filteredPieData}
                  cx="50%"
                  cy="50%"
                  outerRadius={80}
                  fill="#8884d8"
                  dataKey="value"
                >
                  {filteredPieData.map((entry, index) => {
                    const originalIndex = bookingStatusData.findIndex(item => item.name === entry.name);
                    return <Cell key={`cell-${index}`} fill={COLORS[originalIndex % COLORS.length]} />;
                  })}
                </Pie>
                <Tooltip />
                <Legend content={renderCustomLegend} />
              </PieChart>
            </ResponsiveContainer>
          </div>

          {/* Status Count Table */}
          <div>
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse text-sm">
                <thead>
                  <tr className="border-b border-gray-200">
                    <th className="px-4 py-3 font-semibold text-gray-700">Status</th>
                    <th className="px-4 py-3 font-semibold text-gray-700 text-right">Count</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  <tr className="hover:bg-gray-50">
                    <td className="px-4 py-3 text-gray-800 font-medium">Completed</td>
                    <td className="px-4 py-3 text-gray-700 text-right text-green-600 font-semibold">{summary.completed}</td>
                  </tr>
                  <tr className="hover:bg-gray-50">
                    <td className="px-4 py-3 text-gray-800 font-medium">Pending</td>
                    <td className="px-4 py-3 text-gray-700 text-right text-blue-600 font-semibold">{summary.total_bookings - summary.completed - summary.cancelled - summary.no_show}</td>
                  </tr>
                  <tr className="hover:bg-gray-50">
                    <td className="px-4 py-3 text-gray-800 font-medium">Cancelled</td>
                    <td className="px-4 py-3 text-gray-700 text-right text-red-600 font-semibold">{summary.cancelled}</td>
                  </tr>
                  <tr className="hover:bg-gray-50">
                    <td className="px-4 py-3 text-gray-800 font-medium">Deleted</td>
                    <td className="px-4 py-3 text-gray-700 text-right text-gray-600 font-semibold">{summary.no_show}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </Card>

      {/* Chart Section */}
      <Card>
        <div className="p-4 border-b border-gray-200 bg-gray-50">
          <h2 className="text-lg font-semibold text-gray-700">Booking Comparision</h2>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
          {/* Chart - Left */}
          <div className="flex items-center justify-center">
            <ResponsiveContainer width="100%" height={320}>
              <BarChart data={rows} margin={{ top: 10, right: 30, left: 0, bottom: 60 }}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="weekRange" angle={-45} textAnchor="end" height={80} />
                <YAxis />
                <Tooltip />
                <Legend />
                <Bar dataKey="myBookings" fill="#8884d8" name="My Bookings" />
                <Bar dataKey="maxBookings" fill="#82ca9d" name="Max Bookings" />
              </BarChart>
            </ResponsiveContainer>
          </div>

          {/* Table - Right */}
          <div>
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse text-sm">
                <thead>
                  <tr className="border-b border-gray-200">
                    <th className="px-4 py-3 font-semibold text-gray-700">Week</th>
                    <th className="px-4 py-3 font-semibold text-gray-700">My Bookings</th>
                    <th className="px-4 py-3 font-semibold text-gray-700">Max Bookings</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {rows.map((row: any, idx) => (
                    <tr key={idx} className="hover:bg-gray-50">
                      <td className="px-4 py-3 text-gray-800 font-medium text-xs">{row.weekRange}</td>
                      <td className="px-4 py-3 text-gray-700">{row.myBookings}</td>
                      <td className="px-4 py-3 text-gray-700">{row.maxBookings}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </Card>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <p className="text-sm text-gray-500">Total Bookings</p>
          <p className="text-2xl font-bold">{summary.total_bookings}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Completed</p>
          <p className="text-2xl font-bold text-green-600">{summary.completed}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Cancelled</p>
          <p className="text-2xl font-bold text-red-600">{summary.cancelled}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-gray-500">Revenue</p>
          <p className="text-2xl font-bold">${summary.revenue?.toLocaleString?.() ?? summary.revenue}</p>
        </Card>
      </div>

      <Card>
        <div className="p-4 border-b border-gray-200 bg-gray-50">
          <h2 className="text-lg font-semibold text-gray-700">Bookings Summary</h2>
        </div>

        <Table
          columns={[
            {
              key: 'weekRange',
              title: 'Date Range',
            },
            { key: 'myBookings', title: '# of pet served' },
            { key: 'completed', title: 'Completed Bookings' },
            { key: 'cancelled', title: 'Cancelled Bookings' },
            { key: 'pending', title: 'Active Bookings' },
          ]}
          data={rows}
          keyExtractor={(row: any) => row.startDate}
          isLoading={isLoading}
          emptyMessage="No booking data found"
        />
      </Card>
    </div>
  );
}
