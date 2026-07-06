<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { toast } from 'vue-sonner'
import { useRouter } from 'vue-router'
import Card from '../components/common/Card.vue'
import Button from '../components/common/Button.vue'
import { formTemplateApi, type FormField } from '@/api/formTemplate'
import { formulaApi, type FormulaVariable } from '@/api/formula'

const router = useRouter()

const formulaName = ref('')
const formulaDescription = ref('')
const formulaUnit = ref('Rupiah')
const currentFormulaId = ref<number | null>(null)

interface FormulaToken {
  id: string
  type: 'var' | 'op' | 'num' | 'paren' | 'func'
  value: string
  display?: string
  label?: string
}

const formulaTokens = ref<FormulaToken[]>([])
let tokenCounter = 0
function nextTokenId() { return `t${++tokenCounter}` }

const formulaExpression = computed(() => {
  return formulaTokens.value.map(t => {
    if (t.type === 'var') return t.value
    return t.value
  }).join('')
})

const variables = ref<FormulaVariable[]>([])
const testValues = ref<Record<string, number>>({})

const activeVarTab = ref<'pertanyaan' | 'instrumen'>('pertanyaan')
const varSource = ref<'berita_acara' | 'rekap_analis'>('berita_acara')
const varFormFieldId = ref<number | null>(null)
const pertanyaanDropdownOpen = ref(false)
const pertanyaanDropdownRef = ref<HTMLElement | null>(null)
const sumberDropdownOpen = ref(false)

function addInstrumentVariable() {
  const idx = variables.value.filter(v => v.source === 'instrumen').length + 1
  const name = `Instrumen ${idx}`
  const key = makeKey(name)
  variables.value.push({
    key, label: name, source: 'instrumen',
    form_field_id: null, instrument_id: null, default_value: null,
  })
  addVariableToken(key, name)
  toast.success(`Variabel "${name}" ditambahkan`)
}

function togglePertanyaanDropdown() {
  pertanyaanDropdownOpen.value = !pertanyaanDropdownOpen.value
}

function selectFormField(field: FormField) {
  varFormFieldId.value = field.id!
  pertanyaanDropdownOpen.value = false
}

function handleClickOutsidePertanyaan(e: MouseEvent) {
  const target = e.target as Node
  const wrapper = document.querySelector('.pf-dropdown-wrapper')
  if (wrapper && !wrapper.contains(target)) {
    pertanyaanDropdownOpen.value = false
    sumberDropdownOpen.value = false
  }
}

const selectedFieldLabel = computed(() => {
  const fields = availableFields(varSource.value)
  const field = fields.find(f => f.id === varFormFieldId.value)
  return field?.label || null
})

watch(varSource, () => {
  varFormFieldId.value = null
  pertanyaanDropdownOpen.value = false
})

const beritaAcaraFields = ref<FormField[]>([])
const rekapAnalisFields = ref<FormField[]>([])

function availableFields(source: string) {
  return source === 'berita_acara' ? beritaAcaraFields.value : rekapAnalisFields.value
}

async function loadData() {
  try {
    const [baRes, raRes] = await Promise.allSettled([
      formTemplateApi.getPublicActiveByType('berita_acara'),
      formTemplateApi.getPublicActiveByType('rekap_analis'),
    ])
    if (baRes.status === 'fulfilled' && baRes.value?.fields) {
      beritaAcaraFields.value = baRes.value.fields
    }
    if (raRes.status === 'fulfilled' && raRes.value?.fields) {
      rekapAnalisFields.value = raRes.value.fields
    }
  } catch {
    // silent
  }
}

function makeKey(label: string): string {
  return label
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_|_$/g, '')
}

function addToDisplay(text: string) {
  formulaTokens.value.push({ id: nextTokenId(), type: 'op', value: text })
}

function addVariableToken(key: string, label: string) {
  formulaTokens.value.push({ id: nextTokenId(), type: 'var', value: key, label })
}

function addNumberToken(num: string) {
  formulaTokens.value.push({ id: nextTokenId(), type: 'num', value: num })
}

function addFuncToken(fn: string) {
  formulaTokens.value.push({ id: nextTokenId(), type: 'func', value: fn })
}

function rebuildTokensFromExpression(expression: string, vars: FormulaVariable[]) {
  formulaTokens.value = []
  variables.value = []

  for (const v of vars) {
    variables.value.push(v)
    testValues.value[v.key] = v.default_value ?? 0
  }

  let remaining = expression
  const opMap: Record<string, string> = {
    '+': '+', '-': '-', '*': '*', '/': '/', '%': '%', '**': '**',
  }
  const displayMap: Record<string, string> = {
    '+': '+', '-': '−', '*': '×', '/': '÷', '%': '%', '**': '^',
  }

  while (remaining.length > 0) {
    remaining = remaining.trimStart()

    let matched = false

    for (const v of vars) {
      if (remaining.startsWith(v.key)) {
        formulaTokens.value.push({ id: nextTokenId(), type: 'var', value: v.key, label: v.label })
        remaining = remaining.slice(v.key.length)
        matched = true
        break
      }
    }
    if (matched) continue

    if (remaining.startsWith('Math.')) {
      const funcMatch = remaining.match(/^Math\.(round|ceil|floor|abs)\(\)/)
      if (funcMatch) {
        formulaTokens.value.push({ id: nextTokenId(), type: 'func', value: funcMatch[0] })
        remaining = remaining.slice(funcMatch[0].length)
        continue
      }
    }

    if (remaining.startsWith('(') || remaining.startsWith(')')) {
      formulaTokens.value.push({ id: nextTokenId(), type: 'paren', value: remaining[0], display: remaining[0] })
      remaining = remaining.slice(1)
      continue
    }

    let numMatch = remaining.match(/^(\d+\.?\d*)/)
    if (numMatch) {
      formulaTokens.value.push({ id: nextTokenId(), type: 'num', value: numMatch[1] })
      remaining = remaining.slice(numMatch[1].length)
      continue
    }

    let opFound = false
    for (const [op, display] of Object.entries(displayMap)) {
      if (remaining.startsWith(op)) {
        formulaTokens.value.push({ id: nextTokenId(), type: 'op', value: op, display })
        remaining = remaining.slice(op.length)
        opFound = true
        break
      }
    }
    if (opFound) continue

    if (remaining[0] === ' ') {
      remaining = remaining.slice(1)
      continue
    }

    formulaTokens.value.push({ id: nextTokenId(), type: 'num', value: remaining[0] })
    remaining = remaining.slice(1)
  }
}

async function loadLatestFormula() {
  try {
    const res = await formulaApi.getAll()
    const formulas = res.data
    if (Array.isArray(formulas) && formulas.length > 0) {
      const latest = formulas[0]
      currentFormulaId.value = latest.id
      formulaName.value = latest.name
      formulaDescription.value = latest.description || ''
      formulaUnit.value = latest.unit || 'Rupiah'
      rebuildTokensFromExpression(latest.expression, latest.variables || [])
    }
  } catch (e: any) {
    console.error('Gagal memuat rumus:', e)
  }
}

function addFromPertanyaan() {
  if (!varFormFieldId.value) {
    toast.error('Pilih pertanyaan terlebih dahulu')
    return
  }
  const fields = availableFields(varSource.value)
  const field = fields.find(f => f.id === varFormFieldId.value)
  if (!field) return

  const key = makeKey(field.label)
  if (variables.value.some(v => v.key === key)) {
    toast.error('Variabel dengan nama ini sudah ada')
    return
  }

  variables.value.push({
    key, label: field.label, source: varSource.value,
    form_field_id: field.id, instrument_id: null, default_value: 0,
  })
  testValues.value[key] = 0
  varFormFieldId.value = null
  addVariableToken(key, field.label)
  toast.success(`Variabel "${field.label}" ditambahkan`)
}

function removeVariable(idx: number) {
  const key = variables.value[idx].key
  delete testValues.value[key]
  variables.value.splice(idx, 1)
  formulaTokens.value = formulaTokens.value.filter(t => t.value !== key)
}

// Operations
const operations = [
  { label: '+', value: '+', display: '+' },
  { label: '−', value: '-', display: '−' },
  { label: '×', value: '*', display: '×' },
  { label: '÷', value: '/', display: '÷' },
  { label: '( )', value: '()' },
  { label: '^', value: '**', display: '^' },
  { label: '%', value: '%', display: '%' },
]

const mathFuncs = [
  { label: 'round', value: 'Math.round()' },
  { label: 'ceil', value: 'Math.ceil()' },
  { label: 'floor', value: 'Math.floor()' },
  { label: 'abs', value: 'Math.abs()' },
]

function insertOp(op: string) {
  if (op === '()') {
    const openCount = formulaTokens.value.filter(t => t.value === '(').length
    const closeCount = formulaTokens.value.filter(t => t.value === ')').length
    if (openCount > closeCount) {
      formulaTokens.value.push({ id: nextTokenId(), type: 'paren', value: ')', display: ')' })
    } else {
      formulaTokens.value.push({ id: nextTokenId(), type: 'paren', value: '(', display: '(' })
    }
  } else if (op.startsWith('Math.')) {
    addFuncToken(op)
  } else {
    const opDef = operations.find(o => o.value === op)
    formulaTokens.value.push({ id: nextTokenId(), type: 'op', value: opDef?.value || op, display: opDef?.display || op })
  }
}

function insertVar(key: string) {
  const v = variables.value.find(v => v.key === key)
  if (v) addVariableToken(key, v.label)
}

function removeToken(id: string) {
  const token = formulaTokens.value.find(t => t.id === id)
  if (token?.type === 'var') {
    const idx = variables.value.findIndex(v => v.key === token.value)
    if (idx !== -1) {
      delete testValues.value[variables.value[idx].key]
      variables.value.splice(idx, 1)
    }
  }
  formulaTokens.value = formulaTokens.value.filter(t => t.id !== id)
}

function clearDisplay() {
  formulaTokens.value = []
}

function backspace() {
  if (formulaTokens.value.length > 0) {
    formulaTokens.value.pop()
  }
}

// Calculation
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
  for (const v of variables.value) {
    const val = testValues.value[v.key] ?? v.default_value ?? 0
    expr = expr.replace(new RegExp(`\\b${v.key}\\b`, 'g'), val.toString())
  }
  if (/[^0-9+\-*/().%,\s]|Math\./.test(expr.replace(/Math\.(round|ceil|floor|abs|min|max|pow|sqrt)/g, ''))) {
    throw new Error('Rumus mengandung karakter yang tidak diizinkan')
  }
  const result = new Function(`return (${expr})`)()
  if (typeof result !== 'number' || isNaN(result)) {
    throw new Error('Hasil perhitungan bukan angka yang valid')
  }
  return result
}

function formatPrice(val: number) {
  return new Intl.NumberFormat('id-ID').format(Math.round(val))
}

async function saveFormula() {
  if (!formulaExpression.value) {
    toast.error('Rumus wajib diisi')
    return
  }
  const name = formulaName.value || formulaExpression.value.slice(0, 50)
  try {
    const payload = {
      name,
      description: formulaDescription.value,
      expression: formulaExpression.value,
      unit: formulaUnit.value,
      variables: variables.value,
    }
    if (currentFormulaId.value) {
      await formulaApi.update(currentFormulaId.value, payload)
    } else {
      await formulaApi.create(payload)
    }
    toast.success('Rumus berhasil disimpan')
    router.push('/admin/forms')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menyimpan rumus')
  }
}

onMounted(() => {
  loadData()
  loadLatestFormula()
  document.addEventListener('click', handleClickOutsidePertanyaan)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutsidePertanyaan)
})
</script>

<template>
    <div>
      <div class="page-header">
        <h1>Perhitungan Biaya</h1>
        <Button @click="saveFormula">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
          Simpan Rumus
        </Button>
      </div>

      <!-- Calculator Display -->
      <Card>
        <div class="calc-display">
          <div class="display-header">
            <span class="display-label">Rumus</span>
            <div class="display-actions">
              <button class="display-action" @click="backspace" title="Hapus token terakhir">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 4H8l-7 8 7 8h13a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/><line x1="18" y1="9" x2="12" y2="15"/><line x1="12" y1="9" x2="18" y2="15"/></svg>
              </button>
              <button class="display-action display-action--clear" @click="clearDisplay" title="Hapus semua">
                Clear
              </button>
            </div>
          </div>
          <div class="display-screen" :class="{ 'has-value': formulaTokens.length > 0 }">
            <div v-if="formulaTokens.length === 0" class="display-placeholder">
              Rumus akan muncul di sini...
            </div>
            <div v-else class="display-tokens">
              <template v-for="token in formulaTokens" :key="token.id">
                <span v-if="token.type === 'var'" class="token-chip" :title="token.label || token.value">
                  {{ token.label || token.value }}
                  <button class="token-chip-remove" @click="removeToken(token.id)" title="Hapus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                  </button>
                </span>
                <span v-else-if="token.type === 'func'" class="token-func">{{ token.value }}</span>
                <span v-else class="token-op">{{ token.display || token.value }}</span>
              </template>
            </div>
          </div>
          <div class="display-preview" v-if="formulaExpression">
            <span class="preview-label">Preview:</span>
            <code class="preview-formula">{{ formulaExpression }}</code>
          </div>
        </div>
      </Card>

      <!-- Keyboard -->
      <Card>
        <div class="split-grid">
          <!-- Kiri: Variabel -->
          <div class="var-panel">
            <div class="panel-header">
              <h3 class="panel-title">Variabel</h3>
              <span class="var-count">{{ variables.length }} variabel</span>
            </div>

            <div class="var-tabs">
              <button
                class="var-tab"
                :class="{ active: activeVarTab === 'pertanyaan' }"
                @click="activeVarTab = 'pertanyaan'"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
                Pertanyaan
              </button>
              <button
                class="var-tab"
                :class="{ active: activeVarTab === 'instrumen' }"
                @click="activeVarTab = 'instrumen'"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                Instrumen
              </button>
            </div>

            <!-- Tab: Pertanyaan -->
            <div v-if="activeVarTab === 'pertanyaan'" class="tab-content">
              <div class="form-group">
                <label>Sumber</label>
                <div ref="pertanyaanDropdownRef" class="pf-dropdown-wrapper">
                  <button
                    type="button"
                    class="pf-dropdown-trigger"
                    :class="{ open: sumberDropdownOpen }"
                    @click.stop="sumberDropdownOpen = !sumberDropdownOpen"
                  >
                    <span class="pf-dropdown-value">{{ varSource === 'berita_acara' ? 'Form Berita Acara' : 'Form Rekap Analis' }}</span>
                    <svg class="pf-dropdown-chevron" :class="{ open: sumberDropdownOpen }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                  </button>
                  <Transition name="pf-dropdown">
                    <div v-if="sumberDropdownOpen" class="pf-dropdown-menu">
                      <div
                        class="pf-dropdown-item"
                        :class="{ active: varSource === 'berita_acara' }"
                        @click="varSource = 'berita_acara'; sumberDropdownOpen = false"
                      >
                        <span class="pf-dropdown-item-label">Form Berita Acara</span>
                        <svg v-if="varSource === 'berita_acara'" class="pf-dropdown-check" width="16" height="16" viewBox="0 0 16 16" fill="none">
                          <path d="M3.5 8.5L6.5 11.5L12.5 4.5" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </div>
                      <div
                        class="pf-dropdown-item"
                        :class="{ active: varSource === 'rekap_analis' }"
                        @click="varSource = 'rekap_analis'; sumberDropdownOpen = false"
                      >
                        <span class="pf-dropdown-item-label">Form Rekap Analis</span>
                        <svg v-if="varSource === 'rekap_analis'" class="pf-dropdown-check" width="16" height="16" viewBox="0 0 16 16" fill="none">
                          <path d="M3.5 8.5L6.5 11.5L12.5 4.5" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </div>
                    </div>
                  </Transition>
                </div>
              </div>
              <div class="form-group">
                <label>Pertanyaan dari Form</label>
                <div ref="pertanyaanDropdownRef" class="pf-dropdown-wrapper">
                  <button
                    type="button"
                    class="pf-dropdown-trigger"
                    :class="{ open: pertanyaanDropdownOpen }"
                    @click.stop="togglePertanyaanDropdown"
                  >
                    <span v-if="selectedFieldLabel" class="pf-dropdown-value">{{ selectedFieldLabel }}</span>
                    <span v-else class="pf-dropdown-placeholder">Pilih pertanyaan...</span>
                    <svg class="pf-dropdown-chevron" :class="{ open: pertanyaanDropdownOpen }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                  </button>
                  <Transition name="pf-dropdown">
                    <div v-if="pertanyaanDropdownOpen" class="pf-dropdown-menu">
                      <div v-if="availableFields(varSource).length === 0" class="pf-dropdown-empty">
                        Tidak ada pertanyaan tersedia
                      </div>
                      <div
                        v-for="f in availableFields(varSource)"
                        :key="f.id"
                        class="pf-dropdown-item"
                        :class="{ active: varFormFieldId === f.id }"
                        @click="selectFormField(f)"
                      >
                        <span class="pf-dropdown-item-label">{{ f.label }}</span>
                        <span class="pf-dropdown-item-type">{{ f.type }}</span>
                        <svg v-if="varFormFieldId === f.id" class="pf-dropdown-check" width="16" height="16" viewBox="0 0 16 16" fill="none">
                          <path d="M3.5 8.5L6.5 11.5L12.5 4.5" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </div>
                    </div>
                  </Transition>
                </div>
              </div>
              <Button variant="outline" size="sm" block @click="addFromPertanyaan" :disabled="!varFormFieldId">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Tambah Variabel
              </Button>
            </div>

            <!-- Tab: Instrumen -->
            <div v-if="activeVarTab === 'instrumen'" class="tab-content">
              <p class="instrumen-hint">Tambah variabel instrumen. Harga akan diambil dari daftar instrumen saat perhitungan.</p>
              <Button variant="outline" size="sm" block @click="addInstrumentVariable">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Tambah Instrumen
              </Button>
            </div>

            <!-- Daftar Variabel -->
            <div v-if="variables.length > 0" class="var-list-section">
              <div class="var-list-header">Daftar Variabel</div>
              <div class="var-list">
                <div v-for="(v, idx) in variables" :key="v.key" class="var-chip" @click="insertVar(v.key)">
                  <span class="var-chip-badge">{{ v.key }}</span>
                  <span class="var-chip-label">{{ v.label }}</span>
                  <span class="var-chip-source">
                    {{ v.source === 'berita_acara' ? 'BA' : v.source === 'rekap_analis' ? 'RA' : 'INS' }}
                  </span>
                  <button class="var-chip-remove" @click.stop="removeVariable(idx)" title="Hapus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Kanan: Operasi -->
          <div class="ops-panel">
            <div class="panel-header">
              <h3 class="panel-title">Operasi</h3>
            </div>

            <!-- Operator -->
            <div class="ops-section">
              <div class="ops-label">Operator</div>
              <div class="ops-grid">
                <button v-for="op in operations" :key="op.label" class="ops-btn" @click="insertOp(op.value)">
                  {{ op.label }}
                </button>
              </div>
            </div>

            <!-- Math Functions -->
            <div class="ops-section">
              <div class="ops-label">Fungsi Math</div>
              <div class="ops-grid ops-grid--4">
                <button v-for="fn in mathFuncs" :key="fn.label" class="ops-btn ops-btn--fn" @click="insertOp(fn.value)">
                  {{ fn.label }}
                </button>
              </div>
            </div>

            <!-- Number Pad -->
            <div class="ops-section">
              <div class="ops-label">Angka</div>
              <div class="ops-grid ops-grid--5">
                <button v-for="n in 10" :key="n-1" class="ops-btn ops-btn--num" @click="addNumberToken(String(n-1))">
                  {{ n - 1 }}
                </button>
                <button class="ops-btn ops-btn--num" @click="addNumberToken('.')">.</button>
                <button class="ops-btn ops-btn--num" @click="addNumberToken('00')">00</button>
              </div>
            </div>

            <!-- Quick Values -->
            <div class="ops-section">
              <div class="ops-label">Nilai Cepat</div>
              <div class="ops-grid ops-grid--4">
                <button class="ops-btn ops-btn--quick" @click="addNumberToken('100')">100</button>
                <button class="ops-btn ops-btn--quick" @click="addNumberToken('1000')">1000</button>
                <button class="ops-btn ops-btn--quick" @click="addNumberToken('10000')">10000</button>
                <button class="ops-btn ops-btn--quick" @click="addNumberToken('100000')">100000</button>
              </div>
            </div>
          </div>
        </div>
      </Card>

      <!-- Card 4: Preview & Test -->
      <Card title="Preview & Test">
        <div v-if="variables.length === 0" class="empty-hint">
          Tambahkan variabel terlebih dahulu untuk bisa menguji rumus
        </div>
        <div v-else class="test-section">
          <div class="test-variables">
            <div v-for="v in variables" :key="v.key" class="test-var-row">
              <label class="test-var-label">
                <span class="var-badge">{{ v.key }}</span>
                <span class="test-var-info">
                  <span class="test-var-name">{{ v.label }}</span>
                  <span class="test-var-source">
                    {{ v.source === 'berita_acara' ? 'Form Berita Acara' : v.source === 'rekap_analis' ? 'Form Rekap Analis' : 'Instrumen' }}
                  </span>
                </span>
              </label>
              <div class="test-var-right">
                <span v-if="v.source === 'instrumen'" class="test-default"> dari daftar instrumen</span>
                <input
                  v-if="v.source !== 'instrumen'"
                  type="number"
                  v-model.number="testValues[v.key]"
                  class="test-input"
                  :placeholder="v.default_value?.toString() || '0'"
                />
                <span v-else class="test-input-placeholder">otomatis</span>
              </div>
            </div>
          </div>
          <div class="test-result" v-if="formulaExpression">
            <div class="result-left">
              <div class="result-label">Rumus:</div>
              <code class="result-formula">{{ formulaExpression }}</code>
            </div>
            <div class="result-right">
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
      </Card>
    </div>
</template>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}
.page-header h1 {
  font-size: 24px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

/* Calculator Display */
.calc-display {
  display: flex;
  flex-direction: column;
}
.display-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}
.display-label {
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}
.display-actions {
  display: flex;
  gap: 6px;
}
.display-action {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: white;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s;
}
.display-action:hover {
  background: #f1f5f9;
  color: #1e293b;
}
.display-action--clear {
  width: auto;
  padding: 0 12px;
  font-size: 12px;
  font-weight: 600;
  color: #dc2626;
  border-color: #fecaca;
}
.display-action--clear:hover {
  background: #fef2f2;
}

.display-screen {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: white;
  min-height: 120px;
  overflow: hidden;
  transition: border-color 0.2s;
  position: relative;
}
.display-screen.has-value {
  border-color: #3b82f6;
}
.display-placeholder {
  padding: 20px;
  color: #cbd5e1;
  font-size: 15px;
  font-family: inherit;
}
.display-tokens {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  padding: 16px;
  min-height: 88px;
}

/* Variable Chip */
.token-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  background: white;
  border: 1.5px solid #e2e8f0;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
  white-space: nowrap;
  transition: all 0.15s;
  cursor: default;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}
.token-chip:hover {
  border-color: #cbd5e1;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}
.token-chip-remove {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  border: none;
  background: #f1f5f9;
  border-radius: 4px;
  color: #94a3b8;
  cursor: pointer;
  padding: 0;
  flex-shrink: 0;
  transition: all 0.15s;
}
.token-chip-remove:hover {
  background: #dc2626;
  color: white;
}

/* Operator Token */
.token-op {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 32px;
  padding: 0 6px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 16px;
  font-weight: 700;
  color: #475569;
  font-family: 'Fira Code', monospace;
}
.token-func {
  display: inline-flex;
  align-items: center;
  padding: 0 8px;
  height: 32px;
  background: #f5f3ff;
  border: 1px solid #e9d5ff;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #7c3aed;
  font-family: 'Fira Code', monospace;
}
.display-preview {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 10px;
  padding: 8px 12px;
  background: #f8fafc;
  border-radius: 8px;
}
.preview-label {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.preview-formula {
  font-size: 13px;
  font-family: 'Fira Code', monospace;
  color: #1e293b;
  word-break: break-all;
}

/* Card 3: Keyboard */
.split-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}
.panel-title {
  font-size: 15px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}
.var-count {
  font-size: 12px;
  color: #94a3b8;
  background: #f1f5f9;
  padding: 2px 10px;
  border-radius: 100px;
}

/* Variable Tabs */
.var-tabs {
  display: flex;
  gap: 4px;
  background: #f1f5f9;
  border-radius: 10px;
  padding: 4px;
  margin-bottom: 16px;
}
.var-tab {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px 0;
  border: none;
  background: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s;
  font-family: inherit;
}
.var-tab:hover { color: #1e293b; }
.var-tab.active {
  background: white;
  color: #1e293b;
  font-weight: 600;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.tab-content { margin-bottom: 16px; }

/* Form */
.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 12px;
}
.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}
.required { color: #ef4444; }
.form-input,
.form-textarea {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  color: #1e293b;
  background: white;
  outline: none;
  transition: border-color 0.15s;
  font-family: inherit;
}
.form-input:focus,
.form-textarea:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
}

/* Pertanyaan Custom Dropdown */
.pf-dropdown-wrapper {
  position: relative;
}
.pf-dropdown-trigger {
  width: 100%;
  padding: 10px 14px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  font-family: inherit;
  color: #1e293b;
  background: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: border-color 0.2s, box-shadow 0.2s;
  outline: none;
  text-align: left;
}
.pf-dropdown-trigger:hover {
  border-color: #cbd5e1;
}
.pf-dropdown-trigger:focus,
.pf-dropdown-trigger.open {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}
.pf-dropdown-value {
  flex: 1;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.pf-dropdown-placeholder {
  flex: 1;
  color: #94a3b8;
}
.pf-dropdown-chevron {
  color: #94a3b8;
  transition: transform 0.2s;
  flex-shrink: 0;
}
.pf-dropdown-chevron.open {
  transform: rotate(180deg);
}
.pf-dropdown-menu {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: #fff;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
  z-index: 50;
  overflow: hidden;
  max-height: 260px;
  overflow-y: auto;
}
.pf-dropdown-empty {
  padding: 20px 14px;
  text-align: center;
  color: #94a3b8;
  font-size: 13px;
}
.pf-dropdown-item {
  padding: 10px 14px;
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  font-size: 14px;
  color: #1e293b;
  transition: background 0.15s;
  border-bottom: 1px solid #f1f5f9;
}
.pf-dropdown-item:last-child {
  border-bottom: none;
}
.pf-dropdown-item:hover {
  background: #f8fafc;
}
.pf-dropdown-item.active {
  background: #eff6ff;
}
.pf-dropdown-item-label {
  flex: 1;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.pf-dropdown-item-type {
  font-size: 11px;
  color: #94a3b8;
  background: #f1f5f9;
  padding: 1px 8px;
  border-radius: 10px;
  flex-shrink: 0;
}
.pf-dropdown-check {
  flex-shrink: 0;
  margin-left: 4px;
}
.pf-dropdown-enter-active,
.pf-dropdown-leave-active {
  transition: opacity 0.15s, transform 0.15s;
}
.pf-dropdown-enter-from,
.pf-dropdown-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

/* Instrumen Hint */
.instrumen-hint {
  font-size: 12px;
  color: #94a3b8;
  margin-bottom: 12px;
  line-height: 1.5;
}

/* Variable List */
.var-list-section {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}
.var-list-header {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 10px;
}
.var-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.var-chip {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s;
}
.var-chip:hover {
  background: #eff6ff;
  border-color: #bfdbfe;
}
.var-chip-badge {
  padding: 2px 8px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 4px;
  font-size: 11px;
  font-family: 'Fira Code', monospace;
  color: #1d4ed8;
  font-weight: 600;
  white-space: nowrap;
}
.var-chip-label {
  flex: 1;
  font-size: 12px;
  color: #1e293b;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.var-chip-source {
  font-size: 10px;
  color: #94a3b8;
  background: #e2e8f0;
  padding: 1px 6px;
  border-radius: 4px;
  font-weight: 600;
}
.var-chip-remove {
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  color: #94a3b8;
  cursor: pointer;
  border-radius: 4px;
}
.var-chip-remove:hover {
  background: #fee2e2;
  color: #ef4444;
}

/* Operations Panel */
.ops-panel {
  border-left: 1px solid #e2e8f0;
  padding-left: 24px;
}

.ops-section { margin-bottom: 14px; }
.ops-label {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}
.ops-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 6px;
}
.ops-grid--5 {
  grid-template-columns: repeat(5, 1fr);
}

.ops-btn {
  padding: 12px 0;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  color: #1e293b;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.12s;
  font-family: inherit;
}
.ops-btn:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}
.ops-btn:active {
  transform: scale(0.93);
  background: #e2e8f0;
}
.ops-btn--fn {
  font-size: 12px;
  color: #7c3aed;
  background: #f5f3ff;
  border-color: #e9d5ff;
}
.ops-btn--fn:hover { background: #ede9fe; }
.ops-btn--fn:active { background: #ddd6fe; }
.ops-btn--num {
  font-size: 14px;
  color: #475569;
}
.ops-btn--num:hover { background: #f8fafc; }
.ops-btn--quick {
  font-size: 12px;
  color: #0369a1;
  background: #f0f9ff;
  border-color: #bae6fd;
}
.ops-btn--quick:hover { background: #e0f2fe; }

/* Preview & Test */
.test-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.test-variables {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.test-var-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}
.test-var-label {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}
.var-badge {
  padding: 2px 8px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 4px;
  font-size: 11px;
  font-family: 'Fira Code', monospace;
  color: #1d4ed8;
  font-weight: 600;
  white-space: nowrap;
}
.test-var-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.test-var-name {
  font-size: 13px;
  color: #1e293b;
  font-weight: 500;
}
.test-var-source {
  font-size: 11px;
  color: #94a3b8;
}
.test-var-right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}
.test-default {
  font-size: 11px;
  color: #059669;
  white-space: nowrap;
}
.test-input {
  width: 120px;
  padding: 6px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 13px;
  text-align: right;
  background: white;
}
.test-input:focus {
  border-color: #3b82f6;
  outline: none;
}
.test-input-placeholder {
  width: 120px;
  padding: 6px 10px;
  text-align: right;
  font-size: 13px;
  color: #94a3b8;
  font-style: italic;
}

.test-result {
  padding: 16px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}
.result-left {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}
.result-label {
  font-size: 12px;
  font-weight: 600;
  color: #166534;
}
.result-formula {
  font-size: 13px;
  font-family: 'Fira Code', monospace;
  color: #166534;
  background: rgba(255,255,255,0.6);
  padding: 4px 8px;
  border-radius: 4px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.result-right {
  text-align: right;
  flex-shrink: 0;
}
.result-value {
  font-size: 24px;
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
  color: #64748b;
  font-size: 13px;
}

@media (max-width: 900px) {
  .split-grid { grid-template-columns: 1fr; }
  .ops-panel {
    border-left: none;
    padding-left: 0;
    border-top: 1px solid #e2e8f0;
    padding-top: 24px;
  }
  .info-grid { grid-template-columns: 1fr; }
}
</style>
