import api from './axios'

export interface Formula {
  id: number
  name: string
  description: string | null
  expression: string
  unit: string
  variables: FormulaVariable[]
  created_by: number
  creator?: { id: number; name: string }
  created_at: string
  updated_at: string
}

export interface FormulaVariable {
  key: string
  label: string
  source: 'berita_acara' | 'rekap_analis' | 'instrumen'
  form_field_id: number | null
  instrument_id: number | null
  default_value: number | null
}

export interface CalculateResult {
  biaya: number
  resolved_values: Record<string, number>
  expression: string
  unit: string
}

export const formulaApi = {
  getAll() {
    return api.get<Formula[]>('/admin/formulas')
  },
  get(id: number) {
    return api.get<Formula>(`/admin/formulas/${id}`)
  },
  create(data: { name: string; description?: string; expression: string; unit?: string; variables: FormulaVariable[] }) {
    return api.post('/admin/formulas', data)
  },
  update(id: number, data: Partial<{ name: string; description: string; expression: string; unit: string; variables: FormulaVariable[] }>) {
    return api.put(`/admin/formulas/${id}`, data)
  },
  delete(id: number) {
    return api.delete(`/admin/formulas/${id}`)
  },
  calculate(id: number, beritaAcaraId: number) {
    return api.post<CalculateResult>(`/admin/formulas/${id}/calculate`, { berita_acara_id: beritaAcaraId })
  },
}
