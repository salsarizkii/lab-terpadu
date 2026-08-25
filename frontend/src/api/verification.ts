import api from './axios'
import type { BeritaAcara } from '../types'

export const verificationApi = {
  // Penyelia
  async getPenyeliaBeritaAcara(): Promise<BeritaAcara[]> {
    const { data } = await api.get<BeritaAcara[]>('/penyelia/berita-acara')
    return data
  },

  async getPenyeliaBeritaAcaraDetail(id: number): Promise<BeritaAcara> {
    const { data } = await api.get<BeritaAcara>(`/penyelia/berita-acara/${id}`)
    return data
  },

  async penyeliaVerify(id: number, payload: { status: 'penyelia_verified' | 'revision' | 'rejected'; note?: string }): Promise<BeritaAcara> {
    const { data } = await api.put(`/penyelia/berita-acara/${id}/status`, payload)
    return data.data || data
  },

  // Manager Teknis
  async getManagerBeritaAcara(): Promise<BeritaAcara[]> {
    const { data } = await api.get<BeritaAcara[]>('/manager-teknis/berita-acara')
    return data
  },

  async getManagerBeritaAcaraDetail(id: number): Promise<BeritaAcara> {
    const { data } = await api.get<BeritaAcara>(`/manager-teknis/berita-acara/${id}`)
    return data
  },

  async managerVerify(id: number, payload: { status: 'completed' | 'revision' | 'rejected'; note?: string }): Promise<BeritaAcara> {
    const { data } = await api.put(`/manager-teknis/berita-acara/${id}/status`, payload)
    return data.data || data
  },
}
