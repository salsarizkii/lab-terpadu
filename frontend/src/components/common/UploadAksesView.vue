<script setup lang="ts">
import { ref, computed } from 'vue'
import Modal from './Modal.vue'
import { FileText, Eye, Download } from '@lucide/vue'

const props = defineProps<{
  path: string | null | undefined
}>()

const showPreview = ref(false)

const fileName = computed(() => props.path?.split('/').pop() || '')
const fileUrl = computed(() => {
  if (!props.path) return null
  const base = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api').replace(/\/api\/?$/, '')
  return `${base}/storage/${props.path}`
})
const isImage = computed(() => /\.(jpg|jpeg|png|gif|webp)$/i.test(fileName.value))
</script>

<template>
  <div v-if="path && fileUrl" class="ua-view">
    <div class="ua-file">
      <div class="ua-file-info">
        <FileText :size="18" class="ua-file-icon" />
        <span class="ua-file-name">{{ fileName }}</span>
      </div>
      <div class="ua-file-actions">
        <button class="ua-btn-icon" title="Preview" @click="showPreview = true">
          <Eye :size="14" />
        </button>
        <a :href="fileUrl" :download="fileName" class="ua-btn-icon" title="Download">
          <Download :size="14" />
        </a>
      </div>
    </div>

    <Modal :show="showPreview" :title="fileName" size="lg" @close="showPreview = false">
      <div class="ua-preview-container">
        <img v-if="isImage" :src="fileUrl" :alt="fileName" class="ua-preview-image" />
        <div v-else class="ua-preview-frame-wrap">
          <iframe :src="fileUrl" class="ua-preview-iframe" frameborder="0" />
        </div>
      </div>
      <template #footer>
        <a :href="fileUrl" :download="fileName" class="ua-download-btn">
          <Download :size="14" />
          Download
        </a>
      </template>
    </Modal>
  </div>
</template>

<style scoped>
.ua-file {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 14px;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 8px;
}
.ua-file-info {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}
.ua-file-icon {
  color: #0369a1;
  flex-shrink: 0;
}
.ua-file-name {
  font-size: 13px;
  font-weight: 500;
  color: #1e293b;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.ua-file-actions {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}
.ua-btn-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: white;
  color: #64748b;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.15s;
}
.ua-btn-icon:hover {
  background: #eff6ff;
  border-color: #bfdbfe;
  color: #2563eb;
}
.ua-preview-container {
  width: 100%;
  min-height: 400px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
  border-radius: 8px;
  overflow: hidden;
}
.ua-preview-image {
  max-width: 100%;
  max-height: 70vh;
  object-fit: contain;
  border-radius: 4px;
}
.ua-preview-frame-wrap {
  width: 100%;
  height: 70vh;
  overflow: hidden;
  border-radius: 4px;
}
.ua-preview-iframe {
  width: 300%;
  height: 100%;
  margin-left: -100%;
  border: none;
}
.ua-download-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  background: #2563eb;
  color: white;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  transition: background 0.15s;
}
.ua-download-btn:hover {
  background: #1d4ed8;
}
</style>
