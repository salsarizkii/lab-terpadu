<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import SampleDataForm from '../components/analyst/SampleDataForm.vue'
import PengujianForm from '../components/analyst/PengujianForm.vue'
import BeritaAcaraDetailView from '../components/admin/BeritaAcaraDetailView.vue'
import SkeletonDetail from '../components/common/SkeletonDetail.vue'
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

const statusMap: Record<string, string> = {
  draft: 'Draft', pending: 'Diajukan', revision: 'Revisi', approved: 'Disetujui', rejected: 'Ditolak',
}
const statusTypeMap: Record<string, 'default' | 'warning' | 'success' | 'danger' | 'info'> = {
  draft: 'default', pending: 'warning', revision: 'warning', approved: 'success', rejected: 'danger',
}
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
  if (ba.samples.every((s: any) => s.status === 'ready' || s.status === 'taken')) return 'ready'
  return 'processing'
}

function getSampleBadgeType(): 'default' | 'warning' | 'success' | 'danger' | 'info' {
  const status = getSampleStatus()
  return sampleStatusType[status] || 'default'
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
    toast.success('Data sampel diperbarui')
    showSampleModal.value = false
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal update sampel')
  }
}

async function markReady(sampleId: number) {
  if (!item.value) return
  try {
    await analystApi.markSampleReady(item.value.id, sampleId)
    const updated = await analystApi.getBeritaAcaraDetail(item.value.id)
    item.value = updated
    toast.success('Sampel siap diambil')
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
</script>

<template>
  <AppLayout>
    <div>
      <div class="page-header">
        <div class="page-header__left">
          <Button variant="outline" @click="router.push('/analyst/samples')">Kembali</Button>
          <h1>Detail Sampel</h1>
        </div>
        <Badge v-if="item" :type="getSampleBadgeType()" :text="getSampleBadgeText()" />
      </div>

      <SkeletonDetail v-if="loading" />

      <template v-else-if="item">
        <div class="detail-grid">
          <div class="detail-main">
            <Card title="Data Berita Acara">
              <BeritaAcaraDetailView :berita-acara="item" :hide-sections="['verifikasi', 'pembayaran']" :override-status="getSampleBadgeObj()" />
            </Card>
          </div>

          <div class="detail-sidebar">
            <Card title="Laporan Pengujian">
              <div v-if="item.pengujians && item.pengujians.length">
                <div class="pengujian-done">Sudah dilaporkan</div>
                <Button v-if="auth.hasPermission('sample_rekap')" block variant="outline" @click="showPengujianModal = true">Edit Laporan</Button>
              </div>
              <div v-else>
                <div v-if="item.samples && item.samples.length > 0 && item.samples.every(s => s.status === 'ready')">
                  <p class="pengujian-desc">Semua sampel sudah siap.</p>
                  <Button v-if="auth.hasPermission('sample_rekap')" block @click="showPengujianModal = true">Laporkan Pengujian</Button>
                </div>
                <div v-else class="pengujian-pending">Tunggu semua sampel siap.</div>
              </div>

              <div class="sticky-divider" />

              <div v-if="item.rekap_analis_data" class="rekap-done">Rekap sudah diisi</div>
              <Button v-if="auth.hasPermission('sample_rekap')" block variant="outline" @click="router.push(`/analyst/samples/${item.id}/rekap`)">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                {{ item.rekap_analis_data ? 'Edit Rekap Analis' : 'Buat Rekap Analis' }}
              </Button>
            </Card>
          </div>
        </div>
      </template>

      <Modal :show="showSampleModal" title="Input Data Sampel" @close="showSampleModal = false">
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
.page-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }

.detail-grid { display: grid; grid-template-columns: 1fr 360px; gap: 24px; align-items: start; }
.detail-sidebar { position: sticky; top: 24px; }
@media (max-width: 900px) { .detail-grid { grid-template-columns: 1fr; } }
.sticky-divider { height: 1px; background: #e2e8f0; margin: 16px 0; }

.pengujian-done { font-size: 13px; color: #16a34a; font-weight: 500; margin-bottom: 12px; }
.pengujian-desc { margin: 0 0 12px; font-size: 14px; color: #475569; }
.pengujian-pending { color: #94a3b8; font-size: 14px; }
.rekap-done { font-size: 13px; color: #16a34a; font-weight: 500; margin-bottom: 12px; }
</style>
