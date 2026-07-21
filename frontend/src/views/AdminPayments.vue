<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import SkeletonTable from '../components/common/SkeletonTable.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import FilterDropdown from '../components/common/FilterDropdown.vue'
import PaymentConfirmation from '../components/admin/PaymentConfirmation.vue'
import { adminApi } from '../api/admin'
import type { Payment } from '../types'
import { toast } from 'vue-sonner'

const statusOptions = [
  { value: '', label: 'Semua Status' },
  { value: 'pending', label: 'Diajukan' },
  { value: 'approved', label: 'Disetujui' },
  { value: 'rejected', label: 'Ditolak' },
]

const payments = ref<Payment[]>([])
const loading = ref(true)
const filterStatus = ref('')
const selectedPayment = ref<Payment | null>(null)
const showConfirmModal = ref(false)

const statusTypeMap: Record<string, 'default' | 'warning' | 'success' | 'danger' | 'info'> = {
  pending: 'warning',
  approved: 'success',
  rejected: 'danger',
}

const filtered = computed(() => {
  if (!filterStatus.value) return payments.value
  return payments.value.filter(p => p.status === filterStatus.value)
})

onMounted(async () => {
  try {
    payments.value = await adminApi.getPayments()
  } catch {
    toast.error('Gagal memuat data')
  } finally {
    loading.value = false
  }
})

function openConfirm(payment: Payment) {
  selectedPayment.value = payment
  showConfirmModal.value = true
}

async function handleConfirm(data: { status: string; discount_approved?: string }) {
  if (!selectedPayment.value) return
  try {
    const updated = await adminApi.confirmPayment(selectedPayment.value.id, data)
    const idx = payments.value.findIndex(p => p.id === selectedPayment.value!.id)
    if (idx !== -1) payments.value[idx] = updated
    toast.success('Status pembayaran diperbarui')
    showConfirmModal.value = false
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal')
  }
}
</script>

<template>
  <AppLayout>
    <div>
      <div class="page-header">
        <h1>Konfirmasi Pembayaran</h1>
      </div>

      <Card>
        <div class="filter-bar">
          <FilterDropdown
            v-model="filterStatus"
            :options="statusOptions"
            placeholder="Semua Status"
          />
        </div>

        <SkeletonTable v-if="loading" :cols="7" :rows="5" />

        <div v-else class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Pemohon</th>
                <th>Jumlah</th>
                <th>Diskon Diminta</th>
                <th>Alasan</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in filtered" :key="p.id">
                <td>#{{ p.id }}</td>
                <td>{{ (p as any).berita_acara?.user?.name || '-' }}</td>
                <td>Rp {{ Number(p.amount).toLocaleString('id-ID') }}</td>
                <td>{{ p.discount_request ? 'Rp ' + Number(p.discount_request).toLocaleString('id-ID') : '-' }}</td>
                <td class="cell-reason">{{ p.discount_reason || '-' }}</td>
                <td><Badge :type="statusTypeMap[p.status]" :text="p.status === 'approved' ? 'Lunas' : p.status === 'rejected' ? 'Ditolak' : 'Diajukan'" /></td>
                <td>
                  <Button v-if="p.status === 'approved' || p.status === 'rejected'" size="sm" variant="outline" @click="openConfirm(p)">Detail</Button>
                  <Button v-else-if="p.payment_proof" size="sm" variant="primary" @click="openConfirm(p)">Proses</Button>
                  <span v-else class="no-proof">Belum ada bukti</span>
                </td>
              </tr>
              <tr v-if="filtered.length === 0">
                <td colspan="7" class="empty-cell">Tidak ada data</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <Modal :show="showConfirmModal" size="lg" title="Konfirmasi Pembayaran" @close="showConfirmModal = false">
        <PaymentConfirmation v-if="selectedPayment" :payment="selectedPayment" @confirm="handleConfirm" @close="showConfirmModal = false" />
      </Modal>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-header { margin-bottom: 24px; }
.page-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
.filter-bar { margin-bottom: 16px; }
.table-wrap { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; font-size: 14px; }
.table th { text-align: left; padding: 12px; background: #f8fafc; color: #475569; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
.table td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
.empty-cell { text-align: center; color: #94a3b8; padding: 40px; }
.cell-reason { max-width: 200px; white-space: normal; word-break: break-word; font-size: 13px; }
.no-proof { font-size: 12px; color: #94a3b8; }

</style>
