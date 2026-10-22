<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import SkeletonTable from '../components/common/SkeletonTable.vue'
import Button from '../components/common/Button.vue'
import { adminApi } from '../api/admin'
import { useAuthStore } from '../stores/authStore'
import type { BeritaAcara } from '../types'
import { toast } from 'vue-sonner'
import { formatDateTime } from '../utils/date'

const auth = useAuthStore()
const router = useRouter()

const items = ref<BeritaAcara[]>([])
const loading = ref(true)
const activeTab = ref('all')

const tabs = [
  { key: 'all', label: 'Semua' },
  { key: 'pending', label: 'Menunggu Admin' },
  { key: 'admin_revisi', label: 'Admin Revisi' },
  { key: 'admin_verified', label: 'Diverifikasi Admin' },
  { key: 'penyelia_verified', label: 'Diverifikasi Penyelia' },
  { key: 'rekap_ready', label: 'Menunggu Manager' },
  { key: 'revision', label: 'Revisi' },
  { key: 'completed', label: 'Selesai' },
]

const statusMap: Record<string, string> = {
  draft: 'Draft', pending: 'Menunggu Admin', revision: 'Revisi', admin_verified: 'Diverifikasi Admin',
  admin_revisi: 'Admin Revisi',
  penyelia_verified: 'Diverifikasi Penyelia', rekap_ready: 'Menunggu Manager Teknis', completed: 'Selesai', rejected: 'Ditolak',
}
const statusTypeMap: Record<string, 'default' | 'warning' | 'success' | 'danger' | 'info'> = {
  draft: 'default', pending: 'warning', revision: 'warning', admin_verified: 'info',
  admin_revisi: 'warning',
  penyelia_verified: 'info', rekap_ready: 'warning', completed: 'success', rejected: 'danger',
}

const filtered = computed(() => {
  if (activeTab.value === 'all') return items.value
  return items.value.filter(i => i.status === activeTab.value)
})

onMounted(fetchData)

async function fetchData() {
  loading.value = true
  try {
    items.value = await adminApi.getBeritaAcara()
  } catch {
    toast.error('Gagal memuat data')
  } finally {
    loading.value = false
  }
}

function goToDetail(id: number) {
  router.push(`/admin/berita-acara/${id}`)
}

function getJudul(item: BeritaAcara) {
  if (item.judul_penelitian) return item.judul_penelitian
  if (item.form_data) {
    const values = Object.values(item.form_data)
    for (const v of values) {
      if (typeof v === 'string' && v.length > 5) return v
    }
  }
  return '-'
}
</script>

<template>
  <AppLayout>
    <div>
      <div class="page-header">
        <h1>Berita Acara</h1>
        <Button v-if="auth.hasPermission('berita_acara_create')" @click="router.push('/user/berita-acara/create')">
          + Buat Berita Acara
        </Button>
      </div>

      <Card>
        <div class="tabs">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            class="tab-btn"
            :class="{ active: activeTab === tab.key }"
            @click="activeTab = tab.key"
          >
            {{ tab.label }}
          </button>
        </div>

        <div class="toolbar">
          <span class="filter-count">{{ filtered.length }} data</span>
          <div class="toolbar-actions">
            <Button variant="ghost" size="sm" @click="fetchData">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
              Muat Ulang
            </Button>
          </div>
        </div>

        <SkeletonTable v-if="loading" :cols="6" :rows="5" />

        <div v-else class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Judul Penelitian</th>
                <th>Pemohon</th>
                <th>Status</th>
                <th>Analis</th>
                <th>Diajukan</th>
                <th class="th-aksi">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filtered" :key="item.id">
                <td class="td-judul">{{ getJudul(item) }}</td>
                <td>{{ item.user?.name || '-' }}</td>
                <td><Badge :type="statusTypeMap[item.status]" :text="statusMap[item.status]" /></td>
                <td>{{ item.analysts?.length ? item.analysts.map(a => a.name).join(', ') : (item.analyst?.name || '-') }}</td>
                <td class="td-date">{{ formatDateTime(item.created_at) }}</td>
                <td class="td-aksi">
                  <button class="eye-btn" @click="goToDetail(item.id)" title="Lihat Detail">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </td>
              </tr>
              <tr v-if="filtered.length === 0">
                <td colspan="6" class="empty-cell">Tidak ada data</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.page-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }

.tabs {
  display: flex; gap: 0; border-bottom: none; margin-bottom: 16px;
  background: #f1f5f9; border-radius: 10px; padding: 4px;
}
.tab-btn {
  flex: 1; padding: 10px 0; border: none; background: none; font-size: 14px; font-weight: 500;
  color: #64748b; cursor: pointer; transition: all 0.15s; font-family: inherit; border-radius: 8px;
}
.tab-btn:hover { color: #1e293b; }
.tab-btn.active { color: #1e293b; font-weight: 600; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }

.toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.filter-count { font-size: 13px; color: #94a3b8; }
.toolbar-actions { display: flex; gap: 8px; }

.table-wrap { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; font-size: 14px; }
.table th { text-align: left; padding: 12px; background: #f8fafc; color: #475569; font-weight: 600; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.table td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
.th-aksi, .td-aksi { width: 60px; text-align: center; }
.td-judul { max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.td-date { white-space: nowrap; }
.empty-cell { text-align: center; color: #94a3b8; padding: 40px; }

.eye-btn {
  background: none; border: 1px solid transparent; border-radius: 6px; padding: 6px 8px;
  cursor: pointer; color: #64748b; transition: all 0.15s; display: inline-flex; align-items: center;
}
.eye-btn:hover { background: #f1f5f9; border-color: #e2e8f0; color: #1e293b; }
</style>
