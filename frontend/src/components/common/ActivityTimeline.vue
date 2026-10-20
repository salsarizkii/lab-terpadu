<script setup lang="ts">
import { ref, computed } from 'vue'
import type { ActivityLog } from '../../types'
import Badge from '../common/Badge.vue'
import { formatDateTime } from '../../utils/date'

const props = defineProps<{
  logs: ActivityLog[]
  showDetail?: boolean
  showProses?: boolean
  revisionActive?: boolean
}>()

const showAll = ref(false)
const displayedLogs = computed(() => (showAll.value ? props.logs : props.logs.slice(0, 2)))

const emit = defineEmits<{
  detail: [log: ActivityLog]
  proses: [log: ActivityLog]
}>()

function hasDetail(log: ActivityLog) {
  return log.action === 'revision_requested' && Boolean(log.revision_id)
}

function latestRevisionLog() {
  return props.logs.find(l => l.action === 'revision_requested' && Boolean(l.revision_id))
}

function isLatestRevision(log: ActivityLog) {
  return hasDetail(log) && log.id === latestRevisionLog()?.id
}

function isProsesTarget(log: ActivityLog) {
  return Boolean(props.showProses) && isLatestRevision(log)
}

function isPendingRevision(log: ActivityLog) {
  return Boolean(props.revisionActive) && isLatestRevision(log)
}

function showActions(log: ActivityLog) {
  return Boolean(props.showDetail && hasDetail(log) && !isPendingRevision(log)) || isProsesTarget(log)
}

function showDetailButton(log: ActivityLog) {
  return Boolean(props.showDetail) && hasDetail(log) && !isPendingRevision(log)
}

function onDetail(log: ActivityLog) {
  emit('detail', log)
}

function onProses(log: ActivityLog) {
  emit('proses', log)
}

const ACTION_META: Record<string, { label: string; type: 'success' | 'warning' | 'danger' | 'info' | 'default' }> = {
  created: { label: 'Berita Acara Diajukan', type: 'info' },
  resubmitted: { label: 'Dikirim Ulang', type: 'info' },
  verified: { label: 'Diverifikasi', type: 'success' },
  admin_verified: { label: 'Diverifikasi Admin', type: 'success' },
  penyelia_verified: { label: 'Diverifikasi Penyelia', type: 'success' },
  rekap_verified: { label: 'Diverifikasi Manager Teknis', type: 'success' },
  completed: { label: 'Berita Acara Selesai', type: 'success' },
  revision_requested: { label: 'Revisi Diminta', type: 'warning' },
  rejected: { label: 'Ditolak', type: 'danger' },
  nomor_agenda: { label: 'Nomor Agenda', type: 'info' },
  analyst_assigned: { label: 'Analis Ditugaskan', type: 'info' },
  penyelia_assigned: { label: 'Penyelia Ditugaskan', type: 'info' },
  manager_teknis_assigned: { label: 'Manager Teknis Ditugaskan', type: 'info' },
  payment_confirmed: { label: 'Pembayaran Disetujui', type: 'success' },
  payment_rejected: { label: 'Pembayaran Ditolak', type: 'danger' },
  payment_uploaded: { label: 'Bukti Pembayaran Diunggah', type: 'info' },
  berkas_ulang_updated: { label: 'Upload Berkas Ulang Diperbarui', type: 'info' },
  berkas_ulang_removed: { label: 'Upload Berkas Ulang Dihapus', type: 'warning' },
  sample_taken: { label: 'Sampel Diambil', type: 'success' },
  sample_ready: { label: 'Hasil Siap Diambil', type: 'success' },
  sample_updated: { label: 'Hasil Pengujian Diperbarui', type: 'info' },
  pengujian_saved: { label: 'Laporan Pengujian Disimpan', type: 'info' },
  rekap_saved: { label: 'Rekap Analis Disimpan', type: 'info' },
  discount_requested: { label: 'Diskon Diajukan', type: 'info' },
  discount_confirmed: { label: 'Diskon Dikonfirmasi', type: 'success' },
  discount_approved: { label: 'Diskon Disetujui', type: 'success' },
  discount_rejected: { label: 'Diskon Ditolak', type: 'danger' },
  discount_given: { label: 'Diskon Diberikan', type: 'success' },
  discount_updated: { label: 'Diskon Diperbarui', type: 'info' },
  discount_removed: { label: 'Diskon Dihapus', type: 'warning' },
  certificate_downloaded: { label: 'Sertifikat Diunduh', type: 'info' },
  certificate_uploaded: { label: 'Sertifikat Diunggah', type: 'success' },
  certificate_removed: { label: 'Sertifikat Dihapus', type: 'warning' },
}

function isRevisionFixed(log: ActivityLog) {
  if (log.action !== 'revision_requested') return false
  const idx = props.logs.findIndex(l => l.id === log.id)
  if (idx < 0) return false
  return props.logs.slice(0, idx).some(l => l.action === 'resubmitted')
}

function isReassigned(log: ActivityLog) {
  if (!['analyst_assigned', 'penyelia_assigned', 'manager_teknis_assigned'].includes(log.action)) return false
  const idx = props.logs.findIndex(l => l.id === log.id)
  if (idx < 0) return false
  return props.logs.slice(idx + 1).some(l => l.action === log.action)
}

function meta(log: ActivityLog) {
  if (log.action === 'revision_requested' && isRevisionFixed(log)) {
    return { label: 'Revisi telah diperbaiki', type: 'success' as const }
  }
  const base = ACTION_META[log.action] ?? { label: log.action, type: 'default' as const }
  if (isReassigned(log)) {
    return { label: `${base.label} (Revisi)`, type: base.type }
  }
  return base
}

function roleLabel(role?: string) {
  const map: Record<string, string> = {
    superadmin: 'Super Admin',
    admin: 'Admin',
    analyst: 'Analis',
    penyelia: 'Penyelia',
    manager_teknis: 'Manager Teknis',
    user: 'Pemohon',
  }
  return role ? (map[role] ?? role) : '-'
}
</script>

<template>
  <div class="activity-timeline">
    <div v-if="!logs.length" class="activity-timeline__empty">
      Belum ada aktivitas tercatat
    </div>

    <ol v-else class="activity-timeline__list">
      <li v-for="log in displayedLogs" :key="log.id" class="activity-timeline__item">
        <span class="activity-timeline__dot" :class="`activity-timeline__dot--${meta(log).type}`" />
        <div class="activity-timeline__body">
          <div class="activity-timeline__row">
            <Badge :type="meta(log).type" :text="meta(log).label" size="sm" />
            <div class="activity-timeline__right">
              <span class="activity-timeline__time">
                {{ formatDateTime(log.created_at) }}
              </span>
              <div v-if="showActions(log)" class="activity-timeline__actions">
                <button
                  v-if="showDetailButton(log)"
                  class="activity-timeline__action-btn activity-timeline__action-btn--detail"
                  type="button"
                  @click="onDetail(log)"
                >
                  📄 Detail Perbaikan
                </button>
                <button
                  v-if="isProsesTarget(log)"
                  class="activity-timeline__action-btn activity-timeline__action-btn--proses"
                  type="button"
                  @click="onProses(log)"
                >
                  Perbaiki
                </button>
              </div>
            </div>
          </div>
          <p v-if="log.description" class="activity-timeline__desc">{{ log.description }}</p>
          <div class="activity-timeline__actor">
            <span v-if="log.user" class="activity-timeline__actor-name">{{ log.user.name }}</span>
            <span v-if="log.user" class="activity-timeline__actor-email">{{ log.user.email }}</span>
            <span class="activity-timeline__actor-role">role: {{ roleLabel(log.user?.role) }}</span>
            <span v-if="!log.user" class="activity-timeline__actor-name">Sistem</span>
          </div>
        </div>
      </li>
    </ol>
    <button
      v-if="logs.length > 2"
      type="button"
      class="activity-timeline__toggle"
      @click="showAll = !showAll"
    >
      {{ showAll ? 'Sembunyikan' : `Lihat semua riwayat aktivitas (${logs.length})` }}
    </button>
  </div>
</template>

<style scoped>
.activity-timeline__empty {
  padding: 24px;
  text-align: center;
  color: var(--color-text-muted, #94a3b8);
  font-size: 13px;
}

.activity-timeline__list {
  list-style: none;
  margin: 0;
  padding: 0;
  position: relative;
}

.activity-timeline__list::before {
  content: '';
  position: absolute;
  left: 7px;
  top: 8px;
  bottom: 8px;
  width: 2px;
  background: var(--color-border, #e2e8f0);
}

.activity-timeline__item {
  position: relative;
  padding-left: 28px;
  padding-bottom: 20px;
}

.activity-timeline__item:last-child {
  padding-bottom: 0;
}

.activity-timeline__dot {
  position: absolute;
  left: 2px;
  top: 4px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: var(--color-text-muted, #94a3b8);
  border: 2px solid #fff;
  box-shadow: 0 0 0 2px var(--color-border, #e2e8f0);
}

.activity-timeline__dot--success { background: var(--color-success, #10b981); }
.activity-timeline__dot--warning { background: var(--color-warning, #f59e0b); }
.activity-timeline__dot--danger { background: var(--color-danger, #ef4444); }
.activity-timeline__dot--info { background: var(--color-info, #6366f1); }

.activity-timeline__row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
  flex-wrap: wrap;
}

.activity-timeline__right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 6px;
}

.activity-timeline__time {
  font-size: 12px;
  color: var(--color-text-muted, #94a3b8);
}

.activity-timeline__actions {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.activity-timeline__action-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  border-radius: 6px;
  border: 1px solid var(--color-border, #e2e8f0);
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: opacity 0.15s ease;
  font-family: inherit;
}

.activity-timeline__action-btn:hover {
  opacity: 0.8;
}

.activity-timeline__action-btn--detail {
  background: var(--color-info-bg, #e0e7ff);
  color: #3730a3;
}

.activity-timeline__action-btn--proses {
  background: var(--color-success-bg, #d1fae5);
  color: #065f46;
}

.activity-timeline__desc {
  margin: 6px 0 0;
  font-size: 13px;
  color: var(--color-text-secondary, #475569);
}

.activity-timeline__actor {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 8px;
  font-size: 12px;
}

.activity-timeline__actor-name {
  font-weight: 600;
  color: var(--color-text, #0f172a);
}

.activity-timeline__actor-email {
  color: var(--color-text-secondary, #475569);
}

.activity-timeline__actor-role {
  padding: 2px 8px;
  border-radius: 100px;
  background: var(--color-info-bg, #e0e7ff);
  color: #3730a3;
  font-size: 11px;
  font-weight: 500;
}

.activity-timeline__toggle {
  margin-top: 12px;
  width: 100%;
  padding: 7px 10px;
  border: 1px solid var(--color-border, #e2e8f0);
  border-radius: 8px;
  background: #fff;
  font-size: 12px;
  font-weight: 600;
  color: #2563eb;
  cursor: pointer;
  font-family: inherit;
  transition: background 0.15s, border-color 0.15s;
}

.activity-timeline__toggle:hover {
  background: #eff6ff;
  border-color: #bfdbfe;
}
</style>
