import type { BeritaAcaraStatus } from '../types'

export type BadgeType = 'default' | 'danger' | 'success' | 'warning' | 'info'

export const baStatusMap: Record<string, string> = {
  draft: 'Draft',
  pending: 'Menunggu Admin',
  revision: 'Revisi',
  admin_verified: 'Diverifikasi Admin',
  penyelia_verified: 'Diverifikasi Penyelia',
  rekap_ready: 'Menunggu Manager Teknis',
  completed: 'Selesai',
  rejected: 'Ditolak',
}

export const baStatusTypeMap: Record<string, BadgeType> = {
  draft: 'default',
  pending: 'warning',
  revision: 'warning',
  admin_verified: 'info',
  penyelia_verified: 'info',
  rekap_ready: 'warning',
  completed: 'success',
  rejected: 'danger',
}

/** Status BA yang sedang berjalan (bukan draft/rejected/selesai) */
export function isBaActive(status: BeritaAcaraStatus): boolean {
  return ['pending', 'revision', 'admin_verified', 'penyelia_verified', 'rekap_ready'].includes(status)
}

/** Status BA yang bisa dihapus user (hanya draft) */
export function isBaEditable(status: BeritaAcaraStatus): boolean {
  return ['draft', 'pending', 'revision'].includes(status)
}
