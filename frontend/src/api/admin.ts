import api from './axios'
import type { BeritaAcara, Payment, Sample, User, SystemActivityLog } from '../types'

export const adminApi = {
  async getBeritaAcara(params?: Record<string, any>): Promise<BeritaAcara[]> {
    const { data } = await api.get<BeritaAcara[]>('/admin/berita-acara', { params })
    return data
  },

  async getBeritaAcaraDetail(id: number): Promise<BeritaAcara> {
    const { data } = await api.get<BeritaAcara>(`/admin/berita-acara/${id}`)
    return data
  },

  async updateStatus(id: number, payload: { status: string; note?: string; nomor_agenda?: string; rejection_reason?: string }): Promise<BeritaAcara> {
    const { data } = await api.put(`/admin/berita-acara/${id}/status`, payload)
    return data.data || data
  },

  async updateNomorAgenda(id: number, nomorAgenda: string): Promise<BeritaAcara> {
    const { data } = await api.put(`/admin/berita-acara/${id}/nomor-agenda`, {
      nomor_agenda: nomorAgenda,
    })
    return data.data || data
  },

  async checkNomorAgenda(nomor: string, ignoreId?: number): Promise<{ available: boolean }> {
    const { data } = await api.get<{ available: boolean }>('/admin/berita-acara/check-nomor-agenda', {
      params: { nomor, ignore_id: ignoreId },
    })
    return data
  },

  async assignAnalyst(id: number, analystIds: number[]): Promise<{ message: string; data: BeritaAcara }> {
    const { data } = await api.put(`/admin/berita-acara/${id}/assign-analyst`, {
      analyst_ids: analystIds,
    })
    return data
  },

  async getPayments(params?: Record<string, any>): Promise<Payment[]> {
    const { data } = await api.get<Payment[]>('/admin/payments', { params })
    return data
  },

  async confirmPayment(id: number, payload: { status: string; discount_approved?: string; rejection_reason?: string }): Promise<Payment> {
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

  async getPenyelias(): Promise<User[]> {
    const { data } = await api.get<User[]>('/admin/penyelia')
    return data
  },

  async assignPenyelia(id: number, penyeliaIds: number[]): Promise<{ message: string; data: BeritaAcara }> {
    const { data } = await api.put(`/admin/berita-acara/${id}/assign-penyelia`, {
      penyelia_ids: penyeliaIds,
    })
    return data
  },

  async getManagerTeknis(): Promise<User[]> {
    const { data } = await api.get<User[]>('/admin/manager-teknis')
    return data
  },

  async assignManagerTeknis(id: number, managerTeknisIds: number[]): Promise<{ message: string; data: BeritaAcara }> {
    const { data } = await api.put(`/admin/berita-acara/${id}/assign-manager-teknis`, {
      manager_teknis_ids: managerTeknisIds,
    })
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

  async getDiscounts(): Promise<any[]> {
    const { data } = await api.get('/admin/discounts')
    return data
  },

  async assignDiscount(payload: {
    berita_acara_id: number
    discount_percent?: number
    discount_nominal?: number
    discount_reason?: string
  }): Promise<any> {
    const { data } = await api.post('/admin/discounts', payload)
    return data
  },

  async updateDiscount(id: number, payload: {
    discount_percent?: number
    discount_nominal?: number
    discount_reason?: string
  }): Promise<any> {
    const { data } = await api.put(`/admin/discounts/${id}`, payload)
    return data
  },

  async deleteDiscount(id: number): Promise<any> {
    const { data } = await api.delete(`/admin/discounts/${id}`)
    return data
  },

  async getDiscountRequests(): Promise<any[]> {
    const { data } = await api.get('/admin/discounts/requests')
    return data
  },

  async approveDiscount(id: number, payload: {
    discount_percent?: number
    discount_nominal?: number
    discount_reason?: string
  }): Promise<any> {
    const { data } = await api.post(`/admin/discounts/${id}/approve`, payload)
    return data
  },

  async rejectDiscount(id: number): Promise<any> {
    const { data } = await api.post(`/admin/discounts/${id}/reject`)
    return data
  },

  async getDiscountHistory(id: number): Promise<any[]> {
    const { data } = await api.get(`/admin/discounts/${id}/history`)
    return data
  },

  async getSystemActivityLogs(params?: { page?: number; per_page?: number; action?: string; user_id?: number; search?: string }): Promise<{
    data: SystemActivityLog[]
    current_page: number
    last_page: number
    per_page: number
    total: number
  }> {
    const { data } = await api.get('/admin/system-activity-logs', { params })
    return data
  },

  async exportDatabase(): Promise<Blob> {
    const { data } = await api.get<Blob>('/admin/export/database', {
      responseType: 'blob',
    })
    return data
  },

  async exportPesanan(): Promise<Blob> {
    const { data } = await api.get<Blob>('/admin/export/pesanan', {
      responseType: 'blob',
    })
    return data
  },
}

