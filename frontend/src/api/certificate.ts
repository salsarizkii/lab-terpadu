import api from './axios'
import type { Certificate } from '../types'

export const certificateApi = {
  async download(id: number): Promise<Blob> {
    const { data } = await api.get<Blob>(`/user/certificates/${id}/download`, {
      responseType: 'blob',
    })
    return data
  },

  async upload(beritaAcaraId: number, file: File): Promise<Certificate> {
    const formData = new FormData()
    formData.append('certificate', file)
    const { data } = await api.post(
      `/admin/certificates/${beritaAcaraId}/upload`,
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )
    return data.data
  },

  async remove(beritaAcaraId: number): Promise<void> {
    await api.delete(`/admin/certificates/${beritaAcaraId}`)
  },
}