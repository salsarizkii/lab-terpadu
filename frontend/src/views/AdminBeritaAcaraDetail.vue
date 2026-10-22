<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import SkeletonDetail from '../components/common/SkeletonDetail.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import BeritaAcaraDetailView from '../components/admin/BeritaAcaraDetailView.vue'
import BeritaAcaraVerification from '../components/admin/BeritaAcaraVerification.vue'
import VerificationPanel from '../components/admin/VerificationPanel.vue'
import AnalystAssignment from '../components/admin/AnalystAssignment.vue'
import UserAssignList from '../components/admin/UserAssignList.vue'
import FormTextarea from '../components/common/FormTextarea.vue'
import FormInput from '../components/common/FormInput.vue'
import DynamicForm from '../components/common/DynamicForm.vue'
import ActivityTimeline from '../components/common/ActivityTimeline.vue'
import DownloadMenu from '../components/common/DownloadMenu.vue'
import { adminApi } from '../api/admin'
import { verificationApi } from '../api/verification'
import { formTemplateApi } from '../api/formTemplate'
import { uploadApi } from '../api/upload'
import { useAuthStore } from '../stores/authStore'
import type { BeritaAcara, User, FormField } from '../types'
import { toast } from 'vue-sonner'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

type LegacyEditKey =
  | 'nama_pemohon' | 'nim' | 'prodi_fakultas_instansi' | 'dosen_pembimbing'
  | 'telp_hp' | 'email' | 'alamat' | 'judul_penelitian' | 'akses_lab'
  | 'bahan_contoh_uji' | 'kemasan_packing' | 'permintaan_analisa'
  | 'jumlah_sampel' | 'kode_nama_sampel' | 'keterangan_sampel'
  | 'keterangan_tambahan' | 'pengukuran_per_sampel' | 'sisa_sampel'

const legacyEditFields: { key: LegacyEditKey; label: string; type: 'text' | 'textarea' }[] = [
  { key: 'nama_pemohon', label: 'Nama Pemohon', type: 'text' },
  { key: 'nim', label: 'NIM', type: 'text' },
  { key: 'prodi_fakultas_instansi', label: 'Prodi/Fakultas/Instansi', type: 'text' },
  { key: 'dosen_pembimbing', label: 'Dosen Pembimbing', type: 'text' },
  { key: 'telp_hp', label: 'Telp/No. HP', type: 'text' },
  { key: 'email', label: 'Email', type: 'text' },
  { key: 'alamat', label: 'Alamat', type: 'textarea' },
  { key: 'judul_penelitian', label: 'Judul Penelitian', type: 'text' },
  { key: 'akses_lab', label: 'Akses Lab', type: 'text' },
  { key: 'bahan_contoh_uji', label: 'Bahan Contoh Uji', type: 'text' },
  { key: 'kemasan_packing', label: 'Kemasan/Packing', type: 'text' },
  { key: 'permintaan_analisa', label: 'Metode / Instrumen', type: 'textarea' },
  { key: 'jumlah_sampel', label: 'Jumlah Hasil Pengujian', type: 'text' },
  { key: 'kode_nama_sampel', label: 'Kode/Nama Hasil Pengujian', type: 'text' },
  { key: 'keterangan_sampel', label: 'Keterangan Hasil Pengujian', type: 'textarea' },
  { key: 'keterangan_tambahan', label: 'Keterangan Tambahan', type: 'textarea' },
  { key: 'pengukuran_per_sampel', label: 'Pengukuran per Hasil Pengujian', type: 'text' },
  { key: 'sisa_sampel', label: 'Sisa Hasil Pengujian Setelah Pengujian', type: 'text' },
]

const legacyEditDefaults: Record<LegacyEditKey, string> = {
  nama_pemohon: '', nim: '', prodi_fakultas_instansi: '', dosen_pembimbing: '',
  telp_hp: '', email: '', alamat: '', judul_penelitian: '', akses_lab: '',
  bahan_contoh_uji: '', kemasan_packing: '', permintaan_analisa: '',
  jumlah_sampel: '', kode_nama_sampel: '', keterangan_sampel: '',
  keterangan_tambahan: '', pengukuran_per_sampel: '', sisa_sampel: '',
}

const item = ref<BeritaAcara | null>(null)
const analysts = ref<User[]>([])
const penyelias = ref<User[]>([])
const managerTeknis = ref<User[]>([])
const loading = ref(true)
const saving = ref(false)
const savingAnalyst = ref(false)
const savingPenyelia = ref(false)
const savingManagerTeknis = ref(false)
const showVerification = ref(false)
const showRevisionModal = ref(false)
const revisionNote = ref('')
const showRevisionDetail = ref(false)
const selectedRevision = ref<any>(null)
const templateFields = ref<FormField[]>([])
const showAssignForm = ref(false)
const showPenyeliaForm = ref(false)
const showManagerTeknisForm = ref(false)
const showEditModal = ref(false)
const savingEdit = ref(false)
const editDynamicRef = ref<InstanceType<typeof DynamicForm> | null>(null)
const editValues = ref<Record<string, any>>({})
const legacyEdit = ref<Record<LegacyEditKey, string>>({ ...legacyEditDefaults })
const agendaInputRef = ref<HTMLInputElement | null>(null)
const showAssignConfirm = ref(false)
const pendingAnalystIds = ref<number[]>([])
const assignNote = ref('')
const nomorAgenda = ref('')
const savingAgenda = ref(false)
const checkingAgenda = ref(false)
const agendaAvailable = ref(true)
let agendaCheckTimer: ReturnType<typeof setTimeout> | null = null

const canVerify = computed(() =>
  item.value &&
  ['pending', 'admin_revisi', 'revision'].includes(item.value.status) &&
  Boolean(item.value.sample_submitted) &&
  Boolean(nomorAgenda.value.trim()) &&
  !checkingAgenda.value &&
  agendaAvailable.value
)

watch(nomorAgenda, (value) => {
  if (agendaCheckTimer) {
    clearTimeout(agendaCheckTimer)
    agendaCheckTimer = null
  }
  const v = value.trim()
  if (!v || v === (item.value?.nomor_agenda || '')) {
    checkingAgenda.value = false
    agendaAvailable.value = true
    return
  }
  checkingAgenda.value = true
  agendaAvailable.value = false
  agendaCheckTimer = setTimeout(async () => {
    try {
      const res = await adminApi.checkNomorAgenda(v, item.value?.id)
      agendaAvailable.value = res.available
    } catch {
      agendaAvailable.value = true
    } finally {
      checkingAgenda.value = false
      agendaCheckTimer = null
    }
  }, 400)
})

onUnmounted(() => {
  if (agendaCheckTimer) clearTimeout(agendaCheckTimer)
})

const currentAnalystIds = computed(() =>
 item.value?.analysts?.map(a => a.id) || (item.value?.analyst_id ? [item.value.analyst_id] : [])
)

const currentPenyeliaIds = computed(() =>
 item.value?.penyelias?.map(v => v.id) || []
)

const currentManagerTeknisIds = computed(() =>
 item.value?.manager_teknis?.map(m => m.id) || []
)

const assignedAnalysts = computed<User[]>(() => {
  if (item.value?.analysts?.length) return item.value.analysts
  if (item.value?.analyst_id) {
    const a = analysts.value.find(x => x.id === item.value!.analyst_id)
    return a ? [a] : []
  }
  return []
})

const assignedPenyelias = computed<User[]>(() => item.value?.penyelias || [])
const assignedManagerTeknis = computed<User[]>(() => item.value?.manager_teknis || [])

const selectedAnalysts = computed(() =>
  analysts.value.filter(a => pendingAnalystIds.value.includes(a.id))
)

function initials(name: string): string {
  return name
    .split(/\s+/)
    .map(w => w.charAt(0))
    .filter(Boolean)
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

const statusMap: Record<string, string> = {
  draft: 'Draft', pending: 'Menunggu Admin', revision: 'Perlu Perbaikan', admin_verified: 'Diverifikasi Admin',
  admin_revisi: 'Admin Revisi',
  penyelia_verified: 'Diverifikasi Penyelia', rekap_ready: 'Menunggu Manager Teknis', completed: 'Selesai', rejected: 'Ditolak',
}
const statusTypeMap: Record<string, 'default' | 'warning' | 'success' | 'danger' | 'info'> = {
  draft: 'default', pending: 'warning', revision: 'warning', admin_verified: 'info',
  admin_revisi: 'warning',
  penyelia_verified: 'info', rekap_ready: 'warning', completed: 'success', rejected: 'danger',
}

const verifiedStatuses = ['admin_verified', 'penyelia_verified', 'rekap_ready', 'completed']

const canAssign = computed(() =>
  ['admin_verified', 'penyelia_verified', 'rekap_ready'].includes(item.value?.status || '')
)

onMounted(async () => {
  const id = Number(route.params.id)
  if (!id || isNaN(id)) {
    toast.error('ID tidak valid')
    loading.value = false
    return
  }
  try {
    const [baData, analystsData, penyeliasData, managerTeknisData] = await Promise.all([
      adminApi.getBeritaAcaraDetail(id),
      adminApi.getAnalysts(),
      adminApi.getPenyelias(),
      adminApi.getManagerTeknis(),
    ])
    item.value = baData
    analysts.value = analystsData
    penyelias.value = penyeliasData
    managerTeknis.value = managerTeknisData
    nomorAgenda.value = baData.nomor_agenda || ''
    if (item.value?.form_template_id) {
      try {
        const tpl = await formTemplateApi.getPublicTemplate(item.value.form_template_id)
        if (tpl?.fields) templateFields.value = tpl.fields
      } catch {}
    }
  } catch {
    toast.error('Gagal memuat data')
  } finally {
    loading.value = false
  }
})

async function handleVerify(data: any) {
  if (!item.value) return
  if (!nomorAgenda.value.trim()) {
    toast.error('Nomor agenda wajib diisi sebelum verifikasi')
    return
  }
  saving.value = true
  try {
    const updated = await adminApi.updateStatus(item.value.id, {
      ...data,
      nomor_agenda: nomorAgenda.value.trim(),
    })
    item.value = updated
    showVerification.value = false
    toast.success('Status Berita Acara diperbarui')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal')
  } finally {
    saving.value = false
  }
}

async function handlePenyeliaOverrideVerify(data: { status: string; sample_answer?: 'sudah' | 'belum' | null; note?: string }) {
  if (!item.value) return
  const id = item.value.id
  saving.value = true
  try {
    await verificationApi.penyeliaVerify(id, {
      status: data.status as 'penyelia_verified',
      note: data.note,
      sample_answer: data.sample_answer || undefined,
    })
    item.value = await adminApi.getBeritaAcaraDetail(id)
    toast.success('Berita acara disetujui (override penyelia), siap dikerjakan analis')
    if (data.sample_answer === 'belum') {
      toast.warning('Jangan lupa update penyerahan sampel di sistem jika sampel sudah diterima.')
    }
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal')
  } finally {
    saving.value = false
  }
}

async function handlePenyeliaOverrideRevision(note: string) {
  if (!item.value) return
  const id = item.value.id
  saving.value = true
  try {
    await verificationApi.penyeliaVerify(id, { status: 'revision', note })
    item.value = await adminApi.getBeritaAcaraDetail(id)
    toast.success('Berita acara dikembalikan ke admin untuk diperbaiki (Admin Revisi)')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal')
  } finally {
    saving.value = false
  }
}

async function handleManagerOverrideVerify(data: { status: string; note?: string }) {
  if (!item.value) return
  const id = item.value.id
  saving.value = true
  try {
    await verificationApi.managerVerify(id, {
      status: data.status as 'completed',
      note: data.note,
    })
    item.value = await adminApi.getBeritaAcaraDetail(id)
    toast.success('Rekap disetujui (override manager teknis), berita acara selesai')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal')
  } finally {
    saving.value = false
  }
}

async function handleManagerOverrideRevision(note: string) {
  if (!item.value) return
  const id = item.value.id
  saving.value = true
  try {
    await verificationApi.managerVerify(id, { status: 'revision', note })
    item.value = await adminApi.getBeritaAcaraDetail(id)
    toast.success('Perubahan telah dikirim ke pemohon')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal')
  } finally {
    saving.value = false
  }
}

async function saveNomorAgenda() {
  if (!item.value) return
  const value = nomorAgenda.value.trim()
  if (value === (item.value.nomor_agenda || '')) return
  savingAgenda.value = true
  try {
    const updated = await adminApi.updateNomorAgenda(item.value.id, value)
    if (item.value) {
      item.value = { ...item.value, nomor_agenda: updated.nomor_agenda || value, activity_logs: updated.activity_logs || item.value.activity_logs }
    }
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menyimpan nomor agenda')
  } finally {
    savingAgenda.value = false
  }
}

function handleAssign(analystIds: number[]) {
  pendingAnalystIds.value = analystIds
  assignNote.value = ''
  showAssignConfirm.value = true
}

async function confirmAssign() {
  showAssignConfirm.value = false
  await doAssign(pendingAnalystIds.value)
}

async function doAssign(analystIds: number[]) {
 if (!item.value) return
 savingAnalyst.value = true
 try {
   const res = await adminApi.assignAnalyst(item.value.id, analystIds, assignNote.value.trim() || undefined)
   const updated = await adminApi.getBeritaAcaraDetail(item.value.id)
   item.value = updated
   showAssignForm.value = false
   toast.success(res.message)
 } catch (e: any) {
   toast.error(e.response?.data?.message || 'Gagal')
 } finally {
   savingAnalyst.value = false
 }
}

async function handleAssignPenyelia(penyeliaIds: number[]) {
 if (!item.value) return
 savingPenyelia.value = true
 try {
   const res = await adminApi.assignPenyelia(item.value.id, penyeliaIds)
   const updated = await adminApi.getBeritaAcaraDetail(item.value.id)
   item.value = updated
   showPenyeliaForm.value = false
   toast.success(res.message)
 } catch (e: any) {
   toast.error(e.response?.data?.message || 'Gagal')
 } finally {
   savingPenyelia.value = false
 }
}

async function handleAssignManagerTeknis(managerTeknisIds: number[]) {
 if (!item.value) return
 savingManagerTeknis.value = true
 try {
   const res = await adminApi.assignManagerTeknis(item.value.id, managerTeknisIds)
   const updated = await adminApi.getBeritaAcaraDetail(item.value.id)
   item.value = updated
   showManagerTeknisForm.value = false
   toast.success(res.message)
 } catch (e: any) {
   toast.error(e.response?.data?.message || 'Gagal')
 } finally {
   savingManagerTeknis.value = false
 }
}

function openRevisionModal() {
  revisionNote.value = ''
  showRevisionModal.value = true
}

const editMode = computed<'template' | 'legacy'>(() =>
  item.value?.form_template_id && templateFields.value.length > 0 ? 'template' : 'legacy'
)

function openEdit() {
  if (!item.value) return
  editValues.value = {}
  legacyEdit.value = { ...legacyEditDefaults }
  for (const f of legacyEditFields) {
    legacyEdit.value[f.key] = (item.value as any)[f.key] ?? ''
  }
  showEditModal.value = true
}

function focusAgenda() {
  agendaInputRef.value?.focus()
}

async function submitEdit() {
  if (!item.value) return
  if (editMode.value === 'template' && editDynamicRef.value) {
    const result = editDynamicRef.value.validate()
    if (!result.valid) {
      const names = result.missingFields.slice(0, 3).join(', ')
      const extra = result.missingFields.length > 3 ? ` dan ${result.missingFields.length - 3} lainnya` : ''
      toast.error(`Wajib diisi: ${names}${extra}`)
      return
    }
  }
  savingEdit.value = true
  try {
    const fd = new FormData()
    if (editMode.value === 'template') {
      const cleaned = { ...editValues.value }
      let uploadAksesPath: string | null = null
      for (const [key, value] of Object.entries(cleaned)) {
        if (value instanceof File) {
          const uploaded = await uploadApi.uploadFile(value, 'berita-acara')
          cleaned[key] = uploaded.path
          const field = templateFields.value.find(f => `field_${f.id}` === key)
          if (field && (field.label || '').toLowerCase().includes('upload akses')) {
            uploadAksesPath = uploaded.path
          }
        }
      }
      fd.append('form_data', JSON.stringify(cleaned))
      if (item.value.form_template_id) {
        fd.append('form_template_id', String(item.value.form_template_id))
      }
      if (uploadAksesPath) {
        fd.append('upload_akses', uploadAksesPath)
      }
    } else {
      for (const f of legacyEditFields) {
        fd.append(f.key, legacyEdit.value[f.key] ?? '')
      }
    }
    const updated = await adminApi.updateBeritaAcara(item.value.id, fd)
    item.value = updated
    showEditModal.value = false
    toast.success('Data berita acara diperbarui')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menyimpan')
  } finally {
    savingEdit.value = false
  }
}

async function submitRevision() {
  if (!item.value || !revisionNote.value.trim()) return
  saving.value = true
  try {
    const updated = await adminApi.updateStatus(item.value.id, {
      status: 'revision',
      note: revisionNote.value.trim(),
    })
    item.value = updated
    showRevisionModal.value = false
    toast.success('Perubahan telah dikirim ke pemohon')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal')
  } finally {
    saving.value = false
  }
}

function openRevisionDetail(rev: any) {
  selectedRevision.value = rev
  showRevisionDetail.value = true
}

function openRevisionFromLog(log: any) {
  const rev = (item.value?.revisions || []).find(r => r.id === log.revision_id)
  if (rev) openRevisionDetail(rev)
}

function getLabelForKey(key: string): string {
  const field = templateFields.value.find(f => `field_${f.id}` === key)
  if (field) return field.label
  return key.replace(/^field_/, '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function formatFieldValue(value: any): string {
  if (value === null || value === undefined) return '-'
  if (Array.isArray(value)) return value.join(', ')
  if (typeof value === 'object') return JSON.stringify(value)
  return String(value)
}

function formatSampleDate(iso?: string | null): string {
  if (!iso) return '-'
  return new Date(iso).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getRevisionDiff(rev: any) {
  if (!rev.previous_form_data || !item.value?.form_data) return []
  const diffs: { label: string; old: string; new: string }[] = []
  const allKeys = new Set([...Object.keys(rev.previous_form_data), ...Object.keys(item.value.form_data)])
  for (const key of allKeys) {
    const oldVal = rev.previous_form_data[key]
    const newVal = item.value.form_data[key]
    if (JSON.stringify(oldVal) !== JSON.stringify(newVal)) {
      diffs.push({
        label: getLabelForKey(key),
        old: formatFieldValue(oldVal),
        new: formatFieldValue(newVal),
      })
    }
  }
  return diffs
}

</script>

<template>
  <AppLayout>
    <div>
      <SkeletonDetail v-if="loading" />

      <template v-else-if="item">
        <div class="page-header flex flex-wrap items-center justify-between gap-3 mb-4">
          <div class="page-header__left flex items-center gap-2">
            <Button variant="outline" @click="router.push('/admin/berita-acara')">Kembali</Button>
            <DownloadMenu :berita-acara="item" show-rekap />
          </div>
          <Badge :type="statusTypeMap[item.status]" :text="statusMap[item.status]" />
        </div>

        <div v-if="item.rejection_reason && ['pending', 'admin_revisi'].includes(item.status)" class="rejection-banner">
          <div class="rejection-banner__icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
          </div>
          <div class="rejection-banner__content">
            <div class="rejection-banner__title">Dikembalikan oleh Penyelia — Admin Revisi</div>
            <div class="rejection-banner__note">{{ item.rejection_reason }}</div>
          </div>
        </div>

        <div class="detail-grid">
          <div class="detail-main">
            <Card title="Data Berita Acara">
              <template #header-action>
                <button class="card-edit-btn" title="Edit Data Berita Acara" @click="openEdit">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                </button>
              </template>
              <BeritaAcaraDetailView :berita-acara="item" />
            </Card>

            <Card title="Penyerahan Sampel" style="margin-top: 24px;">
              <div v-if="item.sample_submitted" class="sample-status sample-status--done">
                <div class="sample-status__title">Sampel sudah diserahkan pada {{ formatSampleDate(item.sample_submitted_at) }}</div>
              </div>
              <div v-else class="sample-status sample-status--pending">
                <div class="sample-status__title">Sampel belum diserahkan</div>
                <p class="sample-status__desc">Jangan lupa update di sistem jika sudah menyerahkan sampel.</p>
              </div>
            </Card>

            <Card title="Riwayat Aktivitas" style="margin-top: 24px;">
              <ActivityTimeline
                :logs="item.activity_logs || []"
                show-detail
                :revision-active="item.status === 'revision'"
                @detail="openRevisionFromLog"
              />
            </Card>
          </div>

          <div class="detail-sidebar">
            <Card title="Aksi">
              <div class="action-stack">
                <template v-if="auth.hasPermission('berita_acara_verify')">
                  <div v-if="!item.sample_submitted && ['pending', 'admin_revisi', 'revision'].includes(item.status)" class="sample-blocked">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                    Sampel belum diserahkan pemohon — verifikasi admin ditunda sampai sampel masuk.
                  </div>

                  <div v-if="!verifiedStatuses.includes(item.status)" class="agenda-field">
                    <div class="agenda-field__header">
                      <label class="agenda-label" for="nomor-agenda">Nomor Agenda</label>
                      <button class="agenda-edit-btn" title="Edit Nomor Agenda" @click="focusAgenda">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                      </button>
                    </div>
                    <div class="agenda-input-wrap">
                      <input
                        id="nomor-agenda"
                        ref="agendaInputRef"
                        v-model="nomorAgenda"
                        class="agenda-input"
                        :class="{ 'agenda-input--checking': checkingAgenda, 'agenda-input--error': !checkingAgenda && !agendaAvailable }"
                        type="text"
                        placeholder="Contoh: AGD-2026-001"
                        @blur="saveNomorAgenda"
                        @keyup.enter="saveNomorAgenda"
                      />
                      <span v-if="checkingAgenda" class="agenda-spinner" aria-label="Memeriksa ketersediaan nomor agenda" />
                      <span v-else-if="!agendaAvailable" class="agenda-status agenda-status--error">Sudah dipakai</span>
                      <span v-else-if="nomorAgenda.trim() && !savingAgenda" class="agenda-status agenda-status--ok">Tersedia</span>
                    </div>
                    <span v-if="savingAgenda" class="agenda-saving">Menyimpan...</span>
                    <span v-if="!agendaAvailable && !checkingAgenda" class="agenda-hint agenda-hint--error">Nomor agenda sudah pernah dipakai. Gunakan nomor lain.</span>
                    <span v-else class="agenda-hint">Wajib diisi sebelum verifikasi. Tidak dapat diubah setelah diverifikasi.</span>
                  </div>

                  <Button
                    block
                    @click="showVerification = true"
                    :disabled="!canVerify"
                    :loading="checkingAgenda"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/></svg>
                    Verifikasi
                  </Button>

                  <Button
                    block
                    variant="danger"
                    @click="openRevisionModal"
                    :disabled="item.status === 'draft' || verifiedStatuses.includes(item.status)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    Perlu Perbaikan
                  </Button>
                </template>

                <template v-if="auth.isSuperAdmin && item.status === 'admin_verified'">
                  <div class="section-divider">
                    <div class="override-note">
                      <strong>Override Super Admin</strong>
                      <span>Berita acara sudah diverifikasi admin. Anda dapat menyelesaikan verifikasi tahap penyelia dari sini.</span>
                    </div>
                    <VerificationPanel
                      :berita-acara="item"
                      target-status="penyelia_verified"
                      approve-label="Setujui (Override Penyelia)"
                      @verify="handlePenyeliaOverrideVerify"
                      @revision="handlePenyeliaOverrideRevision"
                    />
                  </div>
                </template>

                <template v-if="auth.isSuperAdmin && item.status === 'rekap_ready'">
                  <div class="section-divider">
                    <div class="override-note">
                      <strong>Override Super Admin</strong>
                      <span>Rekap analis sudah siap. Anda dapat menyelesaikan verifikasi tahap manager teknis dari sini.</span>
                    </div>
                    <VerificationPanel
                      :berita-acara="item"
                      target-status="completed"
                      approve-label="Setujui &amp; Kirim ke Pemohon (Override Manager Teknis)"
                      @verify="handleManagerOverrideVerify"
                      @revision="handleManagerOverrideRevision"
                    />
                  </div>
                </template>

                <template v-if="auth.hasPermission('berita_acara_assign')">
                  <div class="section-divider">
                    <div v-if="assignedAnalysts.length" class="assign-summary">
                      <div class="assign-summary__label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
                        Analis Ditugaskan
                      </div>
                      <div class="assign-summary__chips">
                        <span v-for="a in assignedAnalysts" :key="a.id" class="assign-chip">{{ a.name }}</span>
                      </div>
                      <button
                        class="assign-edit"
                        title="Ubah penugasan analis"
                        @click="showAssignForm = !showAssignForm"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                      </button>
                    </div>

                    <Button
                      v-else
                      block
                      variant="outline"
                      :disabled="!canAssign"
                      @click="showAssignForm = !showAssignForm"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line id="line" x1="19" x2="19" y1="8" y2="14"/><line id="line" x1="22" x2="16" y1="11" y2="11"/></svg>
                      Assign Analis
                    </Button>

                    <div v-if="showAssignForm" class="assign-form">
                      <AnalystAssignment
                        :berita-acara-id="item.id"
                        :analysts="analysts"
                        v-model="currentAnalystIds"
                        :loading="savingAnalyst"
                        @assign="handleAssign"
                      />
                    </div>
                  </div>
                </template>

                <template v-if="auth.hasPermission('berita_acara_verify')">
                  <div class="section-divider">
                    <div v-if="assignedPenyelias.length" class="assign-summary">
                      <div class="assign-summary__label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
                        Penyelia Ditugaskan
                      </div>
                      <div class="assign-summary__chips">
                        <span v-for="v in assignedPenyelias" :key="v.id" class="assign-chip">{{ v.name }}</span>
                      </div>
                      <button
                        class="assign-edit"
                        title="Ubah penugasan penyelia"
                        @click="showPenyeliaForm = !showPenyeliaForm"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                      </button>
                    </div>

                    <Button
                      v-else
                      block
                      variant="outline"
                      :disabled="!canAssign"
                      @click="showPenyeliaForm = !showPenyeliaForm"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                      Assign Penyelia
                    </Button>

                    <div v-if="showPenyeliaForm" class="assign-form">
                      <UserAssignList
                        :users="penyelias"
                        v-model="currentPenyeliaIds"
                        :loading="savingPenyelia"
                        submit-label="Assign"
                        hint="Pilih satu atau lebih penyelia untuk memverifikasi berita acara ini."
                        @assign="handleAssignPenyelia"
                      />
                    </div>
                  </div>
                </template>

                <template v-if="auth.hasPermission('berita_acara_verify')">
                  <div class="section-divider">
                    <div v-if="assignedManagerTeknis.length" class="assign-summary">
                      <div class="assign-summary__label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
                        Manager Teknis Ditugaskan
                      </div>
                      <div class="assign-summary__chips">
                        <span v-for="m in assignedManagerTeknis" :key="m.id" class="assign-chip">{{ m.name }}</span>
                      </div>
                      <button
                        class="assign-edit"
                        title="Ubah penugasan manager teknis"
                        @click="showManagerTeknisForm = !showManagerTeknisForm"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                      </button>
                    </div>

                    <Button
                      v-else
                      block
                      variant="outline"
                      :disabled="!canAssign"
                      @click="showManagerTeknisForm = !showManagerTeknisForm"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                      Assign Manager Teknis
                    </Button>

                    <div v-if="showManagerTeknisForm" class="assign-form">
                      <UserAssignList
                        :users="managerTeknis"
                        v-model="currentManagerTeknisIds"
                        :loading="savingManagerTeknis"
                        submit-label="Assign"
                        hint="Pilih satu atau lebih manager teknis untuk berita acara ini."
                        @assign="handleAssignManagerTeknis"
                      />
                    </div>
                  </div>
                </template>
              </div>
            </Card>
          </div>
        </div>
      </template>

      <!-- Revision Modal -->
      <Modal :show="showRevisionModal" title="Perlu Perbaikan" size="sm" @close="showRevisionModal = false">
        <div class="revision-form">
          <p class="revision-desc">Tuliskan bagian mana yang perlu diperbaiki oleh pemohon.</p>
          <textarea
            v-model="revisionNote"
            class="revision-textarea"
            rows="4"
            placeholder="Contoh: Data alamat belum lengkap, lampiran tidak terbaca..."
          />
        </div>
        <template #footer>
          <Button variant="secondary" @click="showRevisionModal = false">Batal</Button>
          <Button variant="danger" :loading="saving" :disabled="!revisionNote.trim()" @click="submitRevision">
            Ajukan Perbaikan
          </Button>
        </template>
      </Modal>

      <!-- Edit Data Berita Acara Modal -->
      <Modal :show="showEditModal" title="Edit Data Berita Acara" size="lg" @close="showEditModal = false">
        <div v-if="editMode === 'template'" class="edit-form-wrap">
          <DynamicForm
            ref="editDynamicRef"
            :fields="templateFields"
            :initial-data="item?.form_data || undefined"
            @update:values="editValues = $event"
          />
        </div>
        <div v-else class="legacy-edit-grid">
          <template v-for="f in legacyEditFields" :key="f.key">
            <FormInput
              v-if="f.type === 'text'"
              v-model="legacyEdit[f.key]"
              :label="f.label"
            />
            <FormTextarea
              v-else
              v-model="legacyEdit[f.key]"
              :label="f.label"
              :rows="2"
            />
          </template>
        </div>
        <template #footer>
          <Button variant="outline" @click="showEditModal = false">Batal</Button>
          <Button :loading="savingEdit" @click="submitEdit">Simpan</Button>
        </template>
      </Modal>

      <!-- Revision Detail Modal -->
      <Modal :show="showRevisionDetail" title="Detail Revisi" @close="showRevisionDetail = false">
        <div v-if="selectedRevision" class="revision-diff">
          <div class="revision-diff__note">
            <strong>Catatan:</strong> {{ selectedRevision.note }}
          </div>
          <div v-if="getRevisionDiff(selectedRevision).length === 0" class="revision-diff__empty">
            Tidak ada perubahan data form.
          </div>
          <div v-else class="revision-diff__list">
            <div v-for="(diff, i) in getRevisionDiff(selectedRevision)" :key="i" class="revision-diff__item">
              <div class="revision-diff__label">{{ diff.label }}</div>
              <div class="revision-diff__values">
                <div class="revision-diff__old">
                  <span class="revision-diff__tag revision-diff__tag--old">Sebelum</span>
                  {{ diff.old }}
                </div>
                <div class="revision-diff__new">
                  <span class="revision-diff__tag revision-diff__tag--new">Sesudah</span>
                  {{ diff.new }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </Modal>

      <!-- Assign Analis Confirmation Modal -->
      <Modal :show="showAssignConfirm" title="Assign Analis" size="sm" :closable="false">
        <div class="assign-confirm">
          <div class="assign-confirm__intro">
            <div class="assign-confirm__icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
            </div>
            <div class="assign-confirm__intro-text">
              <p class="assign-confirm__title">Konfirmasi Penugasan</p>
              <p class="assign-confirm__desc">Analis berikut akan ditugaskan pada berita acara ini.</p>
            </div>
          </div>

          <div class="assign-confirm__people">
            <div v-for="a in selectedAnalysts" :key="a.id" class="assign-confirm__person">
              <span class="person-avatar">{{ initials(a.name) }}</span>
              <div class="person-info">
                <span class="person-name">{{ a.name }}</span>
                <span class="person-email">{{ a.email }}</span>
              </div>
              <span class="person-check">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
              </span>
            </div>
          </div>

          <FormTextarea
            v-model="assignNote"
            label="Catatan untuk analis"
            :rows="3"
            placeholder="Opsional — instrumen prioritas, tenggat pengerjaan, atau instruksi khusus..."
          />
        </div>
        <template #footer>
          <Button variant="outline" @click="showAssignConfirm = false">Batal</Button>
          <Button variant="success" :loading="savingAnalyst" @click="confirmAssign">Ya, Assign</Button>
        </template>
      </Modal>

      <!-- Verification Modal -->
      <Modal :show="showVerification" title="Verifikasi Berita Acara" @close="showVerification = false">
        <BeritaAcaraVerification :berita-acara="item!" @submit="handleVerify" />
      </Modal>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}
.page-header__left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.page-header h1 {
  font-size: 24px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.rejection-banner {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  margin-bottom: 24px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 10px;
}
.rejection-banner__icon {
  color: #dc2626;
  flex-shrink: 0;
  margin-top: 1px;
}
.rejection-banner__title {
  font-size: 14px;
  font-weight: 600;
  color: #b91c1c;
}
.rejection-banner__note {
  font-size: 13px;
  color: #991b1b;
  margin-top: 2px;
  line-height: 1.5;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: 24px;
  align-items: start;
}

@media (max-width: 900px) {
  .detail-grid { grid-template-columns: 1fr; }
}

.action-stack {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.section-divider {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.override-note {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 10px 12px;
  margin-bottom: 10px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  font-size: 12.5px;
  color: #1d4ed8;
  line-height: 1.45;
}

.assign-form {
  margin-top: 12px;
  padding: 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.assign-summary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.assign-summary__label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  white-space: nowrap;
}

.assign-summary__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  flex: 1;
}

.assign-chip {
  padding: 3px 10px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 500;
  border-radius: 999px;
}

.assign-edit {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #64748b;
  border-radius: 8px;
  cursor: pointer;
  flex-shrink: 0;
  transition: background 0.1s, color 0.1s;
}

.assign-edit:hover {
  background: #eff6ff;
  color: #2563eb;
}

.revision-diff__note {
  padding: 10px 12px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 8px;
  font-size: 13px;
  color: #92400e;
  margin-bottom: 16px;
}
.revision-diff__empty {
  text-align: center;
  padding: 20px;
  color: #94a3b8;
  font-size: 13px;
}
.revision-diff__list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.revision-diff__item {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}
.revision-diff__label {
  padding: 8px 12px;
  background: #f8fafc;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
}
.revision-diff__values {
  display: flex;
  flex-direction: column;
}
.revision-diff__old, .revision-diff__new {
  padding: 8px 12px;
  font-size: 13px;
  color: #334155;
}
.revision-diff__old {
  background: #fef2f2;
  border-bottom: 1px solid #fecaca;
}
.revision-diff__new {
  background: #f0fdf4;
}
.revision-diff__tag {
  display: inline-block;
  padding: 1px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  margin-right: 6px;
}
.revision-diff__tag--old {
  background: #fecaca;
  color: #991b1b;
}
.revision-diff__tag--new {
  background: #bbf7d0;
  color: #166534;
}

.revision-desc { font-size: 14px; color: #64748b; margin: 0 0 12px; }
.revision-textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  font-family: inherit;
  color: #1e293b;
  resize: vertical;
  outline: none;
  transition: border-color 0.15s;
  box-sizing: border-box;
}
.revision-textarea:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
}
.revision-textarea::placeholder { color: #94a3b8; }

/* Nomor Agenda */
.agenda-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  margin-bottom: 10px;
}
.agenda-field__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}
.sample-blocked {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  margin-bottom: 10px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 8px;
  font-size: 12.5px;
  font-weight: 500;
  color: #92400e;
  line-height: 1.4;
}
.sample-blocked svg {
  color: #d97706;
  flex-shrink: 0;
}
.agenda-edit-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border: none;
  border-radius: 6px;
  background: transparent;
  color: #94a3b8;
  cursor: pointer;
  transition: all 0.15s;
  flex-shrink: 0;
}
.agenda-edit-btn:hover {
  background: #eff6ff;
  color: #2563eb;
}

.card-edit-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s;
}
.card-edit-btn:hover {
  background: #eff6ff;
  border-color: #bfdbfe;
  color: #2563eb;
}

.edit-form-wrap { max-height: 60vh; overflow-y: auto; padding-right: 4px; }
.legacy-edit-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 14px;
  max-height: 60vh;
  overflow-y: auto;
  padding-right: 4px;
}
@media (max-width: 640px) {
  .legacy-edit-grid { grid-template-columns: 1fr; }
}
.agenda-label {
  font-size: 12px;
  font-weight: 700;
  color: #334155;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}
.agenda-input {
  width: 100%;
  padding: 8px 12px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  font-family: inherit;
  color: #1e293b;
  outline: none;
  transition: border-color 0.15s;
  box-sizing: border-box;
  padding-right: 34px;
}
.agenda-input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
}
.agenda-input::placeholder { color: #94a3b8; }
.agenda-input--checking { border-color: #2563eb; }
.agenda-input--error { border-color: #ef4444; }
.agenda-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.agenda-spinner {
  position: absolute;
  right: 10px;
  width: 14px;
  height: 14px;
  border: 2px solid #bfdbfe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: agenda-spin 0.7s linear infinite;
}
@keyframes agenda-spin {
  to { transform: rotate(360deg); }
}
.agenda-status {
  position: absolute;
  right: 10px;
  font-size: 11px;
  font-weight: 600;
}
.agenda-status--ok { color: #16a34a; }
.agenda-status--error { color: #ef4444; }
.agenda-hint { font-size: 11px; color: #94a3b8; }
.agenda-hint--error { color: #ef4444; }
.agenda-saving { font-size: 11px; color: #2563eb; }

.assign-confirm {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.assign-confirm__intro {
  display: flex;
  align-items: center;
  gap: 12px;
}
.assign-confirm__icon {
  flex-shrink: 0;
  width: 42px;
  height: 42px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--color-accent-soft), #eef2ff);
  color: var(--color-accent);
  border: 1px solid var(--color-accent-border);
}
.assign-confirm__intro-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.assign-confirm__title {
  font-size: 14.5px;
  font-weight: 600;
  color: var(--color-text);
  margin: 0;
}
.assign-confirm__desc {
  font-size: 12.5px;
  color: var(--color-text-muted);
  margin: 0;
}
.assign-confirm__people {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  overflow: hidden;
  background: var(--color-surface);
}
.assign-confirm__person {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
}
.assign-confirm__person + .assign-confirm__person {
  border-top: 1px solid var(--color-border-light);
}
.person-avatar {
  flex-shrink: 0;
  width: 30px;
  height: 30px;
  border-radius: var(--radius-full);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.4px;
  background: linear-gradient(135deg, var(--color-accent), var(--color-info));
  color: #fff;
}
.person-info {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}
.person-name {
  font-size: 13.5px;
  font-weight: 600;
  color: var(--color-text);
}
.person-email {
  font-size: 11.5px;
  color: var(--color-text-muted);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.person-check {
  margin-left: auto;
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  border-radius: var(--radius-full);
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-success-bg);
  color: var(--color-success);
}

.sample-status {
  border-radius: 8px;
  padding: 14px 16px;
}
.sample-status--done {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
}
.sample-status--done .sample-status__title {
  color: #15803d;
}
.sample-status--pending {
  background: var(--color-warning-bg);
  border: 1px solid var(--color-warning-border);
}
.sample-status__title {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-warning);
}
.sample-status__desc {
  font-size: 13px;
  color: #a16207;
  margin: 4px 0 0;
}
</style>
