<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Pause, Play, Pencil, Plus } from '@lucide/vue'
import LoadingSpinner from './LoadingSpinner.vue'
import Button from './Button.vue'
import { formTemplateApi } from '../../api/formTemplate'
import type { FormTemplate } from '../../types'
import { toast } from 'vue-sonner'

const props = defineProps<{
  type?: string
}>()

const emit = defineEmits<{
  edit: [id: number]
}>()

const templates = ref<FormTemplate[]>([])
const loading = ref(true)
const creating = ref(false)

async function fetchTemplates() {
  loading.value = true
  try {
    templates.value = await formTemplateApi.getTemplates(props.type)
  } catch {
    toast.error('Gagal memuat data template')
  } finally {
    loading.value = false
  }
}

onMounted(fetchTemplates)

async function toggleActive(tpl: FormTemplate) {
  try {
    await formTemplateApi.updateTemplate(tpl.id, { is_active: !tpl.is_active })
    tpl.is_active = !tpl.is_active
    toast.success(tpl.is_active ? 'Template diaktifkan' : 'Template dinonaktifkan')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal update status')
  }
}

async function createTemplate() {
  if (!props.type) return
  creating.value = true
  try {
    const name = props.type === 'berita_acara' ? 'Formulir Berita Acara Baru' : 'Formulir Rekap Analis Baru'
    const tpl = await formTemplateApi.createTemplate({
      name,
      type: props.type,
      description: `Template ${props.type === 'berita_acara' ? 'berita acara' : 'rekap analis'} baru`,
    })
    toast.success('Template berhasil dibuat')
    emit('edit', tpl.id)
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal membuat template')
  } finally {
    creating.value = false
  }
}

function typeLabel(type: string) {
  return type === 'berita_acara' ? 'Berita Acara' : 'Rekap Analis'
}
</script>

<template>
  <div>
    <div class="template-list-header">
      <Button variant="primary" size="sm" :loading="creating" @click="createTemplate">
        <Plus :size="16" /> Buat Template Baru
      </Button>
    </div>

    <LoadingSpinner v-if="loading" size="lg" />

    <div v-else-if="templates.length === 0" class="template-empty">
      <p>Belum ada template. Klik tombol di atas untuk membuat template baru.</p>
    </div>

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
            <button class="t-action flex-center" @click="toggleActive(tpl)" :title="tpl.is_active ? 'Nonaktifkan' : 'Aktifkan'">
              <Pause v-if="tpl.is_active" :size="16" />
              <Play v-else :size="16" />
            </button>
            <button class="t-action t-action--edit flex-center" @click="emit('edit', tpl.id)" title="Edit form">
              <Pencil :size="16" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.template-list-header {
  display: flex; justify-content: flex-end; margin-bottom: 16px;
}
.template-empty {
  text-align: center; padding: 40px 20px; color: #94a3b8; background: #fff;
  border: 1px dashed #e2e8f0; border-radius: 12px;
}
.template-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 14px; }
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
  width: 32px; height: 32px; border: none; border-radius: 6px; background: transparent;
  cursor: pointer; transition: background 0.15s;
  color: #64748b;
}
.flex-center {
  display: flex;
  align-items: center;
  justify-content: center;
}
.t-action:hover { background: #f1f5f9; color: #1e293b; }
.t-action--edit:hover { background: #eff6ff; color: #2563eb; }
</style>
