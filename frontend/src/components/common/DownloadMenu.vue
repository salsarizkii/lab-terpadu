<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue'
import { toast } from 'vue-sonner'
import { pdfTemplateApi } from '../../api/pdfTemplate'
import { useAuthStore } from '../../stores/authStore'
import type { BeritaAcara } from '../../types'
import Button from './Button.vue'

const props = withDefaults(defineProps<{
  beritaAcara: BeritaAcara
  showRekap?: boolean
  label?: string
}>(), {
  showRekap: false,
  label: 'Download Dokumen',
})

const auth = useAuthStore()

const open = ref(false)
const menuRef = ref<HTMLElement | null>(null)

const isStaff = computed(() =>
  ['superadmin', 'admin', 'manager_teknis'].includes(auth.user?.role ?? ''),
)

function hasRekapData(ba: BeritaAcara): boolean {
  return !!ba.rekap_analis_data && Object.keys(ba.rekap_analis_data).length > 0
}

function isPaid(ba: BeritaAcara): boolean {
  return ba.payment?.status === 'approved'
}

interface DownloadItem {
  key: string
  label: string
  format: 'pdf' | 'docx'
}

const items = computed<DownloadItem[]>(() => {
  const ba = props.beritaAcara
  const list: DownloadItem[] = [
    { key: 'ba-pdf', label: 'Berita Acara', format: 'pdf' },
    { key: 'ba-docx', label: 'Berita Acara', format: 'docx' },
  ]
  if (props.showRekap || isStaff.value || hasRekapData(ba)) {
    list.push(
      { key: 'rekap-pdf', label: 'Rekap Analis', format: 'pdf' },
      { key: 'rekap-docx', label: 'Rekap Analis', format: 'docx' },
    )
  }
  if (ba.payment) {
    list.push(
      { key: 'tagihan-pdf', label: 'Tagihan', format: 'pdf' },
      { key: 'tagihan-docx', label: 'Tagihan', format: 'docx' },
    )
  }
  if (ba.certificate && (isStaff.value || isPaid(ba))) {
    list.push(
      { key: 'sertifikat-pdf', label: 'Sertifikat', format: 'pdf' },
      { key: 'sertifikat-docx', label: 'Sertifikat', format: 'docx' },
    )
  }
  if (isStaff.value || isPaid(ba)) {
    list.push(
      { key: 'pss-pdf', label: 'Pengambilan Sisa Sampel', format: 'pdf' },
      { key: 'pss-docx', label: 'Pengambilan Sisa Sampel', format: 'docx' },
    )
  }
  return list
})

const grouped = computed(() => {
  const groups: { title: string; items: DownloadItem[] }[] = []
  const push = (title: string, titleItems: DownloadItem[]) => {
    if (titleItems.length > 0) groups.push({ title, items: titleItems })
  }
  push('Berita Acara', items.value.filter(i => i.key.startsWith('ba-')))
  push('Rekap Analis', items.value.filter(i => i.key.startsWith('rekap-')))
  push('Tagihan', items.value.filter(i => i.key.startsWith('tagihan-')))
  push('Sertifikat', items.value.filter(i => i.key.startsWith('sertifikat-')))
  push('Pengambilan Sisa Sampel', items.value.filter(i => i.key.startsWith('pss-')))
  return groups
})

function targetId(item: DownloadItem): number | null {
  const ba = props.beritaAcara
  if (item.key.startsWith('ba-') || item.key.startsWith('rekap-') || item.key.startsWith('pss-')) return ba.id
  if (item.key.startsWith('tagihan-')) return ba.payment?.id ?? null
  if (item.key.startsWith('sertifikat-')) return ba.certificate?.id ?? null
  return null
}

function docType(item: DownloadItem): string {
  if (item.key.startsWith('ba-')) return 'berita_acara'
  if (item.key.startsWith('rekap-')) return 'rekap_analis'
  if (item.key.startsWith('tagihan-')) return 'tagihan'
  if (item.key.startsWith('pss-')) return 'pengambilan_sisa_sampel'
  return 'sertifikat'
}

function handleDownload(item: DownloadItem) {
  const id = targetId(item)
  if (id == null) return
  const type = docType(item)
  const url = item.format === 'pdf'
    ? pdfTemplateApi.getDownloadUrl(type, id)
    : pdfTemplateApi.getDocxDownloadUrl(type, id)
  window.location.href = url
  toast.success(`${item.label} (${item.format === 'pdf' ? 'PDF' : 'Word'}) sedang diunduh`)
}

function toggle() {
  open.value = !open.value
}

function onDocumentClick(e: MouseEvent) {
  if (menuRef.value && !menuRef.value.contains(e.target as Node)) {
    open.value = false
  }
}

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
})

document.addEventListener('click', onDocumentClick)
</script>

<template>
  <div ref="menuRef" class="dl-menu">
    <Button variant="primary" size="sm" class="dl-menu__trigger" @click="toggle">
      <svg class="dl-menu__icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      {{ label }}
      <svg class="dl-menu__chevron" :class="{ 'dl-menu__chevron--open': open }" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
    </Button>

    <transition name="dl-fade">
      <div v-if="open" class="dl-menu__panel">
        <div v-for="group in grouped" :key="group.title" class="dl-menu__group">
          <div class="dl-menu__group-title">{{ group.title }}</div>
          <button
            v-for="item in group.items"
            :key="item.key"
            class="dl-menu__item"
            @click="handleDownload(item)"
          >
            <span class="dl-menu__badge" :class="`dl-menu__badge--${item.format}`">
              {{ item.format === 'pdf' ? 'PDF' : 'DOC' }}
            </span>
            <span class="dl-menu__item-label">{{ item.label }}</span>
            <svg class="dl-menu__item-icon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.dl-menu {
  position: relative;
  display: inline-block;
}

.dl-menu__trigger {
  gap: 6px;
}

.dl-menu__icon {
  flex-shrink: 0;
}

.dl-menu__chevron {
  transition: transform var(--transition-fast);
}

.dl-menu__chevron--open {
  transform: rotate(180deg);
}

.dl-menu__panel {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  min-width: 260px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  padding: 8px;
  z-index: 100;
}

.dl-menu__group + .dl-menu__group {
  border-top: 1px solid var(--color-border-light);
  margin-top: 6px;
  padding-top: 6px;
}

.dl-menu__group-title {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--color-text-muted);
  padding: 6px 10px 4px;
}

.dl-menu__item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 8px 10px;
  border: none;
  background: transparent;
  border-radius: var(--radius-sm);
  font-family: var(--font-primary);
  font-size: 13px;
  font-weight: 500;
  color: var(--color-text);
  cursor: pointer;
  transition: background var(--transition-fast);
}

.dl-menu__item:hover:not(:disabled) {
  background: var(--color-accent-soft);
}

.dl-menu__item:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.dl-menu__badge {
  flex-shrink: 0;
  min-width: 34px;
  text-align: center;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.4px;
  padding: 2px 6px;
  border-radius: 5px;
}

.dl-menu__badge--pdf {
  background: var(--color-danger-bg);
  color: var(--color-danger);
}

.dl-menu__badge--docx {
  background: var(--color-info-bg);
  color: var(--color-info);
}

.dl-menu__item-label {
  flex: 1;
  text-align: left;
}

.dl-menu__item-icon {
  color: var(--color-text-muted);
  flex-shrink: 0;
}

.dl-menu__item:hover:not(:disabled) .dl-menu__item-icon {
  color: var(--color-accent);
}

.dl-menu__spinner {
  width: 14px;
  height: 14px;
  border: 2px solid var(--color-accent);
  border-top-color: transparent;
  border-radius: 50%;
  animation: dl-spin 0.6s linear infinite;
  flex-shrink: 0;
}

@keyframes dl-spin {
  to { transform: rotate(360deg); }
}

.dl-fade-enter-active,
.dl-fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.dl-fade-enter-from,
.dl-fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>