<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { FileText, Save, Eye, Upload, Copy, Check, Sliders, Layers, Sparkles } from '@lucide/vue'
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

const docTypes = [
  { type: 'berita_acara', label: 'Berita Acara', desc: 'Form pengajuan penelitian & pengujian' },
  { type: 'rekap_analis', label: 'Rekap Analisis', desc: 'Lembar hasil pengujian tim analis' },
  { type: 'tagihan', label: 'Tagihan Pembayaran', desc: 'Nota & rincian kwitansi biaya' },
  { type: 'sertifikat', label: 'Sertifikat (LHU)', desc: 'Laporan Hasil Uji (TTD basah)' },
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
  toast.info(`Variabel ${key} ditambahkan ke akhir template`)
}

function getPreviewSrc() {
  if (!template.value) return ''
  const token = localStorage.getItem('api_token') || ''
  const baseURL = api.defaults.baseURL || '/api'
  return `${baseURL}/admin/pdf-templates/${activeType.value}/preview?token=${token}&t=${Date.now()}`
}

async function triggerPreviewModal() {
  if (template.value) {
    await handleSave()
  }
  previewModalOpen.value = true
}
</script>

<template>
  <div class="pdf-template-editor">
    <!-- Header Document Type Selector -->
    <div class="doc-type-tabs mb-6 flex flex-wrap gap-3">
      <button
        v-for="doc in docTypes"
        :key="doc.type"
        @click="handleTypeChange(doc.type)"
        class="type-tab-btn flex items-center gap-3 px-4 py-3 rounded-xl border transition-all text-left"
        :class="activeType === doc.type ? 'bg-primary-50 border-primary-500 text-primary-700 shadow-sm font-medium' : 'bg-white border-slate-200 hover:border-slate-300 text-slate-700'"
      >
        <div class="p-2 rounded-lg" :class="activeType === doc.type ? 'bg-primary-100 text-primary-600' : 'bg-slate-100 text-slate-500'">
          <FileText class="w-5 h-5" />
        </div>
        <div>
          <div class="font-semibold text-sm">{{ doc.label }}</div>
          <div class="text-xs text-slate-500 mt-0.5">{{ doc.desc }}</div>
        </div>
      </button>
    </div>

    <div v-if="loading" class="p-12 text-center text-slate-500 bg-white rounded-2xl border border-slate-200">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent mb-3"></div>
      <p>Memuat template PDF...</p>
    </div>

    <div v-else-if="template" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Content Editor (Col 2) -->
      <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <div class="flex flex-wrap items-center justify-between gap-4 mb-4 pb-4 border-b border-slate-100">
            <div>
              <h3 class="text-base font-semibold text-slate-900 flex items-center gap-2">
                <FileText class="w-5 h-5 text-primary-600" />
                Editor Template HTML ({{ template.name }})
              </h3>
              <p class="text-xs text-slate-500 mt-1">Ubah isi dokumen, tambahkan teks, logo, atau variabel dinamis <code>&#123;&#123; nama_variabel &#125;&#125;</code></p>
            </div>

            <!-- Import Docx & Actions -->
            <div class="flex items-center gap-2">
              <label class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg border border-slate-300 transition-colors">
                <Upload class="w-4 h-4 text-slate-600" />
                <span>{{ importing ? 'Mengimpor...' : 'Import Word (.docx)' }}</span>
                <input type="file" accept=".docx" class="hidden" @change="handleImportDocx" :disabled="importing" />
              </label>

              <Button variant="secondary" size="sm" @click="triggerPreviewModal">
                <Eye class="w-4 h-4 mr-1.5" />
                Live Preview
              </Button>

              <Button variant="primary" size="sm" @click="handleSave" :loading="saving">
                <Save class="w-4 h-4 mr-1.5" />
                Simpan
              </Button>
            </div>
          </div>

          <!-- HTML Source Editor -->
          <div class="space-y-2">
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Isi HTML Template Document</label>
            <textarea
              v-model="template.html_content"
              rows="20"
              class="w-full font-mono text-xs p-4 rounded-xl border border-slate-300 bg-slate-900 text-slate-100 focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-y"
              placeholder="Masukkan HTML/CSS template dokumen di sini..."
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Settings & Variables Sidebar (Col 1) -->
      <div class="space-y-6">
        <!-- Paper Settings -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <h3 class="text-sm font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <Sliders class="w-4 h-4 text-primary-600" />
            Pengaturan Kertas & Layout
          </h3>

          <div class="space-y-4 text-xs">
            <div>
              <label class="block font-medium text-slate-700 mb-1">Ukuran Kertas</label>
              <select v-model="template.paper_size" class="w-full rounded-lg border-slate-300 text-xs p-2.5 bg-slate-50 border">
                <option v-for="size in paperSizes" :key="size.value" :value="size.value">{{ size.label }}</option>
              </select>
            </div>

            <div>
              <label class="block font-medium text-slate-700 mb-1">Orientasi</label>
              <select v-model="template.orientation" class="w-full rounded-lg border-slate-300 text-xs p-2.5 bg-slate-50 border">
                <option v-for="ori in orientations" :key="ori.value" :value="ori.value">{{ ori.label }}</option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2">
              <div>
                <label class="block font-medium text-slate-700 mb-1">Margin Atas (mm)</label>
                <input v-model.number="template.margin_top" type="number" class="w-full rounded-lg border-slate-300 text-xs p-2 border" min="0" max="100" />
              </div>
              <div>
                <label class="block font-medium text-slate-700 mb-1">Margin Bawah (mm)</label>
                <input v-model.number="template.margin_bottom" type="number" class="w-full rounded-lg border-slate-300 text-xs p-2 border" min="0" max="100" />
              </div>
              <div>
                <label class="block font-medium text-slate-700 mb-1">Margin Kiri (mm)</label>
                <input v-model.number="template.margin_left" type="number" class="w-full rounded-lg border-slate-300 text-xs p-2 border" min="0" max="100" />
              </div>
              <div>
                <label class="block font-medium text-slate-700 mb-1">Margin Kanan (mm)</label>
                <input v-model.number="template.margin_right" type="number" class="w-full rounded-lg border-slate-300 text-xs p-2 border" min="0" max="100" />
              </div>
            </div>
          </div>
        </div>

        <!-- Dynamic Variables Palette -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
              <Sparkles class="w-4 h-4 text-amber-500" />
              Variabel Dinamis Dokumen
            </h3>
          </div>
          <p class="text-xs text-slate-500 mb-4">Klik tag variabel untuk menyalin atau memasukkannya ke template HTML:</p>

          <div class="space-y-2 max-h-[380px] overflow-y-auto pr-1">
            <div
              v-for="v in variables"
              :key="v.key"
              class="p-2.5 rounded-xl border border-slate-100 bg-slate-50/70 hover:bg-slate-100/80 transition-colors text-xs group"
            >
              <div class="flex items-center justify-between">
                <code class="font-mono font-semibold text-primary-700 bg-primary-50 px-2 py-0.5 rounded text-[11px]">
                  {{ v.key }}
                </code>
                <div class="flex items-center gap-1 opacity-80 group-hover:opacity-100">
                  <button
                    @click="insertVariableToEditor(v.key)"
                    class="p-1 text-slate-600 hover:text-primary-600 rounded hover:bg-white"
                    title="Insert ke editor"
                  >
                    + Insert
                  </button>
                  <button
                    @click="copyVariable(v.key)"
                    class="p-1 text-slate-500 hover:text-slate-800 rounded hover:bg-white"
                    title="Salin variabel"
                  >
                    <Check v-if="copiedKey === v.key" class="w-3.5 h-3.5 text-emerald-600" />
                    <Copy v-else class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
              <div class="text-[11px] font-medium text-slate-700 mt-1">{{ v.label }}</div>
              <div class="text-[10px] text-slate-400 mt-0.5">Contoh: {{ v.example }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Live Preview Modal -->
    <div v-if="previewModalOpen" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl h-[85vh] flex flex-col overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
          <div class="flex items-center gap-2">
            <Eye class="w-5 h-5 text-primary-600" />
            <h3 class="font-semibold text-slate-900 text-sm">Preview PDF: {{ template?.name }}</h3>
          </div>
          <button @click="previewModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold px-2">
            &times;
          </button>
        </div>
        <div class="flex-1 bg-slate-200 p-2">
          <iframe :src="getPreviewSrc()" class="w-full h-full rounded-lg shadow border-0"></iframe>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pdf-template-editor {
  width: 100%;
}
</style>
