<script setup lang="ts">
import { ref, onMounted } from 'vue'
import FormInput from '../common/FormInput.vue'
import FormTextarea from '../common/FormTextarea.vue'
import MetodeInstrumenPicker from '../common/MetodeInstrumenPicker.vue'
import Button from '../common/Button.vue'
import type { BeritaAcara } from '../../types'

const props = withDefaults(defineProps<{
  initialData?: BeritaAcara | null
  loading?: boolean
}>(), {
  initialData: null,
  loading: false,
})

const emit = defineEmits<{
  submit: [data: FormData]
}>()

const form = ref({
  nama_pemohon: '',
  nim: '',
  prodi_fakultas_instansi: '',
  dosen_pembimbing: '',
  telp_hp: '',
  email: '',
  alamat: '',
  judul_penelitian: '',
  akses_lab: '',
  bahan_contoh_uji: '',
  kemasan_packing: '',
  permintaan_analisa: '',
  jumlah_sampel: '',
  kode_nama_sampel: '',
  keterangan_sampel: '',
  keterangan_tambahan: '',
  pengukuran_per_sampel: '',
  sisa_sampel: '',
})

onMounted(() => {
  if (props.initialData) {
    form.value.nama_pemohon = props.initialData.nama_pemohon || ''
    form.value.nim = props.initialData.nim || ''
    form.value.prodi_fakultas_instansi = props.initialData.prodi_fakultas_instansi || ''
    form.value.dosen_pembimbing = props.initialData.dosen_pembimbing || ''
    form.value.telp_hp = props.initialData.telp_hp || ''
    form.value.email = props.initialData.email || ''
    form.value.alamat = props.initialData.alamat || ''
    form.value.judul_penelitian = props.initialData.judul_penelitian || ''
    form.value.akses_lab = props.initialData.akses_lab || ''
    form.value.bahan_contoh_uji = props.initialData.bahan_contoh_uji || ''
    form.value.kemasan_packing = props.initialData.kemasan_packing || ''
    form.value.permintaan_analisa = props.initialData.permintaan_analisa || ''
    form.value.jumlah_sampel = props.initialData.jumlah_sampel || ''
    form.value.kode_nama_sampel = props.initialData.kode_nama_sampel || ''
    form.value.keterangan_sampel = props.initialData.keterangan_sampel || ''
    form.value.keterangan_tambahan = props.initialData.keterangan_tambahan || ''
    form.value.pengukuran_per_sampel = props.initialData.pengukuran_per_sampel || ''
    form.value.sisa_sampel = props.initialData.sisa_sampel || ''
  }
})

function handleSubmit() {
  const fd = new FormData()
  Object.entries(form.value).forEach(([key, val]) => {
    fd.append(key, val)
  })
  emit('submit', fd)
}
</script>

<template>
  <form class="ba-form" @submit.prevent="handleSubmit">
    <div class="ba-form__section">
      <h3 class="ba-form__section-title">Informasi Pemohon</h3>
      <FormInput v-model="form.nama_pemohon" label="Nama Pemohon" required />
      <FormInput v-model="form.nim" label="NIM" />
      <FormInput v-model="form.prodi_fakultas_instansi" label="Prodi/Fakultas/Instansi" />
      <FormInput v-model="form.dosen_pembimbing" label="Dosen Pembimbing" />
      <FormInput v-model="form.telp_hp" label="Telp/No. HP" />
      <FormInput v-model="form.email" label="Email" type="email" />
      <FormTextarea v-model="form.alamat" label="Alamat" :rows="3" />
    </div>

    <div class="ba-form__section">
      <h3 class="ba-form__section-title">Informasi Penelitian</h3>
      <FormTextarea v-model="form.judul_penelitian" label="Judul Penelitian" :rows="3" />
      <FormTextarea v-model="form.akses_lab" label="Akses Lab" :rows="3" />
      <FormTextarea v-model="form.bahan_contoh_uji" label="Bahan Contoh Uji" :rows="3" />
      <FormTextarea v-model="form.kemasan_packing" label="Kemasan/Packing" :rows="3" />
      <MetodeInstrumenPicker v-model="form.permintaan_analisa" label="Metode / Instrumen" />
      <FormInput v-model="form.jumlah_sampel" label="Jumlah Sampel" />
      <FormInput v-model="form.kode_nama_sampel" label="Kode/Nama Sampel" />
      <FormTextarea v-model="form.keterangan_sampel" label="Keterangan Sampel" :rows="3" />
      <FormTextarea v-model="form.keterangan_tambahan" label="Keterangan Tambahan" :rows="3" />
      <FormInput v-model="form.pengukuran_per_sampel" label="Pengukuran/Pengulangan per Sampel" />
      <FormTextarea v-model="form.sisa_sampel" label="Sisa Sampel Setelah Pengujian" :rows="3" />
    </div>

    <div class="ba-form__actions">
      <Button type="submit" :disabled="loading">
        {{ loading ? 'Saving...' : (initialData ? 'Update' : 'Submit') }}
      </Button>
    </div>
  </form>
</template>

<style scoped>
.ba-form__section {
  margin-bottom: 32px;
  padding: 20px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.ba-form__section-title {
  margin: 0 0 16px;
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  padding-bottom: 8px;
  border-bottom: 1px solid #e5e7eb;
}
.ba-form__actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

</style>
