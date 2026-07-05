<script setup lang="ts">
import Skeleton from './Skeleton.vue'

withDefaults(defineProps<{
 title?: boolean
 rows?: number
}>(), {
 title: true,
 rows: 3,
})
</script>

<template>
 <div class="sk-card">
   <Skeleton v-if="title" width="40%" height="18px" style="margin-bottom: 16px" />
   <div v-for="r in rows" :key="r" class="sk-card__row">
     <Skeleton width="35%" height="13px" />
     <Skeleton width="60%" height="13px" />
   </div>
 </div>
</template>

<style scoped>
.sk-card {
 padding: 20px;
 }
.sk-card__row {
 display: flex;
 justify-content: space-between;
 padding: 8px 0;
 border-bottom: 1px solid #f1f5f9;
 }
.sk-card__row:last-child {
 border-bottom: none;
 }
</style>
