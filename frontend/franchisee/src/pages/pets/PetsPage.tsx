import { useState, useRef } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import type { Pet, Customer } from '../../types'
import { Plus, Search, Eye, PenLine } from 'lucide-react'

export function PetsPage() {
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [isSignatureModalOpen, setIsSignatureModalOpen] = useState(false)
  const [selectedPet, setSelectedPet] = useState<Pet | null>(null)
  const [searchTerm, setSearchTerm] = useState('')
  const canvasRef = useRef<HTMLCanvasElement>(null)

  const { data: pets, isLoading } = useQuery({
    queryKey: ['pets'],
    queryFn: async () => {
      // Mock data
      return [
        { id: '1', name: 'Max', breed: 'Golden Retriever', size: 'large', allergies: 'None', notes: 'Friendly dog', ownerId: '1', createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() },
        { id: '2', name: 'Bella', breed: 'Labrador', size: 'large', allergies: 'Chicken', notes: '', ownerId: '2', createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() },
        { id: '3', name: 'Charlie', breed: 'Poodle', size: 'small', allergies: 'None', notes: 'Nervous around strangers', ownerId: '3', createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() },
      ] as Pet[]
    },
  })

  const { data: customers } = useQuery({
    queryKey: ['customers-for-pets'],
    queryFn: async () => {
      return [
        { id: '1', first_name: 'Alice', last_name: 'Johnson' },
        { id: '2', first_name: 'Bob', last_name: 'Smith' },
        { id: '3', first_name: 'Carol', last_name: 'White' },
      ] as Customer[]
    },
  })

  const filteredPets = pets?.filter((pet) =>
    searchTerm === '' ||
    pet.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    pet.breed.toLowerCase().includes(searchTerm.toLowerCase())
  )

  const handleSignature = (pet: Pet) => {
    setSelectedPet(pet)
    setIsSignatureModalOpen(true)
  }

  const clearSignature = () => {
    const canvas = canvasRef.current
    if (canvas) {
      const ctx = canvas.getContext('2d')
      ctx?.clearRect(0, 0, canvas.width, canvas.height)
    }
  }

  const saveSignature = () => {
    const canvas = canvasRef.current
    if (canvas) {
      const signature = canvas.toDataURL('image/png')
      setIsSignatureModalOpen(false)
      clearSignature()
    }
  }

  const sizeLabels = {
    small: 'Small',
    medium: 'Medium',
    large: 'Large',
    extra_large: 'Extra Large',
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-4">
        <Card className="px-4 py-3 shadow-sm border-gray-200 flex-1">
          <h1 className="text-xl font-bold text-gray-800">Pets</h1>
        </Card>
        <Button onClick={() => setIsModalOpen(true)} size="sm" className="flex-shrink-0">
          <Plus className="w-4 h-4 mr-2" />
          Add Pet
        </Button>
      </div>

      <Card>
        <div className="flex items-center gap-4 mb-6">
          <div className="relative flex-1 max-w-md">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
            <input
              type="text"
              placeholder="Search pets..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="input pl-10"
            />
          </div>
        </div>

        <Table
          columns={[
            { key: 'name', title: 'Name' },
            { key: 'breed', title: 'Breed' },
            { key: 'size', title: 'Size', render: (row: Pet) => sizeLabels[row.size] },
            { key: 'allergies', title: 'Allergies' },
            { key: 'owner', title: 'Owner', render: (row: Pet) => {
              const owner = customers?.find(c => c.id === row.ownerId)
              return owner ? `${owner.first_name} ${owner.last_name}` : 'Unknown'
            }},
            {
              key: 'actions',
              title: 'Actions',
              render: (row: Pet) => (
                <div className="flex gap-2">
                  <Button variant="ghost" size="sm">
                    <Eye className="w-4 h-4" />
                  </Button>
                  <Button variant="ghost" size="sm" onClick={() => handleSignature(row)}>
                    <PenLine className="w-4 h-4" />
                  </Button>
                </div>
              ),
            },
          ]}
          data={filteredPets ?? []}
          keyExtractor={(row) => row.id}
          isLoading={isLoading}
        />
      </Card>

      {/* Add Pet Modal */}
      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title="Add New Pet"
        footer={
          <>
            <Button variant="secondary" onClick={() => setIsModalOpen(false)}>
              Cancel
            </Button>
            <Button onClick={() => setIsModalOpen(false)}>Create Pet</Button>
          </>
        }
      >
        <div className="space-y-4">
          <Input label="Pet Name" />
          <Input label="Breed" />
          <div>
            <label className="text-sm font-medium text-secondary-700">Size</label>
            <select className="input mt-1">
              <option value="small">Small</option>
              <option value="medium">Medium</option>
              <option value="large">Large</option>
              <option value="extra_large">Extra Large</option>
            </select>
          </div>
          <div>
            <label className="text-sm font-medium text-secondary-700">Owner</label>
            <select className="input mt-1">
              {customers?.map((c) => (
                <option key={c.id} value={c.id}>{c.first_name} {c.last_name}</option>
              ))}
            </select>
          </div>
          <Input label="Allergies" />
          <div>
            <label className="text-sm font-medium text-secondary-700">Notes</label>
            <textarea className="input mt-1" rows={3} />
          </div>
        </div>
      </Modal>

      {/* Signature Modal */}
      <Modal
        isOpen={isSignatureModalOpen}
        onClose={() => {
          setIsSignatureModalOpen(false)
          clearSignature()
        }}
        title={`Capture Signature - ${selectedPet?.name}`}
        footer={
          <>
            <Button variant="secondary" onClick={clearSignature}>
              Clear
            </Button>
            <Button variant="secondary" onClick={() => setIsSignatureModalOpen(false)}>
              Cancel
            </Button>
            <Button onClick={saveSignature}>Save Signature</Button>
          </>
        }
      >
        <div className="space-y-4">
          <p className="text-sm text-secondary-600">
            Please sign below using your mouse or touch screen.
          </p>
          <div className="border-2 border-dashed border-secondary-300 rounded-lg overflow-hidden">
            <canvas
              ref={canvasRef}
              width={500}
              height={200}
              className="w-full cursor-crosshair bg-white"
              onMouseDown={(e) => {
                const canvas = e.currentTarget
                const ctx = canvas.getContext('2d')
                if (ctx) {
                  ctx.beginPath()
                  ctx.moveTo(e.nativeEvent.offsetX, e.nativeEvent.offsetY)
                  ctx.strokeStyle = '#000'
                  ctx.lineWidth = 2
                  
                  const handleMove = (e: MouseEvent) => {
                    ctx.lineTo(e.offsetX, e.offsetY)
                    ctx.stroke()
                  }
                  
                  const handleUp = () => {
                    canvas.removeEventListener('mousemove', handleMove)
                    canvas.removeEventListener('mouseup', handleUp)
                  }
                  
                  canvas.addEventListener('mousemove', handleMove)
                  canvas.addEventListener('mouseup', handleUp)
                }
              }}
            />
          </div>
        </div>
      </Modal>
    </div>
  )
}
