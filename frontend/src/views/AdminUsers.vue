<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import SkeletonTable from '../components/common/SkeletonTable.vue'
import FilterDropdown from '../components/common/FilterDropdown.vue'
import Dropdown from '../components/common/Dropdown.vue'
import DropdownItem from '../components/common/DropdownItem.vue'
import { Search, Users as UsersIcon, ShieldCheck, ShieldOff, Ban } from '@lucide/vue'
import { adminApi } from '../api/admin'
import { roleApi } from '../api/role'
import type { User, Role } from '../types'
import { toast } from 'vue-sonner'
import { formatDateTime } from '../utils/date'

const users = ref<User[]>([])
const roles = ref<Role[]>([])
const loading = ref(true)

const searchQuery = ref('')
const filterRole = ref('')
const filterVerified = ref('')

const verifiedOptions = [
  { value: '', label: 'Semua Status' },
  { value: 'true', label: 'Terverifikasi' },
  { value: 'false', label: 'Diajukan Verifikasi' },
]

// Modal states
const showModal = ref(false)
const modalType = ref<'verify' | 'revoke' | 'deactivate'>('verify')
const selectedUser = ref<User | null>(null)
const modalLoading = ref(false)

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

function applyFilters() { fetchData() }

function resetFilters() {
  searchQuery.value = ''
  filterRole.value = ''
  filterVerified.value = ''
  fetchData()
}

function openModal(user: User, type: 'verify' | 'revoke' | 'deactivate') {
  selectedUser.value = user
  modalType.value = type
  showModal.value = true
}

async function handleConfirm() {
  if (!selectedUser.value) return
  modalLoading.value = true
  try {
    let res
    if (modalType.value === 'verify') {
      res = await adminApi.verifyUser(selectedUser.value.id, true)
    } else if (modalType.value === 'revoke') {
      res = await adminApi.verifyUser(selectedUser.value.id, false)
    } else {
      res = await adminApi.deactivateUser(selectedUser.value.id)
    }
    toast.success(res.message)
    showModal.value = false
    await fetchData()
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal memproses')
  } finally {
    modalLoading.value = false
  }
}

const modalConfig = {
  verify: { title: 'Verifikasi Akun', icon: ShieldCheck, iconColor: '#059669', btnVariant: 'primary' as const, btnText: 'Verifikasi' },
  revoke: { title: 'Cabut Verifikasi', icon: ShieldOff, iconColor: '#dc2626', btnVariant: 'danger' as const, btnText: 'Cabut' },
  deactivate: { title: 'Nonaktifkan Akun', icon: Ban, iconColor: '#dc2626', btnVariant: 'danger' as const, btnText: 'Nonaktifkan' },
}

function formatDate(dateStr: string) {
  return formatDateTime(dateStr)
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
            <FilterDropdown
              v-model="filterRole"
              :options="[{ value: '', label: 'Semua Role' }, ...roles.map(r => ({ value: r.name, label: r.name }))]"
              placeholder="Semua Role"
            />
            <FilterDropdown
              v-model="filterVerified"
              :options="verifiedOptions"
              placeholder="Semua Status"
            />
            <Button variant="secondary" size="sm" @click="applyFilters">Cari</Button>
          </div>
        </div>
      </Card>

      <SkeletonTable v-if="loading" :cols="7" :rows="5" />

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
              <tr v-for="user in users" :key="user.id" :data-user-id="user.id">
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
                  <div v-if="user.role !== 'superadmin'" class="action-cell">
                    <Dropdown teleport placement="bottom-end">
                      <template #trigger="{ toggle }">
                        <button class="dots-btn" @click="toggle">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                        </button>
                      </template>
                      <template #default="{ close }">
                        <DropdownItem v-if="!user.is_verified" :icon="ShieldCheck" @click="openModal(user, 'verify'); close()">Verifikasi</DropdownItem>
                        <DropdownItem v-if="user.is_verified" :icon="ShieldOff" @click="openModal(user, 'revoke'); close()">Cabut Verifikasi</DropdownItem>
                        <DropdownItem v-if="user.is_verified" :icon="Ban" variant="danger" @click="openModal(user, 'deactivate'); close()">Nonaktifkan Akun</DropdownItem>
                      </template>
                    </Dropdown>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Confirm Modal -->
      <Modal :show="showModal" :title="modalConfig[modalType].title" size="sm" @close="showModal = false">
        <div class="verify-confirm">
          <div class="verify-icon">
            <component :is="modalConfig[modalType].icon" :size="48" :color="modalConfig[modalType].iconColor" />
          </div>
          <p v-if="modalType === 'verify'">
            Verifikasi akun <strong>{{ selectedUser?.name }}</strong> sebagai <strong class="role-highlight">{{ selectedUser?.role }}</strong>?
          </p>
          <p v-else-if="modalType === 'revoke'">
            Cabut verifikasi akun <strong>{{ selectedUser?.name }}</strong>? Pengguna tidak akan bisa login.
          </p>
          <p v-else>
            Nonaktifkan akun <strong>{{ selectedUser?.name }}</strong>? Pengguna tidak akan bisa login sampai diaktifkan kembali.
          </p>
        </div>
        <template #footer>
          <Button variant="secondary" @click="showModal = false">Batal</Button>
          <Button :variant="modalConfig[modalType].btnVariant" @click="handleConfirm" :loading="modalLoading">
            {{ modalConfig[modalType].btnText }}
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

.action-cell {
  position: relative;
}

.dots-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  border-radius: 6px;
  cursor: pointer;
  color: #64748b;
  transition: all 0.15s ease;
}

.dots-btn:hover {
  background: #f1f5f9;
  color: #334155;
}
</style>
