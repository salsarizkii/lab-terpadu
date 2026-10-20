<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Check } from '@lucide/vue'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import BeritaAcaraForm from '../components/user/BeritaAcaraForm.vue'
import { beritaAcaraApi } from '../api/beritaAcara'
import { uploadApi } from '../api/upload'
import type { BeritaAcara } from '../types'
import { toast } from 'vue-sonner'

const router = useRouter()
const loading = ref(false)
const created = ref<BeritaAcara | null>(null)
const showConfirmSubmit = ref(false)
const pendingData = ref<{ form_data: Record<string, any>; form_template_id: number | null; templateFields: any[] } | null>(null)
const sampleAnswer = ref<'sudah' | 'belum' | null>(null)
const sampleSubmitted = ref<boolean | null>(null)

async function uploadFilesInFormData(formValues: Record<string, any>, templateFields: any[]): Promise<{ cleaned: Record<string, any>; uploadAksesPath: string | null }> {
  const cleaned = { ...formValues }
  let uploadAksesPath: string | null = null
  for (const [key, value] of Object.entries(cleaned)) {
    if (value instanceof File) {
      try {
        const uploaded = await uploadApi.uploadFile(value, 'berita-acara')
        cleaned[key] = uploaded.path
        const field = templateFields.find((f: any) => `field_${f.id}` === key || `field_${templateFields.indexOf(f)}` === key)
        if (field && field.label && field.label.toLowerCase().includes('upload akses')) {
          uploadAksesPath = uploaded.path
        }
      } catch {
        toast.error(`Gagal mengupload file: ${value.name}`)
        throw new Error('File upload failed')
      }
    }
  }
  return { cleaned, uploadAksesPath }
}

function handleSubmit(data: { form_data: Record<string, any>; form_template_id: number | null; templateFields: any[] }) {
  pendingData.value = data
  sampleAnswer.value = null
  showConfirmSubmit.value = true
}

async function confirmSubmit() {
  if (!pendingData.value) return
  showConfirmSubmit.value = false
  await doSubmit(pendingData.value)
}

async function doSubmit(data: { form_data: Record<string, any>; form_template_id: number | null; templateFields: any[] }) {
  loading.value = true
  try {
    const { cleaned, uploadAksesPath } = await uploadFilesInFormData(data.form_data, data.templateFields)
    const fd = new FormData()
    fd.append('form_data', JSON.stringify(cleaned))
    if (data.form_template_id) {
      fd.append('form_template_id', String(data.form_template_id))
    }
    if (uploadAksesPath) {
      fd.append('upload_akses', uploadAksesPath)
    }
    const ba = await beritaAcaraApi.create(fd)
    created.value = ba
    if (sampleAnswer.value === 'sudah') {
      try {
        created.value = await beritaAcaraApi.markSampleSubmitted(ba.id)
        const date = new Date(created.value.sample_submitted_at || '')
        toast.success(`Sampel sudah diserahkan pada ${date.toLocaleString('id-ID', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })}`)
      } catch {
        toast.success('Berita acara sudah tersubmit')
      }
    } else if (sampleAnswer.value === 'belum') {
      sampleSubmitted.value = false
      toast.warning('Jangan lupa update di sistem jika sudah menyerahkan sampel.')
    } else {
      toast.success('Berita acara sudah tersubmit')
    }
  } catch (e: any) {
    if (e.message !== 'File upload failed') {
      toast.error(e.response?.data?.message || 'Gagal membuat berita acara')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AppLayout>
    <div>
      <div class="page-header">
        <h1>Buat Berita Acara Baru</h1>
      </div>

      <template v-if="created">
        <Card>
          <div class="success-wrap">
            <div class="success-icon">
              <Check :size="32" />
            </div>
            <h2 class="success-title">Permohonan Pengajuan Berita Acara Berhasil!</h2>
            <p class="success-desc">Pengajuan Anda telah berhasil dikirim dan sedang menunggu verifikasi oleh admin.</p>
            <div v-if="sampleSubmitted === false" class="reminder">
              Jangan lupa update di sistem jika sudah menyerahkan sampel.
            </div>
            <Button @click="router.push(`/user/berita-acara/${created.id}`)">Lihat Detail Pengajuan</Button>
          </div>
        </Card>
      </template>

      <Card v-else>
        <BeritaAcaraForm @submit="handleSubmit" :loading="loading" />
      </Card>
    </div>

    <Modal :show="showConfirmSubmit" title="Konfirmasi Submit" size="sm" :closable="false">
      <p class="modal-question">Apakah Anda yakin ingin submit berita acara ini?</p>
      <div class="sample-question">
        <p class="sample-question__label">
          Apakah Anda sudah menyerahkan sampel?
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
        <Button variant="outline" @click="showConfirmSubmit = false">Batal</Button>
        <Button variant="success" @click="confirmSubmit">Ya, Submit</Button>
      </template>
    </Modal>
  </AppLayout>
</template>

<style scoped>
.page-header {
  margin-bottom: 24px;
}
.page-header h1 {
  font-size: 24px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}
.success-wrap {
  text-align: center;
  padding: 48px 24px;
}
.success-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #22c55e;
  color: #fff;
  font-size: 28px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
}
.success-title {
  font-size: 20px;
  font-weight: 700;
  color: #166534;
  margin: 0 0 8px;
}
.success-desc {
  font-size: 14px;
  color: #475569;
  margin: 0 0 24px;
}
.reminder {
  display: inline-block;
  background: var(--color-warning-bg);
  border: 1px solid var(--color-warning-border);
  color: var(--color-warning);
  font-size: 14px;
  font-weight: 600;
  padding: 10px 16px;
  border-radius: 8px;
  margin-bottom: 24px;
}
.modal-question {
  font-size: 15px;
  color: var(--color-text);
  margin: 0;
}
.sample-question {
  margin-top: 20px;
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
