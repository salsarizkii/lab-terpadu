<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ArrowLeft, Save } from '@lucide/vue'
import AppLayout from '../components/common/AppLayout.vue'
import Button from '../components/common/Button.vue'
import LoadingSpinner from '../components/common/LoadingSpinner.vue'
import FormTemplateList from '../components/common/FormTemplateList.vue'
import FormBuilder from '../components/common/FormBuilder.vue'
import { formTemplateApi } from '../api/formTemplate'
import type { FormTemplate, FormField } from '../types'
import { toast } from 'vue-sonner'

const view = ref<'list' | 'editor'>('list')
const templates = ref<FormTemplate[]>([])
const currentTemplate = ref<FormTemplate | null>(null)
const currentFields = ref<FormField[]>([])
const loading = ref(false)
const saving = ref(false)

async function fetchTemplate(id: number) {
  loading.value = true
  try {
    currentTemplate.value = await formTemplateApi.getTemplate(id)
    currentFields.value = (currentTemplate.value.fields || []).map(f => ({
      label: f.label,
      type: f.type,
      options: f.options,
      is_required: f.is_required,
    }))
    view.value = 'editor'
  } catch {
    toast.error('Gagal memuat template')
  } finally {
    loading.value = false
  }
}

function goToEditor(id: number) {
  fetchTemplate(id)
}

function goToList() {
  view.value = 'list'
  currentTemplate.value = null
  currentFields.value = []
}

async function saveFields() {
  if (!currentTemplate.value) return

  const fields = currentFields.value
  if (fields.length === 0) {
    return toast.error('Form tidak boleh kosong. Tambahkan minimal satu pertanyaan.')
  }

  for (let i = 0; i < fields.length; i++) {
    const f = fields[i]
    if (f.type !== 'divider' && (!f.label || !f.label.trim())) {
      return toast.error(`Teks pada bagian ke-${i + 1} tidak boleh kosong`)
    }
    if (f.type === 'select' || f.type === 'radio' || f.type === 'checkbox') {
      if (!f.options || f.options.length === 0) {
        return toast.error(`Pertanyaan ke-${i + 1} harus memiliki minimal 1 opsi`)
      }
      for (let j = 0; j < f.options.length; j++) {
        const o = f.options[j]
        if (!o.label || !o.label.trim()) {
          return toast.error(`Opsi ke-${j + 1} pada pertanyaan ke-${i + 1} tidak boleh kosong`)
        }
      }
    }
  }

  saving.value = true
  try {
    await formTemplateApi.syncFields(currentTemplate.value.id, {
      fields: currentFields.value.map((f, i) => ({
        label: f.label,
        type: f.type,
        options: f.options,
        is_required: f.is_required,
      })),
    })
    toast.success('Form berhasil disimpan')
  } catch (e: any) {
    const msg = e.response?.data?.message || 'Gagal menyimpan form'
    const errors = e.response?.data?.errors
    if (errors) {
      const flat = Object.values(errors).flat().join('. ')
      toast.error(flat)
    } else {
      toast.error(msg)
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AppLayout>
    <div class="ft-page">
      <div class="ft-header">
        <div>
          <h1>Kelola Form</h1>
          <p class="ft-subtitle">Buat dan edit form berita acara serta rekap data analis</p>
        </div>
      </div>

      <LoadingSpinner v-if="loading" size="lg" />

      <template v-else>
        <div v-if="view === 'list'">
          <FormTemplateList @edit="goToEditor" />
        </div>

        <div v-else-if="currentTemplate" class="editor-section">
          <div class="editor-topbar">
            <div>
              <h2 class="editor-title">{{ currentTemplate.name }}</h2>
              <p class="editor-meta">{{ currentTemplate.type === 'berita_acara' ? 'Berita Acara' : 'Rekap Analis' }} · {{ currentFields.length }} field</p>
            </div>
          </div>
          <FormBuilder v-model:fields="currentFields" class="mb-4" />
          
          <div class="editor-sticky-footer">
            <Button variant="outline" @click="goToList" class="flex-align-center gap-2">
              <ArrowLeft :size="16" /> Kembali
            </Button>
            <Button variant="primary" :loading="saving" @click="saveFields" class="flex-align-center gap-2">
              <Save :size="16" /> Simpan Form
            </Button>
          </div>
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<style scoped>
.ft-page { max-width: 1100px; }
.ft-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
.ft-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
.ft-subtitle { font-size: 14px; color: #64748b; margin: 4px 0 0; }
.flex-align-center { display: flex; align-items: center; }
.gap-2 { gap: 8px; }

.editor-section { margin-top: 4px; }
.editor-topbar {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;
}
.editor-title { font-size: 20px; font-weight: 600; color: #1e293b; margin: 0; }
.editor-meta { font-size: 13px; color: #64748b; margin: 2px 0 0; }

@media (max-width: 640px) {
  .ft-header, .editor-topbar {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }
}

.mb-4 {
  margin-bottom: 24px;
}

.editor-sticky-footer {
  position: sticky;
  bottom: 0;
  z-index: 100;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(12px);
  border-top: 1px solid #e2e8f0;
  border-radius: 0 0 12px 12px;
  box-shadow: 0 -4px 12px rgba(0,0,0,0.03);
  margin: 0 -4px -4px -4px; /* compensate for any padding if needed, or just let it sit inside */
}

@media (max-width: 640px) {
  .editor-sticky-footer {
    justify-content: space-between;
  }
}
</style>
