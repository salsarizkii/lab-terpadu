<script setup lang="ts">
import { ref, watch } from 'vue'
import FormInput from './FormInput.vue'
import FormTextarea from './FormTextarea.vue'
import FormSelect from './FormSelect.vue'
import type { FormField } from '../../types'

const props = defineProps<{
  fields: FormField[]
  initialData?: Record<string, any>
}>()

const emit = defineEmits<{
  'update:values': [data: Record<string, any>]
  'validation-error': [fieldName: string]
}>()

const values = ref<Record<string, any>>({})
const errors = ref<Set<string>>(new Set())

watch(() => props.fields, () => {
  initValues()
}, { immediate: true })

watch(values, (val) => {
  emit('update:values', { ...val })
}, { deep: true })

function initValues() {
  const v: Record<string, any> = {}
  props.fields.forEach((f, i) => {
    const key = fieldKey(f, i)
    if (props.initialData && props.initialData[key] !== undefined) {
      v[key] = props.initialData[key]
    } else if (f.type === 'checkbox') {
      v[key] = []
    } else {
      v[key] = ''
    }
  })
  values.value = v
}

function fieldKey(f: FormField, i: number): string {
  return f.id ? `field_${f.id}` : `field_${i}`
}

function fieldId(f: FormField, i: number): string {
  return `df-field-${fieldKey(f, i)}`
}

function getFieldValue(f: FormField, i: number): any {
  return values.value[fieldKey(f, i)]
}

function setFieldValue(f: FormField, i: number, val: any) {
  values.value[fieldKey(f, i)] = val
  errors.value.delete(fieldKey(f, i))
}

function toggleCheckbox(f: FormField, i: number, option: string) {
  const key = fieldKey(f, i)
  const arr = values.value[key] || []
  const idx = arr.indexOf(option)
  if (idx >= 0) {
    arr.splice(idx, 1)
  } else {
    arr.push(option)
  }
  values.value[key] = [...arr]
  errors.value.delete(key)
}

function validate(): { valid: boolean; firstErrorField?: string } {
  errors.value.clear()
  let firstError: string | undefined

  for (let i = 0; i < props.fields.length; i++) {
    const f = props.fields[i]
    if (!f.is_required) continue
    if (['heading', 'paragraph', 'divider'].includes(f.type)) continue

    const key = fieldKey(f, i)
    const val = values.value[key]

    let isEmpty = false
    if (f.type === 'checkbox') {
      isEmpty = !val || (Array.isArray(val) && val.length === 0)
    } else if (f.type === 'file') {
      isEmpty = !val
    } else {
      isEmpty = !val || (typeof val === 'string' && val.trim() === '')
    }

    if (isEmpty) {
      errors.value.add(key)
      if (!firstError) firstError = fieldId(f, i)
    }
  }

  return { valid: errors.value.size === 0, firstErrorField: firstError }
}

function hasError(f: FormField, i: number): boolean {
  return errors.value.has(fieldKey(f, i))
}

defineExpose({ values, validate })
</script>

<template>
  <div class="dynamic-form">
    <template v-for="(field, i) in fields" :key="i">
      <!-- Heading -->
      <div v-if="field.type === 'heading'" :id="fieldId(field, i)" class="df-heading">
        <h3>{{ field.label }}</h3>
      </div>

      <!-- Paragraph -->
      <div v-else-if="field.type === 'paragraph'" :id="fieldId(field, i)" class="df-paragraph">
        <p>{{ field.label }}</p>
      </div>

      <!-- Divider -->
      <hr v-else-if="field.type === 'divider'" class="df-divider" />

      <!-- Text / Email / Phone / Number / Date / Time -->
      <div v-else-if="['text', 'email', 'phone', 'number', 'date', 'time'].includes(field.type)" :id="fieldId(field, i)" :class="{ 'df-field-error': hasError(field, i) }">
        <FormInput
          :model-value="getFieldValue(field, i)"
          @update:model-value="setFieldValue(field, i, $event)"
          :label="field.label"
          :type="field.type === 'phone' ? 'tel' : field.type"
          :required="field.is_required"
          :placeholder="field.type === 'date' ? 'YYYY-MM-DD' : field.type === 'time' ? 'HH:MM' : ''"
        />
        <p v-if="hasError(field, i)" class="df-error-text">Pertanyaan ini wajib diisi</p>
      </div>

      <!-- Textarea -->
      <div v-else-if="field.type === 'textarea'" :id="fieldId(field, i)" :class="{ 'df-field-error': hasError(field, i) }">
        <FormTextarea
          :model-value="getFieldValue(field, i)"
          @update:model-value="setFieldValue(field, i, $event)"
          :label="field.label"
          :required="field.is_required"
          :rows="3"
        />
        <p v-if="hasError(field, i)" class="df-error-text">Pertanyaan ini wajib diisi</p>
      </div>

      <!-- Select -->
      <div v-else-if="field.type === 'select'" :id="fieldId(field, i)" :class="{ 'df-field-error': hasError(field, i) }">
        <FormSelect
          :model-value="getFieldValue(field, i)"
          @update:model-value="setFieldValue(field, i, $event)"
          :label="field.label"
          :required="field.is_required"
          :options="(field.options || []).map(o => ({ label: o.label, value: o.label }))"
        />
        <p v-if="hasError(field, i)" class="df-error-text">Pertanyaan ini wajib diisi</p>
      </div>

      <!-- Radio -->
      <div v-else-if="field.type === 'radio'" :id="fieldId(field, i)" class="df-field" :class="{ 'df-field-error': hasError(field, i) }">
        <label class="df-label">{{ field.label }} <span v-if="field.is_required" class="df-required">*</span></label>
        <div class="df-options">
          <label v-for="(opt, oi) in field.options" :key="oi" class="df-radio">
            <input
              type="radio"
              :name="fieldKey(field, i)"
              :value="opt.label"
              :checked="getFieldValue(field, i) === opt.label"
              @change="setFieldValue(field, i, opt.label)"
            />
            <span>{{ opt.label }}</span>
          </label>
        </div>
        <p v-if="hasError(field, i)" class="df-error-text">Pertanyaan ini wajib diisi</p>
      </div>

      <!-- Checkbox -->
      <div v-else-if="field.type === 'checkbox'" :id="fieldId(field, i)" class="df-field" :class="{ 'df-field-error': hasError(field, i) }">
        <label class="df-label">{{ field.label }} <span v-if="field.is_required" class="df-required">*</span></label>
        <div class="df-options">
          <label v-for="(opt, oi) in field.options" :key="oi" class="df-checkbox">
            <input
              type="checkbox"
              :value="opt.label"
              :checked="(getFieldValue(field, i) || []).includes(opt.label)"
              @change="toggleCheckbox(field, i, opt.label)"
            />
            <span>{{ opt.label }}</span>
          </label>
        </div>
        <p v-if="hasError(field, i)" class="df-error-text">Pertanyaan ini wajib diisi</p>
      </div>

      <!-- File -->
      <div v-else-if="field.type === 'file'" :id="fieldId(field, i)" class="df-field" :class="{ 'df-field-error': hasError(field, i) }">
        <label class="df-label">{{ field.label }} <span v-if="field.is_required" class="df-required">*</span></label>
        <input type="file" class="df-file" @change="(e: Event) => setFieldValue(field, i, (e.target as HTMLInputElement).files?.[0] || null)" />
        <p v-if="hasError(field, i)" class="df-error-text">Pertanyaan ini wajib diisi</p>
      </div>
    </template>
  </div>
</template>

<style scoped>
.dynamic-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.df-heading h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
  margin: 8px 0 0;
  padding-bottom: 8px;
  border-bottom: 1px solid #e5e7eb;
}

.df-paragraph p {
  font-size: 14px;
  color: #475569;
  margin: 4px 0 0;
  line-height: 1.5;
}

.df-divider {
  border: none;
  border-top: 1px solid #e5e7eb;
  margin: 8px 0;
}

.df-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.df-label {
  font-size: 13px;
  font-weight: 500;
  color: #475569;
  letter-spacing: 0.3px;
}

.df-required {
  color: #ef4444;
  margin-left: 2px;
}

.df-options {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.df-radio,
.df-checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #1e293b;
  cursor: pointer;
}

.df-radio input,
.df-checkbox input {
  accent-color: #2563eb;
  width: 16px;
  height: 16px;
}

.df-file {
  font-size: 14px;
  color: #475569;
}

.df-field-error {
  animation: shake 0.3s ease;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20%, 60% { transform: translateX(-4px); }
  40%, 80% { transform: translateX(4px); }
}

.df-error-text {
  font-size: 12px;
  color: #ef4444;
  margin: 0;
  font-weight: 500;
}
</style>
