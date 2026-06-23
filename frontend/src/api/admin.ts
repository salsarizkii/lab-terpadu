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

  async updateStatus(id: number, data: { status: string; note?: string }): Promise<BeritaAcara> {
    const { data: res } = await api.put<BeritaAcara>(`/admin/berita-acara/${id}/status`, data)
    return res
  },

  async assignAnalyst(id: number, analystId: number): Promise<BeritaAcara> {
    const { data } = await api.put<BeritaAcara>(`/admin/berita-acara/${id}/assign-analyst`, {
      analyst_id: analystId,
    })
    return data
  },

  async getPayments(params?: Record<string, any>): Promise<Payment[]> {
    const { data } = await api.get<Payment[]>('/admin/payments', { params })
    return data
  },

  async confirmPayment(id: number, data: { status: string; discount_approved?: string }): Promise<Payment> {
    const { data: res } = await api.put<Payment>(`/admin/payments/${id}/confirm`, data)
    return res
  },

  async getSamples(params?: Record<string, any>): Promise<Sample[]> {
    const { data } = await api.get<Sample[]>('/admin/samples', { params })
    return data
  },

  async markSampleTaken(id: number): Promise<Sample> {
    const { data } = await api.put<Sample>(`/admin/samples/${id}/mark-taken`)
    return data
  },

  async getAnalysts(): Promise<User[]> {
    const { data } = await api.get<User[]>('/admin/analysts')
    return data
  },
}
