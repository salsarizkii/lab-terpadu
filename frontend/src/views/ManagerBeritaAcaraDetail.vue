<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import SkeletonDetail from '../components/common/SkeletonDetail.vue'
import Button from '../components/common/Button.vue'
import BeritaAcaraDetailView from '../components/admin/BeritaAcaraDetailView.vue'
import RekapAnalisView from '../components/common/RekapAnalisView.vue'
import ActivityTimeline from '../components/common/ActivityTimeline.vue'
import VerificationPanel from '../components/admin/VerificationPanel.vue'
import DownloadMenu from '../components/common/DownloadMenu.vue'
import { verificationApi } from '../api/verification'
import type { BeritaAcara } from '../types'
import { baStatusMap, baStatusTypeMap } from '../utils/baStatus'
import { toast } from 'vue-sonner'

const route = useRoute()
const router = useRouter()

const item = ref<BeritaAcara | null>(null)
const loading = ref(true)
const saving = ref(false)

onMounted(async () => {
  const id = Number(route.params.id)
  if (!id || isNaN(id)) {
    toast.error('ID tidak valid')
    loading.value = false
    return
  }
  try {
    item.value = await verificationApi.getManagerBeritaAcaraDetail(id)
  } catch {
    toast.error('Gagal memuat data')
  } finally {
    loading.value = false
  }
})

async function handleVerify(data: { status: string }) {
  if (!item.value) return
  saving.value = true
  try {
    const updated = await verificationApi.managerVerify(item.value.id, {
      status: data.status as 'completed',
    })
    item.value = updated
    toast.success('Rekap disetujui, berita acara kembali ke pemohon')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal')
  } finally {
    saving.value = false
  }
}

async function handleRevision(note: string) {
  if (!item.value) return
  saving.value = true
  try {
    const updated = await verificationApi.managerVerify(item.value.id, {
      status: 'revision',
      note,
    })
    item.value = updated
    toast.success('Perubahan telah dikirim ke pemohon')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal')
  } finally {
    saving.value = false
  }
}

async function handleReject(note: string) {
  if (!item.value) return
  saving.value = true
  try {
    const updated = await verificationApi.managerVerify(item.value.id, {
      status: 'rejected',
      note,
    })
    item.value = updated
    toast.success('Berita acara ditolak')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AppLayout>
    <div>
      <SkeletonDetail v-if="loading" />

      <template v-else-if="item">
        <div class="page-header">
          <div class="page-header__left">
            <Button variant="outline" @click="router.push('/manager-teknis/berita-acara')">Kembali</Button>
            <DownloadMenu :berita-acara="item" show-rekap />
          </div>
          <Badge :type="baStatusTypeMap[item.status]" :text="baStatusMap[item.status]" />
        </div>

        <div class="detail-grid">
          <div class="detail-main">
            <Card title="Data Berita Acara">
              <BeritaAcaraDetailView :berita-acara="item" />
            </Card>

            <Card title="Rekap Analis" style="margin-top: 24px;">
              <RekapAnalisView :berita-acara="item" />
            </Card>

            <Card title="Riwayat Aktivitas" style="margin-top: 24px;">
              <ActivityTimeline
                :logs="item.activity_logs || []"
                show-detail
                :revision-active="item.status === 'revision'"
              />
            </Card>
          </div>

          <div class="detail-sidebar">
            <Card title="Aksi Verifikasi">
              <VerificationPanel
                :berita-acara="item"
                target-status="completed"
                approve-label="Setujui & Kirim ke Pemohon"
                @verify="handleVerify"
                @revision="handleRevision"
                @reject="handleReject"
              />
            </Card>
          </div>
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.page-header__left { display: flex; align-items: center; gap: 12px; }
.page-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
.detail-grid { display: grid; grid-template-columns: 1fr 360px; gap: 24px; align-items: start; }
@media (max-width: 900px) {
  .detail-grid { grid-template-columns: 1fr; }
}
</style>
