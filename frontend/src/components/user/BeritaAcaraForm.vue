<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DynamicForm from '../common/DynamicForm.vue'
import SkeletonCard from '../common/SkeletonCard.vue'
import Button from '../common/Button.vue'
import { formTemplateApi } from '../../api/formTemplate'
import { instrumentPriceApi } from '../../api/instrumentPrice'
import type { FormField } from '../../types'
import { toast } from 'vue-sonner'

const props = withDefaults(defineProps<{
  initialData?: Record<string, any> | null
  loading?: boolean
}>(), {
  initialData: null,
  loading: false,
})

const emit = defineEmits<{
  submit: [data: { form_data: Record<string, any>; form_template_id: number | null; templateFields: FormField[] }]
}>()

const templateFields = ref<FormField[]>([])
const templateId = ref<number | null>(null)
const loadingTemplate = ref(true)
const formValues = ref<Record<string, any>>({})
const dynamicFormRef = ref<InstanceType<typeof DynamicForm> | null>(null)

onMounted(async () => {
  try {
    const [template, instrumentsRes] = await Promise.all([
      formTemplateApi.getPublicActiveByType('berita_acara'),
      instrumentPriceApi.getAll().then(r => r.data || []).catch(() => []),
    ])
    if (template && template.fields) {
      const instruments = Array.isArray(instrumentsRes) ? instrumentsRes : []
      templateFields.value = template.fields.map(f => {
        const isMetode = f.label.toLowerCase().includes('metode') && f.label.toLowerCase().includes('instrumen')
        if (isMetode) {
          return {
            ...f,
            type: 'checkbox' as const,
            options: instruments.length > 0
              ? instruments.map(i => ({ label: i.name, is_other: false }))
              : f.options,
          }
        }
        return f
      })
      templateId.value = template.id
    }
  } catch {
    // No template found
  } finally {
    loadingTemplate.value = false
  }
})

function handleValuesUpdate(values: Record<string, any>) {
  formValues.value = values
}

function scrollToField(fieldId: string) {
  const el = document.getElementById(fieldId)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }
}

function handleSubmit() {
  if (!dynamicFormRef.value) {
    toast.error('Form belum siap')
    return
  }

  const result = dynamicFormRef.value.validate()
  if (!result.valid) {
    const names = result.missingFields.slice(0, 3).join(', ')
    const extra = result.missingFields.length > 3 ? ` dan ${result.missingFields.length - 3} lainnya` : ''
    toast.error(`Wajib diisi: ${names}${extra}`)
    if (result.firstErrorField) {
      setTimeout(() => scrollToField(result.firstErrorField!), 100)
    }
    return
  }

  emit('submit', {
    form_data: formValues.value,
    form_template_id: templateId.value,
    templateFields: templateFields.value,
  })
}
</script>

<template>
  <div class="ba-form">
    <SkeletonCard v-if="loadingTemplate" :rows="5" />

    <div v-else-if="templateFields.length === 0" class="ba-form__empty">
      <p>Belum ada form template aktif untuk berita acara.</p>
      <p class="ba-form__empty-hint">Hubungi admin untuk membuat form template.</p>
    </div>

    <div v-else class="ba-form__form">
      <div class="ba-form__section">
        <h3 class="ba-form__section-title">Formulir Berita Acara</h3>
        <DynamicForm
          ref="dynamicFormRef"
          :fields="templateFields"
          :initial-data="initialData || undefined"
          @update:values="handleValuesUpdate"
        />
      </div>

      <div class="ba-form__actions">
        <Button :disabled="loading" @click="handleSubmit">
          {{ loading ? 'Saving...' : 'Submit' }}
        </Button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ba-form__section {
  margin-bottom: 32px;
  padding: 20px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.ba-form__section-title {
  margin: 0 0 16px;
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  padding-bottom: 8px;
  border-bottom: 1px solid #e5e7eb;
}
.ba-form__actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}
.ba-form__empty {
  text-align: center;
  padding: 40px 20px;
  color: #64748b;
}
.ba-form__empty-hint {
  font-size: 13px;
  color: #94a3b8;
  margin-top: 4px;
}
</style>
