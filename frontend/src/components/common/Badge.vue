<script setup lang="ts">
withDefaults(defineProps<{
  type?: 'success' | 'warning' | 'danger' | 'info' | 'default'
  text?: string
  size?: 'sm' | 'md'
}>(), {
  type: 'default',
  size: 'md',
})
</script>

<template>
  <span class="badge" :class="[`badge--${type}`, `badge--${size}`]">
    <span class="badge__dot" />
    {{ text || type }}
  </span>
</template>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: var(--font-primary);
  font-weight: 500;
  border-radius: 100px;
  letter-spacing: 0.3px;
  white-space: nowrap;
}
.badge--sm {
  padding: 2px 10px;
  font-size: 11px;
}
.badge--md {
  padding: 4px 14px;
  font-size: 12px;
}
.badge__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  flex-shrink: 0;
}

.badge--success {
  background: var(--color-success-bg);
  color: #065f46;
}
.badge--success .badge__dot { background: var(--color-success); }

.badge--warning {
  background: var(--color-warning-bg);
  color: #92400e;
}
.badge--warning .badge__dot { background: var(--color-warning); }

.badge--danger {
  background: var(--color-danger-bg);
  color: #991b1b;
}
.badge--danger .badge__dot { background: var(--color-danger); }

.badge--info {
  background: var(--color-info-bg);
  color: #3730a3;
}
.badge--info .badge__dot { background: var(--color-info); }

.badge--default {
  background: var(--color-border-light);
  color: var(--color-text-secondary);
}
.badge--default .badge__dot { background: var(--color-text-muted); }
</style>
