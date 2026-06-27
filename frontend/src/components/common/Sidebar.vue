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
  Award,
  ClipboardCheck
} from '@lucide/vue'

const auth = useAuthStore()
const route = useRoute()

const menuItems = computed(() => {
  if (auth.isSuperAdmin) {
    return [
      { label: 'Dashboard', path: '/admin/dashboard', icon: LayoutDashboard },
      { label: 'Berita Acara', path: '/admin/berita-acara', icon: FileText },
      { label: 'Pembayaran', path: '/admin/payments', icon: CreditCard },
      { label: 'Sampel', path: '/admin/samples', icon: FlaskConical },
      { label: 'Kelola Form', path: '/admin/forms', icon: ClipboardList },
      { label: 'Kelola Role', path: '/admin/roles', icon: KeyRound },
      { label: 'Kelola Pengguna', path: '/admin/users', icon: Users },
    ]
  }
  if (auth.isAdmin) {
    return [
      { label: 'Dashboard', path: '/admin/dashboard', icon: LayoutDashboard },
      { label: 'Berita Acara', path: '/admin/berita-acara', icon: FileText },
      { label: 'Pembayaran', path: '/admin/payments', icon: CreditCard },
      { label: 'Sampel', path: '/admin/samples', icon: FlaskConical },
      { label: 'Kelola Form', path: '/admin/forms', icon: ClipboardList },
    ]
  }
  if (auth.isAnalyst) {
    return [
      { label: 'Dashboard', path: '/analyst/dashboard', icon: LayoutDashboard },
      { label: 'Tugas Saya', path: '/analyst/assigned', icon: ClipboardCheck },
    ]
  }
  return [
    { label: 'Dashboard', path: '/user/dashboard', icon: LayoutDashboard },
    { label: 'Pengajuan', path: '/user/berita-acara', icon: FileText },
    { label: 'Pembayaran', path: '/user/payments', icon: CreditCard },
    { label: 'Sertifikat', path: '/user/certificates', icon: Award },
  ]
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
