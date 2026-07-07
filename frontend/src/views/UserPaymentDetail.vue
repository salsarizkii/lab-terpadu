<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import Button from '../components/common/Button.vue'
import SkeletonDetail from '../components/common/SkeletonDetail.vue'
import { paymentApi } from '../api/payment'
import type { Payment } from '../types'
import { toast } from 'vue-sonner'

const route = useRoute()
const router = useRouter()

const payment = ref<Payment | null>(null)
const loading = ref(true)

const showDiscountModal = ref(false)
const discountRequest = ref('')
const discountReason = ref('')
const submittingDiscount = ref(false)

const showUploadModal = ref(false)
const uploadingProof = ref(false)
const proofFile = ref<File | null>(null)

const discountStatus = computed(() => (payment.value as any)?.discount_status)

const statusTypeMap: Record<string, 'default' | 'warning' | 'success' | 'danger' | 'info'> = {
  pending: 'warning',
  approved: 'success',
  rejected: 'danger',
}

function formatPrice(val: number) {
  return new Intl.NumberFormat('id-ID').format(Math.round(val))
}

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

function openDiscountModal() {
  discountRequest.value = ''
  discountReason.value = ''
  showDiscountModal.value = true
}

async function handleSubmitDiscount() {
  if (!payment.value || !discountRequest.value) return
  submittingDiscount.value = true
  try {
    await paymentApi.requestDiscount(payment.value.id, {
      discount_request: discountRequest.value,
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

function handleProofFileChange(e: Event) {
  const input = e.target as HTMLInputElement
  proofFile.value = input.files?.[0] || null
}

async function handleUploadProof() {
  if (!payment.value || !proofFile.value) return
  uploadingProof.value = true
  try {
    const formData = new FormData()
    formData.append('payment_proof', proofFile.value)
    await paymentApi.uploadProof(payment.value.id, formData)
    const updated = await paymentApi.getById(payment.value.id)
    payment.value = updated
    toast.success('Bukti pembayaran berhasil diupload')
    showUploadModal.value = false
    proofFile.value = null
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
      <div class="page-header">
        <div class="page-header__left">
          <Button variant="outline" @click="router.push('/user/payments')">Kembali</Button>
          <h1>Detail Pembayaran</h1>
        </div>
        <Badge v-if="payment" :type="statusTypeMap[payment.status]" :text="payment.status === 'approved' ? 'Lunas' : payment.status === 'rejected' ? 'Ditolak' : 'Diajukan'" />
      </div>

      <SkeletonDetail v-if="loading" />

      <template v-else-if="payment">
        <div class="detail-grid">
          <div class="detail-main">
            <Card title="Informasi Pembayaran">
              <table class="detail-table">
                <tr><td class="label">Jumlah</td><td>Rp {{ formatPrice(Number(payment.amount || 0)) }}</td></tr>
                <tr v-if="payment.discount_request"><td class="label">Diskon Diminta</td><td>Rp {{ formatPrice(Number(payment.discount_request)) }}</td></tr>
                <tr v-if="payment.discount_reason"><td class="label">Alasan Diskon</td><td>{{ payment.discount_reason }}</td></tr>
                <tr v-if="discountStatus"><td class="label">Status Diskon</td><td><Badge :type="discountStatus === 'approved' ? 'success' : discountStatus === 'rejected' ? 'danger' : 'warning'" :text="discountStatus === 'approved' ? 'Disetujui' : discountStatus === 'rejected' ? 'Ditolak' : 'Menunggu'" /></td></tr>
                <tr v-if="payment.discount_approved"><td class="label">Diskon Disetujui</td><td>Rp {{ formatPrice(Number(payment.discount_approved)) }}</td></tr>
                <tr><td class="label">Status Pembayaran</td><td><Badge :type="statusTypeMap[payment.status]" :text="payment.status === 'approved' ? 'Lunas' : payment.status === 'rejected' ? 'Ditolak' : 'Diajukan'" /></td></tr>
              </table>
            </Card>
          </div>

          <div class="detail-sidebar">
            <Card title="Aksi Pembayaran">
              <template v-if="payment.payment_proof">
                <div class="proof-uploaded">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                  <span>Bukti pembayaran sudah diupload</span>
                </div>
              </template>
              <template v-else>
                <Button block variant="outline" @click="showUploadModal = true">
                  Upload Bukti Pembayaran
                </Button>
              </template>

              <div style="margin-top: 12px;">
                <Button block variant="outline" @click="openDiscountModal">
                  Ajukan Diskon
                </Button>
              </div>
            </Card>
          </div>
        </div>
      </template>

      <Modal :show="showDiscountModal" title="Ajukan Diskon" @close="showDiscountModal = false">
        <div class="modal-form">
          <div class="discount-total-info">
            <span class="discount-total-label">Total biaya saat ini:</span>
            <span class="discount-total-value">Rp {{ formatPrice(Number(payment?.amount || 0)) }}</span>
          </div>
          <div class="form-field">
            <label class="form-label">Jumlah Diskon</label>
            <input v-model="discountRequest" type="number" class="form-input" placeholder="Masukkan jumlah diskon" min="0" />
          </div>
          <div class="form-field">
            <label class="form-label">Alasan Diskon</label>
            <textarea v-model="discountReason" class="form-textarea" placeholder="Masukkan alasan pengajuan diskon..." rows="4" />
          </div>
          <div class="modal-actions">
            <Button variant="outline" @click="showDiscountModal = false">Batal</Button>
            <Button :disabled="submittingDiscount || !discountRequest" @click="handleSubmitDiscount">
              {{ submittingDiscount ? 'Mengirim...' : 'Kirim Pengajuan' }}
            </Button>
          </div>
        </div>
      </Modal>

      <Modal :show="showUploadModal" title="Upload Bukti Pembayaran" @close="showUploadModal = false">
        <div class="modal-form">
          <div class="form-field">
            <label class="form-label">Bukti Pembayaran</label>
            <input type="file" accept="image/*,.pdf" @change="handleProofFileChange" class="form-input" />
          </div>
          <div class="modal-actions">
            <Button variant="outline" @click="showUploadModal = false">Batal</Button>
            <Button :disabled="uploadingProof || !proofFile" @click="handleUploadProof">
              {{ uploadingProof ? 'Mengirim...' : 'Upload' }}
            </Button>
          </div>
        </div>
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

.detail-table { width: 100%; border-collapse: collapse; }
.detail-table tr { border-bottom: 1px solid #f1f5f9; }
.detail-table tr:last-child { border-bottom: none; }
.detail-table td { padding: 12px 0; font-size: 14px; }
.detail-table .label { font-weight: 500; color: #64748b; width: 160px; }

.proof-uploaded { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #16a34a; font-weight: 500; }

.modal-form { display: flex; flex-direction: column; gap: 16px; }
.discount-total-info { padding: 10px 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; }
.discount-total-label { font-size: 13px; color: #374151; }
.discount-total-value { font-size: 15px; font-weight: 700; color: #166534; }
.form-field { display: flex; flex-direction: column; gap: 6px; }
.form-label { font-size: 14px; font-weight: 500; color: #374151; }
.form-input { padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: #1e293b; outline: none; }
.form-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.form-textarea { padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: #1e293b; outline: none; resize: vertical; }
.form-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.modal-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 8px; }
</style>
