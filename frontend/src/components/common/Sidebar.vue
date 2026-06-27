<script setup lang="ts">
import { useAuthStore } from '../../stores/authStore'
import { useRoute } from 'vue-router'
import { computed } from 'vue'

const auth = useAuthStore()
const route = useRoute()

const menuItems = computed(() => {
  if (auth.isSuperAdmin) {
    return [
      { label: 'Dashboard', path: '/admin/dashboard', icon: 'dashboard' },
      { label: 'Berita Acara', path: '/admin/berita-acara', icon: 'file' },
      { label: 'Pembayaran', path: '/admin/payments', icon: 'payment' },
      { label: 'Sampel', path: '/admin/samples', icon: 'sample' },
      { label: 'Kelola Form', path: '/admin/forms', icon: 'form' },
      { label: 'Kelola Role', path: '/admin/roles', icon: 'key' },
      { label: 'Kelola Pengguna', path: '/admin/users', icon: 'users' },
    ]
  }
  if (auth.isAdmin) {
    return [
      { label: 'Dashboard', path: '/admin/dashboard', icon: 'dashboard' },
      { label: 'Berita Acara', path: '/admin/berita-acara', icon: 'file' },
      { label: 'Pembayaran', path: '/admin/payments', icon: 'payment' },
      { label: 'Sampel', path: '/admin/samples', icon: 'sample' },
      { label: 'Kelola Form', path: '/admin/forms', icon: 'form' },
    ]
  }
  if (auth.isAnalyst) {
    return [
      { label: 'Dashboard', path: '/analyst/dashboard', icon: 'dashboard' },
      { label: 'Tugas Saya', path: '/analyst/assigned', icon: 'assignment' },
    ]
  }
  return [
    { label: 'Dashboard', path: '/user/dashboard', icon: 'dashboard' },
    { label: 'Pengajuan', path: '/user/berita-acara', icon: 'file' },
    { label: 'Pembayaran', path: '/user/payments', icon: 'payment' },
    { label: 'Sertifikat', path: '/user/certificates', icon: 'certificate' },
  ]
})

const icons: Record<string, string> = {
  dashboard: '<path d="M3 9L12 2L21 9V20C21 20.55 20.55 21 20 21H15.5C14.95 21 14.5 20.55 14.5 20V14.5C14.5 13.95 14.05 13.5 13.5 13.5H10.5C9.95 13.5 9.5 13.95 9.5 14.5V20C9.5 20.55 9.05 21 8.5 21H4C3.45 21 3 20.55 3 20V9Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
  file: '<path d="M14 2H6C5.45 2 5 2.45 5 3V21C5 21.55 5.45 22 6 22H18C18.55 22 19 21.55 19 21V8L14 2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2V8H19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
  payment: '<path d="M2 8C2 6.9 2.9 6 4 6H20C21.1 6 22 6.9 22 8V16C22 17.1 21.1 18 20 18H4C2.9 18 2 17.1 2 16V8Z" stroke="currentColor" stroke-width="1.5"/><path d="M2 10H22" stroke="currentColor" stroke-width="1.5"/><path d="M7 14H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
  sample: '<path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
  plus: '<path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
  certificate: '<path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
  assignment: '<path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="1.5"/><path d="M8 8H16M8 12H14M8 16H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
  key: '<path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
  users: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M23 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
  form: '<path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="1.5"/><path d="M8 8H16M8 12H16M8 16H10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
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
        active-class="sidebar__item--active"
      >
        <span class="sidebar__icon" v-html="icons[item.icon] || icons.dashboard" />
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
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
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
</style>
