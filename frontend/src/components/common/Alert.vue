<script setup lang="ts">
import { ref } from 'vue'

withDefaults(defineProps<{
  type?: 'success' | 'error' | 'warning' | 'info'
  message?: string
  dismissible?: boolean
}>(), {
  type: 'info',
  dismissible: true,
})

const emit = defineEmits<{
  dismiss: []
}>()

const visible = ref(true)

function close() {
  visible.value = false
  emit('dismiss')
}
</script>

<template>
  <Transition name="alert">
    <div v-if="visible" class="alert" :class="`alert--${type}`">
      <div class="alert__icon">
        <svg v-if="type === 'success'" width="18" height="18" viewBox="0 0 18 18" fill="none">
          <path d="M15 4.5L6.75 12.75L3 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <svg v-else-if="type === 'error'" width="18" height="18" viewBox="0 0 18 18" fill="none">
          <circle cx="9" cy="9" r="7.5" stroke="currentColor" stroke-width="1.5"/>
          <path d="M9 5.5V9.5M9 11.5V12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <svg v-else-if="type === 'warning'" width="18" height="18" viewBox="0 0 18 18" fill="none">
          <path d="M9 1.5L1.5 15.5H16.5L9 1.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
          <path d="M9 6.5V10.5M9 11.5V12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <svg v-else width="18" height="18" viewBox="0 0 18 18" fill="none">
          <circle cx="9" cy="9" r="7.5" stroke="currentColor" stroke-width="1.5"/>
          <path d="M9 5V10M9 12V12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </div>
      <div class="alert__message">{{ message }}</div>
      <button v-if="dismissible" class="alert__close" @click="close">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
          <path d="M3 3L11 11M11 3L3 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </button>
    </div>
  </Transition>
</template>

<style scoped>
.alert {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border-radius: var(--radius-sm);
  font-family: var(--font-primary);
  font-size: 13px;
  font-weight: 400;
  margin-bottom: 16px;
  line-height: 1.5;
}
.alert--success { background: var(--color-success-bg); color: #065f46; }
.alert--error { background: var(--color-danger-bg); color: #991b1b; }
.alert--warning { background: var(--color-warning-bg); color: #92400e; }
.alert--info { background: var(--color-accent-soft); color: #1e40af; }

.alert__icon { flex-shrink: 0; display: flex; }
.alert__message { flex: 1; }
.alert__close {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border: none;
  background: transparent;
  color: inherit;
  opacity: 0.6;
  cursor: pointer;
  border-radius: 4px;
  transition: var(--transition);
}
.alert__close:hover { opacity: 1; background: rgba(0,0,0,0.05); }

.alert-enter-active, .alert-leave-active {
  transition: all 0.2s ease;
}
.alert-enter-from, .alert-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
