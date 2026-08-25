import api from './axios'
import type { PdfTemplate, PdfVariableGroup } from '../types'

export interface GetPdfTemplateResponse {
  status: string
  data: PdfTemplate
  available_variables: PdfVariableGroup[]
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

  async importDocx(
    type: string,
    file?: File,
    onUploadProgress?: (percent: number) => void,
  ): Promise<{ message: string; html_content: string; data: PdfTemplate }> {
    const formData = new FormData()
    if (file) {
      formData.append('docx', file)
    }
    const { data } = await api.post(`/admin/pdf-templates/${type}/import-docx`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      onUploadProgress: (e: any) => {
        if (onUploadProgress && e.total) {
          onUploadProgress(Math.round((e.loaded / e.total) * 100))
        }
      },
    })
    return data
  },

  async reset(type: string): Promise<{ message: string; data: PdfTemplate }> {
    const { data } = await api.post(`/admin/pdf-templates/${type}/reset`)
    return data
  },

  async downloadDocx(type: string): Promise<Blob> {
    const { data } = await api.get(`/admin/pdf-templates/${type}/download-docx`, {
      responseType: 'blob',
    })
    return data
  },

  async preview(type: string, payload: Partial<PdfTemplate>): Promise<Blob> {
    const { data } = await api.post(`/admin/pdf-templates/${type}/preview`, payload, {
      responseType: 'blob',
    })
    return data
  },

  async previewDocx(type: string, payload: Partial<PdfTemplate>): Promise<Blob> {
    const { data } = await api.post(`/admin/pdf-templates/${type}/preview-docx`, payload, {
      responseType: 'blob',
    })
    return data
  },

  getDownloadUrl(type: string, id: number): string {
    const baseURL = api.defaults.baseURL || '/api'
    const token = localStorage.getItem('token')
    return `${baseURL}/pdf-generate/${type}/${id}?token=${token}`
  },

  getDocxDownloadUrl(type: string, id: number): string {
    const baseURL = api.defaults.baseURL || '/api'
    const token = localStorage.getItem('token')
    return `${baseURL}/docx-generate/${type}/${id}?token=${token}`
  },
}
