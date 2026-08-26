<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import SkeletonStats from '../components/common/SkeletonStats.vue'
import SkeletonCard from '../components/common/SkeletonCard.vue'
import { verificationApi } from '../api/verification'
import type { BeritaAcara } from '../types'
import { formatDateTime } from '../utils/date'

const router = useRouter()
const pending = ref<BeritaAcara[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    pending.value = await verificationApi.getPenyeliaBeritaAcara()
  } catch {
  } finally {
    loading.value = false
  }
})

const verifiedCount = computed(() => pending.value.filter(i => i.status === 'admin_verified').length)
</script>

<template>
  <AppLayout>
    <div class="dashboard">
      <div class="dashboard-header">
        <h1>Dashboard Penyelia</h1>
      </div>

      <SkeletonStats v-if="loading" />

      <template v-else>
        <div class="stats">
          <Card class="stat-card" hover @click="router.push('/penyelia/berita-acara')">
            <div class="stat-value">{{ pending.length }}</div>
            <div class="stat-label">Menunggu Verifikasi</div>
          </Card>
          <Card class="stat-card">
            <div class="stat-value">{{ verifiedCount }}</div>
            <div class="stat-label">Sudah Diverifikasi Admin</div>
          </Card>
        </div>

        <SkeletonCard v-if="loading" />
        <Card v-else title="Berita Acara Menunggu Verifikasi Anda">
          <div v-if="pending.length === 0" class="empty">Tidak ada berita acara menunggu verifikasi</div>
          <div v-else class="ba-mini-list">
            <div
              v-for="item in pending.slice(0, 5)"
              :key="item.id"
              class="ba-mini-item"
              @click="router.push(`/penyelia/berita-acara/${item.id}`)"
              style="cursor:pointer"
            >
              <span class="ba-mini-title">{{ item.judul_penelitian || 'Tanpa Judul' }}</span>
              <span class="ba-mini-user">{{ item.user?.name || '-' }} · {{ formatDateTime(item.created_at) }}</span>
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
.ba-mini-item:last-child { border-bottom: none; }
.ba-mini-title { font-weight: 500; color: #1e293b; }
.ba-mini-user { font-size: 13px; color: #64748b; }
</style>