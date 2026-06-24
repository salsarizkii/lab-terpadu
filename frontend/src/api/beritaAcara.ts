import api from './axios'
import type { BeritaAcara } from '../types'

export const beritaAcaraApi = {
  async getList(params?: Record<string, any>): Promise<BeritaAcara[]> {
    const { data } = await api.get<BeritaAcara[]>('/user/berita-acara', { params })
    return data
  },

  async getById(id: number): Promise<BeritaAcara> {
    const { data } = await api.get<BeritaAcara>(`/user/berita-acara/${id}`)
    return data
  },

  async create(formData: FormData): Promise<BeritaAcara> {
    const { data } = await api.post('/user/berita-acara', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data.data || data
  },

  async update(id: number, formData: FormData): Promise<BeritaAcara> {
    formData.append('_method', 'PUT')
    const { data } = await api.post(`/user/berita-acara/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data.data || data
  },
}
