<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import SkeletonCard from '../components/common/SkeletonCard.vue'
import CertificateDownload from '../components/user/CertificateDownload.vue'
import { beritaAcaraApi } from '../api/beritaAcara'
import { certificateApi } from '../api/certificate'
import type { BeritaAcara } from '../types'

const items = ref<BeritaAcara[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    const all = await beritaAcaraApi.getList()
    items.value = all.filter(i => i.certificate)
  } catch {
  } finally {
    loading.value = false
  }
})

async function handleDownload(certificateId: number) {
  try {
    const blob = await certificateApi.download(certificateId)
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `sertifikat-${certificateId}.pdf`
    a.click()
    window.URL.revokeObjectURL(url)
  } catch {
  }
}
</script>

<template>
  <AppLayout>
    <div>
      <div class="page-header">
        <h1>Sertifikat</h1>
      </div>

      <SkeletonCard v-if="loading" />

      <div v-else class="cert-list">
        <Card v-for="item in items" :key="item.id" :title="item.judul_penelitian || 'Berita Acara'">
          <div class="cert-item">
            <div class="cert-info">
              <span>Status: {{ item.certificate?.downloaded_at ? 'Sudah diunduh' : 'Belum diunduh' }}</span>
            </div>
            <CertificateDownload :certificate-id="item.certificate!.id" @download="handleDownload" />
          </div>
        </Card>
        <div v-if="items.length === 0" class="empty">Belum ada sertifikat tersedia</div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-header { margin-bottom: 24px; }
.page-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
.cert-list { display: flex; flex-direction: column; gap: 16px; }
.cert-item { display: flex; justify-content: space-between; align-items: center; }
.cert-info { font-size: 14px; color: #475569; }
.empty { text-align: center; padding: 60px; color: #94a3b8; }
</style>
