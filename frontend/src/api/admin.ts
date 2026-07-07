import api from './axios'
import type { BeritaAcara, Payment, Sample, User } from '../types'

export const adminApi = {
  async getBeritaAcara(params?: Record<string, any>): Promise<BeritaAcara[]> {
    const { data } = await api.get<BeritaAcara[]>('/admin/berita-acara', { params })
    return data
  },

  async getBeritaAcaraDetail(id: number): Promise<BeritaAcara> {
    const { data } = await api.get<BeritaAcara>(`/admin/berita-acara/${id}`)
    return data
  },

  async updateStatus(id: number, payload: { status: string; note?: string }): Promise<BeritaAcara> {
    const { data } = await api.put(`/admin/berita-acara/${id}/status`, payload)
    return data.data || data
  },

  async assignAnalyst(id: number, analystIds: number[]): Promise<BeritaAcara> {
    const { data } = await api.put(`/admin/berita-acara/${id}/assign-analyst`, {
      analyst_ids: analystIds,
    })
    return data.data || data
  },

  async getPayments(params?: Record<string, any>): Promise<Payment[]> {
    const { data } = await api.get<Payment[]>('/admin/payments', { params })
    return data
  },

  async confirmPayment(id: number, payload: { status: string; discount_approved?: string }): Promise<Payment> {
    const { data } = await api.put(`/admin/payments/${id}/confirm`, payload)
    return data.data || data
  },

  async confirmDiscount(beritaAcaraId: number, payload: {
    status: 'approved' | 'rejected'
    discount_approved?: number
  }): Promise<any> {
    const { data } = await api.put(
      `/admin/berita-acara/${beritaAcaraId}/confirm-discount`,
      payload
    )
    return data.data || data
  },

  async getSamples(params?: Record<string, any>): Promise<Sample[]> {
    const { data } = await api.get<Sample[]>('/admin/samples', { params })
    return data
  },

  async markSampleTaken(id: number): Promise<Sample> {
    const { data } = await api.put(`/admin/samples/${id}/mark-taken`)
    return data.data || data
  },

  async getAnalysts(): Promise<User[]> {
    const { data } = await api.get<User[]>('/admin/analysts')
    return data
  },

  async getUsers(params?: { search?: string; role?: string; is_verified?: string }): Promise<User[]> {
    const { data } = await api.get<User[]>('/admin/users', { params })
    return data
  },

  async verifyUser(id: number, isVerified: boolean): Promise<{ message: string; data: User }> {
    const { data } = await api.put(`/admin/users/${id}/verify`, { is_verified: isVerified })
    return data
  },

  async deactivateUser(id: number): Promise<{ message: string; data: User }> {
    const { data } = await api.put(`/admin/users/${id}/deactivate`)
    return data
  },
}

