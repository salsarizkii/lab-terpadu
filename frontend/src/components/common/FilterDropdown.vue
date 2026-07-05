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
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  font-family: inherit;
  color: #1e293b;
  background: #fff;
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
  border-color: #cbd5e1;
}
.fd-trigger:focus,
.fd-trigger.open {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
  color: #94a3b8;
}
.fd-chevron {
  color: #94a3b8;
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
  background: #fff;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
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
  color: #1e293b;
  transition: background 0.15s;
  border-bottom: 1px solid #f1f5f9;
}
.fd-item:last-child {
  border-bottom: none;
}
.fd-item:hover {
  background: #f8fafc;
}
.fd-item.active {
  background: #eff6ff;
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
