<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Button from '../components/common/Button.vue'
import SkeletonStats from '../components/common/SkeletonStats.vue'
import SkeletonCard from '../components/common/SkeletonCard.vue'
import { adminApi } from '../api/admin'
import { useAuthStore } from '../stores/authStore'
import type { BeritaAcara } from '../types'

const router = useRouter()
const auth = useAuthStore()
const items = ref<BeritaAcara[]>([])
const loading = ref(true)
const exporting = ref<'database' | 'pesanan' | null>(null)

const canExportDatabase = computed(
  () => auth.hasPermission('berita_acara_riwayat_semua') && auth.hasPermission('user_view')
)
const canExportPesanan = computed(() => auth.hasPermission('berita_acara_riwayat_semua'))

onMounted(async () => {
  try {
    items.value = await adminApi.getBeritaAcara()
  } catch {
  } finally {
    loading.value = false
  }
})

function fileTimestamp(): string {
  const d = new Date()
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${d.getFullYear()}${pad(d.getMonth() + 1)}${pad(d.getDate())}-${pad(d.getHours())}${pad(d.getMinutes())}${pad(d.getSeconds())}`
}

function downloadBlob(blob: Blob, filename: string) {
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  window.URL.revokeObjectURL(url)
}

async function handleExportDatabase() {
  exporting.value = 'database'
  try {
    const blob = await adminApi.exportDatabase()
    downloadBlob(blob, `export-database-${fileTimestamp()}.xlsx`)
    toast.success('Export database berhasil diunduh')
  } catch {
    toast.error('Gagal export database')
  } finally {
    exporting.value = null
  }
}

async function handleExportPesanan() {
  exporting.value = 'pesanan'
  try {
    const blob = await adminApi.exportPesanan()
    downloadBlob(blob, `export-pesanan-${fileTimestamp()}.csv`)
    toast.success('Export pesanan berhasil diunduh')
  } catch {
    toast.error('Gagal export pesanan')
  } finally {
    exporting.value = null
  }
}
</script>

<template>
  <AppLayout>
    <div class="dashboard">
      <div class="dashboard-header">
        <h1>Dashboard Admin</h1>
        <div class="export-actions" v-if="canExportDatabase || canExportPesanan">
          <Button v-if="canExportPesanan" variant="outline" :loading="exporting === 'pesanan'" :disabled="exporting !== null" @click="handleExportPesanan">
            Export Pesanan (CSV)
          </Button>
          <Button v-if="canExportDatabase" variant="primary" :loading="exporting === 'database'" :disabled="exporting !== null" @click="handleExportDatabase">
            Export Database (Excel)
          </Button>
        </div>
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
.dashboard-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
.dashboard-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
.export-actions { display: flex; flex-wrap: wrap; gap: 10px; }
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
