<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { Check, FileText, CreditCard, FlaskConical, Award } from '@lucide/vue'
import Badge from '../common/Badge.vue'
import Button from '../common/Button.vue'
import type { BeritaAcara } from '../../types'

const props = defineProps<{
  beritaAcara: BeritaAcara
}>()

const router = useRouter()

const ba = computed(() => props.beritaAcara)

type BadgeType = 'default' | 'danger' | 'success' | 'warning' | 'info'

const steps = computed(() => {
  return [
    {
      key: 'pengajuan',
      label: 'Pengajuan',
      desc: 'Buat & ajukan berita acara penelitian',
      icon: 'file',
      status: !ba.value ? 'inactive' : (ba.value.status === 'draft' || ba.value.status === 'pending' || ba.value.status === 'revision') ? 'active' : 'done',
      badge: ba.value ? (ba.value.status === 'revision' ? 'Perlu Revisi' : ba.value.status === 'pending' ? 'Diajukan' : ba.value.status === 'approved' ? 'Disetujui' : 'Draft') : '',
      badgeType: (ba.value ? (ba.value.status === 'revision' ? 'warning' : ba.value.status === 'pending' ? 'warning' : ba.value.status === 'approved' ? 'success' : 'default') : 'default') as BadgeType,
      action: (ba.value.status === 'revision' || ba.value.status === 'draft') ? 'Lanjutkan' : 'Lihat',
      route: `/user/berita-acara/${ba.value.id}`,
    },
    {
      key: 'pembayaran',
      label: 'Pembayaran',
      desc: 'Lakukan pembayaran & ajukan diskon',
      icon: 'payment',
      status: ba.value.status !== 'approved' ? 'inactive' : (!ba.value.payment) ? 'active' : (ba.value.payment.status === 'approved') ? 'done' : 'active',
      badge: ba.value.payment ? (ba.value.payment.status === 'approved' ? 'Lunas' : ba.value.payment.status === 'rejected' ? 'Ditolak' : 'Diajukan') : '',
      badgeType: (ba.value.payment ? (ba.value.payment.status === 'approved' ? 'success' : ba.value.payment.status === 'rejected' ? 'danger' : 'warning') : 'default') as BadgeType,
      action: ba.value.status === 'approved' ? 'Lihat' : null,
      route: `/user/berita-acara/${ba.value.id}`,
    },
    {
      key: 'pengambilan',
      label: 'Pengambilan',
      desc: 'Sampel siap diambil di lab',
      icon: 'sample',
      status: !ba.value.samples || ba.value.samples.length === 0 ? 'inactive' : (ba.value.samples.some(s => s.status === 'ready' || s.status === 'taken')) ? 'done' : (ba.value.samples.some(s => s.status === 'processing')) ? 'active' : 'inactive',
      badge: ba.value.samples && ba.value.samples.some(s => s.status === 'ready') ? 'Siap Diambil' : (ba.value.samples && ba.value.samples.some(s => s.status === 'taken') ? 'Sudah Diambil' : ''),
      badgeType: (ba.value.samples && ba.value.samples.some(s => s.status === 'ready') ? 'success' : (ba.value.samples && ba.value.samples.some(s => s.status === 'taken') ? 'info' : 'default')) as BadgeType,
      action: ba.value.samples && ba.value.samples.some(s => s.status === 'ready') ? 'Lihat' : null,
      route: `/user/berita-acara/${ba.value.id}`,
    },
    {
      key: 'sertifikat',
      label: 'Sertifikat',
      desc: 'Unduh sertifikat hasil penelitian',
      icon: 'certificate',
      status: ba.value.certificate ? 'done' : 'inactive',
      badge: ba.value.certificate ? (ba.value.certificate.downloaded_at ? 'Sudah Diunduh' : 'Tersedia') : '',
      badgeType: (ba.value.certificate ? (ba.value.certificate.downloaded_at ? 'info' : 'success') : 'default') as BadgeType,
      action: ba.value.certificate ? 'Unduh' : null,
      route: ba.value.certificate ? `/user/berita-acara/${ba.value.id}` : null,
    },
  ]
})
</script>

<template>
  <div class="flow">
    <div v-for="(step, idx) in steps" :key="step.key" class="flow__item" :class="`flow__item--${step.status}`">
      <div class="flow__connector">
        <div class="flow__dot">
          <Check v-if="step.status === 'done'" :size="18" stroke-width="2.5" />
          <FileText v-else-if="step.key === 'pengajuan'" :size="18" />
          <CreditCard v-else-if="step.key === 'pembayaran'" :size="18" />
          <FlaskConical v-else-if="step.key === 'pengambilan'" :size="18" />
          <Award v-else :size="18" />
        </div>
        <div v-if="idx < steps.length - 1" class="flow__line" :class="{ 'flow__line--active': step.status === 'done' }" />
      </div>
      <div class="flow__content">
        <div class="flow__top">
          <div>
            <h3 class="flow__label">{{ step.label }}</h3>
            <p class="flow__desc">{{ step.desc }}</p>
          </div>
          <Badge v-if="step.badge" :type="step.badgeType" :text="step.badge" />
        </div>
        <div class="flow__actions">
          <Button
            v-if="step.action"
            :variant="step.status === 'done' ? 'outline' : 'primary'"
            size="sm"
            @click="step.route && router.push(step.route)"
          >
            {{ step.action }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.flow {
  display: flex;
  flex-direction: column;
  gap: 0;
}
.flow__item {
  display: flex;
  gap: 20px;
  padding: 0 0 8px;
  opacity: 0.5;
  transition: var(--transition);
}
.flow__item--active, .flow__item--done {
  opacity: 1;
}
.flow__connector {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 40px;
  flex-shrink: 0;
}
.flow__dot {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
  transition: var(--transition);
}
.flow__item--inactive .flow__dot {
  background: var(--color-border-light);
  color: var(--color-text-muted);
}
.flow__item--active .flow__dot {
  background: var(--color-accent);
  color: white;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}
.flow__item--done .flow__dot {
  background: var(--color-success);
  color: white;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.flow__line {
  width: 2px;
  flex: 1;
  min-height: 24px;
  background: var(--color-border);
  margin: 4px 0;
}
.flow__line--active {
  background: var(--color-success);
}
.flow__content {
  flex: 1;
  padding-bottom: 24px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.flow__top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}
.flow__label {
  font-size: 15px;
  font-weight: 600;
  margin: 0;
}
.flow__desc {
  font-size: 13px;
  color: var(--color-text-secondary);
  margin-top: 2px;
}
.flow__actions {
  display: flex;
  gap: 8px;
}
</style>
