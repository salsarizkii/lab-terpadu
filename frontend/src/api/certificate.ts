import api from './axios'

export const certificateApi = {
  async download(id: number): Promise<Blob> {
    const { data } = await api.get<Blob>(`/user/certificates/${id}/download`, {
      responseType: 'blob',
    })
    return data
  },
}
