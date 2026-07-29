<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import SkeletonDetail from '../components/common/SkeletonDetail.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import PaymentForm from '../components/user/PaymentForm.vue'
import DiscountRequest from '../components/user/DiscountRequest.vue'
import CertificateDownload from '../components/user/CertificateDownload.vue'
import { beritaAcaraApi } from '../api/beritaAcara'
import { paymentApi } from '../api/payment'
import { certificateApi } from '../api/certificate'
import { formTemplateApi } from '../api/formTemplate'
import { pdfTemplateApi } from '../api/pdfTemplate'
import type { BeritaAcara, FormField } from '../types'
import { toast } from 'vue-sonner'

const route = useRoute()
const router = useRouter()
const item = ref<BeritaAcara | null>(null)
const loading = ref(true)
const showPaymentModal = ref(false)
const showDiscountModal = ref(false)
const templateFields = ref<FormField[]>([])

const statusMap: Record<string, string> = {
  draft: 'Draft',
  pending: 'Diajukan',
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
const canRequestDiscount = computed(() => item.value?.status === 'approved' && item.value?.diskon_diizinkan && item.value?.payment && (item.value.payment as any).discount_status !== 'pending')
const canDownloadCert = computed(() => item.value?.certificate)
const hasClickedPerbaiki = ref(false)
const isRevision = computed(() => item.value?.status === 'revision')
const showRevisionDetail = ref(false)
const selectedRevision = ref<any>(null)

function getLabelForKey(key: string): string {
  const field = templateFields.value.find(f => `field_${f.id}` === key || `field_${templateFields.value.indexOf(f)}` === key)
  if (field) return field.label
  return key.replace(/^field_/, '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function formatFieldValue(value: any): string {
  if (value === null || value === undefined) return '-'
  if (Array.isArray(value)) return value.join(', ')
  if (typeof value === 'object') return JSON.stringify(value)
  return String(value)
}

onMounted(async () => {
  const id = Number(route.params.id)
  if (!id || isNaN(id)) {
    toast.error('ID tidak valid')
    loading.value = false
    return
  }
  // Check if user already clicked perbaiki for this BA
  if (localStorage.getItem(`ba_perbaiki_${id}`)) {
    hasClickedPerbaiki.value = true
  }
  try {
    item.value = await beritaAcaraApi.getById(id)
    if (item.value?.form_template_id) {
      try {
        const tpl = await formTemplateApi.getPublicTemplate(item.value.form_template_id)
        if (tpl?.fields) templateFields.value = tpl.fields
      } catch {}
    }
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

function downloadPdfBeritaAcara() {
  if (!item.value) return
  window.open(pdfTemplateApi.getDownloadUrl('berita_acara', item.value.id), '_blank')
}

function downloadPdfTagihan() {
  if (!item.value?.payment) return
  window.open(pdfTemplateApi.getDownloadUrl('tagihan', item.value.payment.id), '_blank')
}

function downloadPdfSertifikat() {
  if (!item.value?.certificate) return
  window.open(pdfTemplateApi.getDownloadUrl('sertifikat', item.value.certificate.id), '_blank')
}

function handlePerbaiki() {
  if (!item.value) return
  hasClickedPerbaiki.value = true
  localStorage.setItem(`ba_perbaiki_${item.value.id}`, '1')
  router.push(`/user/berita-acara/${item.value.id}/edit`)
}

function openRevisionDetail(rev: any) {
  selectedRevision.value = rev
  showRevisionDetail.value = true
}

function getRevisionDiff(rev: any) {
  if (!rev.previous_form_data || !item.value?.form_data) return []
  const diffs: { label: string; old: string; new: string }[] = []
  const allKeys = new Set([...Object.keys(rev.previous_form_data), ...Object.keys(item.value.form_data)])
  for (const key of allKeys) {
    const oldVal = rev.previous_form_data[key]
    const newVal = item.value.form_data[key]
    if (JSON.stringify(oldVal) !== JSON.stringify(newVal)) {
      diffs.push({
        label: getLabelForKey(key),
        old: formatFieldValue(oldVal),
        new: formatFieldValue(newVal),
      })
    }
  }
  return diffs
}
</script>

<template>
  <AppLayout>
    <div>
      <SkeletonDetail v-if="loading" />

      <template v-else-if="item">
        <div class="page-header flex flex-wrap items-center justify-between gap-3 mb-4">
          <div class="flex items-center gap-2">
            <Button variant="outline" @click="router.push('/user/berita-acara')">Kembali</Button>
            <Button variant="secondary" size="sm" @click="downloadPdfBeritaAcara">
              📄 Download PDF Berita Acara
            </Button>
            <Button v-if="item.payment" variant="secondary" size="sm" @click="downloadPdfTagihan">
              💳 Download PDF Tagihan
            </Button>
            <Button v-if="item.certificate" variant="secondary" size="sm" @click="downloadPdfSertifikat">
              📜 Download PDF Sertifikat
            </Button>
          </div>
          <Badge :type="statusTypeMap[item.status]" :text="statusMap[item.status]" />
        </div>

        <!-- Revision notice -->
        <div v-if="isRevision && item.revisions && item.revisions.length" class="revision-notice">
          <div class="revision-notice__icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
          </div>
          <div class="revision-notice__content">
            <div class="revision-notice__title">Perlu Perbaikan</div>
            <div class="revision-notice__note">{{ item.revisions[item.revisions.length - 1].note }}</div>
          </div>
        </div>

        <Card title="Detail Berita Acara">
          <table v-if="item.form_data && Object.keys(item.form_data).length" class="detail-table">
            <tr v-for="(value, key) in item.form_data" :key="key">
              <td class="label">{{ getLabelForKey(String(key)) }}</td>
              <td>{{ formatFieldValue(value) }}</td>
            </tr>
          </table>
          <table v-else class="detail-table">
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
            <tr><td class="label">Jumlah Hasil Pengujian</td><td>{{ item.jumlah_sampel || '-' }}</td></tr>
            <tr><td class="label">Kode/Nama Hasil Pengujian</td><td>{{ item.kode_nama_sampel || '-' }}</td></tr>
            <tr><td class="label">Keterangan Hasil Pengujian</td><td>{{ item.keterangan_sampel || '-' }}</td></tr>
            <tr><td class="label">Keterangan Tambahan</td><td>{{ item.keterangan_tambahan || '-' }}</td></tr>
            <tr><td class="label">Pengukuran/Hasil Pengujian</td><td>{{ item.pengukuran_per_sampel || '-' }}</td></tr>
            <tr><td class="label">Sisa Hasil Pengujian</td><td>{{ item.sisa_sampel || '-' }}</td></tr>
            <tr><td class="label">Masa Berlaku Akses</td><td>{{ item.masa_berlaku_akses || '-' }}</td></tr>
          </table>
        </Card>

        <div v-if="item.revisions && item.revisions.length" class="section">
          <Card title="Riwayat Revisi">
            <div v-for="rev in item.revisions" :key="rev.id" class="revision-item">
              <div class="revision-header">
                <div class="revision-note">{{ rev.note }}</div>
                <button
                  v-if="rev.previous_form_data"
                  class="revision-detail-btn"
                  @click="openRevisionDetail(rev)"
                >
                  Detail
                </button>
              </div>
              <div class="revision-date">{{ new Date(rev.created_at).toLocaleString('id-ID') }}</div>
            </div>
            <div v-if="isRevision && !hasClickedPerbaiki && item.revisions && item.revisions.length" class="revision-action">
              <Button size="sm" @click="handlePerbaiki">Perbaiki</Button>
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
                <tr v-if="item.payment.discount_status"><td class="label">Status Diskon</td><td><Badge :type="(item.payment as any).discount_status === 'approved' ? 'success' : (item.payment as any).discount_status === 'rejected' ? 'danger' : 'warning'" :text="(item.payment as any).discount_status === 'approved' ? 'Disetujui' : (item.payment as any).discount_status === 'rejected' ? 'Ditolak' : 'Menunggu'" /></td></tr>
                <tr v-if="item.payment.discount_approved"><td class="label">Diskon Disetujui</td><td>Rp {{ Number(item.payment.discount_approved).toLocaleString('id-ID') }}</td></tr>
                <tr v-if="item.payment.payment_proof"><td class="label">Bukti Pembayaran</td><td>Sudah diupload</td></tr>
                <tr><td class="label">Status</td><td><Badge :type="item.payment.status === 'approved' ? 'success' : item.payment.status === 'rejected' ? 'danger' : 'warning'" :text="item.payment.status === 'approved' ? 'Lunas' : item.payment.status === 'rejected' ? 'Ditolak' : 'Diajukan'" /></td></tr>
              </table>
            </template>
            <div class="action-group">
              <Button v-if="canPay" @click="showPaymentModal = true">Bayar</Button>
              <Button v-if="canRequestDiscount" variant="ghost" @click="openDiscountModal">Ajukan Diskon</Button>
            </div>
          </Card>
        </div>

        <div v-if="item.samples && item.samples.length" class="section">
          <Card title="Hasil Pengujian">
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

        <Modal :show="showRevisionDetail" title="Detail Revisi" @close="showRevisionDetail = false">
          <div v-if="selectedRevision" class="revision-diff">
            <div class="revision-diff__note">
              <strong>Catatan:</strong> {{ selectedRevision.note }}
            </div>
            <div v-if="getRevisionDiff(selectedRevision).length === 0" class="revision-diff__empty">
              Tidak ada perubahan data form.
            </div>
            <div v-else class="revision-diff__list">
              <div v-for="(diff, i) in getRevisionDiff(selectedRevision)" :key="i" class="revision-diff__item">
                <div class="revision-diff__label">{{ diff.label }}</div>
                <div class="revision-diff__values">
                  <div class="revision-diff__old">
                    <span class="revision-diff__tag revision-diff__tag--old">Sebelum</span>
                    {{ diff.old }}
                  </div>
                  <div class="revision-diff__new">
                    <span class="revision-diff__tag revision-diff__tag--new">Sesudah</span>
                    {{ diff.new }}
                  </div>
                </div>
              </div>
            </div>
          </div>
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
.revision-action { margin-top: 16px; padding-top: 12px; border-top: 1px solid #f1f5f9; }

.revision-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 8px;
}
.revision-detail-btn {
  flex-shrink: 0;
  padding: 3px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: #fff;
  font-size: 12px;
  color: #2563eb;
  cursor: pointer;
  transition: all 0.15s;
  font-family: inherit;
}
.revision-detail-btn:hover {
  background: #eff6ff;
  border-color: #bfdbfe;
}

.revision-diff__note {
  padding: 10px 12px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 8px;
  font-size: 13px;
  color: #92400e;
  margin-bottom: 16px;
}
.revision-diff__empty {
  text-align: center;
  padding: 20px;
  color: #94a3b8;
  font-size: 13px;
}
.revision-diff__list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.revision-diff__item {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}
.revision-diff__label {
  padding: 8px 12px;
  background: #f8fafc;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
}
.revision-diff__values {
  display: flex;
  flex-direction: column;
}
.revision-diff__old, .revision-diff__new {
  padding: 8px 12px;
  font-size: 13px;
  color: #334155;
}
.revision-diff__old {
  background: #fef2f2;
  border-bottom: 1px solid #fecaca;
}
.revision-diff__new {
  background: #f0fdf4;
}
.revision-diff__tag {
  display: inline-block;
  padding: 1px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  margin-right: 6px;
}
.revision-diff__tag--old {
  background: #fecaca;
  color: #991b1b;
}
.revision-diff__tag--new {
  background: #bbf7d0;
  color: #166534;
}
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

.revision-notice {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 10px;
  margin-bottom: 20px;
}
.revision-notice__icon {
  color: #d97706;
  flex-shrink: 0;
}
.revision-notice__content {
  flex: 1;
  min-width: 0;
}
.revision-notice__title {
  font-size: 14px;
  font-weight: 600;
  color: #92400e;
  margin-bottom: 2px;
}
.revision-notice__note {
  font-size: 13px;
  color: #a16207;
  line-height: 1.4;
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
