<script setup lang="ts">
withDefaults(defineProps<{
  label?: string
  modelValue: string
  error?: string
  required?: boolean
  rows?: number
  placeholder?: string
  disabled?: boolean
}>(), {
  rows: 4,
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
    <div class="form-textarea-wrap" :class="{ 'form-textarea-wrap--error': error }">
      <textarea
        :value="modelValue"
        :rows="rows"
        :required="required"
        :placeholder="placeholder || `Masukkan ${label?.toLowerCase() || '...'}`"
        :disabled="disabled"
        class="form-textarea"
        @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
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
.form-textarea-wrap {
  position: relative;
  border-radius: var(--radius-sm);
  border: 1.5px solid var(--color-border);
  background: var(--color-surface);
  transition: var(--transition);
}
.form-textarea-wrap:focus-within {
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}
.form-textarea-wrap--error {
  border-color: var(--color-danger);
}
.form-textarea {
  width: 100%;
  padding: 11px 14px;
  border: none;
  background: transparent;
  font-family: var(--font-primary);
  font-size: 14px;
  color: var(--color-text);
  outline: none;
  resize: vertical;
  min-height: 80px;
  line-height: 1.6;
}
.form-textarea::placeholder {
  color: var(--color-text-muted);
  font-weight: 300;
}
.form-textarea:disabled {
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
