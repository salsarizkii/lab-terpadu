<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Check } from '@lucide/vue'

const props = withDefaults(defineProps<{
  modelValue: string
  label?: string
  error?: string
}>(), {})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const instruments = [
  'AAS',
  'Spektrofotometer UV Vis',
  'GC-FID',
  'GC-MS',
  'Mercury analyser',
  'Spektrofotometer ATR FTIR',
  'PCR',
  'PSA Zeta potensial',
  'PSA Particle Size',
  'PSA Zeta Potensial & Particle Size',
  'Analisis Kedap Air',
  'Analisis Kedap udara',
  'Aktivitas Anti bakteri',
  'HPLC',
  'Viscometer Brookfield NDJ-8S',
  'TLC (Camag)',
  'XRF X-MET8000',
  'Thermal Conductivity',
  'Spectrometer FT-NIR TANGO-R',
  'Pengujian Lainnya',
]

const selected = ref<string[]>([])
const lainnya = ref('')

watch(() => props.modelValue, (val) => {
  if (!val) {
    selected.value = []
    lainnya.value = ''
    return
  }
  try {
    const parsed = JSON.parse(val)
    selected.value = parsed.filter((s: string) => !s.startsWith('Lainnya: '))
    const lainnyaItem = parsed.find((s: string) => s.startsWith('Lainnya: '))
    lainnya.value = lainnyaItem ? lainnyaItem.replace('Lainnya: ', '') : ''
  } catch {
    selected.value = val ? val.split(', ').filter(Boolean) : []
    lainnya.value = ''
  }
}, { immediate: true })

function sync() {
  const values = [...selected.value]
  if (lainnya.value.trim()) {
    values.push(`Lainnya: ${lainnya.value.trim()}`)
  }
  emit('update:modelValue', JSON.stringify(values))
}

function toggle(item: string) {
  const idx = selected.value.indexOf(item)
  if (idx >= 0) {
    selected.value.splice(idx, 1)
  } else {
    selected.value.push(item)
  }
  sync()
}
</script>

<template>
  <div class="form-group">
    <label v-if="label" class="form-label">{{ label }}</label>
    <div class="picker" :class="{ 'picker--error': error }">
      <div
        v-for="item in instruments"
        :key="item"
        class="picker__item"
        role="button"
        tabindex="0"
        @click="toggle(item)"
        @keydown.enter.prevent="toggle(item)"
        @keydown.space.prevent="toggle(item)"
      >
        <div class="picker__checkbox" :class="{ 'picker__checkbox--checked': selected.includes(item) }">
          <Check v-if="selected.includes(item)" :size="14" color="white" stroke-width="3" />
        </div>
        <span class="picker__label">{{ item }}</span>
      </div>
      <div v-if="selected.includes('Pengujian Lainnya')" class="picker__lainnya">
        <input
          v-model="lainnya"
          class="picker__input"
          placeholder="Jelaskan pengujian lainnya..."
          @input="sync"
        />
      </div>
    </div>
    <p v-if="error" class="form-error">{{ error }}</p>
  </div>
</template>

<style scoped>
.form-group {
  margin-bottom: 18px;
}
.form-label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: var(--color-text-secondary);
  margin-bottom: 10px;
  letter-spacing: 0.3px;
}
.picker {
  border: 1.5px solid var(--color-border);
  border-radius: var(--radius-sm);
  padding: 8px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 4px;
  transition: var(--transition);
}
.picker:focus-within {
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}
.picker--error {
  border-color: var(--color-danger);
}
.picker__item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 6px;
  cursor: pointer;
  transition: var(--transition);
  user-select: none;
}
.picker__item:hover {
  background: var(--color-bg);
}
.picker__checkbox {
  width: 18px;
  height: 18px;
  border: 2px solid var(--color-border);
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: var(--transition);
}
.picker__checkbox--checked {
  background: var(--color-accent);
  border-color: var(--color-accent);
}
.picker__label {
  font-size: 13px;
  color: var(--color-text);
  line-height: 1.3;
}
.picker__lainnya {
  grid-column: 1 / -1;
  padding: 4px 8px 8px;
}
.picker__input {
  width: 100%;
  padding: 10px 12px;
  border: 1.5px solid var(--color-border);
  border-radius: var(--radius-sm);
  font-family: var(--font-primary);
  font-size: 13px;
  color: var(--color-text);
  outline: none;
  transition: var(--transition);
}
.picker__input:focus {
  border-color: var(--color-accent);
}
.picker__input::placeholder {
  color: var(--color-text-muted);
}
.form-error {
  margin: 4px 0 0;
  font-size: 12px;
  color: var(--color-danger);
}
</style>
