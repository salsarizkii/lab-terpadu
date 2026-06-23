<script setup lang="ts">
withDefaults(defineProps<{
  title?: string
  subtitle?: string
  padding?: 'sm' | 'md' | 'lg'
  hover?: boolean
}>(), {
  padding: 'md',
  hover: false,
})
</script>

<template>
  <div
    class="card"
    :class="[`card--pad-${padding}`, { 'card--hover': hover }]"
  >
    <div v-if="title || subtitle" class="card__header">
      <h3 v-if="title" class="card__title">{{ title }}</h3>
      <p v-if="subtitle" class="card__subtitle">{{ subtitle }}</p>
    </div>
    <div class="card__body">
      <slot />
    </div>
    <div v-if="$slots.footer" class="card__footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<style scoped>
.card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  transition: var(--transition);
}
.card--hover:hover {
  box-shadow: var(--shadow-md);
  border-color: var(--color-accent-light);
}
.card--pad-sm { padding: var(--spacing-md); }
.card--pad-md { padding: var(--spacing-lg); }
.card--pad-lg { padding: var(--spacing-xl); }

.card__header {
  margin-bottom: var(--spacing-md);
  padding-bottom: var(--spacing-md);
  border-bottom: 1px solid var(--color-border-light);
}
.card__title {
  font-size: 16px;
  font-weight: 600;
  color: var(--color-text);
  margin: 0;
}
.card__subtitle {
  font-size: 13px;
  color: var(--color-text-secondary);
  margin-top: 4px;
}
.card__footer {
  margin-top: var(--spacing-md);
  padding-top: var(--spacing-md);
  border-top: 1px solid var(--color-border-light);
}
</style>
