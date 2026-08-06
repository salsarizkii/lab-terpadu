<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import Button from '../components/common/Button.vue'
import SkeletonTable from '../components/common/SkeletonTable.vue'
import { adminApi } from '../api/admin'
import { toast } from 'vue-sonner'
import { History, Check, X } from '@lucide/vue'
import ActivityTimeline from '../components/common/ActivityTimeline.vue'
import { formatDateTime } from '../utils/date'

interface DiscountRequest {
  id: number
  berita_acara_id: number
  judul_penelitian: string
  pemohon: string
  biaya: number
  amount: number
  discount_request: number
  discount_approved: number | null
  discount_reason: string | null
  discount_status: string
  status: string
  created_at: string
  activity_logs?: any[]
}

const items = ref<DiscountRequest[]>([])
const loading = ref(true)
const showModal = ref(false)
const saving = ref(false)
const selectedItem = ref<DiscountRequest | null>(null)
const showHistory = ref(false)
const historyItems = ref<any[]>([])
const loadingHistory = ref(false)

const discountMode = ref('percent')
const discountPercent = ref('')
const discountNominal = ref('')
const adminNote = ref('')
const discountAction = ref<'approve' | 'reject'>('approve')

const discountModeOptions = [
  { value: 'percent', label: 'Persen (%)' },
  { value: 'nominal', label: 'Manual (Rp)' },
  { value: 'both', label: 'Persen + Manual' },
]

watch(discountMode, (mode) => {
  if (mode === 'percent') discountNominal.value = ''
  if (mode === 'nominal') discountPercent.value = ''
})

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
const sisaBayar = computed(() => Math.max(0, (selectedItem.value?.biaya || 0) - totalDiscount.value))
const hasAnyInput = computed(() => totalDiscount.value > 0)

const isOverMax = computed(() => totalDiscount.value > (selectedItem.value?.biaya || 0))

const isReadonly = computed(() => selectedItem.value?.discount_status !== 'pending')

function openModal(item: DiscountRequest) {
  selectedItem.value = item
  const biaya = Number(item.biaya) || 1
  const approved = Number(item.discount_approved) || 0
  const req = Number(item.discount_request) || 0

  if (approved > 0) {
    const pct = Math.round((approved / biaya) * 100 * 100) / 100
    discountPercent.value = String(pct)
  } else if (req > 0) {
    const pct = Math.round((req / biaya) * 100 * 100) / 100
    discountPercent.value = String(pct)
  } else {
    discountPercent.value = ''
  }

  discountNominal.value = ''
  adminNote.value = item.discount_reason || ''
  discountAction.value = item.discount_status === 'rejected' ? 'reject' : 'approve'
  discountMode.value = 'percent'
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  showHistory.value = false
  selectedItem.value = null
}

async function loadHistory() {
  if (!selectedItem.value) return
  if (showHistory.value) {
    showHistory.value = false
    return
  }
  loadingHistory.value = true
  try {
    historyItems.value = await adminApi.getDiscountHistory(selectedItem.value.id)
    showHistory.value = true
  } catch {
    toast.error('Gagal memuat riwayat')
  } finally {
    loadingHistory.value = false
  }
}

const noteRequired = computed(() => discountAction.value === 'reject' && !adminNote.value.trim())
const submitDisabled = computed(() => {
  if (discountAction.value === 'approve') return !hasAnyInput.value || isOverMax.value
  return noteRequired.value
})

const filterTab = ref('all')

const filteredItems = computed(() => {
  if (filterTab.value === 'all') return items.value
  if (filterTab.value === 'proses') return items.value.filter(i => i.discount_status === 'pending' || i.discount_status === 'rejected')
  if (filterTab.value === 'diterima') return items.value.filter(i => i.discount_status === 'approved')
  return items.value
})

function autoFillFromRequest() {
  const biaya = Number(selectedItem.value?.biaya) || 0
  const req = Number(selectedItem.value?.discount_request) || 0
  if (req <= 0) return
  const pct = Math.round((req / biaya) * 100 * 100) / 100
  discountPercent.value = String(pct)
  discountNominal.value = ''
  discountMode.value = 'percent'
}

async function handleQuickApprove(item: DiscountRequest) {
  const biaya = Number(item.biaya) || 0
  const requested = Number(item.discount_request) || 0
  if (requested <= 0) return toast.error('Tidak ada jumlah diskon yang diminta')

  saving.value = true
  try {
    const percent = Math.round((requested / biaya) * 100 * 100) / 100
    await adminApi.approveDiscount(item.id, {
      discount_percent: percent,
      discount_nominal: 0,
      discount_reason: item.discount_reason || undefined,
    })
    toast.success('Diskon disetujui sesuai permintaan')
    await loadData()
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menyetujui diskon')
  } finally {
    saving.value = false
  }
}

async function handleSubmit() {
  if (!selectedItem.value) return

  if (discountAction.value === 'reject') {
    if (!adminNote.value.trim()) return toast.error('Wajib isi catatan untuk penolakan')
    saving.value = true
    try {
      await adminApi.rejectDiscount(selectedItem.value.id)
      toast.success('Ajuan diskon ditolak')
      await loadData()
      closeModal()
    } catch (e: any) {
      toast.error(e.response?.data?.message || 'Gagal menolak diskon')
    } finally {
      saving.value = false
    }
    return
  }

  if (!hasAnyInput.value) return toast.error('Isi minimal satu diskon')
  if (isOverMax.value) return toast.error('Total diskon tidak boleh melebihi biaya')

  saving.value = true
  try {
    await adminApi.approveDiscount(selectedItem.value.id, {
      discount_percent: Number(discountPercent.value) || 0,
      discount_nominal: Number(discountNominal.value) || 0,
      discount_reason: adminNote.value || undefined,
    })
    toast.success('Ajuan diskon disetujui')
    await loadData()
    closeModal()
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menyetujui diskon')
  } finally {
    saving.value = false
  }
}

async function loadData() {
  loading.value = true
  try {
    items.value = await adminApi.getDiscountRequests()
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
          <h1>Ajuan Diskon</h1>
          <p class="subtitle">Proses ajuan diskon dari pengguna</p>
        </div>
      </div>

      <Card>
        <div class="tabs">
          <button class="tab-btn" :class="{ active: filterTab === 'proses' }" @click="filterTab = 'proses'">Dalam Proses</button>
          <button class="tab-btn" :class="{ active: filterTab === 'diterima' }" @click="filterTab = 'diterima'">Diterima</button>
          <button class="tab-btn" :class="{ active: filterTab === 'all' }" @click="filterTab = 'all'">Semua</button>
        </div>

        <SkeletonTable v-if="loading" :cols="7" :rows="5" />

        <div v-else class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Judul Penelitian</th>
                <th>Pemohon</th>
                <th>Biaya</th>
                <th>Diskon Diminta</th>
                <th>Status</th>
                <th>Diajukan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filteredItems" :key="item.id" :class="{ 'row-rejected': item.discount_status === 'rejected' }">
                <td class="cell-title">{{ item.judul_penelitian || '-' }}</td>
                <td>{{ item.pemohon }}</td>
                <td>Rp {{ formatPrice(item.biaya || 0) }}</td>
                <td>
                  <span class="discount-req">Rp {{ formatPrice(item.discount_request || 0) }}</span>
                  <div v-if="item.discount_reason" class="discount-req-reason">{{ item.discount_reason }}</div>
                </td>
                <td>
                  <Badge v-if="item.discount_status === 'approved'" type="success" text="Diterima" />
                  <Badge v-else-if="item.discount_status === 'rejected'" type="danger" text="Ditolak" />
                  <Badge v-else type="warning" text="Diproses" />
                </td>
                <td class="td-date">{{ formatDateTime(item.created_at) }}</td>
                <td>
                  <Button v-if="item.discount_status === 'pending'" size="sm" @click="openModal(item)">Proses</Button>
                  <Button v-else size="sm" variant="outline" @click="openModal(item)">Lihat</Button>
                </td>
              </tr>
              <tr v-if="filteredItems.length === 0">
                <td colspan="7" class="empty-cell">Tidak ada ajuan diskon</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <!-- Popup Proses Ajuan -->
      <div v-if="showModal" class="popup-overlay" @click.self="closeModal">
        <div class="popup">
          <div class="popup-header">
            <h3>Proses Ajuan Diskon</h3>
            <button class="popup-close" @click="closeModal">&times;</button>
          </div>
          <div class="popup-body">
            <template v-if="selectedItem">
              <!-- Info ajuan -->
              <div class="req-info">
                <div class="req-row">
                  <span class="req-label">Pemohon</span>
                  <span class="req-value">{{ selectedItem.pemohon }}</span>
                </div>
                <div class="req-row">
                  <span class="req-label">Judul</span>
                  <span class="req-value">{{ selectedItem.judul_penelitian || '-' }}</span>
                </div>
                <div class="req-row req-row--border">
                  <span class="req-label">Biaya Total</span>
                  <span class="req-value req-value--bold">Rp {{ formatPrice(selectedItem.biaya || 0) }}</span>
                </div>
                <div class="req-row">
                  <span class="req-label">Diskon Diminta</span>
                  <span class="req-value req-value--accent">Rp {{ formatPrice(selectedItem.discount_request || 0) }}</span>
                </div>
                <div v-if="selectedItem.discount_reason" class="req-row">
                  <span class="req-label">Alasan</span>
                  <span class="req-value">{{ selectedItem.discount_reason }}</span>
                </div>
                <div class="req-row">
                  <span class="req-label">Status</span>
                  <span class="req-value">
                    <Badge v-if="selectedItem.discount_status === 'approved'" type="success" text="Diterima" />
                    <Badge v-else-if="selectedItem.discount_status === 'rejected'" type="danger" text="Ditolak" />
                    <Badge v-else type="warning" text="Menunggu" />
                  </span>
                </div>
                <div v-if="selectedItem.discount_approved" class="req-row">
                  <span class="req-label">Diskon Diberikan</span>
                  <span class="req-value req-value--accent">Rp {{ formatPrice(selectedItem.discount_approved || 0) }}</span>
                </div>
              </div>

              <div class="divider" />

              <div class="timeline-box">
                <div class="timeline-box__title">Riwayat Aktivitas</div>
                <ActivityTimeline :logs="selectedItem.activity_logs || []" />
              </div>

              <div class="divider" />

              <Button variant="outline" class="btn-history-toggle" @click="loadHistory" :disabled="loadingHistory">
                <History :size="16" style="margin-right: 6px;" />
                <span>{{ loadingHistory ? 'Memuat...' : 'Riwayat Pengajuan Diskon' }}</span>
              </Button>

              <div v-if="showHistory && historyItems.length" class="history-list">
                <div v-for="(h, idx) in historyItems" :key="h.id" class="history-item">
                  <div class="history-item-header">
                    <span class="history-action">
                      <Badge v-if="h.status === 'pending'" type="warning" text="Diajukan" />
                      <Badge v-else-if="h.status === 'approved'" type="success" text="Disetujui" />
                      <Badge v-else type="danger" text="Ditolak" />
                    </span>
                    <span class="history-date">{{ formatDateTime(h.created_at) }}</span>
                  </div>
                  <div class="history-item-body">
                    <div v-if="h.discount_request" class="history-row">
                      <span>Diskon diminta</span>
                      <span>Rp {{ formatPrice(Number(h.discount_request)) }}</span>
                    </div>
                    <div v-if="h.discount_approved" class="history-row">
                      <span>Diskon diberikan</span>
                      <span>Rp {{ formatPrice(Number(h.discount_approved)) }}</span>
                    </div>
                    <div v-if="h.discount_reason" class="history-row">
                      <span>Catatan</span>
                      <span>{{ h.discount_reason }}</span>
                    </div>
                    <div v-if="h.changed_by" class="history-row">
                      <span>Oleh</span>
                      <span class="history-actor">
                        {{ h.changed_by.name }}
                        <span class="history-actor-sub">{{ h.changed_by.email }}</span>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else-if="showHistory && !historyItems.length" class="history-empty">
                Belum ada catatan riwayat pengajuan diskon.
              </div>

              <div class="divider" />

              <!-- Pilihan Terima / Tolak -->
              <div class="action-toggle">
                <button
                  class="action-toggle-btn"
                  :class="{ active: discountAction === 'approve' }"
                  @click="discountAction = 'approve'"
                >
                  Terima Ajuan
                </button>
                <button
                  class="action-toggle-btn"
                  :class="{ active: discountAction === 'reject' }"
                  @click="discountAction = 'reject'"
                >
                  Tolak Ajuan
                </button>
              </div>

              <!-- Form Setujui -->
              <div v-if="discountAction === 'approve'" class="form-section">
                <label class="form-section-label">Tentukan Diskon yang Disetujui</label>

                <Button variant="outline" size="sm" class="auto-fill-btn" @click="autoFillFromRequest">
                  Beri Diskon Sesuai Pengajuan
                </Button>

                <div class="select-row">
                  <select v-model="discountMode" class="form-select">
                    <option v-for="opt in discountModeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                  </select>
                </div>

                <template v-if="discountMode === 'percent' || discountMode === 'both'">
                  <div class="form-field">
                    <label class="form-label">Persentase (%)</label>
                    <input v-model="discountPercent" type="number" min="0" max="100" class="form-input" placeholder="Contoh: 10" />
                    <p v-if="Number(discountPercent) > 100" class="form-err">Maksimal 100%</p>
                    <p v-else-if="Number(discountPercent) > 0" class="form-hint">= Rp {{ formatPrice(percentAmount) }}</p>
                  </div>
                </template>

                <template v-if="discountMode === 'nominal' || discountMode === 'both'">
                  <div class="form-field">
                    <label class="form-label">Nominal (Rp)</label>
                    <input v-model="discountNominal" type="number" min="0" class="form-input" placeholder="Contoh: 50000" />
                  </div>
                </template>

                <div v-if="hasAnyInput && !isOverMax" class="preview-box">
                  <div class="preview-row" v-if="Number(discountPercent) > 0">
                    <span>Diskon {{ discountPercent }}%</span>
                    <span>- Rp {{ formatPrice(percentAmount) }}</span>
                  </div>
                  <div class="preview-row" v-if="Number(discountNominal) > 0">
                    <span>Diskon Nominal</span>
                    <span>- Rp {{ formatPrice(nominalAmount) }}</span>
                  </div>
                  <div class="preview-row preview-row--total">
                    <span>Total Diskon Diberikan</span>
                    <span>- Rp {{ formatPrice(totalDiscount) }}</span>
                  </div>
                  <div class="preview-row preview-row--sisa">
                    <span>Sisa Bayar</span>
                    <span>Rp {{ formatPrice(sisaBayar) }}</span>
                  </div>
                </div>

                <p v-if="isOverMax" class="form-err">Total diskon melebihi biaya!</p>

                <div class="form-field">
                  <label class="form-label">Catatan (opsional)</label>
                  <textarea v-model="adminNote" class="form-textarea" rows="2" placeholder="Alasan memberikan diskon sebesar ini..." />
                </div>
              </div>

              <!-- Form Tolak -->
              <div v-if="discountAction === 'reject'" class="form-section">
                <div class="form-field">
                  <label class="form-label">Catatan Penolakan <span class="required">*</span></label>
                  <textarea v-model="adminNote" class="form-textarea" rows="3" placeholder="Alasan menolak ajuan diskon ini..." />
                </div>
              </div>
            </template>
          </div>
          <div class="popup-footer">
            <Button variant="outline" @click="closeModal">Batal</Button>
            <Button :disabled="saving || submitDisabled" @click="handleSubmit">
              {{ saving ? 'Menyimpan...' : discountAction === 'approve' ? 'Terima Ajuan' : 'Tolak Ajuan' }}
            </Button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-header { margin-bottom: 20px; }
.page-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
.subtitle { font-size: 14px; color: #64748b; margin: 4px 0 0; }

.tabs { display: flex; gap: 0; border-bottom: none; margin-bottom: 16px; background: #f1f5f9; border-radius: 10px; padding: 4px; }
.tab-btn {
  flex: 1; padding: 10px 0; border: none; background: none; font-size: 14px; font-weight: 500;
  color: #64748b; cursor: pointer; transition: all 0.15s; font-family: inherit; border-radius: 8px;
}
.tab-btn:hover { color: #1e293b; }
.tab-btn.active { color: #1e293b; font-weight: 600; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }

.table-wrap { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; font-size: 14px; }
.table th { text-align: left; padding: 12px; background: #f8fafc; color: #475569; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
.table td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
.cell-title { max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.td-date { white-space: nowrap; }
.empty-cell { text-align: center; color: #94a3b8; padding: 40px; }
.discount-req { font-weight: 600; color: #dc2626; }
.discount-req-reason { font-size: 12px; color: #64748b; margin-top: 2px; max-width: 200px; }
.text-muted { font-size: 13px; color: #94a3b8; }
.row-rejected { opacity: 0.6; background: #fef2f2; }

/* ===== Popup ===== */
.popup-overlay {
  position: fixed; inset: 0;
  background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000; padding: 20px;
}
.popup {
  background: #fff; border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
  width: 100%; max-width: 480px; max-height: 90vh;
  display: flex; flex-direction: column;
  animation: popupIn 0.2s ease-out;
}
@keyframes popupIn {
  from { opacity: 0; transform: translateY(12px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
.popup-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 20px 0; }
.popup-header h3 { font-size: 18px; font-weight: 600; color: #1e293b; margin: 0; }
.popup-close {
  display: flex; align-items: center; justify-content: center;
  width: 32px; height: 32px; border-radius: 8px; border: none;
  background: transparent; color: #94a3b8; cursor: pointer; font-size: 20px;
}
.popup-close:hover { background: #f1f5f9; color: #1e293b; }
.popup-body { padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 16px; }

.req-info { display: flex; flex-direction: column; gap: 6px; }
.req-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
.req-row--border { padding-bottom: 10px; border-bottom: 1px solid #e2e8f0; margin-bottom: 4px; }
.req-label { font-size: 13px; color: #64748b; white-space: nowrap; }
.req-value { font-size: 14px; color: #1e293b; text-align: right; }
.req-value--bold { font-weight: 700; font-size: 16px; color: #1e293b; }
.req-value--accent { font-weight: 600; color: #dc2626; }

.divider { height: 1px; background: #e2e8f0; }

.readonly-info { display: flex; flex-direction: column; gap: 6px; margin-bottom: 8px; }
.readonly-info .req-row { padding: 4px 0; }
.readonly-divider { height: 1px; background: #e2e8f0; margin: 4px 0; }

.history-list { display: flex; flex-direction: column; gap: 8px; max-height: 200px; overflow-y: auto; }
.history-item { padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; }
.history-actor { display: flex; flex-direction: column; }
.history-actor-sub { font-size: 11px; color: #94a3b8; }

.timeline-box {
  padding: 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.timeline-box__title {
  font-size: 13px;
  font-weight: 700;
  color: #334155;
}
.history-item-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
.history-empty {
  font-size: 13px;
  color: #64748b;
  text-align: center;
  padding: 14px;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  border-radius: 8px;
}

.btn-history-toggle {
  width: 100%;
  height: 42px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 500;
  border-radius: 10px;
}

.action-toggle {
  display: flex;
  padding: 4px;
  background: #f1f5f9;
  border-radius: 10px;
  margin: 12px 0;
  gap: 4px;
}
.action-toggle-btn {
  flex: 1;
  height: 40px;
  padding: 0 16px;
  border: none;
  background: transparent;
  font-size: 14px;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.2s ease;
  font-family: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
}
.action-toggle-btn.active {
  background: #2563eb !important;
  color: #ffffff !important;
  font-weight: 600 !important;
  box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
}
.action-toggle-btn:not(.active):hover {
  color: #1e293b;
}

.required { color: #dc2626; }

.form-section { display: flex; flex-direction: column; gap: 12px; }
.form-section-label { font-size: 14px; font-weight: 600; color: #1e293b; }
.auto-fill-btn { align-self: flex-start; }

.select-row { }
.form-select {
  width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px;
  font-size: 14px; color: #1e293b; outline: none; background: #fff; cursor: pointer;
}
.form-select:focus { border-color: #2563eb; }

.form-field { display: flex; flex-direction: column; gap: 4px; }
.form-label { font-size: 13px; font-weight: 500; color: #475569; }
.form-input {
  padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px;
  font-size: 14px; color: #1e293b; outline: none;
}
.form-input:focus { border-color: #2563eb; }
.form-hint { font-size: 12px; color: #94a3b8; margin: 0; }
.form-err { font-size: 12px; color: #dc2626; margin: 0; }

.preview-box {
  padding: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;
}
.preview-row { display: flex; justify-content: space-between; font-size: 13px; color: #475569; padding: 2px 0; }
.preview-row--total {
  font-weight: 700; font-size: 14px; color: #166534;
  padding-top: 6px; margin-top: 6px; border-top: 1px solid #bbf7d0;
}
.preview-row--sisa {
  font-weight: 700; font-size: 15px; color: #1e293b;
  padding-top: 4px; margin-top: 4px; border-top: 1px solid #bbf7d0;
}

.popup-footer {
  padding: 0 20px 20px;
  display: flex; justify-content: space-between; align-items: center;
}
.footer-right { display: flex; gap: 8px; }

.form-textarea {
  padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px;
  font-size: 14px; color: #1e293b; outline: none; resize: vertical; font-family: inherit;
}
.form-textarea:focus { border-color: #2563eb; }

.action-btns { display: flex; gap: 6px; }
</style>
