<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import SkeletonTable from '../components/common/SkeletonTable.vue'
import { paymentApi } from '../api/payment'
import type { Payment } from '../types'

const router = useRouter()
const payments = ref<Payment[]>([])
const loading = ref(true)

const statusTypeMap: Record<string, 'default' | 'warning' | 'success' | 'danger' | 'info'> = {
  pending: 'warning',
  approved: 'success',
  rejected: 'danger',
}

const statusTextMap: Record<string, string> = {
  pending: 'Diajukan',
  approved: 'Lunas',
  rejected: 'Ditolak',
}

function getJudul(p: Payment) {
  const ba = p.berita_acara as any
  if (!ba) return '-'
  if (ba.judul_penelitian) return ba.judul_penelitian
  if (ba.form_data) {
    const values = Object.values(ba.form_data)
    for (const v of values) {
      if (typeof v === 'string' && v.length > 5) return v
    }
  }
  return '-'
}

function getDiscountStatus(p: Payment) {
  const ds = p.discount_status
  if (!ds) return null
  if (ds === 'approved') return { label: 'Diterima', type: 'success' as const }
  if (ds === 'rejected') return { label: 'Ditolak', type: 'danger' as const }
  if (ds === 'pending') return { label: 'Diproses', type: 'warning' as const }
  return null
}

function formatPrice(val: number) {
  return new Intl.NumberFormat('id-ID').format(Math.round(val))
}

onMounted(async () => {
  try {
    payments.value = await paymentApi.getList()
  } catch {
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <AppLayout>
    <div>
      <div class="page-header">
        <h1>Riwayat Pembayaran</h1>
      </div>

      <Card>
        <SkeletonTable v-if="loading" :cols="6" :rows="5" />

        <div v-else class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th class="th-no">No</th>
                <th>Judul Penelitian</th>
                <th>Jumlah</th>
                <th>Diskon</th>
                <th>Status</th>
                <th>Status Diskon</th>
                <th class="th-aksi">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(p, idx) in payments" :key="p.id">
                <td class="td-no">{{ idx + 1 }}</td>
                <td class="td-title">{{ getJudul(p) }}</td>
                <td class="td-amount">Rp {{ formatPrice(Number(p.amount || 0)) }}</td>
                <td class="td-discount">
                  <span v-if="p.discount_approved" class="discount-val">-Rp {{ formatPrice(Number(p.discount_approved)) }}</span>
                  <span v-else-if="p.discount_request" class="discount-pending">Rp {{ formatPrice(Number(p.discount_request)) }} (diajukan)</span>
                  <span v-else class="td-empty">-</span>
                </td>
                <td>
                  <Badge :type="statusTypeMap[p.status]" :text="statusTextMap[p.status] || p.status" />
                </td>
                <td>
                  <Badge v-if="getDiscountStatus(p)" :type="getDiscountStatus(p)!.type" :text="getDiscountStatus(p)!.label" />
                  <span v-else class="td-empty">-</span>
                </td>
                <td class="td-aksi">
                  <button class="eye-btn" @click="router.push(`/user/payments/${p.id}`)" title="Lihat Detail">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </td>
              </tr>
              <tr v-if="payments.length === 0">
                <td colspan="7" class="empty-cell">Belum ada pembayaran</td>
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

.table-wrap { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; font-size: 14px; }
.table th {
  text-align: left; padding: 12px; background: #f8fafc;
  color: #475569; font-weight: 600; border-bottom: 2px solid #e2e8f0;
  white-space: nowrap;
}
.table td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; }
.th-no, .td-no { width: 40px; text-align: center; color: #94a3b8; }
.td-title { max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.td-amount { font-weight: 600; color: #1e293b; white-space: nowrap; }
.td-discount { white-space: nowrap; }
.discount-val { font-weight: 500; color: #16a34a; }
.discount-pending { font-size: 12px; color: #d97706; }
.td-empty { color: #94a3b8; font-size: 13px; }

.th-aksi, .td-aksi { width: 50px; text-align: center; }
.eye-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 32px; height: 32px; border: 1.5px solid #e2e8f0; border-radius: 8px;
  background: #fff; color: #64748b; cursor: pointer; transition: all 0.15s;
}
.eye-btn:hover { background: #f1f5f9; border-color: #cbd5e1; color: #1e293b; }

.empty-cell { text-align: center; color: #94a3b8; padding: 40px; }
</style>
