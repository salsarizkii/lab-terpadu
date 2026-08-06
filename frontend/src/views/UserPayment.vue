<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import PageHeader from '../components/common/PageHeader.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import Button from '../components/common/Button.vue'
import SkeletonTable from '../components/common/SkeletonTable.vue'
import { paymentApi } from '../api/payment'
import type { Payment } from '../types'
import { Eye } from '@lucide/vue'
import { formatDateTime } from '../utils/date'

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
      <PageHeader
        title="Riwayat Pembayaran"
        subtitle="Kelola dan pantau status tagihan pengujian laboratorium Anda"
      />

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
                <th>Status Tagihan</th>
                <th>Status Diskon</th>
                <th>Diajukan</th>
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
                <td class="td-date">{{ formatDateTime(p.created_at) }}</td>
                <td class="td-aksi">
                  <Button
                    variant="outline"
                    size="sm"
                    @click="router.push(`/user/payments/${p.id}`)"
                  >
                    <Eye :size="15" style="margin-right: 4px;" />
                    <span>Detail</span>
                  </Button>
                </td>
              </tr>
              <tr v-if="payments.length === 0">
                <td colspan="8" class="empty-cell">Belum ada pembayaran</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  </AppLayout>
</template>

<style scoped>
.table-wrap { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; font-size: 14px; }
.table th {
  text-align: left; padding: 14px 12px; background: #f8fafc;
  color: #475569; font-weight: 600; border-bottom: 2px solid #e2e8f0;
  white-space: nowrap;
}
.table td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; }
.th-no, .td-no { width: 44px; text-align: center; color: #94a3b8; }
.td-title { max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 500; }
.td-amount { font-weight: 600; color: #1e293b; white-space: nowrap; }
.td-discount { white-space: nowrap; }
.td-date { white-space: nowrap; }
.discount-val { font-weight: 600; color: #16a34a; }
.discount-pending { font-size: 12px; color: #d97706; }
.td-empty { color: #94a3b8; font-size: 13px; }

.th-aksi, .td-aksi { width: 100px; text-align: center; }
.empty-cell { text-align: center; color: #94a3b8; padding: 40px; }
</style>
