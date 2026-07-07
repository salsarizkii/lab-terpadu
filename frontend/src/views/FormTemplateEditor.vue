<script setup lang="ts">
import { ref, computed, onMounted, defineAsyncComponent } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Save, FileText, FlaskConical, Calculator, Settings } from '@lucide/vue'
import AppLayout from '../components/common/AppLayout.vue'
import Button from '../components/common/Button.vue'
import SkeletonCard from '../components/common/SkeletonCard.vue'
import FormBuilder from '../components/common/FormBuilder.vue'
import { formTemplateApi } from '../api/formTemplate'
import { instrumentPriceApi } from '../api/instrumentPrice'
import { useAuthStore } from '../stores/authStore'
import type { FormTemplate, FormField } from '../types'
import { toast } from 'vue-sonner'

const KelolaHargaInstrumen = defineAsyncComponent(() => import('./KelolaHargaInstrumen.vue'))
const PerhitunganBiaya = defineAsyncComponent(() => import('./PerhitunganBiaya.vue'))

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

type CardType = 'cards' | 'berita_acara' | 'rekap_analis' | 'perhitungan_biaya' | 'kelola_harga_instrumen'
const view = ref<CardType>('cards')
const currentTemplate = ref<FormTemplate | null>(null)
const currentFields = ref<FormField[]>([])
const loading = ref(false)
const saving = ref(false)

onMounted(() => {
  const type = route.query.type as string
  if (type && ['berita_acara', 'rekap_analis', 'perhitungan_biaya', 'kelola_harga_instrumen'].includes(type)) {
    selectType(type as CardType)
  }
})

const pageTitle = computed(() => {
  const titles: Record<CardType, string> = {
    cards: 'Kelola Form',
    berita_acara: currentTemplate.value?.name || 'Form Berita Acara',
    rekap_analis: currentTemplate.value?.name || 'Form Rekap Analis',
    perhitungan_biaya: 'Perhitungan Biaya',
    kelola_harga_instrumen: 'Kelola Harga Instrumen',
  }
  return titles[view.value]
})

const typeLabel = computed(() => {
  const labels: Record<string, string> = {
    berita_acara: 'Berita Acara',
    rekap_analis: 'Rekap Analis',
    perhitungan_biaya: 'Perhitungan Biaya',
    kelola_harga_instrumen: 'Kelola Harga Instrumen',
  }
  return labels[currentTemplate.value?.type || ''] || currentTemplate.value?.type || ''
})

async function selectType(type: CardType) {
  if (type === 'perhitungan_biaya' || type === 'kelola_harga_instrumen') {
    view.value = type
    return
  }

  loading.value = true
  try {
    const [template, instrumentsRes] = await Promise.all([
      formTemplateApi.getPublicActiveByType(type),
      instrumentPriceApi.getAll().then(r => r.data || []).catch(() => []),
    ])
    if (!template) {
      toast.error('Template tidak ditemukan atau belum aktif')
      return
    }
    currentTemplate.value = template
    const instruments = Array.isArray(instrumentsRes) ? instrumentsRes : []

    let fields = (template.fields || []).map(f => ({
      label: f.label,
      type: f.type,
      options: f.options,
      is_required: f.is_required,
      locked: f.locked,
    }))

    const lockedMetode = fields.find(f => f.label === 'Metode/Instrumen' && f.locked)
    if (!lockedMetode && type === 'berita_acara') {
      fields = fields.filter(f => f.label !== 'Metode/Instrumen')
      const metodeField: FormField = {
        label: 'Metode/Instrumen',
        type: 'checkbox',
        options: instruments.length > 0
          ? instruments.map(i => ({ label: i.name, is_other: false }))
          : [{ label: 'Pilih instrumen...', is_other: false }],
        is_required: false,
        locked: true,
      }
      fields.push(metodeField)
    }

    currentFields.value = fields
    view.value = type
  } catch {
    toast.error('Gagal memuat template')
  } finally {
    loading.value = false
  }
}

function goBack() {
  view.value = 'cards'
  currentTemplate.value = null
  currentFields.value = []
}

async function saveFields() {
  if (!currentTemplate.value) return

  const fields = currentFields.value
  if (fields.length === 0) {
    return toast.error('Form tidak boleh kosong. Tambahkan minimal satu pertanyaan.')
  }

  for (let i = 0; i < fields.length; i++) {
    const f = fields[i]
    if (f.type !== 'divider' && (!f.label || !f.label.trim())) {
      return toast.error(`Teks pada bagian ke-${i + 1} tidak boleh kosong`)
    }
    if (f.type === 'select' || f.type === 'radio' || f.type === 'checkbox') {
      if (!f.options || f.options.length === 0) {
        return toast.error(`Pertanyaan ke-${i + 1} harus memiliki minimal 1 opsi`)
      }
      for (let j = 0; j < f.options.length; j++) {
        const o = f.options[j]
        if (!o.label || !o.label.trim()) {
          return toast.error(`Opsi ke-${j + 1} pada pertanyaan ke-${i + 1} tidak boleh kosong`)
        }
      }
    }
  }

  saving.value = true
  try {
    await formTemplateApi.syncFields(currentTemplate.value.id, {
      fields: currentFields.value.map((f) => ({
        label: f.label,
        type: f.type,
        options: f.options,
        is_required: f.is_required,
        locked: f.locked || false,
      })),
    })
    toast.success('Form berhasil disimpan')
  } catch (e: any) {
    const msg = e.response?.data?.message || 'Gagal menyimpan form'
    const errors = e.response?.data?.errors
    if (errors) {
      const flat = Object.values(errors).flat().join('. ')
      toast.error(flat)
    } else {
      toast.error(msg)
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AppLayout>
    <div class="ft-page">
      <div class="ft-header">
        <div>
          <div class="ft-header__top">
            <button v-if="view !== 'cards'" class="ft-back" @click="goBack">
              <ArrowLeft :size="18" /> Kembali
            </button>
            <h1>{{ pageTitle }}</h1>
          </div>
          <p v-if="view === 'cards'" class="ft-subtitle">Kelola template form untuk berita acara, rekap data analis, dan pengaturan biaya</p>
        </div>
      </div>

      <SkeletonCard v-if="loading" :rows="4" />

      <template v-else>
        <!-- View: 4 Cards -->
        <div v-if="view === 'cards'" class="ft-cards">
          <div
            v-if="auth.hasPermission('form_manage_berita_acara')"
            class="ft-card"
            @click="selectType('berita_acara')"
          >
            <div class="ft-card__icon ft-card__icon--blue">
              <FileText :size="28" />
            </div>
            <h3 class="ft-card__title">Form Berita Acara</h3>
            <p class="ft-card__desc">Kelola pertanyaan dan formulir untuk berita acara pengujian sampel</p>
          </div>

          <div
            v-if="auth.hasPermission('form_manage_rekap_analis')"
            class="ft-card"
            @click="selectType('rekap_analis')"
          >
            <div class="ft-card__icon ft-card__icon--green">
              <FlaskConical :size="28" />
            </div>
            <h3 class="ft-card__title">Form Rekap Analis</h3>
            <p class="ft-card__desc">Kelola pertanyaan dan formulir untuk rekap data hasil analis</p>
          </div>

          <div
            v-if="auth.hasPermission('form_manage_perhitungan_biaya')"
            class="ft-card"
            @click="selectType('perhitungan_biaya')"
          >
            <div class="ft-card__icon ft-card__icon--orange">
              <Calculator :size="28" />
            </div>
            <h3 class="ft-card__title">Perhitungan Biaya</h3>
            <p class="ft-card__desc">Buat rumus perhitungan harga dari data form berita acara dan rekap analis</p>
          </div>

          <div
            v-if="auth.hasPermission('form_manage_kelola_harga_instrumen')"
            class="ft-card"
            @click="selectType('kelola_harga_instrumen')"
          >
            <div class="ft-card__icon ft-card__icon--purple">
              <Settings :size="28" />
            </div>
            <h3 class="ft-card__title">Kelola Harga Instrumen</h3>
            <p class="ft-card__desc">Daftar dan harga instrumen analisis laboratorium</p>
          </div>
        </div>

        <!-- View: Form Builder (Berita Acara / Rekap Analis) -->
        <div v-else-if="(view === 'berita_acara' || view === 'rekap_analis') && currentTemplate" class="editor-section">
          <div class="editor-topbar">
            <div>
              <h2 class="editor-title">{{ currentTemplate.name }}</h2>
              <p class="editor-meta">{{ typeLabel }} · {{ currentFields.length }} field</p>
            </div>
          </div>
          <FormBuilder v-model:fields="currentFields" class="mb-4" />

          <div class="editor-sticky-footer">
            <Button variant="outline" @click="goBack" class="flex-align-center gap-2">
              <ArrowLeft :size="16" /> Kembali
            </Button>
            <Button variant="primary" :loading="saving" @click="saveFields" class="flex-align-center gap-2">
              <Save :size="16" /> Simpan Form
            </Button>
          </div>
        </div>

        <!-- View: Perhitungan Biaya -->
        <div v-else-if="view === 'perhitungan_biaya'" class="editor-section">
          <PerhitunganBiaya />
        </div>

        <!-- View: Kelola Harga Instrumen -->
        <div v-else-if="view === 'kelola_harga_instrumen'" class="editor-section">
          <KelolaHargaInstrumen />
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<style scoped>
.ft-page { max-width: 1100px; }
.ft-header { margin-bottom: 24px; }
.ft-header__top { display: flex; align-items: center; gap: 12px; }
.ft-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
.ft-subtitle { font-size: 14px; color: #64748b; margin: 4px 0 0; }
.flex-align-center { display: flex; align-items: center; }
.gap-2 { gap: 8px; }
.mb-4 { margin-bottom: 24px; }

.ft-back {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 6px 12px; border-radius: 8px; border: 1.5px solid #e2e8f0;
  background: #fff; color: #475569; font-size: 13px; font-weight: 500;
  cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.ft-back:hover { border-color: #cbd5e1; color: #1e293b; }

.ft-cards {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;
}
.ft-card {
  background: #fff; border: 1.5px solid #e2e8f0; border-radius: 14px;
  padding: 28px 24px; cursor: pointer; transition: all 0.2s;
  display: flex; flex-direction: column; align-items: flex-start; gap: 12px;
}
.ft-card:hover {
  border-color: #2d6a9f; box-shadow: 0 6px 20px rgba(45, 106, 159, 0.1);
  transform: translateY(-2px);
}
.ft-card__icon {
  width: 52px; height: 52px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
}
.ft-card__icon--blue { background: #eff6ff; color: #2563eb; }
.ft-card__icon--green { background: #ecfdf5; color: #059669; }
.ft-card__icon--orange { background: #fff7ed; color: #ea580c; }
.ft-card__icon--purple { background: #faf5ff; color: #9333ea; }
.ft-card__title { font-size: 17px; font-weight: 600; color: #1e293b; margin: 0; }
.ft-card__desc { font-size: 13px; color: #64748b; margin: 0; line-height: 1.5; }

.editor-section { margin-top: 4px; }
.editor-topbar {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;
}
.editor-title { font-size: 20px; font-weight: 600; color: #1e293b; margin: 0; }
.editor-meta { font-size: 13px; color: #64748b; margin: 2px 0 0; }

.editor-sticky-footer {
  position: sticky; bottom: 0; z-index: 100;
  display: flex; justify-content: flex-end; gap: 12px;
  padding: 16px; background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0;
  border-radius: 0 0 12px 12px; box-shadow: 0 -4px 12px rgba(0,0,0,0.03);
}

@media (max-width: 640px) {
  .ft-header__top { flex-direction: column; align-items: stretch; }
  .editor-topbar { flex-direction: column; align-items: stretch; gap: 12px; }
  .editor-sticky-footer { justify-content: space-between; }
}
</style>
