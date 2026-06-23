import { defineStore } from 'pinia'
import { ref } from 'vue'
import { beritaAcaraApi } from '../api/beritaAcara'
import type { BeritaAcara } from '../types'

export const useBeritaAcaraStore = defineStore('beritaAcara', () => {
  const list = ref<BeritaAcara[]>([])
  const current = ref<BeritaAcara | null>(null)
  const loading = ref(false)

  async function fetchAll(params?: Record<string, any>) {
    loading.value = true
    try {
      list.value = await beritaAcaraApi.getList(params)
    } finally {
      loading.value = false
    }
  }

  async function fetchById(id: number) {
    loading.value = true
    try {
      current.value = await beritaAcaraApi.getById(id)
    } finally {
      loading.value = false
    }
  }

  async function create(formData: FormData) {
    const ba = await beritaAcaraApi.create(formData)
    list.value.unshift(ba)
    return ba
  }

  async function update(id: number, formData: FormData) {
    const ba = await beritaAcaraApi.update(id, formData)
    const idx = list.value.findIndex((item) => item.id === id)
    if (idx !== -1) list.value[idx] = ba
    current.value = ba
    return ba
  }

  return { list, current, loading, fetchAll, fetchById, create, update }
})
