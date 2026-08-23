<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Button from '../components/common/Button.vue'
import DynamicForm from '../components/common/DynamicForm.vue'
import SkeletonDetail from '../components/common/SkeletonDetail.vue'
import { beritaAcaraApi } from '../api/beritaAcara'
import { formTemplateApi } from '../api/formTemplate'
import { instrumentPriceApi } from '../api/instrumentPrice'
import { uploadApi } from '../api/upload'
import type { BeritaAcara, FormField } from '../types'
import { toast } from 'vue-sonner'

const route = useRoute()
const router = useRouter()

const item = ref<BeritaAcara | null>(null)
const templateFields = ref<FormField[]>([])
const templateId = ref<number | null>(null)
const loading = ref(true)
const submitting = ref(false)
const formValues = ref<Record<string, any>>({})
const dynamicFormRef = ref<InstanceType<typeof DynamicForm> | null>(null)

const statusMap: Record<string, string> = {
  revision: 'Perlu Perbaikan',
}

onMounted(async () => {
  const id = Number(route.params.id)
  if (!id || isNaN(id)) {
    toast.error('ID tidak valid')
    loading.value = false
    return
  }
  try {
    const baData = await beritaAcaraApi.getById(id)
    if (baData.status !== 'revision') {
      toast.error('Berita acara tidak dalam status perlu perbaikan')
      router.push(`/user/berita-acara/${id}`)
      return
    }
    item.value = baData

    if (baData.form_template_id) {
      try {
        const [tpl, instrumentsRes] = await Promise.all([
          formTemplateApi.getPublicTemplate(baData.form_template_id),
          instrumentPriceApi.getAll().then(r => r.data || []).catch(() => []),
        ])
        if (tpl?.fields) {
          const instruments = Array.isArray(instrumentsRes) ? instrumentsRes : []
          templateFields.value = tpl.fields.map(f => {
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
          templateId.value = tpl.id
        }
      } catch {}
    }
  } catch {
    toast.error('Gagal memuat data')
  } finally {
    loading.value = false
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

async function handleSubmit() {
  if (!item.value) return

  if (dynamicFormRef.value) {
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
  }

  submitting.value = true
  try {
    const cleanedFormData = { ...formValues.value }
    let uploadAksesPath: string | null = null
    for (const [key, value] of Object.entries(cleanedFormData)) {
      if (value instanceof File) {
        try {
          const uploaded = await uploadApi.uploadFile(value, 'berita-acara')
          cleanedFormData[key] = uploaded.path
          const field = templateFields.value.find(f => `field_${f.id}` === key || `field_${templateFields.value.indexOf(f)}` === key)
          if (field && field.label && field.label.toLowerCase().includes('upload akses')) {
            uploadAksesPath = uploaded.path
          }
        } catch {
          toast.error(`Gagal mengupload file: ${value.name}`)
          submitting.value = false
          return
        }
      }
    }
    const fd = new FormData()
    fd.append('form_data', JSON.stringify(cleanedFormData))
    if (templateId.value) {
      fd.append('form_template_id', String(templateId.value))
    }
    if (uploadAksesPath) {
      fd.append('upload_akses', uploadAksesPath)
    }
    await beritaAcaraApi.update(item.value.id, fd)
    localStorage.removeItem(`ba_perbaiki_${item.value.id}`)
    toast.success('Berita acara berhasil diperbarui')
    router.push(`/user/berita-acara/${item.value.id}`)
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menyimpan')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AppLayout>
    <div>
      <SkeletonDetail v-if="loading" />

      <template v-else-if="item">
        <div class="page-header">
          <div class="page-header__left">
            <Button variant="outline" @click="router.push(`/user/berita-acara/${item.id}`)">Kembali</Button>
            <h1>Edit Berita Acara</h1>
          </div>
          <span class="status-badge">{{ statusMap[item.status] || item.status }}</span>
        </div>

        <!-- Revision note -->
        <div v-if="item.revisions && item.revisions.length" class="revision-notice">
          <div class="revision-notice__icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
          </div>
          <div class="revision-notice__content">
            <div class="revision-notice__title">Catatan dari Admin</div>
            <div class="revision-notice__note">{{ item.revisions[item.revisions.length - 1].note }}</div>
          </div>
        </div>

        <Card>
          <div v-if="templateFields.length === 0" class="empty-form">
            <p>Template form tidak ditemukan.</p>
          </div>

          <form v-else class="edit-form" @submit.prevent="handleSubmit">
            <div class="form-section">
              <h3 class="form-section__title">Formulir Berita Acara</h3>
              <DynamicForm
                ref="dynamicFormRef"
                :fields="templateFields"
                :initial-data="item.form_data || undefined"
                @update:values="handleValuesUpdate"
              />
            </div>

            <div class="form-actions">
              <Button variant="outline" @click="router.push(`/user/berita-acara/${item.id}`)">Batal</Button>
              <Button :disabled="submitting" @click="handleSubmit">
                {{ submitting ? 'Menyimpan...' : 'Perbaiki & Submit Ulang' }}
              </Button>
            </div>
          </form>
        </Card>
      </template>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}
.page-header__left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.page-header h1 {
  font-size: 24px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}
.status-badge {
  padding: 6px 14px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 500;
  color: #92400e;
}

.revision-notice {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 10px;
  margin-bottom: 20px;
}
.revision-notice__icon { color: #d97706; flex-shrink: 0; margin-top: 1px; }
.revision-notice__content { flex: 1; }
.revision-notice__title { font-size: 14px; font-weight: 600; color: #92400e; margin-bottom: 2px; }
.revision-notice__note { font-size: 13px; color: #a16207; line-height: 1.4; }

.form-section {
  margin-bottom: 24px;
  padding: 20px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.form-section__title {
  margin: 0 0 16px;
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  padding-bottom: 8px;
  border-bottom: 1px solid #e5e7eb;
}
.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}
.empty-form {
  text-align: center;
  padding: 40px 20px;
  color: #64748b;
}
</style>
