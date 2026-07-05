<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DynamicForm from '../common/DynamicForm.vue'
import SkeletonCard from '../common/SkeletonCard.vue'
import Button from '../common/Button.vue'
import { formTemplateApi } from '../../api/formTemplate'
import type { FormField } from '../../types'
import { toast } from 'vue-sonner'

const props = withDefaults(defineProps<{
  beritaAcaraFormTemplateId?: number | null
  beritaAcaraFormData?: Record<string, any> | null
  rekapAnalisData?: Record<string, any> | null
  loading?: boolean
}>(), {
  beritaAcaraFormTemplateId: null,
  beritaAcaraFormData: null,
  rekapAnalisData: null,
  loading: false,
})

const emit = defineEmits<{
  submit: [data: { rekap_analis_data: Record<string, any>; rekap_analis_template_id: number | null }]
}>()

const templateFields = ref<FormField[]>([])
const templateId = ref<number | null>(null)
const loadingTemplate = ref(true)
const formValues = ref<Record<string, any>>({})
const dynamicFormRef = ref<InstanceType<typeof DynamicForm> | null>(null)

onMounted(async () => {
  try {
    const tid = props.beritaAcaraFormTemplateId
    if (!tid) return

    const template = await formTemplateApi.getPublicTemplate(tid)
    if (template && template.fields) {
      templateFields.value = template.fields
      templateId.value = template.id
    }
  } catch {
    // No template found
  } finally {
    loadingTemplate.value = false
  }
})

function getInitialData(): Record<string, any> {
  if (props.rekapAnalisData && Object.keys(props.rekapAnalisData).length > 0) {
    return props.rekapAnalisData
  }
  if (props.beritaAcaraFormData) {
    return { ...props.beritaAcaraFormData }
  }
  return {}
}

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
    rekap_analis_data: formValues.value,
    rekap_analis_template_id: templateId.value,
  })
}
</script>

<template>
  <div class="rekap-form">
    <SkeletonCard v-if="loadingTemplate" :rows="5" />

    <div v-else-if="templateFields.length === 0" class="rekap-form__empty">
      <p>Form template berita acara tidak ditemukan.</p>
    </div>

    <div v-else class="rekap-form__form">
      <div class="rekap-form__section">
        <h3 class="rekap-form__section-title">Formulir Rekap Analis</h3>
        <p class="rekap-form__desc">Berikut adalah data berita acara yang bisa Anda edit sebagai rekap analis.</p>
        <DynamicForm
          ref="dynamicFormRef"
          :fields="templateFields"
          :initial-data="getInitialData()"
          @update:values="handleValuesUpdate"
        />
      </div>

      <div class="rekap-form__actions">
        <Button :disabled="loading" @click="handleSubmit">
          {{ loading ? 'Menyimpan...' : 'Simpan Rekap' }}
        </Button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.rekap-form__section {
  margin-bottom: 32px;
  padding: 20px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.rekap-form__section-title {
  margin: 0 0 8px;
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  padding-bottom: 8px;
  border-bottom: 1px solid #e5e7eb;
}
.rekap-form__desc {
  margin: 0 0 16px;
  font-size: 13px;
  color: #64748b;
}
.rekap-form__actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}
.rekap-form__empty {
  text-align: center;
  padding: 40px 20px;
  color: #64748b;
}
</style>
