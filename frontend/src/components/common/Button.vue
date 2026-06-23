<script setup lang="ts">
withDefaults(defineProps<{
  variant?: 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger' | 'success'
  size?: 'sm' | 'md' | 'lg'
  loading?: boolean
  disabled?: boolean
  block?: boolean
}>(), {
  variant: 'primary',
  size: 'md',
  loading: false,
  disabled: false,
  block: false,
})
</script>

<template>
  <button
    class="btn"
    :class="[
      `btn--${variant}`,
      `btn--${size}`,
      { 'btn--block': block, 'btn--loading': loading },
    ]"
    :disabled="disabled || loading"
  >
    <span v-if="loading" class="btn__spinner" />
    <slot />
  </button>
</template>

<style scoped>
.btn {
  font-family: var(--font-primary);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 1.5px solid transparent;
  border-radius: var(--radius-sm);
  font-weight: 500;
  cursor: pointer;
  transition: var(--transition);
  white-space: nowrap;
  letter-spacing: 0.2px;
}
.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn--sm {
  padding: 6px 14px;
  font-size: 13px;
}
.btn--md {
  padding: 10px 22px;
  font-size: 14px;
}
.btn--lg {
  padding: 14px 32px;
  font-size: 15px;
}

.btn--block {
  width: 100%;
}

.btn--primary {
  background: var(--color-accent);
  color: var(--color-text-white);
  border-color: var(--color-accent);
}
.btn--primary:hover:not(:disabled) {
  background: var(--color-accent-hover);
  border-color: var(--color-accent-hover);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn--secondary {
  background: var(--color-primary);
  color: var(--color-text-white);
  border-color: var(--color-primary);
}
.btn--secondary:hover:not(:disabled) {
  background: var(--color-primary-light);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn--outline {
  background: transparent;
  color: var(--color-text-secondary);
  border-color: var(--color-border);
}
.btn--outline:hover:not(:disabled) {
  background: var(--color-bg);
  border-color: var(--color-accent);
  color: var(--color-accent);
}

.btn--ghost {
  background: transparent;
  color: var(--color-text-secondary);
  border-color: transparent;
}
.btn--ghost:hover:not(:disabled) {
  background: var(--color-bg);
  color: var(--color-text);
}

.btn--danger {
  background: var(--color-danger);
  color: var(--color-text-white);
  border-color: var(--color-danger);
}
.btn--danger:hover:not(:disabled) {
  background: #dc2626;
  transform: translateY(-1px);
}

.btn--success {
  background: var(--color-success);
  color: var(--color-text-white);
  border-color: var(--color-success);
}
.btn--success:hover:not(:disabled) {
  background: #059669;
  transform: translateY(-1px);
}

.btn__spinner {
  width: 16px;
  height: 16px;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
