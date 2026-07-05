<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import SkeletonTable from '../components/common/SkeletonTable.vue'
import Button from '../components/common/Button.vue'
import { adminApi } from '../api/admin'
import { useAuthStore } from '../stores/authStore'
import type { Sample } from '../types'
import { toast } from 'vue-sonner'

const auth = useAuthStore()
const router = useRouter()
const samples = ref<Sample[]>([])
const loading = ref(true)

const statusTypeMap: Record<string, 'default' | 'warning' | 'success' | 'danger' | 'info'> = {
  pending: 'default',
  processing: 'warning',
  ready: 'success',
  taken: 'info',
}

onMounted(async () => {
  try {
    samples.value = await adminApi.getSamples()
  } catch {
    toast.error('Gagal memuat data')
  } finally {
    loading.value = false
  }
})

async function markTaken(id: number) {
  try {
    await adminApi.markSampleTaken(id)
    const idx = samples.value.findIndex(s => s.id === id)
    if (idx !== -1) samples.value[idx]!.status = 'taken'
    toast.success('Sampel telah diambil')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal')
  }
}
</script>

<template>
  <AppLayout>
    <div>
      <div class="page-header">
        <h1>Rekap Analisis</h1>
      </div>

      <Card>
        <SkeletonTable v-if="loading" :cols="4" :rows="5" />

        <div v-else class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Nama Sampel</th>
                <th>Jumlah</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in samples" :key="s.id">
                <td>{{ s.name }}</td>
                <td>{{ s.quantity || '-' }} {{ s.unit || '' }}</td>
                <td><Badge :type="statusTypeMap[s.status]" :text="s.status" /></td>
                <td>
                  <div class="row-actions">
                    <Button
                      v-if="auth.hasPermission('sample_rekap') && s.berita_acara?.analyst_id"
                      size="sm"
                      @click="router.push('/admin/forms?type=rekap_analis')"
                    >
                      Buat Rekap Analis
                    </Button>
                    <Button v-if="s.status === 'ready'" size="sm" @click="markTaken(s.id)">Sudah Diambil</Button>
                    <span v-else-if="s.status === 'taken'" class="text-muted">Selesai</span>
                    <span v-else-if="s.status === 'processing'" class="text-muted">Diproses Analis</span>
                    <span v-else class="text-muted">Diajukan</span>
                  </div>
                </td>
              </tr>
              <tr v-if="samples.length === 0">
                <td colspan="4" class="empty-cell">Tidak ada sampel</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.page-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
.table-wrap { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; font-size: 14px; }
.table th { text-align: left; padding: 12px; background: #f8fafc; color: #475569; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
.table td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
.empty-cell { text-align: center; color: #94a3b8; padding: 40px; }
.text-muted { color: #94a3b8; font-size: 13px; }
.row-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

</style>
