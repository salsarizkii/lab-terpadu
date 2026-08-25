<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed, watch, nextTick } from 'vue'
import { FileText, Save, Eye, CloudUpload, FileCode, PenLine, RotateCcw, FileDown, BookOpen, RefreshCw } from '@lucide/vue'
import Button from './Button.vue'
import { pdfTemplateApi } from '../../api/pdfTemplate'
import type { PdfTemplate, PdfVariableGroup } from '../../types'
import { toast } from 'vue-sonner'
import { renderAsync } from 'docx-preview'

const activeType = ref<string>('berita_acara')
const activeTab = ref<string>('upload')
const template = ref<PdfTemplate | null>(null)
const variableGroups = ref<PdfVariableGroup[]>([])
const loading = ref(false)
const saving = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const resetting = ref(false)
const downloading = ref(false)
const previewUrl = ref<string | null>(null)
const previewMode = ref<'docx' | 'pdf' | null>(null)
const previewContainer = ref<HTMLElement | null>(null)
const previewLoading = ref(false)
const variableSearch = ref('')
const copiedKey = ref<string | null>(null)

const docTypes = [
  { type: 'berita_acara', label: 'Berita Acara', desc: 'Form pengajuan & data pemohon', color: 'blue' },
  { type: 'rekap_analis', label: 'Rekap Analisis', desc: 'Hasil pengujian & lembar analis', color: 'green' },
  { type: 'tagihan', label: 'Tagihan Pembayaran', desc: 'Nota, subtotal & rincian biaya', color: 'orange' },
  { type: 'sertifikat', label: 'Sertifikat (LHU)', desc: 'Laporan Hasil Uji (TTD basah)', color: 'purple' },
]

const tabs = [
  { id: 'upload', label: 'Upload Template Word', icon: CloudUpload },
  { id: 'preview', label: 'Preview', icon: Eye },
  { id: 'notes', label: 'Catatan Tambahan', icon: PenLine },
]

const filteredGroups = computed(() => {
  if (!variableSearch.value) return variableGroups.value
  const q = variableSearch.value.toLowerCase()
  return variableGroups.value
    .map(g => ({
      ...g,
      variables: g.variables.filter(v => v.key.toLowerCase().includes(q) || v.label.toLowerCase().includes(q)),
    }))
    .filter(g => g.variables.length > 0)
})

onMounted(() => {
  loadTemplate(activeType.value)
})

onBeforeUnmount(() => {
  clearPreview()
})

watch(activeTab, (tab) => {
  if (tab === 'preview' && template.value) {
    refreshPreview()
  }
})

function handleTypeChange(type: string) {
  activeType.value = type
  activeTab.value = 'upload'
  clearPreview()
  loadTemplate(type)
}

function clearPreview() {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = null
  }
  previewMode.value = null
  if (previewContainer.value) previewContainer.value.innerHTML = ''
}

async function loadTemplate(type: string) {
  loading.value = true
  try {
    const res = await pdfTemplateApi.getByType(type)
    template.value = {
      ...res.data,
      catatan: res.data.catatan ?? '',
    }
    variableGroups.value = res.available_variables || []
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Gagal memuat template PDF')
  } finally {
    loading.value = false
  }
}

function buildPreviewPayload(): Partial<PdfTemplate> {
  const t = template.value!
  return {
    html_content: t.html_content,
    catatan: t.catatan,
  }
}

async function refreshPreview() {
  if (!template.value) return
  previewLoading.value = true
  try {
    // Template punya file Word → render .docx asli (persis Word, tanpa LibreOffice).
    if (template.value.docx_path) {
      const blob = await pdfTemplateApi.previewDocx(activeType.value, buildPreviewPayload())
      clearPreview()
      previewMode.value = 'docx'
      await renderDocx(blob)
      return
    }

    // Fallback: pratinjau PDF dari rendering HTML (template tanpa file Word).
    previewMode.value = 'pdf'
    const blob = await pdfTemplateApi.preview(activeType.value, buildPreviewPayload())
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = URL.createObjectURL(blob)
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Gagal memuat pratinjau')
  } finally {
    previewLoading.value = false
  }
}

async function renderDocx(blob: Blob) {
  await nextTick()
  if (!previewContainer.value) return
  previewContainer.value.innerHTML = ''
  await renderAsync(blob, previewContainer.value, undefined, {
    inWrapper: true,
    breakPages: true,
  })
}

async function handleSave() {
  if (!template.value) return
  saving.value = true
  try {
    const payload: any = {
      html_content: template.value.html_content,
      is_active: template.value.is_active,
      catatan: template.value.catatan,
    }
    await pdfTemplateApi.update(activeType.value, payload)
    toast.success('Template PDF berhasil disimpan!')
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Gagal menyimpan template PDF')
  } finally {
    saving.value = false
  }
}

async function handleReset() {
  if (!confirm('Kembalikan template ini ke versi awal? Semua perubahan pada template ini akan hilang.')) return
  resetting.value = true
  try {
    await pdfTemplateApi.reset(activeType.value)
    toast.success('Template dikembalikan ke versi awal')
    await loadTemplate(activeType.value)
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Gagal mengembalikan template')
  } finally {
    resetting.value = false
  }
}

async function handleDownloadDocx() {
  downloading.value = true
  try {
    const blob = await pdfTemplateApi.downloadDocx(activeType.value)
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `template_${activeType.value}.docx`
    a.click()
    URL.revokeObjectURL(url)
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Gagal mengunduh template Word')
  } finally {
    downloading.value = false
  }
}

async function handleUploadDocx(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  uploading.value = true
  uploadProgress.value = 0
  try {
    const res = await pdfTemplateApi.importDocx(
      activeType.value,
      file,
      (percent) => (uploadProgress.value = percent),
    )
    if (template.value) {
      template.value = { ...template.value, ...res.data, html_content: res.html_content }
      uploadProgress.value = 100
      toast.success('Template Word berhasil diunggah!')
      clearPreview()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Gagal mengunggah file Word')
  } finally {
    setTimeout(() => {
      uploading.value = false
      uploadProgress.value = 0
    }, 500)
    if (input) input.value = ''
  }
}

function handleDropUpload(e: DragEvent) {
  e.preventDefault()
  const file = e.dataTransfer?.files?.[0]
  if (!file) return
  const input = document.createElement('input')
  input.type = 'file'
  const dt = new DataTransfer()
  dt.items.add(file)
  input.files = dt.files
  handleUploadDocx({ target: input } as any)
}

function cleanVarKey(key: string): string {
  return key.replace(/\{\{\s*|\s*\}\}/g, '')
}

function copyVariableKey(key: string) {
  navigator.clipboard.writeText('<<' + cleanVarKey(key) + '>>')
  copiedKey.value = key
  setTimeout(() => (copiedKey.value = null), 1500)
}
</script>

<template>
  <div class="pte-root">
    <!-- Type Selector -->
    <div class="pte-type-selector">
      <button
        v-for="dt in docTypes"
        :key="dt.type"
        :class="['pte-type-btn', `pte-type-btn--${dt.color}`, { 'pte-type-btn--active': activeType === dt.type }]"
        @click="handleTypeChange(dt.type)"
      >
        <FileText :size="18" />
        <span class="pte-type-label">{{ dt.label }}</span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="pte-loading">Memuat template...</div>

    <template v-else-if="template">
      <!-- Tabs -->
      <div class="pte-tabs">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          :class="['pte-tab', { 'pte-tab--active': activeTab === tab.id }]"
          @click="activeTab = tab.id"
        >
          <component :is="tab.icon" :size="16" />
          <span>{{ tab.label }}</span>
        </button>
      </div>

      <!-- Tab 1: Upload Word -->
      <div v-if="activeTab === 'upload'" class="pte-panel">
        <div class="pte-upload-section">
          <h3 class="pte-section-title">
            <CloudUpload :size="18" /> Upload File Template Word (.docx)
          </h3>
          <p class="pte-section-desc">
            Unggah file Word yang menjadi sumber dokumen: hasilnya <strong>sama persis dengan Word</strong>,
            hanya tag <code>&lt;&lt;nama_variabel&gt;&gt;</code> yang diganti data. Tidak ada konversi HTML.
          </p>

          <label
            class="pte-dropzone"
            @dragover.prevent
            @drop.prevent="handleDropUpload"
          >
            <input type="file" accept=".docx" class="pte-hidden-input" @change="handleUploadDocx" />
            <CloudUpload :size="36" class="pte-dropzone-icon" />
            <div class="pte-dropzone-text">
              <strong>Klik untuk pilih file</strong> atau tarik & lepas di sini
            </div>
            <span class="pte-dropzone-hint">Format .docx, maks 10 MB</span>
          </label>

          <div v-if="uploading" class="pte-upload-progress">
            <div class="pte-progress-track">
              <div class="pte-progress-bar" :style="{ width: uploadProgress + '%' }"></div>
            </div>
            <span class="pte-progress-label">
              {{ uploadProgress >= 100 ? 'Memproses template...' : `Mengunggah... ${uploadProgress}%` }}
            </span>
          </div>

          <div v-if="template.docx_path" class="pte-file-info">
            <FileText :size="16" />
            <span>{{ template.docx_path.split('/').pop() || template.docx_path }}</span>
            <span class="pte-file-ok">✓ Terunggah</span>
          </div>

          <div class="pte-download-row">
            <Button variant="outline" size="sm" @click="handleDownloadDocx" :loading="downloading">
              <FileDown :size="16" /> Unduh Template Word
            </Button>
            <span class="pte-download-hint">
              Download template untuk diedit di Word, lalu upload lagi.
            </span>
          </div>
        </div>

        <!-- Panduan -->
        <div class="pte-guide">
          <h3 class="pte-section-title">
            <BookOpen :size="18" /> Panduan Menggunakan Template Word
          </h3>
          <ol class="pte-guide-list">
            <li>
              Klik <strong>"Unduh Template Word"</strong> untuk mengambil file template.
            </li>
            <li>
              Buka file di <strong>Microsoft Word</strong>, lalu tulis
              <code>&lt;&lt;nama_variabel&gt;&gt;</code> di posisi yang diinginkan.
              <div class="pte-guide-example">
                Contoh: untuk menampilkan jawaban pertanyaan <em>Nama Pemohon</em>, ketik
                <code>&lt;&lt;nama_pemohon&gt;&gt;</code>. Saat PDF dibuat, tag ini akan diganti dengan jawaban user.
              </div>
            </li>
            <li>
              <strong>Simpan</strong> file Word, lalu <strong>upload</strong> kembali di atas.
            </li>
            <li>
              Buka tab <strong>"Preview"</strong> untuk melihat hasil dokumennya, lalu klik <strong>"Simpan Template"</strong>.
            </li>
          </ol>
        </div>

        <!-- Daftar Variabel -->
        <div class="pte-variables-section">
          <h3 class="pte-section-title">
            <FileCode :size="18" /> Daftar Variabel yang Didukung
          </h3>
          <p class="pte-section-desc">
            Semua pertanyaan pada form Berita Acara & Rekap Analis plus variabel pembayaran. Klik variabel untuk menyalin.
          </p>
          <input
            v-model="variableSearch"
            class="pte-search"
            placeholder="Cari variabel..."
          />
          <div v-for="g in filteredGroups" :key="g.group" class="pte-variable-group">
            <h4 class="pte-group-title">{{ g.group }} <span class="pte-group-count">{{ g.variables.length }}</span></h4>
            <div class="pte-variable-list">
              <button
                v-for="v in g.variables"
                :key="v.key"
                class="pte-variable-item"
                @click="copyVariableKey(v.key)"
              >
                <div class="pte-var-header">
                  <code class="pte-var-key" v-text="'<<' + cleanVarKey(v.key) + '>>'"></code>
                  <span class="pte-var-copy">{{ copiedKey === v.key ? '✓' : 'Salin' }}</span>
                </div>
                <span class="pte-var-label">{{ v.label }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab 2: Preview -->
      <div v-if="activeTab === 'preview'" class="pte-panel">
        <div class="pte-preview-header">
          <div class="pte-preview-title">
            <Eye :size="18" />
            <div>
              <h3 class="pte-section-title">Pratinjau Dokumen: {{ template.name }}</h3>
              <p class="pte-section-desc pte-preview-sub">
                Pratinjau dirender langsung dari file Word template (persis seperti Word, hanya nilai yang diganti).
                Gunakan data contoh. Jika template belum punya file Word, pratinjau memakai rendering HTML.
              </p>
            </div>
          </div>
          <div class="pte-preview-actions">
            <button class="pte-icon-btn" title="Segarkan pratinjau" @click="refreshPreview">
              <RefreshCw :size="14" :class="{ 'pte-spin': previewLoading }" />
            </button>
            <Button variant="outline" size="sm" @click="handleDownloadDocx" :loading="downloading">
              <FileDown :size="14" /> Unduh Template Word
            </Button>
          </div>
        </div>
        <div class="pte-preview-frame">
          <div v-if="previewMode === 'docx'" ref="previewContainer" class="pte-docx-container"></div>
          <iframe v-else-if="previewUrl" :src="previewUrl" class="pte-iframe-inline" title="Pratinjau PDF"></iframe>
          <div v-else class="pte-preview-empty">
            <Eye :size="32" />
            <p>Pratinjau akan muncul di sini...</p>
          </div>
          <div v-if="previewLoading" class="pte-preview-loading">
            <RefreshCw :size="16" class="pte-spin" /> Merender pratinjau dari template Word...
          </div>
        </div>
      </div>

      <!-- Tab 3: Catatan Tambahan -->
      <div v-if="activeTab === 'notes'" class="pte-panel">
        <h3 class="pte-section-title"><PenLine :size="18" /> Catatan Tambahan</h3>
        <p class="pte-section-desc">Teks catatan yang akan muncul di bagian bawah dokumen PDF.</p>
        <textarea
          v-model="template.catatan"
          class="pte-textarea"
          rows="6"
          placeholder="Tulis catatan tambahan di sini..."
        ></textarea>
      </div>

      <!-- Actions -->
      <div class="pte-actions">
        <Button variant="outline" @click="handleReset" :loading="resetting" class="pte-action-btn">
          <RotateCcw :size="16" /> Kembalikan ke Template Awal
        </Button>
        <Button variant="primary" @click="handleSave" :loading="saving" class="pte-action-btn">
          <Save :size="16" /> Simpan Template
        </Button>
      </div>
    </template>
  </div>
</template>

<style scoped>
.pte-root { display: flex; flex-direction: column; gap: 16px; }

/* Type Selector */
.pte-type-selector { display: flex; gap: 8px; flex-wrap: wrap; }
.pte-type-btn {
  display: flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 8px; border: 1.5px solid #e2e8f0;
  background: #fff; color: #475569; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.15s;
}
.pte-type-btn:hover { border-color: #93c5fd; background: #eff6ff; }
.pte-type-btn--active { border-color: #2563eb; background: #dbeafe; color: #1d4ed8; font-weight: 600; }
.pte-type-label { white-space: nowrap; }

/* Loading */
.pte-loading { text-align: center; padding: 40px; color: #94a3b8; }

/* Tabs */
.pte-tabs { display: flex; gap: 4px; border-bottom: 2px solid #e2e8f0; padding-bottom: 0; flex-wrap: wrap; }
.pte-tab {
  display: flex; align-items: center; gap: 6px; padding: 10px 16px; border: none; background: transparent;
  color: #64748b; font-size: 13px; font-weight: 500; cursor: pointer; border-bottom: 2px solid transparent;
  margin-bottom: -2px; transition: all 0.15s;
}
.pte-tab:hover { color: #1e293b; }
.pte-tab--active { color: #2563eb; border-bottom-color: #2563eb; font-weight: 600; }

/* Panel */
.pte-panel { padding: 16px 0; }

/* Section */
.pte-section-title { display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 700; color: #0f172a; margin: 0 0 4px; }
.pte-section-desc { font-size: 13px; color: #64748b; margin: 0 0 12px; }
.pte-section-desc code { background: #f1f5f9; padding: 1px 5px; border-radius: 4px; font-size: 12px; }

/* Upload */
.pte-upload-section { margin-bottom: 24px; }
.pte-dropzone {
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;
  padding: 32px; border: 2px dashed #cbd5e1; border-radius: 12px; background: #f8fafc;
  cursor: pointer; transition: all 0.15s;
}
.pte-dropzone:hover { border-color: #93c5fd; background: #eff6ff; }
.pte-hidden-input { display: none; }
.pte-dropzone-icon { color: #94a3b8; }
.pte-dropzone-text { font-size: 14px; color: #475569; text-align: center; }
.pte-dropzone-hint { font-size: 12px; color: #94a3b8; }
.pte-file-info { display: flex; align-items: center; gap: 8px; margin-top: 10px; padding: 8px 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; font-size: 13px; color: #166534; }
.pte-file-ok { color: #16a34a; font-weight: 600; }
.pte-download-row { display: flex; align-items: center; gap: 10px; margin-top: 12px; }
.pte-download-hint { font-size: 12px; color: #94a3b8; }
.pte-upload-progress { margin-top: 14px; display: flex; flex-direction: column; gap: 6px; }
.pte-progress-track { width: 100%; height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
.pte-progress-bar { height: 100%; background: linear-gradient(90deg, #2563eb, #3b82f6); border-radius: 999px; transition: width 0.2s ease; }
.pte-progress-label { font-size: 12px; color: #475569; }

/* Guide */
.pte-guide {
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
  padding: 14px 18px; margin-bottom: 24px;
}
.pte-guide-list { margin: 8px 0 0; padding-left: 20px; display: flex; flex-direction: column; gap: 8px; }
.pte-guide-list li { font-size: 13px; color: #334155; line-height: 1.6; }
.pte-guide-list code { background: #e0e7ff; color: #3730a3; padding: 1px 6px; border-radius: 4px; font-size: 12px; font-weight: 600; }
.pte-guide-example {
  margin-top: 6px; padding: 8px 12px; background: #eef2ff; border-left: 3px solid #6366f1;
  border-radius: 6px; font-size: 12px; color: #4338ca;
}

/* Variables */
.pte-variables-section { border-top: 1px solid #e2e8f0; padding-top: 16px; }
.pte-search { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; margin-bottom: 14px; outline: none; box-sizing: border-box; }
.pte-search:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
.pte-variable-group { margin-bottom: 18px; }
.pte-group-title {
  display: flex; align-items: center; gap: 8px;
  font-size: 12px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.5px;
  margin: 0 0 8px;
}
.pte-group-count { background: #e2e8f0; color: #475569; border-radius: 999px; padding: 0 8px; font-size: 11px; }
.pte-variable-list { display: flex; flex-wrap: wrap; gap: 8px; }
.pte-variable-item {
  display: flex; flex-direction: column; gap: 2px; padding: 8px 12px; background: #f8fafc;
  border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.15s;
  min-width: 200px; flex: 1; max-width: 300px; text-align: left;
}
.pte-variable-item:hover { border-color: #93c5fd; background: #eff6ff; }
.pte-var-header { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
.pte-var-key { font-size: 12px; color: #2563eb; font-weight: 600; }
.pte-var-copy { font-size: 10px; color: #64748b; white-space: nowrap; }
.pte-var-label { font-size: 12px; color: #475569; }

/* Notes */
.pte-textarea {
  width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px;
  font-size: 13px; font-family: inherit; resize: vertical; outline: none; box-sizing: border-box;
}
.pte-textarea:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }

/* Actions */
.pte-actions { display: flex; gap: 10px; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid #e2e8f0; }
.pte-action-btn { display: flex; align-items: center; gap: 6px; }

/* Preview Tab */
.pte-preview-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
.pte-preview-title { display: flex; align-items: flex-start; gap: 10px; }
.pte-preview-sub { margin: 2px 0 0; }
.pte-preview-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.pte-icon-btn {
  display: flex; align-items: center; justify-content: center; width: 30px; height: 30px;
  border: 1px solid #d1d5db; border-radius: 8px; background: #fff; color: #475569; cursor: pointer;
  transition: all 0.15s;
}
.pte-icon-btn:hover { border-color: #93c5fd; color: #2563eb; }
.pte-preview-frame {
  position: relative; border: 1px solid #cbd5e1; border-radius: 12px; background: #fff;
  height: 70vh; min-height: 480px; overflow: hidden;
}
.pte-iframe-inline { width: 100%; height: 100%; border: none; }
.pte-docx-container {
  width: 100%;
  height: 100%;
  overflow: auto;
  background: #e5e7eb;
  padding: 16px;
  box-sizing: border-box;
  text-align: center;
}
.pte-docx-container :deep(.docx-wrapper) { text-align: left; display: inline-block; }
.pte-docx-container :deep(.docx-wrapper > section.docx) {
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.25);
  margin: 0 auto 16px;
}
.pte-preview-empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 10px; height: 100%; color: #94a3b8; font-size: 13px;
}
.pte-preview-loading {
  position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; gap: 8px;
  background: rgba(248,250,252,0.85); color: #475569; font-size: 13px; font-weight: 500;
}
.pte-spin { animation: pte-spin 0.8s linear infinite; }
@keyframes pte-spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .pte-actions { flex-direction: column-reverse; align-items: stretch; }
  .pte-preview-header { flex-direction: column; }
  .pte-preview-frame { height: 60vh; min-height: 400px; }
}
</style>
