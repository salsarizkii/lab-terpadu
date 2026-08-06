<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import SkeletonTable from '../components/common/SkeletonTable.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import { beritaAcaraApi } from '../api/beritaAcara'
import type { Sample } from '../types'
import { toast } from 'vue-sonner'
import { Eye } from '@lucide/vue'
import { formatDateTime } from '../utils/date'

const samples = ref<Sample[]>([])
const loading = ref(true)
const selectedSample = ref<Sample | null>(null)

function formatPrice(val: number | string) {
  return new Intl.NumberFormat('id-ID').format(Math.round(Number(val) || 0))
}

function hasRekap(s: Sample): boolean {
  return !!s.berita_acara?.rekap_analis_data && Object.keys(s.berita_acara.rekap_analis_data).length > 0
}

onMounted(async () => {
  try {
    samples.value = await beritaAcaraApi.getSamples()
  } catch {
    toast.error('Gagal memuat data hasil pengujian')
  } finally {
    loading.value = false
  }
})

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
        <h1>Hasil Pengujian Saya</h1>
      </div>

      <Card>
        <SkeletonTable v-if="loading" :cols="4" :rows="5" />

        <div v-else class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Nama Hasil Pengujian</th>
                <th>Jumlah</th>
                <th>Rekap</th>
                <th>Diajukan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in samples" :key="s.id">
                <td>{{ s.name }}</td>
                <td>{{ s.quantity || '-' }} {{ s.unit || '' }}</td>
                <td>
                  <span v-if="hasRekap(s)" class="rekap-badge">Selesai</span>
                  <span v-else class="text-muted">-</span>
                </td>
                <td class="td-date">{{ formatDateTime(s.created_at) }}</td>
                <td>
                  <Button v-if="hasRekap(s)" size="sm" variant="outline" @click="selectedSample = s">
                    <Eye :size="14" style="margin-right:4px" /> Lihat Rekap
                  </Button>
                  <span v-else class="text-muted">Menunggu</span>
                </td>
              </tr>
              <tr v-if="samples.length === 0">
                <td colspan="5" class="empty-cell">Belum ada hasil pengujian yang lunas</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </div>

    <Modal :show="!!selectedSample" title="Detail Rekap Analis" size="lg" @close="selectedSample = null">
      <div v-if="selectedSample?.berita_acara" class="rekap-detail">
        <div class="rekap-info-row">
          <span class="rekap-label">Hasil Pengujian</span>
          <span class="rekap-value">{{ selectedSample.name }}</span>
        </div>

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

        <div v-if="selectedSample.berita_acara.custom_items?.length" class="rekap-section">
          <h4 class="rekap-section-title">Rincian Biaya</h4>
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
