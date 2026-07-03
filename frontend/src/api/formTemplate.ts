import api from './axios'
import type { FormTemplate, FormFieldOption } from '../types'

export interface SyncFieldsPayload {
  fields: {
    label: string
    type: string
    options?: FormFieldOption[]
    is_required?: boolean
  }[]
}

export const formTemplateApi = {
  async getTemplates(type?: string): Promise<FormTemplate[]> {
    const params: Record<string, string> = {}
    if (type) params.type = type
    const { data } = await api.get<FormTemplate[]>('/admin/form-templates', { params })
    return data
  },

  async getTemplate(id: number): Promise<FormTemplate> {
    const { data } = await api.get<FormTemplate>(`/admin/form-templates/${id}`)
    return data
  },

  async updateTemplate(id: number, payload: { name?: string; description?: string; is_active?: boolean }): Promise<{ message: string; data: FormTemplate }> {
    const { data } = await api.put(`/admin/form-templates/${id}`, payload)
    return data
  },

  async createTemplate(payload: { name: string; type: string; description?: string }): Promise<FormTemplate> {
    const { data } = await api.post('/admin/form-templates', payload)
    return data.data || data
  },

  async syncFields(id: number, fields: SyncFieldsPayload): Promise<{ message: string; data: FormTemplate }> {
    const { data } = await api.put(`/admin/form-templates/${id}/fields`, fields)
    return data
  },

  async getPublicTemplate(id: number): Promise<FormTemplate> {
    const { data } = await api.get<FormTemplate>(`/form-templates/${id}`)
    return data
  },

  async getPublicActiveByType(type: string): Promise<FormTemplate | null> {
    try {
      const { data } = await api.get<FormTemplate>(`/form-templates/type/${type}`)
      return data
    } catch {
      return null
    }
  },
}
