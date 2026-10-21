<script setup lang="ts">
import { ref, watch } from 'vue'
import Button from '../common/Button.vue'
import type { User } from '../../types'

const props = withDefaults(defineProps<{
  users: User[]
  modelValue?: number[]
  loading?: boolean
  submitLabel?: string
  hint?: string
}>(), {
  submitLabel: 'Assign',
  hint: 'Pilih satu atau lebih pengguna untuk ditugaskan.',
})

const emit = defineEmits<{
  assign: [ids: number[]]
  'update:modelValue': [ids: number[]]
}>()

const selectedIds = ref<Set<number>>(new Set(props.modelValue || []))

watch(() => props.modelValue, (val) => {
  if (val) selectedIds.value = new Set(val)
}, { immediate: true })

function toggle(id: number) {
  if (selectedIds.value.has(id)) {
    selectedIds.value.delete(id)
  } else {
    selectedIds.value.add(id)
  }
  emit('update:modelValue', Array.from(selectedIds.value))
}

function handleAssign() {
  if (selectedIds.value.size === 0) return
  emit('assign', Array.from(selectedIds.value))
}

function initials(name: string): string {
  return name
    .split(/\s+/)
    .map(w => w.charAt(0))
    .filter(Boolean)
    .slice(0, 2)
    .join('')
    .toUpperCase()
}
</script>

<template>
  <div class="assign">
    <p class="assign__hint">{{ hint }}</p>
    <div v-if="!users.length" class="assign__empty">
      <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      <span>Tidak ada pengguna tersedia</span>
    </div>
    <div v-else class="assign__list">
      <label
        v-for="u in users"
        :key="u.id"
        class="assign__item"
        :class="{ 'assign__item--selected': selectedIds.has(u.id) }"
      >
        <input
          type="checkbox"
          :checked="selectedIds.has(u.id)"
          class="assign__checkbox"
          :disabled="props.loading"
          @change="toggle(u.id)"
        />
        <span class="assign__avatar" :class="{ 'assign__avatar--selected': selectedIds.has(u.id) }">
          {{ initials(u.name) }}
        </span>
        <div class="assign__info">
          <span class="assign__name">{{ u.name }}</span>
          <span class="assign__email">{{ u.email }}</span>
        </div>
        <span class="assign__tick" :class="{ 'assign__tick--selected': selectedIds.has(u.id) }">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        </span>
      </label>
    </div>
    <div class="assign__footer">
      <span class="assign__count"><strong>{{ selectedIds.size }}</strong> dipilih</span>
      <Button :disabled="props.loading || selectedIds.size === 0" size="sm" :loading="props.loading" @click="handleAssign">
        {{ props.loading ? 'Menyimpan...' : submitLabel }}
      </Button>
    </div>
  </div>
</template>

<style scoped>
.assign__hint {
  font-size: 12.5px;
  color: var(--color-text-muted);
  margin: 0 0 10px;
}
.assign__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 24px 16px;
  text-align: center;
  font-size: 13px;
  color: var(--color-text-muted);
  border: 1.5px dashed var(--color-border-strong);
  border-radius: var(--radius-md);
}
.assign__list {
  display: flex;
  flex-direction: column;
  gap: 6px;
  max-height: 280px;
  overflow-y: auto;
  padding-right: 2px;
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 transparent;
  }

.assign__list::-webkit-scrollbar {
  width: 5px;
  }

.assign__list::-webkit-scrollbar-track {
  background: transparent;
  }

.assign__list::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
  }

.assign__item {
  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 9px 12px;
  border: 1.5px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-surface);
  cursor: pointer;
  transition: var(--transition);
  }

.assign__item:hover {
  border-color: var(--color-accent-border);
  }

.assign__item:focus-within {
  box-shadow: 0 0 0 3px var(--color-accent-soft);
  }

.assign__item--selected {
  border-color: var(--color-accent);
  background: var(--color-accent-soft);
  }

.assign__checkbox {
  position: absolute;
  opacity: 0;
  pointer-events: none;
  }

.assign__avatar {
  flex-shrink: 0;
  width: 34px;
  height: 34px;
  border-radius: var(--radius-full);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11.5px;
  font-weight: 700;
  letter-spacing: 0.4px;
  background: linear-gradient(135deg, #dbeafe, #e0e7ff);
  color: var(--color-accent-hover);
  transition: var(--transition);
  }

.assign__avatar--selected {
  background: linear-gradient(135deg, var(--color-accent), var(--color-info));
  color: #fff;
  }

.assign__info {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
  }

.assign__name {
  font-size: 13.5px;
  font-weight: 600;
  color: var(--color-text);
  }

.assign__email {
  font-size: 11.5px;
  color: var(--color-text-muted);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  }

.assign__tick {
  margin-left: auto;
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  border-radius: var(--radius-full);
  border: 1.5px solid var(--color-border-strong);
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-surface);
  color: transparent;
  transition: var(--transition);
  }

.assign__tick--selected {
  background: var(--color-accent);
  border-color: var(--color-accent);
  color: #fff;
  }

.assign__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-top: 12px;
  padding-top: 14px;
  border-top: 1px solid var(--color-border-light);
  }

.assign__count {
  font-size: 12.5px;
  color: var(--color-text-muted);
  }

.assign__count strong {
  color: var(--color-accent);
  font-weight: 700;
  }
</style>