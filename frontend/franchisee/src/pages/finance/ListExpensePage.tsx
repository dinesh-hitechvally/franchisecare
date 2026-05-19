import { useState, useRef, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { Card } from '../../components/ui/Card'
import { PortalMenu } from '../../components/ui/PortalMenu'
import { Button } from '../../components/ui/Button'
import { PageHeader } from '../../components/layout/PageHeader'
import { Search, Check, X, MoreVertical, Edit3, Trash2, Receipt } from 'lucide-react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { expensesApi } from '../../api/services'
import { Loader2 } from 'lucide-react'
import type { Expense } from '../../types'
import { formatDisplayDate } from '../../lib/timeFormatUtils'
import { SimpleAuditTrailModal } from '../../components/modals/SimpleAuditTrailModal'

export function ListExpensePage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const [searchInput, setSearchInput] = useState('')
  const [dateFrom, setDateFrom] = useState(() => {
    const d = new Date()
    d.setMonth(d.getMonth() - 6)
    return d.toISOString().split('T')[0]
  })
  const [dateTo, setDateTo] = useState(() => new Date().toISOString().split('T')[0])
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(25)
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)
  const [auditExpense, setAuditExpense] = useState<Expense | null>(null)

  const [appliedSearch, setAppliedSearch] = useState('')
  const [appliedDateFrom, setAppliedDateFrom] = useState(dateFrom)
  const [appliedDateTo, setAppliedDateTo] = useState(dateTo)

  const menuRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
        setOpenMenuId(null)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const { data: result, isLoading } = useQuery({
    queryKey: ['expenses', appliedSearch, appliedDateFrom, appliedDateTo, page, perPage],
    queryFn: () =>
      expensesApi.getPaginated({
        page,
        per_page: perPage,
        ...(appliedSearch ? { search: appliedSearch } : {}),
        ...(appliedDateFrom ? { date_from: appliedDateFrom } : {}),
        ...(appliedDateTo ? { date_to: appliedDateTo } : {}),
      }),
  })

  const deleteMutation = useMutation({
    mutationFn: (id: string) => expensesApi.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['expenses'] })
      setOpenMenuId(null)
    },
  })

  const expenses: Expense[] = (result as any)?.data ?? []
  const meta = (result as any)?.meta

  const handleFilter = () => {
    setAppliedSearch(searchInput)
    setAppliedDateFrom(dateFrom)
    setAppliedDateTo(dateTo)
    setPage(1)
  }

  const formatDate = (dateStr: string) => {
    try {
      return formatDisplayDate(dateStr)
    } catch {
      return dateStr
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader title="List Expenses" icon={<Receipt className="w-5 h-5" />} />

      <Card className="p-5 bg-white border border-gray-200 shadow-sm">
        <div className="flex flex-wrap items-end gap-4">
          <div className="flex flex-col gap-1 flex-1 min-w-[180px]">
            <label className="text-xs font-semibold text-gray-400 uppercase tracking-wide">Search</label>
            <div className="relative">
              <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                placeholder="Search expenses..."
                value={searchInput}
                onChange={(e) => setSearchInput(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                className="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
              />
            </div>
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-xs font-semibold text-gray-400 uppercase tracking-wide">From</label>
            <input
              type="date"
              value={dateFrom}
              onChange={(e) => setDateFrom(e.target.value)}
              className="bg-gray-50 border border-gray-200 rounded-md px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500 text-gray-700"
            />
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-xs font-semibold text-gray-400 uppercase tracking-wide">To</label>
            <input
              type="date"
              value={dateTo}
              onChange={(e) => setDateTo(e.target.value)}
              className="bg-gray-50 border border-gray-200 rounded-md px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500 text-gray-700"
            />
          </div>
          <div className="flex items-center gap-2 ml-auto">
            <Button onClick={handleFilter} className="bg-gray-900 text-white flex items-center gap-2 px-5 py-2 h-auto text-sm">
              <Search className="w-4 h-4" />
              List Expenses
            </Button>
            <Button
              variant="secondary"
              className="border-gray-900 text-gray-900 hover:bg-gray-900 hover:text-white px-5 text-sm py-2 h-auto"
              onClick={() => navigate('/finance/expense/add')}
            >
              + New Expense
            </Button>
          </div>
        </div>
      </Card>

      <Card className="border border-gray-200 shadow-sm overflow-hidden bg-white">
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left border-collapse">
            <thead className="bg-white border-b border-gray-100">
              <tr>
                <th className="px-6 py-4 font-bold text-gray-900">Title</th>
                <th className="px-6 py-4 font-bold text-gray-900">Description</th>
                <th className="px-6 py-4 font-bold text-gray-900 whitespace-nowrap">Category</th>
                <th className="px-6 py-4 font-bold text-gray-900 whitespace-nowrap">Date</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-right whitespace-nowrap">Amount</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center">Active?</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-center">Recurring?</th>
                <th className="px-6 py-4 font-bold text-gray-900 text-right">Mgmt</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50 bg-white">
              {isLoading ? (
                <tr>
                  <td colSpan={8} className="px-6 py-12 text-center text-gray-400">
                    <div className="flex justify-center items-center gap-2">
                      <Loader2 className="w-5 h-5 animate-spin" />
                      Loading...
                    </div>
                  </td>
                </tr>
              ) : expenses.length === 0 ? (
                <tr>
                  <td colSpan={8} className="px-6 py-12 text-center text-gray-400">No expense records found.</td>
                </tr>
              ) : (
                expenses.map((expense) => (
                  <tr key={expense.id} className="hover:bg-gray-50/50 transition-colors">
                    <td className="px-6 py-4 text-gray-800 font-medium min-w-[200px]">{expense.title}</td>
                    <td className="px-6 py-4 text-gray-500 min-w-[220px]">{expense.description || '—'}</td>
                    <td className="px-6 py-4 text-gray-600 whitespace-nowrap">{expense.category?.name ?? '—'}</td>
                    <td className="px-6 py-4 text-gray-600 whitespace-nowrap">{formatDate(expense.expense_date)}</td>
                    <td className="px-6 py-4 text-right text-gray-900 font-bold">${Number(expense.amount).toFixed(2)}</td>
                    <td className="px-6 py-4">
                      <div className="flex justify-center">
                        {expense.is_active ? (
                          <Check className="w-5 h-5 text-green-500" strokeWidth={3} />
                        ) : (
                          <X className="w-5 h-5 text-gray-300" strokeWidth={3} />
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex justify-center">
                        {Boolean((expense as any).recurring_expense_id) ? (
                          <Check className="w-5 h-5 text-blue-500" strokeWidth={3} />
                        ) : (
                          <X className="w-5 h-5 text-gray-300" strokeWidth={3} />
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-4 flex justify-end">
                      <button
                        onClick={(e) => {
                          e.stopPropagation()
                          if (openMenuId === expense.id) {
                            setOpenMenuId(null)
                            setMenuPos(null)
                          } else {
                            const rect = (e.currentTarget as HTMLButtonElement).getBoundingClientRect()
                            setMenuPos({ top: rect.bottom + 4, left: rect.right - 176 })
                            setOpenMenuId(expense.id)
                          }
                        }}
                        className="text-gray-400 hover:text-gray-600 p-1.5 focus:outline-none rounded-full hover:bg-gray-100 transition-colors"
                      >
                        <MoreVertical className="w-5 h-5" />
                      </button>
                      <PortalMenu
                        isOpen={openMenuId === expense.id}
                        onClose={() => {
                          setOpenMenuId(null)
                          setMenuPos(null)
                        }}
                        position={menuPos}
                        width={176}
                      >
                        <button
                          onClick={() => {
                            navigate(`/finance/expense/edit/${expense.id}`)
                            setOpenMenuId(null)
                          }}
                          className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                        >
                          <Edit3 className="w-4 h-4 text-gray-400" />
                          Edit
                        </button>
                        <button
                          onClick={() => {
                            setAuditExpense(expense)
                            setOpenMenuId(null)
                            setMenuPos(null)
                          }}
                          className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                        >
                          <Receipt className="w-4 h-4 text-gray-400" />
                          Audit History
                        </button>
                        <button
                          onClick={() => deleteMutation.mutate(expense.id)}
                          disabled={deleteMutation.isPending}
                          className="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors border-t border-gray-100 mt-1 disabled:opacity-50"
                        >
                          <Trash2 className="w-4 h-4 text-red-400" />
                          Delete
                        </button>
                      </PortalMenu>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {meta ? (
          <div className="px-6 py-4 bg-white border-t border-gray-100 flex items-center justify-end gap-6">
            <div className="flex items-center gap-2">
              <span className="text-sm text-gray-500">Rows per page:</span>
              <select
                value={perPage}
                onChange={(e) => {
                  setPerPage(Number(e.target.value))
                  setPage(1)
                }}
                className="bg-transparent border-none text-sm font-medium focus:ring-0 outline-none cursor-pointer"
              >
                <option value={25}>25</option>
                <option value={50}>50</option>
                <option value={100}>100</option>
              </select>
            </div>
            <span className="text-sm text-gray-600">
              {meta.total === 0 ? '0-0 of 0' : `${(meta.current_page - 1) * meta.per_page + 1}-${Math.min(meta.current_page * meta.per_page, meta.total)} of ${meta.total}`}
            </span>
            <div className="flex items-center gap-1">
              <button
                className="p-1 hover:bg-gray-100 rounded-full disabled:opacity-30"
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={meta.current_page <= 1}
              >
                <span className="text-lg leading-none">‹</span>
              </button>
              <button
                className="p-1 hover:bg-gray-100 rounded-full disabled:opacity-30"
                onClick={() => setPage((p) => Math.min(meta.last_page, p + 1))}
                disabled={meta.current_page >= meta.last_page}
              >
                <span className="text-lg leading-none">›</span>
              </button>
            </div>
          </div>
        ) : null}
      </Card>

      <SimpleAuditTrailModal
        isOpen={!!auditExpense}
        onClose={() => setAuditExpense(null)}
        entityId={auditExpense?.id}
        title="Expense Audit Trail"
        subtitle="Create, update and delete changes for this expense"
        queryKeyPrefix="expense-audits"
        fetchAudits={(id, pageNo) => expensesApi.getAudits(id, pageNo)}
      />
    </div>
  )
}
