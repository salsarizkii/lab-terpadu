<script setup lang="ts">
import { ref, computed } from 'vue'
import Button from '../common/Button.vue'
import Modal from '../common/Modal.vue'
import BeritaAcaraVerification from './BeritaAcaraVerification.vue'
import type { BeritaAcara } from '../../types'

const props = withDefaults(defineProps<{
  beritaAcara: BeritaAcara
  targetStatus: string
  approveLabel?: string
  showRevision?: boolean
  showReject?: boolean
}>(), {
  showRevision: true,
  showReject: true,
})

const emit = defineEmits<{
  verify: [data: { status: string; sample_answer?: 'sudah' | 'belum' | null }]
  revision: [note: string]
  reject: [note: string]
}>()

const showVerify = ref(false)
const showRevisionModal = ref(false)
const showRejectModal = ref(false)
const revisionNote = ref('')
const rejectNote = ref('')
const saving = ref(false)
const showConfirmVerify = ref(false)
const pendingVerify = ref<{ status: string } | null>(null)
const sampleAnswer = ref<'sudah' | 'belum' | null>(null)

const isForwardToAnalis = computed(() => props.targetStatus === 'penyelia_verified')

const canAct = computed(() => {
  const s = props.beritaAcara.status
  if (s === props.targetStatus) return false
  // Hanya status yang benar-benar menunggu aksi verifikator ini
  return props.targetStatus === 'penyelia_verified'
    ? s === 'admin_verified'
    : s === 'rekap_ready'
})

const rejectDescription = computed(() =>
  props.targetStatus === 'penyelia_verified'
    ? 'Berita acara akan dikembalikan ke admin untuk diperbaiki. Tuliskan alasan penolakan.'
    : 'Berita acara akan ditolak permanen. Tuliskan alasan penolakan.'
)

function onVerify(data: { status: string }) {
  saving.value = false
  showVerify.value = false
  if (isForwardToAnalis.value) {
    pendingVerify.value = data
    sampleAnswer.value = null
    showConfirmVerify.value = true
    return
  }
  saving.value = true
  try {
    emit('verify', data)
  } finally {
    saving.value = false
  }
}

function confirmVerify() {
  if (!pendingVerify.value) return
  showConfirmVerify.value = false
  saving.value = true
  try {
    emit('verify', {
      ...pendingVerify.value,
      sample_answer: sampleAnswer.value,
    })
  } finally {
    saving.value = false
    pendingVerify.value = null
    sampleAnswer.value = null
  }
}

function openRevision() {
  revisionNote.value = ''
  showRevisionModal.value = true
}

function submitRevision() {
  if (!revisionNote.value.trim()) return
  saving.value = true
  try {
    emit('revision', revisionNote.value.trim())
  } finally {
    saving.value = false
    showRevisionModal.value = false
  }
}

function submitReject() {
  if (!rejectNote.value.trim()) return
  saving.value = true
  try {
    emit('reject', rejectNote.value.trim())
  } finally {
    saving.value = false
    showRejectModal.value = false
  }
}
</script>

<template>
  <div class="action-stack">
    <Button block :disabled="!canAct || saving" :loading="saving" @click="showVerify = true">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/></svg>
      {{ approveLabel || 'Setujui' }}
    </Button>

    <Button
      v-if="showRevision !== false"
      block
      variant="danger"
      :disabled="!canAct || saving"
      @click="openRevision"
    >
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
      Perlu Perbaikan
    </Button>

    <Button
      v-if="showReject !== false"
      block
      variant="danger"
      :disabled="!canAct || saving"
      @click="showRejectModal = true"
    >
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
      Tolak
    </Button>
  </div>

  <!-- Verifikasi Modal -->
  <Modal :show="showVerify" title="Verifikasi Berita Acara" @close="showVerify = false">
    <BeritaAcaraVerification
      :berita-acara="beritaAcara"
      :target-status="targetStatus"
      :approve-label="approveLabel"
      @submit="onVerify"
    />
  </Modal>

  <!-- Konfirmasi Kirim ke Analis Modal -->
  <Modal v-if="isForwardToAnalis" :show="showConfirmVerify" title="Konfirmasi Setujui & Kirim ke Analis" size="sm" :closable="false">
    <p class="confirm-question">Apakah Anda yakin ingin menyetujui dan meneruskan berita acara ini ke analis?</p>
    <div class="sample-question">
      <p class="sample-question__label">
        Apakah sampel sudah diterima lab?
        <span class="sample-question__optional">(opsional)</span>
      </p>
      <div class="sample-question__options">
        <label class="radio-option" :class="{ 'radio-option--active': sampleAnswer === 'sudah' }">
          <input type="radio" value="sudah" v-model="sampleAnswer" />
          <span>Sudah</span>
        </label>
        <label class="radio-option" :class="{ 'radio-option--active': sampleAnswer === 'belum' }">
          <input type="radio" value="belum" v-model="sampleAnswer" />
          <span>Belum</span>
        </label>
      </div>
    </div>
    <template #footer>
      <Button variant="outline" @click="showConfirmVerify = false">Batal</Button>
      <Button variant="success" @click="confirmVerify">Ya, Setujui</Button>
    </template>
  </Modal>

  <!-- Revision Modal -->
  <Modal :show="showRevisionModal" title="Perlu Perbaikan" size="sm" @close="showRevisionModal = false">
    <div class="revision-form">
      <p class="revision-desc">Tuliskan bagian mana yang perlu diperbaiki oleh pemohon.</p>
      <textarea
        v-model="revisionNote"
        class="revision-textarea"
        rows="4"
        placeholder="Contoh: Data belum lengkap, hasil rekap perlu dicek ulang..."
      />
    </div>
    <template #footer>
      <Button variant="secondary" @click="showRevisionModal = false">Batal</Button>
      <Button variant="danger" :loading="saving" :disabled="!revisionNote.trim()" @click="submitRevision">
        Ajukan Perbaikan
      </Button>
    </template>
  </Modal>

  <!-- Reject Modal -->
  <Modal :show="showRejectModal" title="Tolak Berita Acara" size="sm" @close="showRejectModal = false">
    <div class="revision-form">
      <p class="revision-desc">{{ rejectDescription }}</p>
      <textarea
        v-model="rejectNote"
        class="revision-textarea"
        rows="4"
        placeholder="Alasan menolak berita acara ini..."
      />
    </div>
    <template #footer>
      <Button variant="secondary" @click="showRejectModal = false">Batal</Button>
      <Button variant="danger" :loading="saving" :disabled="!rejectNote.trim()" @click="submitReject">Ya, Tolak</Button>
    </template>
  </Modal>
</template>

<style scoped>
.action-stack {
  display: flex;
  flex-direction: column;
  gap: 10px;
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

.confirm-question {
  font-size: 15px;
  color: var(--color-text);
  margin: 0 0 16px;
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
