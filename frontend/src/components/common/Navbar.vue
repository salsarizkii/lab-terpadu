<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '../../stores/authStore'
import { useRouter } from 'vue-router'
import { FlaskConical, LogOut, User, Lock, ChevronDown } from '@lucide/vue'
import { toast } from 'vue-sonner'
import Modal from './Modal.vue'
import Button from './Button.vue'
import Dropdown from './Dropdown.vue'
import DropdownItem from './DropdownItem.vue'
import DropdownDivider from './DropdownDivider.vue'

const auth = useAuthStore()
const router = useRouter()

const showProfileModal = ref(false)
const profileName = ref('')
const profileEmail = ref('')
const profilePhone = ref('')
const profileSaving = ref(false)

const showPasswordModal = ref(false)
const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const passwordSaving = ref(false)

function handleEditProfile() {
  profileName.value = auth.user?.name || ''
  profileEmail.value = auth.user?.email || ''
  profilePhone.value = auth.user?.phone || ''
  showProfileModal.value = true
}

function handleChangePassword() {
  currentPassword.value = ''
  newPassword.value = ''
  confirmPassword.value = ''
  showPasswordModal.value = true
}

async function submitProfile() {
  if (!profileName.value.trim() || !profileEmail.value.trim()) return
  profileSaving.value = true
  try {
    await auth.updateProfile({
      name: profileName.value.trim(),
      email: profileEmail.value.trim(),
      phone: profilePhone.value.trim() || undefined,
    })
    toast.success('Profile berhasil diperbarui')
    showProfileModal.value = false
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal memperbarui profile')
  } finally {
    profileSaving.value = false
  }
}

async function submitPassword() {
  if (!currentPassword.value || !newPassword.value || newPassword.value !== confirmPassword.value) return
  passwordSaving.value = true
  try {
    await auth.changePassword({
      current_password: currentPassword.value,
      password: newPassword.value,
      password_confirmation: confirmPassword.value,
    })
    toast.success('Password berhasil diubah')
    showPasswordModal.value = false
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal mengubah password')
  } finally {
    passwordSaving.value = false
  }
}

async function handleLogout() {
  closeDropdown()
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <nav class="navbar">
    <div class="navbar__brand" @click="router.push('/dashboard')">
      <div class="navbar__logo">
        <div class="logo-box">
          <FlaskConical :size="18" color="white" stroke-width="2.5" />
        </div>
      </div>
      <div class="navbar__brand-text">
        <span class="navbar__title">Lab Terpadu</span>
        <span class="navbar__subtitle">Sampel Penelitian</span>
      </div>
    </div>
    <div class="navbar__right">
      <Dropdown teleport placement="bottom-end">
        <template #trigger="{ toggle, isOpen }">
          <div class="navbar__user" @click="toggle">
            <div class="navbar__avatar">{{ auth.user?.name?.charAt(0)?.toUpperCase() || 'U' }}</div>
            <div class="navbar__user-info">
              <span class="navbar__user-name">{{ auth.user?.name || 'User' }}</span>
              <span class="navbar__user-role">{{ auth.user?.role || '-' }}</span>
            </div>
            <ChevronDown :size="16" class="navbar__chevron" :class="{ 'navbar__chevron--open': isOpen }" />
          </div>
        </template>
        <template #default="{ close }">
          <div class="user-dropdown__header">
            <div class="user-dropdown__avatar">{{ auth.user?.name?.charAt(0)?.toUpperCase() || 'U' }}</div>
            <div>
              <div class="user-dropdown__name">{{ auth.user?.name }}</div>
              <div class="user-dropdown__email">{{ auth.user?.email }}</div>
            </div>
          </div>
          <DropdownDivider />
          <DropdownItem :icon="User" @click="handleEditProfile(); close()">Edit Profile</DropdownItem>
          <DropdownItem :icon="Lock" @click="handleChangePassword(); close()">Ganti Password</DropdownItem>
          <DropdownDivider />
          <DropdownItem :icon="LogOut" disabled>Keluar</DropdownItem>
        </template>
      </Dropdown>
    </div>
  </nav>

  <!-- Edit Profile Modal -->
  <Modal :show="showProfileModal" title="Edit Profile" @close="showProfileModal = false">
    <div class="profile-form">
      <div class="profile-form__field">
        <label class="profile-form__label">Nama</label>
        <input v-model="profileName" class="profile-form__input" type="text" placeholder="Nama lengkap" />
      </div>
      <div class="profile-form__field">
        <label class="profile-form__label">Email</label>
        <input v-model="profileEmail" class="profile-form__input" type="email" placeholder="Email" />
      </div>
      <div class="profile-form__field">
        <label class="profile-form__label">Telepon</label>
        <input v-model="profilePhone" class="profile-form__input" type="text" placeholder="Nomor telepon (opsional)" />
      </div>
    </div>
    <template #footer>
      <Button variant="secondary" @click="showProfileModal = false">Batal</Button>
      <Button variant="primary" :loading="profileSaving" @click="submitProfile">Simpan</Button>
    </template>
  </Modal>

  <!-- Change Password Modal -->
  <Modal :show="showPasswordModal" title="Ganti Password" @close="showPasswordModal = false">
    <div class="profile-form">
      <div class="profile-form__field">
        <label class="profile-form__label">Password Lama</label>
        <input v-model="currentPassword" class="profile-form__input" type="password" placeholder="Masukkan password lama" />
      </div>
      <div class="profile-form__field">
        <label class="profile-form__label">Password Baru</label>
        <input v-model="newPassword" class="profile-form__input" type="password" placeholder="Minimal 8 karakter" />
      </div>
      <div class="profile-form__field">
        <label class="profile-form__label">Konfirmasi Password Baru</label>
        <input v-model="confirmPassword" class="profile-form__input" type="password" placeholder="Ulangi password baru" />
      </div>
    </div>
    <template #footer>
      <Button variant="secondary" @click="showPasswordModal = false">Batal</Button>
      <Button
        variant="primary"
        :loading="passwordSaving"
        :disabled="!currentPassword || !newPassword || newPassword !== confirmPassword"
        @click="submitPassword"
      >
        Ubah Password
      </Button>
    </template>
  </Modal>
</template>

<style scoped>
.navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 64px;
  padding: 0 28px;
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  backdrop-filter: blur(8px);
}
.navbar__brand {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
}
.navbar__logo {
  display: flex;
}
.logo-box {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: var(--color-accent);
  display: flex;
  align-items: center;
  justify-content: center;
}
.navbar__brand-text {
  display: flex;
  flex-direction: column;
}
.navbar__title {
  font-size: 16px;
  font-weight: 700;
  color: var(--color-text);
  line-height: 1.2;
}
.navbar__subtitle {
  font-size: 10px;
  font-weight: 400;
  color: var(--color-text-muted);
  letter-spacing: 0.5px;
  text-transform: uppercase;
}
.navbar__right {
  display: flex;
  align-items: center;
  gap: 16px;
  position: relative;
}
.navbar__user {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 8px;
  transition: background 0.15s ease;
}
.navbar__user:hover {
  background: var(--color-bg);
}
.navbar__avatar {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, var(--color-accent), var(--color-info));
  color: var(--color-text-white);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 14px;
}
.navbar__user-info {
  display: flex;
  flex-direction: column;
}
.navbar__user-name {
  font-size: 13px;
  font-weight: 500;
  color: var(--color-text);
  line-height: 1.2;
}
.navbar__user-role {
  font-size: 11px;
  color: var(--color-text-muted);
  text-transform: capitalize;
}
.navbar__chevron {
  color: var(--color-text-muted);
  transition: transform 0.2s ease;
}
.navbar__chevron--open {
  transform: rotate(180deg);
}
</style>

<style>
.user-dropdown__header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
}
.user-dropdown__avatar {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, #2d6a9f, #4a9fd5);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 14px;
  flex-shrink: 0;
}
.user-dropdown__name {
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
}
.user-dropdown__email {
  font-size: 11px;
  color: #94a3b8;
}
.profile-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.profile-form__field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.profile-form__label {
  font-size: 13px;
  font-weight: 500;
  color: #475569;
}
.profile-form__input {
  padding: 9px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  color: #1e293b;
  background: white;
  outline: none;
  transition: border-color 0.15s;
}
.profile-form__input:focus {
  border-color: var(--color-accent, #2d6a9f);
}
</style>
