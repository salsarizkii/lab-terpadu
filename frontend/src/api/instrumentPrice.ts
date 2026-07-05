import axios from './axios'

export interface InstrumentPrice {
  id: number
  name: string
  unit: string | null
  price_regular: number
  price_uns: number | null
  note: string | null
  sort_order: number
}

export const instrumentPriceApi = {
  getAll() {
    return axios.get<InstrumentPrice[]>('/instrument-prices')
  },
  create(data: Omit<InstrumentPrice, 'id'>) {
    return axios.post('/instrument-prices', data)
  },
  update(id: number, data: Partial<InstrumentPrice>) {
    return axios.put(`/instrument-prices/${id}`, data)
  },
  delete(id: number) {
    return axios.delete(`/instrument-prices/${id}`)
  },
  bulkUpdate(items: { id: number; price_regular: number; price_uns: number | null }[]) {
    return axios.put('/instrument-prices/bulk', { items })
  },
}
