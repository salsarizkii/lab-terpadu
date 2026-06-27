<script setup lang="ts">
import { useAuthStore } from '../../stores/authStore'
import { useRouter } from 'vue-router'
import { FlaskConical, LogOut } from '@lucide/vue'
import Button from './Button.vue'

const auth = useAuthStore()
const router = useRouter()

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <nav class="navbar">
    <div class="navbar__brand" @click="router.push('/dashboard')">
      <div class="navbar__logo">
        <div class="logo-box">
          <FlaskConical :size="18" color="white" stroke-width="2.5" />
        </div>
      </div>
      <div class="navbar__brand-text">
        <span class="navbar__title">Lab Terpadu</span>
        <span class="navbar__subtitle">Sampel Penelitian</span>
      </div>
    </div>
    <div class="navbar__right">
      <div class="navbar__user">
        <div class="navbar__avatar">{{ auth.user?.name?.charAt(0)?.toUpperCase() || 'U' }}</div>
        <div class="navbar__user-info">
          <span class="navbar__user-name">{{ auth.user?.name || 'User' }}</span>
          <span class="navbar__user-role">{{ auth.user?.role || '-' }}</span>
        </div>
      </div>
      <Button variant="ghost" size="sm" @click="handleLogout">
        <LogOut :size="16" />
        Keluar
      </Button>
    </div>
  </nav>
</template>

<style scoped>
.navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 64px;
  padding: 0 28px;
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  backdrop-filter: blur(8px);
}
.navbar__brand {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
}
.navbar__logo {
  display: flex;
}
.logo-box {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: var(--color-accent);
  display: flex;
  align-items: center;
  justify-content: center;
}
.navbar__brand-text {
  display: flex;
  flex-direction: column;
}
.navbar__title {
  font-size: 16px;
  font-weight: 700;
  color: var(--color-text);
  line-height: 1.2;
}
.navbar__subtitle {
  font-size: 10px;
  font-weight: 400;
  color: var(--color-text-muted);
  letter-spacing: 0.5px;
  text-transform: uppercase;
}
.navbar__right {
  display: flex;
  align-items: center;
  gap: 16px;
}
.navbar__user {
  display: flex;
  align-items: center;
  gap: 10px;
}
.navbar__avatar {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, var(--color-accent), var(--color-info));
  color: var(--color-text-white);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 14px;
}
.navbar__user-info {
  display: flex;
  flex-direction: column;
}
.navbar__user-name {
  font-size: 13px;
  font-weight: 500;
  color: var(--color-text);
  line-height: 1.2;
}
.navbar__user-role {
  font-size: 11px;
  color: var(--color-text-muted);
  text-transform: capitalize;
}
</style>
