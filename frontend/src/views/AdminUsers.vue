<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import LoadingSpinner from '../components/common/LoadingSpinner.vue'
import { Search, Users as UsersIcon, CheckCircle, XCircle } from '@lucide/vue'
import { adminApi } from '../api/admin'
import { roleApi } from '../api/role'
import type { User, Role } from '../types'
import { toast } from 'vue-sonner'

const users = ref<User[]>([])
const roles = ref<Role[]>([])
const loading = ref(true)

// Filters
const searchQuery = ref('')
const filterRole = ref('')
const filterVerified = ref('')

// Verify modal
const showVerifyModal = ref(false)
const verifyingUser = ref<User | null>(null)
const verifyAction = ref(true)
const verifyLoading = ref(false)

async function fetchData() {
  loading.value = true
  try {
    const params: Record<string, any> = {}
    if (searchQuery.value) params.search = searchQuery.value
    if (filterRole.value) params.role = filterRole.value
    if (filterVerified.value !== '') params.is_verified = filterVerified.value

    const [usersData, rolesData] = await Promise.all([
      adminApi.getUsers(params),
      roles.value.length === 0 ? roleApi.getRoles() : Promise.resolve(roles.value),
    ])
    users.value = usersData
    roles.value = rolesData
  } catch {
    toast.error('Gagal memuat data')
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)

function applyFilters() {
  fetchData()
}

function resetFilters() {
  searchQuery.value = ''
  filterRole.value = ''
  filterVerified.value = ''
  fetchData()
}

function openVerifyModal(user: User, action: boolean) {
  verifyingUser.value = user
  verifyAction.value = action
  showVerifyModal.value = true
}

async function handleVerify() {
  if (!verifyingUser.value) return
  verifyLoading.value = true
  try {
    const res = await adminApi.verifyUser(verifyingUser.value.id, verifyAction.value)
    toast.success(res.message)
    showVerifyModal.value = false
    await fetchData()
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal memproses verifikasi')
  } finally {
    verifyLoading.value = false
  }
}

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}
</script>

<template>
  <AppLayout>
    <div class="users-page">
      <div class="users-header">
        <div>
          <h1>Kelola Pengguna</h1>
          <p class="users-subtitle">Lihat, cari, dan verifikasi akun pengguna terdaftar</p>
        </div>
      </div>

      <!-- Filters -->
      <Card class="filter-card">
        <div class="filters">
          <div class="filter-search">
            <Search class="search-icon" :size="18" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari nama, email, atau telepon..."
              class="filter-input"
              @keyup.enter="applyFilters"
            />
          </div>
          <div class="filter-selects">
            <select v-model="filterRole" class="filter-select" @change="applyFilters">
              <option value="">Semua Role</option>
              <option v-for="role in roles" :key="role.name" :value="role.name">
                {{ role.name }}
              </option>
            </select>
            <select v-model="filterVerified" class="filter-select" @change="applyFilters">
              <option value="">Semua Status</option>
              <option value="true">Terverifikasi</option>
              <option value="false">Menunggu Verifikasi</option>
            </select>
            <Button variant="secondary" size="sm" @click="applyFilters">Cari</Button>
          </div>
        </div>
      </Card>

      <LoadingSpinner v-if="loading" size="lg" />

      <div v-else>
        <div class="users-count">
          Menampilkan <strong>{{ users.length }}</strong> pengguna
        </div>

        <div v-if="users.length === 0" class="empty-state">
          <UsersIcon :size="64" color="#cbd5e1" />
          <p>Tidak ada pengguna ditemukan</p>
        </div>

        <div v-else class="users-table-wrap">
          <table class="users-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Telepon</th>
                <th>Role</th>
                <th>Status</th>
                <th>Terdaftar</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users" :key="user.id">
                <td class="user-name">{{ user.name }}</td>
                <td class="user-email">{{ user.email }}</td>
                <td>{{ user.phone || '-' }}</td>
                <td>
                  <span class="role-tag">{{ user.role }}</span>
                </td>
                <td>
                  <span class="status-text" :class="user.is_verified ? 'status--verified' : 'status--unverified'">
                    {{ user.is_verified ? 'Terverifikasi' : 'Belum Terverifikasi' }}
                  </span>
                </td>
                <td class="user-date">{{ formatDate(user.created_at) }}</td>
                <td>
                  <button
                    v-if="user.role !== 'superadmin'"
                    class="verify-btn"
                    :class="user.is_verified ? 'verify-btn--verified' : 'verify-btn--unverified'"
                    @click="openVerifyModal(user, !user.is_verified)"
                  >
                    {{ user.is_verified ? 'Terverifikasi' : 'Verifikasi' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Verify/Revoke Modal -->
      <Modal :show="showVerifyModal" :title="verifyAction ? 'Verifikasi Akun' : 'Cabut Verifikasi'" size="sm" @close="showVerifyModal = false">
        <div class="verify-confirm">
          <div class="verify-icon">
            <CheckCircle v-if="verifyAction" :size="48" color="#059669" />
            <XCircle v-else :size="48" color="#ef4444" />
          </div>
          <p v-if="verifyAction">
            Verifikasi akun <strong>{{ verifyingUser?.name }}</strong> sebagai <strong class="role-highlight">{{ verifyingUser?.role }}</strong>?
          </p>
          <p v-else>
            Cabut verifikasi akun <strong>{{ verifyingUser?.name }}</strong>? Pengguna tidak akan bisa login.
          </p>
        </div>
        <template #footer>
          <Button variant="secondary" @click="showVerifyModal = false">Batal</Button>
          <Button
            :variant="verifyAction ? 'primary' : 'danger'"
            @click="handleVerify"
            :loading="verifyLoading"
          >
            {{ verifyAction ? 'Verifikasi' : 'Cabut' }}
          </Button>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>

<style scoped>
.users-page {
  max-width: 1100px;
}

.users-header {
  margin-bottom: 24px;
}

.users-header h1 {
  font-size: 24px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.users-subtitle {
  font-size: 14px;
  color: #64748b;
  margin: 4px 0 0;
}

.filter-card {
  margin-bottom: 20px;
}

.filters {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.filter-search {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 14px;
  color: #94a3b8;
  pointer-events: none;
}

.filter-input {
  width: 100%;
  padding: 10px 14px 10px 42px;
  border: 1.5px solid var(--color-border, #e2e8f0);
  border-radius: 8px;
  font-size: 14px;
  font-family: var(--font-primary, inherit);
  color: var(--color-text, #1e293b);
  background: var(--color-surface, #fff);
  outline: none;
  transition: all 0.2s ease;
}

.filter-input:focus {
  border-color: #2d6a9f;
  box-shadow: 0 0 0 3px rgba(45, 106, 159, 0.1);
}

.filter-input::placeholder {
  color: #94a3b8;
}

.filter-selects {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

.filter-select {
  padding: 8px 32px 8px 12px;
  border: 1.5px solid var(--color-border, #e2e8f0);
  border-radius: 8px;
  font-size: 13px;
  font-family: var(--font-primary, inherit);
  color: var(--color-text, #1e293b);
  background: var(--color-surface, #fff);
  outline: none;
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1.5L6 6.5L11 1.5' stroke='%2394a3b8' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 10px center;
  min-width: 160px;
}

.filter-select:focus {
  border-color: #2d6a9f;
}

.users-count {
  font-size: 13px;
  color: #64748b;
  margin-bottom: 12px;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #94a3b8;
}

.empty-state p {
  margin-top: 16px;
  font-size: 14px;
}

.users-table-wrap {
  background: var(--color-surface, #fff);
  border: 1px solid var(--color-border, #e2e8f0);
  border-radius: 12px;
  overflow-x: auto;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.users-table th {
  text-align: left;
  padding: 12px 16px;
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}

.users-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
}

.users-table tbody tr:last-child td {
  border-bottom: none;
}

.users-table tbody tr:hover {
  background: #fafbfc;
}

.user-name {
  font-weight: 500;
  color: #1e293b;
}

.user-email {
  color: #64748b;
}

.user-date {
  white-space: nowrap;
  color: #94a3b8;
  font-size: 13px;
}

.role-tag {
  display: inline-block;
  padding: 3px 10px;
  background: #f1f5f9;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  color: #475569;
  text-transform: capitalize;
}

.verify-btn {
  display: inline-flex;
  align-items: center;
  padding: 5px 14px;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
}

.status-text {
  font-size: 13px;
  font-weight: 500;
}

.status--verified {
  color: #16a34a;
}

.status--unverified {
  color: #dc2626;
}

.verify-btn--unverified {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.verify-btn--unverified:hover {
  background: #fee2e2;
}

.verify-btn--verified {
  background: #f0fdf4;
  color: #16a34a;
  border: 1px solid #bbf7d0;
}

.verify-btn--verified:hover {
  background: #dcfce7;
}

.verify-confirm {
  text-align: center;
  padding: 8px 0;
}

.verify-icon {
  margin-bottom: 16px;
}

.verify-confirm p {
  margin: 0;
  font-size: 14px;
  color: #334155;
  line-height: 1.6;
}

.role-highlight {
  text-transform: capitalize;
  color: #2d6a9f;
}
</style>
