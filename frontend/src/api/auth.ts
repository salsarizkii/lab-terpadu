import api from './axios'
import type { User } from '../types'

export interface LoginResponse {
  user: User
  token: string
}

export interface RegisterData {
  name: string
  email: string
  password: string
  password_confirmation: string
  phone?: string
  role: string
}

export interface RegisterResponse {
  message: string
  needs_verification: boolean
}

export interface ProfileData {
  name: string
  email: string
  phone?: string
}

export interface ChangePasswordData {
  current_password: string
  password: string
  password_confirmation: string
}

export const authApi = {
  async login(email: string, password: string): Promise<LoginResponse> {
    const { data } = await api.post<LoginResponse>('/auth/login', { email, password })
    return data
  },

  async register(data: RegisterData): Promise<RegisterResponse> {
    const { data: res } = await api.post<RegisterResponse>('/auth/register', data)
    return res
  },

  async logout(): Promise<void> {
    await api.post('/auth/logout')
  },

  async me(): Promise<User> {
    const { data } = await api.get<User>('/auth/me')
    return data
  },

  async updateProfile(data: ProfileData): Promise<{ message: string; user: User }> {
    const { data: res } = await api.put<{ message: string; user: User }>('/auth/profile', data)
    return res
  },

  async changePassword(data: ChangePasswordData): Promise<{ message: string }> {
    const { data: res } = await api.put<{ message: string }>('/auth/password', data)
    return res
  },
}
