<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import SkeletonTable from '../components/common/SkeletonTable.vue'
import Button from '../components/common/Button.vue'
import FilterDropdown from '../components/common/FilterDropdown.vue'
import { adminApi } from '../api/admin'
import type { Payment } from '../types'
import { toast } from 'vue-sonner'

const statusOptions = [
  { value: '', label: 'Semua Status' },
  { value: 'pending', label: 'Menunggu' },
  { value: 'approved', label: 'Disetujui' },
  { value: 'rejected', label: 'Ditolak' },
]

const payments = ref<Payment[]>([])
const loading = ref(true)
const filterStatus = ref('')
const processingId = ref<number | null>(null)

const statusTypeMap: Record<string, 'default' | 'warning' | 'success' | 'danger' | 'info'> = {
  pending: 'warning',
  approved: 'success',
  rejected: 'danger',
}

const filtered = computed(() => {
  let list = payments.value.filter(p => p.discount_request)
  if (filterStatus.value) list = list.filter(p => (p as any).discount_status === filterStatus.value)
  return list
})

function formatPrice(val: number) {
  return new Intl.NumberFormat('id-ID').format(Math.round(val))
}

onMounted(async () => {
  try {
    payments.value = await adminApi.getPayments()
  } catch {
    toast.error('Gagal memuat data')
  } finally {
    loading.value = false
  }
})

async function handleApprove(payment: Payment) {
  processingId.value = payment.id
  try {
    const discountAmount = Number(payment.discount_request || 0)
    await adminApi.confirmDiscount(payment.berita_acara_id, {
      status: 'approved',
      discount_approved: discountAmount,
    })
    const idx = payments.value.findIndex(p => p.id === payment.id)
    if (idx !== -1) payments.value[idx] = { ...payments.value[idx], discount_status: 'approved', discount_approved: discountAmount } as any
    toast.success('Diskon disetujui')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menyetujui diskon')
  } finally {
    processingId.value = null
  }
}

async function handleReject(payment: Payment) {
  processingId.value = payment.id
  try {
    await adminApi.confirmDiscount(payment.berita_acara_id, {
      status: 'rejected',
    })
    const idx = payments.value.findIndex(p => p.id === payment.id)
    if (idx !== -1) payments.value[idx] = { ...payments.value[idx], discount_status: 'rejected' } as any
    toast.success('Diskon ditolak')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menolak diskon')
  } finally {
    processingId.value = null
  }
}
</script>

<template>
  <AppLayout>
    <div>
      <div class="page-header">
        <h1>Ajuan Diskon</h1>
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
                <th>Total Biaya</th>
                <th>Diskon Diminta</th>
                <th>Alasan</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in filtered" :key="p.id">
                <td>#{{ p.berita_acara_id }}</td>
                <td>{{ (p as any).berita_acara?.user?.name || '-' }}</td>
                <td>Rp {{ formatPrice(Number(p.amount || 0)) }}</td>
                <td>Rp {{ formatPrice(Number(p.discount_request || 0)) }}</td>
                <td class="cell-reason">{{ p.discount_reason || '-' }}</td>
                <td><Badge :type="statusTypeMap[(p as any).discount_status]" :text="(p as any).discount_status === 'approved' ? 'Disetujui' : (p as any).discount_status === 'rejected' ? 'Ditolak' : 'Menunggu'" /></td>
                <td>
                  <template v-if="(p as any).discount_status === 'pending'">
                    <div class="action-btns">
                      <Button size="sm" :disabled="processingId === p.id" @click="handleApprove(p)">Setujui</Button>
                      <Button size="sm" variant="outline" :disabled="processingId === p.id" @click="handleReject(p)">Tolak</Button>
                    </div>
                  </template>
                  <span v-else class="text-muted">-</span>
                </td>
              </tr>
              <tr v-if="filtered.length === 0">
                <td colspan="7" class="empty-cell">Tidak ada ajuan diskon</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
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
.action-btns { display: flex; gap: 6px; }
.text-muted { color: #94a3b8; }
</style>
