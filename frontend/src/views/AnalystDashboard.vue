<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import LoadingSpinner from '../components/common/LoadingSpinner.vue'
import Button from '../components/common/Button.vue'
import { analystApi } from '../api/analyst'
import type { BeritaAcara } from '../types'

const router = useRouter()
const items = ref<BeritaAcara[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    items.value = await analystApi.getBeritaAcara()
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
        <h1>Dashboard Analis</h1>
      </div>

      <LoadingSpinner v-if="loading" size="lg" />

      <template v-else>
        <div class="stats">
          <Card class="stat-card">
            <div class="stat-value">{{ items.length }}</div>
            <div class="stat-label">Total Tugas</div>
          </Card>
          <Card class="stat-card">
            <div class="stat-value">{{ items.filter(i => i.samples?.some(s => s.status === 'processing')).length || 0 }}</div>
            <div class="stat-label">Sedang Diproses</div>
          </Card>
          <Card class="stat-card">
            <div class="stat-value">{{ items.filter(i => i.samples?.every(s => s.status === 'ready')).length || 0 }}</div>
            <div class="stat-label">Selesai</div>
          </Card>
        </div>

        <div class="quick-actions">
          <Card title="Aksi Cepat">
            <Button @click="router.push('/analyst/assigned')">
              Lihat Tugas Saya
            </Button>
          </Card>
        </div>

        <Card title="Tugas Terbaru">
          <div v-if="items.length === 0" class="empty">Belum ada tugas</div>
          <div v-else class="task-list">
            <div v-for="item in items.slice(0, 5)" :key="item.id" class="task-item" @click="router.push('/analyst/assigned')">
              <div class="task-title">{{ item.judul_penelitian || 'Tanpa Judul' }}</div>
              <div class="task-user">{{ item.user?.name || '-' }}</div>
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
.empty { text-align: center; padding: 40px; color: #94a3b8; }
.task-list { display: flex; flex-direction: column; gap: 8px; }
.task-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; cursor: pointer; }
.task-item:hover { background: #f8fafc; }
.task-title { font-weight: 500; color: #1e293b; }
.task-user { font-size: 13px; color: #64748b; }

</style>
