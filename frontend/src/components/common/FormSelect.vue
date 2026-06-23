<script setup lang="ts">
withDefaults(defineProps<{
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
</script>

<template>
  <div class="form-group">
    <label v-if="label" class="form-label">
      {{ label }}
      <span v-if="required" class="form-required">*</span>
    </label>
    <div class="form-select-wrap" :class="{ 'form-select-wrap--error': error }">
      <select
        :value="modelValue"
        :required="required"
        :disabled="disabled"
        class="form-select"
        @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
      >
        <option value="" disabled>{{ placeholder || `Pilih ${label?.toLowerCase() || '...'}` }}</option>
        <option v-for="opt in options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
      <span class="form-select-arrow">
        <svg width="12" height="8" viewBox="0 0 12 8" fill="none">
          <path d="M1 1.5L6 6.5L11 1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
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
  border-radius: var(--radius-sm);
  border: 1.5px solid var(--color-border);
  background: var(--color-surface);
  transition: var(--transition);
}
.form-select-wrap:focus-within {
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}
.form-select-wrap--error {
  border-color: var(--color-danger);
}
.form-select {
  width: 100%;
  padding: 11px 38px 11px 14px;
  border: none;
  background: transparent;
  font-family: var(--font-primary);
  font-size: 14px;
  color: var(--color-text);
  outline: none;
  appearance: none;
  cursor: pointer;
}
.form-select:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.form-select-arrow {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-text-muted);
  pointer-events: none;
}
.form-error {
  margin: 4px 0 0;
  font-size: 12px;
  color: var(--color-danger);
  font-weight: 400;
}
</style>
