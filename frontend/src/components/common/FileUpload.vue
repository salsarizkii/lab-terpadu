<script setup lang="ts">
import { ref } from 'vue'

withDefaults(defineProps<{
  label?: string
  modelValue: File | null
  accept?: string
  error?: string
}>(), {
  accept: '*/*',
})

const emit = defineEmits<{
  'update:modelValue': [value: File | null]
}>()

const fileName = ref('')

function handleChange(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] || null
  fileName.value = file ? file.name : ''
  emit('update:modelValue', file)
}
</script>

<template>
  <div class="form-group">
    <label v-if="label" class="form-label">{{ label }}</label>
    <div class="file-upload" :class="{ 'file-upload--error': error }">
      <label class="file-upload__label">
        <svg class="file-upload__icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
          <path d="M10 3V13M10 3L6 7M10 3L14 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M2 15V16.5C2 17.88 3.12 19 4.5 19H15.5C16.88 19 18 17.88 18 16.5V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <span v-if="fileName" class="file-upload__name">{{ fileName }}</span>
        <span v-else class="file-upload__placeholder">Pilih file...</span>
        <input type="file" :accept="accept" class="file-upload__input" @change="handleChange" />
      </label>
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
.file-upload {
  border-radius: var(--radius-sm);
  border: 1.5px dashed var(--color-border);
  transition: var(--transition);
}
.file-upload:hover {
  border-color: var(--color-accent);
  background: var(--color-accent-soft);
}
.file-upload--error {
  border-color: var(--color-danger);
}
.file-upload__label {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  cursor: pointer;
  font-family: var(--font-primary);
  font-size: 14px;
}
.file-upload__icon {
  color: var(--color-accent);
  flex-shrink: 0;
}
.file-upload__name {
  color: var(--color-text);
  font-weight: 400;
}
.file-upload__placeholder {
  color: var(--color-text-muted);
  font-weight: 300;
}
.file-upload__input {
  display: none;
}
.form-error {
  margin: 4px 0 0;
  font-size: 12px;
  color: var(--color-danger);
}
</style>
