<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import Badge from '../common/Badge.vue'
import { formTemplateApi } from '../../api/formTemplate'
import type { BeritaAcara, FormField } from '../../types'

const props = defineProps<{
  beritaAcara: BeritaAcara
  hideSections?: string[]
  overrideStatus?: { type: string; label: string } | null
}>()

const templateFields = ref<FormField[]>([])

const legacyFields = [
  { label: 'Nama Pemohon', key: 'nama_pemohon' },
  { label: 'NIM', key: 'nim' },
  { label: 'Prodi/Fakultas/Instansi', key: 'prodi_fakultas_instansi' },
  { label: 'Dosen Pembimbing', key: 'dosen_pembimbing' },
  { label: 'Telp/No. HP', key: 'telp_hp' },
  { label: 'Email', key: 'email' },
  { label: 'Alamat', key: 'alamat' },
  { label: 'Judul Penelitian', key: 'judul_penelitian' },
  { label: 'Akses Lab', key: 'akses_lab' },
  { label: 'Bahan Contoh Uji', key: 'bahan_contoh_uji' },
  { label: 'Kemasan/Packing', key: 'kemasan_packing' },
  { label: 'Metode / Instrumen', key: 'permintaan_analisa' },
  { label: 'Jumlah Hasil Pengujian', key: 'jumlah_sampel' },
  { label: 'Kode/Nama Hasil Pengujian', key: 'kode_nama_sampel' },
  { label: 'Keterangan Hasil Pengujian', key: 'keterangan_sampel' },
  { label: 'Keterangan Tambahan', key: 'keterangan_tambahan' },
  { label: 'Pengukuran per Hasil Pengujian', key: 'pengukuran_per_sampel' },
  { label: 'Sisa Hasil Pengujian Setelah Pengujian', key: 'sisa_sampel' },
]

const statusMap: Record<string, { type: 'success' | 'warning' | 'danger' | 'info' | 'default'; label: string }> = {
  draft: { type: 'default', label: 'Draft' },
  pending: { type: 'warning', label: 'Diajukan' },
  revision: { type: 'danger', label: 'Revisi' },
  approved: { type: 'success', label: 'Disetujui' },
  rejected: { type: 'danger', label: 'Ditolak' },
}

function hasFormData(): boolean {
  const fd = (props.beritaAcara as any).form_data
  return fd && typeof fd === 'object' && Object.keys(fd).length > 0
}

function formatFieldKey(key: string): string {
  const field = templateFields.value.find(f => `field_${f.id}` === key)
  if (field) return field.label
  return key.replace(/^field_/, '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function resolveFieldValue(field: FormField): any {
  const fd = (props.beritaAcara as any).form_data
  if (!fd) return undefined
  // 1. Direct ID match
  const byId = fd[`field_${field.id}`]
  if (byId !== undefined) return byId
  // 2. Match by label: find old form_data entry whose formatted key matches this field's label
  for (const [key, val] of Object.entries(fd)) {
    if (formatFieldKey(key) === field.label) return val
  }
  return undefined
}

function formatFieldValue(val: any): string {
  if (val === null || val === undefined) return '-'
  if (Array.isArray(val)) {
    return val.map(v => typeof v === 'string' ? v.replace('Lainnya: ', '(Lainnya) ') : v).join(', ')
  }
  if (typeof val === 'string' && (val.startsWith('[') || val.startsWith('{'))) {
    try {
      const parsed = JSON.parse(val)
      if (Array.isArray(parsed)) {
        return parsed.map((s: string) => s.replace('Lainnya: ', '(Lainnya) ')).join(', ')
      }
    } catch {}
  }
  return String(val)
}

const templateInputFields = computed(() => {
  return templateFields.value.filter(f => f.type !== 'heading' && f.type !== 'paragraph' && f.type !== 'divider')
})

const matchedKeys = computed(() => {
  const keys = new Set<string>()
  if (!templateFields.value.length) return keys
  for (const field of templateFields.value) {
    keys.add(`field_${field.id}`)
  }
  const fd = (props.beritaAcara as any).form_data || {}
  for (const key of Object.keys(fd)) {
    if (formatFieldKey(key) && templateFields.value.some(f => f.label === formatFieldKey(key))) {
      keys.add(key)
    }
  }
  return keys
})

const extraFormData = computed(() => {
  const fd = (props.beritaAcara as any).form_data
  if (!fd) return {}
  const result: Record<string, any> = {}
  for (const [key, val] of Object.entries(fd)) {
    if (!matchedKeys.value.has(key)) result[key] = val
  }
  return result
})

async function loadTemplate() {
  const tplId = (props.beritaAcara as any).form_template_id
  if (!tplId) return
  try {
    const tpl = await formTemplateApi.getPublicTemplate(tplId)
    if (tpl?.fields) templateFields.value = tpl.fields
  } catch {}
}

watch(() => props.beritaAcara, () => {
  loadTemplate()
}, { immediate: true })
</script>

<template>
  <div class="detail">
    <div class="detail__meta">
      <div class="detail__meta-item">
        <span class="detail__label">Status Hasil Pengujian</span>
        <Badge
          :type="overrideStatus?.type || statusMap[beritaAcara.status]?.type || 'default'"
          :text="overrideStatus?.label || statusMap[beritaAcara.status]?.label || beritaAcara.status"
        />
      </div>
      <div class="detail__meta-item">
        <span class="detail__label">Pemohon</span>
        <span>{{ beritaAcara.user?.name || '-' }}</span>
      </div>
      <div class="detail__meta-item">
        <span class="detail__label">Diajukan</span>
        <span>{{ new Date(beritaAcara.created_at).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) }}</span>
      </div>
      <div class="detail__meta-item">
        <span class="detail__label">Analis Ditugaskan</span>
        <template v-if="beritaAcara.analysts && beritaAcara.analysts.length > 0">
          <div class="analyst-list">
            <span v-for="(a, i) in beritaAcara.analysts" :key="a.id" class="analyst-chip">
              {{ a.name }}<span v-if="i < beritaAcara.analysts!.length - 1">, </span>
            </span>
          </div>
        </template>
        <span v-else class="text-muted">Belum ada</span>
      </div>
    </div>

    <!-- Template fields (latest version) -->
    <table v-if="templateInputFields.length > 0" class="detail__table">
      <tr v-for="field in templateInputFields" :key="field.id">
        <td class="detail__td-label">{{ field.label }}</td>
        <td class="detail__td-value">{{ formatFieldValue(resolveFieldValue(field)) }}</td>
      </tr>
    </table>

    <!-- Extra form_data not matched to template (old submissions) -->
    <table v-if="Object.keys(extraFormData).length > 0" class="detail__table">
      <tr v-for="(value, key) in extraFormData" :key="key">
        <td class="detail__td-label">{{ formatFieldKey(String(key)) }}</td>
        <td class="detail__td-value">{{ formatFieldValue(value) }}</td>
      </tr>
    </table>

    <!-- Legacy hardcoded fields -->
    <table v-if="!hasFormData() && templateInputFields.length === 0" class="detail__table">
      <tr v-for="f in legacyFields" :key="f.key">
        <td class="detail__td-label">{{ f.label }}</td>
        <td class="detail__td-value">{{ formatFieldValue((beritaAcara as any)[f.key]) }}</td>
      </tr>
    </table>

    <div v-if="!props.hideSections?.includes('verifikasi') && (beritaAcara.status === 'approved' || beritaAcara.status === 'revision' || beritaAcara.status === 'rejected')" class="detail__section">
      <div class="detail__section-title">Informasi Verifikasi</div>
      <table class="detail__table">
        <tr>
          <td class="detail__td-label">Hasil Verifikasi</td>
          <td class="detail__td-value">
            <Badge :type="statusMap[beritaAcara.status]?.type || 'default'" :text="statusMap[beritaAcara.status]?.label || beritaAcara.status" />
          </td>
        </tr>
        <tr v-if="beritaAcara.biaya">
          <td class="detail__td-label">Biaya</td>
          <td class="detail__td-value">Rp {{ Number(beritaAcara.biaya).toLocaleString('id-ID') }}</td>
        </tr>
        <tr v-if="beritaAcara.biaya">
          <td class="detail__td-label">Diskon Diizinkan</td>
          <td class="detail__td-value">{{ beritaAcara.diskon_diizinkan ? 'Ya' : 'Tidak' }}</td>
        </tr>
        <tr v-if="beritaAcara.revisions && beritaAcara.revisions.length">
          <td class="detail__td-label">Catatan Revisi</td>
          <td class="detail__td-value">
            <div v-for="rev in beritaAcara.revisions" :key="rev.id" class="revision-note">
              <div>{{ rev.note }}</div>
              <div class="revision-date">{{ new Date(rev.created_at).toLocaleString('id-ID') }}</div>
            </div>
          </td>
        </tr>
      </table>
    </div>

    <div v-if="!props.hideSections?.includes('pembayaran')" class="detail__section">
      <div class="detail__section-title">Informasi Pembayaran</div>
      <table class="detail__table">
        <tr v-if="beritaAcara.biaya">
          <td class="detail__td-label">Nominal Biaya</td>
          <td class="detail__td-value">Rp {{ Number(beritaAcara.biaya).toLocaleString('id-ID') }}</td>
        </tr>
        <tr>
          <td class="detail__td-label">Status Pembayaran</td>
          <td class="detail__td-value">
            <template v-if="beritaAcara.payment">
              <Badge
                :type="beritaAcara.payment.status === 'approved' ? 'success' : beritaAcara.payment.status === 'rejected' ? 'danger' : 'warning'"
                :text="beritaAcara.payment.status === 'approved' ? 'Lunas' : beritaAcara.payment.status === 'rejected' ? 'Ditolak' : 'Diajukan'"
              />
            </template>
            <span v-else>Belum dibayar</span>
          </td>
        </tr>
        <tr v-if="beritaAcara.payment">
          <td class="detail__td-label">Jumlah Dibayar</td>
          <td class="detail__td-value">Rp {{ Number(beritaAcara.payment.amount).toLocaleString('id-ID') }}</td>
        </tr>
        <tr v-if="beritaAcara.payment?.discount_request">
          <td class="detail__td-label">Diskon Diminta</td>
          <td class="detail__td-value">Rp {{ Number(beritaAcara.payment.discount_request).toLocaleString('id-ID') }}</td>
        </tr>
        <tr v-if="beritaAcara.payment?.discount_reason">
          <td class="detail__td-label">Alasan Diskon</td>
          <td class="detail__td-value">{{ beritaAcara.payment.discount_reason }}</td>
        </tr>
        <tr v-if="beritaAcara.payment?.discount_approved">
          <td class="detail__td-label">Diskon Disetujui</td>
          <td class="detail__td-value">Rp {{ Number(beritaAcara.payment.discount_approved).toLocaleString('id-ID') }}</td>
        </tr>
        <tr v-if="beritaAcara.payment?.payment_proof">
          <td class="detail__td-label">Bukti Pembayaran</td>
          <td class="detail__td-value">Sudah diupload</td>
        </tr>
      </table>
    </div>
  </div>
</template>

<style scoped>
.detail__meta {
  display: flex;
  gap: 24px;
  padding-bottom: 16px;
  margin-bottom: 16px;
  border-bottom: 1px solid var(--color-border-light);
  flex-wrap: wrap;
}
.detail__meta-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.detail__label {
  font-size: 11px;
  font-weight: 500;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.text-muted {
  font-size: 13px;
  color: var(--color-text-muted);
  font-style: italic;
}
.analyst-list {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}
.analyst-chip {
  font-size: 13px;
  color: var(--color-text);
}
.detail__table {
  width: 100%;
  border-collapse: collapse;
}
.detail__table tr {
  border-bottom: 1px solid var(--color-border-light);
}
.detail__table tr:last-child {
  border-bottom: none;
}
.detail__td-label {
  padding: 10px 16px 10px 0;
  font-size: 13px;
  font-weight: 500;
  color: var(--color-text-secondary);
  white-space: nowrap;
  vertical-align: top;
  width: 220px;
}
.detail__td-value {
  padding: 10px 0;
  font-size: 13px;
  color: var(--color-text);
  line-height: 1.5;
  word-break: break-word;
}
.detail__section {
  margin-top: 24px;
  padding-top: 16px;
  border-top: 2px solid var(--color-border-light);
}
.detail__section-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text);
  margin-bottom: 8px;
}
.revision-note {
  margin-bottom: 8px;
}
.revision-date {
  font-size: 11px;
  color: var(--color-text-muted);
  margin-top: 2px;
}
</style>
