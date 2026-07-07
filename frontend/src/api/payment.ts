import api from './axios'
import type { Payment } from '../types'

export const paymentApi = {
  async create(formData: FormData): Promise<Payment> {
    const { data } = await api.post('/user/payments', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data.data || data
  },

  async requestDiscount(id: number, payload: { discount_request: string; discount_reason?: string }): Promise<Payment> {
    const { data } = await api.post(`/user/payments/${id}/discount`, payload)
    return data.data || data
  },

  async getList(params?: Record<string, any>): Promise<Payment[]> {
    const { data } = await api.get<Payment[]>('/user/payments', { params })
    return data
  },

  async getById(id: number): Promise<Payment> {
    const { data } = await api.get<Payment>(`/user/payments/${id}`)
    return data
  },

  async uploadProof(id: number, formData: FormData): Promise<Payment> {
    const { data } = await api.post(`/user/payments/${id}/upload-proof`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data.data || data
  },
}
