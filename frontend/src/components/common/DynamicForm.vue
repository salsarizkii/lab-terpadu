<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue'
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
const checkboxDropdownOpen = ref<Record<string, boolean>>({})
const checkboxDropdownRefs = ref<Record<string, HTMLElement | null>>({})

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

function toggleCheckboxDropdown(key: string) {
  checkboxDropdownOpen.value[key] = !checkboxDropdownOpen.value[key]
}

function handleClickOutsideCheckbox(e: MouseEvent) {
  const target = e.target as Node
  for (const key in checkboxDropdownRefs.value) {
    const el = checkboxDropdownRefs.value[key]
    if (el && !el.contains(target)) {
      checkboxDropdownOpen.value[key] = false
    }
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutsideCheckbox)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutsideCheckbox)
})

function validate(): { valid: boolean; firstErrorField?: string; missingFields: string[] } {
  errors.value.clear()
  let firstError: string | undefined
  const missingFields: string[] = []

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
      missingFields.push(f.label)
      if (!firstError) firstError = fieldId(f, i)
    }
  }

  return { valid: errors.value.size === 0, firstErrorField: firstError, missingFields }
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

      <!-- Checkbox (Dropdown style) -->
      <div v-else-if="field.type === 'checkbox'" :id="fieldId(field, i)" class="df-field" :class="{ 'df-field-error': hasError(field, i) }">
        <label class="df-label">{{ field.label }} <span v-if="field.is_required" class="df-required">*</span></label>
        <div class="df-checkbox-dropdown" :ref="el => { checkboxDropdownRefs[fieldKey(field, i)] = el as HTMLElement }">
          <button
            type="button"
            class="df-checkbox-trigger"
            :class="{ open: checkboxDropdownOpen[fieldKey(field, i)] }"
            @click.stop="toggleCheckboxDropdown(fieldKey(field, i))"
          >
            <span class="df-checkbox-trigger__text">
              <template v-if="(getFieldValue(field, i) || []).length === 0">Pilih...</template>
              <template v-else>{{ (getFieldValue(field, i) || []).length }} dipilih</template>
            </span>
            <svg class="df-checkbox-trigger__chevron" :class="{ open: checkboxDropdownOpen[fieldKey(field, i)] }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <Transition name="df-dropdown">
            <div v-if="checkboxDropdownOpen[fieldKey(field, i)]" class="df-checkbox-panel" @click.stop>
              <label v-for="(opt, oi) in field.options" :key="oi" class="df-checkbox-item">
                <input
                  type="checkbox"
                  :value="opt.label"
                  :checked="(getFieldValue(field, i) || []).includes(opt.label)"
                  @change="toggleCheckbox(field, i, opt.label)"
                />
                <span class="df-checkbox-item__check">
                  <svg v-if="(getFieldValue(field, i) || []).includes(opt.label)" width="12" height="12" viewBox="0 0 16 16" fill="none"><path d="M3.5 8.5L6.5 11.5L12.5 4.5" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="df-checkbox-item__label">{{ opt.label }}</span>
              </label>
            </div>
          </Transition>
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

.df-checkbox-dropdown {
  position: relative;
}
.df-checkbox-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 10px 14px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  font-size: 14px;
  color: #1e293b;
  cursor: pointer;
  transition: border-color 0.15s;
  font-family: inherit;
  text-align: left;
}
.df-checkbox-trigger:hover { border-color: #cbd5e1; }
.df-checkbox-trigger.open { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 236, 0.08); }
.df-checkbox-trigger__text { color: #64748b; }
.df-checkbox-trigger__chevron { color: #94a3b8; transition: transform 0.2s; flex-shrink: 0; margin-left: 8px; }
.df-checkbox-trigger__chevron.open { transform: rotate(180deg); }

.df-checkbox-panel {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  max-height: 240px;
  overflow-y: auto;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
  z-index: 50;
  padding: 6px 0;
}
.df-checkbox-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 14px;
  cursor: pointer;
  transition: background 0.1s;
  font-size: 14px;
  color: #1e293b;
}
.df-checkbox-item:hover { background: #f1f5f9; }
.df-checkbox-item input { display: none; }
.df-checkbox-item__check {
  width: 18px;
  height: 18px;
  border: 1.5px solid #d1d5db;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.15s;
}
.df-checkbox-item input:checked ~ .df-checkbox-item__check {
  background: #2563eb;
  border-color: #2563eb;
}
.df-checkbox-item__label { flex: 1; line-height: 1.3; }

.df-dropdown-enter-active, .df-dropdown-leave-active { transition: opacity 0.15s, transform 0.15s; }
.df-dropdown-enter-from, .df-dropdown-leave-to { opacity: 0; transform: translateY(-4px); }

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
