<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import SkeletonStats from '../components/common/SkeletonStats.vue'
import SkeletonCard from '../components/common/SkeletonCard.vue'
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
</script>

<template>
  <AppLayout>
    <div class="dashboard">
      <div class="dashboard-header">
        <h1>Dashboard Admin</h1>
      </div>

      <SkeletonStats v-if="loading" />

      <template v-else>
        <div class="stats">
          <Card class="stat-card">
            <div class="stat-value">{{ items.filter(i => i.status === 'pending').length }}</div>
            <div class="stat-label">Menunggu Admin</div>
          </Card>
          <Card class="stat-card">
            <div class="stat-value">{{ items.filter(i => i.status === 'admin_verified').length }}</div>
            <div class="stat-label">Menunggu Penyelia</div>
          </Card>
          <Card class="stat-card">
            <div class="stat-value">{{ items.filter(i => i.status === 'revision').length }}</div>
            <div class="stat-label">Perlu Revisi</div>
          </Card>
          <Card class="stat-card">
            <div class="stat-value">{{ items.filter(i => i.status === 'completed').length }}</div>
            <div class="stat-label">Selesai</div>
          </Card>
        </div>

        <Card title="Berita Acara Diajukan">
          <div v-if="items.filter(i => i.status === 'pending').length === 0" class="empty">Tidak ada yang diajukan</div>
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
.empty { text-align: center; padding: 40px; color: #94a3b8; }
.ba-mini-list { display: flex; flex-direction: column; gap: 8px; }
.ba-mini-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
.ba-mini-title { font-weight: 500; color: #1e293b; }
.ba-mini-user { font-size: 13px; color: #64748b; }

</style>
