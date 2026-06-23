<script setup lang="ts">
import Badge from '../common/Badge.vue'
import Button from '../common/Button.vue'
import type { Sample } from '../../types'

defineProps<{
  samples: Sample[]
}>()

const emit = defineEmits<{
  markTaken: [sampleId: number]
}>()

const statusMap: Record<string, { type: 'success' | 'warning' | 'danger' | 'info' | 'default'; label: string }> = {
  pending: { type: 'default', label: 'Pending' },
  processing: { type: 'warning', label: 'Processing' },
  ready: { type: 'success', label: 'Ready' },
  taken: { type: 'info', label: 'Taken' },
}
</script>

<template>
  <div class="sample-list">
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Quantity</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="sample in samples" :key="sample.id">
          <td>{{ sample.name }}</td>
          <td>{{ sample.quantity ? `${sample.quantity} ${sample.unit || ''}` : '-' }}</td>
          <td>
            <Badge :type="statusMap[sample.status]?.type || 'default'" :text="statusMap[sample.status]?.label || sample.status" />
          </td>
          <td>
            <Button
              v-if="sample.status === 'ready'"
              size="sm"
              @click="emit('markTaken', sample.id)"
            >
              Mark Taken
            </Button>
            <span v-else class="text-muted">-</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}
.table th {
  text-align: left;
  padding: 12px 16px;
  background: #f9fafb;
  color: #6b7280;
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #e5e7eb;
}
.table td {
  padding: 12px 16px;
  border-bottom: 1px solid #e5e7eb;
  color: #374151;
}
.text-muted { color: #9ca3af; font-size: 13px; }
</style>
