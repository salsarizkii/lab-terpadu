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
  border: 1px solid transparent;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  white-space: nowrap;
  letter-spacing: 0.3px;
  position: relative;
  overflow: hidden;
}
.btn::after {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(255, 255, 255, 0.1);
  opacity: 0;
  transition: opacity 0.2s ease;
}
.btn:hover::after {
  opacity: 1;
}
.btn:active:not(:disabled) {
  transform: scale(0.97);
}
.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none !important;
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
  background: linear-gradient(135deg, var(--color-accent), #1d4ed8);
  color: #fff;
  border: none;
  box-shadow: 0 2px 4px rgba(37, 99, 235, 0.15);
}
.btn--primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(37, 99, 235, 0.25), 0 4px 6px rgba(37, 99, 235, 0.15);
}

.btn--secondary {
  background: linear-gradient(135deg, var(--color-primary), #334155);
  color: #fff;
  border: none;
  box-shadow: 0 2px 4px rgba(15, 23, 42, 0.1);
}
.btn--secondary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(15, 23, 42, 0.2), 0 4px 6px rgba(15, 23, 42, 0.1);
}

.btn--outline {
  background: transparent;
  color: var(--color-text-secondary);
  border-color: var(--color-border);
}
.btn--outline::after {
  display: none;
}
.btn--outline:hover:not(:disabled) {
  background: #f8fafc;
  border-color: var(--color-accent);
  color: var(--color-accent);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.btn--ghost {
  background: transparent;
  color: var(--color-text-secondary);
}
.btn--ghost::after {
  background: var(--color-accent-soft);
}
.btn--ghost:hover:not(:disabled) {
  color: var(--color-accent);
}

.btn--danger {
  background: linear-gradient(135deg, var(--color-danger), #b91c1c);
  color: #fff;
  border: none;
  box-shadow: 0 2px 4px rgba(239, 68, 68, 0.15);
}
.btn--danger:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(239, 68, 68, 0.25);
}

.btn--success {
  background: linear-gradient(135deg, var(--color-success), #047857);
  color: #fff;
  border: none;
  box-shadow: 0 2px 4px rgba(16, 185, 129, 0.15);
}
.btn--success:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(16, 185, 129, 0.25);
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
