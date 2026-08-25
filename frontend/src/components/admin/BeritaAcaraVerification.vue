<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import Button from '../common/Button.vue'
import Badge from '../common/Badge.vue'
import { formTemplateApi } from '../../api/formTemplate'
import { useAuthStore } from '../../stores/authStore'
import type { BeritaAcara, FormField } from '../../types'

const props = defineProps<{
  beritaAcara: BeritaAcara
  targetStatus?: string
  approveLabel?: string
}>()

const emit = defineEmits<{
  submit: [data: { status: string }]
}>()

const auth = useAuthStore()
const loading = ref(false)
const templateFields = ref<FormField[]>([])

const verifierName = computed(() => auth.user?.name || '-')
const verifierEmail = computed(() => auth.user?.email || '-')

function handleSubmit() {
  loading.value = true
  emit('submit', { status: props.targetStatus || 'admin_verified' })
  loading.value = false
}

const statusMap: Record<string, { type: 'success' | 'warning' | 'danger' | 'info' | 'default'; label: string }> = {
  draft: { type: 'default', label: 'Draft' },
  pending: { type: 'warning', label: 'Menunggu Admin' },
  revision: { type: 'danger', label: 'Revisi' },
  admin_verified: { type: 'info', label: 'Diverifikasi Admin' },
  penyelia_verified: { type: 'info', label: 'Diverifikasi Penyelia' },
  rekap_ready: { type: 'warning', label: 'Menunggu Manager Teknis' },
  completed: { type: 'success', label: 'Selesai' },
  rejected: { type: 'danger', label: 'Ditolak' },
}

function formData(): Record<string, any> {
  return (props.beritaAcara as any).form_data || {}
}

function formatFieldKey(key: string): string {
  const field = templateFields.value.find(f => `field_${f.id}` === key)
  if (field) return field.label
  return key.replace(/^field_/, '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function resolveByLabel(label: string): any {
  const fd = formData()
  if (!Object.keys(fd).length) return undefined
  const field = templateFields.value.find(f => f.label.toLowerCase().includes(label.toLowerCase()))
  if (field) {
    const byId = fd[`field_${field.id}`]
    if (byId !== undefined) return byId
  }
  for (const [key, val] of Object.entries(fd)) {
    if (formatFieldKey(key).toLowerCase().includes(label.toLowerCase())) return val
  }
  return undefined
}

function formatValue(val: any): string {
  if (val === null || val === undefined) return '-'
  if (Array.isArray(val)) return val.join(', ')
  if (typeof val === 'string' && (val.startsWith('[') || val.startsWith('{'))) {
    try {
      const parsed = JSON.parse(val)
      if (Array.isArray(parsed)) return parsed.join(', ')
    } catch {}
  }
  return String(val)
}

const nomorAgenda = computed(() => props.beritaAcara.nomor_agenda?.trim() || '-')

const judul = computed(() => {
  const fromForm = resolveByLabel('Judul')
  return formatValue(fromForm) !== '-' ? formatValue(fromForm) : (props.beritaAcara.judul_penelitian || '-')
})

const pemohonNama = computed(() => {
  const fromForm = resolveByLabel('Nama Pemohon')
  if (formatValue(fromForm) !== '-') return formatValue(fromForm)
  return props.beritaAcara.nama_pemohon || props.beritaAcara.user?.name || '-'
})

const pemohonEmail = computed(() => {
  const fromForm = resolveByLabel('Email')
  if (formatValue(fromForm) !== '-') return formatValue(fromForm)
  return props.beritaAcara.email || props.beritaAcara.user?.email || '-'
})

const instrumen = computed(() => {
  const fromForm = resolveByLabel('Metode/Instrumen') ?? resolveByLabel('Permintaan Analisa')
  if (formatValue(fromForm) !== '-') return formatValue(fromForm)
  return props.beritaAcara.permintaan_analisa || '-'
})

async function loadTemplate() {
  const tplId = (props.beritaAcara as any).form_template_id
  if (!tplId) return
  try {
    const tpl = await formTemplateApi.getPublicTemplate(tplId)
    if (tpl?.fields) templateFields.value = tpl.fields
  } catch {}
}

watch(() => props.beritaAcara, () => {
  templateFields.value = []
  loadTemplate()
}, { immediate: true })
</script>

<template>
  <div class="verification">
    <div class="verification__info">
      <div class="verification__row">
        <span class="verification__label">Nomor Agenda</span>
        <span class="verification__agenda">{{ nomorAgenda }}</span>
      </div>
      <div class="verification__row">
        <span class="verification__label">Judul</span>
        <span class="verification__value">{{ judul }}</span>
      </div>
      <div class="verification__row">
        <span class="verification__label">Pemohon</span>
        <span class="verification__value">
          {{ pemohonNama }}
          <span v-if="pemohonEmail && pemohonEmail !== '-'" class="verification__email">({{ pemohonEmail }})</span>
        </span>
      </div>
      <div class="verification__row">
        <span class="verification__label">Instrumen</span>
        <span class="verification__value">{{ instrumen }}</span>
      </div>
      <div class="verification__row">
        <span class="verification__label">Status Saat Ini</span>
        <Badge :type="statusMap[beritaAcara.status]?.type || 'default'" :text="statusMap[beritaAcara.status]?.label || beritaAcara.status" />
      </div>
      <div class="verification__divider" />
      <div class="verification__row">
        <span class="verification__label">Diverifikasi Oleh</span>
        <span class="verification__value">
          <span class="verification__verifier-name">{{ verifierName }}</span>
          <span class="verification__verifier-email">{{ verifierEmail }}</span>
        </span>
      </div>
    </div>
    <div class="verification__actions">
      <Button :disabled="loading" @click="handleSubmit">
        {{ loading ? 'Memproses...' : (approveLabel || 'Setujui') }}
      </Button>
    </div>
  </div>
</template>

<style scoped>
.verification__info {
  margin-bottom: 16px;
  padding: 12px;
  background: #f9fafb;
  border-radius: 8px;
}
.verification__row {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin: 6px 0;
  font-size: 14px;
}
.verification__label {
  font-weight: 600;
  color: #475569;
  white-space: nowrap;
}
.verification__value {
  color: #0f172a;
  text-align: right;
  word-break: break-word;
}
.verification__agenda {
  color: #2563eb;
  font-weight: 700;
}
.verification__email {
  color: #64748b;
  font-size: 13px;
}
.verification__divider {
  margin: 10px 0;
  border-top: 1px dashed #e2e8f0;
}
.verification__verifier-name {
  font-weight: 700;
  color: #1d4ed8;
}
.verification__verifier-email {
  display: block;
  color: #64748b;
  font-size: 12px;
  margin-top: 2px;
}
.verification__actions {
  display: flex;
  justify-content: flex-end;
}
</style>
