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
import { formTemplateApi } from '../api/formTemplate'
import type { BeritaAcara, FormField } from '../types'
import { toast } from 'vue-sonner'

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
const discountAmount = ref<string>('')
const discountReason = ref<string>('')
const submittingDiscount = ref(false)

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

onMounted(async () => {
  const id = Number(route.params.id)
  if (!id || isNaN(id)) {
    toast.error('ID tidak valid')
    loading.value = false
    return
  }
  try {
    item.value = await analystApi.getBeritaAcaraDetail(id)
    if (item.value?.biaya) {
      biaya.value = String(item.value.biaya)
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
    await analystApi.storeRekapAnalis(item.value.id, {
      rekap_analis_data: JSON.stringify(formValues.value),
      rekap_analis_template_id: templateIdVal.value,
      biaya: biaya.value ? Number(biaya.value) : null,
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
  discountAmount.value = biaya.value || ''
  discountReason.value = ''
  showDiscountModal.value = true
}

async function handleSubmitDiscount() {
  if (!item.value) return
  if (!discountAmount.value || Number(discountAmount.value) <= 0) {
    toast.error('Masukkan jumlah diskon')
    return
  }

  submittingDiscount.value = true
  try {
    await analystApi.requestDiscount(item.value.id, {
      discount_request: Number(discountAmount.value),
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
              <label class="biaya-label">Biaya (Rp)</label>
              <div class="biaya-row">
                <input
                  v-model="biaya"
                  type="number"
                  min="0"
                  class="biaya-input"
                  placeholder="Masukkan biaya pengujian"
                />
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
          <div class="discount-field">
            <label class="discount-label">Jumlah Diskon (Rp)</label>
            <input
              v-model="discountAmount"
              type="number"
              min="0"
              class="discount-input"
              placeholder="Masukkan jumlah diskon"
            />
          </div>
          <div class="discount-field">
            <label class="discount-label">Alasan Diskon</label>
            <textarea
              v-model="discountReason"
              class="discount-textarea"
              placeholder="Masukkan alasan diskon (opsional)"
              rows="3"
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
.biaya-label { display: block; font-size: 14px; font-weight: 600; color: #92400e; margin-bottom: 8px; }
.biaya-row { display: flex; gap: 12px; align-items: center; }
.biaya-input { flex: 1; max-width: 320px; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: #1e293b; outline: none; transition: border-color 0.2s; }
.biaya-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

.discount-form { display: flex; flex-direction: column; gap: 16px; }
.discount-field { display: flex; flex-direction: column; gap: 6px; }
.discount-label { font-size: 14px; font-weight: 500; color: #374151; }
.discount-input { padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: #1e293b; outline: none; transition: border-color 0.2s; }
.discount-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.discount-textarea { padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: #1e293b; outline: none; resize: vertical; transition: border-color 0.2s; }
.discount-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.discount-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 8px; }
</style>
