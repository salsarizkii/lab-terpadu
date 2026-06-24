<script setup lang="ts">
import { ref, computed } from 'vue'
import FormSelect from '../common/FormSelect.vue'
import FormInput from '../common/FormInput.vue'
import Button from '../common/Button.vue'
import FormTextarea from '../common/FormTextarea.vue'
import Badge from '../common/Badge.vue'
import type { BeritaAcara } from '../../types'

defineProps<{
  beritaAcara: BeritaAcara
}>()

const emit = defineEmits<{
  submit: [data: { status: string; note?: string; biaya?: number; diskon_diizinkan?: boolean }]
}>()

const status = ref('')
const biaya = ref<number | null>(null)
const diskonDiizinkan = ref(false)
const note = ref('')
const loading = ref(false)

const isApproved = computed(() => status.value === 'approved')

const statusOptions = [
  { value: 'approved', label: 'Disetujui' },
  { value: 'revision', label: 'Revisi' },
  { value: 'rejected', label: 'Ditolak' },
]

function handleSubmit() {
  loading.value = true
  const data: any = { status: status.value, note: note.value || undefined }
  if (isApproved.value) {
    data.biaya = biaya.value ?? 0
    data.diskon_diizinkan = diskonDiizinkan.value
  }
  emit('submit', data)
  loading.value = false
}

const statusMap: Record<string, { type: 'success' | 'warning' | 'danger' | 'info' | 'default'; label: string }> = {
  draft: { type: 'default', label: 'Draft' },
  pending: { type: 'warning', label: 'Menunggu' },
  revision: { type: 'danger', label: 'Revisi' },
  approved: { type: 'success', label: 'Disetujui' },
  rejected: { type: 'danger', label: 'Ditolak' },
}
</script>

<template>
  <div class="verification">
    <div class="verification__info">
      <p><strong>Judul:</strong> {{ beritaAcara.judul_penelitian || '-' }}</p>
      <p><strong>Pemohon:</strong> {{ beritaAcara.nama_pemohon || '-' }}</p>
      <p><strong>Status Saat Ini:</strong>
        <Badge :type="statusMap[beritaAcara.status]?.type || 'default'" :text="statusMap[beritaAcara.status]?.label || beritaAcara.status" />
      </p>
    </div>
    <form @submit.prevent="handleSubmit">
      <FormSelect v-model="status" label="Status Baru" :options="statusOptions" required />

      <template v-if="isApproved">
        <FormInput v-model.number="biaya" label="Nominal Biaya (Rp)" type="number" required />
        <div class="form-toggle">
          <label class="toggle-label">
            <input type="checkbox" v-model="diskonDiizinkan" class="toggle-input" />
            <span class="toggle-switch"></span>
            Izinkan pengajuan diskon
          </label>
        </div>
      </template>

      <FormTextarea v-if="status === 'revision'" v-model="note" label="Catatan Revisi" required />
      <div class="verification__actions">
        <Button type="submit" :disabled="loading || !status">
          {{ loading ? 'Menyimpan...' : 'Simpan' }}
        </Button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.form-toggle {
  margin: 16px 0;
}
.toggle-label {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  color: var(--color-text);
  cursor: pointer;
  user-select: none;
}
.toggle-input {
  display: none;
}
.toggle-switch {
  position: relative;
  width: 40px;
  height: 22px;
  background: #cbd5e1;
  border-radius: 11px;
  transition: 0.2s;
  flex-shrink: 0;
}
.toggle-switch::after {
  content: '';
  position: absolute;
  top: 2px;
  left: 2px;
  width: 18px;
  height: 18px;
  background: #fff;
  border-radius: 50%;
  transition: 0.2s;
}
.toggle-input:checked + .toggle-switch {
  background: var(--color-primary);
}
.toggle-input:checked + .toggle-switch::after {
  left: 20px;
}
</style>

<style scoped>
.verification__info {
  margin-bottom: 16px;
  padding: 12px;
  background: #f9fafb;
  border-radius: 8px;
}
.verification__info p {
  margin: 4px 0;
  font-size: 14px;
}
.verification__actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}

</style>
