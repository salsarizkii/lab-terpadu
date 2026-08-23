<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Check } from '@lucide/vue'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Button from '../components/common/Button.vue'
import BeritaAcaraForm from '../components/user/BeritaAcaraForm.vue'
import { beritaAcaraApi } from '../api/beritaAcara'
import { uploadApi } from '../api/upload'
import type { BeritaAcara } from '../types'
import { toast } from 'vue-sonner'

const router = useRouter()
const loading = ref(false)
const created = ref<BeritaAcara | null>(null)

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

async function handleSubmit(data: { form_data: Record<string, any>; form_template_id: number | null; templateFields: any[] }) {
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
    toast.success('Berita Acara berhasil dibuat!')
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
            <Button @click="router.push(`/user/berita-acara/${created.id}`)">Lihat Detail Pengajuan</Button>
          </div>
        </Card>
      </template>

      <Card v-else>
        <BeritaAcaraForm @submit="handleSubmit" :loading="loading" />
      </Card>
    </div>
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
</style>
