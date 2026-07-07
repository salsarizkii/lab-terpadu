<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { GripVertical, Trash2, File as FileIcon, ChevronRight, CircleDot, CheckSquare, Plus, FileQuestion, Type, AlignLeft, Hash, Mail, Phone, Calendar, Clock, List, ChevronDown, Minus, Heading, Copy, Lock } from '@lucide/vue'
import type { FormField, FormFieldOption } from '../../types'

const props = defineProps<{
  fields: FormField[]
}>()

const emit = defineEmits<{
  'update:fields': [fields: FormField[]]
}>()

const fieldTypes = [
  { value: 'text', label: 'Text', lucide: Type, group: 'input' },
  { value: 'textarea', label: 'Text Panjang', lucide: AlignLeft, group: 'input' },
  { value: 'number', label: 'Angka', lucide: Hash, group: 'input' },
  { value: 'email', label: 'Email', lucide: Mail, group: 'input' },
  { value: 'phone', label: 'Telepon', lucide: Phone, group: 'input' },
  { value: 'date', label: 'Tanggal', lucide: Calendar, group: 'input' },
  { value: 'time', label: 'Jam', lucide: Clock, group: 'input' },
  { value: 'file', label: 'File', lucide: FileIcon, group: 'input' },
  { value: 'select', label: 'Dropdown', lucide: List, group: 'choice' },
  { value: 'radio', label: 'Radio', lucide: CircleDot, group: 'choice' },
  { value: 'checkbox', label: 'Checkbox', lucide: CheckSquare, group: 'choice' },
  { value: 'heading', label: 'Judul', lucide: Heading, group: 'layout' },
  { value: 'paragraph', label: 'Paragraf', lucide: AlignLeft, group: 'layout' },
  { value: 'divider', label: 'Garis', lucide: Minus, group: 'layout' },
]

const typePickerOpen = ref<number | null>(null)

const selectedIndex = ref<number | null>(null)
const dragIndex = ref<number | null>(null)
const dragOverIndex = ref<number | null>(null)
const dragCounter = ref(0)

function hasOptions(type: string) {
  return type === 'select' || type === 'radio' || type === 'checkbox'
}

function isInput(type: string) {
  return !['heading', 'paragraph', 'divider'].includes(type)
}

function closeTypePicker() {
  typePickerOpen.value = null
}

onMounted(() => {
  window.addEventListener('click', closeTypePicker)
})

onUnmounted(() => {
  window.removeEventListener('click', closeTypePicker)
})

function addField(type: FormField['type'], afterIndex: number) {
  const newField: FormField = {
    label: '',
    type,
    is_required: false,
    options: hasOptions(type) ? [{ label: '', is_other: false }] : undefined,
  }
  const updated = [...props.fields]
  updated.splice(afterIndex + 1, 0, newField)
  emit('update:fields', updated)
  selectedIndex.value = afterIndex + 1
}

function removeField(index: number) {
  const updated = props.fields.filter((_, i) => i !== index)
  emit('update:fields', updated)
  if (selectedIndex.value === index) selectedIndex.value = null
  else if (selectedIndex.value !== null && selectedIndex.value > index) selectedIndex.value--
}

function duplicateField(index: number) {
  const original = props.fields[index]
  if (!original) return
  const clone: FormField = {
    label: original.label ? original.label + ' (salinan)' : '',
    type: original.type,
    is_required: original.is_required,
    options: original.options ? original.options.map(o => ({ ...o })) : undefined,
  }
  const updated = [...props.fields]
  updated.splice(index + 1, 0, clone)
  emit('update:fields', updated)
  selectedIndex.value = index + 1
}

function updateField(index: number, patch: Partial<FormField>) {
  const updated = [...props.fields]
  const existing = updated[index]
  if (!existing) return
  updated[index] = { ...existing, ...patch }
  emit('update:fields', updated)
}

function changeFieldType(type: FormField['type'], index: number) {
  selectedIndex.value = index
  const field = props.fields[index]
  if (!field) return
  const needsOpts = hasOptions(type)
  const patch: Partial<FormField> = { type }
  if (needsOpts && !field.options?.length) patch.options = [{ label: '', is_other: false }]
  if (!needsOpts) patch.options = undefined
  updateField(index, patch)
  typePickerOpen.value = null
}

function addOption(fieldIndex: number) {
  const field = props.fields[fieldIndex]
  if (!field) return
  const opts = [...(field.options || []), { label: '', is_other: false }]
  updateField(fieldIndex, { options: opts })
}

function updateOption(fieldIndex: number, optIndex: number, patch: Partial<FormFieldOption>) {
  const field = props.fields[fieldIndex]
  if (!field) return
  const opts = [...(field.options || [])]
  const existing = opts[optIndex]
  if (!existing) return
  opts[optIndex] = { label: existing.label, is_other: existing.is_other, ...patch }
  updateField(fieldIndex, { options: opts })
}

function removeOption(fieldIndex: number, optIndex: number) {
  const field = props.fields[fieldIndex]
  if (!field) return
  const opts = (field.options || []).filter((_: FormFieldOption, i: number) => i !== optIndex)
  updateField(fieldIndex, { options: opts })
}

function onDragStart(e: DragEvent, index: number) {
  dragIndex.value = index
  e.dataTransfer!.effectAllowed = 'move'
  e.dataTransfer!.setData('text/plain', String(index))
  const el = e.target as HTMLElement
  requestAnimationFrame(() => { el.style.opacity = '0.4' })
}

function onDragEnd(e: DragEvent) {
  ;(e.target as HTMLElement).style.opacity = '1'
  dragIndex.value = null
  dragOverIndex.value = null
  dragCounter.value = 0
}

function onDragEnter(e: DragEvent, index: number) {
  e.preventDefault()
  dragCounter.value++
  if (dragIndex.value !== null && dragIndex.value !== index) {
    dragOverIndex.value = index
  }
}

function onDragLeave(e: DragEvent) {
  dragCounter.value--
  if (dragCounter.value === 0) {
    dragOverIndex.value = null
  }
}

function onDragOver(e: DragEvent) {
  e.preventDefault()
  e.dataTransfer!.dropEffect = 'move'
}

function onDrop(e: DragEvent, toIndex: number) {
  e.preventDefault()
  dragCounter.value = 0
  const fromIndex = dragIndex.value
  if (fromIndex === null || fromIndex === toIndex) return
  const updated = [...props.fields]
  const [item] = updated.splice(fromIndex, 1)
  if (item === undefined) return
  updated.splice(toIndex > fromIndex ? toIndex - 1 : toIndex, 0, item)
  emit('update:fields', updated)
  if (selectedIndex.value === fromIndex) selectedIndex.value = toIndex
  else if (selectedIndex.value !== null) {
    if (fromIndex < selectedIndex.value && toIndex >= selectedIndex.value) selectedIndex.value--
    else if (fromIndex > selectedIndex.value && toIndex <= selectedIndex.value) selectedIndex.value++
  }
  dragIndex.value = null
  dragOverIndex.value = null
}
</script>

<template>
  <div class="form-builder">
    <template v-for="(field, index) in fields" :key="index">
      <!-- Drop zone atas -->
      <div
        class="drop-zone"
        :class="{ 'drop-zone--active': dragOverIndex === index && dragIndex !== null && dragIndex !== index }"
        @dragenter="onDragEnter($event, index)"
        @dragleave="onDragLeave"
        @dragover="onDragOver"
        @drop="onDrop($event, index)"
      />

      <div
        class="field-card"
        :class="[
          { 'field-card--selected': selectedIndex === index, 'field-card--dragging': dragIndex === index, 'field-card--locked': field.locked },
          `field-card--${field.type}`
        ]"
        draggable="true"
        @click="selectedIndex = index"
        @dragstart="onDragStart($event, index)"
        @dragend="onDragEnd"
      >
        <div class="field-card__header">
          <div class="field-card__drag" title="Geser">
            <GripVertical :size="16" />
          </div>

          <div class="field-card__body">
            <template v-if="field.type === 'heading'">
              <input
                class="field-heading-input"
                :value="field.label"
                @input="updateField(index, { label: ($event.target as HTMLInputElement).value })"
                placeholder="Judul section..."
                @click.stop
              />
            </template>
            <template v-else-if="field.type === 'paragraph'">
              <textarea
                class="field-paragraph-input"
                :value="field.label"
                @input="updateField(index, { label: ($event.target as HTMLInputElement).value })"
                placeholder="Teks deskripsi..."
                rows="2"
                @click.stop
              />
            </template>
            <template v-else-if="field.type === 'divider'">
              <hr class="field-divider" />
            </template>
            <template v-else>
              <input
                class="field-label-input"
                :value="field.label"
                :readonly="field.locked"
                @input="updateField(index, { label: ($event.target as HTMLInputElement).value })"
                :placeholder="field.locked ? '' : 'Pertanyaan...'"
                @click.stop
              />
            </template>
          </div>

          <div class="field-card__actions" v-if="!field.locked">
            <button class="field-action-btn" @click.stop="addField('text', index)" title="Tambah pertanyaan setelah ini">
              <Plus :size="15" />
            </button>
            <button class="field-action-btn field-action-btn--delete" @click.stop="removeField(index)" title="Hapus">
              <Trash2 :size="15" />
            </button>
            <button class="field-action-btn" @click.stop="duplicateField(index)" title="Duplikasi">
              <Copy :size="15" />
            </button>
          </div>
          <div v-else class="field-card__locked-badge">
            <Lock :size="13" /> Permanen
          </div>
        </div>

        <div v-if="isInput(field.type)" class="field-card__preview">
          <input v-if="field.type === 'text'" class="field-preview-input" placeholder="Jawaban singkat" disabled />
          <textarea v-else-if="field.type === 'textarea'" class="field-preview-textarea" placeholder="Jawaban panjang" disabled />
          <input v-else-if="field.type === 'number'" class="field-preview-input" type="number" placeholder="0" disabled />
          <input v-else-if="field.type === 'email'" class="field-preview-input" type="email" placeholder="email@contoh.com" disabled />
          <input v-else-if="field.type === 'phone'" class="field-preview-input" type="tel" placeholder="08xxxxxxxxxx" disabled />
          <input v-else-if="field.type === 'date'" class="field-preview-input" type="date" disabled />
          <input v-else-if="field.type === 'time'" class="field-preview-input" type="time" disabled />
          <div v-else-if="field.type === 'file'" class="field-preview-file">
            <FileIcon :size="14" />
            Upload file
          </div>

          <!-- Select -->
          <div v-else-if="field.type === 'select'" class="field-options" @click.stop>
            <div v-for="(opt, oi) in (field.options || [])" :key="oi" class="field-option" :class="{ 'field-option--other': opt.is_other }">
              <ChevronRight :size="14" class="field-option__bullet" />
              <input
                class="field-option__input"
                :value="opt.label"
                @input="updateOption(index, oi, { label: ($event.target as HTMLInputElement).value })"
                placeholder="Opsi..."
              />
              <label class="field-option__toggle" :title="opt.is_other ? 'Opsi isi manual' : 'Opsi pilihan'">
                <input type="checkbox" :checked="opt.is_other" @change="updateOption(index, oi, { is_other: ($event.target as HTMLInputElement).checked })" />
                <span class="field-option__toggle-text">isi manual</span>
              </label>
              <button class="field-option__remove" @click="removeOption(index, oi)" title="Hapus">
                <Trash2 :size="14" />
              </button>
            </div>
            <button class="field-option__add" @click="addOption(index)">+ Tambah opsi</button>
          </div>

          <!-- Radio -->
          <div v-else-if="field.type === 'radio'" class="field-options" @click.stop>
            <div v-for="(opt, oi) in (field.options || [])" :key="oi" class="field-option" :class="{ 'field-option--other': opt.is_other }">
              <CircleDot :size="14" class="field-option__bullet" />
              <input
                class="field-option__input"
                :value="opt.label"
                @input="updateOption(index, oi, { label: ($event.target as HTMLInputElement).value })"
                placeholder="Opsi..."
              />
              <label class="field-option__toggle" :title="opt.is_other ? 'Opsi isi manual' : 'Opsi pilihan'">
                <input type="checkbox" :checked="opt.is_other" @change="updateOption(index, oi, { is_other: ($event.target as HTMLInputElement).checked })" />
                <span class="field-option__toggle-text">isi manual</span>
              </label>
              <button class="field-option__remove" @click="removeOption(index, oi)" title="Hapus">
                <Trash2 :size="14" />
              </button>
            </div>
            <button class="field-option__add" @click="addOption(index)">+ Tambah opsi</button>
          </div>

          <!-- Checkbox -->
          <div v-else-if="field.type === 'checkbox'" class="field-options" @click.stop>
            <div v-for="(opt, oi) in (field.options || [])" :key="oi" class="field-option" :class="{ 'field-option--other': opt.is_other }">
              <CheckSquare :size="14" class="field-option__bullet" />
              <input
                class="field-option__input"
                :value="opt.label"
                @input="updateOption(index, oi, { label: ($event.target as HTMLInputElement).value })"
                placeholder="Opsi..."
              />
              <label class="field-option__toggle" :title="opt.is_other ? 'Opsi isi manual' : 'Opsi pilihan'">
                <input type="checkbox" :checked="opt.is_other" @change="updateOption(index, oi, { is_other: ($event.target as HTMLInputElement).checked })" />
                <span class="field-option__toggle-text">isi manual</span>
              </label>
              <button class="field-option__remove" @click="removeOption(index, oi)" title="Hapus">
                <Trash2 :size="14" />
              </button>
            </div>
            <button class="field-option__add" @click="addOption(index)">+ Tambah opsi</button>
          </div>
        </div>

        <div v-if="field.type === 'divider'" class="field-card__preview">
          <hr class="field-divider-preview" />
        </div>

        <div v-if="!field.locked" class="field-card__footer">
          <label class="field-toggle" @click.stop>
            <input type="checkbox" :checked="field.is_required" @change="updateField(index, { is_required: ($event.target as HTMLInputElement).checked })" />
            Wajib diisi
          </label>
          <div class="custom-type-picker" @click.stop="typePickerOpen = typePickerOpen === index ? null : index">
            <div class="custom-type-display">
              <component :is="fieldTypes.find(t => t.value === field.type)?.lucide" :size="14" />
              <span>{{ fieldTypes.find(t => t.value === field.type)?.label }}</span>
              <ChevronDown :size="14" class="custom-type-chevron" />
            </div>
            <div v-if="typePickerOpen === index" class="custom-type-dropdown" @click.stop>
              <div class="custom-type-group">Input</div>
              <div v-for="ft in fieldTypes.filter(t => t.group === 'input')" :key="ft.value" class="custom-type-option" :class="{'active': field.type === ft.value}" @click="changeFieldType(ft.value as FormField['type'], index)">
                <component :is="ft.lucide" :size="14" /> {{ ft.label }}
              </div>
              <div class="custom-type-group">Pilihan</div>
              <div v-for="ft in fieldTypes.filter(t => t.group === 'choice')" :key="ft.value" class="custom-type-option" :class="{'active': field.type === ft.value}" @click="changeFieldType(ft.value as FormField['type'], index)">
                <component :is="ft.lucide" :size="14" /> {{ ft.label }}
              </div>
              <div class="custom-type-group">Layout</div>
              <div v-for="ft in fieldTypes.filter(t => t.group === 'layout')" :key="ft.value" class="custom-type-option" :class="{'active': field.type === ft.value}" @click="changeFieldType(ft.value as FormField['type'], index)">
                <component :is="ft.lucide" :size="14" /> {{ ft.label }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Drop zone bawah -->
    <div
      v-if="fields.length > 0"
      class="drop-zone drop-zone--bottom"
      :class="{ 'drop-zone--active': dragOverIndex === fields.length && dragIndex !== null }"
      @dragenter="onDragEnter($event, fields.length)"
      @dragleave="onDragLeave"
      @dragover="onDragOver"
      @drop="onDrop($event, fields.length)"
    />

    <!-- Empty state -->
    <div v-if="fields.length === 0" class="add-area">
      <button class="add-btn" @click="addField('text', -1)">
        <Plus :size="18" />
        Tambah Pertanyaan
      </button>
    </div>

    <div v-if="fields.length === 0" class="empty-state">
      <FileQuestion :size="48" color="#cbd5e1" stroke-width="1.5" />
      <p>Belum ada field. Klik tombol di atas untuk menambahkan.</p>
    </div>
  </div>
</template>

<style scoped>
.form-builder { display: flex; flex-direction: column; gap: 0; }

.drop-zone { min-height: 8px; border-radius: 4px; transition: all 0.2s ease; margin: 0; }
.drop-zone--active { min-height: 48px; background: #eff6ff; border: 2px dashed #2563eb; border-radius: 8px; margin: 6px 0; }
.drop-zone--bottom { min-height: 20px; }

.field-card {
  background: #fff; border: 2px solid #e2e8f0; border-radius: 12px;
  padding: 16px; cursor: pointer; transition: all 0.15s ease;
}
.field-card:hover { border-color: #cbd5e1; }
.field-card--selected { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); position: relative; z-index: 10; }
.field-card--heading, .field-card--paragraph, .field-card--divider { border-style: dashed; background: #fafbfc; }
.field-card--dragging { opacity: 0.4; }
.field-card--locked { border-color: #e2e8f0; background: #fafbfc; }
.field-card--locked .field-label-input { color: #1e293b; cursor: default; }

.field-card__header { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 4px; }
.field-card__drag {
  width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;
  color: #cbd5e1; cursor: grab; flex-shrink: 0; border-radius: 4px; transition: all 0.15s;
  margin-top: 2px;
}
.field-card__drag:hover { color: #94a3b8; background: #f1f5f9; }
.field-card__drag:active { cursor: grabbing; }

.field-card__actions {
  display: flex;
  align-items: center;
  gap: 2px;
  flex-shrink: 0;
  opacity: 0;
  transition: opacity 0.15s;
}
.field-card:hover .field-card__actions { opacity: 1; }

.field-card__locked-badge {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  color: #94a3b8;
  padding: 4px 8px;
  background: #f1f5f9;
  border-radius: 6px;
  flex-shrink: 0;
}

.field-action-btn {
  width: 28px; height: 28px; border: none; border-radius: 6px; background: transparent;
  color: #94a3b8; cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: all 0.15s;
}
.field-action-btn:hover { background: #f1f5f9; color: #1e293b; }
.field-action-btn--delete:hover { color: #ef4444; background: #fef2f2; }

.field-card__body { flex: 1; }
.field-label-input {
  width: 100%; border: none; border-bottom: 1px solid #e2e8f0; padding: 4px 0;
  font-size: 15px; font-weight: 500; color: #1e293b; outline: none; background: transparent;
}
.field-label-input:focus { border-bottom-color: #2563eb; }
.field-label-input::placeholder { color: #94a3b8; }

.field-heading-input {
  width: 100%; border: none; padding: 4px 0; font-size: 18px; font-weight: 700;
  color: #1e293b; outline: none; background: transparent;
}
.field-heading-input:focus { border-bottom: 2px solid #2563eb; }
.field-heading-input::placeholder { color: #94a3b8; }

.field-paragraph-input {
  width: 100%; border: none; padding: 4px 0; font-size: 13px; color: #475569;
  outline: none; background: transparent; resize: none; line-height: 1.6;
}
.field-paragraph-input:focus { border-bottom: 1px solid #2563eb; }
.field-paragraph-input::placeholder { color: #94a3b8; }

.field-divider { border: none; border-top: 2px solid #e2e8f0; margin: 8px 0; }
.field-divider-preview { border: none; border-top: 2px solid #e2e8f0; margin: 8px 0; }

.field-divider { border: none; border-top: 2px solid #e2e8f0; margin: 8px 0; }
.field-divider-preview { border: none; border-top: 2px solid #e2e8f0; margin: 8px 0; }

.field-card__preview { padding: 4px 0 4px 34px; }
.field-preview-input, .field-preview-textarea {
  width: 100%; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px;
  font-size: 13px; color: #64748b; background: #fafbfc; resize: none;
}
.field-preview-textarea { min-height: 60px; }
.field-preview-file {
  display: flex; align-items: center; gap: 6px; font-size: 13px; color: #64748b;
  padding: 8px 10px; border: 1px dashed #e2e8f0; border-radius: 6px; background: #fafbfc;
}

.field-options { display: flex; flex-direction: column; gap: 6px; }
.field-option { display: flex; align-items: center; gap: 8px; padding: 4px 0; }
.field-option--other { background: #f0fdf4; border-radius: 6px; padding: 4px 6px; margin: 0 -6px; }
.field-option__bullet { width: 14px; height: 14px; flex-shrink: 0; color: #94a3b8; }
.field-option__input {
  flex: 1; border: none; border-bottom: 1px solid #e2e8f0; padding: 4px 0;
  font-size: 13px; color: #1e293b; outline: none; background: transparent;
}
.field-option__input:focus { border-bottom-color: #2563eb; }
.field-option__input::placeholder { color: #cbd5e1; }
.field-option__toggle {
  display: flex; align-items: center; gap: 4px; cursor: pointer; flex-shrink: 0;
}
.field-option__toggle input { accent-color: #2563eb; width: 14px; height: 14px; }
.field-option__toggle-text { font-size: 10px; color: #94a3b8; white-space: nowrap; }
.field-option__remove {
  width: 24px; height: 24px; border: none; border-radius: 4px; background: transparent;
  color: #94a3b8; cursor: pointer; display: flex; align-items: center; justify-content: center;
  opacity: 0; transition: all 0.15s;
}
.field-option:hover .field-option__remove { opacity: 1; }
.field-option__remove:hover { color: #ef4444; background: #fef2f2; }
.field-option__add {
  align-self: flex-start; border: none; background: none; padding: 4px 0;
  font-size: 13px; color: #2563eb; cursor: pointer; font-weight: 500;
}
.field-option__add:hover { text-decoration: underline; }

.field-card__footer {
  display: flex; align-items: center; justify-content: space-between;
  padding-top: 8px; border-top: 1px solid #f1f5f9; margin-top: 8px;
}
.field-toggle { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; cursor: pointer; }
.field-toggle input { accent-color: #2563eb; }
.custom-type-picker {
  position: relative;
  font-size: 11px;
  color: #475569;
}
.custom-type-display {
  display: flex;
  align-items: center;
  gap: 6px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 4px 8px;
  background: #fff;
  cursor: pointer;
  user-select: none;
}
.custom-type-display:hover {
  border-color: #cbd5e1;
}
.custom-type-chevron {
  color: #94a3b8;
  margin-left: 4px;
}
.custom-type-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  right: 0;
  width: 160px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  z-index: 50;
  padding: 6px 0;
  max-height: 240px;
  overflow-y: auto;
}
.custom-type-group {
  padding: 4px 12px;
  font-size: 10px;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
}
.custom-type-option {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  cursor: pointer;
  color: #334155;
  transition: background 0.15s;
}
.custom-type-option:hover {
  background: #f8fafc;
}
.custom-type-option.active {
  background: #eff6ff;
  color: #2563eb;
}

/* Empty state */
.add-area { display: flex; justify-content: center; padding: 8px 0; }
.add-btn {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 20px; border: 2px dashed #d1d5db; border-radius: 10px;
  background: #fafbfc; color: #64748b; font-size: 14px; font-weight: 500;
  cursor: pointer; transition: all 0.15s;
}
.add-btn:hover { border-color: #2563eb; color: #2563eb; background: #eff6ff; }

.empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; }
.empty-state p { margin-top: 12px; font-size: 14px; }

@media (max-width: 640px) {
  .field-card__footer {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  .field-card__actions {
    opacity: 1;
  }
}
</style>
