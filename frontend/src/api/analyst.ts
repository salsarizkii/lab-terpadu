import api from './axios'
import type { BeritaAcara, Sample } from '../types'

export const analystApi = {
  async getBeritaAcara(params?: Record<string, any>): Promise<BeritaAcara[]> {
    const { data } = await api.get<BeritaAcara[]>('/analyst/berita-acara', { params })
    return data
  },

  async getBeritaAcaraDetail(id: number): Promise<BeritaAcara> {
    const { data } = await api.get<BeritaAcara>(`/analyst/berita-acara/${id}`)
    return data
  },

  async updateSample(beritaAcaraId: number, sampleId: number, payload: {
    test_result?: string
    status?: string
  }): Promise<Sample> {
    const { data } = await api.put(
      `/analyst/berita-acara/${beritaAcaraId}/samples/${sampleId}`,
      payload
    )
    return data.data || data
  },

  async markSampleReady(beritaAcaraId: number, sampleId: number): Promise<Sample> {
    const { data } = await api.put(
      `/analyst/berita-acara/${beritaAcaraId}/samples/${sampleId}/ready`
    )
    return data.data || data
  },

  async storePengujian(beritaAcaraId: number, payload: any): Promise<any> {
    const { data } = await api.post(
      `/analyst/berita-acara/${beritaAcaraId}/pengujian`,
      payload
    )
    return data.data || data
  },
}
