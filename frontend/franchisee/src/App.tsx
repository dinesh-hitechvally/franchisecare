import { AppRoutes } from './routes'
import { Toaster } from 'react-hot-toast'

function App() {
  return (
    <>
      <AppRoutes />
      <Toaster position="top-right" />
    </>
  )
}

export default App
