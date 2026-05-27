import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { PortalMenu } from '../../components/ui/PortalMenu'
import { Card } from '../../components/ui/Card'
import { PageHeader } from '../../components/layout/PageHeader'
import { Check, X, MoreVertical, Wallet, Loader2 } from 'lucide-react'
import { expenseCategoriesApi } from '../../api/services'
import type { ExpenseCategory } from '../../types'

export function ExpenseCategoriesPage() {
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)

  const { data: categories = [], isLoading } = useQuery({
    queryKey: ['expense-categories'],
    queryFn: () => expenseCategoriesApi.getAll(),
  })

  // Group categories by type (using description or a default)
  const groupedCategories = categories.reduce((acc, cat) => {
    const type = (cat as any).type || 'General'
    if (!acc[type]) acc[type] = []
    acc[type].push(cat)
    return acc
  }, {} as Record<string, ExpenseCategory[]>)

  const expenseTypes = Object.entries(groupedCategories).map(([type, cats]) => ({
    type,
    categories: cats.map(cat => ({
      id: cat.id,
      name: cat.name,
      description: cat.description || '',
      entries: (cat as any).entries_count || 0,
      gst: cat.gst_inclusive ?? false,
      status: cat.is_active ?? true,
    }))
  }))

  return (
    <div className="space-y-6">
      <PageHeader
        title="Expense Categories"
        icon={<Wallet className="w-5 h-5" />}
      />

      {isLoading ? (
        <Card className="border border-gray-200 shadow-sm bg-white p-8">
          <div className="flex items-center justify-center gap-2 text-gray-500">
            <Loader2 className="w-5 h-5 animate-spin" />
            <span>Loading categories...</span>
          </div>
        </Card>
      ) : (
      <Card className="border border-gray-200 shadow-sm overflow-hidden bg-white">
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left border-collapse">
            <thead className="bg-white border-b border-gray-200">
              <tr>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100 uppercase text-xs tracking-wider">Expense Type</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100 uppercase text-xs tracking-wider">Expense Category</th>
                <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100 uppercase text-xs tracking-wider">Description</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center border-r border-gray-100 uppercase text-xs tracking-wider"># of Entries</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center border-r border-gray-100 uppercase text-xs tracking-wider">Gst Inclusive</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center border-r border-gray-100 uppercase text-xs tracking-wider">Status</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center uppercase text-xs tracking-wider">Mgmt</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100 bg-white">
              {expenseTypes.length === 0 ? (
                <tr>
                  <td colSpan={7} className="px-6 py-8 text-center text-gray-500">
                    No expense categories found
                  </td>
                </tr>
              ) : (
              expenseTypes.map((typeGroup) => (
                typeGroup.categories.map((cat, index) => (
                  <tr key={`${typeGroup.type}-${cat.name}`} className="hover:bg-gray-50/50 transition-colors">
                    {index === 0 && (
                      <td 
                        rowSpan={typeGroup.categories.length} 
                        className="px-6 py-4 text-gray-900 font-bold border-r border-gray-100 bg-gray-50/30 align-top"
                      >
                        {typeGroup.type}
                      </td>
                    )}
                    <td className="px-6 py-4 text-gray-700 font-medium border-r border-gray-100">{cat.name}</td>
                    <td className="px-6 py-4 text-gray-600 border-r border-gray-100">{cat.description}</td>
                    <td className="px-6 py-4 text-center text-gray-700 font-medium border-r border-gray-100">{cat.entries}</td>
                    <td className="px-6 py-4 border-r border-gray-100">
                      <div className="flex justify-center">
                        {cat.gst ? (
                          <Check className="w-5 h-5 text-green-500" strokeWidth={3} />
                        ) : (
                          <X className="w-5 h-5 text-gray-400" strokeWidth={3} />
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex justify-center">
                        <Check className="w-5 h-5 text-green-500" strokeWidth={3} />
                      </div>
                    </td>
                    <td className="px-6 py-4 text-center">
                      <button
                        type="button"
                        onClick={(e) => {
                          const rowId = `${typeGroup.type}-${cat.name}`
                          if (openMenuId === rowId) {
                            setOpenMenuId(null); setMenuPos(null)
                          } else {
                            const rect = (e.currentTarget as HTMLButtonElement).getBoundingClientRect()
                            setMenuPos({ top: rect.bottom + 4, left: rect.right - 144 })
                            setOpenMenuId(rowId)
                          }
                        }}
                        className="p-1 rounded hover:bg-gray-100"
                      >
                        <MoreVertical className="w-5 h-5 text-gray-600" />
                      </button>

                      <PortalMenu
                        isOpen={openMenuId === `${typeGroup.type}-${cat.name}`}
                        onClose={() => { setOpenMenuId(null); setMenuPos(null) }}
                        position={menuPos}
                        width={144}
                      >
                          <button
                            type="button"
                            className="w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                          >
                            Edit
                          </button>
                          <button
                            type="button"
                            className="w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                          >
                            View Entries
                          </button>
                          <button
                            type="button"
                            className="w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                          >
                            {cat.status ? 'Deactivate' : 'Activate'}
                          </button>
                      </PortalMenu>
                    </td>
                  </tr>
                ))
              ))
              )}
            </tbody>
          </table>
        </div>
      </Card>
      )}
    </div>
  )
}
