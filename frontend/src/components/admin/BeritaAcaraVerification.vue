<script setup lang="ts">
import { ref } from 'vue'
import Button from '../common/Button.vue'
import Badge from '../common/Badge.vue'
import type { BeritaAcara } from '../../types'

defineProps<{
  beritaAcara: BeritaAcara
}>()

const emit = defineEmits<{
  submit: [data: { status: string }]
}>()

const loading = ref(false)

function handleSubmit() {
  loading.value = true
  emit('submit', { status: 'approved' })
  loading.value = false
}

const statusMap: Record<string, { type: 'success' | 'warning' | 'danger' | 'info' | 'default'; label: string }> = {
  draft: { type: 'default', label: 'Draft' },
  pending: { type: 'warning', label: 'Diajukan' },
  revision: { type: 'danger', label: 'Revisi' },
  approved: { type: 'success', label: 'Disetujui' },
  rejected: { type: 'danger', label: 'Ditolak' },
}
</script>

<template>
  <div class="verification">
    <div class="verification__info">
      <p><strong>Judul:</strong> {{ beritaAcara.judul_penelitian || '-' }}</p>
      <p><strong>Pemohon:</strong> {{ beritaAcara.nama_pemohon || '-' }}</p>
      <p><strong>Status Saat Ini:</strong>
        <Badge :type="statusMap[beritaAcara.status]?.type || 'default'" :text="statusMap[beritaAcara.status]?.label || beritaAcara.status" />
      </p>
    </div>
    <div class="verification__actions">
      <Button :disabled="loading" @click="handleSubmit">
        {{ loading ? 'Memproses...' : 'Setujui' }}
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
.verification__info p {
  margin: 4px 0;
  font-size: 14px;
}
.verification__actions {
  display: flex;
  justify-content: flex-end;
}
</style>
