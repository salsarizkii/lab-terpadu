const FIELD_LABELS: Record<string, string> = {
  name: 'Nama lengkap',
  email: 'Email',
  password: 'Password',
  phone: 'No. Telepon',
  role: 'Role',
}

function fieldLabel(field?: string): string {
  return field ? (FIELD_LABELS[field] ?? field) : 'Field'
}

function translateValidationMessage(msg: string, field?: string): string {
  const lower = msg.toLowerCase()
  if (lower.includes('already been taken')) return 'Email sudah digunakan, silakan gunakan email lain'
  if (lower.includes('at least 8 characters')) return 'Password minimal 8 karakter'
  if (lower.includes('confirmation does not match')) return 'Konfirmasi password tidak sama'
  if (lower.includes('valid email')) return 'Format email tidak valid'
  if (lower.includes('required')) return `${fieldLabel(field)} wajib diisi`
  if (lower.includes('min 8')) return 'Password minimal 8 karakter'
  return msg
}

/**
 * Ekstrak pesan error dari response API (axios) menjadi string yang bisa ditampilkan.
 * - Pesan custom (misal: login salah) dipakai langsung.
 * - Error validasi Laravel (errors.{field}) diterjemahkan ke Bahasa Indonesia.
 */
export function extractApiError(e: unknown, fallback = 'Terjadi kesalahan'): string {
  const data = (e as any)?.response?.data
  if (!data) return fallback

  if (data.errors && typeof data.errors === 'object') {
    const keys = Object.keys(data.errors)
    const field = keys[0]
    if (field) {
      const raw = Array.isArray(data.errors[field]) ? data.errors[field][0] : data.errors[field]
      if (raw) return translateValidationMessage(String(raw), field)
    }
    return data.message || fallback
  }

  if (typeof data.message === 'string' && data.message) return data.message
  return fallback
}