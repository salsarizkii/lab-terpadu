<script setup lang="ts">
import { ref, computed } from 'vue'
import Button from '../common/Button.vue'
import type { Payment } from '../../types'
import { toast } from 'vue-sonner'
import {
  CheckCircle2,
  XCircle,
  FileText,
  RefreshCw,
  ExternalLink,
  Download,
  CreditCard,
  User,
  MessageSquare,
  Calculator,
  AlertCircle
} from '@lucide/vue'

const props = defineProps<{
  payment: Payment
}>()

const emit = defineEmits<{
  confirm: [data: { status: string; discount_approved?: string; rejection_reason?: string }]
  close: []
}>()

const submittingStatus = ref<string | null>(null)
const showRejectField = ref(false)
const rejectionReason = ref('')
const rejectionError = ref('')

function formatPrice(val: number | string) {
  return new Intl.NumberFormat('id-ID').format(Math.round(Number(val)))
}

function getStorageUrl(path?: string) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  const cleanPath = path.replace(/^public\//, '')
  return `http://localhost:8000/storage/${cleanPath}`
}

const proofUrl = computed(() => getStorageUrl(props.payment.payment_proof))

const isImageFile = computed(() => {
  if (!props.payment.payment_proof) return false
  return /\.(jpg|jpeg|png|webp)$/i.test(props.payment.payment_proof)
})

const isPdfFile = computed(() => {
  if (!props.payment.payment_proof) return false
  return /\.pdf$/i.test(props.payment.payment_proof)
})

const fileName = computed(() => {
  if (!props.payment.payment_proof) return ''
  return props.payment.payment_proof.split('/').pop() || 'Bukti Pembayaran'
})

// Financial Reconciliation Computations
const totalAwal = computed(() => Number(props.payment.amount || 0))
const diskonNominal = computed(() => Number(props.payment.discount_approved || 0))
const totalAkhir = computed(() => Math.max(0, totalAwal.value - diskonNominal.value))

function handleApprove() {
  showRejectField.value = false
  rejectionError.value = ''
  submittingStatus.value = 'approved'
  emit('confirm', {
    status: 'approved',
    discount_approved: props.payment.discount_approved || undefined,
  })
}

function handleReject() {
  if (!showRejectField.value) {
    showRejectField.value = true
    rejectionError.value = 'Wajib mengisikan alasan penolakan pembayaran di bawah ini.'
    toast.error('Silakan isi alasan penolakan pembayaran terlebih dahulu.')
    return
  }

  if (!rejectionReason.value.trim()) {
    rejectionError.value = 'Alasan penolakan pembayaran wajib diisi!'
    toast.error('Alasan penolakan pembayaran wajib diisi!')
    return
  }

  rejectionError.value = ''
  submittingStatus.value = 'rejected'
  emit('confirm', {
    status: 'rejected',
    rejection_reason: rejectionReason.value.trim(),
  })
}
</script>

<template>
  <div class="confirm-container">
    <!-- 1. Header Summary Card (Pemohon & Status) -->
    <div class="summary-card">
      <div class="summary-row">
        <div class="summary-item">
          <div class="summary-label">
            <User :size="14" />
            <span>Pemohon</span>
          </div>
          <div class="summary-value">{{ (payment as any).berita_acara?.user?.name || 'User #' + payment.berita_acara_id }}</div>
        </div>
        <div class="summary-item summary-item--right">
          <div class="summary-label">
            <CreditCard :size="14" />
            <span>Status Pengajuan</span>
          </div>
          <div class="summary-status-badge" :class="payment.status">
            {{ payment.status === 'approved' ? 'Lunas' : payment.status === 'rejected' ? 'Ditolak' : 'Menunggu Verifikasi' }}
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Financial Reconciliation Card (Total Harga, Diskon, Total Harus Dibayar) -->
    <div class="reconciliation-card">
      <div class="reconciliation-header">
        <Calculator :size="16" class="text-blue" />
        <span>Rincian & Pencocokan Pembayaran</span>
      </div>
      <div class="reconciliation-body">
        <div class="reconciliation-row">
          <span class="rec-label">Total Harga (Biaya Awal):</span>
          <span class="rec-val">Rp {{ formatPrice(totalAwal) }}</span>
        </div>
        <div v-if="diskonNominal > 0" class="reconciliation-row text-amber">
          <span class="rec-label">Diskon yang Diberikan:</span>
          <span class="rec-val">- Rp {{ formatPrice(diskonNominal) }}</span>
        </div>
        <div class="reconciliation-divider" />
        <div class="reconciliation-row reconciliation-row--total">
          <span class="rec-label-total">Total yang Harus Dibayar:</span>
          <span class="rec-val-total">Rp {{ formatPrice(totalAkhir) }}</span>
        </div>
      </div>
    </div>

    <!-- 3. Catatan Pembeli / Pengirim (Dipindah tepat di bawah Total yang Harus Dibayar) -->
    <div v-if="payment.keterangan" class="keterangan-box">
      <div class="keterangan-label">
        <MessageSquare :size="14" />
        <span>Catatan Pembeli / Pengirim:</span>
      </div>
      <div class="keterangan-text">{{ payment.keterangan }}</div>
    </div>

    <!-- 4. Uploaded Proof Section with Active Preview & Download Button -->
    <div class="proof-section">
      <div class="proof-header">
        <span class="proof-title">Bukti Transfer / Pembayaran</span>
        <div class="proof-header-actions">
          <a
            v-if="proofUrl"
            :href="proofUrl"
            download
            target="_blank"
            rel="noopener noreferrer"
            class="btn-download"
            title="Download Bukti Pembayaran"
          >
            <Download :size="14" />
            <span>Download</span>
          </a>
          <a
            v-if="proofUrl"
            :href="proofUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="btn-open-external"
            title="Buka di Tab Baru"
          >
            <ExternalLink :size="14" />
            <span>Buka Tab Baru</span>
          </a>
        </div>
      </div>

      <!-- Active Preview Container (Image / PDF embed / Generic) -->
      <div class="proof-preview-box">
        <!-- Image Preview -->
        <div v-if="isImageFile" class="image-box">
          <img :src="proofUrl" alt="Bukti Pembayaran" class="proof-image" />
        </div>

        <!-- Live Embedded PDF Preview -->
        <div v-else-if="isPdfFile" class="pdf-container">
          <iframe :src="proofUrl" class="pdf-embed-object" title="Bukti PDF"></iframe>
        </div>

        <!-- Fallback Generic File Preview -->
        <div v-else class="generic-box">
          <FileText :size="40" class="generic-icon" />
          <span class="generic-name">{{ fileName }}</span>
        </div>
      </div>
    </div>

    <!-- Rejection reason display if payment was rejected -->
    <div v-if="payment.rejection_reason" class="keterangan-box reject-reason-display">
      <div class="keterangan-label text-danger">
        <XCircle :size="14" />
        <span>Alasan Penolakan Admin:</span>
      </div>
      <div class="keterangan-text">{{ payment.rejection_reason }}</div>
    </div>

    <!-- Field Alasan Penolakan Pembayaran (Wajib Jika Ditolak & Status Pending) -->
    <div v-if="showRejectField && payment.status === 'pending'" class="reject-reason-box">
      <label class="reject-label">
        <AlertCircle :size="16" class="icon-danger" />
        <span>Alasan Penolakan Pembayaran <span class="required-tag">*Wajib Isi</span></span>
      </label>
      <textarea
        v-model="rejectionReason"
        class="reject-textarea"
        :class="{ 'reject-textarea--error': !!rejectionError }"
        placeholder="Tuliskan alasan penolakan (Contoh: Nominal transfer kurang / Resi tidak terbaca / Nama rekening tidak sesuai)"
        rows="3"
      />
      <span v-if="rejectionError" class="error-hint">{{ rejectionError }}</span>
    </div>

    <!-- 5. Action Buttons: Detail Mode (Tutup) vs Pending Mode (Approve/Reject) -->
    <div v-if="payment.status !== 'pending'" class="confirm-actions">
      <Button variant="outline" @click="emit('close')">Tutup</Button>
    </div>

    <div v-else class="confirm-actions">
      <Button
        variant="outline"
        class="btn-reject"
        :disabled="!!submittingStatus"
        @click="handleReject"
      >
        <RefreshCw v-if="submittingStatus === 'rejected'" class="spinner" :size="16" />
        <XCircle v-else :size="16" />
        <span>{{ showRejectField ? 'Konfirmasi Penolakan' : 'Tolak Pembayaran' }}</span>
      </Button>

      <Button
        variant="primary"
        class="btn-approve"
        :disabled="!!submittingStatus"
        @click="handleApprove"
      >
        <RefreshCw v-if="submittingStatus === 'approved'" class="spinner" :size="16" />
        <CheckCircle2 v-else :size="16" />
        <span>Terima & Konfirmasi</span>
      </Button>
    </div>
  </div>
</template>

<style scoped>
.confirm-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding-top: 4px;
}

/* Summary Card */
.summary-card {
  padding: 14px 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.summary-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.summary-item--right {
  align-items: flex-end;
}
.summary-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 500;
  color: #64748b;
}
.summary-value {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}
.summary-status-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
  background: #fef3c7;
  color: #d97706;
}
.summary-status-badge.approved {
  background: #dcfce7;
  color: #15803d;
}
.summary-status-badge.rejected {
  background: #fee2e2;
  color: #b91c1c;
}

/* Reconciliation Card */
.reconciliation-card {
  padding: 14px 16px;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.reconciliation-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 700;
  color: #0369a1;
}
.text-blue { color: #0284c7; }

.reconciliation-body {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.reconciliation-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 13px;
  color: #334155;
}
.rec-label { color: #475569; }
.rec-val { font-weight: 600; color: #0f172a; }
.reconciliation-divider {
  height: 1px;
  background: #bae6fd;
  margin: 2px 0;
}
.reconciliation-row--total {
  padding-top: 4px;
}
.rec-label-total {
  font-size: 14px;
  font-weight: 700;
  color: #0c4a6e;
}
.rec-val-total {
  font-size: 18px;
  font-weight: 800;
  color: #0284c7;
}

/* Catatan Pembeli / Pengirim */
.keterangan-box {
  padding: 12px 16px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.keterangan-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 700;
  color: #1d4ed8;
}
.keterangan-text {
  font-size: 14px;
  font-weight: 500;
  color: #1e293b;
}

/* Proof Section */
.proof-section {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.proof-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.proof-title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}
.proof-header-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}
.btn-download {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 8px;
  background: #2563eb;
  color: #ffffff;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.15s ease;
}
.btn-download:hover {
  background: #1d4ed8;
}
.btn-open-external {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  color: #475569;
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.15s ease;
}
.btn-open-external:hover {
  background: #f1f5f9;
  color: #0f172a;
}

/* Proof Preview Container */
.proof-preview-box {
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 12px;
  background: #0f172a;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.image-box {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px;
  background: #0f172a;
  min-height: 220px;
  max-height: 380px;
}
.proof-image {
  max-width: 100%;
  max-height: 360px;
  object-fit: contain;
  border-radius: 8px;
}

.pdf-container {
  width: 100%;
  height: 420px;
  background: #ffffff;
}
.pdf-embed-object {
  width: 100%;
  height: 420px;
  border: none;
  display: block;
}

.generic-box {
  padding: 36px 20px;
  background: #ffffff;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  color: #64748b;
  width: 100%;
}
.generic-icon { color: #2563eb; }
.generic-name { font-size: 14px; font-weight: 600; color: #1e293b; }

/* Rejection Reason Input Box */
.reject-reason-box {
  padding: 14px;
  background: #fef2f2;
  border: 1px solid #fca5a5;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  animation: fadeIn 0.2s ease-out;
}
.reject-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 700;
  color: #991b1b;
}
.icon-danger { color: #dc2626; }
.required-tag {
  font-size: 11px;
  color: #dc2626;
  font-weight: 700;
  margin-left: 4px;
}
.reject-textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #fca5a5;
  border-radius: 8px;
  font-size: 13px;
  color: #1e293b;
  outline: none;
  font-family: inherit;
  resize: vertical;
  background: #ffffff;
}
.reject-textarea:focus {
  border-color: #dc2626;
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15);
}
.reject-textarea--error {
  border-color: #dc2626;
}
.error-hint {
  font-size: 12px;
  color: #dc2626;
  font-weight: 600;
}

/* Actions */
.confirm-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 4px;
  padding-top: 14px;
  border-top: 1px solid #f1f5f9;
}
.btn-reject {
  border-color: #fca5a5 !important;
  color: #dc2626 !important;
}
.btn-reject:hover {
  background: #fef2f2 !important;
}
.btn-approve {
  background: #16a34a !important;
  border-color: #16a34a !important;
  color: #fff !important;
}
.btn-approve:hover {
  background: #15803d !important;
}

.spinner {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-6px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
