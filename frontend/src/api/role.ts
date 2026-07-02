import api from './axios'
import type { Role, Permission } from '../types'

export const roleApi = {
  // Public: get roles for registration
  async getPublicRoles(): Promise<Role[]> {
    const { data } = await api.get<Role[]>('/roles')
    return data
  },

  // Admin: get all roles with user counts
  async getRoles(): Promise<Role[]> {
    const { data } = await api.get<Role[]>('/admin/roles')
    return data
  },

  async getRole(id: number): Promise<Role> {
    const { data } = await api.get<Role>(`/admin/roles/${id}`)
    return data
  },

  async createRole(payload: { name: string; description?: string; requires_verification?: boolean; permission_ids?: number[] }): Promise<{ message: string; data: Role }> {
    const { data } = await api.post<{ message: string; data: Role }>('/admin/roles', payload)
    return data
  },

  async updateRole(id: number, payload: { name?: string; description?: string; requires_verification?: boolean; permission_ids?: number[] }): Promise<{ message: string; data: Role }> {
    const { data } = await api.put<{ message: string; data: Role }>(`/admin/roles/${id}`, payload)
    return data
  },

  async deleteRole(id: number): Promise<{ message: string }> {
    const { data } = await api.delete<{ message: string }>(`/admin/roles/${id}`)
    return data
  },

  // Permissions
  async getPermissions(): Promise<Permission[]> {
    const { data } = await api.get<Permission[]>('/admin/roles/permissions/list')
    return data
  },
}
