import { Card } from '../../components/ui/Card'
import { Calculator, TrendingUp, TrendingDown, DollarSign } from 'lucide-react'

export function AccountingPage() {
  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-secondary-900">Accounting</h1>
      
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <div className="flex items-center gap-3">
            <div className="p-3 bg-green-100 rounded-lg">
              <DollarSign className="w-6 h-6 text-green-600" />
            </div>
            <div>
              <p className="text-sm text-secondary-500">Total Revenue</p>
              <p className="text-2xl font-bold text-secondary-900">$45,230</p>
            </div>
          </div>
        </Card>
        
        <Card className="p-4">
          <div className="flex items-center gap-3">
            <div className="p-3 bg-red-100 rounded-lg">
              <TrendingDown className="w-6 h-6 text-red-600" />
            </div>
            <div>
              <p className="text-sm text-secondary-500">Total Expenses</p>
              <p className="text-2xl font-bold text-secondary-900">$12,450</p>
            </div>
          </div>
        </Card>
        
        <Card className="p-4">
          <div className="flex items-center gap-3">
            <div className="p-3 bg-blue-100 rounded-lg">
              <Calculator className="w-6 h-6 text-blue-600" />
            </div>
            <div>
              <p className="text-sm text-secondary-500">Net Profit</p>
              <p className="text-2xl font-bold text-secondary-900">$32,780</p>
            </div>
          </div>
        </Card>
        
        <Card className="p-4">
          <div className="flex items-center gap-3">
            <div className="p-3 bg-purple-100 rounded-lg">
              <TrendingUp className="w-6 h-6 text-purple-600" />
            </div>
            <div>
              <p className="text-sm text-secondary-500">Growth</p>
              <p className="text-2xl font-bold text-secondary-900">+12.5%</p>
            </div>
          </div>
        </Card>
      </div>
      
      <Card title="Recent Transactions" className="p-6">
        <div className="space-y-3">
          {[
            { id: 1, desc: 'Service Revenue - Full Groom', amount: 450, type: 'income', date: '2024-01-15' },
            { id: 2, desc: 'Supplier Payment - Shampoo Stock', amount: -230, type: 'expense', date: '2024-01-14' },
            { id: 3, desc: 'Service Revenue - Bath & Dry', amount: 180, type: 'income', date: '2024-01-14' },
            { id: 4, desc: 'Utility Bill - Electricity', amount: -145, type: 'expense', date: '2024-01-13' },
          ].map((tx) => (
            <div key={tx.id} className="flex items-center justify-between py-2 border-b last:border-0">
              <div>
                <p className="font-medium">{tx.desc}</p>
                <p className="text-sm text-secondary-500">{tx.date}</p>
              </div>
              <p className={`font-bold ${tx.amount > 0 ? 'text-green-600' : 'text-red-600'}`}>
                {tx.amount > 0 ? '+' : ''}${Math.abs(tx.amount)}
              </p>
            </div>
          ))}
        </div>
      </Card>
    </div>
  )
}
