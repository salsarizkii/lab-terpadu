<script setup lang="ts">
import { ref, watch } from 'vue'
import Button from '../common/Button.vue'
import type { User } from '../../types'

const props = withDefaults(defineProps<{
  users: User[]
  modelValue?: number[]
  loading?: boolean
  submitLabel?: string
}>(), {
  submitLabel: 'Assign',
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
</script>

<template>
 <div class="assign">
   <div v-if="!users.length" class="assign__empty">
     Tidak ada pengguna tersedia
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
       <div class="assign__info">
         <span class="assign__name">{{ u.name }}</span>
         <span class="assign__email">{{ u.email }}</span>
       </div>
     </label>
   </div>
   <div class="assign__footer">
     <span class="assign__count">{{ selectedIds.size }} dipilih</span>
     <Button :disabled="props.loading || selectedIds.size === 0" size="sm" :loading="props.loading" @click="handleAssign">
       {{ props.loading ? 'Menyimpan...' : submitLabel }}
     </Button>
   </div>
 </div>
</template>

<style scoped>
.assign__empty {
  padding: 16px;
  text-align: center;
  font-size: 13px;
  color: #94a3b8;
}
.assign__list {
  display: flex;
  flex-direction: column;
  gap: 4px;
  max-height: 280px;
  overflow-y: auto;
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
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.1s;
  }

.assign__item:hover {
  background: #f1f5f9;
  }

.assign__item--selected {
  background: #eff6ff;
  }

.assign__checkbox {
  width: 16px;
  height: 16px;
  accent-color: #2563eb;
  cursor: pointer;
  }

.assign__info {
  display: flex;
  flex-direction: column;
  gap: 1px;
  }

.assign__name {
  font-size: 13px;
  font-weight: 500;
  color: #1e293b;
  }

.assign__email {
  font-size: 11px;
  color: #94a3b8;
  }

.assign__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 12px;
  padding-top: 10px;
  border-top: 1px solid #e2e8f0;
  }

.assign__count {
  font-size: 12px;
  color: #94a3b8;
  }
</style>
