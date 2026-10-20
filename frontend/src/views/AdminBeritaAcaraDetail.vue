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
import AnalystAssignment from '../components/admin/AnalystAssignment.vue'
import UserAssignList from '../components/admin/UserAssignList.vue'
import ActivityTimeline from '../components/common/ActivityTimeline.vue'
import DownloadMenu from '../components/common/DownloadMenu.vue'
import { adminApi } from '../api/admin'
import { formTemplateApi } from '../api/formTemplate'
import { useAuthStore } from '../stores/authStore'
import type { BeritaAcara, User, FormField } from '../types'
import { toast } from 'vue-sonner'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

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
const showRejectModal = ref(false)
const rejectNote = ref('')
const showRevisionDetail = ref(false)
const selectedRevision = ref<any>(null)
const templateFields = ref<FormField[]>([])
const showAssignForm = ref(false)
const showPenyeliaForm = ref(false)
const showManagerTeknisForm = ref(false)
const showAssignConfirm = ref(false)
const pendingAnalystIds = ref<number[]>([])
const assignSampleAnswer = ref<'sudah' | 'belum' | null>(null)
const nomorAgenda = ref('')
const savingAgenda = ref(false)
const checkingAgenda = ref(false)
const agendaAvailable = ref(true)
let agendaCheckTimer: ReturnType<typeof setTimeout> | null = null

const canVerify = computed(() =>
  item.value &&
  item.value.status === 'pending' &&
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

const selectedAnalystNames = computed(() =>
  analysts.value.filter(a => pendingAnalystIds.value.includes(a.id)).map(a => a.name)
)

const statusMap: Record<string, string> = {
  draft: 'Draft', pending: 'Menunggu Admin', revision: 'Perlu Perbaikan', admin_verified: 'Diverifikasi Admin',
  penyelia_verified: 'Diverifikasi Penyelia', rekap_ready: 'Menunggu Manager Teknis', completed: 'Selesai', rejected: 'Ditolak',
}
const statusTypeMap: Record<string, 'default' | 'warning' | 'success' | 'danger' | 'info'> = {
  draft: 'default', pending: 'warning', revision: 'warning', admin_verified: 'info',
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
  assignSampleAnswer.value = null
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
   const res = await adminApi.assignAnalyst(item.value.id, analystIds)
   const updated = await adminApi.getBeritaAcaraDetail(item.value.id)
   item.value = updated
   showAssignForm.value = false
   toast.success(res.message)
   if (assignSampleAnswer.value === 'belum') {
     toast.warning('Jangan lupa update penyerahan sampel di sistem jika sampel sudah diterima.')
   }
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

function openRejectModal() {
  rejectNote.value = ''
  showRejectModal.value = true
}

async function submitReject() {
  if (!item.value || !rejectNote.value.trim()) return
  saving.value = true
  try {
    const updated = await adminApi.updateStatus(item.value.id, {
      status: 'rejected',
      rejection_reason: rejectNote.value.trim(),
    })
    item.value = updated
    showRejectModal.value = false
    toast.success('Berita acara ditolak')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal')
  } finally {
    saving.value = false
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

        <div v-if="item.rejection_reason && item.status === 'pending'" class="rejection-banner">
          <div class="rejection-banner__icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
          </div>
          <div class="rejection-banner__content">
            <div class="rejection-banner__title">Dikembalikan oleh Penyelia</div>
            <div class="rejection-banner__note">{{ item.rejection_reason }}</div>
          </div>
        </div>

        <div class="detail-grid">
          <div class="detail-main">
            <Card title="Data Berita Acara">
              <BeritaAcaraDetailView :berita-acara="item" />
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
                  <div v-if="!verifiedStatuses.includes(item.status)" class="agenda-field">
                    <label class="agenda-label" for="nomor-agenda">Nomor Agenda</label>
                    <div class="agenda-input-wrap">
                      <input
                        id="nomor-agenda"
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
                    :disabled="item.status === 'draft' || item.status === 'revision' || verifiedStatuses.includes(item.status)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    Perlu Perbaikan
                  </Button>

                  <Button
                    block
                    variant="danger"
                    @click="openRejectModal"
                    :disabled="item.status === 'draft' || item.status === 'revision' || verifiedStatuses.includes(item.status)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    Tolak
                  </Button>
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

      <!-- Tolak Modal -->
      <Modal :show="showRejectModal" title="Tolak Berita Acara" size="sm" @close="showRejectModal = false">
        <div class="revision-form">
          <p class="revision-desc">Berita acara akan ditolak permanen. Tuliskan alasan penolakan.</p>
          <textarea
            v-model="rejectNote"
            class="revision-textarea"
            rows="4"
            placeholder="Alasan menolak berita acara ini..."
          />
        </div>
        <template #footer>
          <Button variant="secondary" @click="showRejectModal = false">Batal</Button>
          <Button variant="danger" :loading="saving" :disabled="!rejectNote.trim()" @click="submitReject">
            Ya, Tolak
          </Button>
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
      <Modal :show="showAssignConfirm" title="Konfirmasi Assign Analis" size="sm" :closable="false">
        <p class="assign-confirm-question">Apakah Anda yakin ingin assign analis ini?</p>
        <div v-if="selectedAnalystNames.length" class="assign-confirm-list">
          <span v-for="name in selectedAnalystNames" :key="name" class="assign-confirm-chip">{{ name }}</span>
        </div>
        <div class="sample-question">
          <p class="sample-question__label">
            Apakah sampel sudah diterima lab?
            <span class="sample-question__optional">(opsional)</span>
          </p>
          <div class="sample-question__options">
            <label class="radio-option" :class="{ 'radio-option--active': assignSampleAnswer === 'sudah' }">
              <input type="radio" value="sudah" v-model="assignSampleAnswer" />
              <span>Sudah</span>
            </label>
            <label class="radio-option" :class="{ 'radio-option--active': assignSampleAnswer === 'belum' }">
              <input type="radio" value="belum" v-model="assignSampleAnswer" />
              <span>Belum</span>
            </label>
          </div>
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

.assign-confirm-question {
  font-size: 15px;
  color: var(--color-text);
  margin: 0 0 12px;
}
.assign-confirm-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 20px;
}
.assign-confirm-chip {
  padding: 4px 12px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1d4ed8;
  font-size: 13px;
  font-weight: 500;
  border-radius: 999px;
}
.sample-question {
  padding: 14px 16px;
  background: #f8fafc;
  border: 1px solid var(--color-border);
  border-radius: 8px;
}
.sample-question__label {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 10px;
}
.sample-question__optional {
  font-size: 12px;
  font-weight: 400;
  color: var(--color-text-muted);
}
.sample-question__options {
  display: flex;
  gap: 10px;
}
.radio-option {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 8px 12px;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  background: var(--color-surface);
  font-size: 14px;
  font-weight: 500;
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: var(--transition);
}
.radio-option input {
  accent-color: var(--color-accent);
}
.radio-option--active {
  border-color: var(--color-accent);
  background: var(--color-accent-soft);
  color: var(--color-accent);
}
</style>
