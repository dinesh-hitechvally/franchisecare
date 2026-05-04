import { useState } from 'react'
import { Printer, Download, Search, FileText, Landmark, Quote } from 'lucide-react'

export function GSTSummaryPage() {
  const [year, setYear] = useState('2026')
  const [quarter, setQuarter] = useState('Q3 (Jan-Mar)')

  const gstCollected = [
    { category: 'Booking Income', amount: 6590.90, gst: 659.09 },
    { category: 'Product Sales', amount: 1250.00, gst: 125.00 },
    { category: 'Other Income', amount: 0, gst: 0 },
  ]

  const gstPaid = [
    { category: 'Inventory/Supplies', amount: 450.00, gst: 45.00 },
    { category: 'Motor Vehicle Expenses', amount: 800.00, gst: 80.00 },
    { category: 'Marketing', amount: 200.00, gst: 20.00 },
    { category: 'Rent/Utilities', amount: 1200.00, gst: 120.00 },
  ]

  const totalCollected = gstCollected.reduce((acc, curr) => acc + curr.gst, 0)
  const totalPaid = gstPaid.reduce((acc, curr) => acc + curr.gst, 0)
  const netGST = totalCollected - totalPaid

  return (
    <div className="flex flex-col gap-6 p-6 min-h-screen bg-[#f4f6f8]">
      {/* Page Title Card */}
      <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-100 flex items-center justify-between">
        <h1 className="text-xl font-bold text-gray-800 flex items-center gap-2">
          <Landmark size={24} className="text-purple-600" />
          GST Summary Report
        </h1>
        <div className="flex gap-2">
          <button className="flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors text-xs font-medium">
            <Download size={14} /> Export CSV
          </button>
          <button className="flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors text-xs font-medium">
            <Printer size={14} /> Print
          </button>
        </div>
      </div>

      {/* Filter Card */}
      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">Financial Year</label>
            <select 
              value={year}
              onChange={(e) => setYear(e.target.value)}
              className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-lg"
            >
              <option value="2026">2026-2027</option>
              <option value="2025">2025-2026</option>
            </select>
          </div>
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">Period</label>
            <select 
              value={quarter}
              onChange={(e) => setQuarter(e.target.value)}
              className="w-full text-sm p-2 bg-gray-50 border border-gray-200 rounded-lg"
            >
              <option>Q1 (Jul-Sep)</option>
              <option>Q2 (Oct-Dec)</option>
              <option>Q3 (Jan-Mar)</option>
              <option>Q4 (Apr-Jun)</option>
            </select>
          </div>
          <button className="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-all shadow-md font-bold text-xs uppercase tracking-widest">
            <Search size={16} />
            Generate GST Summary
          </button>
        </div>
      </div>

      {/* Main Report Table Content */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="p-8 text-center border-b border-gray-100 bg-gray-50/20">
          <h2 className="text-2xl font-bold text-gray-900">RetailCare Pty Ltd</h2>
          <p className="text-xs text-gray-500 mt-1 uppercase tracking-widest font-bold">GST Summary (BAS Calculation)</p>
          <p className="text-xs font-medium text-gray-400 mt-1 italic">Period: {quarter} {year}</p>
        </div>

        <div className="p-8 space-y-12">
          {/* GST Collected (Output) */}
          <div className="space-y-4">
            <h3 className="text-sm font-bold text-primary-700 uppercase tracking-widest flex items-center gap-2">
              <span className="w-2 h-2 bg-primary-500 rounded-full"></span>
              GST Collected (Sales)
            </h3>
            <table className="w-full border-collapse">
              <thead>
                <tr className="border-b-2 border-gray-100">
                  <th className="p-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest">Component</th>
                  <th className="p-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest">Base Amount</th>
                  <th className="p-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest">GST Amount</th>
                </tr>
              </thead>
              <tbody>
                {gstCollected.map((item, idx) => (
                  <tr key={idx} className="border-b border-gray-50">
                    <td className="p-3 text-xs text-gray-600 font-medium">{item.category}</td>
                    <td className="p-3 text-xs text-right text-gray-500">${item.amount.toLocaleString()}</td>
                    <td className="p-3 text-xs text-right text-gray-900 font-bold">${item.gst.toLocaleString()}</td>
                  </tr>
                ))}
              </tbody>
              <tfoot className="bg-gray-50">
                <tr>
                  <td colSpan={2} className="p-3 text-xs font-bold text-gray-600 uppercase tracking-wider">Total GST Collected</td>
                  <td className="p-3 text-xs text-right text-primary-900 font-black">${totalCollected.toLocaleString()}</td>
                </tr>
              </tfoot>
            </table>
          </div>

          {/* GST Paid (Input Tax) */}
          <div className="space-y-4">
            <h3 className="text-sm font-bold text-error-700 uppercase tracking-widest flex items-center gap-2">
              <span className="w-2 h-2 bg-error-500 rounded-full"></span>
              GST Paid (Expenses & Assets)
            </h3>
            <table className="w-full border-collapse">
              <thead>
                <tr className="border-b-2 border-gray-100">
                  <th className="p-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest">Component</th>
                  <th className="p-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest">Base Amount</th>
                  <th className="p-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest">GST Amount</th>
                </tr>
              </thead>
              <tbody>
                {gstPaid.map((item, idx) => (
                  <tr key={idx} className="border-b border-gray-50">
                    <td className="p-3 text-xs text-gray-600 font-medium">{item.category}</td>
                    <td className="p-3 text-xs text-right text-gray-500">${item.amount.toLocaleString()}</td>
                    <td className="p-3 text-xs text-right text-gray-900 font-bold">${item.gst.toLocaleString()}</td>
                  </tr>
                ))}
              </tbody>
              <tfoot className="bg-gray-50">
                <tr>
                  <td colSpan={2} className="p-3 text-xs font-bold text-gray-600 uppercase tracking-wider">Total GST Paid</td>
                  <td className="p-3 text-xs text-right text-error-900 font-black">${totalPaid.toLocaleString()}</td>
                </tr>
              </tfoot>
            </table>
          </div>

          {/* Net Position (Summary) */}
          <div className="bg-primary-900 rounded-xl p-8 text-white">
            <div className="flex flex-col md:flex-row items-center justify-between gap-8">
              <div className="space-y-2">
                <h4 className="text-xs font-bold text-primary-400 uppercase tracking-widest">Net GST Position</h4>
                <p className="text-xs text-primary-200 font-medium italic">Amount {netGST >= 0 ? 'Payable to' : 'Refundable from'} the Australian Taxation Office (ATO)</p>
              </div>
              <div className="flex gap-12 items-center">
                <div className="text-center">
                  <span className="block text-[10px] text-primary-300 uppercase font-black tracking-widest mb-1">Total Due</span>
                  <span className="text-3xl font-black">${Math.abs(netGST).toLocaleString()}</span>
                </div>
                <div className={`p-4 rounded-xl ${netGST >= 0 ? 'bg-error-500 text-white' : 'bg-green-500 text-white'}`}>
                  {netGST >= 0 ? <Landmark size={32} /> : <Quote size={32} />}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
