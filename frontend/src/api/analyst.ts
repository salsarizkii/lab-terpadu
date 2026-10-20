import api from './axios'
import type { BeritaAcara, Sample } from '../types'
import type { CalculateResult } from './formula'

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

  async storeRekapAnalis(beritaAcaraId: number, payload: {
    rekap_analis_data: string
    rekap_analis_template_id?: number | null
    biaya?: number | null
    formula_id?: number | null
    custom_items?: Array<{ name: string; amount: number }>
    keterangan?: string | null
  }): Promise<BeritaAcara> {
    const { data } = await api.put(
      `/analyst/berita-acara/${beritaAcaraId}/rekap-analis`,
      payload
    )
    return data.data || data
  },

  async calculateCost(beritaAcaraId: number, payload: {
    formula_id: number
    rekap_analis_data?: string
  }): Promise<CalculateResult> {
    const { data } = await api.post(
      `/analyst/berita-acara/${beritaAcaraId}/calculate`,
      payload
    )
    return data
  },

  async requestDiscount(beritaAcaraId: number, payload: {
    discount_request: number
    discount_reason?: string
  }): Promise<any> {
    const { data } = await api.post(
      `/analyst/berita-acara/${beritaAcaraId}/discount`,
      payload
    )
    return data.data || data
  },

  async confirmDiscount(beritaAcaraId: number, payload: {
    status: 'approved' | 'rejected'
    discount_approved?: number
  }): Promise<any> {
    const { data } = await api.put(
      `/analyst/berita-acara/${beritaAcaraId}/confirm-discount`,
      payload
    )
    return data.data || data
  },

  async uploadPaymentProof(beritaAcaraId: number, file: File): Promise<any> {
    const formData = new FormData()
    formData.append('payment_proof', file)
    const { data } = await api.post(
      `/analyst/berita-acara/${beritaAcaraId}/upload-proof`,
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )
    return data.data || data
  },

  async uploadAkses(beritaAcaraId: number, file: File): Promise<{ upload_akses: string }> {
    const formData = new FormData()
    formData.append('upload_akses', file)
    const { data } = await api.post(
      `/analyst/berita-acara/${beritaAcaraId}/upload-akses`,
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )
    return data.data
  },

  async removeAkses(beritaAcaraId: number): Promise<void> {
    await api.delete(`/analyst/berita-acara/${beritaAcaraId}/upload-akses`)
  },

  async uploadBerkas(beritaAcaraId: number, file: File): Promise<{ berkas_ulang: string }> {
    const formData = new FormData()
    formData.append('berkas_ulang', file)
    const { data } = await api.post(
      `/analyst/berita-acara/${beritaAcaraId}/upload-berkas`,
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )
    return data.data
  },

  async removeBerkas(beritaAcaraId: number): Promise<void> {
    await api.delete(`/analyst/berita-acara/${beritaAcaraId}/upload-berkas`)
  },
}
