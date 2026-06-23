<script setup lang="ts">
withDefaults(defineProps<{
  size?: 'sm' | 'md' | 'lg'
  text?: string
}>(), {
  size: 'md',
})
</script>

<template>
  <div class="spinner-wrapper">
    <div class="spinner" :class="`spinner--${size}`">
      <div class="spinner__ring" />
    </div>
    <p v-if="text" class="spinner__text">{{ text }}</p>
  </div>
</template>

<style scoped>
.spinner-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 20px;
  gap: 16px;
}
.spinner__ring {
  border-radius: 50%;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-accent);
  animation: spin 0.7s linear infinite;
}
.spinner--sm .spinner__ring {
  width: 24px;
  height: 24px;
  border-width: 2px;
}
.spinner--md .spinner__ring {
  width: 40px;
  height: 40px;
}
.spinner--lg .spinner__ring {
  width: 56px;
  height: 56px;
  border-width: 4px;
}
.spinner__text {
  font-family: var(--font-primary);
  font-size: 13px;
  color: var(--color-text-muted);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
