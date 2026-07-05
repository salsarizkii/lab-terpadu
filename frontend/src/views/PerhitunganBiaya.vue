<template>
  <div class="perhitungan-biaya">
    <div class="page-header">
      <div class="page-header-left">
        <h1 class="page-title">Perhitungan Biaya</h1>
        <p class="page-subtitle">Buat rumus perhitungan harga dari data form</p>
      </div>
      <div class="page-header-right">
        <button class="btn-secondary" @click="openVariablePanel = !openVariablePanel">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
          Variabel
        </button>
        <button class="btn-primary" @click="saveFormula">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
          Simpan
        </button>
      </div>
    </div>

    <div class="content-grid">
      <!-- Left: Formula Editor -->
      <div class="formula-panel">
        <div class="content-card">
          <div class="card-header">
            <h3>Rumus Perhitungan</h3>
          </div>
          <div class="card-body">
            <div class="formula-section">
              <label class="section-label">Nama Perhitungan</label>
              <input v-model="formulaName" class="form-input" placeholder="Contoh: Biaya Analisis Gula" />
            </div>

            <div class="formula-section">
              <label class="section-label">Deskripsi</label>
              <textarea v-model="formulaDescription" class="form-textarea" rows="2" placeholder="Deskripsi perhitungan..."></textarea>
            </div>

            <div class="formula-section">
              <label class="section-label">Rumus</label>
              <div class="formula-editor">
                <div class="formula-input-wrapper">
                  <textarea 
                    v-model="formulaExpression" 
                    class="formula-input" 
                    rows="4"
                    placeholder="Masukkan rumus di sini...&#10;Contoh: (harga_sampel * jumlah_sampel) + biaya_tambahan&#10;&#10;Gunakan operator: +, -, *, /, (), Math.round(), Math.ceil(), Math.floor()"
                  ></textarea>
                </div>
                <div class="formula-hint">
                  <span class="hint-title">Tips:</span>
                  <code>harga_sampel</code> — nama variabel dari form
                  <code>*</code> <code>+</code> <code>-</code> <code>/</code> — operator matematika
                  <code>( )</code> — kurung untuk urutan operasi
                  <code>Math.round(x)</code> — bulatkan ke integer terdekat
                </div>
              </div>
            </div>

            <div class="formula-section">
              <label class="section-label">Satuan Hasil</label>
              <input v-model="formulaUnit" class="form-input" placeholder="Contoh: Rupiah" />
            </div>
          </div>
        </div>

        <!-- Preview / Test -->
        <div class="content-card">
          <div class="card-header">
            <h3>Preview & Test</h3>
          </div>
          <div class="card-body">
            <div v-if="variables.length === 0" class="empty-hint">
              Tambahkan variabel terlebih dahulu untuk bisa menguji rumus
            </div>
            <div v-else class="test-section">
              <div class="test-variables">
                <div v-for="v in variables" :key="v.key" class="test-var-row">
                  <label class="test-var-label">
                    <span class="var-badge">{{ v.key }}</span>
                    {{ v.label }}
                  </label>
                  <input 
                    type="number" 
                    v-model.number="testValues[v.key]" 
                    class="test-input" 
                    :placeholder="v.default_value?.toString() || '0'"
                  />
                </div>
              </div>
              <div class="test-result" v-if="formulaExpression">
                <div class="result-label">Hasil:</div>
                <div class="result-value">
                  <template v-if="calculationError">
                    <span class="result-error">{{ calculationError }}</span>
                  </template>
                  <template v-else>
                    Rp{{ formatPrice(calculateTestResult()) }}
                  </template>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Variables -->
      <div class="variables-panel">
        <div class="content-card" :class="{ 'panel-open': openVariablePanel }">
          <div class="card-header">
            <h3>Variabel</h3>
            <button class="btn-add-var" @click="openAddVariable">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
              Tambah
            </button>
          </div>
          <div class="card-body">
            <div v-if="variables.length === 0" class="empty-state">
              <p>Belum ada variabel</p>
              <button class="btn-text" @click="openAddVariable">Tambah variabel pertama</button>
            </div>
            <div v-else class="var-list">
              <div v-for="(v, idx) in variables" :key="v.key" class="var-item">
                <div class="var-item-header">
                  <span class="var-badge">{{ v.key }}</span>
                  <span class="var-source">{{ v.source === 'berita_acara' ? 'Form Berita Acara' : 'Form Rekap Analis' }}</span>
                </div>
                <div class="var-item-body">
                  <div class="var-label">{{ v.label }}</div>
                  <div class="var-default" v-if="v.default_value">Default: {{ v.default_value }}</div>
                </div>
                <div class="var-item-actions">
                  <button class="btn-icon-sm" @click="editVariable(idx)" title="Edit">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                  </button>
                  <button class="btn-icon-sm btn-danger" @click="removeVariable(idx)" title="Hapus">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Variable Modal -->
    <Teleport to="body">
      <div v-if="showVarModal" class="modal-overlay" @click.self="showVarModal = false">
        <div class="modal-content">
          <div class="modal-header">
            <h2>{{ editingVarIndex >= 0 ? 'Edit Variabel' : 'Tambah Variabel' }}</h2>
            <button class="btn-close" @click="showVarModal = false">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>Sumber <span class="required">*</span></label>
              <select v-model="varForm.source" class="form-select">
                <option value="berita_acara">Form Berita Acara</option>
                <option value="rekap_analis">Form Rekap Analis</option>
                <option value="manual">Input Manual</option>
              </select>
            </div>
            <div class="form-group">
              <label>Kode Variabel <span class="required">*</span></label>
              <input v-model="varForm.key" class="form-input" :disabled="editingVarIndex >= 0" placeholder="Contoh: harga_sampel" />
              <span class="form-hint">Gunakan huruf kecil, underscore. Contoh: jumlah_sampel, harga_per_kg</span>
            </div>
            <div class="form-group">
              <label>Label / Nama Pertanyaan <span class="required">*</span></label>
              <input v-model="varForm.label" class="form-input" placeholder="Contoh: Harga per Sampel" />
            </div>
            <div v-if="varForm.source !== 'manual'" class="form-group">
              <label>Pertanyaan dari Form</label>
              <select v-model="varForm.form_field_id" class="form-select">
                <option :value="null">-- Pilih pertanyaan --</option>
                <option v-for="f in availableFields(varForm.source)" :key="f.id" :value="f.id">
                  [{{ f.type }}] {{ f.label }}
                </option>
              </select>
              <span class="form-hint">Nilai dari form ini akan dijadikan variabel</span>
            </div>
            <div class="form-group">
              <label>Nilai Default</label>
              <input v-model.number="varForm.default_value" type="number" class="form-input" placeholder="0" />
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn-secondary" @click="showVarModal = false">Batal</button>
            <button class="btn-primary" @click="saveVariable" :disabled="!varForm.key || !varForm.label">
              {{ editingVarIndex >= 0 ? 'Simpan' : 'Tambah' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-sonner'
import { formTemplateApi, type FormField } from '@/api/formTemplate'
import { useRouter } from 'vue-router'

const toast = useToast()
const router = useRouter()

const openVariablePanel = ref(true)
const formulaName = ref('')
const formulaDescription = ref('')
const formulaExpression = ref('')
const formulaUnit = ref('Rupiah')

interface FormulaVariable {
  key: string
  label: string
  source: 'berita_acara' | 'rekap_analis' | 'manual'
  form_field_id: number | null
  default_value: number | null
}

const variables = ref<FormulaVariable[]>([])
const testValues = ref<Record<string, number>>({})

const showVarModal = ref(false)
const editingVarIndex = ref(-1)
const varForm = ref<Omit<FormulaVariable, 'id'>>({
  key: '',
  label: '',
  source: 'berita_acara',
  form_field_id: null,
  default_value: 0,
})

// Load form fields
const beritaAcaraFields = ref<FormField[]>([])
const rekapAnalisFields = ref<FormField[]>([])

function availableFields(source: string) {
  return source === 'berita_acara' ? beritaAcaraFields.value : rekapAnalisFields.value
}

async function loadFormFields() {
  try {
    const [baRes, raRes] = await Promise.all([
      formTemplateApi.getPublicActiveByType('berita_acara'),
      formTemplateApi.getPublicActiveByType('rekap_analis'),
    ])
    if (baRes?.fields) beritaAcaraFields.value = baRes.fields
    if (raRes?.fields) rekapAnalisFields.value = raRes.fields
  } catch (e) {
    // silent
  }
}

function openAddVariable() {
  editingVarIndex.value = -1
  varForm.value = { key: '', label: '', source: 'berita_acara', form_field_id: null, default_value: 0 }
  showVarModal.value = true
}

function editVariable(idx: number) {
  editingVarIndex.value = idx
  varForm.value = { ...variables.value[idx] }
  showVarModal.value = true
}

function saveVariable() {
  if (!varForm.value.key || !varForm.value.label) return
  // Validate key format
  if (!/^[a-z_][a-z0-9_]*$/.test(varForm.value.key)) {
    toast.error('Kode variabel hanya boleh huruf kecil, angka, dan underscore')
    return
  }
  if (editingVarIndex.value >= 0) {
    variables.value[editingVarIndex.value] = { ...varForm.value }
  } else {
    variables.value.push({ ...varForm.value })
    testValues.value[varForm.value.key] = varForm.value.default_value || 0
  }
  showVarModal.value = false
  toast.success(editingVarIndex.value >= 0 ? 'Variabel diperbarui' : 'Variabel ditambahkan')
}

function removeVariable(idx: number) {
  const key = variables.value[idx].key
  delete testValues.value[key]
  variables.value.splice(idx, 1)
}

const calculationError = computed(() => {
  try {
    calculateTestResult()
    return null
  } catch (e: any) {
    return e.message || 'Rumus tidak valid'
  }
})

function calculateTestResult(): number {
  let expr = formulaExpression.value
  if (!expr) return 0
  // Replace variable names with values
  for (const v of variables.value) {
    const val = testValues.value[v.key] ?? v.default_value ?? 0
    expr = expr.replace(new RegExp(`\\b${v.key}\\b`, 'g'), val.toString())
  }
  // Only allow safe math characters
  if (/[^0-9+\-*/().%,\s]|Math\./.test(expr.replace(/Math\.(round|ceil|floor|abs|min|max|pow|sqrt)/g, ''))) {
    throw new Error('Rumus mengandung karakter yang tidak diizinkan')
  }
  // Replace Math functions
  expr = expr.replace(/Math\.round/g, 'Math.round')
  expr = expr.replace(/Math\.ceil/g, 'Math.ceil')
  expr = expr.replace(/Math\.floor/g, 'Math.floor')
  const result = new Function(`return (${expr})`)()
  if (typeof result !== 'number' || isNaN(result)) {
    throw new Error('Hasil perhitungan bukan angka yang valid')
  }
  return result
}

function formatPrice(val: number) {
  return new Intl.NumberFormat('id-ID').format(Math.round(val))
}

function saveFormula() {
  if (!formulaName.value) {
    toast.error('Nama perhitungan wajib diisi')
    return
  }
  if (!formulaExpression.value) {
    toast.error('Rumus wajib diisi')
    return
  }
  // Save to localStorage (could be API later)
  const data = {
    name: formulaName.value,
    description: formulaDescription.value,
    expression: formulaExpression.value,
    unit: formulaUnit.value,
    variables: variables.value,
    created_at: new Date().toISOString(),
  }
  const existing = JSON.parse(localStorage.getItem('formulas_biaya') || '[]')
  existing.push(data)
  localStorage.setItem('formulas_biaya', JSON.stringify(existing))
  toast.success('Rumus berhasil disimpan')
  router.push('/admin/forms')
}

onMounted(loadFormFields)
</script>

<style scoped>
.perhitungan-biaya {
  padding: 0;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
}

.page-title {
  font-size: 24px;
  font-weight: 700;
  color: var(--color-text-primary);
  margin: 0;
}

.page-subtitle {
  font-size: 14px;
  color: var(--color-text-secondary);
  margin: 4px 0 0;
}

.page-header-right {
  display: flex;
  gap: 12px;
  flex-shrink: 0;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 18px;
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: var(--radius-md);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-primary:hover { background: var(--color-primary-hover); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 18px;
  background: var(--color-bg-secondary);
  color: var(--color-text-primary);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-secondary:hover { background: var(--color-bg-tertiary); }

.content-grid {
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: 24px;
  align-items: start;
}

.content-card {
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.content-card + .content-card {
  margin-top: 20px;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid var(--color-border);
}

.card-header h3 {
  font-size: 15px;
  font-weight: 700;
  color: var(--color-text-primary);
  margin: 0;
}

.card-body {
  padding: 20px;
}

.formula-section {
  margin-bottom: 20px;
}

.section-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-secondary);
  margin-bottom: 8px;
}

.form-input,
.form-textarea,
.form-select {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 14px;
  color: var(--color-text-primary);
  background: var(--color-bg-primary);
  outline: none;
  transition: border-color 0.15s ease;
  font-family: inherit;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px rgba(var(--color-primary-rgb), 0.15);
}

.form-textarea {
  resize: vertical;
  min-height: 60px;
}

.form-hint {
  display: block;
  font-size: 12px;
  color: var(--color-text-tertiary);
  margin-top: 4px;
}

.formula-editor {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  overflow: hidden;
}

.formula-input-wrapper {
  background: #1e1e2e;
}

.formula-input {
  width: 100%;
  padding: 16px;
  border: none;
  border-radius: 0;
  font-family: 'Fira Code', 'Consolas', monospace;
  font-size: 14px;
  color: #cdd6f4;
  background: transparent;
  outline: none;
  resize: vertical;
  min-height: 100px;
  line-height: 1.6;
}

.formula-hint {
  padding: 12px 16px;
  background: var(--color-bg-secondary);
  border-top: 1px solid var(--color-border);
  font-size: 12px;
  color: var(--color-text-secondary);
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
}

.hint-title {
  font-weight: 600;
  color: var(--color-text-primary);
  margin-right: 4px;
}

.formula-hint code {
  padding: 2px 6px;
  background: var(--color-bg-primary);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  font-size: 11px;
  font-family: 'Fira Code', 'Consolas', monospace;
  color: var(--color-primary);
}

/* Test section */
.test-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.test-variables {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.test-var-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.test-var-label {
  flex: 1;
  font-size: 13px;
  color: var(--color-text-primary);
  display: flex;
  align-items: center;
  gap: 8px;
}

.var-badge {
  padding: 2px 8px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 4px;
  font-size: 12px;
  font-family: 'Fira Code', monospace;
  color: #1d4ed8;
  font-weight: 600;
  white-space: nowrap;
}

.test-input {
  width: 120px;
  padding: 6px 10px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  font-size: 13px;
  text-align: right;
}

.test-input:focus {
  border-color: var(--color-primary);
  outline: none;
}

.test-result {
  padding: 16px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  gap: 12px;
}

.result-label {
  font-size: 13px;
  font-weight: 600;
  color: #166534;
}

.result-value {
  font-size: 20px;
  font-weight: 700;
  color: #166534;
}

.result-error {
  font-size: 14px;
  color: #dc2626;
  font-weight: 500;
}

.empty-hint {
  padding: 20px;
  text-align: center;
  color: var(--color-text-secondary);
  font-size: 13px;
}

/* Variables panel */
.variables-panel .panel-open {
  position: sticky;
  top: 24px;
}

.btn-add-var {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: var(--radius-sm);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.btn-add-var:hover { background: var(--color-primary-hover); }

.empty-state {
  text-align: center;
  padding: 32px 20px;
  color: var(--color-text-secondary);
}

.empty-state p {
  margin: 0 0 12px;
}

.btn-text {
  padding: 0;
  background: none;
  border: none;
  color: var(--color-primary);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.btn-text:hover { text-decoration: underline; }

.var-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.var-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  transition: background 0.15s;
}

.var-item:hover {
  background: var(--color-bg-secondary);
}

.var-item-header {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.var-source {
  font-size: 11px;
  color: var(--color-text-tertiary);
}

.var-item-body {
  flex: 1;
  min-width: 0;
}

.var-label {
  font-size: 13px;
  color: var(--color-text-primary);
  font-weight: 500;
}

.var-default {
  font-size: 11px;
  color: var(--color-text-tertiary);
}

.var-item-actions {
  display: flex;
  gap: 2px;
  flex-shrink: 0;
}

.btn-icon-sm {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: var(--radius-sm);
  cursor: pointer;
  background: transparent;
  color: var(--color-text-secondary);
  transition: all 0.15s;
}

.btn-icon-sm:hover { background: var(--color-bg-tertiary); }
.btn-icon-sm.btn-danger { color: #ef4444; }
.btn-icon-sm.btn-danger:hover { background: #fef2f2; }

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: var(--color-bg-card);
  border-radius: var(--radius-lg);
  width: 100%;
  max-width: 520px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid var(--color-border);
}

.modal-header h2 {
  font-size: 18px;
  font-weight: 700;
  color: var(--color-text-primary);
  margin: 0;
}

.btn-close {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  font-size: 22px;
  color: var(--color-text-secondary);
  cursor: pointer;
  border-radius: var(--radius-sm);
}

.btn-close:hover { background: var(--color-bg-secondary); }

.modal-body {
  padding: 24px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 24px;
  border-top: 1px solid var(--color-border);
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 16px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-secondary);
}

.required { color: #ef4444; }

.btn-secondary {
  padding: 10px 18px;
  background: var(--color-bg-secondary);
  color: var(--color-text-primary);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
}

.btn-secondary:hover { background: var(--color-bg-tertiary); }

@media (max-width: 900px) {
  .content-grid {
    grid-template-columns: 1fr;
  }
}
</style>
