<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppLayout from '../components/common/AppLayout.vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import LoadingSpinner from '../components/common/LoadingSpinner.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
import Alert from '../components/common/Alert.vue'
import SampleDataForm from '../components/analyst/SampleDataForm.vue'
import { analystApi } from '../api/analyst'
import type { BeritaAcara } from '../types'

const items = ref<BeritaAcara[]>([])
const loading = ref(true)
const error = ref('')
const selectedBa = ref<BeritaAcara | null>(null)
const showSampleModal = ref(false)
const selectedSample = ref<any>(null)

onMounted(async () => {
  try {
    items.value = await analystApi.getBeritaAcara()
  } catch {
    error.value = 'Gagal memuat data'
  } finally {
    loading.value = false
  }
})

function openSampleForm(sample: any, baId: number) {
  selectedSample.value = { ...sample, berita_acara_id: baId }
  showSampleModal.value = true
}

async function handleSampleUpdate(data: any) {
  if (!selectedSample.value) return
  try {
    await analystApi.updateSample(
      selectedSample.value.berita_acara_id,
      selectedSample.value.id,
      data
    )
    if (data.status) {
      const ba = items.value.find(i => i.id === selectedSample.value!.berita_acara_id)
      if (ba) {
        const s = ba.samples?.find(s => s.id === selectedSample.value!.id)
        if (s) s.status = data.status
        if (data.test_result !== undefined && s) s.test_result = data.test_result
      }
    }
    showSampleModal.value = false
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal update sampel'
  }
}

async function markReady(baId: number, sampleId: number) {
  try {
    await analystApi.markSampleReady(baId, sampleId)
    const ba = items.value.find(i => i.id === baId)
    if (ba) {
      const s = ba.samples?.find(s => s.id === sampleId)
      if (s) s.status = 'ready'
    }
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal'
  }
}

const sampleStatusType: Record<string, string> = {
  pending: 'default',
  processing: 'warning',
  ready: 'success',
  taken: 'info',
}
</script>

<template>
  <AppLayout>
    <div>
      <div class="page-header">
        <h1>Tugas Saya</h1>
      </div>

      <Alert v-if="error" type="error" :message="error" @dismiss="error = ''" />
      <LoadingSpinner v-if="loading" size="lg" />

      <div v-else-if="items.length === 0" class="empty">
        <Card>
          <div class="empty-text">Belum ada tugas yang di-assign</div>
        </Card>
      </div>

      <div v-else class="task-list">
        <Card v-for="ba in items" :key="ba.id" :title="ba.judul_penelitian || 'Berita Acara'">
          <div class="task-header">
            <div class="task-meta">
              <span>Pemohon: {{ ba.user?.name || '-' }}</span>
              <span> | </span>
              <Badge :type="ba.status === 'approved' ? 'success' : 'warning'" :text="ba.status" />
            </div>
          </div>

          <div class="samples-section">
            <h4 class="section-title">Data Sampel</h4>
            <div v-if="!ba.samples || ba.samples.length === 0" class="empty-text">Belum ada sampel</div>
            <div v-else class="sample-card-list">
              <div v-for="s in ba.samples" :key="s.id" class="sample-card">
                <div class="sample-info">
                  <div class="sample-name">{{ s.name }}</div>
                  <div class="sample-detail">{{ s.quantity || '-' }} {{ s.unit || '' }}</div>
                  <Badge :type="sampleStatusType[s.status]" :text="s.status" />
                </div>
                <div class="sample-actions">
                  <Button size="sm" variant="outline" @click="openSampleForm(s, ba.id)">Input Data</Button>
                  <Button v-if="s.status === 'processing'" size="sm" variant="success" @click="markReady(ba.id, s.id)">Siap Diambil</Button>
                </div>
              </div>
            </div>
          </div>
        </Card>
      </div>

      <Modal :show="showSampleModal" title="Input Data Sampel" @close="showSampleModal = false">
        <SampleDataForm
          v-if="selectedSample"
          :sample="selectedSample"
          :berita-acara-id="selectedSample.berita_acara_id"
          @submit="handleSampleUpdate"
        />
      </Modal>
    </div>
  </AppLayout>
</template>

<style scoped>
.page-header { margin-bottom: 24px; }
.page-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
.empty-text { text-align: center; padding: 40px; color: #94a3b8; }
.task-list { display: flex; flex-direction: column; gap: 20px; }
.task-header { margin-bottom: 16px; }
.task-meta { font-size: 14px; color: #475569; }
.section-title { font-size: 16px; font-weight: 600; color: #1e293b; margin: 0 0 12px; }
.sample-card-list { display: flex; flex-direction: column; gap: 12px; }
.sample-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}
.sample-info { display: flex; flex-direction: column; gap: 4px; }
.sample-name { font-weight: 500; color: #1e293b; }
.sample-detail { font-size: 13px; color: #64748b; }
.sample-actions { display: flex; gap: 8px; }

</style>
