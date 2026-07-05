<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import DataTable from '../components/common/DataTable.vue'
import SkeletonTable from '../components/common/SkeletonTable.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import FilterDropdown from '../components/common/FilterDropdown.vue'
import FlowSteps from '../components/user/FlowSteps.vue'
import { beritaAcaraApi } from '../api/beritaAcara'
import { useAuthStore } from '../stores/authStore'
import type { BeritaAcara } from '../types'

const statusOptions = [
  { value: '', label: 'Semua Status' },
  { value: 'draft', label: 'Draft' },
  { value: 'pending', label: 'Diajukan' },
  { value: 'revision', label: 'Revisi' },
  { value: 'approved', label: 'Disetujui' },
  { value: 'rejected', label: 'Ditolak' },
]

const auth = useAuthStore()
const router = useRouter()
const items = ref<BeritaAcara[]>([])
const loading = ref(true)
const filterStatus = ref('')
const showFlowModal = ref(false)
const flowBa = ref<BeritaAcara | null>(null)

function openFlow(ba: BeritaAcara) {
  flowBa.value = ba
  showFlowModal.value = true
}

const columns = [
  { key: 'judul_penelitian', label: 'Judul Penelitian' },
  { key: 'status', label: 'Status' },
  { key: 'created_at', label: 'Tanggal' },
  { key: 'action', label: 'Aksi' },
]

function getJudul(row: BeritaAcara) {
  if (row.judul_penelitian) return row.judul_penelitian
  // Fallback: cari dari form_data
  if (row.form_data) {
    const values = Object.values(row.form_data)
    for (const v of values) {
      if (typeof v === 'string' && v.length > 5) return v
    }
  }
  return '-'
}

const filtered = computed(() => {
  if (!filterStatus.value) return items.value
  return items.value.filter(i => i.status === filterStatus.value)
})

const statusMap: Record<string, string> = {
  draft: 'Draft',
  pending: 'Diajukan',
  revision: 'Revisi',
  approved: 'Disetujui',
  rejected: 'Ditolak',
}

const statusTypeMap: Record<string, 'default' | 'warning' | 'success' | 'danger' | 'info'> = {
  draft: 'default',
  pending: 'warning',
  revision: 'warning',
  approved: 'success',
  rejected: 'danger',
}

onMounted(async () => {
  try {
    items.value = await beritaAcaraApi.getList()
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
        <h1>Berita Acara Saya</h1>
        <Button v-if="auth.hasPermission('berita_acara_create')" @click="router.push('/user/berita-acara/create')">
          + Buat Baru
        </Button>
      </div>

      <Card>
        <div class="filter-bar">
          <FilterDropdown
            v-model="filterStatus"
            :options="statusOptions"
            placeholder="Semua Status"
          />
        </div>

        <SkeletonTable v-if="loading" :cols="4" :rows="5" />

        <DataTable v-else :columns="columns" :data="filtered">
          <template #cell-judul_penelitian="{ row }">
            <span class="judul-link" @click.stop="router.push(`/user/berita-acara/${row.id}`)">
              {{ getJudul(row) }}
            </span>
          </template>
          <template #cell-status="{ row }">
            <Badge :type="statusTypeMap[row.status]" :text="statusMap[row.status]" />
          </template>
          <template #cell-created_at="{ row }">
            {{ new Date(row.created_at).toLocaleDateString('id-ID') }}
          </template>
          <template #cell-action="{ row }">
            <div class="action-cell">
              <button class="action-icon" title="Detail" @click.stop="router.push(`/user/berita-acara/${row.id}`)">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
              <button class="action-icon" title="Alur" @click.stop="openFlow(row)">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18"/><path d="M3 6h18"/><path d="M3 18h18"/></svg>
              </button>
            </div>
          </template>
        </DataTable>

        <Modal :show="showFlowModal" title="Alur Pemesanan" size="lg" @close="showFlowModal = false">
          <FlowSteps v-if="flowBa" :berita-acara="flowBa" />
        </Modal>
      </Card>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}
.page-header h1 {
  font-size: 24px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}
.filter-bar {
  margin-bottom: 16px;
}
.action-cell {
  display: flex;
  gap: 4px;
}

.judul-link {
  color: #2563eb;
  cursor: pointer;
  font-weight: 500;
  transition: color 0.15s;
}
.judul-link:hover {
  color: #1d4ed8;
  text-decoration: underline;
}

.action-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  border-radius: 6px;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s;
}
.action-icon:hover {
  background: #f1f5f9;
  color: #1e293b;
}
</style>
