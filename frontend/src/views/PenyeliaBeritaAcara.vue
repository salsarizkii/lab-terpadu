<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import SkeletonTable from '../components/common/SkeletonTable.vue'
import Button from '../components/common/Button.vue'
import { verificationApi } from '../api/verification'
import { useAuthStore } from '../stores/authStore'
import type { BeritaAcara } from '../types'
import { baStatusMap, baStatusTypeMap } from '../utils/baStatus'
import { toast } from 'vue-sonner'
import { formatDateTime } from '../utils/date'

const auth = useAuthStore()
const router = useRouter()

const items = ref<BeritaAcara[]>([])
const loading = ref(true)

onMounted(fetchData)

async function fetchData() {
  loading.value = true
  try {
    items.value = await verificationApi.getPenyeliaBeritaAcara()
  } catch {
    toast.error('Gagal memuat data')
  } finally {
    loading.value = false
  }
}

const pendingCount = computed(() => items.value.filter(i => i.status === 'admin_verified').length)
const approvedCount = computed(() => items.value.filter(i => i.status === 'penyelia_verified').length)

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
        <h1>Verifikasi Penyelia</h1>
        <Button variant="ghost" size="sm" @click="fetchData">Muat Ulang</Button>
      </div>

      <Card>
        <div class="toolbar">
          <span class="filter-count">{{ pendingCount }} menunggu verifikasi · {{ approvedCount }} diverifikasi</span>
        </div>

        <SkeletonTable v-if="loading" :cols="5" :rows="5" />

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
              <tr v-for="item in items" :key="item.id">
                <td class="td-judul">{{ getJudul(item) }}</td>
                <td>{{ item.user?.name || '-' }}</td>
                <td><Badge :type="baStatusTypeMap[item.status]" :text="baStatusMap[item.status]" /></td>
                <td>{{ item.analysts?.length ? item.analysts.map(a => a.name).join(', ') : '-' }}</td>
                <td class="td-date">{{ formatDateTime(item.created_at) }}</td>
                <td class="td-aksi">
                  <button class="eye-btn" @click="router.push(`/penyelia/berita-acara/${item.id}`)" title="Lihat Detail">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </td>
              </tr>
              <tr v-if="items.length === 0">
                <td colspan="6" class="empty-cell">Tidak ada berita acara</td>
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
.toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.filter-count { font-size: 13px; color: #94a3b8; }
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
