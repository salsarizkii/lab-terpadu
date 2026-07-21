<script setup lang="ts">
import Navbar from './Navbar.vue'
import Sidebar from './Sidebar.vue'
</script>

<template>
  <div class="layout">
    <Navbar />
    <Sidebar />
    <main class="layout__main">
      <div class="layout__content">
        <slot />
      </div>
    </main>
  </div>
</template>

<style scoped>
.layout {
  min-height: 100vh;
  background: var(--color-bg, #f8fafc);
}

.layout__main {
  margin-left: 240px;
  margin-top: 64px;
  min-height: calc(100vh - 64px);
  background: var(--color-bg, #f8fafc);
  transition: margin-left 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.layout__content {
  padding: 24px 32px;
  width: 100%;
  box-sizing: border-box;
}

@media (max-width: 768px) {
  .layout__main {
    margin-left: 70px;
  }
  .layout__content {
    padding: 16px;
  }
}
</style>
