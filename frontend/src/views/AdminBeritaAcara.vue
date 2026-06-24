<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import LoadingSpinner from '../components/common/LoadingSpinner.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import Alert from '../components/common/Alert.vue'
import BeritaAcaraVerification from '../components/admin/BeritaAcaraVerification.vue'
import BeritaAcaraDetailView from '../components/admin/BeritaAcaraDetailView.vue'
import AnalystAssignment from '../components/admin/AnalystAssignment.vue'
import { adminApi } from '../api/admin'
import type { BeritaAcara, User } from '../types'

const items = ref<BeritaAcara[]>([])
const analysts = ref<User[]>([])
const loading = ref(true)
const error = ref('')
const filterStatus = ref('')
const selectedBa = ref<BeritaAcara | null>(null)
const showVerifyModal = ref(false)
const showDetailModal = ref(false)
const showAssignModal = ref(false)

const statusMap: Record<string, string> = {
  draft: 'Draft', pending: 'Menunggu', revision: 'Revisi', approved: 'Disetujui', rejected: 'Ditolak',
}
const statusTypeMap: Record<string, string> = {
  draft: 'default', pending: 'warning', revision: 'warning', approved: 'success', rejected: 'danger',
}

const filtered = computed(() => {
  if (!filterStatus.value) return items.value
  return items.value.filter(i => i.status === filterStatus.value)
})

onMounted(async () => {
  try {
    const [baRes, aRes] = await Promise.all([
      adminApi.getBeritaAcara(),
      adminApi.getAnalysts(),
    ])
    items.value = baRes
    analysts.value = aRes
  } catch {
    error.value = 'Gagal memuat data'
  } finally {
    loading.value = false
  }
})

function openVerify(item: BeritaAcara) {
  selectedBa.value = item
  showVerifyModal.value = true
}

function openDetail(item: BeritaAcara) {
  selectedBa.value = item
  showDetailModal.value = true
}

function openAssign(item: BeritaAcara) {
  selectedBa.value = item
  showAssignModal.value = true
}

async function handleVerify(data: { status: string; note?: string }) {
  if (!selectedBa.value) return
  try {
    await adminApi.updateStatus(selectedBa.value.id, data)
    const idx = items.value.findIndex(i => i.id === selectedBa.value!.id)
    if (idx !== -1) items.value[idx].status = data.status as any
    showVerifyModal.value = false
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal'
  }
}

async function handleAssign(analystId: number) {
  if (!selectedBa.value) return
  try {
    await adminApi.assignAnalyst(selectedBa.value.id, analystId)
    showAssignModal.value = false
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal'
  }
}
</script>

<template>
  <AppLayout>
    <div>
      <div class="page-header">
        <h1>Verifikasi Berita Acara</h1>
      </div>

      <Alert v-if="error" type="error" :message="error" @dismiss="error = ''" />

      <Card>
        <div class="filter-bar">
          <select v-model="filterStatus" class="form-select">
            <option value="">Semua Status</option>
            <option value="pending">Menunggu</option>
            <option value="revision">Revisi</option>
            <option value="approved">Disetujui</option>
            <option value="rejected">Ditolak</option>
          </select>
        </div>

        <LoadingSpinner v-if="loading" size="lg" />

        <div v-else class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Judul</th>
                <th>Pemohon</th>
                <th>Status</th>
                <th>Analis</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filtered" :key="item.id">
                <td>{{ item.judul_penelitian || '-' }}</td>
                <td>{{ item.user?.name || '-' }}</td>
                <td><Badge :type="statusTypeMap[item.status]" :text="statusMap[item.status]" /></td>
                <td>{{ item.analyst?.name || '-' }}</td>
                <td class="actions">
                  <Button size="sm" variant="ghost" @click="openDetail(item)">Detail</Button>
                  <Button size="sm" @click="openVerify(item)">Verifikasi</Button>
                  <Button size="sm" variant="outline" @click="openAssign(item)">Assign</Button>
                </td>
              </tr>
              <tr v-if="filtered.length === 0">
                <td colspan="5" class="empty-cell">Tidak ada data</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <Modal :show="showDetailModal" title="Detail Berita Acara" size="lg" @close="showDetailModal = false">
        <BeritaAcaraDetailView v-if="selectedBa" :berita-acara="selectedBa" />
      </Modal>

      <Modal :show="showVerifyModal" title="Verifikasi Berita Acara" size="lg" @close="showVerifyModal = false">
        <BeritaAcaraVerification v-if="selectedBa" :berita-acara="selectedBa" @submit="handleVerify" />
      </Modal>

      <Modal :show="showAssignModal" title="Assign Analis" @close="showAssignModal = false">
        <AnalystAssignment v-if="selectedBa" :berita-acara-id="selectedBa.id" :analysts="analysts" @assign="handleAssign" />
      </Modal>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-header { margin-bottom: 24px; }
.page-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
.filter-bar { margin-bottom: 16px; }
.form-select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; min-width: 200px; }
.table-wrap { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; font-size: 14px; }
.table th { text-align: left; padding: 12px; background: #f8fafc; color: #475569; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
.table td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
.actions { display: flex; gap: 8px; }
.empty-cell { text-align: center; color: #94a3b8; padding: 40px; }

</style>
