import api from './axios'
import type { PdfTemplate, PdfVariable } from '../types'

export interface GetPdfTemplateResponse {
  status: string
  data: PdfTemplate
  available_variables: PdfVariable[]
}

export const pdfTemplateApi = {
  async getAll(): Promise<PdfTemplate[]> {
    const { data } = await api.get('/admin/pdf-templates')
    return data.data || []
  },

  async getByType(type: string): Promise<GetPdfTemplateResponse> {
    const { data } = await api.get<GetPdfTemplateResponse>(`/admin/pdf-templates/${type}`)
    return data
  },

  async update(type: string, payload: Partial<PdfTemplate>): Promise<{ message: string; data: PdfTemplate }> {
    const { data } = await api.put(`/admin/pdf-templates/${type}`, payload)
    return data
  },

  async importDocx(type: string, file?: File): Promise<{ message: string; html_content: string; data: PdfTemplate }> {
    const formData = new FormData()
    if (file) {
      formData.append('docx', file)
    }
    const { data } = await api.post(`/admin/pdf-templates/${type}/import-docx`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
    return data
  },

  getDownloadUrl(type: string, id: number): string {
    const baseURL = api.defaults.baseURL || '/api'
    const token = localStorage.getItem('token')
    return `${baseURL}/pdf-generate/${type}/${id}?token=${token}`
  },
}
