<script setup lang="ts">
withDefaults(defineProps<{
  label?: string
  modelValue: string
  type?: string
  error?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
}>(), {
  type: 'text',
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
    <div class="form-input-wrap" :class="{ 'form-input-wrap--error': error }">
      <input
        :type="type"
        :value="modelValue"
        :placeholder="placeholder || `Masukkan ${label?.toLowerCase() || '...'}`"
        :required="required"
        :disabled="disabled"
        class="form-input"
        @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      />
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
.form-input-wrap {
  position: relative;
  border-radius: var(--radius-sm);
  border: 1.5px solid var(--color-border);
  background: var(--color-surface);
  transition: var(--transition);
}
.form-input-wrap:focus-within {
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}
.form-input-wrap--error {
  border-color: var(--color-danger);
}
.form-input-wrap--error:focus-within {
  box-shadow: 0 0 0 3px var(--color-danger-bg);
}
.form-input {
  width: 100%;
  padding: 11px 14px;
  border: none;
  background: transparent;
  font-family: var(--font-primary);
  font-size: 14px;
  color: var(--color-text);
  outline: none;
}
.form-input::placeholder {
  color: var(--color-text-muted);
  font-weight: 300;
}
.form-input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.form-error {
  margin: 4px 0 0;
  font-size: 12px;
  color: var(--color-danger);
  font-weight: 400;
}
</style>
