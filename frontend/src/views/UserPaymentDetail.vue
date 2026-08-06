<script setup lang="ts">
import { ref, onMounted, computed, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import PageHeader from '../components/common/PageHeader.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import Button from '../components/common/Button.vue'
import SkeletonDetail from '../components/common/SkeletonDetail.vue'
import ActivityTimeline from '../components/common/ActivityTimeline.vue'
import { paymentApi } from '../api/payment'
import type { Payment } from '../types'
import { toast } from 'vue-sonner'
import {
  UploadCloud,
  FileText,
  FileImage,
  X,
  XCircle,
  Clock,
  AlertTriangle,
  CheckCircle2,
  Trash2,
  RefreshCw,
  Eye,
  FileCheck,
  Download,
  Info
} from '@lucide/vue'

const route = useRoute()
const router = useRouter()

const payment = ref<Payment | null>(null)
const loading = ref(true)

const showDiscountModal = ref(false)
const discountReason = ref('')
const discountMode = ref('nominal')
const discountPercent = ref('')
const discountNominal = ref('')
const submittingDiscount = ref(false)

const computedDiscountRequest = computed(() => {
  const biaya = Number(payment.value?.amount || 0)
  if (discountMode.value === 'percent') {
    return Math.round(biaya * Number(discountPercent.value) / 100)
  }
  return Number(discountNominal.value) || 0
})

// Upload Modal State
const showUploadModal = ref(false)
const uploadingProof = ref(false)
const proofFile = ref<File | null>(null)
const proofKeterangan = ref('')
const previewUrl = ref('')
const isDragging = ref(false)
const fileInputRef = ref<HTMLInputElement | null>(null)

const MAX_FILE_SIZE_BYTES = 2 * 1024 * 1024 // 2 MB

const showProofPreview = ref(false)
const discountStatus = computed(() => (payment.value as any)?.discount_status)

const parsedCustomItems = computed(() => {
  const ba = payment.value?.berita_acara
  if (!ba || !ba.custom_items) return []
  if (Array.isArray(ba.custom_items)) return ba.custom_items
  if (typeof ba.custom_items === 'string') {
    try {
      const parsed = JSON.parse(ba.custom_items)
      return Array.isArray(parsed) ? parsed : []
    } catch {
      return []
    }
  }
  return []
})

const statusTypeMap: Record<string, 'default' | 'warning' | 'success' | 'danger' | 'info'> = {
  pending: 'warning',
  approved: 'success',
  rejected: 'danger',
}

function formatPrice(val: any) {
  const num = Number(val)
  if (isNaN(num) || num === null || num === undefined) return '0'
  return new Intl.NumberFormat('id-ID').format(Math.round(num))
}

function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 B'
  if (bytes < 1024 * 1024) {
    return `${(bytes / 1024).toFixed(1)} KB`
  }
  return `${(bytes / (1024 * 1024)).toFixed(2)} MB`
}

function getStorageUrl(path?: string) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  const cleanPath = path.replace(/^public\//, '')
  return `http://localhost:8000/storage/${cleanPath}`
}

const proofUrl = computed(() => getStorageUrl(payment.value?.payment_proof))

const isProofImage = computed(() => {
  if (!payment.value?.payment_proof) return false
  return /\.(jpg|jpeg|png|webp)$/i.test(payment.value.payment_proof)
})

const isProofPdf = computed(() => {
  if (!payment.value?.payment_proof) return false
  return /\.pdf$/i.test(payment.value.payment_proof)
})

const proofFileName = computed(() => {
  if (!payment.value?.payment_proof) return ''
  return payment.value.payment_proof.split('/').pop() || 'Bukti Pembayaran'
})

const fileSizeBytes = computed(() => proofFile.value?.size || 0)
const isValidFileSize = computed(() => fileSizeBytes.value > 0 && fileSizeBytes.value <= MAX_FILE_SIZE_BYTES)
const fileSizeBytesFormatted = computed(() => formatFileSize(fileSizeBytes.value))

const isImageFile = computed(() => {
  if (!proofFile.value) return false
  return proofFile.value.type.startsWith('image/') || /\.(jpg|jpeg|png|webp)$/i.test(proofFile.value.name)
})

const isPdfFile = computed(() => {
  if (!proofFile.value) return false
  return proofFile.value.type === 'application/pdf' || /\.pdf$/i.test(proofFile.value.name)
})

const fileExtension = computed(() => {
  if (!proofFile.value) return ''
  const name = proofFile.value.name
  const ext = name.split('.').pop()
  return ext ? ext.toUpperCase() : ''
})

const fileSizePercentage = computed(() => {
  if (!fileSizeBytes.value) return 0
  return Math.min(100, Math.round((fileSizeBytes.value / MAX_FILE_SIZE_BYTES) * 100))
})

onMounted(async () => {
  const id = Number(route.params.id)
  if (!id || isNaN(id)) {
    toast.error('ID tidak valid')
    loading.value = false
    return
  }
  try {
    payment.value = await paymentApi.getById(id)
  } catch {
    toast.error('Gagal memuat data pembayaran')
  } finally {
    loading.value = false
  }
})

onUnmounted(() => {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
  }
})

function openDiscountModal() {
  discountPercent.value = ''
  discountNominal.value = ''
  discountReason.value = ''
  discountMode.value = 'nominal'
  showDiscountModal.value = true
}

async function handleSubmitDiscount() {
  if (!payment.value || !computedDiscountRequest.value) return
  submittingDiscount.value = true
  try {
    await paymentApi.requestDiscount(payment.value.id, {
      discount_request: computedDiscountRequest.value,
      discount_reason: discountReason.value || undefined,
    })
    const updated = await paymentApi.getById(payment.value.id)
    payment.value = updated
    toast.success('Pengajuan diskon berhasil')
    showDiscountModal.value = false
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal mengajukan diskon')
  } finally {
    submittingDiscount.value = false
  }
}

function openUploadModal() {
  clearSelectedFile()
  proofKeterangan.value = payment.value?.keterangan || ''
  showUploadModal.value = true
}

function closeUploadModal() {
  showUploadModal.value = false
  clearSelectedFile()
}

function clearSelectedFile() {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = ''
  }
  proofFile.value = null
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

function processFile(file: File) {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = ''
  }
  proofFile.value = file

  if (file.type.startsWith('image/') || /\.(jpg|jpeg|png|webp)$/i.test(file.name)) {
    previewUrl.value = URL.createObjectURL(file)
  }

  if (file.size > MAX_FILE_SIZE_BYTES) {
    toast.error(`Ukuran file (${formatFileSize(file.size)}) melebihi batas 2.0 MB`)
  }
}

function handleProofFileChange(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (file) {
    processFile(file)
  }
}

function handleDragOver(e: DragEvent) {
  e.preventDefault()
  isDragging.value = true
}

function handleDragLeave(e: DragEvent) {
  e.preventDefault()
  isDragging.value = false
}

function handleDrop(e: DragEvent) {
  e.preventDefault()
  isDragging.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file) {
    processFile(file)
  }
}

function triggerFileSelect() {
  fileInputRef.value?.click()
}

async function handleUploadProof() {
  if (!payment.value || !proofFile.value) return
  if (!isValidFileSize.value) {
    toast.error('Ukuran file melebihi batas maksimal 2.0 MB')
    return
  }

  uploadingProof.value = true
  try {
    const formData = new FormData()
    formData.append('payment_proof', proofFile.value)
    if (proofKeterangan.value.trim()) {
      formData.append('keterangan', proofKeterangan.value.trim())
    }

    await paymentApi.uploadProof(payment.value.id, formData)
    const updated = await paymentApi.getById(payment.value.id)
    payment.value = updated
    toast.success('Bukti pembayaran berhasil diupload')
    closeUploadModal()
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal upload bukti')
  } finally {
    uploadingProof.value = false
  }
}
</script>

<template>
  <AppLayout>
    <div>
      <PageHeader
        title="Detail Pembayaran"
        subtitle="Rincian tagihan, bukti transfer, dan status pembayaran hasil pengujian"
        show-back
        back-to="/user/payments"
      >
        <Badge v-if="payment" :type="statusTypeMap[payment.status]" :text="payment.status === 'approved' ? 'Lunas' : payment.status === 'rejected' ? 'Ditolak' : 'Diajukan'" />
      </PageHeader>

      <SkeletonDetail v-if="loading" />

      <template v-else-if="payment">
        <div class="detail-grid">
          <div class="detail-main">
            <Card title="Informasi Pembayaran">
              <table class="detail-table">
                <tr>
                  <td class="label" style="vertical-align: top; padding-top: 14px;">Jumlah</td>
                  <td>
                    <div v-if="payment.berita_acara" class="jumlah-breakdown">
                      <template v-if="parsedCustomItems && parsedCustomItems.length">
                        <div class="jumlah-items">
                          <div v-for="(ci, idx) in parsedCustomItems" :key="idx" class="jumlah-item">
                            <span class="jumlah-item-label">{{ ci.name || 'Biaya' }}:</span>
                            <span class="jumlah-item-val">Rp {{ formatPrice(ci.amount) }}</span>
                          </div>
                        </div>
                      </template>
                      <template v-else-if="payment.berita_acara.formula">
                        <div class="jumlah-item">
                          <span class="jumlah-item-label">Rumus:</span>
                          <span class="jumlah-item-name">{{ payment.berita_acara.formula.name }}</span>
                          <span class="jumlah-item-val">Rp {{ formatPrice(Number(payment.berita_acara.biaya || payment.amount || 0)) }}</span>
                        </div>
                      </template>
                      <template v-else>
                        <div class="jumlah-total">
                          Rp {{ formatPrice(Number(payment.amount || 0)) }}
                        </div>
                      </template>

                      <div class="jumlah-divider" />
                      <div class="jumlah-total">
                        <span>Total Biaya</span>
                        <strong>Rp {{ formatPrice(Number(payment.berita_acara.biaya || payment.amount || 0)) }}</strong>
                      </div>
                      <div v-if="payment.berita_acara.keterangan" class="jumlah-ket">
                        <span class="jumlah-ket-label">Keterangan:</span>
                        {{ payment.berita_acara.keterangan }}
                      </div>
                    </div>
                    <span v-else>Rp {{ formatPrice(Number(payment.amount || 0)) }}</span>
                  </td>
                </tr>
                <tr v-if="payment.discount_request"><td class="label">Diskon Diminta</td><td>Rp {{ formatPrice(Number(payment.discount_request)) }}</td></tr>
                <tr v-if="payment.discount_reason"><td class="label">Alasan Diskon</td><td>{{ payment.discount_reason }}</td></tr>
                <tr v-if="discountStatus"><td class="label">Status Diskon</td><td><Badge :type="discountStatus === 'approved' ? 'success' : discountStatus === 'rejected' ? 'danger' : 'warning'" :text="discountStatus === 'approved' ? 'Disetujui' : discountStatus === 'rejected' ? 'Ditolak' : 'Menunggu'" /></td></tr>
                <tr v-if="payment.discount_approved"><td class="label">Diskon Disetujui</td><td>Rp {{ formatPrice(Number(payment.discount_approved)) }}</td></tr>
                <tr><td class="label">Status Pembayaran</td><td><Badge :type="statusTypeMap[payment.status]" :text="payment.status === 'approved' ? 'Lunas' : payment.status === 'rejected' ? 'Ditolak' : 'Diajukan'" /></td></tr>
              </table>

              <div class="total-bayar">
                <span class="total-bayar-label">Total yang Harus Dibayar</span>
                <span class="total-bayar-value">Rp {{ formatPrice(Math.max(0, Number(payment.amount || 0) - Number(payment.discount_approved || 0))) }}</span>
              </div>

              <!-- Evidence of Payment Card -->
              <div v-if="payment.payment_proof" class="uploaded-proof-card">
                <div class="uploaded-proof-header">
                  <div class="uploaded-proof-title">
                    <CheckCircle2 class="icon-success" :size="20" />
                    <span>Bukti Pembayaran Terunggah</span>
                  </div>
                  <div class="btn-proof-group">
                    <a
                      :href="getStorageUrl(payment.payment_proof)"
                      download
                      target="_blank"
                      rel="noopener noreferrer"
                      class="btn-proof-download"
                    >
                      <Download :size="15" />
                      <span>Download</span>
                    </a>
                    <button class="btn-preview-toggle" @click="showProofPreview = !showProofPreview">
                      <Eye :size="15" />
                      <span>{{ showProofPreview ? 'Sembunyikan' : 'Preview' }}</span>
                    </button>
                  </div>
                </div>
                <!-- Preview File Bukti -->
                <div v-if="showProofPreview" class="proof-preview-box">
                  <div v-if="isProofImage" class="proof-image-box">
                    <img :src="proofUrl" alt="Bukti Pembayaran" class="proof-image" />
                  </div>
                  <div v-else-if="isProofPdf" class="proof-pdf-box">
                    <iframe :src="proofUrl" class="proof-iframe" title="Bukti PDF"></iframe>
                  </div>
                  <div v-else class="proof-generic-box">
                    <FileText :size="36" class="proof-generic-icon" />
                    <span class="proof-generic-name">{{ proofFileName }}</span>
                  </div>
                </div>
                <div v-if="payment.keterangan" class="uploaded-proof-ket">
                  <span class="uploaded-proof-ket-label">Catatan:</span> {{ payment.keterangan }}
                </div>
              </div>
              <!-- Rejection Reason Alert Box if Payment Rejected -->
              <div v-if="payment.status === 'rejected'" class="rejection-alert-card">
                <div class="rejection-alert-header">
                  <XCircle class="icon-rejected" :size="20" />
                  <span>Pembayaran Ditolak Admin</span>
                </div>
                <div v-if="payment.rejection_reason" class="rejection-reason-text">
                  <strong>Alasan Penolakan:</strong> {{ payment.rejection_reason }}
                </div>
                <div class="rejection-hint">
                  Silakan upload ulang bukti pembayaran yang valid.
                </div>
              </div>
            </Card>

            <Card title="Riwayat Aktivitas" style="margin-top: 24px;">
              <ActivityTimeline :logs="(payment as any).berita_acara?.activity_logs || []" />
            </Card>
          </div>

          <div class="detail-sidebar">
            <Card title="Aksi Pembayaran">
              <!-- Upload proof button -->
              <Button v-if="!payment.payment_proof || payment.status === 'rejected'" block variant="primary" @click="openUploadModal" style="margin-bottom: 10px;">
                <UploadCloud :size="18" style="margin-right: 6px;" />
                {{ payment.status === 'rejected' ? 'Upload Ulang Bukti Pembayaran' : 'Upload Bukti Pembayaran' }}
              </Button>

              <!-- Discount request button -->
              <Button
                v-if="payment.status !== 'approved' && (!payment.discount_request || discountStatus === 'rejected')"
                block
                variant="outline"
                @click="openDiscountModal"
              >
                {{ discountStatus === 'rejected' ? 'Ajukan Diskon Lagi' : 'Ajukan Diskon' }}
              </Button>

              <!-- Proof uploaded, pending verification -->
              <div v-if="payment.payment_proof && payment.status === 'pending'" class="status-card-box pending-box">
                <div class="status-card-header">
                  <Clock :size="18" class="icon-warning" />
                  <span>Menunggu Verifikasi Admin</span>
                </div>
                <p class="status-card-desc">Bukti pembayaran Anda telah terunggah dan sedang diverifikasi oleh admin.</p>
              </div>

              <!-- Payment approved -->
              <div v-if="payment.status === 'approved'" class="status-card-box success-box">
                <div class="status-card-header">
                  <CheckCircle2 :size="18" class="icon-success" />
                  <span>Pembayaran Lunas</span>
                </div>
                <p class="status-card-desc">Pembayaran Anda telah diverifikasi dan disetujui.</p>
              </div>
            </Card>
          </div>
        </div>

        <!-- Popup Upload Redesigned -->
        <div v-if="showUploadModal" class="popup-overlay" @click.self="closeUploadModal">
          <div class="popup upload-modal">
            <div class="popup-header">
              <div class="header-icon-box">
                <UploadCloud :size="22" class="header-icon" />
              </div>
              <div class="header-text">
                <h3>Upload Bukti Pembayaran</h3>
                <p>Unggah resi transfer atau bukti pembayaran Anda</p>
              </div>
              <button class="popup-close" @click="closeUploadModal">
                <X :size="20" />
              </button>
            </div>

            <div class="popup-body">
              <!-- Hidden File Input -->
              <input
                ref="fileInputRef"
                type="file"
                accept="image/jpeg,image/png,image/webp,application/pdf,.jpg,.jpeg,.png,.webp,.pdf"
                class="hidden-input"
                @change="handleProofFileChange"
              />

              <!-- State 1: Dropzone when no file selected -->
              <div
                v-if="!proofFile"
                class="dropzone"
                :class="{ 'dropzone--active': isDragging }"
                @dragover="handleDragOver"
                @dragleave="handleDragLeave"
                @drop="handleDrop"
                @click="triggerFileSelect"
              >
                <div class="dropzone__icon-circle">
                  <UploadCloud :size="32" />
                </div>
                <div class="dropzone__title">
                  Tarik & lepas file di sini, atau <span class="highlight">Pilih File</span>
                </div>
                <div class="dropzone__hint">
                  Mendukung: <strong>JPG, PNG, WEBP, PDF</strong>
                </div>
                <div class="dropzone__badge">
                  Maksimal 2 MB
                </div>
              </div>

              <!-- State 2: File Review Card when file is selected -->
              <div v-else class="review-section">
                <div class="review-header">
                  <div class="review-title">
                    <FileCheck :size="18" class="text-primary" />
                    <span>Review Bukti Pembayaran</span>
                  </div>
                  <Badge
                    :type="isValidFileSize ? 'success' : 'danger'"
                    :text="isValidFileSize ? 'Format & Ukuran Valid' : 'Ukuran Melebihi Batas'"
                  />
                </div>

                <!-- Error Alert if Size > 2MB -->
                <div v-if="!isValidFileSize" class="alert-box alert-box--danger">
                  <AlertTriangle :size="20" class="alert-icon" />
                  <div class="alert-content">
                    <strong>Ukuran File Terlalu Besar ({{ fileSizeBytesFormatted }})</strong>
                    <p>Batas maksimal file yang diizinkan adalah <strong>2.0 MB</strong>. Silakan kecilkan ukuran atau ganti file.</p>
                  </div>
                </div>

                <!-- Preview Visual Container -->
                <div class="preview-container">
                  <!-- Image Preview -->
                  <div v-if="isImageFile && previewUrl" class="image-preview-wrapper">
                    <img :src="previewUrl" alt="Preview Bukti Pembayaran" class="image-preview" />
                  </div>

                  <!-- PDF Preview Icon -->
                  <div v-else-if="isPdfFile" class="pdf-preview-wrapper">
                    <div class="pdf-icon-box">
                      <FileText :size="48" class="pdf-icon" />
                    </div>
                    <div class="pdf-info">
                      <span class="pdf-badge">Dokumen PDF</span>
                      <span class="pdf-filename">{{ proofFile.name }}</span>
                    </div>
                  </div>

                  <!-- Fallback Generic File -->
                  <div v-else class="generic-file-preview">
                    <FileImage :size="40" class="generic-icon" />
                    <span>{{ proofFile.name }}</span>
                  </div>
                </div>

                <!-- File Metadata Details -->
                <div class="file-meta-card">
                  <div class="file-meta-row">
                    <div class="file-meta-info">
                      <div class="file-meta-name" :title="proofFile.name">{{ proofFile.name }}</div>
                      <div class="file-meta-sub">
                        {{ isImageFile ? 'Gambar (' + fileExtension + ')' : isPdfFile ? 'Dokumen PDF' : fileExtension }}
                        •
                        <strong :class="{ 'text-danger': !isValidFileSize }">{{ fileSizeBytesFormatted }}</strong>
                      </div>
                    </div>
                    <button type="button" class="btn-remove-file" @click="clearSelectedFile" title="Hapus / Ganti File">
                      <Trash2 :size="16" />
                      <span>Ganti File</span>
                    </button>
                  </div>

                  <!-- File Size Meter / Progress Bar -->
                  <div class="size-meter">
                    <div class="size-meter__labels">
                      <span>Ukuran File: <strong>{{ fileSizeBytesFormatted }}</strong></span>
                      <span>Maks. 2.0 MB</span>
                    </div>
                    <div class="size-meter__bar">
                      <div
                        class="size-meter__fill"
                        :class="{ 'size-meter__fill--over': !isValidFileSize }"
                        :style="{ width: `${fileSizePercentage}%` }"
                      />
                    </div>
                  </div>
                </div>

                <!-- Keterangan / Notes Input -->
                <div class="form-field">
                  <label class="form-label">Keterangan / Catatan Tambahan <span class="optional">(Opsional)</span></label>
                  <textarea
                    v-model="proofKeterangan"
                    class="form-textarea"
                    placeholder="Contoh: Transfer via Bank BCA a.n. Budi S / Ref: 981273"
                    rows="2"
                  />
                  <span class="form-hint">Tambahkan catatan nomor referensi atau nama pengirim transfer jika diperlukan.</span>
                </div>
              </div>
            </div>

            <div class="popup-footer">
              <Button variant="outline" @click="closeUploadModal">Batal</Button>
              <Button
                variant="primary"
                :disabled="uploadingProof || !proofFile || !isValidFileSize"
                @click="handleUploadProof"
              >
                <RefreshCw v-if="uploadingProof" class="spinner" :size="16" style="margin-right: 6px;" />
                <UploadCloud v-else :size="16" style="margin-right: 6px;" />
                {{ uploadingProof ? 'Mengirim...' : 'Upload Bukti' }}
              </Button>
            </div>
          </div>
        </div>

        <!-- Popup Diskon -->
        <div v-if="showDiscountModal" class="popup-overlay" @click.self="showDiscountModal = false">
          <div class="popup">
            <div class="popup-header">
              <div class="header-text">
                <h3>Ajukan Diskon</h3>
              </div>
              <button class="popup-close" @click="showDiscountModal = false">&times;</button>
            </div>
            <div class="popup-body">
              <div class="discount-total-info">
                <span class="discount-total-label">Total biaya saat ini:</span>
                <span class="discount-total-value">Rp {{ formatPrice(Number(payment?.amount || 0)) }}</span>
              </div>

              <div class="form-field">
                <label class="form-label">Tipe Diskon</label>
                <select v-model="discountMode" class="form-select">
                  <option value="nominal">Manual (Rp)</option>
                  <option value="percent">Persen (%)</option>
                </select>
              </div>

              <template v-if="discountMode === 'percent'">
                <div class="form-field">
                  <label class="form-label">Persentase (%)</label>
                  <input v-model="discountPercent" type="number" min="0" max="100" class="form-input" placeholder="Contoh: 10" />
                  <p v-if="Number(discountPercent) > 0" class="form-hint">= Rp {{ formatPrice(computedDiscountRequest) }}</p>
                </div>
              </template>
              <template v-else>
                <div class="form-field">
                  <label class="form-label">Jumlah Diskon (Rp)</label>
                  <input v-model="discountNominal" type="number" min="0" class="form-input" placeholder="Masukkan jumlah diskon" />
                </div>
              </template>

              <div class="form-field">
                <label class="form-label">Alasan Diskon</label>
                <textarea v-model="discountReason" class="form-textarea" placeholder="Masukkan alasan pengajuan diskon..." rows="3" />
              </div>

              <div v-if="computedDiscountRequest > 0" class="preview-box">
                <div class="preview-row">
                  <span>Diskon Diajukan</span>
                  <span>-Rp {{ formatPrice(computedDiscountRequest) }}</span>
                </div>
                <div class="preview-row preview-row--sisa">
                  <span>Sisa Bayar</span>
                  <span>Rp {{ formatPrice(Math.max(0, Number(payment?.amount || 0) - computedDiscountRequest)) }}</span>
                </div>
              </div>
            </div>
            <div class="popup-footer">
              <Button variant="outline" @click="showDiscountModal = false">Batal</Button>
              <Button :disabled="submittingDiscount || !computedDiscountRequest" @click="handleSubmitDiscount">
                {{ submittingDiscount ? 'Mengirim...' : 'Kirim Pengajuan' }}
              </Button>
            </div>
          </div>
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<style scoped>
.detail-grid { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
.detail-sidebar { position: sticky; top: 88px; }
@media (max-width: 1024px) { .detail-grid { grid-template-columns: 1fr; } }

.detail-table { width: 100%; border-collapse: collapse; }
.detail-table tr { border-bottom: 1px solid #f1f5f9; }
.detail-table tr:last-child { border-bottom: none; }
.detail-table td { padding: 12px 0; font-size: 14px; }
.detail-table .label { font-weight: 500; color: #64748b; width: 160px; }

.total-bayar {
  margin-top: 20px; padding: 18px 20px;
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  border-radius: 12px;
  display: flex; justify-content: space-between; align-items: center;
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
}
.total-bayar-label { font-size: 15px; font-weight: 500; color: #bfdbfe; }
.total-bayar-value { font-size: 22px; font-weight: 800; color: #fff; }

.jumlah-breakdown { display: flex; flex-direction: column; gap: 6px; }
.jumlah-item { display: flex; align-items: center; gap: 6px; font-size: 13px; }
.jumlah-item-label { color: #64748b; }
.jumlah-item-name { color: #1e293b; font-weight: 500; flex: 1; }
.jumlah-item-val { font-weight: 600; color: #1e293b; white-space: nowrap; }
.jumlah-items { display: flex; flex-direction: column; gap: 4px; }
.jumlah-divider { height: 1px; background: #e2e8f0; margin: 2px 0; }
.jumlah-total { display: flex; justify-content: space-between; font-size: 15px; color: #1e293b; }
.jumlah-total strong { color: #166534; }
.jumlah-ket { font-size: 12px; color: #64748b; padding-top: 4px; }
.jumlah-ket-label { font-weight: 500; }

.info-after { margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
.info-after-item { font-size: 13px; color: #16a34a; font-weight: 500; margin-bottom: 4px; }

/* Uploaded Proof Card */
.uploaded-proof-card {
  margin-top: 16px;
  padding: 14px 16px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 10px;
}
.uploaded-proof-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.uploaded-proof-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 800;
  font-size: 15px;
  color: #15803d;
  letter-spacing: -0.2px;
}
/* Rejection Alert Card */
.rejection-alert-card {
  margin-top: 16px;
  padding: 14px 16px;
  background: #fef2f2;
  border: 1px solid #fca5a5;
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.rejection-alert-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
  color: #991b1b;
  font-size: 14px;
}
.icon-rejected { color: #dc2626; }
.rejection-reason-text {
  font-size: 13px;
  color: #7f1d1d;
  background: #ffffff;
  padding: 8px 12px;
  border-radius: 6px;
  border: 1px solid #fecaca;
}
.rejection-hint {
  font-size: 12px;
  color: #b91c1c;
  font-weight: 500;
}
.status-card-box {
  margin-top: 10px;
  padding: 12px 14px;
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.pending-box {
  background: #fffbeb;
  border: 1px solid #fef3c7;
}
.success-box {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
}
.status-card-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 700;
  color: #1e293b;
}
.status-card-desc {
  font-size: 12px;
  color: #475569;
  margin: 0;
  line-height: 1.45;
}
.icon-warning { color: #d97706; }
.icon-success { color: #16a34a; }
.btn-view-proof {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: #ffffff;
  border: 1px solid #86efac;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  color: #15803d;
  text-decoration: none;
  transition: all 0.15s ease;
}
.btn-view-proof:hover {
  background: #dcfce7;
  border-color: #4ade80;
  color: #166534;
}
.btn-proof-group {
  display: flex;
  align-items: center;
  gap: 8px;
}
.btn-proof-download {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: #166534;
  border: 1px solid #15803d;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  color: #ffffff;
  text-decoration: none;
  transition: all 0.15s ease;
}
.btn-proof-download:hover {
  background: #14532d;
  border-color: #166534;
  color: #ffffff;
}
.btn-preview-toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  color: #475569;
  cursor: pointer;
  transition: all 0.15s ease;
  font-family: inherit;
}
.btn-preview-toggle:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
  color: #1e293b;
}
.proof-preview-box {
  margin-top: 10px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #d1d5db;
  background: #f9fafb;
}
.proof-image-box {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 12px;
}
.proof-image {
  max-width: 100%;
  max-height: 360px;
  border-radius: 6px;
  object-fit: contain;
}
.proof-pdf-box {
  width: 100%;
  height: 450px;
}
.proof-iframe {
  width: 100%;
  height: 100%;
  border: none;
}
.proof-generic-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 28px 16px;
}
.proof-generic-icon {
  color: #6b7280;
}
.proof-generic-name {
  font-size: 13px;
  color: #374151;
  font-weight: 500;
  word-break: break-all;
  text-align: center;
}
.uploaded-proof-ket {
  margin-top: 8px;
  font-size: 13px;
  color: #374151;
  background: rgba(255, 255, 255, 0.6);
  padding: 6px 10px;
  border-radius: 6px;
}
.uploaded-proof-ket-label {
  font-weight: 600;
  color: #166534;
}

/* ===== Redesigned Popup / Modal Styles ===== */
.popup-overlay {
  position: fixed; inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000; padding: 20px;
}
.popup {
  background: #fff; border-radius: 16px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  width: 100%; max-width: 520px;
  animation: popupIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  overflow: hidden;
}
@keyframes popupIn {
  from { opacity: 0; transform: translateY(16px) scale(0.96); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.popup-header {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 20px 24px;
  border-bottom: 1px solid #f1f5f9;
  position: relative;
}
.header-icon-box {
  width: 44px; height: 44px; border-radius: 12px;
  background: #eff6ff; color: #2563eb;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.header-text { flex: 1; }
.header-text h3 { font-size: 18px; font-weight: 700; margin: 0; color: #0f172a; }
.header-text p { font-size: 13px; color: #64748b; margin: 4px 0 0; }

.popup-close {
  display: flex; align-items: center; justify-content: center;
  width: 32px; height: 32px; border-radius: 8px; border: none;
  background: transparent; color: #94a3b8; cursor: pointer;
  transition: all 0.15s ease;
}
.popup-close:hover { background: #f1f5f9; color: #0f172a; }

.popup-body { padding: 20px 24px; display: flex; flex-direction: column; gap: 16px; }
.popup-footer {
  padding: 16px 24px;
  background: #f8fafc;
  border-top: 1px solid #f1f5f9;
  display: flex; gap: 12px; justify-content: flex-end;
}

.hidden-input { display: none; }

/* ===== Dropzone Component ===== */
.dropzone {
  border: 2px dashed #cbd5e1;
  border-radius: 14px;
  padding: 32px 20px;
  text-align: center;
  background: #fafafa;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}
.dropzone:hover, .dropzone--active {
  border-color: #2563eb;
  background: #eff6ff;
}
.dropzone__icon-circle {
  width: 56px; height: 56px; border-radius: 50%;
  background: #e0e7ff; color: #4338ca;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 4px;
  transition: transform 0.2s ease;
}
.dropzone:hover .dropzone__icon-circle {
  transform: translateY(-2px);
}
.dropzone__title {
  font-size: 14px; font-weight: 500; color: #334155;
}
.dropzone__title .highlight {
  color: #2563eb; font-weight: 600; text-decoration: underline;
}
.dropzone__hint { font-size: 12px; color: #64748b; }
.dropzone__badge {
  display: inline-block;
  margin-top: 4px;
  padding: 4px 10px;
  background: #e2e8f0;
  color: #475569;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
}

/* ===== Review Section ===== */
.review-section { display: flex; flex-direction: column; gap: 16px; }

.review-header {
  display: flex; justify-content: space-between; align-items: center;
}
.review-title {
  display: flex; align-items: center; gap: 8px;
  font-weight: 600; font-size: 15px; color: #0f172a;
}
.text-primary { color: #2563eb; }

/* Alert Box */
.alert-box {
  display: flex; gap: 12px; align-items: flex-start;
  padding: 12px 16px; border-radius: 10px; font-size: 13px;
}
.alert-box--danger {
  background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b;
}
.alert-icon { flex-shrink: 0; color: #dc2626; margin-top: 2px; }
.alert-content p { margin: 2px 0 0; font-size: 12px; opacity: 0.9; }

/* Preview Box */
.preview-container {
  width: 100%;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 140px;
  max-height: 220px;
}

.image-preview-wrapper {
  width: 100%;
  max-height: 220px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
}
.image-preview {
  max-width: 100%;
  max-height: 204px;
  object-fit: contain;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.pdf-preview-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 24px;
  text-align: center;
}
.pdf-icon-box {
  width: 64px; height: 64px; border-radius: 14px;
  background: #fef2f2; color: #dc2626;
  display: flex; align-items: center; justify-content: center;
}
.pdf-info { display: flex; flex-direction: column; gap: 4px; }
.pdf-badge {
  font-size: 11px; font-weight: 700; color: #dc2626; letter-spacing: 0.5px;
}
.pdf-filename {
  font-size: 13px; font-weight: 600; color: #1e293b; max-width: 320px;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

.generic-file-preview {
  display: flex; flex-direction: column; align-items: center; gap: 8px;
  padding: 24px; color: #64748b; font-size: 13px;
}

/* File Metadata Card */
.file-meta-card {
  padding: 14px 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.file-meta-row {
  display: flex; justify-content: space-between; align-items: center; gap: 12px;
}
.file-meta-info { overflow: hidden; }
.file-meta-name {
  font-size: 14px; font-weight: 600; color: #0f172a;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.file-meta-sub { font-size: 12px; color: #64748b; margin-top: 2px; }
.text-danger { color: #dc2626; }

.btn-remove-file {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 6px 12px; border-radius: 8px; border: 1px solid #cbd5e1;
  background: #ffffff; color: #475569; font-size: 12px; font-weight: 500;
  cursor: pointer; transition: all 0.15s ease; flex-shrink: 0;
}
.btn-remove-file:hover {
  background: #f1f5f9; color: #dc2626; border-color: #fca5a5;
}

/* Size Meter Bar */
.size-meter { display: flex; flex-direction: column; gap: 4px; }
.size-meter__labels {
  display: flex; justify-content: space-between; font-size: 11px; color: #64748b;
}
.size-meter__bar {
  height: 6px; width: 100%; background: #e2e8f0; border-radius: 3px; overflow: hidden;
}
.size-meter__fill {
  height: 100%; background: #2563eb; transition: width 0.3s ease, background 0.3s ease;
}
.size-meter__fill--over {
  background: #dc2626;
}

/* Form inputs & hints */
.form-field { display: flex; flex-direction: column; gap: 6px; }
.form-label { font-size: 13px; font-weight: 600; color: #334155; }
.form-label .optional { font-weight: 400; color: #94a3b8; }
.form-input { padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: #1e293b; outline: none; }
.form-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.form-textarea { padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; color: #1e293b; outline: none; resize: vertical; font-family: inherit; }
.form-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.form-select { width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: #1e293b; outline: none; background: #fff; cursor: pointer; }
.form-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.form-hint { font-size: 12px; color: #94a3b8; margin: 0; }

.discount-total-info { padding: 10px 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; }
.discount-total-label { font-size: 13px; color: #374151; }
.discount-total-value { font-size: 15px; font-weight: 700; color: #166534; }

.preview-box {
  padding: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;
}
.preview-row {
  display: flex; justify-content: space-between; font-size: 14px; color: #475569; padding: 2px 0;
}
.preview-row--sisa {
  font-weight: 700; color: #1e293b;
  padding-top: 4px; margin-top: 4px; border-top: 1px solid #bbf7d0;
}

.spinner {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
