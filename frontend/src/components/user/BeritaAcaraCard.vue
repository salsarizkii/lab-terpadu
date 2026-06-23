<script setup lang="ts">
import type { BeritaAcara } from '../../types'
import Badge from '../common/Badge.vue'

defineProps<{
  item: BeritaAcara
}>()

const statusMap: Record<string, { type: 'success' | 'warning' | 'danger' | 'info' | 'default'; label: string }> = {
  draft: { type: 'default', label: 'Draft' },
  pending: { type: 'warning', label: 'Pending' },
  revision: { type: 'danger', label: 'Revision' },
  approved: { type: 'success', label: 'Approved' },
  rejected: { type: 'danger', label: 'Rejected' },
}
</script>

<template>
  <div class="ba-card">
    <div class="ba-card__header">
      <h4 class="ba-card__title">{{ item.judul_penelitian || 'No title' }}</h4>
      <Badge :type="statusMap[item.status]?.type || 'default'" :text="statusMap[item.status]?.label || item.status" />
    </div>
    <p class="ba-card__meta">
      {{ item.nama_pemohon ? `${item.nama_pemohon} • ` : '' }}{{ new Date(item.created_at).toLocaleDateString() }}
    </p>
  </div>
</template>

<style scoped>
.ba-card {
  padding: 16px;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  cursor: pointer;
  transition: box-shadow 0.15s;
}
.ba-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}
.ba-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
}
.ba-card__title {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
  color: #111827;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.ba-card__meta {
  margin: 0;
  font-size: 13px;
  color: #6b7280;
}
</style>
