<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { ChevronDown, Check } from '@lucide/vue'

const props = withDefaults(defineProps<{
  label?: string
  modelValue: string
  options: { value: string; label: string }[]
  error?: string
  required?: boolean
  placeholder?: string
  disabled?: boolean
}>(), {
  disabled: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const open = ref(false)
const wrapRef = ref<HTMLElement | null>(null)
const activeIndex = ref(-1)

const selected = computed(() => props.options.find(o => o.value === props.modelValue))

const displayText = computed(() => {
  if (selected.value) return selected.value.label
  return props.placeholder || `Pilih ${props.label?.toLowerCase() || '...'}`
})

function toggle() {
  if (props.disabled) return
  open.value = !open.value
  if (open.value) {
    const idx = props.options.findIndex(o => o.value === props.modelValue)
    activeIndex.value = idx
  }
}

function select(opt: { value: string; label: string }) {
  emit('update:modelValue', opt.value)
  open.value = false
}

function onKeydown(e: KeyboardEvent) {
  if (!open.value) return
  if (e.key === 'Escape') {
    open.value = false
  } else if (e.key === 'ArrowDown') {
    e.preventDefault()
    if (props.options.length === 0) return
    activeIndex.value = (activeIndex.value + 1 + props.options.length) % props.options.length
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    if (props.options.length === 0) return
    activeIndex.value = (activeIndex.value - 1 + props.options.length) % props.options.length
  } else if (e.key === 'Enter') {
    e.preventDefault()
    const opt = props.options[activeIndex.value]
    if (opt) select(opt)
  }
}

function handleClickOutside(e: Event) {
  if (wrapRef.value && !wrapRef.value.contains(e.target as Node)) {
    open.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="form-group">
    <label v-if="label" class="form-label">
      {{ label }}
      <span v-if="required" class="form-required">*</span>
    </label>
    <div ref="wrapRef" class="form-select-wrap" :class="{ 'form-select-wrap--error': error, 'form-select-wrap--open': open }">
      <button
        type="button"
        class="form-select-trigger"
        :disabled="disabled"
        :class="{ 'form-select-trigger--placeholder': !selected }"
        @click="toggle"
        @keydown="onKeydown"
      >
        <span class="form-select-value">{{ displayText }}</span>
        <span class="form-select-arrow" :class="{ 'form-select-arrow--open': open }">
          <ChevronDown :size="16" />
        </span>
      </button>

      <Transition name="fs-dropdown">
        <div v-if="open" class="form-select-menu" role="listbox">
          <button
            v-for="(opt, i) in options"
            :key="opt.value"
            type="button"
            role="option"
            :aria-selected="opt.value === modelValue"
            class="form-select-option"
            :class="{ 'form-select-option--active': i === activeIndex, 'form-select-option--selected': opt.value === modelValue }"
            @click="select(opt)"
            @mouseenter="activeIndex = i"
          >
            <span class="form-select-option-label">{{ opt.label }}</span>
            <span v-if="opt.value === modelValue" class="form-select-option-check">
              <Check :size="15" />
            </span>
          </button>
          <div v-if="options.length === 0" class="form-select-empty">Tidak ada pilihan</div>
        </div>
      </Transition>
    </div>
    <p v-if="error" class="form-error">{{ error }}</p>
  </div>
</template>

<style scoped>
.form-group {
  margin-bottom: 18px;
}
.form-label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: var(--color-text-secondary);
  margin-bottom: 6px;
  letter-spacing: 0.3px;
}
.form-required {
  color: var(--color-danger);
  margin-left: 2px;
}
.form-select-wrap {
  position: relative;
}
.form-select-trigger {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 11px 14px;
  border: 1.5px solid var(--color-border);
  border-radius: var(--radius-sm);
  background: var(--color-surface);
  font-family: var(--font-primary);
  font-size: 14px;
  color: var(--color-text);
  cursor: pointer;
  outline: none;
  text-align: left;
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}
.form-select-trigger:hover:not(:disabled) {
  border-color: var(--color-border-strong);
}
.form-select-trigger:focus-visible {
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}
.form-select-wrap--open .form-select-trigger {
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}
.form-select-wrap--error .form-select-trigger {
  border-color: var(--color-danger);
}
.form-select-trigger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.form-select-trigger--placeholder .form-select-value {
  color: var(--color-text-muted);
}
.form-select-value {
  flex: 1;
  min-width: 0;
  line-height: 1.4;
  white-space: normal;
  overflow-wrap: break-word;
}
.form-select-arrow {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  color: var(--color-text-muted);
  transition: transform var(--transition);
}
.form-select-arrow--open {
  transform: rotate(180deg);
  color: var(--color-accent);
}
.form-select-menu {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  max-height: 260px;
  overflow-y: auto;
  background: var(--color-surface);
  border: 1.5px solid var(--color-border);
  border-radius: var(--radius-sm);
  box-shadow: var(--shadow-lg);
  z-index: 60;
  padding: 6px;
}
.form-select-option {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 9px 12px;
  border: none;
  border-radius: 6px;
  background: transparent;
  font-family: var(--font-primary);
  font-size: 14px;
  color: var(--color-text);
  cursor: pointer;
  text-align: left;
  transition: background var(--transition-fast), color var(--transition-fast);
}
.form-select-option:hover,
.form-select-option--active {
  background: var(--color-accent-soft);
  color: var(--color-accent-hover);
}
.form-select-option-label {
  flex: 1;
  min-width: 0;
  line-height: 1.4;
  white-space: normal;
  overflow-wrap: break-word;
}
.form-select-option-check {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  color: var(--color-accent);
}
.form-select-empty {
  padding: 12px;
  font-size: 13px;
  color: var(--color-text-muted);
  text-align: center;
}
.form-error {
  margin: 4px 0 0;
  font-size: 12px;
  color: var(--color-danger);
  font-weight: 400;
}

.fs-dropdown-enter-active,
.fs-dropdown-leave-active {
  transition: opacity var(--transition-fast), transform var(--transition-fast);
  transform-origin: top center;
}
.fs-dropdown-enter-from,
.fs-dropdown-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.98);
}
</style>
