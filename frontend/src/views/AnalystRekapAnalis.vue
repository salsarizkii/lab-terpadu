<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import SkeletonDetail from '../components/common/SkeletonDetail.vue'
import DynamicForm from '../components/common/DynamicForm.vue'
import { analystApi } from '../api/analyst'
import { formulaApi, type Formula } from '../api/formula'
import { formTemplateApi } from '../api/formTemplate'
import type { BeritaAcara, FormField } from '../types'
import { toast } from 'vue-sonner'
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
const showDiscountModal = ref(false)
const discountReason = ref('')
const keterangan = ref('')
const submittingDiscount = ref(false)

const formulas = ref<Formula[]>([])

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
    formulaId: null,
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

async function handleCalculateRow(row: FormulaRow) {
  if (!row.formulaId || !item.value || row.formulaId === -1) return
  row.calculating = true
  row.biaya = null
  row.resolvedValues = null
  try {
    const result = await analystApi.calculateCost(item.value.id, {
      formula_id: row.formulaId,
      rekap_analis_data: JSON.stringify(formValues.value),
    })
    row.biaya = result.biaya
    row.resolvedValues = result.resolved_values
    toast.success(`Biaya dihitung: Rp ${formatPrice(result.biaya)}`)
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menghitung biaya')
  } finally {
    row.calculating = false
  }
}

async function handleCalculateAll() {
  const formulaOnly = formulaRows.value.filter(r => r.formulaId && r.formulaId !== -1)
  if (formulaOnly.length === 0) {
    toast.error('Tidak ada rumus untuk dihitung')
    return
  }
  for (const row of formulaOnly) {
    if (!row.calculating) {
      await handleCalculateRow(row)
    }
  }
  toast.success('Semua rumus selesai dihitung')
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
  if (ba.samples.every((s: any) => s.status === 'ready' || s.status === 'taken')) return 'ready'
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

function formatFieldKey(key: string): string {
  const field = templateFields.value.find(f => `field_${f.id}` === key)
  if (field) return field.label
  return key.replace(/^field_/, '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function formatFieldValue(val: any): string {
  if (val === null || val === undefined) return '-'
  if (Array.isArray(val)) return val.join(', ')
  return String(val)
}

function formatPrice(val: number) {
  return new Intl.NumberFormat('id-ID').format(Math.round(val))
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
    const firstFormula = formulaRows.value.find(r => r.formulaId)
    await analystApi.storeRekapAnalis(item.value.id, {
      rekap_analis_data: JSON.stringify(formValues.value),
      rekap_analis_template_id: templateIdVal.value,
      biaya: totalBiaya.value || null,
      formula_id: firstFormula?.formulaId || null,
      custom_items: formulaRows.value
        .filter(r => !r.formulaId && r.biaya)
        .map(r => ({ name: r.formula?.name || `Biaya tambahan`, amount: r.biaya || 0 })),
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

function openDiscountModal() {
  discountReason.value = ''
  showDiscountModal.value = true
}

async function handleSubmitDiscount() {
  if (!item.value) return

  submittingDiscount.value = true
  try {
    await analystApi.requestDiscount(item.value.id, {
      discount_request: totalBiaya.value || 0,
      discount_reason: discountReason.value || undefined,
    })
    toast.success('Ajukan diskon berhasil')
    showDiscountModal.value = false
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal mengajukan diskon')
  } finally {
    submittingDiscount.value = false
  }
}
</script>

<template>
  <AppLayout>
    <div>
      <div class="page-header">
        <div class="page-header__left">
          <Button variant="outline" @click="router.push(`/analyst/samples/${route.params.id}`)">Kembali</Button>
          <h1>Rekap Analis</h1>
        </div>
        <Badge v-if="item" :type="getSampleBadgeType()" :text="getSampleBadgeText()" />
      </div>

      <SkeletonDetail v-if="loading" />

      <template v-else-if="item">
        <Card title="Data Berita Acara" style="margin-bottom: 24px;">
          <table class="detail-table">
            <template v-if="item.form_data && Object.keys(item.form_data).length > 0">
              <tr v-for="(value, key) in item.form_data" :key="String(key)">
                <td class="detail-td-label">{{ formatFieldKey(String(key)) }}</td>
                <td class="detail-td-value">{{ formatFieldValue(value) }}</td>
              </tr>
            </template>
            <template v-else>
              <tr><td class="detail-td-label">Nama Pemohon</td><td class="detail-td-value">{{ item.nama_pemohon || '-' }}</td></tr>
              <tr><td class="detail-td-label">NIM</td><td class="detail-td-value">{{ item.nim || '-' }}</td></tr>
              <tr><td class="detail-td-label">Judul Penelitian</td><td class="detail-td-value">{{ item.judul_penelitian || '-' }}</td></tr>
              <tr><td class="detail-td-label">Email</td><td class="detail-td-value">{{ item.email || '-' }}</td></tr>
              <tr><td class="detail-td-label">Telp/HP</td><td class="detail-td-value">{{ item.telp_hp || '-' }}</td></tr>
            </template>
          </table>
        </Card>

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

            <div class="biaya-section">
              <label class="biaya-label">Perhitungan Biaya</label>

              <!-- Formula rows -->
              <div v-for="(row, idx) in formulaRows" :key="row.id" class="formula-row">
                <div class="formula-row-header">
                  <span class="formula-row-num">{{ idx + 1 }}</span>
                  <div class="formula-row-selects">
                    <select v-model="row.formulaId" class="formula-select" @change="onFormulaSelect(row)">
                      <option :value="null">Pilih rumus...</option>
                      <option :value="-1">Isi Manual</option>
                      <option v-for="f in formulas" :key="f.id" :value="f.id">
                        {{ f.name }} ({{ f.unit || 'Rupiah' }})
                      </option>
                    </select>
                  </div>
                  <button class="formula-row-remove" @click="removeFormulaRow(idx)" title="Hapus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                  </button>
                </div>

                <!-- Manual input: textarea + biaya -->
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
                    placeholder="Jumlah (Rp)"
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

                <!-- Calculate button -->
                <div v-if="row.formulaId && row.formulaId !== -1" class="formula-row-actions">
                  <Button
                    variant="outline"
                    size="sm"
                    :disabled="row.calculating"
                    @click="handleCalculateRow(row)"
                  >
                    {{ row.calculating ? 'Menghitung...' : 'Hitung' }}
                  </Button>
                  <span v-if="row.biaya" class="formula-row-result">Rp {{ formatPrice(row.biaya) }}</span>
                </div>

                <!-- Manual result -->
                <div v-if="row.formulaId === -1 && row.biaya" class="formula-row-result">
                  Rp {{ formatPrice(row.biaya) }}
                </div>
              </div>

              <!-- Add formula button -->
              <Button variant="outline" size="sm" class="add-formula-btn" @click="addFormulaRow">+ Tambah</Button>

              <!-- Hitung Semua -->
              <div class="hitung-semua-row">
                <Button
                  variant="outline"
                  size="sm"
                  :disabled="formulaRows.filter(r => r.formulaId && r.formulaId !== -1).length === 0"
                  @click="handleCalculateAll"
                >
                  Hitung Semua
                </Button>
              </div>

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

              <!-- Diskon -->
              <div class="discount-row">
                <Button variant="outline" size="sm" @click="openDiscountModal">Ajukan Diskon</Button>
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

      <Modal :show="showDiscountModal" title="Ajukan Diskon" @close="showDiscountModal = false">
        <div class="discount-form">
          <div class="discount-total-info">
            <span class="discount-total-label">Total biaya yang diajukan:</span>
            <span class="discount-total-value">Rp {{ formatPrice(totalBiaya) }}</span>
          </div>
          <div class="discount-field">
            <label class="discount-label">Alasan Diskon</label>
            <textarea
              v-model="discountReason"
              class="discount-textarea"
              placeholder="Masukkan alasan pengajuan diskon..."
              rows="4"
            />
          </div>
          <div class="discount-actions">
            <Button variant="outline" @click="showDiscountModal = false">Batal</Button>
            <Button :disabled="submittingDiscount" @click="handleSubmitDiscount">
              {{ submittingDiscount ? 'Mengirim...' : 'Kirim Pengajuan' }}
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.page-header__left { display: flex; align-items: center; gap: 12px; }
.page-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }

.detail-table { width: 100%; border-collapse: collapse; }
.detail-table tr { border-bottom: 1px solid #f1f5f9; }
.detail-table tr:last-child { border-bottom: none; }
.detail-td-label { padding: 10px 16px 10px 0; font-size: 13px; font-weight: 500; color: #64748b; white-space: nowrap; width: 180px; }
.detail-td-value { padding: 10px 0; font-size: 14px; color: #1e293b; }

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
.formula-select { flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; color: #1e293b; background: white; outline: none; cursor: pointer; }
.formula-select:focus { border-color: #3b82f6; }
.formula-row-remove { display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; border: none; background: transparent; color: #94a3b8; cursor: pointer; border-radius: 4px; flex-shrink: 0; }
.formula-row-remove:hover { background: #fee2e2; color: #dc2626; }
.formula-row-actions { display: flex; gap: 12px; align-items: center; margin-top: 8px; }
.formula-row-result { font-size: 14px; font-weight: 600; color: #166534; }

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
</style>
