<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import PageHeader from '../components/common/PageHeader.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import SkeletonDetail from '../components/common/SkeletonDetail.vue'
import DynamicForm from '../components/common/DynamicForm.vue'
import FormSelect from '../components/common/FormSelect.vue'
import { analystApi } from '../api/analyst'
import { formulaApi, type Formula } from '../api/formula'
import { formTemplateApi } from '../api/formTemplate'
import type { BeritaAcara, FormField } from '../types'
import { toast } from 'vue-sonner'
import { Upload, X, FileText, Eye, Download } from '@lucide/vue'
import api from '../api/axios'

const route = useRoute()
const router = useRouter()

const item = ref<BeritaAcara | null>(null)
const loading = ref(true)
const saving = ref(false)

const templateFields = ref<FormField[]>([])
const templateIdVal = ref<number | null>(null)
const loadingTemplate = ref(true)
const formValues = ref<Record<string, any>>({})
const dynamicFormRef = ref<InstanceType<typeof DynamicForm> | null>(null)
const biaya = ref<string>('')
const keterangan = ref('')

const uploadAksesFile = ref<File | null>(null)
const uploadingAkses = ref(false)

const formulas = ref<Formula[]>([])

const formulaOptions = computed(() => [
  { value: '-1', label: 'Isi Manual' },
  ...formulas.value.map(f => ({ value: String(f.id), label: `${f.name} (${f.unit || 'Rupiah'})` })),
])

function getFormulaSelectValue(row: FormulaRow): string {
  return String(row.formulaId ?? -1)
}

function onFormulaSelectValue(row: FormulaRow, val: string) {
  if (val === '-1') row.formulaId = -1
  else row.formulaId = Number(val)
  onFormulaSelect(row)
  autoCalculateAll()
}

interface FormulaRow {
  id: number
  formulaId: number | null
  formula: Formula | null
  calculating: boolean
  biaya: number | null
  resolvedValues: Record<string, number> | null
  tokens: Array<{ type: string; value: string; label?: string }>
  manualNote: string
}
let rowIdCounter = 0
const formulaRows = ref<FormulaRow[]>([])

function addFormulaRow() {
  formulaRows.value.push({
    id: ++rowIdCounter,
    formulaId: -1,
    formula: null,
    calculating: false,
    biaya: null,
    resolvedValues: null,
    tokens: [],
    manualNote: '',
  })
}
function removeFormulaRow(index: number) {
  formulaRows.value.splice(index, 1)
  autoCalculateAll()
}

function parseFormulaExpression(expr: string, vars: FormulaVariable[]) {
  const tokens: Array<{ type: string; value: string; label?: string }> = []
  let remaining = expr
  while (remaining.length > 0) {
    const varMatch = vars.find(v => remaining.startsWith(v.key))
    if (varMatch) {
      tokens.push({ type: 'var', value: varMatch.key, label: varMatch.label })
      remaining = remaining.slice(varMatch.key.length)
      continue
    }
    const funcMatch = remaining.match(/^(sqrt|abs|round|floor|ceil)\(/)
    if (funcMatch) {
      tokens.push({ type: 'func', value: funcMatch[1] })
      remaining = remaining.slice(funcMatch[1].length)
      continue
    }
    const numMatch = remaining.match(/^(\d+(\.\d+)?)/)
    if (numMatch) {
      tokens.push({ type: 'num', value: numMatch[1] })
      remaining = remaining.slice(numMatch[1].length)
      continue
    }
    const opMatch = remaining.match(/^([+\-*/^%]|>=|<=|!=|==|&&|\|\||>)/)
    if (opMatch) {
      const opMap: Record<string, string> = { '*': '×', '/': '÷', '-': '−', '^': '^', '>=': '≥', '<=': '≤', '==': '=', '!=': '≠', '&&': 'AND', '||': 'OR' }
      tokens.push({ type: 'op', value: opMatch[1], display: opMap[opMatch[1]] || opMatch[1] })
      remaining = remaining.slice(opMatch[1].length)
      continue
    }
    if (remaining[0] === '(' || remaining[0] === ')') {
      tokens.push({ type: 'paren', value: remaining[0] })
      remaining = remaining.slice(1)
      continue
    }
    remaining = remaining.slice(1)
  }
  return tokens
}

function onFormulaSelect(row: FormulaRow) {
  if (row.formulaId === -1) {
    row.formula = null
    row.tokens = []
    row.biaya = null
    row.resolvedValues = null
    row.manualNote = ''
    return
  }
  const f = formulas.value.find(x => x.id === row.formulaId)
  row.formula = f || null
  row.tokens = f ? parseFormulaExpression(f.expression, f.variables || []) : []
  row.biaya = null
  row.resolvedValues = null
  row.manualNote = ''
}

let calculateTimer: any = null

function autoCalculateAll() {
  if (calculateTimer) clearTimeout(calculateTimer)
  calculateTimer = setTimeout(async () => {
    const formulaOnly = formulaRows.value.filter(r => r.formulaId && r.formulaId !== -1)
    for (const row of formulaOnly) {
      await handleCalculateRowSilent(row)
    }
  }, 200)
}

async function handleCalculateRowSilent(row: FormulaRow) {
  if (!row.formulaId || !item.value || row.formulaId === -1) return
  row.calculating = true
  try {
    const result = await analystApi.calculateCost(item.value.id, {
      formula_id: row.formulaId,
      rekap_analis_data: JSON.stringify(formValues.value),
    })
    row.biaya = result.biaya
    row.resolvedValues = result.resolved_values
  } catch {
    // Silent catch during auto calculate
  } finally {
    row.calculating = false
  }
}

const totalFormulaBiaya = computed(() => formulaRows.value.reduce((s, r) => s + (r.biaya || 0), 0))
const totalBiaya = computed(() => totalFormulaBiaya.value)

const statusMap: Record<string, string> = {
  draft: 'Draft', pending: 'Diajukan', revision: 'Revisi', approved: 'Disetujui', rejected: 'Ditolak',
}
const statusTypeMap: Record<string, 'default' | 'warning' | 'success' | 'danger' | 'info'> = {
  draft: 'default', pending: 'warning', revision: 'warning', approved: 'success', rejected: 'danger',
}

const sampleStatusType: Record<string, 'default' | 'warning' | 'success' | 'danger' | 'info'> = {
  pending: 'default', processing: 'warning', ready: 'success', taken: 'info',
}
const sampleStatusText: Record<string, string> = {
  pending: 'Menunggu', processing: 'Proses', ready: 'Siap Diambil', taken: 'Sudah Dikirim',
}

function getSampleStatus(): string {
  const ba = item.value as any
  if (ba?.rekap_analis_data !== null && ba?.rekap_analis_data !== undefined) return 'taken'
  if (!ba?.samples || ba.samples.length === 0) return 'processing'
  if (ba.samples.every((s: any) => s.status === 'taken')) return 'taken'
  if (ba.samples.some((s: any) => s.status === 'processing')) return 'processing'
  return 'processing'
}

function getSampleBadgeType(): 'default' | 'warning' | 'success' | 'danger' | 'info' {
  return sampleStatusType[getSampleStatus()] || 'default'
}

function getSampleBadgeText(): string {
  return sampleStatusText[getSampleStatus()] || '-'
}

const hasExistingRekap = computed(() => {
  if (!item.value?.rekap_analis_data) return false
  return Object.keys(item.value.rekap_analis_data).length > 0
})

function formatPrice(val: number) {
  return new Intl.NumberFormat('id-ID').format(Math.round(val))
}

const uploadAksesPath = computed(() => item.value?.upload_akses || null)
const uploadAksesUrl = computed(() => {
  if (!uploadAksesPath.value) return null
  const base = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api').replace(/\/api\/?$/, '')
  return `${base}/storage/${uploadAksesPath.value}`
})
const uploadAksesFileName = computed(() => uploadAksesPath.value?.split('/').pop() || '')
const uploadAksesIsImage = computed(() => /\.(jpg|jpeg|png|gif|webp)$/i.test(uploadAksesFileName.value))
const showPreviewModal = ref(false)

function onAksesFileChange(e: Event) {
  const input = e.target as HTMLInputElement
  uploadAksesFile.value = input.files?.[0] || null
}

async function handleUploadAkses() {
  if (!uploadAksesFile.value || !item.value) return
  uploadingAkses.value = true
  try {
    const result = await analystApi.uploadAkses(item.value.id, uploadAksesFile.value)
    if (item.value) {
      item.value = { ...item.value, upload_akses: result.upload_akses }
    }
    uploadAksesFile.value = null
    toast.success('File Upload Akses berhasil diupload')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal upload file')
  } finally {
    uploadingAkses.value = false
  }
}

async function handleRemoveAkses() {
  if (!item.value) return
  try {
    await analystApi.removeAkses(item.value.id)
    if (item.value) {
      item.value = { ...item.value, upload_akses: undefined }
    }
    toast.success('File Upload Akses berhasil dihapus')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menghapus file')
  }
}

onMounted(async () => {
  const id = Number(route.params.id)
  if (!id || isNaN(id)) {
    toast.error('ID tidak valid')
    loading.value = false
    return
  }
  try {
    const [baData, formulasData] = await Promise.all([
      analystApi.getBeritaAcaraDetail(id),
      api.get('/analyst/formulas').then(r => Array.isArray(r.data) ? r.data : []).catch(() => []),
    ])
    item.value = baData
    formulas.value = Array.isArray(formulasData) ? formulasData : []
    if (item.value?.biaya) {
      biaya.value = String(item.value.biaya)
    }
    // Load existing formula rows
    if (item.value?.formula_id) {
      const f = formulas.value.find(x => x.id === item.value!.formula_id)
      if (f) {
        formulaRows.value.push({
          id: ++rowIdCounter,
          formulaId: f.id,
          formula: f,
          calculating: false,
          biaya: item.value?.biaya || null,
          resolvedValues: null,
          tokens: parseFormulaExpression(f.expression, f.variables || []),
          manualNote: '',
        })
      }
    }
    // Load existing custom items as formula rows
    if (item.value?.custom_items && Array.isArray(item.value.custom_items)) {
      for (const ci of item.value.custom_items) {
        formulaRows.value.push({
          id: ++rowIdCounter,
          formulaId: -1,
          formula: null,
          calculating: false,
          biaya: ci.amount || 0,
          resolvedValues: null,
          tokens: [],
          manualNote: ci.name || '',
        })
      }
    }
    // Always have at least one formula row
    if (formulaRows.value.length === 0) {
      addFormulaRow()
    }
    if (item.value?.form_template_id) {
      await loadTemplate(item.value.form_template_id)
    }
  } catch {
    toast.error('Gagal memuat data')
  } finally {
    loading.value = false
  }
})

async function loadTemplate(tid: number) {
  loadingTemplate.value = true
  try {
    const tpl = await formTemplateApi.getPublicTemplate(tid)
    if (tpl?.fields) {
      templateFields.value = tpl.fields
      templateIdVal.value = tpl.id
    }
  } catch {
    // No template
  } finally {
    loadingTemplate.value = false
  }
}

function getInitialData(): Record<string, any> {
  if (hasExistingRekap.value) {
    return { ...item.value!.rekap_analis_data }
  }
  if (item.value?.form_data) {
    return { ...item.value.form_data }
  }
  return {}
}

function handleValuesUpdate(values: Record<string, any>) {
  formValues.value = values
  autoCalculateAll()
}

function scrollToField(fieldId: string) {
  const el = document.getElementById(fieldId)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }
}

async function handleSubmit() {
  if (!dynamicFormRef.value || !item.value) {
    toast.error('Form belum siap')
    return
  }

  const result = dynamicFormRef.value.validate()
  if (!result.valid) {
    const names = result.missingFields.slice(0, 3).join(', ')
    const extra = result.missingFields.length > 3 ? ` dan ${result.missingFields.length - 3} lainnya` : ''
    toast.error(`Wajib diisi: ${names}${extra}`)
    if (result.firstErrorField) {
      setTimeout(() => scrollToField(result.firstErrorField!), 100)
    }
    return
  }

  saving.value = true
  try {
    const firstFormula = formulaRows.value.find(r => r.formulaId && r.formulaId !== -1)
    await analystApi.storeRekapAnalis(item.value.id, {
      rekap_analis_data: JSON.stringify(formValues.value),
      rekap_analis_template_id: templateIdVal.value,
      biaya: totalBiaya.value || null,
      formula_id: firstFormula?.formulaId || null,
      custom_items: formulaRows.value
        .filter(r => r.biaya && r.biaya > 0)
        .map(r => ({
          name: r.formulaId && r.formulaId !== -1 ? (r.formula?.name || 'Rumus') : (r.manualNote || 'Biaya tambahan'),
          amount: r.biaya || 0,
        })),
      keterangan: keterangan.value || null,
    })
    const updated = await analystApi.getBeritaAcaraDetail(item.value.id)
    item.value = updated
    toast.success('Rekap analis berhasil disimpan')
    router.push(`/analyst/samples/${item.value.id}`)
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menyimpan rekap analis')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AppLayout>
    <div>
      <PageHeader
        title="Rekap Analis"
        subtitle="Formulir pengisian hasil rekap analisis dan perhitungan biaya otomatis"
        show-back
        :back-to="`/analyst/samples/${route.params.id}`"
      >
        <Badge v-if="item" :type="getSampleBadgeType()" :text="getSampleBadgeText()" />
      </PageHeader>

      <SkeletonDetail v-if="loading" />

      <template v-else-if="item">
        <Card title="Formulir Rekap Analis">
          <SkeletonDetail v-if="loadingTemplate" />
          <div v-else-if="templateFields.length === 0" class="empty-state">
            <p>Form template berita acara tidak ditemukan.</p>
          </div>
          <div v-else>
            <p class="form-desc">
              {{ hasExistingRekap ? 'Edit data rekap analis di bawah ini.' : 'Isi formulir rekap analis berdasarkan data berita acara.' }}
            </p>
            <DynamicForm
              ref="dynamicFormRef"
              :fields="templateFields"
              :initial-data="getInitialData()"
              @update:values="handleValuesUpdate"
            />

            <!-- Upload Akses Section -->
            <div class="akses-section">
              <label class="akses-label">Upload Akses</label>
              <p class="akses-desc">File akses yang diupload saat pembuatan berita acara.</p>

              <!-- Existing file -->
              <div v-if="uploadAksesPath && !uploadAksesFile" class="akses-file-preview">
                <div class="akses-file-info">
                  <FileText :size="18" class="akses-file-icon" />
                  <span class="akses-file-name">{{ uploadAksesFileName }}</span>
                </div>
                <div class="akses-file-actions">
                  <button class="akses-btn-icon" title="Preview" @click="showPreviewModal = true">
                    <Eye :size="14" />
                  </button>
                  <a :href="uploadAksesUrl" :download="uploadAksesFileName" class="akses-btn-icon" title="Download">
                    <Download :size="14" />
                  </a>
                  <label class="akses-btn-replace">
                    <Upload :size="14" />
                    Ganti
                    <input type="file" class="akses-input-hidden" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" @change="onAksesFileChange" />
                  </label>
                  <button class="akses-btn-remove" @click="handleRemoveAkses" title="Hapus file">
                    <X :size="14" />
                  </button>
                </div>
              </div>

              <!-- New file selected -->
              <div v-else-if="uploadAksesFile" class="akses-file-selected">
                <div class="akses-file-info">
                  <FileText :size="18" class="akses-file-icon" />
                  <span class="akses-file-name">{{ uploadAksesFile.name }}</span>
                  <span class="akses-file-size">({{ (uploadAksesFile.size / 1024).toFixed(1) }} KB)</span>
                </div>
                <div class="akses-file-actions">
                  <Button size="sm" :disabled="uploadingAkses" @click="handleUploadAkses">
                    {{ uploadingAkses ? 'Mengupload...' : 'Upload' }}
                  </Button>
                  <button class="akses-btn-cancel" @click="uploadAksesFile = null">Batal</button>
                </div>
              </div>

              <!-- No file yet -->
              <div v-else class="akses-upload-area">
                <label class="akses-upload-trigger">
                  <Upload :size="16" />
                  <span>Pilih file untuk diupload</span>
                  <input type="file" class="akses-input-hidden" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" @change="onAksesFileChange" />
                </label>
                <span class="akses-hint">PDF, JPG, PNG, DOC, DOCX (maks 10MB)</span>
              </div>
            </div>

            <div class="biaya-section">
              <label class="biaya-label">Biaya Pengujian</label>

              <!-- Formula rows -->
              <div v-for="(row, idx) in formulaRows" :key="row.id" class="formula-row">
                <div class="formula-row-header">
                  <span class="formula-row-num">{{ idx + 1 }}</span>
                  <div class="formula-row-selects">
                    <FormSelect
                      :model-value="getFormulaSelectValue(row)"
                      :options="formulaOptions"
                      @update:model-value="onFormulaSelectValue(row, $event)"
                    />
                  </div>
                  <button class="formula-row-remove" @click="removeFormulaRow(idx)" title="Hapus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                  </button>
                </div>

                <!-- Manual input: keterangan + biaya -->
                <div v-if="row.formulaId === -1" class="manual-section">
                  <input
                    v-model="row.manualNote"
                    type="text"
                    class="manual-note-input"
                    placeholder="Keterangan (contoh: Overtime, Konsultasi)"
                  />
                  <input
                    v-model.number="row.biaya"
                    type="number"
                    min="0"
                    class="manual-biaya-input"
                    placeholder="Jumlah Biaya (Rp)"
                  />
                </div>

                <!-- Formula display -->
                <div v-if="row.formula && row.formulaId !== -1" class="formula-display">
                  <div class="formula-display-label">{{ row.formula.name }}</div>
                  <div class="formula-display-tokens">
                    <template v-for="(token, tidx) in row.tokens" :key="tidx">
                      <span v-if="token.type === 'var'" class="token-chip">{{ token.label || token.value }}</span>
                      <span v-else-if="token.type === 'func'" class="token-func">{{ token.value }}(</span>
                      <span v-else-if="token.type === 'paren'" class="token-paren">{{ token.value }}</span>
                      <span v-else class="token-op">{{ token.display || token.value }}</span>
                    </template>
                  </div>
                </div>

                <!-- Resolved values -->
                <div v-if="row.resolvedValues && row.formulaId !== -1" class="resolved-values">
                  <div class="resolved-title">Nilai yang digunakan:</div>
                  <div class="resolved-grid">
                    <div v-for="(val, key) in row.resolvedValues" :key="String(key)" class="resolved-item">
                      <span class="resolved-key">{{ String(key) }}</span>
                      <span class="resolved-val">Rp {{ formatPrice(val) }}</span>
                    </div>
                  </div>
                </div>

                <!-- Automatic Formula Result Display -->
                <div v-if="row.formulaId && row.formulaId !== -1" class="formula-row-actions">
                  <span v-if="row.calculating" class="calculating-text">Menghitung...</span>
                  <span v-else-if="row.biaya !== null" class="formula-row-result">Rp {{ formatPrice(row.biaya) }}</span>
                </div>

                <!-- Manual result -->
                <div v-if="row.formulaId === -1 && row.biaya" class="formula-row-result">
                  Rp {{ formatPrice(row.biaya) }}
                </div>
              </div>

              <!-- Add formula button -->
              <Button variant="outline" size="sm" class="add-formula-btn" @click="addFormulaRow">+ Tambah</Button>

              <!-- Keterangan -->
              <div class="keterangan-section">
                <label class="keterangan-label">Keterangan <span class="keterangan-optional">(opsional)</span></label>
                <textarea
                  v-model="keterangan"
                  class="keterangan-textarea"
                  placeholder="Tambahkan keterangan jika diperlukan..."
                  rows="3"
                />
              </div>

              <!-- Total -->
              <div class="total-bar">
                <span class="total-label">Total Biaya</span>
                <span class="total-value">Rp {{ formatPrice(totalBiaya) }}</span>
              </div>
            </div>

            <div class="form-actions">
              <Button variant="outline" @click="router.push(`/analyst/samples/${route.params.id}`)">Batal</Button>
              <Button :disabled="saving" @click="handleSubmit">
                {{ saving ? 'Mengirim...' : 'Kirim' }}
              </Button>
            </div>
          </div>
        </Card>
      </template>
    </div>
  </AppLayout>

  <!-- Preview Modal -->
  <Modal :show="showPreviewModal" :title="uploadAksesFileName" size="lg" @close="showPreviewModal = false">
    <div class="preview-container">
      <img v-if="uploadAksesIsImage" :src="uploadAksesUrl" :alt="uploadAksesFileName" class="preview-image" />
      <div v-else class="preview-frame-wrap">
        <iframe :src="uploadAksesUrl" class="preview-iframe" frameborder="0" />
      </div>
    </div>
    <template #footer>
      <a :href="uploadAksesUrl" :download="uploadAksesFileName" class="preview-download-btn">
        <Download :size="14" />
        Download
      </a>
    </template>
  </Modal>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.page-header__left { display: flex; align-items: center; gap: 12px; }
.page-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }

.empty-state { text-align: center; padding: 40px 20px; color: #64748b; }
.form-desc { margin: 0 0 20px; font-size: 14px; color: #64748b; }
.form-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid #e5e7eb; }

.biaya-section { margin-top: 24px; padding: 16px; background: #fefce8; border: 1px solid #fde68a; border-radius: 8px; }
.biaya-label { display: block; font-size: 14px; font-weight: 600; color: #92400e; margin-bottom: 12px; }
.biaya-sublabel { font-size: 14px; font-weight: 500; color: #92400e; white-space: nowrap; }
.biaya-row { display: flex; gap: 12px; align-items: center; }
.biaya-input { flex: 1; max-width: 320px; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: #1e293b; outline: none; transition: border-color 0.2s; }
.biaya-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

.formula-row { margin-bottom: 12px; padding: 12px; background: white; border: 1px solid #e5e7eb; border-radius: 8px; }
.formula-row-header { display: flex; gap: 8px; align-items: center; }
.formula-row-num { width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; background: #e0e7ff; color: #4338ca; border-radius: 50%; font-size: 12px; font-weight: 600; flex-shrink: 0; }
.formula-row-selects { flex: 1; display: flex; gap: 8px; }
.formula-row-remove { display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; border: none; background: transparent; color: #94a3b8; cursor: pointer; border-radius: 4px; flex-shrink: 0; }
.formula-row-remove:hover { background: #fee2e2; color: #dc2626; }
.formula-row-actions { display: flex; gap: 12px; align-items: center; margin-top: 8px; }
.formula-row-result { font-size: 14px; font-weight: 600; color: #166534; }
.calculating-text { font-size: 12px; color: #2563eb; font-style: italic; }

.manual-section { display: flex; gap: 8px; margin-top: 8px; }
.manual-note-input { flex: 1; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; color: #1e293b; outline: none; }
.manual-note-input:focus { border-color: #3b82f6; }
.manual-biaya-input { width: 160px; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; color: #1e293b; outline: none; }
.manual-biaya-input:focus { border-color: #3b82f6; }

.hitung-semua-row { margin: 12px 0 0; }

.keterangan-section { margin-top: 12px; }
.keterangan-label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 4px; }
.keterangan-optional { font-weight: 400; color: #94a3b8; }
.keterangan-textarea { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; color: #1e293b; outline: none; resize: vertical; }
.keterangan-textarea:focus { border-color: #3b82f6; }

.add-formula-btn { margin-bottom: 0; }

.formula-display { margin-top: 8px; padding: 8px 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; }
.formula-display-label { font-size: 11px; color: #64748b; margin-bottom: 4px; }
.formula-display-tokens { display: flex; flex-wrap: wrap; gap: 3px; align-items: center; }

.token-chip { display: inline-flex; align-items: center; padding: 2px 6px; background: #dbeafe; color: #1d4ed8; border-radius: 3px; font-size: 11px; font-weight: 500; white-space: nowrap; }
.token-func { font-size: 11px; color: #7c3aed; font-weight: 500; }
.token-op { font-size: 12px; color: #dc2626; font-weight: 600; padding: 0 1px; }
.token-paren { font-size: 12px; color: #64748b; font-weight: 600; }

.resolved-values { margin-top: 8px; padding: 8px 10px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; }
.resolved-title { font-size: 11px; font-weight: 500; color: #64748b; margin-bottom: 4px; }
.resolved-grid { display: flex; flex-wrap: wrap; gap: 6px; }
.resolved-item { display: flex; align-items: center; gap: 4px; padding: 2px 6px; background: white; border: 1px solid #e2e8f0; border-radius: 3px; font-size: 11px; }
.resolved-key { color: #64748b; }
.resolved-val { font-weight: 600; color: #1e293b; }

.total-bar { display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding: 12px 16px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; }
.total-label { font-size: 14px; font-weight: 600; color: #166534; }
.total-value { font-size: 18px; font-weight: 700; color: #166534; }

.discount-row { margin-top: 12px; display: flex; justify-content: flex-end; }

.discount-form { display: flex; flex-direction: column; gap: 16px; }
.discount-total-info { padding: 10px 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; }
.discount-total-label { font-size: 13px; color: #374151; }
.discount-total-value { font-size: 15px; font-weight: 700; color: #166534; }
.discount-field { display: flex; flex-direction: column; gap: 6px; }
.discount-label { font-size: 14px; font-weight: 500; color: #374151; }
.discount-textarea { padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: #1e293b; outline: none; resize: vertical; transition: border-color 0.2s; }
.discount-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.discount-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 8px; }

.discount-table { width: 100%; border-collapse: collapse; }
.discount-table tr { border-bottom: 1px solid #f1f5f9; }
.discount-table tr:last-child { border-bottom: none; }
.discount-td-label { padding: 8px 12px 8px 0; font-size: 12px; font-weight: 500; color: #64748b; white-space: nowrap; }
.discount-td-value { padding: 8px 0; font-size: 13px; color: #1e293b; }

/* Upload Akses Section */
.akses-section {
  margin-top: 20px;
  padding: 16px;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 8px;
}
.akses-label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #0369a1;
  margin-bottom: 4px;
}
.akses-desc {
  font-size: 12px;
  color: #64748b;
  margin: 0 0 12px;
}
.akses-file-preview,
.akses-file-selected {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 14px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}
.akses-file-info {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}
.akses-file-icon {
  color: #0369a1;
  flex-shrink: 0;
}
.akses-file-name {
  font-size: 13px;
  font-weight: 500;
  color: #1e293b;
  text-decoration: underline;
  text-overflow: ellipsis;
  overflow: hidden;
  white-space: nowrap;
}
.akses-file-name:hover {
  color: #2563eb;
}
.akses-file-size {
  font-size: 11px;
  color: #94a3b8;
  white-space: nowrap;
}
.akses-file-actions {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}
.akses-btn-replace {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 5px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: white;
  font-size: 12px;
  font-weight: 500;
  color: #475569;
  cursor: pointer;
  transition: all 0.15s;
}
.akses-btn-replace:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}
.akses-btn-remove {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: white;
  color: #94a3b8;
  cursor: pointer;
  transition: all 0.15s;
}
.akses-btn-remove:hover {
  background: #fee2e2;
  border-color: #fecaca;
  color: #dc2626;
}
.akses-btn-icon {
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
.akses-btn-icon:hover {
  background: #eff6ff;
  border-color: #bfdbfe;
  color: #2563eb;
}
.akses-btn-cancel {
  padding: 5px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: white;
  font-size: 12px;
  color: #64748b;
  cursor: pointer;
}
.akses-btn-cancel:hover {
  background: #f1f5f9;
}
.akses-input-hidden {
  display: none;
}
.akses-upload-area {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.akses-upload-trigger {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 16px;
  border: 2px dashed #bae6fd;
  border-radius: 8px;
  background: white;
  font-size: 13px;
  font-weight: 500;
  color: #0369a1;
  cursor: pointer;
  transition: all 0.15s;
}
.akses-upload-trigger:hover {
  border-color: #38bdf8;
  background: #f0f9ff;
}
.akses-hint {
  font-size: 11px;
  color: #94a3b8;
}

/* Preview Modal */
.preview-container {
  width: 100%;
  min-height: 400px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
  border-radius: 8px;
  overflow: hidden;
}
.preview-image {
  max-width: 100%;
  max-height: 70vh;
  object-fit: contain;
  border-radius: 4px;
}
.preview-frame-wrap {
  width: 100%;
  height: 70vh;
  overflow: hidden;
  border-radius: 4px;
}
.preview-iframe {
  width: 300%;
  height: 100%;
  margin-left: -100%;
  border: none;
}
.preview-download-btn {
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
.preview-download-btn:hover {
  background: #1d4ed8;
}
</style>
