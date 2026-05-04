import { Card } from '../../components/ui/Card'

export function HomePage() {
  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-secondary-900">Home</h1>
      <Card className="p-6">
        <p className="text-secondary-600">Welcome to the Home page.</p>
      </Card>
    </div>
  )
}
