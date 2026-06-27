<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import LoadingSpinner from '../components/common/LoadingSpinner.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import PaymentForm from '../components/user/PaymentForm.vue'
import DiscountRequest from '../components/user/DiscountRequest.vue'
import CertificateDownload from '../components/user/CertificateDownload.vue'
import { beritaAcaraApi } from '../api/beritaAcara'
import { paymentApi } from '../api/payment'
import { certificateApi } from '../api/certificate'
import type { BeritaAcara } from '../types'
import { toast } from 'vue-sonner'

const route = useRoute()
const router = useRouter()
const item = ref<BeritaAcara | null>(null)
const loading = ref(true)
const showPaymentModal = ref(false)
const showDiscountModal = ref(false)

const statusMap: Record<string, string> = {
  draft: 'Draft',
  pending: 'Menunggu Verifikasi',
  revision: 'Perlu Revisi',
  approved: 'Disetujui',
  rejected: 'Ditolak',
}

const statusTypeMap: Record<string, 'default' | 'warning' | 'success' | 'danger' | 'info'> = {
  draft: 'default',
  pending: 'warning',
  revision: 'warning',
  approved: 'success',
  rejected: 'danger',
}

const canEdit = computed(() => item.value && ['draft', 'pending', 'revision'].includes(item.value.status))
const canPay = computed(() => item.value?.payment && item.value.payment.status === 'pending' && !item.value?.payment?.payment_proof)
const canRequestDiscount = computed(() => item.value?.status === 'approved' && item.value?.diskon_diizinkan && item.value?.payment && item.value.payment.status === 'pending' && !item.value.payment.discount_request)
const canDownloadCert = computed(() => item.value?.certificate)

onMounted(async () => {
  const id = Number(route.params.id)
  if (!id || isNaN(id)) {
    toast.error('ID tidak valid')
    loading.value = false
    return
  }
  try {
    item.value = await beritaAcaraApi.getById(id)
  } catch {
    toast.error('Gagal memuat data')
  } finally {
    loading.value = false
  }
})

async function handlePayment(fd: FormData) {
  if (!item.value?.payment) return
  try {
    const payment = await paymentApi.uploadProof(item.value.payment.id, fd)
    if (item.value) item.value.payment = payment
    toast.success('Bukti pembayaran berhasil diupload')
    showPaymentModal.value = false
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal')
  }
}

function openDiscountModal() {
  showDiscountModal.value = true
}

async function handleDiscount(data: any) {
  try {
    const payment = await paymentApi.requestDiscount(item.value!.payment!.id, data)
    if (item.value) item.value.payment = payment
    toast.success('Pengajuan diskon berhasil dikirim')
    showDiscountModal.value = false
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal')
  }
}

async function handleDownload(certificateId: number) {
  try {
    const blob = await certificateApi.download(certificateId)
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `sertifikat-${item.value?.judul_penelitian || certificateId}.pdf`
    a.click()
    window.URL.revokeObjectURL(url)
  } catch (e: any) {
    toast.error('Gagal mengunduh sertifikat')
  }
}
</script>

<template>
  <AppLayout>
    <div>
      <LoadingSpinner v-if="loading" size="lg" />

      <template v-else-if="item">
        <div class="page-header">
          <div>
            <Button variant="outline" @click="router.push('/user/berita-acara')">Kembali</Button>
          </div>
          <Badge :type="statusTypeMap[item.status]" :text="statusMap[item.status]" />
        </div>

        <Card title="Detail Berita Acara">
          <table class="detail-table">
            <tr><td class="label">Nama Pemohon</td><td>{{ item.nama_pemohon || '-' }}</td></tr>
            <tr><td class="label">NIM</td><td>{{ item.nim || '-' }}</td></tr>
            <tr><td class="label">Prodi/Fakultas/Instansi</td><td>{{ item.prodi_fakultas_instansi || '-' }}</td></tr>
            <tr><td class="label">Dosen Pembimbing</td><td>{{ item.dosen_pembimbing || '-' }}</td></tr>
            <tr><td class="label">Telp/HP</td><td>{{ item.telp_hp || '-' }}</td></tr>
            <tr><td class="label">Email</td><td>{{ item.email || '-' }}</td></tr>
            <tr><td class="label">Alamat</td><td>{{ item.alamat || '-' }}</td></tr>
            <tr><td class="label">Judul Penelitian</td><td>{{ item.judul_penelitian || '-' }}</td></tr>
            <tr><td class="label">Akses Lab</td><td>{{ item.akses_lab || '-' }}</td></tr>
            <tr><td class="label">Bahan Contoh Uji</td><td>{{ item.bahan_contoh_uji || '-' }}</td></tr>
            <tr><td class="label">Kemasan/Packing</td><td>{{ item.kemasan_packing || '-' }}</td></tr>
            <tr><td class="label">Permintaan Analisa</td><td>{{ item.permintaan_analisa || '-' }}</td></tr>
            <tr><td class="label">Jumlah Sampel</td><td>{{ item.jumlah_sampel || '-' }}</td></tr>
            <tr><td class="label">Kode/Nama Sampel</td><td>{{ item.kode_nama_sampel || '-' }}</td></tr>
            <tr><td class="label">Keterangan Sampel</td><td>{{ item.keterangan_sampel || '-' }}</td></tr>
            <tr><td class="label">Keterangan Tambahan</td><td>{{ item.keterangan_tambahan || '-' }}</td></tr>
            <tr><td class="label">Pengukuran/Sampel</td><td>{{ item.pengukuran_per_sampel || '-' }}</td></tr>
            <tr><td class="label">Sisa Sampel</td><td>{{ item.sisa_sampel || '-' }}</td></tr>
            <tr><td class="label">Masa Berlaku Akses</td><td>{{ item.masa_berlaku_akses || '-' }}</td></tr>
          </table>
        </Card>

        <div v-if="item.revisions && item.revisions.length" class="section">
          <Card title="Riwayat Revisi">
            <div v-for="rev in item.revisions" :key="rev.id" class="revision-item">
              <div class="revision-note">{{ rev.note }}</div>
              <div class="revision-date">{{ new Date(rev.created_at).toLocaleString('id-ID') }}</div>
            </div>
          </Card>
        </div>

        <div class="section">
          <Card title="Pembayaran">
            <template v-if="item.status === 'approved' && item.biaya">
              <div class="biaya-info">
                <div class="biaya-label">Biaya yang harus dibayar</div>
                <div class="biaya-value">Rp {{ Number(item.biaya).toLocaleString('id-ID') }}</div>
              </div>
            </template>

            <div v-if="!item.payment" class="empty-text">Belum ada pembayaran</div>
            <template v-else>
              <table class="detail-table">
                <tr><td class="label">Jumlah</td><td>Rp {{ Number(item.payment.amount).toLocaleString('id-ID') }}</td></tr>
                <tr v-if="item.payment.discount_request"><td class="label">Diskon Diminta</td><td>Rp {{ Number(item.payment.discount_request).toLocaleString('id-ID') }}</td></tr>
                <tr v-if="item.payment.discount_reason"><td class="label">Alasan Diskon</td><td>{{ item.payment.discount_reason }}</td></tr>
                <tr v-if="item.payment.discount_approved"><td class="label">Diskon Disetujui</td><td>Rp {{ Number(item.payment.discount_approved).toLocaleString('id-ID') }}</td></tr>
                <tr v-if="item.payment.payment_proof"><td class="label">Bukti Pembayaran</td><td>Sudah diupload</td></tr>
                <tr><td class="label">Status</td><td><Badge :type="item.payment.status === 'approved' ? 'success' : item.payment.status === 'rejected' ? 'danger' : 'warning'" :text="item.payment.status === 'approved' ? 'Lunas' : item.payment.status === 'rejected' ? 'Ditolak' : 'Menunggu'" /></td></tr>
              </table>
            </template>
            <div class="action-group">
              <Button v-if="canPay" @click="showPaymentModal = true">Bayar</Button>
              <Button v-if="canRequestDiscount" variant="ghost" @click="openDiscountModal">Ajukan Diskon</Button>
            </div>
          </Card>
        </div>

        <div v-if="item.samples && item.samples.length" class="section">
          <Card title="Sampel">
            <div v-for="s in item.samples" :key="s.id" class="sample-item">
              <div class="sample-name">{{ s.name }}</div>
              <Badge :type="s.status === 'ready' ? 'success' : s.status === 'taken' ? 'info' : 'default'" :text="s.status" />
            </div>
          </Card>
        </div>

        <div v-if="canDownloadCert" class="section">
          <Card title="Sertifikat">
            <CertificateDownload :certificate-id="item.certificate!.id" @download="handleDownload" />
          </Card>
        </div>

        <Modal :show="showPaymentModal" title="Pembayaran" @close="showPaymentModal = false">
          <PaymentForm v-if="item" :berita-acara="item" @submit="handlePayment" />
        </Modal>

        <Modal :show="showDiscountModal" title="Ajukan Diskon" @close="showDiscountModal = false">
          <DiscountRequest v-if="item.payment" :payment="item.payment" @submit="handleDiscount" />
        </Modal>
      </template>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}
.section {
  margin-top: 24px;
}
.detail-table {
  width: 100%;
  border-collapse: collapse;
}
.detail-table td {
  padding: 10px 12px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 14px;
}
.detail-table .label {
  font-weight: 600;
  color: #475569;
  width: 220px;
}
.revision-item {
  padding: 12px 0;
  border-bottom: 1px solid #f1f5f9;
}
.revision-note { font-size: 14px; color: #334155; }
.revision-date { font-size: 12px; color: #94a3b8; margin-top: 4px; }
.sample-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #f1f5f9;
}
.sample-name { font-weight: 500; }
.action-group {
  display: flex;
  gap: 12px;
  margin-top: 16px;
}
.empty-text {
  color: #94a3b8;
  text-align: center;
  padding: 20px;
}
.biaya-info {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
  text-align: center;
}
.biaya-label {
  font-size: 12px;
  color: #16a34a;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}
.biaya-value {
  font-size: 28px;
  font-weight: 700;
  color: #15803d;
}
.upload-proof-form {
  padding: 8px 0;
}
.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}

</style>
