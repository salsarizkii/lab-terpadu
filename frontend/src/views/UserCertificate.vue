<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import SkeletonTable from '../components/common/SkeletonTable.vue'
import { beritaAcaraApi } from '../api/beritaAcara'
import { certificateApi } from '../api/certificate'
import { useAuthStore } from '../stores/authStore'
import type { BeritaAcara } from '../types'
import { toast } from 'vue-sonner'
import { formatDateTime } from '../utils/date'
import { Eye, Pencil, Download, Upload, FileText } from '@lucide/vue'

const auth = useAuthStore()

const all = ref<BeritaAcara[]>([])
const loading = ref(true)

const canUpload = computed(() => auth.hasPermission('certificate_upload'))

function isEligible(item: BeritaAcara): boolean {
  return item.status === 'completed' && !!item.payment && item.payment.status === 'approved'
}

const items = computed(() => all.value.filter(isEligible))

async function load() {
  loading.value = true
  try {
    all.value = await beritaAcaraApi.getList()
  } catch {
    toast.error('Gagal memuat data')
  } finally {
    loading.value = false
  }
}

onMounted(load)

// ---- Modal sertifikat ----
const showModal = ref(false)
const selectedItem = ref<BeritaAcara | null>(null)
const mode = ref<'view' | 'upload'>('view')
const selectedFile = ref<File | null>(null)
const objectUrl = ref<string | null>(null)
const busy = ref(false)

const storageBase = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api').replace(/\/api\/?$/, '')

const uploadedPath = computed(() => selectedItem.value?.certificate?.file_path || null)
const uploadedUrl = computed<string | undefined>(() => uploadedPath.value ? `${storageBase}/storage/${uploadedPath.value}` : undefined)
const uploadedFileName = computed(() => uploadedPath.value?.split('/').pop() || '')
const uploadedIsImage = computed(() => /\.(jpg|jpeg|png|gif|webp)$/i.test(uploadedFileName.value))

const selectedFileName = computed(() => selectedFile.value?.name || '')
const selectedFileSize = computed(() => selectedFile.value ? `${(selectedFile.value.size / 1024).toFixed(1)} KB` : '')
const selectedIsImage = computed(() => /\.(jpg|jpeg|png|gif|webp)$/i.test(selectedFileName.value))

watch(selectedFile, (file) => {
  if (objectUrl.value) {
    URL.revokeObjectURL(objectUrl.value)
    objectUrl.value = null
  }
  if (file) {
    objectUrl.value = URL.createObjectURL(file)
  }
})

function openUpload(item: BeritaAcara) {
  selectedItem.value = item
  mode.value = 'upload'
  selectedFile.value = null
  showModal.value = true
}

function openView(item: BeritaAcara) {
  selectedItem.value = item
  mode.value = 'view'
  selectedFile.value = null
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  selectedItem.value = null
  selectedFile.value = null
}

function onFileChange(e: Event) {
  const input = e.target as HTMLInputElement
  selectedFile.value = input.files?.[0] || null
  input.value = ''
}

const dragDepth = ref(0)
const dragOver = computed(() => dragDepth.value > 0)

function onDragEnter() {
  dragDepth.value++
}
function onDragOver() {
  // No-op — dibutuhkan agar drop diperbolehkan
}
function onDragLeave() {
  dragDepth.value = Math.max(0, dragDepth.value - 1)
}
function onDrop(e: DragEvent) {
  dragDepth.value = 0
  const file = e.dataTransfer?.files?.[0] || null
  if (!file) return
  if (!/\.(pdf|jpg|jpeg|png)$/i.test(file.name)) {
    toast.error('Format file tidak didukung. Gunakan PDF, JPG, atau PNG.')
    return
  }
  selectedFile.value = file
}

async function handleDownload(certificateId: number) {
  try {
    const blob = await certificateApi.download(certificateId)
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `sertifikat-${certificateId}.pdf`
    a.click()
    window.URL.revokeObjectURL(url)
  } catch {
    toast.error('Gagal mengunduh sertifikat')
  }
}

async function confirmUpload() {
  if (!selectedItem.value || !selectedFile.value) return
  busy.value = true
  try {
    await certificateApi.upload(selectedItem.value.id, selectedFile.value)
    toast.success('Sertifikat berhasil diupload')
    closeModal()
    await load()
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Gagal upload sertifikat')
  } finally {
    busy.value = false
  }
}

async function handleDelete() {
  if (!selectedItem.value) return
  if (!confirm(`Hapus sertifikat ${selectedItem.value.judul_penelitian || selectedItem.value.user?.name || ''}?`)) return
  busy.value = true
  try {
    await certificateApi.remove(selectedItem.value.id)
    toast.success('Sertifikat dihapus')
    closeModal()
    await load()
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Gagal menghapus sertifikat')
  } finally {
    busy.value = false
  }
}

function switchToUpload() {
  mode.value = 'upload'
  selectedFile.value = null
}
</script>

<template>
  <AppLayout>
    <div>
      <div class="page-header">
        <h1>Sertifikat</h1>
      </div>

      <Card>
        <SkeletonTable v-if="loading" :cols="5" :rows="4" />

        <div v-else class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Pemohon</th>
                <th>Judul Penelitian</th>
                <th>Status Sertifikat</th>
                <th>Selesai Dibayar</th>
                <th class="th-aksi">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in items" :key="item.id">
                <td>{{ item.user?.name || item.nama_pemohon || '-' }}</td>
                <td class="cell-judul">{{ item.judul_penelitian || '-' }}</td>
                <td>
                  <Badge
                    v-if="item.certificate"
                    type="success"
                    :text="item.certificate.downloaded_at ? 'Sudah diunduh' : 'Tersedia'"
                  />
                  <Badge v-else type="warning" text="Menunggu diupload" />
                </td>
                <td class="td-date">{{ formatDateTime(item.payment?.created_at || item.updated_at) }}</td>
                <td class="td-aksi">
                  <div class="action-cell">
                    <template v-if="item.certificate">
                      <button
                        v-if="canUpload"
                        class="icon-btn"
                        title="Ubah Sertifikat"
                        @click="openView(item)"
                      >
                        <Pencil :size="16" />
                      </button>
                      <button
                        v-else
                        class="icon-btn"
                        title="Lihat Sertifikat"
                        @click="openView(item)"
                      >
                        <Eye :size="16" />
                      </button>
                      <button
                        class="icon-btn icon-btn--accent"
                        title="Download Sertifikat"
                        @click="handleDownload(item.certificate.id)"
                      >
                        <Download :size="16" />
                      </button>
                    </template>
                    <button
                      v-else-if="canUpload"
                      class="icon-btn icon-btn--accent"
                      title="Upload Sertifikat"
                      @click="openUpload(item)"
                    >
                      <Upload :size="16" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="items.length === 0">
                <td colspan="5" class="empty-cell">Belum ada sertifikat tersedia</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <!-- Modal Sertifikat -->
      <Modal
        :show="showModal"
        :title="`Sertifikat — ${selectedItem?.judul_penelitian || selectedItem?.user?.name || ''}`"
        size="lg"
        @close="closeModal"
      >
        <template v-if="mode === 'view' && selectedItem?.certificate">
          <div class="cert-info-bar">
            <FileText :size="16" class="cert-file-icon" />
            <span class="cert-file-name">{{ uploadedFileName }}</span>
            <span class="cert-file-status">
              {{ selectedItem.certificate.downloaded_at ? 'Sudah diunduh' : 'Belum diunduh' }}
            </span>
          </div>
          <div class="preview-box">
            <img v-if="uploadedIsImage" :src="uploadedUrl" :alt="uploadedFileName" class="preview-image" />
            <iframe v-else-if="uploadedUrl" :src="uploadedUrl" class="preview-iframe" frameborder="0" />
            <p v-else class="preview-empty">File sertifikat tidak ditemukan.</p>
          </div>
        </template>

        <template v-else>
          <div
            class="dropzone"
            :class="{ 'dropzone--active': dragOver, 'dropzone--filled': selectedFile }"
            @dragenter.prevent="onDragEnter"
            @dragover.prevent="onDragOver"
            @dragleave.prevent="onDragLeave"
            @drop.prevent="onDrop"
          >
            <template v-if="!selectedFile">
              <div class="dropzone__icon">
                <Upload :size="26" />
              </div>
              <p class="dropzone__title">Upload Sertifikat</p>
              <p class="dropzone__sub">Seret &amp; letakkan file di sini, atau</p>
              <label class="dropzone__btn">
                Pilih File
                <input
                  type="file"
                  class="attach-input"
                  accept=".pdf,.jpg,.jpeg,.png"
                  @change="onFileChange"
                />
              </label>
              <span class="dropzone__hint">PDF, JPG, PNG — maks 10MB</span>
            </template>

            <div v-else class="dropzone-filled">
              <div class="dropzone-filled__icon">
                <FileText :size="16" />
              </div>
              <div class="dropzone-filled__meta">
                <span class="dropzone-filled__name">{{ selectedFileName }}</span>
                <span class="dropzone-filled__size">{{ selectedFileSize }}</span>
              </div>
              <label class="dropzone-filled__btn">
                Ganti File
                <input
                  type="file"
                  class="attach-input"
                  accept=".pdf,.jpg,.jpeg,.png"
                  @change="onFileChange"
                />
              </label>
            </div>
          </div>

          <div v-if="selectedFile" class="preview-box preview-box--highlight">
            <img v-if="selectedIsImage" :src="objectUrl || undefined" :alt="selectedFileName" class="preview-image" />
            <iframe v-else-if="objectUrl" :src="objectUrl" class="preview-iframe" frameborder="0" />
          </div>
          <div v-else class="preview-placeholder">
            <FileText :size="28" />
            <span>Pratinjau file akan muncul di sini</span>
          </div>
        </template>

        <template #footer>
          <template v-if="mode === 'view' && selectedItem?.certificate">
            <Button variant="secondary" size="sm" @click="handleDownload(selectedItem.certificate.id)">
              <Download :size="14" />
              Download
            </Button>
            <Button v-if="canUpload" variant="outline" size="sm" @click="switchToUpload">
              Ganti
            </Button>
            <Button v-if="canUpload" variant="danger" size="sm" :disabled="busy" @click="handleDelete">
              Hapus
            </Button>
            <Button variant="outline" size="sm" @click="closeModal">Tutup</Button>
          </template>
          <template v-else>
            <Button variant="secondary" size="sm" @click="closeModal">Batal</Button>
            <Button size="sm" :disabled="!selectedFile || busy" :loading="busy" @click="confirmUpload">
              Konfirmasi Upload
            </Button>
          </template>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-header { margin-bottom: 24px; }
.page-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }

.table-wrap { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; font-size: 14px; }
.table th { text-align: left; padding: 14px 12px; background: #f8fafc; color: #475569; font-weight: 600; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.table td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.th-aksi, .td-aksi { width: 160px; }
.empty-cell { text-align: center; color: #94a3b8; padding: 40px; }
.cell-judul { max-width: 260px; white-space: normal; word-break: break-word; }
.td-date { white-space: nowrap; font-size: 13px; color: #64748b; }

.action-cell { display: flex; align-items: center; gap: 8px; }

.icon-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s;
}
.icon-btn:hover { background: #eff6ff; border-color: #bfdbfe; color: #2563eb; }
.icon-btn--accent {
  border-color: #3b82f6;
  background: #eff6ff;
  color: #2563eb;
}
.icon-btn--accent:hover {
  background: #2563eb;
  border-color: #2563eb;
  color: #fff;
}

/* Modal */
.cert-info-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  margin-bottom: 12px;
}
.cert-file-icon { color: #0369a1; flex-shrink: 0; }
.cert-file-name { font-size: 13px; font-weight: 500; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cert-file-status { margin-left: auto; font-size: 11px; color: #16a34a; white-space: nowrap; }
.cert-file-size { font-size: 11px; color: #94a3b8; white-space: nowrap; }

.attach-input { display: none; }
.attach-hint { font-size: 11px; color: #94a3b8; }

.dropzone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 36px 24px;
  border: 2px dashed #cbd5e1;
  border-radius: 14px;
  background: linear-gradient(180deg, #f8fafc 0%, #eff6ff 100%);
  text-align: center;
  transition: all 0.2s ease;
}
.dropzone--active {
  border-color: #3b82f6;
  background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
}
.dropzone--filled {
  display: block;
  padding: 12px 14px;
  border: 1px solid #86efac;
  background: linear-gradient(180deg, #f9fefb 0%, #f0fdf4 100%);
  border-radius: 10px;
}

.dropzone-filled {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}
.dropzone-filled__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  background: #dcfce7;
  color: #16a34a;
  flex-shrink: 0;
}
.dropzone-filled__meta {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
  flex: 1;
}
.dropzone-filled__name {
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.dropzone-filled__size {
  font-size: 11px;
  color: #94a3b8;
}
.dropzone-filled__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  padding: 6px 14px;
  border: 1px solid #16a34a;
  border-radius: 8px;
  background: #fff;
  color: #15803d;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}
.dropzone-filled__btn:hover {
  background: #16a34a;
  color: #fff;
}

.dropzone__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: #fff;
  box-shadow: 0 8px 18px rgba(37, 99, 235, 0.25);
  margin-bottom: 8px;
}
.dropzone__title {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: #1e293b;
}
.dropzone__sub {
  margin: 0;
  font-size: 13px;
  color: #64748b;
}
.dropzone__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-top: 8px;
  padding: 10px 22px;
  border: none;
  border-radius: 8px;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  letter-spacing: 0.3px;
  box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
  transition: all 0.2s ease;
}
.dropzone__btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
}
.dropzone__hint {
  margin-top: 6px;
  font-size: 11px;
  color: #94a3b8;
}

.preview-box {
  margin-top: 14px;
  width: 100%;
  height: 380px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06);
  overflow: hidden;
}
.preview-box--highlight {
  border: 2px solid #3b82f6;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.14), 0 10px 28px rgba(37, 99, 235, 0.16);
  animation: preview-pop 0.25s ease;
}
@keyframes preview-pop {
  from { opacity: 0.4; transform: scale(0.985); }
  to { opacity: 1; transform: scale(1); }
}
.preview-image { max-width: 100%; max-height: 100%; object-fit: contain; }
.preview-iframe { width: 100%; height: 100%; border: none; border-radius: 12px; }
.preview-empty, .preview-hint { margin: 0; padding: 24px; text-align: center; color: #94a3b8; font-size: 13px; }

.preview-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 14px;
  height: 140px;
  border: 2px dashed #e2e8f0;
  border-radius: 12px;
  color: #94a3b8;
  font-size: 13px;
}
</style>