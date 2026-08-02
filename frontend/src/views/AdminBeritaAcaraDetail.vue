<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
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
import { adminApi } from '../api/admin'
import { formTemplateApi } from '../api/formTemplate'
import { pdfTemplateApi } from '../api/pdfTemplate'
import { useAuthStore } from '../stores/authStore'
import type { BeritaAcara, User, FormField } from '../types'
import { toast } from 'vue-sonner'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const item = ref<BeritaAcara | null>(null)
const analysts = ref<User[]>([])
const loading = ref(true)
const saving = ref(false)
const showVerification = ref(false)
const showRevisionModal = ref(false)
const revisionNote = ref('')
const showRevisionDetail = ref(false)
const selectedRevision = ref<any>(null)
const templateFields = ref<FormField[]>([])
const showAssignForm = ref(false)
const nomorAgenda = ref('')
const savingAgenda = ref(false)

const currentAnalystIds = computed(() =>
 item.value?.analysts?.map(a => a.id) || (item.value?.analyst_id ? [item.value.analyst_id] : [])
)

const statusMap: Record<string, string> = {
  draft: 'Draft', pending: 'Diajukan', revision: 'Perlu Perbaikan', approved: 'Disetujui', rejected: 'Ditolak',
}
const statusTypeMap: Record<string, 'default' | 'warning' | 'success' | 'danger' | 'info'> = {
  draft: 'default', pending: 'warning', revision: 'warning', approved: 'success', rejected: 'danger',
}

const canAssign = computed(() =>
  item.value?.status === 'approved'
)

onMounted(async () => {
  const id = Number(route.params.id)
  if (!id || isNaN(id)) {
    toast.error('ID tidak valid')
    loading.value = false
    return
  }
  try {
    const [baData, analystsData] = await Promise.all([
      adminApi.getBeritaAcaraDetail(id),
      adminApi.getAnalysts(),
    ])
    item.value = baData
    analysts.value = analystsData
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
      item.value = { ...item.value, nomor_agenda: updated.nomor_agenda || value }
    }
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menyimpan nomor agenda')
  } finally {
    savingAgenda.value = false
  }
}

async function handleAssign(analystIds: number[]) {
 if (!item.value) return
 saving.value = true
 try {
   await adminApi.assignAnalyst(item.value.id, analystIds)
   const updated = await adminApi.getBeritaAcaraDetail(item.value.id)
   item.value = updated
   toast.success('Analis berhasil di-assign')
 } catch (e: any) {
   toast.error(e.response?.data?.message || 'Gagal')
 } finally {
   saving.value = false
 }
}

function openRevisionModal() {
  revisionNote.value = ''
  showRevisionModal.value = true
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

function downloadPdfBeritaAcara() {
  if (!item.value) return
  window.open(pdfTemplateApi.getDownloadUrl('berita_acara', item.value.id), '_blank')
}

function downloadPdfRekapAnalis() {
  if (!item.value) return
  window.open(pdfTemplateApi.getDownloadUrl('rekap_analis', item.value.id), '_blank')
}

function downloadPdfTagihan() {
  if (!item.value?.payment) return
  window.open(pdfTemplateApi.getDownloadUrl('tagihan', item.value.payment.id), '_blank')
}

function downloadPdfSertifikat() {
  if (!item.value?.certificate) return
  window.open(pdfTemplateApi.getDownloadUrl('sertifikat', item.value.certificate.id), '_blank')
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
            <Button variant="secondary" size="sm" @click="downloadPdfBeritaAcara">
              📄 PDF Berita Acara
            </Button>
            <Button variant="secondary" size="sm" @click="downloadPdfRekapAnalis">
              🧪 PDF Rekap Analis
            </Button>
            <Button v-if="item.payment" variant="secondary" size="sm" @click="downloadPdfTagihan">
              💳 PDF Tagihan
            </Button>
            <Button v-if="item.certificate" variant="secondary" size="sm" @click="downloadPdfSertifikat">
              📜 PDF Sertifikat
            </Button>
          </div>
          <Badge :type="statusTypeMap[item.status]" :text="statusMap[item.status]" />
        </div>

        <div class="detail-grid">
          <div class="detail-main">
            <Card title="Data Berita Acara">
              <BeritaAcaraDetailView :berita-acara="item" />
            </Card>
          </div>

          <div class="detail-sidebar">
            <Card title="Aksi">
              <div class="action-stack">
                <template v-if="auth.hasPermission('berita_acara_verify')">
                  <div class="agenda-field">
                    <label class="agenda-label" for="nomor-agenda">Nomor Agenda</label>
                    <input
                      id="nomor-agenda"
                      v-model="nomorAgenda"
                      class="agenda-input"
                      type="text"
                      placeholder="Contoh: AGD-2026-001"
                      @blur="saveNomorAgenda"
                      @keyup.enter="saveNomorAgenda"
                    />
                    <span v-if="savingAgenda" class="agenda-saving">Menyimpan...</span>
                    <span class="agenda-hint">Wajib diisi sebelum verifikasi.</span>
                  </div>

                  <Button
                    block
                    @click="showVerification = true"
                    :disabled="item.status === 'draft' || item.status === 'approved' || !nomorAgenda.trim()"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/></svg>
                    Verifikasi
                  </Button>

                  <Button
                    block
                    variant="danger"
                    @click="openRevisionModal"
                    :disabled="item.status === 'draft' || item.status === 'revision' || item.status === 'approved'"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    Perlu Perbaikan
                  </Button>
                </template>

                <template v-if="auth.hasPermission('berita_acara_assign')">
                  <div class="section-divider">
                    <Button
                      block
                      variant="outline"
                      :disabled="!canAssign"
                      @click="showAssignForm = !showAssignForm"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line id="line" x1="19" x2="19" y1="8" y2="14"/><line id="line" x1="22" x2="16" y1="11" y2="11"/></svg>
                      {{ currentAnalystIds.length ? 'Ganti Analis' : 'Assign Analis' }}
                    </Button>

                    <div v-if="showAssignForm" class="assign-form">
                      <AnalystAssignment
                        :berita-acara-id="item.id"
                        :analysts="analysts"
                        v-model="currentAnalystIds"
                        :loading="saving"
                        @assign="handleAssign"
                      />
                    </div>
                  </div>
                </template>
              </div>
            </Card>
          </div>
        </div>

        <!-- Revisions history -->
        <div v-if="item.revisions && item.revisions.length" style="margin-top: 24px;">
          <Card title="Riwayat Perbaikan">
            <div v-for="rev in item.revisions" :key="rev.id" class="revision-item">
              <div class="revision-header">
                <div class="revision-note">{{ rev.note }}</div>
                <button
                  v-if="rev.previous_form_data"
                  class="revision-detail-btn"
                  @click="openRevisionDetail(rev)"
                >
                  Detail
                </button>
              </div>
              <div class="revision-date">{{ new Date(rev.created_at).toLocaleString('id-ID') }}</div>
            </div>
          </Card>
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

.revision-item {
  padding: 12px 0;
  border-bottom: 1px solid #f1f5f9;
}
.revision-item:last-child { border-bottom: none; }
.revision-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 8px;
}
.revision-note { font-size: 14px; color: #334155; }
.revision-date { font-size: 12px; color: #94a3b8; margin-top: 4px; }

.revision-detail-btn {
  flex-shrink: 0;
  padding: 3px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: #fff;
  font-size: 12px;
  color: #2563eb;
  cursor: pointer;
  transition: all 0.15s;
  font-family: inherit;
}
.revision-detail-btn:hover {
  background: #eff6ff;
  border-color: #bfdbfe;
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
}
.agenda-input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
}
.agenda-input::placeholder { color: #94a3b8; }
.agenda-hint { font-size: 11px; color: #94a3b8; }
.agenda-saving { font-size: 11px; color: #2563eb; }
</style>
