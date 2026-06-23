<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Alert from '../components/common/Alert.vue'
import BeritaAcaraForm from '../components/user/BeritaAcaraForm.vue'
import { beritaAcaraApi } from '../api/beritaAcara'

const router = useRouter()
const error = ref('')
const loading = ref(false)

async function handleSubmit(data: any) {
  error.value = ''
  loading.value = true
  try {
    const ba = await beritaAcaraApi.create(data)
    router.push(`/user/berita-acara/${ba.id}`)
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
      <Card>
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
</style>
