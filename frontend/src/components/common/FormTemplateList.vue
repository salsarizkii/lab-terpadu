<script setup lang="ts">
import { ref, onMounted } from 'vue'
import LoadingSpinner from './LoadingSpinner.vue'
import Alert from './Alert.vue'
import { formTemplateApi } from '../../api/formTemplate'
import type { FormTemplate } from '../../types'

const emit = defineEmits<{
  edit: [id: number]
}>()

const templates = ref<FormTemplate[]>([])
const loading = ref(true)
const error = ref('')
const success = ref('')

async function fetchTemplates() {
  loading.value = true
  try {
    templates.value = await formTemplateApi.getTemplates()
  } catch {
    error.value = 'Gagal memuat data template'
  } finally {
    loading.value = false
  }
}

onMounted(fetchTemplates)

async function toggleActive(tpl: FormTemplate) {
  try {
    await formTemplateApi.updateTemplate(tpl.id, { is_active: !tpl.is_active })
    tpl.is_active = !tpl.is_active
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal update status'
  }
}

function typeLabel(type: string) {
  return type === 'berita_acara' ? 'Berita Acara' : 'Rekap Analis'
}
</script>

<template>
  <div>
    <Alert v-if="success" type="success" :message="success" @dismiss="success = ''" />
    <Alert v-if="error" type="error" :message="error" @dismiss="error = ''" />

    <LoadingSpinner v-if="loading" size="lg" />

    <div v-else class="template-list">
      <div v-for="tpl in templates" :key="tpl.id" class="template-card">
        <div class="template-card__header">
          <div>
            <h3 class="template-card__name">{{ tpl.name }}</h3>
            <p class="template-card__meta">{{ typeLabel(tpl.type) }} · {{ tpl.fields_count ?? 0 }} field</p>
          </div>
          <span class="template-card__status" :class="tpl.is_active ? 'active' : 'inactive'">
            {{ tpl.is_active ? 'Aktif' : 'Nonaktif' }}
          </span>
        </div>
        <p v-if="tpl.description" class="template-card__desc">{{ tpl.description }}</p>
        <div class="template-card__footer">
          <span class="template-card__creator">{{ tpl.creator?.name || '-' }}</span>
          <div class="template-card__actions">
            <button v-if="!tpl.is_default" class="t-action" @click="toggleActive(tpl)" :title="tpl.is_active ? 'Nonaktifkan' : 'Aktifkan'">
              {{ tpl.is_active ? '⏸' : '▶' }}
            </button>
            <button class="t-action t-action--edit" @click="emit('edit', tpl.id)" title="Edit form">✏️</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.template-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px; }
.template-card {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
  padding: 18px; transition: all 0.15s;
}
.template-card:hover { border-color: #cbd5e1; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.template-card__header { display: flex; justify-content: space-between; align-items: flex-start; }
.template-card__name { font-size: 15px; font-weight: 600; color: #1e293b; margin: 0 0 2px; }
.template-card__meta { font-size: 12px; color: #94a3b8; margin: 0; }
.template-card__status {
  font-size: 11px; padding: 3px 10px; border-radius: 20px; font-weight: 500;
}
.template-card__status.active { background: #d1fae5; color: #065f46; }
.template-card__status.inactive { background: #f1f5f9; color: #64748b; }
.template-card__desc { font-size: 13px; color: #64748b; margin: 10px 0 0; line-height: 1.5; }
.template-card__footer {
  display: flex; justify-content: space-between; align-items: center;
  margin-top: 14px; padding-top: 12px; border-top: 1px solid #f1f5f9;
}
.template-card__creator { font-size: 12px; color: #94a3b8; }
.template-card__actions { display: flex; gap: 4px; }
.t-action {
  width: 30px; height: 30px; border: none; border-radius: 6px; background: transparent;
  cursor: pointer; font-size: 14px; transition: background 0.15s;
}
.t-action:hover { background: #f1f5f9; }
.t-action--edit:hover { background: #eff6ff; }
</style>
