<script setup lang="ts">
import { useAuthStore } from '../../stores/authStore'
import { useRoute } from 'vue-router'
import { computed } from 'vue'
import {
  LayoutDashboard,
  FileText,
  CreditCard,
  FlaskConical,
  ClipboardList,
  KeyRound,
  Users,
  Award
} from '@lucide/vue'

const auth = useAuthStore()
const route = useRoute()

const menuItems = computed(() => {
  const items = []

  // Dashboard
  if (auth.hasPermission('dashboard')) {
    const dashboardPath = auth.isAdmin ? '/admin/dashboard' : auth.isAnalyst ? '/analyst/dashboard' : '/user/dashboard'
    items.push({ label: 'Dashboard', path: dashboardPath, icon: LayoutDashboard })
  }

  // Berita Acara — muncul jika ada SATU PUN permission berita_acara_*
  const hasBeritaAcara = auth.hasPermission('berita_acara_view') ||
    auth.hasPermission('berita_acara_create') ||
    auth.hasPermission('berita_acara_verify') ||
    auth.hasPermission('berita_acara_assign') ||
    auth.hasPermission('berita_acara_riwayat_personal') ||
    auth.hasPermission('berita_acara_riwayat_semua')
  if (hasBeritaAcara) {
    // Path admin jika bisa lihat semua/verifikasi/assign, path user jika hanya create/riwayat personal
    const path = (auth.hasPermission('berita_acara_riwayat_semua') || auth.hasPermission('berita_acara_verify') || auth.hasPermission('berita_acara_assign'))
      ? '/admin/berita-acara'
      : '/user/berita-acara'
    items.push({ label: 'Berita Acara', path, icon: FileText })
  }

  // Pembayaran — muncul jika ada SATU PUN permission payment_*
  const hasPayment = auth.hasPermission('payment_view') ||
    auth.hasPermission('payment_upload') ||
    auth.hasPermission('payment_confirm') ||
    auth.hasPermission('payment_discount')
  if (hasPayment) {
    const path = (auth.hasPermission('payment_view') || auth.hasPermission('payment_confirm'))
      ? '/admin/payments'
      : '/user/payments'
    items.push({ label: 'Pembayaran', path, icon: CreditCard })
  }

  // Sampel — muncul jika ada SATU PUN permission sample_*
  const hasSample = auth.hasPermission('sample_view') ||
    auth.hasPermission('sample_input') ||
    auth.hasPermission('sample_rekap') ||
    auth.hasPermission('sample_status') ||
    auth.hasPermission('sample_riwayat_personal') ||
    auth.hasPermission('sample_riwayat_semua')
  if (hasSample) {
    const samplePath = auth.isAnalyst ? '/analyst/samples' : '/admin/samples'
    items.push({ label: 'Sampel', path: samplePath, icon: FlaskConical })
  }

  // Kelola Form
  if (auth.hasPermission('form_manage_berita_acara') || auth.hasPermission('form_manage_rekap_analis')) {
    items.push({ label: 'Kelola Form', path: '/admin/forms', icon: ClipboardList })
  }

  // Kelola Role
  if (auth.hasPermission('role_manage')) {
    items.push({ label: 'Kelola Role', path: '/admin/roles', icon: KeyRound })
  }

  // Kelola Pengguna
  if (auth.hasPermission('user_view')) {
    items.push({ label: 'Kelola Pengguna', path: '/admin/users', icon: Users })
  }

  // Sertifikat
  if (auth.hasPermission('certificate_download')) {
    items.push({ label: 'Sertifikat', path: '/user/certificates', icon: Award })
  }

  return items
})
</script>

<template>
  <aside class="sidebar">
    <nav class="sidebar__nav">
      <router-link
        v-for="item in menuItems"
        :key="item.path"
        :to="item.path"
        class="sidebar__item"
        active-class="sidebar__item--active"
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
  background: var(--color-surface);
  border-right: 1px solid var(--color-border);
  height: calc(100vh - 64px);
  position: fixed;
  top: 64px;
  left: 0;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: width 0.3s ease;
}
.sidebar__nav {
  display: flex;
  flex-direction: column;
  padding: 12px;
  gap: 2px;
}
.sidebar__item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  font-size: 14px;
  color: var(--color-text-secondary);
  text-decoration: none;
  border-radius: var(--radius-sm);
  transition: var(--transition);
  font-weight: 400;
}
.sidebar__item:hover {
  background: var(--color-bg);
  color: var(--color-text);
}
.sidebar__item--active {
  background: var(--color-accent-soft);
  color: var(--color-accent);
  font-weight: 500;
}
.sidebar__item--active .sidebar__icon {
  color: var(--color-accent);
}
.sidebar__icon {
  flex-shrink: 0;
  color: var(--color-text-muted);
}
.sidebar__item:hover .sidebar__icon {
  color: var(--color-text-secondary);
}
.sidebar__item--active .sidebar__icon {
  color: var(--color-accent);
}
.sidebar__label {
  line-height: 1;
}
.sidebar__footer {
  padding: 16px 20px;
  border-top: 1px solid var(--color-border-light);
}
.sidebar__version {
  font-size: 11px;
  color: var(--color-text-muted);
  letter-spacing: 0.5px;
}

/* Make sidebar more responsive on smaller screens */
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
