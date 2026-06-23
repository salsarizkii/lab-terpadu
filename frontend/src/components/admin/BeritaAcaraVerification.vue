<script setup lang="ts">
import { ref } from 'vue'
import FormSelect from '../common/FormSelect.vue'
import Button from '../common/Button.vue'
import FormTextarea from '../common/FormTextarea.vue'
import Badge from '../common/Badge.vue'
import type { BeritaAcara } from '../../types'

defineProps<{
  beritaAcara: BeritaAcara
}>()

const emit = defineEmits<{
  submit: [data: { status: string; note?: string }]
}>()

const status = ref('')
const note = ref('')
const loading = ref(false)

const statusOptions = [
  { value: 'approved', label: 'Approved' },
  { value: 'revision', label: 'Revision' },
  { value: 'rejected', label: 'Rejected' },
]

function handleSubmit() {
  loading.value = true
  emit('submit', { status: status.value, note: note.value || undefined })
  loading.value = false
}

const statusMap: Record<string, { type: 'success' | 'warning' | 'danger' | 'info' | 'default'; label: string }> = {
  draft: { type: 'default', label: 'Draft' },
  pending: { type: 'warning', label: 'Pending' },
  revision: { type: 'danger', label: 'Revision' },
  approved: { type: 'success', label: 'Approved' },
  rejected: { type: 'danger', label: 'Rejected' },
}
</script>

<template>
  <div class="verification">
    <div class="verification__info">
      <p><strong>Title:</strong> {{ beritaAcara.judul_penelitian || '-' }}</p>
      <p><strong>Applicant:</strong> {{ beritaAcara.nama_pemohon || '-' }}</p>
      <p><strong>Current Status:</strong>
        <Badge :type="statusMap[beritaAcara.status]?.type || 'default'" :text="statusMap[beritaAcara.status]?.label || beritaAcara.status" />
      </p>
    </div>
    <form @submit.prevent="handleSubmit">
      <FormSelect v-model="status" label="New Status" :options="statusOptions" required />
      <FormTextarea v-if="status === 'revision'" v-model="note" label="Revision Note" required />
      <div class="verification__actions">
        <Button type="submit" :disabled="loading || !status">
          {{ loading ? 'Updating...' : 'Update Status' }}
        </Button>
      </div>
    </form>
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
  margin-top: 16px;
}

</style>
