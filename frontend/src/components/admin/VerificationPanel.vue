<script setup lang="ts">
import { ref, computed } from 'vue'
import Button from '../common/Button.vue'
import Modal from '../common/Modal.vue'
import BeritaAcaraVerification from './BeritaAcaraVerification.vue'
import type { BeritaAcara } from '../../types'

const props = defineProps<{
  beritaAcara: BeritaAcara
  targetStatus: string
  approveLabel?: string
  showRevision?: boolean
  showReject?: boolean
}>()

const emit = defineEmits<{
  verify: [data: { status: string }]
  revision: [note: string]
  reject: []
}>()

const showVerify = ref(false)
const showRevisionModal = ref(false)
const showRejectModal = ref(false)
const revisionNote = ref('')
const saving = ref(false)

const canAct = computed(() => {
  const s = props.beritaAcara.status
  return ['pending', 'admin_verified', 'penyelia_verified', 'rekap_ready', 'revision'].includes(s)
})

function onVerify(data: { status: string }) {
  saving.value = true
  try {
    emit('verify', data)
  } finally {
    saving.value = false
    showVerify.value = false
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
  saving.value = true
  try {
    emit('reject')
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
      <p class="revision-desc">Berita acara akan ditolak permanen. Lanjutkan?</p>
    </div>
    <template #footer>
      <Button variant="secondary" @click="showRejectModal = false">Batal</Button>
      <Button variant="danger" :loading="saving" @click="submitReject">Ya, Tolak</Button>
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
</style>
