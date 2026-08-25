<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import PageHeader from '../components/common/PageHeader.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import SampleDataForm from '../components/analyst/SampleDataForm.vue'
import PengujianForm from '../components/analyst/PengujianForm.vue'
import BeritaAcaraDetailView from '../components/admin/BeritaAcaraDetailView.vue'
import SkeletonDetail from '../components/common/SkeletonDetail.vue'
import ActivityTimeline from '../components/common/ActivityTimeline.vue'
import { analystApi } from '../api/analyst'
import { useAuthStore } from '../stores/authStore'
import type { BeritaAcara } from '../types'
import { toast } from 'vue-sonner'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const item = ref<BeritaAcara | null>(null)
const loading = ref(true)
const showSampleModal = ref(false)
const selectedSample = ref<any>(null)
const showPengujianModal = ref(false)

const showDiscountSidebar = computed(() => {
  return !!item.value?.payment && !!item.value.payment.discount_request && (item.value.payment as any).discount_status === 'pending'
})

const hasRekap = computed(() => {
  return item.value?.rekap_analis_data !== null && item.value?.rekap_analis_data !== undefined
})

const canWork = computed(() => item.value?.status === 'penyelia_verified')

const sampleStatusType: Record<string, 'default' | 'warning' | 'success' | 'danger' | 'info'> = {
  pending: 'default', processing: 'warning', ready: 'success', taken: 'info',
}
const sampleStatusText: Record<string, string> = {
  pending: 'Menunggu', processing: 'Proses', ready: 'Siap Diambil', taken: 'Sudah Dikirim',
}

function getSampleStatus(): string {
  const ba = item.value as any
  if (ba?.rekap_analis_data !== null && ba?.rekap_analis_data !== undefined) return 'taken'
  if (!ba?.samples || ba.samples.length === 0) return 'processing'
  if (ba.samples.every((s: any) => s.status === 'taken')) return 'taken'
  if (ba.samples.some((s: any) => s.status === 'processing')) return 'processing'
  return 'processing'
}

function getSampleBadgeType(): 'default' | 'warning' | 'success' | 'danger' | 'info' {
  return sampleStatusType[getSampleStatus()] || 'default'
}

function getSampleBadgeText(): string {
  return sampleStatusText[getSampleStatus()] || '-'
}

function getSampleBadgeObj(): { type: string; label: string } {
  return {
    type: sampleStatusType[getSampleStatus()] || 'default',
    label: sampleStatusText[getSampleStatus()] || '-',
  }
}

onMounted(async () => {
  const id = Number(route.params.id)
  if (!id || isNaN(id)) {
    toast.error('ID tidak valid')
    loading.value = false
    return
  }
  try {
    item.value = await analystApi.getBeritaAcaraDetail(id)
  } catch {
    toast.error('Gagal memuat data')
  } finally {
    loading.value = false
  }
})

function openSampleForm(sample: any) {
  selectedSample.value = { ...sample, berita_acara_id: item.value!.id }
  showSampleModal.value = true
}

async function handleSampleUpdate(data: any) {
  if (!selectedSample.value || !item.value) return
  try {
    await analystApi.updateSample(item.value.id, selectedSample.value.id, data)
    const updated = await analystApi.getBeritaAcaraDetail(item.value.id)
    item.value = updated
    toast.success('Data hasil pengujian diperbarui')
    showSampleModal.value = false
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal update hasil pengujian')
  }
}

async function markReady(sampleId: number) {
  if (!item.value) return
  try {
    await analystApi.markSampleReady(item.value.id, sampleId)
    const updated = await analystApi.getBeritaAcaraDetail(item.value.id)
    item.value = updated
    toast.success('Hasil Pengujian siap diambil')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal')
  }
}

async function handlePengujianSubmit(data: any) {
  if (!item.value) return
  try {
    await analystApi.storePengujian(item.value.id, data)
    const updated = await analystApi.getBeritaAcaraDetail(item.value.id)
    item.value = updated
    toast.success('Laporan pengujian disimpan')
    showPengujianModal.value = false
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal')
  }
}

function formatPrice(val: number) {
  return new Intl.NumberFormat('id-ID').format(Math.round(val))
}

async function handleApproveDiscount() {
  if (!item.value) return
  try {
    const discountAmount = Number(item.value.payment?.discount_request || 0)
    await analystApi.confirmDiscount(item.value.id, {
      status: 'approved',
      discount_approved: discountAmount,
    })
    const updated = await analystApi.getBeritaAcaraDetail(item.value.id)
    item.value = updated
    toast.success('Diskon disetujui')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menyetujui diskon')
  }
}

async function handleRejectDiscount() {
  if (!item.value) return
  try {
    await analystApi.confirmDiscount(item.value.id, {
      status: 'rejected',
    })
    const updated = await analystApi.getBeritaAcaraDetail(item.value.id)
    item.value = updated
    toast.success('Diskon ditolak')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menolak diskon')
  }
}
</script>

<template>
  <AppLayout>
    <div>
      <PageHeader
        title="Detail Hasil Pengujian"
        subtitle="Kelola laporan pengujian hasil pengujian dan rekap analis"
        show-back
        back-to="/analyst/samples"
      >
        <div v-if="item" class="page-header__status">
          <span class="status-label">Status Hasil Pengujian</span>
          <Badge :type="getSampleBadgeType()" :text="getSampleBadgeText()" />
        </div>
      </PageHeader>

      <SkeletonDetail v-if="loading" />

      <template v-else-if="item">
        <div class="detail-grid" :class="{ 'has-discount-sidebar': showDiscountSidebar }">
          <div class="detail-main">
            <Card title="Data Berita Acara">
              <BeritaAcaraDetailView :berita-acara="item" :hide-sections="['verifikasi', 'pembayaran']" :override-status="getSampleBadgeObj()" />
            </Card>

            <Card title="Riwayat Aktivitas" style="margin-top: 24px;">
              <ActivityTimeline :logs="item.activity_logs || []" />
            </Card>
          </div>

          <div class="detail-sidebar">
            <!-- Laporan Pengujian -->
            <Card title="Laporan Pengujian">
              <div v-if="item.status === 'rekap_ready'" class="pengujian-pending">Rekap sedang menunggu verifikasi manager teknis.</div>
              <div v-else-if="!canWork" class="pengujian-pending">Belum waktunya mengerjakan — berita acara belum diverifikasi penyelia.</div>
              <template v-else>
              <div v-if="item.pengujians && item.pengujians.length">
                <div class="pengujian-done">Sudah dilaporkan</div>
                <Button v-if="auth.hasPermission('hasil_pengujian_rekap')" block variant="outline" @click="showPengujianModal = true">Edit Laporan</Button>
              </div>
              <div v-else>
                <div v-if="item.samples && item.samples.length > 0 && item.samples.every(s => s.status === 'ready')">
                  <p class="pengujian-desc">Semua hasil pengujian sudah siap.</p>
                  <Button v-if="auth.hasPermission('hasil_pengujian_rekap')" block @click="showPengujianModal = true">Laporkan Pengujian</Button>
                </div>
                <div v-else class="pengujian-pending">Tunggu semua hasil pengujian siap.</div>
              </div>

              <div class="sticky-divider" />

              <div v-if="hasRekap" class="rekap-done">Rekap sudah diisi</div>
              <div v-if="hasRekap && item.biaya" class="rekap-biaya">Jumlah Pembayaran: Rp {{ formatPrice(Number(item.biaya)) }}</div>
              <Button v-if="!hasRekap && auth.hasPermission('hasil_pengujian_rekap')" block variant="primary" @click="router.push(`/analyst/samples/${item.id}/rekap`)">
                Buat Rekap Analis
              </Button>
              </template>
            </Card>
          </div>

          <!-- SIDEBAR: Ajuan Diskon (panel terpisah) -->
          <div v-if="showDiscountSidebar" class="detail-discount-sidebar">
            <Card title="Ajuan Diskon">
              <table class="discount-table">
                <tr>
                  <td class="discount-td-label">Jumlah Diajukan</td>
                  <td class="discount-td-value">Rp {{ formatPrice(Number(item.payment?.discount_request || 0)) }}</td>
                </tr>
                <tr v-if="item.payment?.discount_reason">
                  <td class="discount-td-label">Alasan</td>
                  <td class="discount-td-value">{{ item.payment.discount_reason }}</td>
                </tr>
                <tr>
                  <td class="discount-td-label">Status</td>
                  <td class="discount-td-value">
                    <Badge type="warning" text="Menunggu" />
                  </td>
                </tr>
              </table>
              <div v-if="auth.hasPermission('discount_verifikasi')" class="discount-actions-row">
                <Button variant="outline" size="sm" @click="handleApproveDiscount">Setujui</Button>
                <Button variant="outline" size="sm" @click="handleRejectDiscount">Tolak</Button>
              </div>
            </Card>
          </div>
        </div>
      </template>

      <Modal :show="showSampleModal" title="Input Data Hasil Pengujian" @close="showSampleModal = false">
        <SampleDataForm
          v-if="selectedSample"
          :sample="selectedSample"
          :berita-acara-id="selectedSample.berita_acara_id"
          @submit="handleSampleUpdate"
        />
      </Modal>

      <Modal :show="showPengujianModal" title="Laporan Pengujian" size="lg" @close="showPengujianModal = false">
        <PengujianForm
          v-if="item"
          :berita-acara="item"
          :existing="item.pengujians?.[0] || null"
          @submit="handlePengujianSubmit"
        />
      </Modal>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.page-header__left { display: flex; align-items: center; gap: 12px; }
.page-header__status { display: flex; align-items: center; gap: 8px; }
.status-label { font-size: 13px; font-weight: 500; color: #64748b; }
.page-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }

.detail-grid { display: grid; grid-template-columns: 1fr 360px; gap: 24px; align-items: start; }
.detail-grid.has-discount-sidebar { grid-template-columns: 1fr 360px 320px; }
.detail-sidebar { position: sticky; top: 24px; }
.detail-discount-sidebar { position: sticky; top: 24px; }
@media (max-width: 900px) { .detail-grid { grid-template-columns: 1fr; } .detail-grid.has-discount-sidebar { grid-template-columns: 1fr; } }
.sticky-divider { height: 1px; background: #e2e8f0; margin: 16px 0; }

.pengujian-done { font-size: 13px; color: #16a34a; font-weight: 500; margin-bottom: 12px; }
.rekap-done { font-size: 13px; color: #16a34a; font-weight: 500; margin-bottom: 8px; }
.rekap-biaya { font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 12px; }
.pengujian-desc { margin: 0 0 12px; font-size: 14px; color: #475569; }
.pengujian-pending { color: #94a3b8; font-size: 14px; }

.discount-table { width: 100%; border-collapse: collapse; }
.discount-table tr { border-bottom: 1px solid #f1f5f9; }
.discount-table tr:last-child { border-bottom: none; }
.discount-td-label { padding: 8px 12px 8px 0; font-size: 12px; font-weight: 500; color: #64748b; white-space: nowrap; }
.discount-td-value { padding: 8px 0; font-size: 13px; color: #1e293b; }
.discount-actions-row { display: flex; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid #f1f5f9; }
</style>
