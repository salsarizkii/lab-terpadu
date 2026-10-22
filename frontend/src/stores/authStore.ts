import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '../api/auth'
import { roleApi } from '../api/role'
import type { User } from '../types'
import type { RegisterData } from '../api/auth'

const ACTIVE_ROLE_KEY = 'active_role'
const ROLE_PERMISSIONS_KEY = 'role_permissions'

const getInitialToken = (): string | null => {
  return localStorage.getItem('token')
}

const getInitialUser = (): User | null => {
  const saved = localStorage.getItem('user')
  if (saved) {
    try {
      return JSON.parse(saved)
    } catch {
      return null
    }
  }
  return null
}

const getInitialActiveRole = (): string | null => {
  const saved = localStorage.getItem(ACTIVE_ROLE_KEY)
  return saved && saved !== 'superadmin' ? saved : null
}

const getInitialRolePermissions = (): Record<string, string[]> => {
  const saved = localStorage.getItem(ROLE_PERMISSIONS_KEY)
  if (!saved) return {}
  try {
    return JSON.parse(saved)
  } catch {
    return {}
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(getInitialToken())
  const user = ref<User | null>(getInitialUser())

  // Switch role ala "pilih channel": hanya berlaku untuk akun superadmin.
  // Akun tetap superadmin (login & backend tidak berubah), tampilan mengikuti role aktif.
  const activeRole = ref<string | null>(getInitialActiveRole())
  const rolePermissions = ref<Record<string, string[]>>(getInitialRolePermissions())

  const isAuthenticated = computed(() => !!token.value)
  const isSuperAdminAccount = computed(() => user.value?.role === 'superadmin')
  const effectiveRole = computed(() => {
    if (!isSuperAdminAccount.value) return user.value?.role
    return activeRole.value ?? 'superadmin'
  })
  const isSuperAdmin = computed(() => effectiveRole.value === 'superadmin')

  const userPermissions = computed(() => user.value?.permissions ?? [])

  function hasPermission(permission: string): boolean {
    if (!user.value) return false
    if (isSuperAdminAccount.value) {
      if (isSuperAdmin.value) return true
      const switchedPermissions = rolePermissions.value[effectiveRole.value ?? '']
      // Snapshot permission role belum ada → jangan kunci superadmin
      if (!switchedPermissions) return true
      return switchedPermissions.includes(permission)
    }
    return userPermissions.value.includes(permission)
  }

  function switchRole(roleName: string) {
    if (!isSuperAdminAccount.value) return
    if (roleName === 'superadmin') {
      activeRole.value = null
      localStorage.removeItem(ACTIVE_ROLE_KEY)
      return
    }
    activeRole.value = roleName
    localStorage.setItem(ACTIVE_ROLE_KEY, roleName)
  }

  async function fetchRoles() {
    if (!isSuperAdminAccount.value) return
    const roles = await roleApi.getRoles()
    const map: Record<string, string[]> = {}
    for (const role of roles) {
      map[role.name] = (role.permissions ?? []).map(p => p.name)
    }
    rolePermissions.value = map
    localStorage.setItem(ROLE_PERMISSIONS_KEY, JSON.stringify(map))
  }

  function resetRoleSwitch() {
    activeRole.value = null
    rolePermissions.value = {}
    localStorage.removeItem(ACTIVE_ROLE_KEY)
    localStorage.removeItem(ROLE_PERMISSIONS_KEY)
  }

  function setAuth(userData: User, tokenStr: string) {
    user.value = userData
    token.value = tokenStr
    localStorage.setItem('token', tokenStr)
    localStorage.setItem('user', JSON.stringify(userData))
    if (userData.role !== 'superadmin') resetRoleSwitch()
  }

  function clearAuth() {
    user.value = null
    token.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    resetRoleSwitch()
  }

  async function login(email: string, password: string) {
    const res = await authApi.login(email, password)
    setAuth(res.user, res.token)
  }

  async function register(data: RegisterData) {
    return await authApi.register(data)
  }

  async function logout() {
    try {
      await authApi.logout()
    } catch {
    } finally {
      clearAuth()
    }
  }

  async function fetchMe() {
    try {
      const userData = await authApi.me()
      user.value = userData
      localStorage.setItem('user', JSON.stringify(userData))
    } catch {
      clearAuth()
    }
  }

  async function updateProfile(data: { name: string; email: string; phone?: string }) {
    const res = await authApi.updateProfile(data)
    user.value = res.user
    localStorage.setItem('user', JSON.stringify(res.user))
    return res.message
  }

  async function changePassword(data: { current_password: string; password: string; password_confirmation: string }) {
    const res = await authApi.changePassword(data)
    return res.message
  }

  function initFromStorage() {
    const savedToken = localStorage.getItem('token')
    const savedUser = localStorage.getItem('user')
    if (savedToken && savedUser) {
      token.value = savedToken
      user.value = JSON.parse(savedUser)
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    isSuperAdmin,
    isSuperAdminAccount,
    effectiveRole,
    activeRole,
    rolePermissions,
    userPermissions,
    hasPermission,
    switchRole,
    fetchRoles,
    resetRoleSwitch,
    login,
    register,
    logout,
    fetchMe,
    updateProfile,
    changePassword,
    initFromStorage,
    setAuth,
    clearAuth,
  }
})
