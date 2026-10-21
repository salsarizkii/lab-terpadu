<script setup lang="ts">
import { ref, watch } from 'vue'
import Button from '../common/Button.vue'
import type { User } from '../../types'

const props = defineProps<{
 beritaAcaraId: number
 analysts: User[]
 modelValue?: number[]
 loading?: boolean
}>()

const emit = defineEmits<{
 assign: [analystIds: number[]]
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

function initials(name: string): string {
  return name
    .split(/\s+/)
    .map(w => w.charAt(0))
    .filter(Boolean)
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

function handleAssign() {
 if (selectedIds.value.size === 0) return
 emit('assign', Array.from(selectedIds.value))
}
</script>

<template>
  <div class="assign">
    <p class="assign__hint">Pilih satu atau lebih analis untuk mengerjakan berita acara ini.</p>
    <div class="assign__list">
      <label
        v-for="a in analysts"
        :key="a.id"
        class="assign__item"
        :class="{ 'assign__item--selected': selectedIds.has(a.id) }"
      >
        <input
          type="checkbox"
          :checked="selectedIds.has(a.id)"
          class="assign__checkbox"
          :disabled="props.loading"
          @change="toggle(a.id)"
        />
        <span class="assign__avatar" :class="{ 'assign__avatar--selected': selectedIds.has(a.id) }">
          {{ initials(a.name) }}
        </span>
        <div class="assign__info">
          <span class="assign__name">{{ a.name }}</span>
          <span class="assign__email">{{ a.email }}</span>
        </div>
        <span class="assign__tick" :class="{ 'assign__tick--selected': selectedIds.has(a.id) }">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        </span>
      </label>
    </div>
    <div class="assign__footer">
      <span class="assign__count"><strong>{{ selectedIds.size }}</strong> analis dipilih</span>
      <Button :disabled="props.loading || selectedIds.size === 0" size="sm" :loading="props.loading" @click="handleAssign">
        {{ props.loading ? 'Menyimpan...' : 'Assign' }}
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
