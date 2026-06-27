<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppLayout from '../components/common/AppLayout.vue'
import Button from '../components/common/Button.vue'
import LoadingSpinner from '../components/common/LoadingSpinner.vue'
import Alert from '../components/common/Alert.vue'
import FormTemplateList from '../components/common/FormTemplateList.vue'
import FormBuilder from '../components/common/FormBuilder.vue'
import { formTemplateApi } from '../api/formTemplate'
import type { FormTemplate, FormField } from '../types'

const view = ref<'list' | 'editor'>('list')
const templates = ref<FormTemplate[]>([])
const currentTemplate = ref<FormTemplate | null>(null)
const currentFields = ref<FormField[]>([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

async function fetchTemplate(id: number) {
  loading.value = true
  try {
    currentTemplate.value = await formTemplateApi.getTemplate(id)
    currentFields.value = (currentTemplate.value.fields || []).map(f => ({
      label: f.label,
      type: f.type,
      options: f.options,
      is_required: f.is_required,
      placeholder: f.placeholder,
    }))
    view.value = 'editor'
  } catch {
    error.value = 'Gagal memuat template'
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
  saving.value = true
  error.value = ''
  try {
    await formTemplateApi.syncFields(currentTemplate.value.id, {
      fields: currentFields.value.map((f, i) => ({
        label: f.label,
        type: f.type,
        options: f.options,
        is_required: f.is_required,
        placeholder: f.placeholder,
      })),
    })
    success.value = 'Form berhasil disimpan'
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal menyimpan form'
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
        <Button v-if="view === 'editor'" variant="secondary" @click="goToList">← Kembali</Button>
      </div>

      <Alert v-if="success" type="success" :message="success" @dismiss="success = ''" />
      <Alert v-if="error" type="error" :message="error" @dismiss="error = ''" />

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
            <Button variant="primary" :loading="saving" @click="saveFields">💾 Simpan Form</Button>
          </div>
          <FormBuilder v-model:fields="currentFields" />
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

.editor-section { margin-top: 4px; }
.editor-topbar {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;
}
.editor-title { font-size: 20px; font-weight: 600; color: #1e293b; margin: 0; }
.editor-meta { font-size: 13px; color: #64748b; margin: 2px 0 0; }
</style>
