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
.layout__main {
  margin-left: 240px;
  margin-top: 64px;
  min-height: calc(100vh - 64px);
  background: var(--color-bg);
}
.layout__content {
  padding: var(--spacing-xl);
  max-width: 1200px;
}
</style>
