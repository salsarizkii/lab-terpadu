<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import LoadingSpinner from '../components/common/LoadingSpinner.vue'
import FlowSteps from '../components/user/FlowSteps.vue'
import { PlusSquare } from '@lucide/vue'
import { beritaAcaraApi } from '../api/beritaAcara'
import type { BeritaAcara } from '../types'

const router = useRouter()
const items = ref<BeritaAcara[]>([])
const loading = ref(true)
const showFlowModal = ref(false)
const flowBa = ref<BeritaAcara | null>(null)

function openFlow(ba: BeritaAcara) {
  flowBa.value = ba
  showFlowModal.value = true
}

onMounted(async () => {
  try {
    items.value = await beritaAcaraApi.getList()
  } catch {
  } finally {
    loading.value = false
  }
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

        <div class="empty-state" v-if="items.length === 0">
          <div class="empty-icon">
            <PlusSquare :size="48" color="var(--color-accent)" stroke-width="1.5" />
          </div>
          <h3>Belum ada pengajuan</h3>
          <p>Mulai dengan membuat berita acara penelitian Anda</p>
          <Button @click="router.push('/user/berita-acara/create')">Buat Berita Acara</Button>
        </div>

        <div v-if="items.length > 0" class="history-section">
          <Card title="Riwayat Pengajuan" subtitle="Semua berita acara yang pernah diajukan">
            <div class="history-list">
              <div
                v-for="item in items"
                :key="item.id"
                class="history-item"
              >
                <div class="history-left" @click="router.push(`/user/berita-acara/${item.id}`)">
                  <span class="history-title">{{ item.judul_penelitian || 'Tanpa Judul' }}</span>
                  <span class="history-date">{{ new Date(item.created_at).toLocaleDateString('id-ID') }}</span>
                </div>
                <div class="history-right">
                  <Badge
                    :type="item.status === 'approved' ? 'success' : item.status === 'rejected' ? 'danger' : item.status === 'revision' ? 'warning' : 'default'"
                    :text="item.status"
                  />
                  <Button size="sm" variant="ghost" @click="openFlow(item)">Lihat Alur</Button>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <Modal :show="showFlowModal" title="Alur Pemesanan" size="lg" @close="showFlowModal = false">
          <FlowSteps v-if="flowBa" :berita-acara="flowBa" />
        </Modal>
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
.history-item:hover .history-left {
  padding-left: 8px;
}
.history-left {
  display: flex;
  flex-direction: column;
  gap: 2px;
  cursor: pointer;
  flex: 1;
}
.history-right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
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
