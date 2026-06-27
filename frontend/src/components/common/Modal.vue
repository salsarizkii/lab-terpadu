<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { X } from '@lucide/vue'

withDefaults(defineProps<{
  show: boolean
  title?: string
  size?: 'sm' | 'md' | 'lg'
  closable?: boolean
}>(), {
  size: 'md',
  closable: true,
})

const emit = defineEmits<{
  close: []
}>()

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') emit('close')
}

onMounted(() => window.addEventListener('keydown', onKeydown))
onUnmounted(() => window.removeEventListener('keydown', onKeydown))
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="modal-overlay" @click.self="closable && emit('close')">
        <div class="modal" :class="`modal--${size}`">
          <div class="modal__header">
            <h2 class="modal__title">{{ title || '' }}</h2>
            <button v-if="closable" class="modal__close" @click="emit('close')">
              <X :size="18" stroke-width="1.5" />
            </button>
          </div>
          <div class="modal__body">
            <slot />
          </div>
          <div v-if="$slots.footer" class="modal__footer">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}
.modal {
  background: var(--color-surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-xl);
  width: 100%;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  animation: modalIn 0.2s ease-out;
}
.modal--sm { max-width: 420px; }
.modal--md { max-width: 560px; }
.modal--lg { max-width: 720px; }

@keyframes modalIn {
  from { opacity: 0; transform: translateY(12px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-lg) var(--spacing-lg) 0;
}
.modal__title {
  font-size: 18px;
  font-weight: 600;
  margin: 0;
}
.modal__close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: transparent;
  color: var(--color-text-muted);
  cursor: pointer;
  transition: var(--transition);
}
.modal__close:hover {
  background: var(--color-bg);
  color: var(--color-text);
}
.modal__body {
  padding: var(--spacing-lg);
  overflow-y: auto;
}
.modal__footer {
  padding: 0 var(--spacing-lg) var(--spacing-lg);
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.modal-enter-active, .modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
</style>
