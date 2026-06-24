<script setup lang="ts">
import { ref, onMounted } from 'vue'
import FormInput from '../common/FormInput.vue'
import FormTextarea from '../common/FormTextarea.vue'
import Button from '../common/Button.vue'
import type { BeritaAcara } from '../../types'

const props = defineProps<{
  beritaAcara: BeritaAcara
  loading?: boolean
  existing?: any | null
}>()

const emit = defineEmits<{
  submit: [data: any]
}>()

const nama = ref('')
const agenda = ref('')
const jumlahPengujian = ref('')
const namaPengujian = ref('')
const tanggalPermohonan = ref('')
const tanggalPengujian = ref('')
const analis = ref('')
const pengambilanHasil = ref('')
const namaFilePengujian = ref('')
const keteranganPengujian = ref('')
const verifikator = ref('')

onMounted(() => {
  if (props.existing) {
    nama.value = props.existing.nama || ''
    agenda.value = props.existing.agenda || ''
    jumlahPengujian.value = props.existing.jumlah_pengujian || ''
    namaPengujian.value = props.existing.nama_pengujian || ''
    tanggalPermohonan.value = props.existing.tanggal_permohonan || ''
    tanggalPengujian.value = props.existing.tanggal_pengujian || ''
    analis.value = props.existing.analis || ''
    pengambilanHasil.value = props.existing.pengambilan_hasil || ''
    namaFilePengujian.value = props.existing.nama_file_pengujian || ''
    keteranganPengujian.value = props.existing.keterangan_pengujian || ''
    verifikator.value = props.existing.verifikator || ''
  }
})

function handleSubmit() {
  emit('submit', {
    nama: nama.value,
    agenda: agenda.value,
    jumlah_pengujian: jumlahPengujian.value,
    nama_pengujian: namaPengujian.value,
    tanggal_permohonan: tanggalPermohonan.value || undefined,
    tanggal_pengujian: tanggalPengujian.value || undefined,
    analis: analis.value,
    pengambilan_hasil: pengambilanHasil.value,
    nama_file_pengujian: namaFilePengujian.value,
    keterangan_pengujian: keteranganPengujian.value,
    verifikator: verifikator.value,
  })
}
</script>

<template>
  <form @submit.prevent="handleSubmit" class="pengujian-form">
    <div class="form-grid">
      <FormInput v-model="nama" label="Nama" required />
      <FormInput v-model="agenda" label="Agenda" />
      <FormInput v-model="jumlahPengujian" label="Jumlah Pengujian (Total Replikasi, Jumlah Cawan, Jumlah Pengukuran)" />
      <FormInput v-model="namaPengujian" label="Nama Pengujian (FTIR, DMA, Anti Bakteri, AAS dll)" />
      <FormInput v-model="tanggalPermohonan" label="Tanggal Permohonan" type="date" />
      <FormInput v-model="tanggalPengujian" label="Tanggal Pengujian" type="date" />
      <FormInput v-model="analis" label="Analis" />
      <div class="form-group hasil-group">
        <label class="form-label">Pengambilan Hasil</label>
        <textarea v-model="pengambilanHasil" class="form-textarea hasil-textarea" rows="3"></textarea>
      </div>
      <FormInput v-model="namaFilePengujian" label="Nama File Pengujian (contoh: FTIR_NamaCustomer_NoAgenda)" />
      <FormTextarea v-model="keteranganPengujian" label="Keterangan Pengujian (Detail Replikasi, Jumlah Cawan, Jumlah Pengukuran)" />
      <FormInput v-model="verifikator" label="Verifikator" />
    </div>
    <div class="form-actions">
      <Button type="submit" :disabled="loading">
        {{ loading ? 'Menyimpan...' : existing ? 'Update Pengujian' : 'Simpan Pengujian' }}
      </Button>
    </div>
  </form>
</template>

<style scoped>
.form-grid {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.form-group {
  margin-bottom: 4px;
}
.form-label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: var(--color-text-secondary);
  margin-bottom: 4px;
}
.form-textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  font-family: var(--font-primary);
  font-size: 14px;
  color: var(--color-text);
  resize: vertical;
  outline: none;
  transition: var(--transition);
  box-sizing: border-box;
}
.form-textarea:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-soft);
}
.hasil-textarea {
  background: #dcfce7;
  border-color: #86efac;
}
.hasil-textarea:focus {
  border-color: #22c55e;
  box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
}
.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}
</style>
