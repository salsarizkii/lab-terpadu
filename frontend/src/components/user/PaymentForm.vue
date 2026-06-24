<script setup lang="ts">
import { ref, computed } from 'vue'
import FileUpload from '../common/FileUpload.vue'
import Button from '../common/Button.vue'
import type { BeritaAcara } from '../../types'

const props = defineProps<{
  beritaAcara: BeritaAcara
  loading?: boolean
}>()

const emit = defineEmits<{
  submit: [data: FormData]
}>()

const paymentProof = ref<File | null>(null)

const biayaDisplay = computed(() => {
  return props.beritaAcara.biaya
    ? `Rp ${Number(props.beritaAcara.biaya).toLocaleString('id-ID')}`
    : 'Rp 0'
})

function handleSubmit() {
  const fd = new FormData()
  fd.append('berita_acara_id', String(props.beritaAcara.id))
  if (paymentProof.value) {
    fd.append('payment_proof', paymentProof.value)
  }
  emit('submit', fd)
}
</script>

<template>
  <form @submit.prevent="handleSubmit">
    <div class="form-group">
      <label class="form-label">Total Pembayaran</label>
      <div class="biaya-display">{{ biayaDisplay }}</div>
    </div>
    <FileUpload v-model="paymentProof" label="Upload Bukti Pembayaran" accept="image/*,.pdf" />
    <div class="form-actions">
      <Button type="submit" :disabled="loading || !paymentProof">
        {{ loading ? 'Mengirim...' : 'Ajukan Pembayaran' }}
      </Button>
    </div>
  </form>
</template>

<style scoped>
.form-group {
  margin-bottom: 16px;
}
.form-label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: var(--color-text-secondary);
  margin-bottom: 4px;
}
.biaya-display {
  font-size: 24px;
  font-weight: 700;
  color: var(--color-primary);
  padding: 8px 0;
}
.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}
</style>

<style scoped>
.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}

</style>
