<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import LoadingSpinner from '../components/common/LoadingSpinner.vue'
import Button from '../components/common/Button.vue'
import { adminApi } from '../api/admin'
import type { BeritaAcara } from '../types'

const router = useRouter()
const items = ref<BeritaAcara[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    items.value = await adminApi.getBeritaAcara()
  } catch {
  } finally {
    loading.value = false
  }
})

const pendingCount = ref(0)
const approvedCount = ref(0)
const revisionCount = ref(0)
</script>

<template>
  <AppLayout>
    <div class="dashboard">
      <div class="dashboard-header">
        <h1>Dashboard Admin</h1>
      </div>

      <LoadingSpinner v-if="loading" size="lg" />

      <template v-else>
        <div class="stats">
          <Card class="stat-card">
            <div class="stat-value">{{ items.filter(i => i.status === 'pending').length }}</div>
            <div class="stat-label">Menunggu Verifikasi</div>
          </Card>
          <Card class="stat-card">
            <div class="stat-value">{{ items.filter(i => i.status === 'approved').length }}</div>
            <div class="stat-label">Disetujui</div>
          </Card>
          <Card class="stat-card">
            <div class="stat-value">{{ items.filter(i => i.status === 'revision').length }}</div>
            <div class="stat-label">Perlu Revisi</div>
          </Card>
          <Card class="stat-card">
            <div class="stat-value">{{ items.filter(i => i.status === 'rejected').length }}</div>
            <div class="stat-label">Ditolak</div>
          </Card>
        </div>

        <div class="quick-actions">
          <Card title="Aksi Cepat">
            <div class="action-buttons">
              <Button @click="router.push('/admin/berita-acara')">Verifikasi Berita Acara</Button>
              <Button variant="outline" @click="router.push('/admin/payments')">Konfirmasi Pembayaran</Button>
              <Button variant="outline" @click="router.push('/admin/samples')">Status Sampel</Button>
            </div>
          </Card>
        </div>

        <Card title="Berita Acara Menunggu">
          <div v-if="items.filter(i => i.status === 'pending').length === 0" class="empty">Tidak ada yang menunggu</div>
          <div v-else class="ba-mini-list">
            <div v-for="item in items.filter(i => i.status === 'pending').slice(0, 5)" :key="item.id" class="ba-mini-item" @click="router.push(`/admin/berita-acara`)" style="cursor:pointer">
              <span class="ba-mini-title">{{ item.judul_penelitian || 'Tanpa Judul' }}</span>
              <span class="ba-mini-user">{{ item.user?.name || '-' }}</span>
            </div>
          </div>
        </Card>
      </template>
    </div>
  </AppLayout>
</template>

<style scoped>
.dashboard-header { margin-bottom: 24px; }
.dashboard-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
.stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px; }
.stat-card { text-align: center; }
.stat-value { font-size: 32px; font-weight: 700; color: #1e3a5f; }
.stat-label { font-size: 13px; color: #64748b; margin-top: 4px; }
.quick-actions { margin-bottom: 24px; }
.action-buttons { display: flex; gap: 12px; flex-wrap: wrap; }
.empty { text-align: center; padding: 40px; color: #94a3b8; }
.ba-mini-list { display: flex; flex-direction: column; gap: 8px; }
.ba-mini-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
.ba-mini-title { font-weight: 500; color: #1e293b; }
.ba-mini-user { font-size: 13px; color: #64748b; }

</style>
