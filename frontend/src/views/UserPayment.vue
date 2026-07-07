<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import SkeletonCard from '../components/common/SkeletonCard.vue'
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

      <SkeletonCard v-if="loading" />

      <div v-else class="payment-list">
        <Card v-for="p in payments" :key="p.id" class="payment-card" @click="router.push(`/user/payments/${p.id}`)">
          <div class="payment-item">
            <div>
              <div class="payment-amount">Rp {{ Number(p.amount).toLocaleString('id-ID') }}</div>
              <div class="payment-meta">
                  <span v-if="p.discount_request">Diskon diminta: Rp {{ Number(p.discount_request).toLocaleString('id-ID') }}</span>
                  <span v-if="p.discount_reason">Alasan: {{ p.discount_reason }}</span>
                  <span v-if="p.discount_approved">Diskon disetujui: Rp {{ Number(p.discount_approved).toLocaleString('id-ID') }}</span>
              </div>
            </div>
            <Badge :type="statusTypeMap[p.status]" :text="p.status" />
          </div>
        </Card>
        <div v-if="payments.length === 0" class="empty">Belum ada pembayaran</div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-header { margin-bottom: 24px; }
.page-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
.payment-list { display: flex; flex-direction: column; gap: 16px; }
.payment-card { cursor: pointer; transition: border-color 0.2s; }
.payment-card:hover { border-color: var(--color-accent); }
.payment-item { display: flex; justify-content: space-between; align-items: center; }
.payment-amount { font-size: 18px; font-weight: 600; color: #1e293b; }
.payment-meta { font-size: 13px; color: #64748b; margin-top: 4px; }
.empty { text-align: center; padding: 60px; color: #94a3b8; }
</style>
