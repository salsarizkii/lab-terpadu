<script setup lang="ts">
import { ref, computed } from 'vue'
import Button from './Button.vue'
import FormInput from './FormInput.vue'
import FormTextarea from './FormTextarea.vue'
import Modal from './Modal.vue'
import type { FormField } from '../types'

const props = defineProps<{
  fields: FormField[]
}>()

const emit = defineEmits<{
  'update:fields': [fields: FormField[]]
}>()

const fieldTypes = [
  { value: 'text', label: 'Text', icon: 'T' },
  { value: 'textarea', label: 'Text Panjang', icon: '¶' },
  { value: 'number', label: 'Angka', icon: '#' },
  { value: 'select', label: 'Pilihan Ganda', icon: '▾' },
  { value: 'checkbox', label: 'Checkbox', icon: '☑' },
  { value: 'date', label: 'Tanggal', icon: '📅' },
  { value: 'file', label: 'File', icon: '📎' },
]

const selectedIndex = ref<number | null>(null)
const showTypePicker = ref(false)
const showOptionModal = ref(false)
const optionText = ref('')

const selectedField = computed(() => {
  if (selectedIndex.value === null) return null
  return props.fields[selectedIndex.value] || null
})

function addField(type: FormField['type']) {
  const newField: FormField = {
    label: '',
    type,
    is_required: false,
    placeholder: '',
    options: type === 'select' ? ['Pilihan 1'] : undefined,
  }
  const updated = [...props.fields, newField]
  emit('update:fields', updated)
  selectedIndex.value = updated.length - 1
  showTypePicker.value = false
}

function removeField(index: number) {
  const updated = props.fields.filter((_, i) => i !== index)
  emit('update:fields', updated)
  if (selectedIndex.value === index) selectedIndex.value = null
  else if (selectedIndex.value !== null && selectedIndex.value > index) {
    selectedIndex.value--
  }
}

function moveField(from: number, to: number) {
  if (to < 0 || to >= props.fields.length) return
  const updated = [...props.fields]
  const [item] = updated.splice(from, 1)
  updated.splice(to, 0, item)
  emit('update:fields', updated)
  if (selectedIndex.value === from) selectedIndex.value = to
  else if (selectedIndex.value !== null) {
    if (from < selectedIndex.value && to >= selectedIndex.value) selectedIndex.value--
    else if (from > selectedIndex.value && to <= selectedIndex.value) selectedIndex.value++
  }
}

function updateField(index: number, patch: Partial<FormField>) {
  const updated = [...props.fields]
  updated[index] = { ...updated[index], ...patch }
  emit('update:fields', updated)
}

function openOptions(index: number) {
  selectedIndex.value = index
  optionText.value = (props.fields[index].options || []).join('\n')
  showOptionModal.value = true
}

function saveOptions() {
  if (selectedIndex.value === null) return
  const opts = optionText.value.split('\n').map(s => s.trim()).filter(Boolean)
  updateField(selectedIndex.value, { options: opts })
  showOptionModal.value = false
}
</script>

<template>
  <div class="form-builder">
    <div class="form-builder__main">
      <TransitionGroup name="field-list" tag="div" class="form-builder__fields">
        <div
          v-for="(field, index) in fields"
          :key="index"
          class="field-card"
          :class="{ 'field-card--selected': selectedIndex === index }"
          @click="selectedIndex = index"
        >
          <div class="field-card__header">
            <div class="field-card__drag">
              <button class="drag-btn" :disabled="index === 0" @click.stop="moveField(index, index - 1)" title="Pindah ke atas">
                <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3L12 8H2L7 3Z" fill="currentColor"/></svg>
              </button>
              <button class="drag-btn" :disabled="index === fields.length - 1" @click.stop="moveField(index, index + 1)" title="Pindah ke bawah">
                <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 11L2 6H12L7 11Z" fill="currentColor"/></svg>
              </button>
            </div>
            <div class="field-card__body">
              <input
                class="field-label-input"
                :value="field.label"
                @input="updateField(index, { label: ($event.target as HTMLInputElement).value })"
                placeholder="Label pertanyaan..."
                @click.stop
              />
              <span class="field-type-badge">{{ fieldTypes.find(t => t.value === field.type)?.label || field.type }}</span>
            </div>
          </div>

          <div class="field-card__preview">
            <input v-if="field.type === 'text'" class="field-preview-input" :placeholder="field.placeholder || 'Jawaban singkat'" disabled />
            <textarea v-else-if="field.type === 'textarea'" class="field-preview-textarea" :placeholder="field.placeholder || 'Jawaban panjang'" disabled />
            <input v-else-if="field.type === 'number'" class="field-preview-input" type="number" :placeholder="field.placeholder || '0'" disabled />
            <div v-else-if="field.type === 'select'" class="field-preview-select">
              <select disabled><option>Pilih...</option></select>
            </div>
            <label v-else-if="field.type === 'checkbox'" class="field-preview-check">
              <input type="checkbox" disabled /> Opsi checkbox
            </label>
            <input v-else-if="field.type === 'date'" class="field-preview-input" type="date" disabled />
            <div v-else-if="field.type === 'file'" class="field-preview-file">📎 Upload file</div>
          </div>

          <div class="field-card__footer">
            <label class="field-toggle" @click.stop>
              <input type="checkbox" :checked="field.is_required" @change="updateField(index, { is_required: ($event.target as HTMLInputElement).checked })" />
              Wajib diisi
            </label>
            <div class="field-card__actions">
              <button v-if="field.type === 'select'" class="field-action-btn" @click.stop="openOptions(index)" title="Edit opsi">
                <svg width="14" height="14" viewBox="0 0 14 14"><path d="M2 4h10M2 7h10M2 10h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
              </button>
              <button class="field-action-btn field-action-btn--danger" @click.stop="removeField(index)" title="Hapus">
                <svg width="14" height="14" viewBox="0 0 14 14"><path d="M3 3l8 8M11 3l-8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
              </button>
            </div>
          </div>
        </div>
      </TransitionGroup>

      <div class="form-builder__add" v-if="fields.length === 0">
        <div class="empty-state">
          <svg width="48" height="48" viewBox="0 0 48 48" fill="none"><circle cx="24" cy="24" r="22" stroke="#cbd5e1" stroke-width="2"/><path d="M24 16v16M16 24h16" stroke="#cbd5e1" stroke-width="2" stroke-linecap="round"/></svg>
          <p>Belum ada field. Klik tombol di bawah untuk menambahkan.</p>
        </div>
      </div>
    </div>

    <div class="form-builder__sidebar">
      <div class="sidebar-section">
        <p class="sidebar-title">Tambah Field</p>
        <div class="field-type-grid">
          <button v-for="ft in fieldTypes" :key="ft.value" class="field-type-btn" @click="addField(ft.value as FormField['type'])">
            <span class="field-type-btn__icon">{{ ft.icon }}</span>
            <span class="field-type-btn__label">{{ ft.label }}</span>
          </button>
        </div>
      </div>

      <div class="sidebar-section" v-if="selectedField">
        <p class="sidebar-title">Edit Field</p>
        <div class="field-edit">
          <label class="field-edit__label">Label</label>
          <input class="field-edit__input" :value="selectedField.label" @input="updateField(selectedIndex!, { label: ($event.target as HTMLInputElement).value })" />
        </div>
        <div class="field-edit">
          <label class="field-edit__label">Placeholder</label>
          <input class="field-edit__input" :value="selectedField.placeholder" @input="updateField(selectedIndex!, { placeholder: ($event.target as HTMLInputElement).value })" />
        </div>
        <div class="field-edit">
          <label class="field-edit__label">Tipe</label>
          <select class="field-edit__input" :value="selectedField.type" @change="updateField(selectedIndex!, { type: ($event.target as HTMLSelectElement).value as FormField['type'] })">
            <option v-for="ft in fieldTypes" :key="ft.value" :value="ft.value">{{ ft.label }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Options Modal -->
    <Modal :show="showOptionModal" title="Edit Pilihan" size="md" @close="showOptionModal = false">
      <p class="modal-hint">Satu opsi per baris.</p>
      <textarea class="options-textarea" v-model="optionText" rows="8" placeholder="Pilihan 1&#10;Pilihan 2&#10;Pilihan 3"></textarea>
      <div class="modal-actions">
        <Button variant="secondary" @click="showOptionModal = false">Batal</Button>
        <Button variant="primary" @click="saveOptions">Simpan</Button>
      </div>
    </Modal>
  </div>
</template>

<style scoped>
.form-builder { display: grid; grid-template-columns: 1fr 280px; gap: 20px; }
.form-builder__main { min-height: 200px; }

.form-builder__fields { display: flex; flex-direction: column; gap: 12px; }

.field-card {
  background: #fff; border: 2px solid #e2e8f0; border-radius: 12px;
  padding: 16px; cursor: pointer; transition: all 0.15s ease;
}
.field-card:hover { border-color: #cbd5e1; }
.field-card--selected { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }

.field-card__header { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px; }
.field-card__drag { display: flex; flex-direction: column; gap: 2px; padding-top: 4px; }
.drag-btn {
  width: 22px; height: 22px; border: none; border-radius: 4px; background: transparent;
  color: #94a3b8; cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: all 0.15s;
}
.drag-btn:hover:not(:disabled) { background: #f1f5f9; color: #475569; }
.drag-btn:disabled { opacity: 0.3; cursor: default; }

.field-card__body { flex: 1; }
.field-label-input {
  width: 100%; border: none; border-bottom: 1px solid #e2e8f0; padding: 4px 0;
  font-size: 15px; font-weight: 500; color: #1e293b; outline: none;
  background: transparent; transition: border-color 0.15s;
}
.field-label-input:focus { border-bottom-color: #2563eb; }
.field-label-input::placeholder { color: #94a3b8; }

.field-type-badge {
  display: inline-block; margin-top: 4px; font-size: 11px; padding: 2px 8px;
  border-radius: 20px; background: #f1f5f9; color: #64748b; font-weight: 500;
}

.field-card__preview { padding: 8px 0 8px 34px; }
.field-preview-input, .field-preview-textarea {
  width: 100%; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px;
  font-size: 13px; color: #64748b; background: #fafbfc; resize: none;
}
.field-preview-textarea { min-height: 60px; }
.field-preview-select select {
  width: 100%; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px;
  font-size: 13px; color: #64748b; background: #fafbfc;
}
.field-preview-check { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b; }
.field-preview-file { font-size: 13px; color: #64748b; padding: 8px 10px; border: 1px dashed #e2e8f0; border-radius: 6px; background: #fafbfc; }

.field-card__footer { display: flex; align-items: center; justify-content: space-between; padding-top: 8px; border-top: 1px solid #f1f5f9; margin-top: 8px; }
.field-toggle { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; cursor: pointer; }
.field-toggle input { accent-color: #2563eb; }
.field-card__actions { display: flex; gap: 4px; }
.field-action-btn {
  width: 28px; height: 28px; border: none; border-radius: 6px; background: transparent;
  color: #94a3b8; cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: all 0.15s;
}
.field-action-btn:hover { background: #f1f5f9; color: #475569; }
.field-action-btn--danger:hover { background: #fef2f2; color: #ef4444; }

.empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; }
.empty-state p { margin-top: 12px; font-size: 14px; }

.form-builder__sidebar { position: sticky; top: 20px; align-self: start; }
.sidebar-section {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 12px;
}
.sidebar-title { font-size: 13px; font-weight: 600; color: #475569; margin: 0 0 12px; }

.field-type-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
.field-type-btn {
  display: flex; flex-direction: column; align-items: center; gap: 4px;
  padding: 10px 6px; border: 1px solid #e2e8f0; border-radius: 8px;
  background: #fafbfc; cursor: pointer; transition: all 0.15s;
}
.field-type-btn:hover { border-color: #2563eb; background: #eff6ff; color: #2563eb; }
.field-type-btn__icon { font-size: 18px; }
.field-type-btn__label { font-size: 11px; font-weight: 500; }

.field-edit { margin-bottom: 12px; }
.field-edit__label { display: block; font-size: 12px; font-weight: 500; color: #475569; margin-bottom: 4px; }
.field-edit__input {
  width: 100%; border: 1px solid #e2e8f0; border-radius: 6px; padding: 7px 10px;
  font-size: 13px; color: #1e293b; outline: none;
}
.field-edit__input:focus { border-color: #2563eb; }

.modal-hint { font-size: 13px; color: #64748b; margin: 0 0 10px; }
.options-textarea {
  width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px;
  font-size: 13px; resize: vertical; font-family: inherit; outline: none;
}
.options-textarea:focus { border-color: #2563eb; }
.modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; }

.field-list-enter-active, .field-list-leave-active { transition: all 0.2s ease; }
.field-list-enter-from { opacity: 0; transform: translateY(-10px); }
.field-list-leave-to { opacity: 0; transform: translateX(20px); }
</style>
