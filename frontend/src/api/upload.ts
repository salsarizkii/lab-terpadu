import api from './axios'

export const uploadApi = {
  async uploadFile(file: File, folder = 'uploads'): Promise<{ path: string; url: string }> {
    const fd = new FormData()
    fd.append('file', file)
    fd.append('folder', folder)
    const { data } = await api.post('/upload-file', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data
  },
}
