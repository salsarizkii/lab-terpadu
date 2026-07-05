<script setup lang="ts">
import Skeleton from './Skeleton.vue'

withDefaults(defineProps<{
 rows?: number
 cols?: number
}>(), {
 rows: 5,
 cols: 5,
})
</script>

<template>
 <div class="sk-table">
   <div class="sk-table__head">
     <Skeleton v-for="c in cols" :key="c" height="14px" />
   </div>
   <div v-for="r in rows" :key="r" class="sk-table__row">
     <Skeleton v-for="c in cols" :key="c" :height="r === rows ? '14px' : '14px'" />
   </div>
 </div>
</template>

<style scoped>
.sk-table {
 width: 100%;
 }
.sk-table__head {
 display: grid;
 gap: 12px;
 padding: 12px 16px;
 background: #f8fafc;
 border-bottom: 2px solid #e2e8f0;
 border-radius: 8px 8px 0 0;
 }
.sk-table__row {
 display: grid;
 gap: 12px;
 padding: 14px 16px;
 border-bottom: 1px solid #f1f5f9;
 }
.sk-table__row:last-child {
 border-bottom: none;
 }
</style>
