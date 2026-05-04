import { useState } from 'react'
import { Printer, Download, Search, TrendingDown, TrendingUp, DollarSign } from 'lucide-react'

interface PLCategory {
  name: string
  values: { [key: string]: number | null }
  total: number
}

interface PLSection {
  title: string
  categories: PLCategory[]
  total: number
}

export function ProfitLossPage() {
  const [fromDate, setFromDate] = useState('2026-03-05')
  const [toDate, setToDate] = useState('2026-04-02')

  const salesSection: PLSection = {
    title: 'Sales',
    categories: [
      { name: 'Booking Income', values: { 'Mar 2026': 659.09, 'Apr 2026': null }, total: 659.09 },
      { name: 'Test', values: { 'Mar 2026': null, 'Apr 2026': null }, total: 0 },
      { name: 'Test Category', values: { 'Mar 2026': null, 'Apr 2026': null }, total: 0 },
    ],
    total: 659.09
  }

  const expenseSections: PLSection[] = [
    {
      title: 'Administration',
      categories: [
        { name: 'Franchise Fees', values: { 'Mar 2026': null, 'Apr 2026': null }, total: 0 },
        { name: 'Mate Fees', values: { 'Mar 2026': null, 'Apr 2026': null }, total: 0 },
        { name: 'Internet', values: { 'Mar 2026': null, 'Apr 2026': null }, total: 0 },
        { name: 'Accountancy', values: { 'Mar 2026': null, 'Apr 2026': null }, total: 0 },
      ],
      total: 0
    },
    {
      title: 'Motor Vehicle',
      categories: [
        { name: 'Fuel', values: { 'Mar 2026': null, 'Apr 2026': null }, total: 0 },
        { name: 'Registration', values: { 'Mar 2026': null, 'Apr 2026': null }, total: 0 },
        { name: 'Maintenance', values: { 'Mar 2026': null, 'Apr 2026': null }, total: 0 },
      ],
      total: 0
    },
    {
      title: 'Trailer/Mobile Salon',
      categories: [
        { name: 'Equipment', values: { 'Mar 2026': null, 'Apr 2026': null }, total: 0 },
        { name: 'Shampoos/Cologne', values: { 'Mar 2026': null, 'Apr 2026': null }, total: 0 },
      ],
      total: 0
    }
  ]

  const totalExpenses = expenseSections.reduce((acc, sec) => acc + sec.total, 0)
  const netProfit = salesSection.total - totalExpenses

  return (
    <div className="flex flex-col gap-6 p-6 min-h-screen bg-[#f4f6f8]">
      {/* Page Title Card */}
      <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-100 mb-2">
        <h1 className="text-xl font-bold text-gray-800 flex items-center gap-2">
          <TrendingUp size={24} className="text-green-600" />
          Profit/Loss Statement
        </h1>
      </div>

      {/* Filter Card */}
      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-2">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">From</label>
            <input
              type="date"
              value={fromDate}
              onChange={(e) => setFromDate(e.target.value)}
              className="w-full text-sm p-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            />
          </div>
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-gray-400 uppercase tracking-widest">To</label>
            <input
              type="date"
              value={toDate}
              onChange={(e) => setToDate(e.target.value)}
              className="w-full text-sm p-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            />
          </div>
          <button className="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-all shadow-md font-bold text-sm">
            <Search size={18} />
            Generate Statement
          </button>
        </div>
      </div>

      {/* Report Container */}
      <div className="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden print:shadow-none print:border-none">
        {/* Statement Header */}
        <div className="p-8 text-center border-b border-gray-100 bg-gray-50/30">
          <div className="flex justify-end gap-2 mb-6 print:hidden">
            <button className="px-3 py-1 bg-white border border-gray-200 rounded text-xs font-medium hover:bg-gray-50 flex items-center gap-2">
              <Download size={14} /> Export CSV
            </button>
            <button className="px-3 py-1 bg-white border border-gray-200 rounded text-xs font-medium hover:bg-gray-50 flex items-center gap-2">
              <Printer size={14} /> Print Preview
            </button>
          </div>
          
          <h2 className="text-2xl font-bold text-gray-900">RetailCare Pty Ltd</h2>
          <p className="text-xs text-gray-500 mt-1">240 Chapel St, 240 Chapel St, Prahran, Victoria, 3141</p>
          <p className="text-xs text-gray-500">Phone: 0430831238 | Mobile: 0430831237</p>
          <p className="text-xs text-blue-600 mb-4 cursor-pointer hover:underline">admin@retailcare.com.au</p>
          <p className="text-xs font-bold text-gray-600 uppercase tracking-widest">ABN: 123457</p>
          <div className="mt-8">
            <h3 className="text-lg font-bold text-gray-800 uppercase tracking-[0.2em]">Income/Expense Statement</h3>
            <p className="text-xs font-medium text-gray-400 mt-1 italic">5th Mar, 2026 - 2nd Apr, 2026</p>
          </div>
        </div>

        {/* Statement Table */}
        <div className="overflow-x-auto">
          <table className="w-full border-collapse">
            <thead>
              <tr className="bg-gray-100/80">
                <th className="p-2 text-left text-[11px] font-bold text-gray-600 uppercase tracking-wider w-[40%]"></th>
                <th className="p-2 text-right text-[11px] font-bold text-gray-600 uppercase tracking-wider">Mar 2026</th>
                <th className="p-2 text-right text-[11px] font-bold text-gray-600 uppercase tracking-wider">Apr 2026</th>
                <th className="p-2 text-right text-[11px] font-bold text-gray-600 uppercase tracking-wider">Total</th>
              </tr>
            </thead>
            
            {/* Sales Section */}
            <tbody>
              <tr className="bg-gray-50">
                <td colSpan={4} className="px-4 py-2 text-[12px] font-bold text-gray-700 uppercase tracking-widest border-b border-gray-200">{salesSection.title}</td>
              </tr>
              {salesSection.categories.map((cat, idx) => (
                <tr key={idx} className="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                  <td className="px-6 py-1.5 text-xs text-gray-600">{cat.name}</td>
                  <td className="px-2 py-1.5 text-xs text-gray-900 border-l border-gray-100 text-right">{cat.values['Mar 2026'] ? `$${cat.values['Mar 2026'].toFixed(2)}` : '-'}</td>
                  <td className="px-2 py-1.5 text-xs text-gray-900 border-l border-gray-100 text-right">{cat.values['Apr 2026'] ? `$${cat.values['Apr 2026'].toFixed(2)}` : '-'}</td>
                  <td className="px-2 py-1.5 text-xs text-gray-900 border-l border-gray-100 text-right font-semibold">{cat.total > 0 ? `$${cat.total.toFixed(2)}` : '-'}</td>
                </tr>
              ))}
              <tr className="bg-gray-50/50 font-bold border-t-2 border-gray-200">
                <td className="px-4 py-2 text-xs text-gray-800 uppercase tracking-wider text-left">Total Sales</td>
                <td className="px-2 py-2 text-xs text-gray-900 border-l border-gray-100 text-right">$659.09</td>
                <td className="px-2 py-2 text-xs text-gray-900 border-l border-gray-100 text-right">-</td>
                <td className="px-2 py-2 text-xs text-gray-900 border-l border-gray-100 text-right font-black">$659.09</td>
              </tr>
            </tbody>

            {/* Expenses Section */}
            <tbody>
              <tr className="bg-gray-50 border-t-4 border-white">
                <td colSpan={4} className="px-4 py-2 text-[12px] font-bold text-gray-700 uppercase tracking-widest border-b border-gray-200">Expenses</td>
              </tr>
              {expenseSections.map((section, sidx) => (
                <React.Fragment key={sidx}>
                  <tr className="bg-white">
                    <td colSpan={4} className="px-6 py-2 text-[11px] font-bold text-gray-500 uppercase tracking-wider">{section.title}</td>
                  </tr>
                  {section.categories.map((cat, cidx) => (
                    <tr key={cidx} className="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                      <td className="px-8 py-1 text-xs text-gray-600">{cat.name}</td>
                      <td className="px-2 py-1 text-xs text-gray-400 border-l border-gray-100 text-right">-</td>
                      <td className="px-2 py-1 text-xs text-gray-400 border-l border-gray-100 text-right">-</td>
                      <td className="px-2 py-1 text-xs text-gray-400 border-l border-gray-100 text-right">-</td>
                    </tr>
                  ))}
                </React.Fragment>
              ))}
              <tr className="bg-gray-50 font-bold border-t-2 border-gray-200">
                <td className="px-4 py-3 text-sm text-gray-800 uppercase tracking-wider text-left">Total Expenses</td>
                <td className="px-2 py-3 text-sm text-gray-400 border-l border-gray-100 text-right">-</td>
                <td className="px-2 py-3 text-sm text-gray-400 border-l border-gray-100 text-right">-</td>
                <td className="px-2 py-3 text-sm text-gray-400 border-l border-gray-100 text-right">-</td>
              </tr>
            </tbody>

            {/* Bottom Summary */}
            <tfoot>
              <tr className="bg-gray-900 text-white font-black">
                <td className="px-4 py-4 text-sm uppercase tracking-[0.1em] text-left">Profit / Loss</td>
                <td className="px-2 py-4 text-sm border-l border-gray-800 text-right">$659.09</td>
                <td className="px-2 py-4 text-sm border-l border-gray-800 text-right">-</td>
                <td className="px-2 py-4 text-sm border-l border-gray-800 text-right">$659.09</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  )
}

import React from 'react'
