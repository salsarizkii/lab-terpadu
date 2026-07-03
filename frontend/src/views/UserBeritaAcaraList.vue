<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import DataTable from '../components/common/DataTable.vue'
import LoadingSpinner from '../components/common/LoadingSpinner.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import FlowSteps from '../components/user/FlowSteps.vue'
import { beritaAcaraApi } from '../api/beritaAcara'
import { useAuthStore } from '../stores/authStore'
import type { BeritaAcara } from '../types'

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

const filtered = computed(() => {
  if (!filterStatus.value) return items.value
  return items.value.filter(i => i.status === filterStatus.value)
})

const statusMap: Record<string, string> = {
  draft: 'Draft',
  pending: 'Menunggu',
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
          <select v-model="filterStatus" class="form-select">
            <option value="">Semua Status</option>
            <option value="draft">Draft</option>
            <option value="pending">Menunggu</option>
            <option value="revision">Revisi</option>
            <option value="approved">Disetujui</option>
            <option value="rejected">Ditolak</option>
          </select>
        </div>

        <LoadingSpinner v-if="loading" size="lg" />

        <DataTable v-else :columns="columns" :data="filtered">
          <template #cell-status="{ row }">
            <Badge :type="statusTypeMap[row.status]" :text="statusMap[row.status]" />
          </template>
          <template #cell-created_at="{ row }">
            {{ new Date(row.created_at).toLocaleDateString('id-ID') }}
          </template>
          <template #cell-action="{ row }">
            <div class="action-cell">
              <Button size="sm" variant="outline" @click.stop="router.push(`/user/berita-acara/${row.id}`)">
                Detail
              </Button>
              <Button size="sm" variant="ghost" @click.stop="openFlow(row)">
                Alur
              </Button>
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
.form-select {
  padding: 8px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 14px;
  min-width: 200px;
}
.action-cell {
  display: flex;
  gap: 6px;
}
</style>
