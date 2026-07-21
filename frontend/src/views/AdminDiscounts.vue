<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import Modal from '../components/common/Modal.vue'
import Button from '../components/common/Button.vue'
import SkeletonTable from '../components/common/SkeletonTable.vue'
import FormInput from '../components/common/FormInput.vue'
import FormSelect from '../components/common/FormSelect.vue'
import { Search, Eye, Trash2 } from '@lucide/vue'
import { adminApi } from '../api/admin'
import { toast } from 'vue-sonner'

interface DiscountItem {
  id: number
  judul_penelitian: string
  pemohon: string
  biaya: number
  diskon_diizinkan: boolean
  payment: {
    id: number
    amount: number
    discount_approved: number | null
    discount_status: string | null
    discount_reason: string | null
    discount_percent: number | null
    discount_nominal: number | null
    status: string
  } | null
  has_discount: boolean
  created_at: string
}

const items = ref<DiscountItem[]>([])
const loading = ref(true)
const search = ref('')
const showModal = ref(false)
const saving = ref(false)
const removing = ref(false)

const selectedItem = ref<DiscountItem | null>(null)
const discountMode = ref('percent')
const discountPercent = ref('')
const discountNominal = ref('')
const discountReason = ref('')

const discountModeOptions = [
  { value: 'percent', label: 'Persen (%)' },
  { value: 'nominal', label: 'Manual (Rp)' },
  { value: 'both', label: 'Persen + Manual' },
]

watch(discountMode, (mode) => {
  if (mode === 'percent') discountNominal.value = ''
  if (mode === 'nominal') discountPercent.value = ''
})

const filtered = computed(() => {
  if (!search.value) return items.value
  const q = search.value.toLowerCase()
  return items.value.filter(i =>
    i.judul_penelitian?.toLowerCase().includes(q) ||
    i.pemohon?.toLowerCase().includes(q) ||
    String(i.id).includes(q)
  )
})

const totalBiaya = computed(() => items.value.reduce((s, i) => s + Number(i.biaya || 0), 0))
const totalDiskon = computed(() => items.value.reduce((s, i) => s + Number(i.payment?.discount_approved || 0), 0))
const countWithDiscount = computed(() => items.value.filter(i => i.has_discount).length)

function formatPrice(val: number) {
  return new Intl.NumberFormat('id-ID').format(Math.round(val))
}

const percentAmount = computed(() => {
  const biaya = selectedItem.value?.biaya || 0
  const pct = Number(discountPercent.value) || 0
  return Math.round(biaya * pct / 100)
})

const nominalAmount = computed(() => Number(discountNominal.value) || 0)

const totalDiscount = computed(() => percentAmount.value + nominalAmount.value)

const sisaBayar = computed(() => {
  const biaya = selectedItem.value?.biaya || 0
  return Math.max(0, biaya - totalDiscount.value)
})

const maxNominal = computed(() => {
  const biaya = selectedItem.value?.biaya || 0
  return Math.max(0, biaya - percentAmount.value)
})

const hasAnyInput = computed(() => {
  if (discountMode.value === 'percent') return (Number(discountPercent.value) || 0) > 0
  if (discountMode.value === 'nominal') return (Number(discountNominal.value) || 0) > 0
  return (Number(discountPercent.value) || 0) > 0 || (Number(discountNominal.value) || 0) > 0
})

const isOverMax = computed(() => {
  if (discountMode.value === 'percent' || discountMode.value === 'both') {
    if (Number(discountPercent.value) > 100) return true
  }
  if (discountMode.value === 'nominal') {
    if (nominalAmount.value > Number(selectedItem.value?.biaya || 0)) return true
  }
  if (discountMode.value === 'both') {
    if (nominalAmount.value > maxNominal.value) return true
  }
  return false
})

function openDetail(item: DiscountItem) {
  selectedItem.value = item
  if (item.payment?.discount_approved) {
    discountPercent.value = item.payment.discount_percent ? String(item.payment.discount_percent) : ''
    discountNominal.value = item.payment.discount_nominal ? String(item.payment.discount_nominal) : ''
    discountReason.value = item.payment.discount_reason || ''
    const hasPct = !!item.payment.discount_percent
    const hasNom = !!item.payment.discount_nominal
    if (hasPct && hasNom) discountMode.value = 'both'
    else if (hasPct) discountMode.value = 'percent'
    else discountMode.value = 'nominal'
  } else {
    discountPercent.value = ''
    discountNominal.value = ''
    discountReason.value = ''
    discountMode.value = 'percent'
  }
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  selectedItem.value = null
}

async function handleSave() {
  if (!selectedItem.value) return
  if (!hasAnyInput.value) {
    return toast.error('Isi minimal satu diskon')
  }

  saving.value = true
  try {
    const payload: any = {
      discount_percent: discountMode.value === 'nominal' ? 0 : (Number(discountPercent.value) || 0),
      discount_nominal: discountMode.value === 'percent' ? 0 : (Number(discountNominal.value) || 0),
      discount_reason: discountReason.value || undefined,
    }
    if (selectedItem.value.has_discount && selectedItem.value.payment?.id) {
      await adminApi.updateDiscount(selectedItem.value.payment.id, payload)
      toast.success('Diskon berhasil diperbarui')
    } else {
      await adminApi.assignDiscount({ berita_acara_id: selectedItem.value.id, ...payload })
      toast.success('Diskon berhasil diberikan')
    }
    await loadData()
    closeModal()
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menyimpan diskon')
  } finally {
    saving.value = false
  }
}

async function handleReject() {
  if (!selectedItem.value?.payment?.id) return
  removing.value = true
  try {
    await adminApi.deleteDiscount(selectedItem.value.payment.id)
    toast.success('Diskon berhasil dihapus')
    await loadData()
    closeModal()
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menghapus diskon')
  } finally {
    removing.value = false
  }
}

async function loadData() {
  loading.value = true
  try {
    items.value = await adminApi.getDiscounts()
  } catch {
    toast.error('Gagal memuat data')
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>

<template>
  <AppLayout>
    <div>
      <div class="page-header">
        <div>
          <h1>Kelola Diskon</h1>
          <p class="subtitle">Beri diskon (persen dan/atau nominal) untuk berita acara yang sudah disetujui</p>
        </div>
      </div>

      <div class="stats-row">
        <div class="stat-card">
          <span class="stat-label">Total Berita Acara</span>
          <span class="stat-value">{{ items.length }}</span>
        </div>
        <div class="stat-card stat-card--accent">
          <span class="stat-label">Sudah Diberi Diskon</span>
          <span class="stat-value">{{ countWithDiscount }}</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Total Biaya</span>
          <span class="stat-value">Rp {{ formatPrice(totalBiaya) }}</span>
        </div>
        <div class="stat-card stat-card--green">
          <span class="stat-label">Total Diskon</span>
          <span class="stat-value">Rp {{ formatPrice(totalDiskon) }}</span>
        </div>
      </div>

      <Card>
        <div class="filter-bar">
          <div class="search-box">
            <Search :size="16" />
            <input v-model="search" type="text" placeholder="Cari judul, pemohon, atau ID..." />
          </div>
        </div>

        <SkeletonTable v-if="loading" :cols="6" :rows="5" />

        <div v-else class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Judul Penelitian</th>
                <th>Pemohon</th>
                <th>Biaya</th>
                <th>Diskon</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filtered" :key="item.id">
                <td>#{{ item.id }}</td>
                <td class="cell-title">{{ item.judul_penelitian || '-' }}</td>
                <td>{{ item.pemohon }}</td>
                <td>Rp {{ formatPrice(Number(item.biaya || 0)) }}</td>
                <td>
                  <template v-if="item.has_discount">
                    <Badge type="success" text="Aktif" />
                    <span class="discount-detail">
                      <template v-if="item.payment?.discount_percent">{{ item.payment.discount_percent }}%</template>
                      <template v-if="item.payment?.discount_percent && item.payment?.discount_nominal"> + </template>
                      <template v-if="item.payment?.discount_nominal">Rp {{ formatPrice(Number(item.payment.discount_nominal)) }}</template>
                    </span>
                  </template>
                  <span v-else class="text-muted">Belum ada diskon</span>
                </td>
                <td>
                  <button class="icon-btn" title="Lihat Detail" @click="openDetail(item)">
                    <Eye :size="16" />
                  </button>
                </td>
              </tr>
              <tr v-if="filtered.length === 0">
                <td colspan="6" class="empty-cell">Tidak ada data</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <!-- Modal Detail Diskon -->
      <Modal :show="showModal" title="Detail Diskon" size="sm" @close="closeModal">
        <div v-if="selectedItem" class="discount-form">
          <div class="form-info">
            <p><strong>#{{ selectedItem.id }}</strong> — {{ selectedItem.judul_penelitian || 'Tanpa judul' }}</p>
            <p class="form-info__pemohon">{{ selectedItem.pemohon }}</p>
          </div>

          <div class="form-biaya">
            <span>Biaya</span>
            <strong>Rp {{ formatPrice(Number(selectedItem.biaya || 0)) }}</strong>
          </div>

          <FormSelect
            v-model="discountMode"
            label="Tipe Diskon"
            :options="discountModeOptions"
          />

          <template v-if="discountMode === 'percent' || discountMode === 'both'">
            <FormInput
              v-model="discountPercent"
              label="Diskon Persen (%)"
              type="number"
              placeholder="Contoh: 10"
            />
            <p v-if="Number(discountPercent) > 100" class="form-error-msg">Maksimal 100%</p>
            <p v-else-if="Number(discountPercent) > 0" class="form-hint">
              = Rp {{ formatPrice(percentAmount) }}
            </p>
          </template>

          <template v-if="discountMode === 'nominal' || discountMode === 'both'">
            <FormInput
              v-model="discountNominal"
              label="Diskon Manual (Rp)"
              type="number"
              placeholder="Contoh: 50000"
            />
            <p v-if="discountMode === 'both' && nominalAmount > maxNominal" class="form-error-msg">
              Melebihi sisa biaya (maks Rp {{ formatPrice(maxNominal) }})
            </p>
            <p v-else-if="discountMode === 'nominal' && nominalAmount > Number(selectedItem.biaya || 0)" class="form-error-msg">
              Tidak boleh melebihi biaya
            </p>
          </template>

          <div v-if="hasAnyInput" class="form-preview">
            <div class="form-preview__row" v-if="Number(discountPercent) > 0">
              <span>Persentase ({{ discountPercent }}%)</span>
              <span>- Rp {{ formatPrice(percentAmount) }}</span>
            </div>
            <div class="form-preview__row" v-if="Number(discountNominal) > 0">
              <span>Nominal</span>
              <span>- Rp {{ formatPrice(nominalAmount) }}</span>
            </div>
            <div class="form-preview__row form-preview__row--total">
              <span>Total Diskon</span>
              <span>- Rp {{ formatPrice(totalDiscount) }}</span>
            </div>
            <div class="form-preview__row form-preview__row--sisa">
              <span>Sisa Bayar</span>
              <span>Rp {{ formatPrice(sisaBayar) }}</span>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Catatan (opsional)</label>
            <textarea
              v-model="discountReason"
              class="form-textarea"
              rows="2"
              placeholder="Alasan pemberian diskon..."
            />
          </div>
        </div>

        <template #footer>
          <div class="modal-footer">
            <div class="modal-footer__left">
              <Button
                v-if="selectedItem?.has_discount"
                variant="danger"
                :loading="removing"
                @click="handleReject"
              >
                <Trash2 :size="14" />
                Hapus Diskon
              </Button>
            </div>
            <div class="modal-footer__right">
              <Button variant="secondary" @click="closeModal">Tutup</Button>
              <Button
                variant="primary"
                :loading="saving"
                :disabled="!hasAnyInput || isOverMax"
                @click="handleSave"
              >
                {{ selectedItem?.has_discount ? 'Perbarui' : 'Simpan' }}
              </Button>
            </div>
          </div>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-header { margin-bottom: 20px; }
.page-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
.subtitle { font-size: 14px; color: #64748b; margin: 4px 0 0; }

.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px; }
.stat-card {
  background: #fff; border: 1.5px solid #e2e8f0; border-radius: 12px;
  padding: 16px 20px; display: flex; flex-direction: column; gap: 4px;
}
.stat-card--accent { border-color: #2563eb; background: #eff6ff; }
.stat-card--green { border-color: #059669; background: #ecfdf5; }
.stat-label { font-size: 12px; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px; }
.stat-value { font-size: 18px; font-weight: 700; color: #1e293b; }

.filter-bar { margin-bottom: 16px; }
.search-box {
  display: flex; align-items: center; gap: 8px;
  padding: 8px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px;
  background: #fff; max-width: 320px; color: #94a3b8;
}
.search-box input { border: none; outline: none; font-size: 14px; flex: 1; color: #1e293b; background: transparent; }

.table-wrap { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; font-size: 14px; }
.table th { text-align: left; padding: 12px; background: #f8fafc; color: #475569; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
.table td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
.cell-title { max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.empty-cell { text-align: center; color: #94a3b8; padding: 40px; }
.text-muted { color: #94a3b8; font-size: 13px; }

.discount-detail { font-size: 13px; color: #059669; font-weight: 500; margin-left: 6px; }

.icon-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 32px; height: 32px; border: 1.5px solid #e2e8f0; border-radius: 8px;
  background: #fff; color: #475569; cursor: pointer; transition: all 0.15s;
}
.icon-btn:hover { background: #f1f5f9; border-color: #cbd5e1; color: #1e293b; }

/* Modal form */
.discount-form { display: flex; flex-direction: column; gap: 14px; }
.form-info { padding: 12px; background: #f8fafc; border-radius: 10px; font-size: 14px; color: #334155; }
.form-info__pemohon { font-size: 13px; color: #64748b; margin: 4px 0 0; }
.form-biaya {
  display: flex; justify-content: space-between; align-items: center;
  padding: 12px 16px; background: #fefce8; border: 1px solid #fde68a; border-radius: 10px;
  font-size: 14px; color: #92400e;
}
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-label { font-size: 13px; font-weight: 600; color: #334155; }

.form-hint { font-size: 12px; color: #94a3b8; margin: 0; }
.form-error-msg { font-size: 12px; color: #dc2626; margin: 0; }

.form-preview {
  padding: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;
}
.form-preview__row {
  display: flex; justify-content: space-between; font-size: 13px; color: #475569; padding: 2px 0;
}
.form-preview__row--total {
  font-weight: 700; font-size: 14px; color: #166534;
  padding-top: 6px; margin-top: 6px; border-top: 1px solid #bbf7d0;
}
.form-preview__row--sisa {
  font-weight: 700; font-size: 15px; color: #1e293b;
  padding-top: 4px; margin-top: 4px; border-top: 1px solid #bbf7d0;
}

.form-textarea {
  padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 10px;
  font-size: 14px; color: #1e293b; outline: none; resize: vertical; font-family: inherit;
}
.form-textarea:focus { border-color: #2563eb; }

.modal-footer { display: flex; justify-content: space-between; align-items: center; width: 100%; }
.modal-footer__left { display: flex; }
.modal-footer__right { display: flex; gap: 8px; }
</style>
