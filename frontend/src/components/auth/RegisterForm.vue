<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import FormInput from '../common/FormInput.vue'
import Button from '../common/Button.vue'
import { roleApi } from '../../api/role'
import type { Role } from '../../types'

defineProps<{
  loading?: boolean
}>()

const emit = defineEmits<{
  submit: [data: {
    name: string
    email: string
    password: string
    password_confirmation: string
    phone?: string
    role: string
  }]
}>()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const phone = ref('')
const selectedRole = ref('')
const roles = ref<Role[]>([])
const loadingRoles = ref(true)
const dropdownOpen = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)

const roleColorMap: Record<string, { bg: string; text: string; dot: string }> = {
  user:       { bg: '#eff6ff', text: '#1e40af', dot: '#3b82f6' },
  admin:      { bg: '#fef3c7', text: '#92400e', dot: '#f59e0b' },
  analyst:    { bg: '#ecfdf5', text: '#065f46', dot: '#10b981' },
  superadmin: { bg: '#fce7f3', text: '#9d174d', dot: '#ec4899' },
}
const defaultColor = { bg: '#f1f5f9', text: '#475569', dot: '#94a3b8' }

function getRoleColor(roleName: string) {
  return roleColorMap[roleName] || defaultColor
}

const selectedRoleObj = computed(() => roles.value.find(r => r.name === selectedRole.value))

const selectedDisplay = computed(() => {
  if (!selectedRoleObj.value) return null
  return {
    ...selectedRoleObj.value,
    colors: getRoleColor(selectedRoleObj.value.name),
  }
})

function selectRole(roleName: string) {
  selectedRole.value = roleName
  dropdownOpen.value = false
}

function toggleDropdown() {
  dropdownOpen.value = !dropdownOpen.value
}

function handleClickOutside(e: Event) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
    dropdownOpen.value = false
  }
}

onMounted(async () => {
  document.addEventListener('click', handleClickOutside)
  try {
    roles.value = await roleApi.getPublicRoles()
    if (roles.value.length === 0) {
      selectedRole.value = 'user'
    } else {
      const userRole = roles.value.find(r => r.name === 'user')
      if (userRole) selectedRole.value = userRole.name
    }
  } catch {
    selectedRole.value = 'user'
  } finally {
    loadingRoles.value = false
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})

function formatRoleName(name: string) {
  return name
    .replace(/_/g, ' ')
    .replace(/\b\w/g, c => c.toUpperCase())
}

function handleSubmit() {
  emit('submit', {
    name: name.value,
    email: email.value,
    password: password.value,
    password_confirmation: passwordConfirmation.value,
    phone: phone.value || undefined,
    role: selectedRole.value,
  })
}
</script>

<template>
  <form class="auth-form" @submit.prevent="handleSubmit">
    <FormInput v-model="name" label="Nama Lengkap" placeholder="Nama lengkap" required />
    <FormInput v-model="email" label="Email" type="email" placeholder="contoh@email.com" required />
    <FormInput v-model="password" label="Password" type="password" placeholder="Min. 8 karakter" required />
    <FormInput v-model="passwordConfirmation" label="Konfirmasi Password" type="password" placeholder="Ulangi password" required />
    <FormInput v-model="phone" label="No. Telepon (opsional)" type="tel" placeholder="08xxxxxxxxxx" />

    <div class="role-section">
      <label class="role-label">
        Pilih Role <span class="role-required">*</span>
      </label>
      <div v-if="loadingRoles" class="role-loading">Memuat daftar role...</div>
      <div v-else-if="roles.length === 0" class="role-loading">Anda akan terdaftar sebagai <strong>user</strong>.</div>
      <div v-else ref="dropdownRef" class="role-dropdown-wrapper">
        <button
          type="button"
          class="role-dropdown-trigger"
          @click="toggleDropdown"
        >
          <template v-if="selectedDisplay">
            <span class="role-dot" :style="{ background: selectedDisplay.colors.dot }"></span>
            <span class="role-name">{{ formatRoleName(selectedDisplay.name) }}</span>
            <span v-if="selectedDisplay.requires_verification" class="role-badge-verif">Verifikasi</span>
          </template>
          <span v-else class="role-placeholder">Pilih role...</span>
          <svg class="role-chevron" :class="{ open: dropdownOpen }" width="14" height="14" viewBox="0 0 14 14" fill="none">
            <path d="M3.5 5.25L7 8.75L10.5 5.25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
        <Transition name="dropdown">
          <div v-if="dropdownOpen" class="role-dropdown-menu">
            <div
              v-for="role in roles"
              :key="role.name"
              class="role-dropdown-item"
              :class="{ active: selectedRole === role.name }"
              @click="selectRole(role.name)"
            >
              <span class="role-dot" :style="{ background: getRoleColor(role.name).dot }"></span>
              <span class="role-name">{{ formatRoleName(role.name) }}</span>
              <span v-if="role.requires_verification" class="role-badge-verif">Verifikasi</span>
              <svg v-if="selectedRole === role.name" class="role-check" width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M3.5 8.5L6.5 11.5L12.5 4.5" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </div>
        </Transition>
      </div>
    </div>

    <Button variant="primary" size="lg" block :loading="loading" type="submit">
      Daftar
    </Button>
  </form>
</template>

<style scoped>
.auth-form {
  display: flex;
  flex-direction: column;
}

.role-section {
  margin-bottom: 20px;
  position: relative;
}

.role-label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: #475569;
  margin-bottom: 10px;
  letter-spacing: 0.3px;
}

.role-required {
  color: #ef4444;
  margin-left: 2px;
}

.role-loading {
  text-align: center;
  padding: 20px;
  color: #94a3b8;
  font-size: 13px;
}

.role-dropdown-wrapper {
  position: relative;
}

.role-dropdown-trigger {
  width: 100%;
  padding: 10px 14px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  font-family: inherit;
  color: #1e293b;
  background: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: border-color 0.2s, box-shadow 0.2s;
  outline: none;
  text-align: left;
}

.role-dropdown-trigger:hover {
  border-color: #cbd5e1;
}

.role-dropdown-trigger:focus,
.role-dropdown-trigger.open {
  border-color: #2d6a9f;
  box-shadow: 0 0 0 3px rgba(45, 106, 159, 0.1);
}

.role-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.role-name {
  flex: 1;
  font-weight: 500;
}

.role-placeholder {
  color: #94a3b8;
  flex: 1;
}

.role-badge-verif {
  font-size: 10px;
  font-weight: 600;
  padding: 2px 7px;
  border-radius: 10px;
  background: #fef3c7;
  color: #92400e;
  letter-spacing: 0.3px;
}

.role-chevron {
  color: #94a3b8;
  transition: transform 0.2s;
  flex-shrink: 0;
}

.role-chevron.open {
  transform: rotate(180deg);
}

.role-dropdown-menu {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: #fff;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
  z-index: 50;
  overflow: hidden;
}

.role-dropdown-item {
  padding: 10px 14px;
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  font-size: 14px;
  color: #1e293b;
  transition: background 0.15s;
}

.role-dropdown-item:hover {
  background: #f8fafc;
}

.role-dropdown-item.active {
  background: #f0f7ff;
}

.role-check {
  flex-shrink: 0;
  margin-left: auto;
}

.dropdown-enter-active,
.dropdown-leave-active {
  transition: opacity 0.15s, transform 0.15s;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
