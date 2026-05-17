import { useState, useEffect, useMemo } from 'react'
import { Card } from '../../components/ui/Card'
import { Table } from '../../components/ui/Table'
import { TablePagination } from '../../components/ui/TablePagination'
import { Input } from '../../components/ui/Input'
import { Check, X, Search as SearchIcon, MoreVertical, Eye, Edit3, Trash2, Inbox } from 'lucide-react'
import { PortalMenu } from '../../components/ui/PortalMenu'
import { PageHeader } from '../../components/layout/PageHeader'

interface Order {
  id: string
  date: string
  items: number
  total: number
  orderStatus: 'Order Locked' | 'Shipped' | 'Delivered'
  paymentStatus: 'Paid' | 'Pending' | 'Failed'
  editable: boolean
}

export function InwardGoodsPage() {
  const [searchTerm, setSearchTerm] = useState('')
  const [filterStatus, setFilterStatus] = useState('')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(25)
  const [sortColumn, setSortColumn] = useState<string | undefined>(undefined)
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc')
  const [openMenuId, setOpenMenuId] = useState<string | null>(null)
  const [menuPos, setMenuPos] = useState<{ top: number; left: number } | null>(null)

  // Mock data
  const allOrders: Order[] = [
    {
      id: 'BWO-2025-13328',
      date: 'Wednesday, 11th Jun 2025',
      items: 1,
      total: 33.90,
      orderStatus: 'Order Locked',
      paymentStatus: 'Paid',
      editable: false,
    },
    {
      id: 'BWO-2025-13245',
      date: 'Monday, 2nd Jun 2025',
      items: 3,
      total: 140.00,
      orderStatus: 'Shipped',
      paymentStatus: 'Paid',
      editable: false,
    },
    {
      id: 'BWO-2025-13110',
      date: 'Friday, 23rd May 2025',
      items: 5,
      total: 615.35,
      orderStatus: 'Order Locked',
      paymentStatus: 'Paid',
      editable: true,
    },
    {
      id: 'BWO-2025-13050',
      date: 'Wednesday, 21st May 2025',
      items: 2,
      total: 89.50,
      orderStatus: 'Delivered',
      paymentStatus: 'Paid',
      editable: false,
    },
    {
      id: 'BWO-2025-12999',
      date: 'Monday, 19th May 2025',
      items: 4,
      total: 250.75,
      orderStatus: 'Shipped',
      paymentStatus: 'Pending',
      editable: true,
    },
  ]

  // Filter and search
  const filteredOrders = useMemo(() => {
    return allOrders.filter((order) => {
      const matchesSearch =
        searchTerm === '' ||
        order.id.toLowerCase().includes(searchTerm.toLowerCase()) ||
        order.date.toLowerCase().includes(searchTerm.toLowerCase())

      const matchesStatus = filterStatus === '' || order.orderStatus === filterStatus

      return matchesSearch && matchesStatus
    })
  }, [searchTerm, filterStatus])

  // Pagination
  const totalPages = Math.ceil(filteredOrders.length / perPage)
  const paginatedOrders = filteredOrders.slice((page - 1) * perPage, page * perPage)

  // Reset page when search/filter changes
  useEffect(() => {
    setPage(1)
  }, [searchTerm, filterStatus])

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
        setOpenMenuId(null)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const handleSort = (column: string, direction: 'asc' | 'desc') => {
    setSortColumn(column)
    setSortDirection(direction)
  }

  // Table columns
  const columns = [
    {
      key: 'id',
      title: 'Order Number/Code',
      render: (order: Order) => <span className="font-medium text-gray-900">{order.id}</span>,
      sortable: true,
    },
    {
      key: 'date',
      title: 'Order Date',
      render: (order: Order) => <span className="text-gray-600">{order.date}</span>,
      sortable: true,
    },
    {
      key: 'items',
      title: '# of Items',
      render: (order: Order) => (
        <span className="text-center font-medium text-gray-700">{order.items}</span>
      ),
      sortable: true,
    },
    {
      key: 'total',
      title: 'Total Amount',
      render: (order: Order) => (
        <span className="text-right font-semibold text-gray-900">${order.total.toFixed(2)}</span>
      ),
      sortable: true,
    },
    {
      key: 'orderStatus',
      title: 'Order Status',
      render: (order: Order) => (
        <span
          className={`text-xs font-semibold ${
            order.orderStatus === 'Shipped'
              ? 'text-blue-600'
              : order.orderStatus === 'Delivered'
                ? 'text-green-600'
                : 'text-gray-700'
          }`}
        >
          {order.orderStatus}
        </span>
      ),
    },
    {
      key: 'paymentStatus',
      title: 'Payment Status',
      render: (order: Order) => (
        <span
          className={`text-xs font-semibold ${
            order.paymentStatus === 'Paid'
              ? 'text-green-600'
              : order.paymentStatus === 'Pending'
                ? 'text-yellow-600'
                : 'text-red-600'
          }`}
        >
          {order.paymentStatus}
        </span>
      ),
    },
    {
      key: 'editable',
      title: 'Editable?',
      render: (order: Order) => (
        <div className="flex justify-center">
          {order.editable ? (
            <Check className="w-5 h-5 text-green-500" strokeWidth={3} />
          ) : (
            <X className="w-5 h-5 text-gray-400" strokeWidth={3} />
          )}
        </div>
      ),
    },
    {
      key: 'actions',
      title: 'Mgmt',
      render: (order: Order) => (
        <div className="flex items-center justify-end relative">
          <button
            onClick={(e) => {
              e.stopPropagation()
              if (openMenuId === order.id) {
                setOpenMenuId(null)
                setMenuPos(null)
              } else {
                const rect = (e.currentTarget as HTMLButtonElement).getBoundingClientRect()
                setMenuPos({ top: rect.bottom + 4, left: rect.right - 192 })
                setOpenMenuId(order.id)
              }
            }}
            className="text-gray-400 hover:text-gray-600 p-1.5 focus:outline-none rounded-full hover:bg-gray-100 transition-colors"
          >
            <MoreVertical className="w-5 h-5" />
          </button>

          <PortalMenu
            isOpen={openMenuId === order.id}
            onClose={() => { setOpenMenuId(null); setMenuPos(null) }}
            position={menuPos}
            width={192}
          >
              <button
                onClick={() => {
                  // View action
                  setOpenMenuId(null)
                  setMenuPos(null)
                }}
                className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
              >
                <Eye className="w-4 h-4 text-gray-400" />
                View
              </button>
              {order.editable && (
                <button
                  onClick={() => {
                    // Edit action
                    setOpenMenuId(null)
                    setMenuPos(null)
                  }}
                  className="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                >
                  <Edit3 className="w-4 h-4 text-gray-400" />
                  Edit
                </button>
              )}
              {order.editable && (
                <button className="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors border-t border-gray-100 mt-1">
                  <Trash2 className="w-4 h-4 text-red-400" />
                  Cancel
                </button>
              )}
          </PortalMenu>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-4">
      {/* Header */}
      <PageHeader
        title="Inward Goods"
        icon={<Inbox className="w-5 h-5" />}
        variant="compact"
      />

      {/* Search and Filters */}
      <Card className="border border-gray-200 shadow-sm p-3 bg-white">
        <div className="flex flex-col sm:flex-row gap-4">
          <div className="flex-1 relative">
            <SearchIcon className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <Input
              type="text"
              placeholder="Search by order number or date..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="pl-10"
            />
          </div>
          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Status</option>
            <option value="Order Locked">Order Locked</option>
            <option value="Shipped">Shipped</option>
            <option value="Delivered">Delivered</option>
          </select>
        </div>
      </Card>

      {/* Table */}
      <Card className="border border-gray-200 shadow-sm overflow-hidden bg-white">
        <Table
          columns={columns}
          data={paginatedOrders}
          keyExtractor={(order) => order.id}
          sortColumn={sortColumn}
          sortDirection={sortDirection}
          onSort={handleSort}
          emptyMessage="No inward goods orders found"
        />
      </Card>

      {/* Pagination */}
      <TablePagination
        meta={{
          current_page: page,
          last_page: totalPages,
          per_page: perPage,
          total: filteredOrders.length,
        }}
        onPageChange={setPage}
        onPerPageChange={setPerPage}
      />
    </div>
  )
}
