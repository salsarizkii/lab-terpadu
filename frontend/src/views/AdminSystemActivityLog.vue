<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import SkeletonTable from '../components/common/SkeletonTable.vue'
import { Search, History } from '@lucide/vue'
import { adminApi } from '../api/admin'
import type { SystemActivityLog } from '../types'
import { toast } from 'vue-sonner'
import { formatDateTime } from '../utils/date'

const logs = ref<SystemActivityLog[]>([])
const loading = ref(true)
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const perPage = ref(30)

const searchQuery = ref('')
const filterAction = ref('')

const actionOptions = [
  { value: '', label: 'Semua Aksi' },
  { value: 'user_verified', label: 'Verifikasi Pengguna' },
  { value: 'user_deactivated', label: 'Nonaktifkan Pengguna' },
  { value: 'role_created', label: 'Buat Role' },
  { value: 'role_updated', label: 'Ubah Role' },
  { value: 'role_deleted', label: 'Hapus Role' },
  { value: 'form_template_created', label: 'Buat Template Form' },
  { value: 'form_template_updated', label: 'Ubah Template Form' },
  { value: 'form_template_deleted', label: 'Hapus Template Form' },
  { value: 'form_template_fields_synced', label: 'Sync Fields Form' },
  { value: 'formula_created', label: 'Buat Rumus' },
  { value: 'formula_updated', label: 'Ubah Rumus' },
  { value: 'formula_deleted', label: 'Hapus Rumus' },
  { value: 'instrument_price_created', label: 'Tambah Instrumen' },
  { value: 'instrument_price_updated', label: 'Ubah Harga Instrumen' },
  { value: 'instrument_price_deleted', label: 'Hapus Instrumen' },
  { value: 'instrument_prices_bulk_updated', label: 'Update Massal Harga' },
  { value: 'pdf_template_updated', label: 'Ubah Template PDF' },
  { value: 'pdf_template_imported_docx', label: 'Import DOCX' },
  { value: 'pdf_template_reset', label: 'Reset Template PDF' },
]

const ACTION_META: Record<string, { label: string; type: string }> = {
  user_verified: { label: 'Verifikasi Pengguna', type: 'success' },
  user_deactivated: { label: 'Nonaktifkan Pengguna', type: 'danger' },
  role_created: { label: 'Buat Role', type: 'info' },
  role_updated: { label: 'Ubah Role', type: 'info' },
  role_deleted: { label: 'Hapus Role', type: 'danger' },
  form_template_created: { label: 'Buat Template Form', type: 'info' },
  form_template_updated: { label: 'Ubah Template Form', type: 'info' },
  form_template_deleted: { label: 'Hapus Template Form', type: 'danger' },
  form_template_fields_synced: { label: 'Sync Fields Form', type: 'info' },
  formula_created: { label: 'Buat Rumus', type: 'info' },
  formula_updated: { label: 'Ubah Rumus', type: 'info' },
  formula_deleted: { label: 'Hapus Rumus', type: 'danger' },
  instrument_price_created: { label: 'Tambah Instrumen', type: 'info' },
  instrument_price_updated: { label: 'Ubah Harga Instrumen', type: 'info' },
  instrument_price_deleted: { label: 'Hapus Instrumen', type: 'danger' },
  instrument_prices_bulk_updated: { label: 'Update Massal Harga', type: 'info' },
  pdf_template_updated: { label: 'Ubah Template PDF', type: 'info' },
  pdf_template_imported_docx: { label: 'Import DOCX', type: 'info' },
  pdf_template_reset: { label: 'Reset Template PDF', type: 'warning' },
}

function meta(action: string) {
  return ACTION_META[action] ?? { label: action, type: 'default' }
}

function roleLabel(role?: string) {
  const map: Record<string, string> = {
    superadmin: 'Super Admin',
    admin: 'Admin',
    analyst: 'Analis',
    penyelia: 'Penyelia',
    manager_teknis: 'Manager Teknis',
    user: 'Pemohon',
  }
  return role ? (map[role] ?? role) : '-'
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null

function onSearchInput() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    fetchData()
  }, 400)
}

watch(filterAction, () => {
  currentPage.value = 1
  fetchData()
})

async function fetchData() {
  loading.value = true
  try {
    const params: Record<string, any> = {
      page: currentPage.value,
      per_page: perPage.value,
    }
    if (searchQuery.value) params.search = searchQuery.value
    if (filterAction.value) params.action = filterAction.value

    const result = await adminApi.getSystemActivityLogs(params)
    logs.value = result.data
    lastPage.value = result.last_page
    total.value = result.total
  } catch {
    toast.error('Gagal memuat log aktivitas')
  } finally {
    loading.value = false
  }
}

function goToPage(page: number) {
  if (page < 1 || page > lastPage.value) return
  currentPage.value = page
  fetchData()
}

onMounted(fetchData)
</script>

<template>
  <AppLayout>
    <div class="page-header">
      <div class="page-header__left">
        <History :size="24" class="page-header__icon" />
        <div>
          <h1 class="page-header__title">Log Aktivitas Sistem</h1>
          <p class="page-header__subtitle">Riwayat semua aktivitas pengelolaan sistem</p>
        </div>
      </div>
      <div class="page-header__right">
        <span class="page-header__total">{{ total }} aktivitas</span>
      </div>
    </div>

    <Card>
      <div class="toolbar">
        <div class="toolbar__search">
          <Search :size="16" class="toolbar__search-icon" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari aktivitas..."
            class="toolbar__search-input"
            @input="onSearchInput"
          />
        </div>
        <select v-model="filterAction" class="toolbar__filter">
          <option v-for="opt in actionOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>
      </div>

      <SkeletonTable v-if="loading" :rows="8" :cols="4" />

      <div v-else-if="logs.length === 0" class="empty-state">
        <p>Belum ada aktivitas tercatat</p>
      </div>

      <div v-else class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Waktu</th>
              <th>Pelaku</th>
              <th>Aksi</th>
              <th>Deskripsi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <td class="cell-time">{{ formatDateTime(log.created_at) }}</td>
              <td>
                <div v-if="log.user" class="actor">
                  <span class="actor__name">{{ log.user.name }}</span>
                  <span class="actor__role">{{ roleLabel(log.user.role) }}</span>
                </div>
                <span v-else class="muted">Sistem</span>
              </td>
              <td>
                <span class="badge" :class="`badge--${meta(log.action).type}`">
                  {{ meta(log.action).label }}
                </span>
              </td>
              <td class="cell-desc">{{ log.description || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="lastPage > 1" class="pagination">
        <button class="pagination__btn" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
          &laquo; Prev
        </button>
        <span class="pagination__info">Halaman {{ currentPage }} dari {{ lastPage }}</span>
        <button class="pagination__btn" :disabled="currentPage >= lastPage" @click="goToPage(currentPage + 1)">
          Next &raquo;
        </button>
      </div>
    </Card>
  </AppLayout>
</template>

<style scoped>
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 12px;
}
.page-header__left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.page-header__icon {
  color: #6366f1;
}
.page-header__title {
  font-size: 20px;
  font-weight: 700;
  color: var(--color-text, #0f172a);
  margin: 0;
}
.page-header__subtitle {
  font-size: 13px;
  color: var(--color-text-muted, #94a3b8);
  margin: 2px 0 0;
}
.page-header__total {
  font-size: 13px;
  color: var(--color-text-muted, #94a3b8);
  background: var(--color-bg, #f8fafc);
  padding: 4px 12px;
  border-radius: 100px;
}

.toolbar {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}
.toolbar__search {
  position: relative;
  flex: 1;
  min-width: 200px;
}
.toolbar__search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}
.toolbar__search-input {
  width: 100%;
  padding: 8px 12px 8px 36px;
  border: 1px solid var(--color-border, #e2e8f0);
  border-radius: 8px;
  font-size: 13px;
  font-family: inherit;
  outline: none;
  transition: border-color 0.15s;
}
.toolbar__search-input:focus {
  border-color: #6366f1;
}
.toolbar__filter {
  padding: 8px 12px;
  border: 1px solid var(--color-border, #e2e8f0);
  border-radius: 8px;
  font-size: 13px;
  font-family: inherit;
  background: #fff;
  outline: none;
  cursor: pointer;
}

.table-wrap {
  overflow-x: auto;
}
.table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.table th {
  text-align: left;
  padding: 10px 12px;
  font-weight: 600;
  color: var(--color-text-secondary, #475569);
  border-bottom: 2px solid var(--color-border, #e2e8f0);
  white-space: nowrap;
}
.table td {
  padding: 10px 12px;
  border-bottom: 1px solid var(--color-border-light, #f1f5f9);
  vertical-align: middle;
}
.table tbody tr:hover {
  background: var(--color-bg, #f8fafc);
}

.cell-time {
  white-space: nowrap;
  font-size: 12px;
  color: var(--color-text-muted, #94a3b8);
}
.cell-desc {
  max-width: 300px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--color-text-secondary, #475569);
}

.actor {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.actor__name {
  font-weight: 600;
  color: var(--color-text, #0f172a);
}
.actor__role {
  font-size: 11px;
  color: var(--color-text-muted, #94a3b8);
}

.muted {
  color: var(--color-text-muted, #94a3b8);
  font-size: 12px;
}

.badge {
  display: inline-flex;
  padding: 3px 10px;
  border-radius: 100px;
  font-size: 12px;
  font-weight: 500;
  white-space: nowrap;
}
.badge--success { background: #d1fae5; color: #065f46; }
.badge--danger { background: #fee2e2; color: #991b1b; }
.badge--info { background: #e0e7ff; color: #3730a3; }
.badge--warning { background: #fef3c7; color: #92400e; }
.badge--default { background: #f1f5f9; color: #475569; }

.empty-state {
  padding: 40px;
  text-align: center;
  color: var(--color-text-muted, #94a3b8);
  font-size: 14px;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 16px 0 0;
}
.pagination__btn {
  padding: 6px 14px;
  border: 1px solid var(--color-border, #e2e8f0);
  border-radius: 6px;
  background: #fff;
  font-size: 13px;
  cursor: pointer;
  font-family: inherit;
  transition: background 0.15s;
}
.pagination__btn:hover:not(:disabled) {
  background: var(--color-bg, #f8fafc);
}
.pagination__btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.pagination__info {
  font-size: 13px;
  color: var(--color-text-muted, #94a3b8);
}
</style>
