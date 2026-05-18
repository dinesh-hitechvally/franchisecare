import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { PortalMenu } from '../../components/ui/PortalMenu'
import { Button } from '../../components/ui/Button'
import { PageHeader } from '../../components/layout/PageHeader'
import { Check, X, MoreVertical, Edit3, Trash2, Plus, Loader2, Wallet } from 'lucide-react'
import { incomeCategoriesApi } from '../../api/services'
import type { IncomeCategory } from '../../types'

export function IncomeCategoriesPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)
  // const menuRef = useRef<HTMLDivElement>(null)

  const { data: categories = [], isLoading, isError, error } = useQuery({
    queryKey: ['income-categories'],
    queryFn: () => incomeCategoriesApi.getAll(),
  })

  const deleteMutation = useMutation({
    mutationFn: (id: string) => incomeCategoriesApi.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['income-categories'] })
      setOpenMenuId(null)
    },
  })

  return (
    <div className="space-y-6">
      <PageHeader
        title="Income Categories"
        icon={<Wallet className="w-5 h-5" />}
        actions={
          <Button
            onClick={() => navigate('/finance/income/add-category')}
            className="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors"
          >
            <Plus className="w-4 h-4" />
            Add Category
          </Button>
        }
      />

      {/* Error State */}
      {isError && (
        <Card className="p-6 bg-red-50 border border-red-200">
          <p className="text-red-700">
            Error loading categories: {error instanceof Error ? error.message : 'Unknown error'}
          </p>
        </Card>
      )}

      {/* Loading State */}
      {isLoading ? (
        <Card className="p-8 flex items-center justify-center">
          <Loader2 className="w-6 h-6 animate-spin text-gray-400" />
          <span className="ml-2 text-gray-600">Loading categories...</span>
        </Card>
      ) : (
        <Card className="border border-gray-200 shadow-sm overflow-hidden bg-white">
          {categories.length === 0 ? (
            <div className="p-8 text-center text-gray-500">
              <p>No income categories found. Create your first category to get started.</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left border-collapse">
                <thead className="bg-white border-b border-gray-200">
                  <tr>
                    <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Income Category</th>
                    <th className="px-6 py-4 font-bold text-gray-900 border-r border-gray-100">Description</th>
                    <th className="px-6 py-4 font-bold text-gray-900 text-center border-r border-gray-100">GST Inclusive</th>
                    <th className="px-6 py-4 font-bold text-gray-900 text-center border-r border-gray-100">Status</th>
                    <th className="px-6 py-4 font-bold text-gray-900 text-right">Mgmt</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100 bg-white">
                  {categories.map((cat: IncomeCategory) => (
                    <tr key={cat.id} className="hover:bg-gray-50 transition-colors">
                      <td className="px-6 py-4 text-gray-700 font-medium">{cat.name}</td>
                      <td className="px-6 py-4 text-gray-600">{cat.description || '-'}</td>
                      <td className="px-6 py-4">
                        <div className="flex justify-center">
                          {cat.gst_inclusive ? (
                            <Check className="w-5 h-5 text-green-500" strokeWidth={3} />
                          ) : (
                            <X className="w-5 h-5 text-gray-400" strokeWidth={3} />
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex justify-center">
                          {cat.is_active ? (
                            <Check className="w-5 h-5 text-green-500" strokeWidth={3} />
                          ) : (
                            <X className="w-5 h-5 text-gray-400" strokeWidth={3} />
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-4 flex justify-end">
                        {!cat.is_system && (
                          <>
                            <button
                              onClick={(e) => {
                                e.stopPropagation()
                                if (openMenuId === String(cat.id)) {
                                  setOpenMenuId(null); setMenuPos(null)
                                } else {
                                  const rect = (e.currentTarget as HTMLButtonElement).getBoundingClientRect()
                                  setMenuPos({ top: rect.bottom + 4, left: rect.right - 160 })
                                  setOpenMenuId(String(cat.id))
                                }
                              }}
                              className="text-gray-400 hover:text-gray-600 p-1.5 focus:outline-none rounded-full hover:bg-gray-100 transition-colors"
                            >
                              <MoreVertical className="w-5 h-5" />
                            </button>
                            <PortalMenu
                              isOpen={openMenuId === String(cat.id)}
                              onClose={() => { setOpenMenuId(null); setMenuPos(null) }}
                              position={menuPos}
                              width={160}
                            >
                                <button
                                  onClick={() => {
                                    navigate(`/finance/income/edit-category/${cat.id}`)
                                    setOpenMenuId(null)
                                  }}
                                  className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                >
                                  <Edit3 className="w-4 h-4 text-gray-400" />
                                  Edit
                                </button>
                                <button
                                  onClick={() => {
                                    deleteMutation.mutate(String(cat.id))
                                  }}
                                  disabled={deleteMutation.isPending}
                                  className="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors border-t border-gray-100 disabled:opacity-50"
                                >
                                  <Trash2 className="w-4 h-4 text-red-400" />
                                  {deleteMutation.isPending ? 'Deleting...' : 'Delete'}
                                </button>
                            </PortalMenu>
                          </>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </Card>
      )}
    </div>
  )
}
