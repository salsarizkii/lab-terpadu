<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import SkeletonTable from '../components/common/SkeletonTable.vue'
import Modal from '../components/common/Modal.vue'
import RekapAnalisView from '../components/common/RekapAnalisView.vue'
import { adminApi } from '../api/admin'
import type { BeritaAcara, Sample } from '../types'
import { toast } from 'vue-sonner'
import { formatDateTime } from '../utils/date'

const rows = ref<Array<{ ba: BeritaAcara; sampleNames: string; created_at: string }>>([])
const loading = ref(true)
const selectedBa = ref<BeritaAcara | null>(null)
const activeTab = ref('all')

const tabs = [
  { key: 'all', label: 'Semua' },
  { key: 'waiting', label: 'Menunggu Verifikasi' },
  { key: 'verified', label: 'Diverifikasi' },
]

const statusTypeMap: Record<string, 'default' | 'warning' | 'success' | 'danger' | 'info'> = {
  waiting: 'warning',
  verified: 'success',
}

const statusTextMap: Record<string, string> = {
  waiting: 'Menunggu Verifikasi',
  verified: 'Diverifikasi Manager Teknis',
}

function getVerificationStatus(ba: BeritaAcara): 'waiting' | 'verified' {
  return ba.status === 'completed' ? 'verified' : 'waiting'
}

function getJudul(ba: BeritaAcara): string {
  if (ba.judul_penelitian) return ba.judul_penelitian
  if (ba.form_data) {
    const values = Object.values(ba.form_data)
    for (const v of values) {
      if (typeof v === 'string' && v.length > 5) return v
    }
  }
  return '-'
}

function hasRekap(ba: BeritaAcara): boolean {
  return !!ba.rekap_analis_data && Object.keys(ba.rekap_analis_data).length > 0
}

const filtered = computed(() => {
  if (activeTab.value === 'all') return rows.value
  return rows.value.filter(r => getVerificationStatus(r.ba) === activeTab.value)
})

const waitingCount = computed(() => rows.value.filter(r => getVerificationStatus(r.ba) === 'waiting').length)
const verifiedCount = computed(() => rows.value.filter(r => getVerificationStatus(r.ba) === 'verified').length)

onMounted(async () => {
  try {
    const samples: Sample[] = await adminApi.getSamples()
    const grouped = new Map<number, { ba: BeritaAcara; names: string[]; created_at: string }>()
    for (const s of samples) {
      if (!s.berita_acara) continue
      const id = s.berita_acara.id
      const existing = grouped.get(id)
      const name = s.name || 'Hasil Pengujian'
      if (existing) {
        if (!existing.names.includes(name)) existing.names.push(name)
      } else {
        grouped.set(id, { ba: s.berita_acara, names: [name], created_at: s.created_at })
      }
    }
    rows.value = Array.from(grouped.values()).map(g => ({
      ba: g.ba,
      sampleNames: g.names.join(', '),
      created_at: g.created_at,
    }))
  } catch {
    toast.error('Gagal memuat data')
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <AppLayout>
    <div>
      <div class="page-header">
        <h1>Rekap Analisis</h1>
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
          <span class="filter-count">{{ waitingCount }} menunggu verifikasi · {{ verifiedCount }} diverifikasi</span>
        </div>

        <SkeletonTable v-if="loading" :cols="6" :rows="5" />

        <div v-else class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Judul Penelitian</th>
                <th>Pemohon</th>
                <th>Status</th>
                <th>Nama Hasil Pengujian</th>
                <th>Diajukan</th>
                <th class="th-aksi">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in filtered" :key="row.ba.id">
                <td class="td-judul">{{ getJudul(row.ba) }}</td>
                <td>{{ row.ba.user?.name || row.ba.nama_pemohon || '-' }}</td>
                <td>
                  <Badge :type="statusTypeMap[getVerificationStatus(row.ba)]" :text="statusTextMap[getVerificationStatus(row.ba)]" />
                </td>
                <td class="td-hasil">{{ row.sampleNames }}</td>
                <td class="td-date">{{ formatDateTime(row.created_at) }}</td>
                <td class="td-aksi">
                  <button class="eye-btn" @click="selectedBa = row.ba" title="Lihat Detail">
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

    <!-- Modal Detail Rekap -->
    <Modal :show="!!selectedBa" title="Detail Rekap Analis" size="lg" @close="selectedBa = null">
      <div v-if="selectedBa">
        <RekapAnalisView :berita-acara="selectedBa" />
        <div v-if="!hasRekap(selectedBa)" class="no-rekap">
          Belum ada data rekap analis untuk berita acara ini.
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<style scoped>
.page-header { margin-bottom: 24px; }
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

.table-wrap { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; font-size: 14px; }
.table th { text-align: left; padding: 12px; background: #f8fafc; color: #475569; font-weight: 600; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.table td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
.th-aksi, .td-aksi { width: 60px; text-align: center; }
.td-judul { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.td-hasil { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; color: #64748b; }
.td-date { white-space: nowrap; }
.empty-cell { text-align: center; color: #94a3b8; padding: 40px; }

.eye-btn {
  background: none; border: 1px solid transparent; border-radius: 6px; padding: 6px 8px;
  cursor: pointer; color: #64748b; transition: all 0.15s; display: inline-flex; align-items: center;
}
.eye-btn:hover { background: #f1f5f9; border-color: #e2e8f0; color: #1e293b; }

.no-rekap {
  margin-top: 12px;
  padding: 16px;
  text-align: center;
  font-size: 13px;
  color: #94a3b8;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  border-radius: 10px;
}
</style>