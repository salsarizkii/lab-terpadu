<script setup lang="ts">
defineProps<{
 icon?: any
 variant?: 'default' | 'danger'
 disabled?: boolean
}>()

defineEmits<{
 click: [e: MouseEvent]
}>()
</script>

<template>
 <button
   class="dd-item"
   :class="[`dd-item--${variant || 'default'}`, { 'dd-item--disabled': disabled }]"
   :disabled="disabled"
   @click="$emit('click', $event)"
 >
   <component v-if="icon" :is="icon" :size="15" class="dd-item__icon" />
   <slot />
 </button>
</template>

<style scoped>
.dd-item {
 display: flex;
 align-items: center;
 gap: 8px;
 width: 100%;
 padding: 8px 12px;
 border: none;
 background: none;
 border-radius: 6px;
 font-size: 13px;
 font-family: inherit;
 color: #334155;
 cursor: pointer;
 text-align: left;
 transition: background 0.1s;
 white-space: nowrap;
 }

.dd-item:hover {
 background: #f1f5f9;
 }

.dd-item--danger {
 color: #dc2626;
 }

.dd-item--danger:hover {
 background: #fef2f2;
 }

.dd-item--disabled {
 opacity: 0.4;
 cursor: not-allowed;
 }

.dd-item--disabled:hover {
 background: none;
 }

.dd-item__icon {
 flex-shrink: 0;
 }
</style>
