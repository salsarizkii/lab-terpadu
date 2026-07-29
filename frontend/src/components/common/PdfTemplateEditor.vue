<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { FileText, Save, Eye, Upload, Copy, Check, Sliders, Sparkles, FileCode, Layers, RefreshCw } from '@lucide/vue'
import Button from './Button.vue'
import { pdfTemplateApi } from '../../api/pdfTemplate'
import type { PdfTemplate, PdfVariable } from '../../types'
import { toast } from 'vue-sonner'
import api from '../../api/axios'

const props = defineProps<{
  initialType?: string
}>()

const activeType = ref<string>(props.initialType || 'berita_acara')
const template = ref<PdfTemplate | null>(null)
const variables = ref<PdfVariable[]>([])
const loading = ref(false)
const saving = ref(false)
const importing = ref(false)
const copiedKey = ref<string | null>(null)
const previewModalOpen = ref(false)
const variableSearch = ref('')

const docTypes = [
  { type: 'berita_acara', label: 'Berita Acara', desc: 'Form pengajuan & data pemohon', icon: FileText, color: 'blue' },
  { type: 'rekap_analis', label: 'Rekap Analisis', desc: 'Hasil pengujian & lembar analis', icon: Layers, color: 'green' },
  { type: 'tagihan', label: 'Tagihan Pembayaran', desc: 'Nota, subtotal & rincian biaya', icon: FileCode, color: 'orange' },
  { type: 'sertifikat', label: 'Sertifikat (LHU)', desc: 'Laporan Hasil Uji (TTD basah)', icon: Sparkles, color: 'purple' },
]

const paperSizes = [
  { value: 'a4', label: 'A4 (210 x 297 mm)' },
  { value: 'letter', label: 'Letter (216 x 279 mm)' },
  { value: 'legal', label: 'Legal (216 x 356 mm)' },
]

const orientations = [
  { value: 'portrait', label: 'Portrait (Tegak)' },
  { value: 'landscape', label: 'Landscape (Mendatar)' },
]

onMounted(() => {
  loadTemplate(activeType.value)
})

watch(() => props.initialType, (newType) => {
  if (newType && newType !== activeType.value) {
    activeType.value = newType
    loadTemplate(newType)
  }
})

async function loadTemplate(type: string) {
  loading.value = true
  try {
    const res = await pdfTemplateApi.getByType(type)
    template.value = res.data
    variables.value = res.available_variables || []
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Gagal memuat template PDF')
  } finally {
    loading.value = false
  }
}

function handleTypeChange(type: string) {
  activeType.value = type
  loadTemplate(type)
}

async function handleSave() {
  if (!template.value) return
  saving.value = true
  try {
    await pdfTemplateApi.update(activeType.value, {
      html_content: template.value.html_content,
      paper_size: template.value.paper_size,
      orientation: template.value.orientation,
      margin_top: template.value.margin_top,
      margin_bottom: template.value.margin_bottom,
      margin_left: template.value.margin_left,
      margin_right: template.value.margin_right,
      is_active: template.value.is_active,
    })
    toast.success('Template PDF berhasil disimpan!')
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Gagal menyimpan template PDF')
  } finally {
    saving.value = false
  }
}

async function handleImportDocx(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  
  importing.value = true
  try {
    const res = await pdfTemplateApi.importDocx(activeType.value, file)
    if (template.value) {
      template.value.html_content = res.html_content
    }
    toast.success('Berhasil mengimpor file Word (.docx) ke template editor!')
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Gagal mengimpor file Word')
  } finally {
    importing.value = false
    input.value = ''
  }
}

function copyVariable(key: string) {
  navigator.clipboard.writeText(key)
  copiedKey.value = key
  toast.success(`Variabel ${key} berhasil disalin`)
  setTimeout(() => {
    copiedKey.value = null
  }, 2000)
}

function insertVariableToEditor(key: string) {
  if (!template.value) return
  template.value.html_content += ` ${key} `
  toast.info(`Variabel ${key} ditambahkan ke template`)
}

const previewBlobUrl = ref<string | null>(null)

async function triggerPreviewModal() {
  if (template.value) {
    await handleSave()
  }
  loading.value = true
  try {
    const res = await api.post(`/admin/pdf-templates/${activeType.value}/preview`, null, {
      responseType: 'blob',
    })
    if (previewBlobUrl.value) URL.revokeObjectURL(previewBlobUrl.value)
    previewBlobUrl.value = URL.createObjectURL(res.data)
    previewModalOpen.value = true
  } catch (err: any) {
    toast.error('Gagal memuat preview PDF')
  } finally {
    loading.value = false
  }
}

function closePreviewModal() {
  previewModalOpen.value = false
  if (previewBlobUrl.value) {
    URL.revokeObjectURL(previewBlobUrl.value)
    previewBlobUrl.value = null
  }
}

const filteredVariables = () => {
  if (!variableSearch.value) return variables.value
  const q = variableSearch.value.toLowerCase()
  return variables.value.filter(v => v.key.toLowerCase().includes(q) || v.label.toLowerCase().includes(q))
}
</script>

<template>
  <div class="pdf-editor-container">
    <!-- Top Selector Tabs -->
    <div class="pdf-tabs-grid">
      <button
        v-for="doc in docTypes"
        :key="doc.type"
        @click="handleTypeChange(doc.type)"
        class="pdf-tab-card"
        :class="{ 'pdf-tab-card--active': activeType === doc.type }"
      >
        <div class="pdf-tab-icon" :class="`pdf-tab-icon--${doc.color}`">
          <component :is="doc.icon" :size="22" />
        </div>
        <div class="pdf-tab-info">
          <span class="pdf-tab-title">{{ doc.label }}</span>
          <span class="pdf-tab-desc">{{ doc.desc }}</span>
        </div>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="pdf-loading-state">
      <div class="spinner"></div>
      <p>Memuat konfigurasi template PDF...</p>
    </div>

    <!-- Main Workspace Layout -->
    <div v-else-if="template" class="pdf-workspace">
      <!-- Left Main Editor Column -->
      <div class="pdf-editor-card">
        <div class="pdf-card-header">
          <div class="pdf-card-title-group">
            <FileCode :size="22" class="text-accent" />
            <div>
              <h3>HTML Template Editor</h3>
              <p>Sesuaikan struktur visual, kop surat, styling CSS, dan tag <code>&#123;&#123; variabel &#125;&#125;</code></p>
            </div>
          </div>

          <div class="pdf-card-actions">
            <!-- Import Word Button -->
            <label class="btn-import-word" :class="{ 'btn-import-word--loading': importing }">
              <Upload :size="15" />
              <span>{{ importing ? 'Mengimpor...' : 'Import Word (.docx)' }}</span>
              <input type="file" accept=".docx" class="hidden-file-input" @change="handleImportDocx" :disabled="importing" />
            </label>

            <!-- Live Preview Button -->
            <button class="btn-preview" @click="triggerPreviewModal">
              <Eye :size="15" />
              <span>Live Preview</span>
            </button>

            <!-- Save Button -->
            <button class="btn-save" @click="handleSave" :disabled="saving">
              <Save v-if="!saving" :size="15" />
              <RefreshCw v-else :size="15" class="spin-icon" />
              <span>{{ saving ? 'Menyimpan...' : 'Simpan Template' }}</span>
            </button>
          </div>
        </div>

        <div class="pdf-card-body">
          <div class="editor-textarea-wrapper">
            <div class="editor-code-header">
              <span class="code-badge">HTML / Blade Template</span>
              <span class="code-info">Gunakan sintaks HTML standar dengan inline CSS styling</span>
            </div>
            <textarea
              v-model="template.html_content"
              class="code-textarea"
              placeholder="Masukkan HTML/CSS template dokumen di sini..."
              spellcheck="false"
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Right Config & Variables Column -->
      <div class="pdf-sidebar">
        <!-- Paper Config Card -->
        <div class="pdf-sidebar-card">
          <div class="sidebar-card-header">
            <Sliders :size="18" class="text-accent" />
            <h4>Pengaturan Kertas & Layout</h4>
          </div>
          <div class="sidebar-card-body">
            <div class="form-group-sm">
              <label>Ukuran Kertas</label>
              <select v-model="template.paper_size" class="custom-select">
                <option v-for="size in paperSizes" :key="size.value" :value="size.value">{{ size.label }}</option>
              </select>
            </div>

            <div class="form-group-sm">
              <label>Orientasi Halaman</label>
              <div class="orientation-options">
                <button
                  v-for="ori in orientations"
                  :key="ori.value"
                  type="button"
                  class="ori-btn"
                  :class="{ 'ori-btn--active': template.orientation === ori.value }"
                  @click="template.orientation = ori.value as any"
                >
                  {{ ori.label }}
                </button>
              </div>
            </div>

            <div class="margin-grid">
              <div class="margin-input-group">
                <label>Margin Atas</label>
                <div class="input-with-unit">
                  <input v-model.number="template.margin_top" type="number" min="0" max="100" />
                  <span>mm</span>
                </div>
              </div>
              <div class="margin-input-group">
                <label>Margin Bawah</label>
                <div class="input-with-unit">
                  <input v-model.number="template.margin_bottom" type="number" min="0" max="100" />
                  <span>mm</span>
                </div>
              </div>
              <div class="margin-input-group">
                <label>Margin Kiri</label>
                <div class="input-with-unit">
                  <input v-model.number="template.margin_left" type="number" min="0" max="100" />
                  <span>mm</span>
                </div>
              </div>
              <div class="margin-input-group">
                <label>Margin Kanan</label>
                <div class="input-with-unit">
                  <input v-model.number="template.margin_right" type="number" min="0" max="100" />
                  <span>mm</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Dynamic Variables Palette Card -->
        <div class="pdf-sidebar-card">
          <div class="sidebar-card-header justify-between">
            <div class="flex-align-gap">
              <Sparkles :size="18" class="text-amber" />
              <h4>Variabel Dinamis</h4>
            </div>
            <span class="var-count-badge">{{ filteredVariables().length }} Variabel</span>
          </div>

          <div class="sidebar-card-body">
            <input
              v-model="variableSearch"
              type="text"
              placeholder="Cari variabel..."
              class="var-search-input"
            />

            <div class="variables-scroll-list">
              <div
                v-for="v in filteredVariables()"
                :key="v.key"
                class="var-item"
              >
                <div class="var-item-header">
                  <code class="var-key">{{ v.key }}</code>
                  <div class="var-actions">
                    <button class="var-btn-insert" @click="insertVariableToEditor(v.key)" title="Insert ke editor">
                      + Insert
                    </button>
                    <button class="var-btn-copy" @click="copyVariable(v.key)" title="Salin tag">
                      <Check v-if="copiedKey === v.key" :size="13" class="text-success" />
                      <Copy v-else :size="13" />
                    </button>
                  </div>
                </div>
                <div class="var-label">{{ v.label }}</div>
                <div class="var-example">Contoh: {{ v.example }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Live Preview Modal -->
    <div v-if="previewModalOpen" class="pdf-modal-backdrop" @click.self="closePreviewModal">
      <div class="pdf-modal-container">
        <div class="pdf-modal-header">
          <div class="pdf-modal-title">
            <Eye :size="20" class="text-accent" />
            <span>Pratinjau Hasil PDF: {{ template?.name }}</span>
          </div>
          <button class="pdf-modal-close" @click="closePreviewModal">&times;</button>
        </div>
        <div class="pdf-modal-body">
          <iframe v-if="previewBlobUrl" :src="previewBlobUrl" class="pdf-iframe-preview"></iframe>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pdf-editor-container {
  width: 100%;
  font-family: var(--font-primary, sans-serif);
}

/* Document Type Tabs */
.pdf-tabs-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 14px;
  margin-bottom: 24px;
}

.pdf-tab-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px;
  background: var(--color-surface, #ffffff);
  border: 1.5px solid var(--color-border, #e2e8f0);
  border-radius: var(--radius-lg, 16px);
  cursor: pointer;
  text-align: left;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: var(--shadow-xs, 0 1px 2px rgba(0,0,0,0.04));
}

.pdf-tab-card:hover {
  border-color: #cbd5e1;
  transform: translateY(-2px);
  box-shadow: var(--shadow-md, 0 4px 14px rgba(0,0,0,0.08));
}

.pdf-tab-card--active {
  border-color: var(--color-accent, #2563eb);
  background: #f8fafc;
  box-shadow: 0 4px 16px rgba(37, 99, 235, 0.12);
}

.pdf-tab-icon {
  width: 44px;
  height: 44px;
  border-radius: var(--radius-md, 12px);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.pdf-tab-icon--blue { background: #eff6ff; color: #2563eb; }
.pdf-tab-icon--green { background: #f0fdf4; color: #16a34a; }
.pdf-tab-icon--orange { background: #fff7ed; color: #ea580c; }
.pdf-tab-icon--purple { background: #faf5ff; color: #9333ea; }

.pdf-tab-info {
  display: flex;
  flex-direction: column;
}

.pdf-tab-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text, #0f172a);
}

.pdf-tab-desc {
  font-size: 12px;
  color: var(--color-text-secondary, #64748b);
  margin-top: 2px;
}

/* Loading State */
.pdf-loading-state {
  padding: 60px;
  text-align: center;
  background: #ffffff;
  border-radius: var(--radius-lg, 16px);
  border: 1px solid var(--color-border, #e2e8f0);
  color: #64748b;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #e2e8f0;
  border-top-color: var(--color-accent, #2563eb);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 12px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Workspace Grid */
.pdf-workspace {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 24px;
}

@media (max-width: 1024px) {
  .pdf-workspace {
    grid-template-columns: 1fr;
  }
}

/* Main Editor Card */
.pdf-editor-card {
  background: #ffffff;
  border-radius: var(--radius-lg, 16px);
  border: 1px solid var(--color-border, #e2e8f0);
  box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.06));
  overflow: hidden;
}

.pdf-card-header {
  padding: 18px 24px;
  border-bottom: 1px solid var(--color-border, #e2e8f0);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  background: #fafafa;
}

.pdf-card-title-group {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pdf-card-title-group h3 {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.pdf-card-title-group p {
  font-size: 12px;
  color: #64748b;
  margin: 2px 0 0 0;
}

.pdf-card-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Action Buttons */
.hidden-file-input {
  display: none;
}

.btn-import-word {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  background: #f1f5f9;
  color: #334155;
  border: 1px solid #cbd5e1;
  border-radius: var(--radius-sm, 8px);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-import-word:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.btn-preview {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
  border-radius: var(--radius-sm, 8px);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-preview:hover {
  background: #dbeafe;
}

.btn-save {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 18px;
  background: var(--color-accent, #2563eb);
  color: #ffffff;
  border: none;
  border-radius: var(--radius-sm, 8px);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
}

.btn-save:hover {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.btn-save:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spin-icon {
  animation: spin 0.8s linear infinite;
}

/* Editor Body & Textarea */
.pdf-card-body {
  padding: 20px;
}

.editor-textarea-wrapper {
  border: 1px solid #334155;
  border-radius: var(--radius-md, 12px);
  overflow: hidden;
  background: #0f172a;
}

.editor-code-header {
  padding: 10px 16px;
  background: #1e293b;
  border-bottom: 1px solid #334155;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.code-badge {
  font-size: 11px;
  font-weight: 700;
  color: #38bdf8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.code-info {
  font-size: 11px;
  color: #94a3b8;
}

.code-textarea {
  width: 100%;
  height: 520px;
  padding: 16px;
  background: #0f172a;
  color: #f8fafc;
  font-family: 'Fira Code', 'Consolas', 'Courier New', monospace;
  font-size: 12px;
  line-height: 1.6;
  border: none;
  outline: none;
  resize: vertical;
}

/* Sidebar Cards */
.pdf-sidebar {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.pdf-sidebar-card {
  background: #ffffff;
  border-radius: var(--radius-lg, 16px);
  border: 1px solid var(--color-border, #e2e8f0);
  box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.06));
  overflow: hidden;
}

.sidebar-card-header {
  padding: 14px 18px;
  border-bottom: 1px solid #f1f5f9;
  background: #fafafa;
  display: flex;
  align-items: center;
  gap: 8px;
}

.sidebar-card-header h4 {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.sidebar-card-body {
  padding: 18px;
}

.form-group-sm {
  margin-bottom: 14px;
}

.form-group-sm label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  margin-bottom: 6px;
}

.custom-select {
  width: 100%;
  padding: 9px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 12px;
  background: #ffffff;
  color: #0f172a;
  outline: none;
}

.orientation-options {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.ori-btn {
  padding: 8px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #ffffff;
  font-size: 11px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all 0.15s;
}

.ori-btn--active {
  background: #eff6ff;
  border-color: #2563eb;
  color: #2563eb;
}

.margin-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-top: 14px;
}

.margin-input-group label {
  display: block;
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 4px;
}

.input-with-unit {
  display: flex;
  align-items: center;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  overflow: hidden;
  background: #ffffff;
}

.input-with-unit input {
  width: 100%;
  padding: 6px 10px;
  border: none;
  font-size: 12px;
  outline: none;
  color: #0f172a;
}

.input-with-unit span {
  padding: 6px 8px;
  background: #f1f5f9;
  font-size: 10px;
  font-weight: 600;
  color: #64748b;
  border-left: 1px solid #e2e8f0;
}

/* Variable Palette */
.flex-align-gap {
  display: flex;
  align-items: center;
  gap: 8px;
}

.justify-between {
  justify-content: space-between;
}

.var-count-badge {
  font-size: 11px;
  font-weight: 700;
  background: #fef3c7;
  color: #b45309;
  padding: 2px 8px;
  border-radius: 12px;
}

.var-search-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 12px;
  margin-bottom: 12px;
  outline: none;
}

.variables-scroll-list {
  max-height: 380px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-right: 4px;
}

.var-item {
  padding: 10px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #f8fafc;
  transition: all 0.15s;
}

.var-item:hover {
  background: #ffffff;
  border-color: #cbd5e1;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.var-item-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.var-key {
  font-family: monospace;
  font-size: 11px;
  font-weight: 700;
  color: #2563eb;
  background: #eff6ff;
  padding: 2px 6px;
  border-radius: 4px;
}

.var-actions {
  display: flex;
  align-items: center;
  gap: 4px;
}

.var-btn-insert {
  font-size: 10px;
  font-weight: 700;
  color: #2563eb;
  background: #ffffff;
  border: 1px solid #bfdbfe;
  padding: 2px 6px;
  border-radius: 4px;
  cursor: pointer;
}

.var-btn-insert:hover {
  background: #2563eb;
  color: #ffffff;
}

.var-btn-copy {
  padding: 3px 5px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  color: #64748b;
  cursor: pointer;
}

.var-btn-copy:hover {
  color: #0f172a;
}

.var-label {
  font-size: 11px;
  font-weight: 600;
  color: #334155;
}

.var-example {
  font-size: 10px;
  color: #94a3b8;
  margin-top: 2px;
}

.text-accent { color: #2563eb; }
.text-amber { color: #d97706; }
.text-success { color: #16a34a; }

/* Modal Preview */
.pdf-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(15, 23, 42, 0.7);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.pdf-modal-container {
  background: #ffffff;
  border-radius: 20px;
  width: 100%;
  max-width: 1000px;
  height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
  overflow: hidden;
}

.pdf-modal-header {
  padding: 16px 24px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.pdf-modal-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.pdf-modal-close {
  background: transparent;
  border: none;
  font-size: 24px;
  font-weight: bold;
  color: #64748b;
  cursor: pointer;
}

.pdf-modal-close:hover {
  color: #0f172a;
}

.pdf-modal-body {
  flex: 1;
  background: #64748b;
  padding: 12px;
}

.pdf-iframe-preview {
  width: 100%;
  height: 100%;
  border: none;
  border-radius: 10px;
  background: #ffffff;
}
</style>
