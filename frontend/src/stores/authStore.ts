import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '../api/auth'
import type { User } from '../types'
import type { RegisterData } from '../api/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value)
  const isSuperAdmin = computed(() => user.value?.role === 'superadmin')
  const isAdmin = computed(() => user.value?.role === 'admin' || user.value?.role === 'superadmin')
  const isAnalyst = computed(() => user.value?.role === 'analyst')
  const isUser = computed(() => user.value?.role === 'user')

  const userPermissions = computed(() => user.value?.permissions ?? [])

  function hasPermission(permission: string): boolean {
    if (isSuperAdmin.value) return true
    return userPermissions.value.includes(permission)
  }

  function setAuth(userData: User, tokenStr: string) {
    user.value = userData
    token.value = tokenStr
    localStorage.setItem('token', tokenStr)
    localStorage.setItem('user', JSON.stringify(userData))
  }

  function clearAuth() {
    user.value = null
    token.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
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
    isAdmin,
    isAnalyst,
    isUser,
    userPermissions,
    hasPermission,
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
