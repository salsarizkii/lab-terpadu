<script setup lang="ts">
import { ref, computed, watch, onBeforeUnmount } from 'vue'

const props = withDefaults(defineProps<{
  modelValue: string
  options: { value: string; label: string }[]
  placeholder?: string
  minWidth?: string
}>(), {
  placeholder: 'Pilih...',
  minWidth: '180px',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const isOpen = ref(false)
const wrapperRef = ref<HTMLElement | null>(null)

const selectedLabel = computed(() => {
  const opt = props.options.find(o => o.value === props.modelValue)
  return opt?.label || null
})

function select(value: string) {
  emit('update:modelValue', value)
  isOpen.value = false
}

function toggle() {
  isOpen.value = !isOpen.value
}

function handleClickOutside(e: MouseEvent) {
  if (wrapperRef.value && !wrapperRef.value.contains(e.target as Node)) {
    isOpen.value = false
  }
}

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})

watch(isOpen, (open) => {
  if (open) {
    document.addEventListener('click', handleClickOutside)
  } else {
    document.removeEventListener('click', handleClickOutside)
  }
})
</script>

<template>
  <div ref="wrapperRef" class="fd-wrapper" :style="{ minWidth }">
    <button
      type="button"
      class="fd-trigger"
      :class="{ open: isOpen }"
      @click.stop="toggle"
    >
      <span v-if="selectedLabel" class="fd-value">{{ selectedLabel }}</span>
      <span v-else class="fd-placeholder">{{ placeholder }}</span>
      <svg class="fd-chevron" :class="{ open: isOpen }" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
    </button>
    <Transition name="fd-menu">
      <div v-if="isOpen" class="fd-menu">
        <div
          v-for="opt in options"
          :key="opt.value"
          class="fd-item"
          :class="{ active: modelValue === opt.value }"
          @click="select(opt.value)"
        >
          <span class="fd-item-label">{{ opt.label }}</span>
          <svg v-if="modelValue === opt.value" class="fd-check" width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M3.5 8.5L6.5 11.5L12.5 4.5" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.fd-wrapper {
  position: relative;
  display: inline-flex;
}
.fd-trigger {
  width: auto;
  min-width: 0;
  padding: 8px 12px;
  border: 1.5px solid var(--color-border);
  border-radius: var(--radius-sm);
  font-size: 14px;
  font-family: var(--font-primary);
  color: var(--color-text);
  background: var(--color-surface);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: border-color 0.2s, box-shadow 0.2s;
  outline: none;
  text-align: left;
  white-space: nowrap;
}
.fd-trigger:hover {
  border-color: var(--color-border-hover, #cbd5e1);
}
.fd-trigger:focus,
.fd-trigger.open {
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}
.fd-value {
  flex: 1;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.fd-placeholder {
  flex: 1;
  color: var(--color-text-muted);
}
.fd-chevron {
  color: var(--color-text-muted);
  transition: transform 0.2s;
  flex-shrink: 0;
}
.fd-chevron.open {
  transform: rotate(180deg);
}
.fd-menu {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: var(--color-surface);
  border: 1.5px solid var(--color-border);
  border-radius: var(--radius-sm);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
  z-index: 50;
  overflow: hidden;
  min-width: 100%;
}
.fd-item {
  padding: 9px 14px;
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  font-size: 14px;
  color: var(--color-text);
  transition: background 0.15s;
  border-bottom: 1px solid var(--color-border-light);
}
.fd-item:last-child {
  border-bottom: none;
}
.fd-item:hover {
  background: var(--color-accent-soft);
}
.fd-item.active {
  background: var(--color-accent-soft);
}
.fd-item-label {
  flex: 1;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.fd-check {
  flex-shrink: 0;
  margin-left: 4px;
}
.fd-menu-enter-active,
.fd-menu-leave-active {
  transition: opacity 0.15s, transform 0.15s;
}
.fd-menu-enter-from,
.fd-menu-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
