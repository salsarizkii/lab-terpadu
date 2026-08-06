<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import SkeletonTable from '../components/common/SkeletonTable.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import { adminApi } from '../api/admin'
import { useAuthStore } from '../stores/authStore'
import type { Sample } from '../types'
import { toast } from 'vue-sonner'
import { Eye, Download } from '@lucide/vue'
import { formatDateTime } from '../utils/date'

const auth = useAuthStore()
const router = useRouter()
const samples = ref<Sample[]>([])
const loading = ref(true)
const selectedSample = ref<Sample | null>(null)

const statusTypeMap: Record<string, 'default' | 'warning' | 'success' | 'danger' | 'info'> = {
  pending: 'default',
  processing: 'warning',
  ready: 'success',
  taken: 'info',
}

function formatPrice(val: number | string) {
  return new Intl.NumberFormat('id-ID').format(Math.round(Number(val) || 0))
}

function hasRekap(s: Sample): boolean {
  return !!s.berita_acara?.rekap_analis_data && Object.keys(s.berita_acara.rekap_analis_data).length > 0
}

function getStorageUrl(path?: string) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  const cleanPath = path.replace(/^public\//, '')
  return `http://localhost:8000/storage/${cleanPath}`
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
    toast.success('Hasil Pengujian telah diambil')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal')
  }
}

function getRekapEntries(): Array<{ key: string; val: string }> {
  if (!selectedSample.value?.berita_acara?.rekap_analis_data) return []
  const raw = selectedSample.value.berita_acara.rekap_analis_data
  return Object.entries(raw)
    .filter(([, v]) => v !== null && v !== undefined && v !== '')
    .map(([k, v]) => ({
      key: k.startsWith('field_') ? 'Hasil ' + k.replace('field_', '') : k,
      val: String(v),
    }))
}
</script>

<template>
  <AppLayout>
    <div>
      <div class="page-header">
        <h1>Rekap Analisis</h1>
      </div>

      <Card>
        <SkeletonTable v-if="loading" :cols="5" :rows="5" />

        <div v-else class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Nama Hasil Pengujian</th>
                <th>Pemohon</th>
                <th>Jumlah</th>
                <th>Rekap</th>
                <th>Status</th>
                <th>Diajukan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in samples" :key="s.id">
                <td>{{ s.name }}</td>
                <td>{{ s.berita_acara?.user?.name || '-' }}</td>
                <td>{{ s.quantity || '-' }} {{ s.unit || '' }}</td>
                <td>
                  <span v-if="hasRekap(s)" class="rekap-badge">Selesai</span>
                  <span v-else class="text-muted">-</span>
                </td>
                <td><Badge :type="statusTypeMap[s.status]" :text="s.status" /></td>
                <td class="td-date">{{ formatDateTime(s.created_at) }}</td>
                <td>
                  <div class="row-actions">
                    <Button
                      v-if="hasRekap(s)"
                      size="sm"
                      variant="outline"
                      @click="selectedSample = s"
                    >
                      <Eye :size="14" style="margin-right:4px" /> Lihat Rekap
                    </Button>
                    <Button v-if="s.status === 'ready'" size="sm" @click="markTaken(s.id)">Sudah Diambil</Button>
                    <span v-else-if="s.status === 'taken'" class="text-muted">Selesai</span>
                    <span v-else-if="s.status === 'processing'" class="text-muted">Diproses Analis</span>
                    <span v-else class="text-muted">Diajukan</span>
                  </div>
                </td>
              </tr>
              <tr v-if="samples.length === 0">
                <td colspan="7" class="empty-cell">Tidak ada hasil pengujian</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </div>

    <!-- Modal Lihat Rekap -->
    <Modal :show="!!selectedSample" title="Detail Rekap Analis" size="lg" @close="selectedSample = null">
      <div v-if="selectedSample?.berita_acara" class="rekap-detail">
        <div class="rekap-info-row">
          <span class="rekap-label">Pemohon</span>
          <span class="rekap-value">{{ selectedSample.berita_acara.user?.name || '-' }}</span>
        </div>
        <div class="rekap-info-row">
          <span class="rekap-label">Judul Penelitian</span>
          <span class="rekap-value">{{ (selectedSample.berita_acara as any).judul_penelitian || '-' }}</span>
        </div>
        <div class="rekap-info-row">
          <span class="rekap-label">Hasil Pengujian</span>
          <span class="rekap-value">{{ selectedSample.name }}</span>
        </div>

        <!-- Rekap Analis Form Data -->
        <div v-if="selectedSample.berita_acara.rekap_analis_data" class="rekap-section">
          <h4 class="rekap-section-title">Data Rekap Analis</h4>
          <table class="rekap-table">
            <tbody>
              <tr v-for="entry in getRekapEntries()" :key="entry.key">
                <td class="rekap-td-label">{{ entry.key }}</td>
                <td class="rekap-td-value">{{ entry.val }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Custom Items -->
        <div v-if="selectedSample.berita_acara.custom_items?.length" class="rekap-section">
          <h4 class="rekap-section-title">Rincian Biaya Tambahan</h4>
          <div v-for="(ci, idx) in selectedSample.berita_acara.custom_items" :key="idx" class="rekap-item-row">
            <span>{{ ci.name }}</span>
            <span>Rp {{ formatPrice(ci.amount) }}</span>
          </div>
        </div>

        <div v-if="selectedSample.berita_acara.biaya" class="rekap-info-row rekap-total">
          <span class="rekap-label">Total Biaya</span>
          <span class="rekap-value rekap-value--bold">Rp {{ formatPrice(selectedSample.berita_acara.biaya) }}</span>
        </div>

        <div v-if="selectedSample.berita_acara.keterangan" class="rekap-info-row">
          <span class="rekap-label">Keterangan</span>
          <span class="rekap-value">{{ selectedSample.berita_acara.keterangan }}</span>
        </div>

        <div class="rekap-section">
          <h4 class="rekap-section-title">Pembayaran</h4>
          <div class="rekap-info-row">
            <span class="rekap-label">Status</span>
            <Badge :type="selectedSample.berita_acara.payment?.status === 'approved' ? 'success' : selectedSample.berita_acara.payment?.status === 'rejected' ? 'danger' : 'warning'" :text="selectedSample.berita_acara.payment?.status || '-'" />
          </div>
          <div v-if="selectedSample.berita_acara.payment?.discount_approved" class="rekap-info-row">
            <span class="rekap-label">Diskon Disetujui</span>
            <span class="rekap-value">Rp {{ formatPrice(selectedSample.berita_acara.payment.discount_approved) }}</span>
          </div>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.page-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
.table-wrap { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; font-size: 14px; }
.table th { text-align: left; padding: 12px; background: #f8fafc; color: #475569; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
.table td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
.td-date { white-space: nowrap; }
.empty-cell { text-align: center; color: #94a3b8; padding: 40px; }
.text-muted { color: #94a3b8; font-size: 13px; }
.row-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

.rekap-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; background: #dcfce7; color: #15803d; }
.rekap-detail { display: flex; flex-direction: column; gap: 10px; }
.rekap-info-row { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; }
.rekap-label { font-size: 13px; font-weight: 600; color: #475569; }
.rekap-value { font-size: 13px; color: #1e293b; }
.rekap-value--bold { font-weight: 700; }
.rekap-section { margin-top: 12px; padding-top: 12px; border-top: 1px solid #e2e8f0; }
.rekap-section-title { font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 8px; }
.rekap-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.rekap-td-label { padding: 6px 8px; font-weight: 600; color: #475569; background: #f8fafc; border-bottom: 1px solid #f1f5f9; width: 40%; }
.rekap-td-value { padding: 6px 8px; color: #1e293b; border-bottom: 1px solid #f1f5f9; }
.rekap-item-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 13px; color: #1e293b; }
.rekap-total { margin-top: 4px; padding-top: 8px; border-top: 1px solid #cbd5e1; }
</style>
