<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Users, Pencil, Trash2, Plus, AlertCircle } from '@lucide/vue'
import AppLayout from '../components/common/AppLayout.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import FormInput from '../components/common/FormInput.vue'
import FormTextarea from '../components/common/FormTextarea.vue'
import LoadingSpinner from '../components/common/LoadingSpinner.vue'
import Badge from '../components/common/Badge.vue'
import { roleApi } from '../api/role'
import type { Role, Permission } from '../types'
import { toast } from 'vue-sonner'

const roles = ref<Role[]>([])
const allPermissions = ref<Permission[]>([])
const loading = ref(true)

const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const modalLoading = ref(false)

const formName = ref('')
const formDescription = ref('')
const formPermissionIds = ref<number[]>([])
const editingRole = ref<Role | null>(null)
const deletingRole = ref<Role | null>(null)

const groupedPermissions = computed(() => {
  const groups: Record<string, Permission[]> = {}
  for (const p of allPermissions.value) {
    if (!groups[p.group]) groups[p.group] = []
    groups[p.group]!.push(p)
  }
  return groups
})

async function fetchRoles() {
  loading.value = true
  try {
    roles.value = await roleApi.getRoles()
  } catch (e) {
    console.error('fetchRoles error:', e)
    toast.error('Gagal memuat data role')
  } finally {
    loading.value = false
  }
}

async function fetchPermissions() {
  try {
    allPermissions.value = await roleApi.getPermissions()
  } catch (e) {
    console.error('fetchPermissions error:', e)
  }
}

onMounted(() => {
  fetchRoles()
  fetchPermissions()
})

function togglePermission(id: number) {
  const idx = formPermissionIds.value.indexOf(id)
  if (idx === -1) formPermissionIds.value.push(id)
  else formPermissionIds.value.splice(idx, 1)
}

function toggleGroup(groupName: string) {
  const permIds = (groupedPermissions.value[groupName] || []).map(p => p.id)
  const allChecked = permIds.every(id => formPermissionIds.value.includes(id))
  if (allChecked) {
    formPermissionIds.value = formPermissionIds.value.filter(id => !permIds.includes(id))
  } else {
    for (const id of permIds) {
      if (!formPermissionIds.value.includes(id)) formPermissionIds.value.push(id)
    }
  }
}

function isGroupChecked(groupName: string): boolean {
  const permIds = (groupedPermissions.value[groupName] || []).map(p => p.id)
  return permIds.length > 0 && permIds.every(id => formPermissionIds.value.includes(id))
}

function isGroupIndeterminate(groupName: string): boolean {
  const permIds = (groupedPermissions.value[groupName] || []).map(p => p.id)
  const checked = permIds.filter(id => formPermissionIds.value.includes(id))
  return checked.length > 0 && checked.length < permIds.length
}

function rolePermissionNames(role: Role): string[] {
  return (role.permissions || []).map(p => p.display_name)
}

function openCreateModal() {
  formName.value = ''
  formDescription.value = ''
  formPermissionIds.value = []
  showCreateModal.value = true
}

function openEditModal(role: Role) {
  editingRole.value = role
  formName.value = role.name
  formDescription.value = role.description || ''
  formPermissionIds.value = (role.permissions || []).map(p => p.id)
  showEditModal.value = true
}

function openDeleteModal(role: Role) {
  deletingRole.value = role
  showDeleteModal.value = true
}

async function createRole() {
  modalLoading.value = true
  try {
    await roleApi.createRole({
      name: formName.value,
      description: formDescription.value || undefined,
      permission_ids: formPermissionIds.value.length ? formPermissionIds.value : undefined,
    })
    toast.success('Role berhasil dibuat')
    showCreateModal.value = false
    await fetchRoles()
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal membuat role')
  } finally {
    modalLoading.value = false
  }
}

async function updateRole() {
  if (!editingRole.value) return
  modalLoading.value = true
  try {
    const payload: Record<string, any> = {
      description: formDescription.value,
      permission_ids: formPermissionIds.value,
    }
    if (!editingRole.value.is_system) {
      payload.name = formName.value
    }
    await roleApi.updateRole(editingRole.value.id, payload)
    toast.success('Role berhasil diperbarui')
    showEditModal.value = false
    await fetchRoles()
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal memperbarui role')
  } finally {
    modalLoading.value = false
  }
}

async function deleteRole() {
  if (!deletingRole.value) return
  modalLoading.value = true
  try {
    await roleApi.deleteRole(deletingRole.value.id)
    toast.success('Role berhasil dihapus')
    showDeleteModal.value = false
    await fetchRoles()
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menghapus role')
  } finally {
    modalLoading.value = false
  }
}
</script>

<template>
  <AppLayout>
    <div class="roles-page">
      <div class="roles-header">
        <div>
          <h1>Kelola Role</h1>
          <p class="roles-subtitle">Kelola role dan hak akses pengguna sistem</p>
        </div>
        <Button variant="primary" @click="openCreateModal" class="flex-align-center gap-2">
          <Plus :size="18" /> Buat Role Baru
        </Button>
      </div>

      <LoadingSpinner v-if="loading" size="lg" />

      <div v-else class="roles-grid">
        <div v-for="role in roles" :key="role.id" class="role-item">
          <div class="role-item__header">
            <div class="role-item__title-row">
              <h3 class="role-item__name">{{ role.name }}</h3>
              <Badge v-if="role.is_system" type="info" text="Sistem" />
            </div>
            <p class="role-item__desc">{{ role.description || 'Tidak ada deskripsi' }}</p>
            <div v-if="role.permissions && role.permissions.length" class="role-item__perms">
              <span v-for="p in role.permissions.slice(0, 4)" :key="p.id" class="perm-tag">{{ p.display_name }}</span>
              <span v-if="role.permissions.length > 4" class="perm-tag perm-tag--more">+{{ role.permissions.length - 4 }} lagi</span>
            </div>
          </div>
          <div class="role-item__footer">
            <span class="role-item__count">
              <Users :size="16" />
              {{ role.users_count ?? 0 }} pengguna
            </span>
            <div class="role-item__actions">
              <button class="role-action-btn role-action-btn--edit" @click="openEditModal(role)" title="Edit role">
                <Pencil :size="16" />
              </button>
              <button v-if="!role.is_system" class="role-action-btn role-action-btn--delete" @click="openDeleteModal(role)" title="Hapus role">
                <Trash2 :size="16" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Create Modal -->
      <Modal :show="showCreateModal" title="Buat Role Baru" size="lg" @close="showCreateModal = false">
        <form @submit.prevent="createRole">
          <FormInput v-model="formName" label="Nama Role" placeholder="contoh: manager, staff_lab" required />
          <p class="form-hint">Gunakan huruf kecil dan underscore, tanpa spasi.</p>
          <FormTextarea v-model="formDescription" label="Deskripsi" placeholder="Jelaskan tugas dan tanggung jawab role ini" />

          <div class="perm-section">
            <label class="perm-label">Hak Akses</label>
            <div v-for="(perms, groupName) in groupedPermissions" :key="groupName" class="perm-group">
              <label class="perm-group__header">
                <input type="checkbox" :checked="isGroupChecked(groupName)" :indeterminate="isGroupIndeterminate(groupName)" @change="toggleGroup(groupName)" />
                <span class="perm-group__name">{{ groupName }}</span>
              </label>
              <div class="perm-group__items">
                <label v-for="p in perms" :key="p.id" class="perm-check">
                  <input type="checkbox" :checked="formPermissionIds.includes(p.id)" @change="togglePermission(p.id)" />
                  <span>{{ p.display_name }}</span>
                </label>
              </div>
            </div>
          </div>

          <div class="modal-actions">
            <Button variant="secondary" @click="showCreateModal = false">Batal</Button>
            <Button variant="primary" type="submit" :loading="modalLoading">Simpan</Button>
          </div>
        </form>
      </Modal>

      <!-- Edit Modal -->
      <Modal :show="showEditModal" title="Edit Role" size="lg" @close="showEditModal = false">
        <form @submit.prevent="updateRole">
          <FormInput v-model="formName" label="Nama Role" :disabled="editingRole?.is_system" placeholder="Nama role" required />
          <p v-if="editingRole?.is_system" class="form-hint form-hint--warn">Nama role sistem tidak dapat diubah.</p>
          <FormTextarea v-model="formDescription" label="Deskripsi" placeholder="Jelaskan tugas dan tanggung jawab role ini" />

          <div class="perm-section">
            <label class="perm-label">Hak Akses</label>
            <div v-for="(perms, groupName) in groupedPermissions" :key="groupName" class="perm-group">
              <label class="perm-group__header">
                <input type="checkbox" :checked="isGroupChecked(groupName)" :indeterminate="isGroupIndeterminate(groupName)" @change="toggleGroup(groupName)" />
                <span class="perm-group__name">{{ groupName }}</span>
              </label>
              <div class="perm-group__items">
                <label v-for="p in perms" :key="p.id" class="perm-check">
                  <input type="checkbox" :checked="formPermissionIds.includes(p.id)" @change="togglePermission(p.id)" />
                  <span>{{ p.display_name }}</span>
                </label>
              </div>
            </div>
          </div>

          <div class="modal-actions">
            <Button variant="secondary" @click="showEditModal = false">Batal</Button>
            <Button variant="primary" type="submit" :loading="modalLoading">Simpan</Button>
          </div>
        </form>
      </Modal>

      <!-- Delete Confirmation Modal -->
      <Modal :show="showDeleteModal" title="Hapus Role" size="sm" @close="showDeleteModal = false">
        <div class="delete-confirm">
          <div class="delete-icon">
            <AlertCircle :size="48" color="#ef4444" stroke-width="1.5" />
          </div>
          <p>Apakah Anda yakin ingin menghapus role <strong>{{ deletingRole?.name }}</strong>?</p>
          <p class="delete-warn">Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <template #footer>
          <Button variant="secondary" @click="showDeleteModal = false">Batal</Button>
          <Button variant="danger" @click="deleteRole" :loading="modalLoading">Hapus</Button>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>

<style scoped>
.roles-page { max-width: 960px; }
.roles-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 24px; gap: 16px; }
.roles-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
.roles-subtitle { font-size: 14px; color: #64748b; margin: 4px 0 0; }
.flex-align-center { display: flex; align-items: center; }
.gap-2 { gap: 8px; }

.roles-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; }
.role-item { background: var(--color-surface, #fff); border: 1px solid var(--color-border, #e2e8f0); border-radius: 12px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; transition: all 0.2s ease; }
.role-item:hover { border-color: #cbd5e1; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06); }
.role-item__header { margin-bottom: 16px; }
.role-item__title-row { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
.role-item__name { font-size: 16px; font-weight: 600; color: #1e293b; margin: 0; text-transform: capitalize; }
.role-item__desc { font-size: 13px; color: #64748b; margin: 0; line-height: 1.5; }
.role-item__perms { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 8px; }
.perm-tag { font-size: 10px; padding: 2px 8px; border-radius: 20px; background: #eff6ff; color: #2563eb; font-weight: 500; }
.perm-tag--more { background: #f1f5f9; color: #64748b; }

.role-item__footer { display: flex; align-items: center; justify-content: space-between; padding-top: 14px; border-top: 1px solid #f1f5f9; }
.role-item__count { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #94a3b8; }
.role-item__actions { display: flex; gap: 4px; }
.role-action-btn { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border: none; border-radius: 8px; background: transparent; cursor: pointer; transition: all 0.15s ease; color: #94a3b8; }
.role-action-btn:hover { background: #f1f5f9; }
.role-action-btn--edit:hover { color: #2563eb; background: #eff6ff; }
.role-action-btn--delete:hover { color: #ef4444; background: #fef2f2; }

.form-hint { margin: -8px 0 16px; font-size: 12px; color: #94a3b8; }
.form-hint--warn { color: #d97706; }

.perm-section { margin-top: 16px; margin-bottom: 8px; }
.perm-label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 10px; }
.perm-group { margin-bottom: 12px; }
.perm-group__header { display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 6px 0; font-size: 13px; font-weight: 600; color: #1e293b; user-select: none; }
.perm-group__header input[type="checkbox"] { accent-color: #2563eb; width: 16px; height: 16px; }
.perm-group__name { text-transform: capitalize; }
.perm-group__items { display: flex; flex-direction: column; gap: 4px; padding-left: 24px; }
.perm-check { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #475569; cursor: pointer; padding: 3px 0; user-select: none; }
.perm-check input[type="checkbox"] { accent-color: #2563eb; width: 15px; height: 15px; }

.modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px; }
.delete-confirm { text-align: center; padding: 8px 0; }
.delete-icon { margin-bottom: 16px; }
.delete-confirm p { margin: 0 0 8px; font-size: 14px; color: #334155; }
.delete-warn { color: #94a3b8 !important; font-size: 13px !important; }
</style>
