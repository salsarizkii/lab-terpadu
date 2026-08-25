<script setup lang="ts">
import { computed } from 'vue'
import Badge from './Badge.vue'
import type { BeritaAcara } from '../../types'

const props = defineProps<{
  beritaAcara: BeritaAcara
}>()

function formatPrice(val: number | string) {
  return new Intl.NumberFormat('id-ID').format(Math.round(Number(val) || 0))
}

const rekapEntries = computed(() => {
  const raw = props.beritaAcara.rekap_analis_data
  if (!raw) return []
  return Object.entries(raw)
    .filter(([, v]) => v !== null && v !== undefined && v !== '')
    .map(([k, v]) => ({
      key: k.startsWith('field_') ? 'Hasil ' + k.replace('field_', '') : k,
      val: String(v),
    }))
})
</script>

<template>
  <div class="rekap-detail">
    <div class="rekap-info-row">
      <span class="rekap-label">Pemohon</span>
      <span class="rekap-value">{{ beritaAcara.user?.name || beritaAcara.nama_pemohon || '-' }}</span>
    </div>
    <div class="rekap-info-row">
      <span class="rekap-label">Judul Penelitian</span>
      <span class="rekap-value">{{ beritaAcara.judul_penelitian || '-' }}</span>
    </div>

    <div v-if="rekapEntries.length" class="rekap-section">
      <h4 class="rekap-section-title">Data Rekap Analis</h4>
      <table class="rekap-table">
        <tbody>
          <tr v-for="entry in rekapEntries" :key="entry.key">
            <td class="rekap-td-label">{{ entry.key }}</td>
            <td class="rekap-td-value">{{ entry.val }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="beritaAcara.custom_items?.length" class="rekap-section">
      <h4 class="rekap-section-title">Rincian Biaya Tambahan</h4>
      <div v-for="(ci, idx) in beritaAcara.custom_items" :key="idx" class="rekap-item-row">
        <span>{{ ci.name }}</span>
        <span>Rp {{ formatPrice(ci.amount) }}</span>
      </div>
    </div>

    <div v-if="beritaAcara.biaya" class="rekap-info-row rekap-total">
      <span class="rekap-label">Total Biaya</span>
      <span class="rekap-value rekap-value--bold">Rp {{ formatPrice(beritaAcara.biaya) }}</span>
    </div>

    <div v-if="beritaAcara.keterangan" class="rekap-info-row">
      <span class="rekap-label">Keterangan</span>
      <span class="rekap-value">{{ beritaAcara.keterangan }}</span>
    </div>

    <div class="rekap-section">
      <h4 class="rekap-section-title">Pembayaran</h4>
      <div class="rekap-info-row">
        <span class="rekap-label">Status</span>
        <Badge
          :type="beritaAcara.payment?.status === 'approved' ? 'success' : beritaAcara.payment?.status === 'rejected' ? 'danger' : 'warning'"
          :text="beritaAcara.payment?.status === 'approved' ? 'Lunas' : beritaAcara.payment?.status === 'rejected' ? 'Ditolak' : 'Diajukan'"
        />
      </div>
      <div v-if="beritaAcara.payment?.discount_approved" class="rekap-info-row">
        <span class="rekap-label">Diskon Disetujui</span>
        <span class="rekap-value">Rp {{ formatPrice(beritaAcara.payment.discount_approved) }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.rekap-detail { display: flex; flex-direction: column; gap: 10px; }
.rekap-info-row { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; }
.rekap-label { font-size: 13px; font-weight: 600; color: #475569; }
.rekap-value { font-size: 13px; color: #1e293b; text-align: right; word-break: break-word; }
.rekap-value--bold { font-weight: 700; }
.rekap-section { margin-top: 12px; padding-top: 12px; border-top: 1px solid #e2e8f0; }
.rekap-section-title { font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 8px; }
.rekap-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.rekap-td-label { padding: 6px 8px; font-weight: 600; color: #475569; background: #f8fafc; border-bottom: 1px solid #f1f5f9; width: 40%; }
.rekap-td-value { padding: 6px 8px; color: #1e293b; border-bottom: 1px solid #f1f5f9; }
.rekap-item-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 13px; color: #1e293b; }
.rekap-total { margin-top: 4px; padding-top: 8px; border-top: 1px solid #cbd5e1; }
</style>
