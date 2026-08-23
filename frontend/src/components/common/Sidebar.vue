<script setup lang="ts">
import { useAuthStore } from '../../stores/authStore'
import { useRoute } from 'vue-router'
import { computed, onMounted } from 'vue'
import {
  LayoutDashboard,
  FileText,
  CreditCard,
  FlaskConical,
  ClipboardList,
  KeyRound,
  Users,
  Award,
  BadgePercent,
  History
} from '@lucide/vue'

const auth = useAuthStore()
const route = useRoute()

onMounted(() => {
  if (!auth.user) {
    auth.initFromStorage()
  }
})

const menuItems = computed(() => {
  const items: Array<{ label: string; path: string; icon: any }> = []

  const canAdminBa = auth.hasPermission('berita_acara_riwayat_semua') || auth.hasPermission('berita_acara_verify') || auth.hasPermission('berita_acara_assign')

  // Dashboard — tentukan path dari level permission tertinggi
  if (auth.hasPermission('dashboard')) {
    let dashPath = '/user/dashboard'
    if (auth.hasPermission('berita_acara_riwayat_semua') || auth.hasPermission('hasil_pengujian_riwayat_semua') || auth.hasPermission('payment_riwayat_semua') || auth.hasPermission('user_view')) {
      dashPath = '/admin/dashboard'
    } else if (auth.hasPermission('hasil_pengujian_rekap')) {
      dashPath = '/analyst/dashboard'
    }
    items.push({ label: 'Dashboard', path: dashPath, icon: LayoutDashboard })
  }

  // Berita Acara
  if (canAdminBa) {
    items.push({ label: 'Berita Acara', path: '/admin/berita-acara', icon: FileText })
  } else if (auth.hasPermission('berita_acara_create') || auth.hasPermission('berita_acara_riwayat_personal')) {
    items.push({ label: 'Berita Acara', path: '/user/berita-acara', icon: FileText })
  }

  // Pembayaran
  if (auth.hasPermission('payment_riwayat_semua') || auth.hasPermission('payment_upload') || auth.hasPermission('payment_discount') || auth.hasPermission('payment_riwayat_personal')) {
    const payPath = auth.hasPermission('payment_riwayat_semua') ? '/admin/payments' : '/user/payments'
    items.push({ label: 'Pembayaran', path: payPath, icon: CreditCard })
  }

  // Hasil Pengujian
  if (auth.hasPermission('hasil_pengujian_rekap') || auth.hasPermission('hasil_pengujian_riwayat_semua') || auth.hasPermission('hasil_pengujian_riwayat_personal')) {
    const samplePath = auth.hasPermission('hasil_pengujian_riwayat_semua') ? '/admin/samples' : (auth.hasPermission('hasil_pengujian_rekap') ? '/analyst/samples' : '/user/samples')
    items.push({ label: 'Hasil Pengujian', path: samplePath, icon: FlaskConical })
  }

  // Sertifikat
  if (auth.hasPermission('certificate_view') || auth.hasPermission('certificate_download')) {
    items.push({ label: 'Sertifikat', path: '/user/certificates', icon: Award })
  }

  // Diskon
  if (auth.hasPermission('discount_verifikasi') || auth.hasPermission('discount_riwayat_semua')) {
    items.push({ label: 'Ajuan Diskon', path: '/admin/discounts', icon: BadgePercent })
  }

  // Kelola Form
  if (auth.hasPermission('form_manage_berita_acara') || auth.hasPermission('form_manage_rekap_analis') || auth.hasPermission('form_manage_perhitungan_biaya') || auth.hasPermission('form_manage_kelola_harga_instrumen')) {
    items.push({ label: 'Kelola Form', path: '/admin/forms', icon: ClipboardList })
  }

  // Kelola Role
  if (auth.isSuperAdmin) {
    items.push({ label: 'Kelola Role', path: '/admin/roles', icon: KeyRound })
  }

  // Kelola Pengguna
  if (auth.hasPermission('user_view')) {
    items.push({ label: 'Kelola Pengguna', path: '/admin/users', icon: Users })
  }

  // Log Aktivitas
  if (auth.hasPermission('user_view')) {
    items.push({ label: 'Log Aktivitas', path: '/admin/activity-log', icon: History })
  }

  return items
})

function isItemActive(path: string): boolean {
  if (route.path === path) return true
  if (path !== '/' && path !== '/dashboard' && route.path.startsWith(path)) return true
  return false
}
</script>

<template>
  <aside class="sidebar">
    <nav class="sidebar__nav">
      <router-link
        v-for="item in menuItems"
        :key="item.path"
        :to="item.path"
        class="sidebar__item"
        :class="{ 'sidebar__item--active': isItemActive(item.path) }"
      >
        <component :is="item.icon" class="sidebar__icon" :size="20" />
        <span class="sidebar__label">{{ item.label }}</span>
      </router-link>
    </nav>
    <div class="sidebar__footer">
      <div class="sidebar__version">v1.0.0</div>
    </div>
  </aside>
</template>

<style scoped>
.sidebar {
  width: 240px;
  background: var(--color-surface, #ffffff);
  border-right: 1px solid var(--color-border, #e2e8f0);
  height: calc(100vh - 64px);
  position: fixed;
  top: 64px;
  left: 0;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: width 0.3s ease;
  z-index: 100;
  box-shadow: 2px 0 12px rgba(15, 23, 42, 0.04);
}
.sidebar__nav {
  display: flex;
  flex-direction: column;
  padding: 12px;
  gap: 4px;
}
.sidebar__item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  font-size: 14px;
  color: var(--color-text-secondary, #475569);
  text-decoration: none;
  border-radius: var(--radius-sm, 8px);
  transition: all 0.15s ease;
  font-weight: 500;
}
.sidebar__item:hover {
  background: var(--color-bg, #f8fafc);
  color: var(--color-text, #0f172a);
}
.sidebar__item--active {
  background: #dbeafe !important;
  color: #1d4ed8 !important;
  font-weight: 600 !important;
}
.sidebar__icon {
  flex-shrink: 0;
  color: #64748b;
}
.sidebar__item:hover .sidebar__icon {
  color: #1e293b;
}
.sidebar__item--active .sidebar__icon {
  color: #1d4ed8 !important;
}
.sidebar__label {
  line-height: 1;
}
.sidebar__footer {
  padding: 16px 20px;
  border-top: 1px solid var(--color-border-light, #f1f5f9);
}
.sidebar__version {
  font-size: 11px;
  color: var(--color-text-muted, #94a3b8);
  letter-spacing: 0.5px;
}

@media (max-width: 768px) {
  .sidebar {
    width: 70px;
  }
  .sidebar__label {
    display: none;
  }
  .sidebar__item {
    justify-content: center;
    padding: 10px;
  }
  .sidebar__version {
    display: none;
  }
}
</style>
