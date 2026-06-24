<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Button from '../components/common/Button.vue'
import Alert from '../components/common/Alert.vue'
import BeritaAcaraForm from '../components/user/BeritaAcaraForm.vue'
import { beritaAcaraApi } from '../api/beritaAcara'
import type { BeritaAcara } from '../types'

const router = useRouter()
const error = ref('')
const loading = ref(false)
const created = ref<BeritaAcara | null>(null)

async function handleSubmit(data: any) {
  error.value = ''
  loading.value = true
  try {
    const ba = await beritaAcaraApi.create(data)
    created.value = ba
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal membuat berita acara'
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
      <Alert v-if="error" type="error" :message="error" @dismiss="error = ''" />

      <template v-if="created">
        <Card>
          <div class="success-wrap">
            <div class="success-icon">✓</div>
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
