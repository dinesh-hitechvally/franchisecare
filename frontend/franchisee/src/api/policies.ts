import { apiClient } from './client'

export interface CancellationPolicyRecord {
  id: number
  name: string
  description: string
  created_at: string
  updated_at: string
}

export const policiesApi = {
  getAll: () => apiClient.get<CancellationPolicyRecord[]>('/cancellation-policies'),
}
