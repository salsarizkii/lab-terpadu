<script setup lang="ts">
withDefaults(defineProps<{
  title?: string
  subtitle?: string
  padding?: 'sm' | 'md' | 'lg'
  hover?: boolean
  variant?: 'default' | 'glass' | 'bordered'
}>(), {
  padding: 'md',
  hover: false,
  variant: 'default',
})
</script>

<template>
  <div
    class="card"
    :class="[
      `card--pad-${padding}`,
      `card--${variant}`,
      { 'card--hover': hover }
    ]"
  >
    <div v-if="title || subtitle || $slots.header || $slots['header-action']" class="card__header">
      <div class="card__header-text">
        <slot name="header">
          <h3 v-if="title" class="card__title">{{ title }}</h3>
          <p v-if="subtitle" class="card__subtitle">{{ subtitle }}</p>
        </slot>
      </div>
      <div v-if="$slots['header-action']" class="card__header-action">
        <slot name="header-action" />
      </div>
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
  background: var(--color-surface, #ffffff);
  border: 1px solid var(--color-border, #e2e8f0);
  border-radius: var(--radius-lg, 16px);
  box-shadow: var(--shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.06));
  transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.2s;
}

.card--glass {
  background: rgba(255, 255, 255, 0.88);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-color: rgba(226, 232, 240, 0.8);
}

.card--bordered {
  box-shadow: none;
  border-color: #cbd5e1;
}

.card--hover:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md, 0 4px 14px rgba(15, 23, 42, 0.08));
  border-color: #93c5fd;
}

.card--pad-sm { padding: 14px 16px; }
.card--pad-md { padding: 20px 24px; }
.card--pad-lg { padding: 28px 32px; }

.card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--color-border-light, #f1f5f9);
}

.card__header-text {
  flex: 1;
}

.card__title {
  font-size: 16px;
  font-weight: 700;
  color: var(--color-text, #0f172a);
  margin: 0;
  line-height: 1.3;
}

.card__subtitle {
  font-size: 13px;
  color: var(--color-text-secondary, #475569);
  margin-top: 4px;
  margin-bottom: 0;
}

.card__header-action {
  display: flex;
  align-items: center;
  gap: 8px;
}

.card__footer {
  margin-top: 16px;
  padding-top: 14px;
  border-top: 1px solid var(--color-border-light, #f1f5f9);
}
</style>
