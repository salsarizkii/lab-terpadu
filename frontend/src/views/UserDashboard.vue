<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import Button from '../components/common/Button.vue'
import LoadingSpinner from '../components/common/LoadingSpinner.vue'
import { beritaAcaraApi } from '../api/beritaAcara'
import type { BeritaAcara } from '../types'

const router = useRouter()
const items = ref<BeritaAcara[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    items.value = await beritaAcaraApi.getList()
  } catch {
  } finally {
    loading.value = false
  }
})

const latestBa = computed(() => items.value[0])

const steps = computed(() => {
  const ba = latestBa.value
  return [
    {
      key: 'pengajuan',
      label: 'Pengajuan',
      desc: 'Buat & ajukan berita acara penelitian',
      icon: 'file',
      status: !ba ? 'inactive' : (ba.status === 'draft' || ba.status === 'pending' || ba.status === 'revision') ? 'active' : 'done',
      badge: ba ? (ba.status === 'revision' ? 'Perlu Revisi' : ba.status === 'pending' ? 'Menunggu' : ba.status === 'approved' ? 'Disetujui' : 'Draft') : '',
      badgeType: ba ? (ba.status === 'revision' ? 'warning' : ba.status === 'pending' ? 'warning' : ba.status === 'approved' ? 'success' : 'default') : 'default',
      action: !ba ? 'Buat Baru' : (ba.status === 'revision' || ba.status === 'draft') ? 'Lanjutkan' : 'Lihat',
      route: !ba ? '/user/berita-acara/create' : `/user/berita-acara/${ba.id}`,
    },
    {
      key: 'pembayaran',
      label: 'Pembayaran',
      desc: 'Lakukan pembayaran & ajukan diskon',
      icon: 'payment',
      status: !ba || ba.status !== 'approved' ? 'inactive' : (!ba.payment) ? 'active' : (ba.payment.status === 'approved') ? 'done' : 'active',
      badge: ba?.payment ? (ba.payment.status === 'approved' ? 'Lunas' : ba.payment.status === 'rejected' ? 'Ditolak' : 'Menunggu') : '',
      badgeType: ba?.payment ? (ba.payment.status === 'approved' ? 'success' : ba.payment.status === 'rejected' ? 'danger' : 'warning') : 'default',
      action: ba && ba.status === 'approved' && (!ba.payment || ba.payment.status !== 'approved') ? 'Bayar' : null,
      route: ba && ba.status === 'approved' ? `/user/berita-acara/${ba.id}` : null,
    },
    {
      key: 'pengambilan',
      label: 'Pengambilan',
      desc: 'Sampel siap diambil di lab',
      icon: 'sample',
      status: !ba || !ba.samples || ba.samples.length === 0 ? 'inactive' : (ba.samples.some(s => s.status === 'ready' || s.status === 'taken')) ? 'done' : (ba.samples.some(s => s.status === 'processing')) ? 'active' : 'inactive',
      badge: ba && ba.samples && ba.samples.some(s => s.status === 'ready') ? 'Siap Diambil' : (ba && ba.samples && ba.samples.some(s => s.status === 'taken') ? 'Sudah Diambil' : ''),
      badgeType: ba && ba.samples && ba.samples.some(s => s.status === 'ready') ? 'success' : (ba && ba.samples && ba.samples.some(s => s.status === 'taken') ? 'info' : 'default'),
      action: ba && ba.samples && ba.samples.some(s => s.status === 'ready') ? 'Lihat' : null,
      route: ba ? `/user/berita-acara/${ba.id}` : null,
    },
    {
      key: 'sertifikat',
      label: 'Sertifikat',
      desc: 'Unduh sertifikat hasil penelitian',
      icon: 'certificate',
      status: ba && ba.certificate ? 'done' : 'inactive',
      badge: ba?.certificate ? (ba.certificate.downloaded_at ? 'Sudah Diunduh' : 'Tersedia') : '',
      badgeType: ba?.certificate ? (ba.certificate.downloaded_at ? 'info' : 'success') : 'default',
      action: ba?.certificate ? 'Unduh' : null,
      route: ba?.certificate ? `/user/berita-acara/${ba.id}` : null,
    },
  ]
})

const activeCount = computed(() => items.value.filter(i => i.status !== 'draft').length)
const approvedCount = computed(() => items.value.filter(i => i.status === 'approved').length)
const readyCount = computed(() => items.value.filter(i => i.samples?.some(s => s.status === 'ready')).length || 0)
const certCount = computed(() => items.value.filter(i => i.certificate).length || 0)
</script>

<template>
  <AppLayout>
    <div class="dashboard">
      <div class="dashboard-header">
        <div>
          <h1>Dashboard</h1>
          <p>Selamat datang di sistem pemesanan sampel penelitian</p>
        </div>
        <Button @click="router.push('/user/berita-acara/create')">
          + Buat Berita Acara
        </Button>
      </div>

      <LoadingSpinner v-if="loading" size="lg" />

      <template v-else>
        <div class="stats">
          <Card class="stat-card" hover @click="router.push('/user/berita-acara')">
            <div class="stat-value">{{ activeCount }}</div>
            <div class="stat-label">Total Pengajuan</div>
          </Card>
          <Card class="stat-card" hover @click="router.push('/user/payments')">
            <div class="stat-value">{{ approvedCount }}</div>
            <div class="stat-label">Disetujui</div>
          </Card>
          <Card class="stat-card" hover>
            <div class="stat-value">{{ readyCount }}</div>
            <div class="stat-label">Sampel Siap</div>
          </Card>
          <Card class="stat-card" hover @click="router.push('/user/certificates')">
            <div class="stat-value">{{ certCount }}</div>
            <div class="stat-label">Sertifikat</div>
          </Card>
        </div>

        <Card title="Alur Pemesanan" subtitle="Status pengajuan terakhir Anda" padding="lg">
          <div v-if="items.length === 0" class="empty-state">
            <div class="empty-icon">
              <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                <rect width="48" height="48" rx="12" fill="var(--color-accent-soft)"/>
                <path d="M24 14V34M14 24H34" stroke="var(--color-accent)" stroke-width="2.5" stroke-linecap="round"/>
              </svg>
            </div>
            <h3>Belum ada pengajuan</h3>
            <p>Mulai dengan membuat berita acara penelitian Anda</p>
            <Button @click="router.push('/user/berita-acara/create')">Buat Berita Acara</Button>
          </div>

          <div v-else class="flow">
            <div v-for="(step, idx) in steps" :key="step.key" class="flow__item" :class="`flow__item--${step.status}`">
              <div class="flow__connector">
                <div class="flow__dot">
                  <svg v-if="step.status === 'done'" width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <path d="M4.5 9L7.5 12L13.5 6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <svg v-else-if="step.key === 'pengajuan'" width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <path d="M9 2L3 7V15H15V7L9 2Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                  </svg>
                  <svg v-else-if="step.key === 'pembayaran'" width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <rect x="1" y="4" width="16" height="10" rx="2" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M1 7H17" stroke="currentColor" stroke-width="1.5"/>
                    <circle cx="9" cy="10" r="2" fill="currentColor"/>
                  </svg>
                  <svg v-else-if="step.key === 'pengambilan'" width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <path d="M9 1L2 5.5V12.5L9 17L16 12.5V5.5L9 1Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                  </svg>
                  <svg v-else width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <path d="M9 1L11.5 6.5L17.5 7.5L13 12L14 18L9 15L4 18L5 12L0.5 7.5L6.5 6.5L9 1Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                  </svg>
                </div>
                <div v-if="idx < steps.length - 1" class="flow__line" :class="{ 'flow__line--active': step.status === 'done' }" />
              </div>
              <div class="flow__content">
                <div class="flow__top">
                  <div>
                    <h3 class="flow__label">{{ step.label }}</h3>
                    <p class="flow__desc">{{ step.desc }}</p>
                  </div>
                  <Badge v-if="step.badge" :type="step.badgeType" :text="step.badge" />
                </div>
                <div class="flow__actions">
                  <Button
                    v-if="step.action"
                    :variant="step.status === 'done' ? 'outline' : 'primary'"
                    size="sm"
                    @click="step.route && router.push(step.route)"
                  >
                    {{ step.action }}
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </Card>

        <div v-if="items.length > 1" class="history-section">
          <Card title="Riwayat Pengajuan" subtitle="Semua berita acara yang pernah diajukan">
            <div class="history-list">
              <div
                v-for="item in items"
                :key="item.id"
                class="history-item"
                @click="router.push(`/user/berita-acara/${item.id}`)"
              >
                <div class="history-left">
                  <span class="history-title">{{ item.judul_penelitian || 'Tanpa Judul' }}</span>
                  <span class="history-date">{{ new Date(item.created_at).toLocaleDateString('id-ID') }}</span>
                </div>
                <Badge
                  :type="item.status === 'approved' ? 'success' : item.status === 'rejected' ? 'danger' : item.status === 'revision' ? 'warning' : 'default'"
                  :text="item.status"
                />
              </div>
            </div>
          </Card>
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<style scoped>
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: var(--spacing-lg);
}
.dashboard-header h1 {
  font-size: 26px;
  font-weight: 700;
  letter-spacing: -0.5px;
  margin: 0;
}
.dashboard-header p {
  font-size: 14px;
  color: var(--color-text-secondary);
  margin-top: 4px;
}
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  margin-bottom: var(--spacing-lg);
}
.stat-card {
  text-align: center;
  cursor: pointer;
}
.stat-value {
  font-size: 32px;
  font-weight: 700;
  color: var(--color-accent);
}
.stat-label {
  font-size: 13px;
  color: var(--color-text-secondary);
  margin-top: 4px;
}

.empty-state {
  text-align: center;
  padding: 48px 20px;
}
.empty-icon {
  margin-bottom: 16px;
  display: flex;
  justify-content: center;
}
.empty-state h3 {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 8px;
}
.empty-state p {
  font-size: 14px;
  color: var(--color-text-secondary);
  margin-bottom: 20px;
}

.flow {
  display: flex;
  flex-direction: column;
  gap: 0;
}
.flow__item {
  display: flex;
  gap: 20px;
  padding: 0 0 8px;
  opacity: 0.5;
  transition: var(--transition);
}
.flow__item--active, .flow__item--done {
  opacity: 1;
}
.flow__connector {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 40px;
  flex-shrink: 0;
}
.flow__dot {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
  transition: var(--transition);
}
.flow__item--inactive .flow__dot {
  background: var(--color-border-light);
  color: var(--color-text-muted);
}
.flow__item--active .flow__dot {
  background: var(--color-accent);
  color: white;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}
.flow__item--done .flow__dot {
  background: var(--color-success);
  color: white;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.flow__line {
  width: 2px;
  flex: 1;
  min-height: 24px;
  background: var(--color-border);
  margin: 4px 0;
}
.flow__line--active {
  background: var(--color-success);
}
.flow__content {
  flex: 1;
  padding-bottom: 24px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.flow__top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}
.flow__label {
  font-size: 15px;
  font-weight: 600;
  margin: 0;
}
.flow__desc {
  font-size: 13px;
  color: var(--color-text-secondary);
  margin-top: 2px;
}
.flow__actions {
  display: flex;
  gap: 8px;
}

.history-section {
  margin-top: var(--spacing-lg);
}
.history-list {
  display: flex;
  flex-direction: column;
}
.history-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid var(--color-border-light);
  cursor: pointer;
  transition: var(--transition);
}
.history-item:last-child {
  border-bottom: none;
}
.history-item:hover {
  padding-left: 8px;
}
.history-left {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.history-title {
  font-size: 14px;
  font-weight: 500;
  color: var(--color-text);
}
.history-date {
  font-size: 12px;
  color: var(--color-text-muted);
}
</style>
